<?php

use App\Events\NotificationCreatedEvent;
use App\Livewire\Sale\OnlinePayments;
use App\Livewire\Sale\View as SaleView;
use App\Models\Account;
use App\Models\Configuration;
use App\Models\Permission;
use App\Models\Sale;
use App\Models\SaleDaySession;
use App\Models\StorefrontCheckout;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\OnlineSaleNotification;
use App\Support\Storefront\TapSettings;
use App\Support\TenantCache;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\Support\PosWorld;

/**
 * Storefront checkout through Tap's hosted payment page.
 *
 * Tap is faked at the HTTP layer. What these pin down: the browser never sets a
 * price, a captured charge becomes exactly one completed sale however many times
 * it is reported, and nothing but a CAPTURED charge — as Tap itself reports it
 * when asked with the secret key — ever records one.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create(stock: 5, price: 250);
    $this->tapAccountId = $this->world->addPaymentMethod('Tap Payments');

    $tenantId = $this->world->tenant->id;
    Configuration::create(['tenant_id' => $tenantId, 'key' => 'base_currency_code', 'value' => 'QAR']);
    Configuration::create(['tenant_id' => $tenantId, 'key' => TapSettings::SECRET_KEY, 'value' => TapSettings::encryptSecret('sk_test_fake')]);
    Configuration::create(['tenant_id' => $tenantId, 'key' => TapSettings::KEY, 'value' => json_encode([
        'enabled' => true,
        'payment_account_id' => $this->tapAccountId,
        'user_id' => $this->world->user->id,
        'delivery_branch_id' => null,
    ])]);
    TenantCache::forget('base_currency_code');
});

/** A Tap charge body; [$overrides] merge recursively. */
function storefrontTapCharge(array $overrides = []): array
{
    return array_replace_recursive([
        'id' => 'chg_TS_test_0001',
        'object' => 'charge',
        'status' => 'INITIATED',
        'amount' => 500,
        'currency' => 'QAR',
        'transaction' => ['url' => 'https://checkout.payments.tap.company/?mode=page&token=abc'],
        'response' => ['code' => '100', 'message' => 'Initiated'],
    ], $overrides);
}

/** Creating a charge returns it INITIATED; retrieving it reports [$status] for [$amount]. */
function storefrontFakeTap(string $status, float $amount = 500): void
{
    Http::fake(fn (Request $request) => $request->method() === 'POST'
        ? Http::response(storefrontTapCharge())
        : Http::response(storefrontTapCharge(['status' => $status, 'amount' => $amount])));
}

function storefrontCheckoutPayload(PosWorld $world, array $overrides = []): array
{
    return array_merge([
        'fulfilment' => 'pickup',
        'branchId' => $world->branch->id,
        'customerName' => 'Aisha Khan',
        'customerEmail' => 'aisha@example.com',
        'countryCode' => '974',
        'customerMobile' => '55123456',
        'items' => [['productId' => $world->product->id, 'quantity' => 2]],
        'returnUrl' => 'https://shop.example/site/#/product/1',
    ], $overrides);
}

function storefrontStockAt(PosWorld $world, int $branchId): float
{
    return (float) DB::table('inventories')->where('product_id', $world->product->id)->where('branch_id', $branchId)->value('quantity');
}

it('prices the bag from the catalogue and opens a Tap charge', function (): void {
    storefrontFakeTap('INITIATED');

    $response = $this->postJson($this->world->url('/api/v1/storefront/checkout'), storefrontCheckoutPayload($this->world, [
        // A price from the browser is not part of the contract and changes nothing.
        'items' => [['productId' => $this->world->product->id, 'quantity' => 2, 'unitPrice' => 1]],
    ]));

    $response->assertCreated()
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.payment_url', 'https://checkout.payments.tap.company/?mode=page&token=abc');
    expect($response->json('data.amount'))->toEqual(500);

    $checkout = StorefrontCheckout::withoutGlobalScopes()->sole();
    expect((float) $checkout->amount)->toBe(500.0)
        ->and($checkout->gateway_charge_id)->toBe('chg_TS_test_0001');

    Http::assertSent(fn (Request $request) => $request->method() === 'POST'
        && $request->url() === 'https://api.tap.company/v2/charges'
        && $request->hasHeader('Authorization', 'Bearer sk_test_fake')
        && $request['amount'] == 500
        && $request['currency'] === 'QAR'
        && $request['source']['id'] === 'src_all'
        // The #route is dropped so Tap's ?tap_id lands in the real query string.
        && $request['redirect']['url'] === 'https://shop.example/site/?checkout='.$checkout->reference
        && str_contains($request['post']['url'], '/api/v1/storefront/checkout/tap-webhook'));

    // Nothing is paid yet, so nothing is sold and no stock has moved.
    expect(Sale::withoutGlobalScopes()->count())->toBe(0)
        ->and(storefrontStockAt($this->world, $this->world->branch->id))->toBe(5.0);
});

it('refuses more pairs than the chosen shop holds', function (): void {
    Http::fake();

    $this->postJson($this->world->url('/api/v1/storefront/checkout'), storefrontCheckoutPayload($this->world, [
        'items' => [['productId' => $this->world->product->id, 'quantity' => 6]],
    ]))->assertUnprocessable()->assertJsonPath('success', false);

    Http::assertNothingSent();
    expect(StorefrontCheckout::withoutGlobalScopes()->count())->toBe(0);
});

it('will not collect from a branch hidden from the showcase', function (): void {
    Http::fake();
    $hidden = $this->world->addBranch('Warehouse', 'WH');
    $hidden->update(['exclude_from_showcase' => true]);

    $this->postJson($this->world->url('/api/v1/storefront/checkout'), storefrontCheckoutPayload($this->world, [
        'branchId' => $hidden->id,
    ]))->assertUnprocessable();

    Http::assertNothingSent();
});

it('is unavailable until online payments are switched on', function (): void {
    Http::fake();
    Configuration::where('key', TapSettings::KEY)->update(['value' => json_encode(['enabled' => false])]);

    $this->getJson($this->world->url('/api/v1/storefront/checkout/config'))
        ->assertSuccessful()
        ->assertJsonPath('data.enabled', false);

    $this->postJson($this->world->url('/api/v1/storefront/checkout'), storefrontCheckoutPayload($this->world))
        ->assertUnprocessable();

    Http::assertNothingSent();
});

it('records exactly one completed sale for a captured charge, however often it is reported', function (): void {
    storefrontFakeTap('CAPTURED');

    $reference = $this->postJson($this->world->url('/api/v1/storefront/checkout'), storefrontCheckoutPayload($this->world))
        ->assertCreated()
        ->json('data.reference');

    // The customer lands back, Tap's webhook arrives, and the page is reloaded.
    $this->getJson($this->world->url("/api/v1/storefront/checkout/{$reference}?tap_id=chg_TS_test_0001"))
        ->assertSuccessful()
        ->assertJsonPath('data.status', 'paid')
        ->assertJsonPath('data.payment_url', null);
    $this->postJson($this->world->url('/api/v1/storefront/checkout/tap-webhook'), ['id' => 'chg_TS_test_0001', 'status' => 'CAPTURED'])
        ->assertSuccessful();
    $reload = $this->getJson($this->world->url("/api/v1/storefront/checkout/{$reference}"))->assertSuccessful();

    $sale = Sale::withoutGlobalScopes()->sole();
    expect($sale->status)->toBe('completed')
        ->and($sale->source)->toBe('storefront')
        ->and($sale->branch_id)->toBe($this->world->branch->id)
        ->and((float) $sale->grand_total)->toBe(500.0)
        ->and((float) $sale->paid)->toBe(500.0)
        ->and($sale->reference_no)->toBe('chg_TS_test_0001');

    expect(DB::table('sale_payments')->where('sale_id', $sale->id)->pluck('payment_method_id')->all())->toBe([$this->tapAccountId])
        ->and(storefrontStockAt($this->world, $this->world->branch->id))->toBe(3.0);

    $checkout = StorefrontCheckout::withoutGlobalScopes()->sole();
    expect($checkout->status)->toBe('paid')
        ->and($checkout->sale_id)->toBe($sale->id);

    $reload->assertJsonPath('data.invoice_no', $sale->invoice_no);
});

it('leaves no sale behind when the payment is declined', function (): void {
    storefrontFakeTap('DECLINED');

    $reference = $this->postJson($this->world->url('/api/v1/storefront/checkout'), storefrontCheckoutPayload($this->world))->json('data.reference');

    $this->getJson($this->world->url("/api/v1/storefront/checkout/{$reference}"))
        ->assertSuccessful()
        ->assertJsonPath('data.status', 'failed')
        ->assertJsonPath('data.gateway_status', 'DECLINED');

    expect(Sale::withoutGlobalScopes()->count())->toBe(0)
        ->and(storefrontStockAt($this->world, $this->world->branch->id))->toBe(5.0);
});

it('keeps waiting while the customer is still on the payment page', function (): void {
    storefrontFakeTap('INITIATED');

    $reference = $this->postJson($this->world->url('/api/v1/storefront/checkout'), storefrontCheckoutPayload($this->world))->json('data.reference');

    $this->getJson($this->world->url("/api/v1/storefront/checkout/{$reference}"))
        ->assertSuccessful()
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.payment_url', 'https://checkout.payments.tap.company/?mode=page&token=abc');

    expect(Sale::withoutGlobalScopes()->count())->toBe(0);
});

it('takes the outcome from Tap, not from the webhook body', function (): void {
    storefrontFakeTap('INITIATED');

    $this->postJson($this->world->url('/api/v1/storefront/checkout'), storefrontCheckoutPayload($this->world))->assertCreated();

    // A forged "CAPTURED" post: the charge is re-read from Tap, which says INITIATED.
    $this->postJson($this->world->url('/api/v1/storefront/checkout/tap-webhook'), [
        'id' => 'chg_TS_test_0001',
        'status' => 'CAPTURED',
        'amount' => 500,
    ])->assertSuccessful();

    expect(Sale::withoutGlobalScopes()->count())->toBe(0)
        ->and(StorefrontCheckout::withoutGlobalScopes()->sole()->status)->toBe('pending');
});

it('ignores a webhook for a charge it did not start', function (): void {
    Http::fake();

    $this->postJson($this->world->url('/api/v1/storefront/checkout/tap-webhook'), ['id' => 'chg_someone_else'])
        ->assertSuccessful()
        ->assertJsonPath('message', 'Ignored');

    Http::assertNothingSent();
});

it('holds a captured charge for review instead of selling at a different amount', function (): void {
    storefrontFakeTap('CAPTURED', amount: 1);

    $reference = $this->postJson($this->world->url('/api/v1/storefront/checkout'), storefrontCheckoutPayload($this->world))->json('data.reference');

    $this->getJson($this->world->url("/api/v1/storefront/checkout/{$reference}"))
        ->assertSuccessful()
        ->assertJsonPath('data.status', 'review');

    expect(Sale::withoutGlobalScopes()->count())->toBe(0)
        ->and(StorefrontCheckout::withoutGlobalScopes()->sole()->failure_reason)->toContain('does not match');
});

it('rolls a half-recorded sale back but keeps the paid checkout for review', function (): void {
    storefrontFakeTap('CAPTURED', amount: 400);

    $reference = $this->postJson($this->world->url('/api/v1/storefront/checkout'), storefrontCheckoutPayload($this->world))->json('data.reference');

    // The capture matches the checkout, but the lines (2 × 250) do not — so the sale
    // is written in full (items, stock, journal) and only then refused.
    StorefrontCheckout::withoutGlobalScopes()->where('reference', $reference)->update(['amount' => 400]);

    $this->getJson($this->world->url("/api/v1/storefront/checkout/{$reference}"))
        ->assertSuccessful()
        ->assertJsonPath('data.status', 'review');

    expect(Sale::withoutGlobalScopes()->withTrashed()->count())->toBe(0)
        ->and(DB::table('sale_items')->count())->toBe(0)
        ->and(storefrontStockAt($this->world, $this->world->branch->id))->toBe(5.0);

    $checkout = StorefrontCheckout::withoutGlobalScopes()->sole();
    expect($checkout->sale_id)->toBeNull()
        ->and($checkout->gateway_status)->toBe('CAPTURED');
});

it('ships delivery orders from the configured branch', function (): void {
    storefrontFakeTap('CAPTURED');
    $warehouse = $this->world->addBranch('Warehouse', 'WH');
    Configuration::where('key', TapSettings::KEY)->update(['value' => json_encode([
        'enabled' => true,
        'payment_account_id' => $this->tapAccountId,
        'user_id' => $this->world->user->id,
        'delivery_branch_id' => $warehouse->id,
    ])]);
    // The customer picked building 12 from the QNAS list, so its position is cached.
    Cache::put('qnas:buildings:56:340', [['number' => '12', 'lat' => 25.2854473, 'lng' => 51.5310398]]);

    $reference = $this->postJson($this->world->url('/api/v1/storefront/checkout'), storefrontCheckoutPayload($this->world, [
        'fulfilment' => 'delivery',
        'branchId' => null,
        'zoneNumber' => '56',
        'streetNumber' => '340',
        'buildingNumber' => '12',
        'city' => 'Doha',
    ]))->assertCreated()->json('data.reference');

    $this->getJson($this->world->url("/api/v1/storefront/checkout/{$reference}"))->assertJsonPath('data.status', 'paid');

    $checkout = StorefrontCheckout::withoutGlobalScopes()->sole();
    expect($checkout->zone_number)->toBe('56')
        ->and($checkout->street_number)->toBe('340')
        ->and($checkout->building_number)->toBe('12')
        ->and($checkout->city)->toBe('Doha')
        ->and($checkout->latitude)->toBe(25.2854473)
        ->and($checkout->longitude)->toBe(51.5310398);

    $sale = Sale::withoutGlobalScopes()->sole();
    expect($sale->branch_id)->toBe($warehouse->id)
        ->and($sale->address)->toBe('Zone 56, Street 340, Building 12, Doha')
        ->and(storefrontStockAt($this->world, $warehouse->id))->toBe(98.0);
});

it('needs every part of the delivery address', function (): void {
    Http::fake();

    $this->postJson($this->world->url('/api/v1/storefront/checkout'), storefrontCheckoutPayload($this->world, [
        'fulfilment' => 'delivery',
        'branchId' => null,
        'zoneNumber' => '56',
        'city' => 'Doha',
    ]))->assertUnprocessable()->assertJsonValidationErrors(['streetNumber', 'buildingNumber']);

    Http::assertNothingSent();
});

it('refuses delivery when the store ships from nowhere', function (): void {
    Http::fake();

    $this->postJson($this->world->url('/api/v1/storefront/checkout'), storefrontCheckoutPayload($this->world, [
        'fulfilment' => 'delivery',
        'branchId' => null,
        'zoneNumber' => '56',
        'streetNumber' => '340',
        'buildingNumber' => '12',
        'city' => 'Doha',
    ]))->assertUnprocessable();

    Http::assertNothingSent();
});

/** Point "Delivery orders ship from" at [$branchId]. */
function storefrontShipFrom(object $test, int $branchId): void
{
    Configuration::where('key', TapSettings::KEY)->update(['value' => json_encode([
        'enabled' => true,
        'payment_account_id' => $test->tapAccountId,
        'user_id' => $test->world->user->id,
        'delivery_branch_id' => $branchId,
    ])]);
}

it('transfers a paid pickup order to the online branch and books the sale there', function (): void {
    storefrontFakeTap('CAPTURED');
    $online = $this->world->addBranch('Online', 'ON');
    storefrontShipFrom($this, $online->id);

    $reference = $this->postJson($this->world->url('/api/v1/storefront/checkout'), storefrontCheckoutPayload($this->world))
        ->assertCreated()
        ->json('data.reference');

    // The shop the customer collects from is kept on the checkout.
    expect(StorefrontCheckout::withoutGlobalScopes()->sole()->branch_id)->toBe($this->world->branch->id);

    $this->getJson($this->world->url("/api/v1/storefront/checkout/{$reference}"))->assertJsonPath('data.status', 'paid');

    $transfer = DB::table('inventory_transfers')->sole();
    expect($transfer->from_branch_id)->toBe($this->world->branch->id)
        ->and($transfer->to_branch_id)->toBe($online->id)
        ->and($transfer->status)->toBe('completed')
        ->and(DB::table('inventory_transfer_items')->where('inventory_transfer_id', $transfer->id)->value('quantity'))->toEqual(2);

    $sale = Sale::withoutGlobalScopes()->sole();
    expect($sale->branch_id)->toBe($online->id)
        ->and(DB::table('sale_items')->where('sale_id', $sale->id)->value('inventory_id'))
        ->toBe(DB::table('inventories')->where('branch_id', $online->id)->where('product_id', $this->world->product->id)->value('id'))
        // Out of the shop, through the online branch, and sold: 5 − 2 there, 100 + 2 − 2 here.
        ->and(storefrontStockAt($this->world, $this->world->branch->id))->toBe(3.0)
        ->and(storefrontStockAt($this->world, $online->id))->toBe(100.0);
});

it('takes delivery stock from a shop when the online branch cannot fill the bag', function (): void {
    storefrontFakeTap('CAPTURED');
    $online = $this->world->addBranch('Online', 'ON');
    DB::table('inventories')->where('branch_id', $online->id)->update(['quantity' => 0]);
    storefrontShipFrom($this, $online->id);

    $reference = $this->postJson($this->world->url('/api/v1/storefront/checkout'), storefrontCheckoutPayload($this->world, [
        'fulfilment' => 'delivery',
        'branchId' => null,
        'zoneNumber' => '56',
        'streetNumber' => '340',
        'buildingNumber' => '12',
        'city' => 'Doha',
    ]))->assertCreated()->json('data.reference');

    expect(StorefrontCheckout::withoutGlobalScopes()->sole()->branch_id)->toBe($this->world->branch->id);

    $this->getJson($this->world->url("/api/v1/storefront/checkout/{$reference}"))->assertJsonPath('data.status', 'paid');

    expect(Sale::withoutGlobalScopes()->sole()->branch_id)->toBe($online->id)
        ->and(storefrontStockAt($this->world, $this->world->branch->id))->toBe(3.0)
        ->and(storefrontStockAt($this->world, $online->id))->toBe(0.0);
});

it('dates an online sale today and keeps it out of the open day session', function (): void {
    storefrontFakeTap('CAPTURED');
    $session = SaleDaySession::create([
        'tenant_id' => $this->world->tenant->id,
        'branch_id' => $this->world->branch->id,
        'opened_by' => $this->world->user->id,
        'opened_at' => now()->subDay(),
        'opening_amount' => 0,
        'status' => 'open',
    ]);

    $reference = $this->postJson($this->world->url('/api/v1/storefront/checkout'), storefrontCheckoutPayload($this->world))->json('data.reference');
    $this->getJson($this->world->url("/api/v1/storefront/checkout/{$reference}"))->assertJsonPath('data.status', 'paid');

    $sale = Sale::withoutGlobalScopes()->sole();
    expect($sale->date)->toBe(today()->toDateString())
        ->and($sale->sale_day_session_id)->toBeNull()
        ->and($session->fresh()->status)->toBe('open');
});

it('notifies the shop once when an online order is paid', function (): void {
    Event::fake([NotificationCreatedEvent::class]);
    storefrontFakeTap('CAPTURED');

    $this->world->user->update(['is_browser_notification_enabled' => true]);
    $quietAdmin = User::factory()->create(['tenant_id' => $this->world->tenant->id, 'is_admin' => 1, 'is_browser_notification_enabled' => false]);
    $cashier = User::factory()->create(['tenant_id' => $this->world->tenant->id, 'is_admin' => 0, 'is_browser_notification_enabled' => true]);
    $onlineDesk = User::factory()->create(['tenant_id' => $this->world->tenant->id, 'is_admin' => 0, 'is_browser_notification_enabled' => false]);
    $onlineDesk->givePermissionTo(Permission::findOrCreate('sale.online payments', 'web'));
    $inactiveAdmin = User::factory()->create(['tenant_id' => $this->world->tenant->id, 'is_admin' => 1, 'is_active' => 0]);
    $otherTenantAdmin = User::factory()->create(['tenant_id' => Tenant::factory()->create()->id, 'is_admin' => 1, 'is_browser_notification_enabled' => true]);

    $reference = $this->postJson($this->world->url('/api/v1/storefront/checkout'), storefrontCheckoutPayload($this->world))->json('data.reference');
    $this->getJson($this->world->url("/api/v1/storefront/checkout/{$reference}"))->assertJsonPath('data.status', 'paid');
    $this->postJson($this->world->url('/api/v1/storefront/checkout/tap-webhook'), ['id' => 'chg_TS_test_0001', 'status' => 'CAPTURED']);

    $sale = Sale::withoutGlobalScopes()->sole();
    $notifications = DB::table('notifications')->where('type', OnlineSaleNotification::class)->get();

    expect($notifications->pluck('notifiable_id')->sort()->values()->all())->toBe([$this->world->user->id, $quietAdmin->id, $onlineDesk->id])
        ->and($notifications->pluck('tenant_id')->unique()->all())->toBe([$this->world->tenant->id]);

    $data = json_decode($notifications->first()->data, true);
    expect($data['title'])->toBe('New Online Order')
        ->and($data['message'])->toBe("Aisha Khan paid 500.00 for invoice #{$sale->invoice_no}.")
        ->and($data['link'])->toBe(route('sale::view', $sale->id, false))
        ->and($data['model_id'])->toBe($sale->id);

    Event::assertDispatchedTimes(NotificationCreatedEvent::class, 1);
    Event::assertDispatched(NotificationCreatedEvent::class, fn (NotificationCreatedEvent $event) => $event->userId === $this->world->user->id);
});

it('sends no notification when the payment does not go through', function (): void {
    Event::fake([NotificationCreatedEvent::class]);
    storefrontFakeTap('DECLINED');

    $reference = $this->postJson($this->world->url('/api/v1/storefront/checkout'), storefrontCheckoutPayload($this->world))->json('data.reference');
    $this->getJson($this->world->url("/api/v1/storefront/checkout/{$reference}"))->assertJsonPath('data.status', 'failed');

    expect(DB::table('notifications')->count())->toBe(0);
    Event::assertNotDispatched(NotificationCreatedEvent::class);
});

it('shows the Tap transaction, delivery plates and map pin on the sale view', function (): void {
    Http::fake(fn (Request $request) => Http::response(storefrontTapCharge($request->method() === 'POST' ? [] : [
        'status' => 'CAPTURED',
        'source' => ['payment_method' => 'VISA', 'payment_type' => 'DEBIT'],
        'card' => ['first_six' => '450875', 'last_four' => '1019', 'brand' => 'VISA'],
        'reference' => ['payment' => '3029251234', 'acquirer' => '529212345678', 'gateway' => '1234567'],
        'receipt' => ['id' => '203029251234'],
        'response' => ['code' => '000', 'message' => 'Captured'],
    ])));
    Configuration::where('key', TapSettings::KEY)->update(['value' => json_encode([
        'enabled' => true,
        'payment_account_id' => $this->tapAccountId,
        'user_id' => $this->world->user->id,
        'delivery_branch_id' => $this->world->branch->id,
    ])]);
    Cache::put('qnas:buildings:56:340', [['number' => '12', 'lat' => 25.2854473, 'lng' => 51.5310398]]);

    $reference = $this->postJson($this->world->url('/api/v1/storefront/checkout'), storefrontCheckoutPayload($this->world, [
        'fulfilment' => 'delivery',
        'branchId' => null,
        'zoneNumber' => '56',
        'streetNumber' => '340',
        'buildingNumber' => '12',
        'city' => 'Doha',
    ]))->json('data.reference');
    $this->getJson($this->world->url("/api/v1/storefront/checkout/{$reference}"))->assertJsonPath('data.status', 'paid');

    $checkout = StorefrontCheckout::withoutGlobalScopes()->sole();
    expect($checkout->paymentDetails())->toMatchArray([
        'method' => 'VISA',
        'payment_type' => 'DEBIT',
        'card_number' => '450875 •• 1019',
        'payment_reference' => '3029251234',
        'receipt_no' => '203029251234',
    ]);

    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);

    Livewire::test(SaleView::class, ['table_id' => Sale::withoutGlobalScopes()->sole()->id])
        ->assertSee('Online order')
        ->assertSee('chg_TS_test_0001')
        ->assertSee('450875 •• 1019')
        ->assertSee('529212345678')
        ->assertSeeInOrder(['Zone', '56', 'Street', '340', 'Building', '12'])
        ->assertSee('https://maps.google.com/maps?q=25.2854473,51.5310398&amp;z=16&amp;output=embed', false)
        ->assertSee('https://www.google.com/maps/dir/?api=1&amp;destination=25.2854473,51.5310398', false)
        ->assertDontSee('Notes &amp; information', false);
});

it('shows the pickup shop instead of a map for a pickup order', function (): void {
    storefrontFakeTap('CAPTURED');

    $reference = $this->postJson($this->world->url('/api/v1/storefront/checkout'), storefrontCheckoutPayload($this->world))->json('data.reference');
    $this->getJson($this->world->url("/api/v1/storefront/checkout/{$reference}"))->assertJsonPath('data.status', 'paid');

    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);

    Livewire::test(SaleView::class, ['table_id' => Sale::withoutGlobalScopes()->sole()->id])
        ->assertSee('Store pickup')
        ->assertSee($this->world->branch->name)
        ->assertDontSee('output=embed', false);
});

it('searches the typed address when the customer dropped no pin', function (): void {
    $checkout = new StorefrontCheckout(['fulfilment' => 'delivery', 'address' => 'Zone 56, Street 340, Building 12, Doha']);

    expect($checkout->hasMapPin())->toBeFalse()
        ->and($checkout->mapEmbedUrl())->toBeNull()
        ->and($checkout->directionsUrl())->toBeNull()
        ->and($checkout->mapUrl())->toBe('https://www.google.com/maps/search/?api=1&query=Zone%2056%2C%20Street%20340%2C%20Building%2012%2C%20Doha')
        ->and($checkout->paymentDetails()['card_number'])->toBeNull();
});

it('keeps the shopper email on the customer it creates', function (): void {
    storefrontFakeTap('CAPTURED');

    $reference = $this->postJson($this->world->url('/api/v1/storefront/checkout'), storefrontCheckoutPayload($this->world))->json('data.reference');
    $this->getJson($this->world->url("/api/v1/storefront/checkout/{$reference}"))->assertJsonPath('data.status', 'paid');

    $customer = Account::withoutGlobalScopes()->find(Sale::withoutGlobalScopes()->sole()->account_id);
    expect($customer->name)->toBe('Aisha Khan')
        ->and($customer->email)->toBe('aisha@example.com');
});

it('fills a missing email on a returning customer but never replaces one', function (?string $stored, string $expected): void {
    storefrontFakeTap('CAPTURED');
    $existing = Account::create([
        'tenant_id' => $this->world->tenant->id,
        'account_type' => 'asset',
        'name' => 'Aisha Khan',
        'mobile' => '55123456',
        'email' => $stored,
        'model' => 'customer',
    ]);

    $reference = $this->postJson($this->world->url('/api/v1/storefront/checkout'), storefrontCheckoutPayload($this->world))->json('data.reference');
    $this->getJson($this->world->url("/api/v1/storefront/checkout/{$reference}"))->assertJsonPath('data.status', 'paid');

    expect(Sale::withoutGlobalScopes()->sole()->account_id)->toBe($existing->id)
        ->and($existing->fresh()->email)->toBe($expected);
})->with([
    'no email yet' => [null, 'aisha@example.com'],
    'staff recorded one' => ['aisha.office@example.com', 'aisha.office@example.com'],
]);

/**
 * Pays a checkout through the storefront, then fakes Tap so charges read CAPTURED
 * and a refund is answered [$created] and later retrieved as [$retrieved].
 */
function storefrontPaidThenRefundable(PosWorld $world, string $created, ?string $retrieved = null, int $refundHttpStatus = 200): StorefrontCheckout
{
    Http::fake(function (Request $request) use ($created, $retrieved, $refundHttpStatus) {
        if (str_contains($request->url(), '/refunds')) {
            if ($refundHttpStatus !== 200) {
                return Http::response(['errors' => [['description' => 'Refund amount exceeds the charge']]], $refundHttpStatus);
            }

            return Http::response([
                'id' => 're_TS_test_0001',
                'object' => 'refund',
                'charge_id' => 'chg_TS_test_0001',
                'amount' => 500,
                'currency' => 'QAR',
                'status' => $request->method() === 'POST' ? $created : ($retrieved ?? $created),
                'response' => ['code' => '000', 'message' => 'Refund '.strtolower($request->method() === 'POST' ? $created : ($retrieved ?? $created))],
            ]);
        }

        return $request->method() === 'POST'
            ? Http::response(storefrontTapCharge())
            : Http::response(storefrontTapCharge(['status' => 'CAPTURED']));
    });

    $reference = test()->postJson($world->url('/api/v1/storefront/checkout'), storefrontCheckoutPayload($world))->assertCreated()->json('data.reference');
    test()->getJson($world->url("/api/v1/storefront/checkout/{$reference}"))->assertJsonPath('data.status', 'paid');

    $world->user->givePermissionTo(Permission::findOrCreate('sale.online payments', 'web'), Permission::findOrCreate('sale.online payments refund', 'web'));
    test()->actingAs($world->user);
    session(['branch_id' => $world->branch->id]);

    return StorefrontCheckout::withoutGlobalScopes()->sole();
}

it('refunds a paid online order through Tap and cancels its sale', function (): void {
    $checkout = storefrontPaidThenRefundable($this->world, 'REFUNDED');
    expect(storefrontStockAt($this->world, $this->world->branch->id))->toBe(3.0);

    Livewire::test(OnlinePayments::class)->call('refund', $checkout->id, 'Customer changed mind')->assertDispatched('success');

    $checkout->refresh();
    expect($checkout->status)->toBe(StorefrontCheckout::STATUS_REFUNDED)
        ->and($checkout->refund_id)->toBe('re_TS_test_0001')
        ->and($checkout->refund_status)->toBe('REFUNDED')
        ->and((float) $checkout->refund_amount)->toBe(500.0)
        ->and($checkout->refund_reason)->toBe('Customer changed mind')
        ->and($checkout->refund_requested_by)->toBe($this->world->user->id)
        ->and($checkout->refunded_at)->not->toBeNull()
        ->and($checkout->refund_request['charge_id'])->toBe('chg_TS_test_0001')
        ->and($checkout->refund_response['status'])->toBe('REFUNDED')
        ->and($checkout->gateway_request['amount'])->toEqual(500)
        ->and($checkout->failure_reason)->toBeNull();

    expect(Sale::withoutGlobalScopes()->sole()->status)->toBe('cancelled')
        ->and(storefrontStockAt($this->world, $this->world->branch->id))->toBe(5.0);

    Http::assertSent(fn (Request $request) => $request->method() === 'POST'
        && $request->url() === 'https://api.tap.company/v2/refunds'
        && $request['charge_id'] === 'chg_TS_test_0001'
        && (float) $request['amount'] === 500.0
        && str_contains($request['post']['url'], '/api/v1/storefront/checkout/tap-webhook'));

    // A refunded payment cannot be refunded again.
    Livewire::test(OnlinePayments::class)->call('refund', $checkout->id)->assertDispatched('error');
    Http::assertSentCount(3);
});

it('keeps a pending refund open until Tap reports it refunded', function (): void {
    $checkout = storefrontPaidThenRefundable($this->world, 'PENDING', 'REFUNDED');

    Livewire::test(OnlinePayments::class)->call('refund', $checkout->id)->assertDispatched('success');

    expect($checkout->fresh()->status)->toBe(StorefrontCheckout::STATUS_PAID)
        ->and($checkout->fresh()->refund_status)->toBe('PENDING')
        ->and($checkout->fresh()->refundPending())->toBeTrue()
        ->and(Sale::withoutGlobalScopes()->sole()->status)->toBe('completed');

    // Tap's webhook only names the refund; the outcome is re-read from Tap.
    $this->postJson($this->world->url('/api/v1/storefront/checkout/tap-webhook'), ['id' => 're_TS_test_0001', 'object' => 'refund', 'status' => 'REFUNDED'])
        ->assertSuccessful();

    expect($checkout->fresh()->status)->toBe(StorefrontCheckout::STATUS_REFUNDED)
        ->and(Sale::withoutGlobalScopes()->sole()->status)->toBe('cancelled');
});

it('records what Tap said when it refuses a refund', function (): void {
    $checkout = storefrontPaidThenRefundable($this->world, 'REFUNDED', refundHttpStatus: 400);

    Livewire::test(OnlinePayments::class)->call('refund', $checkout->id)->assertDispatched('error');

    $checkout->refresh();
    expect($checkout->status)->toBe(StorefrontCheckout::STATUS_PAID)
        ->and($checkout->refund_id)->toBeNull()
        ->and($checkout->refund_request['charge_id'])->toBe('chg_TS_test_0001')
        ->and($checkout->refund_response['error'])->toBe('Refund amount exceeds the charge')
        ->and($checkout->isRefundable())->toBeTrue();
});

it('does not refund without the refund permission', function (): void {
    $checkout = storefrontPaidThenRefundable($this->world, 'REFUNDED');
    $this->world->user->revokePermissionTo('sale.online payments refund');

    Livewire::test(OnlinePayments::class)->call('refund', $checkout->id)->assertForbidden();

    expect($checkout->fresh()->status)->toBe(StorefrontCheckout::STATUS_PAID);
});

it('shows the full transaction with Tap requests and responses in the details popup', function (): void {
    $checkout = storefrontPaidThenRefundable($this->world, 'REFUNDED');

    Livewire::test(OnlinePayments::class)
        ->call('showDetails', $checkout->id)
        ->assertSee('Charge request')
        ->assertSee('Charge response')
        ->assertSee('&quot;src_all&quot;', false)
        ->assertSee($checkout->reference)
        ->assertSee('Aisha Khan')
        ->call('closeDetails')
        ->assertDontSee('Charge request');
});

<?php

use App\Models\Configuration;
use App\Models\Sale;
use App\Models\StorefrontCheckout;
use App\Support\Storefront\TapSettings;
use App\Support\TenantCache;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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

    $reference = $this->postJson($this->world->url('/api/v1/storefront/checkout'), storefrontCheckoutPayload($this->world, [
        'fulfilment' => 'delivery',
        'branchId' => null,
        'address' => 'Villa 12, Street 340, Doha',
    ]))->assertCreated()->json('data.reference');

    $this->getJson($this->world->url("/api/v1/storefront/checkout/{$reference}"))->assertJsonPath('data.status', 'paid');

    $sale = Sale::withoutGlobalScopes()->sole();
    expect($sale->branch_id)->toBe($warehouse->id)
        ->and($sale->address)->toBe('Villa 12, Street 340, Doha')
        ->and(storefrontStockAt($this->world, $warehouse->id))->toBe(98.0);
});

it('refuses delivery when the store ships from nowhere', function (): void {
    Http::fake();

    $this->postJson($this->world->url('/api/v1/storefront/checkout'), storefrontCheckoutPayload($this->world, [
        'fulfilment' => 'delivery',
        'branchId' => null,
        'address' => 'Villa 12, Street 340, Doha',
    ]))->assertUnprocessable();

    Http::assertNothingSent();
});

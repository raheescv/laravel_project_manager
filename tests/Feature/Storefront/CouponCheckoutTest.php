<?php

use App\Models\Brand;
use App\Models\Configuration;
use App\Models\Coupon;
use App\Models\JournalEntry;
use App\Models\Sale;
use App\Models\StorefrontCheckout;
use App\Models\User;
use App\Support\Storefront\TapSettings;
use App\Support\TenantCache;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Support\PosWorld;

/**
 * Promo codes on the online store. What these pin down: nothing is listed
 * without a search, the server alone decides what a code is worth, and a paid
 * order with a code always becomes a completed sale whose total matches Tap.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create(stock: 5, price: 745);
    $tenantId = $this->world->tenant->id;

    Configuration::create(['tenant_id' => $tenantId, 'key' => 'base_currency_code', 'value' => 'QAR']);
    Configuration::create(['tenant_id' => $tenantId, 'key' => TapSettings::SECRET_KEY, 'value' => TapSettings::encryptSecret('sk_test_fake')]);
    Configuration::create(['tenant_id' => $tenantId, 'key' => TapSettings::KEY, 'value' => json_encode([
        'enabled' => true,
        'payment_account_id' => $this->world->addPaymentMethod('Tap Payments'),
        'user_id' => $this->world->user->id,
        'delivery_branch_id' => null,
    ])]);
    TenantCache::forget('base_currency_code');

    $this->items = [['productId' => $this->world->product->id, 'quantity' => 1]];
    $this->search = fn (string $q, ?array $items = null) => $this->postJson(
        $this->world->url('/api/v1/storefront/coupons/search'),
        ['q' => $q, 'items' => $items ?? $this->items],
    );
    $this->checkout = fn (array $overrides = []) => $this->postJson($this->world->url('/api/v1/storefront/checkout'), array_merge([
        'fulfilment' => 'pickup',
        'branchId' => $this->world->branch->id,
        'customerName' => 'Aisha Khan',
        'customerEmail' => 'aisha@example.com',
        'countryCode' => '974',
        'customerMobile' => '55123456',
        'items' => $this->items,
        'returnUrl' => 'https://shop.example/site/',
    ], $overrides));
    $this->capture = fn (float $amount) => Http::fake(fn (Request $request) => Http::response([
        'id' => 'chg_coupon_1',
        'status' => $request->method() === 'POST' ? 'INITIATED' : 'CAPTURED',
        'amount' => $amount,
        'currency' => 'QAR',
        'transaction' => ['url' => 'https://checkout.payments.tap.company/?token=abc'],
    ]));
    $this->paidCheckout = fn (Coupon $coupon, string $mobile = '55123456') => StorefrontCheckout::create([
        'reference' => bin2hex(random_bytes(16)),
        'branch_id' => $this->world->branch->id,
        'fulfilment' => 'pickup',
        'customer_name' => 'Earlier Customer',
        'customer_mobile' => $mobile,
        'customer_email' => 'earlier@example.com',
        'items' => [],
        'amount' => 100,
        'currency' => 'QAR',
        'coupon_id' => $coupon->id,
        'coupon_code' => $coupon->code,
        'coupon_discount' => 10,
        'gateway' => 'tap',
        'status' => StorefrontCheckout::STATUS_PAID,
    ]);
});

it('finds codes only from a search of 3 characters, best saving first', function (): void {
    Coupon::factory()->fixed(50)->create(['code' => 'RUN50']);
    Coupon::factory()->create(['code' => 'RUNNER20', 'discount_value' => 20]);
    Coupon::factory()->create(['code' => 'OTHER10']);

    ($this->search)('RU')->assertUnprocessable()->assertJsonValidationErrors('q');

    $response = ($this->search)('run')->assertOk();

    expect($response->json('data.results.*.code'))->toBe(['RUNNER20', 'RUN50'])
        ->and($response->json('data.results.0.state'))->toBe('ready')
        ->and($response->json('data.results.0.saving'))->toEqual(149)
        ->and($response->json('data.results.0.new_total'))->toEqual(596)
        ->and($response->json('data.total'))->toEqual(745);
});

it('shows a private code only when typed in full', function (): void {
    Coupon::factory()->private()->create(['code' => 'VIP25']);

    expect(($this->search)('VIP')->json('data.results'))->toBeEmpty();

    ($this->search)('vip25')->assertOk()
        ->assertJsonPath('data.results.0.code', 'VIP25')
        ->assertJsonPath('data.results.0.is_private', true);
});

it('never finds a paused or scheduled code', function (string $state): void {
    Coupon::factory()->{$state}()->create(['code' => 'HIDDEN10']);

    expect(($this->search)('HIDDEN10')->json('data.results'))->toBeEmpty();
})->with(['paused', 'scheduled']);

it('explains an ended code only to someone who typed it in full', function (): void {
    Coupon::factory()->expired()->create(['code' => 'EID30']);

    expect(($this->search)('EID')->json('data.results'))->toBeEmpty();
    ($this->search)('EID30')->assertJsonPath('data.results.0.state', 'expired');
});

it('says how much more a bag needs and unlocks once it has it', function (): void {
    Coupon::factory()->create(['code' => 'PAIR15', 'discount_value' => 15, 'min_bag_total' => 1200]);

    ($this->search)('PAIR15')
        ->assertJsonPath('data.results.0.state', 'locked')
        ->assertJsonPath('data.results.0.short', 455);

    ($this->search)('PAIR15', [['productId' => $this->world->product->id, 'quantity' => 2]])
        ->assertJsonPath('data.results.0.state', 'ready');
});

it('only discounts what the code covers', function (): void {
    $otherBrand = Brand::create(['name' => 'Not Ours']);
    Coupon::factory()->create(['code' => 'BRAND15', 'applies_to' => Coupon::SCOPE_BRAND, 'applies_to_ids' => [$otherBrand->id]]);

    ($this->search)('BRAND15')->assertJsonPath('data.results.0.state', 'ineligible');

    $this->world->product->update(['brand_id' => $otherBrand->id]);

    ($this->search)('BRAND15')->assertJsonPath('data.results.0.state', 'ready');
});

it('leaves excepted products out of the discount', function (): void {
    $other = $this->world->product->replicate()->fill(['name' => 'Second Pair', 'code' => 'SECOND', 'barcode' => 'SECOND1', 'mrp' => 255]);
    $other->save();
    $bothPairs = [['productId' => $this->world->product->id, 'quantity' => 1], ['productId' => $other->id, 'quantity' => 1]];
    Coupon::factory()->create(['code' => 'NOTOURS', 'excluded_product_ids' => [$this->world->product->id]]);

    ($this->search)('NOTOURS')->assertJsonPath('data.results.0.state', 'ineligible');

    expect(($this->search)('NOTOURS', $bothPairs)->json('data.results.0.saving'))->toEqual(25);
});

it('caps a percent discount at its maximum', function (): void {
    Coupon::factory()->create(['code' => 'CAP50', 'discount_value' => 50, 'max_discount' => 100]);

    expect(($this->search)('CAP50')->json('data.results.0.saving'))->toEqual(100);
});

it('reports the percent only when the saving really is that percent', function (): void {
    Coupon::factory()->create(['code' => 'PLAIN20', 'discount_value' => 20]);
    Coupon::factory()->create(['code' => 'CAPPED20', 'discount_value' => 20, 'max_discount' => 100]);
    Coupon::factory()->create(['code' => 'ROOMY20', 'discount_value' => 20, 'max_discount' => 500]);
    Coupon::factory()->fixed(50)->create(['code' => 'FLAT50']);

    expect(($this->search)('PLAIN20')->json('data.results.0.percent'))->toEqual(20)
        ->and(($this->search)('CAPPED20')->json('data.results.0.percent'))->toBeNull()
        ->and(($this->search)('ROOMY20')->json('data.results.0.percent'))->toEqual(20)
        ->and(($this->search)('FLAT50')->json('data.results.0.percent'))->toBeNull();
});

it('will not let a code swallow the whole bag', function (): void {
    Coupon::factory()->fixed(800)->create(['code' => 'HUGE800']);

    ($this->search)('HUGE800')
        ->assertJsonPath('data.results.0.state', 'locked')
        ->assertJsonPath('data.results.0.short', 56);
});

it('takes the store round off out of the saving so the charge stays whole', function (): void {
    Coupon::factory()->create(['code' => 'ROUND10']);

    expect(($this->search)('ROUND10')->json('data.results.0.saving'))->toEqual(74)
        ->and(($this->search)('ROUND10')->json('data.results.0.new_total'))->toEqual(671);
});

it('keeps exact cents when the store has round off switched off', function (): void {
    Configuration::create(['tenant_id' => $this->world->tenant->id, 'key' => 'round_off_enabled', 'value' => 'no']);
    Coupon::factory()->create(['code' => 'ROUND10']);

    expect(($this->search)('ROUND10')->json('data.results.0.saving'))->toEqual(74.5);
});

it('charges the discounted amount and records a matching sale with the discount journal', function (): void {
    $coupon = Coupon::factory()->create(['code' => 'SAVE20', 'discount_value' => 20]);
    ($this->capture)(596);

    $response = ($this->checkout)(['couponCode' => 'save20'])->assertCreated();

    Http::assertSent(fn (Request $request) => $request->method() === 'POST' && (float) $request['amount'] === 596.0);

    $this->getJson($this->world->url('/api/v1/storefront/checkout/'.$response->json('data.reference')))
        ->assertOk()
        ->assertJsonPath('data.status', 'paid');

    $checkout = StorefrontCheckout::withoutGlobalScopes()->sole();
    $sale = Sale::withoutGlobalScopes()->findOrFail($checkout->sale_id);

    expect($checkout->coupon_id)->toBe($coupon->id)
        ->and((float) $checkout->coupon_discount)->toBe(149.0)
        ->and((float) $sale->other_discount)->toBe(149.0)
        ->and((float) $sale->grand_total)->toBe(596.0)
        ->and((float) $sale->balance)->toBe(0.0)
        ->and($coupon->fresh()->usedCount())->toBe(1);

    $entries = JournalEntry::withoutGlobalScopes()->where('model', 'Sale')->where('model_id', $sale->id)->get();
    expect(round($entries->sum('debit'), 2))->toBe(round($entries->sum('credit'), 2))
        ->and($entries->where('account_id', $this->world->accounts['discount'])->sum('debit'))->toEqual(149);
});

it('records the sale even when the recording user has a low discount limit', function (): void {
    User::query()->whereKey($this->world->user->id)->update(['max_discount_per_sale' => 1]);
    Coupon::factory()->create(['code' => 'SAVE20', 'discount_value' => 20]);
    ($this->capture)(596);

    $response = ($this->checkout)(['couponCode' => 'SAVE20'])->assertCreated();

    $this->getJson($this->world->url('/api/v1/storefront/checkout/'.$response->json('data.reference')))
        ->assertJsonPath('data.status', 'paid');
});

it('charges a percent code that needed rounding without sending the order to review', function (): void {
    Coupon::factory()->create(['code' => 'ROUND10']);
    ($this->capture)(671);

    $response = ($this->checkout)(['couponCode' => 'ROUND10'])->assertCreated();

    Http::assertSent(fn (Request $request) => $request->method() === 'POST' && (float) $request['amount'] === 671.0);
    $this->getJson($this->world->url('/api/v1/storefront/checkout/'.$response->json('data.reference')))
        ->assertJsonPath('data.status', 'paid');
});

it('refuses a checkout whose code is unknown or no longer valid', function (): void {
    Http::fake();
    Coupon::factory()->expired()->create(['code' => 'OLD10']);

    ($this->checkout)(['couponCode' => 'NOPE99'])->assertUnprocessable()->assertJsonPath('message', 'NOPE99 is not a valid promo code.');
    ($this->checkout)(['couponCode' => 'OLD10'])->assertUnprocessable()->assertJsonPath('message', 'OLD10 has ended.');

    Http::assertNothingSent();
});

it('holds a customer to the per-customer limit at checkout', function (): void {
    Http::fake();
    $coupon = Coupon::factory()->create(['code' => 'ONCE10', 'per_customer_limit' => 1]);
    ($this->paidCheckout)($coupon);

    ($this->checkout)(['couponCode' => 'ONCE10'])->assertUnprocessable()->assertJsonPath('message', 'You have already used ONCE10.');
});

it('closes a code once its total uses are spent, and a refund gives one back', function (): void {
    $coupon = Coupon::factory()->create(['code' => 'FIRST1', 'usage_limit' => 1]);
    $earlier = ($this->paidCheckout)($coupon, '55000000');

    ($this->search)('FIRST1')->assertJsonPath('data.results.0.state', 'used_up');

    $earlier->update(['status' => StorefrontCheckout::STATUS_REFUNDED]);

    ($this->search)('FIRST1')->assertJsonPath('data.results.0.state', 'ready');
});

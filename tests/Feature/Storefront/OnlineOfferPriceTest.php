<?php

use App\Models\Configuration;
use App\Models\Coupon;
use App\Models\ProductOffer;
use App\Models\ProductPrice;
use App\Models\Sale;
use App\Models\StorefrontCheckout;
use App\Support\Storefront\TapSettings;
use App\Support\TenantCache;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\Support\PosWorld;

/**
 * Offers on the online store: a running Product Offer prices the storefront
 * card, product page and checkout, while a normal POS sale keeps MRP.
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

    $this->offer = function (float $amount): ProductOffer {
        $offer = ProductOffer::create([
            'tenant_id' => $this->world->tenant->id,
            'type' => 'product',
            'name' => 'Online week',
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addWeek()->toDateString(),
            'status' => 'active',
            'created_by' => $this->world->user->id,
            'updated_by' => $this->world->user->id,
        ]);
        ProductPrice::create([
            'product_id' => $this->world->product->id,
            'product_offer_id' => $offer->id,
            'price_type' => 'offer',
            'amount' => $amount,
            'start_date' => $offer->start_date->toDateString(),
            'end_date' => $offer->end_date->toDateString(),
            'status' => 'active',
        ]);

        return $offer;
    };

    $this->checkout = fn (array $overrides = []) => $this->postJson($this->world->url('/api/v1/storefront/checkout'), array_merge([
        'fulfilment' => 'pickup',
        'branchId' => $this->world->branch->id,
        'customerName' => 'Aisha Khan',
        'customerEmail' => 'aisha@example.com',
        'countryCode' => '974',
        'customerMobile' => '55123456',
        'items' => [['productId' => $this->world->product->id, 'quantity' => 2]],
        'returnUrl' => 'https://shop.example/site/',
    ], $overrides));

    $this->capture = fn (float $amount) => Http::fake(fn (Request $request) => Http::response([
        'id' => 'chg_online_1',
        'status' => $request->method() === 'POST' ? 'INITIATED' : 'CAPTURED',
        'amount' => $amount,
        'currency' => 'QAR',
        'transaction' => ['url' => 'https://checkout.payments.tap.company/?token=abc'],
    ]));
});

it('shows the offer price on the product list and detail, and a normal POS sale keeps MRP', function (): void {
    ($this->offer)(633);
    Sanctum::actingAs($this->world->user);
    $id = $this->world->product->id;

    $row = collect($this->getJson($this->world->url('/api/v1/products?in_stock_only=0&per_page=100'))->assertOk()->json('data.data'))
        ->firstWhere('id', $id);

    expect((float) $row['online_price'])->toBe(633.0)
        ->and($row['online_discount_percent'])->toBe(15)
        ->and((float) $row['mrp'])->toBe(745.0);

    $this->getJson($this->world->url("/api/v1/products/{$id}"))
        ->assertOk()
        ->assertJsonPath('data.online_price', 633)
        ->assertJsonPath('data.online_discount_percent', 15);

    expect((float) $this->world->product->fresh()->saleTypePrice('normal'))->toBe(745.0);
});

it('drops the online price once the offer is switched off', function (): void {
    $offer = ($this->offer)(633);
    $offer->prices()->update(['status' => 'disabled']);

    expect($this->world->product->fresh()->onlinePrice())->toBe(745.0);
});

it('ignores an online offer priced at or above MRP', function (): void {
    ($this->offer)(800);

    expect($this->world->product->fresh()->onlinePrice())->toBe(745.0)
        ->and($this->world->product->fresh()->onlineUnitDiscount())->toBe(0.0);
});

it('charges the online price and books the saving as the line discount', function (): void {
    ($this->offer)(633);
    ($this->capture)(1266);

    $response = ($this->checkout)()->assertCreated();

    Http::assertSent(fn (Request $request) => $request->method() === 'POST' && (float) $request['amount'] === 1266.0);

    $this->getJson($this->world->url('/api/v1/storefront/checkout/'.$response->json('data.reference')))
        ->assertOk()
        ->assertJsonPath('data.status', 'paid');

    $checkout = StorefrontCheckout::withoutGlobalScopes()->sole();
    $sale = Sale::withoutGlobalScopes()->with('items')->findOrFail($checkout->sale_id);
    $line = $sale->items->sole();

    expect((float) $line->unit_price)->toBe(745.0)
        ->and((float) $line->discount)->toBe(224.0)
        ->and((float) $sale->item_discount)->toBe(224.0)
        ->and((float) $sale->grand_total)->toBe(1266.0)
        ->and((float) $sale->balance)->toBe(0.0);
});

it('rounds a cents online price the way the sale will', function (): void {
    ($this->offer)(633.25);
    ($this->capture)(633);

    ($this->checkout)(['items' => [['productId' => $this->world->product->id, 'quantity' => 1]]])->assertCreated();

    expect((float) StorefrontCheckout::withoutGlobalScopes()->sole()->amount)->toBe(633.0);
});

it('takes a promo code off the online price, not MRP', function (): void {
    ($this->offer)(633);
    Coupon::factory()->create(['code' => 'SAVE10', 'discount_value' => 10]);

    $this->postJson($this->world->url('/api/v1/storefront/coupons/search'), [
        'q' => 'SAVE10',
        'items' => [['productId' => $this->world->product->id, 'quantity' => 1]],
    ])->assertOk()
        ->assertJsonPath('data.total', 633)
        ->assertJsonPath('data.results.0.new_total', 570);
});

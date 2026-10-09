<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\ProductPrice;
use App\Models\Tenant;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Support\PosWorld;

beforeEach(function (): void {
    $this->world = PosWorld::create();

    foreach (config('permissions.product offer') as $action) {
        $this->world->user->givePermissionTo(Permission::firstOrCreate(['name' => "product offer.{$action}", 'guard_name' => 'web']));
    }

    $this->actingAs($this->world->user);
    $this->api = fn (string $path = ''): string => $this->world->url('/product/offer/api'.$path);

    $this->footwear = Category::create(['name' => 'Footwear']);
    $this->sneakers = Category::create(['name' => 'Sneakers', 'parent_id' => $this->footwear->id]);
    $this->sandals = Category::create(['name' => 'Sandals', 'parent_id' => $this->footwear->id]);

    $this->product = function (array $state = []): Product {
        return Product::create($state + [
            'type' => 'product',
            'name' => 'Shoe '.Str::random(5),
            'code' => 'S'.Str::upper(Str::random(6)),
            'unit_id' => $this->world->product->unit_id,
            'department_id' => $this->world->product->department_id,
            'main_category_id' => $this->footwear->id,
            'sub_category_id' => $this->sneakers->id,
            'mrp' => 100,
            'cost' => 60,
            'is_selling' => true,
            'created_by' => $this->world->user->id,
            'updated_by' => $this->world->user->id,
        ]);
    };

    $this->payload = fn (array $items, array $overrides = []): array => $overrides + [
        'type' => 'product',
        'name' => 'Eid Festive Sale',
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => now()->addWeek()->toDateString(),
        'status' => 'active',
        'items' => $items,
    ];
});

it('serves the list and editor pages and guards them by permission', function (): void {
    $this->withoutVite();

    $this->get($this->world->url('/product/offer'))->assertOk()->assertSee('id="product-offer-list"', false);
    $this->get($this->world->url('/product/offer/create'))->assertOk()->assertSee('id="product-offer-editor"', false);

    $this->world->user->revokePermissionTo('product offer.create');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->get($this->world->url('/product/offer/create'))->assertForbidden();
    $this->postJson(($this->api)(), ($this->payload)([]))->assertForbidden();
});

it('creates an offer as one offer price per product that the POS reads', function (): void {
    $first = ($this->product)(['mrp' => 520]);
    $second = ($this->product)(['mrp' => 310]);

    $id = $this->postJson(($this->api)(), ($this->payload)([
        ['product_id' => $first->id, 'amount' => 416],
        ['product_id' => $second->id, 'amount' => 248.5],
    ]))->assertCreated()->json('data.id');

    $offer = ProductOffer::findOrFail($id);
    expect($offer->created_by)->toBe($this->world->user->id)
        ->and($offer->prices()->count())->toBe(2)
        ->and($first->fresh()->offer_price)->toEqual(416)
        ->and($second->fresh()->saleTypePrice('offer'))->toEqual(248.5);
});

it('syncs prices, dates and status on update', function (): void {
    $kept = ($this->product)();
    $dropped = ($this->product)();
    $added = ($this->product)();

    $id = $this->postJson(($this->api)(), ($this->payload)([
        ['product_id' => $kept->id, 'amount' => 80],
        ['product_id' => $dropped->id, 'amount' => 80],
    ]))->json('data.id');

    $this->putJson(($this->api)("/{$id}"), ($this->payload)([
        ['product_id' => $kept->id, 'amount' => 75],
        ['product_id' => $added->id, 'amount' => 90],
    ], ['name' => 'Renamed', 'end_date' => now()->addMonth()->toDateString()]))->assertOk();

    $prices = ProductPrice::where('product_offer_id', $id)->get()->keyBy('product_id');
    expect($prices->keys()->sort()->values()->all())->toBe(collect([$kept->id, $added->id])->sort()->values()->all())
        ->and((float) $prices[$kept->id]->amount)->toBe(75.0)
        ->and(substr((string) $prices[$added->id]->end_date, 0, 10))->toBe(now()->addMonth()->toDateString())
        ->and($dropped->fresh()->offer_price)->toBeNull();

    $this->putJson(($this->api)("/{$id}"), ($this->payload)([
        ['product_id' => $kept->id, 'amount' => 75],
    ], ['status' => 'disabled']))->assertOk();

    expect($kept->fresh()->offer_price)->toBeNull()
        ->and($kept->fresh()->saleTypePrice('offer'))->toEqual(100);
});

it('returns the offer with its products for the editor', function (): void {
    $product = ($this->product)(['name' => 'Samba OG', 'mrp' => 420, 'cost' => 260]);
    $id = $this->postJson(($this->api)(), ($this->payload)([['product_id' => $product->id, 'amount' => 349]]))->json('data.id');

    $offer = $this->getJson(($this->api)("/{$id}"))->assertOk()->json('data');

    expect($offer['name'])->toBe('Eid Festive Sale')
        ->and($offer['items'][0])->toMatchArray([
            'id' => $product->id,
            'name' => 'Samba OG',
            'mrp' => 420.0,
            'cost' => 260.0,
            'amount' => 349.0,
            'main_category' => 'Footwear',
            'sub_category' => 'Sneakers',
        ]);
});

it('deletes an offer and returns its products to their normal price', function (): void {
    $product = ($this->product)();
    $id = $this->postJson(($this->api)(), ($this->payload)([['product_id' => $product->id, 'amount' => 70]]))->json('data.id');

    $this->deleteJson(($this->api)("/{$id}"))->assertOk();

    expect(ProductOffer::find($id))->toBeNull()
        ->and(ProductPrice::where('product_offer_id', $id)->exists())->toBeFalse()
        ->and($product->fresh()->offer_price)->toBeNull();
});

it('rejects invalid offers and products from another tenant', function (): void {
    $product = ($this->product)();

    $this->postJson(($this->api)(), ($this->payload)([]))
        ->assertUnprocessable()->assertJsonValidationErrors(['items' => 'Add at least one product to the offer.']);

    $this->postJson(($this->api)(), ($this->payload)([['product_id' => $product->id, 'amount' => 50]], [
        'start_date' => now()->toDateString(),
        'end_date' => now()->subDay()->toDateString(),
    ]))->assertUnprocessable()->assertJsonValidationErrors(['end_date' => 'The offer must end on or after its start date.']);

    $otherTenant = Tenant::factory()->create();
    $foreign = ($this->product)(['tenant_id' => $otherTenant->id]);

    $this->postJson(($this->api)(), ($this->payload)([['product_id' => $foreign->id, 'amount' => 50]]))
        ->assertStatus(422)->assertJsonPath('message', 'Some products in this offer no longer exist.');

    expect(ProductOffer::count())->toBe(0)->and(ProductPrice::count())->toBe(0);
});

it('lists categories with product counts and loads products by category or search', function (): void {
    ($this->product)(['name' => 'Pegasus']);
    ($this->product)(['name' => 'Ultraboost']);
    ($this->product)(['name' => 'Slide', 'sub_category_id' => $this->sandals->id]);
    ($this->product)(['name' => 'Hidden', 'is_selling' => false]);

    $tree = collect($this->getJson(($this->api)('/categories'))->assertOk()->json('data'))->keyBy('name');

    expect($tree['Footwear']['count'])->toBe(3)
        ->and(collect($tree['Footwear']['subs'])->pluck('count', 'name')->all())->toBe(['Sandals' => 1, 'Sneakers' => 2])
        ->and($tree['General']['count'])->toBe(1);

    $sneakers = $this->getJson(($this->api)("/products?main_category_id={$this->footwear->id}&sub_category_id={$this->sneakers->id}"))->json('data');
    expect(collect($sneakers['products'])->pluck('name')->all())->toBe(['Pegasus', 'Ultraboost'])
        ->and($sneakers['truncated'])->toBeFalse();

    $search = $this->getJson(($this->api)('/products?search=slid'))->json('data.products');
    expect(collect($search)->pluck('name')->all())->toBe(['Slide']);
});

it('lists offers with their running state', function (): void {
    $product = ($this->product)();
    $make = fn (array $state) => ProductOffer::factory()->create($state + ['created_by' => $this->world->user->id, 'updated_by' => $this->world->user->id]);

    $running = $make(['name' => 'Running now']);
    $running->prices()->create(['product_id' => $product->id, 'price_type' => 'offer', 'amount' => 80, 'start_date' => $running->start_date, 'end_date' => $running->end_date]);
    $make(['name' => 'Next month', 'start_date' => now()->addMonth()->toDateString(), 'end_date' => now()->addMonths(2)->toDateString()]);
    $make(['name' => 'Last year', 'start_date' => now()->subYear()->toDateString(), 'end_date' => now()->subYear()->addWeek()->toDateString()]);
    $make(['name' => 'Switched off', 'status' => 'disabled']);

    $offers = collect($this->getJson(($this->api)())->assertOk()->json('data.offers'))->keyBy('name');
    expect($offers->map(fn (array $offer): string => $offer['state'])->all())->toBe([
        'Switched off' => 'disabled',
        'Last year' => 'expired',
        'Next month' => 'scheduled',
        'Running now' => 'running',
    ])->and($offers['Running now']['products_count'])->toBe(1);

    $scheduled = $this->getJson(($this->api)('?state=scheduled'))->json('data.offers');
    expect(collect($scheduled)->pluck('name')->all())->toBe(['Next month']);
});

it('keeps service offers apart from product offers', function (): void {
    $haircut = ($this->product)(['type' => 'service', 'name' => 'Haircut']);
    $shoe = ($this->product)(['name' => 'Pegasus']);

    $this->postJson(($this->api)(), ($this->payload)([['product_id' => $haircut->id, 'amount' => 40]], ['type' => 'service', 'name' => 'Salon week']))->assertCreated();
    $this->postJson(($this->api)(), ($this->payload)([['product_id' => $shoe->id, 'amount' => 80]]))->assertCreated();

    expect(collect($this->getJson(($this->api)('?type=service'))->json('data.offers'))->pluck('name')->all())->toBe(['Salon week'])
        ->and(collect($this->getJson(($this->api)())->json('data.offers'))->pluck('name')->all())->toBe(['Eid Festive Sale'])
        ->and(collect($this->getJson(($this->api)('/products?type=service&search=a'))->json('data.products'))->pluck('name')->all())->toBe(['Haircut'])
        ->and($haircut->fresh()->offer_price)->toEqual(40);

    $this->postJson(($this->api)(), ($this->payload)([['product_id' => $shoe->id, 'amount' => 80]], ['type' => 'service']))
        ->assertStatus(422)->assertJsonPath('message', 'Some services in this offer no longer exist.');
});

it('links to offers from the product and service pages instead of the menu', function (): void {
    $this->withoutVite();
    foreach (['product.view', 'service.view'] as $permission) {
        $this->world->user->givePermissionTo(Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']));
    }

    $this->get($this->world->url('/product'))->assertOk()->assertSee(route('product::offer::index'), false);
    $this->get($this->world->url('/service'))->assertOk()->assertSee(route('product::offer::index', ['type' => 'service']), false);
    $this->get($this->world->url('/product/offer?type=service'))->assertOk()->assertSee('Service Offers')->assertSee('data-type="service"', false);

    $this->world->user->revokePermissionTo('product offer.view');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->get($this->world->url('/product'))->assertOk()->assertDontSee(route('product::offer::index'), false);
});

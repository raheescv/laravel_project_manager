<?php

use App\Models\Configuration;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * Settings → Catalog in the mobile app: listing, sorting, filtering and
 * editing products and services through the web Product actions.
 *
 * @see App\Http\Controllers\Api\V1\CatalogItemController
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->grant = function (string ...$names): void {
        foreach ($names as $name) {
            $this->world->user->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
        }
    };
    ($this->grant)('product.view', 'product.create', 'product.edit', 'product.delete', 'service.view', 'service.create', 'service.edit');
    Sanctum::actingAs($this->world->user);

    $this->catalogItem = function (string $type, string $name, array $attributes = []): Product {
        return Product::create([
            'tenant_id' => $this->world->tenant->id,
            'type' => $type,
            'name' => $name,
            'code' => 'C'.Str::upper(Str::random(6)),
            'barcode_number' => (string) random_int(10000000, 99999999),
            'unit_id' => $this->world->product->unit_id,
            'department_id' => $this->world->product->department_id,
            'main_category_id' => $this->world->product->main_category_id,
            'mrp' => 10,
            'created_by' => $this->world->user->id,
            'updated_by' => $this->world->user->id,
            ...$attributes,
        ]);
    };

    $this->payload = fn (array $overrides = []): array => [
        'name' => 'Matte Lipstick '.Str::random(4),
        'department_id' => $this->world->product->department_id,
        'main_category_id' => $this->world->product->main_category_id,
        'unit_id' => $this->world->product->unit_id,
        'cost' => 40,
        'mrp' => 85,
        'tax' => 5,
        ...$overrides,
    ];
});

it('lists one type at a time with pagination and whole-catalog counts', function (): void {
    ($this->catalogItem)('service', 'Signature Haircut', ['time' => 45]);
    ($this->catalogItem)('product', 'Disabled Clip', ['status' => 'disabled']);

    $products = $this->getJson($this->world->url('/api/v1/catalog/product'))->assertSuccessful();
    $services = $this->getJson($this->world->url('/api/v1/catalog/service'))->assertSuccessful();

    expect(collect($products->json('data.data'))->pluck('type')->unique()->all())->toBe(['product'])
        ->and($products->json('data.summary.total'))->toBe(2)
        ->and($products->json('data.summary.disabled'))->toBe(1)
        ->and($products->json('data.pagination.total'))->toBe(2)
        ->and($products->json('data.data.0'))->toHaveKeys(['id', 'name', 'department', 'main_category', 'unit', 'barcode', 'cost', 'mrp', 'thumbnail'])
        ->and($services->json('data.data.0.name'))->toBe('Signature Haircut')
        ->and((float) $services->json('data.summary.avg_time'))->toBe(45.0);
});

it('sorts relation columns by the related name, not the foreign key', function (): void {
    $zebra = DB::table('departments')->insertGetId(['tenant_id' => $this->world->tenant->id, 'name' => 'Zebra']);
    $apple = DB::table('departments')->insertGetId(['tenant_id' => $this->world->tenant->id, 'name' => 'Apple']);
    ($this->catalogItem)('product', 'In Zebra', ['department_id' => $zebra]);
    ($this->catalogItem)('product', 'In Apple', ['department_id' => $apple]);

    $ascending = $this->getJson($this->world->url('/api/v1/catalog/product?sort=department&direction=asc'))->assertSuccessful();
    $descending = $this->getJson($this->world->url('/api/v1/catalog/product?sort=department&direction=desc'))->assertSuccessful();

    expect($ascending->json('data.data.0.department'))->toBe('Apple')
        ->and($descending->json('data.data.0.department'))->toBe('Zebra');
});

it('searches and filters by status', function (): void {
    ($this->catalogItem)('product', 'Rose Gold Clip', ['status' => 'disabled']);

    $this->getJson($this->world->url('/api/v1/catalog/product?search=Rose+Gold'))
        ->assertSuccessful()
        ->assertJsonPath('data.pagination.total', 1);

    $this->getJson($this->world->url('/api/v1/catalog/product?status=disabled'))
        ->assertSuccessful()
        ->assertJsonPath('data.data.0.name', 'Rose Gold Clip')
        ->assertJsonPath('data.pagination.total', 1);
});

it('rejects a column that cannot be sorted', function (): void {
    $this->getJson($this->world->url('/api/v1/catalog/product?sort=password'))->assertUnprocessable();
});

it('gates each type on its own web permissions', function (): void {
    $this->world->user->revokePermissionTo('product.view');

    $this->getJson($this->world->url('/api/v1/catalog/product'))->assertForbidden();
    $this->getJson($this->world->url('/api/v1/catalog/service'))->assertSuccessful();
    $this->postJson($this->world->url('/api/v1/catalog/service/1/images'))->assertStatus(422);
});

it('creates a product through the web action, generating code and barcode', function (): void {
    $response = $this->postJson($this->world->url('/api/v1/catalog/product'), ($this->payload)(['name' => 'Velvet Lipstick']))
        ->assertCreated()
        ->assertJsonPath('data.name', 'Velvet Lipstick');

    $product = Product::find($response->json('data.id'));
    expect($product->type)->toBe('product')
        ->and($product->code)->not->toBeEmpty()
        ->and($product->barcode_number)->not->toBeEmpty()
        ->and($product->inventories()->count())->toBeGreaterThan(0);
});

it('creates a service with cost equal to its price and no unit', function (): void {
    $response = $this->postJson($this->world->url('/api/v1/catalog/service'), ($this->payload)([
        'name' => 'Balayage', 'unit_id' => null, 'cost' => null, 'mrp' => 450, 'time' => 150,
    ]))->assertCreated();

    $service = Product::find($response->json('data.id'));
    expect($service->type)->toBe('service')
        ->and((float) $service->cost)->toBe(450.0)
        ->and((float) $service->time)->toBe(150.0);
});

it('starts an item with no unit on the default base unit, as the web form does', function (): void {
    $this->postJson($this->world->url('/api/v1/catalog/product'), ($this->payload)(['unit_id' => null]))
        ->assertCreated()
        ->assertJsonPath('data.unit_id', $this->world->product->unit_id);
});

it('reports the web validation message, such as a duplicate name', function (): void {
    $this->postJson($this->world->url('/api/v1/catalog/product'), ($this->payload)(['name' => $this->world->product->name]))
        ->assertUnprocessable()
        ->assertJsonPath('success', false);
});

it('copies the barcode into the code when barcode = SKU is on', function (): void {
    Configuration::updateOrCreate(['key' => 'sync_barcode_to_code'], ['value' => 'yes']);

    $response = $this->postJson($this->world->url('/api/v1/catalog/product'), ($this->payload)(['barcode_number' => '880011', 'code' => '']))
        ->assertCreated();

    expect(Product::find($response->json('data.id'))->code)->toBe('880011');
});

it('quick-creates a department named with the add prefix', function (): void {
    $response = $this->postJson($this->world->url('/api/v1/catalog/product'), ($this->payload)(['department_id' => 'add Fragrance']))
        ->assertCreated();

    expect($response->json('data.department'))->toBe('Fragrance');
});

it('updates an item and keeps its code when the form sends none', function (): void {
    $item = ($this->catalogItem)('product', 'Old Name');

    $this->putJson($this->world->url("/api/v1/catalog/product/{$item->id}"), ($this->payload)(['name' => 'New Name', 'code' => '', 'status' => 'disabled']))
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'New Name')
        ->assertJsonPath('data.code', $item->code)
        ->assertJsonPath('data.status', 'disabled');
});

it('does not open a product through the service routes', function (): void {
    $this->getJson($this->world->url("/api/v1/catalog/service/{$this->world->product->id}"))->assertNotFound();
});

it('deletes an unused item', function (): void {
    $item = ($this->catalogItem)('product', 'Short Lived');

    $this->deleteJson($this->world->url("/api/v1/catalog/product/{$item->id}"))->assertSuccessful();

    expect(Product::find($item->id))->toBeNull();
});

it('adds a photo and makes the first one the thumbnail, then removes it', function (): void {
    Storage::fake('public');
    $item = ($this->catalogItem)('product', 'Photographed');

    $added = $this->post($this->world->url("/api/v1/catalog/product/{$item->id}/images"), [
        'image' => UploadedFile::fake()->image('front.jpg'),
    ], ['Accept' => 'application/json'])->assertCreated();

    $imageId = $added->json('data.images.0.id');
    expect($added->json('data.thumbnail'))->toBe($added->json('data.images.0.path'));

    $this->deleteJson($this->world->url("/api/v1/catalog/product/{$item->id}/images/{$imageId}"))
        ->assertSuccessful()
        ->assertJsonCount(0, 'data.images')
        ->assertJsonPath('data.thumbnail', null);
});

it('returns the picker options and barcode settings', function (): void {
    $this->getJson($this->world->url('/api/v1/catalog/options'))
        ->assertSuccessful()
        ->assertJsonStructure(['data' => ['departments', 'main_categories', 'sub_categories', 'units', 'brands', 'default_unit_id', 'barcode_type', 'sync_barcode_to_code']])
        ->assertJsonPath('data.departments.0.name', 'General');
});

it('reads and writes product configuration behind configuration.settings', function (): void {
    $this->getJson($this->world->url('/api/v1/settings/product'))->assertForbidden();
    ($this->grant)('configuration.settings');

    $this->putJson($this->world->url('/api/v1/settings/product'), [
        'barcode_type' => 'product_wise',
        'barcode_prefix' => 'LS',
        'sync_barcode_to_code' => 'yes',
        'hide_out_of_stock_sale_items' => 'yes',
    ])->assertSuccessful()
        ->assertJsonPath('data.barcode_prefix', 'LS')
        ->assertJsonPath('data.sync_barcode_to_code', 'yes')
        // Hiding stock from the POS also needs sale.create, which this user lacks.
        ->assertJsonPath('data.hide_out_of_stock_sale_items', 'no');

    ($this->grant)('sale.create');
    $this->putJson($this->world->url('/api/v1/settings/product'), ['hide_out_of_stock_sale_items' => 'yes'])
        ->assertSuccessful()
        ->assertJsonPath('data.hide_out_of_stock_sale_items', 'yes')
        ->assertJsonPath('data.barcode_prefix', 'LS');
});

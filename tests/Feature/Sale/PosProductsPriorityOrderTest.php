<?php

use App\Livewire\Product\Page;
use App\Models\Configuration;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * Products and services carry a priority; the POS grid follows Settings → Sale →
 * POS Product Order (appProductSort()): by priority or name, ascending or
 * descending. Priority ties break alphabetically.
 *
 * @see App\Actions\Sale\Pos\GetProductsAction
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);

    $this->addProduct = function (string $name, int $priority, string $type = 'product'): Product {
        $product = $this->world->product->replicate(['barcode']);
        $product->name = $name;
        $product->type = $type;
        $product->priority = $priority;
        $product->code = 'P'.Str::upper(Str::random(8));
        $product->save();

        DB::table('inventories')->insert([
            'tenant_id' => $this->world->tenant->id,
            'branch_id' => $this->world->branch->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'batch' => (string) Str::uuid(),
            'cost' => 10,
            'barcode_prefix' => '',
            'barcode_number' => (string) random_int(10000000, 99999999),
            'created_by' => $this->world->user->id,
            'updated_by' => $this->world->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $product;
    };
});

it('lists higher priority items first, then by name, when set to descending', function (): void {
    Configuration::updateOrCreate(['key' => 'app_product_sort_direction'], ['value' => 'desc']);
    $this->world->product->update(['priority' => 0, 'name' => 'Zz Base Item']);
    ($this->addProduct)('Apple', 0);
    ($this->addProduct)('Banana', 5);
    ($this->addProduct)('Cherry', 10);
    ($this->addProduct)('Avocado', 5);

    $names = collect($this->getJson($this->world->url('products'))->assertOk()->json())->pluck('name')->all();

    expect($names)->toBe(['Cherry', 'Avocado', 'Banana', 'Apple', 'Zz Base Item']);
});

it('lists the lowest priority number first by default', function (): void {
    $this->world->product->update(['priority' => 9, 'name' => 'Zz Base Item']);
    ($this->addProduct)('Banana', 5);
    ($this->addProduct)('Cherry', 0);
    ($this->addProduct)('Avocado', 5);

    $names = collect($this->getJson($this->world->url('products'))->assertOk()->json())->pluck('name')->all();

    expect($names)->toBe(['Cherry', 'Avocado', 'Banana', 'Zz Base Item']);
});

it('lists by name when the order is set to name', function (): void {
    Configuration::updateOrCreate(['key' => 'app_product_sort_by'], ['value' => 'name']);
    Configuration::updateOrCreate(['key' => 'app_product_sort_direction'], ['value' => 'desc']);
    $this->world->product->update(['priority' => 9, 'name' => 'Mango']);
    ($this->addProduct)('Banana', 0);
    ($this->addProduct)('Cherry', 5);

    $names = collect($this->getJson($this->world->url('products'))->assertOk()->json())->pluck('name')->all();

    expect($names)->toBe(['Mango', 'Cherry', 'Banana']);
});

it('orders services by priority too', function (): void {
    Configuration::updateOrCreate(['key' => 'app_product_sort_direction'], ['value' => 'desc']);
    ($this->addProduct)('Haircut', 1, 'service');
    ($this->addProduct)('Facial', 3, 'service');

    $names = collect($this->getJson($this->world->url('products?type=service'))->assertOk()->json())->pluck('name')->all();

    expect($names)->toBe(['Facial', 'Haircut']);
});

it('saves the priority from the product form', function (): void {
    Queue::fake();
    $this->world->user->givePermissionTo(Permission::firstOrCreate(['name' => 'product.edit', 'guard_name' => 'web']));
    $this->world->product->update(['barcode_number' => '12345678']);

    Livewire::test(Page::class, ['type' => 'product', 'table_id' => $this->world->product->id])
        ->set('products.priority', 7)
        ->call('save', 1)
        ->assertHasNoErrors()
        ->assertNotDispatched('error');

    expect($this->world->product->fresh()->priority)->toBe(7);
});

<?php

use App\Livewire\Purchase\Page;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * The purchase "Browse catalogue" drawer: a per-column filtered product list
 * where a size / colour family is one entry, and picked lines (each with its
 * own quantity) are added to the purchase in one go.
 */
beforeEach(function (): void {
    Queue::fake();
    $this->world = PosWorld::create();
    $this->world->user->givePermissionTo(Permission::firstOrCreate(['name' => 'purchase.create', 'guard_name' => 'web']));
    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);

    $this->shoe = $this->world->product;
    $this->shoe->update(['name' => 'Running Shoe', 'code' => 'RS-41-BK', 'size' => '41', 'color' => 'Black']);
    $this->blue = Product::factory()->variantOf($this->shoe, '42', 'Blue')->create(['code' => 'RS-42-BL', 'barcode_number' => '77001122']);
    $this->hexBlue = Product::factory()->variantOf($this->shoe, '44', '#2563eb')->create(['code' => 'RS-44-HX']);
    $this->plain = Product::factory()->variantOf($this->shoe, '43')->create(['code' => 'RS-43']);
    $this->cap = Product::factory()->create(array_merge($this->shoe->only(['unit_id', 'department_id', 'main_category_id']), [
        'name' => 'Trail Cap', 'code' => 'CAP-1',
    ]));

    $this->catalogue = fn (array $query = []) => $this->getJson($this->world->url('/product/catalogue?'.http_build_query($query)))->assertSuccessful();
});

function catalogueStock(PosWorld $world, Product $product, float $quantity, ?int $employeeId = null): void
{
    DB::table('inventories')->insert([
        'tenant_id' => $world->tenant->id,
        'branch_id' => $world->branch->id,
        'product_id' => $product->id,
        'employee_id' => $employeeId,
        'quantity' => $quantity,
        'batch' => (string) Str::uuid(),
        'cost' => 10,
        'barcode_prefix' => '',
        'barcode_number' => (string) random_int(10000000, 99999999),
        'created_by' => $world->user->id,
        'updated_by' => $world->user->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

it('lists a family as one entry carrying every member, next to standalone products', function (): void {
    $data = ($this->catalogue)()->json('data');

    expect(collect($data)->pluck('name')->all())->toBe(['Running Shoe', 'Trail Cap']);

    $family = $data[0];
    expect($family['is_family'])->toBeTrue()
        ->and($family['variant_count'])->toBe(4)
        ->and(collect($family['members'])->pluck('code')->all())->toBe(['RS-41-BK', 'RS-42-BL', 'RS-44-HX', 'RS-43'])
        ->and($data[1]['is_family'])->toBeFalse()
        ->and($data[1]['members'])->toHaveCount(1);
});

it('narrows a family to the members matching the size, SKU and barcode filters', function (array $filter, array $codes): void {
    $data = ($this->catalogue)($filter)->json('data');

    expect($data)->toHaveCount(1)
        ->and($data[0]['name'])->toBe('Running Shoe')
        ->and(collect($data[0]['members'])->pluck('code')->all())->toBe($codes);
})->with([
    'size' => [['size' => '42'], ['RS-42-BL']],
    'sku' => [['sku' => 'rs-43'], ['RS-43']],
    'barcode' => [['barcode' => '7700'], ['RS-42-BL']],
]);

it('finds a colour by name even when the variant stored a picked hex', function (): void {
    $members = collect(($this->catalogue)(['colour' => 'blue'])->json('data.0.members'));

    expect($members->pluck('code')->all())->toBe(['RS-42-BL', 'RS-44-HX'])
        ->and($members->pluck('colour')->all())->toBe(['Blue', 'Blue'])
        ->and($members->last()['colour_hex'])->toBe('#2563eb');
});

it('matches every word of the name filter', function (): void {
    expect(collect(($this->catalogue)(['name' => 'shoe run'])->json('data'))->pluck('name')->all())->toBe(['Running Shoe'])
        ->and(($this->catalogue)(['name' => 'shoe cap'])->json('data'))->toBe([]);
});

it('lists every product on its own row when grouping is off', function (): void {
    $response = ($this->catalogue)(['group' => 0]);

    expect($response->json('meta.total'))->toBe(5)
        ->and(collect($response->json('data'))->firstWhere('code', 'RS-42-BL')['family_id'])->toBe($this->shoe->id)
        ->and(collect($response->json('data'))->firstWhere('code', 'RS-42-BL')['variant'])->toBe('42 / Blue');
});

it('shows the current branch stock without stock held by employees', function (): void {
    catalogueStock($this->world, $this->blue, 7);
    catalogueStock($this->world, $this->blue, 5, $this->world->user->id);

    $members = collect(($this->catalogue)()->json('data.0.members'))->keyBy('code');

    expect($members['RS-41-BK']['stock'])->toEqual(100)
        ->and($members['RS-42-BL']['stock'])->toEqual(7)
        ->and($members['RS-43']['stock'])->toEqual(0);
});

it('sorts the flat list by the chosen column and direction', function (): void {
    expect(collect(($this->catalogue)(['group' => 0, 'sort' => 'code', 'direction' => 'desc'])->json('data'))->pluck('code')->all())
        ->toBe(['RS-44-HX', 'RS-43', 'RS-42-BL', 'RS-41-BK', 'CAP-1'])
        ->and(collect(($this->catalogue)(['group' => 0, 'sort' => 'code'])->json('data'))->pluck('code')->all())
        ->toBe(['CAP-1', 'RS-41-BK', 'RS-42-BL', 'RS-43', 'RS-44-HX']);
});

it('sorts families by the stock of the whole family', function (): void {
    catalogueStock($this->world, $this->blue, 7);
    catalogueStock($this->world, $this->cap, 105);

    expect(collect(($this->catalogue)(['sort' => 'stock', 'direction' => 'desc'])->json('data'))->pluck('name')->all())
        ->toBe(['Running Shoe', 'Trail Cap'])
        ->and(collect(($this->catalogue)(['sort' => 'stock', 'direction' => 'asc'])->json('data'))->pluck('name')->all())
        ->toBe(['Trail Cap', 'Running Shoe']);
});

it('rejects an unknown sort column', function (): void {
    $this->getJson($this->world->url('/product/catalogue?sort=password'))->assertUnprocessable()->assertJsonValidationErrors('sort');
});

it('adds the picked catalogue lines with their quantities, merging into existing lines', function (): void {
    $component = Livewire::test(Page::class)
        ->set('product_id', $this->blue->id)
        ->call('addCatalogueItems', [
            ['product_id' => $this->shoe->id, 'quantity' => 3],
            ['product_id' => $this->blue->id, 'quantity' => 2],
            ['product_id' => $this->plain->id, 'quantity' => 0],
            ['product_id' => 'x', 'quantity' => 4],
        ])
        ->assertReturned(2);

    $items = collect($component->get('items'))->values();

    expect($items->pluck('product_id')->all())->toBe([$this->shoe->id, $this->blue->id])
        ->and($items->pluck('quantity')->map(fn ($quantity) => (float) $quantity)->all())->toBe([3.0, 3.0])
        ->and($items->first()['variant'])->toBe('41 / Black')
        ->and((float) $component->get('purchases.total_quantity'))->toBe(6.0);
});

it('never adds a service from the catalogue', function (): void {
    $this->cap->update(['type' => 'service']);

    Livewire::test(Page::class)
        ->call('addCatalogueItems', [['product_id' => $this->cap->id, 'quantity' => 1]])
        ->assertReturned(0)
        ->assertSet('items', []);
});

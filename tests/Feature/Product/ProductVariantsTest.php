<?php

use App\Actions\Product\CreateAction;
use App\Actions\Product\DeleteAction;
use App\Actions\Product\UpdateAction;
use App\Actions\Product\Variant\CreateVariantsAction;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\InventoryLog;
use App\Models\Product;
use App\Models\Tenant;
use App\Services\TenantService;
use App\Support\ProductColours;
use App\Support\TenantCache;
use Illuminate\Support\Facades\Queue;
use Tests\Support\PosWorld;

/**
 * Size / colour variants: one family, every variant its own product with its own
 * barcode and stock. The worked example is the shoe from the spec: the original
 * 41 / Black plus 42 Black, 42 Blue, 43 Black and 44 Black.
 */
beforeEach(function (): void {
    Queue::fake();
    $this->world = PosWorld::create();
    $this->mall = Branch::create(['tenant_id' => $this->world->tenant->id, 'name' => 'Mall', 'code' => 'ML']);
    TenantCache::forget('branches');
    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);

    $this->shoe = $this->world->product;
    $this->shoe->update(['name' => 'Running Shoe', 'size' => '41', 'color' => 'Black', 'cost' => 85, 'mrp' => 149, 'barcode_number' => '62910044101']);
});

function shoeRows(): array
{
    return [
        ['size' => '42', 'color' => 'Black', 'cost' => 85, 'mrp' => 149, 'opening_stock' => 8],
        ['size' => '42', 'color' => 'Blue', 'cost' => 85, 'mrp' => 149, 'opening_stock' => 3],
        ['size' => '43', 'color' => 'Black', 'cost' => 85, 'mrp' => 149, 'opening_stock' => 10],
        ['size' => '44', 'color' => 'Black', 'cost' => 85, 'mrp' => 149, 'opening_stock' => ''],
    ];
}

it('creates each variant as its own product with stock only in the current branch', function (): void {
    $inventoryBefore = Inventory::count();
    $logsBefore = InventoryLog::count();

    $response = (new CreateVariantsAction())->execute($this->shoe->id, shoeRows(), $this->world->user->id);

    expect($response['success'])->toBeTrue($response['message']);

    $variants = Product::where('parent_id', $this->shoe->id)->orderBy('id')->get();
    expect($variants)->toHaveCount(4)
        ->and($variants->map(fn (Product $p) => Product::variantLabel($p->size, $p->color))->all())
        ->toBe(['42 / Black', '42 / Blue', '43 / Black', '44 / Black'])
        ->and($variants->pluck('name')->unique()->all())->toBe(['Running Shoe'])
        ->and($variants->pluck('main_category_id')->unique()->all())->toBe([$this->shoe->main_category_id])
        ->and($variants->pluck('barcode_number')->unique())->toHaveCount(4)
        ->and($variants->pluck('size_category')->unique()->all())->toBe(['adult']);

    expect(Inventory::count() - $inventoryBefore)->toBe(8);

    $mainStock = Inventory::whereIn('product_id', $variants->pluck('id'))
        ->where('branch_id', $this->world->branch->id)->pluck('quantity', 'product_id')->map(fn ($q) => (float) $q);
    expect($mainStock->values()->all())->toBe([8.0, 3.0, 10.0, 0.0])
        ->and(Inventory::whereIn('product_id', $variants->pluck('id'))->where('branch_id', $this->mall->id)->sum('quantity'))->toEqual(0)
        ->and(InventoryLog::count() - $logsBefore)->toBe(3);

    expect($this->shoe->fresh()->parent_id)->toBeNull()
        ->and(Product::familyHeads()->where('name', 'Running Shoe')->count())->toBe(1);
});

it('creates size-only variants with no colour', function (): void {
    $this->shoe->update(['name' => 'Gym Shorts', 'size' => 'M', 'color' => null]);

    $response = (new CreateVariantsAction())->execute($this->shoe->id, [
        ['size' => 'S'], ['size' => 'L'], ['size' => 'XL'],
    ], $this->world->user->id);

    expect($response['success'])->toBeTrue($response['message'])
        ->and(Product::where('parent_id', $this->shoe->id)->whereNull('color')->count())->toBe(3);
});

it('rejects a size + colour the family already has, ignoring case and blanks', function (array $rows): void {
    $response = (new CreateVariantsAction())->execute($this->shoe->id, $rows, $this->world->user->id);

    expect($response['success'])->toBeFalse()
        ->and(Product::where('parent_id', $this->shoe->id)->count())->toBe(0);
})->with([
    'same as the original' => [[['size' => '41', 'color' => 'black ']]],
    'twice in one batch' => [[['size' => '42', 'color' => 'Blue'], ['size' => '42', 'color' => 'BLUE']]],
    'two plain sizes' => [[['size' => '43'], ['size' => ' 43 ', 'color' => '']]],
    'no size' => [[['size' => '', 'color' => 'Blue']]],
    'barcode twice in one batch' => [[['size' => '42', 'barcode_number' => '777'], ['size' => '43', 'barcode_number' => '777']]],
]);

it('rejects a barcode another product already uses and writes nothing', function (): void {
    $taken = $this->shoe->barcode_number;

    $response = (new CreateVariantsAction())->execute($this->shoe->id, [
        ['size' => '42', 'color' => 'Black'],
        ['size' => '43', 'color' => 'Black', 'barcode_number' => $taken],
    ], $this->world->user->id);

    expect($response['success'])->toBeFalse()
        ->and(Product::where('parent_id', $this->shoe->id)->count())->toBe(0);
});

it('does not let a variant have variants', function (): void {
    $variant = Product::factory()->variantOf($this->shoe, '42', 'Blue')->create();

    $response = (new CreateVariantsAction())->execute($variant->id, [['size' => '44', 'color' => 'Blue']], $this->world->user->id);

    expect($response['success'])->toBeFalse();
});

it('lets siblings share a name but keeps standalone names unique', function (): void {
    Product::factory()->variantOf($this->shoe, '42', 'Blue')->create();

    $standalone = (new CreateAction())->execute([
        'type' => 'product', 'name' => 'Running Shoe', 'unit_id' => $this->shoe->unit_id,
        'department_id' => $this->shoe->department_id, 'main_category_id' => $this->shoe->main_category_id,
        'cost' => 1, 'mrp' => 2,
    ], $this->world->user->id);

    expect($standalone['success'])->toBeFalse()
        ->and(Product::where('name', 'Running Shoe')->count())->toBe(2);
});

it('never revives a deleted sibling when another size is created', function (): void {
    $deleted = Product::factory()->variantOf($this->shoe, '43', 'Blue')->create();
    $deleted->delete();

    $response = (new CreateVariantsAction())->execute($this->shoe->id, [['size' => '44', 'color' => 'Black']], $this->world->user->id);

    expect($response['success'])->toBeTrue($response['message'])
        ->and(Product::onlyTrashed()->find($deleted->id))->not->toBeNull()
        ->and($response['data'][0]->id)->not->toBe($deleted->id);
});

it('revives a deleted variant of the same size and colour instead of duplicating it', function (): void {
    $deleted = Product::factory()->variantOf($this->shoe, '43', 'Blue')->create();
    $deleted->delete();

    $response = (new CreateVariantsAction())->execute($this->shoe->id, [['size' => '43', 'color' => 'blue']], $this->world->user->id);

    expect($response['success'])->toBeTrue($response['message'])
        ->and($response['data'][0]->id)->toBe($deleted->id);
});

it('pushes the head\'s shared fields to every variant and keeps their own fields', function (): void {
    $variant = Product::factory()->variantOf($this->shoe, '42', 'Blue')->create(['mrp' => 155, 'barcode_number' => '42424242']);
    $data = array_merge($this->shoe->fresh()->toArray(), ['name' => 'Trail Shoe', 'tax' => 5]);

    $response = (new UpdateAction())->execute($data, $this->shoe->id, $this->world->user->id);

    $variant->refresh();
    expect($response['success'])->toBeTrue($response['message'])
        ->and($variant->name)->toBe('Trail Shoe')
        ->and((float) $variant->tax)->toBe(5.0)
        ->and((float) $variant->mrp)->toBe(155.0)
        ->and($variant->barcode_number)->toBe('42424242')
        ->and($variant->size)->toBe('42')
        ->and($variant->color)->toBe('Blue');
});

it('keeps a variant on its head\'s shared fields and refuses a duplicate size on edit', function (): void {
    $variant = Product::factory()->variantOf($this->shoe, '42', 'Blue')->create();

    $renamed = (new UpdateAction())->execute(array_merge($variant->toArray(), ['name' => 'Something Else']), $variant->id, $this->world->user->id);
    expect($renamed['success'])->toBeTrue($renamed['message'])
        ->and($variant->fresh()->name)->toBe('Running Shoe');

    $clash = (new UpdateAction())->execute(array_merge($variant->fresh()->toArray(), ['size' => '41', 'color' => 'BLACK']), $variant->id, $this->world->user->id);
    expect($clash['success'])->toBeFalse()
        ->and($variant->fresh()->size)->toBe('42');
});

it('blocks deleting a head that still has variants', function (): void {
    Product::factory()->variantOf($this->shoe, '42', 'Blue')->create();

    $response = (new DeleteAction())->execute($this->shoe->id);

    expect($response['success'])->toBeFalse()
        ->and(Product::find($this->shoe->id))->not->toBeNull();
});

it('names a variant with its size and colour', function (): void {
    $blue = Product::factory()->variantOf($this->shoe, '42', 'Blue')->create();
    $plain = Product::factory()->variantOf($this->shoe, '43')->create();

    expect($blue->displayName())->toBe('Running Shoe - 42 / Blue')
        ->and($plain->displayName())->toBe('Running Shoe - 43')
        ->and($this->shoe->fresh()->displayName())->toBe('Running Shoe - 41 / Black');
});

it('remembers chosen colours once per name', function (): void {
    ProductColours::remember([['name' => 'Navy', 'hex' => '#1E3A8A'], ['name' => 'navy', 'hex' => '#000000'], ['name' => '', 'hex' => '#ffffff']]);
    ProductColours::remember([['name' => 'Rose Gold', 'hex' => '#b76e79'], ['name' => 'Bad', 'hex' => 'red']]);

    expect(ProductColours::all())->toBe([
        ['name' => 'Navy', 'hex' => '#1e3a8a'],
        ['name' => 'Rose Gold', 'hex' => '#b76e79'],
    ]);
});

it('never sees another tenant\'s family', function (): void {
    Product::factory()->variantOf($this->shoe, '42', 'Blue')->create();
    $other = Tenant::factory()->create();
    app(TenantService::class)->setCurrentTenant($other);

    expect(Product::where('parent_id', $this->shoe->id)->count())->toBe(0)
        ->and(Product::familyCombinationTaken($this->shoe->id, '42', 'Blue'))->toBeFalse();
});

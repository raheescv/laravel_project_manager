<?php

use App\Livewire\Product\Table;
use App\Models\Product;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * The product list shows a size / colour family as one row (expandable to its
 * variants) and can switch to one row per variant.
 */
beforeEach(function (): void {
    Queue::fake();
    $this->world = PosWorld::create();
    $this->world->user->givePermissionTo(Permission::firstOrCreate(['name' => 'product.delete', 'guard_name' => 'web']));
    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);

    $this->shoe = $this->world->product;
    $this->shoe->update(['name' => 'Running Shoe', 'size' => '41', 'color' => 'Black']);
    $this->blue = Product::factory()->variantOf($this->shoe, '42', 'Blue')->create(['barcode_number' => '77001122']);
    $this->plain = Product::factory()->variantOf($this->shoe, '43')->create();
});

it('shows a family as one row with its sizes and expands to the variants', function (): void {
    $table = Livewire::test(Table::class)
        ->assertSee('3 variants')
        ->assertDontSee('42 / Blue');

    expect($table->viewData('data')->total())->toBe(1);

    $table->call('toggleFamily', $this->shoe->id)
        ->assertSee('42 / Blue')
        ->assertSee('Original')
        ->call('toggleFamily', $this->shoe->id)
        ->assertDontSee('42 / Blue');
});

it('finds the family by a variant\'s barcode or size', function (): void {
    expect(Livewire::test(Table::class)->set('search', '77001122')->viewData('data')->pluck('id')->all())->toBe([$this->shoe->id])
        ->and(Livewire::test(Table::class)->set('size', '43')->viewData('data')->pluck('id')->all())->toBe([$this->shoe->id]);
});

it('lists every variant on its own row when grouping is off', function (): void {
    $table = Livewire::test(Table::class)->set('groupVariants', false);

    expect($table->viewData('data')->total())->toBe(3);
    $table->assertSee('— 42 / Blue')->assertSee('— 41 / Black');
});

it('refuses to delete a family head that still has variants', function (): void {
    Livewire::test(Table::class)
        ->set('selected', [$this->shoe->id])
        ->call('delete')
        ->assertDispatched('error');

    expect(Product::find($this->shoe->id))->not->toBeNull();
});

it('filters to main products (parent_id null) or to variants only', function (): void {
    $loose = Product::factory()->create(['name' => 'Loose Socks'] + $this->shoe->only(['unit_id', 'department_id', 'main_category_id']));

    $main = Livewire::test(Table::class)->set('groupVariants', false)->set('variant', 'main');
    expect($main->viewData('data')->pluck('id')->sort()->values()->all())->toBe(collect([$this->shoe->id, $loose->id])->sort()->values()->all());

    $variants = Livewire::test(Table::class)->set('variant', 'variants')->assertSet('groupVariants', false);
    expect($variants->viewData('data')->pluck('id')->sort()->values()->all())->toBe(collect([$this->blue->id, $this->plain->id])->sort()->values()->all());

    $variants->set('groupVariants', true)->assertSet('variant', '');
});

it('passes the variant filter to the export', function (): void {
    $loose = Product::factory()->create(['name' => 'Loose Socks'] + $this->shoe->only(['unit_id', 'department_id', 'main_category_id']));

    $mainIds = (new \App\Exports\ProductExport(['variant' => 'main']))->query()->pluck('id')->sort()->values()->all();
    $variantIds = (new \App\Exports\ProductExport(['variant' => 'variants']))->query()->pluck('id')->sort()->values()->all();

    expect($mainIds)->toBe(collect([$this->shoe->id, $loose->id])->sort()->values()->all())
        ->and($variantIds)->toBe(collect([$this->blue->id, $this->plain->id])->sort()->values()->all());
});

<?php

use App\Livewire\Product\Page;
use App\Livewire\Product\Variants;
use App\Models\Product;
use App\Support\ProductColours;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * The product page's "Variants" tab (Swatch Board): pick sizes and optional
 * colours, tick the cells, create them, and see a variant's own page point back
 * to its family.
 */
beforeEach(function (): void {
    Queue::fake();
    $this->world = PosWorld::create();
    foreach (['product.view', 'product.edit', 'product.create'] as $permission) {
        $this->world->user->givePermissionTo(Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']));
    }
    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);

    $this->shoe = $this->world->product;
    $this->shoe->update(['name' => 'Running Shoe', 'size' => '41', 'color' => 'Black', 'barcode_number' => '62910044101']);
});

it('creates the ticked sizes and colours as variants', function (): void {
    $tab = Livewire::test(Variants::class, ['product_id' => $this->shoe->id])
        ->assertSee('Sizes')
        ->assertSee('Black');

    foreach (['42', '43'] as $size) {
        $tab->set('newSize', $size)->call('addSize');
    }
    $tab->call('addColour', 'Navy', '#1E3A8A');

    $component = $tab->instance();
    $tab->call('toggle', $component->cellKey('42', 'Black'))
        ->call('toggle', $component->cellKey('42', 'Navy'))
        ->call('toggle', $component->cellKey('43', 'Black'))
        ->set('picked.'.$component->cellKey('43', 'Black').'.opening_stock', '5')
        ->assertSee('Create 3 variants')
        ->call('save')
        ->assertDispatched('success')
        ->assertSet('picked', []);

    $variants = Product::where('parent_id', $this->shoe->id)->get();
    expect($variants->map(fn (Product $p) => Product::variantLabel($p->size, $p->color))->sort()->values()->all())
        ->toBe(['42 / Black', '42 / Navy', '43 / Black'])
        ->and(collect(ProductColours::all())->firstWhere('name', 'Navy'))->toBe(['name' => 'Navy', 'hex' => '#1e3a8a']);

    $tab->assertSee('Made');
});

it('never toggles a cell that already exists and reports a failed save', function (): void {
    Product::factory()->variantOf($this->shoe, '42', 'Black')->create();
    $tab = Livewire::test(Variants::class, ['product_id' => $this->shoe->id]);
    $made = $tab->instance()->cellKey('42', 'Black');

    $tab->call('toggle', $made)->assertSet('picked', []);

    $tab->set('newSize', '44')->call('addSize')
        ->call('toggle', $tab->instance()->cellKey('44', 'Black'))
        ->set('picked.'.$tab->instance()->cellKey('44', 'Black').'.mrp', 'abc')
        ->call('save')
        ->assertDispatched('error');

    expect(Product::where('parent_id', $this->shoe->id)->count())->toBe(1);
});

it('picks every new size automatically for a size-only product', function (): void {
    $this->shoe->update(['name' => 'Gym Shorts', 'size' => 'M', 'color' => null]);

    $tab = Livewire::test(Variants::class, ['product_id' => $this->shoe->id])
        ->assertSee('sizes only')
        ->set('newSize', 'L')->call('addSize')
        ->set('newSize', 'l')->call('addSize');

    expect($tab->get('sizes'))->toBe(['M', 'L'])
        ->and($tab->instance()->orderedPickedKeys())->toHaveCount(1);
});

it('asks for the original\'s size first', function (): void {
    $this->shoe->update(['size' => null]);

    Livewire::test(Variants::class, ['product_id' => $this->shoe->id])
        ->assertSee('What size is Running Shoe?')
        ->set('baseSize', '41')
        ->call('saveBaseSize')
        ->assertSee('Sizes');

    expect($this->shoe->fresh()->size)->toBe('41');
});

it('shows a variant its family instead of the board', function (): void {
    $variant = Product::factory()->variantOf($this->shoe, '42', 'Blue')->create();

    Livewire::test(Variants::class, ['product_id' => $variant->id])
        ->assertSee('Variant of Running Shoe')
        ->assertSee('41 / Black')
        ->assertSee('42 / Blue')
        ->assertDontSee('Choose a colour');

    Livewire::test(Page::class, ['type' => 'product', 'table_id' => $variant->id])
        ->assertSee('a variant of');
});

it('shows a colour the product form saved as a hex in that colour, under its name', function (): void {
    $this->shoe->update(['size' => 'S', 'color' => '#ff0000']);

    $tab = Livewire::test(Variants::class, ['product_id' => $this->shoe->id])
        ->assertSee('#FF0000')
        ->assertSee('Red')
        ->assertSee('S / Red');

    expect($tab->get('hex')['#ff0000'])->toBe('#ff0000');
    $tab->assertViewHas('quickAdd', fn (array $names) => ! in_array('Red', $names, true));
});

it('removes an added colour and its picks, but never one that already has variants', function (): void {
    $tab = Livewire::test(Variants::class, ['product_id' => $this->shoe->id])
        ->set('newSize', '42')->call('addSize')
        ->call('addColour', 'Orange', '#ea580c');
    $tab->call('toggle', $tab->instance()->cellKey('42', 'Orange'))
        ->call('removeColour', 'Orange')
        ->call('removeColour', 'Black');

    expect($tab->get('colours'))->toBe(['Black'])
        ->and($tab->get('picked'))->toBe([]);
});

it('groups the price table by colour or size and prices one group at a time', function (): void {
    $tab = Livewire::test(Variants::class, ['product_id' => $this->shoe->id])
        ->set('newSize', '42')->call('addSize')
        ->set('newSize', '43')->call('addSize')
        ->call('addColour', 'Navy', '#1e3a8a');
    $component = $tab->instance();
    foreach ([['42', 'Black'], ['43', 'Black'], ['42', 'Navy']] as [$size, $colour]) {
        $tab->call('toggle', $component->cellKey($size, $colour));
    }
    $groupKey = fn (string $value) => collect($tab->instance()->groups())->firstWhere('value', $value)['key'];

    $tab->set('groupBulk.'.$groupKey('Black').'.mrp', '199')->call('applyToGroup', $groupKey('Black'));
    $picked = $tab->get('picked');
    expect($picked[$component->cellKey('42', 'Black')]['mrp'])->toBe('199')
        ->and($picked[$component->cellKey('43', 'Black')]['mrp'])->toBe('199')
        ->and($picked[$component->cellKey('42', 'Navy')]['mrp'])->not->toBe('199')
        ->and(collect($tab->instance()->groups())->pluck('value')->all())->toBe(['Black', 'Navy']);

    $tab->call('setGroupBy', 'size')->assertSee('Same for Size 42');
    $tab->set('groupBulk.'.$groupKey('42').'.cost', '60')->call('applyToGroup', $groupKey('42'));
    $picked = $tab->get('picked');
    expect($picked[$component->cellKey('42', 'Navy')]['cost'])->toBe('60')
        ->and($picked[$component->cellKey('42', 'Black')]['cost'])->toBe('60')
        ->and($picked[$component->cellKey('43', 'Black')]['cost'])->not->toBe('60');
});

it('lets every open size be picked under a colour loaded before that size', function (): void {
    $this->shoe->update(['size' => 'L', 'color' => '#ff0000']);
    Product::factory()->variantOf($this->shoe, 'L', 'Teal')->create();
    Product::factory()->variantOf($this->shoe, 'M', 'Black')->create();
    Product::factory()->variantOf($this->shoe, 'S', 'Black')->create();

    $tab = Livewire::test(Variants::class, ['product_id' => $this->shoe->id]);
    foreach (['M', 'S'] as $size) {
        foreach (['#ff0000', 'Teal'] as $colour) {
            $tab->call('toggle', $tab->instance()->cellKey($size, $colour));
        }
    }

    expect($tab->instance()->orderedPickedKeys())->toHaveCount(4);
    $tab->call('toggleColour', '#ff0000');
    expect($tab->instance()->orderedPickedKeys())->toHaveCount(2);
});

it('edits the barcode and prices of variants that already exist', function (): void {
    $blue = Product::factory()->variantOf($this->shoe, '42', 'Blue')->create(['barcode_number' => '42000001', 'cost' => 85, 'mrp' => 149]);
    $navy = Product::factory()->variantOf($this->shoe, '43', 'Blue')->create(['barcode_number' => '43000001', 'cost' => 85, 'mrp' => 149]);
    $tab = Livewire::test(Variants::class, ['product_id' => $this->shoe->id]);
    $blueKey = $tab->instance()->cellKey('42', 'Blue');
    $groupKey = collect($tab->instance()->groups())->firstWhere('value', 'Blue')['key'];

    $tab->assertDontSee('Update')
        ->set("edits.{$blueKey}.barcode_number", '42000099')
        ->assertSee('Edited')
        ->assertSee('Update 1')
        ->set("groupBulk.{$groupKey}.mrp", '175')
        ->call('applyToGroup', $groupKey)
        ->assertSee('Update 2')
        ->call('save')
        ->assertDispatched('success')
        ->assertDontSee('Edited');

    expect($blue->fresh()->barcode_number)->toBe('42000099')
        ->and((float) $blue->fresh()->mrp)->toBe(175.0)
        ->and((float) $navy->fresh()->mrp)->toBe(175.0)
        ->and((float) $this->shoe->fresh()->mrp)->not->toBe(175.0);
});

it('keeps every variant unchanged when one edited barcode is already taken', function (): void {
    $blue = Product::factory()->variantOf($this->shoe, '42', 'Blue')->create(['barcode_number' => '42000001', 'mrp' => 149]);
    $tab = Livewire::test(Variants::class, ['product_id' => $this->shoe->id]);

    $tab->set('edits.'.$tab->instance()->cellKey('42', 'Blue').'.mrp', '199')
        ->set('edits.'.$tab->instance()->cellKey('41', 'Black').'.barcode_number', '42000001')
        ->call('save')
        ->assertDispatched('error');

    expect((float) $blue->fresh()->mrp)->toBe(149.0)
        ->and($this->shoe->fresh()->barcode_number)->toBe('62910044101');
});

it('never stores a hex code as a colour name', function (): void {
    ProductColours::remember([['name' => '#ff0000', 'hex' => '#ff0000'], ['name' => 'Navy', 'hex' => '#1e3a8a']]);

    expect(collect(ProductColours::all())->pluck('name')->all())->toBe(['Navy'])
        ->and(ProductColours::displayName('#ff0000'))->toBe('Red');
});

it('refuses to create variants without product.create', function (): void {
    $this->world->user->revokePermissionTo('product.create');
    $tab = Livewire::test(Variants::class, ['product_id' => $this->shoe->id])
        ->set('newSize', '42')->call('addSize');

    $tab->call('toggle', $tab->instance()->cellKey('42', 'Black'))
        ->call('save')
        ->assertForbidden();
});

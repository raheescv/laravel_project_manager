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
 * The product page's "Variants" tab, in three steps: name the layers and list
 * their values, tick the ones that exist on a grid, price them — and a
 * variant's own page points back to its family.
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
        ->call('goToStep', 3)
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
        ->assertSee('Does it come in colours?')
        ->assertSet('coloursOn', false)
        ->set('newSize', 'L')->call('addSize')
        ->set('newSize', 'l')->call('addSize');

    expect($tab->get('sizes'))->toBe(['M', 'L'])
        ->and($tab->instance()->orderedPickedKeys())->toHaveCount(1);
});

it('asks for the original\'s size first', function (): void {
    $this->shoe->update(['size' => null]);

    Livewire::test(Variants::class, ['product_id' => $this->shoe->id])
        ->assertSee('Which design and size is Running Shoe?')
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
        ->assertSee('Red')
        ->call('goToStep', 3)
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

it('groups the prices by colour when there is no first layer and prices one group at a time', function (): void {
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

    $tab->call('goToStep', 3)->assertSee('Same for Navy');
});

it('groups the prices by the first layer when the family has one', function (): void {
    $this->shoe->update(['color' => null, 'variant_value' => 'Soda', 'size' => 'Mango']);
    $tab = Livewire::test(Variants::class, ['product_id' => $this->shoe->id])
        ->set('newDesign', 'Red Bull')->call('addDesign')
        ->set('newSize', 'Lemon')->call('addSize')
        ->call('toggleColumn', 'Red Bull');

    expect($tab->instance()->groupField())->toBe('design')
        ->and(collect($tab->instance()->groups())->pluck('value')->all())->toBe(['Soda', 'Red Bull'])
        ->and(collect($tab->instance()->groups())->firstWhere('value', 'Red Bull')['picked'])->toHaveCount(2);
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
    $tab->call('toggleColumn', '#ff0000');
    expect($tab->instance()->orderedPickedKeys())->toHaveCount(2);
});

it('edits the barcode and prices of variants that already exist', function (): void {
    $blue = Product::factory()->variantOf($this->shoe, '42', 'Blue')->create(['barcode_number' => '42000001', 'cost' => 85, 'mrp' => 149]);
    $navy = Product::factory()->variantOf($this->shoe, '43', 'Blue')->create(['barcode_number' => '43000001', 'cost' => 85, 'mrp' => 149]);
    $tab = Livewire::test(Variants::class, ['product_id' => $this->shoe->id])->call('goToStep', 3);
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

it('puts sizes and colours under each design', function (): void {
    $this->shoe->update(['name' => 'Polo Shirt', 'size' => null, 'color' => 'Navy', 'variant_value' => 'Floral']);

    $tab = Livewire::test(Variants::class, ['product_id' => $this->shoe->id])
        ->assertSet('designs', ['Floral'])
        ->assertSet('sizes', [''])
        ->set('newSize', 'M')->call('addSize')
        ->set('newSize', 'L')->call('addSize')
        ->set('newDesign', 'Striped')->call('addDesign');

    $component = $tab->instance();
    $tab->call('toggle', $component->cellKey('L', 'Navy', 'Striped'))
        ->call('toggle', $component->cellKey('M', 'Navy', 'Floral'))
        ->call('goToStep', 3)
        ->assertSee('Create 2 variants')
        ->call('save')
        ->assertDispatched('success');

    $labels = Product::where('parent_id', $this->shoe->id)->get()->map(fn (Product $p) => $p->variantName())->sort()->values()->all();
    expect($labels)->toBe(['Floral / M / Navy', 'Striped / L / Navy'])
        ->and(Product::where('parent_id', $this->shoe->id)->where('variant_value', 'Striped')->sole()->displayName())
        ->toBe('Polo Shirt - Striped / L / Navy');

    $tab->assertSet('designs', ['Floral', 'Striped'])
        ->assertSet('sizes', ['', 'M', 'L']);
});

it('makes flavour-only variants with no size or colour', function (): void {
    $this->shoe->update(['name' => 'Power Drink', 'size' => null, 'color' => null, 'variant_value' => 'Lemongrass']);

    Livewire::test(Variants::class, ['product_id' => $this->shoe->id])
        ->call('setOption', 'Flavour')
        ->assertSee('Flavours')
        ->set('newDesign', 'Mango Passion')->call('addDesign')
        ->set('newDesign', 'Strawberry')->call('addDesign')
        ->call('goToStep', 3)
        ->assertSee('Create 2 variants')
        ->call('save')
        ->assertDispatched('success');

    $variants = Product::where('parent_id', $this->shoe->id)->orderBy('id')->get();
    expect($variants->pluck('variant_value')->all())->toBe(['Mango Passion', 'Strawberry'])
        ->and($variants->pluck('size')->filter()->all())->toBe([])
        ->and($variants->pluck('variant_option')->unique()->all())->toBe(['Flavour'])
        ->and($variants->first()->displayName())->toBe('Power Drink - Mango Passion');
});

it('refuses a design + size + colour the family already has', function (): void {
    $this->shoe->update(['variant_value' => 'Floral']);
    Product::factory()->variantOf($this->shoe, '42', 'Black')->create(['variant_value' => 'Striped']);

    $tab = Livewire::test(Variants::class, ['product_id' => $this->shoe->id]);
    $made = $tab->instance()->cellKey('42', 'Black', 'Striped');
    $tab->call('toggle', $made)->assertSet('picked', []);

    expect(Product::familyCombinationTaken($this->shoe->id, '42', 'Black', 0, 'striped'))->toBeTrue()
        ->and(Product::familyCombinationTaken($this->shoe->id, '42', 'Black', 0, 'Floral'))->toBeFalse();
});

it('moves flavours typed in as sizes into the flavour layer', function (): void {
    $this->shoe->update(['name' => 'Power Drink', 'size' => 'Lemongrass', 'color' => null]);
    Product::factory()->variantOf($this->shoe, 'Mango')->create();

    Livewire::test(Variants::class, ['product_id' => $this->shoe->id])
        ->call('setOption', 'Flavour')
        ->call('goToStep', 1)
        ->call('useTwoLayers', true)
        ->assertSee('Sizes are flavours')
        ->call('moveSizesToDesign')
        ->assertDispatched('success')
        ->assertSet('designs', ['Lemongrass', 'Mango'])
        ->assertSet('sizes', ['']);

    $family = Product::where('id', $this->shoe->id)->orWhere('parent_id', $this->shoe->id)->get();
    expect($family->pluck('size')->filter()->all())->toBe([])
        ->and($family->pluck('variant_value')->sort()->values()->all())->toBe(['Lemongrass', 'Mango']);
});

it('renames the first layer on every variant and goes back to Design', function (): void {
    $variant = Product::factory()->variantOf($this->shoe, '42', 'Black')->create();

    $tab = Livewire::test(Variants::class, ['product_id' => $this->shoe->id])
        ->set('customOption', 'scent')
        ->call('setOption')
        ->assertSet('option', 'Scent');

    expect($variant->fresh()->variant_option)->toBe('Scent');

    $tab->call('setOption', 'design')->assertSet('option', 'Design');
    expect($variant->fresh()->variant_option)->toBeNull();
});

it('names the second layer Flavour for a Base × Flavour pack, each pack its own item', function (): void {
    $this->shoe->update(['variant_value' => 'Red Bull', 'size' => 'Mango']);

    Livewire::test(Variants::class, ['product_id' => $this->shoe->id])
        ->call('setOption', 'Base')
        ->call('setSizeOption', 'Flavour')
        ->assertSet('sizeOption', 'Flavour')
        ->assertSee('Layer 2')
        ->assertSee('Flavours')
        ->assertSet('twoLayers', true)
        ->set('newSize', 'Passion')->call('addSize')
        ->set('newDesign', 'Soda')->call('addDesign')
        ->call('toggleColumn', 'Soda')
        ->call('save')
        ->assertDispatched('success');

    $family = Product::where('id', $this->shoe->id)->orWhere('parent_id', $this->shoe->id)->get();
    expect($family->pluck('variant_size_option')->unique()->all())->toBe(['Flavour'])
        ->and($family->pluck('variant_option')->unique()->all())->toBe(['Base'])
        ->and($family->where('variant_value', 'Soda')->pluck('size')->sort()->values()->all())->toBe(['Mango', 'Passion'])
        ->and($family->first()->sizeName())->toBe('Flavour');
});

it('keeps colours out of the way until the family comes in colours', function (): void {
    $this->shoe->update(['color' => null, 'variant_value' => 'Soda', 'size' => 'Mango']);

    $tab = Livewire::test(Variants::class, ['product_id' => $this->shoe->id])
        ->assertSet('coloursOn', false)
        ->assertSee('Does it come in colours?')
        ->assertDontSee('Choose a colour')
        ->set('newSize', 'Lemon')->call('addSize');

    $lemon = $tab->instance()->cellKey('Lemon', '', 'Soda');
    $tab->call('toggle', $lemon)
        ->call('useColours', true)
        ->assertSee('Choose a colour')
        ->call('addColour', 'Navy', '#1e3a8a')
        ->call('toggle', $tab->instance()->cellKey('Lemon', 'Navy', 'Soda'))
        ->call('useColours', false)
        ->assertSet('coloursOn', false)
        ->assertSet('colours', [''])
        ->assertSet('picked', fn (array $picked) => array_keys($picked) === [$lemon])
        ->call('save')
        ->assertDispatched('success');

    expect(Product::where('parent_id', $this->shoe->id)->where('size', 'Lemon')->where(fn ($q) => $q->whereNull('color')->orWhere('color', ''))->exists())->toBeTrue();
});

it('will not turn colours off while a made variant has one', function (): void {
    Livewire::test(Variants::class, ['product_id' => $this->shoe->id])
        ->assertSet('coloursOn', true)
        ->call('useColours', false)
        ->assertDispatched('error')
        ->assertSet('coloursOn', true)
        ->assertSet('colours', ['Black']);
});

it('opens a family that has variants on its grid, one colour at a time', function (): void {
    $this->shoe->update(['variant_value' => 'Soda', 'size' => 'Mango']);
    Product::factory()->variantOf($this->shoe, 'Lemon', 'Black')->create(['variant_value' => 'Soda']);

    $tab = Livewire::test(Variants::class, ['product_id' => $this->shoe->id])
        ->assertSet('step', 2)
        ->assertSee('Tap the ones you sell')
        ->call('addColour', 'Navy', '#1e3a8a')
        ->call('selectColour', 'Navy')
        ->assertSee('Showing Navy')
        ->call('toggleRow', 'Mango');

    expect($tab->get('picked'))->toHaveKey($tab->instance()->cellKey('Mango', 'Navy', 'Soda'))
        ->and($tab->instance()->gridKey('Lemon', 'Soda'))->toBe($tab->instance()->cellKey('Lemon', 'Navy', 'Soda'));
});

it('shows one layer until a second one is asked for', function (): void {
    $this->shoe->update(['name' => 'Fruit Tart', 'size' => 'S', 'color' => null]);

    $tab = Livewire::test(Variants::class, ['product_id' => $this->shoe->id])
        ->assertSet('twoLayers', false)
        ->assertSee('Comes in')
        ->assertDontSee('No design')
        ->assertDontSee('Sizes are designs');
    expect($tab->instance()->singleLayer())->toBe('size');

    $tab->call('useTwoLayers', true)
        ->assertSee('Layer 1')
        ->assertSee('Layer 2')
        ->set('newDesign', 'Chocolate')->call('addDesign')
        ->call('useTwoLayers', false)
        ->assertDispatched('error')
        ->assertSet('twoLayers', true);
});

it('treats a flavours-only family as one layer of flavours', function (): void {
    $this->shoe->update(['name' => 'Power Drink', 'size' => null, 'color' => null, 'variant_value' => 'Mango', 'variant_option' => 'Flavour']);

    $tab = Livewire::test(Variants::class, ['product_id' => $this->shoe->id])
        ->assertSet('twoLayers', false)
        ->assertSee('Flavours')
        ->assertDontSee('Sizes');
    expect($tab->instance()->singleLayer())->toBe('design');
});

it('swaps which layer the till asks for first across the whole family', function (): void {
    $this->shoe->update(['color' => null, 'variant_value' => 'Red Bull', 'size' => 'Mango', 'variant_option' => 'Base', 'variant_size_option' => 'Flavour']);
    $soda = Product::factory()->variantOf($this->shoe, 'Lemon')->create(['variant_value' => 'Soda', 'color' => null]);

    Livewire::test(Variants::class, ['product_id' => $this->shoe->id])
        ->call('goToStep', 1)
        ->assertSee('Flavour first')
        ->call('swapLayers')
        ->assertDispatched('success')
        ->assertSet('option', 'Flavour')
        ->assertSet('sizeOption', 'Base')
        ->assertSet('designs', ['Mango', 'Lemon'])
        ->assertSet('sizes', ['Red Bull', 'Soda']);

    expect($soda->fresh()->only(['variant_value', 'size', 'variant_option', 'variant_size_option']))
        ->toBe(['variant_value' => 'Lemon', 'size' => 'Soda', 'variant_option' => 'Flavour', 'variant_size_option' => 'Base']);
});

it('will not swap the layers while new variants are still unsaved', function (): void {
    $this->shoe->update(['color' => null, 'variant_value' => 'Red Bull', 'size' => 'Mango']);

    $tab = Livewire::test(Variants::class, ['product_id' => $this->shoe->id])
        ->set('newSize', 'Lemon')->call('addSize');
    $tab->call('toggle', $tab->instance()->cellKey('Lemon', '', 'Red Bull'))
        ->call('swapLayers')
        ->assertDispatched('error');

    expect($this->shoe->fresh()->variant_value)->toBe('Red Bull');
});

it('renames the second layer back to Size', function (): void {
    $variant = Product::factory()->variantOf($this->shoe, '42', 'Black')->create();

    $tab = Livewire::test(Variants::class, ['product_id' => $this->shoe->id])
        ->set('customSizeOption', 'weight')
        ->call('setSizeOption')
        ->assertSet('sizeOption', 'Weight');
    expect($variant->fresh()->variant_size_option)->toBe('Weight');

    $tab->call('setSizeOption', 'size')->assertSet('sizeOption', 'Size');
    expect($variant->fresh()->variant_size_option)->toBeNull();
});

it('keeps the design layer when the product form is saved', function (): void {
    $this->shoe->update(['variant_option' => 'Flavour', 'variant_value' => 'Mango']);

    Livewire::test(Page::class, ['type' => 'product', 'table_id' => $this->shoe->id])
        ->set('products.variant_option', null)
        ->set('products.variant_value', null)
        ->call('save');

    expect($this->shoe->fresh()->only(['variant_option', 'variant_value']))->toBe(['variant_option' => 'Flavour', 'variant_value' => 'Mango']);
});

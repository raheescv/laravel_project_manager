<?php

use App\Http\Controllers\BarcodeController;
use App\Livewire\Inventory\Barcode\CartPage;
use App\Models\Configuration;
use App\Support\BarcodeLabel;
use App\Support\BarcodeTemplateConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Support\PosWorld;

/**
 * Print cart rows carry their own MRP, and their weight when the sale setting
 * "Quantity Label In Print" is Weight; the template decides row-per-scan.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create(price: 50);
    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);

    $this->inventoryId = DB::table('inventories')->where('product_id', $this->world->product->id)->value('id');
    $this->barcode = DB::table('inventories')->where('id', $this->inventoryId)->value('barcode');

    $this->printWeights = fn () => Configuration::updateOrCreate(['key' => 'print_quantity_label'], ['value' => 'weight']);

    $this->useTemplate = function (string $type): void {
        BarcodeTemplateConfiguration::saveConfiguration([
            'default_template' => 'shop',
            'templates' => [
                'shop' => ['name' => 'Shop', 'type' => $type, 'settings' => ['type' => $type]],
            ],
        ]);
    };
});

it('drops the print cart keys an earlier build saved on templates', function (): void {
    $jewellery = BarcodeTemplateConfiguration::normalizeSettings(['fields' => ['weight' => ['visible' => true]], 'cart' => ['separate_rows' => true]], 'jewellery_tag');
    $standard = BarcodeTemplateConfiguration::normalizeSettings(['weight' => ['visible' => true], 'elements' => ['weight' => ['top' => 1]]], 'standard');

    expect($jewellery['fields'])->not->toHaveKey('weight')
        ->and($jewellery)->not->toHaveKey('cart')
        ->and($standard)->not->toHaveKey('weight')
        ->and($standard['elements'])->not->toHaveKey('weight');
});

it('adds to the existing row when separate rows is off', function (): void {
    ($this->useTemplate)('standard');

    $component = Livewire::test(CartPage::class)
        ->set('cartItems', [])
        ->set('barcodeInput', $this->barcode)->call('handleBarcodeScan')
        ->set('barcodeInput', $this->barcode)->call('handleBarcodeScan');

    expect($component->get('cartItems'))->toHaveCount(1)
        ->and($component->get("cartItems.inventory_{$this->inventoryId}.quantity"))->toBe(2);
});

it('adds a new row per scan when the console switch is on, and remembers it', function (): void {
    ($this->useTemplate)('standard');

    $component = Livewire::test(CartPage::class)
        ->set('cartItems', [])
        ->set('separateRows', true)
        ->set('barcodeInput', $this->barcode)->call('handleBarcodeScan')
        ->set('barcodeInput', $this->barcode)->call('handleBarcodeScan');

    $rows = $component->get('cartItems');
    expect($rows)->toHaveCount(2)
        ->and(collect($rows)->pluck('inventory_id')->unique()->all())->toBe([$this->inventoryId])
        ->and(collect($rows)->pluck('quantity')->all())->toBe([1, 1]);

    Livewire::test(CartPage::class)->assertSet('separateRows', true);
});

it('asks for a weight per row when quantities print as weight and rows are custom filled', function (): void {
    ($this->printWeights)();
    ($this->useTemplate)('jewellery_tag');
    $key = "inventory_{$this->inventoryId}";

    Livewire::test(CartPage::class)
        ->set('autoFill', false)
        ->set('cartItems', [])
        ->call('addToCart', $this->inventoryId)
        ->call('printBarcodes')
        ->assertDispatched('error')
        ->assertNotDispatched('label-print')
        ->set("cartItems.{$key}.weight", '4.2567')
        ->assertSet("cartItems.{$key}.weight", 4.257)
        ->set("cartItems.{$key}.price", '99')
        ->call('printBarcodes')
        ->assertDispatched('label-print', url: route('inventory::barcode::cart::print', ['template' => 'shop']));

    expect(session('print_cart_items')[$key])->toMatchArray(['weight' => 4.257]);
});

it('prints without a weight when quantities print as qty', function (): void {
    ($this->useTemplate)('standard');

    Livewire::test(CartPage::class)
        ->set('cartItems', [])
        ->call('addToCart', $this->inventoryId)
        ->call('printBarcodes')
        ->assertDispatched('label-print');
});

it('lets every row edit its MRP and falls back to the product MRP when cleared', function (): void {
    ($this->useTemplate)('standard');
    $key = "inventory_{$this->inventoryId}";
    $mrp = round((float) $this->world->product->mrp, 2);

    Livewire::test(CartPage::class)
        ->set('cartItems', [])
        ->call('addToCart', $this->inventoryId)
        ->assertSet("cartItems.{$key}.price", $mrp)
        ->set("cartItems.{$key}.price", '123.456')
        ->assertSet("cartItems.{$key}.price", 123.46)
        ->set("cartItems.{$key}.price", '')
        ->assertSet("cartItems.{$key}.price", $mrp);
});

it('duplicates a row as a new piece without its weight', function (): void {
    ($this->printWeights)();
    ($this->useTemplate)('jewellery_tag');
    $key = "inventory_{$this->inventoryId}";

    $component = Livewire::test(CartPage::class)
        ->set('cartItems', [])
        ->set('autoFill', false)
        ->call('addToCart', $this->inventoryId)
        ->set("cartItems.{$key}.weight", 3)
        ->call('duplicateRow', $key);

    expect(collect($component->get('cartItems'))->pluck('weight')->all())->toEqual([3, null]);
});

it('prints the row MRP and its weight on the jewellery qty line', function (): void {
    ($this->printWeights)();
    $settings = BarcodeTemplateConfiguration::normalizeSettings([], 'jewellery_tag');

    $html = view('inventory.barcode-cart-print', [
        'settings' => $settings,
        'company_name' => 'Shop',
        'company_logo' => '',
        'cartItems' => [
            'a' => ['item_type' => 'inventory', 'inventory_id' => $this->inventoryId, 'quantity' => 1, 'price' => 99, 'weight' => 4.25],
            'b' => ['item_type' => 'inventory', 'inventory_id' => $this->inventoryId, 'quantity' => 1, 'price' => 120.5, 'weight' => 5.1],
        ],
    ])->render();

    expect($html)->toContain('QR 99.00')->toContain('QR 120.50')
        ->toContain('Wt 4.250 g')->toContain('Wt 5.100 g')
        ->not->toContain('Qty 1');
});

it('prints the quantity, not a stale weight, when quantities print as qty', function (): void {
    $settings = BarcodeTemplateConfiguration::normalizeSettings([], 'jewellery_tag');

    $html = view('inventory.barcode-cart-print', [
        'settings' => $settings,
        'company_name' => 'Shop',
        'company_logo' => '',
        'cartItems' => [
            'a' => ['item_type' => 'inventory', 'inventory_id' => $this->inventoryId, 'quantity' => 1, 'price' => 99, 'weight' => 4.25],
        ],
    ])->render();

    expect($html)->toContain('QR 99.00')->toContain('Qty 1')->not->toContain('4.250 g');
});

it('prints the weight on a standard sticker qty element', function (): void {
    ($this->printWeights)();
    $settings = BarcodeTemplateConfiguration::normalizeSettings(['qty' => ['visible' => true]], 'standard');

    $html = view('inventory.barcode-cart-print', [
        'settings' => $settings,
        'company_name' => 'Shop',
        'company_logo' => '',
        'cartItems' => [
            'a' => ['item_type' => 'inventory', 'inventory_id' => $this->inventoryId, 'quantity' => 1, 'price' => 77, 'weight' => 2.5],
        ],
    ])->render();

    expect($html)->toContain('77.00')->toContain('Wt 2.500 g');
});

it('follows Quantity Label In Print for the qty caption', function (): void {
    expect(BarcodeLabel::quantityCaption())->toBe('Qty');

    ($this->printWeights)();

    expect(BarcodeLabel::quantityCaption())->toBe('Weight')
        ->and(BarcodeLabel::rowValues([], ['price' => '10', 'weight' => '1.5']))->toBe(['price' => 10.0, 'weight' => 1.5]);
});

it('shows each cart row with its product category', function (): void {
    ($this->useTemplate)('standard');

    $component = Livewire::test(CartPage::class)
        ->set('cartItems', [])
        ->call('addToCart', $this->inventoryId);

    expect($component->get("cartItems.inventory_{$this->inventoryId}.category_name"))->toBe('General');
    $component->assertSeeHtml('<span class="bcx-cart__category">General</span>');
});

it('titles cart rows with the category when Item Label In Print is Category', function (): void {
    ($this->useTemplate)('standard');
    Configuration::updateOrCreate(['key' => 'print_item_label'], ['value' => 'category']);

    Livewire::test(CartPage::class)
        ->set('cartItems', [])
        ->call('addToCart', $this->inventoryId)
        ->assertSeeHtml('title="General">General</div>')
        ->assertSeeHtml('<span class="bcx-cart__category">'.$this->world->product->name.'</span>');
});

it('fills in the category for cart rows saved before it was carried', function (): void {
    session(['cart_items' => [
        "inventory_{$this->inventoryId}" => ['item_type' => 'inventory', 'inventory_id' => $this->inventoryId, 'product_id' => $this->world->product->id, 'name' => 'Old row', 'barcode' => $this->barcode, 'mrp' => 50, 'quantity' => 1],
    ]]);

    expect(Livewire::test(CartPage::class)->get("cartItems.inventory_{$this->inventoryId}.category_name"))->toBe('General');
});

it('follows Item Label In Print for the name line on the label', function (): void {
    $product = $this->world->product;
    $render = fn (): string => view('inventory.barcode-cart-print', [
        'settings' => BarcodeTemplateConfiguration::normalizeSettings([], 'jewellery_tag'),
        'company_name' => 'Shop',
        'company_logo' => '',
        'cartItems' => ['a' => ['item_type' => 'inventory', 'inventory_id' => $this->inventoryId, 'quantity' => 1, 'price' => 50]],
    ])->render();

    expect(BarcodeLabel::itemName($product))->toBe($product->name)
        ->and($render())->toContain($product->name);

    Configuration::updateOrCreate(['key' => 'print_item_label'], ['value' => 'category']);

    expect(BarcodeLabel::itemName($product))->toBe('General')
        ->and(BarcodeLabel::itemNameArabic($product))->toBe('')
        ->and($render())->toContain('>General</span>')->toContain('jt-line--wrap')->not->toContain($product->name);
});

it('previews the category name in the template designer when Item Label In Print is Category', function (): void {
    ($this->useTemplate)('jewellery_tag');
    Configuration::updateOrCreate(['key' => 'print_item_label'], ['value' => 'category']);

    $html = app(BarcodeController::class)
        ->preview(Request::create('/', 'GET', ['template' => 'shop', 'product_id' => $this->world->product->id]))
        ->render();

    expect($html)->toContain('>General</span>')->not->toContain($this->world->product->name);
});

it('auto fills new rows with the product MRP and 1 g, and remembers the switch', function (): void {
    ($this->printWeights)();
    ($this->useTemplate)('jewellery_tag');
    $key = "inventory_{$this->inventoryId}";

    Livewire::test(CartPage::class)
        ->set('cartItems', [])
        ->call('addToCart', $this->inventoryId)
        ->assertSet("cartItems.{$key}.price", round((float) $this->world->product->mrp, 2))
        ->assertSet("cartItems.{$key}.weight", 1.0)
        ->call('printBarcodes')
        ->assertDispatched('label-print');

    Livewire::test(CartPage::class)->set('autoFill', false);

    expect(Configuration::where('key', 'barcode_cart_auto_fill')->value('value'))->toBe('0')
        ->and(Livewire::test(CartPage::class)->get('autoFill'))->toBeFalse();
});

it('starts custom filled rows blank and asks for the MRP before printing', function (): void {
    ($this->useTemplate)('jewellery_tag');
    $key = "inventory_{$this->inventoryId}";

    Livewire::test(CartPage::class)
        ->set('autoFill', false)
        ->set('cartItems', [])
        ->call('addToCart', $this->inventoryId)
        ->assertSet("cartItems.{$key}.price", null)
        ->call('printBarcodes')
        ->assertDispatched('error')
        ->assertNotDispatched('label-print')
        ->set("cartItems.{$key}.price", '')
        ->assertSet("cartItems.{$key}.price", null)
        ->set("cartItems.{$key}.price", '80')
        ->call('printBarcodes')
        ->assertDispatched('label-print');
});

it('applies the fill switch to rows already in the cart', function (): void {
    ($this->printWeights)();
    ($this->useTemplate)('jewellery_tag');
    $mrp = round((float) $this->world->product->mrp, 2);

    $component = Livewire::test(CartPage::class)
        ->set('separateRows', true)
        ->set('cartItems', [])
        ->call('addToCart', $this->inventoryId)
        ->call('addToCart', $this->inventoryId);
    [$first, $second] = array_keys($component->get('cartItems'));

    $component->set("cartItems.{$second}.weight", '2.5')->set("cartItems.{$second}.price", '75')
        ->set('autoFill', false)
        ->assertSet("cartItems.{$first}.price", null)
        ->assertSet("cartItems.{$first}.weight", null)
        ->assertSet("cartItems.{$second}.price", 75.0)
        ->assertSet("cartItems.{$second}.weight", 2.5)
        ->set('autoFill', true)
        ->assertSet("cartItems.{$first}.price", $mrp)
        ->assertSet("cartItems.{$first}.weight", 1.0)
        ->assertSet("cartItems.{$second}.price", 75.0);
});

it('fills the MRP and weight of every row at once', function (): void {
    ($this->printWeights)();
    ($this->useTemplate)('jewellery_tag');

    $component = Livewire::test(CartPage::class)
        ->set('autoFill', false)
        ->set('separateRows', true)
        ->set('cartItems', [])
        ->call('addToCart', $this->inventoryId)
        ->call('addToCart', $this->inventoryId)
        ->set('fillPrice', '250')
        ->set('fillWeight', '3.5')
        ->call('fillAllRows')
        ->assertDispatched('success');

    expect(collect($component->get('cartItems'))->map(fn (array $row): array => [$row['price'], $row['weight']])->values()->all())
        ->toEqual([[250.0, 3.5], [250.0, 3.5]]);
});

it('starts the unit price at the product MRP and adds the product tax to the MRP', function (): void {
    $this->world = PosWorld::create(price: 50, tax: 10);
    session(['branch_id' => $this->world->branch->id]);
    $inventoryId = DB::table('inventories')->where('product_id', $this->world->product->id)->value('id');
    ($this->printWeights)();
    ($this->useTemplate)('jewellery_tag');
    $key = "inventory_{$inventoryId}";
    $mrp = round((float) $this->world->product->mrp, 2);

    $component = Livewire::test(CartPage::class)
        ->set('cartItems', [])
        ->call('addToCart', $inventoryId)
        ->assertSet("cartItems.{$key}.unit_price", $mrp)
        ->assertSet("cartItems.{$key}.tax", 10.0)
        ->assertSet("cartItems.{$key}.price", round($mrp * 1.1, 2))
        ->set("cartItems.{$key}.weight", '2');

    expect($component->get("cartItems.{$key}.price"))->toBe(round($mrp * 2 * 1.1, 2));
});

it('works out the MRP from unit price, weight and tax', function (): void {
    ($this->printWeights)();
    ($this->useTemplate)('jewellery_tag');
    $key = "inventory_{$this->inventoryId}";

    $component = Livewire::test(CartPage::class)
        ->set('cartItems', [])
        ->call('addToCart', $this->inventoryId)
        ->assertSee('Unit Price')
        ->assertSee('Tax %')
        ->set("cartItems.{$key}.unit_price", '300')
        ->set("cartItems.{$key}.weight", '2.5')
        ->set("cartItems.{$key}.tax", '5');

    expect($component->get("cartItems.{$key}.price"))->toBe(787.5);

    $component->set("cartItems.{$key}.price", '800');
    expect($component->get("cartItems.{$key}.price"))->toBe(800.0);

    $component->set("cartItems.{$key}.weight", '1');
    expect($component->get("cartItems.{$key}.price"))->toBe(315.0);
});

it('works out the MRP from unit price and tax per piece when quantities print as qty', function (): void {
    ($this->useTemplate)('standard');
    $key = "inventory_{$this->inventoryId}";

    $component = Livewire::test(CartPage::class)
        ->set('cartItems', [])
        ->call('addToCart', $this->inventoryId)
        ->set("cartItems.{$key}.tax", '10')
        ->set("cartItems.{$key}.unit_price", '100');

    expect($component->get("cartItems.{$key}.price"))->toBe(110.0);
});

it('fills the unit price and tax of every row and works out each MRP', function (): void {
    ($this->printWeights)();
    ($this->useTemplate)('jewellery_tag');

    $component = Livewire::test(CartPage::class)
        ->set('separateRows', true)
        ->set('cartItems', [])
        ->call('addToCart', $this->inventoryId)
        ->call('addToCart', $this->inventoryId);

    $keys = array_keys($component->get('cartItems'));
    $component->set("cartItems.{$keys[1]}.weight", '2')
        ->set('fillUnitPrice', '200')
        ->set('fillTax', '5')
        ->call('fillAllRows')
        ->assertDispatched('success');

    expect(collect($component->get('cartItems'))->pluck('price')->sort()->values()->all())->toEqual([210.0, 420.0]);
});

it('hides the MRP and Weight columns when the template prints neither', function (): void {
    ($this->printWeights)();
    BarcodeTemplateConfiguration::saveConfiguration([
        'default_template' => 'plain',
        'templates' => [
            'plain' => ['name' => 'Plain', 'type' => 'jewellery_tag', 'settings' => [
                'type' => 'jewellery_tag',
                'fields' => ['price' => ['visible' => false], 'qty' => ['visible' => false]],
            ]],
        ],
    ]);

    Livewire::test(CartPage::class)
        ->set('autoFill', false)
        ->set('cartItems', [])
        ->call('addToCart', $this->inventoryId)
        ->assertDontSeeHtml('<th class="bcx-cart__num">MRP</th>')
        ->assertDontSeeHtml('<th class="bcx-cart__num">Weight</th>')
        ->assertDontSee('Fill all rows')
        ->call('printBarcodes')
        ->assertDispatched('label-print');
});

it('renders the console with weight and MRP columns in weight mode', function (): void {
    ($this->printWeights)();
    ($this->useTemplate)('jewellery_tag');

    Livewire::test(CartPage::class)
        ->set('cartItems', [])
        ->call('addToCart', $this->inventoryId)
        ->assertSee('MRP')
        ->assertSee('Weight')
        ->assertSee('Labels')
        ->assertSee('Label proof')
        ->assertSee('Separate row per scan');
});

it('opens the print cart full window with a way back to inventory', function (): void {
    $permission = config('permission.models.permission');
    $this->world->user->givePermissionTo($permission::firstOrCreate(['name' => 'inventory.barcode cart', 'guard_name' => 'web']));

    $this->get(route('inventory::barcode::cart::index'))
        ->assertOk()
        ->assertSee('standalone-body', false)
        ->assertSee('href="'.route('inventory::index').'"', false)
        ->assertDontSee('breadcrumb-item', false)
        ->assertSee('const messageOf = (e) => (Array.isArray(e) ? e[0] : e)?.message', false);
});

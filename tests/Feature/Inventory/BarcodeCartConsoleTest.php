<?php

use App\Livewire\Inventory\Barcode\CartPage;
use App\Models\Configuration;
use App\Support\BarcodeLabel;
use App\Support\BarcodeTemplateConfiguration;
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

it('asks for a weight per row when quantities print as weight', function (): void {
    ($this->printWeights)();
    ($this->useTemplate)('jewellery_tag');
    $key = "inventory_{$this->inventoryId}";

    Livewire::test(CartPage::class)
        ->set('cartItems', [])
        ->call('addToCart', $this->inventoryId)
        ->call('printBarcodes')
        ->assertDispatched('error')
        ->assertNotDispatched('label-print')
        ->set("cartItems.{$key}.weight", '4.2567')
        ->assertSet("cartItems.{$key}.weight", 4.257)
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

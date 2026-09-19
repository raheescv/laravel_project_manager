<?php

use App\Livewire\Purchase\Page;
use App\Models\AccountCategory;
use App\Models\Product;
use App\Models\PurchaseScanTemplate;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * Scanning a vendor's invoice into the /purchase/create cart.
 *
 * The reading of a PDF or a photo is pinned in InvoiceTableReaderTest; these
 * start from the grid that produces and cover what the purchase screen does
 * with it — mapping columns onto fields, matching the lines to the catalogue,
 * and what ends up in the cart. Nothing here saves a purchase: a scan fills the
 * same cart typing would have filled, and Save Draft / Submit still do the rest.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();

    foreach (['purchase.create', 'purchase.scan invoice'] as $name) {
        $this->world->user->givePermissionTo(Permission::firstOrCreate([
            'tenant_id' => $this->world->tenant->id,
            'name' => $name,
            'guard_name' => 'web',
        ]));
    }

    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);

    $category = AccountCategory::firstOrCreate([
        'tenant_id' => $this->world->tenant->id,
        'name' => 'Sundry Creditors',
    ]);

    $this->vendorId = DB::table('accounts')->insertGetId([
        'tenant_id' => $this->world->tenant->id,
        'account_category_id' => $category->id,
        'name' => 'Makeida Midas',
        'slug' => 'makeida_midas',
        'account_type' => 'liability',
        'model' => 'vendor',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->tray = Product::create([
        'tenant_id' => $this->world->tenant->id,
        'type' => 'product',
        'name' => 'HL MAKEUP TRAY',
        'code' => 'HL-001',
        'unit_id' => $this->world->product->unit_id,
        'cost' => 190.68,
        'created_by' => $this->world->user->id,
        'updated_by' => $this->world->user->id,
    ]);

    $this->comb = Product::create([
        'tenant_id' => $this->world->tenant->id,
        'type' => 'product',
        'name' => 'TONY&GUY COMB',
        'code' => 'TG-001',
        'unit_id' => $this->world->product->unit_id,
        'cost' => 28.57,
        'created_by' => $this->world->user->id,
        'updated_by' => $this->world->user->id,
    ]);
});

/** The grid a scan hands over: cells only, no headings the vendor did not print. */
function scannedInvoice(array $rows): array
{
    return [
        'columns' => ['Column 1', 'Column 2', 'Column 3', 'Column 4', 'Column 5', 'Column 6'],
        'bands' => [
            ['x0' => 7.0, 'x1' => 16.0, 'numeric' => true],
            ['x0' => 28.0, 'x1' => 138.0, 'numeric' => false],
            ['x0' => 201.0, 'x1' => 215.0, 'numeric' => true],
            ['x0' => 321.0, 'x1' => 349.0, 'numeric' => true],
            ['x0' => 463.0, 'x1' => 472.0, 'numeric' => true],
            ['x0' => 532.0, 'x1' => 560.0, 'numeric' => true],
        ],
        'rows' => $rows,
        'mapping' => [
            'product_code' => '',
            'barcode' => '',
            'product_name' => '1',
            'batch' => '',
            'quantity' => '2',
            'unit_price' => '3',
            'discount' => '',
            'tax' => '4',
        ],
    ];
}

/** Drive the page to the point a scan has been read and the columns are mapped. */
function scanned(array $rows, $vendorId, string $totalColumn = '5'): \Livewire\Features\SupportTesting\Testable
{
    $invoice = scannedInvoice($rows);

    return Livewire::test(Page::class)
        ->set('purchases.account_id', $vendorId)
        ->call('openInvoiceScan')
        ->set('scanColumns', $invoice['columns'])
        ->set('scanBands', $invoice['bands'])
        ->set('scanRows', $invoice['rows'])
        ->set('scanMapping', $invoice['mapping'])
        ->set('scanTotalColumn', $totalColumn)
        ->call('resolveScannedRows');
}

it('puts the invoice lines in the cart at the vendor\'s own figures', function (): void {
    $component = scanned([
        ['1', 'HL MAKEUP TRAY', '1.00', '190.68', '18', '225.00'],
        ['2', 'TONY&GUY COMB', '2.00', '28.57', '5', '60.00'],
    ], $this->vendorId)
        ->assertSet('scanStep', 3)
        ->call('addScannedToCart');

    $items = array_values($component->get('items'));

    expect($items)->toHaveCount(2);

    $tray = collect($items)->firstWhere('product_id', $this->tray->id);
    expect($tray['quantity'])->toEqual(1.0)
        ->and($tray['unit_price'])->toEqual(190.68)
        ->and($tray['tax'])->toEqual(18.0)
        ->and($tray['total'])->toEqual(225.0);

    $comb = collect($items)->firstWhere('product_id', $this->comb->id);
    expect($comb['quantity'])->toEqual(2.0)
        ->and($comb['unit_price'])->toEqual(28.57)
        ->and($comb['total'])->toEqual(60.0);

    // The cart's own totals have to follow, or the deck shows the old figure.
    expect($component->get('purchases.total'))->toEqual(285.0);
});

it('ticks the lines it matched and leaves the rest alone', function (): void {
    $component = scanned([
        ['1', 'HL MAKEUP TRAY', '1.00', '190.68', '18', '225.00'],
        ['2', 'SOMETHING WE DO NOT STOCK', '4.00', '12.00', '5', '50.40'],
    ], $this->vendorId);

    expect($component->get('scanSelected'))->toBe([0])
        ->and($component->get('scanItems')[1]['status'])->toBe('unmatched');

    $component->call('addScannedToCart');

    expect($component->get('items'))->toHaveCount(1);
});

it('adds a line the user matched by hand', function (): void {
    $component = scanned([
        ['1', 'A NAME THE VENDOR MADE UP', '3.00', '28.57', '5', '90.00'],
    ], $this->vendorId)
        ->call('bindScanProduct', 0, $this->comb->id);

    expect($component->get('scanItems')[0]['product_id'])->toBe($this->comb->id)
        ->and($component->get('scanSelected'))->toBe([0]);

    $component->call('addScannedToCart');

    $item = collect($component->get('items'))->first();
    expect($item['product_id'])->toBe($this->comb->id)
        ->and($item['quantity'])->toEqual(3.0);
});

it('folds a product the invoice lists twice into one cart line', function (): void {
    $component = scanned([
        ['1', 'HL MAKEUP TRAY', '1.00', '190.68', '18', '225.00'],
        ['2', 'HL MAKEUP TRAY', '2.00', '190.68', '18', '450.00'],
    ], $this->vendorId)->call('addScannedToCart');

    $items = array_values($component->get('items'));

    expect($items)->toHaveCount(1)
        ->and($items[0]['quantity'])->toEqual(3.0)
        ->and($items[0]['unit_price'])->toEqual(190.68);
});

it('reaches the catalogue name when the invoice clips its description', function (): void {
    $component = scanned([
        ['1', 'HL MAKEUP TRA', '1.00', '190.68', '18', '225.00'],
    ], $this->vendorId);

    expect($component->get('scanItems')[0]['product_id'])->toBe($this->tray->id)
        ->and($component->get('scanItems')[0]['name'])->toBe('HL MAKEUP TRAY');
});

/**
 * The check that earns its keep on a photographed invoice: OCR can be perfectly
 * confident and still read 3.00 as 1.00, and the only thing that knows better
 * is the line total printed next to it.
 */
it('flags a line whose figures do not add up to the printed total', function (): void {
    $component = scanned([
        ['1', 'HL MAKEUP TRAY', '1.00', '190.68', '18', '675.00'],
        ['2', 'TONY&GUY COMB', '2.00', '28.57', '5', '60.00'],
    ], $this->vendorId);

    $items = $component->get('scanItems');

    expect($items[0]['uncertain'])->toContain('675.00')
        ->and($items[1]['uncertain'])->toBeNull();
});

it('remembers the vendor\'s layout and uses it on the next invoice', function (): void {
    scanned([
        ['1', 'HL MAKEUP TRAY', '1.00', '190.68', '18', '225.00'],
    ], $this->vendorId)->call('addScannedToCart');

    $template = PurchaseScanTemplate::where('account_id', $this->vendorId)->first();

    expect($template)->not->toBeNull()
        ->and($template->mapping['product_name'])->toBe('1')
        ->and($template->mapping['unit_price'])->toBe('3')
        ->and($template->mapping['__total'])->toBe('5')
        ->and($template->used_count)->toBe(1)
        ->and($template->fits(scannedInvoice([])['bands']))->toBeTrue();
});

it('will not use a saved layout for an invoice with different columns', function (): void {
    scanned([
        ['1', 'HL MAKEUP TRAY', '1.00', '190.68', '18', '225.00'],
    ], $this->vendorId)->call('addScannedToCart');

    $moved = scannedInvoice([])['bands'];
    $moved[3]['x0'] = 280.0;

    expect(PurchaseScanTemplate::where('account_id', $this->vendorId)->first()->fits($moved))->toBeFalse();
});

it('keeps the scanner behind its own permission', function (): void {
    $this->world->user->revokePermissionTo('purchase.scan invoice');

    Livewire::test(Page::class)
        ->call('openInvoiceScan')
        ->assertStatus(403);
});

it('asks for a column that identifies the product before matching', function (): void {
    $invoice = scannedInvoice([['1', 'HL MAKEUP TRAY', '1.00', '190.68', '18', '225.00']]);
    $mapping = $invoice['mapping'];
    $mapping['product_name'] = '';

    Livewire::test(Page::class)
        ->call('openInvoiceScan')
        ->set('scanRows', $invoice['rows'])
        ->set('scanMapping', $mapping)
        ->call('resolveScannedRows')
        ->assertSet('scanStep', 1)
        ->assertSet('scanError', 'Point at least one column at the product — its name, its code or its barcode.');
});

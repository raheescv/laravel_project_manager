<?php

use App\Livewire\Purchase\Import;
use App\Models\AccountCategory;
use App\Models\Product;
use App\Models\Purchase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * /purchase/import — the three step invoice uploader.
 *
 * The invoice head is typed, the lines come from the vendor's own sheet, and
 * the result is a DRAFT purchase: no stock movement, no journal entry. These
 * pin column auto-detection, line matching, the manual fix for an unmatched
 * line, and what actually lands in the database.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();

    // `permissions` carries a tenant_id, so the row has to be built with one
    // rather than through Spatie's findOrCreate (see PermissionSeeder).
    foreach (['purchase.create', 'purchase.import'] as $name) {
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
        'name' => 'Acme Trading',
        'slug' => 'acme_trading',
        'account_type' => 'liability',
        'model' => 'vendor',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->widget = Product::create([
        'tenant_id' => $this->world->tenant->id,
        'type' => 'product',
        'name' => 'Blue Widget',
        'code' => 'WID-001',
        'unit_id' => $this->world->product->unit_id,
        'cost' => 20,
        'created_by' => $this->world->user->id,
        'updated_by' => $this->world->user->id,
    ]);

    $this->gadget = Product::create([
        'tenant_id' => $this->world->tenant->id,
        'type' => 'product',
        'name' => 'Red Gadget',
        'code' => 'GAD-001',
        'unit_id' => $this->world->product->unit_id,
        'cost' => 8,
        'created_by' => $this->world->user->id,
        'updated_by' => $this->world->user->id,
    ]);
});

function invoiceSheet(string $body): UploadedFile
{
    return UploadedFile::fake()->createWithContent('vendor-invoice.csv', $body);
}

it('will not leave the invoice step without a vendor, date and invoice no', function (): void {
    Livewire::test(Import::class)
        ->call('goToUpload')
        ->assertHasErrors(['account_id', 'invoice_no'])
        ->assertSet('step', 1);
});

it('detects the sheet columns by their own headings', function (): void {
    $sheet = invoiceSheet(<<<'CSV'
    Item Code,Description,Qty,Rate,Disc,VAT %
    WID-001,Blue Widget,10,25.5,5,5
    CSV);

    Livewire::test(Import::class)
        ->set('account_id', $this->vendorId)
        ->set('invoice_no', 'INV-9001')
        ->call('goToUpload')
        ->assertSet('step', 2)
        ->set('file', $sheet)
        ->assertSet('mapping.product_code', '0')
        ->assertSet('mapping.product_name', '1')
        ->assertSet('mapping.quantity', '2')
        ->assertSet('mapping.unit_price', '3')
        ->assertSet('mapping.discount', '4')
        ->assertSet('mapping.tax', '5')
        ->assertSet('mapping.barcode', '')
        ->assertSet('mapping.batch', '');
});

it('matches every line, flags the ones it cannot, and prices them like the purchase screen', function (): void {
    $sheet = invoiceSheet(<<<'CSV'
    Item Code,Description,Qty,Rate,Disc,VAT %
    WID-001,Blue Widget,10,25.5,5,5
    NOPE-999,Mystery Item,3,12,0,0
    CSV);

    $component = Livewire::test(Import::class)
        ->set('account_id', $this->vendorId)
        ->set('invoice_no', 'INV-9001')
        ->call('goToUpload')
        ->set('file', $sheet)
        ->call('buildRows')
        ->assertSet('step', 3);

    $items = $component->get('items');

    expect($items)->toHaveCount(2)
        ->and($items[0]['product_id'])->toBe($this->widget->id)
        ->and($items[0]['status'])->toBe('ok')
        ->and($items[0]['matched_on'])->toBe('code')
        // 10 x 25.50 = 255, less 5 discount = 250, +5% tax = 262.50
        ->and($items[0]['gross_amount'])->toBe(255.0)
        ->and($items[0]['tax_amount'])->toBe(12.5)
        ->and($items[0]['total'])->toBe(262.5)
        ->and($items[1]['product_id'])->toBeNull()
        ->and($items[1]['status'])->toBe('unmatched');

    expect($component->get('totals')['grand_total'])->toBe(262.5);
    expect($component->instance()->readyCount)->toBe(1);
    expect($component->instance()->issueCount)->toBe(1);
});

it('lets an unmatched line be pinned to a product by hand', function (): void {
    $sheet = invoiceSheet(<<<'CSV'
    Item Code,Description,Qty,Rate
    NOPE-999,Gadget in red,4,9
    CSV);

    $component = Livewire::test(Import::class)
        ->set('account_id', $this->vendorId)
        ->set('invoice_no', 'INV-9002')
        ->call('goToUpload')
        ->set('file', $sheet)
        ->call('buildRows')
        ->call('openResolve', 0)
        ->set('productSearch', 'Red Gadget');

    expect(collect($component->get('productResults'))->pluck('id'))->toContain($this->gadget->id);

    $component->call('assignProduct', $this->gadget->id)
        ->assertSet('resolvingIndex', null);

    $items = $component->get('items');

    expect($items[0]['product_id'])->toBe($this->gadget->id)
        ->and($items[0]['status'])->toBe('ok')
        ->and($items[0]['matched_on'])->toBe('manual')
        ->and($items[0]['total'])->toBe(36.0);
});

it('folds a repeated product into one line', function (): void {
    $sheet = invoiceSheet(<<<'CSV'
    Item Code,Description,Qty,Rate
    WID-001,Blue Widget,10,20
    WID-001,Blue Widget,5,20
    CSV);

    $component = Livewire::test(Import::class)
        ->set('account_id', $this->vendorId)
        ->set('invoice_no', 'INV-9003')
        ->call('goToUpload')
        ->set('file', $sheet)
        ->call('buildRows');

    expect($component->get('items'))->toHaveCount(1)
        ->and($component->get('items')[0]['quantity'])->toBe(15.0)
        ->and($component->get('mergedRows'))->toBe(1);
});

it('saves a draft purchase from the ready lines and skips the flagged one', function (): void {
    $sheet = invoiceSheet(<<<'CSV'
    Item Code,Description,Qty,Rate,Disc,VAT %
    WID-001,Blue Widget,10,25.5,5,5
    NOPE-999,Mystery Item,3,12,0,0
    CSV);

    Livewire::test(Import::class)
        ->set('account_id', $this->vendorId)
        ->set('invoice_no', 'INV-9004')
        ->set('date', '2026-09-01')
        ->set('freight', 10)
        ->call('goToUpload')
        ->set('file', $sheet)
        ->call('buildRows')
        ->call('save')
        ->assertRedirect();

    $purchase = Purchase::where('invoice_no', 'INV-9004')->first();

    expect($purchase)->not->toBeNull()
        ->and($purchase->status)->toBe('draft')
        ->and($purchase->account_id)->toBe($this->vendorId)
        ->and($purchase->date->toString ?? (string) $purchase->date)->toContain('2026-09-01')
        ->and($purchase->branch_id)->toBe($this->world->branch->id)
        ->and($purchase->freight)->toEqual(10)
        ->and($purchase->items)->toHaveCount(1);

    $item = $purchase->items->first();

    expect($item->product_id)->toBe($this->widget->id)
        ->and((float) $item->quantity)->toBe(10.0)
        ->and((float) $item->unit_price)->toBe(25.5)
        ->and((float) $item->total)->toBe(262.5)
        ->and((float) $purchase->grand_total)->toBe(272.5);

    // a draft must not touch stock or the ledger
    expect(DB::table('journal_entries')->where('model', 'Purchase')->where('model_id', $purchase->id)->count())->toBe(0);
});

it('refuses to save while flagged lines must be resolved', function (): void {
    $sheet = invoiceSheet(<<<'CSV'
    Item Code,Description,Qty,Rate
    NOPE-999,Mystery Item,3,12
    CSV);

    Livewire::test(Import::class)
        ->set('account_id', $this->vendorId)
        ->set('invoice_no', 'INV-9005')
        ->call('goToUpload')
        ->set('file', $sheet)
        ->call('buildRows')
        ->set('skipUnmatched', false)
        ->call('save')
        ->assertDispatched('error');

    expect(Purchase::where('invoice_no', 'INV-9005')->exists())->toBeFalse();
});

it('needs a unit price column before it will match anything', function (): void {
    $sheet = invoiceSheet(<<<'CSV'
    Item Code,Description,Qty
    WID-001,Blue Widget,10
    CSV);

    Livewire::test(Import::class)
        ->set('account_id', $this->vendorId)
        ->set('invoice_no', 'INV-9006')
        ->call('goToUpload')
        ->set('file', $sheet)
        ->call('buildRows')
        ->assertHasErrors('mapping')
        ->assertSet('step', 2);
});

it('falls back to a prefix match when the sheet truncates the product name', function (): void {
    // vendor invoices clip the description column: "…FACIAL SER" for "…FACIAL SERUM"
    $serum = Product::create([
        'tenant_id' => $this->world->tenant->id,
        'type' => 'product',
        'name' => 'LEEPOSH HYDRA FACIAL SERUM 50ML',
        'code' => 'LEE-001',
        'unit_id' => $this->world->product->unit_id,
        'cost' => 700,
        'created_by' => $this->world->user->id,
        'updated_by' => $this->world->user->id,
    ]);

    $sheet = invoiceSheet(<<<'CSV'
    Description,Qty,Rate
    LEEPOSH HYDRA FACIAL SER,3,889.83
    CSV);

    $component = Livewire::test(Import::class)
        ->set('account_id', $this->vendorId)
        ->set('invoice_no', 'INV-9007')
        ->call('goToUpload')
        ->set('file', $sheet)
        ->call('buildRows');

    $item = $component->get('items')[0];

    expect($item['product_id'])->toBe($serum->id)
        ->and($item['status'])->toBe('ok')
        ->and($item['matched_on'])->toBe('name~')
        // the catalogue name wins; the sheet's wording is kept only to show
        ->and($item['name'])->toBe('LEEPOSH HYDRA FACIAL SERUM 50ML')
        ->and($item['raw_name'])->toBe('LEEPOSH HYDRA FACIAL SER');

    $component->assertSee('LEEPOSH HYDRA FACIAL SERUM 50ML')
        ->assertSee('LEEPOSH HYDRA FACIAL SER');
});

it('flags a line rather than guessing when the truncated name fits several products', function (): void {
    // products.(tenant_id, name, type) is UNIQUE, so names never collide outright
    // — but a clipped vendor description is a prefix of several real products.
    foreach ([
        '26288-003' => 'LEEPOSH HYDRA FACIAL SERUM 30ML',
        '26288-004' => 'LEEPOSH HYDRA FACIAL SERUM 50ML',
        '26288-005' => 'LEEPOSH HYDRA FACIAL SERUM 100ML',
    ] as $code => $name) {
        Product::create([
            'tenant_id' => $this->world->tenant->id,
            'type' => 'product',
            'name' => $name,
            'code' => $code,
            'unit_id' => $this->world->product->unit_id,
            'cost' => 890,
            'created_by' => $this->world->user->id,
            'updated_by' => $this->world->user->id,
        ]);
    }

    $sheet = invoiceSheet(<<<'CSV'
    Description,Qty,Rate
    LEEPOSH HYDRA FACIAL SER,3,889.83
    CSV);

    $component = Livewire::test(Import::class)
        ->set('account_id', $this->vendorId)
        ->set('invoice_no', 'INV-9008')
        ->call('goToUpload')
        ->set('file', $sheet)
        ->call('buildRows');

    $item = $component->get('items')[0];

    expect($item['status'])->toBe('ambiguous')
        ->and($item['product_id'])->toBeNull()
        ->and($item['candidates'])->toHaveCount(3)
        ->and($item['message'])->toContain('3 products start with this name');

    // editing a value must not quietly downgrade it to a plain "no match"
    $component->set('items.0.quantity', 5)
        ->assertSet('items.0.status', 'ambiguous');

    // and picking one resolves it
    $chosen = Product::where('code', '26288-004')->first();
    $component->call('openResolve', 0)
        ->call('assignProduct', $chosen->id)
        ->assertSet('items.0.product_id', $chosen->id)
        ->assertSet('items.0.status', 'ok')
        ->assertSet('items.0.matched_on', 'manual');
});

it('prefers an exact name over a longer product that merely starts with it', function (): void {
    $exact = Product::create([
        'tenant_id' => $this->world->tenant->id,
        'type' => 'product',
        'name' => 'MAKEIDA TISSUE',
        'code' => 'TIS-001',
        'unit_id' => $this->world->product->unit_id,
        'cost' => 22,
        'created_by' => $this->world->user->id,
        'updated_by' => $this->world->user->id,
    ]);
    Product::create([
        'tenant_id' => $this->world->tenant->id,
        'type' => 'product',
        'name' => 'MAKEIDA TISSUE BOX LARGE',
        'code' => 'TIS-002',
        'unit_id' => $this->world->product->unit_id,
        'cost' => 40,
        'created_by' => $this->world->user->id,
        'updated_by' => $this->world->user->id,
    ]);

    $sheet = invoiceSheet(<<<'CSV'
    Description,Qty,Rate
    MAKEIDA TISSUE,60,22.03
    CSV);

    $item = Livewire::test(Import::class)
        ->set('account_id', $this->vendorId)
        ->set('invoice_no', 'INV-9009')
        ->call('goToUpload')
        ->set('file', $sheet)
        ->call('buildRows')
        ->get('items')[0];

    expect($item['product_id'])->toBe($exact->id)
        ->and($item['matched_on'])->toBe('name');
});

/** Three variants behind one clipped description, told apart only by price. */
function leeposhVariants(array $costs, $world): void
{
    foreach ($costs as $code => [$name, $cost]) {
        Product::create([
            'tenant_id' => $world->tenant->id,
            'type' => 'product',
            'name' => $name,
            'code' => $code,
            'unit_id' => $world->product->unit_id,
            'cost' => $cost,
            'created_by' => $world->user->id,
            'updated_by' => $world->user->id,
        ]);
    }
}

it('uses the sheet rate to settle which variant a clipped name means', function (): void {
    leeposhVariants([
        '26288-003' => ['LEEPOSH HYDRA FACIAL SERUM 30ML', 700],
        '26288-004' => ['LEEPOSH HYDRA FACIAL SERUM 50ML', 889.83],
        '26288-005' => ['LEEPOSH HYDRA FACIAL SERUM 100ML', 1200],
    ], $this->world);

    $sheet = invoiceSheet(<<<'CSV'
    Description,Qty,Rate
    LEEPOSH HYDRA FACIAL SER,3,889.83
    CSV);

    $item = Livewire::test(Import::class)
        ->set('account_id', $this->vendorId)
        ->set('invoice_no', 'INV-9010')
        ->call('goToUpload')
        ->set('file', $sheet)
        ->call('buildRows')
        ->get('items')[0];

    expect($item['status'])->toBe('ok')
        ->and($item['name'])->toBe('LEEPOSH HYDRA FACIAL SERUM 50ML')
        ->and($item['matched_on'])->toBe('name~')
        ->and($item['by_cost'])->toBeTrue()
        ->and($item['product_cost'])->toBe(889.83);
});

it('stays ambiguous when the rate fits more than one candidate', function (): void {
    leeposhVariants([
        '26288-003' => ['LEEPOSH HYDRA FACIAL SERUM 30ML', 889.83],
        '26288-004' => ['LEEPOSH HYDRA FACIAL SERUM 50ML', 889.83],
        '26288-005' => ['LEEPOSH HYDRA FACIAL SERUM 100ML', 1200],
    ], $this->world);

    $sheet = invoiceSheet(<<<'CSV'
    Description,Qty,Rate
    LEEPOSH HYDRA FACIAL SER,3,889.83
    CSV);

    $component = Livewire::test(Import::class)
        ->set('account_id', $this->vendorId)
        ->set('invoice_no', 'INV-9011')
        ->call('goToUpload')
        ->set('file', $sheet)
        ->call('buildRows');

    expect($component->get('items')[0]['status'])->toBe('ambiguous')
        ->and($component->get('items')[0]['by_cost'])->toBeFalse();

    // the candidates are listed with their cost, and one click picks one
    $chosen = Product::where('code', '26288-004')->first();
    $component->assertSee('LEEPOSH HYDRA FACIAL SERUM 30ML')
        ->call('chooseCandidate', 0, $chosen->id)
        ->assertSet('items.0.product_id', $chosen->id)
        ->assertSet('items.0.status', 'ok')
        ->assertSet('items.0.product_cost', 889.83);
});

it('shows the catalogue cost against the invoice rate when they disagree', function (): void {
    $sheet = invoiceSheet(<<<'CSV'
    Item Code,Description,Qty,Rate
    WID-001,Blue Widget,10,25.5
    CSV);

    // the widget is costed at 20 in the catalogue; the vendor billed 25.50
    $component = Livewire::test(Import::class)
        ->set('account_id', $this->vendorId)
        ->set('invoice_no', 'INV-9012')
        ->call('goToUpload')
        ->set('file', $sheet)
        ->call('buildRows');

    expect($component->get('items')[0]['product_cost'])->toBe(20.0);

    $component->assertSee('catalogue cost')->assertSee('+27.5%');
});

it('does not flag a cost variance when the rate matches the catalogue', function (): void {
    $sheet = invoiceSheet(<<<'CSV'
    Item Code,Description,Qty,Rate
    WID-001,Blue Widget,10,20
    CSV);

    Livewire::test(Import::class)
        ->set('account_id', $this->vendorId)
        ->set('invoice_no', 'INV-9013')
        ->call('goToUpload')
        ->set('file', $sheet)
        ->call('buildRows')
        ->assertDontSee('catalogue cost');
});

it('ignores a rounding-sized cost gap but reports a real one', function (): void {
    // 0.04 on a 21,186 line is the vendor rounding its rate, not a price change
    $big = Product::create([
        'tenant_id' => $this->world->tenant->id,
        'type' => 'product',
        'name' => 'HYDRA FACIAL 10 IN 1 WELLNESS MACHINE',
        'code' => 'HYD-001',
        'unit_id' => $this->world->product->unit_id,
        'cost' => 21186.40,
        'created_by' => $this->world->user->id,
        'updated_by' => $this->world->user->id,
    ]);

    $sheet = invoiceSheet(<<<'CSV'
    Item Code,Description,Qty,Rate
    HYD-001,HYDRA FACIAL,1,21186.44
    WID-001,Blue Widget,10,25.5
    CSV);

    $component = Livewire::test(Import::class)
        ->set('account_id', $this->vendorId)
        ->set('invoice_no', 'INV-9014')
        ->call('goToUpload')
        ->set('file', $sheet)
        ->call('buildRows');

    $items = $component->get('items');
    $instance = $component->instance();

    expect($items[0]['product_id'])->toBe($big->id)
        ->and($instance->hasCostVariance($items[0]))->toBeFalse()
        // the widget really is 27.5% over its catalogue cost
        ->and($instance->hasCostVariance($items[1]))->toBeTrue();

    $component->assertDontSee('+0.0%');
});

/** Seven variants behind one clipped name, as a real catalogue has. */
function pearlVariants($world): void
{
    $costs = [
        '24877-028' => ['NATURES WHITENING PEARL 800', 813.56],
        '24877-029' => ['NATURES WHITENING PEARL 900', 338.98],
        '24877-031' => ['NATURES WHITENING PEARL 950', 474.58],
        '24877-032' => ['NATURES WHITENING PEARL 1100', 745.76],
        '24877-033' => ['NATURES WHITENING PEARL 1200', 813.56],
        '24877-034' => ['NATURES WHITENING PEARL 1300', 900.00],
        '24877-035' => ['NATURES WHITENING PEARL 1400', 1000.00],
    ];

    foreach ($costs as $code => [$name, $cost]) {
        Product::create([
            'tenant_id' => $world->tenant->id,
            'type' => 'product',
            'name' => $name,
            'code' => $code,
            'unit_id' => $world->product->unit_id,
            'cost' => $cost,
            'created_by' => $world->user->id,
            'updated_by' => $world->user->id,
        ]);
    }
}

it('counts every match in the chip, not just the ones it lists', function (): void {
    pearlVariants($this->world);

    $sheet = invoiceSheet(<<<'CSV'
    Description,Qty,Rate
    NATURES WHITENING PEARL,1,400
    CSV);

    $component = Livewire::test(Import::class)
        ->set('account_id', $this->vendorId)
        ->set('invoice_no', 'INV-9015')
        ->call('goToUpload')
        ->set('file', $sheet)
        ->call('buildRows');

    $item = $component->get('items')[0];

    expect($item['status'])->toBe('ambiguous')
        ->and($item['candidate_count'])->toBe(7)
        // the list is capped, the count is not
        ->and($item['candidates'])->toHaveCount(4)
        // nearest cost to the line's 400 leads: 338.98, 474.58, 745.76, then 813.56
        ->and(collect($item['candidates'])->pluck('code')->all())
        ->toBe(['24877-029', '24877-031', '24877-032', '24877-033']);

    // the chip, the sentence and the overflow hint must all agree
    $component->assertSee('7 matches')
        ->assertSee('7 products start with this name')
        ->assertSee('+3 more');
});

it('resolves an ambiguous line when the rate is corrected by hand', function (): void {
    pearlVariants($this->world);

    // the sheet carries the tax-inclusive 400.00; the real rate is 338.98
    $sheet = invoiceSheet(<<<'CSV'
    Description,Qty,Rate,VAT %
    NATURES WHITENING PEARL,1,400,18
    CSV);

    $component = Livewire::test(Import::class)
        ->set('account_id', $this->vendorId)
        ->set('invoice_no', 'INV-9016')
        ->call('goToUpload')
        ->set('file', $sheet)
        ->call('buildRows')
        ->assertSet('items.0.status', 'ambiguous');

    $component->set('items.0.unit_price', 338.98);

    $item = $component->get('items')[0];
    $expected = Product::where('code', '24877-029')->first();

    expect($item['status'])->toBe('ok')
        ->and($item['product_id'])->toBe($expected->id)
        ->and($item['by_cost'])->toBeTrue()
        ->and($item['candidate_count'])->toBe(0)
        ->and($item['total'])->toBe(400.0);
});

it('leaves the line ambiguous when a corrected rate still fits two products', function (): void {
    pearlVariants($this->world);

    $sheet = invoiceSheet(<<<'CSV'
    Description,Qty,Rate
    NATURES WHITENING PEARL,1,400
    CSV);

    // 813.56 is the cost of both the 800 and the 1200
    Livewire::test(Import::class)
        ->set('account_id', $this->vendorId)
        ->set('invoice_no', 'INV-9017')
        ->call('goToUpload')
        ->set('file', $sheet)
        ->call('buildRows')
        ->set('items.0.unit_price', 813.56)
        ->assertSet('items.0.status', 'ambiguous')
        ->assertSet('items.0.product_id', null);
});

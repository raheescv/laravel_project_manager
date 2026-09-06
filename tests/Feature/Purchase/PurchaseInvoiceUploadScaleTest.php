<?php

use App\Livewire\Purchase\Import;
use App\Models\AccountCategory;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * A long invoice must stay as responsive as a short one.
 *
 * Livewire ships every public property on every round-trip, so holding all the
 * resolved lines in component state made a 400 line sheet cost ~180KB and the
 * better part of a second per keystroke. The lines live in the cache now and
 * only the visible page goes over the wire — these pin that arrangement.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();

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
        'name' => 'Creditors',
    ]);
    $this->vendorId = DB::table('accounts')->insertGetId([
        'tenant_id' => $this->world->tenant->id,
        'account_category_id' => $category->id,
        'name' => 'Bulk Vendor',
        'slug' => 'bulk-vendor',
        'account_type' => 'liability',
        'model' => 'vendor',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
});

/** @return string CSV body for $n matching lines */
function bulkSheet(int $n, $world): string
{
    $rows = ['Item Code,Description,Qty,Rate,Disc,VAT %'];
    for ($i = 1; $i <= $n; $i++) {
        $code = 'BULK-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT);
        Product::create([
            'tenant_id' => $world->tenant->id,
            'type' => 'product',
            'name' => 'Bulk Product '.$i,
            'code' => $code,
            'unit_id' => $world->product->unit_id,
            'cost' => 100 + $i,
            'created_by' => $world->user->id,
            'updated_by' => $world->user->id,
        ]);
        $rows[] = "{$code},Bulk Product {$i},2,".(100 + $i).',0,5';
    }

    return implode("\n", $rows);
}

function uploadBulk(int $n, $world, $vendorId)
{
    return Livewire::test(Import::class)
        ->set('account_id', $vendorId)
        ->set('invoice_no', 'BULK-'.$n)
        ->call('goToUpload')
        ->set('file', UploadedFile::fake()->createWithContent('bulk.csv', bulkSheet($n, $world)))
        ->call('buildRows');
}

it('sends only one page of lines over the wire, however long the invoice', function (): void {
    $component = uploadBulk(120, $this->world, $this->vendorId);

    expect($component->get('lineCount'))->toBe(120)
        ->and($component->get('readyCount'))->toBe(120)
        // the wire carries a page, not the invoice
        ->and($component->get('items'))->toHaveCount(50)
        ->and($component->instance()->pageCount)->toBe(3);

    // and the payload stays flat as the sheet grows
    $bytes = strlen(json_encode($component->get('items')));
    expect($bytes)->toBeLessThan(40 * 1024);
});

it('keeps matching and totals correct across the whole set, not just the page', function (): void {
    $component = uploadBulk(120, $this->world, $this->vendorId);

    // 2 x (101..220) summed, +5% tax
    $gross = 0;
    for ($i = 1; $i <= 120; $i++) {
        $gross += 2 * (100 + $i);
    }

    expect($component->get('totals')['lines'])->toBe(120)
        ->and($component->get('totals')['gross_amount'])->toBe(round($gross, 2))
        ->and($component->get('totals')['grand_total'])->toBe(round($gross * 1.05, 2));
});

it('walks pages without losing an edit made on an earlier one', function (): void {
    $component = uploadBulk(120, $this->world, $this->vendorId);

    $component->set('items.0.quantity', 9)
        ->call('setPage', 2)
        ->assertSet('page', 2);

    // page two holds lines 50..99 and none of page one
    expect(array_keys($component->get('items'))[0])->toBe(50)
        ->and($component->get('items'))->not->toHaveKey(0);

    $component->call('setPage', 1);

    expect((float) $component->get('items')[0]['quantity'])->toBe(9.0)
        // and the edit is in the totals for the whole set
        ->and((float) $component->get('totals')['quantity'])->toBe(247.0);
});

it('saves every line of the working set, not just the visible page', function (): void {
    uploadBulk(120, $this->world, $this->vendorId)
        ->call('save')
        ->assertRedirect();

    $purchase = App\Models\Purchase::where('invoice_no', 'BULK-120')->first();

    expect($purchase->items)->toHaveCount(120);
});

it('says so plainly when the cached working set has gone', function (): void {
    $component = uploadBulk(10, $this->world, $this->vendorId);

    Illuminate\Support\Facades\Cache::flush();

    $component->call('setPage', 1)
        ->assertSet('expired', true)
        ->assertSee('This upload has expired')
        ->call('save')
        ->assertDispatched('error');

    expect(App\Models\Purchase::where('invoice_no', 'BULK-10')->exists())->toBeFalse();
});

it('does not fall back to a full-table scan when a prefix search will do', function (): void {
    // 20 products all beginning "Bulk Product", so the indexed prefix pass fills
    // the 12 result slots on its own
    $component = uploadBulk(20, $this->world, $this->vendorId);
    $component->call('openResolve', 0);

    DB::flushQueryLog();
    DB::enableQueryLog();
    $component->set('productSearch', 'Bulk Product');
    $likes = collect(DB::getQueryLog())->pluck('query')->filter(fn ($q) => str_contains($q, 'like'));
    DB::disableQueryLog();

    expect($component->get('productResults'))->toHaveCount(12)
        ->and($likes)->toHaveCount(1);
});

it('only widens to a contains search when the prefix pass came up short', function (): void {
    $component = uploadBulk(20, $this->world, $this->vendorId);
    $component->call('openResolve', 0);

    DB::flushQueryLog();
    DB::enableQueryLog();
    // nothing starts with "roduct", so the prefix pass returns nothing
    $component->set('productSearch', 'roduct 7');
    $likes = collect(DB::getQueryLog())->pluck('query')->filter(fn ($q) => str_contains($q, 'like'));
    DB::disableQueryLog();

    expect($likes)->toHaveCount(2)
        ->and(collect($component->get('productResults'))->pluck('name'))
        ->toContain('Bulk Product 7');
});

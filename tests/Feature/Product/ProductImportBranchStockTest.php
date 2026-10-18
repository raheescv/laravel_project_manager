<?php

use App\Actions\Purchase\JournalEntryAction;
use App\Actions\Purchase\StockUpdateAction;
use App\Imports\ProductImport;
use App\Livewire\Product\Import;
use App\Models\Inventory;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\Purchase;
use App\Support\OpeningStock;
use App\Support\TenantCache;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Tests\Support\PosWorld;

beforeEach(function (): void {
    Queue::fake();
    $this->world = PosWorld::create(stock: 5);
    $this->secondBranch = $this->world->addBranch('Doha Branch', 'DB');
    TenantCache::forget('branches');
});

function writeBranchStockCsv(array $rows): string
{
    $path = tempnam(sys_get_temp_dir(), 'product-branch-stock-').'.csv';
    $handle = fopen($path, 'w');
    foreach ($rows as $row) {
        fputcsv($handle, $row);
    }
    fclose($handle);

    return $path;
}

/**
 * @return array<int, Purchase> branch id => opening-stock draft
 */
function importBranchStock(PosWorld $world, array $rows, array $mappings, string $strategy = 'skip'): array
{
    $file = writeBranchStockCsv($rows);

    $import = new ProductImport(
        $world->user->id,
        count($rows) - 1,
        $world->branch->id,
        $mappings,
        'product',
        'Product',
        $world->tenant->id,
        $strategy
    );
    Excel::import($import, $file);

    @unlink($file);

    return $import->createOpeningStockDrafts();
}

function branchQuantity(int $productId, int $branchId): ?float
{
    $quantity = Inventory::query()
        ->where('product_id', $productId)
        ->where('branch_id', $branchId)
        ->whereNull('employee_id')
        ->value('quantity');

    return $quantity === null ? null : (float) $quantity;
}

it('records new products\' opening stock as one draft purchase per branch against the Opening Stock account', function (): void {
    $main = $this->world->branch->id;
    $doha = $this->secondBranch->id;

    $drafts = importBranchStock($this->world, [
        ['name', 'cost', 'stock_main', 'stock_doha'],
        ['Branch Split Product', '4', '7', '12'],
        ['Main Only Product', '10', '3', ''],
    ], ['name' => 'name', 'cost' => 'cost', 'stock_branch_'.$main => 'stock_main', 'stock_branch_'.$doha => 'stock_doha']);

    $split = Product::query()->where('name', 'Branch Split Product')->firstOrFail();
    $mainOnly = Product::query()->where('name', 'Main Only Product')->firstOrFail();

    expect(branchQuantity($split->id, $main))->toBe(0.0)
        ->and(branchQuantity($split->id, $doha))->toBe(0.0)
        ->and(array_keys($drafts))->toEqualCanonicalizing([$main, $doha]);

    $mainDraft = Purchase::with('items')->findOrFail($drafts[$main]->id);
    expect($mainDraft->status)->toBe('draft')
        ->and($mainDraft->account_id)->toBe(OpeningStock::accountId())
        ->and($mainDraft->invoice_no)->toStartWith('OPENING-MB-')
        ->and((float) $mainDraft->grand_total)->toBe(58.0)
        ->and($mainDraft->items->mapWithKeys(fn ($item) => [$item->product_id => [(float) $item->quantity, (float) $item->unit_price]])->all())
        ->toEqual([$split->id => [7.0, 4.0], $mainOnly->id => [3.0, 10.0]]);

    $dohaDraft = Purchase::with('items')->findOrFail($drafts[$doha]->id);
    expect($dohaDraft->branch_id)->toBe($doha)
        ->and($dohaDraft->items->pluck('quantity', 'product_id')->map(fn ($quantity) => (float) $quantity)->all())
        ->toEqual([$split->id => 12.0]);
});

it('brings the stock in and posts Dr Inventory / Cr Opening Stock when the draft is completed', function (): void {
    $main = $this->world->branch->id;

    $drafts = importBranchStock($this->world, [
        ['name', 'cost', 'stock'],
        ['Completed Opening Product', '5', '6'],
    ], ['name' => 'name', 'cost' => 'cost', 'stock' => 'stock']);

    $purchase = $drafts[$main];
    $purchase->update(['status' => 'completed']);
    expect((new StockUpdateAction())->execute($purchase->refresh(), $this->world->user->id)['success'])->toBeTrue()
        ->and((new JournalEntryAction())->execute($purchase->refresh(), $this->world->user->id)['success'])->toBeTrue();

    $product = Product::query()->where('name', 'Completed Opening Product')->firstOrFail();
    expect(branchQuantity($product->id, $main))->toBe(6.0);

    $entries = JournalEntry::query()->where('model', 'Purchase')->where('model_id', $purchase->id)->get();
    expect((float) $entries->sum('debit'))->toBe((float) $entries->sum('credit'))
        ->and((float) $entries->where('account_id', $this->world->accounts['inventory'])->sum('debit'))->toBe(30.0)
        ->and((float) $entries->where('account_id', OpeningStock::accountId())->sum('credit'))->toBe(30.0);

    expect(Purchase::payable()->whereKey($purchase->id)->exists())->toBeFalse();
});

it('never changes existing products\' stock and queues it on the branch drafts instead', function (): void {
    $product = $this->world->product;
    $main = $this->world->branch->id;
    $doha = $this->secondBranch->id;
    $dohaBefore = branchQuantity($product->id, $doha);

    $drafts = importBranchStock($this->world, [
        ['id', 'name', 'barcode', 'stock_main', 'stock_doha'],
        [$product->id, $product->name, '55443322', '', '40'],
    ], ['id' => 'id', 'name' => 'name', 'barcode' => 'barcode', 'stock_branch_'.$main => 'stock_main', 'stock_branch_'.$doha => 'stock_doha'], 'update');

    expect(branchQuantity($product->id, $main))->toBe(5.0)
        ->and(branchQuantity($product->id, $doha))->toBe($dohaBefore)
        ->and(array_keys($drafts))->toBe([$doha])
        ->and($drafts[$doha]->items()->pluck('quantity', 'product_id')->map(fn ($quantity) => (float) $quantity)->all())
        ->toEqual([$product->id => 40.0]);
});

it('opens an empty inventory row for a branch the existing product was never stocked in', function (): void {
    $product = $this->world->product;
    $third = $this->world->addBranch('Wakra Branch', 'WB');
    Inventory::query()->where('product_id', $product->id)->where('branch_id', $third->id)->forceDelete();

    $drafts = importBranchStock($this->world, [
        ['id', 'name', 'barcode', 'stock_wakra'],
        [$product->id, $product->name, '55443322', '9'],
    ], ['id' => 'id', 'name' => 'name', 'barcode' => 'barcode', 'stock_branch_'.$third->id => 'stock_wakra'], 'update');

    expect(branchQuantity($product->id, $third->id))->toBe(0.0)
        ->and((float) $drafts[$third->id]->items()->value('quantity'))->toBe(9.0);

    $drafts[$third->id]->update(['status' => 'completed']);
    expect((new StockUpdateAction())->execute($drafts[$third->id]->refresh(), $this->world->user->id)['success'])->toBeTrue()
        ->and(branchQuantity($product->id, $third->id))->toBe(9.0);
});

it('still puts a plain stock column into the importing branch', function (): void {
    $drafts = importBranchStock($this->world, [
        ['name', 'stock'],
        ['Legacy Stock Product', '3'],
    ], ['name' => 'name', 'stock' => 'stock']);

    $product = Product::query()->where('name', 'Legacy Stock Product')->firstOrFail();

    expect(array_keys($drafts))->toBe([$this->world->branch->id])
        ->and($drafts[$this->world->branch->id]->items()->value('product_id'))->toBe($product->id);
});

it('prices a product with no buying price at half its selling price', function (): void {
    $drafts = importBranchStock($this->world, [
        ['name', 'cost', 'mrp', 'stock'],
        ['No Cost Product', '0', '75', '2'],
    ], ['name' => 'name', 'cost' => 'cost', 'mrp' => 'mrp', 'stock' => 'stock']);

    expect((float) $drafts[$this->world->branch->id]->items()->value('unit_price'))->toBe(37.5);
});

it('creates no draft when new products carry no opening stock', function (): void {
    $drafts = importBranchStock($this->world, [
        ['name', 'stock'],
        ['Zero Stock Product', '0'],
    ], ['name' => 'name', 'stock' => 'stock']);

    expect($drafts)->toBe([])
        ->and(Purchase::query()->count())->toBe(0);
});

it('maps a plain stock header to the current branch and branch-named headers to their branch', function (): void {
    $main = $this->world->branch->id;
    $doha = $this->secondBranch->id;

    $this->actingAs($this->world->user);
    session(['branch_id' => $main, 'tenant_id' => $this->world->tenant->id]);

    $component = Livewire::test(Import::class);
    $fields = $component->get('availableFields');

    expect($fields)->toHaveKeys(['stock_branch_'.$main, 'stock_branch_'.$doha])
        ->not->toHaveKey('stock')
        ->and($fields['stock_branch_'.$main])->toBe('Stock — Main Branch (current)');

    $aliases = (fn () => $this->getBranchStockAliases())->call($component->instance());
    $normalize = fn (string $value): string => strtolower(str_replace(['_', ' ', '-'], '', $value));

    expect(array_map($normalize, $aliases['stock_branch_'.$main]))->toContain('stock', 'stockmainbranch')
        ->and(array_map($normalize, $aliases['stock_branch_'.$doha]))->toContain('stockdohabranch', 'stockdb', 'dohabranchquantity')
        ->not->toContain('stock');
});

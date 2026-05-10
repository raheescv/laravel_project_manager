<?php

namespace App\Console\Commands\SingleUse;

use App\Models\Inventory;
use App\Models\InventoryLog;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturnItem;
use App\Models\SaleItem;
use App\Models\SaleReturnItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecalculateCostCommand extends Command
{
    protected $signature = 'inventory:recalculate-cost
        {--product= : Limit to a specific product ID}
        {--branch= : Limit to a specific branch ID}
        {--dry-run : Preview changes without writing}';

    protected $description = 'Replay purchase/sale/return items chronologically to rebuild inventory cost, inventory_logs, and Sale/SaleReturn COGS journal entries.';

    private const REPLAYABLE_MODELS = ['Purchase', 'PurchaseReturn', 'Sale', 'SaleReturn'];

    public function handle(): int
    {
        $productFilter = $this->option('product') ? (int) $this->option('product') : null;
        $branchFilter = $this->option('branch') ? (int) $this->option('branch') : null;
        $dryRun = (bool) $this->option('dry-run');

        $accounts = Cache::get('accounts_slug_id_map', []);
        $cogsId = $accounts['cost_of_goods_sold'] ?? null;
        $inventoryAccountId = $accounts['inventory'] ?? null;
        if (! $cogsId || ! $inventoryAccountId) {
            $this->error('Missing accounts cache (cost_of_goods_sold / inventory). Run app boot to warm Cache::get(accounts_slug_id_map).');

            return self::FAILURE;
        }

        $pairsQuery = Inventory::withoutGlobalScopes()
            ->select('product_id', 'branch_id', 'tenant_id')
            ->groupBy('product_id', 'branch_id', 'tenant_id');
        if ($productFilter) {
            $pairsQuery->where('product_id', $productFilter);
        }
        if ($branchFilter) {
            $pairsQuery->where('branch_id', $branchFilter);
        }
        $pairs = $pairsQuery->get();

        $this->info("Processing {$pairs->count()} (product, branch) inventory pairs.".($dryRun ? ' [DRY RUN]' : ''));

        $touchedSales = [];        // [sale_id => totalCogs]
        $touchedSaleReturns = [];  // [sale_return_id => totalCogs]
        $touchedProducts = [];

        DB::beginTransaction();
        try {
            foreach ($pairs as $pair) {
                $this->processPair(
                    $pair->product_id,
                    $pair->branch_id,
                    $touchedSales,
                    $touchedSaleReturns,
                );
                $touchedProducts[$pair->product_id] = true;
            }

            $this->info('Updating Sale COGS journal entries: '.count($touchedSales));
            foreach ($touchedSales as $saleId => $cogs) {
                $this->updateCogsJournal('Sale', $saleId, round($cogs, 2), $cogsId, $inventoryAccountId);
            }

            $this->info('Updating SaleReturn COGS journal entries: '.count($touchedSaleReturns));
            foreach ($touchedSaleReturns as $srId => $cogs) {
                $this->updateCogsJournal('SaleReturn', $srId, round($cogs, 2), $cogsId, $inventoryAccountId);
            }

            $this->info('Recomputing Product.cost (weighted avg across branches): '.count($touchedProducts));
            foreach (array_keys($touchedProducts) as $productId) {
                $this->updateProductCost($productId);
            }

            if ($dryRun) {
                DB::rollBack();
                $this->warn('Dry run — rolled back all changes.');
            } else {
                DB::commit();
                $this->info('Done.');
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Aborted: '.$e->getMessage());
            $this->error($e->getTraceAsString());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function processPair(int $productId, int $branchId, array &$touchedSales, array &$touchedSaleReturns): void
    {
        $events = $this->collectEvents($productId, $branchId);
        if ($events->isEmpty()) {
            return;
        }

        $qty = 0.0;
        $cost = 0.0;
        $logsToInsert = [];

        foreach ($events as $event) {
            [$type, $item, $parent] = [$event['type'], $event['item'], $event['parent']];
            $q = (float) $item->base_unit_quantity;
            $unitPrice = $item->conversion_factor ? round(((float) $item->unit_price) / (float) $item->conversion_factor, 2) : (float) $item->unit_price;

            $qIn = 0.0;
            $qOut = 0.0;

            switch ($type) {
                case 'Purchase':
                    $newQty = $qty + $q;
                    $cost = $newQty > 0 ? (($cost * $qty) + ($unitPrice * $q)) / $newQty : 0;
                    $qty = $newQty;
                    $qIn = $q;
                    break;
                case 'PurchaseReturn':
                    $newQty = $qty - $q;
                    $cost = $newQty > 0 ? (($cost * $qty) - ($unitPrice * $q)) / $newQty : 0;
                    $qty = $newQty;
                    $qOut = $q;
                    break;
                case 'Sale':
                    $qty -= $q;
                    $qOut = $q;
                    $touchedSales[$parent->id] = ($touchedSales[$parent->id] ?? 0) + ($cost * $q);
                    break;
                case 'SaleReturn':
                    $qty += $q;
                    $qIn = $q;
                    $touchedSaleReturns[$parent->id] = ($touchedSaleReturns[$parent->id] ?? 0) + ($cost * $q);
                    break;
            }

            if ($qIn == 0 && $qOut == 0) {
                continue;
            }

            $logsToInsert[] = [
                'tenant_id' => $parent->tenant_id ?? null,
                'branch_id' => $branchId,
                'product_id' => $productId,
                'quantity_in' => $qIn,
                'quantity_out' => $qOut,
                'balance' => round($qty, 4),
                'cost' => round($cost, 2),
                'remarks' => $type.':'.($parent->invoice_no ?? $parent->id),
                'model' => $type,
                'model_id' => $parent->id,
                'created_at' => $event['ts'],
                'updated_at' => $event['ts'],
            ];
        }

        // Replace inventory_logs for the replayable models for this (branch, product)
        InventoryLog::withoutGlobalScopes()
            ->where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->whereIn('model', self::REPLAYABLE_MODELS)
            ->delete();
        foreach (array_chunk($logsToInsert, 500) as $chunk) {
            InventoryLog::insert($chunk);
        }

        // Update Inventory.cost & quantity for this (branch, product)
        Inventory::withoutGlobalScopes()
            ->where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->update([
                'cost' => round($cost, 2),
                'quantity' => round($qty, 4),
            ]);
    }

    private function collectEvents(int $productId, int $branchId)
    {
        $purchases = PurchaseItem::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('product_id', $productId)
            ->whereHas('purchase', fn ($q) => $q->withoutGlobalScopes()->whereNull('deleted_at')->where('branch_id', $branchId))
            ->with(['purchase' => fn ($q) => $q->withoutGlobalScopes()->whereNull('deleted_at')])
            ->get()
            ->map(fn ($i) => [
                'type' => 'Purchase',
                'item' => $i,
                'parent' => $i->purchase,
                'ts' => $i->purchase->date.' '.($i->purchase->created_at ? $i->purchase->created_at->format('H:i:s') : '00:00:00'),
                'sort' => [$i->purchase->date, $i->purchase->id, $i->id],
            ]);

        $purchaseReturns = PurchaseReturnItem::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('product_id', $productId)
            ->whereHas('purchaseReturn', fn ($q) => $q->withoutGlobalScopes()->whereNull('deleted_at')->where('branch_id', $branchId))
            ->with(['purchaseReturn' => fn ($q) => $q->withoutGlobalScopes()->whereNull('deleted_at')])
            ->get()
            ->map(fn ($i) => [
                'type' => 'PurchaseReturn',
                'item' => $i,
                'parent' => $i->purchaseReturn,
                'ts' => $i->purchaseReturn->date.' '.($i->purchaseReturn->created_at ? $i->purchaseReturn->created_at->format('H:i:s') : '00:00:00'),
                'sort' => [$i->purchaseReturn->date, $i->purchaseReturn->id, $i->id],
            ]);

        $sales = SaleItem::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('product_id', $productId)
            ->whereHas('sale', fn ($q) => $q->withoutGlobalScopes()->whereNull('deleted_at')->where('branch_id', $branchId))
            ->with(['sale' => fn ($q) => $q->withoutGlobalScopes()->whereNull('deleted_at')])
            ->get()
            ->map(fn ($i) => [
                'type' => 'Sale',
                'item' => $i,
                'parent' => $i->sale,
                'ts' => $i->sale->date.' '.($i->sale->created_at ? $i->sale->created_at->format('H:i:s') : '00:00:00'),
                'sort' => [$i->sale->date, $i->sale->id, $i->id],
            ]);

        $saleReturns = SaleReturnItem::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('product_id', $productId)
            ->whereHas('saleReturn', fn ($q) => $q->withoutGlobalScopes()->whereNull('deleted_at')->where('branch_id', $branchId))
            ->with(['saleReturn' => fn ($q) => $q->withoutGlobalScopes()->whereNull('deleted_at')])
            ->get()
            ->map(fn ($i) => [
                'type' => 'SaleReturn',
                'item' => $i,
                'parent' => $i->saleReturn,
                'ts' => $i->saleReturn->date.' '.($i->saleReturn->created_at ? $i->saleReturn->created_at->format('H:i:s') : '00:00:00'),
                'sort' => [$i->saleReturn->date, $i->saleReturn->id, $i->id],
            ]);

        return $purchases->concat($purchaseReturns)->concat($sales)->concat($saleReturns)
            ->sortBy(fn ($e) => $e['sort'][0].'|'.str_pad((string) $e['sort'][1], 12, '0', STR_PAD_LEFT).'|'.str_pad((string) $e['sort'][2], 12, '0', STR_PAD_LEFT))
            ->values();
    }

    private function updateCogsJournal(string $model, int $modelId, float $newCogs, int $cogsId, int $inventoryAccountId): void
    {
        $entries = JournalEntry::withoutGlobalScopes()
            ->where('model', $model)
            ->where('model_id', $modelId)
            ->where(function ($q) use ($cogsId, $inventoryAccountId) {
                $q->whereIn('account_id', [$cogsId, $inventoryAccountId])
                    ->whereIn('counter_account_id', [$cogsId, $inventoryAccountId]);
            })
            ->get();

        if ($entries->isEmpty()) {
            return;
        }

        // Sale: COGS account is debit side. SaleReturn: inventory account is debit side.
        $debitAccount = $model === 'Sale' ? $cogsId : $inventoryAccountId;

        foreach ($entries as $entry) {
            $oldDebit = (float) $entry->debit;
            $oldCredit = (float) $entry->credit;

            if ($entry->account_id == $debitAccount) {
                $entry->debit = $newCogs;
                $entry->credit = 0;
            } else {
                $entry->debit = 0;
                $entry->credit = $newCogs;
            }

            $newDebit = (float) $entry->debit;
            $newCredit = (float) $entry->credit;

            if (round($oldDebit, 2) === round($newDebit, 2) && round($oldCredit, 2) === round($newCredit, 2)) {
                continue;
            }

            $entry->save();

            $message = sprintf(
                '[%s#%d] JournalEntry #%d (account=%d): debit %.2f → %.2f, credit %.2f → %.2f',
                $model,
                $modelId,
                $entry->id,
                $entry->account_id,
                $oldDebit,
                $newDebit,
                $oldCredit,
                $newCredit,
            );
            $this->line($message);
            Log::info('inventory:recalculate-cost '.$message, [
                'model' => $model,
                'model_id' => $modelId,
                'journal_entry_id' => $entry->id,
                'account_id' => $entry->account_id,
                'old_debit' => $oldDebit,
                'new_debit' => $newDebit,
                'old_credit' => $oldCredit,
                'new_credit' => $newCredit,
            ]);
        }
    }

    private function updateProductCost(int $productId): void
    {
        $rows = Inventory::withoutGlobalScopes()
            ->where('product_id', $productId)
            ->get(['cost', 'quantity']);

        $totalQty = $rows->sum('quantity');
        if ($totalQty <= 0) {
            return;
        }
        $totalCost = $rows->sum(fn ($r) => $r->cost * $r->quantity);
        $cost = round($totalCost / $totalQty, 2);

        $oldCost = (float) Product::withoutGlobalScopes()
            ->where('id', $productId)
            ->value('cost');

        if (round($oldCost, 2) === $cost) {
            return;
        }

        Product::withoutGlobalScopes()
            ->where('id', $productId)
            ->update(['cost' => $cost]);

        $message = sprintf('[Product#%d] cost: %.2f → %.2f', $productId, $oldCost, $cost);
        $this->line($message);
        Log::info('inventory:recalculate-cost '.$message, [
            'product_id' => $productId,
            'old_cost' => $oldCost,
            'new_cost' => $cost,
        ]);
    }
}

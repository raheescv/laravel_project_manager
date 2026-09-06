<?php

namespace App\Console\Commands\SingleUse;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MergeDuplicateProductsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:merge-duplicate-products
                            {keep? : ID of the product to keep}
                            {merge?* : IDs of the duplicate products to merge into it}
                            {--like= : Merge every product whose name contains this text into a single product}
                            {--auto : Merge every group whose names differ only by a trailing separator (a number, a -1/-2 counter or a [code])}
                            {--loose : With --auto, also strip a trailing number that equals the price, and merge across different prices}
                            {--scan : List duplicate-looking name groups and exit, changing nothing}
                            {--name= : Rename the surviving product to this name (single merge only)}
                            {--inventory-only : Skip the product merge and only fold duplicate inventory rows together}
                            {--keep-inventory : Leave the inventory alone: do not fold duplicate rows and do not touch the cost}
                            {--replay-cost : Rebuild the cost from purchase/sale history with inventory:recalculate-cost instead of averaging the rows}
                            {--apply : Write the changes (without this flag it is a dry run)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Merge duplicate products into one: repoint every reference to the surviving product, delete the duplicates, fold their inventory rows together and recalculate the cost';

    /**
     * Every column in the database that points at products.id.
     * [table, column] — plain repoint, no unique constraint in the way.
     */
    private array $repoint = [
        ['asset_depreciation_schedules', 'product_id'],
        ['grn_items', 'product_id'],
        ['inventories', 'product_id'],
        ['inventory_logs', 'product_id'],
        ['inventory_transfer_items', 'product_id'],
        ['issue_items', 'product_id'],
        ['local_purchase_order_items', 'product_id'],
        ['product_images', 'product_id'],
        ['product_prices', 'product_id'],
        ['purchase_items', 'product_id'],
        ['purchase_request_products', 'product_id'],
        ['purchase_return_items', 'product_id'],
        ['sale_items', 'product_id'],
        ['sale_return_items', 'product_id'],
        ['stock_check_items', 'product_id'],
        ['supply_request_items', 'product_id'],
        ['tailoring_order_items', 'product_id'],
    ];

    /**
     * Every column in the database that points at inventories.id.
     * Used when duplicate stock rows are folded into one; none of them is unique.
     */
    private array $inventoryRepoint = [
        ['inventory_transfer_items', 'inventory_id'],
        ['issue_items', 'inventory_id'],
        ['sale_items', 'inventory_id'],
        ['sale_return_items', 'inventory_id'],
        ['stock_check_items', 'inventory_id'],
        ['tailoring_order_items', 'inventory_id'],
    ];

    /**
     * Columns guarded by a unique index. A duplicate's row is repointed only when the
     * survivor has no equivalent row; otherwise the duplicate's row is dropped.
     * [table, column, other columns forming the unique key]
     */
    private array $uniqueRepoint = [
        ['product_units', 'product_id', ['sub_unit_id']],
        ['employee_commissions', 'product_id', ['tenant_id', 'employee_id']],
        ['product_raw_materials', 'product_id', ['tenant_id', 'raw_material_id']],
        ['product_raw_materials', 'raw_material_id', ['tenant_id', 'product_id']],
    ];

    public function handle(): int
    {
        if ($this->option('scan')) {
            return $this->scan();
        }

        if ($this->option('inventory-only')) {
            return $this->inventoryOnly();
        }

        $groups = match (true) {
            (bool) $this->option('like') => $this->groupsByLike((string) $this->option('like')),
            (bool) $this->option('auto') => $this->autoGroups(),
            default => $this->groupFromArguments(),
        };

        if ($groups === null) {
            return self::FAILURE;
        }

        if ($groups === []) {
            $this->warn('Nothing to merge.');

            return self::SUCCESS;
        }

        foreach ($groups as $group) {
            $this->describe($group);
        }

        if (! $this->option('apply')) {
            $this->newLine();
            $this->comment('Dry run. Nothing was written. Re-run with --apply to perform the merge.');

            return self::SUCCESS;
        }

        $total = count($groups);
        $this->newLine();
        if (! $this->confirm("This permanently deletes the duplicate products in {$total} group(s). Continue?", false)) {
            $this->comment('Aborted.');

            return self::SUCCESS;
        }

        $survivors = [];
        foreach ($groups as $group) {
            $keepId = $group['keep']->id;
            $mergeIds = $group['duplicates']->keys()->all();

            $result = DB::transaction(function () use ($keepId, $mergeIds, $group) {
                $this->merge($keepId, $mergeIds, $group['name']);

                if ($this->option('keep-inventory')) {
                    return;
                }

                return [
                    'stock' => $this->foldInventory([$keepId]),
                    'cost' => $this->recalculateProductCost($keepId),
                ];
            });

            $this->info('Merged  #'.$keepId.'  '.$group['name'].'  ('.(count($mergeIds) + 1).' -> 1)');
            $this->reportOutcome($result);
            $survivors[] = $keepId;
        }

        if ($this->option('replay-cost')) {
            $this->replayCost($survivors);
        }

        return self::SUCCESS;
    }

    /**
     * Report duplicate-looking name groups so a human can decide which are real.
     */
    private function scan(): int
    {
        $products = DB::table('products')->whereNull('deleted_at')->orderBy('id')
            ->get(['id', 'tenant_id', 'name', 'type', 'mrp', 'cost']);

        $groups = $products->groupBy(fn ($p) => $p->tenant_id.'|'.$p->type.'|'.mb_strtoupper($this->baseName($p->name)))
            ->filter(fn ($group) => $group->count() > 1);

        if ($groups->isEmpty()) {
            $this->info('No duplicate-looking product names found.');

            return self::SUCCESS;
        }

        $mergedByAuto = collect($this->autoGroups())
            ->flatMap(fn ($group) => $group['duplicates']->keys()->push($group['keep']->id))
            ->flip();

        foreach ($groups as $group) {
            $this->newLine();
            $this->line('<fg=cyan>'.$this->baseName($group->first()->name).'</>');
            foreach ($group as $p) {
                $auto = $mergedByAuto->has($p->id);
                $this->line(sprintf(
                    '  %s #%-6s %-45s mrp=%-10s cost=%s',
                    $auto ? '<fg=green>merge</>' : '     ',
                    $p->id, $p->name, $p->mrp, $p->cost
                ));
            }
        }

        $this->newLine();
        $this->comment('Only the rows marked "merge" are touched by --auto. Merge any other group deliberately with --like or by ID.');

        return self::SUCCESS;
    }

    /**
     * Every product whose name contains the given text becomes one product.
     */
    private function groupsByLike(string $like): ?array
    {
        $matches = DB::table('products')->whereNull('deleted_at')
            ->where('name', 'like', '%'.$like.'%')->orderBy('id')->get();

        if ($matches->count() < 2) {
            $this->error('Found '.$matches->count()." product(s) matching \"{$like}\" — need at least 2 to merge.");

            return null;
        }

        if ($matches->pluck('type')->unique()->count() > 1 || $matches->pluck('tenant_id')->unique()->count() > 1) {
            $this->error("Products matching \"{$like}\" span more than one type or tenant. Narrow the pattern, or merge by ID.");

            return null;
        }

        $keep = $matches->first();
        $name = $this->option('name') ?: ($this->commonPrefix($matches->pluck('name')->all()) ?: $like);

        return [[
            'keep' => $keep,
            'duplicates' => $matches->skip(1)->keyBy('id'),
            'name' => $name,
        ]];
    }

    /**
     * Groups whose names are the same once the trailing separator is removed.
     *
     * By default the price must match too, and a trailing number that IS the price is
     * never stripped — that keeps genuine size variants such as
     * "NATURES WHITENING PEARL 1200 / 500 / 700" as separate products.
     * --loose drops both guards.
     */
    private function autoGroups(): array
    {
        $loose = (bool) $this->option('loose');
        $products = DB::table('products')->whereNull('deleted_at')->orderBy('id')->get();

        return $products
            ->groupBy(fn ($p) => $p->tenant_id.'|'.$p->type
                .'|'.($loose ? '' : (float) $p->mrp)
                .'|'.mb_strtoupper($this->separatorBase($p, ! $loose)))
            ->filter(fn ($group) => $group->count() > 1)
            ->map(fn ($group) => [
                'keep' => $group->first(),
                'duplicates' => $group->skip(1)->keyBy('id'),
                'name' => $this->separatorBase($group->first(), ! $loose),
            ])
            ->values()
            ->all();
    }

    /**
     * The original explicit form: a survivor ID plus the IDs to fold into it.
     */
    private function groupFromArguments(): ?array
    {
        $keepId = (int) $this->argument('keep');
        $mergeIds = array_values(array_unique(array_map('intval', (array) $this->argument('merge'))));

        if (! $keepId || ! $mergeIds) {
            $this->error('Give a surviving product ID and at least one ID to merge, or use --like=, --auto or --scan.');

            return null;
        }

        if (in_array($keepId, $mergeIds, true)) {
            $this->error("The surviving product ({$keepId}) cannot also be in the merge list.");

            return null;
        }

        $keep = DB::table('products')->find($keepId);
        if (! $keep) {
            $this->error("Product {$keepId} does not exist.");

            return null;
        }

        $duplicates = DB::table('products')->whereIn('id', $mergeIds)->get()->keyBy('id');
        if ($missing = array_diff($mergeIds, $duplicates->keys()->all())) {
            $this->error('These product IDs do not exist: '.implode(', ', $missing));

            return null;
        }

        foreach ($duplicates as $duplicate) {
            if ($duplicate->tenant_id !== $keep->tenant_id) {
                $this->error("Product {$duplicate->id} belongs to tenant {$duplicate->tenant_id}, but the surviving product belongs to tenant {$keep->tenant_id}. Refusing to merge across tenants.");

                return null;
            }
            if ($duplicate->type !== $keep->type) {
                $this->error("Product {$duplicate->id} is a '{$duplicate->type}' but the surviving product is a '{$keep->type}'. Refusing to merge different types.");

                return null;
            }
        }

        return [[
            'keep' => $keep,
            'duplicates' => $duplicates,
            'name' => $this->option('name') ?: $keep->name,
        ]];
    }

    /**
     * Print what one group will do, and verify the new name is free.
     */
    private function describe(array $group): void
    {
        $keep = $group['keep'];
        $mergeIds = $group['duplicates']->keys()->all();

        $this->newLine();
        $this->line('<fg=cyan>'.$group['name'].'</>');
        $this->line('  keep    #'.$keep->id.'  '.$keep->name.'  (code '.$keep->code.', barcode '.$keep->barcode.')');
        foreach ($group['duplicates'] as $duplicate) {
            $this->line('  delete  #'.$duplicate->id.'  '.$duplicate->name.'  (code '.$duplicate->code.', barcode '.$duplicate->barcode.')');
        }

        $moves = [];
        foreach ($this->plan($keep->id, $mergeIds) as $entry) {
            if ($entry['move'] === 0 && $entry['drop'] === 0) {
                continue;
            }
            $moves[] = $entry['table'].' '.$entry['move'].($entry['drop'] ? ' (+'.$entry['drop'].' dropped)' : '');
        }
        $this->line('  moves   '.($moves ? implode(', ', $moves) : 'nothing references the duplicates'));

        $this->describeInventory(array_merge([$keep->id], $mergeIds), $keep);

        $clash = DB::table('products')
            ->where('tenant_id', $keep->tenant_id)
            ->where('name', $group['name'])
            ->where('type', $keep->type)
            ->whereNotIn('id', array_merge([$keep->id], $mergeIds))
            ->first();
        if ($clash) {
            $this->warn('  name    "'.$group['name'].'" is already used by #'.$clash->id.' — the rename will be skipped for this group.');
        }
    }

    /**
     * Repoint every reference onto the survivor, delete the duplicates, rename.
     */
    private function merge(int $keepId, array $mergeIds, string $newName): void
    {
        foreach ($this->repoint as [$table, $column]) {
            DB::table($table)->whereIn($column, $mergeIds)->update([$column => $keepId]);
        }

        foreach ($this->uniqueRepoint as [$table, $column, $keyColumns]) {
            $existing = DB::table($table)->where($column, $keepId)->get()
                ->map(fn ($row) => $this->keyOf($row, $keyColumns))
                ->flip();

            foreach (DB::table($table)->whereIn($column, $mergeIds)->get() as $row) {
                if ($existing->has($this->keyOf($row, $keyColumns))) {
                    DB::table($table)->where('id', $row->id)->delete();

                    continue;
                }
                DB::table($table)->where('id', $row->id)->update([$column => $keepId]);
                $existing->put($this->keyOf($row, $keyColumns), true);
            }
        }

        // A recipe row can now point a product at itself; that is not a real bill of materials.
        DB::table('product_raw_materials')->whereColumn('product_id', 'raw_material_id')->delete();

        // Hard delete: the unique (tenant_id, name, type) index still counts soft-deleted rows,
        // so a soft delete here would block the rename below.
        DB::table('products')->whereIn('id', $mergeIds)->delete();

        $keep = DB::table('products')->find($keepId);
        $taken = DB::table('products')
            ->where('tenant_id', $keep->tenant_id)
            ->where('name', $newName)
            ->where('type', $keep->type)
            ->where('id', '!=', $keepId)
            ->exists();

        if (! $taken) {
            DB::table('products')->where('id', $keepId)->update(['name' => $newName, 'updated_at' => now()]);
        }

        Cache::increment('product_units_version_'.$keepId);
    }

    /**
     * Count what each column would move or drop, without writing anything.
     */
    private function plan(int $keepId, array $mergeIds): array
    {
        $plan = [];

        foreach ($this->repoint as [$table, $column]) {
            $plan[] = [
                'table' => $table,
                'column' => $column,
                'move' => DB::table($table)->whereIn($column, $mergeIds)->count(),
                'drop' => 0,
            ];
        }

        foreach ($this->uniqueRepoint as [$table, $column, $keyColumns]) {
            $existing = DB::table($table)->where($column, $keepId)->get()
                ->map(fn ($row) => $this->keyOf($row, $keyColumns))
                ->flip();

            $move = 0;
            $drop = 0;
            foreach (DB::table($table)->whereIn($column, $mergeIds)->get() as $row) {
                $key = $this->keyOf($row, $keyColumns);
                if ($existing->has($key)) {
                    $drop++;

                    continue;
                }
                $move++;
                $existing->put($key, true);
            }

            $plan[] = ['table' => $table, 'column' => $column, 'move' => $move, 'drop' => $drop];
        }

        return $plan;
    }

    /**
     * The unique-index signature of a row, with the merged column already set to the survivor.
     */
    private function keyOf(object $row, array $keyColumns): string
    {
        return implode('|', array_map(fn ($column) => (string) $row->{$column}, $keyColumns));
    }

    /**
     * The product name with its trailing separator stripped.
     *
     * Removes a trailing "-1" / "-2" counter, a trailing bracketed code such as
     * "[25810-005]", and a trailing standalone number. Parentheses are left alone:
     * "Highlight (without prelighter)" is a different service, not a code suffix.
     *
     * When $keepPriceSuffix is true, a trailing number equal to the product's own
     * price is kept, because there it identifies a real size or variant.
     */
    private function separatorBase(object $product, bool $keepPriceSuffix = false): string
    {
        $name = trim((string) $product->name);

        $name = trim((string) preg_replace('/-\d{1,2}$/', '', $name));
        $name = trim((string) preg_replace('/\s*\[[^\]]*\]$/u', '', $name));

        if (preg_match('/^(.*\S)[\s\-_]+(\d+)$/u', $name, $matches)) {
            $isPrice = (float) $matches[2] === (float) $product->mrp;
            if (! ($keepPriceSuffix && $isPrice)) {
                $name = trim($matches[1]);
            }
        }

        return $name !== '' ? $name : trim((string) $product->name);
    }

    /**
     * A loose base name, used only by --scan to surface candidates for a human to read.
     */
    private function baseName(string $name): string
    {
        $base = preg_replace('/[\s\-_]*(\[[^\]]*\]|\([^\)]*\)|[0-9]+([\-\/][0-9A-Za-z]+)*)\s*$/u', '', trim($name));
        $base = trim((string) preg_replace('/\s+/', ' ', (string) $base));

        return $base !== '' ? $base : trim($name);
    }

    /**
     * Longest common prefix of the group's names, trimmed of trailing separators.
     */
    private function commonPrefix(array $names): string
    {
        $prefix = array_shift($names) ?? '';
        foreach ($names as $name) {
            while ($prefix !== '' && ! str_starts_with($name, $prefix)) {
                $prefix = mb_substr($prefix, 0, -1);
            }
        }

        return trim($prefix, " \t-_[(");
    }

    /**
     * --inventory-only: fold duplicate stock rows of products that are already merged
     * (or were never duplicated at all) without touching the products themselves.
     */
    private function inventoryOnly(): int
    {
        if ($this->option('keep-inventory')) {
            $this->error('--inventory-only and --keep-inventory ask for opposite things. Drop one of them.');

            return self::FAILURE;
        }

        $query = DB::table('products')->whereNull('deleted_at');

        if ($like = (string) $this->option('like')) {
            $query->where('name', 'like', '%'.$like.'%');
        }

        $ids = array_values(array_filter(array_map(
            'intval',
            array_merge([$this->argument('keep')], (array) $this->argument('merge'))
        )));
        if ($ids) {
            $query->whereIn('id', $ids);
        }

        $products = $query->orderBy('id')->get()->keyBy('id');
        $groups = $this->inventoryGroups($products->keys()->all());

        if ($groups->isEmpty()) {
            $this->info('No product has more than one stock row per branch, employee and batch.');

            return self::SUCCESS;
        }

        $productIds = $groups->map(fn ($rows) => $rows->first()->product_id)->unique()->values()->all();

        foreach ($productIds as $productId) {
            $product = $products[$productId];
            $this->newLine();
            $this->line('<fg=cyan>#'.$product->id.'  '.$product->name.'</>');
            $this->describeInventory([$productId], $product);
        }

        if (! $this->option('apply')) {
            $this->newLine();
            $this->comment('Dry run. Nothing was written. Re-run with --apply to fold these rows.');

            return self::SUCCESS;
        }

        $this->newLine();
        if (! $this->confirm('This permanently deletes the duplicate stock rows of '.count($productIds).' product(s). Continue?', false)) {
            $this->comment('Aborted.');

            return self::SUCCESS;
        }

        foreach ($productIds as $productId) {
            $result = DB::transaction(fn () => [
                'stock' => $this->foldInventory([$productId]),
                'cost' => $this->recalculateProductCost($productId),
            ]);

            $this->info('Folded  #'.$productId.'  '.$products[$productId]->name);
            $this->reportOutcome($result);
        }

        if ($this->option('replay-cost')) {
            $this->replayCost($productIds);
        }

        return self::SUCCESS;
    }

    /**
     * Print what the stock rows of these products will become, and the cost that follows.
     * $as is the product the rows end up on — its current cost is the "before" value.
     */
    private function describeInventory(array $productIds, object $as): void
    {
        if ($this->option('keep-inventory')) {
            $this->line('  stock   left untouched (--keep-inventory)');

            return;
        }

        foreach ($this->inventoryGroups($productIds, (int) $as->id) as $rows) {
            $first = $rows->first();
            $this->line(sprintf(
                '  stock   branch %s · batch %s · %d rows -> 1  (qty %s, cost %s, barcodes %s)',
                $first->branch_id,
                $first->batch,
                $rows->count(),
                round($rows->sum(fn ($row) => (float) $row->quantity), 3),
                $this->weightedCost($rows),
                $rows->pluck('barcode')->filter()->implode(', ')
            ));
        }

        $all = DB::table('inventories')->whereNull('deleted_at')->whereIn('product_id', $productIds)->get();
        if ($all->isEmpty()) {
            return;
        }

        // Folding preserves both the total quantity and the total value, so the average
        // over every row is already the cost the product ends up with.
        // A cost of zero is never written back — it would only wipe what the product knows.
        $cost = $this->weightedCost($all);
        if ($cost > 0 && round((float) $as->cost, 2) !== $cost) {
            $this->line('  cost    '.number_format((float) $as->cost, 2, '.', '').' -> '.number_format($cost, 2, '.', ''));
        }
    }

    /**
     * Stock rows of the given products that share a branch, employee and batch — the rows
     * that a merge turns into duplicates. Pass $asProductId to group rows across products
     * as they will sit once every row has been repointed onto the survivor.
     *
     * @return Collection<string, Collection<int, object>>
     */
    private function inventoryGroups(array $productIds, ?int $asProductId = null): Collection
    {
        if (! $productIds) {
            return collect();
        }

        return DB::table('inventories')->whereNull('deleted_at')
            ->whereIn('product_id', $productIds)->orderBy('id')->get()
            ->groupBy(fn ($row) => implode('|', [
                $row->tenant_id,
                $row->branch_id,
                $row->employee_id ?? 0,
                $asProductId ?? $row->product_id,
                mb_strtoupper(trim((string) $row->batch)),
            ]))
            ->filter(fn ($rows) => $rows->count() > 1);
    }

    /**
     * Fold every duplicate stock row of these products into one row per branch, employee
     * and batch: references move onto the surviving row, the quantities add up and the
     * cost becomes the quantity-weighted average of the rows.
     */
    private function foldInventory(array $productIds): array
    {
        $summary = ['groups' => 0, 'removed' => 0, 'moved' => 0];
        $barcodes = DB::table('products')->whereIn('id', $productIds)->pluck('barcode_number', 'id');

        foreach ($this->inventoryGroups($productIds) as $rows) {
            $keep = $this->survivingRow($rows, $barcodes[$rows->first()->product_id] ?? null);
            $loserIds = $rows->filter(fn ($row) => $row->id != $keep->id)->pluck('id')->all();

            foreach ($this->inventoryRepoint as [$table, $column]) {
                $summary['moved'] += DB::table($table)->whereIn($column, $loserIds)->update([$column => $keep->id]);
            }

            // barcode and total are generated columns; only the raw values may be written.
            DB::table('inventories')->where('id', $keep->id)->update([
                'quantity' => round($rows->sum(fn ($row) => (float) $row->quantity), 3),
                'cost' => $this->weightedCost($rows),
                'updated_at' => now(),
            ]);

            // Hard delete: a soft-deleted row keeps holding its barcode, and the stock
            // lists and scanners read barcodes straight off this table.
            DB::table('inventories')->whereIn('id', $loserIds)->delete();

            $summary['groups']++;
            $summary['removed'] += count($loserIds);
        }

        return $summary;
    }

    /**
     * The row the others fold into: the one carrying the product's own barcode if there is
     * one, so a product-wise label keeps scanning, otherwise the oldest row.
     */
    private function survivingRow(Collection $rows, ?string $productBarcode): object
    {
        if ($productBarcode) {
            $match = $rows->first(fn ($row) => (string) $row->barcode_number === (string) $productBarcode);
            if ($match) {
                return $match;
            }
        }

        return $rows->sortBy('id')->first();
    }

    /**
     * Quantity-weighted average cost. With no quantity left to weigh by, the plain average
     * of the rows that do carry a cost is the best available answer.
     */
    private function weightedCost(Collection $rows): float
    {
        $quantity = $rows->sum(fn ($row) => (float) $row->quantity);

        if ($quantity > 0) {
            return round($rows->sum(fn ($row) => (float) $row->cost * (float) $row->quantity) / $quantity, 2);
        }

        $costs = $rows->map(fn ($row) => (float) $row->cost)->filter(fn ($cost) => $cost > 0);

        return $costs->isEmpty() ? 0.0 : round($costs->avg(), 2);
    }

    /**
     * Rewrite products.cost as the weighted average of what is actually in stock.
     *
     * @return array{0: float, 1: float}|null [old, new] when the cost changed
     */
    private function recalculateProductCost(int $productId): ?array
    {
        $rows = DB::table('inventories')->whereNull('deleted_at')->where('product_id', $productId)->get();
        if ($rows->isEmpty()) {
            return null;
        }

        $cost = $this->weightedCost($rows);
        $old = (float) DB::table('products')->where('id', $productId)->value('cost');

        if ($cost <= 0 || round($old, 2) === $cost) {
            return null;
        }

        DB::table('products')->where('id', $productId)->update(['cost' => $cost, 'updated_at' => now()]);

        return [$old, $cost];
    }

    /**
     * Replay every purchase, sale and return to rebuild the cost, the inventory logs and
     * the COGS journal entries. Needs a resolvable tenant, unlike the rest of this command.
     */
    private function replayCost(array $productIds): void
    {
        foreach ($productIds as $productId) {
            $this->newLine();
            $this->line('<fg=cyan>Replaying cost history for product #'.$productId.'</>');
            $this->call('inventory:recalculate-cost', ['--product' => $productId]);
        }
    }

    /**
     * What one applied group actually did to the stock and the cost.
     */
    private function reportOutcome(?array $result): void
    {
        if (! $result) {
            return;
        }

        $stock = $result['stock'];
        if ($stock['removed']) {
            $this->line('  stock   '.$stock['removed'].' duplicate row(s) folded into '.$stock['groups'].', '.$stock['moved'].' reference(s) moved');
        }

        if ($result['cost']) {
            [$old, $new] = $result['cost'];
            $this->line('  cost    '.number_format($old, 2, '.', '').' -> '.number_format($new, 2, '.', ''));
        }
    }
}

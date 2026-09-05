<?php

namespace App\Console\Commands\SingleUse;

use Illuminate\Console\Command;
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
                            {--apply : Write the changes (without this flag it is a dry run)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Merge duplicate products into one: repoint every reference to the surviving product, then delete the duplicates';

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

        foreach ($groups as $group) {
            DB::transaction(fn () => $this->merge($group['keep']->id, $group['duplicates']->keys()->all(), $group['name']));
            $this->info('Merged  #'.$group['keep']->id.'  '.$group['name'].'  ('.($group['duplicates']->count() + 1).' -> 1)');
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
}

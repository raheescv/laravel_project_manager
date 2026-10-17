<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\Country;
use App\Models\Grn;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Sale;
use App\Models\Scopes\AssignedBranchScope;
use App\Models\TailoringOrder;
use App\Models\UniqueNoCounter;
use App\Models\User;
use App\Services\TenantService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Every segment getNextUniqueNumber() is called with, and the counter rows the
 * settings page can offer for them.
 *
 * A counter row only exists once its first document is numbered, so a branch
 * that never made a GRN has no GRN row to edit. possibleRows() fills those gaps
 * for the current year, seeding each with the highest number already used by
 * that branch's documents so saving it can never hand out a duplicate.
 */
final class UniqueNoCounters
{
    public const SALE = 'Sale';

    public const GRN = 'Grn';

    public const TAILORING_ORDER = 'TailoringOrder';

    public const BARCODE = 'Barcode';

    /** @var array<string, string> */
    public const SEGMENTS = [
        self::SALE => 'Sale',
        self::GRN => 'GRN',
        self::TAILORING_ORDER => 'Tailoring Order',
        self::BARCODE => 'Barcode',
    ];

    /** Tables whose `barcode_number` is drawn from the barcode counter. */
    public const BARCODE_TABLES = ['products', 'product_units', 'inventories'];

    /**
     * The permission that issues numbers for each segment: whoever can create
     * the document is the one who may see and correct its counter.
     *
     * @var array<string, string>
     */
    public const PERMISSIONS = [
        self::SALE => 'sale.create',
        self::GRN => 'grn.create',
        self::TAILORING_ORDER => 'tailoring order.create',
        self::BARCODE => 'product.create',
    ];

    /**
     * Segments the user may see and edit.
     *
     * @return list<string>
     */
    public static function allowedSegments(?User $user): array
    {
        if (! $user) {
            return [];
        }

        return array_values(array_filter(array_keys(self::PERMISSIONS), static fn (string $segment): bool => $user->can(self::PERMISSIONS[$segment])));
    }

    /** The `year` a segment's counter is keyed by right now. */
    public static function year(string $segment): string
    {
        if (tenant_cache('country_id', Country::QATAR) == Country::INDIA) {
            if (now()->lt(now()->copy()->month(3)->day(31))) {
                return now()->subYear()->format('y').'/'.now()->format('y');
            }

            return now()->format('y').'/'.now()->addYear()->format('y');
        }

        return $segment === self::BARCODE ? '1' : now()->format('y');
    }

    public static function label(string $segment): string
    {
        return self::SEGMENTS[$segment] ?? $segment;
    }

    /**
     * Saved counters plus one suggested row per segment and branch that has none yet,
     * limited to the given segments.
     *
     * @param  list<string>  $segments
     * @return list<array{year: string, branch_code: string, segment: string, number: int, is_new: bool}>
     */
    public static function possibleRows(array $segments): array
    {
        $rows = UniqueNoCounter::query()
            ->whereIn('segment', $segments)
            ->get(['year', 'branch_code', 'segment', 'number'])
            ->map(static fn (UniqueNoCounter $counter): array => [
                'year' => (string) $counter->year,
                'branch_code' => (string) $counter->branch_code,
                'segment' => (string) $counter->segment,
                'number' => (int) $counter->number,
                'is_new' => false,
            ]);

        $existing = $rows->mapWithKeys(static fn (array $row): array => [self::key($row['segment'], $row['branch_code'], $row['year']) => true]);
        $branches = Branch::query()->orderBy('code')->get(['id', 'code']);
        $highestBarcode = null;

        foreach (array_intersect(array_keys(self::SEGMENTS), $segments) as $segment) {
            $year = self::year($segment);

            foreach ($branches as $branch) {
                if (blank($branch->code) || isset($existing[self::key($segment, $branch->code, $year)])) {
                    continue;
                }

                $number = $segment === self::BARCODE
                    ? ($highestBarcode ??= self::highestBarcode())
                    : self::highestDocumentNumber($segment, $branch);

                $rows->push([
                    'year' => $year,
                    'branch_code' => (string) $branch->code,
                    'segment' => $segment,
                    'number' => $number,
                    'is_new' => true,
                ]);
            }
        }

        $order = array_flip(array_keys(self::SEGMENTS));

        return $rows
            ->sortBy([
                static fn (array $a, array $b): int => ($order[$a['segment']] ?? PHP_INT_MAX) <=> ($order[$b['segment']] ?? PHP_INT_MAX) ?: strcmp($a['segment'], $b['segment']),
                ['year', 'asc'],
                ['branch_code', 'asc'],
            ])
            ->values()
            ->all();
    }

    /** The trailing number of the branch's latest document in this year's format, or 0. */
    private static function highestDocumentNumber(string $segment, Branch $branch): int
    {
        [$query, $column, $prefix] = match ($segment) {
            self::SALE => [Sale::withTrashed()->withoutGlobalScope(AssignedBranchScope::class), 'invoice_no', $branch->code.'-'.self::year(self::SALE).'-'],
            self::GRN => [Grn::withTrashed(), 'grn_no', 'GRN-'.$branch->code.'-'.now()->format('y').'-'],
            self::TAILORING_ORDER => [TailoringOrder::withTrashed(), 'order_no', $branch->code.'-'.now()->format('y').'-'],
            default => [null, null, null],
        };

        if (! $query instanceof Builder) {
            return 0;
        }

        return (int) $query
            ->where('branch_id', $branch->id)
            ->where($column, 'like', str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $prefix).'%')
            ->selectRaw("MAX(CAST(SUBSTRING({$column}, ?) AS UNSIGNED)) as highest", [strlen($prefix) + 1])
            ->value('highest');
    }

    /** Barcodes are unique across the tenant, so every branch starts above the highest one in use. */
    private static function highestBarcode(): int
    {
        return (int) collect([Product::withTrashed(), ProductUnit::query(), Inventory::withTrashed()->withoutGlobalScope(AssignedBranchScope::class)])
            ->map(static fn (Builder $query): int => (int) $query
                ->whereRaw("barcode_number REGEXP '^[0-9]+$'")
                ->selectRaw('MAX(CAST(barcode_number AS UNSIGNED)) as highest')
                ->value('highest'))
            ->max();
    }

    /** The highest barcode the counters have handed out; the top of the system series. */
    public static function barcodeCeiling(): int
    {
        return (int) UniqueNoCounter::query()->where('segment', self::BARCODE)->max('number');
    }

    /**
     * How many distinct barcodes the system series holds and the range they span.
     *
     * @return array{count: int, first: ?int, last: ?int}
     */
    public static function barcodeSeries(?int $ceiling = null): array
    {
        $ceiling ??= self::barcodeCeiling();

        if ($ceiling < 1) {
            return ['count' => 0, 'first' => null, 'last' => null];
        }

        [$sql, $bindings] = self::barcodeSeriesSql($ceiling);
        $series = DB::selectOne("SELECT COUNT(*) AS total, MIN(CAST(barcode_number AS UNSIGNED)) AS first, MAX(CAST(barcode_number AS UNSIGNED)) AS last FROM ({$sql}) series", $bindings);

        return [
            'count' => (int) $series->total,
            'first' => $series->first === null ? null : (int) $series->first,
            'last' => $series->last === null ? null : (int) $series->last,
        ];
    }

    /**
     * Distinct barcode numbers of the system series across every barcode table:
     * all-digit values at or below the counter's ceiling. Longer or higher numbers,
     * such as a manufacturer's EAN typed in by hand, are left out.
     *
     * The length cap keeps CAST from overflowing, which strict mode turns into an error.
     *
     * @return array{0: string, 1: list<int>}
     */
    public static function barcodeSeriesSql(int $ceiling): array
    {
        $tenantId = (int) app(TenantService::class)->getCurrentTenantId();
        $selects = [];
        $bindings = [];

        foreach (self::BARCODE_TABLES as $table) {
            $selects[] = "SELECT barcode_number FROM {$table} WHERE tenant_id = ? AND barcode_number REGEXP '^[0-9]{1,18}$' AND CAST(barcode_number AS UNSIGNED) <= ?";
            array_push($bindings, $tenantId, $ceiling);
        }

        return [implode(' UNION ', $selects), $bindings];
    }

    private static function key(string $segment, string $branchCode, string $year): string
    {
        return $segment.'|'.$branchCode.'|'.$year;
    }
}

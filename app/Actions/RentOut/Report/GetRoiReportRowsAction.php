<?php

namespace App\Actions\RentOut\Report;

use App\Models\RentOutRoi;
use App\Models\RentOutTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ROI report rows, collapsed to one row per sale (lease) agreement.
 *
 * ROI is booked per period on the ROI tab and paid out separately, so the
 * numbers that matter are the agreement's: what ROI was booked over the period,
 * what has been paid out to the buyer, and what is still payable. The ROI lines
 * are aggregated per `rent_out_id` and the payouts folded in from
 * `rent_out_transactions` - the same shape as the service charge report.
 *
 * The same query backs the screen and the Excel export, so both always agree.
 */
class GetRoiReportRowsAction
{
    /** Amounts under this are rounding noise, not a real balance. */
    public const EPSILON = 0.005;

    private const AMOUNT_SQL = 'COALESCE(SUM(rent_out_rois.amount), 0)';

    /** Every ROI payout on the agreement, floored at zero. */
    private const PAYOUTS_SQL = 'GREATEST(COALESCE(MAX(rp.payouts), 0), 0)';

    /**
     * Payouts are agreement-wide while the ROI here is period-filtered, so cap
     * what we call "paid" at what this report booked - otherwise a buyer paid up
     * for three years shows a negative balance on a one-year view.
     */
    private const PAID_SQL = 'LEAST('.self::AMOUNT_SQL.', '.self::PAYOUTS_SQL.')';

    private const BALANCE_SQL = self::AMOUNT_SQL.' - '.self::PAID_SQL;

    /** Sortable UI field => the select alias it orders on. */
    private const SORTABLE = [
        'date' => 'last_booked_at',
        'customer' => 'customer_name',
        'group' => 'group_name',
        'building' => 'building_name',
        'property' => 'property_number_sort',
        'start_date' => 'period_start',
        'end_date' => 'period_end',
        'no_of_months' => 'no_of_months',
        'sale_price' => 'sale_price',
        'per_month_roi' => 'per_month_roi',
        'lines' => 'entry_count',
        'amount' => 'amount',
        'paid' => 'paid',
        'balance' => 'balance',
    ];

    /**
     * Settlement state of a report row.
     */
    public static function status(float $amount, float $paid, float $balance): string
    {
        if ($amount > self::EPSILON && $balance <= self::EPSILON) {
            return 'paid';
        }

        return $paid > self::EPSILON ? 'partial' : 'unpaid';
    }

    /**
     * A page of agreement rows for the screen.
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        $rows = $this->ordered($filters)->paginate($perPage);

        return $rows->setCollection($this->withStatus($rows->getCollection()));
    }

    /**
     * Every agreement row for the current filters - used by the export.
     */
    public function rows(array $filters): Collection
    {
        return $this->withStatus($this->ordered($filters)->get());
    }

    /**
     * One row per agreement, ordered for display.
     */
    public function ordered(array $filters): QueryBuilder
    {
        $column = self::SORTABLE[$filters['sortField'] ?? ''] ?? 'last_booked_at';
        $direction = strtolower($filters['sortDirection'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $this->query($filters)
            ->toBase()
            ->orderBy($column, $direction)
            ->orderBy('rent_out_id', 'desc');
    }

    /**
     * Report-wide totals, summed over the grouped rows so the capped `paid`
     * carries through instead of being recomputed on raw payouts.
     *
     * @return array{agreements:int,lines:int,amount:float,paid:float,balance:float,paid_rate:float}
     */
    public function totals(array $filters): array
    {
        $row = $this->aggregate($filters)->first();

        $amount = (float) ($row->amount ?? 0);
        $paid = (float) ($row->paid ?? 0);

        return [
            'agreements' => (int) ($row->agreements ?? 0),
            'lines' => (int) ($row->entry_count ?? 0),
            'amount' => $amount,
            'paid' => $paid,
            'balance' => (float) ($row->balance ?? 0),
            'paid_rate' => $amount > 0 ? round($paid / $amount * 100, 1) : 0.0,
        ];
    }

    /**
     * Booked / paid / payable per project group.
     *
     * @return array<int, array{name:string,agreements:int,lines:int,amount:float,paid:float,balance:float,paid_rate:float}>
     */
    public function summaryByGroup(array $filters): array
    {
        return $this->aggregate($filters)
            ->addSelect('t.group_name')
            ->groupBy('t.group_name')
            ->orderByDesc('amount')
            ->get()
            ->map(function ($row) {
                $amount = (float) $row->amount;
                $paid = (float) $row->paid;

                return [
                    'name' => $row->group_name ?: 'Uncategorised',
                    'agreements' => (int) $row->agreements,
                    'lines' => (int) $row->entry_count,
                    'amount' => $amount,
                    'paid' => $paid,
                    'balance' => (float) $row->balance,
                    'paid_rate' => $amount > 0 ? round($paid / $amount * 100, 1) : 0.0,
                ];
            })
            ->all();
    }

    /**
     * The ROI lines behind the given agreements, keyed by `rent_out_id`.
     *
     * Built from the same filtered base query as the grouped rows, so an
     * expanded row always adds up to the total printed on its parent.
     *
     * @param  array<int, int>  $rentOutIds
     * @return Collection<array-key, covariant Collection<int, RentOutRoi>>
     */
    public function lines(array $filters, array $rentOutIds): Collection
    {
        if (empty($rentOutIds)) {
            return collect();
        }

        return $this->baseQuery($filters)
            ->whereIn('rent_out_rois.rent_out_id', $rentOutIds)
            ->select('rent_out_rois.*')
            ->orderBy('rent_out_rois.start_date')
            ->orderBy('rent_out_rois.id')
            ->get()
            ->groupBy('rent_out_id');
    }

    /**
     * The grouped query: one row per agreement, unordered.
     */
    public function query(array $filters): Builder
    {
        return $this->baseQuery($filters)
            ->leftJoinSub($this->payoutsSubQuery($filters), 'rp', 'rp.rent_out_id', '=', 'rent_out_rois.rent_out_id')
            ->groupBy('rent_out_rois.rent_out_id')
            ->select([
                DB::raw('rent_out_rois.rent_out_id as rent_out_id'),
                DB::raw('COUNT(*) as entry_count'),
                DB::raw('MAX(rent_out_rois.date) as last_booked_at'),
                DB::raw('MIN(rent_out_rois.start_date) as period_start'),
                DB::raw('MAX(rent_out_rois.end_date) as period_end'),
                DB::raw('COALESCE(SUM(rent_out_rois.no_of_months), 0) as no_of_months'),
                DB::raw('MAX(rent_out_rois.sale_price) as sale_price'),
                DB::raw('MAX(rent_out_rois.per_month_roi) as per_month_roi'),
                DB::raw(self::AMOUNT_SQL.' as amount'),
                DB::raw(self::PAYOUTS_SQL.' as payouts'),
                DB::raw(self::PAID_SQL.' as paid'),
                DB::raw(self::BALANCE_SQL.' as balance'),
                DB::raw($this->collapsed('rent_out_rois.remark').' as remark'),
                DB::raw('MAX(rent_outs.account_id) as account_id'),
                DB::raw('MAX(accounts.name) as customer_name'),
                DB::raw('MAX(properties.number) as property_number'),
                DB::raw('MAX(CAST(properties.number AS UNSIGNED)) as property_number_sort'),
                DB::raw('MAX(properties.ownership) as ownership'),
                DB::raw('COALESCE(MAX(property_groups.name), \'Uncategorised\') as group_name'),
                DB::raw('MAX(property_buildings.name) as building_name'),
                DB::raw('MAX(property_types.name) as type_name'),
            ])
            ->tap(fn ($query) => $this->applyStatusFilter($query, $filters));
    }

    /**
     * ROI lines for sale (lease) agreements, joined out for grouping and filtered.
     *
     * @return Builder<RentOutRoi>
     */
    private function baseQuery(array $filters): Builder
    {
        return RentOutRoi::query()
            ->whereHas('rentOut', fn ($query) => $query->where('agreement_type', 'lease'))
            ->join('rent_outs', 'rent_outs.id', '=', 'rent_out_rois.rent_out_id')
            ->leftJoin('accounts', 'accounts.id', '=', 'rent_outs.account_id')
            ->leftJoin('properties', 'properties.id', '=', 'rent_outs.property_id')
            ->leftJoin('property_groups', 'property_groups.id', '=', 'rent_outs.property_group_id')
            ->leftJoin('property_buildings', 'property_buildings.id', '=', 'rent_outs.property_building_id')
            ->leftJoin('property_types', 'property_types.id', '=', 'rent_outs.property_type_id')
            ->tap(fn ($query) => $this->applyFilters($query, $filters));
    }

    /**
     * ROI paid out per agreement, bounded only by the closing date: ROI booked
     * inside the window is often paid later in the same window.
     */
    private function payoutsSubQuery(array $filters): Builder
    {
        return RentOutTransaction::query()
            ->selectRaw('rent_out_transactions.rent_out_id, COALESCE(SUM(rent_out_transactions.debit), 0) as payouts')
            ->where('rent_out_transactions.source', RentOutRoi::PAYMENT_SOURCE)
            ->when(
                $filters['dateTo'] ?? null,
                fn ($query, $value) => $query->whereDate('rent_out_transactions.date', '<=', $value)
            )
            ->groupBy('rent_out_transactions.rent_out_id');
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        $query
            ->when($filters['filterGroup'] ?? null, fn ($q, $v) => $q->where('rent_outs.property_group_id', $v))
            ->when($filters['filterBuilding'] ?? null, fn ($q, $v) => $q->where('rent_outs.property_building_id', $v))
            ->when($filters['filterType'] ?? null, fn ($q, $v) => $q->where('rent_outs.property_type_id', $v))
            ->when($filters['filterProperty'] ?? null, fn ($q, $v) => $q->where('rent_outs.property_id', $v))
            ->when($filters['filterCustomer'] ?? null, fn ($q, $v) => $q->where('rent_outs.account_id', $v))
            ->when($filters['filterOwnership'] ?? null, fn ($q, $v) => $q->where('properties.ownership', $v))
            // Filter on the period the ROI covers, not the day it was booked.
            ->when($filters['dateFrom'] ?? null, fn ($q, $v) => $q->where('rent_out_rois.start_date', '>=', $v))
            ->when($filters['dateTo'] ?? null, fn ($q, $v) => $q->where('rent_out_rois.start_date', '<=', $v))
            ->when($filters['search'] ?? null, function ($q, $value) {
                $value = trim($value);
                $q->where(function ($q) use ($value) {
                    $q->where('rent_out_rois.id', 'like', "%{$value}%")
                        ->orWhere('rent_out_rois.remark', 'like', "%{$value}%")
                        ->orWhere('rent_out_rois.description', 'like', "%{$value}%")
                        ->orWhere('accounts.name', 'like', "%{$value}%")
                        ->orWhere('properties.number', 'like', "%{$value}%");
                });
            });
    }

    /**
     * Paid / part-paid / unpaid, applied after aggregation.
     */
    private function applyStatusFilter(Builder $query, array $filters): void
    {
        $epsilon = self::EPSILON;

        match ($filters['filterStatus'] ?? '') {
            'paid' => $query->havingRaw(self::AMOUNT_SQL." > {$epsilon} and ".self::BALANCE_SQL." <= {$epsilon}"),
            'partial' => $query->havingRaw(self::PAID_SQL." > {$epsilon} and ".self::BALANCE_SQL." > {$epsilon}"),
            'unpaid' => $query->havingRaw(self::PAID_SQL." <= {$epsilon}"),
            default => null,
        };
    }

    /**
     * Aggregate over the grouped rows - `paid` is capped per agreement, so it can
     * only be totalled after grouping, never re-derived from the raw payouts.
     */
    private function aggregate(array $filters): QueryBuilder
    {
        return DB::query()
            ->fromSub($this->query($filters)->toBase(), 't')
            ->selectRaw('COUNT(*) as agreements')
            ->selectRaw('COALESCE(SUM(t.entry_count), 0) as entry_count')
            ->selectRaw('COALESCE(SUM(t.amount), 0) as amount')
            ->selectRaw('COALESCE(SUM(t.paid), 0) as paid')
            ->selectRaw('COALESCE(SUM(t.balance), 0) as balance');
    }

    /**
     * Stamp each row with its settlement state so the view and the export label it identically.
     *
     * @template TRow of object
     *
     * @param  Collection<int, TRow>  $rows
     * @return Collection<int, TRow>
     */
    private function withStatus(Collection $rows): Collection
    {
        return $rows->each(function ($row) {
            $row->status = self::status((float) $row->amount, (float) $row->paid, (float) $row->balance);
        });
    }

    /**
     * Per-agreement text: the one value when the lines agree, a marker when they don't.
     */
    private function collapsed(string $column): string
    {
        return "CASE WHEN COUNT(DISTINCT COALESCE(NULLIF({$column}, ''), '__EMPTY__')) > 1"
            ." THEN 'Multiple entries' ELSE MAX({$column}) END";
    }
}

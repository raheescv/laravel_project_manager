<?php

namespace App\Actions\RentOut\Report;

use App\Models\RentOutService;
use App\Models\RentOutTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Service charge report rows, collapsed to one row per sale (lease) agreement.
 *
 * A single agreement is usually charged several times - a period per year, a
 * top-up when the unit size is corrected - and the interesting numbers are the
 * agreement's: what was charged over the period, what the customer has actually
 * paid, what is still owed. So the charge lines are aggregated per `rent_out_id`
 * and the receipts are folded in from `rent_out_transactions`.
 *
 * The same query backs the screen and the Excel export, so both always agree.
 */
class GetServiceChargeReportRowsAction
{
    /** Sources carrying service money in `rent_out_transactions` (debit = charge, credit = receipt). */
    public const SERVICE_SOURCES = ['Service', 'ServiceCharge'];

    /** Amounts under this are rounding noise, not a real balance. */
    public const EPSILON = 0.005;

    private const AMOUNT_SQL = 'COALESCE(SUM(rent_out_services.amount), 0)';

    /** Every service receipt on the agreement, floored at zero so a net refund cannot read as negative paid. */
    private const RECEIPTS_SQL = 'GREATEST(COALESCE(MAX(sp.receipts), 0), 0)';

    /**
     * Receipts are agreement-wide while the charges here are period-filtered, so
     * cap what we call "paid" at what this report actually charged - otherwise a
     * customer paid up for three years shows a negative balance on a one-year view.
     */
    private const PAID_SQL = 'LEAST('.self::AMOUNT_SQL.', '.self::RECEIPTS_SQL.')';

    private const BALANCE_SQL = self::AMOUNT_SQL.' - '.self::PAID_SQL;

    /** Sortable UI field => the select alias it orders on. */
    private const SORTABLE = [
        'date' => 'last_charged_at',
        'customer' => 'customer_name',
        'group' => 'group_name',
        'building' => 'building_name',
        'property' => 'property_number_sort',
        'start_date' => 'period_start',
        'end_date' => 'period_end',
        'no_of_months' => 'no_of_months',
        'no_of_days' => 'no_of_days',
        'unit_size' => 'unit_size',
        'lines' => 'charge_count',
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
        $column = self::SORTABLE[$filters['sortField'] ?? ''] ?? 'last_charged_at';
        $direction = strtolower($filters['sortDirection'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $this->query($filters)
            ->toBase()
            ->orderBy($column, $direction)
            ->orderBy('rent_out_id', 'desc');
    }

    /**
     * Report-wide totals, summed over the grouped rows so the capped `paid`
     * carries through instead of being recomputed on raw receipts.
     *
     * @return array{agreements:int,lines:int,amount:float,paid:float,balance:float,collection_rate:float}
     */
    public function totals(array $filters): array
    {
        $row = $this->aggregate($filters)->first();

        $amount = (float) ($row->amount ?? 0);
        $paid = (float) ($row->paid ?? 0);

        return [
            'agreements' => (int) ($row->agreements ?? 0),
            'lines' => (int) ($row->charge_count ?? 0),
            'amount' => $amount,
            'paid' => $paid,
            'balance' => (float) ($row->balance ?? 0),
            'collection_rate' => $amount > 0 ? round($paid / $amount * 100, 1) : 0.0,
        ];
    }

    /**
     * Charged / paid / outstanding per project group.
     *
     * @return array<int, array{name:string,agreements:int,lines:int,amount:float,paid:float,balance:float,collection_rate:float}>
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
                    'lines' => (int) $row->charge_count,
                    'amount' => $amount,
                    'paid' => $paid,
                    'balance' => (float) $row->balance,
                    'collection_rate' => $amount > 0 ? round($paid / $amount * 100, 1) : 0.0,
                ];
            })
            ->all();
    }

    /**
     * The charge lines behind the given agreements, keyed by `rent_out_id`.
     *
     * Built from the same filtered base query as the grouped rows, so an
     * expanded row always adds up to the total printed on its parent.
     *
     * @param  array<int, int>  $rentOutIds
     * @return Collection<int, Collection<int, RentOutService>>
     */
    public function lines(array $filters, array $rentOutIds): Collection
    {
        if (empty($rentOutIds)) {
            return collect();
        }

        return $this->baseQuery($filters)
            ->whereIn('rent_out_services.rent_out_id', $rentOutIds)
            ->select('rent_out_services.*')
            ->orderBy('rent_out_services.start_date')
            ->orderBy('rent_out_services.id')
            ->get()
            ->groupBy('rent_out_id');
    }

    /**
     * The grouped query: one row per agreement, unordered.
     */
    public function query(array $filters): Builder
    {
        return $this->baseQuery($filters)
            ->leftJoinSub($this->receiptsSubQuery($filters), 'sp', 'sp.rent_out_id', '=', 'rent_out_services.rent_out_id')
            ->groupBy('rent_out_services.rent_out_id')
            ->select([
                DB::raw('rent_out_services.rent_out_id as rent_out_id'),
                DB::raw('COUNT(*) as charge_count'),
                DB::raw('MAX(rent_out_services.created_at) as last_charged_at'),
                DB::raw('MIN(rent_out_services.start_date) as period_start'),
                DB::raw('MAX(rent_out_services.end_date) as period_end'),
                DB::raw('COALESCE(SUM(rent_out_services.no_of_months), 0) as no_of_months'),
                DB::raw('COALESCE(SUM(rent_out_services.no_of_days), 0) as no_of_days'),
                DB::raw('MAX(rent_out_services.unit_size) as unit_size'),
                DB::raw('MAX(rent_out_services.per_square_meter_price) as per_square_meter_price'),
                DB::raw('MAX(rent_out_services.per_day_price) as per_day_price'),
                DB::raw(self::AMOUNT_SQL.' as amount'),
                DB::raw(self::RECEIPTS_SQL.' as receipts'),
                DB::raw(self::PAID_SQL.' as paid'),
                DB::raw(self::BALANCE_SQL.' as balance'),
                DB::raw($this->collapsed('rent_out_services.remark').' as remark'),
                DB::raw($this->collapsed('rent_out_services.reason').' as reason'),
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
     * Charge lines for sale (lease) agreements, joined out for grouping and filtered.
     */
    private function baseQuery(array $filters): Builder
    {
        return RentOutService::query()
            ->whereHas('rentOut', fn ($query) => $query->where('agreement_type', 'lease'))
            ->join('rent_outs', 'rent_outs.id', '=', 'rent_out_services.rent_out_id')
            ->leftJoin('accounts', 'accounts.id', '=', 'rent_outs.account_id')
            ->leftJoin('properties', 'properties.id', '=', 'rent_outs.property_id')
            ->leftJoin('property_groups', 'property_groups.id', '=', 'rent_outs.property_group_id')
            ->leftJoin('property_buildings', 'property_buildings.id', '=', 'rent_outs.property_building_id')
            ->leftJoin('property_types', 'property_types.id', '=', 'rent_outs.property_type_id')
            ->tap(fn ($query) => $this->applyFilters($query, $filters));
    }

    /**
     * Service receipts per agreement.
     *
     * Only the closing date bounds this: a charge raised inside the window is
     * frequently settled later in the same window, and money banked before the
     * window still pays the charge down.
     */
    private function receiptsSubQuery(array $filters): Builder
    {
        return RentOutTransaction::query()
            ->selectRaw('rent_out_transactions.rent_out_id, COALESCE(SUM(rent_out_transactions.credit), 0) as receipts')
            ->whereIn('rent_out_transactions.source', self::SERVICE_SOURCES)
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
            // Filter on the period the charge covers, not the day it was raised -
            // a charge for next year is billed months ahead of its start date.
            ->when($filters['dateFrom'] ?? null, fn ($q, $v) => $q->where('rent_out_services.start_date', '>=', $v))
            ->when($filters['dateTo'] ?? null, fn ($q, $v) => $q->where('rent_out_services.start_date', '<=', $v))
            ->when($filters['search'] ?? null, function ($q, $value) {
                $value = trim($value);
                $q->where(function ($q) use ($value) {
                    $q->where('rent_out_services.id', 'like', "%{$value}%")
                        ->orWhere('rent_out_services.remark', 'like', "%{$value}%")
                        ->orWhere('rent_out_services.reason', 'like', "%{$value}%")
                        ->orWhere('rent_out_services.description', 'like', "%{$value}%")
                        ->orWhere('accounts.name', 'like', "%{$value}%")
                        ->orWhere('properties.number', 'like', "%{$value}%");
                });
            });
    }

    /**
     * Settled / part-settled / unsettled, applied after aggregation.
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
     * only be totalled after grouping, never re-derived from the raw receipts.
     */
    private function aggregate(array $filters): QueryBuilder
    {
        return DB::query()
            ->fromSub($this->query($filters)->toBase(), 't')
            ->selectRaw('COUNT(*) as agreements')
            ->selectRaw('COALESCE(SUM(t.charge_count), 0) as charge_count')
            ->selectRaw('COALESCE(SUM(t.amount), 0) as amount')
            ->selectRaw('COALESCE(SUM(t.paid), 0) as paid')
            ->selectRaw('COALESCE(SUM(t.balance), 0) as balance');
    }

    /**
     * Stamp each row with its settlement state so the view and the export label it identically.
     *
     * @param  Collection<int, object>  $rows
     * @return Collection<int, object>
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

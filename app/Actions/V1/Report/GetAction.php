<?php

namespace App\Actions\V1\Report;

use App\Http\Requests\V1\Report\GetRequest;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturnItem;
use Illuminate\Support\Facades\DB;

class GetAction
{
    /**
     * Fetch a system-wide analytical report by the requested breakdown type.
     *
     * Every breakdown is server-paginated (page / per_page) for infinite scroll,
     * and ships a `summary` of full-set grand totals so the mobile client can
     * draw accurate share-of-total bars and footers without loading every page.
     */
    public function execute(GetRequest $request): array
    {
        $type = $request->validated('type');
        $startDate = $request->validated('startDate');
        $endDate = $request->validated('endDate');

        // The overview report is a single rich snapshot (sales performance +
        // payment overview + breakdowns), not a paginated table — return it as-is.
        if ($type === 'overview') {
            return array_merge(
                ['type' => $type],
                (new OverviewAction())->execute($startDate, $endDate, $request->validated('branch_id')),
            );
        }

        $employeeId = $request->validated('employee_id');
        $sort = $request->validated('sort');
        $page = max(1, (int) ($request->validated('page') ?? 1));
        $perPage = min(100, max(1, (int) ($request->validated('per_page') ?? 20)));

        [$rows, $summary, $total] = match ($type) {
            'employeewise' => $this->employeeWise($startDate, $endDate, $employeeId, $page, $perPage),
            'itemwise' => $this->itemWise($startDate, $endDate, $employeeId, $page, $perPage, $sort),
            default => $this->billWise($startDate, $endDate, $employeeId, $page, $perPage),
        };

        return [
            'type' => $type,
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'filters' => [
                'employee_id' => $employeeId ? (string) $employeeId : null,
            ],
            'summary' => $summary,
            'rows' => $rows,
            'pagination' => [
                'current_page' => $page,
                'last_page' => (int) max(1, ceil($total / $perPage)),
                'per_page' => $perPage,
                'total' => $total,
            ],
        ];
    }

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: array<string, mixed>, 2: int}
     */
    private function billWise(?string $startDate, ?string $endDate, ?int $employeeId, int $page, int $perPage): array
    {
        $base = Sale::query()
            ->completed()
            ->when($startDate, fn ($q, $value) => $q->whereDate('date', '>=', $value))
            ->when($endDate, fn ($q, $value) => $q->whereDate('date', '<=', $value))
            ->when($employeeId, fn ($q, $value) => $q->whereHas('items', fn ($i) => $i->where('employee_id', $value)));

        $totals = (clone $base)->selectRaw('COUNT(*) as invoices, COALESCE(SUM(paid), 0) as paid')->first();
        $total = (int) $totals->invoices;

        $rows = (clone $base)
            ->with('account:id,name')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->forPage($page, $perPage)
            ->get()
            ->map(fn (Sale $sale) => [
                'id' => (string) $sale->id,
                'invoice_no' => $sale->invoice_no,
                'date' => $sale->date,
                'customer' => $sale->customer_name ?: $sale->account?->name,
                'gross_amount' => (float) $sale->gross_amount,
                'discount' => round((float) $sale->item_discount + (float) $sale->other_discount, 2),
                'paid' => (float) $sale->paid,
            ])
            ->values()
            ->all();

        $summary = [
            'invoices' => $total,
            'total_paid' => round((float) $totals->paid, 2),
        ];

        return [$rows, $summary, $total];
    }

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: array<string, mixed>, 2: int}
     */
    private function employeeWise(?string $startDate, ?string $endDate, ?int $employeeId, int $page, int $perPage): array
    {
        // Net of returns (sale items − return items) using base-unit quantities —
        // mirrors the web Employee Performance table & the overview, so the figures
        // reconcile. Counts (bills/items) come from the sales side only.
        $saleItems = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('users', 'users.id', '=', 'sale_items.employee_id')
            ->where('sales.status', 'completed')
            ->whereNull('sales.deleted_at')
            ->when($startDate, fn ($q, $value) => $q->whereDate('sales.date', '>=', $value))
            ->when($endDate, fn ($q, $value) => $q->whereDate('sales.date', '<=', $value))
            ->when($employeeId, fn ($q, $value) => $q->where('sale_items.employee_id', $value))
            ->groupBy('users.id', 'users.name')
            ->selectRaw('users.id, users.name as employee')
            ->selectRaw('SUM(sale_items.total) as sale_total')
            ->selectRaw('0 as return_total')
            ->selectRaw('SUM(sale_items.base_unit_quantity) as sale_qty')
            ->selectRaw('0 as return_qty')
            ->selectRaw('COUNT(DISTINCT sale_items.sale_id) as bills_count')
            ->selectRaw('COUNT(sale_items.id) as items_count');

        $returnItems = SaleReturnItem::query()
            ->join('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
            ->join('users', 'users.id', '=', 'sale_return_items.employee_id')
            ->where('sale_returns.status', 'completed')
            ->whereNull('sale_returns.deleted_at')
            ->when($startDate, fn ($q, $value) => $q->whereDate('sale_returns.date', '>=', $value))
            ->when($endDate, fn ($q, $value) => $q->whereDate('sale_returns.date', '<=', $value))
            ->when($employeeId, fn ($q, $value) => $q->where('sale_return_items.employee_id', $value))
            ->groupBy('users.id', 'users.name')
            ->selectRaw('users.id, users.name as employee')
            ->selectRaw('0 as sale_total')
            ->selectRaw('SUM(sale_return_items.total) as return_total')
            ->selectRaw('0 as sale_qty')
            ->selectRaw('SUM(sale_return_items.base_unit_quantity) as return_qty')
            ->selectRaw('0 as bills_count')
            ->selectRaw('0 as items_count');

        $net = DB::query()
            ->fromSub($saleItems->unionAll($returnItems), 'u')
            ->groupBy('id', 'employee')
            ->havingRaw('SUM(sale_qty) - SUM(return_qty) > 0')
            ->selectRaw('id, employee')
            ->selectRaw('SUM(sale_total) - SUM(return_total) as revenue')
            ->selectRaw('SUM(sale_qty) - SUM(return_qty) as quantity')
            ->selectRaw('SUM(bills_count) as bills_count')
            ->selectRaw('SUM(items_count) as items_count');

        $wrapped = DB::query()->fromSub($net, 'n');
        $total = (clone $wrapped)->count();
        $totalRevenue = (float) (clone $wrapped)->sum('revenue');

        $rows = (clone $wrapped)
            ->orderByDesc('revenue')
            ->forPage($page, $perPage)
            ->get()
            ->map(fn ($row) => [
                'employee_id' => (string) $row->id,
                'employee_name' => $row->employee,
                'bills_count' => (int) $row->bills_count,
                'items_count' => (int) $row->items_count,
                'revenue' => round((float) $row->revenue, 2),
            ])
            ->all();

        $summary = [
            'employees' => $total,
            'total_revenue' => round($totalRevenue, 2),
        ];

        return [$rows, $summary, $total];
    }

    /**
     * Item-wise net totals: every product with its net quantity sold and net
     * amount across the period (sale items − return items, base-unit quantities).
     * Ranked by amount, or by quantity when `sort=quantity`, so the mobile
     * Amount/Qty toggle re-ranks server-side. Mirrors the web Product Performance.
     *
     * @return array{0: array<int, array<string, mixed>>, 1: array<string, mixed>, 2: int}
     */
    private function itemWise(?string $startDate, ?string $endDate, ?int $employeeId, int $page, int $perPage, ?string $sort): array
    {
        $saleItems = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->where('sales.status', 'completed')
            ->whereNull('sales.deleted_at')
            ->when($startDate, fn ($q, $value) => $q->whereDate('sales.date', '>=', $value))
            ->when($endDate, fn ($q, $value) => $q->whereDate('sales.date', '<=', $value))
            ->when($employeeId, fn ($q, $value) => $q->where('sale_items.employee_id', $value))
            ->groupBy('products.id', 'products.name', 'products.code')
            ->selectRaw('products.id, products.name as item_name, products.code as item_code')
            ->selectRaw('SUM(sale_items.total) as sale_total')
            ->selectRaw('0 as return_total')
            ->selectRaw('SUM(sale_items.base_unit_quantity) as sale_qty')
            ->selectRaw('0 as return_qty')
            ->selectRaw('COUNT(DISTINCT sale_items.sale_id) as bills_count');

        $returnItems = SaleReturnItem::query()
            ->join('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
            ->join('products', 'products.id', '=', 'sale_return_items.product_id')
            ->where('sale_returns.status', 'completed')
            ->whereNull('sale_returns.deleted_at')
            ->when($startDate, fn ($q, $value) => $q->whereDate('sale_returns.date', '>=', $value))
            ->when($endDate, fn ($q, $value) => $q->whereDate('sale_returns.date', '<=', $value))
            ->when($employeeId, fn ($q, $value) => $q->where('sale_return_items.employee_id', $value))
            ->groupBy('products.id', 'products.name', 'products.code')
            ->selectRaw('products.id, products.name as item_name, products.code as item_code')
            ->selectRaw('0 as sale_total')
            ->selectRaw('SUM(sale_return_items.total) as return_total')
            ->selectRaw('0 as sale_qty')
            ->selectRaw('SUM(sale_return_items.base_unit_quantity) as return_qty')
            ->selectRaw('0 as bills_count');

        $net = DB::query()
            ->fromSub($saleItems->unionAll($returnItems), 'u')
            ->groupBy('id', 'item_name', 'item_code')
            ->selectRaw('id, item_name, item_code')
            ->selectRaw('SUM(sale_total) - SUM(return_total) as total')
            ->selectRaw('SUM(sale_qty) - SUM(return_qty) as quantity')
            ->selectRaw('SUM(bills_count) as bills_count');

        $wrapped = DB::query()->fromSub($net, 'n');
        $total = (clone $wrapped)->count();
        $sums = (clone $wrapped)
            ->selectRaw('COALESCE(SUM(total), 0) as total_amount, COALESCE(SUM(quantity), 0) as total_quantity')
            ->first();

        $orderColumn = $sort === 'quantity' ? 'quantity' : 'total';

        $rows = (clone $wrapped)
            ->orderByDesc($orderColumn)
            ->forPage($page, $perPage)
            ->get()
            ->map(fn ($row) => [
                'product_id' => (string) $row->id,
                'item_name' => $row->item_name,
                'item_code' => $row->item_code,
                'bills_count' => (int) $row->bills_count,
                'quantity' => round((float) $row->quantity, 3),
                'total' => round((float) $row->total, 2),
            ])
            ->all();

        $summary = [
            'items' => $total,
            'total_quantity' => round((float) $sums->total_quantity, 3),
            'total_amount' => round((float) $sums->total_amount, 2),
        ];

        return [$rows, $summary, $total];
    }
}

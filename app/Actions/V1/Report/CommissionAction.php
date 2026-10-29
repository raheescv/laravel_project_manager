<?php

namespace App\Actions\V1\Report;

use App\Traits\Report\EmployeeReportQueryBuilder;
use Illuminate\Support\Facades\DB;

class CommissionAction
{
    // Reuse the exact same commission-aware query building the web Employee
    // Report uses, so the mobile figures reconcile with the web "Sales Details"
    // table (employee × product, net of returns, × commission %).
    use EmployeeReportQueryBuilder;

    /**
     * Employee commission report: one row per employee × product with quantity,
     * sale / return / net amount, the configured commission % and the resulting
     * commission value. Server-paginated with full-set grand totals in `summary`.
     *
     * Ranked by commission unless [$sort] asks for net sales (`net`), quantity
     * or the product's name, in [$direction].
     *
     * @return array{0: array<int, array<string, mixed>>, 1: array<string, mixed>, 2: int}
     */
    public function execute(
        ?string $startDate,
        ?string $endDate,
        ?int $employeeId,
        ?int $productId,
        ?int $branchId,
        int $page,
        int $perPage,
        ?string $sort = null,
        string $direction = 'desc'
    ): array {
        $filters = [
            'branch_id' => $branchId,
            'employee_id' => $employeeId,
            'product_id' => $productId,
            'from_date' => $startDate,
            'to_date' => $endDate,
        ];

        $lines = $this->buildBaseQuery($filters)
            ->leftJoinSub($this->buildReturnSubqueryByEmployeeAndProduct($filters), 'returns', function ($join): void {
                $join->on('returns.employee_id', '=', 'sale_items.employee_id')
                    ->on('returns.product_id', '=', 'sale_items.product_id');
            })
            ->select('sale_items.employee_id', 'sale_items.product_id', 'users.name as employee', 'products.name as product')
            ->groupBy('sale_items.employee_id', 'sale_items.product_id', 'employee_commissions.commission_percentage')
            ->selectRaw('SUM(sale_items.base_unit_quantity) as sale_quantity')
            ->selectRaw('SUM(sale_items.base_unit_quantity) - COALESCE(MAX(returns.return_quantity), 0) as total_quantity')
            ->selectRaw('SUM(sale_items.total) as total_amount')
            ->selectRaw('COALESCE(MAX(returns.return_amount), 0) as return_amount')
            ->selectRaw('SUM(sale_items.total) - COALESCE(MAX(returns.return_amount), 0) as net_amount')
            ->selectRaw('COALESCE(MAX(employee_commissions.commission_percentage), 0) as commission_percentage')
            ->selectRaw('(SUM(sale_items.total) - COALESCE(MAX(returns.return_amount), 0)) * COALESCE(MAX(employee_commissions.commission_percentage), 0) / 100 as total_commission');

        // Full-set grand totals (all pages) in one pass over the grouped lines,
        // instead of re-running the line query once per figure — mirrors the
        // web Employee Report footer so the mobile summary reconciles.
        $lineTotals = DB::query()
            ->fromSub($lines, 'lines')
            ->selectRaw('COUNT(*) as line_count')
            ->selectRaw('COUNT(DISTINCT employee_id) as employee_count')
            ->selectRaw('COALESCE(SUM(sale_quantity), 0) as total_quantity')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_amount')
            ->selectRaw('COALESCE(SUM(total_commission), 0) as total_commission')
            ->first();

        $rows = (clone $lines)
            ->orderBy($this->orderColumn($sort), $direction)
            ->orderByDesc('net_amount')
            ->orderBy('product')
            ->forPage($page, $perPage)
            ->get()
            ->map(fn ($row) => [
                'employee_id' => (string) $row->employee_id,
                'product_id' => (string) $row->product_id,
                'employee_name' => $row->employee,
                'product_name' => $row->product,
                'quantity' => round((float) $row->total_quantity, 3),
                'sale_amount' => round((float) $row->total_amount, 2),
                'return_amount' => round((float) $row->return_amount, 2),
                'net_amount' => round((float) $row->net_amount, 2),
                'commission_percentage' => round((float) $row->commission_percentage, 2),
                'commission' => round((float) $row->total_commission, 2),
            ])
            ->all();

        $returnTotals = $this->buildReturnTotalsQuery($filters)->first();

        $total = (int) $lineTotals->line_count;
        $totalSale = (float) $lineTotals->total_amount;
        $totalReturn = (float) ($returnTotals->return_amount ?? 0);

        $summary = [
            'rows' => $total,
            'employees' => (int) $lineTotals->employee_count,
            'total_quantity' => round((float) $lineTotals->total_quantity - (float) ($returnTotals->return_quantity ?? 0), 3),
            'total_sale_amount' => round($totalSale, 2),
            'total_return_amount' => round($totalReturn, 2),
            'total_net_amount' => round($totalSale - $totalReturn, 2),
            'total_commission' => round((float) $lineTotals->total_commission, 2),
        ];

        return [$rows, $summary, $total];
    }

    /**
     * The column a line is ranked by for the `sort` the client asked for. The
     * staff breakdowns' `bills` has no meaning on a commission line, so it
     * falls back to the commission like any other unknown sort.
     */
    private function orderColumn(?string $sort): string
    {
        return match ($sort) {
            'net' => 'net_amount',
            'quantity' => 'total_quantity',
            'name' => 'product',
            default => 'total_commission',
        };
    }
}

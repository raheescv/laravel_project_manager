<?php

namespace App\Actions\V1\Report;

use App\Traits\Report\EmployeeReportQueryBuilder;

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
     * @return array{0: array<int, array<string, mixed>>, 1: array<string, mixed>, 2: int}
     */
    public function execute(
        ?string $startDate,
        ?string $endDate,
        ?int $employeeId,
        ?int $productId,
        ?int $branchId,
        int $page,
        int $perPage
    ): array {
        $filters = [
            'branch_id' => $branchId,
            'employee_id' => $employeeId,
            'product_id' => $productId,
            'from_date' => $startDate,
            'to_date' => $endDate,
        ];

        $paginator = $this->buildBaseQuery($filters)
            ->leftJoinSub($this->buildReturnSubqueryByEmployeeAndProduct($filters), 'returns', function ($join): void {
                $join->on('returns.employee_id', '=', 'sale_items.employee_id')
                    ->on('returns.product_id', '=', 'sale_items.product_id');
            })
            ->select('users.name as employee', 'products.name as product')
            ->groupBy('sale_items.employee_id', 'sale_items.product_id', 'employee_commissions.commission_percentage')
            ->selectRaw('SUM(sale_items.base_unit_quantity) - COALESCE(MAX(returns.return_quantity), 0) as total_quantity')
            ->selectRaw('SUM(sale_items.total) as total_amount')
            ->selectRaw('COALESCE(MAX(returns.return_amount), 0) as return_amount')
            ->selectRaw('SUM(sale_items.total) - COALESCE(MAX(returns.return_amount), 0) as net_amount')
            ->selectRaw('COALESCE(MAX(employee_commissions.commission_percentage), 0) as commission_percentage')
            ->selectRaw('(SUM(sale_items.total) - COALESCE(MAX(returns.return_amount), 0)) * COALESCE(MAX(employee_commissions.commission_percentage), 0) / 100 as total_commission')
            ->orderByDesc('total_amount')
            ->paginate($perPage, ['*'], 'page', $page);

        $rows = collect($paginator->items())
            ->map(fn ($row) => [
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

        // Full-set grand totals (all pages), net of returns — mirrors the web
        // Employee Report footer so the mobile summary reconciles.
        $saleTotals = $this->buildBaseQuery($filters)
            ->selectRaw('SUM(sale_items.base_unit_quantity) as total_quantity')
            ->selectRaw('SUM(sale_items.total) as total_amount')
            ->first();

        $returnTotals = $this->buildReturnTotalsQuery($filters)->first();
        $totalCommission = $this->calculateEmployeeCommissions($filters)->sum();

        $totalSale = (float) ($saleTotals->total_amount ?? 0);
        $totalReturn = (float) ($returnTotals->return_amount ?? 0);

        $summary = [
            'rows' => $paginator->total(),
            'total_quantity' => round((float) ($saleTotals->total_quantity ?? 0) - (float) ($returnTotals->return_quantity ?? 0), 3),
            'total_sale_amount' => round($totalSale, 2),
            'total_return_amount' => round($totalReturn, 2),
            'total_net_amount' => round($totalSale - $totalReturn, 2),
            'total_commission' => round((float) $totalCommission, 2),
        ];

        return [$rows, $summary, $paginator->total()];
    }
}

<?php

namespace App\Traits\Report;

use App\Models\SaleItem;
use App\Models\SaleReturnItem;
use Illuminate\Support\Facades\DB;

trait EmployeeReportQueryBuilder
{
    /**
     * Build return subquery for employee and product grouping
     */
    protected function buildReturnSubqueryByEmployeeAndProduct(array $filters)
    {
        return SaleReturnItem::query()
            ->join('sale_returns', 'sale_return_items.sale_return_id', '=', 'sale_returns.id')
            ->where('sale_returns.status', 'completed')
            ->when($filters['branch_id'] ?? null, fn ($q) => $q->where('sale_returns.branch_id', $filters['branch_id']))
            ->when($filters['product_id'] ?? null, fn ($q) => $q->where('sale_return_items.product_id', $filters['product_id']))
            ->when($filters['employee_id'] ?? null, fn ($q) => $q->where('sale_return_items.employee_id', $filters['employee_id']))
            ->when($filters['from_date'] ?? null, fn ($q) => $q->whereDate('sale_returns.date', '>=', $filters['from_date']))
            ->when($filters['to_date'] ?? null, fn ($q) => $q->whereDate('sale_returns.date', '<=', $filters['to_date']))
            ->whereNotNull('sale_return_items.employee_id')
            ->where('sale_return_items.employee_id', '!=', 0)
            ->groupBy('sale_return_items.employee_id', 'sale_return_items.product_id')
            ->select('sale_return_items.employee_id', 'sale_return_items.product_id')
            ->selectRaw('SUM(sale_return_items.total) as return_amount')
            ->selectRaw('SUM(sale_return_items.base_unit_quantity) as return_quantity');
    }

    /**
     * Build return subquery for employee grouping only
     */
    protected function buildReturnSubqueryByEmployee(array $filters)
    {
        return SaleReturnItem::query()
            ->join('sale_returns', 'sale_return_items.sale_return_id', '=', 'sale_returns.id')
            ->where('sale_returns.status', 'completed')
            ->when($filters['branch_id'] ?? null, fn ($q) => $q->where('sale_returns.branch_id', $filters['branch_id']))
            ->when($filters['employee_id'] ?? null, fn ($q) => $q->where('sale_return_items.employee_id', $filters['employee_id']))
            ->when($filters['from_date'] ?? null, fn ($q) => $q->whereDate('sale_returns.date', '>=', $filters['from_date']))
            ->when($filters['to_date'] ?? null, fn ($q) => $q->whereDate('sale_returns.date', '<=', $filters['to_date']))
            ->whereNotNull('sale_return_items.employee_id')
            ->where('sale_return_items.employee_id', '!=', 0)
            ->groupBy('sale_return_items.employee_id')
            ->select('sale_return_items.employee_id')
            ->selectRaw('SUM(sale_return_items.total) as return_amount')
            ->selectRaw('SUM(sale_return_items.base_unit_quantity) as return_quantity');
    }

    /**
     * Build return totals query (no grouping)
     */
    protected function buildReturnTotalsQuery(array $filters)
    {
        return SaleReturnItem::query()
            ->join('sale_returns', 'sale_return_items.sale_return_id', '=', 'sale_returns.id')
            ->where('sale_returns.status', 'completed')
            ->when($filters['branch_id'] ?? null, fn ($q) => $q->where('sale_returns.branch_id', $filters['branch_id']))
            ->when($filters['product_id'] ?? null, fn ($q) => $q->where('sale_return_items.product_id', $filters['product_id']))
            ->when($filters['employee_id'] ?? null, fn ($q) => $q->where('sale_return_items.employee_id', $filters['employee_id']))
            ->when($filters['from_date'] ?? null, fn ($q) => $q->whereDate('sale_returns.date', '>=', $filters['from_date']))
            ->when($filters['to_date'] ?? null, fn ($q) => $q->whereDate('sale_returns.date', '<=', $filters['to_date']))
            ->whereNotNull('sale_return_items.employee_id')
            ->where('sale_return_items.employee_id', '!=', 0)
            ->selectRaw('SUM(sale_return_items.total) as return_amount')
            ->selectRaw('SUM(sale_return_items.base_unit_quantity) as return_quantity');
    }

    /**
     * Build the base query with all necessary joins and filters
     */
    protected function buildBaseQuery(array $filters)
    {
        return SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('users', 'sale_items.employee_id', '=', 'users.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->leftJoin('employee_commissions', function ($join) {
                $join->on('employee_commissions.employee_id', '=', 'sale_items.employee_id')
                    ->on('employee_commissions.product_id', '=', 'sale_items.product_id');
            })
            ->where('sales.status', 'completed')
            ->when($filters['branch_id'] ?? null, fn ($q) => $q->where('sales.branch_id', $filters['branch_id']))
            ->when($filters['product_id'] ?? null, fn ($q) => $q->where('sale_items.product_id', $filters['product_id']))
            ->when($filters['employee_id'] ?? null, fn ($q) => $q->where('sale_items.employee_id', $filters['employee_id']))
            ->when($filters['from_date'] ?? null, fn ($q) => $q->whereDate('sales.date', '>=', $filters['from_date']))
            ->when($filters['to_date'] ?? null, fn ($q) => $q->whereDate('sales.date', '<=', $filters['to_date']));
    }

    /**
     * Calculate commission totals per employee from detailed product data
     */
    protected function calculateEmployeeCommissions(array $filters)
    {
        $returnSubquery = $this->buildReturnSubqueryByEmployeeAndProduct($filters);

        return $this->buildBaseQuery($filters)
            ->leftJoinSub($returnSubquery, 'returns', function ($join) {
                $join->on('returns.employee_id', '=', 'sale_items.employee_id')
                    ->on('returns.product_id', '=', 'sale_items.product_id');
            })
            ->groupBy('sale_items.employee_id', 'sale_items.product_id', 'employee_commissions.commission_percentage')
            ->select(
                'sale_items.employee_id',
                DB::raw('SUM(sale_items.total) - COALESCE(MAX(returns.return_amount), 0) as net_amount'),
                DB::raw('COALESCE(MAX(employee_commissions.commission_percentage), 0) as commission_percentage')
            )
            ->get()
            ->groupBy('employee_id')
            ->map(function ($items) {
                return $items->sum(function ($item) {
                    return ($item->net_amount * $item->commission_percentage) / 100;
                });
            });
    }

    /**
     * Get filters array from component properties
     */
    protected function getFilters(): array
    {
        return [
            'branch_id' => $this->branch_id ?? null,
            'employee_id' => $this->employee_id ?? null,
            'product_id' => $this->product_id ?? null,
            'from_date' => $this->from_date ?? null,
            'to_date' => $this->to_date ?? null,
        ];
    }
}

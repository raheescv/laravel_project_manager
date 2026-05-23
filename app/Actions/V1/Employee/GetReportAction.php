<?php

namespace App\Actions\V1\Employee;

use App\Http\Requests\V1\Employee\ReportRequest;
use App\Models\SaleItem;
use App\Models\User;

class GetReportAction
{
    /**
     * Build a sales performance report for an employee over an optional date range.
     */
    public function execute(ReportRequest $request): array
    {
        $employeeId = (int) $request->validated('employeeId');
        $startDate = $request->validated('startDate');
        $endDate = $request->validated('endDate');

        $employee = User::query()->employee()->findOrFail($employeeId);

        $stats = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sale_items.employee_id', $employeeId)
            ->where('sales.status', 'completed')
            ->when($startDate, fn ($q, $value) => $q->whereDate('sales.date', '>=', $value))
            ->when($endDate, fn ($q, $value) => $q->whereDate('sales.date', '<=', $value))
            ->selectRaw('COUNT(DISTINCT sale_items.sale_id) as bills_count')
            ->selectRaw('COUNT(sale_items.id) as items_count')
            ->selectRaw('COALESCE(SUM(sale_items.quantity), 0) as total_quantity')
            ->selectRaw('COALESCE(SUM(sale_items.unit_price * sale_items.quantity), 0) as gross_revenue')
            ->selectRaw('COALESCE(SUM(sale_items.discount), 0) as total_discount')
            ->first();

        return [
            'employee' => [
                'id' => (string) $employee->id,
                'name' => $employee->name,
                'code' => $employee->code,
            ],
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'summary' => [
                'bills_count' => (int) ($stats->bills_count ?? 0),
                'items_count' => (int) ($stats->items_count ?? 0),
                'total_quantity' => (float) ($stats->total_quantity ?? 0),
                'gross_revenue' => round((float) ($stats->gross_revenue ?? 0), 2),
                'total_discount' => round((float) ($stats->total_discount ?? 0), 2),
                'net_revenue' => round((float) ($stats->gross_revenue ?? 0) - (float) ($stats->total_discount ?? 0), 2),
            ],
        ];
    }
}

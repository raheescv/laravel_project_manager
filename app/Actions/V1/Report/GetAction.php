<?php

namespace App\Actions\V1\Report;

use App\Http\Requests\V1\Report\GetRequest;
use App\Models\Sale;
use App\Models\SaleItem;

class GetAction
{
    /**
     * Fetch a system-wide analytical report by the requested breakdown type.
     */
    public function execute(GetRequest $request): array
    {
        $type = $request->validated('type');
        $startDate = $request->validated('startDate');
        $endDate = $request->validated('endDate');

        $rows = $type === 'employeewise'
            ? $this->employeeWise($startDate, $endDate)
            : $this->billWise($startDate, $endDate);

        return [
            'type' => $type,
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'rows' => $rows,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function billWise(?string $startDate, ?string $endDate): array
    {
        return Sale::query()
            ->completed()
            ->with('account:id,name')
            ->when($startDate, fn ($q, $value) => $q->whereDate('date', '>=', $value))
            ->when($endDate, fn ($q, $value) => $q->whereDate('date', '<=', $value))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(200)
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
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function employeeWise(?string $startDate, ?string $endDate): array
    {
        return SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('users', 'users.id', '=', 'sale_items.employee_id')
            ->where('sales.status', 'completed')
            ->when($startDate, fn ($q, $value) => $q->whereDate('sales.date', '>=', $value))
            ->when($endDate, fn ($q, $value) => $q->whereDate('sales.date', '<=', $value))
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('revenue')
            ->selectRaw('users.id, users.name')
            ->selectRaw('COUNT(DISTINCT sale_items.sale_id) as bills_count')
            ->selectRaw('COUNT(sale_items.id) as items_count')
            ->selectRaw('COALESCE(SUM(sale_items.unit_price * sale_items.quantity), 0) as revenue')
            ->get()
            ->map(fn ($row) => [
                'employee_id' => (string) $row->id,
                'employee_name' => $row->name,
                'bills_count' => (int) $row->bills_count,
                'items_count' => (int) $row->items_count,
                'revenue' => round((float) $row->revenue, 2),
            ])
            ->values()
            ->all();
    }
}

<?php

namespace App\Actions\V1\Sale;

use App\Http\Requests\V1\Sale\ItemIndexRequest;

class EmployeeSummaryAction
{
    public function __construct(public ItemListAction $lines) {}

    /**
     * The Employees tab of the mobile Sales list: the matched sale lines grouped
     * by the employee on the line, with how many invoices they appear on and
     * what their lines total. Runs over exactly the lines the Items tab lists.
     *
     * An invoice served by two employees counts once for each of them, so the
     * summary's `invoices` is the distinct count, not the sum of the rows.
     *
     * @return array{data: list<array{employee_id: string|null, employee: string, invoices: int, quantity: float, total: float}>, summary: array{employees: int, invoices: int, total: float}}
     */
    public function execute(ItemIndexRequest $request): array
    {
        $query = $this->lines->filteredLines($request->validatedWithDefaults(), $request->user());

        $rows = (clone $query)
            ->leftJoin('users as line_employee', 'line_employee.id', '=', 'sale_items.employee_id')
            ->groupBy('sale_items.employee_id', 'line_employee.name')
            ->selectRaw('sale_items.employee_id, line_employee.name as employee_name, COUNT(DISTINCT sale_items.sale_id) as invoices, COALESCE(SUM(sale_items.quantity), 0) as quantity, COALESCE(SUM(sale_items.total), 0) as total')
            ->orderByDesc('total')
            ->orderBy('employee_name')
            ->toBase()
            ->get();

        $summary = (clone $query)
            ->selectRaw('COUNT(DISTINCT sale_items.sale_id) as invoices, COALESCE(SUM(sale_items.total), 0) as total')
            ->toBase()
            ->first();

        return [
            'data' => $rows->map(fn (object $row): array => [
                'employee_id' => $row->employee_id === null ? null : (string) $row->employee_id,
                'employee' => $row->employee_name ?: 'Unassigned',
                'invoices' => (int) $row->invoices,
                'quantity' => round((float) $row->quantity, 3),
                'total' => round((float) $row->total, 2),
            ])->values()->all(),
            'summary' => [
                'employees' => $rows->count(),
                'invoices' => (int) ($summary->invoices ?? 0),
                'total' => round((float) ($summary->total ?? 0), 2),
            ],
        ];
    }
}

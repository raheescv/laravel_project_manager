<?php

namespace App\Exports;

use App\Models\SaleItem;
use App\Models\SaleReturnItem;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class EmployeeReportExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(public array $filters = []) {}

    public function sheets(): array
    {
        return [
            new EmployeeDetailSheet($this->filters),
            new EmployeeSummarySheet($this->filters),
        ];
    }
}

class EmployeeDetailSheet implements FromCollection, WithColumnFormatting, WithEvents, WithHeadings, WithMapping, WithTitle
{
    use Exportable;

    public function __construct(public array $filters = []) {}

    public function collection()
    {
        // Build return subquery
        $returnSubquery = SaleReturnItem::query()
            ->join('sale_returns', 'sale_return_items.sale_return_id', '=', 'sale_returns.id')
            ->where('sale_returns.status', 'completed')
            ->when($this->filters['branch_id'] ?? null, fn ($q) => $q->where('sale_returns.branch_id', $this->filters['branch_id']))
            ->when($this->filters['product_id'] ?? null, fn ($q) => $q->where('sale_return_items.product_id', $this->filters['product_id']))
            ->when($this->filters['employee_id'] ?? null, fn ($q) => $q->where('sale_return_items.employee_id', $this->filters['employee_id']))
            ->when($this->filters['from_date'] ?? null, fn ($q) => $q->whereDate('sale_returns.date', '>=', $this->filters['from_date']))
            ->when($this->filters['to_date'] ?? null, fn ($q) => $q->whereDate('sale_returns.date', '<=', $this->filters['to_date']))
            ->whereNotNull('sale_return_items.employee_id')
            ->where('sale_return_items.employee_id', '!=', 0)
            ->groupBy('sale_return_items.employee_id', 'sale_return_items.product_id')
            ->select('sale_return_items.employee_id', 'sale_return_items.product_id')
            ->selectRaw('SUM(sale_return_items.total) as return_amount');

        // Build main query
        $query = SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('users', 'sale_items.employee_id', '=', 'users.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->leftJoin('employee_commissions', function ($join) {
                $join->on('employee_commissions.employee_id', '=', 'sale_items.employee_id')
                    ->on('employee_commissions.product_id', '=', 'sale_items.product_id');
            })
            ->leftJoinSub($returnSubquery, 'returns', function ($join) {
                $join->on('returns.employee_id', '=', 'sale_items.employee_id')
                    ->on('returns.product_id', '=', 'sale_items.product_id');
            })
            ->where('sales.status', 'completed')
            ->when($this->filters['branch_id'] ?? null, fn ($q) => $q->where('sales.branch_id', $this->filters['branch_id']))
            ->when($this->filters['product_id'] ?? null, fn ($q) => $q->where('sale_items.product_id', $this->filters['product_id']))
            ->when($this->filters['employee_id'] ?? null, fn ($q) => $q->where('sale_items.employee_id', $this->filters['employee_id']))
            ->when($this->filters['from_date'] ?? null, fn ($q) => $q->whereDate('sales.date', '>=', $this->filters['from_date']))
            ->when($this->filters['to_date'] ?? null, fn ($q) => $q->whereDate('sales.date', '<=', $this->filters['to_date']))
            ->groupBy('sale_items.employee_id', 'sale_items.product_id', 'employee_commissions.commission_percentage')
            ->select(
                'users.name as employee',
                'products.name as product',
                DB::raw('SUM(sale_items.quantity) as total_quantity'),
                DB::raw('SUM(sale_items.total) as total_amount'),
                DB::raw('COALESCE(MAX(returns.return_amount), 0) as return_amount'),
                DB::raw('SUM(sale_items.total) - COALESCE(MAX(returns.return_amount), 0) as net_amount'),
                DB::raw('COALESCE(MAX(employee_commissions.commission_percentage), 0) as commission_percentage'),
                DB::raw('(SUM(sale_items.total) - COALESCE(MAX(returns.return_amount), 0)) * COALESCE(MAX(employee_commissions.commission_percentage), 0) / 100 as total_commission')
            )
            ->orderBy('total_amount', 'desc')
            ->get();

        return $query;
    }

    public function headings(): array
    {
        return [
            'Employee',
            'Product',
            'Quantity',
            'Sale Amount',
            'Return Amount',
            'Net Amount',
            'Commission %',
            'Commission',
        ];
    }

    public function map($row): array
    {
       $data= [
            $row->employee,
            $row->product,
            $row->total_quantity,
            $row->total_amount,
            $row->return_amount ?? 0,
            $row->net_amount ?? ($row->total_amount - ($row->return_amount ?? 0)),
            round($row->commission_percentage ?? 0),
            $row->total_commission ?? 0,
        ];
        return $data;
    }

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_NUMBER_00,
            'D' => NumberFormat::FORMAT_NUMBER_00,
            'E' => NumberFormat::FORMAT_NUMBER_00,
            'F' => NumberFormat::FORMAT_NUMBER_00,
            'G' => NumberFormat::FORMAT_NUMBER_00,
            'H' => NumberFormat::FORMAT_NUMBER_00,
        ];
    }

    public function title(): string
    {
        return 'Employee Product Details';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $totalRows = $sheet->getHighestRow() + 1;

                // Add totals row
                $sheet->setCellValue("A{$totalRows}", "Total");
                $sheet->setCellValue("C{$totalRows}", "=SUM(C2:C" . ($totalRows - 1) . ")");
                $sheet->setCellValue("D{$totalRows}", "=SUM(D2:D" . ($totalRows - 1) . ")");
                $sheet->setCellValue("E{$totalRows}", "=SUM(E2:E" . ($totalRows - 1) . ")");
                $sheet->setCellValue("F{$totalRows}", "=SUM(F2:F" . ($totalRows - 1) . ")");
                $sheet->setCellValue("H{$totalRows}", "=SUM(H2:H" . ($totalRows - 1) . ")");

                // Style totals row
                $sheet->getStyle("A{$totalRows}:H{$totalRows}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E8E8E8'],
                    ],
                ]);
            },
        ];
    }
}

class EmployeeSummarySheet implements FromCollection, WithColumnFormatting, WithEvents, WithHeadings, WithMapping, WithTitle
{
    use Exportable;

    public function __construct(public array $filters = []) {}

    public function title(): string
    {
        return 'Employee Summary';
    }

    public function collection()
    {
        // Build return subquery for employee and product grouping (needed for commission calculation)
        $returnSubqueryByProduct = SaleReturnItem::query()
            ->join('sale_returns', 'sale_return_items.sale_return_id', '=', 'sale_returns.id')
            ->where('sale_returns.status', 'completed')
            ->when($this->filters['branch_id'] ?? null, fn ($q) => $q->where('sale_returns.branch_id', $this->filters['branch_id']))
            ->when($this->filters['product_id'] ?? null, fn ($q) => $q->where('sale_return_items.product_id', $this->filters['product_id']))
            ->when($this->filters['employee_id'] ?? null, fn ($q) => $q->where('sale_return_items.employee_id', $this->filters['employee_id']))
            ->when($this->filters['from_date'] ?? null, fn ($q) => $q->whereDate('sale_returns.date', '>=', $this->filters['from_date']))
            ->when($this->filters['to_date'] ?? null, fn ($q) => $q->whereDate('sale_returns.date', '<=', $this->filters['to_date']))
            ->whereNotNull('sale_return_items.employee_id')
            ->where('sale_return_items.employee_id', '!=', 0)
            ->groupBy('sale_return_items.employee_id', 'sale_return_items.product_id')
            ->select('sale_return_items.employee_id', 'sale_return_items.product_id')
            ->selectRaw('SUM(sale_return_items.total) as return_amount');

        // Build return subquery for employee grouping
        $returnSubquery = SaleReturnItem::query()
            ->join('sale_returns', 'sale_return_items.sale_return_id', '=', 'sale_returns.id')
            ->where('sale_returns.status', 'completed')
            ->when($this->filters['branch_id'] ?? null, fn ($q) => $q->where('sale_returns.branch_id', $this->filters['branch_id']))
            ->when($this->filters['employee_id'] ?? null, fn ($q) => $q->where('sale_return_items.employee_id', $this->filters['employee_id']))
            ->when($this->filters['from_date'] ?? null, fn ($q) => $q->whereDate('sale_returns.date', '>=', $this->filters['from_date']))
            ->when($this->filters['to_date'] ?? null, fn ($q) => $q->whereDate('sale_returns.date', '<=', $this->filters['to_date']))
            ->whereNotNull('sale_return_items.employee_id')
            ->where('sale_return_items.employee_id', '!=', 0)
            ->groupBy('sale_return_items.employee_id')
            ->select('sale_return_items.employee_id')
            ->selectRaw('SUM(sale_return_items.total) as return_amount');

        // Get detailed data to calculate commission per employee
        $detailedData = SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('users', 'sale_items.employee_id', '=', 'users.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->leftJoin('employee_commissions', function ($join) {
                $join->on('employee_commissions.employee_id', '=', 'sale_items.employee_id')
                    ->on('employee_commissions.product_id', '=', 'sale_items.product_id');
            })
            ->leftJoinSub($returnSubqueryByProduct, 'returns', function ($join) {
                $join->on('returns.employee_id', '=', 'sale_items.employee_id')
                    ->on('returns.product_id', '=', 'sale_items.product_id');
            })
            ->where('sales.status', 'completed')
            ->when($this->filters['branch_id'] ?? null, fn ($q) => $q->where('sales.branch_id', $this->filters['branch_id']))
            ->when($this->filters['product_id'] ?? null, fn ($q) => $q->where('sale_items.product_id', $this->filters['product_id']))
            ->when($this->filters['employee_id'] ?? null, fn ($q) => $q->where('sale_items.employee_id', $this->filters['employee_id']))
            ->when($this->filters['from_date'] ?? null, fn ($q) => $q->whereDate('sales.date', '>=', $this->filters['from_date']))
            ->when($this->filters['to_date'] ?? null, fn ($q) => $q->whereDate('sales.date', '<=', $this->filters['to_date']))
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

        // Build main query grouped by employee only
        $query = SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('users', 'sale_items.employee_id', '=', 'users.id')
            ->leftJoinSub($returnSubquery, 'returns', function ($join) {
                $join->on('returns.employee_id', '=', 'sale_items.employee_id');
            })
            ->where('sales.status', 'completed')
            ->when($this->filters['branch_id'] ?? null, fn ($q) => $q->where('sales.branch_id', $this->filters['branch_id']))
            ->when($this->filters['employee_id'] ?? null, fn ($q) => $q->where('sale_items.employee_id', $this->filters['employee_id']))
            ->when($this->filters['from_date'] ?? null, fn ($q) => $q->whereDate('sales.date', '>=', $this->filters['from_date']))
            ->when($this->filters['to_date'] ?? null, fn ($q) => $q->whereDate('sales.date', '<=', $this->filters['to_date']))
            ->groupBy('sale_items.employee_id')
            ->select(
                'sale_items.employee_id',
                'users.name as employee',
                DB::raw('SUM(sale_items.quantity) as total_quantity'),
                DB::raw('SUM(sale_items.total) as total_amount'),
                DB::raw('COALESCE(MAX(returns.return_amount), 0) as return_amount'),
                DB::raw('SUM(sale_items.total) - COALESCE(MAX(returns.return_amount), 0) as net_amount')
            )
            ->orderBy('total_amount', 'desc')
            ->get()
            ->map(function ($item) use ($detailedData) {
                $item->total_commission = $detailedData->get($item->employee_id) ?? 0;
                return $item;
            });

        return $query;
    }

    public function headings(): array
    {
        return [
            'Employee',
            'Quantity',
            'Sale Amount',
            'Return Amount',
            'Net Amount',
            'Commission',
        ];
    }

    public function map($row): array
    {
        return [
            $row->employee,
            $row->total_quantity,
            $row->total_amount,
            $row->return_amount ?? 0,
            $row->net_amount ?? ($row->total_amount - ($row->return_amount ?? 0)),
            $row->total_commission ?? 0,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_NUMBER_00,
            'C' => NumberFormat::FORMAT_NUMBER_00,
            'D' => NumberFormat::FORMAT_NUMBER_00,
            'E' => NumberFormat::FORMAT_NUMBER_00,
            'F' => NumberFormat::FORMAT_NUMBER_00,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $totalRows = $sheet->getHighestRow() + 1;

                // Add totals row
                $sheet->setCellValue("A{$totalRows}", "Total");
                $sheet->setCellValue("B{$totalRows}", "=SUM(B2:B" . ($totalRows - 1) . ")");
                $sheet->setCellValue("C{$totalRows}", "=SUM(C2:C" . ($totalRows - 1) . ")");
                $sheet->setCellValue("D{$totalRows}", "=SUM(D2:D" . ($totalRows - 1) . ")");
                $sheet->setCellValue("E{$totalRows}", "=SUM(E2:E" . ($totalRows - 1) . ")");
                $sheet->setCellValue("F{$totalRows}", "=SUM(F2:F" . ($totalRows - 1) . ")");

                // Style totals row
                $sheet->getStyle("A{$totalRows}:F{$totalRows}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E8E8E8'],
                    ],
                ]);
            },
        ];
    }
}

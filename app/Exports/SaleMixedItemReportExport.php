<?php

namespace App\Exports;

use App\Models\SaleItem;
use App\Models\SaleReturnItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class SaleMixedItemReportExport implements FromQuery, WithColumnFormatting, WithEvents, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(public array $filters = [], public array $visibleColumns = []) {}

    public function query()
    {
        $accessibleBranchIds = Auth::user()->branches->pluck('branch_id')->toArray();

        // Sales side
        $saleQuery = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->when($this->filters['from_date'] ?? '', fn ($q) => $q->whereDate('sales.date', '>=', $this->filters['from_date']))
            ->when($this->filters['to_date'] ?? '', fn ($q) => $q->whereDate('sales.date', '<=', $this->filters['to_date']))
            ->when($this->filters['branch_id'] ?? '', fn ($q) => $q->where('sales.branch_id', $this->filters['branch_id']))
            ->when($this->filters['product_id'] ?? '', fn ($q) => $q->where('sale_items.product_id', $this->filters['product_id']))
            ->whereIn('sales.branch_id', $accessibleBranchIds)
            ->where('sales.status', 'completed')
            ->select([
                DB::raw("'sale' as type"),
                'sale_items.id as id',
                'sale_items.sale_id as parent_id',
                'sales.date as date',
                'sales.created_at as created_at',
                'sales.invoice_no as reference',
                'products.name as product_name',
                'products.code as product_code',
                'sale_items.unit_price',
                'sale_items.quantity',
                'sale_items.gross_amount',
                'sale_items.discount',
                'sale_items.net_amount',
                'sale_items.tax_amount',
                'sale_items.total',
                'sales.branch_id as branch_id',
            ]);

        // Sale returns side
        $returnQuery = SaleReturnItem::query()
            ->join('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
            ->leftJoin('sale_items', 'sale_items.id', '=', 'sale_return_items.sale_item_id')
            ->join('products', 'products.id', '=', 'sale_return_items.product_id')
            ->when($this->filters['from_date'] ?? '', fn ($q) => $q->whereDate('sale_returns.date', '>=', $this->filters['from_date']))
            ->when($this->filters['to_date'] ?? '', fn ($q) => $q->whereDate('sale_returns.date', '<=', $this->filters['to_date']))
            ->when($this->filters['branch_id'] ?? '', fn ($q) => $q->where('sale_returns.branch_id', $this->filters['branch_id']))
            ->when($this->filters['product_id'] ?? '', fn ($q) => $q->where('sale_return_items.product_id', $this->filters['product_id']))
            ->whereIn('sale_returns.branch_id', $accessibleBranchIds)
            ->where('sale_returns.status', 'completed')
            ->select([
                DB::raw("'sale_return' as type"),
                'sale_return_items.id as id',
                'sale_return_items.sale_return_id as parent_id',
                'sale_returns.date as date',
                'sale_returns.created_at as created_at',
                DB::raw('COALESCE(sale_returns.reference_no, sale_returns.id) as reference'),
                'products.name as product_name',
                'products.code as product_code',
                'sale_return_items.unit_price',
                DB::raw('(-1) * sale_return_items.quantity as quantity'),
                DB::raw('(-1) * sale_return_items.gross_amount as gross_amount'),
                DB::raw('(-1) * sale_return_items.discount as discount'),
                DB::raw('(-1) * sale_return_items.net_amount as net_amount'),
                DB::raw('(-1) * sale_return_items.tax_amount as tax_amount'),
                DB::raw('(-1) * sale_return_items.total as total'),
                'sale_returns.branch_id as branch_id',
            ]);

        $union = $saleQuery->unionAll($returnQuery);

        // Wrap union as a subquery to allow ordering
        $selectedType = $this->filters['type'] ?? '';
        $outer = DB::query()->fromSub($union, 't')
            ->when($selectedType, fn ($q) => $q->where('type', $selectedType))
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc');

        return $outer;
    }

    public function headings(): array
    {
        $headings = [];

        if ($this->visibleColumns['type'] ?? true) {
            $headings[] = 'Type';
        }
        if ($this->visibleColumns['date'] ?? true) {
            $headings[] = 'Date';
        }
        if ($this->visibleColumns['created_at'] ?? true) {
            $headings[] = 'Created At';
        }
        if ($this->visibleColumns['reference'] ?? true) {
            $headings[] = 'Reference';
        }
        if ($this->visibleColumns['product_name'] ?? true) {
            $headings[] = 'Product';
        }
        if ($this->visibleColumns['product_code'] ?? true) {
            $headings[] = 'Code';
        }
        if ($this->visibleColumns['unit_price'] ?? true) {
            $headings[] = 'Unit Price';
        }
        if ($this->visibleColumns['quantity'] ?? true) {
            $headings[] = 'Quantity';
        }
        if ($this->visibleColumns['gross_amount'] ?? true) {
            $headings[] = 'Gross Amount';
        }
        if ($this->visibleColumns['discount'] ?? true) {
            $headings[] = 'Discount';
        }
        if ($this->visibleColumns['net_amount'] ?? true) {
            $headings[] = 'Net Amount';
        }
        if ($this->visibleColumns['tax_amount'] ?? true) {
            $headings[] = 'Tax Amount';
        }
        if ($this->visibleColumns['total'] ?? true) {
            $headings[] = 'Total';
        }

        return $headings;
    }

    public function chunkSize(): int
    {
        return 2000;
    }

    public function map($row): array
    {
        $data = [];

        if ($this->visibleColumns['type'] ?? true) {
            $data[] = $row->type === 'sale' ? 'Sale' : 'Return';
        }
        if ($this->visibleColumns['date'] ?? true) {
            $data[] = systemDate($row->date);
        }
        if ($this->visibleColumns['created_at'] ?? true) {
            $data[] = systemDateTime($row->created_at);
        }
        if ($this->visibleColumns['reference'] ?? true) {
            $data[] = $row->reference;
        }
        if ($this->visibleColumns['product_name'] ?? true) {
            $data[] = $row->product_name;
        }
        if ($this->visibleColumns['product_code'] ?? true) {
            $data[] = $row->product_code;
        }
        if ($this->visibleColumns['unit_price'] ?? true) {
            $data[] = $row->unit_price;
        }
        if ($this->visibleColumns['quantity'] ?? true) {
            $data[] = $row->quantity;
        }
        if ($this->visibleColumns['gross_amount'] ?? true) {
            $data[] = $row->gross_amount;
        }
        if ($this->visibleColumns['discount'] ?? true) {
            $data[] = $row->discount != 0 ? $row->discount : 0;
        }
        if ($this->visibleColumns['net_amount'] ?? true) {
            $data[] = $row->net_amount;
        }
        if ($this->visibleColumns['tax_amount'] ?? true) {
            $data[] = $row->tax_amount != 0 ? $row->tax_amount : 0;
        }
        if ($this->visibleColumns['total'] ?? true) {
            $data[] = $row->total;
        }

        return $data;
    }

    public function columnFormats(): array
    {
        $formats = [];
        $currentColumn = 'A';

        // Skip non-numeric columns
        if ($this->visibleColumns['type'] ?? true) {
            $currentColumn++;
        }
        if ($this->visibleColumns['date'] ?? true) {
            $currentColumn++;
        }
        if ($this->visibleColumns['created_at'] ?? true) {
            $currentColumn++;
        }
        if ($this->visibleColumns['reference'] ?? true) {
            $currentColumn++;
        }
        if ($this->visibleColumns['product_name'] ?? true) {
            $currentColumn++;
        }
        if ($this->visibleColumns['product_code'] ?? true) {
            $currentColumn++;
        }

        // Format numeric columns
        if ($this->visibleColumns['unit_price'] ?? true) {
            $formats[$currentColumn] = NumberFormat::FORMAT_NUMBER_00;
            $currentColumn++;
        }
        if ($this->visibleColumns['quantity'] ?? true) {
            $formats[$currentColumn] = NumberFormat::FORMAT_NUMBER_00;
            $currentColumn++;
        }
        if ($this->visibleColumns['gross_amount'] ?? true) {
            $formats[$currentColumn] = NumberFormat::FORMAT_NUMBER_00;
            $currentColumn++;
        }
        if ($this->visibleColumns['discount'] ?? true) {
            $formats[$currentColumn] = NumberFormat::FORMAT_NUMBER_00;
            $currentColumn++;
        }
        if ($this->visibleColumns['net_amount'] ?? true) {
            $formats[$currentColumn] = NumberFormat::FORMAT_NUMBER_00;
            $currentColumn++;
        }
        if ($this->visibleColumns['tax_amount'] ?? true) {
            $formats[$currentColumn] = NumberFormat::FORMAT_NUMBER_00;
            $currentColumn++;
        }
        if ($this->visibleColumns['total'] ?? true) {
            $formats[$currentColumn] = NumberFormat::FORMAT_NUMBER_00;
        }

        return $formats;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                $totalRows = $sheet->getHighestRow() + 1;
                $sheet->getStyle("A{$totalRows}:Z{$totalRows}")->applyFromArray([
                    'font' => [
                        'bold' => true,
                    ],
                ]);

                // Add totals row
                $endRow = $totalRows - 1;
                $currentColumn = 'A';

                // Skip non-numeric columns
                if ($this->visibleColumns['type'] ?? true) {
                    $currentColumn++;
                }
                if ($this->visibleColumns['date'] ?? true) {
                    $currentColumn++;
                }
                if ($this->visibleColumns['created_at'] ?? true) {
                    $currentColumn++;
                }
                if ($this->visibleColumns['reference'] ?? true) {
                    $currentColumn++;
                }
                if ($this->visibleColumns['product_name'] ?? true) {
                    $currentColumn++;
                }
                if ($this->visibleColumns['product_code'] ?? true) {
                    $currentColumn++;
                }

                // Add totals for numeric columns
                if ($this->visibleColumns['unit_price'] ?? true) {
                    $sheet->setCellValue("{$currentColumn}{$totalRows}", "=SUM({$currentColumn}2:{$currentColumn}{$endRow})");
                    $currentColumn++;
                }
                if ($this->visibleColumns['quantity'] ?? true) {
                    $sheet->setCellValue("{$currentColumn}{$totalRows}", "=SUM({$currentColumn}2:{$currentColumn}{$endRow})");
                    $currentColumn++;
                }
                if ($this->visibleColumns['gross_amount'] ?? true) {
                    $sheet->setCellValue("{$currentColumn}{$totalRows}", "=SUM({$currentColumn}2:{$currentColumn}{$endRow})");
                    $currentColumn++;
                }
                if ($this->visibleColumns['discount'] ?? true) {
                    $sheet->setCellValue("{$currentColumn}{$totalRows}", "=SUM({$currentColumn}2:{$currentColumn}{$endRow})");
                    $currentColumn++;
                }
                if ($this->visibleColumns['net_amount'] ?? true) {
                    $sheet->setCellValue("{$currentColumn}{$totalRows}", "=SUM({$currentColumn}2:{$currentColumn}{$endRow})");
                    $currentColumn++;
                }
                if ($this->visibleColumns['tax_amount'] ?? true) {
                    $sheet->setCellValue("{$currentColumn}{$totalRows}", "=SUM({$currentColumn}2:{$currentColumn}{$endRow})");
                    $currentColumn++;
                }
                if ($this->visibleColumns['total'] ?? true) {
                    $sheet->setCellValue("{$currentColumn}{$totalRows}", "=SUM({$currentColumn}2:{$currentColumn}{$endRow})");
                    $currentColumn++;
                }
            },
        ];
    }
}

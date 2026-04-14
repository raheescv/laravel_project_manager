<?php

namespace App\Exports;

use App\Models\StockCheck;
use App\Models\StockCheckItem;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockCheckItemExport implements FromQuery, WithHeadings, WithMapping, WithStyles
{
    use Exportable;

    public function __construct(public int $stockCheckId) {}

    public function query()
    {
        $stockCheck = StockCheck::findOrFail($this->stockCheckId);

        return StockCheckItem::where('stock_check_items.stock_check_id', $this->stockCheckId)
            ->join('products', 'stock_check_items.product_id', '=', 'products.id')
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->leftJoin('categories', 'products.main_category_id', '=', 'categories.id')
            ->leftJoin('inventories', function ($join) use ($stockCheck) {
                $join->on('inventories.product_id', '=', 'products.id')
                    ->where('inventories.branch_id', '=', $stockCheck->branch_id)
                    ->whereNull('inventories.employee_id');
            })
            ->select([
                'stock_check_items.id',
                'stock_check_items.product_id',
                'products.name as product_name',
                'products.code as product_code',
                'inventories.barcode',
                'categories.name as category_name',
                'brands.name as brand_name',
                'stock_check_items.recorded_quantity',
                'stock_check_items.physical_quantity',
                'stock_check_items.difference',
                'stock_check_items.status',
            ])
            ->orderBy('products.name', 'asc');
    }

    public function headings(): array
    {
        return [
            'Item ID',
            'Product ID',
            'Product Name',
            'Product Code',
            'Barcode',
            'Category',
            'Brand',
            'Recorded Qty',
            'Physical Qty',
            'Difference',
            'Status',
        ];
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->product_id,
            $row->product_name,
            $row->product_code,
            $row->barcode,
            $row->category_name,
            $row->brand_name,
            $row->recorded_quantity,
            $row->physical_quantity,
            $row->difference,
            $row->status,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();

        // Header styling
        $sheet->getStyle('A1:K1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0D6EFD'],
            ],
        ]);

        // Highlight Physical Qty column (I) with light yellow to indicate editable
        if ($lastRow > 1) {
            $sheet->getStyle("I2:I{$lastRow}")->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FFFDE7'],
                ],
            ]);
        }

        // Auto-size columns
        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return [];
    }

    public function chunkSize(): int
    {
        return 2000;
    }
}

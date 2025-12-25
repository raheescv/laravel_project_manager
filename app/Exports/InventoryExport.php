<?php

namespace App\Exports;

use App\Actions\Product\Inventory\GetAction;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InventoryExport implements FromQuery, WithHeadings, WithMapping, WithStyles
{
    use Exportable;

    public function __construct(public array $filters = []) {}

    public function query()
    {
        return (new GetAction())->execute($this->filters)
            ->select(
                'inventories.id',
                'inventories.cost',
                'inventories.quantity',
                'inventories.total',
                'inventories.barcode',
                'inventories.batch',
                'inventories.created_at',
                'product_id',
                'products.name',
                'products.code',
                'products.mrp',
                'brands.name as brand_name',
                'products.size',
                'products.name_arabic',
                'products.department_id',
                'departments.name as department_name',
                'products.main_category_id',
                'main_categories.name as main_category_name',
                'products.sub_category_id',
                'sub_categories.name as sub_category_name',
                'products.unit_id',
                'units.name as unit_name',
                'branch_id',
                'branches.name as branch_name',
            )
            ->orderBy('inventories.id', 'desc');
    }

    public function headings(): array
    {
        return [
            'ID',
            'Product Name',
            'Product Code',
            'Product Name (Arabic)',
            'Branch',
            'Department',
            'Main Category',
            'Sub Category',
            'Brand',
            'Unit',
            'Size',
            'Barcode',
            'Batch',
            'Quantity',
            'Cost',
            'Total Value',
            'MRP',
            'Created At',
        ];
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->name,
            $row->code,
            $row->name_arabic,
            $row->branch_name,
            $row->department_name,
            $row->main_category_name,
            $row->sub_category_name,
            $row->brand_name,
            $row->unit_name,
            $row->size,
            $row->barcode,
            $row->batch,
            $row->quantity,
            $row->cost,
            $row->total,
            $row->mrp,
            $row->created_at ? $row->created_at->format('Y-m-d H:i:s') : '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E3F2FD'],
                ],
            ],
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}

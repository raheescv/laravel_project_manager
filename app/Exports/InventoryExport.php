<?php

namespace App\Exports;

use App\Models\Inventory;
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
        return Inventory::query()
            ->join('branches', 'inventories.branch_id', '=', 'branches.id')
            ->join('products', 'inventories.product_id', '=', 'products.id')
            ->join('departments', 'products.department_id', '=', 'departments.id')
            ->join('brands', 'products.brand_id', '=', 'brands.id')
            ->join('units', 'products.unit_id', '=', 'units.id')
            ->join('categories as main_categories', 'products.main_category_id', '=', 'main_categories.id')
            ->leftJoin('categories as sub_categories', 'products.sub_category_id', '=', 'sub_categories.id')
            ->where('products.type', 'product')
            ->when($this->filters['branch_id'] ?? null, function ($query, $value) {
                return $query->whereIn('branch_id', $value);
            })
            ->when($this->filters['department_id'] ?? null, function ($query, $value) {
                return $query->where('department_id', $value);
            })
            ->when($this->filters['main_category_id'] ?? null, function ($query, $value) {
                return $query->where('main_category_id', $value);
            })
            ->when($this->filters['sub_category_id'] ?? null, function ($query, $value) {
                return $query->where('sub_category_id', $value);
            })
            ->when($this->filters['product_id'] ?? null, function ($query, $value) {
                return $query->where('product_id', $value);
            })
            ->when($this->filters['unit_id'] ?? null, function ($query, $value) {
                return $query->where('unit_id', $value);
            })
            ->when($this->filters['brand_id'] ?? null, function ($query, $value) {
                return $query->where('brand_id', $value);
            })
            ->when($this->filters['non_zero'] ?? false, function ($query, $value) {
                return $query->where('quantity', '!=', 0);
            })
            ->when($this->filters['size'] ?? null, function ($query, $value) {
                return $query->where('products.size', $value);
            })
            ->when($this->filters['barcode'] ?? null, function ($query, $value) {
                return $query->where('inventories.barcode', $value);
            })
            ->when($this->filters['code'] ?? null, function ($query, $value) {
                return $query->where('products.code', $value);
            })
            ->when($this->filters['search'] ?? null, function ($query, $value) {
                return $query->where(function ($q) use ($value): void {
                    $value = trim($value);
                    $q->where('products.name', 'like', "%{$value}%")
                        ->orWhere('products.name_arabic', 'like', "%{$value}%")
                        ->orWhere('products.code', 'like', "%{$value}%")
                        ->orWhere('branches.name', 'like', "%{$value}%")
                        ->orWhere('departments.name', 'like', "%{$value}%")
                        ->orWhere('units.name', 'like', "%{$value}%")
                        ->orWhere('brands.name', 'like', "%{$value}%")
                        ->orWhere('main_categories.name', 'like', "%{$value}%")
                        ->orWhere('sub_categories.name', 'like', "%{$value}%")
                        ->orWhere('inventories.barcode', 'like', "%{$value}%")
                        ->orWhere('inventories.batch', 'like', "%{$value}%")
                        ->orWhere('products.size', $value)
                        ->orWhere('inventories.quantity', 'like', "%{$value}%")
                        ->orWhere('inventories.cost', 'like', "%{$value}%");
                });
            })
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
            number_format($row->cost, 2),
            number_format($row->total, 2),
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
                    'startColor' => ['rgb' => 'E3F2FD']
                ]
            ],
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }
} 
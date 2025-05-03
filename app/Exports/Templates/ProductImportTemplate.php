<?php

namespace App\Exports\Templates;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductImportTemplate implements FromArray, WithHeadings
{
    public function array(): array
    {
        return [
            [
                'name' => 'Sample Product',
                'code' => 'PRD001',
                'name_arabic' => 'منتج عينة',
                'unit' => 'PCS',
                'department' => 'Electronics',
                'main_category' => 'Mobile',
                'sub_category' => 'Smartphone',
                'hsn_code' => '12345',
                'tax' => '5',
                'description' => 'Sample product description',
                'is_selling' => '1',
                'is_favorite' => '0',
                'cost' => '100',
                'mrp' => '150',
                'barcode' => '123456789',
                'pattern' => 'Pattern1',
                'color' => 'Red',
                'size' => 'Large',
                'model' => 'XYZ123',
                'brand' => 'Brand1',
                'part_no' => 'PT001',
                'min_stock' => '10',
                'max_stock' => '100',
                'location' => 'Shelf A1',
                'reorder_level' => '20',
                'plu' => '001',
                'stock' => '50',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'name',
            'code',
            'name_arabic',
            'unit',
            'department',
            'main_category',
            'sub_category',
            'hsn_code',
            'tax',
            'description',
            'is_selling',
            'is_favorite',
            'cost',
            'mrp',
            'barcode',
            'pattern',
            'color',
            'size',
            'model',
            'brand',
            'part_no',
            'min_stock',
            'max_stock',
            'location',
            'reorder_level',
            'plu',
            'stock',
        ];
    }
}

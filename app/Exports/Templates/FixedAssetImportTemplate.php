<?php

namespace App\Exports\Templates;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class FixedAssetImportTemplate implements FromArray, WithHeadings
{
    public function array(): array
    {
        return [
            [
                'code' => 'FA001',
                'name' => 'Office Chair',
                'name_arabic' => 'كرسي مكتب',
                'unit' => 'Nos',
                'department' => 'Admin',
                'main_category' => 'Furniture',
                'sub_category' => 'Office',
                'brand' => 'Ergo',
                'item_no' => 'CH-1001',
                'color' => 'Black',
                'supplier_name' => 'Sample Supplier',
                'location' => 'HQ Floor 2',
                'purchase_date' => '2026-01-15',
                'cost' => '350',
                'mrp' => '500',
                'duration' => '5',
                'duration_period' => 'years',
                'depreciation_method' => 'straight_line',
                'declining_factor' => '0',
                'prorata_date' => '2026-01-15',
                'remarks' => 'Imported sample fixed asset',
                'status' => 'active',
                'upload_type' => 'new',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'code',
            'name',
            'name_arabic',
            'unit',
            'department',
            'main_category',
            'sub_category',
            'brand',
            'item_no',
            'color',
            'supplier_name',
            'location',
            'purchase_date',
            'cost',
            'mrp',
            'duration',
            'duration_period',
            'depreciation_method',
            'declining_factor',
            'prorata_date',
            'remarks',
            'status',
            'upload_type',
        ];
    }
}

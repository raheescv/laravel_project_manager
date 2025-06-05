<?php

namespace App\Exports\Templates;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ServiceImportTemplate implements FromArray, WithHeadings
{
    public function array(): array
    {
        return [
            [
                'code' => 'SRV001',
                'name' => 'Sample Service',
                'name_arabic' => 'خدمة عينة',
                'department' => 'Electronics',
                'main_category' => 'Mobile',
                'sub_category' => 'Smartphone',
                'hsn_code' => '12345',
                'description' => 'Sample service description',
                'is_favorite' => '0',
                'cost' => '100',
                'time' => '10',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'code',
            'name',
            'name_arabic',
            'department',
            'main_category',
            'sub_category',
            'hsn_code',
            'description',
            'is_favorite',
            'cost',
            'time',
        ];
    }
}

<?php

namespace App\Exports\Templates;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SaleImportTemplate implements FromArray, WithHeadings
{
    public function array(): array
    {
        return [
            [
                'reference_no' => 'REF001',
                'date' => date('Y-m-d'),
                'due_date' => date('Y-m-d', strtotime('+30 days')),
                'sale_type' => 'normal',
                'account_id' => 3,
                'customer_name' => 'General Customer',
                'customer_mobile' => '97412345678',
                'product_code' => 'Code123',
                'product_name' => 'Sample Product',
                'barcode' => '123456789',
                'quantity' => '2',
                'unit_price' => '100',
                'discount' => '10',
                'tax' => '5',
                'employee_name' => 'John Doe',
                'assistant_name' => '',
                'other_discount' => '0',
                'freight' => '0',
                'round_off' => '0',
                'status' => 'completed',
                'payment_method_id' => '1',
                'paid' => '200',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'reference_no',
            'date',
            'due_date',
            'sale_type',
            'account_id',
            'customer_name',
            'customer_mobile',
            'product_code',
            'product_name',
            'barcode',
            'quantity',
            'unit_price',
            'discount',
            'tax',
            'employee_name',
            'assistant_name',
            'other_discount',
            'freight',
            'round_off',
            'status',
            'payment_method_id',
            'paid',
        ];
    }
}

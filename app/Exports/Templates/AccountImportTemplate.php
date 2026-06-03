<?php

namespace App\Exports\Templates;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AccountImportTemplate implements FromArray, WithHeadings, WithStyles
{
    public function array(): array
    {
        return [
            [
                'name' => 'Acme Trading LLC',
                'account_type' => 'asset',
                'account_category' => 'Sundry Debtors',
                'customer_type' => 'Retail',
                'model' => 'Customer',
                'alias_name' => 'Acme',
                'mobile' => '9876543210',
                'whatsapp_mobile' => '9876543210',
                'email' => 'billing@acme.example',
                'opening_debit' => '0',
                'opening_credit' => '0',
                'credit_period_days' => '30',
                'place' => 'Dubai',
                'company' => 'Acme Trading LLC',
                'dob' => '',
                'id_no' => 'TRN-100200300',
                'nationality' => 'UAE',
                'second_reference_no' => '',
                'description' => 'Wholesale customer',
            ],
            [
                'name' => 'Office Stationery Supplier',
                'account_type' => 'liability',
                'account_category' => 'Sundry Creditors',
                'customer_type' => '',
                'model' => 'Vendor',
                'alias_name' => 'OSS',
                'mobile' => '9123456780',
                'whatsapp_mobile' => '',
                'email' => 'orders@oss.example',
                'opening_debit' => '0',
                'opening_credit' => '1500',
                'credit_period_days' => '15',
                'place' => 'Sharjah',
                'company' => 'Office Stationery Supplier',
                'dob' => '',
                'id_no' => '',
                'nationality' => '',
                'second_reference_no' => '',
                'description' => 'Stationery vendor',
            ],
            [
                'name' => 'Electricity Expense',
                'account_type' => 'expense',
                'account_category' => 'Utilities',
                'customer_type' => '',
                'model' => '',
                'alias_name' => '',
                'mobile' => '',
                'whatsapp_mobile' => '',
                'email' => '',
                'opening_debit' => '0',
                'opening_credit' => '0',
                'credit_period_days' => '',
                'place' => '',
                'company' => '',
                'dob' => '',
                'id_no' => '',
                'nationality' => '',
                'second_reference_no' => '',
                'description' => 'Monthly utility expense head',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'name',
            'account_type',
            'account_category',
            'customer_type',
            'model',
            'alias_name',
            'mobile',
            'whatsapp_mobile',
            'email',
            'opening_debit',
            'opening_credit',
            'credit_period_days',
            'place',
            'company',
            'dob',
            'id_no',
            'nationality',
            'second_reference_no',
            'description',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '0D6EFD'],
                ],
            ],
        ];
    }
}

<?php

namespace App\Exports\Templates;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GeneralVoucherImportTemplate implements FromArray, WithHeadings, WithStyles
{
    public function array(): array
    {
        return [
            [
                'date' => '2026-03-28',
                'account_name' => 'Office Rent',
                'account_type' => 'Account Head',
                'account_category' => 'Expenses',
                'debit' => '5000',
                'credit' => '0',
                'description' => 'Monthly office rent payment',
                'reference_number' => 'GV-001',
                'person_name' => 'John Doe',
                'remarks' => 'March 2026 rent',
            ],
            [
                'date' => '2026-03-28',
                'account_name' => 'Cash',
                'account_type' => 'Account Head',
                'account_category' => 'Assets',
                'debit' => '0',
                'credit' => '5000',
                'description' => 'Monthly office rent payment',
                'reference_number' => 'GV-001',
                'person_name' => 'John Doe',
                'remarks' => 'March 2026 rent',
            ],
            [
                'date' => '2026-03-28',
                'account_name' => 'Electricity Expense',
                'account_type' => 'Account Head',
                'account_category' => 'Utilities',
                'debit' => '1200',
                'credit' => '0',
                'description' => 'Electricity bill payment',
                'reference_number' => 'GV-002',
                'person_name' => '',
                'remarks' => 'March electricity',
            ],
            [
                'date' => '2026-03-28',
                'account_name' => 'Bank Account',
                'account_type' => 'Account Head',
                'account_category' => 'Assets',
                'debit' => '0',
                'credit' => '1200',
                'description' => 'Electricity bill payment',
                'reference_number' => 'GV-002',
                'person_name' => '',
                'remarks' => 'March electricity',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'date',
            'account_name',
            'account_type',
            'account_category',
            'debit',
            'credit',
            'description',
            'reference_number',
            'person_name',
            'remarks',
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

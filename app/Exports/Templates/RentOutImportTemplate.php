<?php

namespace App\Exports\Templates;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RentOutImportTemplate implements FromArray, WithHeadings
{
    public function __construct(private string $agreementType = 'rental') {}

    public function array(): array
    {
        return [
            [
                'property' => 'A-101',
                'customer' => 'John Doe',
                'salesman' => '',
                'agreement_type' => $this->agreementType,
                'booking_type' => 'agreement',
                'status' => 'occupied',
                'booking_status' => 'completed',
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
                'vacate_date' => '',
                'rent' => '4500',
                'discount' => '0',
                'total' => '54000',
                'payment_frequency' => 'monthly',
                'no_of_terms' => '12',
                'free_month' => '0',
                'collection_starting_day' => '1',
                'collection_payment_mode' => 'cash',
                'management_fee' => '0',
                'down_payment' => '0',
                'remark' => 'Sample agreement',
                'upload_type' => 'new',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'property',
            'customer',
            'salesman',
            'agreement_type',
            'booking_type',
            'status',
            'booking_status',
            'start_date',
            'end_date',
            'vacate_date',
            'rent',
            'discount',
            'total',
            'payment_frequency',
            'no_of_terms',
            'free_month',
            'collection_starting_day',
            'collection_payment_mode',
            'management_fee',
            'down_payment',
            'remark',
            'upload_type',
        ];
    }
}

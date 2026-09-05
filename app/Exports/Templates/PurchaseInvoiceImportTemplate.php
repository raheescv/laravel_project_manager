<?php

namespace App\Exports\Templates;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Item-only template for the purchase invoice uploader — the vendor, date and
 * invoice number are typed on screen, so the sheet carries just the lines.
 */
class PurchaseInvoiceImportTemplate implements FromArray, WithHeadings
{
    public function array(): array
    {
        return [
            ['CODE-001', '8901234567890', 'Sample Product One', 'BATCH-A', 10, 25.5, 0, 5],
            ['CODE-002', '', 'Sample Product Two', '', 4, 120, 20, 5],
            ['', '8901234567891', 'Sample Product Three', '', 2, 75, 0, 0],
        ];
    }

    public function headings(): array
    {
        return [
            'product_code',
            'barcode',
            'product_name',
            'batch',
            'quantity',
            'unit_price',
            'discount',
            'tax',
        ];
    }
}

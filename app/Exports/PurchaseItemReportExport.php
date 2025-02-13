<?php

namespace App\Exports;

use App\Models\PurchaseItem;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class PurchaseItemReportExport implements FromQuery, WithColumnFormatting, WithEvents, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(public array $filters = []) {}

    public function query()
    {
        $query = PurchaseItem::join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->when($this->filters['from_date'] ?? '', function ($query, $value) {
                $query->where('date', '>=', date('Y-m-d', strtotime($value)));
            })
            ->when($this->filters['to_date'] ?? '', function ($query, $value) {
                $query->where('date', '<=', date('Y-m-d', strtotime($value)));
            })
            ->when($this->filters['branch_id'] ?? '', function ($query, $value) {
                $query->where('branch_id', $value);
            })
            ->when($this->filters['product_id'] ?? '', function ($query, $value) {
                $query->where('product_id', $value);
            })
            ->where('purchases.status', 'completed')
            ->select(
                'purchase_items.id',
                'purchase_items.product_id',
                'purchase_items.unit_price',
                'purchase_items.quantity',
                'purchase_items.gross_amount',
                'purchase_items.discount',
                'purchase_items.net_amount',
                'purchase_items.tax_amount',
                'purchase_items.total',
                'purchases.date',
                'purchases.invoice_no',
            );

        return $query;
    }

    public function headings(): array
    {
        return [
            '#',
            'Date',
            'Invoice No',
            'Product',
            'Unit Price',
            'Quantity',
            'Gross Amount',
            'Discount',
            'Net Amount',
            'Tax Amount',
            'Total',
        ];
    }

    public function chunkSize(): int
    {
        return 2000;
    }

    public function map($row): array
    {
        return [
            $row->id,
            systemDate($row->date),
            $row->invoice_no,
            $row->product?->name,
            $row->unit_price,
            $row->quantity,
            $row->gross_amount,
            $row->discount,
            $row->net_amount,
            $row->tax_amount,
            $row->total,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'G' => NumberFormat::FORMAT_NUMBER_00,
            'H' => NumberFormat::FORMAT_NUMBER_00,
            'I' => NumberFormat::FORMAT_NUMBER_00,
            'J' => NumberFormat::FORMAT_NUMBER_00,
            'K' => NumberFormat::FORMAT_NUMBER_00,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $totalRows = $sheet->getHighestRow() + 1;
                $sheet->getStyle("A{$totalRows}:O{$totalRows}")->applyFromArray([
                    'font' => [
                        'bold' => true,
                    ],
                ]);

                $endRow = $totalRows - 1;
                $sheet->setCellValue("G{$totalRows}", "=SUM(G2:G{$endRow})");
                $sheet->setCellValue("H{$totalRows}", "=SUM(H2:H{$endRow})");
                $sheet->setCellValue("I{$totalRows}", "=SUM(I2:I{$endRow})");
                $sheet->setCellValue("J{$totalRows}", "=SUM(J2:J{$endRow})");
                $sheet->setCellValue("K{$totalRows}", "=SUM(K2:K{$endRow})");
            },
        ];
    }
}

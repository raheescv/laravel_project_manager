<?php

namespace App\Exports;

use App\Models\Purchase;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class PurchaseExport implements FromQuery, WithColumnFormatting, WithEvents, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(public array $filters = []) {}

    public function query()
    {
        $query = Purchase::query()
            ->when($this->filter['branch_id'] ?? '', function ($query, $value) {
                $query->where('branch_id', $value);
            })
            ->when($this->filter['vendor_id'] ?? '', function ($query, $value) {
                $query->where('account_id', $value);
            })
            ->when($this->filter['status'] ?? '', function ($query, $value) {
                $query->where('status', $value);
            })
            ->when($this->filter['from_date'] ?? '', function ($query, $value) {
                $query->whereDate('date', '>=', date('Y-m-d', strtotime($value)));
            })
            ->when($this->filter['to_date'] ?? '', function ($query, $value) {
                $query->whereDate('date', '<=', date('Y-m-d', strtotime($value)));
            });

        return $query;
    }

    public function headings(): array
    {
        return [
            '#',
            'Date',
            'Invoice No',
            'Branch Name',
            'Account Name',
            'Gross Amount',
            'Item Discount',
            'Tax Amount',
            'Total',
            'Other Discount',
            'Freight',
            'Grand Total',
            'Paid',
            'Balance',
            'Status',
            'Created By',
            'Created At',
            'Updated By',
            'Updated At',
            'Cancelled By',
            'Cancelled At',
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
            $row->branch?->name,
            $row->account?->name,
            $row->gross_amount,
            $row->item_discount,
            $row->tax_amount,
            $row->total,
            $row->other_discount,
            $row->freight,
            $row->grand_total,
            $row->paid,
            $row->balance,
            ucfirst($row->status),
            $row->createdUser?->name,
            systemDateTime($row->created_at),
            $row->updatedUser?->name,
            systemDateTime($row->updated_at),
            $row->cancelledUser?->name,
            systemDateTime($row->cancelled_at),
        ];
    }

    public function columnFormats(): array
    {
        return [
            'F' => NumberFormat::FORMAT_NUMBER_00,
            'G' => NumberFormat::FORMAT_NUMBER_00,
            'H' => NumberFormat::FORMAT_NUMBER_00,
            'I' => NumberFormat::FORMAT_NUMBER_00,
            'J' => NumberFormat::FORMAT_NUMBER_00,
            'K' => NumberFormat::FORMAT_NUMBER_00,
            'L' => NumberFormat::FORMAT_NUMBER_00,
            'M' => NumberFormat::FORMAT_NUMBER_00,
            'N' => NumberFormat::FORMAT_NUMBER_00,
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
                $sheet->setCellValue("A{$totalRows}", 'Total');
                $sheet->setCellValue("F{$totalRows}", "=SUM(F2:F{$endRow})");
                $sheet->setCellValue("G{$totalRows}", "=SUM(G2:G{$endRow})");
                $sheet->setCellValue("H{$totalRows}", "=SUM(H2:H{$endRow})");
                $sheet->setCellValue("I{$totalRows}", "=SUM(I2:I{$endRow})");
                $sheet->setCellValue("J{$totalRows}", "=SUM(J2:J{$endRow})");
                $sheet->setCellValue("K{$totalRows}", "=SUM(K2:K{$endRow})");
                $sheet->setCellValue("L{$totalRows}", "=SUM(L2:L{$endRow})");
                $sheet->setCellValue("M{$totalRows}", "=SUM(M2:M{$endRow})");
                $sheet->setCellValue("N{$totalRows}", "=SUM(N2:N{$endRow})");
            },
        ];
    }
}

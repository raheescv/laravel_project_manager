<?php

namespace App\Exports;

use App\Models\Sale;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class SaleExport implements FromQuery, WithColumnFormatting, WithEvents, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(public array $filters = []) {}

    public function query()
    {
        $query = Sale::query()
            ->with([
                'branch:id,name',
                'account:id,name',
                'createdUser:id,name',
                'updatedUser:id,name',
                'cancelledUser:id,name',
            ])
            ->when($this->filters['branch_id'] ?? null, function ($query, $value) {
                return $query->where('branch_id', $value);
            })
            ->when($this->filters['customer_id'] ?? null, function ($query, $value) {
                return $query->where('account_id', $value);
            })
            ->when($this->filters['status'] ?? null, function ($query, $value) {
                return $query->where('status', $value);
            })
            ->when($this->filters['from_date'] ?? null, function ($query, $value) {
                return $query->whereDate('date', '>=', $value);
            })
            ->when($this->filters['to_date'] ?? null, function ($query, $value) {
                return $query->whereDate('date', '<=', $value);
            })
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc');

        return $query;
    }

    public function headings(): array
    {
        return [
            '#',
            'Date',
            'Invoice No',
            'Reference No',
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
        return 1000; // Reduced from 2000 for better memory management
    }

    public function map($row): array
    {
        return [
            $row->id,
            systemDate($row->date),
            $row->invoice_no,
            $row->reference_no,
            $row->branch?->name ?? 'N/A',
            $row->account?->name ?? 'N/A',
            $row->gross_amount ?? 0,
            $row->item_discount ?? 0,
            $row->tax_amount ?? 0,
            $row->total ?? 0,
            $row->other_discount ?? 0,
            $row->freight ?? 0,
            $row->grand_total ?? 0,
            $row->paid ?? 0,
            $row->balance ?? 0,
            ucfirst($row->status ?? ''),
            $row->createdUser?->name ?? 'N/A',
            systemDateTime($row->created_at),
            $row->updatedUser?->name ?? 'N/A',
            systemDateTime($row->updated_at),
            $row->cancelledUser?->name ?? 'N/A',
            $row->cancelled_at ? systemDateTime($row->cancelled_at) : 'N/A',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'G' => NumberFormat::FORMAT_NUMBER_00, // Gross Amount
            'H' => NumberFormat::FORMAT_NUMBER_00, // Item Discount
            'I' => NumberFormat::FORMAT_NUMBER_00, // Tax Amount
            'J' => NumberFormat::FORMAT_NUMBER_00, // Total
            'K' => NumberFormat::FORMAT_NUMBER_00, // Other Discount
            'L' => NumberFormat::FORMAT_NUMBER_00, // Freight
            'M' => NumberFormat::FORMAT_NUMBER_00, // Grand Total
            'N' => NumberFormat::FORMAT_NUMBER_00, // Paid
            'O' => NumberFormat::FORMAT_NUMBER_00, // Balance
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                // Only add totals if we have data rows
                if ($highestRow > 1) {
                    $totalRow = $highestRow + 1;

                    // Style the total row
                    $sheet->getStyle("A{$totalRow}:V{$totalRow}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'E6E6E6'],
                        ],
                    ]);

                    // Set total labels and formulas
                    $sheet->setCellValue("A{$totalRow}", 'Total');
                    $sheet->setCellValue("G{$totalRow}", "=SUM(G2:G{$highestRow})");
                    $sheet->setCellValue("H{$totalRow}", "=SUM(H2:H{$highestRow})");
                    $sheet->setCellValue("I{$totalRow}", "=SUM(I2:I{$highestRow})");
                    $sheet->setCellValue("J{$totalRow}", "=SUM(J2:J{$highestRow})");
                    $sheet->setCellValue("K{$totalRow}", "=SUM(K2:K{$highestRow})");
                    $sheet->setCellValue("L{$totalRow}", "=SUM(L2:L{$highestRow})");
                    $sheet->setCellValue("M{$totalRow}", "=SUM(M2:M{$highestRow})");
                    $sheet->setCellValue("N{$totalRow}", "=SUM(N2:N{$highestRow})");
                    $sheet->setCellValue("O{$totalRow}", "=SUM(O2:O{$highestRow})");

                    // Auto-size columns for better readability
                    foreach (range('A', 'V') as $column) {
                        $sheet->getColumnDimension($column)->setAutoSize(true);
                    }
                }
            },
        ];
    }
}

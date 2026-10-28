<?php

namespace App\Exports\RentOut;

use App\Actions\RentOut\Report\GetRoiReportRowsAction;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * The ROI report as the screen shows it: one row per agreement, with
 * booked / paid / payable. Both read from the same action, so the workbook can
 * never drift from the report it was exported from.
 */
class RoiExport implements FromCollection, WithHeadings, WithMapping, WithStrictNullComparison, WithStyles, WithTitle
{
    use Exportable;

    /** Last column letter — bump alongside headings(). */
    private const LAST_COLUMN = 'S';

    private int $rowNumber = 0;

    public function __construct(public array $filters = []) {}

    public function title(): string
    {
        return 'Sale ROI';
    }

    public function collection()
    {
        return (new GetRoiReportRowsAction())->rows($this->filters);
    }

    public function headings(): array
    {
        return [
            '#',
            'Last Booked',
            'Customer',
            'Group',
            'Building',
            'Property No',
            'Property Type',
            'Ownership',
            'Period From',
            'Period To',
            'Months',
            'Sale Price',
            'Per Month ROI',
            'Entries',
            'ROI',
            'Paid',
            'Payable',
            'Status',
            'Remark',
        ];
    }

    public function map($row): array
    {
        return [
            ++$this->rowNumber,
            $this->date($row->last_booked_at),
            $row->customer_name,
            $row->group_name,
            $row->building_name,
            $row->property_number,
            $row->type_name,
            $row->ownership ? ucfirst($row->ownership) : '',
            $this->date($row->period_start),
            $this->date($row->period_end),
            (int) $row->no_of_months,
            $row->sale_price !== null ? (float) $row->sale_price : null,
            $row->per_month_roi !== null ? (float) $row->per_month_roi : null,
            (int) $row->entry_count,
            (float) $row->amount,
            (float) $row->paid,
            (float) $row->balance,
            ucfirst((string) $row->status),
            $row->remark,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $highest = $sheet->getHighestRow();
        $lastCol = self::LAST_COLUMN;

        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '198754']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(26);

        if ($highest > 1) {
            $sheet->getStyle("A1:{$lastCol}{$highest}")->applyFromArray([
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D4D4D4']],
                ],
            ]);

            // Sale price / monthly ROI, then the money columns — the entry count (N) stays whole.
            $sheet->getStyle("L2:M{$highest}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("O2:Q{$highest}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("K2:Q{$highest}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("O2:Q{$highest}")->getFont()->setBold(true);

            // Totals row, so the workbook reconciles on its own.
            $total = $highest + 1;
            $sheet->setCellValue("A{$total}", 'Total');
            $sheet->setCellValue("N{$total}", "=SUM(N2:N{$highest})");
            $sheet->setCellValue("O{$total}", "=SUM(O2:O{$highest})");
            $sheet->setCellValue("P{$total}", "=SUM(P2:P{$highest})");
            $sheet->setCellValue("Q{$total}", "=SUM(Q2:Q{$highest})");
            $sheet->getStyle("A{$total}:{$lastCol}{$total}")->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F3F5']],
            ]);
            $sheet->getStyle("O{$total}:Q{$total}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("N{$total}:Q{$total}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->freezePane('A2');

        return [];
    }

    private function date($value): string
    {
        return $value ? \Carbon\Carbon::parse($value)->format('d-m-Y') : '';
    }
}

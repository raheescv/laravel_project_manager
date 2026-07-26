<?php

namespace App\Exports\RentOut;

use App\Actions\RentOut\Report\GetServiceChargeReportRowsAction;
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
 * The service charge report as the screen shows it: one row per agreement, with
 * charged / paid / balance. Both read from the same action, so the workbook can
 * never drift from the report it was exported from.
 */
class ServiceChargeExport implements FromCollection, WithHeadings, WithMapping, WithStrictNullComparison, WithStyles, WithTitle
{
    use Exportable;

    /** Last column letter — bump alongside headings(). */
    private const LAST_COLUMN = 'V';

    private int $rowNumber = 0;

    public function __construct(public array $filters = []) {}

    public function title(): string
    {
        return 'Sale Service Charges';
    }

    public function collection()
    {
        return (new GetServiceChargeReportRowsAction())->rows($this->filters);
    }

    public function headings(): array
    {
        return [
            '#',
            'Last Charged',
            'Customer',
            'Group',
            'Building',
            'Property No',
            'Property Type',
            'Ownership',
            'Period From',
            'Period To',
            'Months',
            'Days',
            'Unit Size',
            'Per Sq M Price',
            'Per Day Price',
            'Charges',
            'Charged',
            'Paid',
            'Balance',
            'Status',
            'Remark',
            'Reason',
        ];
    }

    public function map($row): array
    {
        return [
            ++$this->rowNumber,
            $this->date($row->last_charged_at),
            $row->customer_name,
            $row->group_name,
            $row->building_name,
            $row->property_number,
            $row->type_name,
            $row->ownership ? ucfirst($row->ownership) : '',
            $this->date($row->period_start),
            $this->date($row->period_end),
            (int) $row->no_of_months,
            (int) $row->no_of_days,
            $row->unit_size !== null ? (float) $row->unit_size : null,
            $row->per_square_meter_price !== null ? (float) $row->per_square_meter_price : null,
            $row->per_day_price !== null ? (float) $row->per_day_price : null,
            (int) $row->charge_count,
            (float) $row->amount,
            (float) $row->paid,
            (float) $row->balance,
            ucfirst((string) $row->status),
            $row->remark,
            $row->reason,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $highest = $sheet->getHighestRow();
        $lastCol = self::LAST_COLUMN;

        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0D6EFD']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(26);

        if ($highest > 1) {
            $sheet->getStyle("A1:{$lastCol}{$highest}")->applyFromArray([
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D4D4D4']],
                ],
            ]);

            // Unit size / prices, then the money columns — the charge count (P) stays whole.
            $sheet->getStyle("M2:O{$highest}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("Q2:S{$highest}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("K2:S{$highest}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("Q2:S{$highest}")->getFont()->setBold(true);

            // Totals row, so the workbook reconciles on its own.
            $total = $highest + 1;
            $sheet->setCellValue("A{$total}", 'Total');
            $sheet->setCellValue("P{$total}", "=SUM(P2:P{$highest})");
            $sheet->setCellValue("Q{$total}", "=SUM(Q2:Q{$highest})");
            $sheet->setCellValue("R{$total}", "=SUM(R2:R{$highest})");
            $sheet->setCellValue("S{$total}", "=SUM(S2:S{$highest})");
            $sheet->getStyle("A{$total}:{$lastCol}{$total}")->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F3F5']],
            ]);
            $sheet->getStyle("Q{$total}:S{$total}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("P{$total}:S{$total}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
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

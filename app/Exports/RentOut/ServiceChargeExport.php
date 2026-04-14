<?php

namespace App\Exports\RentOut;

use App\Models\RentOutService;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ServiceChargeExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use Exportable;

    public function __construct(public array $filters = []) {}

    public function title(): string
    {
        return 'Sale Service Charges';
    }

    public function query()
    {
        return RentOutService::query()
            ->with(['rentOut.customer', 'rentOut.property', 'rentOut.building', 'rentOut.group', 'rentOut.type'])
            ->whereHas('rentOut', fn ($r) => $r->where('agreement_type', 'lease'))
            ->when($this->filters['filterGroup'] ?? '', fn ($q, $v) => $q->whereHas('rentOut', fn ($r) => $r->where('property_group_id', $v)))
            ->when($this->filters['filterBuilding'] ?? '', fn ($q, $v) => $q->whereHas('rentOut', fn ($r) => $r->where('property_building_id', $v)))
            ->when($this->filters['filterType'] ?? '', fn ($q, $v) => $q->whereHas('rentOut', fn ($r) => $r->where('property_type_id', $v)))
            ->when($this->filters['filterProperty'] ?? '', fn ($q, $v) => $q->whereHas('rentOut', fn ($r) => $r->where('property_id', $v)))
            ->when($this->filters['filterCustomer'] ?? '', fn ($q, $v) => $q->whereHas('rentOut', fn ($r) => $r->where('account_id', $v)))
            ->when($this->filters['filterOwnership'] ?? '', fn ($q, $v) => $q->whereHas('rentOut.property', fn ($p) => $p->where('ownership', $v)))
            ->when($this->filters['dateFrom'] ?? '', fn ($q, $v) => $q->where('rent_out_services.created_at', '>=', $v))
            ->when($this->filters['dateTo'] ?? '', fn ($q, $v) => $q->where('rent_out_services.created_at', '<=', $v.' 23:59:59'))
            ->when($this->filters['search'] ?? '', function ($q, $value) {
                $q->where(function ($q) use ($value) {
                    $q->where('rent_out_services.id', 'like', "%{$value}%")
                        ->orWhere('remark', 'like', "%{$value}%")
                        ->orWhere('reason', 'like', "%{$value}%")
                        ->orWhere('description', 'like', "%{$value}%")
                        ->orWhereHas('rentOut.customer', fn ($c) => $c->where('name', 'like', "%{$value}%"))
                        ->orWhereHas('rentOut.property', fn ($p) => $p->where('number', 'like', "%{$value}%"));
                });
            })
            ->orderBy('rent_out_services.created_at', 'desc')
            ->orderBy('rent_out_services.id', 'desc');
    }

    public function headings(): array
    {
        return [
            '#',
            'Date',
            'Customer',
            'Group',
            'Building',
            'Property No',
            'Start Date',
            'End Date',
            'Months',
            'Days',
            'Unit Size',
            'Per Sq M Price',
            'Per Day Price',
            'Amount',
            'Remark',
            'Reason',
        ];
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->created_at?->format('d-m-Y'),
            $row->rentOut?->customer?->name,
            $row->rentOut?->group?->name,
            $row->rentOut?->building?->name,
            $row->rentOut?->property?->number,
            $row->start_date?->format('d-m-Y'),
            $row->end_date?->format('d-m-Y'),
            $row->no_of_months,
            $row->no_of_days,
            $row->unit_size,
            $row->per_square_meter_price,
            $row->per_day_price,
            (float) $row->amount,
            $row->remark,
            $row->reason,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $highest = $sheet->getHighestRow();
        $lastCol = 'P'; // 16 columns

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
            $sheet->getStyle("K2:N{$highest}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("K2:N{$highest}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("I2:J{$highest}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->freezePane('A2');

        return [];
    }
}

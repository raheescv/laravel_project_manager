<?php

namespace App\Exports\RentOut;

use App\Models\Account;
use App\Models\RentOutTransaction;
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

class ServiceDaybookExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use Exportable;

    protected array $categoryNames = [];

    public function __construct(public array $filters = []) {}

    public function title(): string
    {
        return 'RentOut Daybook';
    }

    public function query()
    {
        $query = RentOutTransaction::query()
            ->with(['rentOut.customer', 'rentOut.property', 'rentOut.building', 'rentOut.group', 'rentOut.type', 'account'])
            ->when($this->filters['filterGroup'] ?? '', fn ($q, $v) => $q->whereHas('rentOut', fn ($r) => $r->where('property_group_id', $v)))
            ->when($this->filters['filterBuilding'] ?? '', fn ($q, $v) => $q->whereHas('rentOut', fn ($r) => $r->where('property_building_id', $v)))
            ->when($this->filters['filterType'] ?? '', fn ($q, $v) => $q->whereHas('rentOut', fn ($r) => $r->where('property_type_id', $v)))
            ->when($this->filters['filterProperty'] ?? '', fn ($q, $v) => $q->whereHas('rentOut', fn ($r) => $r->where('property_id', $v)))
            ->when($this->filters['filterCustomer'] ?? '', fn ($q, $v) => $q->whereHas('rentOut', fn ($r) => $r->where('account_id', $v)))
            ->when($this->filters['filterOwnership'] ?? '', fn ($q, $v) => $q->whereHas('rentOut.property', fn ($p) => $p->where('ownership', $v)))
            ->when($this->filters['filterCategory'] ?? '', fn ($q, $v) => $q->where('category', $v))
            ->when($this->filters['filterSource'] ?? '', fn ($q, $v) => $q->where('source', $v))
            ->when($this->filters['filterGroupCol'] ?? '', fn ($q, $v) => $q->where('group', $v))
            ->when($this->filters['filterPaymentType'] ?? '', fn ($q, $v) => $q->where('payment_type', $v))
            ->when($this->filters['filterDirection'] ?? '', function ($q, $value) {
                return match ($value) {
                    'charge' => $q->where('debit', '>', 0),
                    'payment' => $q->where('credit', '>', 0),
                    default => $q,
                };
            })
            ->when($this->filters['dateFrom'] ?? '', fn ($q, $v) => $q->where('date', '>=', $v))
            ->when($this->filters['dateTo'] ?? '', fn ($q, $v) => $q->where('date', '<=', $v))
            ->when($this->filters['search'] ?? '', function ($q, $value) {
                return $q->where(function ($q) use ($value) {
                    $q->where('rent_out_transactions.id', 'like', "%{$value}%")
                        ->orWhere('voucher_no', 'like', "%{$value}%")
                        ->orWhereHas('rentOut.customer', fn ($c) => $c->where('name', 'like', "%{$value}%"))
                        ->orWhereHas('rentOut.property', fn ($p) => $p->where('number', 'like', "%{$value}%"));
                });
            })
            ->orderBy('date', 'desc')
            ->orderBy('rent_out_transactions.id', 'desc');

        // Pre-resolve category names
        $categoryIds = (clone $query)->pluck('category')->filter()->unique()->values()->toArray();
        $this->categoryNames = Account::whereIn('id', $categoryIds)->pluck('name', 'id')->toArray();

        return $query;
    }

    public function headings(): array
    {
        return [
            '#',
            'Date',
            'Voucher No',
            'Customer',
            'Group/Project',
            'Building',
            'Property Type',
            'Property/Unit',
            'Ownership',
            'Category',
            'Source',
            'Txn Group',
            'Payment Type',
            'Account',
            'Remark',
            'Charge',
            'Paid',
            'Balance',
        ];
    }

    public function map($row): array
    {
        $charge = (float) $row->debit;
        $paid = (float) $row->credit;

        return [
            $row->id,
            $row->date?->format('d-m-Y'),
            $row->voucher_no,
            $row->rentOut?->customer?->name,
            $row->rentOut?->group?->name,
            $row->rentOut?->building?->name,
            $row->rentOut?->type?->name,
            $row->rentOut?->property?->number,
            $row->rentOut?->property?->ownership,
            $this->categoryNames[$row->category] ?? $row->category,
            $row->source,
            $row->group ? ucfirst(str_replace('_', ' ', $row->group)) : null,
            $row->payment_type ? ucfirst(str_replace('_', ' ', $row->payment_type)) : null,
            $row->account?->name,
            $row->remark,
            $charge,
            $paid,
            $charge - $paid,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $highest = $sheet->getHighestRow();
        $lastCol = 'R'; // 18 columns

        // Header row styling
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

            // Number formatting for amount columns (P, Q, R)
            $sheet->getStyle("P2:R{$highest}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("P2:R{$highest}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->freezePane('A2');

        return [];
    }
}

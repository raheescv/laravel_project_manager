<?php

namespace App\Exports\RentOut;

use App\Enums\RentOut\AgreementType;
use App\Models\RentOutCheque;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ChequeExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(public array $filters = []) {}

    public function query()
    {
        $agreementType = AgreementType::from($this->filters['agreementType'] ?? 'rental');

        return RentOutCheque::query()
            ->with(['rentOut.customer', 'rentOut.property', 'rentOut.building'])
            ->whereHas('rentOut', fn ($q) => $q->where('agreement_type', $agreementType))
            ->when($this->filters['filterGroup'] ?? '', fn ($q, $v) => $q->whereHas('rentOut', fn ($r) => $r->where('property_group_id', $v)))
            ->when($this->filters['filterBuilding'] ?? '', fn ($q, $v) => $q->whereHas('rentOut', fn ($r) => $r->where('property_building_id', $v)))
            ->when($this->filters['filterType'] ?? '', fn ($q, $v) => $q->whereHas('rentOut', fn ($r) => $r->where('property_type_id', $v)))
            ->when($this->filters['filterProperty'] ?? '', fn ($q, $v) => $q->whereHas('rentOut', fn ($r) => $r->where('property_id', $v)))
            ->when($this->filters['filterCustomer'] ?? '', fn ($q, $v) => $q->whereHas('rentOut', fn ($r) => $r->where('account_id', $v)))
            ->when($this->filters['filterOwnership'] ?? '', fn ($q, $v) => $q->whereHas('rentOut.property', fn ($p) => $p->where('ownership', $v)))
            ->when($this->filters['filterStatus'] ?? '', fn ($q, $v) => $q->where('status', $v))
            ->when($this->filters['dateFrom'] ?? '', fn ($q, $v) => $q->where('date', '>=', $v))
            ->when($this->filters['dateTo'] ?? '', fn ($q, $v) => $q->where('date', '<=', $v))
            ->when($this->filters['search'] ?? '', function ($q, $value) {
                return $q->where(function ($q) use ($value) {
                    $q->where('id', 'like', "%{$value}%")
                        ->orWhereHas('rentOut.customer', fn ($c) => $c->where('name', 'like', "%{$value}%"));
                });
            })
            ->orderBy('date', 'desc');
    }

    public function headings(): array
    {
        return ['#', 'Date', 'Customer', 'Building', 'Property No/Unit', 'Bank', 'Cheque No', 'Amount', 'Status'];
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->date?->format('d-m-Y'),
            $row->rentOut?->customer?->name,
            $row->rentOut?->building?->name,
            $row->rentOut?->property?->number,
            $row->bank_name,
            $row->cheque_no,
            $row->amount,
            $row->status?->label(),
        ];
    }
}

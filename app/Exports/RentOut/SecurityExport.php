<?php

namespace App\Exports\RentOut;

use App\Models\RentOutSecurity;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SecurityExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(public array $filters = []) {}

    public function query()
    {
        return RentOutSecurity::query()
            ->with(['rentOut.customer', 'rentOut.property', 'rentOut.building', 'rentOut.group', 'rentOut.type'])
            ->when($this->filters['filterGroup'] ?? '', fn ($q, $v) => $q->whereHas('rentOut', fn ($r) => $r->where('property_group_id', $v)))
            ->when($this->filters['filterBuilding'] ?? '', fn ($q, $v) => $q->whereHas('rentOut', fn ($r) => $r->where('property_building_id', $v)))
            ->when($this->filters['filterType'] ?? '', fn ($q, $v) => $q->whereHas('rentOut', fn ($r) => $r->where('property_type_id', $v)))
            ->when($this->filters['filterProperty'] ?? '', fn ($q, $v) => $q->whereHas('rentOut', fn ($r) => $r->where('property_id', $v)))
            ->when($this->filters['filterCustomer'] ?? '', fn ($q, $v) => $q->whereHas('rentOut', fn ($r) => $r->where('account_id', $v)))
            ->when($this->filters['filterOwnership'] ?? '', fn ($q, $v) => $q->whereHas('rentOut.property', fn ($p) => $p->where('ownership', $v)))
            ->when($this->filters['filterSecurityType'] ?? '', fn ($q, $v) => $q->where('type', $v))
            ->when($this->filters['filterPaymentMethod'] ?? '', fn ($q, $v) => $q->where('payment_mode', $v))
            ->when($this->filters['filterSecurityStatus'] ?? '', fn ($q, $v) => $q->where('status', $v))
            ->when($this->filters['dateFrom'] ?? '', fn ($q, $v) => $q->where('due_date', '>=', $v))
            ->when($this->filters['dateTo'] ?? '', fn ($q, $v) => $q->where('due_date', '<=', $v))
            ->when($this->filters['search'] ?? '', function ($q, $value) {
                return $q->where(function ($q) use ($value) {
                    $q->where('id', 'like', "%{$value}%")
                        ->orWhereHas('rentOut.customer', fn ($c) => $c->where('name', 'like', "%{$value}%"));
                });
            })
            ->orderBy('due_date', 'desc');
    }

    public function headings(): array
    {
        return ['#', 'Customer', 'Property Group', 'Property Building', 'Property Type', 'Property No/Unit', 'Type', 'Payment Method', 'Cheque No', 'Bank Name', 'Due Date', 'Amount', 'Status'];
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->rentOut?->customer?->name,
            $row->rentOut?->group?->name,
            $row->rentOut?->building?->name,
            $row->rentOut?->type?->name,
            $row->rentOut?->property?->number,
            $row->type?->label(),
            $row->payment_mode?->label(),
            $row->cheque_no,
            $row->bank_name,
            $row->due_date?->format('d-m-Y'),
            $row->amount,
            $row->status?->label(),
        ];
    }
}

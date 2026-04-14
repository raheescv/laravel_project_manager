<?php

namespace App\Exports\RentOut;

use App\Models\RentOutUtilityTerm;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UtilityExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(public array $filters = []) {}

    public function query()
    {
        return RentOutUtilityTerm::query()
            ->with(['rentOut.customer', 'rentOut.property', 'rentOut.building', 'rentOut.group', 'utility'])
            ->when($this->filters['filterGroup'] ?? '', fn ($q, $v) => $q->whereHas('rentOut', fn ($r) => $r->where('property_group_id', $v)))
            ->when($this->filters['filterBuilding'] ?? '', fn ($q, $v) => $q->whereHas('rentOut', fn ($r) => $r->where('property_building_id', $v)))
            ->when($this->filters['filterType'] ?? '', fn ($q, $v) => $q->whereHas('rentOut', fn ($r) => $r->where('property_type_id', $v)))
            ->when($this->filters['filterProperty'] ?? '', fn ($q, $v) => $q->whereHas('rentOut', fn ($r) => $r->where('property_id', $v)))
            ->when($this->filters['filterCustomer'] ?? '', fn ($q, $v) => $q->whereHas('rentOut', fn ($r) => $r->where('account_id', $v)))
            ->when($this->filters['filterOwnership'] ?? '', fn ($q, $v) => $q->whereHas('rentOut.property', fn ($p) => $p->where('ownership', $v)))
            ->when($this->filters['filterUtility'] ?? '', fn ($q, $v) => $q->where('utility_id', $v))
            ->when($this->filters['filterPaidStatus'] ?? '', function ($q, $value) {
                return match ($value) {
                    'pending' => $q->where('balance', '>', 0),
                    'paid' => $q->where('balance', '<=', 0),
                    default => $q,
                };
            })
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
        return ['#', 'Date', 'Customer', 'Group/Project', 'Building', 'Property No/Unit', 'Ownership', 'Utility', 'Amount', 'Paid', 'Balance'];
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->date?->format('d-m-Y'),
            $row->rentOut?->customer?->name,
            $row->rentOut?->group?->name,
            $row->rentOut?->building?->name,
            $row->rentOut?->property?->number,
            $row->rentOut?->property?->ownership,
            $row->utility?->name,
            $row->amount,
            $row->paid,
            $row->balance,
        ];
    }
}

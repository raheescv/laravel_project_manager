<?php

namespace App\Exports\RentOut;

use App\Enums\RentOut\AgreementType;
use App\Models\RentOut;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RentOutTableExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(public array $filters = []) {}

    public function query()
    {
        $agreementType = AgreementType::from($this->filters['agreementType'] ?? 'lease');

        return RentOut::query()
            ->with(['customer', 'property', 'building', 'group'])
            ->where('agreement_type', $agreementType)
            ->when($this->filters['filterGroup'] ?? '', fn ($q, $v) => $q->where('property_group_id', $v))
            ->when($this->filters['filterBuilding'] ?? '', fn ($q, $v) => $q->where('property_building_id', $v))
            ->when($this->filters['filterProperty'] ?? '', fn ($q, $v) => $q->where('property_id', $v))
            ->when($this->filters['filterCustomer'] ?? '', fn ($q, $v) => $q->where('account_id', $v))
            ->when($this->filters['filterStatus'] ?? '', fn ($q, $v) => $q->where('status', $v))
            ->when($this->filters['fromDate'] ?? '', fn ($q, $v) => $q->whereDate('start_date', '>=', $v))
            ->when($this->filters['toDate'] ?? '', fn ($q, $v) => $q->whereDate('end_date', '<=', $v))
            ->when(($this->filters['electricityFilter'] ?? '') !== '', fn ($q) => $q->where('include_electricity_water', $this->filters['electricityFilter']))
            ->when(($this->filters['acFilter'] ?? '') !== '', fn ($q) => $q->where('include_ac', $this->filters['acFilter']))
            ->when(($this->filters['wifiFilter'] ?? '') !== '', fn ($q) => $q->where('include_wifi', $this->filters['wifiFilter']))
            ->when($this->filters['search'] ?? '', function ($q, $value) {
                return $q->where(function ($q) use ($value) {
                    $q->where('id', 'like', "%{$value}%")
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$value}%"))
                        ->orWhereHas('property', fn ($p) => $p->where('number', 'like', "%{$value}%"));
                });
            })
            ->orderBy('id', 'desc');
    }

    public function headings(): array
    {
        return ['#', 'Customer', 'Group/Project', 'Building', 'Property/Unit', 'Start Date', 'End Date', 'Rent', 'Status'];
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->customer?->name,
            $row->group?->name,
            $row->building?->name,
            $row->property?->number,
            $row->start_date?->format('d-m-Y'),
            $row->end_date?->format('d-m-Y'),
            $row->rent,
            $row->status?->label(),
        ];
    }
}

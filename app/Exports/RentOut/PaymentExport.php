<?php

namespace App\Exports\RentOut;

use App\Enums\RentOut\AgreementType;
use App\Models\RentOutPaymentTerm;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PaymentExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(public array $filters = []) {}

    public function query()
    {
        $agreementType = AgreementType::from($this->filters['agreementType'] ?? 'rental');

        return RentOutPaymentTerm::query()
            ->with(['rentOut.customer', 'rentOut.property', 'rentOut.building', 'rentOut.group', 'rentOut.salesman'])
            ->whereHas('rentOut', fn ($q) => $q->where('agreement_type', $agreementType))
            ->when($this->filters['filterGroup'] ?? '', fn ($q, $v) => $q->whereHas('rentOut', fn ($r) => $r->where('property_group_id', $v)))
            ->when($this->filters['filterBuilding'] ?? '', fn ($q, $v) => $q->whereHas('rentOut', fn ($r) => $r->where('property_building_id', $v)))
            ->when($this->filters['filterType'] ?? '', fn ($q, $v) => $q->whereHas('rentOut', fn ($r) => $r->where('property_type_id', $v)))
            ->when($this->filters['filterProperty'] ?? '', fn ($q, $v) => $q->whereHas('rentOut', fn ($r) => $r->where('property_id', $v)))
            ->when($this->filters['filterCustomer'] ?? '', fn ($q, $v) => $q->whereHas('rentOut', fn ($r) => $r->where('account_id', $v)))
            ->when($this->filters['filterOwnership'] ?? '', fn ($q, $v) => $q->whereHas('rentOut.property', fn ($p) => $p->where('ownership', $v)))
            ->when($this->filters['filterSalesman'] ?? '', fn ($q, $v) => $q->whereHas('rentOut', fn ($r) => $r->where('salesman_id', $v)))
            ->when($this->filters['filterPaymentMode'] ?? '', fn ($q, $v) => $q->where('payment_mode', $v))
            ->when($this->filters['filterPaymentStatus'] ?? '', function ($q, $value) {
                return match ($value) {
                    'pending' => $q->where('status', 'pending'),
                    'paid' => $q->where('status', 'paid'),
                    'overdue' => $q->where('status', 'pending')->where('due_date', '<', now()),
                    default => $q,
                };
            })
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
        return ['#', 'Date', 'Customer', 'Salesman', 'Group/Project', 'Building', 'Property No/Unit', 'Ownership', 'Payment Mode', 'Amount', 'Paid', 'Balance'];
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->due_date?->format('d-m-Y'),
            $row->rentOut?->customer?->name,
            $row->rentOut?->salesman?->name,
            $row->rentOut?->group?->name,
            $row->rentOut?->building?->name,
            $row->rentOut?->property?->number,
            $row->rentOut?->property?->ownership,
            $row->payment_mode,
            $row->total,
            $row->paid,
            $row->balance,
        ];
    }
}

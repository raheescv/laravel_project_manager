<?php

namespace App\Exports\RentOut;

use App\Enums\RentOut\AgreementType;
use App\Enums\RentOut\RentOutStatus;
use App\Models\RentOut;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class BookingExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(public array $filters = []) {}

    public function query()
    {
        $agreementType = AgreementType::from($this->filters['agreementType'] ?? 'lease');

        return RentOut::query()
            ->with(['customer', 'property', 'building', 'group'])
            ->where('agreement_type', $agreementType)
            ->where('status', RentOutStatus::Booked)
            ->when($this->filters['filterGroup'] ?? '', fn ($q, $v) => $q->where('property_group_id', $v))
            ->when($this->filters['filterBuilding'] ?? '', fn ($q, $v) => $q->where('property_building_id', $v))
            ->when($this->filters['filterProperty'] ?? '', fn ($q, $v) => $q->where('property_id', $v))
            ->when($this->filters['filterCustomer'] ?? '', fn ($q, $v) => $q->where('account_id', $v))
            ->when($this->filters['filterBookingType'] ?? '', fn ($q, $v) => $q->where('booking_type', $v))
            ->when($this->filters['filterBookingStatus'] ?? '', fn ($q, $v) => $q->where('booking_status', $v))
            ->when($this->filters['fromDate'] ?? '', fn ($q, $v) => $q->whereDate('start_date', '>=', $v))
            ->when($this->filters['toDate'] ?? '', fn ($q, $v) => $q->whereDate('end_date', '<=', $v))
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
        return ['#', 'Customer', 'Group/Project', 'Building', 'Property No/Unit', 'Start Date', 'End Date', 'Rent', 'Booking Status', 'Status', 'Created At'];
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
            $row->booking_status?->label(),
            $row->status?->label(),
            $row->created_at?->format('d-m-Y h:i:s A'),
        ];
    }
}

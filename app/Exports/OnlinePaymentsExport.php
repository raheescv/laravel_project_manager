<?php

namespace App\Exports;

use App\Livewire\Sale\OnlinePayments;
use App\Models\StorefrontCheckout;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/** The online payments (Tap) report as filtered on screen (OnlinePayments::filteredQuery). */
class OnlinePaymentsExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    /** @param  array<string, mixed>  $filters */
    public function __construct(public array $filters = []) {}

    /** @return Builder<StorefrontCheckout> */
    public function query(): Builder
    {
        return OnlinePayments::filteredQuery($this->filters);
    }

    /** @return list<string> */
    public function headings(): array
    {
        return ['Date', 'Customer', 'Mobile', 'Email', 'Fulfilment', 'Shop', 'Zone', 'Street', 'Building', 'City', 'Latitude', 'Longitude', 'Amount', 'Currency', 'Status', 'Tap Status', 'Tap Charge', 'Invoice', 'Reason'];
    }

    /**
     * @param  StorefrontCheckout  $row
     * @return list<mixed>
     */
    public function map($row): array
    {
        return [
            $row->created_at?->format('Y-m-d H:i'),
            $row->customer_name,
            $row->customer_mobile,
            $row->customer_email,
            ucfirst($row->fulfilment),
            $row->branch?->name,
            $row->zone_number,
            $row->street_number,
            $row->building_number,
            $row->city,
            $row->latitude,
            $row->longitude,
            (float) $row->amount,
            $row->currency,
            ucfirst($row->status),
            $row->gateway_status,
            $row->gateway_charge_id,
            $row->sale?->invoice_no,
            $row->failure_reason,
        ];
    }
}

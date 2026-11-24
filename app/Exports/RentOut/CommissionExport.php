<?php

namespace App\Exports\RentOut;

use App\Actions\RentOut\Report\GetCommissionRowsAction;
use App\Enums\RentOut\CommissionStatus;
use App\Models\RentOutCommission;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * The Commission Report as the screen shows it; both read from the same action.
 */
class CommissionExport implements FromCollection, WithHeadings, WithMapping, WithStrictNullComparison, WithTitle
{
    use Exportable;

    public function __construct(public array $filters = []) {}

    public function title(): string
    {
        return 'Commission Report';
    }

    /** @return Collection<int, RentOutCommission> */
    public function collection(): Collection
    {
        return (new GetCommissionRowsAction())->groupedByAgent($this->filters)->flatten(1)->values();
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return ['Agent Name', 'Property', 'Contract No.', 'Tenant', 'Commission %', 'Commission Amount', 'Status', 'Paid/Unpaid'];
    }

    /** @param  RentOutCommission  $commission */
    public function map($commission): array
    {
        $rentOut = $commission->rentOut;
        $property = $rentOut?->property;

        return [
            $commission->agent?->name,
            trim(($property?->building?->name ?? '').($property ? ' · '.$property->number : '')),
            $rentOut?->agreement_no,
            $rentOut?->customer?->name,
            $commission->commission_type === RentOutCommission::TYPE_PERCENTAGE ? (float) $commission->commission_percentage : 'Fixed',
            (float) $commission->amount,
            $commission->status->label(),
            $commission->status === CommissionStatus::Paid ? 'Paid' : 'Unpaid',
        ];
    }
}

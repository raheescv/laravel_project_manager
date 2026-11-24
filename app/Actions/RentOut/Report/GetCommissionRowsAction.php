<?php

namespace App\Actions\RentOut\Report;

use App\Enums\RentOut\CommissionStatus;
use App\Models\RentOutCommission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * One query behind the Commissions list, the Commission Report and its export,
 * so the screens and the workbook always agree.
 */
class GetCommissionRowsAction
{
    /**
     * @param  array{dateFrom?: ?string, dateTo?: ?string, agentId?: mixed, statuses?: array<int, string>, paid?: string, search?: string, reportable?: bool}  $filters
     * @return Builder<RentOutCommission>
     */
    public function query(array $filters): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));

        return RentOutCommission::query()
            ->with(['agent:id,name', 'paymentAccount:id,name', 'rentOut' => fn ($query) => $query->with(['customer:id,name', 'property:id,number,property_building_id', 'property.building:id,name'])])
            ->when($filters['reportable'] ?? false, fn (Builder $query) => $query->whereNotIn('status', [CommissionStatus::Draft->value, CommissionStatus::Rejected->value]))
            ->when($filters['dateFrom'] ?? null, fn (Builder $query, $date) => $query->whereDate('date', '>=', $date))
            ->when($filters['dateTo'] ?? null, fn (Builder $query, $date) => $query->whereDate('date', '<=', $date))
            ->when($filters['agentId'] ?? null, fn (Builder $query, $agentId) => $query->where('agent_id', $agentId))
            ->when($filters['statuses'] ?? null, fn (Builder $query, $statuses) => $query->whereIn('status', $statuses))
            ->when(($filters['paid'] ?? '') === 'paid', fn (Builder $query) => $query->where('status', CommissionStatus::Paid->value))
            ->when(($filters['paid'] ?? '') === 'unpaid', fn (Builder $query) => $query->where('status', '!=', CommissionStatus::Paid->value))
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($search): void {
                $query->where('invoice_no', 'like', "%{$search}%")
                    ->orWhere('remark', 'like', "%{$search}%")
                    ->orWhereHas('agent', fn (Builder $agent) => $agent->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('rentOut.customer', fn (Builder $customer) => $customer->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('rentOut.property', fn (Builder $property) => $property->where('number', 'like', "%{$search}%"));
            }));
    }

    /**
     * Report rows grouped by agent, agents in name order.
     *
     * @return Collection<string, Collection<int, RentOutCommission>>
     */
    public function groupedByAgent(array $filters): Collection
    {
        return $this->query($filters)
            ->orderBy('date')
            ->orderBy('id')
            ->get()
            ->sortBy(fn (RentOutCommission $commission): string => (string) $commission->agent?->name)
            ->groupBy(fn (RentOutCommission $commission): string => (string) ($commission->agent?->name ?? '—'));
    }
}

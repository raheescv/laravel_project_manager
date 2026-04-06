<?php

namespace App\Livewire\RentOut\Report;

use App\Exports\RentOut\ServiceChargeExport;
use App\Livewire\RentOut\Concerns\HasRentOutReportFilters;
use App\Models\RentOutService;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class ServiceChargeTable extends Component
{
    use HasRentOutReportFilters, WithPagination;

    protected $paginationTheme = 'bootstrap';

    public function getDefaultColumns(): array
    {
        return [
            'date',
            'customer',
            'group',
            'building',
            'property',
            'start_date',
            'end_date',
            'no_of_months',
            'no_of_days',
            'unit_size',
            'per_square_meter_price',
            'per_day_price',
            'amount',
            'remark',
            'reason',
        ];
    }

    /**
     * Service charges limited to LEASE (sale) agreements.
     */
    protected function baseQuery(): Builder
    {
        return RentOutService::query()
            ->whereHas('rentOut', fn ($r) => $r->where('agreement_type', 'lease'))
            ->tap(fn ($q) => $this->applyRentOutFilters($q))
            ->tap(fn ($q) => $this->applyDateFilter($q, 'rent_out_services.created_at'));
    }

    protected function buildQuery(): Builder
    {
        return $this->baseQuery()
            ->with(['rentOut.customer', 'rentOut.property', 'rentOut.building', 'rentOut.group', 'rentOut.type'])
            ->when($this->search, function ($q, $value) {
                $value = trim($value);
                $q->where(function ($q) use ($value) {
                    $q->where('rent_out_services.id', 'like', "%{$value}%")
                        ->orWhere('remark', 'like', "%{$value}%")
                        ->orWhere('reason', 'like', "%{$value}%")
                        ->orWhere('description', 'like', "%{$value}%")
                        ->orWhereHas('rentOut.customer', fn ($c) => $c->where('name', 'like', "%{$value}%"))
                        ->orWhereHas('rentOut.property', fn ($p) => $p->where('number', 'like', "%{$value}%"));
                });
            })
            ->orderBy(
                $this->sortField === 'id' ? 'rent_out_services.id' : $this->sortField,
                $this->sortDirection
            );
    }

    public function getKpisProperty(): array
    {
        $totals = (clone $this->baseQuery())
            ->selectRaw('COALESCE(SUM(amount), 0) as total_amount, COUNT(*) as txns')
            ->first();

        $customerCount = (clone $this->baseQuery())
            ->join('rent_outs', 'rent_outs.id', '=', 'rent_out_services.rent_out_id')
            ->distinct('rent_outs.account_id')
            ->count('rent_outs.account_id');

        return [
            'total_amount' => (float) ($totals->total_amount ?? 0),
            'transactions' => (int) ($totals->txns ?? 0),
            'customers' => $customerCount,
        ];
    }

    /**
     * Group/Project breakdown.
     */
    public function getSummaryProperty(): array
    {
        $rows = (clone $this->baseQuery())
            ->join('rent_outs', 'rent_outs.id', '=', 'rent_out_services.rent_out_id')
            ->leftJoin('property_groups', 'property_groups.id', '=', 'rent_outs.property_group_id')
            ->selectRaw('
                COALESCE(property_groups.name, "Uncategorised") as group_name,
                COALESCE(SUM(rent_out_services.amount), 0) as amount,
                COUNT(*) as txns
            ')
            ->groupBy('property_groups.name')
            ->orderByDesc('amount')
            ->get();

        $summary = [];
        foreach ($rows as $row) {
            $summary[] = [
                'name' => $row->group_name ?: 'Uncategorised',
                'amount' => (float) $row->amount,
                'txns' => (int) $row->txns,
            ];
        }

        return $summary;
    }

    public function download()
    {
        $filters = [
            'filterGroup' => $this->filterGroup,
            'filterBuilding' => $this->filterBuilding,
            'filterType' => $this->filterType,
            'filterProperty' => $this->filterProperty,
            'filterCustomer' => $this->filterCustomer,
            'filterOwnership' => $this->filterOwnership,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'search' => $this->search,
        ];

        return Excel::download(
            new ServiceChargeExport($filters),
            'sale-service-charge-report-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    public function resetFilters(): void
    {
        parent::resetFilters();
        $this->js("
            ['svc_filterGroup', 'svc_filterBuilding', 'svc_filterProperty', 'svc_filterCustomer'].forEach(id => {
                const el = document.getElementById(id);
                if (el && el.tomSelect) { el.tomSelect.clear(); }
            });
        ");
    }

    public function render()
    {
        return view('livewire.rent-out.report.service-charge-table', [
            'data' => $this->buildQuery()->paginate($this->limit),
            'kpis' => $this->kpis,
            'summary' => $this->summary,
            ...$this->getFilterData(),
        ]);
    }
}

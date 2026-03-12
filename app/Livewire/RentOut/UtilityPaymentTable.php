<?php

namespace App\Livewire\RentOut;

use App\Exports\RentOut\UtilityExport;
use App\Livewire\RentOut\Concerns\HasRentOutReportFilters;
use App\Models\RentOutUtilityTerm;
use App\Models\Utility;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class UtilityPaymentTable extends Component
{
    use HasRentOutReportFilters, WithPagination;

    protected $paginationTheme = 'bootstrap';

    // Utility-specific filters
    public $filterUtility = '';

    public $filterPaidStatus = 'pending'; // 'pending', 'paid', ''

    public function getDefaultColumns(): array
    {
        return ['date', 'customer', 'group', 'building', 'property', 'ownership', 'utility', 'amount', 'paid', 'balance'];
    }

    protected function getSelectableIds(): array
    {
        return $this->buildQuery()->limit(2000)->pluck('rent_out_utility_terms.id')->toArray();
    }

    protected function buildQuery(): Builder
    {
        return RentOutUtilityTerm::query()
            ->with(['rentOut.customer', 'rentOut.property', 'rentOut.building', 'rentOut.group', 'utility'])
            ->tap(fn ($q) => $this->applyRentOutFilters($q))
            ->tap(fn ($q) => $this->applyDateFilter($q, 'date'))
            ->tap(fn ($q) => $this->applySearch($q))
            ->when($this->filterUtility, fn ($q, $v) => $q->where('utility_id', $v))
            ->when($this->filterPaidStatus, function ($q, $value) {
                return match ($value) {
                    'pending' => $q->where('balance', '>', 0),
                    'paid' => $q->where('balance', '<=', 0),
                    default => $q,
                };
            })
            ->orderBy($this->sortField === 'id' ? 'rent_out_utility_terms.id' : $this->sortField, $this->sortDirection);
    }

    /**
     * Summary grouped by utility type.
     */
    public function getSummaryProperty(): array
    {
        $baseQuery = RentOutUtilityTerm::query()
            ->tap(fn ($q) => $this->applyRentOutFilters($q))
            ->tap(fn ($q) => $this->applyDateFilter($q, 'date'));

        $utilities = Utility::orderBy('name')->get();
        $summary = [];

        foreach ($utilities as $utility) {
            $row = (clone $baseQuery)->where('utility_id', $utility->id)
                ->selectRaw('COALESCE(SUM(amount), 0) as total_amount, COALESCE(SUM(paid), 0) as total_paid, COALESCE(SUM(balance), 0) as total_balance')
                ->first();

            if (($row->total_amount ?? 0) > 0 || ($row->total_paid ?? 0) > 0) {
                $summary[] = [
                    'name' => $utility->name,
                    'amount' => $row->total_amount ?? 0,
                    'paid' => $row->total_paid ?? 0,
                    'balance' => $row->total_balance ?? 0,
                ];
            }
        }

        // Grand total
        $grand = (clone $baseQuery)
            ->selectRaw('COALESCE(SUM(amount), 0) as total_amount, COALESCE(SUM(paid), 0) as total_paid, COALESCE(SUM(balance), 0) as total_balance')
            ->first();

        $summary[] = [
            'name' => 'Total',
            'amount' => $grand->total_amount ?? 0,
            'paid' => $grand->total_paid ?? 0,
            'balance' => $grand->total_balance ?? 0,
        ];

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
            'filterUtility' => $this->filterUtility,
            'filterPaidStatus' => $this->filterPaidStatus,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'search' => $this->search,
        ];

        return Excel::download(new UtilityExport($filters), 'utility-payments-'.now()->format('Y-m-d').'.xlsx');
    }

    public function paySelected(): void
    {
        if (empty($this->selected)) {
            $this->dispatch('error', ['message' => 'Please select utility terms to pay.']);

            return;
        }

        $this->dispatch('open-utility-pay-selected-modal', ids: $this->selected);
    }

    public function resetFilters(): void
    {
        $this->filterUtility = '';
        $this->filterPaidStatus = 'pending';
        parent::resetFilters();
    }

    public function render()
    {
        return view('livewire.rent-out.utility-payment-table', [
            'data' => $this->buildQuery()->paginate($this->limit),
            'summary' => $this->summary,
            'utilities' => Utility::orderBy('name')->pluck('name', 'id'),
            ...$this->getFilterData(),
        ]);
    }
}

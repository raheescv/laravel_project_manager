<?php

namespace App\Livewire\RentOut;

use App\Enums\RentOut\AgreementType;
use App\Enums\RentOut\ChequeStatus;
use App\Exports\RentOut\ChequeExport;
use App\Livewire\RentOut\Concerns\HasRentOutReportFilters;
use App\Models\Property;
use App\Models\RentOutCheque;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class ChequeManagementTable extends Component
{
    use HasRentOutReportFilters, WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $agreementType = 'rental';

    // Cheque-specific filter
    public array $filterStatus = ['uncleared', 'submitted'];

    public function mount(string $agreementType = 'rental'): void
    {
        $this->agreementType = $agreementType;
    }

    public function getDefaultColumns(): array
    {
        return ['date', 'customer', 'building', 'property', 'bank', 'cheque_no', 'amount', 'status'];
    }

    protected function getSelectableIds(): array
    {
        return $this->buildQuery()->limit(2000)->pluck('rent_out_cheques.id')->toArray();
    }

    protected function buildQuery(): Builder
    {
        $agreementTypeEnum = AgreementType::from($this->agreementType);

        return RentOutCheque::query()
            ->with(['rentOut.customer', 'rentOut.property', 'rentOut.building'])
            ->whereHas('rentOut', fn ($q) => $q->where('agreement_type', $agreementTypeEnum))
            ->tap(fn ($q) => $this->applyRentOutFilters($q))
            ->tap(fn ($q) => $this->applyDateFilter($q, 'date'))
            ->tap(fn ($q) => $this->applySearch($q))
            ->when(!empty($this->filterStatus), fn ($q) => $q->whereIn('status', $this->filterStatus))
            ->orderBy($this->sortField === 'id' ? 'rent_out_cheques.id' : $this->sortField, $this->sortDirection);
    }

    public function openStatusModal(): void
    {
        if (empty($this->selected)) {
            $this->dispatch('error', ['message' => 'Please select cheques to update.']);

            return;
        }

        $this->dispatch('open-cheque-status-modal', selectedIds: $this->selected);
    }

    #[On('cheque-table-refresh')]
    public function refreshTable(): void
    {
        $this->selected = [];
        $this->selectAll = false;
    }

    public function deselectAll(): void
    {
        $this->selected = [];
        $this->selectAll = false;
    }

    public function download()
    {
        $filters = [
            'agreementType' => $this->agreementType,
            'filterGroup' => $this->filterGroup,
            'filterBuilding' => $this->filterBuilding,
            'filterType' => $this->filterType,
            'filterProperty' => $this->filterProperty,
            'filterCustomer' => $this->filterCustomer,
            'filterOwnership' => $this->filterOwnership,
            'filterStatus' => $this->filterStatus,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'search' => $this->search,
        ];

        return Excel::download(new ChequeExport($filters), 'cheque-management-'.now()->format('Y-m-d').'.xlsx');
    }

    public function resetFilters(): void
    {
        $this->filterStatus = ['uncleared', 'submitted'];
        $this->filterGroup = '';
        $this->filterBuilding = '';
        $this->filterType = '';
        $this->filterProperty = '';
        $this->filterCustomer = '';
        $this->filterOwnership = '';
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->endOfMonth()->format('Y-m-d');
        $this->search = '';
        $this->resetPage();
        $this->js("
            ['cheque_filterGroup', 'cheque_filterBuilding', 'cheque_filterProperty', 'cheque_filterCustomer', 'ownership'].forEach(id => {
                const el = document.getElementById(id);
                if (el && el.tomselect) { el.tomselect.clear(); }
            });
            var statusEl = document.getElementById('cheque_filterStatus');
            if (statusEl && statusEl.tomselect) {
                statusEl.tomselect.setValue(['uncleared', 'submitted']);
            }
        ");
    }

    public function render()
    {
        $ownership = Property::pluck('ownership','ownership')->toArray();
        return view('livewire.rent-out.cheque-management-table', [
            'data' => $this->buildQuery()->paginate($this->limit),
            'chequeStatuses' => ChequeStatus::cases(),
            'ownerships' => $ownership,
            ...$this->getFilterData(),
        ]);
    }
}

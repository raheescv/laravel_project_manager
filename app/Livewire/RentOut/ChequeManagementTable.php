<?php

namespace App\Livewire\RentOut;

use App\Actions\RentOut\Cheque\UpdateStatusAction;
use App\Enums\RentOut\AgreementType;
use App\Enums\RentOut\ChequeStatus;
use App\Exports\RentOut\ChequeExport;
use App\Livewire\RentOut\Concerns\HasRentOutReportFilters;
use App\Models\RentOutCheque;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class ChequeManagementTable extends Component
{
    use HasRentOutReportFilters, WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $agreementType = 'rental';

    // Cheque-specific filter
    public $filterStatus = '';

    // Status change modal
    public bool $showStatusModal = false;

    public $statusChangeStatus = '';

    public $statusChangePaymentMethod = '';

    public $statusChangeJournalDate = '';

    public $statusChangeRemark = '';

    public function mount(string $agreementType = 'rental'): void
    {
        $this->agreementType = $agreementType;
        $this->statusChangeJournalDate = now()->format('Y-m-d');
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
            ->when($this->filterStatus, fn ($q, $v) => $q->where('status', $v))
            ->orderBy($this->sortField === 'id' ? 'rent_out_cheques.id' : $this->sortField, $this->sortDirection);
    }

    public function openStatusModal(): void
    {
        if (empty($this->selected)) {
            $this->dispatch('error', ['message' => 'Please select cheques to update.']);

            return;
        }

        $this->statusChangeStatus = ChequeStatus::Cleared->value;
        $this->statusChangePaymentMethod = '';
        $this->statusChangeJournalDate = now()->format('Y-m-d');
        $this->statusChangeRemark = '';
        $this->showStatusModal = true;
        $this->dispatch('open-status-modal');
    }

    public function updateChequeStatus(): void
    {
        $this->validate([
            'statusChangeStatus' => 'required',
        ]);

        try {
            DB::beginTransaction();

            foreach ($this->selected as $id) {
                $cheque = RentOutCheque::findOrFail($id);
                (new UpdateStatusAction())->execute($cheque, [
                    'status' => $this->statusChangeStatus,
                    'payment_method' => $this->statusChangePaymentMethod,
                    'journal_date' => $this->statusChangeJournalDate,
                    'remark' => $this->statusChangeRemark,
                ]);
            }

            DB::commit();
            $this->dispatch('success', ['message' => 'Successfully updated '.count($this->selected).' cheque(s).']);
            $this->showStatusModal = false;
            $this->selected = [];
            $this->selectAll = false;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
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
        $this->filterStatus = '';
        parent::resetFilters();
        $this->js("
            ['cheque_filterGroup', 'cheque_filterBuilding', 'cheque_filterProperty', 'cheque_filterCustomer'].forEach(id => {
                const el = document.getElementById(id);
                if (el && el.tomSelect) { el.tomSelect.clear(); }
            });
        ");
    }

    /**
     * Get selected cheques info for the modal display.
     */
    public function getSelectedChequesProperty(): array
    {
        if (empty($this->selected)) {
            return [];
        }

        return RentOutCheque::with('rentOut.customer')
            ->whereIn('id', $this->selected)
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'customer' => $c->rentOut?->customer?->name,
                'cheque_no' => $c->cheque_no,
            ])
            ->toArray();
    }

    public function render()
    {
        return view('livewire.rent-out.cheque-management-table', [
            'data' => $this->buildQuery()->paginate($this->limit),
            'chequeStatuses' => ChequeStatus::cases(),
            ...$this->getFilterData(),
        ]);
    }
}

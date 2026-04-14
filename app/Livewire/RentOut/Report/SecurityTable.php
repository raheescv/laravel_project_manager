<?php

namespace App\Livewire\RentOut\Report;

use App\Enums\RentOut\PaymentMode;
use App\Enums\RentOut\SecurityStatus;
use App\Enums\RentOut\SecurityType;
use App\Exports\RentOut\SecurityExport;
use App\Livewire\RentOut\Concerns\HasRentOutReportFilters;
use App\Models\RentOutSecurity;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class SecurityTable extends Component
{
    use HasRentOutReportFilters, WithPagination;

    protected $paginationTheme = 'bootstrap';

    // Security-specific filters
    public $filterSecurityType = '';

    public $filterPaymentMethod = '';

    public $filterSecurityStatus = '';

    public function getDefaultColumns(): array
    {
        return ['customer', 'group', 'building', 'type', 'property', 'security_type', 'payment_method', 'cheque_no', 'bank', 'due_date', 'amount', 'status'];
    }

    protected function buildQuery(): Builder
    {
        return RentOutSecurity::query()
            ->with(['rentOut.customer', 'rentOut.property', 'rentOut.building', 'rentOut.group', 'rentOut.type'])
            ->tap(fn ($q) => $this->applyRentOutFilters($q))
            ->tap(fn ($q) => $this->applyDateFilter($q, 'due_date'))
            ->tap(fn ($q) => $this->applySearch($q))
            ->when($this->filterSecurityType, fn ($q, $v) => $q->where('type', $v))
            ->when($this->filterPaymentMethod, fn ($q, $v) => $q->where('payment_mode', $v))
            ->when($this->filterSecurityStatus, fn ($q, $v) => $q->where('status', $v))
            ->orderBy($this->sortField === 'id' ? 'rent_out_securities.id' : $this->sortField, $this->sortDirection);
    }

    /**
     * Summary cards: Total, Overdue, Paid amounts.
     */
    public function getSummaryCardsProperty(): array
    {
        $baseQuery = RentOutSecurity::query()
            ->tap(fn ($q) => $this->applyRentOutFilters($q))
            ->tap(fn ($q) => $this->applyDateFilter($q, 'due_date'));

        $totalAmount = (clone $baseQuery)->sum('amount');
        $overdueAmount = (clone $baseQuery)
            ->where('status', SecurityStatus::Pending)
            ->where('due_date', '<', now())
            ->sum('amount');
        $paidAmount = (clone $baseQuery)
            ->whereIn('status', [SecurityStatus::Collected, SecurityStatus::Returned])
            ->sum('amount');

        return [
            'total' => $totalAmount,
            'overdue' => $overdueAmount,
            'paid' => $paidAmount,
        ];
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
            'filterSecurityType' => $this->filterSecurityType,
            'filterPaymentMethod' => $this->filterPaymentMethod,
            'filterSecurityStatus' => $this->filterSecurityStatus,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'search' => $this->search,
        ];

        return Excel::download(new SecurityExport($filters), 'security-report-'.now()->format('Y-m-d').'.xlsx');
    }

    public function resetFilters(): void
    {
        $this->filterSecurityType = '';
        $this->filterPaymentMethod = '';
        $this->filterSecurityStatus = '';
        parent::resetFilters();
        $this->js("
            ['security_filterGroup', 'security_filterBuilding', 'security_filterProperty', 'security_filterCustomer'].forEach(id => {
                const el = document.getElementById(id);
                if (el && el.tomSelect) { el.tomSelect.clear(); }
            });
        ");
    }

    public function render()
    {
        return view('livewire.rent-out.report.security-table', [
            'data' => $this->buildQuery()->paginate($this->limit),
            'summaryCards' => $this->summaryCards,
            'securityTypes' => SecurityType::cases(),
            'securityStatuses' => SecurityStatus::cases(),
            'paymentModes' => PaymentMode::cases(),
            ...$this->getFilterData(),
        ]);
    }
}

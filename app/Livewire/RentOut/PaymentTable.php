<?php

namespace App\Livewire\RentOut;

use App\Enums\RentOut\AgreementType;
use App\Enums\RentOut\PaymentMode;
use App\Exports\RentOut\PaymentExport;
use App\Livewire\RentOut\Concerns\HasRentOutReportFilters;
use App\Models\RentOutPaymentTerm;
use App\Support\RentOutConfig;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class PaymentTable extends Component
{
    use HasRentOutReportFilters, WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $agreementType = 'rental';

    // Extra payment-specific filters
    public $filterSalesman = '';

    public $filterPaymentMode = '';

    public $filterPaymentStatus = 'pending';

    // Quick filter mode
    public $quickFilterMode = ''; // '', 'overdue', 'advanced'

    public function mount(string $agreementType = 'rental'): void
    {
        $this->agreementType = $agreementType;
    }

    public function getConfigProperty(): RentOutConfig
    {
        return RentOutConfig::make($this->agreementType);
    }

    public function getDefaultColumns(): array
    {
        return ['date', 'customer', 'salesman', 'group', 'building', 'property', 'ownership', 'payment_mode', 'amount', 'paid', 'balance'];
    }

    protected function getSelectableIds(): array
    {
        return $this->buildQuery()->limit(2000)->pluck('rent_out_payment_terms.id')->toArray();
    }

    protected function buildQuery(): Builder
    {
        $agreementTypeEnum = AgreementType::from($this->agreementType);

        return RentOutPaymentTerm::query()
            ->with(['rentOut.customer', 'rentOut.property', 'rentOut.building', 'rentOut.group', 'rentOut.salesman'])
            ->whereHas('rentOut', fn ($q) => $q->where('agreement_type', $agreementTypeEnum))
            ->tap(fn ($q) => $this->applyRentOutFilters($q))
            ->tap(fn ($q) => $this->applyDateFilter($q, 'due_date'))
            ->tap(fn ($q) => $this->applySearch($q))
            ->when($this->filterSalesman, function ($q, $value) {
                return $q->whereHas('rentOut', fn ($r) => $r->where('salesman_id', $value));
            })
            ->when($this->filterPaymentMode, function ($q, $value) {
                return $q->where('payment_mode', $value);
            })
            ->when($this->filterPaymentStatus, function ($q, $value) {
                return match ($value) {
                    'pending' => $q->where('status', 'pending'),
                    'paid' => $q->where('status', 'paid'),
                    'overdue' => $q->where('status', 'pending')->where('due_date', '<', now()),
                    default => $q,
                };
            })
            ->when($this->quickFilterMode === 'overdue', function ($q) {
                return $q->where('status', 'pending')->where('due_date', '<', now());
            })
            ->orderBy($this->sortField === 'id' ? 'rent_out_payment_terms.id' : $this->sortField, $this->sortDirection);
    }

    public function quickFilter(string $mode = ''): void
    {
        $this->quickFilterMode = $mode;
        $this->resetPage();
    }

    /**
     * Payment statistics summary grouped by payment mode.
     */
    public function getStatisticsProperty(): array
    {
        $agreementTypeEnum = AgreementType::from($this->agreementType);
        $baseQuery = RentOutPaymentTerm::query()
            ->whereHas('rentOut', fn ($q) => $q->where('agreement_type', $agreementTypeEnum))
            ->tap(fn ($q) => $this->applyRentOutFilters($q))
            ->tap(fn ($q) => $this->applyDateFilter($q, 'due_date'));

        $stats = [];
        foreach (PaymentMode::cases() as $mode) {
            $row = (clone $baseQuery)->where('payment_mode', $mode->value)
                ->selectRaw('COALESCE(SUM(total), 0) as total_amount, COALESCE(SUM(paid), 0) as amount_paid, COALESCE(SUM(balance), 0) as balance_due')
                ->first();

            $stats[] = [
                'mode' => $mode->label(),
                'color' => match ($mode) {
                    PaymentMode::Cash => 'primary',
                    PaymentMode::Cheque => 'info',
                    PaymentMode::Pos => 'success',
                    PaymentMode::BankTransfer => 'warning',
                },
                'total_amount' => $row->total_amount ?? 0,
                'amount_paid' => $row->amount_paid ?? 0,
                'balance_due' => $row->balance_due ?? 0,
            ];
        }

        $grandTotal = (clone $baseQuery)
            ->selectRaw('COALESCE(SUM(total), 0) as total_amount, COALESCE(SUM(paid), 0) as amount_paid, COALESCE(SUM(balance), 0) as balance_due')
            ->first();

        $stats[] = [
            'mode' => 'GRAND TOTAL',
            'color' => 'dark',
            'total_amount' => $grandTotal->total_amount ?? 0,
            'amount_paid' => $grandTotal->amount_paid ?? 0,
            'balance_due' => $grandTotal->balance_due ?? 0,
        ];

        return $stats;
    }

    /**
     * Overdue payment alert data.
     */
    public function getOverdueAlertProperty(): array
    {
        $agreementTypeEnum = AgreementType::from($this->agreementType);
        $baseQuery = RentOutPaymentTerm::query()
            ->whereHas('rentOut', fn ($q) => $q->where('agreement_type', $agreementTypeEnum))
            ->tap(fn ($q) => $this->applyRentOutFilters($q))
            ->tap(fn ($q) => $this->applyDateFilter($q, 'due_date'));

        $overdueCount = (clone $baseQuery)->where('status', 'pending')->where('due_date', '<', now())->count();
        $overdueAmount = (clone $baseQuery)->where('status', 'pending')->where('due_date', '<', now())->sum('balance');
        $totalCount = (clone $baseQuery)->count();
        $onTimeCount = (clone $baseQuery)->where('status', 'paid')->count();
        $overduePercentage = $totalCount > 0 ? round(($overdueCount / $totalCount) * 100, 1) : 0;

        return [
            'overdue_count' => $overdueCount,
            'overdue_amount' => $overdueAmount,
            'overdue_percentage' => $overduePercentage,
            'on_time_count' => $onTimeCount,
        ];
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
            'filterSalesman' => $this->filterSalesman,
            'filterPaymentMode' => $this->filterPaymentMode,
            'filterPaymentStatus' => $this->filterPaymentStatus,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'search' => $this->search,
        ];

        return Excel::download(new PaymentExport($filters), 'payment-report-'.now()->format('Y-m-d').'.xlsx');
    }

    public function paySelected(): void
    {
        if (empty($this->selected)) {
            $this->dispatch('error', ['message' => 'Please select payment terms to pay.']);

            return;
        }

        $this->dispatch('paySelectedTermsFromJS', ids: $this->selected);
    }

    public function resetFilters(): void
    {
        $this->filterSalesman = '';
        $this->filterPaymentMode = '';
        $this->filterPaymentStatus = 'pending';
        $this->quickFilterMode = '';
        parent::resetFilters();
    }

    public function render()
    {
        return view('livewire.rent-out.payment-table', [
            'data' => $this->buildQuery()->paginate($this->limit),
            'config' => $this->config,
            'statistics' => $this->statistics,
            'overdueAlert' => $this->overdueAlert,
            'paymentModes' => PaymentMode::cases(),
            ...$this->getFilterData(),
        ]);
    }
}

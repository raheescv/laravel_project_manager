<?php

namespace App\Livewire\RentOut\Tabs;

use App\Actions\RentOut\Payment\ReverseTransactionAction;
use App\Enums\RentOut\AgreementType;
use App\Models\RentOut;
use App\Models\RentOutTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class PaymentTab extends Component
{
    public $rentOutId;

    // Filters
    public $filterSource = '';

    public $filterCategory = '';

    public $filterPaymentMode = '';

    public $filterDateFrom = '';

    public $filterDateTo = '';

    // Sorting
    public $sortField = 'date';

    public $sortDirection = 'desc';

    // Selection
    public $selectedIds = [];

    public $selectAll = false;

    public function mount($rentOutId)
    {
        $this->rentOutId = $rentOutId;
    }

    #[On('rent-out-updated')]
    public function refresh() {}

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedIds = $this->getFilteredPayments()->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        } else {
            $this->selectedIds = [];
        }
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function resetFilters()
    {
        $this->filterSource = '';
        $this->filterCategory = '';
        $this->filterPaymentMode = '';
        $this->filterDateFrom = '';
        $this->filterDateTo = '';
    }

    public function deleteSelected()
    {
        // No dedicated payment-delete permission in config/permissions.php; gate destructive payment removal with the agreement 'payment' capability.
        $rentOut = RentOut::find($this->rentOutId);
        abort_unless(Auth::user()?->can($rentOut?->agreement_type === AgreementType::Lease ? 'rent out lease.payment' : 'rent out.payment'), 403);
        if (empty($this->selectedIds)) {
            return;
        }

        $payments = RentOutTransaction::whereIn('id', $this->selectedIds)
            ->where('rent_out_id', $this->rentOutId)
            ->get();

        // Reverse each receipt end-to-end: term paid/status, cheque status, and
        // the journal + entries, then remove the ledger row.
        DB::transaction(function () use ($payments) {
            $reverse = new ReverseTransactionAction();
            $payments->each(fn (RentOutTransaction $payment) => $reverse->reverse($payment));
        });

        $this->selectedIds = [];
        $this->selectAll = false;
        $this->dispatch('rent-out-updated');
    }

    public function deletePayment($id)
    {
        // No dedicated payment-delete permission in config/permissions.php; gate destructive payment removal with the agreement 'payment' capability.
        $rentOut = RentOut::find($this->rentOutId);
        abort_unless(Auth::user()?->can($rentOut?->agreement_type === AgreementType::Lease ? 'rent out lease.payment' : 'rent out.payment'), 403);
        $payment = RentOutTransaction::where('id', $id)
            ->where('rent_out_id', $this->rentOutId)
            ->first();

        if ($payment) {
            // Reverse the receipt end-to-end: term paid/status, cheque status, and
            // the journal + entries, then remove the ledger row.
            DB::transaction(fn () => (new ReverseTransactionAction())->reverse($payment));
        }

        $this->dispatch('rent-out-updated');
    }

    public function editPayment($paymentId)
    {
        $this->dispatch('edit-payout-payment', paymentId: $paymentId);
    }

    public function openPayoutModal()
    {
        $this->dispatch('open-payout-modal', rentOutId: $this->rentOutId);
    }

    public function openTransferModal($paymentId)
    {
        $this->dispatch('open-transfer-modal', paymentId: $paymentId);
    }

    protected function getFilteredPayments()
    {
        $query = RentOutTransaction::with('account')
            ->where('rent_out_id', $this->rentOutId)
            ->where('credit', '>', 0);

        if ($this->filterSource) {
            $query->where('source', $this->filterSource);
        }
        if ($this->filterCategory) {
            $query->where('category', $this->filterCategory);
        }
        if ($this->filterPaymentMode) {
            $query->where('account_id', $this->filterPaymentMode);
        }
        if ($this->filterDateFrom) {
            $query->whereDate('date', '>=', $this->filterDateFrom);
        }
        if ($this->filterDateTo) {
            $query->whereDate('date', '<=', $this->filterDateTo);
        }

        return $query->orderBy($this->sortField, $this->sortDirection)->get();
    }

    public function render()
    {
        $payments = $this->getFilteredPayments();

        // Source summary (receipts only)
        $sourceSummary = RentOutTransaction::where('rent_out_id', $this->rentOutId)
            ->where('credit', '>', 0)
            ->selectRaw('source, SUM(credit) as total_credit, COUNT(*) as count')
            ->groupBy('source')
            ->get();

        // Available filter options (receipts only)
        $sources = RentOutTransaction::where('rent_out_id', $this->rentOutId)
            ->where('credit', '>', 0)
            ->distinct()->pluck('source')->filter()->sort()->values();

        $categories = RentOutTransaction::where('rent_out_id', $this->rentOutId)
            ->where('credit', '>', 0)
            ->distinct()->pluck('category')->filter()->sort()->values();

        $paymentModes = RentOutTransaction::with('account')
            ->where('rent_out_id', $this->rentOutId)
            ->where('credit', '>', 0)
            ->whereNotNull('account_id')
            ->get()
            ->pluck('account.name', 'account_id')
            ->unique()
            ->filter()
            ->sort();

        $rentOut = RentOut::find($this->rentOutId);
        $canTransfer = Auth::user()?->can($rentOut?->agreement_type === AgreementType::Lease ? 'rent out lease.transfer payment' : 'rent out.transfer payment') ?? false;

        return view('livewire.rent-out.tabs.payment-tab', [
            'payments' => $payments,
            'sourceSummary' => $sourceSummary,
            'sources' => $sources,
            'categories' => $categories,
            'paymentModes' => $paymentModes,
            'canTransfer' => $canTransfer,
        ]);
    }
}

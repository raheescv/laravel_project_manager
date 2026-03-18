<?php

namespace App\Livewire\RentOut\Tabs;

use App\Models\Journal;
use App\Models\RentOutTransaction;
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
        if (empty($this->selectedIds)) {
            return;
        }

        $payments = RentOutTransaction::whereIn('id', $this->selectedIds)
            ->where('rent_out_id', $this->rentOutId)
            ->get();

        // Delete associated journals
        $journalIds = $payments->pluck('journal_id')->filter()->unique()->values()->toArray();
        if ($journalIds) {
            Journal::whereIn('id', $journalIds)->delete();
        }

        $payments->each->delete();

        $this->selectedIds = [];
        $this->selectAll = false;
        $this->dispatch('rent-out-updated');
    }

    public function deletePayment($id)
    {
        $payment = RentOutTransaction::where('id', $id)
            ->where('rent_out_id', $this->rentOutId)
            ->first();

        if ($payment) {
            if ($payment->journal_id) {
                Journal::where('id', $payment->journal_id)->delete();
            }
            $payment->delete();
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

        return view('livewire.rent-out.tabs.payment-tab', [
            'payments' => $payments,
            'sourceSummary' => $sourceSummary,
            'sources' => $sources,
            'categories' => $categories,
            'paymentModes' => $paymentModes,
        ]);
    }
}

<?php

namespace App\Livewire\RentOut\Tabs;

use App\Models\Journal;
use App\Models\RentOutPayment;
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

        $payments = RentOutPayment::whereIn('id', $this->selectedIds)
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
        $payment = RentOutPayment::where('id', $id)
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
        $query = RentOutPayment::with('account')
            ->where('rent_out_id', $this->rentOutId);

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

        // Source summary
        $sourceSummary = RentOutPayment::where('rent_out_id', $this->rentOutId)
            ->selectRaw('source, SUM(credit) as total_credit, SUM(debit) as total_debit, COUNT(*) as count')
            ->groupBy('source')
            ->get();

        // Available filter options
        $sources = RentOutPayment::where('rent_out_id', $this->rentOutId)
            ->distinct()->pluck('source')->filter()->sort()->values();

        $categories = RentOutPayment::where('rent_out_id', $this->rentOutId)
            ->distinct()->pluck('category')->filter()->sort()->values();

        $paymentModes = RentOutPayment::with('account')
            ->where('rent_out_id', $this->rentOutId)
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

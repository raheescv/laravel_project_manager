<?php

namespace App\Livewire\RentOut\Tabs;

use App\Enums\RentOut\AgreementType;
use App\Enums\RentOut\ChequeStatus;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\RentOut;
use App\Models\RentOutCheque;
use App\Models\RentOutPaymentTerm;
use App\Models\RentOutTransaction;
use Illuminate\Support\Facades\Auth;
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

        // Roll back the payment term (paid → balance) and cheque status for each receipt.
        $payments->each(fn ($payment) => $this->rollbackPaymentSideEffects($payment));

        // Delete associated journals AND their entries (entries drive balances).
        $journalIds = $payments->pluck('journal_id')->filter()->unique()->values()->toArray();
        if ($journalIds) {
            JournalEntry::whereIn('journal_id', $journalIds)->delete();
            Journal::whereIn('id', $journalIds)->delete();
        }

        $payments->each->delete();

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
            // Roll back the payment term (paid → balance) and cheque status first.
            $this->rollbackPaymentSideEffects($payment);

            if ($payment->journal_id) {
                JournalEntry::where('journal_id', $payment->journal_id)->delete();
                Journal::where('id', $payment->journal_id)->delete();
            }
            $payment->delete();
        }

        $this->dispatch('rent-out-updated');
    }

    /**
     * Reverse the side effects a receipt applied when it was recorded:
     *  - deduct its amount from the payment term's paid total (paid → balance)
     *    and flip the term back to pending when a balance remains;
     *  - reset a cheque-clearance cheque back to uncleared (unpaid).
     */
    protected function rollbackPaymentSideEffects(RentOutTransaction $payment): void
    {
        // Only receipts (money IN) affect term/cheque state; skip payouts.
        $amount = (float) $payment->credit;
        if ($amount <= 0) {
            return;
        }

        // Resolve the payment term this receipt paid. Direct term payments point
        // at RentOutPaymentTerm; cheque clearances point at RentOutCheque but keep
        // the term in source_id.
        $term = null;
        if ($payment->model === 'RentOutPaymentTerm' && $payment->model_id) {
            $term = RentOutPaymentTerm::find($payment->model_id);
        } elseif ($payment->source === 'PaymentTerm' && $payment->source_id) {
            $term = RentOutPaymentTerm::find($payment->source_id);
        }

        if ($term) {
            $term->paid = max(0, (float) $term->paid - $amount);
            if ($term->paid <= 0) {
                $term->paid_date = null;
            }
            // The model's saving hook only flips status TO paid; force it back to
            // pending when the term is no longer fully covered.
            if ($term->paid < (float) $term->total) {
                $term->status = 'pending';
            }
            $term->save();
        }

        // A cheque-clearance receipt marked the cheque cleared — revert to uncleared.
        if ($payment->model === 'RentOutCheque' && $payment->model_id) {
            RentOutCheque::where('id', $payment->model_id)
                ->update(['status' => ChequeStatus::Uncleared->value]);
        }
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

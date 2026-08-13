<?php

namespace App\Livewire\RentOut\Tabs;

use App\Models\Account;
use App\Models\RentOut;
use App\Models\RentOutPaymentTerm;
use App\Models\RentOutTransaction;
use App\Models\RentOutUtilityTerm;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

class TransactionsTab extends Component
{
    public $rentOutId;

    public $sortField = 'date';

    public $sortDirection = 'asc';

    public function mount($rentOutId)
    {
        $this->rentOutId = $rentOutId;
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

    #[On('rent-out-updated')]
    public function refresh() {}

    public function render()
    {
        $ledger = $this->ledger();

        return view('livewire.rent-out.tabs.transactions-tab', [
            'entries' => $this->sortForDisplay($ledger),
            'totalDebit' => $ledger->sum('debit'),
            'totalCredit' => $ledger->sum('credit'),
        ]);
    }

    /**
     * Full statement for the agreement: money movements recorded against the
     * rent out, plus the charges that have fallen due but are only held as
     * terms in this system (rent and utilities accrue when their due date
     * passes, they are not written as transactions).
     *
     * Rows carry a running balance calculated in date order - debit less
     * credit, so a positive balance is what the customer still owes.
     */
    protected function ledger(): Collection
    {
        $entries = $this->recordedTransactions()
            ->concat($this->accruedRentCharges())
            ->concat($this->accruedUtilityCharges())
            ->sortBy([['date', 'asc'], ['debit', 'desc']])
            ->values();

        $balance = 0;

        return $entries->map(function (array $entry) use (&$balance): array {
            $balance += $entry['debit'] - $entry['credit'];

            return $entry + ['balance' => $balance];
        });
    }

    protected function recordedTransactions(): Collection
    {
        $transactions = RentOutTransaction::with('account')
            ->where('rent_out_id', $this->rentOutId)
            ->get();

        $categoryNames = $this->categoryNames($transactions);
        $customerAccountId = $this->customerAccountId();

        return $transactions->map(fn (RentOutTransaction $transaction): array => [
            'date' => $transaction->date,
            'category' => $categoryNames[$transaction->category]
                ?? ($transaction->category ?: $transaction->group),
            // A charge is billed to the customer's own account, so there is no
            // payment mode to show until it is collected.
            'payment_mode' => (int) $transaction->account_id === $customerAccountId
                ? null
                : $transaction->account?->name,
            'debit' => (float) $transaction->debit,
            'credit' => (float) $transaction->credit,
            'remark' => $transaction->remark,
        ]);
    }

    /**
     * The account the agreement bills to — the customer's ledger account.
     */
    protected function customerAccountId(): ?int
    {
        $accountId = RentOut::whereKey($this->rentOutId)->value('account_id');

        return $accountId ? (int) $accountId : null;
    }

    /**
     * Service charges store their category as an account id — the modal's
     * category select is an account picker — so those rows would otherwise
     * show a bare number. Resolved in one query, keyed by the stored value.
     */
    protected function categoryNames(Collection $transactions): array
    {
        $accountIds = $transactions
            ->pluck('category')
            ->filter(fn ($category) => is_numeric($category))
            ->unique();

        if ($accountIds->isEmpty()) {
            return [];
        }

        return Account::whereIn('id', $accountIds)->pluck('name', 'id')->all();
    }

    protected function accruedRentCharges(): Collection
    {
        $vacateDate = $this->vacateDate();

        return RentOutPaymentTerm::where('rent_out_id', $this->rentOutId)
            ->whereDate('due_date', '<=', today())
            // Once the agreement is vacated it stops accruing rent, so terms
            // scheduled after that date must not show as "Rent due" charges.
            ->when($vacateDate, fn ($query) => $query->whereDate('due_date', '<=', $vacateDate))
            ->where('total', '!=', 0)
            ->get()
            ->map(fn (RentOutPaymentTerm $term): array => [
                'date' => $term->due_date,
                'category' => 'Rent',
                'payment_mode' => null,
                'debit' => (float) $term->total,
                'credit' => 0.0,
                'remark' => $term->label ?: 'Rent due '.$term->due_date?->format('d-m-Y'),
            ]);
    }

    protected function accruedUtilityCharges(): Collection
    {
        return RentOutUtilityTerm::with('utility')
            ->where('rent_out_id', $this->rentOutId)
            ->whereDate('date', '<=', today())
            ->get()
            ->map(fn (RentOutUtilityTerm $term): array => [
                'date' => $term->date,
                'category' => 'Utility',
                'payment_mode' => null,
                'debit' => (float) $term->amount,
                'credit' => 0.0,
                'remark' => $term->utility?->name ?: 'Utility due '.$term->date?->format('d-m-Y'),
            ]);
    }

    /**
     * The date the tenant vacated, or null while the agreement is still running.
     * Used to stop rent accruing past the vacate.
     */
    protected function vacateDate()
    {
        return RentOut::whereKey($this->rentOutId)->value('vacate_date');
    }

    protected function sortForDisplay(Collection $ledger): Collection
    {
        $sorted = $ledger->sortBy(
            $this->sortField,
            SORT_REGULAR,
            $this->sortDirection === 'desc'
        );

        return $sorted->values();
    }
}

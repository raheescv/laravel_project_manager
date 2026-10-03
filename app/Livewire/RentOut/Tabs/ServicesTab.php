<?php

namespace App\Livewire\RentOut\Tabs;

use App\Models\Account;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\RentOutTransaction;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class ServicesTab extends Component
{
    public $rentOutId;

    public $sortField = 'date';

    public $sortDirection = 'desc';

    public array $selectedPayments = [];

    public bool $selectAll = false;

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
    public function refresh()
    {
        $this->selectedPayments = [];
        $this->selectAll = false;
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedPayments = RentOutTransaction::where('rent_out_id', $this->rentOutId)
                ->whereIn('source', ['Service', 'ServiceCharge'])
                ->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->toArray();
        } else {
            $this->selectedPayments = [];
        }
    }

    public function openServiceModal()
    {
        $this->dispatch('open-service-modal', rentOutId: $this->rentOutId);
    }

    public function openServiceChargeModal()
    {
        $this->dispatch('open-service-charge-modal', rentOutId: $this->rentOutId);
    }

    public function openServicePaymentModal()
    {
        $this->dispatch('open-service-payment-modal', rentOutId: $this->rentOutId);
    }

    public function editPayment($paymentId)
    {
        $this->dispatch('edit-service-payment', paymentId: $paymentId);
    }

    public function printReceipt($paymentId)
    {
        $payment = RentOutTransaction::query()
            ->where('rent_out_id', $this->rentOutId)
            ->findOrFail($paymentId, ['id', 'credit']);

        $url = $payment->credit > 0
            ? route('print::rentout::payment-receipt', $payment->id)
            : route('print::rentout::payment-voucher', $payment->id);
        $this->dispatch('open-receipt-tab', url: $url);
    }

    public function deleteSelected()
    {
        abort_unless(auth()->user()?->can('rent out service.delete'), 403);
        if (empty($this->selectedPayments)) {
            $this->dispatch('error', message: 'No payments selected.');

            return;
        }

        $this->deleteServiceTransactions($this->selectedPayments);

        $this->selectedPayments = [];
        $this->selectAll = false;
        $this->dispatch('rent-out-updated');
        $this->dispatch('success', message: 'Selected service payments deleted.');
    }

    public function deletePayment(int $id): void
    {
        abort_unless(auth()->user()?->can('rent out service.delete'), 403);

        if (! $this->deleteServiceTransactions([$id])) {
            $this->dispatch('error', message: 'Payment not found.');

            return;
        }

        $this->selectedPayments = array_values(array_diff($this->selectedPayments, [(string) $id]));
        $this->dispatch('rent-out-updated');
        $this->dispatch('success', message: 'Service payment deleted.');
    }

    /**
     * Delete this agreement's service rows together with their journals and
     * journal entries (entries drive balances).
     *
     * @param  array<int, int|string>  $ids
     */
    protected function deleteServiceTransactions(array $ids): int
    {
        $payments = RentOutTransaction::whereIn('id', $ids)
            ->where('rent_out_id', $this->rentOutId)
            ->whereIn('source', ['Service', 'ServiceCharge'])
            ->get();

        DB::transaction(function () use ($payments): void {
            $journalIds = $payments->pluck('journal_id')->filter()->unique()->values()->toArray();
            if ($journalIds) {
                JournalEntry::whereIn('journal_id', $journalIds)->delete();
                Journal::whereIn('id', $journalIds)->delete();
            }

            $payments->each->delete();
        });

        return $payments->count();
    }

    public function render()
    {
        $servicePayments = RentOutTransaction::with('account')
            ->where('rent_out_id', $this->rentOutId)
            ->whereIn('source', ['Service', 'ServiceCharge'])
            ->orderBy($this->sortField, $this->sortDirection)
            ->get();

        // Resolve category IDs to account names
        $categoryIds = $servicePayments->pluck('category')->filter()->unique()->values()->toArray();
        $categoryNames = Account::whereIn('id', $categoryIds)->pluck('name', 'id')->toArray();

        $categorySummary = RentOutTransaction::where('rent_out_id', $this->rentOutId)
            ->whereIn('source', ['Service', 'ServiceCharge'])
            ->selectRaw('category, sum(credit) as credit, sum(debit) as debit')
            ->groupBy('category')
            ->get();

        return view('livewire.rent-out.tabs.services-tab', [
            'servicePayments' => $servicePayments,
            'categorySummary' => $categorySummary,
            'categoryNames' => $categoryNames,
        ]);
    }
}

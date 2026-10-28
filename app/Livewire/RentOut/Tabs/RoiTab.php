<?php

namespace App\Livewire\RentOut\Tabs;

use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\RentOutRoi;
use App\Models\RentOutTransaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class RoiTab extends Component
{
    public $rentOutId;

    public function mount($rentOutId)
    {
        $this->rentOutId = $rentOutId;
    }

    #[On('rent-out-updated')]
    public function refresh() {}

    public function openRoiModal()
    {
        $this->dispatch('open-roi-modal', rentOutId: $this->rentOutId);
    }

    public function openRoiPaymentModal()
    {
        $this->dispatch('open-roi-payment-modal', rentOutId: $this->rentOutId);
    }

    /**
     * ROI rows print as the ROI invoice and payouts as the payment voucher;
     * both are the rent-out voucher document, which picks its title per source.
     */
    public function printDocument(int $transactionId): void
    {
        $transaction = RentOutTransaction::query()
            ->where('rent_out_id', $this->rentOutId)
            ->whereIn('source', [RentOutRoi::SOURCE, RentOutRoi::PAYMENT_SOURCE])
            ->findOrFail($transactionId, ['id']);

        $this->dispatch('open-receipt-tab', url: route('print::rentout::payment-voucher', $transaction->id));
    }

    public function deleteRoi(int $id): void
    {
        abort_unless(auth()->user()?->can('rent out roi.delete'), 403);

        $roi = RentOutRoi::where('rent_out_id', $this->rentOutId)->find($id);
        if (! $roi) {
            $this->dispatch('error', message: 'ROI not found.');

            return;
        }

        // Paid-out ROI must stay covered by what is still booked.
        $totals = RentOutRoi::totalsFor($this->rentOutId);
        if (round($totals['accrued'] - (float) $roi->amount, 2) < $totals['paid']) {
            $this->dispatch('error', message: 'This ROI has already been paid out. Delete the ROI payment first.');

            return;
        }

        DB::transaction(function () use ($roi): void {
            $this->deleteTransactions(RentOutTransaction::where('rent_out_id', $this->rentOutId)
                ->where('source', RentOutRoi::SOURCE)
                ->where('model', 'RentOutRoi')
                ->where('model_id', $roi->id)
                ->get());

            $roi->delete();
        });

        $this->dispatch('rent-out-updated');
        $this->dispatch('success', message: 'ROI deleted.');
    }

    public function deletePayment(int $id): void
    {
        abort_unless(auth()->user()?->can('rent out roi.delete'), 403);

        $payments = RentOutTransaction::where('rent_out_id', $this->rentOutId)
            ->where('source', RentOutRoi::PAYMENT_SOURCE)
            ->whereKey($id)
            ->get();

        if ($payments->isEmpty()) {
            $this->dispatch('error', message: 'Payment not found.');

            return;
        }

        DB::transaction(fn () => $this->deleteTransactions($payments));

        $this->dispatch('rent-out-updated');
        $this->dispatch('success', message: 'ROI payment deleted.');
    }

    /**
     * Delete ledger rows together with their journals and journal entries
     * (entries drive balances).
     *
     * @param  Collection<int, RentOutTransaction>  $transactions
     */
    protected function deleteTransactions(Collection $transactions): void
    {
        $journalIds = $transactions->pluck('journal_id')->filter()->unique()->values()->toArray();
        if ($journalIds) {
            JournalEntry::whereIn('journal_id', $journalIds)->delete();
            Journal::whereIn('id', $journalIds)->delete();
        }

        $transactions->each->delete();
    }

    /**
     * ROI ledger in date order: booked ROI is a credit (owed to the customer),
     * a payout is a debit, and the running balance is what is still payable.
     *
     * @return Collection<int, array{transaction: RentOutTransaction, roi: ?RentOutRoi, balance: float}>
     */
    protected function ledger(): Collection
    {
        $transactions = RentOutTransaction::with('account')
            ->where('rent_out_id', $this->rentOutId)
            ->whereIn('source', [RentOutRoi::SOURCE, RentOutRoi::PAYMENT_SOURCE])
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $rois = RentOutRoi::whereIn('id', $transactions->where('source', RentOutRoi::SOURCE)->pluck('model_id'))
            ->get()
            ->keyBy('id');

        $balance = 0;

        return $transactions->map(function (RentOutTransaction $transaction) use ($rois, &$balance): array {
            $balance = round($balance + $transaction->credit - $transaction->debit, 2);

            return [
                'transaction' => $transaction,
                'roi' => $transaction->source === RentOutRoi::SOURCE ? $rois->get($transaction->model_id) : null,
                'balance' => $balance,
            ];
        });
    }

    public function render()
    {
        return view('livewire.rent-out.tabs.roi-tab', [
            'entries' => $this->ledger(),
            'totals' => RentOutRoi::totalsFor($this->rentOutId),
        ]);
    }
}

<?php

namespace App\Actions\RentOut\Payment;

use App\Actions\Journal\CreateAction as JournalCreateAction;
use App\Actions\RentOut\Payment\Concerns\AppliesPaymentTerms;
use App\Models\RentOut;
use App\Models\RentOutPaymentTerm;
use App\Models\RentOutTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Transfer any amount from one property agreement to another (any customer's).
 * It is a standalone movement, like a payout — not tied to an existing receipt.
 *
 * No cash moves; the value is re-allocated between the two customers with ONE
 * journal:
 *
 *      Dr  Source customer   amount   (their ledger is reduced)
 *      Cr  Target customer   amount
 *
 * and mirrored by two rent_out_transactions rows sharing that journal:
 *   - source agreement: a DEBIT row ("Transfer Out");
 *   - target agreement: a CREDIT row ("Transfer In"), optionally applied to a
 *     target payment term.
 *
 * Both rows carry source = 'Transfer' and payment_type = 'Transfer';
 * ReverseTransactionAction unwinds them as a unit via their shared journal_id.
 */
class TransferTransactionAction
{
    use AppliesPaymentTerms;

    public const SOURCE = 'Transfer';

    public const PAYMENT_TYPE = 'Transfer';

    /**
     * @param  int  $fromRentOutId  the agreement the amount leaves
     * @param  int  $toRentOutId  the agreement the amount goes to
     * @param  float  $amount  how much to transfer
     * @param  int|null  $toTermId  optional target payment term to apply it to
     * @param  string|null  $date  transfer date (Y-m-d); defaults to today
     * @return array{success: bool, message: string, data?: array{from: RentOutTransaction, to: RentOutTransaction, journal_id: int}}
     */
    public function execute(int $fromRentOutId, int $toRentOutId, float $amount, ?int $toTermId = null, string $remark = '', ?string $date = null): array
    {
        try {
            return DB::transaction(function () use ($fromRentOutId, $toRentOutId, $amount, $toTermId, $remark, $date) {
                $fromRentOut = RentOut::with('property')->findOrFail($fromRentOutId);
                $toRentOut = RentOut::with('property')->findOrFail($toRentOutId);
                $amount = round($amount, 2);

                $this->guard($fromRentOut, $toRentOut, $amount);

                $toTerm = $toTermId
                    ? RentOutPaymentTerm::where('rent_out_id', $toRentOut->id)->find($toTermId)
                    : null;

                $date = $date ?: now()->format('Y-m-d');
                $createdBy = Auth::id() ?? $fromRentOut->created_by;

                $fromLabel = $this->agreementLabel($fromRentOut);
                $toLabel = $this->agreementLabel($toRentOut);

                $journalId = $this->postJournal($fromRentOut, $toRentOut, $amount, $date, $remark, $createdBy, $fromLabel, $toLabel);

                $store = new StoreTransactionAction();

                $fromRow = $store->storeContraRow([
                    'rent_out_id' => $fromRentOut->id,
                    'date' => $date,
                    'paid_date' => $date,
                    'debit' => $amount,
                    'credit' => 0,
                    'account_id' => $toRentOut->account_id,
                    'journal_id' => $journalId,
                    'source' => self::SOURCE,
                    'model' => 'RentOut',
                    'model_id' => $toRentOut->id,
                    'group' => 'Transfer Out',
                    'payment_type' => self::PAYMENT_TYPE,
                    'reason' => 'Transferred to '.$toLabel,
                    'remark' => trim(($remark ? $remark.' — ' : '').'Transferred to '.$toLabel),
                    'created_by' => $createdBy,
                ]);
                if (! $fromRow['success']) {
                    throw new \Exception($fromRow['message']);
                }

                $toRow = $store->storeContraRow([
                    'rent_out_id' => $toRentOut->id,
                    'date' => $date,
                    'paid_date' => $date,
                    'due_date' => $toTerm?->due_date?->format('Y-m-d'),
                    'debit' => 0,
                    'credit' => $amount,
                    'account_id' => $fromRentOut->account_id,
                    'journal_id' => $journalId,
                    'source' => self::SOURCE,
                    'source_id' => $fromRow['data']->id,
                    'model' => $toTerm ? 'RentOutPaymentTerm' : 'RentOut',
                    'model_id' => $toTerm?->id ?? $toRentOut->id,
                    'group' => 'Transfer In',
                    'payment_type' => self::PAYMENT_TYPE,
                    'reason' => 'Transferred from '.$fromLabel,
                    'remark' => trim(($remark ? $remark.' — ' : '').'Transferred from '.$fromLabel),
                    'created_by' => $createdBy,
                ]);
                if (! $toRow['success']) {
                    throw new \Exception($toRow['message']);
                }

                $this->creditTerm($toTerm, $amount, $date);

                return [
                    'success' => true,
                    'message' => 'Amount transferred successfully',
                    'data' => ['from' => $fromRow['data'], 'to' => $toRow['data'], 'journal_id' => $journalId],
                ];
            });
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }
    }

    protected function guard(RentOut $fromRentOut, RentOut $toRentOut, float $amount): void
    {
        if ($fromRentOut->id === $toRentOut->id) {
            throw new \RuntimeException('Cannot transfer to the same property.');
        }
        if ($fromRentOut->tenant_id !== $toRentOut->tenant_id) {
            throw new \RuntimeException('Cannot transfer across tenants.');
        }
        if (! $fromRentOut->account_id || ! $toRentOut->account_id) {
            throw new \RuntimeException('Both agreements need a customer account to transfer between.');
        }
        if ($amount <= 0) {
            throw new \RuntimeException('Transfer amount must be greater than zero.');
        }
    }

    /**
     * Dr source customer / Cr target customer — re-allocates the amount with no cash leg.
     */
    protected function postJournal(RentOut $fromRentOut, RentOut $toRentOut, float $amount, string $date, string $remark, int $createdBy, string $fromLabel, string $toLabel): int
    {
        $fromAccount = (int) $fromRentOut->account_id;
        $toAccount = (int) $toRentOut->account_id;
        $narration = 'Amount transferred from '.$fromLabel.' to '.$toLabel;

        $base = ['created_by' => $createdBy, 'remarks' => $remark ?: $narration, 'model' => 'RentOut'];

        $response = (new JournalCreateAction())->execute([
            'tenant_id' => $fromRentOut->tenant_id,
            'branch_id' => $fromRentOut->branch_id,
            'date' => $date,
            'description' => 'Payment Transfer',
            'remarks' => trim(($remark ? $remark.' — ' : '').$narration),
            'source' => $fromRentOut->agreement_type?->sourceSlug() ?? 'rent_out',
            'model' => 'RentOut',
            'model_id' => $fromRentOut->id,
            'created_by' => $createdBy,
            'entries' => [
                array_merge($base, ['account_id' => $fromAccount, 'counter_account_id' => $toAccount, 'debit' => $amount, 'credit' => 0, 'model_id' => $fromRentOut->id, 'branch_id' => $fromRentOut->branch_id]),
                array_merge($base, ['account_id' => $toAccount, 'counter_account_id' => $fromAccount, 'debit' => 0, 'credit' => $amount, 'model_id' => $toRentOut->id, 'branch_id' => $toRentOut->branch_id]),
            ],
        ]);

        if (! $response['success']) {
            throw new \Exception($response['message']);
        }

        return $response['data']->id;
    }

    protected function agreementLabel(RentOut $rentOut): string
    {
        return ($rentOut->agreement_type?->label() ?? 'Agreement').' #'.$rentOut->id
            .($rentOut->property?->number ? ' (Unit '.$rentOut->property->number.')' : '');
    }
}

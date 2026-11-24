<?php

namespace App\Actions\RentOut\Payment;

use App\Actions\Journal\CreateAction as JournalCreateAction;
use App\Actions\RentOut\Payment\Concerns\AppliesPaymentTerms;
use App\Actions\RentOut\Payment\Concerns\ResolvesRentOutAccounts;
use App\Models\RentOut;
use App\Models\RentOutPaymentTerm;
use App\Models\RentOutTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Transfer a received payment — in full or in part — from one property
 * agreement to another (any customer's) without touching the original receipt.
 * A receipt can be split across several transfers until nothing is left.
 *
 * The cash was received once and stays where it is; the transfer only
 * re-allocates that value between agreements. It posts ONE income-to-income
 * journal (no cash leg):
 *
 *      Dr  Rent/Sale Income (source agreement)   amount
 *      Cr  Rent/Sale Income (target agreement)   amount
 *
 * and mirrors it with two rent_out_transactions rows sharing that journal:
 *   - source agreement: a DEBIT row ("Transfer Out") that offsets the receipt;
 *   - target agreement: a CREDIT row ("Transfer In") applying the payment.
 *
 * The source payment term the receipt covered re-opens, and the chosen target
 * term (if any) is credited. Both rows carry source = 'Transfer' and
 * payment_type = 'Transfer'; ReverseTransactionAction unwinds them as a unit
 * via their shared journal_id.
 */
class TransferTransactionAction
{
    use AppliesPaymentTerms;
    use ResolvesRentOutAccounts;

    public const SOURCE = 'Transfer';

    public const PAYMENT_TYPE = 'Transfer';

    /**
     * @param  int  $paymentId  the receipt (rent_out_transactions.id) to transfer
     * @param  int  $toRentOutId  the target RentOut agreement
     * @param  int|null  $toTermId  optional target payment term on the destination
     * @param  float|null  $amount  how much to transfer; null transfers whatever is left
     * @return array{success: bool, message: string, data?: array{from: RentOutTransaction, to: RentOutTransaction, journal_id: int}}
     */
    public function execute(int $paymentId, int $toRentOutId, ?int $toTermId = null, string $remark = '', ?float $amount = null): array
    {
        try {
            return DB::transaction(function () use ($paymentId, $toRentOutId, $toTermId, $remark, $amount) {
                $payment = RentOutTransaction::findOrFail($paymentId);
                $fromRentOut = RentOut::with('property')->findOrFail($payment->rent_out_id);
                $toRentOut = RentOut::with('property')->findOrFail($toRentOutId);

                $amount = round($amount ?? self::transferableAmount($payment), 2);

                $this->guard($payment, $fromRentOut, $toRentOut, $amount);

                $toTerm = $toTermId
                    ? RentOutPaymentTerm::where('rent_out_id', $toRentOut->id)->find($toTermId)
                    : null;

                $date = now()->format('Y-m-d');
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
                    'account_id' => $payment->account_id,
                    'journal_id' => $journalId,
                    'source' => self::SOURCE,
                    'source_id' => $payment->id,
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
                    'account_id' => $payment->account_id,
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

                $this->releaseTerm($this->termFor($payment), $amount);
                $this->creditTerm($toTerm, $amount, $date);

                return [
                    'success' => true,
                    'message' => 'Payment transferred successfully',
                    'data' => ['from' => $fromRow['data'], 'to' => $toRow['data'], 'journal_id' => $journalId],
                ];
            });
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }
    }

    /**
     * What is still left on a receipt to transfer: its amount minus every
     * Transfer Out already posted against it.
     */
    public static function transferableAmount(RentOutTransaction $payment): float
    {
        $transferred = (float) RentOutTransaction::query()
            ->where('rent_out_id', $payment->rent_out_id)
            ->where('source', self::SOURCE)
            ->where('source_id', $payment->id)
            ->where('debit', '>', 0)
            ->sum('debit');

        return round(max(0, (float) $payment->credit - $transferred), 2);
    }

    protected function guard(RentOutTransaction $payment, RentOut $fromRentOut, RentOut $toRentOut, float $amount): void
    {
        if ((float) $payment->credit <= 0) {
            throw new \RuntimeException('Only received payments can be transferred.');
        }
        if ($payment->source !== 'PaymentTerm') {
            throw new \RuntimeException('Only rent (payment term) receipts can be transferred.');
        }
        if ($payment->model === 'RentOutCheque') {
            throw new \RuntimeException('Cheque-based payments cannot be transferred; manage the cheque on the original property instead.');
        }
        if ($fromRentOut->id === $toRentOut->id) {
            throw new \RuntimeException('Cannot transfer a payment to the same property.');
        }
        if ($fromRentOut->tenant_id !== $toRentOut->tenant_id) {
            throw new \RuntimeException('Cannot transfer a payment across tenants.');
        }
        $available = self::transferableAmount($payment);
        if ($available <= 0) {
            throw new \RuntimeException('This payment has already been fully transferred.');
        }
        if ($amount <= 0) {
            throw new \RuntimeException('Transfer amount must be greater than zero.');
        }
        if ($amount > $available + 0.001) {
            throw new \RuntimeException('Transfer amount cannot exceed the '.number_format($available, 2).' left on this payment.');
        }
    }

    /**
     * Dr source income / Cr target income — re-allocates the recognised value
     * between agreements with no cash leg.
     */
    protected function postJournal(RentOut $fromRentOut, RentOut $toRentOut, float $amount, string $date, string $remark, int $createdBy, string $fromLabel, string $toLabel): int
    {
        $fromIncome = $this->incomeAccountId($fromRentOut);
        $toIncome = $this->incomeAccountId($toRentOut);
        $narration = 'Payment transferred from '.$fromLabel.' to '.$toLabel;

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
                array_merge($base, ['account_id' => $fromIncome, 'counter_account_id' => $toIncome, 'debit' => $amount, 'credit' => 0, 'model_id' => $fromRentOut->id, 'branch_id' => $fromRentOut->branch_id]),
                array_merge($base, ['account_id' => $toIncome, 'counter_account_id' => $fromIncome, 'debit' => 0, 'credit' => $amount, 'model_id' => $toRentOut->id, 'branch_id' => $toRentOut->branch_id]),
            ],
        ]);

        if (! $response['success']) {
            throw new \Exception($response['message']);
        }

        return $response['data']->id;
    }

    /**
     * The account a receipt on this agreement was recognised to. Falls back to
     * the customer account so an unconfigured chart still names a real account.
     */
    protected function incomeAccountId(RentOut $rentOut): int
    {
        return (int) ($this->propertyIncomeAccountId($rentOut) ?? $rentOut->account_id);
    }

    protected function agreementLabel(RentOut $rentOut): string
    {
        return ($rentOut->agreement_type?->label() ?? 'Agreement').' #'.$rentOut->id
            .($rentOut->property?->number ? ' (Unit '.$rentOut->property->number.')' : '');
    }
}

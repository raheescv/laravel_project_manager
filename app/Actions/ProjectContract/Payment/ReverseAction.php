<?php

namespace App\Actions\ProjectContract\Payment;

use App\Actions\ProjectContract\Concerns\PostsJournal;
use App\Models\ProjectContractPayment;
use Exception;
use Illuminate\Support\Facades\DB;

class ReverseAction
{
    use PostsJournal;

    /**
     * Undo a payment the ledger already shows: post the contra journal
     * (Dr payment method / Cr Vendor), mark the payment reversed and give the
     * amount back to its terms. The original journal stays for the audit trail.
     *
     * @return array{success: bool, message: string, data?: ProjectContractPayment}
     */
    public function execute(int $id, string $reason, int $userId): array
    {
        try {
            $payment = DB::transaction(function () use ($id, $reason, $userId): ProjectContractPayment {
                $payment = ProjectContractPayment::with(['contract.vendor', 'allocations.term'])->lockForUpdate()->findOrFail($id);
                if ($payment->status !== ProjectContractPayment::STATUS_POSTED) {
                    throw new Exception('This payment is already reversed.');
                }
                if (blank($reason)) {
                    throw new Exception('Give a reason for reversing the payment.');
                }

                $contract = $payment->contract;
                $journal = $this->postPair(
                    $contract,
                    [
                        'date' => today()->toDateString(),
                        'description' => 'Reversal of project contract payment '.$payment->voucher_no.' · '.$contract->reference_no,
                        'model' => 'ProjectContractPayment',
                        'model_id' => $payment->id,
                        'reference_number' => $payment->voucher_no,
                        'remarks' => $reason,
                    ],
                    $payment->payment_method_id,
                    $contract->account_id,
                    (float) $payment->amount,
                    'Reversed payment '.$payment->voucher_no.' to '.($contract->vendor?->name ?? 'vendor').': '.$reason,
                    $userId,
                );

                $payment->update([
                    'status' => ProjectContractPayment::STATUS_REVERSED,
                    'reversal_journal_id' => $journal->id,
                    'reversed_at' => now(),
                    'reversed_by' => $userId,
                    'reversal_reason' => $reason,
                ]);

                foreach ($payment->allocations as $allocation) {
                    $allocation->term?->refreshPaid();
                }

                return $payment->refresh();
            });

            return ['success' => true, 'message' => 'Payment '.$payment->voucher_no.' reversed.', 'data' => $payment];
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }
    }
}

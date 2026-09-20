<?php

namespace App\Actions\QPay;

use App\Models\QpayTransaction;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 * Let the office free a student's card from a top-up QPay will not answer for.
 *
 * StartPaymentAction refuses a new top-up while an earlier one is still pending,
 * so a payment QPay keeps giving no result for blocks the card until the block
 * expires by itself a day later. A parent standing at the counter cannot wait
 * that long, and until now nobody could clear it without editing the database.
 *
 * Releasing decides nothing about the money: the row becomes `unresolved`, never
 * `failed` (which means QPay confirmed nothing was taken), and qpay:inquire-pending
 * goes on asking. If the answer finally says paid, the card is credited then.
 *
 * QPay is asked one last time first, so a payment it is ready to settle is settled
 * properly rather than written off.
 */
class ReleaseAction
{
    public function execute(QpayTransaction $transaction, int $userId): array
    {
        try {
            if ($transaction->type !== QpayTransaction::TYPE_PAYMENT) {
                throw new Exception('Only a payment can be released.', 1);
            }

            if (! $transaction->isPending()) {
                throw new Exception('This top-up already has an outcome — there is nothing to release.', 1);
            }

            // The parent may still be on QPay's page. Releasing then would let them
            // pay a second time for the payment they are in the middle of making,
            // which is the exact thing the block is for.
            if ($transaction->created_at->gt(now()->subMinutes(StartPaymentAction::BROKEN_AFTER_MINUTES))) {
                throw new Exception('This top-up was started less than '.StartPaymentAction::BROKEN_AFTER_MINUTES.' minutes ago and may still be in progress. Try again shortly.', 1);
            }

            (new InquireAction())->execute($transaction);

            if (! $transaction->refresh()->isPending()) {
                $return['success'] = true;
                $return['message'] = 'QPay answered: the top-up is '.strtolower($transaction->statusLabel()).'. Nothing needed releasing.';
                $return['data'] = $transaction;

                return $return;
            }

            $by = User::find($userId)?->name ?: 'the office';

            DB::transaction(fn () => $transaction->forceFill([
                'status' => QpayTransaction::STATUS_UNRESOLVED,
                'failure_reason' => 'Released by '.$by.' on '.now()->format('d M Y h:i A').' so the card could be used again. QPay never gave a final answer'.($transaction->gateway_status ? ' (last said '.trim($transaction->gateway_status.' '.$transaction->gateway_status_message).')' : '').'.',
                'completed_at' => now(),
            ])->save());

            $return['success'] = true;
            $return['message'] = 'Released. The parent can top up again; we will keep asking QPay about this payment and credit the card if it turns out to have been paid.';
            $return['data'] = $transaction;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}

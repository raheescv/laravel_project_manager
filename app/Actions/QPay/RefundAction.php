<?php

namespace App\Actions\QPay;

use App\Actions\Student\GetBalanceAction;
use App\Actions\Student\PostTopupJournalAction;
use App\Models\Account;
use App\Models\Branch;
use App\Models\QpayTransaction;
use App\Models\User;
use App\Services\Payment\QPayClient;
use App\Support\Payment\QPaySettings;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 * Refund a successful top-up in full through QPay (Action 6) and take the money
 * back off the card.
 *
 * QPay only refunds a whole payment (5008), so the card must still hold at least
 * that amount. The refund row is written before QPay is called, so a refund that
 * reaches QPay is always on record; the reversal journal is posted only when QPay
 * accepts it (0000, or 5002 pending). Not wrapped in a caller's transaction: it
 * must never hold a lock across the HTTP call.
 */
class RefundAction
{
    public function execute(int $paymentId, int $userId): array
    {
        try {
            $settings = QPaySettings::current();
            if (! $settings->isReady()) {
                throw new Exception('QPay is not set up.', 1);
            }

            $payment = QpayTransaction::where('type', QpayTransaction::TYPE_PAYMENT)->find($paymentId);
            if (! $payment || $payment->status !== QpayTransaction::STATUS_SUCCESS) {
                throw new Exception('Only a successful top-up can be refunded.', 1);
            }

            $refund = DB::transaction(function () use ($payment, $userId) {
                Account::query()->whereKey($payment->account_id)->lockForUpdate()->first();

                if (QpayTransaction::where('original_pun', $payment->pun)->whereIn('status', [QpayTransaction::STATUS_SUCCESS, QpayTransaction::STATUS_REFUND_PENDING, QpayTransaction::STATUS_PENDING])->exists()) {
                    throw new Exception('This top-up already has a refund.', 1);
                }

                $balance = (new GetBalanceAction())->execute($payment->account_id);
                if ($balance < (float) $payment->amount) {
                    throw new Exception('The card balance is '.currency($balance).'. QPay refunds the whole top-up of '.currency($payment->amount).', so the card must still hold it.', 1);
                }

                return QpayTransaction::create([
                    'type' => QpayTransaction::TYPE_REFUND,
                    'pun' => StartPaymentAction::newPun(),
                    'original_pun' => $payment->pun,
                    'account_id' => $payment->account_id,
                    'guardian_id' => $payment->guardian_id,
                    'amount' => $payment->amount,
                    'currency_code' => QPayClient::CURRENCY_QAR,
                    'lang' => $payment->lang ?: 'En',
                    'status' => QpayTransaction::STATUS_PENDING,
                    'request_date' => QPayClient::timestamp(),
                    'created_by' => $userId,
                ]);
            });

            try {
                $values = (new QPayClient($settings))->refund($refund->pun, $payment->pun, (float) $payment->amount, $refund->lang, $refund->request_date);
            } catch (\Throwable $th) {
                $refund->fill(['status' => QpayTransaction::STATUS_FAILED, 'failure_reason' => $th->getMessage(), 'completed_at' => now()])->save();

                throw $th;
            }

            $code = (string) ($values['Status_1'] ?? $values['EZConnectRequestStatus'] ?? '');
            $refund->fill([
                'gateway_status' => $code,
                'gateway_status_message' => $values['StatusMessage_1'] ?? $values['EZConnectStatusMessage'] ?? null,
                'confirmation_id' => $values['ConfirmationID_1'] ?? null,
                'response_date' => $values['EZConnectResponseDate'] ?? null,
                'payload' => $values,
            ]);

            if (! in_array($code, [QPayClient::SUCCESS, QPayClient::REFUND_PENDING], true)) {
                $refund->fill(['status' => QpayTransaction::STATUS_FAILED, 'failure_reason' => $refund->gateway_status_message ?: 'QPay refused the refund.', 'completed_at' => now()])->save();

                throw new Exception('QPay refused the refund: '.($refund->gateway_status_message ?: $code), 1);
            }

            DB::transaction(function () use ($refund, $payment, $settings, $userId, $code) {
                $user = User::find($userId);
                $response = (new PostTopupJournalAction())->execute($payment->account_id, (float) $payment->amount, $settings->paymentAccountId, [
                    'date' => now()->toDateString(),
                    'branch_id' => $user?->default_branch_id ?: Branch::query()->value('id'),
                    'reference_no' => $refund->pun,
                    'model' => 'QpayTransaction',
                    'model_id' => $refund->id,
                    'remarks' => 'QPay refund '.$refund->pun.' of top-up '.$payment->pun,
                ], $userId, refund: true);
                if (! $response['success']) {
                    throw new Exception($response['message'], 1);
                }

                $refund->fill([
                    'status' => $code === QPayClient::SUCCESS ? QpayTransaction::STATUS_SUCCESS : QpayTransaction::STATUS_REFUND_PENDING,
                    'journal_id' => $response['data']->id,
                    'completed_at' => now(),
                ])->save();
                $payment->fill(['status' => QpayTransaction::STATUS_REFUNDED])->save();
            });

            $return['success'] = true;
            $return['message'] = $code === QPayClient::SUCCESS ? 'Top-up refunded' : 'Refund accepted by QPay and pending';
            $return['data'] = $refund;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}

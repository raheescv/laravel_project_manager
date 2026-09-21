<?php

namespace App\Actions\QPay;

use App\Actions\Student\GetBalanceAction;
use App\Actions\Student\PostTopupJournalAction;
use App\Models\Account;
use App\Models\Branch;
use App\Models\QpayTransaction;
use App\Models\User;
use App\Services\Payment\MpgsClient;
use App\Services\Payment\QPayClient;
use App\Support\Payment\MpgsSettings;
use App\Support\Payment\QPaySettings;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 * Refund a successful top-up in full through the gateway that took it — QPay
 * (Action 6) for a debit card, the Mastercard Gateway (REFUND) for a credit card —
 * and take the money back off the card.
 *
 * QPay only refunds a whole payment (5008), and credit card refunds follow the same
 * rule so the two read alike, so the card must still hold at least that amount. The
 * refund row is written before the gateway is called, so a refund that reaches it is
 * always on record; the reversal journal is posted only when the gateway accepts it
 * (QPay 0000 / MPGS SUCCESS, or pending: QPay 5002 / MPGS PENDING). Not wrapped in a
 * caller's transaction: it must never hold a lock across the HTTP call.
 */
class RefundAction
{
    public function execute(int $paymentId, int $userId): array
    {
        try {
            $payment = QpayTransaction::where('type', QpayTransaction::TYPE_PAYMENT)->find($paymentId);
            if (! $payment || $payment->status !== QpayTransaction::STATUS_SUCCESS) {
                throw new Exception('Only a successful top-up can be refunded.', 1);
            }

            $settings = $payment->gatewaySettings();
            if (! $settings->isReady()) {
                throw new Exception($payment->gatewayLabel().' is not set up.', 1);
            }

            $refund = DB::transaction(function () use ($payment, $userId) {
                Account::query()->whereKey($payment->account_id)->lockForUpdate()->first();

                if (QpayTransaction::where('original_pun', $payment->pun)->whereIn('status', [QpayTransaction::STATUS_SUCCESS, QpayTransaction::STATUS_REFUND_PENDING, QpayTransaction::STATUS_PENDING])->exists()) {
                    throw new Exception('This top-up already has a refund.', 1);
                }

                $balance = (new GetBalanceAction())->execute($payment->account_id);
                if ($balance < (float) $payment->amount) {
                    throw new Exception('The card balance is '.currency($balance).'. '.$payment->gatewayLabel().' refunds the whole top-up of '.currency($payment->amount).', so the card must still hold it.', 1);
                }

                return QpayTransaction::create([
                    'type' => QpayTransaction::TYPE_REFUND,
                    'gateway' => $payment->gateway,
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
                $answer = $payment->isCreditCard() ? $this->viaMpgs($settings, $refund, $payment) : $this->viaQPay($settings, $refund, $payment);
            } catch (\Throwable $th) {
                $refund->fill(['status' => QpayTransaction::STATUS_FAILED, 'failure_reason' => $th->getMessage(), 'completed_at' => now()])->save();

                throw $th;
            }

            $refund->fill([
                'gateway_status' => $answer['code'],
                'gateway_status_message' => $answer['message'],
                'confirmation_id' => $answer['confirmation_id'],
                'response_date' => $answer['response_date'],
                'payload' => $answer['payload'],
            ]);

            if ($answer['state'] === 'refused') {
                $refund->fill(['status' => QpayTransaction::STATUS_FAILED, 'failure_reason' => $refund->gateway_status_message ?: $payment->gatewayLabel().' refused the refund.', 'completed_at' => now()])->save();

                throw new Exception($payment->gatewayLabel().' refused the refund: '.($refund->gateway_status_message ?: $answer['code']), 1);
            }

            $done = $answer['state'] === 'success';

            DB::transaction(function () use ($refund, $payment, $settings, $userId, $done) {
                $user = User::find($userId);
                $response = (new PostTopupJournalAction())->execute($payment->account_id, (float) $payment->amount, $settings->paymentAccountId, [
                    'date' => now()->toDateString(),
                    'branch_id' => $user?->default_branch_id ?: Branch::query()->value('id'),
                    'reference_no' => $refund->pun,
                    'model' => 'QpayTransaction',
                    'model_id' => $refund->id,
                    'remarks' => ($payment->isCreditCard() ? 'Credit card refund ' : 'QPay refund ').$refund->pun.' of top-up '.$payment->pun,
                ], $userId, refund: true);
                if (! $response['success']) {
                    throw new Exception($response['message'], 1);
                }

                $refund->fill([
                    'status' => $done ? QpayTransaction::STATUS_SUCCESS : QpayTransaction::STATUS_REFUND_PENDING,
                    'journal_id' => $response['data']->id,
                    'completed_at' => now(),
                ])->save();
                $payment->fill(['status' => QpayTransaction::STATUS_REFUNDED])->save();
            });

            $return['success'] = true;
            $return['message'] = $done ? 'Top-up refunded' : 'Refund accepted by '.$payment->gatewayLabel().' and pending';
            $return['data'] = $refund;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }

    /**
     * QPay Action 6.
     *
     * @return array{state: string, code: string, message: ?string, confirmation_id: ?string, response_date: ?string, payload: array}
     */
    private function viaQPay(QPaySettings $settings, QpayTransaction $refund, QpayTransaction $payment): array
    {
        $values = (new QPayClient($settings))->refund($refund->pun, $payment->pun, (float) $payment->amount, $refund->lang, $refund->request_date);
        $code = (string) ($values['Status_1'] ?? $values['EZConnectRequestStatus'] ?? '');

        return [
            'state' => match ($code) {
                QPayClient::SUCCESS => 'success',
                QPayClient::REFUND_PENDING => 'pending',
                default => 'refused',
            },
            'code' => $code,
            'message' => $values['StatusMessage_1'] ?? $values['EZConnectStatusMessage'] ?? null,
            'confirmation_id' => $values['ConfirmationID_1'] ?? null,
            'response_date' => $values['EZConnectResponseDate'] ?? null,
            'payload' => $values,
        ];
    }

    /**
     * MPGS REFUND: a new transaction (the refund's PUN) on the payment's order.
     *
     * @return array{state: string, code: string, message: ?string, confirmation_id: ?string, response_date: ?string, payload: array}
     */
    private function viaMpgs(MpgsSettings $settings, QpayTransaction $refund, QpayTransaction $payment): array
    {
        $values = (new MpgsClient($settings))->refund($payment->pun, $refund->pun, (float) $payment->amount);
        $result = (string) ($values['result'] ?? '');
        $code = (string) ($values['response']['gatewayCode'] ?? $result);

        return [
            'state' => match ($result) {
                'SUCCESS' => 'success',
                'PENDING' => 'pending',
                default => 'refused',
            },
            'code' => mb_substr($code, 0, 30),
            'message' => $result === 'SUCCESS' ? 'Refund approved' : ($values['response']['acquirerMessage'] ?? MpgsClient::describe($code)),
            'confirmation_id' => ($values['transaction']['receipt'] ?? null) ?: ($values['transaction']['id'] ?? null),
            'response_date' => null,
            'payload' => array_filter([
                'result' => $values['result'] ?? null,
                'order_status' => $values['order']['status'] ?? null,
                'total_refunded' => $values['order']['totalRefundedAmount'] ?? null,
                'transaction_id' => $values['transaction']['id'] ?? null,
                'gateway_code' => $values['response']['gatewayCode'] ?? null,
                'acquirer_code' => $values['response']['acquirerCode'] ?? null,
                'acquirer_message' => $values['response']['acquirerMessage'] ?? null,
                'receipt' => $values['transaction']['receipt'] ?? null,
            ], fn ($value) => $value !== null),
        ];
    }
}

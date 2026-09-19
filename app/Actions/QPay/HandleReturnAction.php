<?php

namespace App\Actions\QPay;

use App\Models\QpayTransaction;
use App\Services\Payment\QPayApiLog;
use App\Services\Payment\QPayClient;
use App\Support\Payment\QPaySettings;
use Illuminate\Support\Facades\Log;

/**
 * The payment result QPay posts back through the parent's browser.
 *
 * Signed correctly → its status is settled. Hash missing or wrong (tampered, per
 * QPay certification) → the posted status is DROPPED, the payment is marked
 * tampered and QPay's inquiry API decides instead.
 *
 * Every post closes the payment's API log row, or opens a Return row when the
 * payment has none open.
 */
class HandleReturnAction
{
    public function execute(string $rawBody): ?QpayTransaction
    {
        $parsed = QPayClient::parseResponse($rawBody);
        $values = $parsed['values'];
        $pun = $values['PUN'] ?? null;

        $settings = QPaySettings::current();
        $log = QPayApiLog::openPayment($pun) ?? QPayApiLog::start(QPayApiLog::RETURN, request()->path(), null, $settings->merchantId);

        $transaction = $pun ? QpayTransaction::where('pun', $pun)->where('type', QpayTransaction::TYPE_PAYMENT)->first() : null;
        if (! $transaction) {
            Log::warning('QPay return for an unknown payment', ['pun' => $pun]);
            QPayApiLog::answered($log, $parsed, 'No top-up has this PUN.');

            return null;
        }

        $client = new QPayClient($settings);

        if (! $client->verify($parsed)) {
            Log::warning('QPay return failed the secure hash check; inquiring instead', ['pun' => $pun]);
            QPayApiLog::answered($log, $parsed, 'The result failed the secure hash check and was dropped; QPay was inquired instead.');
            $transaction->forceFill(['tampered_at' => now()])->save();
            (new InquireAction())->execute($transaction);

            return $transaction->refresh();
        }

        QPayApiLog::answered($log, $parsed);

        return (new SettleAction())->execute($transaction, [
            'status' => (string) ($values['Status'] ?? ''),
            'message' => $values['StatusMessage'] ?? null,
            'amount' => $values['Amount'] ?? null,
            'confirmation_id' => $values['ConfirmationID'] ?? null,
            'masked_card' => $values['CardNumber'] ?? null,
            'response_date' => $values['EZConnectResponseDate'] ?? null,
        ], $values);
    }
}

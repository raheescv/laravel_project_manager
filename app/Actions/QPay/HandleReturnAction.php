<?php

namespace App\Actions\QPay;

use App\Models\QpayTransaction;
use App\Services\Payment\QPayClient;
use App\Support\Payment\QPaySettings;
use Illuminate\Support\Facades\Log;

/**
 * The payment result QPay posts back through the parent's browser.
 *
 * Signed correctly → its status is settled. Hash missing or wrong (tampered, per
 * QPay certification) → the posted status is DROPPED, the payment is marked
 * tampered and QPay's inquiry API decides instead.
 */
class HandleReturnAction
{
    public function execute(string $rawBody): ?QpayTransaction
    {
        $parsed = QPayClient::parseResponse($rawBody);
        $values = $parsed['values'];
        $pun = $values['PUN'] ?? null;

        $transaction = $pun ? QpayTransaction::where('pun', $pun)->where('type', QpayTransaction::TYPE_PAYMENT)->first() : null;
        if (! $transaction) {
            Log::warning('QPay return for an unknown payment', ['pun' => $pun]);

            return null;
        }

        $client = new QPayClient(QPaySettings::current());

        if (! $client->verify($parsed)) {
            Log::warning('QPay return failed the secure hash check; inquiring instead', ['pun' => $pun]);
            $transaction->forceFill(['tampered_at' => now()])->save();
            (new InquireAction())->execute($transaction);

            return $transaction->refresh();
        }

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

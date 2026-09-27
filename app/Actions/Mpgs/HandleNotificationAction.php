<?php

namespace App\Actions\Mpgs;

use App\Models\ApiLog;
use App\Models\QpayTransaction;
use App\Services\Payment\MpgsClient;
use App\Support\Payment\MpgsSettings;

/**
 * The Mastercard Gateway's webhook: it POSTs the outcome of a checkout to the
 * order's notificationUrl, server to server, so a payment is settled even when
 * the parent closed the tab before being sent back.
 *
 * Only the X-Notification-Secret header is checked against the saved secret; the
 * body is used for nothing but the order id. The order is then read back with
 * InquireAction, exactly as the browser return does, so a notification can never
 * credit more than Retrieve Order confirms, and a repeat is harmless.
 *
 * The gateway re-sends until it gets a 2xx, so an order that is not ours is
 * acknowledged, and a failed read-back is answered 503 to have it sent again.
 */
class HandleNotificationAction
{
    /**
     * @return array{success: bool, status: int, message: string, data?: QpayTransaction}
     */
    public function execute(?string $secret, array $payload): array
    {
        if (! MpgsSettings::current()->isNotificationSecret($secret)) {
            return ['success' => false, 'status' => 401, 'message' => 'Unknown notification secret'];
        }

        $orderId = data_get($payload, 'order.id');
        $log = $this->log($payload, is_scalar($orderId) ? (string) $orderId : null);

        $transaction = is_scalar($orderId) && (string) $orderId !== ''
            ? QpayTransaction::where('pun', (string) $orderId)
                ->where('type', QpayTransaction::TYPE_PAYMENT)
                ->where('gateway', QpayTransaction::GATEWAY_MPGS)
                ->first()
            : null;

        if (! $transaction) {
            $this->finish($log, 'success', 'Ignored: not a top-up of this school.');

            return ['success' => true, 'status' => 200, 'message' => 'Ignored'];
        }

        $response = (new InquireAction())->execute($transaction);
        $this->finish($log, $response['success'] ? 'success' : 'failed', $response['message']);

        if (! $response['success']) {
            return ['success' => false, 'status' => 503, 'message' => 'The order could not be read back from the gateway'];
        }

        return ['success' => true, 'status' => 200, 'message' => 'Processed', 'data' => $transaction->refresh()];
    }

    private function log(array $payload, ?string $orderId): ?ApiLog
    {
        try {
            return ApiLog::create([
                'endpoint' => request()->fullUrl(),
                'method' => 'POST',
                'service_name' => MpgsClient::LOG_NOTIFICATION,
                'request' => json_encode($payload, JSON_UNESCAPED_SLASHES),
                'status' => 'pending',
                'username' => $orderId,
            ]);
        } catch (\Throwable) {
            return null;
        }
    }

    private function finish(?ApiLog $log, string $status, string $description): void
    {
        try {
            $log?->update(['status' => $status, 'description' => $description]);
        } catch (\Throwable) {
            // Logging must never mask the real outcome of the payment.
        }
    }
}

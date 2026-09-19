<?php

namespace App\Services\Payment;

use App\Models\ApiLog;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;

/**
 * Every message exchanged with QPay, on the API Log screen (`api_logs`).
 *
 * - Payment: one row from the signed form handed to the parent's browser until
 *   QPay posts the result back. A parent who never came back leaves it `pending`;
 *   the Inquiry rows show how that payment was settled.
 * - Inquiry / Refund: one row per back-to-back call.
 * - Return: a posted result with no open Payment row (unknown PUN, a repeat post,
 *   or a payment started before logging existed).
 *
 * The response is always what QPay sent, with the SecureHash it signed it with.
 * Requests carry only the hash, never the merchant secret. Logging must never
 * break a payment, so every write swallows its own failure.
 */
final class QPayApiLog
{
    public const PAYMENT = 'QPay Payment';

    public const INQUIRY = 'QPay Inquiry';

    public const REFUND = 'QPay Refund';

    public const RETURN = 'QPay Return';

    /** Status codes with which QPay accepts a message (5002: refund accepted, completes later). */
    private const ACCEPTED = [QPayClient::SUCCESS, QPayClient::REFUND_PENDING];

    public static function start(string $service, string $endpoint, ?array $request, ?string $merchantId): ?ApiLog
    {
        try {
            $actor = self::actor();

            return ApiLog::create([
                'endpoint' => $endpoint,
                'method' => 'POST',
                'service_name' => $service,
                'request' => $request === null ? null : json_encode($request, JSON_UNESCAPED_SLASHES),
                'status' => 'pending',
                'username' => $merchantId,
                'user_id' => $actor instanceof User ? $actor->id : null,
                'user_name' => $actor?->name,
            ]);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Close a row with QPay's parsed answer: `success` when its status code accepts
     * the message, else `failed` with "code: message". A $problem found on our side
     * (hash mismatch, unknown PUN) fails it whatever the code says.
     *
     * @param  array{values: array<string, string>, hash: ?string}  $parsed
     */
    public static function answered(?ApiLog $log, array $parsed, ?string $problem = null): void
    {
        $values = $parsed['values'];
        $code = self::first($values, ['Status', 'Status_1', 'EZConnectRequestStatus']);
        $accepted = $problem === null && in_array($code, self::ACCEPTED, true);
        $message = self::first($values, ['StatusMessage', 'StatusMessage_1', 'EZConnectStatusMessage']);

        self::finish(
            $log,
            $accepted ? 'success' : 'failed',
            $values + ['SecureHash' => $parsed['hash']],
            $problem ?? ($accepted ? null : (trim($code.': '.$message, ': ') ?: 'QPay sent no status.')),
        );
    }

    public static function finish(?ApiLog $log, string $status, ?array $response, ?string $description = null): void
    {
        if (! $log) {
            return;
        }

        try {
            $log->update([
                'status' => $status,
                'response' => $response === null ? null : json_encode($response, JSON_UNESCAPED_SLASHES),
                'description' => $description,
            ]);
        } catch (\Throwable) {
            // Logging must never mask the real outcome of the payment.
        }
    }

    /** The Payment row still waiting for this PUN's result, if its form was logged. */
    public static function openPayment(?string $pun): ?ApiLog
    {
        // PUNs are alphanumeric; anything else (LIKE wildcards in a forged post) matches nothing.
        if (! filled($pun) || ! ctype_alnum($pun)) {
            return null;
        }

        try {
            return ApiLog::where('service_name', self::PAYMENT)
                ->where('status', 'pending')
                ->where('request', 'like', '%'.$pun.'%')
                ->latest('id')
                ->first();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Staff user, or the parent the portal request already signed in (AuthenticateParent
     * does not make `parent` the default guard). A parent is named but not linked:
     * user_id points at users. Nobody for the scheduled inquiry and QPay's return.
     */
    private static function actor(): ?Authenticatable
    {
        $parent = Auth::guard('parent');

        return Auth::user() ?? ($parent->hasUser() ? $parent->user() : null);
    }

    /** @param  array<string, string>  $values */
    private static function first(array $values, array $names): string
    {
        foreach ($names as $name) {
            if (filled($values[$name] ?? null)) {
                return (string) $values[$name];
            }
        }

        return '';
    }
}

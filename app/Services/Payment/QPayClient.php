<?php

namespace App\Services\Payment;

use App\Support\Payment\QPaySettings;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * QCB QPay EZ-Connect (Integration Guide v1.8).
 *
 * Three messages: Pay (Action 0, a browser redirect), Inquiry (Action 14) and
 * Refund (Action 6), the last two back-to-back. Every message in both directions
 * carries a SecureHash: SHA-256 hex of the merchant secret key followed by the
 * parameter VALUES in ascending (byte-wise) order of parameter NAME. On responses
 * the names are read without their "Response." prefix (see responseHashes() for
 * how spaces in status messages are hashed).
 *
 * @see Appendix D of the guide — its worked examples are this class's unit tests.
 */
class QPayClient
{
    public const ACTION_PAY = '0';

    public const ACTION_REFUND = '6';

    public const ACTION_INQUIRY = '14';

    /** ISO 4217 numeric code for QAR, the only currency QPay supports. */
    public const CURRENCY_QAR = '634';

    public const SUCCESS = '0000';

    /** Refund accepted, completes later. */
    public const REFUND_PENDING = '5002';

    /** Inquiry: QPay has no transaction with that PUN. */
    public const NOT_FOUND = '8106';

    public function __construct(private readonly QPaySettings $settings) {}

    /** SHA-256 over secret + values ordered by parameter name. */
    public static function hash(string $secretKey, array $params): string
    {
        unset($params['SecureHash']);
        ksort($params, SORT_STRING);

        return hash('sha256', $secretKey.implode('', array_map(fn ($value) => (string) $value, $params)));
    }

    /** 10.50 → "1050": QPay amounts carry no decimal point. */
    public static function minorUnits(float|string $amount): string
    {
        return (string) (int) round((float) $amount * 100);
    }

    public static function fromMinorUnits(?string $amount): float
    {
        return round(((int) $amount) / 100, 2);
    }

    /** ddMMyyyyHHmmss in Qatar time, whatever the app timezone is. */
    public static function timestamp(?Carbon $at = null): string
    {
        return ($at ?? now())->copy()->setTimezone(config('services.qpay.timezone', 'Asia/Qatar'))->format('dmYHis');
    }

    /**
     * The fields the parent's browser posts to the gateway to pay.
     *
     * Values avoid spaces (description, session id) so the hash is identical
     * however the browser form-encodes them.
     *
     * @return array<string, string>
     */
    public function paymentFields(string $pun, float $amount, string $returnUrl, string $description, string $lang, string $requestDate): array
    {
        $fields = [
            'Action' => self::ACTION_PAY,
            'Amount' => self::minorUnits($amount),
            'BankID' => (string) $this->settings->bankId,
            'CurrencyCode' => self::CURRENCY_QAR,
            'ExtraFields_f14' => $returnUrl,
            'Lang' => $lang,
            'MerchantID' => (string) $this->settings->merchantId,
            'MerchantModuleSessionID' => $pun,
            'PUN' => $pun,
            'PaymentDescription' => mb_substr(preg_replace('/[^A-Za-z0-9\-_.]/', '', $description), 0, 255),
            'Quantity' => '1',
            'TransactionRequestDate' => $requestDate,
        ];
        $fields['SecureHash'] = self::hash((string) $this->settings->secretKey, $fields);

        return $fields;
    }

    /**
     * Parse a urlencoded QPay response WITHOUT PHP's form parsing, which turns the
     * dots in "Response.Status" into underscores. Keys are the names after
     * "Response."; values are fully decoded.
     *
     * @return array{values: array<string, string>, hash: ?string}
     */
    public static function parseResponse(string $body): array
    {
        $values = [];
        $hash = null;

        foreach (explode('&', $body) as $pair) {
            if ($pair === '') {
                continue;
            }
            [$rawName, $rawValue] = array_pad(explode('=', $pair, 2), 2, '');
            $name = urldecode($rawName);
            $name = str_starts_with($name, 'Response.') ? substr($name, 9) : $name;

            if ($name === 'SecureHash') {
                $hash = urldecode($rawValue);

                continue;
            }

            $values[$name] = urldecode($rawValue);
        }

        return ['values' => $values, 'hash' => $hash];
    }

    /**
     * The hash of a response as QPay computes it.
     *
     * The guide's worked examples (Appendix D) hash status messages with their
     * spaces written as "+" ("Payment+Processed+Successfully") while every other
     * value keeps its spaces (a refund's "06/08/2023 16:19:32"). That spelling is
     * tried first; the plainly decoded values are the fallback in case the gateway
     * already sends the message with literal "+" signs.
     *
     * @param  array<string, string>  $values
     * @return array<int, string>
     */
    public static function responseHashes(string $secretKey, array $values): array
    {
        $documented = [];
        foreach ($values as $name => $value) {
            $documented[$name] = str_contains($name, 'StatusMessage') ? str_replace(' ', '+', $value) : $value;
        }

        return array_values(array_unique([self::hash($secretKey, $documented), self::hash($secretKey, $values)]));
    }

    /** Whether a parsed response was signed with this merchant's secret key. */
    public function verify(array $parsed): bool
    {
        if (! $this->settings->secretKey || ! filled($parsed['hash'] ?? null)) {
            return false;
        }

        $received = strtolower((string) $parsed['hash']);
        foreach (self::responseHashes($this->settings->secretKey, $parsed['values']) as $expected) {
            if (hash_equals($expected, $received)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Inquiry (Action 14): QPay's own record of a payment. The response hash is
     * verified; an unsigned or tampered answer throws rather than being believed.
     *
     * @return array<string, string> response values without the "Response." prefix
     */
    public function inquire(string $pun, string $lang = 'En'): array
    {
        $fields = [
            'Action' => self::ACTION_INQUIRY,
            'BankID' => (string) $this->settings->bankId,
            'Lang' => $lang,
            'MerchantID' => (string) $this->settings->merchantId,
            'OriginalPUN' => $pun,
        ];
        $fields['SecureHash'] = self::hash((string) $this->settings->secretKey, $fields);

        return $this->backToBack($fields, 'inquiry');
    }

    /**
     * Refund (Action 6) of a whole payment — QPay refuses partial refunds (5008).
     *
     * @return array<string, string>
     */
    public function refund(string $refundPun, string $originalPun, float $amount, string $lang, string $requestDate): array
    {
        $fields = [
            'Action' => self::ACTION_REFUND,
            'Amount_1' => self::minorUnits($amount),
            'BankID' => (string) $this->settings->bankId,
            'CurrencyCode' => self::CURRENCY_QAR,
            'Lang' => $lang,
            'MerchantID' => (string) $this->settings->merchantId,
            'OriginalTransactionPaymentUniqueNumber_1' => $originalPun,
            'PUN_1' => $refundPun,
            'RequestDate' => '',
            'TransactionRequestDate' => $requestDate,
        ];
        $fields['SecureHash'] = self::hash((string) $this->settings->secretKey, $fields);

        return $this->backToBack($fields, 'refund');
    }

    /** @return array<string, string> */
    private function backToBack(array $fields, string $operation): array
    {
        try {
            $response = Http::asForm()->timeout(30)->post($this->settings->gatewayUrl(), $fields);
        } catch (ConnectionException $e) {
            Log::warning('QPay unreachable', ['operation' => $operation, 'error' => $e->getMessage()]);

            throw new QPayException('The payment service could not be reached. Please try again.', 0, $e);
        }

        if (! $response->successful()) {
            Log::error('QPay request failed', ['operation' => $operation, 'status' => $response->status(), 'body' => $response->body()]);

            throw new QPayException('The payment service rejected the request.', $response->status());
        }

        $parsed = self::parseResponse(trim($response->body()));
        if (! $this->verify($parsed)) {
            Log::error('QPay response failed the secure hash check', ['operation' => $operation, 'body' => $response->body()]);

            throw new QPayException('The payment service response could not be verified.');
        }

        return $parsed['values'];
    }
}

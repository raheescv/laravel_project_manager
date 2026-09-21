<?php

namespace App\Support\Payment;

use App\Models\Configuration;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * The school's Mastercard Gateway (MPGS) merchant for CREDIT card top-ups
 * (Settings → Student Cards → Credit card top-ups). The debit card side is QPay,
 * in QPaySettings — the two are configured, enabled and settled separately.
 *
 * The gateway host is the acquiring bank's own (CBQ's test host is
 * test-cbq.mtf.gateway.mastercard.com), so it is a setting, not config. A merchant
 * ID starting "TEST" is the bank's simulator; the live ID comes later from the bank.
 *
 * Read straight from `configurations` on each use, like QPaySettings: the API
 * password is stored encrypted with the app key and only ever decrypted here.
 */
final class MpgsSettings
{
    public const KEY = 'mpgs_gateway';

    public const PASSWORD_KEY = 'mpgs_api_password';

    /** REST API version. Hosted Checkout's INITIATE_CHECKOUT needs 63+. */
    public const API_VERSION = 100;

    public const DEFAULT_GATEWAY_URL = 'https://test-cbq.mtf.gateway.mastercard.com';

    public function __construct(
        public readonly bool $enabled,
        public readonly string $gatewayUrl,
        public readonly ?string $merchantId,
        public readonly ?string $apiPassword,
        /** Shown to the parent on the payment page (max 40). */
        public readonly ?string $merchantName,
        /** Bank account the money lands in (Dr on a top-up). */
        public readonly ?int $paymentAccountId,
        /** User top-up journals are recorded under; their default branch books the journal. */
        public readonly ?int $userId,
    ) {}

    public static function current(): self
    {
        $values = Configuration::whereIn('key', [self::KEY, self::PASSWORD_KEY])->pluck('value', 'key');
        $config = json_decode((string) ($values[self::KEY] ?? ''), true) ?: [];

        return new self(
            enabled: (bool) ($config['enabled'] ?? false),
            gatewayUrl: self::normaliseUrl($config['gateway_url'] ?? null) ?? self::DEFAULT_GATEWAY_URL,
            merchantId: filled($config['merchant_id'] ?? null) ? (string) $config['merchant_id'] : null,
            apiPassword: self::decrypt($values[self::PASSWORD_KEY] ?? null),
            merchantName: filled($config['merchant_name'] ?? null) ? (string) $config['merchant_name'] : null,
            paymentAccountId: self::id($config['payment_account_id'] ?? null),
            userId: self::id($config['user_id'] ?? null),
        );
    }

    /** Switched on, and everything a top-up needs to be paid and booked is set. */
    public function isReady(): bool
    {
        return $this->enabled && $this->merchantId && $this->apiPassword && $this->merchantName && $this->paymentAccountId && $this->userId;
    }

    /** The bank's simulator: no real card is charged. */
    public function isTest(): bool
    {
        return str_starts_with(strtoupper((string) $this->merchantId), 'TEST');
    }

    /** …/api/rest/version/100/merchant/{id} */
    public function merchantUrl(): string
    {
        return $this->gatewayUrl.'/api/rest/version/'.self::API_VERSION.'/merchant/'.rawurlencode((string) $this->merchantId);
    }

    /** The Hosted Checkout library the portal loads to open the payment page. */
    public function checkoutScriptUrl(): string
    {
        return $this->gatewayUrl.'/static/checkout/checkout.min.js';
    }

    /**
     * "https://host" with no path or trailing slash, or null for anything that is
     * not an https URL — the API password is sent there, so never over plain http.
     */
    public static function normaliseUrl(?string $url): ?string
    {
        $url = trim((string) $url);
        $parts = parse_url($url);
        if (! $url || ! $parts || ($parts['scheme'] ?? '') !== 'https' || blank($parts['host'] ?? null)) {
            return null;
        }

        return 'https://'.strtolower($parts['host']).(isset($parts['port']) ? ':'.$parts['port'] : '');
    }

    public static function encryptSecret(string $secret): string
    {
        return Crypt::encryptString($secret);
    }

    /** `…Xy1x` — enough to recognise the saved password without revealing it. */
    public static function hint(?string $secret): ?string
    {
        return $secret ? '…'.substr($secret, -4) : null;
    }

    private static function decrypt(?string $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return null;
        }
    }

    private static function id(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }
}

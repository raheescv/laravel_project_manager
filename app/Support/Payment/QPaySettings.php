<?php

namespace App\Support\Payment;

use App\Models\Configuration;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * The school's QPay (QCB EZ-Connect) merchant for student card top-ups
 * (Settings → Student Cards).
 *
 * Read straight from `configurations` on each use, like TapSettings: the secret is
 * stored encrypted with the app key and only ever decrypted here, and a cached copy
 * of a regenerated secret key is exactly what must never be used.
 */
final class QPaySettings
{
    public const KEY = 'qpay_gateway';

    public const SECRET_KEY = 'qpay_secret_key';

    public function __construct(
        public readonly bool $enabled,
        public readonly string $environment,
        public readonly ?string $bankId,
        public readonly ?string $merchantId,
        public readonly ?string $secretKey,
        /** Bank account the money lands in (Dr on a top-up). */
        public readonly ?int $paymentAccountId,
        /** User top-up journals are recorded under; their default branch books the journal. */
        public readonly ?int $userId,
    ) {}

    public static function current(): self
    {
        $values = Configuration::whereIn('key', [self::KEY, self::SECRET_KEY])->pluck('value', 'key');
        $config = json_decode((string) ($values[self::KEY] ?? ''), true) ?: [];

        return new self(
            enabled: (bool) ($config['enabled'] ?? false),
            environment: ($config['environment'] ?? 'staging') === 'production' ? 'production' : 'staging',
            bankId: filled($config['bank_id'] ?? null) ? (string) $config['bank_id'] : null,
            merchantId: filled($config['merchant_id'] ?? null) ? (string) $config['merchant_id'] : null,
            secretKey: self::decrypt($values[self::SECRET_KEY] ?? null),
            paymentAccountId: self::id($config['payment_account_id'] ?? null),
            userId: self::id($config['user_id'] ?? null),
        );
    }

    /** Switched on, and everything a top-up needs to be paid and booked is set. */
    public function isReady(): bool
    {
        return $this->enabled && $this->bankId && $this->merchantId && $this->secretKey && $this->paymentAccountId && $this->userId;
    }

    public function gatewayUrl(): string
    {
        return (string) config('services.qpay.'.($this->environment === 'production' ? 'production_url' : 'staging_url'));
    }

    public static function encryptSecret(string $secret): string
    {
        return Crypt::encryptString($secret);
    }

    /** `…Xy1x` — enough to recognise the saved key without revealing it. */
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

<?php

namespace App\Support\Storefront;

use App\Models\Configuration;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * The tenant's Tap Payments setup for storefront checkout (Settings → Online Payments).
 *
 * Read straight from `configurations` on each use rather than through TenantCache:
 * it is one indexed query on a path that is about to make an HTTP call to Tap
 * anyway, and a cached copy of a rotated secret key is the last thing to serve.
 * The secret is stored encrypted with the app key and only ever decrypted here.
 */
final class TapSettings
{
    /** Configuration key holding the non-secret fields as JSON. */
    public const KEY = 'storefront_payment_gateway';

    /** Configuration key holding the encrypted Tap secret key. */
    public const SECRET_KEY = 'tap_secret_key';

    public function __construct(
        public readonly bool $enabled,
        public readonly ?string $secretKey,
        public readonly ?string $merchantId,
        public readonly ?int $paymentAccountId,
        public readonly ?int $userId,
        public readonly ?int $deliveryBranchId,
    ) {}

    public static function current(): self
    {
        $values = Configuration::whereIn('key', [self::KEY, self::SECRET_KEY])->pluck('value', 'key');
        $config = json_decode((string) ($values[self::KEY] ?? ''), true) ?: [];

        return new self(
            enabled: (bool) ($config['enabled'] ?? false),
            secretKey: self::decrypt($values[self::SECRET_KEY] ?? null),
            merchantId: filled($config['merchant_id'] ?? null) ? (string) $config['merchant_id'] : null,
            paymentAccountId: self::id($config['payment_account_id'] ?? null),
            userId: self::id($config['user_id'] ?? null),
            deliveryBranchId: self::id($config['delivery_branch_id'] ?? null),
        );
    }

    /** Switched on, and everything a checkout needs to record a sale is set. */
    public function isReady(): bool
    {
        return $this->enabled && $this->secretKey && $this->paymentAccountId && $this->userId;
    }

    /** Tap issues separate key pairs per mode; the prefix is the only reliable tell. */
    public function isLiveMode(): bool
    {
        return str_starts_with((string) $this->secretKey, 'sk_live_');
    }

    public function deliveryEnabled(): bool
    {
        return $this->deliveryBranchId !== null;
    }

    public static function encryptSecret(string $secret): string
    {
        return Crypt::encryptString($secret);
    }

    /** `sk_test_…Xy1x` — enough to recognise which key is saved without revealing it. */
    public static function hint(?string $secret): ?string
    {
        if (! $secret) {
            return null;
        }

        $prefix = str_starts_with($secret, 'sk_live_') ? 'sk_live_' : (str_starts_with($secret, 'sk_test_') ? 'sk_test_' : '');

        return $prefix.'…'.substr($secret, -4);
    }

    /** A key encrypted under a since-rotated APP_KEY reads as "no key", not as a crash. */
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

<?php

namespace App\Support\Storefront;

/**
 * What one promo code is worth on one bag, or why it is worth nothing.
 */
final readonly class CouponQuote
{
    public const READY = 'ready';

    /** Valid, but the bag is [short] away from qualifying. */
    public const LOCKED = 'locked';

    /** Nothing in the bag is covered by the code. */
    public const INELIGIBLE = 'ineligible';

    public const EXPIRED = 'expired';

    public const USED_UP = 'used_up';

    /** This customer has already spent their uses of the code. */
    public const ALREADY_USED = 'already_used';

    /** Paused, or not started yet. */
    public const UNAVAILABLE = 'unavailable';

    public function __construct(
        public string $state,
        public float $saving = 0,
        public float $short = 0,
    ) {}

    public function isReady(): bool
    {
        return $this->state === self::READY;
    }

    /** Why the code cannot be used, as the checkout tells the customer. */
    public function message(string $code): string
    {
        return match ($this->state) {
            self::LOCKED => "Add {$this->short} more to your bag to use {$code}.",
            self::INELIGIBLE => "{$code} does not cover the items in your bag.",
            self::EXPIRED => "{$code} has ended.",
            self::USED_UP => "{$code} has been fully claimed.",
            self::ALREADY_USED => "You have already used {$code}.",
            default => "{$code} cannot be used right now.",
        };
    }
}

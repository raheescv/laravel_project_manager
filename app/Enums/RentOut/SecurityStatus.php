<?php

namespace App\Enums\RentOut;

enum SecurityStatus: string
{
    case Deposited = 'deposited';
    case Submitted = 'submitted';
    case Returned = 'returned';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case PaidReleased = 'paid_released';

    public function label(): string
    {
        return match ($this) {
            self::Deposited => 'Deposited',
            self::Submitted => 'Submitted',
            self::Returned => 'Returned',
            self::Paid => 'Paid',
            self::Overdue => 'Overdue',
            self::PaidReleased => 'Paid & Released',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Deposited => 'primary',
            self::Submitted => 'warning',
            self::Returned => 'info',
            self::Paid => 'success',
            self::Overdue => 'danger',
            self::PaidReleased => 'secondary',
        };
    }

    /**
     * The deposit money has been received (banked or taken as cash), so the
     * collection receipt is posted: Dr Payment Method / Cr Security Deposit.
     */
    public function isCollected(): bool
    {
        return in_array($this, [self::Deposited, self::Paid, self::Returned, self::PaidReleased], true);
    }

    /**
     * The deposit has been handed back to the customer, so the refund payout
     * is posted on top of the collection receipt.
     */
    public function isRefunded(): bool
    {
        return in_array($this, [self::Returned, self::PaidReleased], true);
    }

    /**
     * Statuses still awaiting the money (cheque only held, or not yet received).
     *
     * @return array<int, self>
     */
    public static function awaiting(): array
    {
        return [self::Submitted, self::Overdue];
    }

    /**
     * @return array<int, self>
     */
    public static function collected(): array
    {
        return array_values(array_filter(self::cases(), fn (self $status): bool => $status->isCollected()));
    }

    /**
     * @return array<int, string>
     */
    public static function collectedValues(): array
    {
        return array_map(fn (self $status): string => $status->value, self::collected());
    }

    /**
     * @return array<int, string>
     */
    public static function refundedValues(): array
    {
        return [self::Returned->value, self::PaidReleased->value];
    }
}

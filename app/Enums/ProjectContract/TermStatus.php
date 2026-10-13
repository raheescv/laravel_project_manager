<?php

namespace App\Enums\ProjectContract;

enum TermStatus: string
{
    case Pending = 'pending';
    case Partial = 'partial';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Partial => 'Partial',
            self::Paid => 'Paid',
        };
    }

    public static function fromAmounts(float $amount, float $paid): self
    {
        if ($paid <= 0) {
            return self::Pending;
        }

        return round($amount - $paid, 2) <= 0 ? self::Paid : self::Partial;
    }
}

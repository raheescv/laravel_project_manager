<?php

namespace App\Enums\RentOut;

enum SecurityType: string
{
    case Deposit = 'deposit';
    case Guarantee = 'guarantee';

    public function label(): string
    {
        return match ($this) {
            self::Deposit => 'Deposit',
            self::Guarantee => 'Guarantee',
        };
    }
}

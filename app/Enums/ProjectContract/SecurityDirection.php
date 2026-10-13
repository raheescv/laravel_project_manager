<?php

namespace App\Enums\ProjectContract;

enum SecurityDirection: string
{
    case Received = 'received';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Received => 'Received from vendor',
            self::Paid => 'Paid to vendor',
        };
    }
}

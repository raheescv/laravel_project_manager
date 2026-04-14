<?php

namespace App\Enums\RentOut;

enum SecurityStatus: string
{
    case Pending = 'pending';
    case Collected = 'collected';
    case Returned = 'returned';
    case Adjusted = 'adjusted';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Collected => 'Collected',
            self::Returned => 'Returned',
            self::Adjusted => 'Adjusted',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Collected => 'success',
            self::Returned => 'info',
            self::Adjusted => 'secondary',
        };
    }
}

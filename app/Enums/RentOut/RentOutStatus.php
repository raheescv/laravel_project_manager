<?php

namespace App\Enums\RentOut;

enum RentOutStatus: string
{
    case Occupied = 'occupied';
    case Vacated = 'vacated';
    case Expired = 'expired';
    case Booked = 'booked';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Occupied => 'Occupied',
            self::Vacated => 'Vacated',
            self::Expired => 'Expired',
            self::Booked => 'Booked',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Occupied => 'success',
            self::Vacated => 'warning',
            self::Expired => 'danger',
            self::Booked => 'info',
            self::Cancelled => 'secondary',
        };
    }
}

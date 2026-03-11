<?php

namespace App\Enums\Property;

enum PropertyStatus: string
{
    case Vacant = 'vacant';
    case Occupied = 'occupied';
    case Booked = 'booked';
    case Sold = 'sold';

    public function label(): string
    {
        return match ($this) {
            self::Vacant => 'vacant',
            self::Occupied => 'Occupied',
            self::Booked => 'Booked',
            self::Sold => 'Sold',
        };
    }
}

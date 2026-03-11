<?php

namespace App\Enums\Property;

enum BuildingOwnership: string
{
    case Own = 'own';
    case Lease = 'lease';
    case Rent = 'rent';

    public function label(): string
    {
        return match ($this) {
            self::Own => 'Own',
            self::Lease => 'Lease',
            self::Rent => 'Rent',
        };
    }
}

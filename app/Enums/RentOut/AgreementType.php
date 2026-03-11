<?php

namespace App\Enums\RentOut;

enum AgreementType: string
{
    case Rental = 'rental';
    case Lease = 'lease';

    public function label(): string
    {
        return match ($this) {
            self::Rental => 'Rental',
            self::Lease => 'Lease / Sale',
        };
    }
}

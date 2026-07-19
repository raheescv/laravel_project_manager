<?php

namespace App\Enums\RentOut;

use App\Support\RentOutConfig;

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

    public function config(): RentOutConfig
    {
        return new RentOutConfig($this);
    }

    /**
     * Slug stored on transactions/journals so the originating module can be
     * identified later (e.g. on receipts, vouchers and ledgers).
     */
    public function sourceSlug(): string
    {
        return $this === self::Lease ? 'sale' : 'rent_out';
    }
}

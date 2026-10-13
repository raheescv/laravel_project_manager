<?php

namespace App\Enums\Account;

enum VendorType: string
{
    case Company = 'company';
    case Individual = 'individual';
    case Subcontractor = 'subcontractor';
    case Supplier = 'supplier';

    public function label(): string
    {
        return match ($this) {
            self::Company => 'Company',
            self::Individual => 'Individual',
            self::Subcontractor => 'Subcontractor',
            self::Supplier => 'Supplier',
        };
    }
}

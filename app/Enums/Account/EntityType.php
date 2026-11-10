<?php

namespace App\Enums\Account;

enum EntityType: string
{
    case Company = 'company';
    case Individual = 'individual';

    public function label(): string
    {
        return match ($this) {
            self::Company => 'Company',
            self::Individual => 'Individual',
        };
    }
}

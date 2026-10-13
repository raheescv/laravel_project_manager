<?php

namespace App\Enums\ProjectContract;

enum SettlementType: string
{
    case Return = 'return';
    case Forfeit = 'forfeit';

    public function label(): string
    {
        return match ($this) {
            self::Return => 'Returned',
            self::Forfeit => 'Forfeited',
        };
    }
}

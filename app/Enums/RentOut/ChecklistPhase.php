<?php

namespace App\Enums\RentOut;

enum ChecklistPhase: string
{
    case MoveIn = 'move_in';
    case MoveOut = 'move_out';

    public function label(): string
    {
        return match ($this) {
            self::MoveIn => 'Move-In',
            self::MoveOut => 'Move-Out',
        };
    }
}

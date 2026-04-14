<?php

namespace App\Enums\RentOut;

enum ChequeStatus: string
{
    case Uncleared = 'uncleared';
    case Submitted = 'submitted';
    case Return = 'return';
    case Bounce = 'bounce';
    case Cleared = 'cleared';
    case Terminated = 'terminated';

    public function label(): string
    {
        return match ($this) {
            self::Uncleared => 'Uncleared',
            self::Submitted => 'Submitted',
            self::Return => 'Return',
            self::Bounce => 'Bounce',
            self::Cleared => 'Cleared',
            self::Terminated => 'Terminated',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Uncleared => 'warning',
            self::Submitted => 'info',
            self::Return => 'danger',
            self::Bounce => 'danger',
            self::Cleared => 'success',
            self::Terminated => 'secondary',
        };
    }
}

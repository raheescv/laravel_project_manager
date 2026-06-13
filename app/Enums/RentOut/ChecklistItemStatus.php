<?php

namespace App\Enums\RentOut;

enum ChecklistItemStatus: string
{
    case Ok = 'ok';
    case NotOk = 'not_ok';
    case Na = 'na';

    public function label(): string
    {
        return match ($this) {
            self::Ok => 'Good / Present',
            self::NotOk => 'Damaged / Missing',
            self::Na => 'N/A',
        };
    }

    public function symbol(): string
    {
        return match ($this) {
            self::Ok => '✓',
            self::NotOk => '✗',
            self::Na => '—',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Ok => 'success',
            self::NotOk => 'danger',
            self::Na => 'secondary',
        };
    }
}

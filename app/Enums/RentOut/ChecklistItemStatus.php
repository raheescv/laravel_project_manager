<?php

namespace App\Enums\RentOut;

enum ChecklistItemStatus: string
{
    case Ok = 'ok';
    case NotOk = 'not_ok';
    case Na = 'na';

    /**
     * The value a line may store for [phase]. Move-In is binary — present (ok) or
     * blank — while Move-Out takes any recognised status; anything else is blank.
     */
    public static function normalizeFor(ChecklistPhase $phase, mixed $value): ?string
    {
        if ($phase === ChecklistPhase::MoveIn) {
            return $value === self::Ok->value ? self::Ok->value : null;
        }

        return self::tryFrom((string) $value)?->value;
    }

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

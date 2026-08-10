<?php

namespace App\Enums\RentOut;

/**
 * Progress of a single fixture-comment entry — the rectification work recorded
 * against an area of the unit (tiles to refit, switch to replace, paint damage…).
 */
enum FixtureStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::InProgress => 'In Progress',
            self::Completed => 'Completed',
        };
    }

    /** Bootstrap contextual colour, matching the badge classes used across the app. */
    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::InProgress => 'primary',
            self::Completed => 'success',
        };
    }

    /** Hex used by the printed form, which has no Bootstrap to lean on. */
    public function printColor(): string
    {
        return match ($this) {
            self::Pending => '#b7791f',
            self::InProgress => '#2563eb',
            self::Completed => '#1d7a45',
        };
    }

    /** @return array<string, string> value => label, for select options. */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all();
    }
}

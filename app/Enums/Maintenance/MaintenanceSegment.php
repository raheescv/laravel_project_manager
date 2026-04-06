<?php

namespace App\Enums\Maintenance;

enum MaintenanceSegment: string
{
    case PPMC = 'ppmc';
    case Corrective = 'corrective';
    case Preparation = 'preparation';

    public function label(): string
    {
        return match ($this) {
            self::PPMC => 'PPMC',
            self::Corrective => 'Corrective',
            self::Preparation => 'Preparation',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PPMC => 'primary',
            self::Corrective => 'warning',
            self::Preparation => 'info',
        };
    }
}

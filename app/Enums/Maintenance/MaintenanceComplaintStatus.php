<?php

namespace App\Enums\Maintenance;

enum MaintenanceComplaintStatus: string
{
    case Pending = 'pending';
    case Assigned = 'assigned';
    case Completed = 'completed';
    case Outstanding = 'outstanding';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Assigned => 'Assigned',
            self::Completed => 'Completed',
            self::Outstanding => 'Outstanding',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Assigned => 'info',
            self::Completed => 'success',
            self::Outstanding => 'dark',
            self::Cancelled => 'secondary',
        };
    }
}

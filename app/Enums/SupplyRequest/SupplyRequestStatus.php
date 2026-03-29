<?php

namespace App\Enums\SupplyRequest;

enum SupplyRequestStatus: string
{
    case REQUIREMENT = 'requirement';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case COLLECTED = 'collected';
    case FINAL_APPROVED = 'final_approved';
    case COMPLETED = 'completed';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::REQUIREMENT => 'Requirement',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::COLLECTED => 'Collected',
            self::FINAL_APPROVED => 'Final Approved',
            self::COMPLETED => 'Completed',
            self::EXPIRED => 'Expired',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::REQUIREMENT => 'warning',
            self::APPROVED => 'info',
            self::REJECTED => 'danger',
            self::COLLECTED => 'dark',
            self::FINAL_APPROVED => 'primary',
            self::COMPLETED => 'success',
            self::EXPIRED => 'secondary',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::REQUIREMENT => 'fa-clock-o',
            self::APPROVED => 'fa-check-circle',
            self::REJECTED => 'fa-times-circle',
            self::COLLECTED => 'fa-money',
            self::FINAL_APPROVED => 'fa-shield',
            self::COMPLETED => 'fa-flag-checkered',
            self::EXPIRED => 'fa-hourglass-end',
        };
    }

    public static function values(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->toArray();
    }
}

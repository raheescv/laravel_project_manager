<?php

namespace App\Enums\ProjectContract;

enum PaymentFrequency: string
{
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case HalfYearly = 'half_yearly';
    case Yearly = 'yearly';
    case Milestone = 'milestone';
    case OneTime = 'one_time';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Monthly',
            self::Quarterly => 'Quarterly',
            self::HalfYearly => 'Half-Yearly',
            self::Yearly => 'Yearly',
            self::Milestone => 'Milestone',
            self::OneTime => 'One-time',
        };
    }
}

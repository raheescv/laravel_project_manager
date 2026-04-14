<?php

namespace App\Enums\RentOut;

enum RentOutBookingStatus: string
{
    case Created = 'created';
    case Submitted = 'submitted';
    case FinancialApproved = 'financial approved';
    case Approved = 'approved';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Created',
            self::Submitted => 'Submitted',
            self::FinancialApproved => 'Financial Approved',
            self::Approved => 'Legal Approved',
            self::Completed => 'Completed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Created => 'info',
            self::Submitted => 'info',
            self::FinancialApproved => 'primary',
            self::Approved => 'warning',
            self::Completed => 'success',
        };
    }
}

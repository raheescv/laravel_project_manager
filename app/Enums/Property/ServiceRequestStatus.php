<?php

namespace App\Enums\Property;

enum ServiceRequestStatus: string
{
    case Requested = 'requested';
    case Approved = 'approved';
    case FinancialApproved = 'financial_approved';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Requested',
            self::Approved => 'Approved',
            self::FinancialApproved => 'Financial Approved',
            self::Completed => 'Completed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Requested => 'warning',
            self::Approved => 'info',
            self::FinancialApproved => 'primary',
            self::Completed => 'success',
        };
    }

    /**
     * The status a request moves to from this one; null once it is completed.
     */
    public function next(): ?self
    {
        return match ($this) {
            self::Requested => self::Approved,
            self::Approved => self::FinancialApproved,
            self::FinancialApproved => self::Completed,
            self::Completed => null,
        };
    }

    /**
     * The permission that moves a request into this status.
     */
    public function permission(): ?string
    {
        return match ($this) {
            self::Requested => null,
            self::Approved => 'property service request.approve',
            self::FinancialApproved => 'property service request.financial approve',
            self::Completed => 'property service request.complete',
        };
    }

    public function actionLabel(): string
    {
        return match ($this) {
            self::Requested => 'Request',
            self::Approved => 'Approve',
            self::FinancialApproved => 'Financial Approve',
            self::Completed => 'Complete',
        };
    }
}

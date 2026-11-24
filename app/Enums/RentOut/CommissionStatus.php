<?php

namespace App\Enums\RentOut;

/**
 * Approval flow of an agent's commission on an agreement:
 * Draft → Submitted → CEO Approved → Owner Approved (optional) → Finance Approved → Paid.
 * A rejection sends it back to be edited and resubmitted.
 */
enum CommissionStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case CeoApproved = 'ceo_approved';
    case OwnerApproved = 'owner_approved';
    case FinanceApproved = 'finance_approved';
    case Paid = 'paid';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::CeoApproved => 'CEO Approved',
            self::OwnerApproved => 'Owner Approved',
            self::FinanceApproved => 'Finance Approved',
            self::Paid => 'Paid',
            self::Rejected => 'Rejected',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'secondary',
            self::Submitted => 'info',
            self::CeoApproved, self::OwnerApproved => 'primary',
            self::FinanceApproved => 'warning',
            self::Paid => 'success',
            self::Rejected => 'danger',
        };
    }

    /** Still editable by the agent's side (nothing approved yet). */
    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Rejected], true);
    }

    /** Waiting on an approver, so it can still be rejected. */
    public function isRejectable(): bool
    {
        return in_array($this, [self::Submitted, self::CeoApproved, self::OwnerApproved], true);
    }

    /** Posted to the books (Dr Commission Expense / Cr Commission Payable). */
    public function isPosted(): bool
    {
        return in_array($this, [self::FinanceApproved, self::Paid], true);
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}

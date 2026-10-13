<?php

namespace App\Enums\ProjectContract;

/** Status of something that waits on the approval levels: an extension or a security settlement. */
enum ApprovalStatus: string
{
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PendingApproval => 'Pending approval',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
        };
    }
}

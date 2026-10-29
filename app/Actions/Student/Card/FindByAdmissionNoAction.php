<?php

namespace App\Actions\Student\Card;

use App\Models\StudentDetail;

/**
 * The till's fallback when a cashier types an admission number instead of
 * tapping the card: the same summary as {@see FindByUidAction}, so the card the
 * student holds still travels with the sale and is checked when it is charged.
 *
 * Read-only: null when no student has that admission number.
 */
class FindByAdmissionNoAction
{
    public function execute(?string $admissionNo): ?array
    {
        $admissionNo = trim((string) $admissionNo);
        if ($admissionNo === '') {
            return null;
        }

        $detail = StudentDetail::with('account')->where('admission_no', $admissionNo)->first();
        if (! $detail || ! $detail->account) {
            return null;
        }

        return FindByUidAction::summary($detail);
    }
}

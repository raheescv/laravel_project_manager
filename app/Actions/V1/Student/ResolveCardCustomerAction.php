<?php

namespace App\Actions\V1\Student;

use App\Models\Account;
use App\Models\StudentDetail;
use App\Support\ModuleAccess;
use RuntimeException;

/**
 * The student a QLOUD POS card sale is for, checked against the card that was tapped.
 *
 * A card sale is refused when it was queued offline (the balance and the card's
 * block status are only known to the server), when the card is not this
 * student's, or when the card is blocked or the student is no longer active.
 * The balance itself is checked by the web sale action (GuardCardPaymentAction),
 * inside the sale's own transaction.
 */
class ResolveCardCustomerAction
{
    public function execute(int $studentAccountId, ?string $cardUid, ?string $offlineRef = null): Account
    {
        if (! ModuleAccess::school()) {
            throw new RuntimeException('Student cards are not enabled for this business.');
        }

        if (filled($offlineRef)) {
            throw new RuntimeException('Student card sales need a connection. Reconnect and charge the card again, or take another payment.');
        }

        $student = Account::student()->with('studentDetail')->find($studentAccountId);
        $detail = $student?->studentDetail;
        if (! $student || ! $detail) {
            throw new RuntimeException('This student could not be found.');
        }

        $uid = StudentDetail::normalizeCardUid($cardUid);
        if (! $uid || $uid !== $detail->card_uid) {
            throw new RuntimeException('The tapped card does not belong to '.$student->name.'. Tap the card again.');
        }
        if ($detail->isCardBlocked()) {
            throw new RuntimeException($student->name.'\'s card is blocked. Ask the school office.');
        }
        if ($detail->status !== 'active') {
            throw new RuntimeException($student->name.' is not an active student.');
        }

        return $student;
    }
}

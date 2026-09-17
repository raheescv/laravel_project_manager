<?php

namespace App\Actions\V1\Parent;

use App\Actions\Parent\FindStudentAction;
use App\Actions\Student\Card\BlockAction;
use App\Exceptions\ParentPortalException;
use App\Models\Guardian;
use Illuminate\Support\Facades\DB;

/**
 * A parent reports their child's card lost: it is refused at the canteen from the
 * next tap. Only the school office can switch it back on (see BlockAction).
 */
class BlockCardAction
{
    public function execute(Guardian $guardian, int|string $accountId, ?string $reason): string
    {
        $student = (new FindStudentAction())->execute($guardian, $accountId);

        return DB::transaction(function () use ($student, $guardian, $reason) {
            $response = (new BlockAction())->execute($student->id, 'guardian', $guardian->id, $reason ?: 'Reported by parent');
            if (! $response['success']) {
                throw new ParentPortalException($response['message']);
            }

            return $response['message'];
        });
    }
}

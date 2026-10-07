<?php

namespace App\Actions\RentOut\Checklist\Line;

use App\Enums\RentOut\ChecklistItemStatus;
use App\Enums\RentOut\ChecklistPhase;
use App\Models\RentOutChecklistLine;

/**
 * "The rest are fine": mark the given items present (move-in) / good (move-out).
 * Items that already carry a status for the phase are left as recorded, so a
 * damaged item can never be flipped to good by a bulk tap.
 */
class MarkOkAction
{
    /**
     * @param  array<int, int|string>  $lineIds
     */
    public function execute($rentOutId, ChecklistPhase $phase, array $lineIds)
    {
        try {
            // Saved one by one rather than as a bulk query so each change lands in the audit trail.
            $lines = RentOutChecklistLine::where('rent_out_id', $rentOutId)
                ->whereIn('id', array_map('intval', $lineIds))
                ->whereNull($phase->statusColumn())
                ->get();

            $lines->each(fn (RentOutChecklistLine $line) => $line->update([$phase->statusColumn() => ChecklistItemStatus::Ok->value]));
            $count = $lines->count();

            $return['success'] = true;
            $return['message'] = $count === 1 ? '1 item marked' : "{$count} items marked";
            $return['data'] = $count;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}

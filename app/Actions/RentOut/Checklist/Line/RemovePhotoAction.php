<?php

namespace App\Actions\RentOut\Checklist\Line;

use App\Enums\RentOut\ChecklistPhase;
use App\Models\RentOutChecklistLine;
use Illuminate\Support\Facades\Storage;

/**
 * Drop the photo taken of a checklist item for one phase, file and all.
 */
class RemovePhotoAction
{
    public function execute($rentOutId, $lineId, ChecklistPhase $phase)
    {
        try {
            $line = RentOutChecklistLine::where('rent_out_id', $rentOutId)->findOrFail($lineId);
            $column = $phase->imageColumn();

            if ($line->{$column} && Storage::disk('public')->exists($line->{$column})) {
                Storage::disk('public')->delete($line->{$column});
            }

            $line->update([$column => null]);

            $return['success'] = true;
            $return['message'] = 'Photo removed';
            $return['data'] = $line;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}

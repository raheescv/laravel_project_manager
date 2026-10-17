<?php

namespace App\Actions\RentOut\Checklist\Line;

use App\Enums\RentOut\ChecklistPhase;
use App\Models\RentOutChecklistLine;
use App\Support\ChecklistPhoto;
use Illuminate\Http\UploadedFile;

/**
 * Store the photo taken of a checklist item for one phase, replacing that phase's
 * previous photo. Same folder as the web tab's line uploads.
 */
class SavePhotoAction
{
    public function execute($rentOutId, $lineId, ChecklistPhase $phase, UploadedFile $photo)
    {
        try {
            $line = RentOutChecklistLine::where('rent_out_id', $rentOutId)->findOrFail($lineId);
            $column = $phase->imageColumn();

            $path = ChecklistPhoto::replace($photo, 'rent-out-checklist/'.$line->rent_out_id, $line->{$column});

            $line->update([$column => $path]);

            $return['success'] = true;
            $return['message'] = 'Photo saved';
            $return['data'] = $line;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}

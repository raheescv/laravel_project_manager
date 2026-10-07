<?php

namespace App\Actions\RentOut\Checklist\Line;

use App\Enums\RentOut\ChecklistPhase;
use App\Models\RentOutChecklistLine;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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

            $path = $photo->store('rent-out-checklist/'.$line->rent_out_id, 'public');

            $old = $line->{$column};
            if ($old && $old !== $path && Storage::disk('public')->exists($old)) {
                Storage::disk('public')->delete($old);
            }

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

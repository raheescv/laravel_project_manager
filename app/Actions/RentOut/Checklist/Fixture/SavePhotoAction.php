<?php

namespace App\Actions\RentOut\Checklist\Fixture;

use App\Models\RentOutFixtureEntry;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Store the before or after photo of a Fixture Comments entry, replacing the
 * previous one. Same folder as the web tab's fixture uploads.
 */
class SavePhotoAction
{
    public function execute($entryId, string $which, UploadedFile $photo)
    {
        try {
            if (! in_array($which, ['before', 'after'], true)) {
                throw new \Exception('A fixture photo is either "before" or "after".');
            }

            $entry = RentOutFixtureEntry::with('area')->findOrFail($entryId);
            $column = $which.'_image_path';

            $path = $photo->store('rent-out-fixtures/'.$entry->area->rent_out_id, 'public');

            $old = $entry->{$column};
            if ($old && $old !== $path && Storage::disk('public')->exists($old)) {
                Storage::disk('public')->delete($old);
            }

            $entry->update([$column => $path]);

            $return['success'] = true;
            $return['message'] = 'Photo saved';
            $return['data'] = $entry;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}

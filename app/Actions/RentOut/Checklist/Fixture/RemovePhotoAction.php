<?php

namespace App\Actions\RentOut\Checklist\Fixture;

use App\Models\RentOutFixtureEntry;
use App\Support\ChecklistPhoto;

/**
 * Drop the before or after photo of a Fixture Comments entry, file and all.
 */
class RemovePhotoAction
{
    public function execute($entryId, string $which)
    {
        try {
            if (! in_array($which, ['before', 'after'], true)) {
                throw new \Exception('A fixture photo is either "before" or "after".');
            }

            $entry = RentOutFixtureEntry::findOrFail($entryId);
            $column = $which.'_image_path';

            ChecklistPhoto::delete($entry->{$column});
            $entry->update([$column => null]);

            $return['success'] = true;
            $return['message'] = 'Photo removed';
            $return['data'] = $entry;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}

<?php

namespace App\Actions\RentOut\Checklist\Fixture;

use App\Models\RentOutFixtureEntry;
use App\Support\ChecklistPhoto;

/**
 * Remove one Fixture Comments entry along with its before/after photos, as the
 * web tab's removeFixtureEntry does. The area block itself stays.
 */
class DeleteAction
{
    public function execute($entryId)
    {
        try {
            $entry = RentOutFixtureEntry::findOrFail($entryId);

            ChecklistPhoto::delete($entry->before_image_path, $entry->after_image_path);

            $entry->delete();

            $return['success'] = true;
            $return['message'] = 'Fixture comment removed';
            $return['data'] = null;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}

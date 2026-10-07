<?php

namespace App\Actions\RentOut\Checklist\Fixture;

use App\Models\RentOutFixtureEntry;
use Illuminate\Support\Facades\Storage;

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

            foreach (['before_image_path', 'after_image_path'] as $column) {
                if ($entry->{$column} && Storage::disk('public')->exists($entry->{$column})) {
                    Storage::disk('public')->delete($entry->{$column});
                }
            }

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

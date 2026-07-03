<?php

namespace App\Actions\Maintenance\Complaint;

use App\Models\SupplyRequestNote;

/**
 * Delete a supply-request note. Mirrors Complaint::deleteNote. Shared by
 * web + mobile.
 */
class DeleteNoteAction
{
    public function execute($noteId)
    {
        try {
            $note = SupplyRequestNote::find($noteId);
            if ($note) {
                $note->delete();
            }

            $return['success'] = true;
            $return['message'] = 'Note deleted successfully';
            $return['data'] = null;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}

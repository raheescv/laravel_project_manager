<?php

namespace App\Actions\V1\Technician;

use App\Actions\Maintenance\Complaint\DeleteNoteAction as SharedDeleteNoteAction;
use App\Actions\V1\Technician\Concerns\InteractsWithComplaint;
use App\Actions\V1\Technician\Concerns\LogsApiActivity;
use App\Models\MaintenanceComplaint;
use App\Models\SupplyRequestNote;

/**
 * Mobile wrapper for deleting a note: scopes to the technician's own complaint,
 * delegates to the shared action, then re-fetches the detail. Mirrors
 * Complaint::deleteNote.
 */
class DeleteNoteAction
{
    use InteractsWithComplaint, LogsApiActivity;

    public function __construct(private readonly SharedDeleteNoteAction $action = new SharedDeleteNoteAction()) {}

    public function execute(int $noteId): MaintenanceComplaint
    {
        return $this->withApiLog('Technician Delete Note', ['note_id' => $noteId], function () use ($noteId) {
            $note = SupplyRequestNote::findOrFail($noteId);
            $mc = $this->findOwnedComplaintBySupplyRequest($note->supply_request_id);

            $this->runShared($this->action->execute($noteId));

            return $this->findOwnedComplaintWithDetail($mc->id);
        });
    }
}

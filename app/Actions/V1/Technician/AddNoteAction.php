<?php

namespace App\Actions\V1\Technician;

use App\Actions\Maintenance\Complaint\AddNoteAction as SharedAddNoteAction;
use App\Actions\V1\Technician\Concerns\InteractsWithComplaint;
use App\Actions\V1\Technician\Concerns\LogsApiActivity;
use App\Models\MaintenanceComplaint;
use Illuminate\Support\Facades\Auth;

/**
 * Mobile wrapper for adding a note: scopes to the technician's own complaint,
 * delegates to the shared action, then re-fetches the detail. Mirrors
 * Complaint::addNote.
 */
class AddNoteAction
{
    use InteractsWithComplaint, LogsApiActivity;

    public function __construct(private readonly SharedAddNoteAction $action = new SharedAddNoteAction()) {}

    public function execute(int $id, string $note): MaintenanceComplaint
    {
        return $this->withApiLog('Technician Add Note', ['complaint_id' => $id], function () use ($id, $note) {
            $this->findOwnedComplaint($id); // 404s unless assigned to this technician

            $this->runShared($this->action->execute($id, $note, Auth::id()));

            return $this->findOwnedComplaintWithDetail($id);
        });
    }
}

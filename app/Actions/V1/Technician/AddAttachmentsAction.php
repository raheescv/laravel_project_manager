<?php

namespace App\Actions\V1\Technician;

use App\Actions\Maintenance\Complaint\AddAttachmentsAction as SharedAddAttachmentsAction;
use App\Actions\V1\Technician\Concerns\InteractsWithComplaint;
use App\Actions\V1\Technician\Concerns\LogsApiActivity;
use App\Models\MaintenanceComplaint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;

/**
 * Mobile wrapper for uploading attachments: scopes to the technician's own
 * complaint, delegates to the shared action, then re-fetches the detail.
 * Mirrors Complaint::updatedImages.
 */
class AddAttachmentsAction
{
    use InteractsWithComplaint, LogsApiActivity;

    public function __construct(private readonly SharedAddAttachmentsAction $action = new SharedAddAttachmentsAction()) {}

    /**
     * @param  array<int, UploadedFile>  $files
     */
    public function execute(int $id, array $files): MaintenanceComplaint
    {
        return $this->withApiLog('Technician Add Attachments', ['complaint_id' => $id, 'count' => count($files)], function () use ($id, $files) {
            $this->findOwnedComplaint($id); // 404s unless assigned to this technician

            $this->runShared($this->action->execute($id, $files, Auth::id()));

            return $this->findOwnedComplaintWithDetail($id);
        });
    }
}

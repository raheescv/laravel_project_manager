<?php

namespace App\Actions\V1\Technician;

use App\Actions\Maintenance\Complaint\CompleteAction as SharedCompleteAction;
use App\Actions\V1\Technician\Concerns\InteractsWithComplaint;
use App\Actions\V1\Technician\Concerns\LogsApiActivity;
use App\Models\MaintenanceComplaint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Mobile wrapper for completing a complaint: requires a non-empty remark (as
 * the web detail page does), scopes to the technician's own complaint, then
 * delegates to the shared action (which also auto-completes the parent
 * maintenance). Mirrors Complaint::save('completed').
 */
class CompleteAction
{
    use InteractsWithComplaint, LogsApiActivity;

    public function __construct(private readonly SharedCompleteAction $action = new SharedCompleteAction()) {}

    public function execute(int $id, ?string $remark): MaintenanceComplaint
    {
        return $this->withApiLog('Technician Complete Complaint', ['complaint_id' => $id], function () use ($id, $remark) {
            $remark = trim((string) $remark);
            if ($remark === '') {
                throw ValidationException::withMessages([
                    'technician_remark' => 'The technician remark is required to complete.',
                ]);
            }

            $this->findOwnedComplaint($id); // 404s unless assigned to this technician

            $this->runShared($this->action->execute($id, Auth::id(), $remark));

            return $this->findOwnedComplaintWithDetail($id);
        });
    }
}

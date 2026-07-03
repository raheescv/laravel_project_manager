<?php

namespace App\Actions\V1\Technician;

use App\Actions\Maintenance\Complaint\SaveRemarkAction as SharedSaveRemarkAction;
use App\Actions\V1\Technician\Concerns\InteractsWithComplaint;
use App\Actions\V1\Technician\Concerns\LogsApiActivity;
use App\Models\MaintenanceComplaint;
use Illuminate\Support\Facades\Auth;

/**
 * Mobile wrapper for saving the technician remark: scopes to the technician's
 * own complaint, delegates to the shared complaint action, then re-fetches the
 * detail payload. Mirrors Complaint::save('pending').
 */
class SaveRemarkAction
{
    use InteractsWithComplaint, LogsApiActivity;

    public function __construct(private readonly SharedSaveRemarkAction $action = new SharedSaveRemarkAction()) {}

    public function execute(int $id, ?string $remark): MaintenanceComplaint
    {
        return $this->withApiLog('Technician Save Remark', ['complaint_id' => $id], function () use ($id, $remark) {
            $this->findOwnedComplaint($id); // 404s unless assigned to this technician

            $this->runShared($this->action->execute($id, $remark, Auth::id()));

            return $this->findOwnedComplaintWithDetail($id);
        });
    }
}

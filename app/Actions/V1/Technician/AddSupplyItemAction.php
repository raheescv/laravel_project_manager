<?php

namespace App\Actions\V1\Technician;

use App\Actions\Maintenance\Complaint\AddSupplyItemAction as SharedAddSupplyItemAction;
use App\Actions\V1\Technician\Concerns\InteractsWithComplaint;
use App\Actions\V1\Technician\Concerns\LogsApiActivity;
use App\Models\MaintenanceComplaint;
use Illuminate\Support\Facades\Auth;

/**
 * Mobile wrapper for adding a supply item: scopes to the technician's own
 * complaint, delegates to the shared action (barcode lookup, lazy supply
 * request, totals), then re-fetches the detail. Mirrors Complaint::addCart.
 */
class AddSupplyItemAction
{
    use InteractsWithComplaint, LogsApiActivity;

    public function __construct(private readonly SharedAddSupplyItemAction $action = new SharedAddSupplyItemAction()) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(int $id, array $data): MaintenanceComplaint
    {
        return $this->withApiLog('Technician Add Supply Item', ['complaint_id' => $id], function () use ($id, $data) {
            $this->findOwnedComplaint($id); // 404s unless assigned to this technician

            $this->runShared($this->action->execute($id, $data, Auth::id()));

            return $this->findOwnedComplaintWithDetail($id);
        });
    }
}

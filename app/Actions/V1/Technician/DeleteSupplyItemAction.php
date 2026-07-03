<?php

namespace App\Actions\V1\Technician;

use App\Actions\Maintenance\Complaint\DeleteSupplyItemAction as SharedDeleteSupplyItemAction;
use App\Actions\V1\Technician\Concerns\InteractsWithComplaint;
use App\Actions\V1\Technician\Concerns\LogsApiActivity;
use App\Models\MaintenanceComplaint;
use App\Models\SupplyRequestItem;

/**
 * Mobile wrapper for deleting a supply item: scopes to the technician's own
 * complaint, delegates to the shared action, then re-fetches the detail.
 * Mirrors Complaint::deleteItem.
 */
class DeleteSupplyItemAction
{
    use InteractsWithComplaint, LogsApiActivity;

    public function __construct(private readonly SharedDeleteSupplyItemAction $action = new SharedDeleteSupplyItemAction()) {}

    public function execute(int $itemId): MaintenanceComplaint
    {
        return $this->withApiLog('Technician Delete Supply Item', ['item_id' => $itemId], function () use ($itemId) {
            $item = SupplyRequestItem::findOrFail($itemId);
            $mc = $this->findOwnedComplaintBySupplyRequest($item->supply_request_id);

            $this->runShared($this->action->execute($itemId));

            return $this->findOwnedComplaintWithDetail($mc->id);
        });
    }
}

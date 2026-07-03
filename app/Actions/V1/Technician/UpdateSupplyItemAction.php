<?php

namespace App\Actions\V1\Technician;

use App\Actions\Maintenance\Complaint\UpdateSupplyItemAction as SharedUpdateSupplyItemAction;
use App\Actions\V1\Technician\Concerns\InteractsWithComplaint;
use App\Actions\V1\Technician\Concerns\LogsApiActivity;
use App\Models\MaintenanceComplaint;
use App\Models\SupplyRequestItem;

/**
 * Mobile wrapper for editing a supply item: scopes to the technician's own
 * complaint, delegates to the shared action, then re-fetches the detail.
 * Mirrors Complaint::editCartItem.
 */
class UpdateSupplyItemAction
{
    use InteractsWithComplaint, LogsApiActivity;

    public function __construct(private readonly SharedUpdateSupplyItemAction $action = new SharedUpdateSupplyItemAction()) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(int $itemId, array $data): MaintenanceComplaint
    {
        return $this->withApiLog('Technician Update Supply Item', ['item_id' => $itemId], function () use ($itemId, $data) {
            $item = SupplyRequestItem::findOrFail($itemId);
            $mc = $this->findOwnedComplaintBySupplyRequest($item->supply_request_id);

            $this->runShared($this->action->execute($itemId, $data));

            return $this->findOwnedComplaintWithDetail($mc->id);
        });
    }
}

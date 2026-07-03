<?php

namespace App\Actions\V1\Technician;

use App\Actions\Maintenance\Complaint\DeleteAttachmentAction as SharedDeleteAttachmentAction;
use App\Actions\V1\Technician\Concerns\InteractsWithComplaint;
use App\Actions\V1\Technician\Concerns\LogsApiActivity;
use App\Models\MaintenanceComplaint;
use App\Models\SupplyRequestImage;

/**
 * Mobile wrapper for deleting an attachment: scopes to the technician's own
 * complaint, delegates to the shared action, then re-fetches the detail.
 * Mirrors Complaint::deleteImage.
 */
class DeleteAttachmentAction
{
    use InteractsWithComplaint, LogsApiActivity;

    public function __construct(private readonly SharedDeleteAttachmentAction $action = new SharedDeleteAttachmentAction()) {}

    public function execute(int $imageId): MaintenanceComplaint
    {
        return $this->withApiLog('Technician Delete Attachment', ['image_id' => $imageId], function () use ($imageId) {
            $image = SupplyRequestImage::findOrFail($imageId);
            $mc = $this->findOwnedComplaintBySupplyRequest($image->supply_request_id);

            $this->runShared($this->action->execute($imageId));

            return $this->findOwnedComplaintWithDetail($mc->id);
        });
    }
}

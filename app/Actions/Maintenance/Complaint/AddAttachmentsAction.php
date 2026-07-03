<?php

namespace App\Actions\Maintenance\Complaint;

use App\Actions\Maintenance\Complaint\Concerns\ManagesSupplyRequest;
use App\Models\MaintenanceComplaint;
use App\Models\SupplyRequestImage;
use Illuminate\Support\Facades\DB;

/**
 * Store one or more uploaded files (image / video / pdf / doc) against the
 * complaint's supply request. Mirrors Complaint::updatedImages, using
 * SupplyRequestImage::storeFile. Shared by web + mobile.
 */
class AddAttachmentsAction
{
    use ManagesSupplyRequest;

    /**
     * @param  array<int, \Illuminate\Http\UploadedFile>  $files
     */
    public function execute($complaintId, array $files, $userId)
    {
        try {
            $mc = MaintenanceComplaint::with('maintenance')->find($complaintId);
            if (! $mc) {
                throw new \Exception("Maintenance Complaint not found with the specified ID: $complaintId.", 1);
            }

            DB::transaction(function () use ($mc, $files, $userId) {
                $sr = $this->getOrCreateSupplyRequest($mc, $userId);

                foreach ($files as $file) {
                    $image = new SupplyRequestImage();
                    $result = $image->storeFile($file, $sr->id);
                    if (! ($result['success'] ?? false)) {
                        throw new \Exception($result['message'] ?? 'Failed to upload file.');
                    }
                    $image->supply_request_id = $sr->id;
                    $image->name = $file->getClientOriginalName();
                    $image->type = $result['type'] ?? $file->getClientMimeType();
                    $image->path = $result['path'];
                    $image->save();
                }
            });

            $return['success'] = true;
            $return['message'] = 'Files uploaded successfully.';
            $return['data'] = null;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}

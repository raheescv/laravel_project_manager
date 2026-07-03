<?php

namespace App\Actions\Maintenance\Complaint;

use App\Models\SupplyRequestImage;
use Illuminate\Support\Facades\DB;

/**
 * Delete an attachment (stored file + row). Mirrors Complaint::deleteImage.
 * Shared by web + mobile.
 */
class DeleteAttachmentAction
{
    public function execute($imageId)
    {
        try {
            DB::transaction(function () use ($imageId) {
                $image = SupplyRequestImage::find($imageId);
                if ($image) {
                    $image->deleteFile();
                    $image->delete();
                }
            });

            $return['success'] = true;
            $return['message'] = 'Successfully deleted image';
            $return['data'] = null;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}

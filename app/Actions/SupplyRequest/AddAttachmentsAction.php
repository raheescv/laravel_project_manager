<?php

namespace App\Actions\SupplyRequest;

use App\Models\SupplyRequest;
use App\Models\SupplyRequestImage;

/**
 * Attach files to an existing supply request without touching its items —
 * used once the request is completed and the full save is closed.
 */
class AddAttachmentsAction
{
    /**
     * @param  array<int, \Illuminate\Http\UploadedFile>  $files
     * @return array{success: bool, message: string}
     */
    public function execute(int $supplyRequestId, array $files): array
    {
        try {
            $supplyRequest = SupplyRequest::find($supplyRequestId);
            if (! $supplyRequest) {
                throw new \Exception('Supply request not found.', 1);
            }
            if (! $files) {
                throw new \Exception('Please choose a file to upload.', 1);
            }

            foreach ($files as $file) {
                $result = (new SupplyRequestImage())->storeFile($file, $supplyRequest->id);
                if (! ($result['success'] ?? false)) {
                    throw new \Exception($result['message'] ?? 'Failed to upload file.');
                }
                SupplyRequestImage::create([
                    'supply_request_id' => $supplyRequest->id,
                    'name' => $result['fileName'],
                    'path' => $result['path'],
                    'type' => $result['type'],
                ]);
            }

            $return['success'] = true;
            $return['message'] = 'Files uploaded successfully.';
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}

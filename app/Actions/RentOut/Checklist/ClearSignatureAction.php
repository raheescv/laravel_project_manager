<?php

namespace App\Actions\RentOut\Checklist;

use App\Models\RentOutChecklistSignature;
use App\Support\ChecklistPhoto;

/**
 * Wipe a saved hand-over signature, image and all, so that signatory can sign
 * again. Allowed on a sealed phase too — clearing a signature simply reopens it.
 */
class ClearSignatureAction
{
    public function execute($rentOutId, string $phase, string $role)
    {
        try {
            $signature = RentOutChecklistSignature::query()
                ->where('rent_out_id', $rentOutId)
                ->where('phase', $phase)
                ->where('role', $role)
                ->firstOrFail();

            ChecklistPhoto::delete($signature->signature_path);
            $signature->delete();

            $return['success'] = true;
            $return['message'] = 'Signature cleared';
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}

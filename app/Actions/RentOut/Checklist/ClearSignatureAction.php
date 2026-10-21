<?php

namespace App\Actions\RentOut\Checklist;

use App\Enums\RentOut\ChecklistPhase;
use App\Models\RentOut;
use App\Models\RentOutChecklistSignature;
use App\Support\ChecklistPhoto;
use App\Support\RentOutChecklistState;

/**
 * Wipe a saved hand-over signature, image and all, so that signatory can sign
 * again. A sealed phase is a closed record and keeps its signatures.
 */
class ClearSignatureAction
{
    public function execute($rentOutId, string $phase, string $role)
    {
        try {
            $rentOut = RentOut::with('checklistSignatures')->findOrFail($rentOutId);

            if (RentOutChecklistState::isSealed($rentOut, ChecklistPhase::from($phase))) {
                throw new \Exception('This hand-over is sealed; its signatures can no longer be cleared.');
            }

            $signature = RentOutChecklistSignature::query()
                ->where('rent_out_id', $rentOut->id)
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

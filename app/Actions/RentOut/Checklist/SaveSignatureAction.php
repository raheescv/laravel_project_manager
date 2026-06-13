<?php

namespace App\Actions\RentOut\Checklist;

use App\Models\RentOutChecklistSignature;
use Illuminate\Support\Facades\Storage;

class SaveSignatureAction
{
    public function execute(array $data)
    {
        try {
            $img = str_replace('data:image/png;base64,', '', $data['signature']);
            $img = str_replace(' ', '+', $img);

            $path = "rent-out-checklists/{$data['rent_out_id']}/signatures/signature_{$data['phase']}_{$data['role']}_".time().'.png';

            Storage::disk('public')->put($path, base64_decode($img));

            $signature = RentOutChecklistSignature::updateOrCreate(
                [
                    'rent_out_id' => $data['rent_out_id'],
                    'phase' => $data['phase'],
                    'role' => $data['role'],
                ],
                [
                    'user_id' => $data['user_id'] ?? null,
                    'signer_name' => $data['signer_name'] ?? null,
                    'signature_path' => $path,
                    'signed_at' => now(),
                ]
            );

            $return['success'] = true;
            $return['message'] = 'Successfully Saved Signature';
            $return['data'] = $signature;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}

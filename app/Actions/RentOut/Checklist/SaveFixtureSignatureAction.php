<?php

namespace App\Actions\RentOut\Checklist;

use App\Models\RentOutFixtureArea;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SaveFixtureSignatureAction
{
    /**
     * Store the owner's acceptance signature against one Fixture Comments area.
     * Mirrors SaveSignatureAction — the pad hands over a PNG data URI.
     */
    public function execute(array $data)
    {
        try {
            $area = RentOutFixtureArea::where('rent_out_id', $data['rent_out_id'])
                ->where('id', $data['area_id'])
                ->firstOrFail();

            $img = str_replace('data:image/png;base64,', '', $data['signature']);
            $img = str_replace(' ', '+', $img);

            // The category is user-typed, so it is slugged before it reaches the filesystem.
            $slug = Str::slug($area->category) ?: 'area';
            $path = "rent-out-checklists/{$area->rent_out_id}/fixtures/owner_{$slug}_{$area->id}_".time().'.png';

            Storage::disk('public')->put($path, base64_decode($img));

            // Replace the previous signature file rather than leaving it orphaned.
            $old = $area->owner_signature_path;
            if ($old && $old !== $path && Storage::disk('public')->exists($old)) {
                Storage::disk('public')->delete($old);
            }

            $area->update([
                'owner_name' => $data['owner_name'] ?? $area->owner_name,
                'owner_user_id' => $data['owner_user_id'] ?? null,
                'owner_signature_path' => $path,
                'owner_signed_at' => now(),
            ]);

            $return['success'] = true;
            $return['message'] = 'Successfully Saved Signature';
            $return['data'] = $area;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}

<?php

namespace App\Actions\Maintenance\Complaint;

use App\Actions\Maintenance\Complaint\Concerns\ManagesSupplyRequest;
use App\Models\MaintenanceComplaint;
use App\Models\SupplyRequestNote;
use Illuminate\Support\Facades\DB;

/**
 * Add a note to the complaint's supply request (lazily creating it). Mirrors
 * Complaint::addNote. Shared by web + mobile.
 */
class AddNoteAction
{
    use ManagesSupplyRequest;

    public function execute($complaintId, $note, $userId)
    {
        try {
            $mc = MaintenanceComplaint::with('maintenance')->find($complaintId);
            if (! $mc) {
                throw new \Exception("Maintenance Complaint not found with the specified ID: $complaintId.", 1);
            }

            $model = DB::transaction(function () use ($mc, $note, $userId) {
                $sr = $this->getOrCreateSupplyRequest($mc, $userId);

                return SupplyRequestNote::create([
                    'supply_request_id' => $sr->id,
                    'note' => $note,
                    'created_by' => $userId,
                ]);
            });

            $return['success'] = true;
            $return['message'] = 'Note added successfully';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}

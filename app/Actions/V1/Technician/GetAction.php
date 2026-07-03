<?php

namespace App\Actions\V1\Technician;

use App\Actions\V1\Technician\Concerns\InteractsWithComplaint;
use App\Models\MaintenanceComplaint;

/**
 * Load a single assigned complaint with the full detail payload
 * (App\Livewire\Maintenance\Complaint::loadData).
 */
class GetAction
{
    use InteractsWithComplaint;

    public function execute(int $id): MaintenanceComplaint
    {
        return $this->findOwnedComplaintWithDetail($id);
    }
}

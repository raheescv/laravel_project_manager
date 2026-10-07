<?php

namespace App\Actions\V1\Technician\Checklist;

use App\Actions\V1\Technician\Concerns\InteractsWithChecklist;
use App\Models\RentOut;

/**
 * Load one coordinated rent-out with everything the checklist screens need.
 */
class GetAction
{
    use InteractsWithChecklist;

    public function execute(int $id): RentOut
    {
        return $this->findOwnedRentOutWithDetail($id);
    }
}

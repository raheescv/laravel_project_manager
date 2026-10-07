<?php

namespace App\Actions\V1\Technician\Checklist;

use App\Actions\RentOut\Checklist\Fixture\UpdateAction;
use App\Actions\V1\Technician\Concerns\InteractsWithChecklist;
use App\Actions\V1\Technician\Concerns\LogsApiActivity;
use App\Models\RentOut;

/**
 * Mobile wrapper: edit a Fixture Comments entry.
 */
class UpdateFixtureAction
{
    use InteractsWithChecklist, LogsApiActivity;

    public function __construct(private readonly UpdateAction $action = new UpdateAction()) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(int $entryId, array $data): RentOut
    {
        return $this->withApiLog('Technician Checklist Update Fixture', ['entry_id' => $entryId], function () use ($entryId, $data) {
            $rentOutId = $this->findOwnedFixtureEntry($entryId)->area->rent_out_id;

            $this->runShared($this->action->execute($entryId, $data));

            return $this->findOwnedRentOutWithDetail($rentOutId);
        });
    }
}

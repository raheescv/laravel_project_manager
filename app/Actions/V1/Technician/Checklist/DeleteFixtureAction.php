<?php

namespace App\Actions\V1\Technician\Checklist;

use App\Actions\RentOut\Checklist\Fixture\DeleteAction;
use App\Actions\V1\Technician\Concerns\InteractsWithChecklist;
use App\Actions\V1\Technician\Concerns\LogsApiActivity;
use App\Models\RentOut;

/**
 * Mobile wrapper: remove a Fixture Comments entry.
 */
class DeleteFixtureAction
{
    use InteractsWithChecklist, LogsApiActivity;

    public function __construct(private readonly DeleteAction $action = new DeleteAction()) {}

    public function execute(int $entryId): RentOut
    {
        return $this->withApiLog('Technician Checklist Delete Fixture', ['entry_id' => $entryId], function () use ($entryId) {
            $rentOutId = $this->findOwnedFixtureEntry($entryId)->area->rent_out_id;

            $this->runShared($this->action->execute($entryId));

            return $this->findOwnedRentOutWithDetail($rentOutId);
        });
    }
}

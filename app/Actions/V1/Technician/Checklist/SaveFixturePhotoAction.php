<?php

namespace App\Actions\V1\Technician\Checklist;

use App\Actions\RentOut\Checklist\Fixture\SavePhotoAction;
use App\Actions\V1\Technician\Concerns\InteractsWithChecklist;
use App\Actions\V1\Technician\Concerns\LogsApiActivity;
use App\Models\RentOut;
use Illuminate\Http\UploadedFile;

/**
 * Mobile wrapper: the before / after photo of a Fixture Comments entry.
 */
class SaveFixturePhotoAction
{
    use InteractsWithChecklist, LogsApiActivity;

    public function __construct(private readonly SavePhotoAction $action = new SavePhotoAction()) {}

    public function execute(int $entryId, string $which, UploadedFile $photo): RentOut
    {
        return $this->withApiLog('Technician Checklist Fixture Photo', ['entry_id' => $entryId, 'which' => $which], function () use ($entryId, $which, $photo) {
            $rentOutId = $this->findOwnedFixtureEntry($entryId)->area->rent_out_id;

            $this->runShared($this->action->execute($entryId, $which, $photo));

            return $this->findOwnedRentOutWithDetail($rentOutId);
        });
    }
}

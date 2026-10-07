<?php

namespace App\Actions\V1\Technician\Checklist;

use App\Actions\RentOut\Checklist\Line\SavePhotoAction;
use App\Actions\V1\Technician\Concerns\InteractsWithChecklist;
use App\Actions\V1\Technician\Concerns\LogsApiActivity;
use App\Enums\RentOut\ChecklistPhase;
use App\Models\RentOut;
use Illuminate\Http\UploadedFile;

/**
 * Mobile wrapper: the photo of one checklist item for one phase.
 */
class SaveLinePhotoAction
{
    use InteractsWithChecklist, LogsApiActivity;

    public function __construct(private readonly SavePhotoAction $action = new SavePhotoAction()) {}

    public function execute(int $id, int $lineId, ChecklistPhase $phase, UploadedFile $photo): RentOut
    {
        return $this->withApiLog('Technician Checklist Line Photo', ['rent_out_id' => $id, 'line_id' => $lineId], function () use ($id, $lineId, $phase, $photo) {
            $this->findOwnedRentOutFor($id, $phase);

            $this->runShared($this->action->execute($id, $lineId, $phase, $photo));

            return $this->findOwnedRentOutWithDetail($id);
        });
    }
}

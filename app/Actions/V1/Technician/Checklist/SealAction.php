<?php

namespace App\Actions\V1\Technician\Checklist;

use App\Actions\RentOut\Checklist\SealAction as SharedSealAction;
use App\Actions\V1\Technician\Concerns\InteractsWithChecklist;
use App\Actions\V1\Technician\Concerns\LogsApiActivity;
use App\Enums\RentOut\ChecklistPhase;
use App\Models\RentOut;

/**
 * Mobile wrapper: seal a fully-signed hand-over phase.
 */
class SealAction
{
    use InteractsWithChecklist, LogsApiActivity;

    public function __construct(private readonly SharedSealAction $action = new SharedSealAction()) {}

    /**
     * @param  array{actual_date?: ?string, remarks?: ?string}  $data
     */
    public function execute(int $id, ChecklistPhase $phase, array $data): RentOut
    {
        return $this->withApiLog('Technician Checklist Seal', ['rent_out_id' => $id, 'phase' => $phase->value], function () use ($id, $phase, $data) {
            $this->findOwnedRentOutFor($id, $phase);

            $this->runShared($this->action->execute($id, $phase, $data));

            return $this->findOwnedRentOutWithDetail($id);
        });
    }
}

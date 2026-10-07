<?php

namespace App\Actions\V1\Technician\Checklist;

use App\Actions\RentOut\Checklist\Line\MarkOkAction as SharedMarkOkAction;
use App\Actions\V1\Technician\Concerns\InteractsWithChecklist;
use App\Actions\V1\Technician\Concerns\LogsApiActivity;
use App\Enums\RentOut\ChecklistPhase;
use App\Models\RentOut;

/**
 * Mobile wrapper: "the rest of this room is fine".
 */
class MarkOkAction
{
    use InteractsWithChecklist, LogsApiActivity;

    public function __construct(private readonly SharedMarkOkAction $action = new SharedMarkOkAction()) {}

    /**
     * @param  array<int, int>  $lineIds
     */
    public function execute(int $id, ChecklistPhase $phase, array $lineIds): RentOut
    {
        return $this->withApiLog('Technician Checklist Mark OK', ['rent_out_id' => $id, 'count' => count($lineIds)], function () use ($id, $phase, $lineIds) {
            $this->findOwnedRentOutFor($id, $phase);

            $this->runShared($this->action->execute($id, $phase, $lineIds));

            return $this->findOwnedRentOutWithDetail($id);
        });
    }
}

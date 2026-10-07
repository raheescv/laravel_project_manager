<?php

namespace App\Actions\V1\Technician\Checklist;

use App\Actions\RentOut\Checklist\Line\UpdateAction;
use App\Actions\V1\Technician\Concerns\InteractsWithChecklist;
use App\Actions\V1\Technician\Concerns\LogsApiActivity;
use App\Enums\RentOut\ChecklistPhase;
use App\Models\RentOut;

/**
 * Mobile wrapper: record one checklist item's status / comment / qty / damage cost.
 */
class UpdateLineAction
{
    use InteractsWithChecklist, LogsApiActivity;

    public function __construct(private readonly UpdateAction $action = new UpdateAction()) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(int $id, int $lineId, ChecklistPhase $phase, array $data): RentOut
    {
        return $this->withApiLog('Technician Checklist Update Line', ['rent_out_id' => $id, 'line_id' => $lineId], function () use ($id, $lineId, $phase, $data) {
            $this->findOwnedRentOutFor($id, $phase);

            $this->runShared($this->action->execute($id, $lineId, $phase, $data));

            return $this->findOwnedRentOutWithDetail($id);
        });
    }
}

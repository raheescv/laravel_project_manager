<?php

namespace App\Actions\V1\Technician\Checklist;

use App\Actions\RentOut\Checklist\SaveFixtureSignatureAction;
use App\Actions\V1\Technician\Concerns\InteractsWithChecklist;
use App\Actions\V1\Technician\Concerns\LogsApiActivity;
use App\Models\RentOut;
use App\Models\RentOutFixtureArea;
use Illuminate\Validation\ValidationException;

/**
 * Mobile wrapper: the owner's acceptance of an area's rectification work. As on
 * the web, the pad only opens once every entry in the area reads Completed.
 */
class SignFixtureAction
{
    use InteractsWithChecklist, LogsApiActivity;

    public function __construct(private readonly SaveFixtureSignatureAction $action = new SaveFixtureSignatureAction()) {}

    public function execute(int $id, int $areaId, string $ownerName, string $signature): RentOut
    {
        return $this->withApiLog('Technician Checklist Fixture Sign', ['rent_out_id' => $id, 'area_id' => $areaId], function () use ($id, $areaId, $ownerName, $signature) {
            $this->findOwnedRentOut($id);

            $area = RentOutFixtureArea::with('entries')->where('rent_out_id', $id)->findOrFail($areaId);
            if (! $area->isReadyForAcceptance()) {
                throw ValidationException::withMessages([
                    'signature' => "Every fixture in {$area->category} must be completed before the owner signs.",
                ]);
            }

            $this->runShared($this->action->execute([
                'rent_out_id' => $id,
                'area_id' => $areaId,
                'owner_name' => trim($ownerName),
                'signature' => $signature,
            ]));

            return $this->findOwnedRentOutWithDetail($id);
        });
    }
}

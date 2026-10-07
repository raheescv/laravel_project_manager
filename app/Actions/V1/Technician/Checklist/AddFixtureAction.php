<?php

namespace App\Actions\V1\Technician\Checklist;

use App\Actions\RentOut\Checklist\Fixture\CreateAction;
use App\Actions\V1\Technician\Concerns\InteractsWithChecklist;
use App\Actions\V1\Technician\Concerns\LogsApiActivity;
use App\Models\RentOut;

/**
 * Mobile wrapper: add a Fixture Comments entry to an area.
 */
class AddFixtureAction
{
    use InteractsWithChecklist, LogsApiActivity;

    public function __construct(private readonly CreateAction $action = new CreateAction()) {}

    /**
     * @param  array{comments?: ?string, status?: ?string}  $data
     */
    public function execute(int $id, string $category, array $data): RentOut
    {
        return $this->withApiLog('Technician Checklist Add Fixture', ['rent_out_id' => $id, 'category' => $category], function () use ($id, $category, $data) {
            $this->findOwnedRentOut($id);

            $this->runShared($this->action->execute($id, $category, $data));

            return $this->findOwnedRentOutWithDetail($id);
        });
    }
}

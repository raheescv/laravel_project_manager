<?php

namespace App\Actions\Tenant;

use App\Models\Tenant;
use App\Services\TenantService;

class ToggleStatusAction
{
    /**
     * Switch a tenant on or off. The tenant the super admin is signed into can
     * never be switched off — that would lock them out of the very screen they
     * need to switch it back on.
     */
    public function execute(int $id, bool $isActive): array
    {
        try {
            $model = Tenant::find($id);
            if (! $model) {
                throw new \Exception("Tenant not found with the specified ID: $id.", 1);
            }
            if (! $isActive && $model->id === app(TenantService::class)->getCurrentTenantId()) {
                throw new \Exception('You cannot deactivate the tenant you are signed into.', 1);
            }
            $model->update(['is_active' => $isActive]);

            $return['success'] = true;
            $return['message'] = $isActive ? "{$model->name} is active" : "{$model->name} is deactivated";
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}

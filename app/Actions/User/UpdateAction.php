<?php

namespace App\Actions\User;

use App\Models\User;
use Illuminate\Support\Arr;

class UpdateAction
{
    /**
     * Fields that must never be mass-assigned from request/form input.
     * Privilege flags are granted only through deliberate, separately
     * authorized flows; tenant_id is set automatically by BelongsToTenant.
     */
    private const PROTECTED_FIELDS = [
        'id', 'is_admin', 'is_super_admin', 'tenant_id',
        'created_at', 'updated_at', 'deleted_at',
    ];

    public function execute($data, $id)
    {
        try {
            $model = User::find($id);
            if (! $model) {
                throw new \Exception("User not found with the specified ID: $id.", 1);
            }
            $data = Arr::except($data, self::PROTECTED_FIELDS);
            validationHelper(User::updateRules($id), $data);
            $model->update($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Updated User';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}

<?php

namespace App\Actions\User;

use App\Models\User;
use Illuminate\Support\Arr;

class CreateAction
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

    public function execute($data)
    {
        try {
            $data = Arr::except($data, self::PROTECTED_FIELDS);
            $data['password'] = $data['password'] ?? 'password';
            validationHelper(User::createRules(), $data);
            $model = User::create($data);
            $return['success'] = true;
            $return['message'] = 'Successfully Created User';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}

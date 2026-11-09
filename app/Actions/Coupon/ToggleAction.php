<?php

namespace App\Actions\Coupon;

use App\Models\Coupon;

class ToggleAction
{
    /**
     * Switch a coupon on or off without touching anything else on it.
     *
     * @return array{success: bool, message: string, data?: Coupon}
     */
    public function execute(int $id, bool $isActive, int $userId): array
    {
        try {
            $model = Coupon::findOrFail($id);
            $model->update(['is_active' => $isActive, 'updated_by' => $userId]);

            $return['data'] = $model;
            $return['success'] = true;
            $return['message'] = $isActive ? "{$model->code} is on." : "{$model->code} is paused.";
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}

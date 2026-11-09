<?php

namespace App\Actions\Coupon;

use App\Models\Coupon;

class UpdateAction
{
    /**
     * @param  array<string, mixed>  $data
     * @return array{success: bool, message: string, data?: Coupon}
     */
    public function execute(array $data, int $id, int $userId): array
    {
        try {
            $model = Coupon::findOrFail($id);
            validationHelper(Coupon::rules($id), $data);

            $model->update(Coupon::attributesFrom($data) + ['updated_by' => $userId]);

            $return['data'] = $model;
            $return['success'] = true;
            $return['message'] = 'Coupon updated.';
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}

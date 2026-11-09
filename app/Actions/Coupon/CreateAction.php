<?php

namespace App\Actions\Coupon;

use App\Models\Coupon;

class CreateAction
{
    /**
     * @param  array<string, mixed>  $data
     * @return array{success: bool, message: string, data?: Coupon}
     */
    public function execute(array $data, int $userId): array
    {
        try {
            validationHelper(Coupon::rules(), $data);

            $return['data'] = Coupon::create(Coupon::attributesFrom($data) + ['created_by' => $userId, 'updated_by' => $userId]);
            $return['success'] = true;
            $return['message'] = 'Coupon created.';
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}

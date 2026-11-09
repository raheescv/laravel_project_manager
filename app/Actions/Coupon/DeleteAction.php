<?php

namespace App\Actions\Coupon;

use App\Models\Coupon;
use Exception;

class DeleteAction
{
    /**
     * Delete a coupon nobody has paid with. One that has been used stays, so its
     * history keeps a name; it can be paused instead.
     *
     * @return array{success: bool, message: string, data?: Coupon}
     */
    public function execute(int $id): array
    {
        try {
            $model = Coupon::findOrFail($id);

            if ($model->redemptions()->exists()) {
                throw new Exception('Customers have used this coupon. Pause it instead.', 1);
            }

            $model->delete();

            $return['data'] = $model;
            $return['success'] = true;
            $return['message'] = 'Coupon deleted.';
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}

<?php

namespace App\Actions\RentOut\Security;

use App\Models\RentOutSecurity;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = RentOutSecurity::find($id);
            if (! $model) {
                throw new \Exception("RentOut Security not found with the specified ID: $id.", 1);
            }
            if (! $model->delete()) {
                throw new \Exception('Oops! Something went wrong while deleting the Security Deposit. Please try again.', 1);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Deleted Security Deposit';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}

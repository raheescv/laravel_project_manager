<?php

namespace App\Actions\ComboOffer;

use App\Models\ComboOffer;

class UpdateAction
{
    public function execute($data, $id)
    {
        try {
            $model = ComboOffer::find($id);
            if (! $model) {
                throw new \Exception("ComboOffer not found with the specified ID: $id.", 1);
            }

            validationHelper(ComboOffer::rules($id), $data);

            $model->update($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Update ComboOffer';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}

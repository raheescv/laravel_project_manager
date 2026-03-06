<?php

namespace App\Actions\Settings\Rack;

use App\Models\Rack;

class UpdateAction
{
    public function execute($data, $id)
    {
        try {
            $model = Rack::find($id);
            if (! $model) {
                throw new \Exception("Rack not found with the specified ID: $id.", 1);
            }
            validationHelper(Rack::rules($id), $data);
            $model->update($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Updated Rack';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}

<?php

namespace App\Actions\Settings\Holiday;

use App\Models\Holiday;

class DeleteAction
{
    public function execute($id, $userId)
    {
        try {
            $model = Holiday::findOrFail($id);
            $model->updated_by = $userId;
            $model->save();
            $model->delete();

            $return['success'] = true;
            $return['message'] = 'Successfully deleted the holiday';
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}

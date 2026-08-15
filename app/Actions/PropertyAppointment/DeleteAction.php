<?php

namespace App\Actions\PropertyAppointment;

use App\Models\PropertyAppointment;

class DeleteAction
{
    public function execute($id, $userId)
    {
        try {
            $model = PropertyAppointment::findOrFail($id);

            if ($model->status === 'completed') {
                throw new \Exception('A completed appointment cannot be deleted. Cancel it instead if it needs to be undone.', 1);
            }

            $model->deleted_by = $userId;
            $model->save();
            $model->delete();

            $return['success'] = true;
            $return['message'] = 'Successfully deleted appointment';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}

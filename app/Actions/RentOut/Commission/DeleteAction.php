<?php

namespace App\Actions\RentOut\Commission;

use App\Models\RentOutCommission;
use Exception;

class DeleteAction
{
    public function execute(int $id, int $userId): array
    {
        try {
            $model = RentOutCommission::findOrFail($id);
            if (! $model->status->isEditable()) {
                throw new Exception('Only a draft or rejected commission can be deleted.', 1);
            }

            $model->update(['updated_by' => $userId]);
            $model->delete();

            $return['success'] = true;
            $return['message'] = 'Commission deleted.';
            $return['data'] = [];
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}

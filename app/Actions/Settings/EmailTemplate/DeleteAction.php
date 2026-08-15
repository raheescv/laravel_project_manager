<?php

namespace App\Actions\Settings\EmailTemplate;

use App\Models\EmailTemplate;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = EmailTemplate::findOrFail($id);

            if ($model->is_active) {
                throw new \Exception('Deactivate this template before deleting it — it is the one currently in use for '.$model->typeLabel().'.', 1);
            }

            $model->delete();

            $return['success'] = true;
            $return['message'] = 'Successfully deleted template';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}

<?php

namespace App\Actions\Settings\DocumentType;

use App\Models\DocumentType;

class UpdateAction
{
    public function execute($data, $id)
    {
        try {
            $model = DocumentType::find($id);
            if (! $model) {
                throw new \Exception("Document Type not found with the specified ID: $id.", 1);
            }
            $data['name'] = trim($data['name']);
            validationHelper(DocumentType::rules($id), $data);
            $model->update($data);
            $return['success'] = true;
            $return['message'] = 'Successfully Updated Document Type';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}

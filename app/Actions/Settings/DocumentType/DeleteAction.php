<?php

namespace App\Actions\Settings\DocumentType;

use App\Models\DocumentType;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = DocumentType::find($id);
            if (! $model) {
                throw new \Exception("Document Type not found with the specified ID: $id.", 1);
            }
            if ($model->documents()->exists()) {
                throw new \Exception('Cannot delete Document Type. There are documents using this type.', 1);
            }
            if (! $model->delete()) {
                throw new \Exception('Oops! Something went wrong while deleting the Document Type. Please try again.', 1);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Deleted Document Type';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}

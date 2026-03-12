<?php

namespace App\Actions\RentOut\Document;

use App\Models\RentOutDocument;
use Illuminate\Support\Facades\Storage;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = RentOutDocument::findOrFail($id);
            if ($model->path && Storage::exists($model->path)) {
                Storage::delete($model->path);
            }
            $model->delete();
            $return['success'] = true;
            $return['message'] = 'Document deleted successfully';
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}

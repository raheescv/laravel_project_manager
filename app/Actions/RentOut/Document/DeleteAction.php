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
            $path = preg_replace('#^public/#', '', $model->path);
            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
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

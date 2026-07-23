<?php

namespace App\Actions\RentOut\Document;

use App\Models\RentOut;
use App\Models\RentOutDocument;
use Illuminate\Support\Facades\Storage;

class UpdateAction
{
    public function execute($id, $data)
    {
        try {
            $model = RentOutDocument::findOrFail($id);

            $oldPath = $model->path;
            $fileReplaced = ! empty($data['path']) && $data['path'] !== $oldPath;

            if (empty($data['account_id']) && ! empty($data['rent_out_id'])) {
                $data['account_id'] = RentOut::whereKey($data['rent_out_id'])->value('account_id');
            }

            $payload = array_merge([
                'rent_out_id' => $model->rent_out_id,
                'document_type_id' => $model->document_type_id,
                'name' => $model->name,
                'path' => $model->path,
            ], $data);
            validationHelper(RentOutDocument::rules($id), $payload, 'RentOut Document');

            $model->update($data);

            // Remove the replaced file only after the record is saved successfully.
            if ($fileReplaced) {
                $relative = preg_replace('#^public/#', '', $oldPath);
                if ($relative && Storage::disk('public')->exists($relative)) {
                    Storage::disk('public')->delete($relative);
                }
            }

            $return['success'] = true;
            $return['message'] = 'Document updated successfully';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}

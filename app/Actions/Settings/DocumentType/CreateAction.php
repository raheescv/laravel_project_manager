<?php

namespace App\Actions\Settings\DocumentType;

use App\Models\DocumentType;

class CreateAction
{
    public function execute($data)
    {
        try {
            $data['name'] = trim($data['name']);
            validationHelper(DocumentType::rules(), $data, 'Document Type');
            $exists = DocumentType::withTrashed()->firstWhere('name', $data['name']);
            if ($exists) {
                $model = $exists->restore();
            } else {
                $model = DocumentType::create($data);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Created Document Type';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}

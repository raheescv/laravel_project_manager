<?php

namespace App\Actions\RentOut\Document;

use App\Models\RentOutDocument;

class CreateAction
{
    public function execute($data)
    {
        try {
            validationHelper(RentOutDocument::rules(), $data, 'RentOut Document');
            $model = RentOutDocument::create($data);
            $return['success'] = true;
            $return['message'] = 'Document uploaded successfully';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}

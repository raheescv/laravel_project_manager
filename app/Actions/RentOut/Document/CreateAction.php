<?php

namespace App\Actions\RentOut\Document;

use App\Models\RentOut;
use App\Models\RentOutDocument;

class CreateAction
{
    public function execute($data)
    {
        try {
            validationHelper(RentOutDocument::rules(), $data, 'RentOut Document');
            if (empty($data['account_id']) && ! empty($data['rent_out_id'])) {
                $data['account_id'] = RentOut::whereKey($data['rent_out_id'])->value('account_id');
            }
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

<?php

namespace App\Actions\Package\Item;

use App\Models\PackageItem;
use Illuminate\Support\Facades\Auth;

class CreateAction
{
    public function execute($data)
    {
        try {
            $data['created_by'] = Auth::id();
            validationHelper(PackageItem::rules(), $data);
            $model = PackageItem::create($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Created Package Item';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}

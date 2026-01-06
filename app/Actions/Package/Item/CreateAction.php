<?php

namespace App\Actions\Package\Item;

use App\Models\PackageItem;
use Exception;
use Illuminate\Support\Facades\Auth;

class CreateAction
{
    public function execute($data)
    {
        try {
            $data['created_by'] = Auth::id();

            validationHelper(PackageItem::rules(0, [], $data), $data);
            $model = PackageItem::create($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Created Package Item';
            $return['data'] = $model;
        } catch (Exception $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }
}

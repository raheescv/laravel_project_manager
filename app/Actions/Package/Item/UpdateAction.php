<?php

namespace App\Actions\Package\Item;

use App\Models\PackageItem;
use Exception;
use Illuminate\Support\Facades\Auth;

class UpdateAction
{
    public function execute($data, $id)
    {
        try {
            $model = PackageItem::find($id);
            if (! $model) {
                throw new Exception("Package Item not found with the specified ID: $id.", 1);
            }
            $data['updated_by'] = Auth::id();

            validationHelper(PackageItem::rules($id, [], $data), $data);
            $model->update($data);

            $return['success'] = true;
            $return['message'] = 'Successfully Updated Package Item';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}

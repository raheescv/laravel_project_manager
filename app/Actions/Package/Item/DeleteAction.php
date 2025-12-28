<?php

namespace App\Actions\Package\Item;

use App\Models\PackageItem;
use Exception;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = PackageItem::find($id);
            if (! $model) {
                throw new Exception("Package Item not found with the specified ID: $id.", 1);
            }
            if (! $model->delete()) {
                throw new Exception('Oops! Something went wrong while deleting the Package Item. Please try again.', 1);
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Deleted Package Item';
            $return['data'] = [];
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}

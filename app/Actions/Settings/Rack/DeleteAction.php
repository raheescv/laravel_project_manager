<?php

namespace App\Actions\Settings\Rack;

use App\Models\Rack;
use Exception;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = Rack::find($id);
            if (! $model) {
                throw new Exception("Rack not found with the specified ID: $id.", 1);
            }

            if ($model->orders->count() > 0) {
                throw new Exception('This rack is used by Tailoring Orders, so please update or remove those orders first.', 1);
            }

            if (! $model->delete()) {
                throw new Exception('Oops! Something went wrong while deleting the Rack. Please try again.', 1);
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Deleted Rack';
            $return['data'] = $model;
        } catch (Exception $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }
}

<?php

namespace App\Actions\Package;

use App\Models\Package;
use Exception;
use Illuminate\Support\Facades\Auth;

class CreateAction
{
    public function execute($data)
    {
        try {
            $userId = Auth::id();
            validationHelper(Package::rules(), $data);
            $data['created_by'] = $userId;
            $data['paid'] = $data['paid'] ?? 0;
            $model = Package::create($data);

            $model->refresh();

            $response = (new JournalEntryAction())->creditExecute($model, $userId);
            if (! $response['success']) {
                throw new Exception($response['message'], 1);
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Created Package';
            $return['data'] = $model;
        } catch (Exception $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }
}

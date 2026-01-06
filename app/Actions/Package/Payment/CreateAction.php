<?php

namespace App\Actions\Package\Payment;

use App\Actions\Package\JournalEntryAction;
use App\Models\PackagePayment;
use Exception;
use Illuminate\Support\Facades\Auth;

class CreateAction
{
    public function execute($data)
    {
        try {
            $userId = Auth::id();
            $data['created_by'] = $userId;
            validationHelper(PackagePayment::rules(), $data);

            $model = PackagePayment::create($data);

            $response = (new JournalEntryAction())->debitExecute($model, $userId);
            if (! $response['success']) {
                throw new Exception($response['message']);
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Created Package Payment';
            $return['data'] = $model;
        } catch (Exception $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }
}

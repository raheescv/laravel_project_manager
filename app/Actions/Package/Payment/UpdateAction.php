<?php

namespace App\Actions\Package\Payment;

use App\Actions\Package\JournalDeleteAction;
use App\Actions\Package\JournalEntryAction;
use App\Models\PackagePayment;
use Exception;
use Illuminate\Support\Facades\Auth;

class UpdateAction
{
    public function execute($data, $id)
    {
        try {
            $userId = Auth::id();
            $model = PackagePayment::find($id);
            if (! $model) {
                throw new Exception("Package Payment not found with the specified ID: $id.", 1);
            }
            $data['updated_by'] = $userId;
            validationHelper(PackagePayment::rules($id), $data);

            $model->update($data);

            $this->deleteJournalEntries($id, $userId);

            $this->createJournalEntries($model, $userId);

            $return['success'] = true;
            $return['message'] = 'Successfully Updated Package Payment';
            $return['data'] = $model;
        } catch (Exception $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }

    private function deleteJournalEntries($id, $userId)
    {
        $response = (new JournalDeleteAction())->executeByEntryId($id, $userId);
        if (! $response['success']) {
            throw new Exception($response['message'], 1);
        }
    }

    private function createJournalEntries($model, $userId)
    {
        $response = (new JournalEntryAction())->debitExecute($model, $userId);
        if (! $response['success']) {
            throw new Exception($response['message'], 1);
        }
    }
}

<?php

namespace App\Actions\Package;

use App\Models\Package;
use Exception;

class DeleteAction
{
    public $userId;

    public function execute($id, $userId)
    {
        try {
            $this->userId = $userId;
            $model = Package::find($id);
            if (! $model) {
                throw new Exception("Package not found with the specified ID: $id.", 1);
            }
            if (! $model->delete()) {
                throw new Exception('Oops! Something went wrong while deleting the Package. Please try again.', 1);
            }
            $this->deleteJournalEntries($model, $userId);
            $return['success'] = true;
            $return['message'] = 'Successfully Deleted Package';
            $return['data'] = [];
        } catch (Exception $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }

    private function deleteJournalEntries($model)
    {
        $response = (new JournalDeleteAction())->execute($model, $this->userId);
        if (! $response['success']) {
            throw new Exception($response['message'], 1);
        }
    }
}

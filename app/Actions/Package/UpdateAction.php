<?php

namespace App\Actions\Package;

use App\Models\Package;
use Exception;
use Illuminate\Support\Facades\Auth;

class UpdateAction
{
    public $userId;

    public $packageId;

    public $model;

    public function execute($data, $id)
    {
        try {
            $this->userId = Auth::id();
            $this->packageId = $id;

            $this->model = $model = Package::find($id);
            if (! $model) {
                throw new Exception("Package not found with the specified ID: $id.", 1);
            }

            if ($data['status'] != 'cancelled') {
                validationHelper(Package::rules($id), $data);
                $data['updated_by'] = $this->userId;
                $data['paid'] = $data['paid'] ?? $model->paid;
                $model->update($data);

                $this->deleteJournalEntries();

                $this->createJournalEntries();

            } else {
                $model->update(['status' => 'cancelled', 'updated_by' => $this->userId]);
                $this->deleteJournalEntries();
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Updated Package';
            $return['data'] = $this->model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }

    private function deleteJournalEntries()
    {
        $response = (new JournalDeleteAction())->execute($this->model, $this->userId);
        if (! $response['success']) {
            throw new Exception($response['message'], 1);
        }
    }

    private function createJournalEntries()
    {
        $response = (new JournalEntryAction())->creditExecute($this->model, $this->userId);
        if (! $response['success']) {
            throw new Exception($response['message'], 1);
        }
    }
}

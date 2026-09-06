<?php

namespace App\Actions\Purchase;

use App\Models\Purchase;
use Exception;
use Illuminate\Support\Facades\Auth;

class UpdateAction
{
    public $userId;

    public $model;

    public $oldStatus;

    public function execute($data, $purchase_id, $userId)
    {
        try {
            $this->userId = $userId;
            $this->model = $model = Purchase::find($purchase_id);
            if (! $model) {
                throw new Exception("Purchase not found with the specified ID: $purchase_id.", 1);
            }

            // Captured before the update: every rollback below has to know what the
            // purchase was posted as, not what it is being changed to.
            $this->oldStatus = $model->status;

            if ($data['status'] == 'cancelled') {
                $data['cancelled_by'] = $userId;
            } else {
                $data['updated_by'] = $userId;
            }

            validationHelper(Purchase::rules($purchase_id), $data);

            if ($data['status'] != 'cancelled') {
                // Reverse the posted stock and journals while the model still holds the
                // branch and items it was posted with.
                $this->rollbackIfCompleted();
            }

            $model->update($data);

            if ($data['status'] != 'cancelled') {

                foreach ($data['items'] as $value) {
                    $value['purchase_id'] = $purchase_id;

                    if (isset($value['id'])) {
                        $response = (new Item\UpdateAction())->execute($value, $value['id'], $userId);
                    } else {
                        $response = (new Item\CreateAction())->execute($value, $userId);
                    }

                    if (! $response['success']) {
                        throw new Exception($response['message'], 1);
                    }
                }

                foreach ($data['payments'] as $value) {
                    $value['purchase_id'] = $purchase_id;

                    if (isset($value['id'])) {
                        $response = (new Payment\UpdateAction())->execute($value, $value['id'], $userId);
                    } else {
                        $value['date'] = $model->date;
                        $response = (new Payment\CreateAction())->execute($value, $userId);
                    }

                    if (! $response['success']) {
                        throw new Exception($response['message'], 1);
                    }
                }

                if ($model['status'] == 'completed') {
                    $model->refresh();
                    $response = (new StockUpdateAction())->execute($model, $userId, 'purchase');
                    if (! $response['success']) {
                        throw new Exception($response['message'], 1);
                    }
                    $model->refresh();
                    $response = (new JournalEntryAction())->execute($model, $userId);
                    if (! $response['success']) {
                        throw new Exception($response['message'], 1);
                    }
                }
            } elseif ($this->oldStatus == 'completed') {
                // Only a completed purchase ever posted stock or journals, so only that
                // one has anything to reverse on cancel.
                $response = (new StockUpdateAction())->execute($model, $userId, 'cancel');
                if (! $response['success']) {
                    throw new Exception($response['message'], 1);
                }
                $response = (new JournalDeleteAction())->execute($model, $userId);
                if (! $response['success']) {
                    throw new Exception($response['message'], 1);
                }
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Update Purchase';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }

    private function rollbackIfCompleted()
    {
        if ($this->oldStatus == 'completed') {
            if (! Auth::user()->can('purchase.edit completed')) {
                throw new Exception("You don't have permission to edit it.", 1);
            }
            $response = (new JournalDeleteAction())->execute($this->model, $this->userId);
            if (! $response['success']) {
                throw new Exception($response['message'], 1);
            }
            $response = (new StockUpdateAction())->execute($this->model, $this->userId, 'purchase_reversal');
            if (! $response['success']) {
                throw new Exception($response['message'], 1);
            }
        }
    }
}

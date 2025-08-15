<?php

namespace App\Actions\Purchase;

use App\Models\Purchase;
use Exception;
use Illuminate\Support\Facades\Auth;

class UpdateAction
{
    public $userId;

    public $model;

    public function execute($data, $purchase_id, $userId)
    {
        try {
            $this->userId = $userId;
            $this->model = $model = Purchase::find($purchase_id);
            if (! $model) {
                throw new Exception("Purchase not found with the specified ID: $purchase_id.", 1);
            }

            if ($data['status'] == 'cancelled') {
                $data['cancelled_by'] = $userId;
            } else {
                $data['updated_by'] = $userId;
            }

            $this->rollbackIfCompleted();

            validationHelper(Purchase::rules($purchase_id), $data);
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
            } else {
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
        $oldStatus = $this->model->status;
        if ($oldStatus == 'completed') {
            if (! Auth::user()->can('sale.edit completed')) {
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

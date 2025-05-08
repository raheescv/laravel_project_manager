<?php

namespace App\Actions\Journal;

use App\Events\PurchaseUpdatedEvent;
use App\Events\SaleReturnUpdatedEvent;
use App\Events\SaleUpdatedEvent;
use App\Models\Journal;
use App\Models\PurchasePayment;
use App\Models\SalePayment;
use App\Models\SaleReturnPayment;
use Exception;

class DeleteAction
{
    public function execute($id, $userId)
    {
        try {
            $model = Journal::find($id);
            switch ($model->model) {
                case 'Sale':
                    if ($model->source == 'sale' && $model->sale) {
                        throw new Exception("You can't delete the Sale Journal without deleting the Sale", 1);
                    }
                    break;
                case 'Purchase':
                    if ($model->source == 'purchase' && $model->purchase) {
                        throw new Exception("You can't delete the Purchase Journal without deleting the Purchase", 1);
                    }
                    break;
                case 'SaleReturn':
                    if ($model->source == 'saleReturn' && $model->saleReturn) {
                        throw new Exception("You can't delete the sales return Journal without deleting the Sales Return", 1);
                    }
                    break;
            }
            if (! $model) {
                throw new Exception("Journal not found with the specified ID: $id.", 1);
            }

            $this->subModelDelete($model);

            $model->entries()->update(['deleted_by' => $userId]);
            $model->entries()->delete();

            $model->update(['deleted_by' => $userId]);
            if (! $model->delete()) {
                throw new Exception('Oops! Something went wrong while deleting the Account. Please try again.', 1);
            }

            $this->events($model);

            $return['success'] = true;
            $return['message'] = 'Successfully Deleted Account';
            $return['data'] = [];
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }

    private function subModelDelete($model)
    {
        foreach ($model->entries as $entry) {
            switch ($entry->model) {
                case 'PurchasePayment':
                    $subModel = PurchasePayment::find($entry->model_id);
                    if ($subModel && ! $subModel->delete()) {
                        throw new Exception('Oops! Something went wrong while deleting the PurchasePayment. Please try again.', 1);
                    }
                    break;
                case 'SalePayment':
                    $subModel = SalePayment::find($entry->model_id);
                    if ($subModel && ! $subModel->delete()) {
                        throw new Exception('Oops! Something went wrong while deleting the SalePayment. Please try again.', 1);
                    }
                    break;
                case 'SaleReturnPayment':
                    $subModel = SaleReturnPayment::find($entry->model_id);
                    if ($subModel && ! $subModel->delete()) {
                        throw new Exception('Oops! Something went wrong while deleting the SaleReturnPayment. Please try again.', 1);
                    }
                    break;
            }
        }
    }

    private function events($model)
    {
        switch ($model->model) {
            case 'Sale':
                event(new SaleUpdatedEvent('payment', $model->sale));
                event(new SaleUpdatedEvent('discount', $model->sale));
                break;
            case 'Purchase':
                event(new PurchaseUpdatedEvent('payment', $model->purchase));
                event(new PurchaseUpdatedEvent('discount', $model->purchase));
                break;
            case 'SaleReturn':
                event(new SaleReturnUpdatedEvent('payment', $model->saleReturn));
                event(new SaleReturnUpdatedEvent('discount', $model->saleReturn));
                break;
        }
    }
}

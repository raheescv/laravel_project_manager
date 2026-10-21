<?php

namespace App\Actions\Purchase;

use App\Models\Purchase;
use App\Models\PurchaseReturnItem;
use App\Models\User;

class DeleteAction
{
    public function execute($id, $user_id)
    {
        try {
            $model = Purchase::find($id);
            if (! $model) {
                throw new \Exception("Purchase not found with the specified ID: $id.", 1);
            }
            if ($model->status == 'completed') {
                if (! User::find($user_id)?->is_super_admin) {
                    throw new \Exception('Completed purchase cant be deleted', 1);
                }
                $this->reverseCompleted($model, $user_id);
            }
            $model->items()->update(['deleted_by' => $user_id]);
            $model->items()->delete();

            $model->payments()->update(['deleted_by' => $user_id]);
            $model->payments()->delete();

            $model->update(['deleted_by' => $user_id]);

            if (! $model->delete()) {
                throw new \Exception('Oops! Something went wrong while deleting the Purchase. Please try again.', 1);
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

    /**
     * Take the purchased stock back out of the branch and drop every journal posted for the purchase.
     */
    protected function reverseCompleted(Purchase $model, int $userId): void
    {
        $hasReturns = PurchaseReturnItem::query()
            ->whereIn('purchase_item_id', $model->items()->pluck('id'))
            ->exists();
        if ($hasReturns) {
            throw new \Exception('Purchase '.$model->invoice_no.' has purchase returns against it. Delete those returns first.', 1);
        }

        $response = (new StockUpdateAction())->execute($model, $userId, 'delete');
        if (! $response['success']) {
            throw new \Exception($response['message'], 1);
        }

        $response = (new JournalDeleteAction())->execute($model, $userId);
        if (! $response['success']) {
            throw new \Exception($response['message'], 1);
        }
    }
}

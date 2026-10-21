<?php

namespace App\Actions\InventoryTransfer;

use App\Models\InventoryTransfer;
use App\Models\Journal;
use App\Models\User;

class DeleteAction
{
    public function execute($id, $userId)
    {
        try {
            $model = InventoryTransfer::find($id);
            if (! $model) {
                throw new \Exception("InventoryTransfer not found with the specified ID: $id.", 1);
            }

            if ($model->status == 'completed') {
                if (! User::find($userId)?->is_super_admin) {
                    throw new \Exception('Completed inventory transfer cant be deleted', 1);
                }
                $this->reverseCompleted($model, $userId);
            }
            $model->items()->delete();

            if (! $model->delete()) {
                throw new \Exception('Oops! Something went wrong while deleting the InventoryTransfer. Please try again.', 1);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Update InventoryTransfer';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }

    /**
     * Move the stock back to the source branch and drop the transfer's journal and its entries.
     */
    protected function reverseCompleted(InventoryTransfer $model, int $userId): void
    {
        $response = (new StockUpdateAction())->execute($model, $userId, 'transfer_reversal');
        if (! $response['success']) {
            throw new \Exception($response['message'], 1);
        }

        $journals = Journal::withoutGlobalScopes()
            ->where('model', 'InventoryTransfer')
            ->where('model_id', $model->id)
            ->get();

        foreach ($journals as $journal) {
            $journal->entries()->update(['deleted_by' => $userId]);
            $journal->entries()->delete();
            $journal->update(['deleted_by' => $userId]);
            $journal->delete();
        }
    }
}

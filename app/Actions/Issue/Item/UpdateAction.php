<?php

namespace App\Actions\Issue\Item;

use App\Models\IssueItem;

class UpdateAction
{
    public function execute(array $data, int $id): array
    {
        try {
            $model = IssueItem::find($id);
            if (! $model) {
                throw new \Exception("Issue item not found with ID: {$id}.", 1);
            }
            validationHelper(IssueItem::rules(), $data);
            $model->update([
                'product_id' => $data['product_id'],
                'date' => $data['date'],
                'quantity_in' => $data['quantity_in'] ?? 0,
                'quantity_out' => $data['quantity_out'] ?? 0,
            ]);

            return [
                'success' => true,
                'message' => 'Successfully updated issue item',
                'data' => $model,
            ];
        } catch (\Throwable $th) {
            return [
                'success' => false,
                'message' => $th->getMessage(),
            ];
        }
    }
}

<?php

namespace App\Actions\Issue\Item;

use App\Models\IssueItem;

class CreateAction
{
    public function execute(array $data): array
    {
        try {
            validationHelper(IssueItem::rules(), $data);
            $model = IssueItem::create([
                'issue_id' => $data['issue_id'],
                'product_id' => $data['product_id'],
                'date' => $data['date'],
                'quantity_in' => $data['quantity_in'] ?? 0,
                'quantity_out' => $data['quantity_out'] ?? 0,
            ]);

            return [
                'success' => true,
                'message' => 'Successfully created issue item',
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

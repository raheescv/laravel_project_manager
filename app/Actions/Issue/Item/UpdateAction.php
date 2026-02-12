<?php

namespace App\Actions\Issue\Item;

use App\Models\Issue;
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
            $issue = Issue::find((int) $data['issue_id']);
            if (! $issue) {
                throw new \Exception("Issue not found with ID: {$data['issue_id']}.", 1);
            }
            $model->update([
                'tenant_id' => $issue->tenant_id,
                'product_id' => $data['product_id'],
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

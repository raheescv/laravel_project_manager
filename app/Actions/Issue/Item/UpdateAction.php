<?php

namespace App\Actions\Issue\Item;

use App\Models\Issue;
use App\Models\IssueItem;
use Exception;

class UpdateAction
{
    public function execute(array $data, int $id): array
    {
        try {
            $model = IssueItem::find($id);
            if (! $model) {
                throw new Exception("Issue item not found with ID: {$id}.", 1);
            }
            validationHelper(IssueItem::rules(), $data);
            $issue = Issue::find((int) $data['issue_id']);
            if (! $issue) {
                throw new Exception("Issue not found with ID: {$data['issue_id']}.", 1);
            }

            $data = [
                'tenant_id' => $issue->tenant_id,
                'product_id' => $data['product_id'],
                'quantity_in' => $data['quantity_in'] ?? 0,
                'quantity_out' => $data['quantity_out'] ?? 0,
            ];
            $model->update($data);

            $return['success'] = true;
            $return['message'] = 'Successfully updated issue item';
            $return['data'] = $model;
        } catch (Exception $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }
}

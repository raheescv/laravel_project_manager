<?php

namespace App\Actions\Issue\Item;

use App\Models\Issue;
use App\Models\IssueItem;
use Exception;

class CreateAction
{
    public function execute(array $data): array
    {
        try {
            validationHelper(IssueItem::rules(), $data);
            $issue = Issue::find((int) $data['issue_id']);
            if (! $issue) {
                throw new Exception("Issue not found with ID: {$data['issue_id']}.", 1);
            }
            $data = [
                'tenant_id' => $issue->tenant_id,
                'issue_id' => $data['issue_id'],
                'product_id' => $data['product_id'],
                'quantity_in' => $data['quantity_in'] ?? 0,
                'quantity_out' => $data['quantity_out'] ?? 0,
            ];
            $model = IssueItem::create($data);

            $return['success'] = true;
            $return['message'] = 'Successfully created issue item';
            $return['data'] = $model;
        } catch (Exception $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }
}

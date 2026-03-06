<?php

namespace App\Actions\Issue\Item;

use App\Models\IssueItem;
use Exception;

class DeleteAction
{
    public function execute(int $id): array
    {
        try {
            $model = IssueItem::find($id);
            if (! $model) {
                throw new Exception("Issue item not found with ID: {$id}.", 1);
            }
            $issueId = $model->issue_id;
            $model->delete();

            $data = [
                'issue_id' => $issueId,
            ];

            $return['success'] = true;
            $return['message'] = 'Successfully deleted issue item';
            $return['data'] = $data;
        } catch (Exception $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }
}

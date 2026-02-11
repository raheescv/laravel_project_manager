<?php

namespace App\Actions\Issue\Item;

use App\Models\IssueItem;

class DeleteAction
{
    public function execute(int $id): array
    {
        try {
            $model = IssueItem::find($id);
            if (! $model) {
                throw new \Exception("Issue item not found with ID: {$id}.", 1);
            }
            $issueId = $model->issue_id;
            $model->delete();

            return [
                'success' => true,
                'message' => 'Successfully deleted issue item',
                'data' => ['issue_id' => $issueId],
            ];
        } catch (\Throwable $th) {
            return [
                'success' => false,
                'message' => $th->getMessage(),
            ];
        }
    }
}

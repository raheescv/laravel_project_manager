<?php

namespace App\Actions\Issue;

use App\Models\Issue;

class DeleteAction
{
    public function execute(int $id): array
    {
        try {
            $model = Issue::find($id);
            if (! $model) {
                throw new \Exception("Issue not found with ID: {$id}.", 1);
            }
            $model->items()->delete();
            $model->delete();

            return [
                'success' => true,
                'message' => 'Successfully deleted issue',
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

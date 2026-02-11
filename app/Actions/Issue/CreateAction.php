<?php

namespace App\Actions\Issue;

use App\Models\Issue;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreateAction
{
    public function execute(array $data): array
    {
        try {
            validationHelper(Issue::rules(), $data);

            DB::beginTransaction();

            $issue = Issue::create([
                'account_id' => $data['account_id'],
                'remarks' => $data['remarks'] ?? null,
                'no_of_items_out' => 0,
                'no_of_items_in' => 0,
            ]);

            foreach ($data['items'] ?? [] as $item) {
                $item['issue_id'] = $issue->id;
                $response = (new Item\CreateAction())->execute($item);
                if (! $response['success']) {
                    throw new Exception($response['message'], 1);
                }
            }

            $issue->refresh();
            $updateData = [
                'no_of_items_out' => $issue->items()->sum('quantity_out'),
                'no_of_items_in' => $issue->items()->sum('quantity_in'),
            ];
            $issue->update($updateData);

            $stockResponse = (new StockUpdateAction())->execute($issue->fresh(), Auth::id());
            if (! $stockResponse['success']) {
                throw new Exception($stockResponse['message'], 1);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Successfully created issue',
                'data' => $issue->fresh(),
            ];
        } catch (Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}

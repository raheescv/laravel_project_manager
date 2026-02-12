<?php

namespace App\Actions\Issue;

use App\Models\Issue;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UpdateAction
{
    public function execute(array $data, int $issueId): array
    {
        try {
            $issue = Issue::find($issueId);
            if (! $issue) {
                throw new Exception("Issue not found with ID: {$issueId}.", 1);
            }

            validationHelper(Issue::rules($issueId), $data);

            DB::beginTransaction();

            $issue->load('items');
            [$itemsToReverse, $unchangedIds] = $this->itemsToReverseAndUnchangedIds($issue, $data);

            // Reverse only changed/removed rows
            if ($itemsToReverse->isNotEmpty()) {
                $reversalResponse = (new StockUpdateAction())->executeForItems($issue, $itemsToReverse, Auth::id(), 'reversal');
                if (! $reversalResponse['success']) {
                    throw new Exception($reversalResponse['message'], 1);
                }
            }

            $issue->update([
                'type' => $data['type'],
                'account_id' => $data['account_id'],
                'date' => $data['date'],
                'remarks' => $data['remarks'] ?? null,
                'updated_by' => Auth::id(),
            ]);

            $submittedItemIds = [];
            foreach ($data['items'] ?? [] as $item) {
                $item['issue_id'] = $issueId;
                if (! empty($item['id'])) {
                    $response = (new Item\UpdateAction())->execute($item, (int) $item['id']);
                    $submittedItemIds[] = (int) $item['id'];
                } else {
                    $response = (new Item\CreateAction())->execute($item);
                    $submittedItemIds[] = (int) $response['data']->id;
                }
                if (! $response['success']) {
                    throw new Exception($response['message'], 1);
                }
            }

            // Delete items that were removed from the payload (stock already reversed above)
            $issue->items()->whereNotIn('id', $submittedItemIds)->delete();

            $issue->refresh();

            $updateData = [
                'no_of_items_out' => $issue->items()->sum('quantity_out'),
                'no_of_items_in' => $issue->items()->sum('quantity_in'),
                'updated_by' => Auth::id(),
            ];
            $issue->update($updateData);

            // Apply only new/changed rows (skip unchanged)
            $issue->refresh();
            $itemsToApply = $issue->items->whereNotIn('id', $unchangedIds);
            if ($itemsToApply->isNotEmpty()) {
                $stockResponse = (new StockUpdateAction())->executeForItems($issue, $itemsToApply, Auth::id());
                if (! $stockResponse['success']) {
                    throw new Exception($stockResponse['message'], 1);
                }
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Successfully updated '.$data['type'],
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

    /**
     * Rows to reverse = removed or changed (by id). UnchangedIds = current ids that match incoming.
     *
     * @return array{0: Collection, 1: array<int>}
     */
    protected function itemsToReverseAndUnchangedIds(Issue $issue, array $data): array
    {
        $incomingById = collect($data['items'] ?? [])->mapWithKeys(function ($i, $index) {
            $key = isset($i['id']) && $i['id'] !== '' ? (int) $i['id'] : 'new_'.$index;

            return [$key => [
                'product_id' => $i['product_id'] ?? null,
                'quantity_in' => (float) ($i['quantity_in'] ?? 0),
                'quantity_out' => (float) ($i['quantity_out'] ?? 0),
            ]];
        });

        $itemsToReverse = $issue->items->filter(function ($item) use ($incomingById) {
            $in = $incomingById->get($item->id);
            if ($in === null) {
                return true; // removed from payload
            }

            return $item->product_id != $in['product_id']
                || (float) $item->quantity_in !== $in['quantity_in']
                || (float) $item->quantity_out !== $in['quantity_out'];
        });

        $unchangedIds = $issue->items->pluck('id')->diff($itemsToReverse->pluck('id'))->values()->all();

        return [$itemsToReverse, $unchangedIds];
    }
}

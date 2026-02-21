<?php

namespace App\Actions\Tailoring\Order;

use App\Actions\Tailoring\JournalEntryAction;
use App\Actions\Tailoring\Payment\CreateAction;
use App\Actions\Tailoring\Payment\UpdateAction;
use App\Models\TailoringOrder;
use Exception;

class UpdateTailoringOrderAction
{
    public function execute($id, $data, $userId)
    {
        try {
            $model = TailoringOrder::findOrFail($id);
            $data['updated_by'] = $userId;
            validationHelper(TailoringOrder::rules($id), $data);
            $model->update($data);
            // Update items if provided
            if (isset($data['items'])) {
                $this->updateItems($model, $data['items'], $userId);
            }

            if ($data['payment_method'] == 'credit') {
                // delete all the payment if exists
                $model->payments()->delete();
            } else {
                // Update payments if provided
                if (isset($data['payments'])) {
                    $this->updatePayments($model, $data['payments'], $userId);
                }
            }

            $model->refresh();
            $model->calculateTotals();
            $model->save();

            $journalAction = new JournalEntryAction();
            $journalAction->deleteJournalsForOrder($model, $userId);
            $response = $journalAction->executeForOrder($model, $userId);
            if (! ($response['success'] ?? false)) {
                throw new Exception($response['message'] ?? 'Failed to create journal entry', 1);
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Updated Tailoring Order';
            $return['data'] = $model;
        } catch (Exception $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }

    private function updateItems($order, $items, $userId)
    {
        // Keep items not present in payload until completion processing reverses their stock.
        $incomingItemIds = collect($items)->pluck('id')->filter()->map(fn ($id) => (int) $id)->values()->all();
        $removedItemIds = $order->items()->whereNotIn('id', $incomingItemIds)->pluck('id')->map(fn ($id) => (int) $id)->values()->all();

        // Update or create items
        $itemNo = 1;
        $completionItemsData = [];
        foreach ($items as $itemData) {
            $baseItemData = $this->baseItemData($itemData);

            if (isset($itemData['id']) && $itemData['id']) {
                // Update existing item
                $baseItemData['item_no'] = $itemNo++;
                $response = (new Item\UpdateTailoringItemAction())->execute($itemData['id'], $baseItemData, $userId);
                $itemId = (int) $itemData['id'];
            } else {
                // Create new item
                $baseItemData['tailoring_order_id'] = $order->id;
                $baseItemData['item_no'] = $itemNo++;
                $response = (new Item\AddTailoringItemAction())->execute($baseItemData, $userId);
                $itemId = (int) $response['data']->id;
            }

            if (! $response['success']) {
                throw new Exception($response['message'], 1);
            }

            $completionItemsData[] = array_merge( ['id' => $itemId], $this->completionData($itemData) );
        }

        foreach ($removedItemIds as $removedItemId) {
            $completionItemsData[] = [
                'id' => $removedItemId,
                'used_quantity' => 0,
                'wastage' => 0,
                'completed_quantity' => 0,
                'delivered_quantity' => 0,
                'is_selected_for_completion' => false,
                'tailor_assignments' => [],
                'status' => 'pending',
            ];
        }
        if (! empty($completionItemsData)) {
            (new ProcessOrderCompletionItemsAction())->execute($order, $completionItemsData, (int) $userId);
        }

        if (! empty($removedItemIds)) {
            $order->items()->whereIn('id', $removedItemIds)->delete();
        }
    }

    private function baseItemData(array $itemData): array
    {
        unset(
            $itemData['used_quantity'],
            $itemData['wastage'],
            $itemData['item_completion_date'],
            $itemData['completed_quantity'],
            $itemData['delivered_quantity'],
            $itemData['is_selected_for_completion'],
            $itemData['tailor_assignment'],
            $itemData['tailor_assignments'],
            $itemData['status']
        );

        return $itemData;
    }

    private function completionData(array $itemData): array
    {
        $data['used_quantity'] = $itemData['quantity'] * $itemData['quantity_per_item'];
        return $data;
    }

    private function updatePayments($order, $payments, $userId)
    {
        // Delete payments not in the new list
        $existingPaymentIds = collect($payments)->pluck('id')->filter()->toArray();
        $order->payments()->whereNotIn('id', $existingPaymentIds)->delete();

        // Update or create payments
        foreach ($payments as $paymentData) {
            if (isset($paymentData['id']) && $paymentData['id']) {
                $response = (new UpdateAction())->execute($paymentData['id'], $paymentData, $userId);
            } else {
                $paymentData['tailoring_order_id'] = $order->id;
                $response = (new CreateAction())->execute($paymentData, $userId);
            }

            if (! $response['success']) {
                throw new Exception($response['message'], 1);
            }
        }
    }
}

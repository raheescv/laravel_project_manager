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
        // Delete items not in the new list
        $existingItemIds = collect($items)->pluck('id')->filter()->toArray();
        $order->items()->whereNotIn('id', $existingItemIds)->delete();

        // Update or create items
        $itemNo = 1;
        foreach ($items as $itemData) {
            if (isset($itemData['id']) && $itemData['id']) {
                // Update existing item
                $itemData['item_no'] = $itemNo++;
                $response = (new Item\UpdateTailoringItemAction())->execute($itemData['id'], $itemData, $userId);
            } else {
                // Create new item
                $itemData['tailoring_order_id'] = $order->id;
                $itemData['item_no'] = $itemNo++;
                $response = (new Item\AddTailoringItemAction())->execute($itemData, $userId);
            }

            if (! $response['success']) {
                throw new Exception($response['message'], 1);
            }
        }
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

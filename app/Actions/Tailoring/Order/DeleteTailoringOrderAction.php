<?php

namespace App\Actions\Tailoring\Order;

use App\Actions\Tailoring\JournalEntryAction;
use App\Models\TailoringOrder;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DeleteTailoringOrderAction
{
    /**
     * Delete a tailoring order and related data: reverse stock, remove journals,
     * then soft-delete payments, measurements, items, and the order.
     */
    public function execute(int $orderId, int $userId): array
    {
        try {
            $order = TailoringOrder::with(['items', 'payments', 'measurements'])->findOrFail($orderId);

            DB::beginTransaction();

            $stockResult = (new StockUpdateAction())->reverseStockForOrder($order, $userId);
            if (! $stockResult['success']) {
                throw new Exception($stockResult['message'], 1);
            }

            (new JournalEntryAction())->deleteJournalsForOrder($order, $userId);

            foreach ($order->payments as $payment) {
                $this->softDelete($payment, $userId);
            }
            foreach ($order->measurements as $measurement) {
                $this->softDelete($measurement, $userId);
            }
            foreach ($order->items as $item) {
                $this->softDelete($item, $userId);
            }
            $this->softDelete($order, $userId);

            DB::commit();

            return $this->result(true, 'Order and all related data removed successfully');
        } catch (Exception $e) {
            DB::rollBack();

            return $this->result(false, $e->getMessage());
        }
    }

    private function result(bool $success, string $message, mixed $data = null): array
    {
        return ['success' => $success, 'message' => $message, 'data' => $data];
    }

    private function softDelete(Model $model, int $userId): void
    {
        $model->deleted_by = $userId;
        $model->save();
        $model->delete();
    }
}

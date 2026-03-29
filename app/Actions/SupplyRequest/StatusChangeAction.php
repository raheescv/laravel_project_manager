<?php

namespace App\Actions\SupplyRequest;

use App\Enums\SupplyRequest\SupplyRequestStatus;
use App\Models\SupplyRequest;
use Exception;
use Illuminate\Support\Facades\DB;
use Throwable;

class StatusChangeAction
{
    public function execute(int $id, string $status, int $userId, ?int $paymentModeId = null): array
    {
        try {
            return DB::transaction(function () use ($id, $status, $userId, $paymentModeId) {
                $model = SupplyRequest::findOrFail($id);
                $oldStatus = $model->status;
                $data = ['status' => $status];

                $statusEnum = SupplyRequestStatus::from($status);
                $timestamp = ['by' => $userId, 'at' => now()];

                switch ($statusEnum) {
                    case SupplyRequestStatus::APPROVED:
                    case SupplyRequestStatus::REJECTED:
                        $data['approved_by'] = $timestamp['by'];
                        $data['approved_at'] = $timestamp['at'];
                        break;
                    case SupplyRequestStatus::FINAL_APPROVED:
                        $data['final_approved_by'] = $timestamp['by'];
                        $data['final_approved_at'] = $timestamp['at'];
                        break;
                    case SupplyRequestStatus::COLLECTED:
                        $data['payment_mode_id'] = $paymentModeId;
                        $data['accounted_by'] = $timestamp['by'];
                        $data['accounted_at'] = $timestamp['at'];
                        break;
                    case SupplyRequestStatus::COMPLETED:
                        $data['completed_by'] = $timestamp['by'];
                        $data['completed_at'] = $timestamp['at'];
                        break;
                }
                $model->update($data);

                // On completion: reduce inventory and create journal entries
                if ($statusEnum === SupplyRequestStatus::COMPLETED && $oldStatus !== SupplyRequestStatus::COMPLETED) {
                    $response = (new StockUpdateAction())->execute($model, $userId, 'complete');
                    if (! $response['success']) {
                        throw new Exception($response['message']);
                    }

                    $response = (new JournalEntryAction())->execute($model, $userId);
                    if (! $response['success']) {
                        throw new Exception($response['message']);
                    }
                }

                return [
                    'success' => true,
                    'data' => $model,
                    'message' => 'Status updated successfully',
                ];
            });
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}

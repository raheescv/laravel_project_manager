<?php

namespace App\Actions\Tailoring\Order;

use App\Actions\Tailoring\JournalEntryAction;
use App\Actions\Tailoring\Payment\CreateAction;
use App\Models\TailoringOrder;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class CreateTailoringOrderAction
{
    public $model;

    public $userId;

    public function execute($data, $userId)
    {
        $this->userId = $userId;
        try {
            DB::transaction(function () use ($data) {
                $data['branch_id'] = $data['branch_id'] ?? session('branch_id');
                $data['created_by'] = $this->userId;
                $data['order_no'] = getNextTailorOrderNo();
                $data['order_date'] = $data['order_date'] ?? date('Y-m-d');

                validationHelper(TailoringOrder::rules(), $data);
                $this->model = TailoringOrder::create($data);

                $completionItemsData = $this->items($data['items'] ?? []);
                if (! empty($completionItemsData)) {
                    (new ProcessOrderCompletionItemsAction())->execute($this->model, $completionItemsData, (int) $this->userId);
                }

                if ($data['payment_method'] != 'credit') {
                    $this->payments($data['payments'] ?? []);
                }

                $this->model->refresh();
                $this->model->calculateTotals();
                $this->model->save();

                $this->model->refresh();

                // Validate max_discount_per_sale if needed
                $totalDiscount = ($this->model->item_discount ?? 0) + ($this->model->other_discount ?? 0);
                if ($totalDiscount) {
                    $user = User::find($this->userId);
                    User::validateMaxDiscount($user->max_discount_per_sale, $this->model->gross_amount, $totalDiscount);
                }
            });

            $response = (new JournalEntryAction())->executeForOrder($this->model, $this->userId);
            if (! ($response['success'] ?? false)) {
                throw new Exception($response['message'] ?? 'Failed to create journal entry', 1);
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Created Tailoring Order';
            $return['data'] = $this->model;
        } catch (Exception $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }

    private function items($data): array
    {
        $itemNo = 1;
        $completionItemsData = [];
        foreach ($data as $value) {
            $baseItemData = $this->baseItemData($value);
            $baseItemData['tailoring_order_id'] = $this->model->id;
            $baseItemData['item_no'] = $itemNo++;

            $response = (new Item\AddTailoringItemAction())->execute($baseItemData, $this->userId);
            if (! $response['success']) {
                throw new Exception($response['message'], 1);
            }

            $completionItemsData[] = array_merge(['id' => (int) $response['data']->id], $this->completionData($baseItemData));
        }

        return $completionItemsData;
    }

    private function baseItemData(array $itemData): array
    {
        unset(
            $itemData['category'],
        );

        return $itemData;
    }

    private function completionData(array $itemData): array
    {
        $data['used_quantity'] = $itemData['quantity'] * $itemData['quantity_per_item'];

        return $data;
    }

    private function payments($data)
    {
        foreach ($data as $value) {
            $value['tailoring_order_id'] = $this->model->id;
            $value['date'] = $value['date'] ?? $this->model->order_date;
            $response = (new CreateAction())->execute($value, $this->userId);
            if (! $response['success']) {
                throw new Exception($response['message'], 1);
            }
        }
    }
}

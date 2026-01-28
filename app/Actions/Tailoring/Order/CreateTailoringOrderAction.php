<?php

namespace App\Actions\Tailoring\Order;

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
                $data['order_no'] = $data['order_no'] ?? TailoringOrder::generateOrderNo();
                $data['order_date'] = $data['order_date'] ?? date('Y-m-d');

                validationHelper(TailoringOrder::rules(), $data);
                $this->model = TailoringOrder::create($data);

                $this->items($data['items'] ?? []);
                $this->payments($data['payments'] ?? []);

                $this->model->refresh();
                $this->model->calculateTotals();
                $this->model->save();

                $this->model->refresh();

                // Validate max_discount_per_sale if needed
                $totalDiscount = ($this->model->item_discount ?? 0) + ($this->model->other_discount ?? 0);
                if ($totalDiscount) {
                    $user = User::find($this->userId);
                    if (method_exists($user, 'validateMaxDiscount')) {
                        $user->validateMaxDiscount($this->model->gross_amount, $totalDiscount);
                    }
                }
            });

            $return['success'] = true;
            $return['message'] = 'Successfully Created Tailoring Order';
            $return['data'] = $this->model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }

    private function items($data)
    {
        $itemNo = 1;
        foreach ($data as $value) {
            $value['tailoring_order_id'] = $this->model->id;
            $value['item_no'] = $itemNo++;
            $response = (new Item\AddTailoringItemAction())->execute($value, $this->userId);
            if (! $response['success']) {
                throw new Exception($response['message'], 1);
            }
        }
    }

    private function payments($data)
    {
        foreach ($data as $value) {
            $value['tailoring_order_id'] = $this->model->id;
            $value['date'] = $value['date'] ?? $this->model->order_date;
            $response = (new \App\Actions\Tailoring\Payment\CreateAction())->execute($value, $this->userId);
            if (! $response['success']) {
                throw new Exception($response['message'], 1);
            }
        }
    }
}

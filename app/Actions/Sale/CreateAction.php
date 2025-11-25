<?php

namespace App\Actions\Sale;

use App\Models\Sale;
use App\Models\User;
use Exception;

class CreateAction
{
    public $model;

    public $userId;

    public function execute($data, $userId)
    {
        $this->userId = $userId;
        try {
            $data['branch_id'] = $data['branch_id'] ?? session('branch_id');
            $data['created_by'] = $this->userId;
            $data['invoice_no'] = $data['invoice_no'] ?? getNextSaleInvoiceNo();

            validationHelper(Sale::rules(), $data);
            $this->model = Sale::create($data);

            $this->items($data['items']);
            $this->payments($data['payments']);
            $this->comboOffers($data['comboOffers'] ?? []);

            $this->model->refresh();

            $updateData = [
                'gross_amount' => $this->model->items->sum('gross_amount'),
                'item_discount' => $this->model->items->sum('discount'),
                'tax_amount' => $this->model->items->sum('tax_amount'),
                'paid' => $this->model->payments->sum('amount'),
            ];
            if ($updateData['gross_amount'] == $this->model->gross_amount) {
                unset($updateData['gross_amount']);
            }
            if ($updateData['item_discount'] == $this->model->item_discount) {
                unset($updateData['item_discount']);
            }
            if ($updateData['tax_amount'] == $this->model->tax_amount) {
                unset($updateData['tax_amount']);
            }
            if ($updateData['paid'] == $this->model->paid) {
                unset($updateData['paid']);
            }
            if ($updateData) {
                $this->model->update($updateData);
            }

            $this->model->refresh();
            $totalDiscount = ($this->model->item_discount ?? 0) + ($this->model->other_discount ?? 0);
            // Validate max_discount_per_sale
            $user = User::find($this->userId);
            if ($totalDiscount) {
                $user->validateMaxDiscount($this->model->gross_amount, $totalDiscount);
            }

            if ($this->model['status'] == 'completed') {
                $response = (new StockUpdateAction())->execute($this->model, $this->userId);
                if (! $response['success']) {
                    throw new Exception($response['message'], 1);
                }
                $this->model->refresh();
                $response = (new JournalEntryAction())->execute($this->model, $this->userId);
                if (! $response['success']) {
                    throw new Exception($response['message'], 1);
                }
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Created Sale';
            $return['data'] = $this->model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }

    private function items($data)
    {
        foreach ($data as $value) {
            $value['sale_id'] = $this->model->id;
            $response = (new Item\CreateAction())->execute($value, $this->userId);
            if (! $response['success']) {
                throw new Exception($response['message'], 1);
            }
        }
    }

    private function payments($data)
    {
        foreach ($data as $value) {
            $value['sale_id'] = $this->model->id;
            $value['date'] = $this->model->date;
            $response = (new Payment\CreateAction())->execute($value, $this->userId);
            if (! $response['success']) {
                throw new Exception($response['message'], 1);
            }
        }
    }

    private function comboOffers($data)
    {
        foreach ($data as $value) {
            $value['sale_id'] = $this->model->id;
            $response = (new ComboOffer\CreateAction())->execute($value);
            if (! $response['success']) {
                throw new Exception($response['message'], 1);
            }
        }
    }
}

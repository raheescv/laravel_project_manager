<?php

namespace App\Actions\Sale;

use App\Models\Sale;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;

class UpdateAction
{
    public $userId;

    public $saleId;

    public $model;

    public function execute($data, $saleId, $userId)
    {
        try {
            $this->userId = $userId;
            $this->saleId = $saleId;

            $this->model = $model = Sale::find($saleId);
            if (! $model) {
                throw new Exception("Sale not found with the specified ID: $saleId.", 1);
            }
            if ($data['status'] != 'cancelled') {
                $data['invoice_no'] = $model->invoice_no;
                $data['branch_id'] = $model->branch_id;
                $data['updated_by'] = $this->userId;

                // if it is edit after complete
                $this->rollbackIfCompleted();

                validationHelper(Sale::rules($saleId), $data);
                // to avoid storing the audit log
                if (true) {
                    if ($model->gross_amount == $data['gross_amount']) {
                        $data['gross_amount'] = $model->gross_amount;
                    }
                    if ($model->item_discount == $data['item_discount']) {
                        $data['item_discount'] = $model->item_discount;
                    }
                    if ($model->tax_amount == $data['tax_amount']) {
                        $data['tax_amount'] = $model->tax_amount;
                    }
                    if ($model->total == $data['total']) {
                        $data['total'] = $model->total;
                    }
                    if ($model->paid == ($data['paid'] ?? 0)) {
                        $data['paid'] = $model->paid;
                    }
                }
                if ($data['paid'] == $model->paid) {
                    unset($model['paid']);
                }
                $model->update($data);

                $this->items($data['items']);

                $this->payments($data['payments']);

                $this->comboOffers($data['comboOffers']);

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
                $user->validateMaxDiscount($this->model->gross_amount, $totalDiscount);

                if ($model['status'] == 'completed') {
                    $this->completed();
                }
            } else {
                $model->update([
                    'status' => 'cancelled',
                    'cancelled_by' => $this->userId,
                ]);
                $this->model->refresh();
                $this->cancel();
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Update Sale';
            $return['data'] = $this->model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }

    private function completed()
    {
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

    private function cancel()
    {
        $response = (new StockUpdateAction())->execute($this->model, $this->userId, 'cancel');
        if (! $response['success']) {
            throw new Exception($response['message'], 1);
        }
        $response = (new JournalDeleteAction())->execute($this->model, $this->userId);
        if (! $response['success']) {
            throw new Exception($response['message'], 1);
        }
    }

    private function items($data)
    {
        foreach ($data as $value) {
            $value['sale_id'] = $this->saleId;

            if (isset($value['id'])) {
                $response = (new Item\UpdateAction())->execute($value, $value['id'], $this->userId);
            } else {
                $response = (new Item\CreateAction())->execute($value, $this->userId);
            }
            if (! $response['success']) {
                throw new Exception($response['message'], 1);
            }
        }
    }

    private function payments($data)
    {
        foreach ($data as $value) {
            $value['sale_id'] = $this->saleId;

            if (isset($value['id'])) {
                $response = (new Payment\UpdateAction())->execute($value, $value['id'], $this->userId);
            } else {
                $value['date'] = $this->model->date;
                $response = (new Payment\CreateAction())->execute($value, $this->userId);
            }

            if (! $response['success']) {
                throw new Exception($response['message'], 1);
            }
        }
    }

    private function comboOffers($data)
    {
        foreach ($data as $value) {
            $value['sale_id'] = $this->saleId;
            if (isset($value['id'])) {
                $response = (new ComboOffer\UpdateAction())->execute($value, $value['id']);
            } else {
                $response = (new ComboOffer\CreateAction())->execute($value);
            }
            if (! $response['success']) {
                throw new Exception($response['message'], 1);
            }
        }
    }

    private function rollbackIfCompleted()
    {
        $oldStatus = $this->model->status;
        if ($oldStatus == 'completed') {
            if (! Auth::user()->can('sale.edit completed')) {
                throw new Exception("You don't have permission to edit it.", 1);
            }
            $response = (new JournalDeleteAction())->execute($this->model, $this->userId);
            if (! $response['success']) {
                throw new Exception($response['message'], 1);
            }
            $response = (new StockUpdateAction())->execute($this->model, $this->userId, 'sale_reversal');
            if (! $response['success']) {
                throw new Exception($response['message'], 1);
            }
        }
    }
}

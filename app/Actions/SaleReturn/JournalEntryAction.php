<?php

namespace App\Actions\SaleReturn;

use App\Actions\Journal\CreateAction;
use App\Models\SaleReturn;
use Illuminate\Support\Facades\DB;

class JournalEntryAction
{
    public function execute(SaleReturn $model, $user_id)
    {
        try {
            $data['date'] = $model->date;
            $data['description'] = 'SaleReturn:'.$model->id;
            $data['reference_no'] = $model->reference_no;
            $data['model'] = 'SaleReturn';
            $data['model_id'] = $model->id;
            $data['created_by'] = $user_id;

            $items = $model->items()->with('inventory')->get();
            $totalCost = $items->map(function ($item) {
                $item->total_cost = $item->inventory->cost * $item->quantity;

                return $item;
            })->sum('total_cost');

            $items = $model->payments;

            $entries = [];
            if ($model->gross_amount > 0) {
                $remarks = 'SaleReturn to '.$model->account->name;
                $entries[] = [
                    'account_id' => DB::table('accounts')->where('name', 'Sales Returns')->value('id'),
                    'debit' => $model->gross_amount,
                    'credit' => 0,
                    'created_by' => $user_id,
                    'remarks' => $remarks,
                ];
                $entries[] = [
                    'account_id' => $model->account_id,
                    'debit' => 0,
                    'credit' => $model->gross_amount,
                    'created_by' => $user_id,
                    'remarks' => $remarks,
                ];
            }

            if ($totalCost > 0) {
                $remarks = 'Cost of goods return (Inventory transfer)';
                $entries[] = [
                    'account_id' => DB::table('accounts')->where('name', 'Cost of Goods Sold')->value('id'),
                    'debit' => 0,
                    'credit' => $totalCost,
                    'created_by' => $user_id,
                    'remarks' => $remarks,
                ];
                $entries[] = [
                    'account_id' => DB::table('accounts')->where('name', 'Inventory')->value('id'),
                    'debit' => $totalCost,
                    'credit' => 0,
                    'created_by' => $user_id,
                    'remarks' => $remarks,
                ];
            }

            if ($model->tax_amount > 0) {
                $remarks = 'Sale Returns tax collected on sale';
                $entries[] = [
                    'account_id' => DB::table('accounts')->where('name', 'Tax Amount')->value('id'),
                    'debit' => $model->tax_amount,
                    'credit' => 0,
                    'created_by' => $user_id,
                    'remarks' => $remarks,
                ];
                $entries[] = [
                    'account_id' => $model->account_id,
                    'debit' => 0,
                    'credit' => $model->tax_amount,
                    'created_by' => $user_id,
                    'remarks' => $remarks,
                ];
            }

            if ($model->item_discount > 0) {
                $remarks = 'Discount provided on individual product on sale return';
                $entries[] = [
                    'account_id' => DB::table('accounts')->where('name', 'Discount')->value('id'),
                    'debit' => 0,
                    'credit' => $model->item_discount,
                    'created_by' => $user_id,
                    'remarks' => $remarks,
                ];
                $entries[] = [
                    'account_id' => $model->account_id,
                    'debit' => $model->item_discount,
                    'credit' => 0,
                    'created_by' => $user_id,
                    'remarks' => $remarks,
                ];
            }
            if ($model->other_discount > 0) {
                $remarks = SaleReturn::ADDITIONAL_DISCOUNT_DESCRIPTION;
                $entries[] = [
                    'account_id' => DB::table('accounts')->where('name', 'Discount')->value('id'),
                    'debit' => 0,
                    'credit' => $model->other_discount,
                    'created_by' => $user_id,
                    'remarks' => $remarks,
                ];
                $entries[] = [
                    'account_id' => $model->account_id,
                    'debit' => $model->other_discount,
                    'credit' => 0,
                    'created_by' => $user_id,
                    'remarks' => $remarks,
                ];
            }

            foreach ($items as $value) {
                $remarks = $value->paymentMethod->name.' payment made by '.$model->account->name;
                $entries[] = [
                    'account_id' => $value->payment_method_id,
                    'debit' => 0,
                    'credit' => $value->amount,
                    'created_by' => $user_id,
                    'remarks' => $remarks,
                    'model' => 'SaleReturnPayment',
                    'model_id' => $value->id,
                ];
                $entries[] = [
                    'account_id' => $model->account_id,
                    'debit' => $value->amount,
                    'credit' => 0,
                    'created_by' => $user_id,
                    'remarks' => $remarks,
                    'model' => 'SaleReturnPayment',
                    'model_id' => $value->id,
                ];
            }

            $data['entries'] = $entries;

            $response = (new CreateAction())->execute($data);
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Updated Journal';
            $return['data'] = [];
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}

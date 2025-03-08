<?php

namespace App\Actions\Purchase;

use App\Actions\Journal\CreateAction;
use Illuminate\Support\Facades\DB;

class JournalEntryAction
{
    public function execute($purchase, $user_id)
    {
        try {

            $data['date'] = $purchase->date;
            $data['description'] = 'Purchase:'.$purchase->invoice_no;
            $data['reference_no'] = $purchase->reference_no;
            $data['model'] = 'Purchase';
            $data['model_id'] = $purchase->id;
            $data['created_by'] = $user_id;

            $items = $purchase->items()->with('product')->get();
            $totalCost = $items->map(function ($item) {
                $item->total_cost = $item->product->cost * $item->quantity;

                return $item;
            })->sum('total_cost');

            $items = $purchase->payments;

            $entries = [];
            if ($purchase->gross_amount > 0) {
                $remarks = 'Purchase to '.$purchase->account->name;
                $entries[] = [
                    'account_id' => DB::table('accounts')->where('name', 'Purchase')->value('id'),
                    'debit' => $purchase->gross_amount,
                    'credit' => 0,
                    'created_by' => $user_id,
                    'remarks' => $remarks,
                ];
                $entries[] = [
                    'account_id' => $purchase->account_id,
                    'debit' => 0,
                    'credit' => $purchase->gross_amount,
                    'created_by' => $user_id,
                    'remarks' => $remarks,
                ];
            }

            if ($totalCost > 0) {
                $remarks = 'Cost of goods sold (Inventory transfer)';
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

            if ($purchase->tax_amount > 0) {
                $remarks = 'purchases tax collected on purchase';
                $entries[] = [
                    'account_id' => DB::table('accounts')->where('name', 'Tax Amount')->value('id'),
                    'debit' => $purchase->tax_amount,
                    'credit' => 0,
                    'created_by' => $user_id,
                    'remarks' => $remarks,
                ];
                $entries[] = [
                    'account_id' => $purchase->account_id,
                    'debit' => 0,
                    'credit' => $purchase->tax_amount,
                    'created_by' => $user_id,
                    'remarks' => $remarks,
                ];
            }

            if ($purchase->item_discount > 0) {
                $remarks = 'Discount provided on individual product on  purchase';
                $entries[] = [
                    'account_id' => DB::table('accounts')->where('name', 'Discount')->value('id'),
                    'debit' => 0,
                    'credit' => $purchase->item_discount,
                    'created_by' => $user_id,
                    'remarks' => $remarks,
                ];
                $entries[] = [
                    'account_id' => $purchase->account_id,
                    'debit' => $purchase->item_discount,
                    'credit' => 0,
                    'created_by' => $user_id,
                    'remarks' => $remarks,
                ];
            }
            if ($purchase->other_discount > 0) {
                $remarks = 'Additional Discount provided on purchase';
                $entries[] = [
                    'account_id' => DB::table('accounts')->where('name', 'Discount')->value('id'),
                    'debit' => 0,
                    'credit' => $purchase->other_discount,
                    'created_by' => $user_id,
                    'remarks' => $remarks,
                ];
                $entries[] = [
                    'account_id' => $purchase->account_id,
                    'debit' => $purchase->other_discount,
                    'credit' => 0,
                    'created_by' => $user_id,
                    'remarks' => $remarks,
                ];
            }

            if ($purchase->freight > 0) {
                $remarks = 'Freight Charge provided on purchase';
                $entries[] = [
                    'account_id' => DB::table('accounts')->where('name', 'Freight')->value('id'),
                    'debit' => $purchase->freight,
                    'credit' => 0,
                    'created_by' => $user_id,
                    'remarks' => $remarks,
                ];
                $entries[] = [
                    'account_id' => $purchase->account_id,
                    'debit' => 0,
                    'credit' => $purchase->freight,
                    'created_by' => $user_id,
                    'remarks' => $remarks,
                ];
            }

            foreach ($items as $value) {
                $remarks = $value->paymentMethod->name.' payment made by '.$purchase->account->name;
                $entries[] = [
                    'account_id' => $value->payment_method_id,
                    'debit' => 0,
                    'credit' => $value->amount,
                    'created_by' => $user_id,
                    'remarks' => $remarks,
                ];
                $entries[] = [
                    'account_id' => $purchase->account_id,
                    'debit' => $value->amount,
                    'credit' => 0,
                    'created_by' => $user_id,
                    'remarks' => $remarks,
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

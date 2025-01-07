<?php

namespace App\Actions\Sale;

use App\Actions\Journal\CreateAction;
use Illuminate\Support\Facades\DB;

class JournalEntryAction
{
    public function execute($sale, $user_id)
    {
        try {

            $data['date'] = $sale->date;
            $data['description'] = 'Sale:'.$sale->invoice_no;
            $data['reference_no'] = $sale->reference_no;
            $data['model'] = 'Sale';
            $data['model_id'] = $sale->id;
            $data['created_by'] = $user_id;

            $items = $sale->items()->with('inventory')->get();
            $totalCost = $items->map(function ($item) {
                $item->total_cost = $item->inventory->cost * $item->quantity;

                return $item;
            })->sum('total_cost');

            $items = $sale->payments;

            $entries = [];
            if ($sale->gross_amount > 0) {
                $remarks = 'Sale to '.$sale->account->name;
                $entries[] = [
                    'account_id' => DB::table('accounts')->where('name', 'Sale')->value('id'),
                    'debit' => 0,
                    'credit' => $sale->gross_amount,
                    'created_by' => $user_id,
                    'remarks' => $remarks,
                ];
                $entries[] = [
                    'account_id' => $sale->account_id,
                    'debit' => $sale->gross_amount,
                    'credit' => 0,
                    'created_by' => $user_id,
                    'remarks' => $remarks,
                ];
            }

            if ($totalCost > 0) {
                $remarks = 'Cost of goods sold (Inventory transfer)';
                $entries[] = [
                    'account_id' => DB::table('accounts')->where('name', 'Cost of Goods Sold')->value('id'),
                    'debit' => $totalCost,
                    'credit' => 0,
                    'created_by' => $user_id,
                    'remarks' => $remarks,
                ];
                $entries[] = [
                    'account_id' => DB::table('accounts')->where('name', 'Inventory')->value('id'),
                    'debit' => 0,
                    'credit' => $totalCost,
                    'created_by' => $user_id,
                    'remarks' => $remarks,
                ];
            }

            if ($sale->tax_amount > 0) {
                $remarks = 'Sales tax collected on sale';
                $entries[] = [
                    'account_id' => DB::table('accounts')->where('name', 'Tax Amount')->value('id'),
                    'debit' => 0,
                    'credit' => $sale->tax_amount,
                    'created_by' => $user_id,
                    'remarks' => $remarks,
                ];
                $entries[] = [
                    'account_id' => $sale->account_id,
                    'debit' => $sale->tax_amount,
                    'credit' => 0,
                    'created_by' => $user_id,
                    'remarks' => $remarks,
                ];
            }

            if ($sale->item_discount > 0) {
                $remarks = 'Discount provided on individual product on  sale';
                $entries[] = [
                    'account_id' => DB::table('accounts')->where('name', 'Discount')->value('id'),
                    'debit' => $sale->item_discount,
                    'credit' => 0,
                    'created_by' => $user_id,
                    'remarks' => $remarks,
                ];
                $entries[] = [
                    'account_id' => $sale->account_id,
                    'debit' => 0,
                    'credit' => $sale->item_discount,
                    'created_by' => $user_id,
                    'remarks' => $remarks,
                ];
            }
            if ($sale->other_discount > 0) {
                $remarks = 'Additional Discount provided on sale';
                $entries[] = [
                    'account_id' => DB::table('accounts')->where('name', 'Discount')->value('id'),
                    'debit' => $sale->other_discount,
                    'credit' => 0,
                    'created_by' => $user_id,
                    'remarks' => $remarks,
                ];
                $entries[] = [
                    'account_id' => $sale->account_id,
                    'debit' => 0,
                    'credit' => $sale->other_discount,
                    'created_by' => $user_id,
                    'remarks' => $remarks,
                ];
            }

            if ($sale->freight > 0) {
                $remarks = 'Freight Charge provided on sale';
                $entries[] = [
                    'account_id' => DB::table('accounts')->where('name', 'Freight')->value('id'),
                    'debit' => 0,
                    'credit' => $sale->freight,
                    'created_by' => $user_id,
                    'remarks' => $remarks,
                ];
                $entries[] = [
                    'account_id' => $sale->account_id,
                    'debit' => $sale->freight,
                    'credit' => 0,
                    'created_by' => $user_id,
                    'remarks' => $remarks,
                ];
            }

            foreach ($items as $value) {
                $remarks = $value->paymentMethod->name.' payment made by '.$sale->account->name;
                $entries[] = [
                    'account_id' => $value->payment_method_id,
                    'debit' => $value->amount,
                    'credit' => 0,
                    'created_by' => $user_id,
                    'remarks' => $remarks,
                ];
                $entries[] = [
                    'account_id' => $sale->account_id,
                    'debit' => 0,
                    'credit' => $value->amount,
                    'created_by' => $user_id,
                    'remarks' => $remarks,
                ];
            }

            $data['entries'] = $entries;

            $response = (new CreateAction)->execute($data);
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

<?php

namespace App\Actions\SaleReturn;

use App\Actions\Journal\CreateAction;
use App\Models\SaleReturn;
use Illuminate\Support\Facades\DB;

class JournalEntryAction
{
    public $userId;

    public function execute(SaleReturn $model, $userId)
    {
        try {
            $this->userId = $userId;
            $data = [
                'date' => $model->date,
                'description' => 'SaleReturn:'.$model->id,
                'reference_no' => $model->reference_no,
                'source' => 'saleReturn',
                'model' => 'SaleReturn',
                'model_id' => $model->id,
                'created_by' => $this->userId,
            ];

            $accountIds = $this->getAccountIds(['Sales Returns', 'Inventory', 'Cost of Goods Sold', 'Tax Amount', 'Discount']);

            // Cost of Goods Sold
            $totalCost = $model->items()->with('product', 'inventory')
                ->get()
                ->filter(fn ($item) => $item->product?->type === 'product')
                ->sum(fn ($item) => $item->inventory->cost * $item->quantity);

            $entries = [];

            if ($model->gross_amount > 0) {
                $remarks = 'SaleReturn to '.$model->account->name;
                $debit = $model->gross_amount;
                $credit = 0;
                $entries[] = $this->makeEntryPair($accountIds['Sales Returns'], $model->account_id, $debit, $credit, $remarks);
            }

            if ($totalCost > 0) {
                $remarks = 'Cost of goods return (Inventory transfer)';
                $debit = $totalCost;
                $credit = 0;
                $entries[] = $this->makeEntryPair($accountIds['Inventory'], $accountIds['Cost of Goods Sold'], $debit, $credit, $remarks);
            }

            if ($model->tax_amount > 0) {
                $remarks = 'Sale Returns tax collected on sale';
                $debit = $model->tax_amount;
                $credit = 0;
                $entries[] = $this->makeEntryPair($accountIds['Tax Amount'], $model->account_id, $debit, $credit, $remarks);
            }

            if ($model->item_discount > 0) {
                $remarks = 'Discount provided on individual product on sale return';
                $debit = 0;
                $credit = $model->item_discount;
                $entries[] = $this->makeEntryPair($accountIds['Discount'], $model->account_id, $debit, $credit, $remarks);
            }

            if ($model->other_discount > 0) {
                $remarks = SaleReturn::ADDITIONAL_DISCOUNT_DESCRIPTION;
                $debit = 0;
                $credit = $model->other_discount;
                $entries[] = $this->makeEntryPair($accountIds['Discount'], $model->account_id, $debit, $credit, $remarks);
            }

            foreach ($model->payments as $payment) {
                $remarks = $payment->paymentMethod->name.' payment made by '.$model->account->name;
                $debit = 0;
                $credit = $payment->amount;
                $entries[] = $this->makeEntryPair($payment->payment_method_id, $model->account_id, $debit, $credit, $remarks, 'SaleReturnPayment', $payment->id);
            }

            $data['entries'] = array_merge(...$entries);

            $response = (new CreateAction())->execute($data);

            if (! $response['success']) {
                throw new \Exception($response['message']);
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

    protected function getAccountIds(array $names): array
    {
        return DB::table('accounts')->whereIn('name', $names)->pluck('id', 'name')->toArray();
    }

    protected function makeEntryPair($accountId1, $accountId2, $debit, $credit, $remarks, $model = null, $modelId = null): array
    {
        $base = [
            'created_by' => $this->userId,
            'remarks' => $remarks,
            'model' => $model,
            'model_id' => $modelId,
        ];

        return [
            array_merge($base, [
                'account_id' => $accountId1,
                'counter_account_id' => $accountId2,
                'debit' => $debit,
                'credit' => $credit,
            ]),
            array_merge($base, [
                'account_id' => $accountId2,
                'counter_account_id' => $accountId1,
                'debit' => $credit,
                'credit' => $debit,
            ]),
        ];
    }
}

<?php

namespace App\Actions\SupplyRequest;

use App\Actions\Journal\CreateAction;
use App\Models\SupplyRequest;
use Illuminate\Support\Facades\Cache;

class JournalEntryAction
{
    public $userId;

    public function execute(SupplyRequest $supplyRequest, int $userId): array
    {
        try {
            $this->userId = $userId;

            $data = [
                'tenant_id' => $supplyRequest->tenant_id,
                'date' => $supplyRequest->date,
                'branch_id' => $supplyRequest->branch_id,
                'description' => 'SupplyRequest:'.$supplyRequest->order_no,
                'source' => 'supply_request',
                'model' => 'SupplyRequest',
                'model_id' => $supplyRequest->id,
                'created_by' => $this->userId,
            ];

            $accounts = Cache::get('accounts_slug_id_map', []);

            $entries = [];

            // Inventory & Sale entry for grand total
            if ($supplyRequest->grand_total > 0) {
                if ($supplyRequest->type === 'Add') {
                    // Add: Debit Cost of Goods Sold, Credit Inventory (stock going out)
                    $remarks = 'Supply Request '.$supplyRequest->order_no.' - Stock supplied to property';
                    $entries[] = $this->makeEntryPair(
                        $accounts['cost_of_goods_sold'],
                        $accounts['inventory'],
                        $supplyRequest->grand_total,
                        0,
                        $remarks,
                        'SupplyRequest',
                        $supplyRequest->id
                    );
                } else {
                    // Return: Debit Inventory, Credit Cost of Goods Sold (stock coming back)
                    $remarks = 'Supply Request '.$supplyRequest->order_no.' - Stock returned from property';
                    $entries[] = $this->makeEntryPair(
                        $accounts['inventory'],
                        $accounts['cost_of_goods_sold'],
                        $supplyRequest->grand_total,
                        0,
                        $remarks,
                        'SupplyRequest',
                        $supplyRequest->id
                    );
                }
            }

            // Payment entry: Debit payment mode, Credit inventory account (for Add)
            // or Debit inventory, Credit payment mode (for Return)
            if ($supplyRequest->grand_total > 0 && $supplyRequest->payment_mode_id) {
                $paymentMethodName = $supplyRequest->paymentMode?->name ?? 'Payment';
                if ($supplyRequest->type === 'Add') {
                    $remarks = $paymentMethodName.' payment for Supply Request '.$supplyRequest->order_no;
                    $entries[] = $this->makeEntryPair(
                        $supplyRequest->payment_mode_id,
                        $accounts['cost_of_goods_sold'],
                        $supplyRequest->grand_total,
                        0,
                        $remarks,
                        'SupplyRequest',
                        $supplyRequest->id
                    );
                } else {
                    $remarks = $paymentMethodName.' refund for Supply Request '.$supplyRequest->order_no;
                    $entries[] = $this->makeEntryPair(
                        $accounts['cost_of_goods_sold'],
                        $supplyRequest->payment_mode_id,
                        $supplyRequest->grand_total,
                        0,
                        $remarks,
                        'SupplyRequest',
                        $supplyRequest->id
                    );
                }
            }

            $data['entries'] = array_merge(...$entries);

            $response = (new CreateAction())->execute($data);
            if (! $response['success']) {
                throw new \Exception($response['message']);
            }

            $return['success'] = true;
            $return['data'] = $response['data'];
            $return['message'] = 'Successfully Created Journal';
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
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

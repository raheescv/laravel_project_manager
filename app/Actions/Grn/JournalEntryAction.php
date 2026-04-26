<?php

namespace App\Actions\Grn;

use App\Actions\Journal\CreateAction;
use Illuminate\Support\Facades\Cache;

class JournalEntryAction
{
    public int $userId;

    public function execute($grn, $userId)
    {
        try {
            $this->userId = $userId;

            $vendor = $grn->vendor ?? $grn->localPurchaseOrder?->vendor;

            if (! $vendor) {
                throw new \Exception('Vendor not found for GRN.');
            }

            $data = [
                'tenant_id' => $grn->tenant_id,
                'date' => $grn->date,
                'branch_id' => $grn->branch_id,
                'description' => 'GRN:'.$grn->grn_no,
                'source' => 'grn',
                'model' => 'Grn',
                'model_id' => $grn->id,
                'created_by' => $this->userId,
            ];

            $accounts = Cache::get('accounts_slug_id_map', []);

            if (empty($accounts['inventory']) || empty($accounts['unbilled_payables'])) {
                throw new \Exception('Required account heads are missing: inventory or unbilled_payables.');
            }

            $entries = [];

            // Calculate total value from LPO item rates
            $totalValue = $grn->items->sum('total');

            // Inventory Debit / Unbilled Payables Credit
            if ($totalValue > 0) {
                $remarks = 'GRN received from '.$vendor->name;
                $entries[] = $this->makeEntryPair($accounts['inventory'], $accounts['unbilled_payables'], $totalValue, 0, $remarks, 'Grn', $grn->id);
            }

            if (empty($entries)) {
                return [
                    'success' => true,
                    'message' => 'No journal entries needed (zero value).',
                ];
            }

            $data['entries'] = array_merge(...$entries);

            $response = (new CreateAction())->execute($data);
            if (! $response['success']) {
                throw new \Exception($response['message']);
            }

            return [
                'success' => true,
                'message' => 'Successfully Created GRN Journal',
            ];
        } catch (\Throwable $th) {
            return [
                'success' => false,
                'message' => $th->getMessage(),
            ];
        }
    }

    protected function makeEntryPair($accountId1, $accountId2, $debit, $credit, $remarks, $model = null, $modelId = null)
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

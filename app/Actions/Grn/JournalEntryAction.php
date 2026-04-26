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

            $remarks = 'GRN received from '.$vendor->name;
            $base = [
                'created_by' => $this->userId,
                'remarks' => $remarks,
                'model' => 'Grn',
                'model_id' => $grn->id,
            ];

            // Group items by their expense account; fall back to inventory for items without one
            $groups = $grn->items->groupBy(fn ($item) => $item->account_id ?? 'inventory');

            $debitEntries = [];
            $totalValue = 0;

            foreach ($groups as $accountKey => $items) {
                $groupTotal = $items->sum('total');
                if ($groupTotal <= 0) {
                    continue;
                }

                $debitAccountId = $accountKey === 'inventory'
                    ? $accounts['inventory']
                    : (int) $accountKey;

                $debitEntries[] = array_merge($base, [
                    'account_id' => $debitAccountId,
                    'counter_account_id' => $accounts['unbilled_payables'],
                    'debit' => $groupTotal,
                    'credit' => 0,
                ]);

                $totalValue += $groupTotal;
            }

            if (empty($debitEntries) || $totalValue <= 0) {
                return [
                    'success' => true,
                    'message' => 'No journal entries needed (zero value).',
                ];
            }

            // Single consolidated Unbilled Payables credit
            $creditEntry = array_merge($base, [
                'account_id' => $accounts['unbilled_payables'],
                'counter_account_id' => $accounts['inventory'],
                'debit' => 0,
                'credit' => $totalValue,
            ]);

            $data['entries'] = array_merge($debitEntries, [$creditEntry]);

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

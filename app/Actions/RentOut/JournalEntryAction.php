<?php

namespace App\Actions\RentOut;

use App\Actions\Journal\CreateAction;
use App\Models\Journal;
use App\Models\RentOut;
use Illuminate\Support\Facades\Cache;

class JournalEntryAction
{
    public $userId;

    public function execute(RentOut $rentOut, $userId)
    {
        try {
            $this->userId = $userId;

            // Delete existing journals for this rent out
            Journal::where('model', 'RentOut')
                ->where('model_id', $rentOut->id)
                ->delete();

            $data = [
                'tenant_id' => $rentOut->tenant_id,
                'date' => now()->toDateString(),
                'branch_id' => $rentOut->branch_id,
                'description' => 'RentOut:'.$rentOut->id,
                'source' => 'rent_out',
                'model' => 'RentOut',
                'model_id' => $rentOut->id,
                'created_by' => $this->userId,
            ];

            $accounts = Cache::get('accounts_slug_id_map', []);
            $entries = [];

            // Rent Income Entry
            if ($rentOut->rent > 0) {
                $remarks = 'Rent income from '.($rentOut->customer->name ?? 'Customer');
                $entries[] = $this->makeEntryPair(
                    $accounts['rent_income'] ?? $accounts['sale'] ?? 0,
                    $rentOut->account_id,
                    0,
                    $rentOut->rent,
                    $remarks,
                    'RentOut',
                    $rentOut->id
                );
            }

            if (! empty($entries)) {
                $data['entries'] = array_merge(...$entries);
                $response = (new CreateAction())->execute($data);
                if (! $response['success']) {
                    throw new \Exception($response['message']);
                }
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Created Journal Entries';
            $return['data'] = [];
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }

    public function executeManagementFee(RentOut $rentOut, $userId)
    {
        try {
            $this->userId = $userId;

            $data = [
                'tenant_id' => $rentOut->tenant_id,
                'date' => now()->toDateString(),
                'branch_id' => $rentOut->branch_id,
                'description' => 'RentOut Management Fee:'.$rentOut->id,
                'source' => 'rent_out',
                'model' => 'RentOut',
                'model_id' => $rentOut->id,
                'created_by' => $this->userId,
            ];

            $accounts = Cache::get('accounts_slug_id_map', []);
            $entries = [];

            // Management Fee: Dr Customer, Cr Service Charge
            $remarks = 'Management fee for RentOut:'.$rentOut->id;
            $entries[] = $this->makeEntryPair(
                $rentOut->account_id,
                $accounts['service_charge'] ?? $accounts['sale'] ?? 0,
                $rentOut->management_fee,
                0,
                $remarks,
                'RentOut',
                $rentOut->id
            );

            if (! empty($entries)) {
                $data['entries'] = array_merge(...$entries);
                $response = (new CreateAction())->execute($data);
                if (! $response['success']) {
                    throw new \Exception($response['message']);
                }
            }

            $return['success'] = true;
            $return['message'] = 'Management Fee Journal Created';
            $return['data'] = [];
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }

    public function executeDownPayment(RentOut $rentOut, $userId)
    {
        try {
            $this->userId = $userId;

            // Delete existing down payment journals
            Journal::where('model', 'RentOut')
                ->where('model_id', $rentOut->id)
                ->where('description', 'like', '%Down Payment%')
                ->delete();

            $data = [
                'tenant_id' => $rentOut->tenant_id,
                'date' => $rentOut->created_at ? $rentOut->created_at->toDateString() : now()->toDateString(),
                'branch_id' => $rentOut->branch_id,
                'description' => 'RentOut Down Payment:'.$rentOut->id,
                'source' => 'rent_out',
                'model' => 'RentOut',
                'model_id' => $rentOut->id,
                'created_by' => $this->userId,
            ];

            $accounts = Cache::get('accounts_slug_id_map', []);
            $entries = [];

            $remarks = $rentOut->down_payment_remarks ?: 'RentOut Down Payment: '.$rentOut->id;
            $paymentMethodId = $accounts[$rentOut->down_payment_mode] ?? $accounts['cash'] ?? 0;

            $entries[] = $this->makeEntryPair(
                $paymentMethodId,
                $rentOut->account_id,
                $rentOut->down_payment,
                0,
                $remarks,
                'RentOut',
                $rentOut->id
            );

            if (! empty($entries)) {
                $data['entries'] = array_merge(...$entries);
                $response = (new CreateAction())->execute($data);
                if (! $response['success']) {
                    throw new \Exception($response['message']);
                }
            }

            $return['success'] = true;
            $return['message'] = 'Down Payment Journal Created';
            $return['data'] = [];
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }

    protected function makeEntryPair($accountId1, $accountId2, $debit, $credit, $remarks, $model, $modelId)
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

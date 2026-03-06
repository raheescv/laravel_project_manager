<?php

namespace App\Actions\Package;

use App\Actions\Journal\CreateAction;
use App\Models\Package;
use App\Models\PackagePayment;
use Illuminate\Support\Facades\Cache;

class JournalEntryAction
{
    private const MODEL_PACKAGE = 'Package';

    private const MODEL_PACKAGE_PAYMENT = 'PackagePayment';

    private const REFERENCE_PREFIX = 'PKG-';

    private const SOURCE = 'package';

    protected ?int $userId = null;

    public function creditExecute(Package $package, int $userId): array
    {
        $return = ['success' => false, 'data' => [], 'message' => ''];

        try {
            $this->userId = $userId;
            $this->loadPackageRelations($package);

            $entries = $this->buildCreditEntries($package);
            if (empty($entries)) {
                $return['message'] = 'No entries to create';

                return $return;
            }

            $data = $this->buildJournalData($package, self::MODEL_PACKAGE, $package->id);
            $return = $this->executeJournalCreation($data, $entries);

        } catch (\Throwable $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }

    public function debitExecute(PackagePayment $packagePayment, int $userId): array
    {
        $return = ['success' => false, 'data' => [], 'message' => ''];

        try {
            $this->userId = $userId;
            $this->loadPackagePaymentRelations($packagePayment);

            $package = $packagePayment->package;
            $entries = $this->buildDebitEntries($packagePayment, $package);

            $data = $this->buildJournalData(
                $package,
                self::MODEL_PACKAGE_PAYMENT,
                $packagePayment->id
            );

            $return = $this->executeJournalCreation($data, $entries);

        } catch (\Throwable $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }

    protected function buildCreditEntries(Package $package): array
    {
        $entries = [];
        $accounts = $this->getAccounts();

        if ($package->amount > 0) {
            $this->validateAccountExists($accounts, 'sale');
            $remarks = "Package sale to {$package->account->name}";
            $entries[] = $this->makeEntryPair(
                $accounts['sale'],
                $package->account_id,
                0,
                $package->amount,
                $remarks,
                self::MODEL_PACKAGE,
                $package->id
            );
        }

        return $entries;
    }

    protected function buildDebitEntries(PackagePayment $packagePayment, Package $package): array
    {
        $remarks = "{$packagePayment->paymentMethod->name} payment made by {$package->account->name}";

        return [
            $this->makeEntryPair(
                $packagePayment->payment_method_id,
                $package->account_id,
                $packagePayment->amount,
                0,
                $remarks,
                self::MODEL_PACKAGE_PAYMENT,
                $packagePayment->id
            ),
        ];
    }

    protected function buildJournalData(Package $package, string $model, int $modelId): array
    {
        return [
            'tenant_id' => $package->tenant_id,
            'date' => $package->start_date,
            'branch_id' => session('branch_id'),
            'description' => "Package:{$package->id}",
            'reference_no' => self::REFERENCE_PREFIX.$package->id,
            'source' => self::SOURCE,
            'model' => $model,
            'model_id' => $modelId,
            'created_by' => $this->userId,
        ];
    }

    protected function executeJournalCreation(array $data, array $entries): array
    {
        $data['entries'] = array_merge(...$entries);
        $response = (new CreateAction())->execute($data);

        if (! $response['success']) {
            throw new \Exception($response['message']);
        }

        return [
            'success' => true,
            'data' => $response['data'] ?? [],
            'message' => 'Successfully Created Journal',
        ];
    }

    protected function loadPackageRelations(Package $package): void
    {
        if (! $package->relationLoaded('account')) {
            $package->load('account');
        }
    }

    protected function loadPackagePaymentRelations(PackagePayment $packagePayment): void
    {
        if (! $packagePayment->relationLoaded('package')) {
            $packagePayment->load('package.account');
        }
        if (! $packagePayment->relationLoaded('paymentMethod')) {
            $packagePayment->load('paymentMethod');
        }
    }

    protected function getAccounts(): array
    {
        return Cache::get('accounts_slug_id_map', []);
    }

    protected function validateAccountExists(array $accounts, string $accountKey): void
    {
        if (! isset($accounts[$accountKey])) {
            throw new \Exception("Account '{$accountKey}' not found in accounts configuration");
        }
    }

    protected function makeEntryPair(int $accountId1, int $accountId2, float $debit, float $credit, string $remarks, string $model, int $modelId): array
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

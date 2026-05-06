<?php

namespace App\Actions\Asset;

use App\Actions\Journal\GeneralVoucherJournalEntryAction;
use App\Models\AssetDepreciationSchedule;
use App\Services\AccountingPeriodLockService;
use Illuminate\Support\Facades\DB;

class PostDepreciationAction
{
    public function __construct(
        protected ?AccountingPeriodLockService $periodLockService = null
    ) {
        $this->periodLockService ??= app(AccountingPeriodLockService::class);
    }

    public function execute(AssetDepreciationSchedule $schedule, int $userId): array
    {
        try {
            if ($schedule->status === 'posted' && $schedule->journal_id) {
                return ['success' => true, 'message' => 'Schedule already posted.', 'data' => $schedule];
            }

            $schedule->loadMissing('product');
            $asset = $schedule->product;
            if (! $asset) {
                throw new \Exception('Asset not found for depreciation posting.');
            }

            if (! $asset->depreciation_expense_account_id || ! $asset->accumulated_depreciation_account_id) {
                throw new \Exception('Asset accounting mapping is incomplete. Depreciation Expense and Accumulated Depreciation accounts are required.');
            }

            $this->periodLockService->ensureOpen($schedule->schedule_date->toDateString(), $schedule->branch_id, $schedule->tenant_id);

            $journalData = [
                'tenant_id' => $schedule->tenant_id,
                'branch_id' => $schedule->branch_id,
                'date' => $schedule->schedule_date->toDateString(),
                'description' => 'Asset Depreciation:'.$asset->name,
                'remarks' => 'Period '.$schedule->period_no.' depreciation for '.$asset->name,
                'reference_number' => $asset->code ?: 'ASSET-'.$asset->id,
                'source' => 'Asset Depreciation',
                'model' => 'AssetDepreciationSchedule',
                'model_id' => $schedule->id,
                'entries' => [
                    [
                        'account_id' => $asset->depreciation_expense_account_id,
                        'counter_account_id' => $asset->accumulated_depreciation_account_id,
                        'debit' => $schedule->depreciation_amount,
                        'credit' => 0,
                        'created_by' => $userId,
                        'remarks' => 'Depreciation expense for '.$asset->name,
                        'model' => 'AssetDepreciationSchedule',
                        'model_id' => $schedule->id,
                    ],
                    [
                        'account_id' => $asset->accumulated_depreciation_account_id,
                        'counter_account_id' => $asset->depreciation_expense_account_id,
                        'debit' => 0,
                        'credit' => $schedule->depreciation_amount,
                        'created_by' => $userId,
                        'remarks' => 'Accumulated depreciation for '.$asset->name,
                        'model' => 'AssetDepreciationSchedule',
                        'model_id' => $schedule->id,
                    ],
                ],
            ];

            $response = DB::transaction(function () use ($schedule, $journalData, $userId) {
                $journalResponse = (new GeneralVoucherJournalEntryAction())->execute($userId, $journalData, null);
                if (! $journalResponse['success']) {
                    throw new \Exception($journalResponse['message']);
                }

                $schedule->update([
                    'journal_id' => $journalResponse['data']->id,
                    'status' => 'posted',
                    'posted_at' => now(),
                    'posting_note' => 'Posted automatically',
                    'updated_by' => $userId,
                ]);

                return $journalResponse;
            });

            return ['success' => true, 'message' => 'Depreciation posted successfully.', 'data' => $response['data']];
        } catch (\Throwable $th) {
            $schedule->update([
                'status' => str_contains($th->getMessage(), 'locked') ? 'locked' : 'failed',
                'posting_note' => $th->getMessage(),
                'updated_by' => $userId,
            ]);

            return ['success' => false, 'message' => $th->getMessage()];
        }
    }
}

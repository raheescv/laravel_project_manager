<?php

namespace App\Actions\Account\Vendor;

use App\Actions\Account\DeleteAction as AccountDeleteAction;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\ProjectContract;
use App\Models\Scopes\AssignedBranchScope;
use Exception;

class DeleteAction
{
    /**
     * Delete a vendor account only when nothing points at it: no project
     * contract (in any branch) and no ledger entry.
     *
     * @return array{success: bool, message: string, data?: Account}
     */
    public function execute(int $id): array
    {
        try {
            $vendor = Account::vendor()->findOrFail($id);

            $contracts = ProjectContract::withoutGlobalScope(AssignedBranchScope::class)->withTrashed()->where('account_id', $vendor->id)->count();
            if ($contracts > 0) {
                throw new Exception("{$vendor->name} has {$contracts} ".($contracts === 1 ? 'contract' : 'contracts').' and cannot be deleted.');
            }
            if (JournalEntry::withoutGlobalScopes()->where('tenant_id', $vendor->tenant_id)->where('account_id', $vendor->id)->exists()) {
                throw new Exception("{$vendor->name} has ledger entries and cannot be deleted.");
            }

            return (new AccountDeleteAction())->execute($vendor->id);
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }
    }
}

<?php

namespace App\Actions\ProjectContract\Security;

use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\ProjectContractSecurity;
use Exception;
use Illuminate\Support\Facades\DB;

class DeleteAction
{
    /**
     * Remove a security entered by mistake, with its lodging journal. One that
     * has been returned or forfeited (even in part) has to stay.
     *
     * @return array{success: bool, message: string, data?: ProjectContractSecurity}
     */
    public function execute(int $id, int $userId): array
    {
        try {
            $security = DB::transaction(function () use ($id, $userId): ProjectContractSecurity {
                $security = ProjectContractSecurity::lockForUpdate()->findOrFail($id);
                if ($security->settlements()->exists()) {
                    throw new Exception('This security has returns or forfeits recorded and cannot be deleted.');
                }

                if ($security->journal_id) {
                    JournalEntry::withoutGlobalScopes()->where('journal_id', $security->journal_id)->update(['deleted_by' => $userId]);
                    JournalEntry::withoutGlobalScopes()->where('journal_id', $security->journal_id)->delete();
                    Journal::withoutGlobalScopes()->whereKey($security->journal_id)->update(['deleted_by' => $userId]);
                    Journal::withoutGlobalScopes()->whereKey($security->journal_id)->delete();
                }

                $security->delete();

                return $security;
            });

            return ['success' => true, 'message' => 'Security deleted.', 'data' => $security];
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }
    }
}

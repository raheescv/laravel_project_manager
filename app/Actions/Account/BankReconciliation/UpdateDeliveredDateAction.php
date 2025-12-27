<?php

namespace App\Actions\Account\BankReconciliation;

use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;
use Throwable;

class UpdateDeliveredDateAction
{
    public function execute(array $journalEntryIds, string $deliveredDate)
    {
        try {
            DB::beginTransaction();

            $count = JournalEntry::whereIn('id', $journalEntryIds)->update(['delivered_date' => $deliveredDate]);

            DB::commit();

            $return['success'] = true;
            $return['message'] = "Successfully updated delivered date for {$count} journal entry(ies).";
        } catch (Throwable $th) {
            DB::rollBack();

            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }

    public function executeMultiple(array $updates)
    {
        // $updates format: [id => date, id => date, ...]
        try {
            DB::beginTransaction();

            $count = 0;
            foreach ($updates as $id => $date) {
                $updated = JournalEntry::where('id', $id)->update(['delivered_date' => $date]);
                if ($updated) {
                    $count++;
                }
            }

            DB::commit();

            $return['success'] = true;
            $return['message'] = "Successfully updated delivered date for {$count} journal entry(ies).";
        } catch (Throwable $th) {
            DB::rollBack();
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}

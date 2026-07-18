<?php

namespace App\Actions\RentOut;

use App\Enums\Property\PropertyStatus;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\RentOut;
use Illuminate\Support\Facades\DB;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = RentOut::find($id);
            if (! $model) {
                throw new \Exception("RentOut not found with the specified ID: $id.", 1);
            }

            // Everything below must succeed or fail as a unit — a half-deleted
            // agreement leaves orphaned ledger/journal rows and corrupts the books.
            DB::transaction(function () use ($model) {
                // Cascade delete related records
                $model->paymentTerms()->delete();
                $model->securities()->delete();
                $model->extends()->delete();
                $model->cheques()->delete();
                $model->utilityTerms()->delete();
                $model->services()->delete();
                $model->notes()->delete();

                // Remove the ledger rows and their journals. Journal *entries* must
                // go too — account balances are derived from entries, so deleting
                // only the journal header would leave balances permanently inflated.
                $journalIds = Journal::where('model', 'RentOut')
                    ->where('model_id', $model->id)
                    ->pluck('id');

                if ($journalIds->isNotEmpty()) {
                    JournalEntry::whereIn('journal_id', $journalIds)->delete();
                    Journal::whereIn('id', $journalIds)->delete();
                }

                $model->rentOutTransactions()->delete();

                // Free up the property
                $property = $model->property;
                if ($property) {
                    $property->update([
                        'status' => PropertyStatus::Vacant->value,
                        'availability_status' => 'available',
                    ]);
                }

                if (! $model->delete()) {
                    throw new \Exception('Oops! Something went wrong while deleting the RentOut Agreement. Please try again.', 1);
                }
            });

            $return['success'] = true;
            $return['message'] = 'Successfully Deleted RentOut Agreement';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}

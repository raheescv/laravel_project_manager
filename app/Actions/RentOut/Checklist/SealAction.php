<?php

namespace App\Actions\RentOut\Checklist;

use App\Enums\RentOut\ChecklistPhase;
use App\Models\RentOut;
use App\Support\RentOutChecklistState;
use Carbon\Carbon;

/**
 * Close a phase of the hand-over: once every signatory has signed, record the
 * actual hand-over date (today unless given, keeping a date already on file) and
 * the general remarks. A sealed phase drops out of the technician's inbox.
 *
 * On a lease/sale (one hand-over) the date is the "Hand Over Date"; an empty
 * "Inspection Date" is filled with it too, since both visits happened.
 */
class SealAction
{
    /**
     * @param  array{actual_date?: ?string, remarks?: ?string}  $data
     */
    public function execute($rentOutId, ChecklistPhase $phase, array $data)
    {
        try {
            $rentOut = RentOut::with('checklistSignatures')->findOrFail($rentOutId);

            $label = RentOutChecklistState::phaseLabel($rentOut, $phase);
            if (! RentOutChecklistState::hasPhase($rentOut, $phase)) {
                throw new \Exception('This agreement has no move-out hand-over.');
            }

            if (! RentOutChecklistState::isFullySigned($rentOut, $phase)) {
                $missing = RentOutChecklistState::signaturesRequired() - RentOutChecklistState::signaturesDone($rentOut, $phase);
                throw new \Exception("The {$label} hand-over still needs {$missing} signature(s) before it can be sealed.");
            }

            $dateColumn = RentOutChecklistState::handoverDateColumn($rentOut, $phase);
            $date = filled($data['actual_date'] ?? null)
                ? Carbon::parse($data['actual_date'])->toDateString()
                : ($rentOut->{$dateColumn}?->toDateString() ?? now()->toDateString());

            $changes = [$dateColumn => $date];
            if (RentOutChecklistState::isSingleHandover($rentOut) && ! $rentOut->actual_move_in_date) {
                $changes['actual_move_in_date'] = $date;
            }
            if (array_key_exists('remarks', $data)) {
                $changes[$phase->remarksColumn()] = filled($data['remarks']) ? trim((string) $data['remarks']) : null;
            }

            $rentOut->update($changes);

            $return['success'] = true;
            $return['message'] = "{$label} hand-over sealed";
            $return['data'] = $rentOut;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}

<?php

namespace App\Actions\RentOut\Checklist;

use App\Enums\RentOut\ChecklistItemStatus;
use App\Models\RentOut;
use App\Models\RentOutChecklistLine;
use Illuminate\Support\Facades\DB;

class SaveAction
{
    /**
     * Persist the checklist header (stored directly on the RentOut record) and sync its
     * lines to match the submitted set (update existing, create new, delete removed).
     *
     * Status semantics:
     *   - Move-In  : binary  — present (ok) or blank.
     *   - Move-Out : 3-state — good (ok) / damaged (not_ok) / blank.
     */
    public function execute($rentOutId, array $header, array $lines): array
    {
        try {
            DB::beginTransaction();

            $rentOut = RentOut::findOrFail($rentOutId);
            $rentOut->update([
                'actual_move_in_date' => $header['actual_move_in_date'] ?: null,
                'actual_move_out_date' => $header['actual_move_out_date'] ?: null,
                'facility_coordinator_id' => $header['facility_coordinator_id'] ?: null,
                'leasing_coordinator_id' => $header['leasing_coordinator_id'] ?: null,
                'move_in_remarks' => $header['move_in_remarks'] ?? null,
                'move_out_remarks' => $header['move_out_remarks'] ?? null,
            ]);

            $keepIds = [];

            foreach (array_values($lines) as $index => $line) {
                $checklistId = $line['checklist_id'] ?? null;

                // Every line references a master checklist item (2NF). A brand-new row
                // without one has nothing to persist — skip it.
                if (empty($line['id']) && empty($checklistId)) {
                    continue;
                }

                $payload = [
                    'rent_out_id' => $rentOut->id,
                    'checklist_id' => $checklistId,
                    'image_path' => $line['image_path'] ?? null,
                    'qty' => $this->toIntOrNull($line['qty'] ?? null),
                    'move_in_status' => $this->moveInStatus($line['move_in_status'] ?? null),
                    'move_in_comment' => $line['move_in_comment'] ?? null,
                    'move_out_status' => $this->moveOutStatus($line['move_out_status'] ?? null),
                    'move_out_comment' => $line['move_out_comment'] ?? null,
                    'damage_cost' => $this->toMoney($line['damage_cost'] ?? null),
                    'sort_order' => (int) ($line['sort_order'] ?? ($index + 1)),
                ];

                // Update the existing line (scoped to THIS rent-out so a stale/foreign id
                // can never hijack another rent-out's row), otherwise create it.
                if (! empty($line['id'])) {
                    $model = RentOutChecklistLine::where('rent_out_id', $rentOut->id)
                        ->where('id', $line['id'])
                        ->first();

                    if ($model) {
                        $model->update($payload);
                        $keepIds[] = $model->id;

                        continue;
                    }
                }

                $keepIds[] = RentOutChecklistLine::create($payload)->id;
            }

            // Remove the lines that were deleted in the UI.
            RentOutChecklistLine::where('rent_out_id', $rentOut->id)
                ->whereNotIn('id', $keepIds ?: [0])
                ->delete();

            DB::commit();

            return ['success' => true, 'message' => 'Checklist saved successfully', 'data' => $rentOut];
        } catch (\Throwable $e) {
            DB::rollBack();

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /** Move-In only ever stores "present" (ok) or null. */
    private function moveInStatus($value): ?string
    {
        return $value === ChecklistItemStatus::Ok->value ? ChecklistItemStatus::Ok->value : null;
    }

    /** Move-Out accepts ok / not_ok, anything else (incl. blank) becomes null. */
    private function moveOutStatus($value): ?string
    {
        return ChecklistItemStatus::tryFrom((string) $value)?->value;
    }

    private function toIntOrNull($value): ?int
    {
        return ($value === '' || $value === null) ? null : (int) $value;
    }

    private function toMoney($value): float
    {
        return ($value === '' || $value === null) ? 0.0 : round((float) $value, 2);
    }
}

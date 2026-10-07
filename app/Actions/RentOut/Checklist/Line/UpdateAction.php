<?php

namespace App\Actions\RentOut\Checklist\Line;

use App\Enums\RentOut\ChecklistItemStatus;
use App\Enums\RentOut\ChecklistPhase;
use App\Models\RentOutChecklistLine;

/**
 * Record one checklist item for one phase — its status, comment, quantity and (on
 * move-out) damage cost. Only the keys present in $data change, so a caller that
 * edits a single item never touches the rest of the checklist (unlike SaveAction,
 * which syncs the whole set from the web tab).
 */
class UpdateAction
{
    /**
     * @param  array{status?: ?string, comment?: ?string, qty?: int|string|null, damage_cost?: float|string|null}  $data
     */
    public function execute($rentOutId, $lineId, ChecklistPhase $phase, array $data)
    {
        try {
            $line = RentOutChecklistLine::where('rent_out_id', $rentOutId)->findOrFail($lineId);

            $changes = [];
            if (array_key_exists('status', $data)) {
                $changes[$phase->statusColumn()] = ChecklistItemStatus::normalizeFor($phase, $data['status']);
            }
            if (array_key_exists('comment', $data)) {
                $changes[$phase->commentColumn()] = filled($data['comment']) ? trim((string) $data['comment']) : null;
            }
            if (array_key_exists('qty', $data)) {
                $changes['qty'] = filled($data['qty']) ? max(0, (int) $data['qty']) : null;
            }
            if (array_key_exists('damage_cost', $data) && $phase === ChecklistPhase::MoveOut) {
                $changes['damage_cost'] = round(max(0, (float) ($data['damage_cost'] ?? 0)), 2);
            }

            // Damage is charged for damaged items only — clear it when the item is marked good again.
            $status = $changes[$phase->statusColumn()] ?? $line->{$phase->statusColumn()}?->value;
            if ($phase === ChecklistPhase::MoveOut && $status !== ChecklistItemStatus::NotOk->value) {
                $changes['damage_cost'] = 0;
            }

            $line->update($changes);

            $return['success'] = true;
            $return['message'] = 'Checklist item saved';
            $return['data'] = $line;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}

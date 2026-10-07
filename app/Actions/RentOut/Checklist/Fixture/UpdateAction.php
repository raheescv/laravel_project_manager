<?php

namespace App\Actions\RentOut\Checklist\Fixture;

use App\Enums\RentOut\FixtureStatus;
use App\Models\RentOutFixtureEntry;
use Carbon\Carbon;

/**
 * Edit one Fixture Comments entry. Only the keys present in $data change; moving
 * an entry to Completed stamps today's date unless a date is supplied.
 */
class UpdateAction
{
    /**
     * @param  array{comments?: ?string, status?: ?string, completed_date?: ?string}  $data
     */
    public function execute($entryId, array $data)
    {
        try {
            $entry = RentOutFixtureEntry::findOrFail($entryId);

            $changes = [];
            if (array_key_exists('comments', $data)) {
                $changes['comments'] = filled($data['comments']) ? trim((string) $data['comments']) : null;
            }
            if (array_key_exists('status', $data)) {
                $changes['status'] = (FixtureStatus::tryFrom((string) $data['status']) ?? FixtureStatus::Pending)->value;
            }
            if (array_key_exists('completed_date', $data)) {
                $changes['completed_date'] = filled($data['completed_date']) ? Carbon::parse($data['completed_date'])->toDateString() : null;
            }

            $completed = ($changes['status'] ?? $entry->status?->value) === FixtureStatus::Completed->value;
            if ($completed && empty($changes['completed_date']) && ! $entry->completed_date) {
                $changes['completed_date'] = now()->toDateString();
            }

            $entry->update($changes);

            $return['success'] = true;
            $return['message'] = 'Fixture comment saved';
            $return['data'] = $entry;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}

<?php

namespace App\Actions\RentOut\Checklist\Fixture;

use App\Enums\RentOut\FixtureStatus;
use App\Models\RentOutFixtureArea;
use App\Models\RentOutFixtureEntry;
use Illuminate\Support\Facades\DB;

/**
 * Add one rectification entry to an area's Fixture Comments block, opening the
 * block for that area first when it has none yet (keyed on rent-out + category,
 * the same way SaveFixtureAction keys it).
 */
class CreateAction
{
    /**
     * @param  array{comments?: ?string, status?: ?string}  $data
     */
    public function execute($rentOutId, string $category, array $data)
    {
        try {
            $category = trim($category);
            if ($category === '') {
                throw new \Exception('Choose the area this fixture belongs to.');
            }

            $entry = DB::transaction(function () use ($rentOutId, $category, $data) {
                $area = RentOutFixtureArea::where('rent_out_id', $rentOutId)->where('category', $category)->first()
                    ?? RentOutFixtureArea::create([
                        'rent_out_id' => $rentOutId,
                        'category' => $category,
                        'sort_order' => (int) RentOutFixtureArea::where('rent_out_id', $rentOutId)->max('sort_order') + 1,
                    ]);

                $status = FixtureStatus::tryFrom((string) ($data['status'] ?? '')) ?? FixtureStatus::Pending;

                return RentOutFixtureEntry::create([
                    'rent_out_fixture_area_id' => $area->id,
                    'comments' => filled($data['comments'] ?? null) ? trim((string) $data['comments']) : null,
                    'status' => $status->value,
                    'completed_date' => $status === FixtureStatus::Completed ? now()->toDateString() : null,
                    'sort_order' => (int) $area->entries()->max('sort_order') + 1,
                ]);
            });

            $return['success'] = true;
            $return['message'] = 'Fixture comment added';
            $return['data'] = $entry;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}

<?php

namespace App\Actions\RentOut\Checklist;

use App\Enums\RentOut\FixtureStatus;
use App\Models\RentOut;
use App\Models\RentOutFixtureArea;
use App\Models\RentOutFixtureEntry;
use Illuminate\Support\Facades\DB;

class SaveFixtureAction
{
    /**
     * Sync a rent-out's Fixture Comments blocks to match the submitted set — creating
     * the areas that are new, updating the entries that already exist, and dropping
     * whatever was removed in the UI.
     *
     * The owner's acceptance signature is NOT touched here: it is captured on the
     * checklist sign page by SaveFixtureSignatureAction, and re-saving the tab must
     * never wipe a signature that is already on file.
     *
     * @param  array  $areas  [['id'=>?int,'category'=>string,'entries'=>[...]], …]
     */
    public function execute($rentOutId, array $areas): array
    {
        try {
            DB::beginTransaction();

            $rentOut = RentOut::findOrFail($rentOutId);
            $keepAreaIds = [];

            foreach (array_values($areas) as $index => $area) {
                $category = trim((string) ($area['category'] ?? ''));

                // An area is nothing but its category — a blank one has nothing to key on.
                if ($category === '') {
                    continue;
                }

                // Keyed on the category rather than the id so an area the user re-added
                // by hand lands back on its existing row instead of tripping the
                // rent_out_id + category unique index.
                $model = RentOutFixtureArea::firstOrNew([
                    'rent_out_id' => $rentOut->id,
                    'category' => $category,
                ]);
                $model->sort_order = (int) ($area['sort_order'] ?? ($index + 1));
                $model->save();

                $keepAreaIds[] = $model->id;
                $this->syncEntries($model, $area['entries'] ?? []);
            }

            // Areas removed in the UI go, and their entries with them (cascade).
            RentOutFixtureArea::where('rent_out_id', $rentOut->id)
                ->whereNotIn('id', $keepAreaIds ?: [0])
                ->delete();

            DB::commit();

            return ['success' => true, 'message' => 'Fixture comments saved successfully', 'data' => $rentOut];
        } catch (\Throwable $e) {
            DB::rollBack();

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /** Sync one area's entries to match the submitted list. */
    private function syncEntries(RentOutFixtureArea $area, array $entries): void
    {
        $keepIds = [];

        foreach (array_values($entries) as $index => $entry) {
            $payload = [
                'rent_out_fixture_area_id' => $area->id,
                'before_image_path' => $entry['before_image_path'] ?? null,
                'after_image_path' => $entry['after_image_path'] ?? null,
                'comments' => $entry['comments'] ?? null,
                'status' => $this->status($entry['status'] ?? null),
                'completed_date' => $entry['completed_date'] ?: null,
                'sort_order' => (int) ($entry['sort_order'] ?? ($index + 1)),
            ];

            // Scoped to THIS area so a stale id can never hijack another area's row.
            if (! empty($entry['id'])) {
                $model = RentOutFixtureEntry::where('rent_out_fixture_area_id', $area->id)
                    ->where('id', $entry['id'])
                    ->first();

                if ($model) {
                    $model->update($payload);
                    $keepIds[] = $model->id;

                    continue;
                }
            }

            $keepIds[] = RentOutFixtureEntry::create($payload)->id;
        }

        RentOutFixtureEntry::where('rent_out_fixture_area_id', $area->id)
            ->whereNotIn('id', $keepIds ?: [0])
            ->delete();
    }

    /** Anything unrecognised (including blank) falls back to Pending. */
    private function status($value): string
    {
        return (FixtureStatus::tryFrom((string) $value) ?? FixtureStatus::Pending)->value;
    }
}

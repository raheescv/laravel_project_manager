<?php

namespace App\Livewire\RentOut\Tabs;

use App\Actions\RentOut\Checklist\SaveAction;
use App\Actions\RentOut\Checklist\SaveFixtureAction;
use App\Enums\RentOut\FixtureStatus;
use App\Models\Checklist;
use App\Models\RentOut;
use App\Models\RentOutChecklistLine;
use App\Models\RentOutFixtureArea;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class ChecklistTab extends Component
{
    use WithFileUploads;

    public $rentOutId;

    public $agreement_type;

    /** Property type of this rent-out's unit — seeds the Add-Items filter. */
    public $propertyTypeId = null;

    /** Pending per-line image uploads, keyed by line index. */
    public array $newImages = [];

    public ?string $actualMoveInDate = null;

    public ?string $actualMoveOutDate = null;

    public $facilityCoordinatorId = null;

    public $leasingCoordinatorId = null;

    public ?string $moveInRemarks = null;

    public ?string $moveOutRemarks = null;

    public ?string $lesseeName = null;

    public ?string $facilityCoordinatorName = null;

    public ?string $leasingCoordinatorName = null;

    public array $lines = [];

    /**
     * Fixture Comments blocks — one per area shown on this checklist, each holding its
     * own rectification entries. Rebuilt by loadFixtures() from the checklist's
     * categories plus any area saved earlier.
     */
    public array $fixtureAreas = [];

    /** Pending before/after uploads, keyed "areaIndex.entryIndex.before|after". */
    public array $newFixtureImages = [];

    /** Free-text box for adding an area that has no checklist items of its own. */
    public ?string $newAreaCategory = null;

    public array $selected = [];

    public bool $selectAll = false;

    public function mount($rentOutId)
    {
        $this->rentOutId = $rentOutId;

        $rentOut = RentOut::with(['facilityCoordinator', 'leasingCoordinator', 'account'])->findOrFail($rentOutId);

        $this->agreement_type = $rentOut->agreement_type;

        $this->propertyTypeId = $rentOut->property_type_id;
        $this->actualMoveInDate = optional($rentOut->actual_move_in_date)->format('Y-m-d');
        $this->actualMoveOutDate = optional($rentOut->actual_move_out_date)->format('Y-m-d');
        $this->facilityCoordinatorId = $rentOut->facility_coordinator_id;
        $this->leasingCoordinatorId = $rentOut->leasing_coordinator_id;
        $this->moveInRemarks = $rentOut->move_in_remarks;
        $this->moveOutRemarks = $rentOut->move_out_remarks;
        $this->facilityCoordinatorName = $rentOut->facilityCoordinator?->name;
        $this->leasingCoordinatorName = $rentOut->leasingCoordinator?->name;
        $this->lesseeName = $rentOut->account?->name;

        $this->loadLines();
    }

    protected function loadLines(): void
    {
        $lines = RentOutChecklistLine::with('item:id,name,category,image_path')
            ->where('rent_out_id', $this->rentOutId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $this->lines = $lines->map(fn ($l) => [
            'id' => $l->id,
            'checklist_id' => $l->checklist_id,
            'category' => $l->item?->category,
            'name' => $l->item?->name,
            'image_path' => $l->image_path,
            'master_image_url' => $l->item?->image_path ? asset('storage/'.$l->item->image_path) : null,
            'resolved_image_url' => $l->resolved_image_url,
            'qty' => $l->qty,
            'move_in_status' => $l->move_in_status?->value,
            'move_in_comment' => $l->move_in_comment,
            'move_out_status' => $l->move_out_status?->value,
            'move_out_comment' => $l->move_out_comment,
            'damage_cost' => (float) $l->damage_cost,
            'sort_order' => $l->sort_order,
        ])->values()->toArray();

        $this->newImages = [];
        $this->selected = [];
        $this->selectAll = false;

        $this->loadFixtures();
    }

    /**
     * Rebuild the Fixture Comments blocks. Every category on the checklist gets one, in
     * the order the item rows group; areas saved earlier follow, which is how a
     * hand-added area (one with no inventory items) survives a reload.
     */
    protected function loadFixtures(): void
    {
        $stored = RentOutFixtureArea::with('entries')
            ->where('rent_out_id', $this->rentOutId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->keyBy('category');

        $categories = collect($this->lines)
            ->map(fn ($l) => $l['category'] ?: 'Others')
            ->merge($stored->keys())
            ->unique()
            ->values();

        $this->fixtureAreas = $categories->map(function ($category, $i) use ($stored) {
            $area = $stored->get($category);

            return [
                'id' => $area?->id,
                'category' => $category,
                'sort_order' => $i + 1,
                'owner_name' => $area?->owner_name,
                'owner_signed_at' => $area?->owner_signed_at?->format('d M Y, H:i'),
                'signature_url' => $area?->owner_signature_url,
                'entries' => collect($area?->entries ?? [])->map(fn ($e) => [
                    'id' => $e->id,
                    'before_image_path' => $e->before_image_path,
                    'before_image_url' => $e->before_image_url,
                    'after_image_path' => $e->after_image_path,
                    'after_image_url' => $e->after_image_url,
                    'comments' => $e->comments,
                    'status' => $e->status?->value ?? FixtureStatus::Pending->value,
                    'completed_date' => $e->completed_date?->format('Y-m-d'),
                    'sort_order' => $e->sort_order,
                ])->values()->toArray(),
            ];
        })->values()->toArray();

        $this->newFixtureImages = [];
    }

    public function addFixtureEntry($areaIndex): void
    {
        if (! isset($this->fixtureAreas[$areaIndex])) {
            return;
        }

        $this->fixtureAreas[$areaIndex]['entries'][] = [
            'id' => null,
            'before_image_path' => null,
            'before_image_url' => null,
            'after_image_path' => null,
            'after_image_url' => null,
            'comments' => null,
            'status' => FixtureStatus::Pending->value,
            'completed_date' => null,
            'sort_order' => count($this->fixtureAreas[$areaIndex]['entries']) + 1,
        ];
    }

    public function removeFixtureEntry($areaIndex, $entryIndex): void
    {
        if (! isset($this->fixtureAreas[$areaIndex]['entries'][$entryIndex])) {
            return;
        }

        // The entry owns its photos — drop the files with it rather than orphaning them.
        $entry = $this->fixtureAreas[$areaIndex]['entries'][$entryIndex];
        foreach (['before_image_path', 'after_image_path'] as $field) {
            if (! empty($entry[$field]) && Storage::disk('public')->exists($entry[$field])) {
                Storage::disk('public')->delete($entry[$field]);
            }
        }

        array_splice($this->fixtureAreas[$areaIndex]['entries'], $entryIndex, 1);
        unset($this->newFixtureImages[$areaIndex]);
    }

    /** A before/after photo was picked — store it and point the entry at it. */
    public function updatedNewFixtureImages($value, $key): void
    {
        [$a, $e, $which] = array_pad(explode('.', (string) $key), 3, null);
        $a = (int) $a;
        $e = (int) $e;

        if (! $value || ! in_array($which, ['before', 'after'], true) || ! isset($this->fixtureAreas[$a]['entries'][$e])) {
            return;
        }

        $this->validate([
            "newFixtureImages.$key" => 'image|max:2048',
        ], [
            "newFixtureImages.$key.image" => 'The file must be an image',
            "newFixtureImages.$key.max" => 'The image size must not exceed 2MB',
        ]);

        $field = $which.'_image_path';
        $old = $this->fixtureAreas[$a]['entries'][$e][$field] ?? null;
        if ($old && Storage::disk('public')->exists($old)) {
            Storage::disk('public')->delete($old);
        }

        $path = $value->store('rent-out-fixtures/'.$this->rentOutId, 'public');
        $this->fixtureAreas[$a]['entries'][$e][$field] = $path;
        $this->fixtureAreas[$a]['entries'][$e][$which.'_image_url'] = asset('storage/'.$path);

        unset($this->newFixtureImages[$a][$e][$which]);
    }

    public function removeFixtureImage($areaIndex, $entryIndex, $which): void
    {
        if (! in_array($which, ['before', 'after'], true) || ! isset($this->fixtureAreas[$areaIndex]['entries'][$entryIndex])) {
            return;
        }

        $field = $which.'_image_path';
        $path = $this->fixtureAreas[$areaIndex]['entries'][$entryIndex][$field] ?? null;
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        $this->fixtureAreas[$areaIndex]['entries'][$entryIndex][$field] = null;
        $this->fixtureAreas[$areaIndex]['entries'][$entryIndex][$which.'_image_url'] = null;
    }

    /**
     * Add a block for an area that has no checklist items — picked from the master
     * category list or typed in. Existing areas are left alone, matched case-insensitively
     * so "Balcony" and "balcony" can't both open a block.
     */
    public function addFixtureArea($category = null): void
    {
        $category = trim((string) ($category ?? $this->newAreaCategory));
        $this->newAreaCategory = null;

        if ($category === '') {
            return;
        }

        if (collect($this->fixtureAreas)->contains(fn ($a) => strcasecmp((string) $a['category'], $category) === 0)) {
            return;
        }

        $this->fixtureAreas[] = [
            'id' => null,
            'category' => $category,
            'sort_order' => count($this->fixtureAreas) + 1,
            'owner_name' => null,
            'owner_signed_at' => null,
            'signature_url' => null,
            'entries' => [],
        ];

        $this->addFixtureEntry(count($this->fixtureAreas) - 1);
    }

    /**
     * Drop a hand-added area. Areas backed by checklist items aren't removable — they
     * would simply reappear on the next reload, since the item rows put them there.
     */
    public function removeFixtureArea($areaIndex): void
    {
        if (! isset($this->fixtureAreas[$areaIndex])) {
            return;
        }

        foreach ($this->fixtureAreas[$areaIndex]['entries'] as $entry) {
            foreach (['before_image_path', 'after_image_path'] as $field) {
                if (! empty($entry[$field]) && Storage::disk('public')->exists($entry[$field])) {
                    Storage::disk('public')->delete($entry[$field]);
                }
            }
        }

        array_splice($this->fixtureAreas, $areaIndex, 1);
        $this->newFixtureImages = [];
    }

    /** Resolved preview URL for a line array: own upload first, else the master image. */
    protected function resolvedImageUrl(array $line): ?string
    {
        if (! empty($line['image_path'])) {
            return asset('storage/'.$line['image_path']);
        }

        return $line['master_image_url'] ?? null;
    }

    /** A line image was picked — store it immediately and point the line at it. */
    public function updatedNewImages($value, $key): void
    {
        $index = (int) $key;

        if (! isset($this->lines[$index]) || ! $value) {
            return;
        }

        $this->validate([
            "newImages.$index" => 'image|max:2048',
        ], [
            "newImages.$index.image" => 'The file must be an image',
            "newImages.$index.max" => 'The image size must not exceed 2MB',
        ]);

        // Replace the line's previous own image, if any.
        $old = $this->lines[$index]['image_path'] ?? null;
        if ($old && Storage::disk('public')->exists($old)) {
            Storage::disk('public')->delete($old);
        }

        $path = $value->store('rent-out-checklist/'.$this->rentOutId, 'public');
        $this->lines[$index]['image_path'] = $path;
        $this->lines[$index]['resolved_image_url'] = asset('storage/'.$path);

        unset($this->newImages[$index]);
    }

    /** Clear the line's own image so it falls back to the master item image. */
    public function removeLineImage($index): void
    {
        if (! isset($this->lines[$index])) {
            return;
        }

        $own = $this->lines[$index]['image_path'] ?? null;
        if ($own && Storage::disk('public')->exists($own)) {
            Storage::disk('public')->delete($own);
        }

        $this->lines[$index]['image_path'] = null;
        $this->lines[$index]['resolved_image_url'] = $this->lines[$index]['master_image_url'] ?? null;
        unset($this->newImages[$index]);
    }

    protected function selectedIndexes(): array
    {
        return array_values(array_filter(
            array_map('intval', $this->selected),
            fn ($i) => isset($this->lines[$i])
        ));
    }

    public function updatedSelectAll($value): void
    {
        $this->selected = $value ? array_map('strval', array_keys($this->lines)) : [];
    }

    /** Move-In is binary: present (ok) or not. */
    public function toggleMoveIn($index): void
    {
        if (! isset($this->lines[$index])) {
            return;
        }
        $this->lines[$index]['move_in_status'] = ($this->lines[$index]['move_in_status'] ?? null) === 'ok' ? null : 'ok';
    }

    /** Move-Out is 3-state: blank -> good (ok) -> damaged (not_ok) -> blank. */
    public function cycleStatus($index, $phase): void
    {
        $key = $phase === 'move_in' ? 'move_in_status' : 'move_out_status';
        if (! isset($this->lines[$index])) {
            return;
        }
        $current = $this->lines[$index][$key] ?? null;
        $this->lines[$index][$key] = ($current === null || $current === '')
            ? 'ok'
            : ($current === 'ok' ? 'not_ok' : null);
    }

    public function bulkMoveIn($present): void
    {
        foreach ($this->selectedIndexes() as $i) {
            $this->lines[$i]['move_in_status'] = $present ? 'ok' : null;
        }
    }

    public function bulkMoveOut($status): void
    {
        $status = in_array($status, ['ok', 'not_ok'], true) ? $status : null;
        foreach ($this->selectedIndexes() as $i) {
            $this->lines[$i]['move_out_status'] = $status;
        }
    }

    public function deleteSelected(): void
    {
        $idx = $this->selectedIndexes();
        rsort($idx);
        foreach ($idx as $i) {
            array_splice($this->lines, $i, 1);
        }
        $this->selected = [];
        $this->selectAll = false;
    }

    public function removeLine($index): void
    {
        if (isset($this->lines[$index])) {
            array_splice($this->lines, $index, 1);
        }
    }

    public function openAddItems(): void
    {
        $excludeIds = collect($this->lines)
            ->pluck('checklist_id')
            ->filter()
            ->map(fn ($v) => (int) $v)
            ->values()
            ->toArray();

        $this->dispatch('open-checklist-add-items', excludeIds: $excludeIds, propertyTypeId: $this->propertyTypeId ?: '');
    }

    #[On('checklist-items-selected')]
    public function addItems($ids = []): void
    {
        $existing = collect($this->lines)
            ->pluck('checklist_id')
            ->filter()
            ->map(fn ($v) => (int) $v)
            ->all();

        foreach ((array) $ids as $id) {
            $id = (int) $id;
            if (in_array($id, $existing, true)) {
                continue;
            }
            $item = Checklist::find($id);
            if (! $item) {
                continue;
            }
            $masterUrl = $item->image_path ? asset('storage/'.$item->image_path) : null;
            $this->lines[] = [
                'id' => null,
                'checklist_id' => $item->id,
                'category' => $item->category,
                'name' => $item->name,
                'image_path' => null,
                'master_image_url' => $masterUrl,
                'resolved_image_url' => $masterUrl,
                'qty' => 1,
                'move_in_status' => null,
                'move_in_comment' => null,
                'move_out_status' => null,
                'move_out_comment' => null,
                'damage_cost' => 0,
                'sort_order' => count($this->lines) + 1,
            ];
            $existing[] = $id;
        }
    }

    public function save(): void
    {
        abort_unless(Auth::user()?->can('rent out checklist.edit'), 403);
        $header = [
            'actual_move_in_date' => $this->actualMoveInDate,
            'actual_move_out_date' => $this->actualMoveOutDate,
            'facility_coordinator_id' => $this->facilityCoordinatorId,
            'leasing_coordinator_id' => $this->leasingCoordinatorId,
            'move_in_remarks' => $this->moveInRemarks,
            'move_out_remarks' => $this->moveOutRemarks,
        ];

        $response = (new SaveAction())->execute($this->rentOutId, $header, $this->lines);

        if (! $response['success']) {
            $this->dispatch('error', ['message' => $response['message']]);

            return;
        }

        // An area with nothing recorded in it isn't persisted — the block is offered for
        // every category regardless, so saving them all would fill the table with empties.
        // An already-signed area is kept even if its entries were cleared, so the owner's
        // acceptance isn't silently thrown away.
        $fixtures = collect($this->fixtureAreas)
            ->filter(fn ($a) => ! empty($a['entries']) || ! empty($a['signature_url']))
            ->values()
            ->all();

        $fixtureResponse = (new SaveFixtureAction())->execute($this->rentOutId, $fixtures);

        if (! $fixtureResponse['success']) {
            $this->dispatch('error', ['message' => $fixtureResponse['message']]);

            return;
        }

        $this->loadLines();
        $this->dispatch('success', ['message' => $response['message']]);
    }

    public function render()
    {
        $grouped = [];
        foreach ($this->lines as $i => $line) {
            $category = $line['category'] ?: 'Others';
            $grouped[$category][] = ['i' => $i, 'line' => $line];
        }

        $damageTotal = array_sum(array_map(fn ($l) => (float) ($l['damage_cost'] ?? 0), $this->lines));

        // Where each area's block lives in $fixtureAreas, so the item table can drop it
        // in under the matching category without searching the array in the view.
        $fixtureIndex = [];
        foreach ($this->fixtureAreas as $i => $area) {
            $fixtureIndex[$area['category']] = $i;
        }

        // Areas the user can still add by hand: master categories not already on show.
        $shown = array_map(fn ($a) => mb_strtolower((string) $a['category']), $this->fixtureAreas);
        $availableCategories = Checklist::query()
            ->where('is_active', true)
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->reject(fn ($c) => in_array(mb_strtolower($c), $shown, true))
            ->values()
            ->all();

        return view('livewire.rent-out.tabs.checklist-tab', [
            'grouped' => $grouped,
            'damageTotal' => $damageTotal,
            'fixtureIndex' => $fixtureIndex,
            'availableCategories' => $availableCategories,
            'statusOptions' => FixtureStatus::options(),
        ]);
    }
}

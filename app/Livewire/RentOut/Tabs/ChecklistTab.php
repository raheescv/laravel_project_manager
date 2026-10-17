<?php

namespace App\Livewire\RentOut\Tabs;

use App\Actions\RentOut\Checklist\Fixture\RemovePhotoAction as RemoveFixturePhotoAction;
use App\Actions\RentOut\Checklist\Fixture\SavePhotoAction as SaveFixturePhotoAction;
use App\Actions\RentOut\Checklist\Line\RemovePhotoAction;
use App\Actions\RentOut\Checklist\Line\SavePhotoAction;
use App\Actions\RentOut\Checklist\SaveAction;
use App\Actions\RentOut\Checklist\SaveFixtureAction;
use App\Actions\RentOut\Checklist\SealAction;
use App\Enums\RentOut\AgreementType;
use App\Enums\RentOut\ChecklistPhase;
use App\Enums\RentOut\ChecklistSignatoryRole;
use App\Enums\RentOut\FixtureStatus;
use App\Models\Checklist;
use App\Models\RentOut;
use App\Models\RentOutChecklistLine;
use App\Models\RentOutFixtureArea;
use App\Support\ChecklistPhoto;
use App\Support\RentOutChecklistState;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
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

    /**
     * Pending move-out photo uploads, keyed by line index. Unlike the line image these
     * are written straight to the line (as the technician app does), so a later
     * "Save Checklist" from a stale tab can never wipe a photo taken on site.
     */
    public array $newMoveOutImages = [];

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

    /** The room open in the walkthrough — switched on the device, kept valid by render(). */
    public ?string $room = null;

    /** The hand-over phase being worked: move_in, or move_out on a rental. */
    public string $phase = 'move_in';

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
            // The move-out photo (taken here or in the technician app), shown beside the move-out status.
            'move_out_image_url' => $l->move_out_image_path ? asset('storage/'.$l->move_out_image_path) : null,
            'qty' => $l->qty,
            'move_in_status' => $l->move_in_status?->value,
            'move_in_comment' => $l->move_in_comment,
            'move_out_status' => $l->move_out_status?->value,
            'move_out_comment' => $l->move_out_comment,
            'damage_cost' => (float) $l->damage_cost,
            'sort_order' => $l->sort_order,
        ])->values()->toArray();

        $this->newImages = [];

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
                'entries' => collect($area->entries ?? [])->map(fn ($e) => [
                    'id' => $e->id,
                    'before_image_path' => $e->before_image_path,
                    'before_image_url' => $e->before_image_url,
                    'after_image_path' => $e->after_image_path,
                    'after_image_url' => $e->after_image_url,
                    'comments' => $e->comments,
                    'status' => $e->status->value ?? FixtureStatus::Pending->value,
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
        ChecklistPhoto::delete($entry['before_image_path'] ?? null, $entry['after_image_path'] ?? null);

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

        $field = $which.'_image_path';
        $entryId = $this->fixtureAreas[$a]['entries'][$e]['id'] ?? null;

        $path = $this->storePickedPhoto(
            "newFixtureImages.$key",
            $value,
            'rent-out-fixtures/'.$this->rentOutId,
            $this->fixtureAreas[$a]['entries'][$e][$field] ?? null,
            $entryId ? fn () => (new SaveFixturePhotoAction())->execute($entryId, $which, $value) : null,
            $field,
        );
        unset($this->newFixtureImages[$a][$e][$which]);

        if ($path) {
            $this->fixtureAreas[$a]['entries'][$e][$field] = $path;
            $this->fixtureAreas[$a]['entries'][$e][$which.'_image_url'] = asset('storage/'.$path);
        }
    }

    public function removeFixtureImage($areaIndex, $entryIndex, $which): void
    {
        if (! in_array($which, ['before', 'after'], true) || ! isset($this->fixtureAreas[$areaIndex]['entries'][$entryIndex])) {
            return;
        }

        $field = $which.'_image_path';
        $entryId = $this->fixtureAreas[$areaIndex]['entries'][$entryIndex]['id'] ?? null;

        $removed = $this->removePickedPhoto(
            $this->fixtureAreas[$areaIndex]['entries'][$entryIndex][$field] ?? null,
            $entryId ? fn () => (new RemoveFixturePhotoAction())->execute($entryId, $which) : null,
        );
        if (! $removed) {
            return;
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
        $this->room = $category;
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
            ChecklistPhoto::delete($entry['before_image_path'] ?? null, $entry['after_image_path'] ?? null);
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

        $lineId = $this->lines[$index]['id'] ?? null;

        $path = $this->storePickedPhoto(
            "newImages.$index",
            $value,
            'rent-out-checklist/'.$this->rentOutId,
            $this->lines[$index]['image_path'] ?? null,
            $lineId ? fn () => (new SavePhotoAction())->execute($this->rentOutId, $lineId, ChecklistPhase::MoveIn, $value) : null,
            'image_path',
        );
        unset($this->newImages[$index]);

        if ($path) {
            $this->lines[$index]['image_path'] = $path;
            $this->lines[$index]['resolved_image_url'] = asset('storage/'.$path);
        }
    }

    /**
     * The one path every picked photo takes. A row already on file takes it straight
     * away through $persist (a photo action), so it survives without "Save Checklist";
     * a row not saved yet holds it until then. Any failure is raised as a toast — the
     * camera buttons have nowhere to print an inline error, which is how a rejected
     * photo used to vanish without a word.
     *
     * @param  (callable(): array{success: bool, message: string, data?: mixed})|null  $persist
     * @return string|null the stored path, or null when nothing was stored
     */
    protected function storePickedPhoto(string $key, $photo, string $directory, ?string $old, ?callable $persist, string $column): ?string
    {
        try {
            $this->validate([$key => ChecklistPhoto::rules()], ChecklistPhoto::messages($key));
        } catch (ValidationException $e) {
            $this->dispatch('error', ['message' => collect($e->errors())->flatten()->first()]);

            return null;
        }

        if ($persist) {
            $response = $persist();
            if (! $response['success']) {
                $this->dispatch('error', ['message' => $response['message']]);

                return null;
            }
            $path = $response['data']->{$column};
        } else {
            try {
                $path = ChecklistPhoto::replace($photo, $directory, $old);
            } catch (\Throwable $th) {
                $this->dispatch('error', ['message' => $th->getMessage()]);

                return null;
            }
        }

        $this->dispatch('success', ['message' => 'Photo saved']);

        return $path;
    }

    /**
     * Drop a photo: a row on file loses it now through $persist (the file is going, so
     * the row must stop pointing at it); a row not saved yet just loses the file.
     *
     * @param  (callable(): array{success: bool, message: string})|null  $persist
     */
    protected function removePickedPhoto(?string $path, ?callable $persist): bool
    {
        if (! $persist) {
            ChecklistPhoto::delete($path);

            return true;
        }

        $response = $persist();
        if (! $response['success']) {
            $this->dispatch('error', ['message' => $response['message']]);
        }

        return $response['success'];
    }

    /** A move-out photo was picked — store it on the line right away. */
    public function updatedNewMoveOutImages($value, $key): void
    {
        abort_unless(Auth::user()?->can('rent out checklist.edit'), 403);
        $index = (int) $key;
        $lineId = $this->lines[$index]['id'] ?? null;

        if (! $value || ! $lineId) {
            return;
        }

        $path = $this->storePickedPhoto(
            "newMoveOutImages.$index",
            $value,
            'rent-out-checklist/'.$this->rentOutId,
            null,
            fn () => (new SavePhotoAction())->execute($this->rentOutId, $lineId, ChecklistPhase::MoveOut, $value),
            'move_out_image_path',
        );
        unset($this->newMoveOutImages[$index]);

        if ($path) {
            $this->lines[$index]['move_out_image_url'] = asset('storage/'.$path);
        }
    }

    public function removeMoveOutImage($index): void
    {
        abort_unless(Auth::user()?->can('rent out checklist.edit'), 403);
        $lineId = $this->lines[$index]['id'] ?? null;
        if (! $lineId) {
            return;
        }

        if ($this->removePickedPhoto(null, fn () => (new RemovePhotoAction())->execute($this->rentOutId, $lineId, ChecklistPhase::MoveOut))) {
            $this->lines[$index]['move_out_image_url'] = null;
        }
    }

    /**
     * Seal a hand-over phase once all three signatories have signed — the same step
     * as "Seal hand-over" in the technician app. Uses the date and remarks on screen;
     * a blank date means today.
     */
    public function seal(string $phase): void
    {
        abort_unless(Auth::user()?->can('rent out checklist.edit'), 403);
        $phase = ChecklistPhase::from($phase);
        $rentOut = RentOut::findOrFail($this->rentOutId);

        $date = RentOutChecklistState::handoverDateColumn($rentOut, $phase) === 'actual_move_out_date'
            ? $this->actualMoveOutDate
            : $this->actualMoveInDate;

        $response = (new SealAction())->execute($this->rentOutId, $phase, [
            'actual_date' => $date,
            'remarks' => $phase === ChecklistPhase::MoveIn ? $this->moveInRemarks : $this->moveOutRemarks,
        ]);

        if (! $response['success']) {
            $this->dispatch('error', ['message' => $response['message']]);

            return;
        }

        $rentOut->refresh();
        $this->actualMoveInDate = optional($rentOut->actual_move_in_date)->format('Y-m-d');
        $this->actualMoveOutDate = optional($rentOut->actual_move_out_date)->format('Y-m-d');
        $this->dispatch('success', ['message' => $response['message']]);
    }

    /**
     * Where each hand-over phase stands, for the status cards: items checked (from the
     * rows on screen), the three signatures, and whether it is sealed.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function handoverStatus(): array
    {
        $rentOut = RentOut::with('checklistSignatures')->find($this->rentOutId);
        if (! $rentOut) {
            return [];
        }

        return collect(RentOutChecklistState::phasesFor($rentOut))->map(fn (ChecklistPhase $phase) => [
            'phase' => $phase->value,
            'label' => RentOutChecklistState::phaseLabel($rentOut, $phase),
            'title' => RentOutChecklistState::isSingleHandover($rentOut) ? 'Hand-over' : $phase->label().' hand-over',
            'checked' => collect($this->lines)->filter(fn ($l) => filled($l[$phase->statusColumn()] ?? null))->count(),
            'total' => count($this->lines),
            'signatures' => collect([ChecklistSignatoryRole::FacilityCoordinator, ChecklistSignatoryRole::Lessee, ChecklistSignatoryRole::LeasingCoordinator])
                ->map(function (ChecklistSignatoryRole $role) use ($rentOut, $phase) {
                    $signature = $rentOut->checklistSignatureFor($phase, $role);

                    return [
                        'label' => $role->labelFor($rentOut->agreement_type),
                        'signed' => (bool) $signature?->signature_path,
                        'name' => $signature?->signer_name,
                        'at' => $signature?->signed_at?->format('d M Y, H:i'),
                    ];
                })->all(),
            'ready' => RentOutChecklistState::isFullySigned($rentOut, $phase),
            'sealed' => RentOutChecklistState::isSealed($rentOut, $phase),
            'sealed_on' => $rentOut->{RentOutChecklistState::handoverDateColumn($rentOut, $phase)}?->format('d M Y'),
        ])->all();
    }

    /** Clear the line's own image so it falls back to the master item image. */
    public function removeLineImage($index): void
    {
        if (! isset($this->lines[$index])) {
            return;
        }

        $lineId = $this->lines[$index]['id'] ?? null;

        $removed = $this->removePickedPhoto(
            $this->lines[$index]['image_path'] ?? null,
            $lineId ? fn () => (new RemovePhotoAction())->execute($this->rentOutId, $lineId, ChecklistPhase::MoveIn) : null,
        );
        if (! $removed) {
            return;
        }

        $this->lines[$index]['image_path'] = null;
        $this->lines[$index]['resolved_image_url'] = $this->lines[$index]['master_image_url'] ?? null;
        unset($this->newImages[$index]);
    }

    /** Move-In is binary: present (ok) or not. */
    public function toggleMoveIn($index): void
    {
        if (! isset($this->lines[$index])) {
            return;
        }
        $this->lines[$index]['move_in_status'] = ($this->lines[$index]['move_in_status'] ?? null) === 'ok' ? null : 'ok';
    }

    /** Move-Out is good (ok) or damaged (not_ok); tapping the chosen one again clears it. */
    public function setMoveOut($index, $status): void
    {
        if (! isset($this->lines[$index]) || ! in_array($status, ['ok', 'not_ok'], true)) {
            return;
        }
        $this->lines[$index]['move_out_status'] = ($this->lines[$index]['move_out_status'] ?? null) === $status ? null : $status;
    }

    /**
     * "All present" / "All good" for one room. A move-out never overrides an item
     * already marked damaged — the same rule as the technician app's mark-ok.
     */
    public function markRoom($category, $phase): void
    {
        foreach ($this->lines as $i => $line) {
            if (($line['category'] ?: 'Others') !== $category) {
                continue;
            }
            if ($phase === ChecklistPhase::MoveIn->value) {
                $this->lines[$i]['move_in_status'] = 'ok';
            } elseif (($line['move_out_status'] ?? null) !== 'not_ok') {
                $this->lines[$i]['move_out_status'] = 'ok';
            }
        }
    }

    public function stepQty($index, $step): void
    {
        if (isset($this->lines[$index])) {
            $this->lines[$index]['qty'] = max(0, (int) ($this->lines[$index]['qty'] ?? 0) + (int) $step);
        }
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
        // One room per category the items sit in, then any area added by hand (it has
        // no items, so only its Fixture Comments put it on screen).
        $rooms = [];
        foreach ($this->lines as $i => $line) {
            $category = $line['category'] ?: 'Others';
            $rooms[$category]['rows'][] = ['i' => $i, 'line' => $line];
        }
        foreach ($this->fixtureAreas as $a => $area) {
            $rooms[$area['category']]['fixture'] = $a;
        }
        $rooms = collect($rooms)->map(fn ($room, $name) => [
            'name' => (string) $name,
            'rows' => $room['rows'] ?? [],
            'fixture' => $room['fixture'] ?? null,
            'checked' => [
                'move_in' => collect($room['rows'] ?? [])->filter(fn ($r) => filled($r['line']['move_in_status']))->count(),
                'move_out' => collect($room['rows'] ?? [])->filter(fn ($r) => filled($r['line']['move_out_status']))->count(),
            ],
        ])->values()->all();

        $names = array_column($rooms, 'name');
        if (! in_array($this->room, $names, true)) {
            $this->room = $names[0] ?? null;
        }
        if ($this->agreement_type != AgreementType::Rental) {
            $this->phase = ChecklistPhase::MoveIn->value;
        }

        $damageTotal = array_sum(array_map(fn ($l) => (float) ($l['damage_cost'] ?? 0), $this->lines));

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
            'rooms' => $rooms,
            'damageTotal' => $damageTotal,
            'availableCategories' => $availableCategories,
            'statusOptions' => FixtureStatus::options(),
            'handover' => $this->handoverStatus(),
        ]);
    }
}

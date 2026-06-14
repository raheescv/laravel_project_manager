<?php

namespace App\Livewire\RentOut\Tabs;

use App\Actions\RentOut\Checklist\SaveAction;
use App\Models\Checklist;
use App\Models\RentOut;
use App\Models\RentOutChecklistLine;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class ChecklistTab extends Component
{
    use WithFileUploads;

    public $rentOutId;

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

    public array $selected = [];

    public bool $selectAll = false;

    public function mount($rentOutId)
    {
        $this->rentOutId = $rentOutId;

        $rentOut = RentOut::with(['facilityCoordinator', 'leasingCoordinator', 'account'])->findOrFail($rentOutId);

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
        abort_unless(auth()->user()?->can('rent out checklist.edit'), 403);
        $header = [
            'actual_move_in_date' => $this->actualMoveInDate,
            'actual_move_out_date' => $this->actualMoveOutDate,
            'facility_coordinator_id' => $this->facilityCoordinatorId,
            'leasing_coordinator_id' => $this->leasingCoordinatorId,
            'move_in_remarks' => $this->moveInRemarks,
            'move_out_remarks' => $this->moveOutRemarks,
        ];

        $response = (new SaveAction())->execute($this->rentOutId, $header, $this->lines);

        if ($response['success']) {
            $this->loadLines();
            $this->dispatch('success', ['message' => $response['message']]);
        } else {
            $this->dispatch('error', ['message' => $response['message']]);
        }
    }

    public function render()
    {
        $grouped = [];
        foreach ($this->lines as $i => $line) {
            $category = $line['category'] ?: 'Others';
            $grouped[$category][] = ['i' => $i, 'line' => $line];
        }

        $damageTotal = array_sum(array_map(fn ($l) => (float) ($l['damage_cost'] ?? 0), $this->lines));

        return view('livewire.rent-out.tabs.checklist-tab', [
            'grouped' => $grouped,
            'damageTotal' => $damageTotal,
        ]);
    }
}

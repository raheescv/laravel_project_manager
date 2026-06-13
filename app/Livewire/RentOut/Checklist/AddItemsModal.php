<?php

namespace App\Livewire\RentOut\Checklist;

use App\Models\Checklist;
use App\Models\PropertyType;
use Livewire\Attributes\On;
use Livewire\Component;

class AddItemsModal extends Component
{
    public string $search = '';

    public string $filterCategory = '';

    /** Selected property type id (string for select binding), '' = all. */
    public string $filterPropertyType = '';

    /** @var int[] ids already on the checklist — shown disabled */
    public array $excludeIds = [];

    /** @var string[] ticked item ids (kept as strings to match checkbox bindings) */
    public array $selectedIds = [];

    public bool $loaded = false;

    #[On('open-checklist-add-items')]
    public function openModal($excludeIds = [], $propertyTypeId = '')
    {
        $this->excludeIds = array_values(array_unique(array_map('intval', (array) $excludeIds)));
        $this->selectedIds = [];
        $this->search = '';
        $this->filterCategory = '';
        // Default the filter to the unit's property type; user can clear it.
        $this->filterPropertyType = $propertyTypeId ? (string) $propertyTypeId : '';
        $this->loaded = true;
        $this->dispatch('ToggleChecklistAddItemsModal');
    }

    protected function baseQuery()
    {
        return Checklist::where('is_active', true)
            ->when($this->search, function ($q, $value) {
                $q->where(function ($w) use ($value): void {
                    $w->where('name', 'like', "%{$value}%")
                        ->orWhere('category', 'like', "%{$value}%");
                });
            })
            ->when($this->filterCategory, fn ($q, $v) => $q->where('category', $v))
            ->when($this->filterPropertyType, fn ($q, $v) => $q->where(function ($w) use ($v): void {
                // Items for this type, plus universal items (no property type set).
                $w->where('property_type_id', $v)->orWhereNull('property_type_id');
            }))
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    /** Visible, not-yet-added item ids as strings. */
    protected function selectableVisibleIds(): array
    {
        return $this->baseQuery()
            ->whereNotIn('id', $this->excludeIds ?: [0])
            ->pluck('id')
            ->map(fn ($v) => (string) $v)
            ->all();
    }

    /** Select every shown item, or clear them if all are already selected. */
    public function toggleVisible(): void
    {
        $visible = $this->selectableVisibleIds();

        if (empty($visible)) {
            return;
        }

        $allSelected = empty(array_diff($visible, $this->selectedIds));

        $this->selectedIds = $allSelected
            ? array_values(array_diff($this->selectedIds, $visible))
            : array_values(array_unique(array_merge($this->selectedIds, $visible)));
    }

    public function clearSelection(): void
    {
        $this->selectedIds = [];
    }

    public function clearSearch(): void
    {
        $this->search = '';
        $this->filterCategory = '';
        $this->filterPropertyType = '';
    }

    public function addSelected()
    {
        $ids = array_values(array_diff(array_map('intval', $this->selectedIds), $this->excludeIds));

        if (! empty($ids)) {
            $this->dispatch('checklist-items-selected', ids: $ids);
        }

        $this->selectedIds = [];
        $this->dispatch('ToggleChecklistAddItemsModal');
    }

    public function render()
    {
        $items = $this->loaded
            ? $this->baseQuery()->get()->groupBy('category')
            : collect();

        $categories = Checklist::query()
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $propertyTypes = PropertyType::orderBy('name')->get(['id', 'name']);

        // Counts for the summary / toggle-button label.
        $visibleSelectable = $items
            ->flatten()
            ->reject(fn ($i) => in_array((int) $i->id, $this->excludeIds, true))
            ->pluck('id')
            ->map(fn ($v) => (string) $v);

        $allShownSelected = $visibleSelectable->isNotEmpty()
            && $visibleSelectable->every(fn ($id) => in_array($id, $this->selectedIds, true));

        return view('livewire.rent-out.checklist.add-items-modal', [
            'items' => $items,
            'categories' => $categories,
            'propertyTypes' => $propertyTypes,
            'shownCount' => $items->sum(fn ($g) => $g->count()),
            'categoryCount' => $items->count(),
            'allShownSelected' => $allShownSelected,
        ]);
    }
}

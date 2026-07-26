<?php

namespace App\Livewire\Concerns;

use App\Models\UserPreference;
use Livewire\Attributes\Renderless;

/**
 * Per-user column visibility for a Livewire table.
 *
 * The consuming component declares the shipped columns (`defaultColumns()`) and
 * the preference namespace (`columnPreferenceKey()`), then calls
 * `initializeColumnPreferences()` from its `mount()`. From there the visibility
 * map lives in `$columns` and every change is written back to the signed-in
 * user's `user_preferences` row.
 *
 * Views may either bind straight to the property (`wire:model.live="columns.x"`)
 * or keep the toggling client side and call `$wire.setColumnVisibility(...)`.
 */
trait HasColumnPreferences
{
    /** @var array<string, bool> */
    public array $columns = [];

    /**
     * Columns this screen ships, with their out-of-the-box visibility.
     *
     * @return array<string, bool>
     */
    abstract protected function defaultColumns(): array;

    /** Dotted preference key holding this table's column visibility map. */
    abstract protected function columnPreferenceKey(): string;

    /** Seed `$columns` from the user's saved preference. Call from `mount()`. */
    public function initializeColumnPreferences(): void
    {
        $this->columns = $this->resolveColumns(UserPreference::getValue($this->columnPreferenceKey()));
    }

    /** Livewire writes into `$columns` directly when a view binds `wire:model="columns.x"`. */
    public function updatedColumns(): void
    {
        $this->columns = $this->resolveColumns($this->columns);
        $this->persistColumns();
    }

    /**
     * Toggle one column without re-rendering, for views that hide columns client side.
     *
     * @return array<string, bool> the stored visibility map
     */
    #[Renderless]
    public function setColumnVisibility(string $column, $visible): array
    {
        if (array_key_exists($column, $this->defaultColumns())) {
            $this->columns[$column] = (bool) $visible;
            $this->persistColumns();
        }

        return $this->columns;
    }

    /** @return array<string, bool> the restored defaults */
    public function resetColumns(): array
    {
        $this->columns = $this->defaultColumns();
        $this->persistColumns();

        return $this->columns;
    }

    protected function persistColumns(): void
    {
        UserPreference::setValue($this->columnPreferenceKey(), $this->columns);
    }

    /**
     * Only honour keys we still ship, so adding or dropping a column later never
     * leaves a stale preference deciding what renders.
     *
     * @return array<string, bool>
     */
    private function resolveColumns($saved): array
    {
        $defaults = $this->defaultColumns();

        if (! is_array($saved)) {
            return $defaults;
        }

        return array_merge(
            $defaults,
            array_map(fn ($visible) => (bool) $visible, array_intersect_key($saved, $defaults))
        );
    }
}

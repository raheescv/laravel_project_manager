<?php

namespace App\Livewire\RentOut\Concerns;

use App\Models\PropertyBuilding;
use App\Models\PropertyGroup;
use App\Models\PropertyType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

trait HasRentOutReportFilters
{
    // Property & Location Filters
    public $filterGroup = '';

    public $filterBuilding = '';

    public $filterType = '';

    public $filterProperty = '';

    // Customer & People Filters
    public $filterCustomer = '';

    public $filterOwnership = '';

    // Date Filters
    public $dateFrom = '';

    public $dateTo = '';

    // Table Controls
    public $search = '';

    public $limit = 20;

    public $sortField = 'id';

    public $sortDirection = 'desc';

    // Selection
    public $selected = [];

    public $selectAll = false;

    // Column Visibility
    public $visibleColumns = [];

    public function initializeHasRentOutReportFilters(): void
    {
        $this->dateFrom = $this->dateFrom ?: Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = $this->dateTo ?: Carbon::now()->endOfMonth()->format('Y-m-d');

        if (empty($this->visibleColumns)) {
            $this->visibleColumns = $this->getDefaultColumns();
        }
    }

    /**
     * Override this in each component to define default visible columns.
     */
    public function getDefaultColumns(): array
    {
        return [];
    }

    public function toggleColumn(string $column): void
    {
        if (in_array($column, $this->visibleColumns)) {
            $this->visibleColumns = array_values(array_diff($this->visibleColumns, [$column]));
        } else {
            $this->visibleColumns[] = $column;
        }
    }

    public function isColumnVisible(string $column): bool
    {
        return in_array($column, $this->visibleColumns);
    }

    public function resetColumns(): void
    {
        $this->visibleColumns = $this->getDefaultColumns();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'desc';
        }
    }

    public function applyFilters(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->filterGroup = '';
        $this->filterBuilding = '';
        $this->filterType = '';
        $this->filterProperty = '';
        $this->filterCustomer = '';
        $this->filterOwnership = '';
        $this->dateFrom = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = Carbon::now()->endOfMonth()->format('Y-m-d');
        $this->search = '';
        $this->resetPage();
    }

    public function updatedSelectAll($value): void
    {
        $this->selected = $value ? $this->getSelectableIds() : [];
    }

    public function selectAllItems(bool $value = true): void
    {
        $this->selectAll = $value;
        $this->selected = $value ? $this->getSelectableIds() : [];
    }

    public function updated($key, $value): void
    {
        if (! in_array($key, ['selectAll']) && ! str_starts_with($key, 'selected.')) {
            $this->resetPage();
        }
    }

    /**
     * Override this to return the selectable IDs for the current query.
     */
    protected function getSelectableIds(): array
    {
        return [];
    }

    /**
     * Apply common property/location filters to a RentOut-based query.
     * Expects the query to have a rentOut relationship (or be the RentOut model itself).
     */
    protected function applyRentOutFilters(Builder $query, string $rentOutRelation = 'rentOut'): Builder
    {
        return $query
            ->when($this->filterGroup, function ($q, $value) use ($rentOutRelation) {
                return $q->whereHas($rentOutRelation, fn ($r) => $r->where('property_group_id', $value));
            })
            ->when($this->filterBuilding, function ($q, $value) use ($rentOutRelation) {
                return $q->whereHas($rentOutRelation, fn ($r) => $r->where('property_building_id', $value));
            })
            ->when($this->filterType, function ($q, $value) use ($rentOutRelation) {
                return $q->whereHas($rentOutRelation, fn ($r) => $r->where('property_type_id', $value));
            })
            ->when($this->filterProperty, function ($q, $value) use ($rentOutRelation) {
                return $q->whereHas($rentOutRelation, fn ($r) => $r->where('property_id', $value));
            })
            ->when($this->filterCustomer, function ($q, $value) use ($rentOutRelation) {
                return $q->whereHas($rentOutRelation, fn ($r) => $r->where('account_id', $value));
            })
            ->when($this->filterOwnership, function ($q, $value) use ($rentOutRelation) {
                return $q->whereHas("{$rentOutRelation}.property", fn ($p) => $p->where('ownership', $value));
            });
    }

    /**
     * Apply date range filter on a given date column.
     */
    protected function applyDateFilter(Builder $query, string $dateColumn = 'due_date'): Builder
    {
        return $query
            ->when($this->dateFrom, fn ($q, $v) => $q->where($dateColumn, '>=', $v))
            ->when($this->dateTo, fn ($q, $v) => $q->where($dateColumn, '<=', $v));
    }

    /**
     * Apply search on customer name via rentOut relationship.
     */
    protected function applySearch(Builder $query, string $rentOutRelation = 'rentOut'): Builder
    {
        return $query->when($this->search, function ($q, $value) use ($rentOutRelation) {
            return $q->where(function ($q) use ($value, $rentOutRelation) {
                $q->where('id', 'like', "%{$value}%")
                    ->orWhereHas("{$rentOutRelation}.customer", fn ($c) => $c->where('name', 'like', "%{$value}%"))
                    ->orWhereHas("{$rentOutRelation}.property", fn ($p) => $p->where('number', 'like', "%{$value}%"));
            });
        });
    }

    /**
     * Get common filter data for views (dropdowns).
     */
    protected function getFilterData(): array
    {
        return [
            'groups' => PropertyGroup::orderBy('name')->pluck('name', 'id'),
            'buildings' => PropertyBuilding::when($this->filterGroup, fn ($q, $v) => $q->where('property_group_id', $v))
                ->orderBy('name')->pluck('name', 'id'),
            'types' => PropertyType::orderBy('name')->pluck('name', 'id'),
        ];
    }

    /**
     * Build the filtered query — must be implemented by each component.
     * Used by both render() and download() for DRY.
     */
    abstract protected function buildQuery(): Builder;
}

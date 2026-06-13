<?php

namespace App\Livewire\Settings\ChecklistItem;

use App\Actions\Settings\ChecklistItem\DeleteAction;
use App\Models\Checklist;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Table extends Component
{
    use WithPagination;

    public $search = '';

    public $filterCategory = '';

    public $filterPropertyType = '';

    public $limit = 10;

    public $selected = [];

    public $selectAll = false;

    public $sortField = 'sort_order';

    public $sortDirection = 'asc';

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'ChecklistItem-Refresh-Component' => '$refresh',
    ];

    public function delete()
    {
        try {
            DB::beginTransaction();
            if (! count($this->selected)) {
                throw new \Exception('Please select any item to delete.', 1);
            }
            foreach ($this->selected as $id) {
                $response = (new DeleteAction())->execute($id);
                if (! $response['success']) {
                    throw new \Exception($response['message'], 1);
                }
            }
            $this->dispatch('success', ['message' => 'Successfully Deleted '.count($this->selected).' items']);
            DB::commit();
            if (count($this->selected) > 10) {
                $this->resetPage();
            }
            $this->selected = [];
            $this->selectAll = false;
            $this->dispatch('RefreshChecklistItemTable');
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function updated($key, $value)
    {
        if (! in_array($key, ['SelectAll']) && ! preg_match('/^selected\..*/', $key)) {
            $this->resetPage();
        }
    }

    public function updatingFilterCategory()
    {
        $this->resetPage();
    }

    public function updatingFilterPropertyType()
    {
        $this->resetPage();
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selected = Checklist::latest()->limit(2000)->pluck('id')->toArray();
        } else {
            $this->selected = [];
        }
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'desc';
        }
    }

    public function render()
    {
        $query = Checklist::query()
            ->with('propertyType:id,name')
            ->when($this->search ?? '', function ($query, $value) {
                return $query->where(function ($q) use ($value): void {
                    $q->where('name', 'like', "%{$value}%")
                        ->orWhere('category', 'like', "%{$value}%");
                });
            })
            ->when($this->filterCategory ?? '', function ($query, $value) {
                return $query->where('category', $value);
            })
            ->when($this->filterPropertyType ?? '', function ($query, $value) {
                // "none" isolates universal items; an id matches that specific type.
                $value === 'none'
                    ? $query->whereNull('property_type_id')
                    : $query->where('property_type_id', $value);
            });

        // Always group rows by category first: the same item name recurs in many
        // rooms (Ceiling, Light Switches, …) by design, so grouping keeps them
        // reading as room sections rather than duplicates. The chosen column sorts
        // within each category.
        if ($this->sortField === 'category') {
            $query->orderBy('category', $this->sortDirection)->orderBy('sort_order');
        } else {
            $query->orderBy('category')->orderBy($this->sortField, $this->sortDirection);
        }

        $data = $query->paginate($this->limit);

        $categories = Checklist::query()
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('livewire.settings.checklist-item.table', [
            'data' => $data,
            'categories' => $categories,
        ]);
    }
}

<?php

namespace App\Livewire\Settings\TailoringCategoryModel;

use App\Actions\Settings\TailoringCategoryModel\DeleteAction;
use App\Models\TailoringCategory;
use App\Models\TailoringCategoryModel;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Table extends Component
{
    use WithPagination;

    public $search = '';

    public $limit = 10;

    public $selected = [];

    public $selectAll = false;

    public $categoryId = '';

    public $sortField = 'id';

    public $sortDirection = 'desc';

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'RefreshTailoringCategoryModelTable' => '$refresh',
        'SelectCategoryForModels' => 'selectCategory',
    ];

    public function selectCategory($id = null)
    {
        $this->categoryId = $id;
        $this->resetPage();
    }

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
            $this->dispatch('RefreshTailoringCategoryModelTable');
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

    public function updatedSelectAll($value)
    {
        if ($value && $this->categoryId) {
            $this->selected = TailoringCategoryModel::byCategory($this->categoryId)->limit(2000)->pluck('id')->toArray();
        } else {
            $this->selected = [];
        }
    }

    public function updatedCategoryId()
    {
        $this->selected = [];
        $this->selectAll = false;
        $this->resetPage();
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
        $categories = TailoringCategory::ordered()->get(['id', 'name']);

        if (! $this->categoryId) {
            $data = TailoringCategoryModel::whereRaw('1=0')->paginate($this->limit);
        } else {
            $data = TailoringCategoryModel::with('category')
                ->byCategory($this->categoryId)
                ->when($this->search ?? '', function ($query, $value) {
                    return $query->where(function ($q) use ($value) {
                        $q->where('name', 'like', "%{$value}%")
                            ->orWhere('description', 'like', "%{$value}%");
                    });
                })
                ->orderBy($this->sortField, $this->sortDirection)
                ->paginate($this->limit);
        }

        return view('livewire.settings.tailoring-category-model.table', [
            'data' => $data,
            'categories' => $categories,
        ]);
    }
}

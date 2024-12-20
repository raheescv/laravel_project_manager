<?php

namespace App\Livewire\Category;

use App\Actions\Category\DeleteAction;
use App\Exports\CategoryExport;
use App\Jobs\Export\ExportCategoryJob;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class Table extends Component
{
    use WithPagination;

    public $exportLink = '';

    public $search = '';

    public $limit = 10;

    public $selected = [];

    public $selectAll = false;

    public $sortField = 'id';

    public $sortDirection = 'desc';

    protected $queryString = ['sortField', 'sortDirection'];

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'Category-Refresh-Component' => '$refresh',
    ];

    public function delete()
    {
        try {
            DB::beginTransaction();
            foreach ($this->selected as $id) {
                $response = (new DeleteAction)->execute($id);
                if (! $response['success']) {
                    throw new \Exception($response['message'], 1);
                }
            }
            $this->dispatch('success', ['message' => 'Successfully Deleted ' . count($this->selected) . ' items']);
            DB::commit();
            if (count($this->selected) > 10) {
                $this->resetPage();
            }
            $this->selected = [];

            $this->selectAll = false;
            $this->dispatch('RefreshCategoryTable');
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingLimit()
    {
        $this->resetPage();
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selected = Category::latest()->limit(2000)->pluck('id')->toArray();
        } else {
            $this->selected = [];
        }
    }

    public function export()
    {
        $count = Category::count();
        if ($count > 2000) {
            ExportCategoryJob::dispatch(auth()->user());
            $this->dispatch('success', ['message' => 'You will get your file in your mailbox.']);
        } else {
            $exportFileName = 'category_' . now()->timestamp . '.xlsx';

            return Excel::download(new CategoryExport, $exportFileName);
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
        $data = Category::orderBy($this->sortField, $this->sortDirection)
            ->where('name', 'like', '%' . $this->search . '%')
            ->latest()
            ->paginate($this->limit);

        return view('livewire.category.table', [
            'data' => $data,
        ]);
    }
}

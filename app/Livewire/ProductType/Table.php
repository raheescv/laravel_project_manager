<?php

namespace App\Livewire\ProductType;

use App\Actions\ProductType\DeleteAction;
use App\Exports\ProductTypeExport;
use App\Jobs\Export\ExportProductTypesJob;
use App\Models\ProductType;
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
        'ProductType-Refresh-Component' => '$refresh',
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
            $this->dispatch('success', ['message' => 'Successfully Deleted '.count($this->selected).' items']);
            DB::commit();
            if (count($this->selected) > 10) {
                $this->resetPage();
            }
            $this->selected = [];

            $this->selectAll = false;
            $this->dispatch('RefreshProductTypeTable');
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
            $this->selected = ProductType::latest()->limit(2000)->pluck('id')->toArray();
        } else {
            $this->selected = [];
        }
    }

    public function export()
    {
        $count = ProductType::count();
        if ($count > 2000) {
            ExportProductTypesJob::dispatch(auth()->user());
            $this->dispatch('success', ['message' => 'You will get your file in your mailbox.']);
        } else {
            $exportFileName = 'product_types_'.now()->timestamp.'.xlsx';

            return Excel::download(new ProductTypeExport, $exportFileName);
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
        $data = ProductType::orderBy($this->sortField, $this->sortDirection)
            ->where('name', 'like', '%'.$this->search.'%')
            ->latest()
            ->paginate($this->limit);

        return view('livewire.product-type.table', [
            'data' => $data,
        ]);
    }
}

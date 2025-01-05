<?php

namespace App\Livewire\Product;

use App\Actions\Product\DeleteAction;
use App\Exports\ProductExport;
use App\Jobs\Export\ExportProductJob;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class Table extends Component
{
    use WithPagination;

    public $exportLink = '';

    public $search = '';

    public $department_id = '';

    public $main_category_id = '';

    public $sub_category_id = '';

    public $is_selling = '';

    public $unit_id = '';

    public $limit = 10;

    public $selected = [];

    public $selectAll = false;

    public $sortField = 'id';

    public $sortDirection = 'desc';

    protected $queryString = ['sortField', 'sortDirection'];

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'Product-Refresh-Component' => '$refresh',
    ];

    public function delete()
    {
        try {
            DB::beginTransaction();
            if (! count($this->selected)) {
                throw new \Exception('Please select any item to delete.', 1);
            }
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
            $this->dispatch('RefreshProductTable');
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
        if ($value) {
            $this->selected = Product::latest()->limit(2000)->pluck('id')->toArray();
        } else {
            $this->selected = [];
        }
    }

    public function export()
    {
        $count = Product::count();
        if ($count > 2000) {
            ExportProductJob::dispatch(auth()->user());
            $this->dispatch('success', ['message' => 'You will get your file in your mailbox.']);
        } else {
            $exportFileName = 'product_'.now()->timestamp.'.xlsx';

            return Excel::download(new ProductExport, $exportFileName);
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
        $data = Product::orderBy($this->sortField, $this->sortDirection)
            ->when($this->search ?? '', function ($query, $value) {
                $query->where(function ($q) use ($value) {
                    $value = trim($value);
                    $q->where('name', 'like', "%{$value}%")
                        ->orWhere('name_arabic', 'like', "%{$value}%")
                        ->orWhere('code', 'like', "%{$value}%")
                        ->orWhere('cost', 'like', "%{$value}%")
                        ->orWhere('barcode', 'like', "%{$value}%")
                        ->orWhere('mrp', 'like', "%{$value}%");
                });
            })
            ->when($this->department_id ?? '', function ($query, $value) {
                $query->where('department_id', $value);
            })
            ->when($this->main_category_id ?? '', function ($query, $value) {
                $query->where('main_category_id', $value);
            })
            ->when($this->sub_category_id ?? '', function ($query, $value) {
                $query->where('sub_category_id', $value);
            })
            ->when($this->unit_id ?? '', function ($query, $value) {
                $query->where('unit_id', $value);
            })
            ->when($this->is_selling ?? '', function ($query, $value) {
                $query->where('is_selling', $value);
            })
            ->latest()
            ->paginate($this->limit);

        return view('livewire.product.table', [
            'data' => $data,
        ]);
    }
}

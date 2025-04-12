<?php

namespace App\Livewire\Log;

use App\Exports\InventoryLogExport;
use App\Jobs\Export\ExportInventoryLogJob;
use App\Models\InventoryLog;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class Inventory extends Component
{
    use WithPagination;

    public $search = '';

    public $product_id = '';

    public $branch_id = '';

    public $from_date;

    public $to_date;

    public $limit = 10;

    public $sortField = 'inventory_logs.id';

    public $sortDirection = 'desc';

    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        $this->from_date = date('Y-m-d', strtotime('-1 days'));
        $this->to_date = date('Y-m-d');
        $this->branch_id = session('branch_id');

    }

    public function export()
    {
        $count = InventoryLog::query()
            ->when($this->search, function ($query, $value) {
                return $query->where(function ($q) use ($value) {
                    $value = trim($value);
                    $q->where('batch', 'like', "%{$value}%")
                        ->orWhere('barcode', 'like', "%{$value}%")
                        ->orWhere('remarks', 'like', "%{$value}%")
                        ->orWhere('quantity_in', 'like', "%{$value}%")
                        ->orWhere('quantity_out', 'like', "%{$value}%")
                        ->orWhere('balance', 'like', "%{$value}%")
                        ->orWhere('user_name', 'like', "%{$value}%");
                });
            })
            ->when($this->from_date ?? '', function ($query, $value) {
                return $query->where('created_at', '>=', date('Y-m-d H:i:s', strtotime($value)));
            })
            ->when($this->to_date ?? '', function ($query, $value) {
                return $query->where('created_at', '<=', date('Y-m-d H:i:s', strtotime($value)));
            })
            ->when($this->branch_id ?? '', function ($query, $value) {
                return $query->where('branch_id', $value);
            })
            ->when($this->product_id ?? '', function ($query, $value) {
                return $query->where('product_id', $value);
            })
            ->count();

        $filter = [
            'from_date' => $this->from_date,
            'to_date' => $this->to_date,
            'branch_id' => $this->branch_id,
            'product_id' => $this->product_id,
            'search' => $this->search,
        ];

        if ($count > 2000) {
            ExportInventoryLogJob::dispatch(Auth::user(), $filter);
            $this->dispatch('success', ['message' => 'You will get your file in your mailbox.']);
        } else {
            $exportFileName = 'InventoryLog-'.now()->timestamp.'.xlsx';

            return Excel::download(new InventoryLogExport($filter), $exportFileName);
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

    public function updated($key, $value)
    {
        $this->resetPage();
    }

    public function render()
    {
        $data = InventoryLog::with('branch:id,name', 'product:id,name,department_id,main_category_id,sub_category_id', 'product.department:id,name', 'product.subCategory:id,name', 'product.mainCategory:id,name')
            ->orderBy($this->sortField, $this->sortDirection)
            ->when($this->search, function ($query, $value) {
                return $query->where(function ($q) use ($value) {
                    $value = trim($value);
                    $q->where('batch', 'like', "%{$value}%")
                        ->orWhere('barcode', 'like', "%{$value}%")
                        ->orWhere('remarks', 'like', "%{$value}%")
                        ->orWhere('quantity_in', 'like', "%{$value}%")
                        ->orWhere('quantity_out', 'like', "%{$value}%")
                        ->orWhere('balance', 'like', "%{$value}%")
                        ->orWhere('user_name', 'like', "%{$value}%");
                });
            })
            ->when($this->from_date ?? '', function ($query, $value) {
                return $query->where('created_at', '>=', date('Y-m-d H:i:s', strtotime($value)));
            })
            ->when($this->to_date ?? '', function ($query, $value) {
                return $query->where('created_at', '<=', date('Y-m-d H:i:s', strtotime($value)));
            })
            ->when($this->branch_id ?? '', function ($query, $value) {
                return $query->where('branch_id', $value);
            })
            ->when($this->product_id ?? '', function ($query, $value) {
                return $query->where('product_id', $value);
            })
            ->latest();
        $data = $data->paginate($this->limit);

        return view('livewire.log.inventory', ['data' => $data]);
    }
}

<?php

namespace App\Livewire\Report;

use App\Actions\Product\ProductReportAction;
use App\Exports\ProductReportExport;
use App\Models\Product;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class ProductReport extends Component
{
    use WithPagination;

    public $search = '';

    public $barcode = '';

    public $branch_id = '';

    public $from_date;

    public $to_date;

    public $department_id = '';

    public $main_category_id = '';

    public $limit = 10;

    public $sortField = 'products.name';

    public $sortDirection = 'asc';

    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        $this->from_date = date('Y-m-01');
        $this->to_date = date('Y-m-d');
        $this->branch_id = session('branch_id');
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

    public function export()
    {
        $filter = [
            'search' => $this->search,
            'barcode' => $this->barcode,
            'branch_id' => $this->branch_id,
            'from_date' => $this->from_date,
            'to_date' => $this->to_date,
            'main_category_id' => $this->main_category_id,
            'sortField' => $this->sortField,
            'sortDirection' => $this->sortDirection,
        ];
        $count = Product::product()->count();
        if ($count > 2000) {
            // For large exports, you might want to create ExportProductReportJob similar to ExportProductJob
            // For now, we'll use direct export
            $this->dispatch('success', ['message' => 'Export started. This may take a few moments.']);
        }
        $exportFileName = 'Product_Report_'.now()->timestamp.'.xlsx';

        return Excel::download(new ProductReportExport($filter), $exportFileName);
    }

    public function render()
    {
        $filter = [
            'search' => $this->search,
            'barcode' => $this->barcode,
            'branch_id' => $this->branch_id,
            'from_date' => $this->from_date,
            'to_date' => $this->to_date,
            'main_category_id' => $this->main_category_id,
            'sortField' => $this->sortField,
            'sortDirection' => $this->sortDirection,
        ];

        $query = (new ProductReportAction())->execute($filter);
        $products = $query->paginate($this->limit);

        return view('livewire.report.product-report', [
            'products' => $products,
        ]);
    }
}

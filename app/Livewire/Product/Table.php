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
use Illuminate\Support\Facades\Http;

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
        'Product-Refresh-Component' => '$refresh',
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
            $this->dispatch('RefreshProductTable');
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
            $this->selected = Product::latest()->limit(2000)->pluck('id')->toArray();
        } else {
            $this->selected = [];
        }
    }

    public function export()
    {
        $count = Product::count();
        if ($count > 2000) {
            // ExportProductJob::dispatch(auth()->user());
            $this->dispatch('success', ['message' => 'You will get your file in your mailbox.']);
        } else {
            $exportFileName = 'category_' . now()->timestamp . '.xlsx';

            // return Excel::download(new ProductExport, $exportFileName);
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
        $number = '+919633155669';
        $message = 'your pdf pls find it in your message';
        $filePath = public_path('node/sample.pdf');
        $response = Http::post('http://localhost:3002/send-message', [
            'number' => $number,
            'message' => $message,
            'filePath' => $filePath,
        ]);
        // $response = $response->json();
        // dd($response);

        $data = Product::orderBy($this->sortField, $this->sortDirection)
            ->where('name', 'like', '%' . $this->search . '%')
            ->latest()
            ->paginate($this->limit);

        return view('livewire.product.table', [
            'data' => $data,
        ]);
    }
}

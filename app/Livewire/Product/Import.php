<?php

namespace App\Livewire\Product;

use App\Exports\Templates\ProductImportTemplate;
use App\Jobs\Product\ImportProductJob;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

class Import extends Component
{
    use WithFileUploads;

    public $file;

    public $batchId = null;

    public function updatedFile()
    {
        $this->validate([
            'file' => 'required|file|mimes:csv,xlsx|max:10240',
        ]);
    }

    public function sample()
    {
        return Excel::download(new ProductImportTemplate(), 'product_import_template.xlsx');
    }

    public function save()
    {
        $this->validate([
            'file' => 'required|file|mimes:csv,xlsx|max:10240',
        ]);
        $filePath = $this->file->store('imports', 'public');
        ImportProductJob::dispatch(Auth::id(), $filePath);
    }

    public function render()
    {
        return view('livewire.product.import');
    }
}

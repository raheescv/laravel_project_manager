<?php

namespace App\Livewire\Sale;

use App\Exports\Templates\SaleImportTemplate;
use App\Jobs\Sale\ImportSaleJob;
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
        return Excel::download(new SaleImportTemplate(), 'sale_import_template.xlsx');
    }

    public function save()
    {
        $this->validate([
            'file' => 'required|file|mimes:csv,xlsx|max:10240',
        ]);
        $filePath = $this->file->store('imports', 'public');
        ImportSaleJob::dispatch(Auth::id(), $filePath, session('branch_id'));
    }

    public function render()
    {
        return view('livewire.sale.import');
    }
}

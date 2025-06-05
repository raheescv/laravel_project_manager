<?php

namespace App\Livewire\Service;

use App\Exports\Templates\ServiceImportTemplate;
use App\Jobs\Product\ImportServiceJob;
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
        return Excel::download(new ServiceImportTemplate(), 'service_import_template.xlsx');
    }

    public function save()
    {
        $this->validate([
            'file' => 'required|file|mimes:csv,xlsx|max:10240',
        ]);
        $filePath = $this->file->store('imports', 'public');
        ImportServiceJob::dispatch(Auth::id(), $filePath);
    }

    public function render()
    {
        return view('livewire.service.import');
    }
}

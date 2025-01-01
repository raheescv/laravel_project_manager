<?php

namespace App\Livewire\Product;

use App\Jobs\Product\ImportProductJob;
use Livewire\Component;
use Livewire\WithFileUploads;

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

    public function save()
    {
        $this->validate([
            'file' => 'required|file|mimes:csv,xlsx|max:10240',
        ]);
        $filePath = $this->file->store('imports', 'public');
        ImportProductJob::dispatch(auth()->id(), $filePath);
    }

    public function render()
    {
        return view('livewire.product.import');
    }
}

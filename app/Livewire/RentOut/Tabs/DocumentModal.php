<?php

namespace App\Livewire\RentOut\Tabs;

use App\Actions\RentOut\Document\CreateAction;
use App\Models\DocumentType;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class DocumentModal extends Component
{
    use WithFileUploads;

    public ?int $rentOutId = null;

    public $file;

    public $documentTypeId = '';

    public $remarks = '';

    #[On('open-document-modal')]
    public function openModal($rentOutId, $editingId = null)
    {
        $this->rentOutId = $rentOutId;
        $this->file = null;
        $this->documentTypeId = '';
        $this->remarks = '';
        $this->resetValidation();
        $this->dispatch('ClearDocumentModal');
        $this->dispatch('ToggleDocumentModal');
    }

    public function save()
    {
        $this->validate([
            'file' => 'required|file|max:10240',
            'documentTypeId' => 'required|exists:document_types,id',
        ], [
            'file.required' => 'Please choose a file to upload.',
            'file.max' => 'File size must not exceed 10MB.',
            'documentTypeId.required' => 'Document type is required.',
        ]);

        try {
            DB::beginTransaction();

            $originalName = $this->file->getClientOriginalName();
            $path = $this->file->store('rent-out-documents/'.$this->rentOutId, 'public');

            $data = [
                'rent_out_id' => $this->rentOutId,
                'document_type_id' => $this->documentTypeId,
                'name' => $originalName,
                'path' => $path,
                'remarks' => $this->remarks ?? '',
            ];

            $response = (new CreateAction)->execute($data);
            if (! $response['success']) {
                throw new \Exception($response['message']);
            }

            DB::commit();
            $this->dispatch('ToggleDocumentModal');
            $this->dispatch('rent-out-updated');
            $this->dispatch('success', message: $response['message']);
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.rent-out.tabs.document-modal', [
            'documentTypes' => DocumentType::orderBy('name')->get(),
        ]);
    }
}

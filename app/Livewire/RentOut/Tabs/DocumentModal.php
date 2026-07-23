<?php

namespace App\Livewire\RentOut\Tabs;

use App\Actions\RentOut\Document\CreateAction;
use App\Actions\RentOut\Document\UpdateAction;
use App\Models\DocumentType;
use App\Models\RentOutDocument;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class DocumentModal extends Component
{
    use WithFileUploads;

    public ?int $rentOutId = null;

    public ?int $editingId = null;

    public $file;

    public $documentTypeId = '';

    public $remarks = '';

    public ?string $existingFileName = null;

    protected function rules(): array
    {
        return [
            // On edit the file is optional — an existing document stays unless a new file is chosen.
            'file' => ($this->editingId ? 'nullable' : 'required').'|file|max:10240',
            'documentTypeId' => 'required|exists:document_types,id',
        ];
    }

    protected function messages(): array
    {
        return [
            'file.required' => 'Please choose a file to upload.',
            'file.file' => 'The uploaded item must be a valid file.',
            'file.max' => 'File size must not exceed 10MB.',
            'documentTypeId.required' => 'Document type is required.',
            'documentTypeId.exists' => 'The selected document type is invalid.',
        ];
    }

    #[On('open-document-modal')]
    public function openModal($rentOutId, $editingId = null)
    {
        $this->reset(['file', 'documentTypeId', 'remarks', 'editingId', 'existingFileName']);
        $this->rentOutId = $rentOutId;
        $this->editingId = $editingId;

        $documentType = null;
        if ($editingId) {
            $document = RentOutDocument::with('documentType')->find($editingId);
            if ($document) {
                $this->documentTypeId = (string) $document->document_type_id;
                $this->remarks = $document->remarks ?? '';
                $this->existingFileName = $document->name;
                $documentType = $document->documentType;
            }
        }

        $this->resetValidation();
        $this->dispatch('FillDocumentModal',
            id: $documentType?->id,
            name: $documentType?->name,
        );
        $this->dispatch('ToggleDocumentModal');
    }

    public function updated($property)
    {
        $this->validateOnly($property);
    }

    public function save()
    {
        // abort_unless(auth()->user()?->can('rent out document.'.($this->editingId ? 'edit' : 'create')), 403);

        $this->validate();

        try {
            DB::beginTransaction();

            $data = [
                'rent_out_id' => $this->rentOutId,
                'document_type_id' => $this->documentTypeId,
                'remarks' => $this->remarks ?? '',
            ];

            if ($this->file) {
                $data['name'] = $this->file->getClientOriginalName();
                $data['path'] = $this->file->store('rent-out-documents/'.$this->rentOutId, 'public');
            }

            $response = $this->editingId
                ? (new UpdateAction())->execute($this->editingId, $data)
                : (new CreateAction())->execute($data);

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

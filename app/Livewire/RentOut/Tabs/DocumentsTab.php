<?php

namespace App\Livewire\RentOut\Tabs;

use App\Actions\RentOut\Document\DeleteAction;
use App\Models\DocumentType;
use App\Models\RentOut;
use App\Models\RentOutDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;

class DocumentsTab extends Component
{
    public $rentOutId;

    public $filterDocumentTypeId = '';

    public array $selectedDocs = [];

    public bool $selectAll = false;

    public function mount($rentOutId)
    {
        $this->rentOutId = $rentOutId;
    }

    #[On('rent-out-updated')]
    public function refresh()
    {
        $this->selectedDocs = [];
        $this->selectAll = false;
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedDocs = $this->getFilteredDocuments()
                ->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->toArray();
        } else {
            $this->selectedDocs = [];
        }
    }

    public function openDocumentModal()
    {
        $this->dispatch('open-document-modal',
            rentOutId: $this->rentOutId,
            editingId: null,
        );
    }

    public function downloadDocument($id)
    {
        $doc = RentOutDocument::find($id);
        if (! $doc || ! Storage::exists($doc->path)) {
            $this->dispatch('error', message: 'File not found.');

            return;
        }

        return Storage::download($doc->path, $doc->name);
    }

    public function deleteDocument($id)
    {
        try {
            DB::beginTransaction();
            $response = (new DeleteAction)->execute($id);
            if (! $response['success']) {
                throw new \Exception($response['message']);
            }
            DB::commit();
            $this->dispatch('rent-out-updated');
            $this->dispatch('success', message: $response['message']);
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    public function deleteSelected()
    {
        if (empty($this->selectedDocs)) {
            $this->dispatch('error', message: 'Please select at least one document to delete.');

            return;
        }

        try {
            DB::beginTransaction();
            $action = new DeleteAction;
            foreach ($this->selectedDocs as $id) {
                $response = $action->execute($id);
                if (! $response['success']) {
                    throw new \Exception($response['message']);
                }
            }
            DB::commit();
            $this->selectedDocs = [];
            $this->selectAll = false;
            $this->dispatch('rent-out-updated');
            $this->dispatch('success', message: 'Selected documents deleted successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    protected function getFilteredDocuments()
    {
        $query = RentOutDocument::where('rent_out_id', $this->rentOutId)
            ->with('documentType');

        if ($this->filterDocumentTypeId) {
            $query->where('document_type_id', $this->filterDocumentTypeId);
        }

        return $query->latest()->get();
    }

    public function render()
    {
        $documents = $this->getFilteredDocuments();
        $documentTypes = DocumentType::orderBy('name')->get();

        return view('livewire.rent-out.tabs.documents-tab', [
            'documents' => $documents,
            'documentTypes' => $documentTypes,
        ]);
    }
}

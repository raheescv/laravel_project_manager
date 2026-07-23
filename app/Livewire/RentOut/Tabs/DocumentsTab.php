<?php

namespace App\Livewire\RentOut\Tabs;

use App\Actions\RentOut\Document\DeleteAction;
use App\Models\DocumentType;
use App\Models\RentOut;
use App\Models\RentOutDocument;
use Illuminate\Support\Facades\Auth;
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

    public bool $isBooking = false;

    public function mount($rentOutId, $isBooking = false)
    {
        $this->rentOutId = $rentOutId;
        $this->isBooking = (bool) $isBooking;
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

    public function openMandatoryModal()
    {
        // Mandatory documents are configured at the booking stage only.
        if (! $this->isBooking) {
            return;
        }
        $this->dispatch('open-mandatory-document-modal', rentOutId: $this->rentOutId);
    }

    public function editDocument($id)
    {
        $this->dispatch('open-document-modal',
            rentOutId: $this->rentOutId,
            editingId: $id,
        );
    }

    public function downloadDocument($id)
    {
        $doc = RentOutDocument::find($id);
        $path = $doc ? preg_replace('#^public/#', '', $doc->path) : null;
        if (! $doc || ! Storage::disk('public')->exists($path)) {
            $this->dispatch('error', message: 'File not found.');

            return;
        }

        return Storage::disk('public')->download($path, $doc->name);
    }

    public function deleteDocument($id)
    {
        abort_unless(Auth::user()?->can('rent out document.delete'), 403);
        try {
            DB::beginTransaction();
            $response = (new DeleteAction())->execute($id);
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
        abort_unless(Auth::user()?->can('rent out document.delete'), 403);
        if (empty($this->selectedDocs)) {
            $this->dispatch('error', message: 'Please select at least one document to delete.');

            return;
        }

        try {
            DB::beginTransaction();
            $action = new DeleteAction();
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

    /**
     * The mandatory-document checklist for this booking, each flagged as
     * fulfilled (a document of that type has been uploaded) or still pending.
     */
    protected function getMandatoryDocuments()
    {
        $rentOut = RentOut::find($this->rentOutId);
        $typeIds = $rentOut ? $rentOut->mandatoryDocumentTypeIds() : [];

        if (empty($typeIds)) {
            return collect();
        }

        $uploadedTypeIds = RentOutDocument::where('rent_out_id', $this->rentOutId)
            ->pluck('document_type_id')
            ->filter()
            ->unique();

        $types = DocumentType::whereIn('id', $typeIds)->get(['id', 'name'])->keyBy('id');

        return collect($typeIds)
            ->map(fn ($id) => $types->get($id))
            ->filter()
            ->map(fn ($type) => (object) [
                'id' => $type->id,
                'name' => $type->name,
                'done' => $uploadedTypeIds->contains($type->id),
            ])
            ->values();
    }

    public function render()
    {
        $documents = $this->getFilteredDocuments();
        $documentTypes = DocumentType::orderBy('name')->get();
        // The mandatory-document checklist belongs to the booking page only.
        $mandatoryDocuments = $this->isBooking ? $this->getMandatoryDocuments() : collect();

        return view('livewire.rent-out.tabs.documents-tab', [
            'documents' => $documents,
            'documentTypes' => $documentTypes,
            'mandatoryDocuments' => $mandatoryDocuments,
            'mandatoryPendingCount' => $mandatoryDocuments->where('done', false)->count(),
        ]);
    }
}

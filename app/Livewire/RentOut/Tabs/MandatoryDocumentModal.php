<?php

namespace App\Livewire\RentOut\Tabs;

use App\Models\DocumentType;
use App\Models\RentOut;
use Livewire\Attributes\On;
use Livewire\Component;

class MandatoryDocumentModal extends Component
{
    public ?int $rentOutId = null;

    public array $selected = [];

    #[On('open-mandatory-document-modal')]
    public function openModal($rentOutId): void
    {
        $this->rentOutId = (int) $rentOutId;

        $rentOut = RentOut::find($this->rentOutId);
        $this->selected = $rentOut ? $rentOut->mandatoryDocumentTypeIds() : [];

        $this->dispatch('ToggleMandatoryDocumentModal');
    }

    public function selectAllTypes(): void
    {
        $this->selected = DocumentType::orderBy('name')->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    public function clearAll(): void
    {
        $this->selected = [];
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->can('rent out document.edit'), 403);

        $rentOut = RentOut::find($this->rentOutId);
        if (! $rentOut) {
            $this->dispatch('error', message: 'Booking not found.');

            return;
        }

        $value = collect($this->selected)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->implode(',');

        $rentOut->update(['mandatory_documents' => $value]);

        $this->dispatch('ToggleMandatoryDocumentModal');
        $this->dispatch('rent-out-updated');
        $this->dispatch('success', message: 'Mandatory documents updated successfully.');
    }

    public function render()
    {
        return view('livewire.rent-out.tabs.mandatory-document-modal', [
            'documentTypes' => DocumentType::orderBy('name')->get(['id', 'name']),
        ]);
    }
}

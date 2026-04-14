<?php

namespace App\Livewire\RentOut\Tabs;

use App\Models\RentOut;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class NotesTab extends Component
{
    public $rentOutId;

    public $newNote = '';

    public function mount($rentOutId)
    {
        $this->rentOutId = $rentOutId;
    }

    #[On('rent-out-updated')]
    public function refresh() {}

    public function addNote()
    {
        if (trim($this->newNote) === '') {
            return;
        }

        try {
            $rentOut = RentOut::find($this->rentOutId);
            DB::beginTransaction();
            $rentOut->notes()->create([
                'tenant_id' => $rentOut->tenant_id,
                'branch_id' => $rentOut->branch_id,
                'note' => $this->newNote,
                'created_by' => Auth::id(),
            ]);
            DB::commit();
            $this->newNote = '';
            $this->dispatch('success', message: 'Note added successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    public function deleteNote($id)
    {
        try {
            $rentOut = RentOut::find($this->rentOutId);
            DB::beginTransaction();
            $note = $rentOut->notes()->find($id);
            if ($note) {
                $note->delete();
            }
            DB::commit();
            $this->dispatch('success', message: 'Note deleted successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    public function render()
    {
        $rentOut = RentOut::with('notes.creator')->find($this->rentOutId);

        return view('livewire.rent-out.tabs.notes-tab', ['rentOut' => $rentOut]);
    }
}

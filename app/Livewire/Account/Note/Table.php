<?php

namespace App\Livewire\Account\Note;

use App\Models\AccountNote;
use Livewire\Component;
use Livewire\WithPagination;

class Table extends Component
{
    use WithPagination;

    public $account_id;

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'Refresh-AccountNote-Table-Component' => '$refresh',
        'Add-TableId-AccountNote-Table-Component' => 'accountIdAssign',
    ];

    public function mount($account_id = null)
    {
        $this->account_id = $account_id;
    }

    public function accountIdAssign($id)
    {
        $this->account_id = $id;
    }

    public function openNoteModal()
    {
        $this->dispatch('Open-AccountNote-Page-Component', $this->account_id);
    }

    public function delete($id)
    {
        $model = AccountNote::find($id);
        if ($model) {
            $model->delete();
            $this->dispatch('success', ['message' => 'Note deleted successfully']);
        }
    }

    public function render()
    {
        $notes = AccountNote::with(['createdBy'])
            ->when($this->account_id ?? '', function ($query, $value) {
                return $query->where('account_id', $value);
            })
            ->latest()
            ->paginate(10);

        return view('livewire.account.note.table', [
            'notes' => $notes,
            'text_gradient' => true,
        ]);
    }
}

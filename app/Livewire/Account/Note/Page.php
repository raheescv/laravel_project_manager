<?php

namespace App\Livewire\Account\Note;

use App\Actions\Account\Note\CreateAction;
use App\Actions\Account\Note\UpdateAction;
use App\Models\AccountNote;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Page extends Component
{
    public $table_id;

    public $account_id;

    public $notes = [];

    protected $listeners = [
        'Open-AccountNote-Page-Component' => 'create',
        'Edit-AccountNote-Page-Component' => 'edit',
    ];

    public function mount($account_id = null, $table_id = null)
    {
        $this->table_id = $table_id;
        $this->account_id = $account_id;
        if ($this->table_id) {
            $model = AccountNote::find($this->table_id);
            $this->notes = $model->toArray();
            $this->account_id = $model->account_id;
        } else {
            $this->notes = [
                'account_id' => $this->account_id,
                'note' => '',
                'type' => 'general',
                'follow_up_date' => null,
                'status' => 'pending',
            ];
        }
    }

    public function create($account_id = null)
    {
        $this->mount($account_id);
        $this->dispatch('ToggleAccountNoteModal');
    }

    public function edit($id)
    {
        $this->mount($this->account_id, $id);
        $this->dispatch('ToggleAccountNoteModal');
    }

    protected function rules()
    {
        return [
            'notes.note' => ['required'],
            'notes.type' => ['required', 'in:general,payment,complaint,followup,appointment'],
            'notes.follow_up_date' => ['nullable', 'date'],
            'notes.status' => ['required', 'in:pending,completed'],
        ];
    }

    protected $messages = [
        'notes.note.required' => 'The note field is required',
        'notes.type.required' => 'The type field is required',
        'notes.type.in' => 'The selected type is invalid',
        'notes.follow_up_date.date' => 'The follow-up date must be a valid date',
        'notes.status.required' => 'The status field is required',
    ];

    public function save($status = 'pending')
    {
        $this->validate();
        try {
            $this->notes['status'] = $status;
            $userId = Auth::id();
            $this->notes['created_by'] = $userId;
            $this->notes['updated_by'] = $userId;
            if ($this->table_id) {
                $response = (new UpdateAction())->execute($this->notes, $this->table_id);
            } else {
                $response = (new CreateAction())->execute($this->notes);
            }
            if (! $response['success']) {
                $this->dispatch('error', ['message' => $response['message']]);
            }

            $this->dispatch('ToggleAccountNoteModal');
            $this->dispatch('Refresh-AccountNote-Table-Component');
            $this->dispatch('success', ['message' => 'Note saved successfully']);
            $this->mount($this->table_id);
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.account.note.page');
    }
}

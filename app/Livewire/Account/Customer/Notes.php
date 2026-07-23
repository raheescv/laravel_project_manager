<?php

namespace App\Livewire\Account\Customer;

use App\Actions\Account\Note\CreateAction;
use App\Actions\Account\Note\UpdateAction;
use App\Models\AccountNote;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Notes tab of the customer view.
 *
 * Self-contained on purpose: the shared account.note.table component opens its
 * editor by dispatching a browser event that a @push('scripts') handler turns
 * into a jQuery modal toggle. That handler is never registered when the tab is
 * mounted lazily (a push has nowhere to land during a Livewire update), so
 * "New Note" silently did nothing. Here the modal is driven purely by Livewire
 * state, so it works wherever the component is rendered.
 */
class Notes extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $account_id;

    public $note_id;

    public $showModal = false;

    public $filter_type = '';

    public $filter_status = '';

    public $form = [
        'note' => '',
        'type' => 'general',
        'follow_up_date' => null,
        'status' => 'pending',
    ];

    public function mount($account_id = null)
    {
        $this->account_id = $account_id;
    }

    public function updatedFilterType()
    {
        $this->resetPage();
    }

    public function updatedFilterStatus()
    {
        $this->resetPage();
    }

    protected function rules()
    {
        return [
            'form.note' => ['required', 'string', 'max:5000'],
            'form.type' => ['required', 'in:'.implode(',', array_keys(noteTypes()))],
            'form.follow_up_date' => ['nullable', 'date'],
            'form.status' => ['required', 'in:pending,completed'],
        ];
    }

    protected $messages = [
        'form.note.required' => 'The note field is required.',
        'form.type.required' => 'Please choose a note type.',
        'form.type.in' => 'The selected type is invalid.',
        'form.follow_up_date.date' => 'The follow-up date must be a valid date.',
    ];

    protected $validationAttributes = [
        'form.note' => 'note',
        'form.type' => 'type',
        'form.follow_up_date' => 'follow-up date',
        'form.status' => 'status',
    ];

    public function openCreate()
    {
        abort_unless(auth()->user()?->can('account note.create'), 403);
        $this->resetValidation();
        $this->note_id = null;
        $this->form = [
            'note' => '',
            'type' => 'general',
            'follow_up_date' => null,
            'status' => 'pending',
        ];
        $this->showModal = true;
    }

    public function openEdit($id)
    {
        abort_unless(auth()->user()?->can('account note.edit'), 403);
        $note = AccountNote::where('account_id', $this->account_id)->find($id);
        if (! $note) {
            $this->dispatch('error', ['message' => 'Note not found']);

            return;
        }
        $this->resetValidation();
        $this->note_id = $note->id;
        $this->form = [
            'note' => $note->note,
            'type' => $note->type,
            'follow_up_date' => $note->follow_up_date?->format('Y-m-d'),
            'status' => $note->status,
        ];
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetValidation();
    }

    public function save($status = null)
    {
        abort_unless(auth()->user()?->can($this->note_id ? 'account note.edit' : 'account note.create'), 403);

        if ($status !== null) {
            $this->form['status'] = $status;
        }
        $this->validate();

        if (! $this->account_id) {
            $this->dispatch('error', ['message' => 'No customer selected']);

            return;
        }

        $payload = array_merge($this->form, [
            'account_id' => $this->account_id,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        $response = $this->note_id
            ? (new UpdateAction())->execute($payload, $this->note_id)
            : (new CreateAction())->execute($payload);

        if (! $response['success']) {
            $this->dispatch('error', ['message' => $response['message']]);

            return;
        }

        $this->showModal = false;
        $this->dispatch('success', ['message' => 'Note saved successfully']);
        $this->dispatch('RefreshCustomerView');
    }

    public function toggleStatus($id)
    {
        abort_unless(auth()->user()?->can('account note.edit'), 403);
        $note = AccountNote::where('account_id', $this->account_id)->find($id);
        if (! $note) {
            return;
        }
        $note->update([
            'status' => $note->status === 'completed' ? 'pending' : 'completed',
            'updated_by' => Auth::id(),
        ]);
        $this->dispatch('success', ['message' => 'Note status updated']);
    }

    public function delete($id)
    {
        abort_unless(auth()->user()?->can('account note.delete'), 403);
        $note = AccountNote::where('account_id', $this->account_id)->find($id);
        if (! $note) {
            return;
        }
        $note->delete();
        $this->dispatch('success', ['message' => 'Note deleted successfully']);
        $this->dispatch('RefreshCustomerView');
    }

    public function render()
    {
        // whereRaw(false) rather than where('account_id', null): the latter compiles to
        // "account_id is null" and would list notes that belong to no customer.
        $notes = AccountNote::query()
            ->with('createdBy:id,name')
            ->when($this->account_id, fn ($q, $value) => $q->where('account_id', $value), fn ($q) => $q->whereRaw('1 = 0'))
            ->when($this->filter_type, fn ($q, $value) => $q->where('type', $value))
            ->when($this->filter_status, fn ($q, $value) => $q->where('status', $value))
            ->latest()
            ->paginate(10);

        $counts = $this->account_id
            ? AccountNote::where('account_id', $this->account_id)
                ->selectRaw("COUNT(*) AS total, SUM(status = 'pending') AS pending, SUM(status = 'completed') AS completed")
                ->first()
            : null;

        return view('livewire.account.customer.notes', [
            'notes' => $notes,
            'counts' => $counts,
        ]);
    }
}

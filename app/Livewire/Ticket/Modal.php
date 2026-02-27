<?php

namespace App\Livewire\Ticket;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketComment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Spatie\LivewireFilepond\WithFilePond;

class Modal extends Component
{
    use WithFilePond;

    public bool $showModal = false;

    public string $modalMode = 'create';

    public ?int $activeTicketId = null;

    public array $form = [
        'title' => '',
        'description' => '',
        'status' => Ticket::STATUS_OPEN,
    ];

    public array $uploads = [];

    public string $comment = '';

    public ?int $editingCommentId = null;

    public string $editingCommentText = '';

    #[On('ticket-modal-open')]
    public function open(string $mode = 'create', ?int $ticketId = null, ?string $status = null): void
    {
        if (! in_array($mode, ['create', 'view', 'edit'], true)) {
            return;
        }

        if ($mode === 'create') {
            $this->resetModalState();
            $this->modalMode = 'create';
            if ($status && array_key_exists($status, Ticket::statuses())) {
                $this->form['status'] = $status;
            }
            $this->showModal = true;
            $this->dispatch('TicketModalControl', action: 'show');

            return;
        }

        if (! $ticketId || ! $this->loadTicket($ticketId)) {
            $this->dispatch('error', ['message' => 'Ticket not found.']);

            return;
        }

        $this->modalMode = $mode;
        $this->showModal = true;
        $this->dispatch('TicketModalControl', action: 'show');
    }

    public function setModalMode(string $mode): void
    {
        if (! in_array($mode, ['view', 'edit'], true)) {
            return;
        }

        if ($mode === 'edit' && ! auth()->user()?->can('ticket.edit')) {
            return;
        }

        $this->modalMode = $mode;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetModalState();
        $this->dispatch('TicketModalControl', action: 'hide');
        $this->dispatch('ticket-board-refresh');
    }

    public function saveModal(): void
    {
        $this->validate($this->rules());

        if ($this->modalMode === 'create') {
            if (! auth()->user()?->can('ticket.create')) {
                $this->dispatch('error', ['message' => 'You do not have permission to create tickets.']);

                return;
            }

            $ticket = Ticket::create([
                'title' => $this->form['title'],
                'description' => $this->form['description'],
                'status' => $this->form['status'],
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            $this->storeUploads($ticket);
            $this->loadTicket($ticket->id);
            $this->modalMode = 'view';
            $this->dispatch('ticket-board-refresh');
            $this->dispatch('success', ['message' => 'Ticket created successfully.']);

            return;
        }

        if ($this->modalMode === 'edit') {
            if (! auth()->user()?->can('ticket.edit')) {
                $this->dispatch('error', ['message' => 'You do not have permission to edit tickets.']);

                return;
            }

            $ticket = Ticket::find($this->activeTicketId);

            if (! $ticket) {
                return;
            }

            $ticket->update([
                'title' => $this->form['title'],
                'description' => $this->form['description'],
                'status' => $this->form['status'],
                'updated_by' => Auth::id(),
            ]);

            $this->storeUploads($ticket);
            $this->loadTicket($ticket->id);
            $this->modalMode = 'view';
            $this->dispatch('ticket-board-refresh');
            $this->dispatch('success', ['message' => 'Ticket updated successfully.']);
        }
    }

    public function addComment(): void
    {
        if (! auth()->user()?->can('ticket.comment')) {
            $this->dispatch('error', ['message' => 'You do not have permission to add comments.']);

            return;
        }

        if (! $this->activeTicketId) {
            return;
        }

        $this->validate([
            'comment' => ['required', 'string', 'max:3000'],
        ]);

        TicketComment::create([
            'ticket_id' => $this->activeTicketId,
            'comment' => $this->comment,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        $this->comment = '';
        $this->dispatch('ticket-board-refresh');
        $this->dispatch('success', ['message' => 'Comment added successfully.']);
    }

    public function startEditComment(int $commentId): void
    {
        if (! auth()->user()?->can('ticket.comment')) {
            return;
        }

        if (! $this->activeTicketId) {
            return;
        }

        $comment = TicketComment::where('ticket_id', $this->activeTicketId)->find($commentId);

        if (! $comment) {
            return;
        }

        $this->editingCommentId = $comment->id;
        $this->editingCommentText = $comment->comment;
    }

    public function cancelEditComment(): void
    {
        $this->editingCommentId = null;
        $this->editingCommentText = '';
    }

    public function saveEditedComment(): void
    {
        if (! auth()->user()?->can('ticket.comment')) {
            return;
        }

        if (! $this->activeTicketId || ! $this->editingCommentId) {
            return;
        }

        $this->validate([
            'editingCommentText' => ['required', 'string', 'max:3000'],
        ]);

        $comment = TicketComment::where('ticket_id', $this->activeTicketId)->find($this->editingCommentId);

        if (! $comment) {
            return;
        }

        $comment->update([
            'comment' => $this->editingCommentText,
            'updated_by' => Auth::id(),
        ]);

        $this->cancelEditComment();
        $this->dispatch('ticket-board-refresh');
        $this->dispatch('success', ['message' => 'Comment updated successfully.']);
    }

    public function deleteComment(int $commentId): void
    {
        if (! auth()->user()?->can('ticket.comment')) {
            return;
        }

        if (! $this->activeTicketId) {
            return;
        }

        $comment = TicketComment::where('ticket_id', $this->activeTicketId)->find($commentId);

        if (! $comment) {
            return;
        }

        $comment->delete();

        if ($this->editingCommentId === $commentId) {
            $this->cancelEditComment();
        }

        $this->dispatch('ticket-board-refresh');
        $this->dispatch('success', ['message' => 'Comment deleted successfully.']);
    }

    public function removeAttachment(int $attachmentId): void
    {
        if (! auth()->user()?->can('ticket.edit')) {
            $this->dispatch('error', ['message' => 'You do not have permission to remove attachments.']);

            return;
        }

        if (! $this->activeTicketId) {
            return;
        }

        $attachment = TicketAttachment::find($attachmentId);

        if (! $attachment || (int) $attachment->ticket_id !== (int) $this->activeTicketId) {
            return;
        }

        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();

        $this->dispatch('ticket-board-refresh');
        $this->dispatch('success', ['message' => 'Attachment removed successfully.']);
    }

    protected function rules(): array
    {
        return [
            'form.title' => ['required', 'string', 'max:255'],
            'form.description' => ['required', 'string'],
            'form.status' => ['required', Rule::in(array_keys(Ticket::statuses()))],
            'uploads.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif,webp,mp4,mov,avi,wmv,mkv,pdf,doc,docx,xls,xlsx', 'max:51200'],
        ];
    }

    protected function resetModalState(): void
    {
        $this->activeTicketId = null;
        $this->form = [
            'title' => '',
            'description' => '',
            'status' => Ticket::STATUS_OPEN,
        ];
        $this->uploads = [];
        $this->comment = '';
        $this->editingCommentId = null;
        $this->editingCommentText = '';
        $this->dispatch('filepond-reset-uploads');
    }

    protected function loadTicket(int $ticketId): bool
    {
        $ticket = Ticket::find($ticketId);

        if (! $ticket) {
            return false;
        }

        $this->activeTicketId = $ticket->id;
        $this->form = [
            'title' => $ticket->title,
            'description' => $ticket->description,
            'status' => $ticket->status,
        ];
        $this->editingCommentId = null;
        $this->editingCommentText = '';

        return true;
    }

    protected function storeUploads(Ticket $ticket): void
    {
        if (empty($this->uploads)) {
            return;
        }

        foreach ($this->uploads as $file) {
            $path = $file->store('tickets/'.$ticket->id, 'public');

            TicketAttachment::create([
                'ticket_id' => $ticket->id,
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'file_size' => (int) $file->getSize(),
            ]);
        }

        $this->uploads = [];
        $this->dispatch('filepond-reset-uploads');
    }

    public function render()
    {
        $activeTicket = null;
        if ($this->activeTicketId) {
            $activeTicket = Ticket::with([
                'attachments',
                'comments' => fn ($q) => $q->with('creator:id,name')->latest(),
                'creator:id,name',
            ])->find($this->activeTicketId);
        }

        return view('livewire.ticket.modal', [
            'activeTicket' => $activeTicket,
            'statuses' => Ticket::statuses(),
        ]);
    }
}

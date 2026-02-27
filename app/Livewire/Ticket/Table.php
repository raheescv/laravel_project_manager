<?php

namespace App\Livewire\Ticket;

use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class Table extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public string $from_date = '';

    public string $to_date = '';

    public int $limit = 10;

    public string $viewMode = 'board';

    protected $paginationTheme = 'bootstrap';

    public function mount(): void
    {
        $this->from_date = date('Y-m-01');
        $this->to_date = date('Y-m-d');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedFromDate(): void
    {
        $this->resetPage();
    }

    public function updatedToDate(): void
    {
        $this->resetPage();
    }

    #[On('ticket-board-refresh')]
    public function refreshBoard(): void
    {
        $this->resetPage();
    }

    public function setViewMode(string $mode): void
    {
        if ($mode !== 'board') {
            return;
        }

        $this->viewMode = $mode;
    }

    public function openCreateModal(): void
    {
        if (! auth()->user()?->can('ticket.create')) {
            $this->dispatch('error', ['message' => 'You do not have permission to create tickets.']);

            return;
        }

        $this->dispatch('ticket-modal-open', mode: 'create', ticketId: null, status: Ticket::STATUS_OPEN);
    }

    public function openCreateModalWithStatus(string $status): void
    {
        if (! auth()->user()?->can('ticket.create')) {
            $this->dispatch('error', ['message' => 'You do not have permission to create tickets.']);

            return;
        }

        $nextStatus = array_key_exists($status, Ticket::statuses()) ? $status : Ticket::STATUS_OPEN;
        $this->dispatch('ticket-modal-open', mode: 'create', ticketId: null, status: $nextStatus);
    }

    public function openViewModal(int $ticketId): void
    {
        if (! auth()->user()?->can('ticket.view')) {
            $this->dispatch('error', ['message' => 'You do not have permission to view tickets.']);

            return;
        }

        $this->dispatch('ticket-modal-open', mode: 'view', ticketId: $ticketId, status: null);
    }

    public function openEditModal(int $ticketId): void
    {
        if (! auth()->user()?->can('ticket.edit')) {
            $this->dispatch('error', ['message' => 'You do not have permission to edit tickets.']);

            return;
        }

        $this->dispatch('ticket-modal-open', mode: 'edit', ticketId: $ticketId, status: null);
    }

    public function moveStatus(int $ticketId, string $status): void
    {
        if (! auth()->user()?->can('ticket.edit')) {
            $this->dispatch('error', ['message' => 'You do not have permission to change status.']);

            return;
        }

        if (! array_key_exists($status, Ticket::statuses())) {
            return;
        }

        $ticket = Ticket::find($ticketId);

        if (! $ticket) {
            return;
        }

        $ticket->update([
            'status' => $status,
            'updated_by' => Auth::id(),
        ]);

        $this->dispatch('success', ['message' => 'Ticket status updated successfully.']);
    }

    public function delete(int $id): void
    {
        if (! auth()->user()?->can('ticket.delete')) {
            $this->dispatch('error', ['message' => 'You do not have permission to delete tickets.']);

            return;
        }

        $ticket = Ticket::with('attachments')->find($id);

        if (! $ticket) {
            return;
        }

        foreach ($ticket->attachments as $attachment) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        $ticket->delete();

        $this->dispatch('success', ['message' => 'Ticket deleted successfully.']);
    }

    protected function baseQuery()
    {
        return Ticket::query()
            ->withCount(['attachments', 'comments'])
            ->with([
                'creator:id,name',
                'attachments:id,ticket_id,file_path,mime_type',
            ])
            ->filter([
                'search' => $this->search,
                'status' => $this->status,
                'from_date' => $this->from_date,
                'to_date' => $this->to_date,
            ])
            ->orderByDesc('id');
    }

    public function render()
    {
        $boardData = (clone $this->baseQuery())
            ->limit(300)
            ->get()
            ->groupBy('status');

        return view('livewire.ticket.table', [
            'boardData' => $boardData,
            'statuses' => Ticket::statuses(),
        ]);
    }
}

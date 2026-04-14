<div>
    <div class="row g-2 align-items-center mb-3">
        <div class="col">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white border-secondary-subtle">
                    <i class="fa fa-pencil"></i>
                </span>
                <input type="text" class="form-control form-control-sm border-secondary-subtle shadow-sm"
                    wire:model="newNote" placeholder="Add a note..." wire:keydown.enter="addNote">
            </div>
        </div>
        <div class="col-auto">
            <button type="button" class="btn btn-outline-primary d-inline-flex align-items-center"
                style="font-size: .7rem; padding: .2rem .5rem; border-radius: 4px;"
                wire:click="addNote">
                <i class="fa fa-plus me-1"></i> Add Note
            </button>
        </div>
    </div>
    @forelse($rentOut->notes as $note)
        <div class="card mb-2 border shadow-sm">
            <div class="card-body py-2 px-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="mb-0 small">{{ $note->note }}</p>
                        @if ($note->creator)
                            <small class="text-muted"><i class="fa fa-user me-1"></i>{{ $note->creator->name }}</small>
                        @endif
                    </div>
                    <div class="d-flex align-items-center gap-2 ms-3">
                        <small class="text-muted text-nowrap"><i
                                class="fa fa-clock-o me-1"></i>{{ $note->created_at?->format('d-m-Y H:i') }}</small>
                        <button type="button" class="btn btn-danger btn-sm text-white"
                            wire:click="deleteNote({{ $note->id }})" wire:confirm="Delete this note?" title="Delete"
                            data-bs-toggle="tooltip">
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="text-center text-muted py-4">No notes found</div>
    @endforelse
</div>

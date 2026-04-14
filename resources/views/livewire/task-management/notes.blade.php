<div>
    @if ($showModal && $task)
        <div class="modal-header">
            <div>
                <h5 class="modal-title mb-1">{{ $task->name }}</h5>
                <div class="d-flex gap-2 small">
                    @if ($task->type)
                        <span class="badge bg-danger bg-opacity-10 text-danger">{{ $task->type }}</span>
                    @endif
                    @if ($task->project)
                        <span class="badge bg-info bg-opacity-10 text-info">{{ $task->project }}</span>
                    @endif
                    <span class="badge bg-{{ $task->status === 'Completed' ? 'success' : ($task->status === 'In Review' ? 'info' : ($task->status === 'In Progress' ? 'warning' : 'secondary')) }} bg-opacity-10 text-{{ $task->status === 'Completed' ? 'success' : ($task->status === 'In Review' ? 'info' : ($task->status === 'In Progress' ? 'warning' : 'secondary')) }}">
                        {{ $task->status }}
                    </span>
                </div>
            </div>
            <button type="button" class="btn-close" wire:click="closeModal"></button>
        </div>

        <div class="modal-body p-0">
            <div class="row g-0">
                {{-- Task Details --}}
                <div class="col-lg-5 p-4 border-end">
                    <div class="mb-3">
                        <h6 class="text-primary fw-bold mb-2">Description</h6>
                        <p class="text-muted" style="white-space: pre-line;">{{ $task->description ?: 'No description.' }}</p>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <h6 class="text-muted small fw-semibold">Assigned To</h6>
                            <p class="mb-0"><i class="fa fa-user me-1"></i>{{ $task->assignee?->name ?? '-' }}</p>
                        </div>
                        <div class="col-6">
                            <h6 class="text-muted small fw-semibold">Priority</h6>
                            <span class="badge bg-{{ $task->priority === 'Critical' ? 'danger' : ($task->priority === 'High' ? 'warning' : 'secondary') }}">
                                {{ $task->priority }}
                            </span>
                        </div>
                        <div class="col-6">
                            <h6 class="text-muted small fw-semibold">Created</h6>
                            <p class="mb-0">{{ $task->created_at->diffForHumans() }}</p>
                        </div>
                        <div class="col-6">
                            <h6 class="text-muted small fw-semibold">Due Date</h6>
                            <p class="mb-0">{{ $task->end_date ? systemDate($task->end_date) : '-' }}</p>
                        </div>
                        <div class="col-12">
                            <h6 class="text-muted small fw-semibold">Created By</h6>
                            <p class="mb-0"><i class="fa fa-user me-1"></i>{{ $task->creator?->name ?? '-' }}</p>
                        </div>
                    </div>
                </div>

                {{-- Notes / Comments --}}
                <div class="col-lg-7 p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0 text-primary fw-bold">Notes</h6>
                        <span class="text-muted small">#{{ $task->id }}</span>
                    </div>

                    @can('task-management.add-note')
                        <div class="mb-3">
                            <textarea class="form-control" rows="2" wire:model="remarks" placeholder="Write a note..." wire:keydown.enter="addNote"></textarea>
                            <div class="mt-2 text-end">
                                <button type="button" class="btn btn-sm btn-primary" wire:click="addNote">Add Note</button>
                            </div>
                        </div>
                    @endcan

                    <div style="max-height: 400px; overflow: auto;">
                        @forelse ($task->notes as $note)
                            <div class="border rounded-3 p-3 mb-2 {{ $note->deleted_at ? 'bg-light' : '' }}">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <strong class="small">{{ $note->creator?->name ?? 'User' }}</strong>
                                    <div class="d-flex align-items-center gap-2">
                                        <small class="text-muted">{{ $note->created_at->diffForHumans() }}</small>
                                        <span class="small">
                                            <i class="fa fa-check text-muted"></i>
                                            @if ($note->seen_flag)
                                                <i class="fa fa-check text-primary"></i>
                                            @endif
                                        </span>
                                        @if (!$note->deleted_at && $note->created_by === auth()->id())
                                            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1"
                                                wire:click="deleteNote({{ $note->id }})" wire:confirm="Delete this note?">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                                <div style="white-space: pre-line;" class="{{ $note->deleted_at ? 'text-decoration-line-through text-muted fst-italic' : '' }}">
                                    {{ $note->remarks }}
                                </div>
                            </div>
                        @empty
                            <div class="text-muted text-center py-3">No notes yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" wire:click="closeModal">Close</button>
        </div>
    @endif
</div>

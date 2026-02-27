<div>
    @if ($showModal)
        @php
            $cover = null;
            if ($activeTicket) {
                $cover = $activeTicket->attachments->first(fn($a) => $a->isImage() || $a->isVideo());
            }
        @endphp

        <div class="modal-header">
            <div class="d-flex align-items-center gap-2">
                <select class="form-select form-select-sm" style="width: 170px;" wire:model="form.status" @if ($modalMode === 'view') disabled @endif>
                    @foreach ($statuses as $statusKey => $statusLabel)
                        <option value="{{ $statusKey }}">{{ $statusLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div class="d-flex gap-2">
                @if ($modalMode === 'view' && auth()->user()->can('ticket.edit') && $activeTicketId)
                    <button type="button" class="btn btn-sm btn-outline-primary" wire:click="setModalMode('edit')">Edit</button>
                @endif
                <button type="button" class="btn-close" wire:click="closeModal"></button>
            </div>
        </div>

        @if ($cover)
            @php $coverUrl = '/storage/' . ltrim($cover->file_path, '/'); @endphp
            <div class="border-bottom" style="height: 240px; background: var(--bs-light);">
                @if ($cover->isImage())
                    <img src="{{ $coverUrl }}" alt="cover" style="width:100%;height:100%;object-fit:cover;">
                @else
                    <video controls style="width:100%;height:100%;object-fit:cover;">
                        <source src="{{ $coverUrl }}" type="{{ $cover->mime_type }}">
                    </video>
                @endif
            </div>
        @endif

        <div class="modal-body p-0">
            <div class="row g-0">
                <div class="col-lg-7 p-4 border-end">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-primary">Title</label>
                        <input type="text" class="form-control form-control-lg" wire:model="form.title" @if ($modalMode === 'view') disabled @endif>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-primary">Description</label>
                        <textarea class="form-control" rows="4" wire:model="form.description" @if ($modalMode === 'view') disabled @endif
                            placeholder="Add a more detailed description..."></textarea>
                    </div>

                    @if ($modalMode !== 'view')
                        <div class="mb-3" id="ticket-upload">
                            <label class="form-label fw-semibold text-primary">Attachments</label>
                            <x-filepond::upload wire:model="uploads" multiple max-files="10" class="border border-dashed rounded-3" />
                        </div>
                    @endif

                    @if ($activeTicket)
                        <div class="small text-muted fw-semibold mb-2">Files</div>
                        <div class="d-grid gap-2">
                            @forelse ($activeTicket->attachments as $attachment)
                                @php $attachmentUrl = '/storage/' . ltrim($attachment->file_path, '/'); @endphp
                                <div class="ticket-attachment-row">
                                    <div class="d-flex align-items-center justify-content-between gap-2">
                                        <div class="d-flex align-items-center gap-3 flex-grow-1 min-w-0">
                                            @if ($attachment->isImage())
                                                <img src="{{ $attachmentUrl }}" class="attachment-thumb" alt="file">
                                            @elseif ($attachment->isVideo())
                                                <video controls class="attachment-thumb">
                                                    <source src="{{ $attachmentUrl }}" type="{{ $attachment->mime_type }}">
                                                </video>
                                            @else
                                                <div class="attachment-thumb d-flex align-items-center justify-content-center"><i class="fa fa-file text-secondary"></i></div>
                                            @endif
                                            <div class="min-w-0">
                                                <div class="fw-semibold text-truncate" title="{{ $attachment->file_name }}">{{ $attachment->file_name }}</div>
                                                <small class="text-muted">Added {{ systemDate($attachment->created_at) }}</small>
                                            </div>
                                        </div>
                                        <div class="d-flex gap-1">
                                            <a href="{{ $attachmentUrl }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="fa fa-external-link"></i></a>
                                            @if ($modalMode !== 'view' && auth()->user()->can('ticket.edit'))
                                                <button type="button" class="btn btn-sm btn-outline-danger" wire:click="removeAttachment({{ $attachment->id }})">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-muted small">No files attached.</div>
                            @endforelse
                        </div>
                    @endif
                </div>

                <div class="col-lg-5 p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0 text-primary fw-bold">Comments and activity</h6>
                        @if ($activeTicket)
                            <span class="text-muted small">#{{ $activeTicket->id }}</span>
                        @endif
                    </div>

                    @if ($activeTicket && auth()->user()->can('ticket.comment'))
                        <div class="mb-3">
                            <textarea class="form-control" rows="2" wire:model="comment" placeholder="Write a comment..."></textarea>
                            <div class="mt-2 text-end">
                                <button type="button" class="btn btn-sm btn-primary" wire:click="addComment">Add Comment</button>
                            </div>
                        </div>
                    @endif

                    <div style="max-height: 450px; overflow:auto;">
                        @if ($activeTicket)
                            @forelse ($activeTicket->comments as $item)
                                <div class="comment-box p-3 mb-2">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <strong>{{ $item->creator?->name ?? 'User' }}</strong>
                                        <small class="text-muted">{{ systemDate($item->created_at) }}</small>
                                    </div>
                                    @if ($editingCommentId === $item->id)
                                        <div class="mb-2">
                                            <textarea class="form-control" rows="3" wire:model="editingCommentText"></textarea>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-sm btn-primary" wire:click="saveEditedComment">Save</button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="cancelEditComment">Cancel</button>
                                        </div>
                                    @else
                                        <div style="white-space: pre-line;">{{ $item->comment }}</div>
                                        @can('ticket.comment')
                                            <div class="small text-muted mt-2 d-flex align-items-center gap-1">
                                                <i class="fa fa-commenting-o"></i>
                                                <span>&bull;</span>
                                                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-underline align-baseline" wire:click="startEditComment({{ $item->id }})">
                                                    Edit
                                                </button>
                                                <span>&bull;</span>
                                                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-underline text-danger align-baseline" wire:click="deleteComment({{ $item->id }})"
                                                    wire:confirm="Delete this comment?">
                                                    Delete
                                                </button>
                                            </div>
                                        @endcan
                                    @endif
                                </div>
                            @empty
                                <div class="text-muted">No comments yet.</div>
                            @endforelse
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" wire:click="closeModal">Close</button>
            @if (($modalMode === 'create' && auth()->user()->can('ticket.create')) || ($modalMode === 'edit' && auth()->user()->can('ticket.edit')))
                <button type="button" class="btn btn-primary" wire:click="saveModal">Save</button>
            @endif
        </div>
    @endif

    @once
        @push('styles')
            <link rel="stylesheet" href="{{ https_asset('vendor/livewire-filepond/filepond.css') }}">
        @endpush
        @push('scripts')
            <script src="{{ https_asset('vendor/livewire-filepond/filepond.js') }}"></script>
        @endpush
    @endonce
</div>

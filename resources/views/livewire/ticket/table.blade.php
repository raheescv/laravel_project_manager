<div class="ticket-board-wrapper" x-data="{ draggedId: null }">
    <style>
        .ticket-board-wrapper {
            background:
                radial-gradient(1200px 400px at 10% 0%, rgba(var(--bs-primary-rgb), 0.14), transparent 60%),
                radial-gradient(900px 500px at 90% 10%, rgba(var(--bs-info-rgb), 0.1), transparent 55%),
                var(--bs-light);
            border: 1px solid var(--bs-border-color);
            border-radius: 16px;
            overflow: hidden;
        }

        .ticket-board-top {
            background: rgba(var(--bs-primary-rgb), 0.06);
            border-bottom: 1px solid var(--bs-border-color);
        }

        .ticket-column {
            background: #fff;
            border: 1px solid var(--bs-border-color);
            border-radius: 16px;
            min-height: 420px;
        }

        .ticket-column-title {
            letter-spacing: .02em;
            font-size: 1.06rem;
        }

        .ticket-card {
            background: #fff;
            border: 1px solid var(--bs-border-color);
            border-radius: 14px;
            color: var(--bs-body-color);
            transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
        }

        .ticket-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 22px rgba(var(--bs-primary-rgb), .12);
            border-color: rgba(var(--bs-primary-rgb), 0.5);
        }

        .ticket-card-preview {
            height: 120px;
            border-top-left-radius: 14px;
            border-top-right-radius: 14px;
            object-fit: cover;
            width: 100%;
            background: var(--bs-light);
        }

        .ticket-modal .modal-content {
            border-radius: 18px;
            border: 1px solid var(--bs-border-color);
            background: #fff;
            color: var(--bs-body-color);
        }

        .ticket-modal .modal-header,
        .ticket-modal .modal-footer {
            border-color: var(--bs-border-color);
            background: rgba(var(--bs-primary-rgb), 0.04);
        }

        .ticket-modal .form-control,
        .ticket-modal .form-select,
        .ticket-modal textarea {
            background: #fff;
            color: var(--bs-body-color);
            border-color: var(--bs-border-color);
        }

        .ticket-modal .comment-box {
            background: var(--bs-light);
            border: 1px solid var(--bs-border-color);
            border-radius: 12px;
        }

        .ticket-modal .attachment-thumb {
            width: 96px;
            height: 66px;
            border-radius: 10px;
            object-fit: cover;
            background: var(--bs-light);
        }

        .ticket-attachment-row {
            background: #fff;
            border: 1px solid var(--bs-border-color);
            border-radius: 12px;
            padding: .6rem;
            transition: box-shadow .2s ease, border-color .2s ease;
        }

        .ticket-attachment-row:hover {
            box-shadow: 0 8px 20px rgba(var(--bs-primary-rgb), .08);
            border-color: rgba(var(--bs-primary-rgb), 0.35);
        }

        .ticket-subtitle {
            font-weight: 700;
            color: var(--bs-primary);
            display: flex;
            align-items: center;
            gap: .45rem;
            margin-bottom: .65rem;
        }

        .ticket-soft-panel {
            background: rgba(var(--bs-primary-rgb), .03);
            border: 1px solid rgba(var(--bs-primary-rgb), .12);
            border-radius: 12px;
            padding: .8rem;
        }
    </style>

    <div class="ticket-board-top p-3 p-md-4">
        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <div class="d-flex gap-2 align-items-center">
                <h5 class="mb-0 text-primary">Tickets Board</h5>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Live Board</span>
            </div>
            <div class="d-flex gap-2">
                @can('ticket.create')
                    <button type="button" class="btn btn-sm btn-primary" wire:click="openCreateModal">
                        <i class="fa fa-plus me-1"></i> Create
                    </button>
                @endcan
            </div>
        </div>

        <div class="row g-2 mt-2">
            <div class="col-md-5">
                <input type="text" wire:model.live.debounce.300ms="search" class="form-control form-control-sm" placeholder="Search by title or description">
            </div>
            <div class="col-md-2">
                <select wire:model.live="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    @foreach ($statuses as $statusKey => $statusLabel)
                        <option value="{{ $statusKey }}">{{ $statusLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" wire:model.live="from_date" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <input type="date" wire:model.live="to_date" class="form-control form-control-sm">
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-sm btn-outline-secondary w-100"
                    wire:click="$set('status', ''); $set('search', ''); $set('from_date', '{{ date('Y-m-01') }}'); $set('to_date', '{{ date('Y-m-d') }}');">
                    Reset
                </button>
            </div>
        </div>
    </div>

    <div class="p-3 p-md-4">
        <div class="row g-3">
            @foreach ($statuses as $statusKey => $statusLabel)
                <div class="col-md-6 col-xl-3">
                    <div class="ticket-column d-flex flex-column h-100">
                        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                            <strong class="text-primary ticket-column-title">{{ $statusLabel }}</strong>
                            <span class="badge bg-secondary">{{ count($boardData->get($statusKey, [])) }}</span>
                        </div>
                        <div class="p-2 flex-grow-1" style="min-height: 320px;" @dragover.prevent
                            @drop.prevent="if (draggedId) { $wire.moveStatus(draggedId, '{{ $statusKey }}'); draggedId = null; }">
                            @forelse ($boardData->get($statusKey, collect()) as $ticket)
                                @php
                                    $preview = $ticket->attachments->first(fn($a) => $a->isImage())
                                        ?? $ticket->attachments->first(fn($a) => $a->isVideo());
                                @endphp
                                <div class="ticket-card mb-2" @can('ticket.edit') draggable="true" @dragstart="draggedId = {{ $ticket->id }}" @endcan>
                                    <button type="button" class="btn p-0 text-start w-100" wire:click="openViewModal({{ $ticket->id }})">
                                        @if ($preview)
                                            @php $previewUrl = '/storage/' . ltrim($preview->file_path, '/'); @endphp
                                            @if (str_starts_with((string) $preview->mime_type, 'image/'))
                                                <img src="{{ $previewUrl }}" class="ticket-card-preview" alt="preview">
                                            @else
                                                <video class="ticket-card-preview" muted>
                                                    <source src="{{ $previewUrl }}" type="{{ $preview->mime_type }}">
                                                </video>
                                            @endif
                                        @endif
                                        <div class="p-3">
                                            <div class="fw-semibold mb-1">{{ $ticket->title }}</div>
                                            <div class="small text-muted mb-2">{{ \Illuminate\Support\Str::limit($ticket->description, 80) }}</div>
                                            <div class="d-flex gap-3 small text-muted">
                                                <span><i class="fa fa-comments-o me-1"></i>{{ $ticket->comments_count }}</span>
                                                <span><i class="fa fa-paperclip me-1"></i>{{ $ticket->attachments_count }}</span>
                                            </div>
                                        </div>
                                    </button>
                                </div>
                            @empty
                                <div class="text-center text-muted py-3 small">No tickets</div>
                            @endforelse
                        </div>
                        @can('ticket.create')
                            <div class="p-2 border-top">
                                <button type="button" class="btn btn-sm btn-outline-primary w-100" wire:click="openCreateModalWithStatus('{{ $statusKey }}')">
                                    <i class="fa fa-plus me-1"></i> Add a card
                                </button>
                            </div>
                        @endcan
                    </div>
                </div>
            @endforeach
        </div>
    </div>

</div>

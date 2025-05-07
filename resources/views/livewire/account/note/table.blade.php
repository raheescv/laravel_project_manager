<div class="notes-container">
    <!-- Main Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="text-gradient mb-1">Account Notes</h4>
            <p class="text-muted mb-0">Manage and track all account related notes</p>
        </div>
        @if ($account_id)
            <button wire:click="openNoteModal()" class="btn btn-primary btn-sm rounded-3 btn-hover-elevate">
                <i class="fa fa-plus-circle me-2"></i>
                New Note
            </button>
        @endif
    </div>

    <style>
        /* Container Styles */
        .notes-container {
            padding: 1rem;
        }

        /* Note Card Styles */
        .note-card {
            background: #fff;
            border-radius: 0.75rem;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            transition: transform 0.2s ease;
            position: relative;
            overflow: hidden;
            margin-bottom: 1rem;
        }

        .note-card:hover {
            transform: translateY(-3px);
        }

        /* Note Text - Make this prominent */
        .note-text {
            padding: 1.25rem;
            font-size: 1rem;
            line-height: 1.5;
            color: #2d3436;
            background: #fff;
            border-left: 4px solid #4facfe;
            margin: 0.5rem 0;
        }

        /* Compact Card Header */
        .note-card-header {
            padding: 0.5rem 0.75rem;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        /* Compact Type Badge */
        .note-type {
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .type-general {
            background: #e3f2fd;
            color: #1976d2;
        }

        .type-payment {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .type-complaint {
            background: #fbe9e7;
            color: #d84315;
        }

        .type-other {
            background: #fff3e0;
            color: #ef6c00;
        }

        /* Meta Info - Small and subtle */
        .note-meta {
            padding: 0.5rem 0.75rem;
            background: #f8f9fa;
            font-size: 0.7rem;
            color: #6c757d;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        /* Follow-up date styling */
        .follow-up-date {
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
            background: #fff3e0;
            color: #ef6c00;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            margin-right: auto;
        }

        .follow-up-date i {
            font-size: 0.8rem;
            color: #f57c00;
        }

        /* Status Indicator - Minimal */
        .status-badge {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 0.5rem;
        }

        .status-pending {
            background: #ffc107;
        }

        .status-completed {
            background: #28a745;
        }

        /* Action Buttons - Minimal */
        .note-actions {
            padding: 0.5rem;
            display: flex;
            gap: 0.5rem;
            justify-content: flex-end;
            border-top: 1px solid #f0f0f0;
        }

        .btn-action {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            color: #fff;
        }

        .btn-action.edit {
            background: #1976d2;
        }

        .btn-action.delete {
            background: #dc3545;
        }

        /* Empty State - Compact */
        .empty-state {
            padding: 2rem;
            text-align: center;
            color: #6c757d;
        }

        .empty-state-icon {
            font-size: 2rem;
            color: #4facfe;
            margin-bottom: 1rem;
        }
    </style>

    <!-- Notes Grid -->
    <div class="row g-3">
        @forelse($notes as $note)
            <div class="col-md-6 col-lg-6">
                <div class="note-card">
                    <div class="note-card-header">
                        <div
                            class="note-type {{ $note->type == 'general' ? 'type-general' : ($note->type == 'payment' ? 'type-payment' : ($note->type == 'complaint' ? 'type-complaint' : 'type-other')) }}">
                            <i
                                class="far {{ $note->type == 'general' ? 'fa-comment' : ($note->type == 'payment' ? 'fa-credit-card' : ($note->type == 'complaint' ? 'fa-exclamation-circle' : 'fa-sticky-note')) }}"></i>
                            {{ ucfirst($note->type) }}
                        </div>
                        <span class="status-badge {{ $note->status == 'pending' ? 'status-pending' : 'status-completed' }}"></span>
                    </div>

                    <div class="note-text">
                        {{ $note->note }}
                    </div>

                    <div class="note-meta">
                        <div class="meta-item">
                            <i class="far fa-calendar"></i>
                            {{ systemDateTime($note->created_at) }}
                        </div>
                        <div class="meta-item">
                            <i class="far fa-user"></i>
                            {{ $note->createdBy?->name }}
                        </div>
                    </div>

                    <div class="note-actions">
                        @if ($note->follow_up_date)
                            <div class="follow-up-date">
                                <i class="far fa-clock"></i>
                                Follow-up: {{ systemDate($note->follow_up_date) }}
                            </div>
                        @endif
                        <button class="btn-action edit" wire:click="$dispatch('Edit-AccountNote-Page-Component', {id: '{{ $note->id }}'})">
                            <i class="far fa-edit"></i>
                            Edit
                        </button>
                        <button class="btn-action delete" wire:confirm="Are you sure you want to delete this note?" wire:click="delete({{ $note->id }})">
                            <i class="far fa-trash-alt"></i>
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="far fa-sticky-note"></i>
                    </div>
                    <p>No notes found. Start by adding your first note.</p>
                </div>
            </div>
        @endforelse
    </div>

    @if ($notes->hasPages())
        <div class="mt-4 d-flex justify-content-center">
            {{ $notes->links() }}
        </div>
    @endif
    <x-account.note-modal />
</div>

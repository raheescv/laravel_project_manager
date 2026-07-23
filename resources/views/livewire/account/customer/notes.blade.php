@php
    $typeMeta = [
        'general' => ['icon' => 'fa-pencil-square-o', 'tag' => 'mute'],
        'appointment' => ['icon' => 'fa-calendar', 'tag' => 'info'],
        'payment' => ['icon' => 'fa-money', 'tag' => 'ok'],
        'complaint' => ['icon' => 'fa-exclamation-triangle', 'tag' => 'bad'],
        'followup' => ['icon' => 'fa-bell-o', 'tag' => 'warn'],
    ];
@endphp

<div>
    <div class="panel">
        <div class="phead p-warn">
            <span class="ic"><i class="fa fa-pencil-square-o"></i></span>
            <div>
                <h4>Notes</h4>
                <span class="hint">Follow-ups, complaints and reminders for this customer</span>
            </div>
            <div class="right">
                <span class="tag mute">{{ $counts->total ?? 0 }} total</span>
                @if ($counts?->pending)
                    <span class="tag warn">{{ $counts->pending }} pending</span>
                @endif
                @can('account note.create')
                    @if ($account_id)
                        <button type="button" class="btn sm pri" wire:click="openCreate" wire:loading.attr="disabled" wire:target="openCreate">
                            <span wire:loading.remove wire:target="openCreate"><i class="fa fa-plus"></i></span>
                            <span wire:loading wire:target="openCreate"><i class="fa fa-circle-o-notch fa-spin"></i></span>
                            New Note
                        </button>
                    @endif
                @endcan
            </div>
        </div>

        <div class="filters">
            <div class="row g-2 align-items-end">
            <div class="col-6 col-md-3 fld">
                <label>Type</label>
                <select wire:model.live="filter_type">
                    <option value="">All Types</option>
                    @foreach (noteTypes() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3 fld">
                <label>Status</label>
                <select wire:model.live="filter_status">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="completed">Completed</option>
                </select>
            </div>
            </div>
        </div>

        <div class="pbody">
            <div class="row g-2">
                @forelse ($notes as $note)
                    @php
                        $meta = $typeMeta[$note->type] ?? $typeMeta['general'];
                        $done = $note->status === 'completed';
                        $overdue = ! $done && $note->follow_up_date && $note->follow_up_date->isPast();
                    @endphp
                    <div class="col-12">
                    <article class="note {{ $done ? 'done' : '' }} d-flex flex-wrap align-items-start gap-3">
                        <span class="note-ic tag {{ $meta['tag'] }}"><i class="fa {{ $meta['icon'] }}"></i></span>
                        <div class="flex-fill" style="min-width: 0;">
                            <div class="d-flex flex-wrap align-items-center gap-1">
                                <span class="tag {{ $meta['tag'] }}">{{ noteTypes()[$note->type] ?? $note->type }}</span>
                                <span class="tag {{ $done ? 'ok' : 'warn' }}">
                                    <i class="fa {{ $done ? 'fa-check' : 'fa-clock-o' }}"></i> {{ $done ? 'Completed' : 'Pending' }}
                                </span>
                                @if ($note->follow_up_date)
                                    <span class="tag {{ $overdue ? 'bad' : 'mute' }}">
                                        <i class="fa fa-calendar"></i> {{ systemDate($note->follow_up_date) }}{{ $overdue ? ' · overdue' : '' }}
                                    </span>
                                @endif
                            </div>
                            <p class="note-body">{{ $note->note }}</p>
                            <div class="note-foot d-flex flex-wrap column-gap-4 row-gap-1">
                                <span><i class="fa fa-user"></i> {{ $note->createdBy->name ?? 'System' }}</span>
                                <span><i class="fa fa-clock-o"></i> {{ systemDate($note->created_at) }}</span>
                            </div>
                        </div>
                        <div class="note-acts d-flex flex-wrap gap-1 ms-auto">
                            @can('account note.edit')
                                <button type="button" class="btn sm" wire:click="toggleStatus({{ $note->id }})"
                                    title="{{ $done ? 'Mark as pending' : 'Mark as completed' }}">
                                    <i class="fa {{ $done ? 'fa-undo' : 'fa-check' }}"></i>
                                </button>
                                <button type="button" class="btn sm" wire:click="openEdit({{ $note->id }})" title="Edit note">
                                    <i class="fa fa-pencil"></i>
                                </button>
                            @endcan
                            @can('account note.delete')
                                <button type="button" class="btn sm ghost-bad" wire:click="delete({{ $note->id }})"
                                    wire:confirm="Delete this note? This cannot be undone." title="Delete note">
                                    <i class="fa fa-trash-o"></i>
                                </button>
                            @endcan
                        </div>
                    </article>
                    </div>
                @empty
                    <div class="col-12">
                    <div class="empty">
                        <i class="fa fa-pencil-square-o"></i>
                        @if ($filter_type || $filter_status)
                            No notes match the selected filters.
                        @else
                            No notes yet for this customer.
                        @endif
                    </div>
                    </div>
                @endforelse
            </div>

            @if ($notes->hasPages())
                <div class="cvx-pager mt-3">{{ $notes->links() }}</div>
            @endif
        </div>
    </div>

    {{-- ─────────── Editor (Livewire-driven, no JS needed) ─────────── --}}
    @if ($showModal)
        <div class="cvx-sheet" wire:key="note-editor">
            <div class="cvx-sheet-backdrop" wire:click="closeModal"></div>
            <div class="cvx-sheet-card" role="dialog" aria-modal="true">
                <div class="phead">
                    <span class="ic"><i class="fa {{ $note_id ? 'fa-pencil' : 'fa-plus' }}"></i></span>
                    <div>
                        <h4>{{ $note_id ? 'Edit Note' : 'New Note' }}</h4>
                        <span class="hint">{{ $note_id ? 'Update this note' : 'Add a note against this customer' }}</span>
                    </div>
                    <div class="right">
                        <button type="button" class="btn sm" wire:click="closeModal" aria-label="Close"><i class="fa fa-times"></i></button>
                    </div>
                </div>

                <div class="pbody">
                    @if ($errors->any())
                        <div class="alert-cv bad">
                            <i class="fa fa-exclamation-triangle"></i>
                            <div>
                                <b>Please fix the following:</b>
                                <ul class="mb-0 ps-3 mt-1">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    <div class="row g-2">
                        <div class="col-12 fld">
                            <label>Note <span class="text-danger">*</span></label>
                            <textarea rows="4" wire:model="form.note" placeholder="What happened, or what needs following up?"></textarea>
                            @error('form.note')<span class="err">{{ $message }}</span>@enderror
                        </div>
                        <div class="col-12 col-sm-6 fld">
                            <label>Type <span class="text-danger">*</span></label>
                            <select wire:model="form.type">
                                @foreach (noteTypes() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('form.type')<span class="err">{{ $message }}</span>@enderror
                        </div>
                        <div class="col-12 col-sm-6 fld">
                            <label>Follow Up Date</label>
                            <input type="date" wire:model="form.follow_up_date">
                            @error('form.follow_up_date')<span class="err">{{ $message }}</span>@enderror
                        </div>
                    </div>
                </div>

                <div class="cvx-sheet-foot">
                    <button type="button" class="btn" wire:click="closeModal">Cancel</button>
                    <button type="button" class="btn ok" wire:click="save('completed')" wire:loading.attr="disabled" wire:target="save">
                        <i class="fa fa-check"></i> Save as Completed
                    </button>
                    <button type="button" class="btn pri" wire:click="save('pending')" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save"><i class="fa fa-floppy-o"></i></span>
                        <span wire:loading wire:target="save"><i class="fa fa-circle-o-notch fa-spin"></i></span>
                        Save as Pending
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

@php
    use Carbon\Carbon;
@endphp
<div>
    <style>
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .status-visited {
            background-color: #d1fae5;
            color: #059669;
        }

        .status-rescheduled {
            background-color: #fef3c7;
            color: #d97706;
        }

        .status-pending {
            background-color: #e0e7ff;
            color: #4f46e5;
        }
    </style>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div></div>
        <button class="btn btn-primary btn-sm" wire:click="openModal">
            <i class="demo-psi-add me-2"></i>Add Terms
        </button>
    </div>

    @if (count($items) > 0)
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th width="15%">Date</th>
                        <th width="15%">Rescheduled Date</th>
                        <th width="15%">Status</th>
                        <th width="15%" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $index => $item)
                        <tr>
                            <td>{{ systemDate($item['date']) }}</td>
                            <td>
                                @if ($item['rescheduled_date'])
                                    {{ systemDate($item['rescheduled_date']) }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <span class="status-badge status-{{ $item['status'] }}">
                                    {{ ucfirst($item['status']) }}
                                </span>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary" wire:click="openModal({{ $item['id'] }})" title="Edit">
                                    <i class="demo-psi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" wire:click="delete({{ $item['id'] }})" wire:confirm="Are you sure you want to delete this item?" title="Delete">
                                    <i class="demo-pli-recycling"></i>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            @if ($item['notes'])
                                <td></td>
                                <td colspan="2">
                                    <span class="text-truncate d-inline-block" style="max-width: 300px;" title="{{ $item['notes'] }}">
                                        <b>Note :</b> <i>{{ $item['notes'] }}</i>
                                    </span>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="text-center py-5">
            <i class="demo-psi-calendar-4 fs-1 text-muted mb-3 d-block"></i>
            <p class="text-muted">No terms added yet. Click "Add Term" to get started.</p>
        </div>
    @endif

    <!-- Modal -->
    @if ($showModal)
        <div class="modal show d-block" style="background-color: rgba(0,0,0,0.5);" wire:click="closeModal">
            <div class="modal-dialog" wire:click.stop>
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ $editingId ? 'Edit Term' : 'Add Term' }}</h5>
                        <button type="button" class="btn-close" wire:click="closeModal"></button>
                    </div>
                    <form wire:submit.prevent="save">
                        <div class="modal-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Date <span class="text-danger">*</span></label>
                                    <input type="date" wire:model="item.date" class="form-control">
                                    @error('item.date')
                                        <span class="text-danger small">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Status <span class="text-danger">*</span></label>
                                    {{ html()->select('status', packageItemStatuses())->class('form-control')->id('status')->attributes(['wire:model' => 'item.status'])->required(true)->attribute('style', 'width:100%')->placeholder('Select Status') }}
                                    @error('item.status')
                                        <span class="text-danger small">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Rescheduled Date</label>
                                <input type="date" wire:model="item.rescheduled_date" class="form-control">
                                @error('item.rescheduled_date')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea wire:model="item.notes" class="form-control" rows="2"></textarea>
                                @error('item.notes')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="closeModal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>

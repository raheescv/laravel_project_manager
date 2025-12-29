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
        <div>
            <button class="btn btn-success btn-sm me-2" wire:click="openGenerateModal">
                <i class="demo-psi-calendar-4 me-2"></i>Generate Terms
            </button>
        <button class="btn btn-primary btn-sm" wire:click="openModal">
                <i class="demo-psi-add me-2"></i>Add Terms
        </button>
        </div>
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

    <!-- Generate Modal -->
    @if ($showGenerateModal)
        <div class="modal show d-block" style="background-color: rgba(0,0,0,0.5);" wire:click="closeGenerateModal">
            <div class="modal-dialog modal-xl" wire:click.stop>
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Generate Terms</h5>
                        <button type="button" class="btn-close" wire:click="closeGenerateModal"></button>
                    </div>
                    <form wire:submit.prevent="generateAndSave">
                        <div class="modal-body">
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label class="form-label">From Date <span class="text-danger">*</span></label>
                                    <input type="date" wire:model="generateForm.from_date" class="form-control">
                                    @error('generateForm.from_date')
                                        <span class="text-danger small">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Number of Terms <span class="text-danger">*</span></label>
                                    <input type="number" wire:model="generateForm.number_of_terms" class="form-control" min="1" step="1">
                                    @error('generateForm.number_of_terms')
                                        <span class="text-danger small">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Frequency <span class="text-danger">*</span></label>
                                    <select wire:model="generateForm.frequency" class="form-control" required>
                                        <option value="">Select Frequency</option>
                                        <option value="daily">Daily</option>
                                        <option value="weekly">Weekly</option>
                                        <option value="bi_weekly">Bi-Weekly</option>
                                        <option value="thrice_monthly">Thrice a Month</option>
                                        <option value="monthly">Monthly</option>
                                    </select>
                                    @error('generateForm.frequency')
                                        <span class="text-danger small">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Default Status <span class="text-danger">*</span></label>
                                    <select wire:model="generateForm.status" class="form-control" required>
                                        @foreach (packageItemStatuses() as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('generateForm.status')
                                        <span class="text-danger small">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            @if (!empty($previewDates))
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">
                                        <i class="demo-psi-calendar-4 me-2 text-primary"></i>Preview Dates
                                        <span class="badge bg-primary ms-2">{{ count($previewDates) }} {{ count($previewDates) == 1 ? 'term' : 'terms' }}</span>
                                    </label>
                                    <div class="border rounded p-3 bg-light" style="max-height: 300px; overflow-y: auto; background-color: #f8f9fa !important;">
                                        <div class="row g-2">
                                            @foreach ($previewDates as $index => $date)
                                                <div class="col-md-4 col-sm-6">
                                                    <div class="d-flex align-items-center p-2 bg-white rounded border shadow-sm">
                                                        <div class="badge bg-info me-2" style="min-width: 30px;">{{ $index + 1 }}</div>
                                                        <div class="flex-grow-1">
                                                            <i class="demo-psi-calendar-2 me-1 text-muted small"></i>
                                                            <span class="fw-medium">{{ systemDate($date) }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="closeGenerateModal">Cancel</button>
                            <button type="button" class="btn btn-info" wire:click="previewGenerationDates">Preview</button>
                            <button type="submit" class="btn btn-primary">Generate & Save</button>
                        </div>
                    </form>
                </div>
            </div>
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

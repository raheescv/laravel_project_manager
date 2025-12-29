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
        <div>
            @if (count($selectedItems) > 0)
                <button class="btn btn-danger btn-sm" wire:click="deleteSelected" wire:confirm="Are you sure you want to delete {{ count($selectedItems) }} selected item(s)?">
                    <i class="demo-pli-recycling me-2"></i>Delete Selected ({{ count($selectedItems) }})
                </button>
            @endif
        </div>
        <div>
            <button class="btn btn-success btn-sm me-2" wire:click="openGenerateModal">
                <i class="demo-psi-calendar-4 me-2"></i>Generate Terms
            </button>
        <button class="btn btn-primary btn-sm" wire:click="openModal">
                <i class="demo-psi-add me-2"></i>Add Terms
        </button>
        </div>
    </div>

    @if ($items->count() > 0)
        <div class="table-responsive">
            <table class="table table-striped table-sm table-hover">
                <thead>
                    <tr>
                        <th width="5%">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"
                                    wire:click="toggleSelectAll"
                                    id="selectAll">
                                <label class="form-check-label" for="selectAll" style="cursor: pointer;">
                                    All
                                </label>
                            </div>
                        </th>
                        <th width="15%">Date</th>
                        <th width="15%">Rescheduled Date</th>
                        <th width="15%">Status</th>
                        <th width="15%" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $index => $item)
                        <tr>
                            <td>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox"
                                        wire:click="toggleSelectItem({{ $item->id }})"
                                        @if(in_array($item->id, $selectedItems)) checked @endif
                                        id="item_{{ $item->id }}">
                                    <label class="form-check-label" for="item_{{ $item->id }}" style="cursor: pointer;"></label>
                                </div>
                            </td>
                            <td>{{ systemDate($item->date) }}</td>
                            <td>
                                @if ($item->rescheduled_date)
                                    {{ systemDate($item->rescheduled_date) }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <span class="status-badge status-{{ $item->status }}">
                                    {{ ucfirst($item->status) }}
                                </span>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-xs btn-outline-primary" wire:click="openModal({{ $item->id }})" title="Edit">
                                    <i class="demo-psi-pencil"></i>
                                </button>
                                <button class="btn btn-xs btn-outline-danger" wire:click="delete({{ $item->id }})" wire:confirm="Are you sure you want to delete this item?" title="Delete">
                                    <i class="demo-pli-recycling"></i>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            @if ($item->notes)
                                <td></td>
                                <td colspan="4">
                                    <span class="text-truncate d-inline-block" style="max-width: 300px;" title="{{ $item->notes }}">
                                        <b>Note :</b> <i>{{ $item->notes }}</i>
                                    </span>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($items->hasPages())
            <div class="d-flex justify-content-center mt-3">
                {{ $items->links() }}
            </div>
        @endif
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
                                    <select wire:model="generateForm.frequency" class="form-control" value='daily' required>
                                        <option value="">Select Frequency</option>
                                        @foreach (packageFrequency() as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
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
                            @if (!empty($previewDates) && !empty($calendarData))
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">
                                        <i class="demo-psi-calendar-4 me-2 text-primary"></i>Year View Calendar
                                        <span class="badge bg-primary ms-2">{{ count($previewDates) }} {{ count($previewDates) == 1 ? 'term' : 'terms' }}</span>
                                    </label>
                                    <div class="border rounded p-2 bg-light shadow-sm" style="max-height: 400px; overflow-y: auto; background-color: #f8f9fa !important;">
                                        @foreach ($calendarData as $yearData)
                                            <div class="mb-3">
                                                <h6 class="text-center mb-2 fw-bold text-primary bg-white p-1 rounded">
                                                    <i class="demo-psi-calendar-4 me-2"></i>{{ $yearData['year'] }}
                                                </h6>
                                                <div class="row g-2">
                                                    @foreach ($yearData['months'] as $month)
                                                        <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                            <div class="bg-white rounded border shadow-sm p-1">
                                                                <div class="text-center fw-semibold small mb-1 text-primary" style="font-size: 0.75rem;">
                                                                    {{ $month['month'] }}
                                                                </div>
                                                                <table class="table table-bordered mb-0" style="font-size: 0.65rem; margin: 0;">
                                                                    <thead>
                                                                        <tr style="background-color: #e7f3ff;">
                                                                            <th class="text-center p-1" style="font-size: 0.6rem; padding: 2px !important;">S</th>
                                                                            <th class="text-center p-1" style="font-size: 0.6rem; padding: 2px !important;">M</th>
                                                                            <th class="text-center p-1" style="font-size: 0.6rem; padding: 2px !important;">T</th>
                                                                            <th class="text-center p-1" style="font-size: 0.6rem; padding: 2px !important;">W</th>
                                                                            <th class="text-center p-1" style="font-size: 0.6rem; padding: 2px !important;">T</th>
                                                                            <th class="text-center p-1" style="font-size: 0.6rem; padding: 2px !important;">F</th>
                                                                            <th class="text-center p-1" style="font-size: 0.6rem; padding: 2px !important;">S</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        @foreach ($month['weeks'] as $week)
                                                                            <tr>
                                                                                @foreach ($week as $day)
                                                                                    @if ($day === null)
                                                                                        <td class="text-center p-1" style="padding: 2px !important; background-color: #f8f9fa; border: 1px solid #dee2e6;"></td>
                                                                                    @elseif (!$day['isTerm'])
                                                                                        <td class="text-center p-1" style="padding: 2px !important; border: 1px solid #dee2e6;">
                                                                                            <span class="text-muted" style="font-size: 0.7rem;">{{ $day['day'] }}</span>
                                                                                        </td>
                                                                                    @else
                                                                                        <td class="text-center p-1" style="padding: 2px !important; background-color: #0dcaf0; border: 1px solid #0dcaf0; color: white;">
                                                                                            <span class="fw-bold" style="font-size: 0.7rem;">{{ $day['day'] }}</span>
                                                                                        </td>
                                                                                    @endif
                                                                                @endforeach
                                                                            </tr>
                                                                        @endforeach
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
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

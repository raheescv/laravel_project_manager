@php
    $isCompleted = $currentStatus === \App\Enums\Property\ServiceRequestStatus::Completed;
    $nextStatus = $currentStatus->next();
    $canSave = ! $isCompleted && auth()->user()->can($request_id ? 'property service request.edit' : 'property service request.create');
    $dateLocked = $request_id && ! auth()->user()->can('property service request.edit date');
    $workflowSteps = [
        ['label' => 'Requested', 'by' => $workflow['requested_by'] ?? '', 'at' => $workflow['requested_at'] ?? '', 'icon' => 'fa-paper-plane'],
        ['label' => 'Approved', 'by' => $workflow['approved_by'] ?? '', 'at' => $workflow['approved_at'] ?? '', 'icon' => 'fa-check'],
        ['label' => 'Financial Approved', 'by' => $workflow['financial_approved_by'] ?? '', 'at' => $workflow['financial_approved_at'] ?? '', 'icon' => 'fa-money'],
        ['label' => 'Completed', 'by' => $workflow['completed_by'] ?? '', 'at' => $workflow['completed_at'] ?? '', 'icon' => 'fa-flag-checkered'],
    ];
@endphp
<div>
    <form wire:submit="save">
        {{-- Section 1: Property --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <div class="d-flex align-items-center">
                    <i class="fa fa-building fs-5 me-2 text-primary"></i>
                    <h5 class="mb-0 fw-bold">Property</h5>
                    <span class="badge bg-{{ $currentStatus->color() }} ms-auto">{{ $currentStatus->label() }}</span>
                </div>
            </div>
            <div class="card-body py-4">
                <div class="row g-3">
                    <div class="col-md-2" wire:ignore>
                        <label class="form-label fw-semibold small"><i class="fa fa-map-marker text-primary me-1"></i>Group/Project</label>
                        {{ html()->select('property_group_id', $preFilledDropDowns['group'] ?? [])->value($formData['property_group_id'] ?? '')->class('select-property_group_id')->id('property_group_id')->placeholder('Search Here') }}
                    </div>
                    <div class="col-md-3" wire:ignore>
                        <label class="form-label fw-semibold small"><i class="fa fa-building text-success me-1"></i>Building</label>
                        {{ html()->select('property_building_id', $preFilledDropDowns['building'] ?? [])->value($formData['property_building_id'] ?? '')->class('select-property_building_id')->id('property_building_id')->placeholder('Search Here')->attribute('data-group-select', '#property_group_id') }}
                    </div>
                    <div class="col-md-2" wire:ignore>
                        <label class="form-label fw-semibold small"><i class="fa fa-home text-info me-1"></i>Type</label>
                        {{ html()->select('property_type_id', $preFilledDropDowns['type'] ?? [])->value($formData['property_type_id'] ?? '')->class('select-property_type_id')->id('property_type_id')->placeholder('Search Here') }}
                    </div>
                    <div class="col-md-5" wire:ignore>
                        <label class="form-label fw-semibold small"><i class="fa fa-key text-warning me-1"></i>Property No/Unit *</label>
                        {{ html()->select('property_id', $preFilledDropDowns['property'] ?? [])->value($formData['property_id'] ?? '')->class('select-property_id')->id('property_id')->required(true)->placeholder('Search Here')->attribute('data-building-select', '#property_building_id')->attribute('data-group-select', '#property_group_id')->attribute('data-type-select', '#property_type_id') }}
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- Left: Request details + notes --}}
            <div class="col-lg-7 col-md-12">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <div class="d-flex align-items-center">
                            <i class="fa fa-plug fs-5 me-2 text-success"></i>
                            <h5 class="mb-0 fw-bold">Request Details</h5>
                        </div>
                    </div>
                    <div class="card-body py-4">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small" for="sr_date"><i class="fa fa-calendar-o text-primary me-1"></i>Date *</label>
                                <input type="date" id="sr_date" class="form-control" wire:model="formData.date" required @readonly($dateLocked)>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small" for="sr_utility"><i class="fa fa-bolt text-warning me-1"></i>Utility *</label>
                                <select id="sr_utility" class="form-select" wire:model.live="formData.utility_id" required>
                                    @foreach ($utilities as $utilityId => $utilityName)
                                        <option value="{{ $utilityId }}">{{ $utilityName }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small" for="sr_type"><i class="fa fa-exchange text-info me-1"></i>Request Type *</label>
                                <select id="sr_type" class="form-select" wire:model.live="formData.type" required>
                                    @foreach ($types as $type)
                                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small" for="sr_amount"><i class="fa fa-money text-success me-1"></i>Amount *</label>
                                <input type="number" id="sr_amount" class="form-control" wire:model="formData.amount" min="0" step="any" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-semibold small" for="sr_remarks"><i class="fa fa-comment text-muted me-1"></i>Remarks</label>
                                <textarea id="sr_remarks" class="form-control" wire:model="formData.remarks" rows="2" placeholder="Enter your remarks here"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <div class="d-flex align-items-center">
                            <i class="fa fa-sticky-note-o fs-5 me-2 text-info"></i>
                            <h5 class="mb-0 fw-bold">Notes</h5>
                            <span class="badge bg-light text-dark border ms-2">{{ count($notes) }}</span>
                        </div>
                    </div>
                    <div class="card-body">
                        @if (! $isCompleted)
                            <div class="input-group mb-3">
                                <input type="text" class="form-control" wire:model="note" wire:keydown.enter.prevent="addNote" placeholder="Enter your note here">
                                <button type="button" class="btn btn-primary" wire:click="addNote"><i class="fa fa-plus me-1"></i>Add</button>
                            </div>
                        @endif
                        <ul class="list-group list-group-flush">
                            @forelse ($notes as $index => $item)
                                <li class="list-group-item px-0 d-flex align-items-start gap-2" wire:key="sr-note-{{ $index }}">
                                    <div class="flex-grow-1">
                                        <span class="fw-semibold">{{ $item['creator'] ?? '' }}</span>
                                        <small class="text-muted ms-2">{{ isset($item['created_at']) ? \Illuminate\Support\Carbon::parse($item['created_at'])->format('d M Y H:i') : '' }}</small>
                                        <div>{{ $item['note'] ?? '' }}</div>
                                    </div>
                                    @if (! $isCompleted)
                                        @can('property service request.delete note')
                                            <button type="button" class="btn btn-light btn-sm text-danger" wire:click="deleteNote({{ $index }})" title="Remove note">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        @endcan
                                    @endif
                                </li>
                            @empty
                                <li class="list-group-item px-0 text-muted small">No notes yet.</li>
                            @endforelse
                        </ul>
                        @if (! $isCompleted && $request_id)
                            <div class="form-text mb-0"><i class="fa fa-info-circle me-1"></i>Notes are kept when you press Save.</div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Right: Property info, workflow, history --}}
            <div class="col-lg-5 col-md-12">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <div class="d-flex align-items-center">
                            <i class="fa fa-info-circle fs-5 me-2 text-info"></i>
                            <h5 class="mb-0 fw-bold">Unit Status</h5>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-bordered mb-0">
                            <tbody>
                                <tr>
                                    <th class="bg-light fw-semibold small" style="width: 45%">Property Status</th>
                                    <td>{{ $propertyInfo['property_status'] ?? '' }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light fw-semibold small">Rent Out</th>
                                    <td>
                                        @if (! empty($propertyInfo['rent_out_id']))
                                            <a href="{{ route('property::rent::view', $propertyInfo['rent_out_id']) }}">#{{ $propertyInfo['rent_out_id'] }}</a>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th class="bg-light fw-semibold small">Customer</th>
                                    <td>{{ $propertyInfo['customer_name'] ?? '' }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light fw-semibold small">Utility in Rent</th>
                                    <td>
                                        @if (! empty($propertyInfo['service_status']))
                                            <span class="badge bg-{{ $propertyInfo['service_status'] === 'Included' ? 'success' : 'warning' }}">{{ $propertyInfo['service_status'] }}</span>
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <div class="d-flex align-items-center">
                            <i class="fa fa-tasks fs-5 me-2 text-primary"></i>
                            <h5 class="mb-0 fw-bold">Workflow</h5>
                        </div>
                    </div>
                    <ul class="list-group list-group-flush">
                        @foreach ($workflowSteps as $step)
                            <li class="list-group-item d-flex align-items-center gap-2">
                                <i class="fa fa-fw {{ $step['icon'] }} {{ $step['by'] ? 'text-success' : 'text-muted opacity-50' }}"></i>
                                <span class="fw-semibold small" style="width: 40%">{{ $step['label'] }}</span>
                                <span class="small">{{ $step['by'] ?: '-' }}</span>
                                <small class="text-muted ms-auto">{{ $step['at'] }}</small>
                            </li>
                        @endforeach
                    </ul>
                    @if ($request_id && $nextStatus && auth()->user()->can($nextStatus->permission()))
                        <div class="card-footer bg-white">
                            <button type="button" class="btn btn-info w-100" wire:click="changeStatus" wire:loading.attr="disabled"
                                wire:confirm="{{ $nextStatus->actionLabel() }} this service request?">
                                <i class="fa fa-check-circle me-1"></i>{{ $nextStatus->actionLabel() }}
                            </button>
                        </div>
                    @endif
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <div class="d-flex align-items-center">
                            <i class="fa fa-history fs-5 me-2 text-secondary"></i>
                            <h5 class="mb-0 fw-bold">Connection History</h5>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-striped mb-0">
                            <thead class="bg-light text-muted small">
                                <tr>
                                    <th class="ps-3">Utility</th>
                                    <th>Connected</th>
                                    <th>Disconnected</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($history as $row)
                                    <tr>
                                        <td class="ps-3">{{ $row['utility'] }}</td>
                                        <td>{{ $row['connect_date'] }}</td>
                                        <td>{{ $row['disconnect_date'] ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted small py-3">No connections recorded for this unit.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mb-4">
            <a href="{{ route('property::service_request::index') }}" class="btn btn-light">
                <i class="fa fa-times me-1"></i> Cancel
            </a>
            @if ($canSave)
                <button type="submit" class="btn btn-success px-4" wire:loading.attr="disabled">
                    <i class="fa fa-save me-1"></i> Save
                </button>
            @endif
        </div>
    </form>

    @push('scripts')
        <script>
            $(document).ready(function() {
                function clearAndReload(id) {
                    var el = document.querySelector('#' + id);
                    if (el && el.tomselect) {
                        el.tomselect.clear();
                        el.tomselect.clearOptions();
                        el.tomselect.load('');
                    }
                }

                function preset(id, value, name) {
                    var ts = document.querySelector('#' + id)?.tomselect;
                    if (ts && value && name) {
                        ts.clear(true);
                        ts.clearOptions();
                        ts.addOption({ id: value, name: name });
                        ts.addItem(value, true);
                    }
                }

                $('#property_group_id').on('change', function() {
                    @this.set('formData.property_group_id', $(this).val());
                    clearAndReload('property_building_id');
                    clearAndReload('property_id');
                    @this.set('formData.property_building_id', '');
                    @this.set('formData.property_id', '');
                });
                $('#property_building_id').on('change', function() {
                    @this.set('formData.property_building_id', $(this).val());
                    clearAndReload('property_id');
                    @this.set('formData.property_id', '');
                });
                $('#property_type_id').on('change', function() {
                    @this.set('formData.property_type_id', $(this).val());
                    clearAndReload('property_id');
                    @this.set('formData.property_id', '');
                });
                $('#property_id').on('change', function() {
                    @this.set('formData.property_id', $(this).val());
                });

                Livewire.on('PropertyDetailsLoaded', (params) => {
                    var data = params[0] || params;
                    preset('property_group_id', data.property_group_id, data.group_name);
                    preset('property_building_id', data.property_building_id, data.building_name);
                    preset('property_type_id', data.property_type_id, data.type_name);
                });

                Livewire.on('ServiceRequestSelectValues', (params) => {
                    var data = params[0] || params;
                    preset('property_group_id', data.property_group_id, data.group_name);
                    preset('property_building_id', data.property_building_id, data.building_name);
                    preset('property_type_id', data.property_type_id, data.type_name);
                    preset('property_id', data.property_id, data.property_name);
                });
            });
        </script>
    @endpush
</div>

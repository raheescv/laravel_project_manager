<div>
    {{-- Header --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold"><i class="fa fa-wrench me-2"></i>Maintenance Request Details</h5>
            <span class="badge bg-{{ $customerInfo['complaint_status_color'] ?? 'warning' }} px-3 py-2">
                <i class="fa fa-circle me-1 small"></i>{{ $customerInfo['complaint_status'] ?? 'Pending' }}
            </span>
        </div>
    </div>

    {{-- Two-column: Property Info + Customer & Request Info --}}
    <div class="row">
        {{-- Property Information --}}
        <div class="col-lg-6 col-md-12 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header py-2" style="background: linear-gradient(135deg, #4CAF50, #66BB6A);">
                    <h6 class="mb-0 text-white fw-bold"><i class="fa fa-building me-2"></i>Property Information</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-bordered mb-0">
                        <tbody>
                            <tr>
                                <th class="bg-light fw-semibold small" style="width: 35%">
                                    <i class="fa fa-hashtag text-muted me-1"></i>Registration #
                                </th>
                                <td>
                                    @if($propertyInfo['registration_id'])
                                        <a href="{{ route('property::maintenance::edit', $propertyInfo['registration_id']) }}" class="text-primary fw-semibold">
                                            {{ $propertyInfo['registration_id'] }}
                                        </a>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th class="bg-light fw-semibold small"><i class="fa fa-folder-open text-muted me-1"></i>Group/Project</th>
                                <td>{{ $propertyInfo['group'] }}</td>
                            </tr>
                            <tr>
                                <th class="bg-light fw-semibold small"><i class="fa fa-building text-muted me-1"></i>Building</th>
                                <td>{{ $propertyInfo['building'] }}</td>
                            </tr>
                            <tr>
                                <th class="bg-light fw-semibold small"><i class="fa fa-home text-muted me-1"></i>Type</th>
                                <td>{{ $propertyInfo['type'] }}</td>
                            </tr>
                            <tr>
                                <th class="bg-light fw-semibold small"><i class="fa fa-key text-muted me-1"></i>Property No/Unit</th>
                                <td>{{ $propertyInfo['property_number'] }}</td>
                            </tr>
                            <tr>
                                <th class="bg-light fw-semibold small"><i class="fa fa-exclamation-triangle text-muted me-1"></i>Priority</th>
                                <td>
                                    <span class="badge bg-{{ $propertyInfo['priority_color'] }}">{{ $propertyInfo['priority'] }}</span>
                                </td>
                            </tr>
                            <tr>
                                <th class="bg-light fw-semibold small"><i class="fa fa-calendar text-muted me-1"></i>Appointment Date</th>
                                <td>
                                    <i class="fa fa-calendar me-1 text-muted"></i>{{ $propertyInfo['date'] }}
                                    @if($propertyInfo['time'])
                                        {{ $propertyInfo['time'] }}
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Customer & Request Information --}}
        <div class="col-lg-6 col-md-12 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header py-2" style="background: linear-gradient(135deg, #FF9800, #FFB74D);">
                    <h6 class="mb-0 text-white fw-bold"><i class="fa fa-user me-2"></i>Customer & Request Information</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-bordered mb-0">
                        <tbody>
                            <tr>
                                <th class="bg-light fw-semibold small" style="width: 40%">
                                    <i class="fa fa-info-circle text-muted me-1"></i>Complaint Status
                                </th>
                                <td>
                                    <span class="badge bg-{{ $customerInfo['complaint_status_color'] }}">
                                        <i class="fa fa-circle me-1 small"></i>{{ $customerInfo['complaint_status'] }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th class="bg-light fw-semibold small"><i class="fa fa-file-text text-muted me-1"></i>Rentout</th>
                                <td>{{ $customerInfo['rentout_id'] }}</td>
                            </tr>
                            <tr>
                                <th class="bg-light fw-semibold small"><i class="fa fa-check-circle text-muted me-1"></i>Rentout Status</th>
                                <td>{{ $customerInfo['rentout_status'] }}</td>
                            </tr>
                            <tr>
                                <th class="bg-light fw-semibold small"><i class="fa fa-calendar text-muted me-1"></i>Agreement Starting Date</th>
                                <td>{{ $customerInfo['agreement_start_date'] }}</td>
                            </tr>
                            <tr>
                                <th class="bg-light fw-semibold small"><i class="fa fa-user text-muted me-1"></i>Customer</th>
                                <td>{{ $customerInfo['customer_name'] }}</td>
                            </tr>
                            <tr>
                                <th class="bg-light fw-semibold small"><i class="fa fa-phone text-muted me-1"></i>Customer Mobile</th>
                                <td>{{ $customerInfo['customer_mobile'] }}</td>
                            </tr>
                            <tr>
                                <th class="bg-light fw-semibold small"><i class="fa fa-clipboard text-muted me-1"></i>Work Order No</th>
                                <td class="text-muted fst-italic">{{ $customerInfo['work_order_no'] }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Two-column: Remarks + Activity Log --}}
    <div class="row">
        {{-- Remarks --}}
        <div class="col-lg-7 col-md-12 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="mb-0 fw-bold"><i class="fa fa-comment me-2 text-secondary"></i>Remarks</h6>
                </div>
                <div class="card-body">
                    <label class="form-label fw-semibold small">
                        <i class="fa fa-wrench me-1 text-muted"></i>Technician Remark <span class="text-danger">*</span>
                    </label>
                    <textarea class="form-control @error('technician_remark') is-invalid @enderror"
                        wire:model="technician_remark"
                        rows="5"
                        placeholder="Enter your technical assessment and solution here"
                        @if(in_array($maintenanceComplaint?->status?->value, ['completed', 'cancelled'])) disabled @endif
                    ></textarea>
                    @error('technician_remark')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted mt-1">Please provide details about the issue and how you resolved it</small>
                </div>
            </div>
        </div>

        {{-- Activity Log --}}
        <div class="col-lg-5 col-md-12 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header py-2" style="background: linear-gradient(135deg, #FFC107, #FFD54F);">
                    <h6 class="mb-0 fw-bold"><i class="fa fa-history me-2"></i>Activity Log</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-bordered mb-0">
                        <tbody>
                            <tr>
                                <th class="bg-light fw-semibold small" style="width: 30%">
                                    <i class="fa fa-plus-circle text-success me-1"></i>Created
                                </th>
                                <td class="fw-semibold">{{ $activityLog['created_by'] }}</td>
                                <td class="text-muted small">{{ $activityLog['created_at'] }}</td>
                            </tr>
                            <tr>
                                <th class="bg-light fw-semibold small">
                                    <i class="fa fa-user-plus text-info me-1"></i>Assigned
                                </th>
                                <td class="fw-semibold">{{ $activityLog['assigned_by'] }}</td>
                                <td class="text-muted small">{{ $activityLog['assigned_at'] }}</td>
                            </tr>
                            <tr>
                                <th class="bg-light fw-semibold small">
                                    <i class="fa fa-check-circle text-primary me-1"></i>Completed
                                </th>
                                <td class="fw-semibold">{{ $activityLog['completed_by'] }}</td>
                                <td class="text-muted small">{{ $activityLog['completed_at'] }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- All Maintenance Requests --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="mb-0 fw-bold"><i class="fa fa-list me-2 text-dark"></i>All Maintenance Requests</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle mb-0">
                    <thead class="bg-light">
                        <tr class="small">
                            <th class="fw-semibold"><i class="fa fa-tags me-1"></i>Category</th>
                            <th class="fw-semibold"><i class="fa fa-exclamation-circle me-1"></i>Complaint</th>
                            <th class="fw-semibold"><i class="fa fa-user me-1"></i>Technician</th>
                            <th class="fw-semibold"><i class="fa fa-comment me-1"></i>Technician Remarks</th>
                            <th class="fw-semibold text-center"><i class="fa fa-circle me-1"></i>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($allComplaints as $item)
                            <tr class="{{ $item['is_current'] ? 'table-success' : '' }}">
                                <td>
                                    <span class="badge bg-secondary">{{ $item['category_name'] }}</span>
                                </td>
                                <td>{{ $item['complaint_name'] }}</td>
                                <td>
                                    @if($item['technician_name'])
                                        <a href="{{ route('property::maintenance::complaint', $item['id']) }}" class="text-decoration-none">
                                            <i class="fa fa-user-circle me-1"></i>{{ $item['technician_name'] }}
                                        </a>
                                    @else
                                        <span class="text-muted fst-italic">Not assigned</span>
                                    @endif
                                </td>
                                <td>
                                    @if($item['technician_remark'])
                                        {{ \Illuminate\Support\Str::limit($item['technician_remark'], 50) }}
                                    @else
                                        <span class="text-muted fst-italic">No remarks yet</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $item['status_color'] }}">
                                        <i class="fa fa-circle me-1 small"></i>{{ $item['status_label'] }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Supply Items + Notes + Attachments (Shared Partial) --}}
    @include('partials.supply-request.items-notes-attachments', [
        'items' => $items,
        'item' => $item,
        'notes' => $notes,
        'note' => $note,
        'imageList' => $imageList,
        'branches' => $branches,
        'isCompleted' => $isCompleted || $isCancelled,
        'showTotals' => false,
        'supply_request' => $supply_request,
    ])

    {{-- Action Buttons --}}
    @if(!in_array($maintenanceComplaint?->status?->value, ['completed', 'cancelled']))
        <div class="d-flex justify-content-end gap-2 mb-4">
            <a href="{{ route('property::maintenance::edit', $propertyInfo['registration_id']) }}" class="btn btn-light">
                <i class="fa fa-arrow-left me-1"></i> Back to Registration
            </a>
            <button type="button" wire:click="save('pending')" class="btn btn-primary px-4">
                <i class="fa fa-save me-1"></i> Save
            </button>
            @can('maintenance.complete')
                <button type="button" wire:click="save('completed')"
                    wire:confirm="Are you sure you want to complete this complaint? This action cannot be undone."
                    class="btn btn-success px-4">
                    <i class="fa fa-check me-1"></i> Complete
                </button>
            @endcan
        </div>
    @else
        <div class="d-flex justify-content-end gap-2 mb-4">
            <a href="{{ route('property::maintenance::edit', $propertyInfo['registration_id']) }}" class="btn btn-light">
                <i class="fa fa-arrow-left me-1"></i> Back to Registration
            </a>
        </div>
    @endif

    @push('scripts')
        <script>
            $(document).ready(function() {
                // Barcode and product select events (same as SupplyRequest)
                $(document).on('keypress', '#supply_barcode, .single_cart', function(e) {
                    if (e.keyCode === 13) {
                        $('#supply_add_cart').click();
                        return false;
                    }
                });
                window.addEventListener('barcodeAutofocus', event => {
                    $('#supply_barcode').select();
                });
                window.addEventListener('openProductSelectBox', event => {
                    var el = document.querySelector('#supply_product_id');
                    if (el && el.tomselect) {
                        el.tomselect.focus();
                    }
                });

                // Store and Product select bindings
                $('#item_branch_id').on('change', function() {
                    @this.set('item.branch_id', $(this).val());
                });
                $('#supply_product_id').on('change', function() {
                    @this.set('item.product_id', $(this).val());
                    if ($(this).val()) {
                        $('#supply_item_quantity').select();
                    }
                });
            });
        </script>
    @endpush
</div>

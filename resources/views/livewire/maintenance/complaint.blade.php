<div>
    {{-- Section 1: Compact Property Info Bar --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2 px-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="d-flex flex-wrap align-items-center gap-3 small">
                    <a href="{{ route('property::maintenance::edit', $propertyInfo['registration_id']) }}"
                        class="text-primary fw-bold text-decoration-none">
                        <i class="fa fa-hashtag"></i> REG-{{ $propertyInfo['registration_id'] }}
                    </a>
                    <span class="d-flex align-items-center gap-1 text-dark">
                        <i class="fa fa-folder-open text-muted" style="font-size: 0.7rem;"></i>
                        {{ $propertyInfo['group'] }}
                    </span>
                    <span class="d-flex align-items-center gap-1 text-dark">
                        <i class="fa fa-building text-muted" style="font-size: 0.7rem;"></i>
                        {{ $propertyInfo['building'] }}
                    </span>
                    <span class="d-flex align-items-center gap-1 text-dark">
                        <i class="fa fa-home text-muted" style="font-size: 0.7rem;"></i>
                        {{ $propertyInfo['type'] }}
                    </span>
                    <span class="d-flex align-items-center gap-1 fw-bold text-dark">
                        <i class="fa fa-key text-warning" style="font-size: 0.7rem;"></i>
                        {{ $propertyInfo['property_number'] }}
                    </span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-{{ $propertyInfo['priority_color'] ?? 'secondary' }} rounded-pill px-2 py-1" style="font-size: 0.7rem;">
                        {{ $propertyInfo['priority'] ?? '' }}
                    </span>
                    <span class="badge bg-{{ $customerInfo['complaint_status_color'] ?? 'warning' }} rounded-pill px-2 py-1" style="font-size: 0.7rem;">
                        {{ $customerInfo['complaint_status'] ?? 'Pending' }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Section 2: Three-column — Details | Remark | Activity --}}
    <div class="row g-3 mb-3">
        {{-- Left: Property & Customer Details --}}
        <div class="col-lg-3 col-md-12">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-2 border-bottom">
                    <h6 class="mb-0 fw-bold small text-uppercase text-muted" style="letter-spacing: 0.5px;">
                        <i class="fa fa-building me-1 text-primary"></i> Details
                    </h6>
                </div>
                <div class="card-body p-3">
                    @php
                        $details = [
                            ['icon' => 'fa-calendar', 'color' => 'primary', 'label' => 'Appointment', 'value' => $propertyInfo['date'] . ($propertyInfo['time'] ? ' ' . $propertyInfo['time'] : '')],
                            ['icon' => 'fa-user', 'color' => 'info', 'label' => 'Customer', 'value' => $customerInfo['customer_name'] ?: '-'],
                            ['icon' => 'fa-phone', 'color' => 'success', 'label' => 'Mobile', 'value' => $customerInfo['customer_mobile'] ?: '-'],
                            ['icon' => 'fa-file-text-o', 'color' => 'warning', 'label' => 'Rentout', 'value' => $customerInfo['rentout_id'] ?: '-'],
                            ['icon' => 'fa-calendar-check-o', 'color' => 'danger', 'label' => 'Agreement Start', 'value' => $customerInfo['agreement_start_date'] ?: '-'],
                            ['icon' => 'fa-clipboard', 'color' => 'secondary', 'label' => 'Work Order', 'value' => $customerInfo['work_order_no']],
                        ];
                    @endphp
                    @foreach($details as $d)
                        <div class="d-flex align-items-start gap-2 {{ !$loop->last ? 'mb-3 pb-3 border-bottom' : '' }}">
                            <span class="d-inline-flex align-items-center justify-content-center rounded bg-{{ $d['color'] }} bg-opacity-10 flex-shrink-0"
                                style="width: 28px; height: 28px; border-radius: 6px !important;">
                                <i class="fa {{ $d['icon'] }} text-{{ $d['color'] }}" style="font-size: 0.7rem;"></i>
                            </span>
                            <div class="min-width-0">
                                <div class="text-muted" style="font-size: 0.65rem; letter-spacing: 0.3px;">{{ $d['label'] }}</div>
                                <div class="fw-semibold text-dark small text-truncate">{{ $d['value'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Center: Technician Remark --}}
        <div class="col-lg-6 col-md-12">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-2 border-bottom">
                    <h6 class="mb-0 fw-bold small text-uppercase text-muted" style="letter-spacing: 0.5px;">
                        <i class="fa fa-wrench me-1 text-success"></i> Technician Remarks
                    </h6>
                </div>
                <div class="card-body p-3 d-flex flex-column">
                    <textarea class="form-control flex-grow-1 @error('technician_remark') is-invalid @enderror"
                        wire:model="technician_remark"
                        rows="8"
                        placeholder="Enter your technical assessment, findings, and solution details here..."
                        style="resize: vertical; min-height: 140px;"
                        @if(in_array($maintenanceComplaint?->status?->value, ['completed', 'cancelled'])) disabled @endif
                    ></textarea>
                    @error('technician_remark')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Right: Activity Log --}}
        <div class="col-lg-3 col-md-12">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-2 border-bottom">
                    <h6 class="mb-0 fw-bold small text-uppercase text-muted" style="letter-spacing: 0.5px;">
                        <i class="fa fa-history me-1 text-secondary"></i> Activity Log
                    </h6>
                </div>
                <div class="card-body p-3">
                    @php
                        $timeline = [
                            ['icon' => 'fa-plus', 'color' => 'success', 'label' => 'Created', 'by' => $activityLog['created_by'], 'at' => $activityLog['created_at']],
                            ['icon' => 'fa-user-plus', 'color' => 'info', 'label' => 'Assigned', 'by' => $activityLog['assigned_by'], 'at' => $activityLog['assigned_at']],
                            ['icon' => 'fa-check', 'color' => 'primary', 'label' => 'Completed', 'by' => $activityLog['completed_by'], 'at' => $activityLog['completed_at']],
                        ];
                    @endphp
                    <div class="position-relative">
                        {{-- Vertical timeline line --}}
                        <div class="position-absolute" style="left: 13px; top: 28px; bottom: 28px; width: 2px; background: linear-gradient(to bottom, #e9ecef 0%, #dee2e6 50%, #e9ecef 100%);"></div>

                        @foreach($timeline as $t)
                            <div class="d-flex align-items-start gap-3 position-relative {{ !$loop->last ? 'mb-4' : '' }}">
                                <span class="d-inline-flex align-items-center justify-content-center rounded-circle flex-shrink-0 position-relative
                                    {{ $t['by'] ? 'bg-' . $t['color'] : 'bg-light border' }}"
                                    style="width: 28px; height: 28px; z-index: 1;">
                                    <i class="fa {{ $t['icon'] }} {{ $t['by'] ? 'text-white' : 'text-muted' }}" style="font-size: 0.6rem;"></i>
                                </span>
                                <div class="pt-1 min-width-0">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fw-semibold small text-dark">{{ $t['label'] }}</span>
                                        @if($t['by'])
                                            <span class="d-inline-block rounded-pill px-2 bg-{{ $t['color'] }} bg-opacity-10 text-{{ $t['color'] }}"
                                                style="font-size: 0.6rem; padding-top: 1px; padding-bottom: 1px;">Done</span>
                                        @endif
                                    </div>
                                    @if($t['by'])
                                        <div class="text-dark small">{{ $t['by'] }}</div>
                                        <div class="text-muted" style="font-size: 0.65rem;">
                                            <i class="fa fa-clock-o me-1"></i>{{ $t['at'] }}
                                        </div>
                                    @else
                                        <div class="text-muted fst-italic" style="font-size: 0.72rem;">Pending</div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Section 3: All Maintenance Requests --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white py-2 border-bottom">
            <div class="d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-bold small text-uppercase text-muted" style="letter-spacing: 0.5px;">
                    <i class="fa fa-wrench me-1 text-warning"></i> Maintenance Requests
                </h6>
                @if(count($allComplaints))
                    <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill px-2" style="font-size: 0.68rem;">
                        {{ count($allComplaints) }}
                    </span>
                @endif
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0">
                    <thead>
                        <tr style="background: #f8fafc;">
                            <th class="fw-semibold ps-3 small text-uppercase text-muted border-0" style="font-size: 0.7rem; letter-spacing: 0.5px;">Group</th>
                            <th class="fw-semibold small text-uppercase text-muted border-0" style="font-size: 0.7rem; letter-spacing: 0.5px;">Request</th>
                            <th class="fw-semibold small text-uppercase text-muted border-0" style="font-size: 0.7rem; letter-spacing: 0.5px;">Technician</th>
                            <th class="fw-semibold small text-uppercase text-muted border-0" style="font-size: 0.7rem; letter-spacing: 0.5px;">Remarks</th>
                            <th class="fw-semibold text-center small text-uppercase text-muted border-0" style="font-size: 0.7rem; width: 90px;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($allComplaints as $c)
                            <tr class="{{ $c['is_current'] ? 'bg-primary bg-opacity-10' : ($loop->even ? '' : 'bg-white') }}">
                                <td class="ps-3 text-muted small">{{ $c['category_name'] }}</td>
                                <td>
                                    @if($c['is_current'])
                                        <span class="fw-bold text-primary">{{ $c['complaint_name'] }}</span>
                                    @else
                                        <span class="fw-medium text-dark">{{ $c['complaint_name'] }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($c['technician_name'])
                                        @if(!$c['is_current'])
                                            <a href="{{ route('property::maintenance::complaint', $c['id']) }}" class="text-decoration-none small">
                                                {{ $c['technician_name'] }}
                                            </a>
                                        @else
                                            <span class="small fw-semibold">{{ $c['technician_name'] }}</span>
                                        @endif
                                    @else
                                        <span class="text-muted small fst-italic">-</span>
                                    @endif
                                </td>
                                <td class="text-muted small">{{ \Illuminate\Support\Str::limit($c['technician_remark'], 40) }}</td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $c['status_color'] }} bg-opacity-10 text-{{ $c['status_color'] }} rounded-pill" style="font-size: 0.68rem;">
                                        {{ $c['status_label'] }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Section 4: Supply Items + Notes + Attachments (Shared Partial) --}}
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
    <div class="card border-0 shadow-sm">
        <div class="card-body p-3">
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <a href="{{ route('property::maintenance::edit', $propertyInfo['registration_id']) }}" class="btn btn-light btn-sm d-flex align-items-center gap-1 rounded-pill px-3">
                    <i class="fa fa-arrow-left"></i><span>Back</span>
                </a>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    @if(!in_array($maintenanceComplaint?->status?->value, ['completed', 'cancelled']))
                        <button type="button" wire:click="save('pending')" class="btn btn-primary btn-sm d-flex align-items-center gap-1 rounded-pill px-3 shadow-sm">
                            <i class="fa fa-save"></i><span>Save</span>
                        </button>
                        @can('maintenance.complete')
                            <button type="button" wire:click="save('completed')"
                                wire:confirm="Complete this complaint? This action cannot be undone."
                                class="btn btn-success btn-sm d-flex align-items-center gap-1 rounded-pill px-3 shadow-sm">
                                <i class="fa fa-check-circle"></i><span>Complete</span>
                            </button>
                        @endcan
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                $(document).on('keypress', '#supply_barcode, .single_cart', function(e) {
                    if (e.keyCode === 13) { $('#supply_add_cart').click(); return false; }
                });
                window.addEventListener('barcodeAutofocus', event => { $('#supply_barcode').select(); });
                window.addEventListener('openProductSelectBox', event => {
                    var el = document.querySelector('#supply_product_id');
                    if (el && el.tomselect) el.tomselect.focus();
                });
                $('#item_branch_id').on('change', function() { @this.set('item.branch_id', $(this).val()); });
                $('#supply_product_id').on('change', function() {
                    @this.set('item.product_id', $(this).val());
                    if ($(this).val()) $('#supply_item_quantity').select();
                });
            });
        </script>
    @endpush
</div>

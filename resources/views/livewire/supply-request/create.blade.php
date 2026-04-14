@php
    use App\Enums\SupplyRequest\SupplyRequestStatus;

    $currentStatusRaw = $supply_request['status'] ?? SupplyRequestStatus::REQUIREMENT->value;
    $currentStatusEnum = $currentStatusRaw instanceof SupplyRequestStatus ? $currentStatusRaw : SupplyRequestStatus::tryFrom($currentStatusRaw);
    $currentStatus = $currentStatusRaw instanceof SupplyRequestStatus ? $currentStatusRaw->value : $currentStatusRaw;
    $statusColor = $currentStatusEnum?->color() ?? 'secondary';
    $statusIcon = $currentStatusEnum?->icon() ?? 'fa-info-circle';
    $isEditing = isset($supply_request['id']);
@endphp
<div>
    <form wire:submit.prevent="save" enctype="multipart/form-data">

        {{-- Validation Errors --}}
        @if ($this->getErrorBag()->count())
            <div class="alert alert-danger alert-dismissible fade show mb-3 border-0 shadow-sm" role="alert">
                <div class="d-flex align-items-start gap-2">
                    <i class="fa fa-exclamation-triangle mt-1"></i>
                    <ul class="mb-0 small">
                        @foreach ($this->getErrorBag()->toArray() as $value)
                            <li>{{ $value[0] }}</li>
                        @endforeach
                    </ul>
                </div>
                <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- ══════════════════════════════════════════════════════════════
             HERO SECTION - Status Banner + Workflow
             ══════════════════════════════════════════════════════════════ --}}
        <div class="card border-0 shadow-sm overflow-hidden mb-3">
            {{-- Header bar --}}
            <div class="card-body p-3 pb-2">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div class="d-flex align-items-center">
                        <div class="d-flex align-items-center justify-content-center rounded-3 shadow-sm bg-{{ $statusColor }} bg-gradient text-white me-3"
                            style="width: 42px; height: 42px; min-width: 42px;">
                            <i class="fa fa-truck" style="font-size: 1.1rem;"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 fw-bold text-dark">
                                Supply Request
                                @if ($isEditing)
                                    <span class="text-muted fw-normal fs-6">#{{ $supply_request['order_no'] ?? $supply_request['id'] }}</span>
                                @endif
                            </h5>
                            <span class="badge bg-{{ $statusColor }} bg-gradient rounded-pill px-3 mt-1 shadow-sm" style="font-size: 0.72rem;">
                                <i class="fa {{ $statusIcon }} me-1"></i>{{ ucfirst($currentStatus) }}
                            </span>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-1">
                        @if ($table_id)
                            <a href="{{ route('supply-request::print', ['id' => $table_id, 'mode' => 'Invoice']) }}" target="_blank"
                                class="btn btn-sm btn-outline-info d-flex align-items-center gap-1 rounded-pill px-3">
                                <i class="fa fa-file-pdf-o"></i><span class="d-none d-md-inline">Invoice</span>
                            </a>
                            <a href="{{ route('supply-request::print', ['id' => $table_id, 'mode' => 'Work Order Form']) }}" target="_blank"
                                class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1 rounded-pill px-3">
                                <i class="fa fa-print"></i><span class="d-none d-md-inline">Work Order</span>
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Workflow Stepper --}}
            @php
                $workflow = [
                    [
                        'label' => 'Created',
                        'value' => $supply_request['creator'] ?? '',
                        'time' => $supply_request['created_at_formatted'] ?? '',
                        'icon' => 'fa-user-plus',
                        'color' => 'primary',
                    ],
                    [
                        'label' => $currentStatus === SupplyRequestStatus::REJECTED->value ? 'Rejected' : 'Approved',
                        'value' => $supply_request['approver'] ?? '',
                        'time' => $supply_request['approved_at_formatted'] ?? '',
                        'icon' => $currentStatus === SupplyRequestStatus::REJECTED->value ? 'fa-times' : 'fa-thumbs-up',
                        'color' => $currentStatus === SupplyRequestStatus::REJECTED->value ? 'danger' : 'info',
                    ],
                    [
                        'label' => 'Accounted',
                        'value' => $supply_request['accountant'] ?? '',
                        'time' => $supply_request['accounted_at_formatted'] ?? '',
                        'icon' => 'fa-calculator',
                        'color' => 'warning',
                    ],
                    [
                        'label' => 'Final Approved',
                        'value' => $supply_request['final_approver'] ?? '',
                        'time' => $supply_request['final_approved_at_formatted'] ?? '',
                        'icon' => 'fa-shield',
                        'color' => 'success',
                    ],
                    [
                        'label' => 'Completed',
                        'value' => $supply_request['completer'] ?? '',
                        'time' => $supply_request['completed_at_formatted'] ?? '',
                        'icon' => 'fa-flag-checkered',
                        'color' => 'dark',
                    ],
                ];
                // Find the last completed step index
                $lastCompletedIdx = -1;
                foreach ($workflow as $idx => $s) {
                    if ($s['value']) {
                        $lastCompletedIdx = $idx;
                    }
                }
            @endphp
            <div class="border-top bg-white px-3 py-3">
                <div class="d-flex align-items-start justify-content-between position-relative">
                    {{-- Connector line --}}
                    <div class="position-absolute d-none d-md-block" style="top: 18px; left: 36px; right: 36px; height: 2px; z-index: 0;">
                        <div class="w-100 rounded" style="height: 2px; background: linear-gradient(90deg, #dee2e6 0%, #dee2e6 100%);"></div>
                        @if ($lastCompletedIdx >= 0)
                            <div class="rounded position-absolute top-0 start-0"
                                style="height: 2px; width: {{ ($lastCompletedIdx / (count($workflow) - 1)) * 100 }}%; background: linear-gradient(90deg, #0d6efd, #198754);">
                            </div>
                        @endif
                    </div>

                    @foreach ($workflow as $i => $step)
                        @php
                            $isCompleted = !empty($step['value']);
                            $isCurrent = $i === $lastCompletedIdx;
                        @endphp
                        <div class="d-flex flex-column align-items-center text-center position-relative" style="z-index: 1; flex: 1; min-width: 0;">
                            {{-- Step circle --}}
                            <div class="d-flex align-items-center justify-content-center rounded-circle shadow-sm mb-2 {{ $isCurrent ? 'ring-pulse' : '' }}"
                                style="width: 36px; height: 36px; min-width: 36px;
                                    {{ $isCompleted
                                        ? 'background: var(--bs-' . $step['color'] . '); color: white;'
                                        : 'background: #f1f3f5; color: #adb5bd; border: 2px solid #dee2e6;' }}">
                                @if ($isCompleted)
                                    <i class="fa fa-check" style="font-size: 0.8rem;"></i>
                                @else
                                    <i class="fa {{ $step['icon'] }}" style="font-size: 0.7rem;"></i>
                                @endif
                            </div>

                            {{-- Step label --}}
                            <div class="fw-semibold text-nowrap {{ $isCompleted ? 'text-dark' : 'text-muted' }}" style="font-size: 0.7rem;">
                                {{ $step['label'] }}
                            </div>

                            {{-- User & time --}}
                            @if ($isCompleted)
                                <div class="fw-bold text-{{ $step['color'] }} text-truncate w-100 px-1" style="font-size: 0.68rem; max-width: 120px;"
                                    title="{{ $step['value'] }}">
                                    {{ $step['value'] }}
                                </div>
                                @if ($step['time'])
                                    <div class="text-muted d-none d-lg-block" style="font-size: 0.58rem;">
                                        {{ $step['time'] }}
                                    </div>
                                @endif
                            @else
                                <div class="text-muted" style="font-size: 0.65rem;">—</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <style>
            .ring-pulse {
                animation: ringPulse 2s ease-in-out infinite;
            }

            @keyframes ringPulse {

                0%,
                100% {
                    box-shadow: 0 0 0 0 rgba(var(--bs-primary-rgb), 0.3);
                }

                50% {
                    box-shadow: 0 0 0 6px rgba(var(--bs-primary-rgb), 0);
                }
            }
        </style>

        {{-- ══════════════════════════════════════════════════════════════
             PROPERTY & DETAILS (Two-section card)
             ══════════════════════════════════════════════════════════════ --}}
        <div class="card border-0 shadow-sm mb-3">
            {{-- Section Header --}}
            <div class="card-header bg-white border-bottom py-2 px-3">
                <div class="d-flex align-items-center">
                    <span class="d-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-10 me-2"
                        style="width: 30px; height: 30px;">
                        <i class="fa fa-map-marker text-primary" style="font-size: 0.85rem;"></i>
                    </span>
                    <h6 class="mb-0 fw-bold text-dark">Property & Details</h6>
                </div>
            </div>

            <div class="card-body p-3">
                {{-- Property Selection (inside bordered panel) --}}
                <div class="rounded-3 border p-3 mb-3" style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);">
                    {{-- Row 1: Group + Building --}}
                    <div class="row g-2 g-lg-3 mb-2">
                        <div class="col-12 col-sm-6" wire:ignore>
                            <label class="form-label fw-semibold small mb-1 text-secondary">
                                <i class="fa fa-folder-open me-1" style="font-size: 0.7rem;"></i> Group/Project
                            </label>
                            {{ html()->select('property_group_id', $preFilledDropDowns['group'] ?? [])->value($supply_request['property_group_id'] ?? '')->class('select-property_group_id')->id('property_group_id')->placeholder('Select Group') }}
                        </div>
                        <div class="col-12 col-sm-6" wire:ignore>
                            <label class="form-label fw-semibold small mb-1 text-secondary">
                                <i class="fa fa-building me-1" style="font-size: 0.7rem;"></i> Building
                            </label>
                            {{ html()->select('property_building_id', $preFilledDropDowns['building'] ?? [])->value($supply_request['property_building_id'] ?? '')->class('select-property_building_id')->id('property_building_id')->placeholder('Select Building')->attribute('data-group-select', '#property_group_id') }}
                        </div>
                    </div>
                    {{-- Row 2: Type + Unit + Status --}}
                    <div class="row g-2 g-lg-3">
                        <div class="col-12 col-sm-4" wire:ignore>
                            <label class="form-label fw-semibold small mb-1 text-secondary">
                                <i class="fa fa-tag me-1" style="font-size: 0.7rem;"></i> Type
                            </label>
                            {{ html()->select('property_type_id', $preFilledDropDowns['type'] ?? [])->value($supply_request['property_type_id'] ?? '')->class('select-property_type_id')->id('property_type_id')->placeholder('Select Type') }}
                        </div>
                        <div class="col-12 col-sm-8">
                            <label class="form-label fw-semibold small mb-1 text-primary">
                                <i class="fa fa-home me-1" style="font-size: 0.7rem;"></i> Property No / Unit *
                            </label>
                            <div wire:ignore>
                                {{ html()->select('property_id', $preFilledDropDowns['property'] ?? [])->value($supply_request['property_id'] ?? '')->class('select-property_id')->id('property_id')->required(true)->placeholder('Search Here')->attribute('data-building-select', '#property_building_id')->attribute('data-group-select', '#property_group_id')->attribute('data-type-select', '#property_type_id') }}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Order Details --}}
                <div class="row g-2 g-lg-3">
                    <div class="col-6 col-sm-6 col-lg-3">
                        <label class="form-label fw-semibold small mb-1 text-secondary">
                            <i class="fa fa-calendar me-1" style="font-size: 0.7rem;"></i> Date *
                        </label>
                        <input type="date" class="form-control form-control-sm" wire:model="supply_request.date">
                    </div>
                    <div class="col-6 col-sm-6 col-lg-2">
                        <label class="form-label fw-semibold small mb-1 text-secondary">
                            <i class="fa fa-hashtag me-1" style="font-size: 0.7rem;"></i> Order No
                        </label>
                        <input type="text" class="form-control form-control-sm bg-light" wire:model="supply_request.order_no" readonly>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <label class="form-label fw-semibold small mb-1 text-secondary">
                            <i class="fa fa-user me-1" style="font-size: 0.7rem;"></i> Requested By
                        </label>
                        <input type="text" class="form-control form-control-sm" wire:model="supply_request.contact_person">
                    </div>
                    <div class="col-12 col-sm-6 col-lg-4">
                        <label class="form-label fw-semibold small mb-1 text-secondary">
                            <i class="fa fa-comment me-1" style="font-size: 0.7rem;"></i> Remarks
                        </label>
                        <input type="text" class="form-control form-control-sm" wire:model="supply_request.remarks"
                            placeholder="Enter remarks...">
                    </div>
                </div>
            </div>
        </div>

        @include('partials.supply-request.items-notes-attachments', [
            'items' => $items,
            'item' => $item,
            'notes' => $notes,
            'note' => $note,
            'imageList' => $imageList,
            'branches' => $branches,
            'isCompleted' => $status === SupplyRequestStatus::COMPLETED->value,
            'showTotals' => true,
            'showPayment' => true,
            'supply_request' => $supply_request,
            'paymentMethods' => $paymentMethods,
            'payment_mode_id' => $payment_mode_id,
        ])

        {{-- ══════════════════════════════════════════════════════════════
             ACTION BUTTONS
             ══════════════════════════════════════════════════════════════ --}}
        <div class="card border-0 shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                    <a href="{{ route('supply-request::index') }}" class="btn btn-light btn-sm d-flex align-items-center gap-1 rounded-pill px-3">
                        <i class="fa fa-arrow-left"></i><span>Back</span>
                    </a>

                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        {{-- Reject --}}
                        @if ($isEditing)
                            @if (
                                (!($supply_request['approved_by'] ?? null) && $currentStatus === SupplyRequestStatus::REQUIREMENT->value) ||
                                    $currentStatus === SupplyRequestStatus::APPROVED->value)
                                @can('supply request.approve')
                                    <button type="button" wire:click="statusChange('{{ SupplyRequestStatus::REJECTED->value }}')"
                                        class="btn btn-warning btn-sm d-flex align-items-center gap-1 rounded-pill px-3 shadow-sm"
                                        wire:confirm="Are you sure you want to reject?">
                                        <i class="fa fa-times-circle"></i><span>Reject</span>
                                    </button>
                                @endcan
                            @endif
                        @endif

                        {{-- Status progression --}}
                        @if ($isEditing)
                            @if (!($supply_request['approved_by'] ?? null) && $currentStatus === SupplyRequestStatus::REQUIREMENT->value)
                                @can('supply request.approve')
                                    <button type="button" wire:click="statusChange('{{ SupplyRequestStatus::APPROVED->value }}')"
                                        class="btn btn-info btn-sm d-flex align-items-center gap-1 rounded-pill px-3 shadow-sm"
                                        wire:confirm="Approve this request?">
                                        <i class="fa fa-check"></i><span>Approve</span>
                                    </button>
                                @endcan
                            @elseif ($currentStatus === SupplyRequestStatus::COLLECTED->value && !($supply_request['final_approved_by'] ?? null))
                                @can('supply request.final approve')
                                    <button type="button" wire:click="statusChange('{{ SupplyRequestStatus::FINAL_APPROVED->value }}')"
                                        class="btn btn-primary btn-sm d-flex align-items-center gap-1 rounded-pill px-3 shadow-sm"
                                        wire:confirm="Final approve this request?">
                                        <i class="fa fa-shield"></i><span>Final Approve</span>
                                    </button>
                                @endcan
                            @elseif ($currentStatus === SupplyRequestStatus::FINAL_APPROVED->value && !($supply_request['completed_by'] ?? null))
                                @can('supply request.complete')
                                    <button type="button" wire:click="statusChange('{{ SupplyRequestStatus::COMPLETED->value }}')"
                                        class="btn btn-success btn-sm d-flex align-items-center gap-1 rounded-pill px-3 shadow-sm"
                                        wire:confirm="Complete this request?">
                                        <i class="fa fa-flag-checkered"></i><span>Complete</span>
                                    </button>
                                @endcan
                            @endif
                        @endif

                        {{-- Save --}}
                        @if (($isEditing && auth()->user()->can('supply request.edit')) || (!$isEditing && auth()->user()->can('supply request.create')))
                            @if ($status !== SupplyRequestStatus::COMPLETED->value)
                                <button type="submit" class="btn btn-success btn-sm d-flex align-items-center gap-1 rounded-pill px-4 shadow-sm">
                                    <i class="fa fa-save"></i><span>Save</span>
                                </button>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </form>

    @push('scripts')
        <script>
            $(document).ready(function() {
                function clearAndReload(id) {
                    var el = document.getElementById(id);
                    if (el && el.tomselect) {
                        el.tomselect.clear();
                        el.tomselect.clearOptions();
                        el.tomselect.load('');
                    }
                }
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

                $('#property_group_id').on('change', function() {
                    @this.set('supply_request.property_group_id', $(this).val());
                    clearAndReload('property_building_id');
                    clearAndReload('property_id');
                    @this.set('supply_request.property_building_id', '');
                    @this.set('supply_request.property_id', '');
                });
                $('#property_building_id').on('change', function() {
                    @this.set('supply_request.property_building_id', $(this).val());
                    clearAndReload('property_id');
                    @this.set('supply_request.property_id', '');
                });
                $('#property_type_id').on('change', function() {
                    @this.set('supply_request.property_type_id', $(this).val());
                    clearAndReload('property_id');
                    @this.set('supply_request.property_id', '');
                });
                $('#property_id').on('change', function() {
                    var propertyId = $(this).val();
                    @this.set('supply_request.property_id', propertyId);
                    if (propertyId) {
                        @this.getPropertyDetails(parseInt(propertyId)).then(function(data) {
                            if (!data || !data.property_group_id) return;

                            // Reverse-fill Group
                            var groupEl = document.getElementById('property_group_id');
                            if (groupEl && groupEl.tomselect) {
                                groupEl.tomselect.addOption({
                                    id: data.property_group_id,
                                    name: data.property_group_name
                                });
                                groupEl.tomselect.setValue(data.property_group_id, true);
                            }

                            // Reverse-fill Building
                            var buildingEl = document.getElementById('property_building_id');
                            if (buildingEl && buildingEl.tomselect) {
                                buildingEl.tomselect.addOption({
                                    id: data.property_building_id,
                                    name: data.property_building_name
                                });
                                buildingEl.tomselect.setValue(data.property_building_id, true);
                            }

                            // Reverse-fill Type
                            var typeEl = document.getElementById('property_type_id');
                            if (typeEl && typeEl.tomselect) {
                                typeEl.tomselect.addOption({
                                    id: data.property_type_id,
                                    name: data.property_type_name
                                });
                                typeEl.tomselect.setValue(data.property_type_id, true);
                            }
                        });
                    }
                });
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

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
            <div class="card-body p-0">
                {{-- Status bar --}}
                <div class="d-flex align-items-center p-3 bg-{{ $statusColor }} bg-opacity-10 border-start border-4 border-{{ $statusColor }}">
                    <div class="d-flex align-items-center justify-content-center rounded-circle shadow-sm bg-{{ $statusColor }} text-white me-3"
                        style="width: 44px; height: 44px; min-width: 44px;">
                        <i class="fa fa-truck"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <div>
                                <h5 class="mb-0 fw-bold text-dark">
                                    Supply Request
                                    @if ($isEditing)
                                        <span class="text-muted fw-normal fs-6">#{{ $supply_request['order_no'] ?? $supply_request['id'] }}</span>
                                    @endif
                                </h5>
                                <span class="badge bg-{{ $statusColor }} rounded-pill px-3 mt-1" style="font-size: 0.72rem;">
                                    <i class="fa {{ $statusIcon }} me-1"></i>{{ $currentStatus }}
                                </span>
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
                </div>

                {{-- Workflow Steps --}}
                <div class="px-3 py-2 bg-white border-top">
                    <div class="d-flex flex-wrap gap-2">
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
                                    'label' => 'Final',
                                    'value' => $supply_request['final_approver'] ?? '',
                                    'time' => $supply_request['final_approved_at_formatted'] ?? '',
                                    'icon' => 'fa-shield',
                                    'color' => 'success',
                                ],
                                [
                                    'label' => 'Done',
                                    'value' => $supply_request['completer'] ?? '',
                                    'time' => $supply_request['completed_at_formatted'] ?? '',
                                    'icon' => 'fa-flag-checkered',
                                    'color' => 'dark',
                                ],
                            ];
                        @endphp
                        @foreach ($workflow as $i => $step)
                            <div class="d-flex align-items-center gap-1 {{ $step['value'] ? 'bg-' . $step['color'] . ' bg-opacity-10' : 'bg-light' }} rounded-3 px-2 py-1 border"
                                style="font-size: 0.72rem;">
                                <span
                                    class="d-flex align-items-center justify-content-center rounded-circle bg-{{ $step['value'] ? $step['color'] : 'secondary' }} bg-opacity-{{ $step['value'] ? '25' : '10' }} text-{{ $step['value'] ? $step['color'] : 'muted' }}"
                                    style="width: 20px; height: 20px; min-width: 20px; font-size: 0.55rem;">
                                    <i class="fa {{ $step['icon'] }}"></i>
                                </span>
                                <div class="d-flex flex-column lh-1">
                                    <span class="text-muted" style="font-size: 0.62rem;">{{ $step['label'] }}</span>
                                    <span class="fw-semibold {{ $step['value'] ? 'text-dark' : 'text-muted' }}">{{ $step['value'] ?: '-' }}</span>
                                    @if ($step['time'])
                                        <span class="text-muted" style="font-size: 0.58rem;">
                                            <i class="fa fa-clock-o me-1"></i>{{ $step['time'] }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                            @if (!$loop->last)
                                <i class="fa fa-chevron-right text-muted d-none d-sm-inline align-self-center"
                                    style="font-size: 0.55rem; opacity: 0.4;"></i>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

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

        {{-- ══════════════════════════════════════════════════════════════
             ITEMS
             ══════════════════════════════════════════════════════════════ --}}
        <div class="card border-0 shadow-sm mb-3" style="overflow: visible;">
            {{-- Section Header --}}
            <div class="card-header bg-white border-bottom py-2 px-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <span class="d-flex align-items-center justify-content-center rounded-circle bg-info bg-opacity-10 me-2"
                            style="width: 30px; height: 30px;">
                            <i class="fa fa-cubes text-info" style="font-size: 0.85rem;"></i>
                        </span>
                        <h6 class="mb-0 fw-bold text-dark">Items</h6>
                    </div>
                    @if (count($items))
                        <span class="badge bg-info bg-opacity-10 text-info rounded-pill px-3 py-1 fw-semibold">
                            {{ count($items) }} {{ Str::plural('item', count($items)) }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="card-body p-0" style="overflow: visible;">
                {{-- Add Item Panel --}}
                @if ($status !== SupplyRequestStatus::COMPLETED->value)
                    <div class="border-bottom p-3" style="background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 50%, #f0f9ff 100%);">
                        <div class="row g-2 align-items-end">
                            {{-- Row 1: Store + Product (full width) --}}
                            <div class="col-12 col-sm-3 col-lg-2">
                                <label class="form-label fw-semibold small mb-1 text-secondary">
                                    <i class="fa fa-home me-1" style="font-size: 0.65rem;"></i> Store
                                </label>
                                <select wire:model="item.branch_id" class="form-select form-select-sm">
                                    @foreach ($branches as $branchId => $branchName)
                                        <option value="{{ $branchId }}">{{ $branchName }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-sm-9 col-lg-10" style="overflow: visible;">
                                <label class="form-label fw-semibold small mb-1 text-secondary">
                                    <i class="fa fa-cube me-1" style="font-size: 0.65rem;"></i> Asset / Product
                                </label>
                                <div class="d-flex gap-1">
                                    <input type="text" wire:model.live="item.barcode" class="form-control form-control-sm"
                                        style="max-width: 120px; min-width: 100px;" placeholder="Barcode..." id="supply_barcode" autofocus>
                                    <div class="flex-grow-1" wire:ignore>
                                        <select class="select-product_id-list" id="supply_product_id" style="width:100%">
                                            <option value="">Select Asset</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {{-- Row 2: Mode, Qty, Price, Total, Add --}}
                        <div class="row g-2 align-items-end mt-0">
                            {{-- Mode --}}
                            <div class="col-4 col-sm-3 col-lg-2">
                                <label class="form-label fw-semibold small mb-1 text-secondary">Mode</label>
                                <select wire:model="item.mode" class="form-select form-select-sm">
                                    <option value="New">New</option>
                                    <option value="Damaged">Damaged</option>
                                </select>
                            </div>
                            {{-- Qty --}}
                            <div class="col-4 col-sm-2 col-lg-2">
                                <label class="form-label fw-semibold small mb-1 text-secondary">Qty</label>
                                <input type="number" wire:model="item.quantity" class="form-control form-control-sm text-end" step="any"
                                    id="supply_item_quantity">
                            </div>
                            {{-- Price --}}
                            <div class="col-4 col-sm-2 col-lg-2">
                                <label class="form-label fw-semibold small mb-1 text-secondary">Price</label>
                                <input type="number" wire:model="item.unit_price" class="form-control form-control-sm text-end" step="any">
                            </div>
                            {{-- Total --}}
                            <div class="col-6 col-sm-2 col-lg-2">
                                <label class="form-label fw-semibold small mb-1 text-secondary">Total</label>
                                <input type="number" wire:model="item.total" class="form-control form-control-sm text-end bg-white fw-semibold"
                                    step="any" readonly>
                            </div>
                            {{-- Add --}}
                            <div class="col-6 col-sm-3 col-lg-2 d-grid">
                                <button type="button" tabindex="-1" class="btn btn-success btn-sm shadow-sm" id="supply_add_cart"
                                    wire:click="addCart">
                                    <i class="fa fa-plus me-1"></i> Add Item
                                </button>
                            </div>
                        </div>
                        {{-- Remarks --}}
                        <div class="mt-2">
                            <input type="text" wire:model="item.remarks" class="form-control form-control-sm bg-white"
                                placeholder="Item remarks (optional)..." style="border-style: dashed;">
                        </div>
                    </div>
                @endif

                {{-- Items Table --}}
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle mb-0">
                        <thead>
                            <tr style="background: #f8fafc;">
                                <th class="fw-semibold ps-3 small text-uppercase text-muted border-0"
                                    style="font-size: 0.7rem; letter-spacing: 0.5px;">Store</th>
                                <th class="fw-semibold small text-uppercase text-muted border-0" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                                    Asset</th>
                                <th class="fw-semibold small text-uppercase text-muted border-0" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                                    Mode</th>
                                <th class="fw-semibold text-end small text-uppercase text-muted border-0"
                                    style="font-size: 0.7rem; letter-spacing: 0.5px;">Qty</th>
                                <th class="fw-semibold text-end small text-uppercase text-muted border-0"
                                    style="font-size: 0.7rem; letter-spacing: 0.5px;">Price</th>
                                <th class="fw-semibold text-end small text-uppercase text-muted border-0"
                                    style="font-size: 0.7rem; letter-spacing: 0.5px;">Total</th>
                                @if ($status !== SupplyRequestStatus::COMPLETED->value)
                                    <th class="fw-semibold text-center small text-uppercase text-muted border-0" width="70"
                                        style="font-size: 0.7rem;"></th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($items as $key => $value)
                                <tr wire:key="item-row-{{ $key }}" class="{{ $loop->even ? '' : 'bg-white' }}">
                                    @if (!($value['edit_flag'] ?? false))
                                        <td class="ps-3 text-muted small">{{ $value['branch_name'] ?? '' }}</td>
                                    @else
                                        <td class="ps-3">
                                            <select wire:model="items.{{ $key }}.branch_id" class="form-select form-select-sm"
                                                style="min-width: 100px;">
                                                @foreach ($branches as $branchId => $branchName)
                                                    <option value="{{ $branchId }}">{{ $branchName }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                    @endif
                                    <td>
                                        <span class="fw-medium text-dark">{{ $value['product_name'] ?? '' }}</span>
                                    </td>
                                    <td>
                                        <span
                                            class="badge bg-{{ ($value['mode'] ?? '') === 'New' ? 'success' : 'warning' }} bg-opacity-10 text-{{ ($value['mode'] ?? '') === 'New' ? 'success' : 'warning' }} rounded-pill"
                                            style="font-size: 0.68rem;">
                                            {{ $value['mode'] ?? '' }}
                                        </span>
                                    </td>
                                    @if (!($value['edit_flag'] ?? false))
                                        <td class="text-end">{{ $value['quantity'] }}</td>
                                        <td class="text-end text-muted">{{ currency($value['unit_price']) }}</td>
                                        <td class="text-end fw-semibold text-primary">{{ currency($value['total']) }}</td>
                                    @else
                                        <td><input type="number" wire:model="items.{{ $key }}.quantity"
                                                class="form-control form-control-sm text-end" wire:change="cartCalculator('{{ $key }}')"
                                                step="any" style="width: 80px;"></td>
                                        <td><input type="number" wire:model="items.{{ $key }}.unit_price"
                                                class="form-control form-control-sm text-end" wire:change="cartCalculator('{{ $key }}')"
                                                step="any" style="width: 90px;"></td>
                                        <td><input type="number" wire:model="items.{{ $key }}.total"
                                                class="form-control form-control-sm text-end fw-semibold" readonly style="width: 90px;"></td>
                                    @endif
                                    @if ($status !== SupplyRequestStatus::COMPLETED->value)
                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                @can('supply request.edit')
                                                    @if ($value['edit_flag'] ?? false)
                                                        <button type="button"
                                                            class="btn btn-sm btn-success rounded-circle p-0 d-flex align-items-center justify-content-center"
                                                            style="width: 26px; height: 26px;" wire:click="editCartItem({{ $key }})"
                                                            title="Save">
                                                            <i class="fa fa-check" style="font-size: 0.65rem;"></i>
                                                        </button>
                                                    @else
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline-primary rounded-circle p-0 d-flex align-items-center justify-content-center"
                                                            style="width: 26px; height: 26px;" wire:click="editCartItem({{ $key }})"
                                                            title="Edit">
                                                            <i class="fa fa-pencil" style="font-size: 0.65rem;"></i>
                                                        </button>
                                                    @endif
                                                @endcan
                                                @can('supply request.delete item')
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-danger rounded-circle p-0 d-flex align-items-center justify-content-center"
                                                        style="width: 26px; height: 26px;" wire:click="deleteItem({{ $key }})"
                                                        wire:confirm="Delete this item?" title="Delete">
                                                        <i class="fa fa-trash" style="font-size: 0.6rem;"></i>
                                                    </button>
                                                @endcan
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                                @if (!($value['edit_flag'] ?? false) && ($value['remarks'] ?? ''))
                                    <tr>
                                        <td colspan="7" class="ps-3 text-muted small fst-italic border-0 pt-0 pb-1" style="font-size: 0.75rem;">
                                            <i class="fa fa-quote-left me-1" style="font-size: 0.55rem; opacity: 0.4;"></i>{{ $value['remarks'] }}
                                        </td>
                                    </tr>
                                @endif
                                @if ($value['edit_flag'] ?? false)
                                    <tr>
                                        <td colspan="7" class="ps-3 border-0 pt-0 pb-1">
                                            <input type="text" wire:model="items.{{ $key }}.remarks"
                                                class="form-control form-control-sm" placeholder="Item remarks..." style="border-style: dashed;">
                                        </td>
                                    </tr>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-light mb-2"
                                            style="width: 50px; height: 50px;">
                                            <i class="fa fa-inbox text-muted" style="font-size: 1.3rem; opacity: 0.4;"></i>
                                        </div>
                                        <div class="text-muted small">No items added yet</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════════
             NOTES + TOTALS
             ══════════════════════════════════════════════════════════════ --}}
        <div class="row g-3 mb-3">
            {{-- Notes --}}
            <div class="col-12 col-lg-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom py-2 px-3">
                        <div class="d-flex align-items-center">
                            <span class="d-flex align-items-center justify-content-center rounded-circle bg-warning bg-opacity-10 me-2"
                                style="width: 30px; height: 30px;">
                                <i class="fa fa-sticky-note text-warning" style="font-size: 0.85rem;"></i>
                            </span>
                            <h6 class="mb-0 fw-bold text-dark">Notes</h6>
                            @if (count($notes))
                                <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill ms-2 px-2"
                                    style="font-size: 0.68rem;">{{ count($notes) }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="card-body p-0">
                        @if ($status !== SupplyRequestStatus::COMPLETED->value)
                            <div class="p-2 bg-light border-bottom">
                                <div class="input-group input-group-sm">
                                    <input type="text" wire:model="note" class="form-control" placeholder="Add a note...">
                                    <button type="button" class="btn btn-success px-3" wire:click="addNote">
                                        <i class="fa fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        @endif
                        @if (count($notes))
                            <div class="list-group list-group-flush">
                                @foreach ($notes as $key => $noteItem)
                                    <div
                                        class="list-group-item d-flex justify-content-between align-items-start py-2 px-3 border-start-0 border-end-0">
                                        <div class="small">
                                            <span class="fw-semibold text-dark">{{ $noteItem['creator'] }}</span>
                                            <span class="text-muted mx-1">-</span>
                                            <span class="text-dark">{{ $noteItem['note'] }}</span>
                                            <div class="text-muted mt-1" style="font-size: 0.68rem;">
                                                <i class="fa fa-clock-o me-1"></i>{{ $noteItem['created_at'] }}
                                            </div>
                                        </div>
                                        @if ($noteItem['delete_flag'] ?? false)
                                            @can('supply request.delete item')
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-danger rounded-circle p-0 flex-shrink-0 d-flex align-items-center justify-content-center"
                                                    style="width: 24px; height: 24px;" wire:click="deleteNote({{ $key }})"><i
                                                        class="fa fa-trash" style="font-size: 0.6rem;"></i></button>
                                            @endcan
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="p-4 text-center">
                                <i class="fa fa-comment-o text-muted d-block mb-1" style="font-size: 1.5rem; opacity: 0.3;"></i>
                                <span class="text-muted small">No notes yet</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Summary --}}
            <div class="col-12 col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom py-2 px-3">
                        <div class="d-flex align-items-center">
                            <span class="d-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10 me-2"
                                style="width: 30px; height: 30px;">
                                <i class="fa fa-calculator text-success" style="font-size: 0.85rem;"></i>
                            </span>
                            <h6 class="mb-0 fw-bold text-dark">Summary</h6>
                        </div>
                    </div>
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <span class="text-muted small fw-semibold">Subtotal</span>
                            <span class="fw-bold text-dark">{{ currency($supply_request['total'] ?? 0) }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <span class="text-muted small fw-semibold">Other Charges</span>
                            <input type="number" wire:model="supply_request.other_charges" class="form-control form-control-sm text-end"
                                style="max-width: 120px;" step="any">
                        </div>
                        <div class="d-flex justify-content-between align-items-center pt-3 pb-1">
                            <span class="fw-bold text-dark">Grand Total</span>
                            <span class="fw-bold text-success fs-5">{{ currency($supply_request['grand_total'] ?? 0) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════════
             ATTACHMENTS
             ══════════════════════════════════════════════════════════════ --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom py-2 px-3">
                <div class="d-flex align-items-center">
                    <span class="d-flex align-items-center justify-content-center rounded-circle bg-secondary bg-opacity-10 me-2"
                        style="width: 30px; height: 30px;">
                        <i class="fa fa-paperclip text-secondary" style="font-size: 0.85rem;"></i>
                    </span>
                    <h6 class="mb-0 fw-bold text-dark">Attachments</h6>
                    @if (count($imageList))
                        <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill ms-2 px-2"
                            style="font-size: 0.68rem;">{{ count($imageList) }}</span>
                    @endif
                </div>
            </div>
            <div class="card-body p-3">
                @if ($status !== SupplyRequestStatus::COMPLETED->value)
                    <div class="border rounded-3 p-3 mb-2 text-center bg-light"
                        style="border-style: dashed !important; border-color: #adb5bd !important;">
                        <i class="fa fa-cloud-upload text-muted d-block mb-1" style="font-size: 1.5rem; opacity: 0.5;"></i>
                        <small class="text-muted d-block mb-2">Upload images, PDFs, videos or documents</small>
                        <input type="file" wire:model="images" class="form-control form-control-sm mx-auto" style="max-width: 400px;" multiple
                            accept="image/*,video/*,application/pdf,.doc,.docx,.xls,.xlsx">
                    </div>
                @endif
                @if (count($imageList))
                    <div class="row g-2 mt-1">
                        @foreach ($imageList as $key => $single)
                            <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                                <div class="border rounded-3 p-2 position-relative text-center bg-white h-100 shadow-sm">
                                    @if (!($single['file_exists'] ?? true))
                                        {{-- File missing from disk --}}
                                        <div class="d-flex align-items-center justify-content-center rounded bg-light mb-1" style="height: 80px;">
                                            <div class="text-center">
                                                <i class="fa fa-exclamation-triangle text-warning d-block" style="font-size: 1.5rem; opacity: 0.5;"></i>
                                                <small class="text-muted" style="font-size: 0.6rem;">File missing</small>
                                            </div>
                                        </div>
                                    @elseif ($single['is_video'] ?? false)
                                        <div class="d-flex align-items-center justify-content-center rounded bg-dark bg-opacity-10 mb-1 cursor-pointer"
                                            style="height: 80px;" onclick="window.open('{{ $single['path'] }}', '_blank')">
                                            <i class="fa fa-play-circle text-danger" style="font-size: 2.5rem; opacity: 0.8;"></i>
                                        </div>
                                    @elseif ($single['is_pdf'] ?? false)
                                        <a href="{{ $single['path'] }}" target="_blank"
                                            class="d-flex align-items-center justify-content-center rounded bg-danger bg-opacity-10 mb-1 text-decoration-none"
                                            style="height: 80px;">
                                            <i class="fa fa-file-pdf-o text-danger" style="font-size: 2.2rem;"></i>
                                        </a>
                                    @elseif ($single['is_image'] ?? false)
                                        <div class="rounded mb-1 overflow-hidden cursor-pointer" style="height: 80px;"
                                            onclick="document.getElementById('lightbox-img').src='{{ $single['path'] }}'; document.getElementById('lightbox-title').textContent='{{ $single['name'] }}'; new bootstrap.Modal(document.getElementById('attachmentLightbox')).show();">
                                            <img src="{{ $single['path'] }}" class="w-100 h-100 rounded"
                                                style="object-fit: cover;" alt="{{ $single['name'] }}">
                                        </div>
                                    @else
                                        <a href="{{ $single['path'] }}" target="_blank"
                                            class="d-flex align-items-center justify-content-center rounded bg-primary bg-opacity-10 mb-1 text-decoration-none"
                                            style="height: 80px;">
                                            <i class="fa fa-file-o text-primary" style="font-size: 2.2rem;"></i>
                                        </a>
                                    @endif
                                    <div class="small text-truncate text-muted" title="{{ $single['name'] }}">
                                        {{ Str::limit($single['name'], 18) }}
                                    </div>
                                    @if ($status !== SupplyRequestStatus::COMPLETED->value)
                                        @can('supply request.delete item')
                                            <button type="button"
                                                class="btn btn-sm btn-danger position-absolute top-0 end-0 rounded-circle p-0 shadow-sm"
                                                style="width:22px;height:22px;line-height:22px;font-size:0.6rem; transform: translate(5px, -5px);"
                                                wire:click="deleteImage({{ $key }})" wire:confirm="Delete this file?">
                                                <i class="fa fa-times"></i>
                                            </button>
                                        @endcan
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @elseif ($status === SupplyRequestStatus::COMPLETED->value)
                    <div class="text-center text-muted small py-2">No attachments</div>
                @endif
            </div>

            {{-- Image Lightbox Modal --}}
            <div class="modal fade" id="attachmentLightbox" tabindex="-1">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg">
                        <div class="modal-header border-0 py-2 px-3">
                            <h6 class="modal-title fw-semibold" id="lightbox-title"></h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-2 text-center">
                            <img id="lightbox-img" src="" class="img-fluid rounded" style="max-height: 75vh; object-fit: contain;">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════════
             ACTION BUTTONS
             ══════════════════════════════════════════════════════════════ --}}
        <div class="card border-0 shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                    <a href="{{ route('supply-request::index') }}" class="btn btn-light btn-sm d-flex align-items-center gap-1 rounded-pill px-3">
                        <i class="fa fa-arrow-left"></i><span>Back</span>
                    </a>

                    <div class="d-flex flex-wrap gap-2">
                        {{-- Reject --}}
                        @if ($isEditing)
                            @if ((!($supply_request['approved_by'] ?? null) && $currentStatus === SupplyRequestStatus::REQUIREMENT->value) || $currentStatus === SupplyRequestStatus::APPROVED->value)
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
                            @elseif ($currentStatus === SupplyRequestStatus::APPROVED->value && !($supply_request['final_approved_by'] ?? null))
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
                                        class="btn btn-dark btn-sm d-flex align-items-center gap-1 rounded-pill px-3 shadow-sm"
                                        wire:confirm="Mark as completed?">
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

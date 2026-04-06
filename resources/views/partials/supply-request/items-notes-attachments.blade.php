{{--
    Reusable Supply Request: Items + Notes + Attachments
    ====================================================
    Used by: SupplyRequest/Create, Maintenance/Complaint

    Required variables (passed via @include):
    - $items          : array of supply items
    - $item           : new item form data (for add row)
    - $notes          : array of notes
    - $note           : new note text
    - $imageList      : array of uploaded images
    - $branches       : branch list for store dropdown
    - $isCompleted    : bool - is the record completed
    - $showTotals     : bool - show Summary/Totals card (default: true)
    - $supply_request : supply request data (for totals)

    Optional:
    - $showPayment    : bool - show payment section (default: false)
    - $paymentMethods : array - payment methods for payment section
    - $payment_mode_id: string - selected payment mode
--}}

@php
    use App\Enums\SupplyRequest\SupplyRequestStatus;
    $showTotals = $showTotals ?? true;
    $showPayment = $showPayment ?? false;
    $isCompleted = $isCompleted ?? false;
@endphp

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
        @if (!$isCompleted)
            <div class="border-bottom p-3" style="background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 50%, #f0f9ff 100%);">
                <div class="row g-2 align-items-end">
                    {{-- Row 1: Store + Product --}}
                    <div class="col-12 col-sm-3 col-lg-2" wire:ignore>
                        <label class="form-label fw-semibold small mb-1 text-secondary">
                            <i class="fa fa-home me-1" style="font-size: 0.65rem;"></i> Store
                        </label>
                        {{ html()->select('item_branch_id', [session('branch_id') => session('branch_name')])->value($item['branch_id'] ?? '')->class('select-assigned-branch_id-list')->id('item_branch_id')->placeholder('Select Store') }}
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
                    <div class="col-4 col-sm-3 col-lg-2">
                        <label class="form-label fw-semibold small mb-1 text-secondary">Mode</label>
                        <select wire:model="item.mode" class="form-select form-select-sm">
                            <option value="New">New</option>
                            <option value="Damaged">Damaged</option>
                        </select>
                    </div>
                    <div class="col-4 col-sm-2 col-lg-2">
                        <label class="form-label fw-semibold small mb-1 text-secondary">Qty</label>
                        <input type="number" wire:model="item.quantity" class="form-control form-control-sm text-end" step="any"
                            id="supply_item_quantity">
                    </div>
                    <div class="col-4 col-sm-2 col-lg-2">
                        <label class="form-label fw-semibold small mb-1 text-secondary">Price</label>
                        <input type="number" wire:model="item.unit_price" class="form-control form-control-sm text-end" step="any">
                    </div>
                    <div class="col-6 col-sm-2 col-lg-2">
                        <label class="form-label fw-semibold small mb-1 text-secondary">Total</label>
                        <input type="number" wire:model="item.total" class="form-control form-control-sm text-end bg-white fw-semibold"
                            step="any" readonly>
                    </div>
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
                        @if (!$isCompleted)
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
                            @if (!$isCompleted)
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
    <div class="{{ $showTotals ? 'col-12 col-lg-8' : 'col-12' }}">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-2 px-3">
                <div class="d-flex align-items-center">
                    <span class="d-flex align-items-center justify-content-center rounded-circle bg-warning bg-opacity-10 me-2"
                        style="width: 30px; height: 30px;">
                        <i class="fa fa-comments text-warning" style="font-size: 0.85rem;"></i>
                    </span>
                    <h6 class="mb-0 fw-bold text-dark">Notes</h6>
                    @if (count($notes))
                        <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill ms-2 px-2"
                            style="font-size: 0.68rem;">{{ count($notes) }}</span>
                    @endif
                </div>
            </div>
            <div class="card-body p-0">
                @if (!$isCompleted)
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
    @if ($showTotals)
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
                            style="max-width: 120px;" step="any" @if($isCompleted) readonly @endif>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-3 pb-1">
                        <span class="fw-bold text-dark">Grand Total</span>
                        <span class="fw-bold text-success fs-5">{{ currency($supply_request['grand_total'] ?? 0) }}</span>
                    </div>

                    {{-- Payment Section --}}
                    @if ($showPayment ?? false)
                        @php
                            $currentStatusRaw = $supply_request['status'] ?? '';
                            $currentStatusEnum = $currentStatusRaw instanceof SupplyRequestStatus ? $currentStatusRaw : SupplyRequestStatus::tryFrom($currentStatusRaw);
                            $currentStatus = $currentStatusRaw instanceof SupplyRequestStatus ? $currentStatusRaw->value : $currentStatusRaw;
                            $isEditing = isset($supply_request['id']);
                        @endphp
                        @if ($isEditing && $currentStatus === SupplyRequestStatus::APPROVED->value && !($supply_request['completed_by'] ?? null))
                            <div class="mt-3 pt-3 border-top">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="d-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-10 me-2"
                                        style="width: 24px; height: 24px;">
                                        <i class="fa fa-money text-primary" style="font-size: 0.7rem;"></i>
                                    </span>
                                    <span class="fw-bold small text-dark">Collect Payment</span>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label text-muted small fw-semibold mb-1">
                                        <i class="fa fa-credit-card me-1"></i> Payment Mode
                                    </label>
                                    <select wire:model.live="payment_mode_id" class="form-select form-select-sm">
                                        <option value="">-- Select Payment Mode --</option>
                                        @foreach ($paymentMethods ?? [] as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @can('supply request.payment')
                                    <button type="button" wire:click="statusChange('{{ SupplyRequestStatus::COLLECTED->value }}')"
                                        wire:confirm="Collect payment of {{ currency($supply_request['grand_total'] ?? 0) }} and mark as completed?"
                                        class="btn btn-primary btn-sm w-100 d-flex align-items-center justify-content-center gap-2 shadow-sm mt-2"
                                        @if(!($payment_mode_id ?? '')) disabled @endif>
                                        <i class="fa fa-check-circle"></i>
                                        <span>Pay</span>
                                        <span class="badge bg-white text-primary ms-1">{{ currency($supply_request['grand_total'] ?? 0) }}</span>
                                    </button>
                                @endcan
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    @endif
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
        @if (!$isCompleted)
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
                                <div class="d-flex align-items-center justify-content-center rounded bg-light mb-1" style="height: 80px;">
                                    <div class="text-center">
                                        <i class="fa fa-exclamation-triangle text-warning d-block"
                                            style="font-size: 1.5rem; opacity: 0.5;"></i>
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
                                    <img src="{{ $single['path'] }}" class="w-100 h-100 rounded" style="object-fit: cover;"
                                        alt="{{ $single['name'] }}">
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
                            @if (!$isCompleted)
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
        @elseif ($isCompleted)
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

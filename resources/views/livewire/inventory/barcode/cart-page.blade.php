<div>
    <div class="card shadow-sm">
        {{-- ═══════════ CARD HEADER ═══════════ --}}
        <div class="card-header bg-light py-3">

            {{-- Row 1: Actions + Search --}}
            <div class="row mt-3">
                <div class="col-md-6 d-flex flex-wrap gap-2 align-items-center mb-3 mb-md-0">
                    <button wire:click="printBarcodes" class="btn btn-primary d-flex align-items-center shadow-sm" {{ empty($cartItems) ? 'disabled' : '' }}>
                        <i class="fa fa-print me-2"></i>
                        Print Barcodes
                        @if(count($cartItems))
                            <span class="badge bg-white text-primary ms-2">{{ $this->getTotalQuantity() }}</span>
                        @endif
                    </button>
                    <div class="btn-group shadow-sm">
                        <button wire:click="addAllInventory" class="btn btn-success btn-sm d-flex align-items-center" title="Add All Inventory" data-bs-toggle="tooltip">
                            <i class="fa fa-cube me-md-1"></i>
                            <span class="d-none d-md-inline">All Inventory</span>
                        </button>
                        <button wire:click="addAllProductUnits" class="btn btn-info btn-sm d-flex align-items-center text-white" title="Add All Product Units" data-bs-toggle="tooltip">
                            <i class="fa fa-cubes me-md-1"></i>
                            <span class="d-none d-md-inline">All Units</span>
                        </button>
                        <button wire:click="clearCart" class="btn btn-danger btn-sm d-flex align-items-center" {{ empty($cartItems) ? 'disabled' : '' }} title="Clear Cart" data-bs-toggle="tooltip"
                            wire:confirm="Are you sure you want to clear all cart items?">
                            <i class="fa fa-trash me-md-1"></i>
                            <span class="d-none d-md-inline">Clear</span>
                        </button>
                    </div>
                    <button type="button" class="btn btn-outline-secondary btn-sm shadow-sm d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#keyboardShortcutsModal" title="Keyboard Shortcuts">
                        <i class="fa fa-keyboard-o"></i>
                    </button>
                </div>

                <div class="col-md-6">
                    <div class="row g-2 align-items-center">
                        <div class="col-auto">
                            <label class="form-label mb-0 text-muted small fw-semibold">Qty:</label>
                        </div>
                        <div class="col-auto" style="width:65px;">
                            <input type="number" wire:model="quantity" class="form-control form-control-sm border-secondary-subtle shadow-sm text-center fw-bold" min="1" value="1">
                        </div>
                        <div class="col">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white border-secondary-subtle">
                                    <i class="fa fa-search"></i>
                                </span>
                                <input type="text" wire:model.live.debounce.300ms="searchQuery" placeholder="Search products..." class="form-control form-control-sm border-secondary-subtle shadow-sm" id="searchInput" autocomplete="off">
                                @if($searchQuery)
                                    <button class="btn btn-outline-secondary btn-sm" wire:click="$set('searchQuery', '')">
                                        <i class="fa fa-times"></i>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-3">

            {{-- Row 2: Barcode Scanner + Unit Filter --}}
            <div class="row g-3">
                <div class="col-md-8">
                    <label for="barcodeInput" class="form-label fw-medium">
                        <i class="fa fa-barcode text-primary me-1 small"></i>
                        Barcode Scanner
                        <span class="badge bg-success ms-1" style="font-size:9px;letter-spacing:.5px;">
                            <span class="bc-scan-dot"></span>
                            READY
                        </span>
                    </label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-secondary-subtle">
                            <i class="fa fa-barcode"></i>
                        </span>
                        <input type="text" wire:model="barcodeInput" wire:keydown.enter="handleBarcodeScan()" class="form-control form-control-sm border-secondary-subtle shadow-sm" id="barcodeInput" placeholder="Scan barcode or enter manually..." autocomplete="off" autofocus>
                        <button class="btn btn-dark btn-sm shadow-sm" wire:click="handleBarcodeScan()">
                            <i class="fa fa-bolt me-1"></i>Add
                        </button>
                    </div>
                </div>
                <div class="col-md-4" wire:ignore>
                    <label for="unitFilterSelect" class="form-label fw-medium">
                        <i class="fa fa-filter text-primary me-1 small"></i>
                        Unit Filter
                    </label>
                    <select wire:model.live="selectedUnitId" class="form-select form-select-sm border-secondary-subtle shadow-sm" id="unitFilterSelect">
                        <option value="">All Units</option>
                        @foreach ($this->units as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->code }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- ═══════════ CARD BODY ═══════════ --}}
        <div class="card-body p-0">
            <div class="row g-0">
                {{-- ─── Product Results (Left) ─── --}}
                <div class="col-lg-8 border-end">
                    @if (!empty($products))
                        <div class="px-3 pt-3 pb-2 border-bottom bg-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted small fw-semibold">
                                    <i class="fa fa-list me-1"></i>Search Results ({{ count($products) }})
                                </span>
                                <small class="text-muted">Click to add to cart</small>
                            </div>
                        </div>
                        <div class="p-3">
                            <div class="row g-2">
                                @foreach ($products as $index => $product)
                                    <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                                        <div class="card h-100 border shadow-sm bc-product-tile" wire:click="selectProduct({{ $product['id'] }}, '{{ $product['item_type'] ?? 'inventory' }}')">
                                            <div class="bg-light text-center py-2 position-relative border-bottom">
                                                <img src="{{ $product['thumbnail'] ?? cache('logo') }}" alt="{{ $product['name'] }}" class="rounded" style="width:36px;height:36px;object-fit:cover;">
                                                @if (isset($product['item_type']) && $product['item_type'] === 'product_unit')
                                                    <span class="badge bg-info text-white position-absolute top-0 end-0 m-1" style="font-size:8px;">UNIT</span>
                                                @else
                                                    <span class="badge bg-primary position-absolute top-0 end-0 m-1" style="font-size:8px;">INV</span>
                                                @endif
                                            </div>
                                            <div class="card-body p-2">
                                                <h6 class="fw-semibold text-dark mb-1 bc-tile-name" title="{{ $product['name'] }}">{{ $product['name'] }}</h6>
                                                <div class="mb-1">
                                                    <span class="badge bg-light text-dark border" style="font-size:9px;"><i class="fa fa-barcode me-1 opacity-50"></i>{{ $product['barcode'] }}</span>
                                                    @if (isset($product['size']) && $product['size'])
                                                        <span class="badge bg-warning text-dark" style="font-size:9px;">{{ $product['size'] }}</span>
                                                    @endif
                                                    @if (isset($product['item_type']) && $product['item_type'] === 'product_unit')
                                                        <span class="badge bg-light text-info border" style="font-size:9px;">{{ $product['sub_unit_name'] ?? 'N/A' }} &times;{{ $product['conversion_factor'] ?? 1 }}</span>
                                                    @endif
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center mt-1">
                                                    <span class="fw-bold text-success small">{{ currency($product['mrp']) }}</span>
                                                    @if (!isset($product['item_type']) || $product['item_type'] !== 'product_unit')
                                                        <span class="text-muted" style="font-size:9px;">Stock: {{ $product['quantity'] }}</span>
                                                    @endif
                                                    <span class="btn btn-primary btn-sm rounded-circle d-flex align-items-center justify-content-center p-0 shadow-sm" style="width:22px;height:22px;font-size:9px;">
                                                        <i class="fa fa-plus"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fa fa-barcode fa-3x text-muted opacity-25 mb-3"></i>
                            <h6 class="text-muted fw-semibold">Ready to Scan</h6>
                            <p class="text-muted small mb-3">Scan a barcode or search for products to get started</p>
                            <div class="d-flex justify-content-center gap-3 flex-wrap">
                                <span class="text-muted small"><kbd class="bg-light text-dark border shadow-sm">Ctrl+B</kbd> Scanner</span>
                                <span class="text-muted small"><kbd class="bg-light text-dark border shadow-sm">Ctrl+K</kbd> Search</span>
                                <span class="text-muted small"><kbd class="bg-light text-dark border shadow-sm">Ctrl+P</kbd> Print</span>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- ─── Cart Panel (Right) ─── --}}
                <div class="col-lg-4">
                    <div class="px-3 pt-3 pb-2 border-bottom bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-semibold text-dark small">
                                <i class="fa fa-shopping-cart me-1 text-primary"></i>Cart Items
                            </span>
                            <span class="badge bg-primary rounded-pill shadow-sm">{{ count($cartItems) }}</span>
                        </div>
                    </div>

                    @if (empty($cartItems))
                        <div class="text-center py-5">
                            <i class="fa fa-shopping-cart fa-2x text-muted opacity-25 mb-2"></i>
                            <p class="text-muted small mb-0">Cart is empty</p>
                        </div>
                    @else
                        <div class="bc-cart-items-list">
                            @foreach ($cartItems as $cartKey => $item)
                                <div class="d-flex align-items-center gap-2 px-3 py-2 border-bottom bc-cart-row" wire:key="cart-{{ $cartKey }}">
                                    <img src="{{ $item['thumbnail'] ?? $item['image'] ?? cache('logo') }}" alt="{{ $item['name'] }}" class="rounded border" style="width:32px;height:32px;object-fit:cover;flex-shrink:0;">
                                    <div class="flex-grow-1" style="min-width:0;">
                                        <div class="fw-medium text-dark text-truncate" style="font-size:12px;" title="{{ $item['name'] }}">{{ $item['name'] }}</div>
                                        <div class="d-flex gap-1 flex-wrap mt-1">
                                            <span class="badge bg-light text-dark border" style="font-size:8px;"><i class="fa fa-barcode me-1 opacity-50"></i>{{ $item['barcode'] }}</span>
                                            @if (isset($item['item_type']) && $item['item_type'] === 'product_unit')
                                                <span class="badge bg-info text-white" style="font-size:8px;">Unit</span>
                                            @endif
                                            <span class="badge bg-success" style="font-size:8px;">{{ currency($item['mrp']) }}</span>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-1 flex-shrink-0">
                                        <div class="btn-group btn-group-sm shadow-sm">
                                            <button wire:click="updateQuantity('{{ $cartKey }}', {{ $item['quantity'] - 1 }})" class="btn btn-outline-secondary px-1 py-0" style="font-size:9px;width:22px;height:22px;">
                                                <i class="fa fa-minus"></i>
                                            </button>
                                            <span class="btn btn-light px-2 py-0 fw-bold border" style="font-size:11px;min-width:28px;height:22px;cursor:default;">{{ $item['quantity'] }}</span>
                                            <button wire:click="updateQuantity('{{ $cartKey }}', {{ $item['quantity'] + 1 }})" class="btn btn-outline-secondary px-1 py-0" style="font-size:9px;width:22px;height:22px;">
                                                <i class="fa fa-plus"></i>
                                            </button>
                                        </div>
                                        <button wire:click="removeFromCart('{{ $cartKey }}')" class="btn btn-outline-danger btn-sm px-1 py-0 shadow-sm" style="font-size:9px;width:22px;height:22px;" title="Remove">
                                            <i class="fa fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Cart Footer --}}
                        <div class="p-3 border-top bg-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted small fw-semibold">Total Labels:</span>
                                <span class="fw-bold text-primary fs-5">{{ $this->getTotalQuantity() }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ═══════════ MOBILE FAB ═══════════ --}}
        <div class="position-fixed bottom-0 end-0 mb-4 me-4 d-md-none" style="z-index:1050;">
            <button wire:click="printBarcodes" class="btn btn-primary rounded-circle shadow btn-lg" {{ empty($cartItems) ? 'disabled' : '' }}>
                <i class="fa fa-print"></i>
            </button>
        </div>
    </div>

    {{-- ═══════════ KEYBOARD SHORTCUTS MODAL ═══════════ --}}
    <div class="modal fade" id="keyboardShortcutsModal" tabindex="-1">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title fw-bold"><i class="fa fa-keyboard-o me-2 text-primary"></i>Keyboard Shortcuts</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="bg-light text-muted">
                                <tr class="small">
                                    <th class="fw-semibold py-2">Shortcut</th>
                                    <th class="fw-semibold py-2">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td><kbd class="bg-light text-dark border shadow-sm">Ctrl+B</kbd></td><td class="small text-muted">Focus Scanner</td></tr>
                                <tr><td><kbd class="bg-light text-dark border shadow-sm">Ctrl+K</kbd></td><td class="small text-muted">Focus Search</td></tr>
                                <tr><td><kbd class="bg-light text-dark border shadow-sm">Ctrl+P</kbd></td><td class="small text-muted">Print Barcodes</td></tr>
                                <tr><td><kbd class="bg-light text-dark border shadow-sm">Enter</kbd></td><td class="small text-muted">Scan Barcode</td></tr>
                                <tr><td><kbd class="bg-light text-dark border shadow-sm">Escape</kbd></td><td class="small text-muted">Clear & Refocus</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════ MINIMAL STYLES ═══════════ --}}
    <style>
        /* Scan dot */
        .bc-scan-dot {
            width: 5px; height: 5px;
            background: #fff;
            border-radius: 50%;
            display: inline-block;
            animation: bc-pulse 1.5s ease-in-out infinite;
        }
        @keyframes bc-pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: .4; transform: scale(.6); }
        }

        /* Product tiles */
        .bc-product-tile {
            cursor: pointer;
            transition: all .2s ease;
            overflow: hidden;
        }
        .bc-product-tile:hover {
            transform: translateY(-2px);
            box-shadow: 0 .25rem .75rem rgba(0,0,0,.12) !important;
            border-color: #0d6efd !important;
        }
        .bc-tile-name {
            font-size: 11px;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Cart list */
        .bc-cart-items-list {
            max-height: 400px;
            overflow-y: auto;
        }
        .bc-cart-row { transition: background .15s; }
        .bc-cart-row:hover { background-color: #f8f9fa; }

        /* Scrollbar */
        .bc-cart-items-list::-webkit-scrollbar { width: 4px; }
        .bc-cart-items-list::-webkit-scrollbar-track { background: transparent; }
        .bc-cart-items-list::-webkit-scrollbar-thumb { background: #dee2e6; border-radius: 2px; }

        /* Toast */
        .bc-toast {
            position: fixed;
            top: 16px;
            right: 16px;
            z-index: 9999;
            max-width: 320px;
            padding: 10px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #fff;
            animation: bc-slideIn .3s ease, bc-fadeOut .3s ease 2.7s forwards;
            box-shadow: 0 .5rem 1rem rgba(0,0,0,.15);
        }
        .bc-toast.success { background-color: #198754; }
        .bc-toast.error { background-color: #dc3545; }
        @keyframes bc-slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes bc-fadeOut {
            to { opacity: 0; transform: translateY(-10px); }
        }
    </style>

    @push('scripts')
        <script>
            document.addEventListener('livewire:init', () => {

                // ── Toast Messages ──
                function showToast(message, type) {
                    const toast = document.createElement('div');
                    toast.className = `bc-toast ${type}`;
                    toast.innerHTML = `<i class="fa fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}`;
                    document.body.appendChild(toast);
                    setTimeout(() => toast.remove(), 3200);
                }

                Livewire.on('success', (e) => showToast(e.message, 'success'));
                Livewire.on('error', (e) => showToast(e.message, 'error'));

                // ── Keyboard Shortcuts ──
                document.addEventListener('keydown', function(e) {
                    if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                        e.preventDefault();
                        @this.call('printBarcodes');
                    }
                    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                        e.preventDefault();
                        document.getElementById('searchInput')?.focus();
                    }
                    if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
                        e.preventDefault();
                        document.getElementById('barcodeInput')?.focus();
                    }
                    if (e.key === 'Escape') {
                        @this.set('searchQuery', '');
                        document.getElementById('barcodeInput')?.focus();
                    }
                });

                // ── Tooltips ──
                const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function(el) {
                    return new bootstrap.Tooltip(el, { boundary: document.body });
                });

                // ── Auto-focus barcode scanner ──
                setTimeout(() => {
                    document.getElementById('barcodeInput')?.focus();
                }, 500);

                // ── Re-focus scanner after adding item ──
                Livewire.on('success', () => {
                    setTimeout(() => {
                        document.getElementById('barcodeInput')?.focus();
                    }, 100);
                });

                // ── Unit filter change handler ──
                const unitSelect = document.getElementById('unitFilterSelect');
                if (unitSelect) {
                    unitSelect.addEventListener('change', function() {
                        @this.set('selectedUnitId', this.value);
                    });
                }

                // ── Scan detection: fast typing → flash indicator ──
                let lastKeyTime = 0;
                let keyBuffer = '';
                const barcodeInput = document.getElementById('barcodeInput');

                if (barcodeInput) {
                    barcodeInput.addEventListener('keypress', function(e) {
                        const now = Date.now();
                        const timeDiff = now - lastKeyTime;
                        if (timeDiff > 500) keyBuffer = '';
                        keyBuffer += e.key;
                        lastKeyTime = now;

                        if (timeDiff < 50 && keyBuffer.length >= 4) {
                            const dot = document.querySelector('.bc-scan-dot');
                            if (dot) {
                                dot.style.background = '#0d6efd';
                                dot.style.boxShadow = '0 0 6px #0d6efd';
                                setTimeout(() => {
                                    dot.style.background = '#fff';
                                    dot.style.boxShadow = 'none';
                                }, 500);
                            }
                        }
                    });
                }
            });
        </script>
    @endpush
</div>

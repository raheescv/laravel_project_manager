<div class="bcx">
    <div class="bcx-shell bcx-cart">

        {{-- ═══════════ COMMAND BAR ═══════════ --}}
        <div class="bcx-bar">
            <i class="fa fa-barcode" style="color:var(--bcx-brand)"></i>
            <div>
                <div class="bcx-bar__title">Barcode Print Cart</div>
                <div class="bcx-bar__sub">Scan or search, set quantities, print the batch</div>
            </div>

            <div class="bcx-spacer"></div>

            <button wire:click="addAllInventory" class="bcx-btn bcx-btn--sm" title="Add all inventory">
                <i class="fa fa-cube"></i> <span class="d-none d-md-inline">All Inventory</span>
            </button>
            <button wire:click="addAllProductUnits" class="bcx-btn bcx-btn--sm" title="Add all product units">
                <i class="fa fa-cubes"></i> <span class="d-none d-md-inline">All Units</span>
            </button>
            <button wire:click="clearCart" class="bcx-btn bcx-btn--sm bcx-btn--danger" title="Clear cart"
                wire:confirm="Are you sure you want to clear all cart items?" {{ empty($cartItems) ? 'disabled' : '' }}>
                <i class="fa fa-trash"></i>
            </button>
            <button type="button" class="bcx-btn bcx-btn--sm bcx-btn--icon" data-bs-toggle="modal"
                data-bs-target="#keyboardShortcutsModal" title="Keyboard shortcuts">
                <i class="fa fa-keyboard-o"></i>
            </button>

            <div class="bcx-sep"></div>

            <button wire:click="printBarcodes" class="bcx-btn bcx-btn--primary" {{ empty($cartItems) ? 'disabled' : '' }}>
                <i class="fa fa-print"></i> Print
                @if (count($cartItems))
                    <span class="bcx-num">({{ $this->getTotalQuantity() }})</span>
                @endif
            </button>
        </div>

        {{-- ═══════════ INPUT BAR ═══════════ --}}
        <div class="bcx-bar" style="background:var(--bcx-panel-2)">
            <div style="flex:1 1 320px;min-width:220px;display:flex;align-items:center;gap:8px">
                <span class="bcx-chip bcx-chip--ok"><span class="bc-scan-dot"></span> Ready</span>
                <input type="text" wire:model.live="barcodeInput" wire:keydown.enter="handleBarcodeScan()"
                    class="bcx-input" id="barcodeInput" placeholder="Scan barcode or type it and press Enter…"
                    autocomplete="off" autofocus>
                <button class="bcx-btn bcx-btn--sm" wire:click="handleBarcodeScan()"><i class="fa fa-bolt"></i> Add</button>
            </div>

            <div style="flex:1 1 260px;min-width:200px;display:flex;align-items:center;gap:8px">
                <i class="fa fa-search" style="color:var(--bcx-faint)"></i>
                <input type="text" wire:model.live.debounce.300ms="searchQuery" class="bcx-input" id="searchInput"
                    placeholder="Search products…" autocomplete="off">
                @if ($searchQuery)
                    <button class="bcx-btn bcx-btn--sm bcx-btn--icon" wire:click="$set('searchQuery', '')">
                        <i class="fa fa-times"></i>
                    </button>
                @endif
            </div>

            <label class="bcx-field" style="flex:0 0 130px;padding:0">
                <span style="width:auto">Qty</span>
                <input type="number" wire:model="quantity" min="1" value="1">
            </label>

            <div wire:ignore style="flex:0 0 190px">
                <select wire:model.live="selectedUnitId" class="bcx-input" id="unitFilterSelect" style="width:100%">
                    <option value="">All units</option>
                    @foreach ($this->units as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->code }})</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- ═══════════ BODY ═══════════ --}}
        <div class="bcx-cart__body">
            {{-- ─── results ─── --}}
            <section style="min-width:0;border-inline-end:1px solid var(--bcx-line)">
                @if (!empty($products))
                    <div class="bcx-drawer__title" style="padding:10px 12px 0;margin:0">
                        Search results <span>{{ count($products) }} found · click to add</span>
                    </div>
                    <div class="bcx-tiles">
                        @foreach ($products as $product)
                            <button type="button" class="bcx-tile"
                                wire:click="selectProduct({{ $product['id'] }}, '{{ $product['item_type'] ?? 'inventory' }}')">
                                <img src="{{ $product['thumbnail'] ?? tenant_cache('logo') }}" alt="{{ $product['name'] }}"
                                    class="bcx-tile__img">
                                <div class="bcx-tile__body">
                                    <div class="bcx-tile__name" title="{{ $product['name'] }}">{{ $product['name'] }}</div>
                                    <div class="bcx-tile__meta">{{ $product['barcode'] }}</div>
                                    <div class="bcx-tile__foot">
                                        <span class="bcx-tile__price">{{ currency($product['mrp']) }}</span>
                                        @if (($product['item_type'] ?? 'inventory') === 'product_unit')
                                            <span class="bcx-chip bcx-chip--brand">
                                                {{ $product['sub_unit_name'] ?? 'Unit' }} ×{{ $product['conversion_factor'] ?? 1 }}
                                            </span>
                                        @else
                                            <span class="bcx-tile__meta" style="margin:0">stock {{ $product['quantity'] }}</span>
                                        @endif
                                    </div>
                                    @if (!empty($product['size']))
                                        <div class="bcx-tile__meta">size {{ $product['size'] }}</div>
                                    @endif
                                </div>
                            </button>
                        @endforeach
                    </div>
                @else
                    <div class="bcx-empty">
                        <i class="fa fa-barcode"></i>
                        <div style="font-weight:650;color:var(--bcx-ink)">Ready to scan</div>
                        <p style="margin:6px 0 14px">Scan a barcode or search for products to get started.</p>
                        <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap">
                            <span><span class="bcx-kbd">Ctrl+B</span> Scanner</span>
                            <span><span class="bcx-kbd">Ctrl+K</span> Search</span>
                            <span><span class="bcx-kbd">Ctrl+P</span> Print</span>
                        </div>
                    </div>
                @endif
            </section>

            {{-- ─── cart ─── --}}
            <aside style="min-width:0;display:flex;flex-direction:column">
                <div class="bcx-drawer__title" style="padding:10px 12px 8px;margin:0;border-bottom:1px solid var(--bcx-line)">
                    Cart <span>{{ count($cartItems) }} item{{ count($cartItems) === 1 ? '' : 's' }}</span>
                </div>

                @if (empty($cartItems))
                    <div class="bcx-empty">
                        <i class="fa fa-shopping-cart"></i>
                        <p style="margin:0">Cart is empty</p>
                    </div>
                @else
                    <div class="bcx-cart__list">
                        @foreach ($cartItems as $cartKey => $item)
                            <div class="bcx-cartrow" wire:key="cart-{{ $cartKey }}">
                                <img src="{{ $item['thumbnail'] ?? ($item['image'] ?? tenant_cache('logo')) }}"
                                    alt="{{ $item['name'] }}" class="bcx-tile__img">
                                <div style="flex:1;min-width:0">
                                    <div class="bcx-row__label" title="{{ $item['name'] }}">{{ $item['name'] }}</div>
                                    <div class="bcx-tile__meta">
                                        {{ $item['barcode'] }} · {{ currency($item['mrp']) }}
                                        @if (($item['item_type'] ?? '') === 'product_unit') · unit @endif
                                    </div>
                                </div>
                                <div class="bcx-stepper">
                                    <button wire:click="updateQuantity('{{ $cartKey }}', {{ $item['quantity'] - 1 }})"
                                        title="Less"><i class="fa fa-minus"></i></button>
                                    <b>{{ $item['quantity'] }}</b>
                                    <button wire:click="updateQuantity('{{ $cartKey }}', {{ $item['quantity'] + 1 }})"
                                        title="More"><i class="fa fa-plus"></i></button>
                                </div>
                                <button wire:click="removeFromCart('{{ $cartKey }}')"
                                    class="bcx-ord" title="Remove"><i class="fa fa-times"></i></button>
                            </div>
                        @endforeach
                    </div>
                @endif
            </aside>
        </div>

        {{-- ═══════════ STATUS BAR ═══════════ --}}
        <div class="bcx-status">
            <span>ITEMS <b>{{ count($cartItems) }}</b></span>
            <span>LABELS <b>{{ $this->getTotalQuantity() }}</b></span>
            <span>UNIT FILTER <b>{{ $selectedUnitId ? 'on' : 'all' }}</b></span>
            <span class="bcx-spacer"></span>
            <span>CTRL+B SCANNER · CTRL+K SEARCH · CTRL+P PRINT</span>
        </div>
    </div>

    {{-- ═══════════ MOBILE FAB ═══════════ --}}
    <div class="position-fixed bottom-0 end-0 mb-4 me-4 d-md-none" style="z-index:1050;">
        <button wire:click="printBarcodes" class="bcx-btn bcx-btn--primary" style="border-radius:999px;padding:16px 18px"
            {{ empty($cartItems) ? 'disabled' : '' }}>
            <i class="fa fa-print"></i>
        </button>
    </div>

    {{-- ═══════════ KEYBOARD SHORTCUTS MODAL ═══════════ --}}
    <div class="modal fade" id="keyboardShortcutsModal" tabindex="-1">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content bcx">
                <div class="modal-header">
                    <h6 class="modal-title fw-bold"><i class="fa fa-keyboard-o me-2" style="color:var(--bcx-brand)"></i>Keyboard Shortcuts</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <table class="bcx-table">
                        <tbody>
                            <tr><td><span class="bcx-kbd">Ctrl+B</span></td><td>Focus scanner</td></tr>
                            <tr><td><span class="bcx-kbd">Ctrl+K</span></td><td>Focus search</td></tr>
                            <tr><td><span class="bcx-kbd">Ctrl+P</span></td><td>Print barcodes</td></tr>
                            <tr><td><span class="bcx-kbd">Enter</span></td><td>Scan barcode</td></tr>
                            <tr><td><span class="bcx-kbd">Escape</span></td><td>Clear &amp; refocus</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════ PAGE SPECIFIC STYLES ═══════════ --}}
    <style>
        /* Breakpoints measure the shell, not the window: the app sidebar can
           collapse underneath us without the viewport width ever changing. */
        .bcx-cart {
            container: bcx-cart / inline-size;
        }

        .bcx-cart__body {
            display: grid;
            grid-template-columns: minmax(0, 1fr) clamp(280px, 30cqw, 380px);
            min-height: 420px;
        }

        .bcx-cart__body>* {
            min-width: 0;
        }

        .bcx-cart__list {
            max-height: 460px;
            overflow-y: auto;
        }

        .bcx-cart__list::-webkit-scrollbar {
            width: 5px;
        }

        .bcx-cart__list::-webkit-scrollbar-thumb {
            background: var(--bcx-line);
            border-radius: 3px;
        }

        @container bcx-cart (max-width: 860px) {
            .bcx-cart__body {
                grid-template-columns: 1fr;
            }

            .bcx-cart__body>section {
                border-inline-end: 0;
                border-bottom: 1px solid var(--bcx-line);
            }
        }

        /* Scan indicator */
        .bc-scan-dot {
            width: 5px;
            height: 5px;
            background: currentColor;
            border-radius: 50%;
            display: inline-block;
            animation: bc-pulse 1.5s ease-in-out infinite;
        }

        @keyframes bc-pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: .35; transform: scale(.6); }
        }

        /* Toast */
        .bc-toast {
            position: fixed;
            top: 16px;
            inset-inline-end: 16px;
            z-index: 9999;
            max-width: 320px;
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #fff;
            animation: bc-slideIn .3s ease, bc-fadeOut .3s ease 2.7s forwards;
            box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .18);
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
                                dot.style.animation = 'none';
                                dot.style.transform = 'scale(1.6)';
                                setTimeout(() => {
                                    dot.style.animation = '';
                                    dot.style.transform = '';
                                }, 400);
                            }
                        }
                    });
                }
            });
        </script>
    @endpush
</div>

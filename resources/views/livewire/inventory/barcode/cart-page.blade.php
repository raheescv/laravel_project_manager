@php
    $cart = $this->cartSettings;
    $templates = $this->templates;
    $activeTemplate = $templates[$templateKey] ?? null;
    $templateSettings = \App\Support\BarcodeTemplateConfiguration::resolveSettings($templateKey)['settings'];

    $selectedRow = $cartItems[$selectedRowKey] ?? null;
    if (! $selectedRow && ! empty($cartItems)) {
        $selectedRowKey = array_key_first($cartItems);
        $selectedRow = $cartItems[$selectedRowKey];
    }

    // The inspector proof is the real print view at its natural mm size, scaled to fit the pane.
    $mmPerPx = 25.4 / 96;
    $proofWidth = max(1, (float) ($templateSettings['width'] ?? 50) / $mmPerPx);
    $proofHeight = max(1, (float) ($templateSettings['height'] ?? 30) / $mmPerPx);
    $proofScale = min(250 / $proofWidth, 150 / $proofHeight);

    $proofUrl = null;
    if ($selectedRow) {
        $isUnit = ($selectedRow['item_type'] ?? 'inventory') === 'product_unit';
        $proofUrl = route('inventory::barcode::preview', $isUnit ? [] : ['id' => $selectedRow['inventory_id'] ?? null]) . '?' . http_build_query(array_filter([
            'template' => $templateKey,
            'unit_id' => $isUnit ? ($selectedRow['product_unit_id'] ?? null) : null,
            'price' => $selectedRow['price'] ?? null,
        ], fn ($value) => $value !== null && $value !== '') + ['weight' => $selectedRow['weight'] ?? '']);
    }
@endphp

<div class="bcx">
    <div class="bcx-shell bcx-cart">

        {{-- ═══════════ COMMAND BAR ═══════════ --}}
        <div class="bcx-bar">
            <a href="{{ route('inventory::index') }}" class="bcx-btn bcx-back" title="Back to inventory">
                <i class="fa fa-angle-left"></i><span class="bcx-back__label">Inventory</span>
            </a>
            <i class="fa fa-barcode" style="color:var(--bcx-brand)"></i>
            <div class="bcx-cart__heading">
                <div class="bcx-bar__title">Barcode Print Cart</div>
                <div class="bcx-bar__sub">Scan or search, set each row, print the batch</div>
            </div>

            <div class="bcx-sep"></div>

            <label class="bcx-cart__template" title="Label template this batch prints with">
                <i class="fa fa-tag"></i>
                <select wire:model.live="templateKey" class="bcx-input" id="templateSelect">
                    @foreach ($templates as $key => $template)
                        <option value="{{ $key }}">{{ $template['name'] }}</option>
                    @endforeach
                </select>
            </label>
            @can('configuration.barcode')
                <a href="{{ route('inventory::barcode::configuration.edit', $templateKey) }}" class="bcx-btn bcx-btn--sm bcx-btn--icon"
                    title="Edit this template and its cart settings">
                    <i class="fa fa-pencil"></i>
                </a>
            @endcan

            <div class="bcx-spacer"></div>

            <button wire:click="clearCart" class="bcx-btn bcx-btn--sm bcx-btn--danger" title="Clear cart"
                wire:confirm="Are you sure you want to clear all cart items?" {{ empty($cartItems) ? 'disabled' : '' }}>
                <i class="fa fa-trash"></i>
            </button>
            <button type="button" class="bcx-btn bcx-btn--sm" data-label-printer-choose wire:ignore title="Label printer (QZ Tray)">
                <i class="fa fa-cog"></i> <span class="d-none d-md-inline" data-label-printer-name>Printer</span>
            </button>
            <button wire:click="printBarcodes" class="bcx-btn bcx-btn--primary" {{ empty($cartItems) ? 'disabled' : '' }}>
                <i class="fa fa-print"></i> Print
                @if (count($cartItems))
                    <span class="bcx-num">({{ $this->getTotalQuantity() }})</span>
                @endif
            </button>
        </div>

        {{-- ═══════════ BODY: rail · drawer · stage · inspector ═══════════ --}}
        <div class="bcx-cart__body" x-data="{ section: 'add' }">

            {{-- ─── icon rail ─── --}}
            <nav class="bcx-rail">
                <button type="button" class="bcx-rail__btn" :class="{ 'is-active': section === 'add' }"
                    @click="section = 'add'; $nextTick(() => document.getElementById('barcodeInput')?.focus())" title="Scan & search">
                    <i class="fa fa-barcode"></i>
                </button>
                <button type="button" class="bcx-rail__btn" :class="{ 'is-active': section === 'bulk' }"
                    @click="section = 'bulk'" title="Add in bulk">
                    <i class="fa fa-cubes"></i>
                </button>
                <button type="button" class="bcx-rail__btn" :class="{ 'is-active': section === 'keys' }"
                    @click="section = 'keys'" title="Keyboard shortcuts">
                    <i class="fa fa-keyboard-o"></i>
                </button>
            </nav>

            {{-- ─── drawer ─── --}}
            <aside class="bcx-drawer">
                <div x-show="section === 'add'">
                    <div class="bcx-drawer__title">Scan <span class="bcx-chip bcx-chip--ok"><span class="bc-scan-dot"></span> Ready</span></div>
                    <div class="bcx-cart__scan">
                        <input type="text" wire:model.live="barcodeInput" wire:keydown.enter="handleBarcodeScan()"
                            class="bcx-input" id="barcodeInput" placeholder="Scan or type a barcode, Enter…"
                            autocomplete="off" autofocus>
                        <button class="bcx-btn bcx-btn--sm" wire:click="handleBarcodeScan()" title="Add"><i class="fa fa-bolt"></i></button>
                    </div>

                    <label class="bcx-field">
                        <span>Labels per add</span>
                        <input type="number" wire:model="quantity" min="1" value="1">
                    </label>
                    <label class="bcx-field" title="Scanning an item already in the cart adds a new row instead of raising its label count">
                        <span>Separate row per scan</span>
                        <label class="bcx-switch" style="margin-inline-start:auto">
                            <input type="checkbox" wire:model.live="separateRows" id="separateRowsSwitch">
                            <span></span>
                        </label>
                    </label>
                    <label class="bcx-field" title="Auto: new rows start with the product MRP{{ $cart['weight_column'] ? ' and 1 g' : '' }}. Custom: they start blank for you to fill.">
                        <span>Fill {{ $autoFill ? 'Auto' : 'Custom' }}</span>
                        <label class="bcx-switch" style="margin-inline-start:auto">
                            <input type="checkbox" wire:model.live="autoFill" id="autoFillSwitch">
                            <span></span>
                        </label>
                    </label>

                    <div class="bcx-drawer__title">Search</div>
                    <div class="bcx-cart__scan">
                        <input type="text" wire:model.live.debounce.300ms="searchQuery" class="bcx-input" id="searchInput"
                            placeholder="Name, code or barcode…" autocomplete="off">
                        @if ($searchQuery)
                            <button class="bcx-btn bcx-btn--sm bcx-btn--icon" wire:click="$set('searchQuery', '')" title="Clear">
                                <i class="fa fa-times"></i>
                            </button>
                        @endif
                    </div>

                    @if (!empty($products))
                        <div class="bcx-drawer__title">Results <span>{{ count($products) }} · click to add</span></div>
                        @foreach ($products as $product)
                            <button type="button" class="bcx-row bcx-cart__result" wire:key="result-{{ $product['item_type'] }}-{{ $product['id'] }}"
                                wire:click="selectProduct({{ $product['id'] }}, '{{ $product['item_type'] ?? 'inventory' }}')">
                                <img src="{{ $product['image'] ?? tenant_cache('logo') }}" alt="" class="bcx-cart__thumb">
                                <div style="flex:1;min-width:0;text-align:start">
                                    <div class="bcx-row__label" title="{{ $product['name'] }}">{{ $product['name'] }}</div>
                                    <div class="bcx-row__meta">
                                        {{ $product['barcode'] }} · {{ currency($product['mrp']) }}
                                        @if (($product['item_type'] ?? 'inventory') === 'product_unit')
                                            · {{ $product['sub_unit_name'] ?? 'Unit' }} ×{{ $product['conversion_factor'] ?? 1 }}
                                        @else
                                            · stock {{ $product['quantity'] }}
                                        @endif
                                    </div>
                                </div>
                                <i class="fa fa-plus" style="color:var(--bcx-brand)"></i>
                            </button>
                        @endforeach
                    @elseif (strlen(trim($searchQuery)) >= 2)
                        <p class="bcx-note">Nothing in stock at this branch matches “{{ $searchQuery }}”.</p>
                    @endif
                </div>

                <div x-show="section === 'bulk'" x-cloak>
                    <div class="bcx-drawer__title">Add in bulk</div>
                    <button wire:click="addAllInventory" class="bcx-btn bcx-cart__wide" wire:confirm="Add every inventory item to the cart?">
                        <i class="fa fa-cube"></i> All inventory
                    </button>
                    <label class="bcx-field">
                        <span>Unit</span>
                        <select wire:model.live="selectedUnitId" id="unitFilterSelect">
                            <option value="">All units</option>
                            @foreach ($this->units as $unit)
                                <option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->code }})</option>
                            @endforeach
                        </select>
                    </label>
                    <button wire:click="addAllProductUnits" class="bcx-btn bcx-cart__wide" wire:confirm="Add every product unit to the cart?">
                        <i class="fa fa-cubes"></i> All product units
                    </button>
                    <p class="bcx-note">The unit filter also narrows product units in search results.</p>
                </div>

                <div x-show="section === 'keys'" x-cloak>
                    <div class="bcx-drawer__title">Keyboard shortcuts</div>
                    <table class="bcx-table">
                        <tbody>
                            <tr><td><span class="bcx-kbd">Ctrl+B</span></td><td>Focus scanner</td></tr>
                            <tr><td><span class="bcx-kbd">Ctrl+K</span></td><td>Focus search</td></tr>
                            <tr><td><span class="bcx-kbd">Ctrl+P</span></td><td>Print the batch</td></tr>
                            <tr><td><span class="bcx-kbd">Enter</span></td><td>Scan · next row's weight</td></tr>
                            <tr><td><span class="bcx-kbd">Esc</span></td><td>Clear search, back to scanner</td></tr>
                        </tbody>
                    </table>
                </div>
            </aside>

            {{-- ─── stage: the batch ─── --}}
            <section class="bcx-cart__stage">
                @if (empty($cartItems))
                    <div class="bcx-empty">
                        <i class="fa fa-barcode"></i>
                        <div style="font-weight:650;color:var(--bcx-ink)">Ready to scan</div>
                        <p style="margin:6px 0 14px">Scan a barcode or search on the left to start the batch.</p>
                        <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap">
                            <span><span class="bcx-kbd">Ctrl+B</span> Scanner</span>
                            <span><span class="bcx-kbd">Ctrl+K</span> Search</span>
                            <span><span class="bcx-kbd">Ctrl+P</span> Print</span>
                        </div>
                    </div>
                @else
                    <div class="bcx-card bcx-cart__sheet">
                        <table class="bcx-table bcx-cart__grid">
                            <thead>
                                <tr>
                                    <th style="width:36px">#</th>
                                    <th>Item</th>
                                    @if ($cart['price_column'])
                                        <th class="bcx-cart__num">Unit Price</th>
                                    @endif
                                    @if ($cart['weight_column'])
                                        <th class="bcx-cart__num">Weight</th>
                                    @endif
                                    @if ($cart['price_column'])
                                        <th class="bcx-cart__num bcx-cart__num--tax">Tax %</th>
                                        <th class="bcx-cart__num">MRP</th>
                                    @endif
                                    <th class="bcx-cart__num">Labels</th>
                                    <th style="width:76px"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($cartItems as $cartKey => $item)
                                    @php
                                        $missingWeight = $cart['weight_column'] && empty($item['weight']);
                                        $missingPrice = !is_numeric($item['price'] ?? null);
                                        $titledByCategory = $cart['category_name'] && !empty($item['category_name']);
                                    @endphp
                                    <tr wire:key="cart-{{ $cartKey }}" class="{{ $cartKey === $selectedRowKey ? 'is-selected' : '' }}"
                                        wire:click="selectRow('{{ $cartKey }}')">
                                        <td class="bcx-num" style="color:var(--bcx-faint)">{{ $loop->iteration }}</td>
                                        <td>
                                            <div style="display:flex;align-items:center;gap:10px;min-width:0">
                                                <img src="{{ $item['thumbnail'] ?? ($item['image'] ?? tenant_cache('logo')) }}" alt="" class="bcx-cart__thumb">
                                                <div style="min-width:0">
                                                    @if ($titledByCategory)
                                                        <div class="bcx-table__name bcx-cart__name" title="{{ $item['category_name'] }}">{{ $item['category_name'] }}</div>
                                                    @else
                                                        <div class="bcx-table__name bcx-cart__name" title="{{ $item['name'] }}">{{ $item['name'] }}</div>
                                                    @endif
                                                    <div class="bcx-table__meta">
                                                        @if ($titledByCategory)
                                                            <span class="bcx-cart__category">{{ $item['name'] }}</span> ·
                                                        @elseif (!empty($item['category_name']))
                                                            <span class="bcx-cart__category">{{ $item['category_name'] }}</span> ·
                                                        @endif
                                                        {{ $item['barcode'] }}
                                                        @if (($item['item_type'] ?? '') === 'product_unit') · unit @endif
                                                        @if (!empty($item['size'])) · size {{ $item['size'] }} @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        @if ($cart['price_column'])
                                            <td class="bcx-cart__num" @click.stop>
                                                <input type="number" step="0.01" min="0" class="bcx-input bcx-cart__cell"
                                                    wire:model.blur="cartItems.{{ $cartKey }}.unit_price" placeholder="—"
                                                    title="{{ $cart['weight_column'] ? 'Price per gram' : 'Price per piece' }}: fills the MRP with tax">
                                            </td>
                                        @endif
                                        @if ($cart['weight_column'])
                                            <td class="bcx-cart__num" @click.stop>
                                                <div class="bcx-field__unit bcx-cart__gram {{ $missingWeight ? 'is-missing' : '' }}">
                                                    <input type="number" step="0.001" min="0" class="bcx-input bcx-cart__cell" data-gram-input
                                                        wire:model.blur="cartItems.{{ $cartKey }}.weight" placeholder="0.000"
                                                        @focus="$wire.selectRow('{{ $cartKey }}')">
                                                    <em>g</em>
                                                </div>
                                            </td>
                                        @endif
                                        @if ($cart['price_column'])
                                            <td class="bcx-cart__num bcx-cart__num--tax" @click.stop>
                                                <input type="number" step="0.01" min="0" class="bcx-input bcx-cart__cell"
                                                    wire:model.blur="cartItems.{{ $cartKey }}.tax" placeholder="0" title="Tax % added to the unit price">
                                            </td>
                                            <td class="bcx-cart__num" @click.stop>
                                                <input type="number" step="0.01" min="0" class="bcx-input bcx-cart__cell {{ $missingPrice ? 'is-missing' : '' }}"
                                                    wire:model.blur="cartItems.{{ $cartKey }}.price" placeholder="MRP"
                                                    title="MRP printed on this row's labels{{ is_numeric($item['unit_price'] ?? null) ? ' — worked out from unit price, weight and tax' : '' }}">
                                            </td>
                                        @endif
                                        <td class="bcx-cart__num" @click.stop>
                                            <div class="bcx-stepper">
                                                <button wire:click="updateQuantity('{{ $cartKey }}', {{ $item['quantity'] - 1 }})" title="Less"><i class="fa fa-minus"></i></button>
                                                <b>{{ $item['quantity'] }}</b>
                                                <button wire:click="updateQuantity('{{ $cartKey }}', {{ $item['quantity'] + 1 }})" title="More"><i class="fa fa-plus"></i></button>
                                            </div>
                                        </td>
                                        <td style="text-align:end;white-space:nowrap" @click.stop>
                                            <button wire:click="duplicateRow('{{ $cartKey }}')" class="bcx-ord" title="Another row for this item">
                                                <i class="fa fa-files-o"></i>
                                            </button>
                                            <button wire:click="removeFromCart('{{ $cartKey }}')" class="bcx-ord" title="Remove">
                                                <i class="fa fa-times"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            {{-- ─── inspector ─── --}}
            <aside class="bcx-drawer bcx-drawer--end">
                <div class="bcx-drawer__title">Label proof <span>{{ $activeTemplate['name'] ?? '' }}</span></div>
                @if ($proofUrl)
                    <div class="bcx-cart__proof">
                        <div class="bcx-stage__sheet"
                            style="width:{{ round($proofWidth * $proofScale) }}px;height:{{ round($proofHeight * $proofScale) }}px">
                            <iframe wire:key="proof-{{ md5($proofUrl) }}" src="{{ $proofUrl }}" class="bcx-stage__frame" scrolling="no"
                                style="width:{{ $proofWidth }}px;height:{{ $proofHeight }}px;transform:scale({{ $proofScale }});transform-origin:top left"></iframe>
                        </div>
                    </div>

                    <div class="bcx-drawer__title">Row <span>{{ $selectedRow['barcode'] }}</span></div>
                    @php
                        $selectedTitledByCategory = $cart['category_name'] && !empty($selectedRow['category_name']);
                    @endphp
                    <div class="bcx-row__label" style="margin-bottom:8px">
                        {{ $selectedTitledByCategory ? $selectedRow['category_name'] : $selectedRow['name'] }}
                        @if ($selectedTitledByCategory)
                            <span class="bcx-cart__category">· {{ $selectedRow['name'] }}</span>
                        @elseif (!empty($selectedRow['category_name']))
                            <span class="bcx-cart__category">· {{ $selectedRow['category_name'] }}</span>
                        @endif
                    </div>
                    @if ($cart['price_column'])
                        <label class="bcx-field">
                            <span>Unit Price</span>
                            <input type="number" step="0.01" min="0" wire:model.blur="cartItems.{{ $selectedRowKey }}.unit_price" placeholder="—">
                        </label>
                        <label class="bcx-field">
                            <span>Tax %</span>
                            <input type="number" step="0.01" min="0" wire:model.blur="cartItems.{{ $selectedRowKey }}.tax" placeholder="0">
                        </label>
                        <label class="bcx-field">
                            <span>MRP</span>
                            <input type="number" step="0.01" min="0" wire:model.blur="cartItems.{{ $selectedRowKey }}.price">
                        </label>
                    @endif
                    @if ($cart['weight_column'])
                        <label class="bcx-field">
                            <span>Weight</span>
                            <div class="bcx-field__unit">
                                <input type="number" step="0.001" min="0" wire:model.blur="cartItems.{{ $selectedRowKey }}.weight" placeholder="0.000">
                                <em>g</em>
                            </div>
                        </label>
                    @endif
                    <label class="bcx-field">
                        <span>Labels</span>
                        <input type="number" min="1" wire:model.blur="cartItems.{{ $selectedRowKey }}.quantity">
                    </label>
                    @isset($selectedRow['available_quantity'])
                        <label class="bcx-field">
                            <span>In stock</span>
                            <b class="bcx-num">{{ $selectedRow['available_quantity'] }}</b>
                        </label>
                    @endisset
                @else
                    <p class="bcx-note">Add an item, then pick a row to see its label.</p>
                @endif

                @if (!empty($cartItems) && ($cart['price_column'] || $cart['weight_column']))
                    <div class="bcx-drawer__title">Fill all rows</div>
                    @if ($cart['price_column'])
                        <label class="bcx-field">
                            <span>Unit Price</span>
                            <input type="number" step="0.01" min="0" wire:model="fillUnitPrice" placeholder="Leave as is">
                        </label>
                        <label class="bcx-field">
                            <span>Tax %</span>
                            <input type="number" step="0.01" min="0" wire:model="fillTax" placeholder="Leave as is">
                        </label>
                        <label class="bcx-field">
                            <span>MRP</span>
                            <input type="number" step="0.01" min="0" wire:model="fillPrice" placeholder="Leave as is">
                        </label>
                    @endif
                    @if ($cart['weight_column'])
                        <label class="bcx-field">
                            <span>Weight</span>
                            <div class="bcx-field__unit">
                                <input type="number" step="0.001" min="0" wire:model="fillWeight" placeholder="Leave as is">
                                <em>g</em>
                            </div>
                        </label>
                    @endif
                    <button wire:click="fillAllRows" class="bcx-btn bcx-cart__wide"><i class="fa fa-magic"></i> Apply to {{ count($cartItems) }} row(s)</button>
                @endif

                <div class="bcx-drawer__title">Print settings</div>
                <div class="bcx-cart__flags">
                    <span class="bcx-chip {{ $cart['weight_mode'] ? 'bcx-chip--brand' : '' }}"><i class="fa fa-balance-scale"></i> {{ $cart['weight_mode'] ? 'Weight' : 'Qty' }}</span>
                    <span class="bcx-chip {{ $cart['separate_rows'] ? 'bcx-chip--brand' : '' }}"><i class="fa fa-{{ $cart['separate_rows'] ? 'check' : 'minus' }}"></i> Row per scan</span>
                    <span class="bcx-chip {{ $cart['auto_fill'] ? 'bcx-chip--brand' : '' }}"><i class="fa fa-{{ $cart['auto_fill'] ? 'magic' : 'pencil' }}"></i> {{ $cart['auto_fill'] ? 'Auto fill' : 'Custom fill' }}</span>
                </div>
                <p class="bcx-note">
                    Qty or Weight follows <b>Quantity Label In Print</b> in Sale settings. Row per scan and Auto/Custom
                    fill are the switches under the scanner.
                </p>
            </aside>
        </div>

        {{-- ═══════════ STATUS BAR ═══════════ --}}
        <div class="bcx-status">
            <span>TEMPLATE <b>{{ $activeTemplate['name'] ?? '—' }}</b></span>
            <span>ROWS <b>{{ count($cartItems) }}</b></span>
            <span>LABELS <b>{{ $this->getTotalQuantity() }}</b></span>
            @if ($cart['weight_column'])
                <span>WEIGHT <b>{{ number_format($this->getTotalWeight(), 3) }} g</b></span>
            @endif
            <span>ROW PER SCAN <b>{{ $separateRows ? 'on' : 'off' }}</b></span>
            <span>FILL <b>{{ $autoFill ? 'auto' : 'custom' }}</b></span>
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

    {{-- ═══════════ PAGE SPECIFIC STYLES (layout only; tokens live in <x-barcode.premium />) ═══════════ --}}
    <style>
        /* Breakpoints measure the shell, not the window: the app sidebar can
           collapse underneath us without the viewport width ever changing. */
        .bcx-cart {
            container: bcx-cart / inline-size;
            display: flex;
            flex-direction: column;
        }

        .bcx-cart__heading {
            min-width: 0;
        }

        .bcx-cart__template {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0;
            color: var(--bcx-faint);
        }

        .bcx-cart__template select {
            min-width: 180px;
        }

        .bcx-cart__body {
            display: grid;
            grid-template-columns: 54px clamp(250px, 24cqw, 310px) minmax(0, 1fr) clamp(250px, 24cqw, 300px);
            min-height: 520px;
        }

        .bcx-cart__body>* {
            min-width: 0;
            min-height: 0;
        }

        .bcx-cart .bcx-drawer {
            max-height: 70vh;
        }

        .bcx-cart__scan {
            display: flex;
            gap: 6px;
            margin-bottom: 10px;
        }

        .bcx-cart__wide {
            width: 100%;
            justify-content: center;
            margin-bottom: 10px;
        }

        .bcx-cart__result {
            gap: 10px;
            width: 100%;
        }

        .bcx-cart__thumb {
            width: 34px;
            height: 34px;
            border-radius: 7px;
            object-fit: cover;
            border: 1px solid var(--bcx-line);
            background: #fff;
            flex: 0 0 auto;
        }

        .bcx-cart__stage {
            padding: 16px;
            overflow: auto;
            max-height: 70vh;
            background:
                linear-gradient(color-mix(in srgb, var(--bcx-ink) 3%, transparent), color-mix(in srgb, var(--bcx-ink) 3%, transparent));
        }

        .bcx-cart__stage>.bcx-empty {
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .bcx-cart__grid tbody tr {
            cursor: pointer;
        }

        .bcx-cart__grid tbody tr.is-selected td {
            background: color-mix(in srgb, var(--bcx-brand) 10%, transparent);
        }

        .bcx-cart__grid tbody tr.is-selected td:first-child {
            box-shadow: inset 3px 0 0 var(--bcx-brand);
        }

        .bcx-cart__name {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 32ch;
        }

        .bcx-cart__category {
            color: var(--bcx-brand);
            font-weight: 600;
        }

        .bcx-cart__num {
            text-align: end;
            width: 130px;
        }

        .bcx-cart__num--tax {
            width: 96px;
        }

        .bcx-cart__num--tax .bcx-cart__cell {
            width: 76px;
        }

        .bcx-cart__cell {
            width: 110px;
            text-align: end;
            font-variant-numeric: tabular-nums;
        }

        .bcx-cart__gram {
            display: inline-flex;
        }

        .bcx-cart__gram.is-missing input,
        .bcx-cart__cell.is-missing {
            border-color: var(--bs-danger, #dc3545);
        }

        .bcx-cart__proof {
            display: grid;
            place-items: center;
            padding: 14px;
            margin-bottom: 12px;
            border-radius: 10px;
            background:
                repeating-linear-gradient(0deg, transparent 0 11px, color-mix(in srgb, var(--bcx-ink) 6%, transparent) 11px 12px),
                repeating-linear-gradient(90deg, transparent 0 11px, color-mix(in srgb, var(--bcx-ink) 6%, transparent) 11px 12px);
        }

        .bcx-cart__flags {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 8px;
        }

        @container bcx-cart (max-width: 1180px) {
            .bcx-cart__body {
                grid-template-columns: 54px clamp(240px, 30cqw, 300px) minmax(0, 1fr);
            }

            .bcx-cart__body>.bcx-drawer--end {
                grid-column: 1 / -1;
                border-inline-start: 0;
                border-top: 1px solid var(--bcx-line);
                max-height: none;
            }
        }

        @container bcx-cart (max-width: 820px) {
            .bcx-cart__body {
                grid-template-columns: 1fr;
            }

            .bcx-cart__body>* {
                grid-column: 1;
            }

            .bcx-cart .bcx-rail {
                flex-direction: row;
                justify-content: center;
                border-inline-end: 0;
                border-bottom: 1px solid var(--bcx-line);
            }

            .bcx-cart .bcx-rail__btn.is-active::before {
                display: none;
            }

            .bcx-cart .bcx-drawer {
                border-inline-end: 0;
                border-bottom: 1px solid var(--bcx-line);
                max-height: none;
            }

            .bcx-cart__template select {
                min-width: 0;
            }
        }

        /* The label is the affordance; on a cramped bar the arrow alone still reads. */
        @container bcx-cart (max-width: 560px) {
            .bcx-back__label {
                display: none;
            }

            .bcx-back {
                padding-inline: 9px;
            }
        }

        [x-cloak] {
            display: none !important;
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

                // dispatch('error', ['message' => ...]) arrives as [{ message }]; named params as { message }.
                const messageOf = (e) => (Array.isArray(e) ? e[0] : e)?.message ?? '';

                Livewire.on('success', (e) => showToast(messageOf(e), 'success'));
                Livewire.on('error', (e) => showToast(messageOf(e), 'error'));

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

                // ── Weight: Enter saves the row and moves to the next row's weight,
                //    or back to the scanner after the last one ──
                document.addEventListener('keydown', function(e) {
                    if (e.key !== 'Enter' || !e.target.matches('[data-gram-input]')) return;
                    e.preventDefault();
                    const inputs = [...document.querySelectorAll('[data-gram-input]')];
                    const next = inputs[inputs.indexOf(e.target) + 1];
                    e.target.blur();
                    (next || document.getElementById('barcodeInput'))?.focus();
                });

                // ── Auto-focus barcode scanner ──
                setTimeout(() => {
                    document.getElementById('barcodeInput')?.focus();
                }, 500);

                // ── After adding an item: weight mode jumps to the new row's weight,
                //    otherwise straight back to the scanner ──
                Livewire.on('success', () => {
                    setTimeout(() => {
                        const empty = [...document.querySelectorAll('[data-gram-input]')].find((input) => !input.value);
                        (empty || document.getElementById('barcodeInput'))?.focus();
                    }, 150);
                });

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

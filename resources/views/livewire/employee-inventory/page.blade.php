@php
    $unitsTotal = collect($items)->sum('quantity');
    $valueTotal = collect($items)->sum(fn($line) => $line['quantity'] * $line['cost']);
    $holdingUnits = $holdings->sum('quantity');
    $holdingValue = $holdings->sum(fn($row) => $row->quantity * $row->cost);
    $reasonPresets = ['In house use','Site installation', 'Service call — tools', 'Sample / demo unit', 'Replacement stock', 'Van stock top-up'];
@endphp

<div class="ivx">
    <x-employee-inventory.premium />

    {{-- ── Deck: who is sending, to whom, and how much ─────────────────── --}}
    <div class="ivx-card ivx-deck mb-3">
        <div class="ivx-deck-top">
            <div class="ivx-mark"><i class="fa fa-exchange"></i></div>
            <div>
                <div class="ivx-title">Transfer Stock to Employee</div>
                <div class="d-flex align-items-center gap-2 flex-wrap mt-1" style="font-size: 11px; color: var(--ivx-mut);">
                    <span><i class="fa fa-building-o"></i> {{ $branch?->name ?? 'No branch selected' }}</span>
                    <span class="ivx-pill">{{ date('d M Y') }}</span>
                </div>
            </div>
            <div class="flex-grow-1"></div>
            <div class="d-flex gap-4 ivx-readout pe-3">
                <div>
                    <div class="ivx-k">Lines</div>
                    <div class="v ivx-num">{{ count($items) }}</div>
                </div>
                <div>
                    <div class="ivx-k">Units</div>
                    <div class="v ivx-num">{{ number_format($unitsTotal, 3) }}</div>
                </div>
                <div>
                    <div class="ivx-k">Value</div>
                    <div class="v ivx-num">{{ number_format($valueTotal, 2) }}</div>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('inventory::index') }}" class="ivx-btn ivx-btn-gho"><i class="fa fa-times"></i> Cancel</a>
                <button type="button" class="ivx-btn ivx-btn-pri" wire:click="transfer" wire:loading.attr="disabled" wire:target="transfer"
                    @disabled(!$employee_id || !count($items) || strlen(trim($reason)) < 3)>
                    <span wire:loading.remove wire:target="transfer"><i class="fa fa-paper-plane"></i> Transfer Stock</span>
                    <span wire:loading wire:target="transfer"><span class="spinner-border spinner-border-sm me-1"></span> Transferring…</span>
                </button>
            </div>
        </div>
        <div class="ivx-flow">
            <div class="node">
                <span class="ivx-av ivx-av-sm ivx-av-quiet"><i class="fa fa-building-o"></i></span>
                <div style="min-width: 0;">
                    <div class="ivx-k">From</div>
                    <div class="nm">{{ $branch?->name ?? '—' }} — Main Stock</div>
                </div>
            </div>
            <div class="arrow"><i class="fa fa-long-arrow-right"></i></div>
            <div class="node {{ $employee ? '' : 'ghost' }}">
                @if ($employee)
                    <img src="{{ $employee['photo'] }}" alt="" class="ivx-av ivx-av-sm">
                @else
                    <span class="ivx-av ivx-av-sm ivx-av-quiet"><i class="fa fa-user-o"></i></span>
                @endif
                <div style="min-width: 0;">
                    <div class="ivx-k">To</div>
                    <div class="nm">
                        {{ $employee ? $employee['name'] . ($employee['designation'] ? ' — ' . $employee['designation'] : '') : 'No employee selected yet' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-8">
            {{-- ── 1 · Recipient ───────────────────────────────────────────── --}}
            <div class="ivx-card mb-3">
                <div class="ivx-sec-h">
                    <div class="ivx-step">1</div>
                    <h3>Who is receiving the stock?</h3>
                </div>
                <div class="ivx-sec-b">
                    @if ($employee)
                        <div class="ivx-who">
                            <img src="{{ $employee['photo'] }}" alt="" class="ivx-av ivx-av-lg">
                            <div style="min-width: 0;">
                                <div class="nm">{{ $employee['name'] }}</div>
                                <div class="sub">
                                    {{ collect([$employee['designation'], $employee['code'], $employee['mobile']])->filter()->implode(' · ') }}
                                </div>
                                <div class="d-flex gap-2 flex-wrap mt-2">
                                    <span class="ivx-pill ivx-pill-ok"><i class="fa fa-check"></i> Active</span>
                                    <span class="ivx-pill">{{ $branch?->name }}</span>
                                    <span class="ivx-pill ivx-pill-acc">
                                        Currently holds {{ $holdings->count() }} product{{ $holdings->count() === 1 ? '' : 's' }}
                                    </span>
                                </div>
                            </div>
                            <div class="ms-auto">
                                <button type="button" class="ivx-btn" wire:click="clearEmployee"><i class="fa fa-refresh"></i> Change</button>
                            </div>
                        </div>
                    @else
                        <div class="ivx-pick" x-data="{ open: false }" @click.outside="open = false">
                            <div class="ivx-pick-field">
                                <i class="fa fa-search"></i>
                                <input type="text" wire:model.live.debounce.300ms="employeeSearch" @focus="open = true"
                                    placeholder="Search employee by name, staff ID, mobile or email…" autocomplete="off">
                                <span wire:loading wire:target="employeeSearch" class="spinner-border spinner-border-sm text-muted"></span>
                            </div>
                            <div class="ivx-pick-menu" x-show="open" x-cloak>
                                <div class="ivx-pick-head">
                                    <span class="ivx-k">Employees of this branch</span>
                                    <span class="ivx-k">{{ $employees->count() }} shown</span>
                                </div>
                                <div class="ivx-pick-scroll">
                                    @forelse ($employees as $option)
                                        <button type="button" class="ivx-pick-opt" wire:key="emp-{{ $option->id }}"
                                            wire:click="selectEmployee({{ $option->id }})" @click="open = false">
                                            <img src="{{ $option->photo_url }}" alt="" class="ivx-av ivx-av-sm">
                                            <div style="min-width: 0;">
                                                <div class="nm">{{ $option->name }}</div>
                                                <div class="sub">
                                                    @if ($option->designation?->name)
                                                        <span>{{ $option->designation->name }}</span>
                                                    @endif
                                                    @if ($option->code)
                                                        <span>{{ $option->code }}</span>
                                                    @endif
                                                    @if ($option->mobile)
                                                        <span>{{ $option->mobile }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="ms-auto">
                                                @if ($option->inventories_count)
                                                    <span class="ivx-pill ivx-pill-acc">holds {{ $option->inventories_count }}</span>
                                                @else
                                                    <span class="ivx-pill">no stock</span>
                                                @endif
                                            </div>
                                        </button>
                                    @empty
                                        <div class="ivx-pick-empty">
                                            <i class="fa fa-search fa-2x d-block mb-2 opacity-50"></i>
                                            No employee of this branch matches “{{ $employeeSearch }}”
                                        </div>
                                    @endforelse
                                </div>
                                <div class="ivx-pick-foot">
                                    <span>Only active employees assigned to {{ $branch?->name }}</span>
                                </div>
                            </div>
                        </div>
                    @endif
                    @error('employee_id')
                        <div class="text-danger mt-2" style="font-size: 12px;">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- ── 2 · Basket ──────────────────────────────────────────────── --}}
            <div class="ivx-card mb-3">
                <div class="ivx-sec-h">
                    <div class="ivx-step">2</div>
                    <h3>What is being handed over?</h3>
                    <span class="ms-auto ivx-k"><i class="fa fa-barcode"></i> Scan a barcode or search by name</span>
                </div>
                <div class="ivx-sec-b pb-0">
                    <div class="ivx-pick" x-data="{ open: false }" @click.outside="open = false">
                        <div class="ivx-pick-field">
                            <i class="fa fa-barcode"></i>
                            <input type="text" wire:model.live.debounce.300ms="productSearch" @focus="open = true"
                                placeholder="Scan barcode, or type product name / code / batch…" autocomplete="off">
                            <span wire:loading wire:target="productSearch" class="spinner-border spinner-border-sm text-muted"></span>
                        </div>
                        <div class="ivx-pick-menu" x-show="open" x-cloak>
                            <div class="ivx-pick-head">
                                <span class="ivx-k">Available in {{ $branch?->name }}</span>
                                <span class="ivx-k">{{ $stock->count() }} shown</span>
                            </div>
                            <div class="ivx-pick-scroll">
                                @forelse ($stock as $row)
                                    <button type="button" class="ivx-pick-opt" wire:key="stock-{{ $row->id }}" wire:click="addItem({{ $row->id }})">
                                        <span class="ivx-thumb" style="width: 34px; height: 34px;"><i class="fa fa-cube"></i></span>
                                        <div style="min-width: 0;">
                                            <div class="nm">{{ $row->name }}</div>
                                            <div class="sub">
                                                <code class="ivx-bc">{{ $row->barcode }}</code>
                                                <span>{{ $row->batch }}</span>
                                            </div>
                                        </div>
                                        <div class="ms-auto text-end">
                                            <span class="ivx-pill {{ $row->quantity > 5 ? 'ivx-pill-ok' : 'ivx-pill-warn' }}">
                                                {{ number_format($row->quantity, 3) }}
                                            </span>
                                            <div class="ivx-k mt-1">{{ number_format($row->cost, 2) }}</div>
                                        </div>
                                    </button>
                                @empty
                                    <div class="ivx-pick-empty">
                                        <i class="fa fa-cube fa-2x d-block mb-2 opacity-50"></i>
                                        Nothing in branch stock matches “{{ $productSearch }}”
                                    </div>
                                @endforelse
                            </div>
                            <div class="ivx-pick-foot">
                                <span>Out-of-stock rows are hidden</span>
                                <span>Added at quantity 1 — adjust below</span>
                            </div>
                        </div>
                    </div>
                    @error('items')
                        <div class="text-danger mt-2" style="font-size: 12px;">{{ $message }}</div>
                    @enderror
                </div>

                @if (count($items))
                    <div class="table-responsive mt-3">
                        <table class="ivx-tbl">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="e">In branch</th>
                                    <th>Transfer qty</th>
                                    <th class="e">Left in branch</th>
                                    <th class="e">Unit cost</th>
                                    <th class="e">Value</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($items as $inventoryId => $line)
                                    @php $left = $line['available'] - $line['quantity']; @endphp
                                    <tr wire:key="line-{{ $inventoryId }}">
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <span class="ivx-thumb"><i class="fa fa-cube"></i></span>
                                                <div style="min-width: 0;">
                                                    <div class="ivx-pname">{{ $line['name'] }}</div>
                                                    <div class="ivx-pmeta">
                                                        <code class="ivx-bc">{{ $line['barcode'] }}</code>
                                                        <span class="ivx-pill">{{ $line['batch'] }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="e">
                                            <span class="ivx-rowlabel">In branch</span>
                                            <span class="ivx-num fw-bold">{{ number_format($line['available'], 3) }}</span>
                                        </td>
                                        <td>
                                            <span class="ivx-rowlabel">Transfer qty</span>
                                            <div class="ivx-qty">
                                                <button type="button" wire:click="decrement({{ $inventoryId }})">&minus;</button>
                                                <input type="number" step="0.001" min="0.001" max="{{ $line['available'] }}"
                                                    value="{{ $line['quantity'] }}"
                                                    wire:model.live.debounce.600ms="items.{{ $inventoryId }}.quantity">
                                                <button type="button" wire:click="increment({{ $inventoryId }})">+</button>
                                            </div>
                                        </td>
                                        <td class="e">
                                            <span class="ivx-rowlabel">Left in branch</span>
                                            <span class="ivx-pill {{ $left <= 0 ? 'ivx-pill-bad' : ($left < 3 ? 'ivx-pill-warn' : 'ivx-pill-ok') }}">
                                                {{ number_format($left, 3) }}
                                            </span>
                                        </td>
                                        <td class="e ivx-num">
                                            <span class="ivx-rowlabel">Unit cost</span>{{ number_format($line['cost'], 2) }}
                                        </td>
                                        <td class="e ivx-num fw-bold">
                                            <span class="ivx-rowlabel">Value</span>{{ number_format($line['cost'] * $line['quantity'], 2) }}
                                        </td>
                                        <td class="e">
                                            <span class="ivx-rowlabel">Remove</span>
                                            <button type="button" class="ivx-btn ivx-btn-gho ivx-btn-ico" title="Remove"
                                                wire:click="removeItem({{ $inventoryId }})">
                                                <i class="fa fa-trash-o"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="ivx-empty">
                        <div class="big"><i class="fa fa-inbox"></i></div>
                        <h4>No products added yet</h4>
                        <p>Scan a barcode or search above — the line lands here with quantity 1.</p>
                    </div>
                @endif
            </div>

            {{-- ── Stock already on the employee's name ────────────────────── --}}
            <div class="ivx-card mb-3">
                <div class="ivx-sec-h">
                    <div class="ivx-step"><i class="fa fa-archive"></i></div>
                    <h3>{{ $employee ? 'Already with ' . str($employee['name'])->before(' ') : 'Stock already with the employee' }}</h3>
                    @if ($employee)
                        <span class="ivx-pill ivx-pill-acc">{{ $holdings->count() }} product{{ $holdings->count() === 1 ? '' : 's' }}</span>
                        <span class="ivx-pill">{{ number_format($holdingUnits, 3) }} units</span>
                        <span class="ivx-pill">{{ number_format($holdingValue, 2) }}</span>
                        <a href="{{ route('inventory::index') }}" class="ivx-btn ivx-btn-gho ms-auto">
                            <i class="fa fa-list"></i> Movement log
                        </a>
                    @endif
                </div>

                @if (!$employee)
                    <div class="ivx-empty" style="padding: 26px 18px;">
                        <p>Pick an employee above and everything currently on their name shows here.</p>
                    </div>
                @elseif ($holdings->isEmpty())
                    <div class="ivx-empty" style="padding: 26px 18px;">
                        <div class="big"><i class="fa fa-user-o"></i></div>
                        <h4>Nothing on their name yet</h4>
                        <p>This will be their first assignment from the branch.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="ivx-tbl">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="e">Held</th>
                                    <th class="e">Unit cost</th>
                                    <th class="e">Value</th>
                                    <th>Since</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($holdings as $row)
                                    <tr wire:key="hold-{{ $row->id }}">
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <span class="ivx-thumb"><i class="fa fa-cube"></i></span>
                                                <div style="min-width: 0;">
                                                    <div class="ivx-pname">{{ $row->name }}</div>
                                                    <div class="ivx-pmeta">
                                                        <code class="ivx-bc">{{ $row->barcode }}</code>
                                                        <span class="ivx-pill">{{ $row->batch }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="e">
                                            <span class="ivx-rowlabel">Held</span>
                                            <span class="ivx-pill ivx-pill-acc">{{ number_format($row->quantity, 3) }}</span>
                                        </td>
                                        <td class="e ivx-num">
                                            <span class="ivx-rowlabel">Unit cost</span>{{ number_format($row->cost, 2) }}
                                        </td>
                                        <td class="e ivx-num fw-bold">
                                            <span class="ivx-rowlabel">Value</span>{{ number_format($row->cost * $row->quantity, 2) }}
                                        </td>
                                        <td>
                                            <span class="ivx-rowlabel">Since</span>
                                            <span class="text-muted" style="font-size: 11.5px;">{{ $row->updated_at?->format('d M Y') }}</span>
                                        </td>
                                        <td class="e">
                                            <span class="ivx-rowlabel">Return</span>
                                            <button type="button" class="ivx-btn ivx-btn-gho" wire:click="returnToBranch({{ $row->id }})"
                                                wire:confirm="Return all {{ number_format($row->quantity, 3) }} of {{ $row->name }} to {{ $branch?->name }}?">
                                                <i class="fa fa-reply"></i> Return
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- ── 3 · Confirm rail ────────────────────────────────────────────── --}}
        <div class="col-xl-4">
            <div class="ivx-card ivx-rail">
                <div class="ivx-sec-h">
                    <div class="ivx-step">3</div>
                    <h3>Confirm &amp; transfer</h3>
                </div>
                <div class="ivx-sec-b d-flex flex-column gap-3">
                    <div class="ivx-sum">
                        <div class="r">
                            <span class="lbl">Recipient</span>
                            <span class="val" style="font-size: 12.5px;">{{ $employee['name'] ?? '—' }}</span>
                        </div>
                        <div class="r">
                            <span class="lbl">Products</span>
                            <span class="val ivx-num">{{ count($items) }}</span>
                        </div>
                        <div class="r">
                            <span class="lbl">Total units</span>
                            <span class="val ivx-num">{{ number_format($unitsTotal, 3) }}</span>
                        </div>
                        <div class="r total">
                            <span class="lbl">Stock value</span>
                            <span class="val ivx-num">{{ number_format($valueTotal, 2) }}</span>
                        </div>
                    </div>

                    <div>
                        <label class="ivx-label" for="ivx-reason">Reason <span class="text-danger">*</span></label>
                        <div class="d-flex gap-2 flex-wrap mb-2">
                            @foreach ($reasonPresets as $preset)
                                <button type="button" class="ivx-rchip {{ $reason === $preset ? 'on' : '' }}"
                                    wire:click="setReason('{{ $preset }}')">{{ $preset }}</button>
                            @endforeach
                        </div>
                        <textarea id="ivx-reason" class="ivx-inp" wire:model.blur="reason" rows="3"
                            placeholder="Why is this stock leaving the branch? e.g. site installation at Villa 12"></textarea>
                        @error('reason')
                            <div class="text-danger mt-1" style="font-size: 12px;">{{ $message }}</div>
                        @enderror
                        <div class="text-muted mt-2" style="font-size: 11px;">
                            <i class="fa fa-lock"></i> Saved to the inventory log against your user for audit.
                        </div>
                    </div>

                    <div class="ivx-guard">
                        <i class="fa fa-shield"></i>
                        <div>The employee becomes accountable for these units until they are returned or sold. A return entry reverses it back to the branch.</div>
                    </div>

                    <button type="button" class="ivx-btn ivx-btn-pri ivx-btn-lg w-100 justify-content-center" wire:click="transfer"
                        wire:loading.attr="disabled" wire:target="transfer"
                        @disabled(!$employee_id || !count($items) || strlen(trim($reason)) < 3)>
                        <span wire:loading.remove wire:target="transfer"><i class="fa fa-paper-plane"></i> Transfer Stock</span>
                        <span wire:loading wire:target="transfer"><span class="spinner-border spinner-border-sm me-1"></span> Transferring…</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

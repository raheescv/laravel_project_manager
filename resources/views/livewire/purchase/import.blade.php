<div class="pix">
    <x-purchase.import-premium />

    {{-- ============================================================ deck === --}}
    <div class="pix-deck">
        <div class="pix-deck__top">
            <div>
                <h1 class="pix-deck__title">
                    <i class="fa fa-cloud-upload"></i>
                    Invoice Upload
                </h1>
                <p class="pix-deck__sub">
                    Type the invoice head, upload the vendor's item sheet, review every line — saved as a draft purchase.
                </p>
            </div>

            <div class="pix-steps ms-lg-3">
                @foreach (['Invoice', 'Upload & Map', 'Review'] as $i => $label)
                    @if ($i)
                        <span class="pix-steps__sep"></span>
                    @endif
                    <span @class([
                        'pix-step',
                        'is-active' => $step === $i + 1,
                        'is-done' => $step > $i + 1,
                    ])>
                        <span class="pix-step__n">
                            @if ($step > $i + 1)
                                <i class="fa fa-check"></i>
                            @else
                                {{ $i + 1 }}
                            @endif
                        </span>
                        {{ $label }}
                    </span>
                @endforeach
            </div>

            <div class="pix-deck__spacer"></div>

            <div class="pix-deck__actions">
                @if ($step === 1)
                    <a href="{{ route('purchase::index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fa fa-times me-1"></i> Cancel
                    </a>
                    <button type="button" class="btn btn-sm btn-primary" wire:click="goToUpload">
                        Continue <i class="fa fa-arrow-right ms-1"></i>
                    </button>
                @elseif ($step === 2)
                    <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="$set('step', 1)">
                        <i class="fa fa-arrow-left me-1"></i> Back
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary" wire:click="downloadTemplate">
                        <i class="fa fa-download me-1"></i> Template
                    </button>
                    <button type="button" class="btn btn-sm btn-primary" wire:click="buildRows" @disabled(!$fileName)>
                        <span wire:loading.remove wire:target="buildRows">Match Lines <i class="fa fa-arrow-right ms-1"></i></span>
                        <span wire:loading wire:target="buildRows"><i class="fa fa-refresh fa-spin me-1"></i> Matching…</span>
                    </button>
                @else
                    <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="$set('step', 2)">
                        <i class="fa fa-arrow-left me-1"></i> Mapping
                    </button>
                    <button type="button" class="btn btn-sm btn-success" wire:click="save" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save"><i class="fa fa-check me-1"></i> Create Draft Purchase</span>
                        <span wire:loading wire:target="save"><i class="fa fa-refresh fa-spin me-1"></i> Creating…</span>
                    </button>
                @endif
            </div>
        </div>

        <div class="pix-pods">
            <div class="pix-pod">
                <span class="pix-pod__k">Vendor</span>
                <span class="pix-pod__v" style="font-size:12px">{{ $vendor_name ?: '—' }}</span>
            </div>
            <div class="pix-pod">
                <span class="pix-pod__k">Invoice</span>
                <span class="pix-pod__v" style="font-size:12px">{{ $invoice_no ?: '—' }}</span>
            </div>
            <div class="pix-pod">
                <span class="pix-pod__k">Date</span>
                <span class="pix-pod__v" style="font-size:12px">{{ $date ? systemDate($date) : '—' }}</span>
            </div>
            <div class="pix-pod">
                <span class="pix-pod__k">Lines</span>
                <span class="pix-pod__v">{{ $totals['lines'] ?? 0 }}</span>
            </div>
            <div class="pix-pod pix-pod--ok">
                <span class="pix-pod__k">Ready</span>
                <span class="pix-pod__v">{{ $this->readyCount }}</span>
            </div>
            <div @class(['pix-pod', 'pix-pod--bad' => $this->issueCount])>
                <span class="pix-pod__k">Issues</span>
                <span class="pix-pod__v">{{ $this->issueCount }}</span>
            </div>
            <div class="pix-pod pix-pod--acc">
                <span class="pix-pod__k">Grand Total</span>
                <span class="pix-pod__v">{{ currency($totals['grand_total'] ?? 0) }}</span>
            </div>
        </div>
    </div>

    {{-- ====================================================== step 1 ====== --}}
    {{-- kept in the DOM (hidden) so the vendor TomSelect survives step changes --}}
    <div @class(['d-none' => $step !== 1])>
        <div class="row g-3">
            <div class="col-lg-8">
                <div class="pix-panel">
                    <div class="pix-panel__hd">
                        <i class="fa fa-file-text-o"></i>
                        <h6>Invoice Head</h6>
                        <small class="ms-auto">Typed by hand — the sheet only carries the items</small>
                    </div>
                    <div class="pix-panel__bd">
                        <div class="row g-3">
                            <div class="col-md-6" wire:ignore>
                                <label class="pix-lbl">Vendor <span class="pix-map__req">*</span></label>
                                {{ html()->select('vendor_id')->value($account_id)->class('select-vendor_id')->id('vendor_id')->placeholder('Search vendor by name, mobile or email') }}
                            </div>
                            <div class="col-md-6">
                                <label class="pix-lbl">Invoice No <span class="pix-map__req">*</span></label>
                                <input type="text" class="form-control" placeholder="Vendor's invoice number" wire:model.blur="invoice_no" value="{{ $invoice_no }}">
                                @error('invoice_no')
                                    <span class="pix-err">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="pix-lbl">Invoice Date <span class="pix-map__req">*</span></label>
                                <input type="date" class="form-control" wire:model.live="date" value="{{ $date }}">
                                @error('date')
                                    <span class="pix-err">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="pix-lbl">Delivery Date</label>
                                <input type="date" class="form-control" wire:model="delivery_date" value="{{ $delivery_date }}">
                            </div>
                            <div class="col-md-4">
                                <label class="pix-lbl">Branch</label>
                                <input type="text" class="form-control" value="{{ \App\Models\Branch::find(session('branch_id'))?->name ?? '—' }}" disabled>
                                <span class="pix-hint">Stock lands in your active branch</span>
                            </div>
                            <div class="col-md-4">
                                <label class="pix-lbl">Other Discount</label>
                                <input type="text" class="form-control text-end" wire:model.blur="other_discount" value="{{ $other_discount }}">
                            </div>
                            <div class="col-md-4">
                                <label class="pix-lbl">Freight</label>
                                <input type="text" class="form-control text-end" wire:model.blur="freight" value="{{ $freight }}">
                            </div>
                            <div class="col-md-4">
                                <label class="pix-lbl">Address / Note</label>
                                <input type="text" class="form-control" placeholder="Optional" wire:model="address" value="{{ $address }}">
                            </div>
                        </div>
                        @error('account_id')
                            <span class="pix-err mt-2">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="pix-panel">
                    <div class="pix-panel__hd">
                        <i class="fa fa-info-circle"></i>
                        <h6>How this works</h6>
                    </div>
                    <div class="pix-panel__bd">
                        <ol class="ps-3 mb-3" style="color:var(--pix-ink2);line-height:1.9">
                            <li>Enter the vendor, date and invoice number here.</li>
                            <li>Upload the item sheet and confirm which column is which — most files map themselves.</li>
                            <li>Every line is matched against the catalogue; fix or drop the flagged ones.</li>
                            <li>Save. You get a <strong>draft</strong> purchase — nothing hits stock or the ledger until you complete it.</li>
                        </ol>
                        @if ($vendor_name)
                            <div class="pix-note">
                                <i class="fa fa-user mt-1"></i>
                                <div>
                                    <strong>{{ $vendor_name }}</strong><br>
                                    <span style="color:var(--pix-mut)">
                                        Ledger balance: {{ $vendor_balance !== null ? currency($vendor_balance) : 'n/a' }}
                                    </span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ====================================================== step 2 ====== --}}
    <div @class(['d-none' => $step !== 2])>
        <div class="row g-3">
            <div class="col-lg-5">
                <div class="pix-panel">
                    <div class="pix-panel__hd">
                        <i class="fa fa-table"></i>
                        <h6>Item Sheet</h6>
                        <small class="ms-auto">xlsx, xls or csv · up to {{ \App\Livewire\Purchase\Import::MAX_ROWS }} lines</small>
                    </div>
                    <div class="pix-panel__bd">
                        @if (!$fileName)
                            <div class="pix-drop" wire:loading.class="opacity-50" wire:target="file">
                                <input type="file" wire:model="file" accept=".xlsx,.xls,.csv">
                                <div wire:loading.remove wire:target="file">
                                    <div class="pix-drop__icon"><i class="fa fa-cloud-upload"></i></div>
                                    <div class="pix-drop__t">Drop the invoice sheet here</div>
                                    <div class="pix-drop__s">or click to browse</div>
                                </div>
                                <div wire:loading wire:target="file">
                                    <div class="pix-drop__icon"><i class="fa fa-refresh fa-spin"></i></div>
                                    <div class="pix-drop__t">Reading the sheet…</div>
                                </div>
                            </div>
                            @error('file')
                                <span class="pix-err">{{ $message }}</span>
                            @enderror
                            <div class="pix-note mt-3">
                                <i class="fa fa-lightbulb-o mt-1"></i>
                                <div>Any column layout works — you map the columns on the right. Need a starting point?
                                    <button type="button" class="btn btn-link btn-sm p-0 align-baseline" wire:click="downloadTemplate">download the template</button>.
                                </div>
                            </div>
                        @else
                            <div class="pix-file">
                                <i class="fa fa-file-excel-o pix-file__ic"></i>
                                <div class="flex-grow-1">
                                    <div class="fw-bold">{{ $fileName }}</div>
                                    <div style="color:var(--pix-mut)">
                                        {{ count($rawRows) }} line(s) · {{ count($columns) }} column(s)
                                    </div>
                                </div>
                                <button type="button" class="pix-iconbtn pix-iconbtn--bad" wire:click="removeFile" title="Remove file">
                                    <i class="fa fa-times"></i>
                                </button>
                            </div>

                            @if ($truncated)
                                <div class="pix-note pix-note--warn mt-3">
                                    <i class="fa fa-exclamation-triangle mt-1"></i>
                                    <div>Only the first {{ \App\Livewire\Purchase\Import::MAX_ROWS }} lines were read. Split larger invoices into
                                        several files.</div>
                                </div>
                            @endif

                            <div class="mt-3">
                                <label class="pix-lbl">File Preview</label>
                                <div style="overflow:auto;max-height:220px;border:1px solid var(--pix-ln);border-radius:10px">
                                    <table class="pix-prev">
                                        <thead>
                                            <tr>
                                                @foreach ($columns as $label)
                                                    <th>{{ $label }}</th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($previewRows as $row)
                                                <tr>
                                                    @foreach ($columns as $index => $label)
                                                        <td>{{ $row[$index] ?? '' }}</td>
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="pix-panel">
                    <div class="pix-panel__hd">
                        <i class="fa fa-random"></i>
                        <h6>Column Mapping</h6>
                        <small class="ms-auto">
                            @if ($fileName)
                                <span class="pix-chip pix-chip--acc"><i class="fa fa-magic"></i> {{ $this->mappedCount }} auto-detected</span>
                            @else
                                Upload a sheet to map its columns
                            @endif
                        </small>
                    </div>
                    <div class="pix-panel__bd">
                        @if (!$fileName)
                            <div class="pix-empty">
                                <i class="fa fa-random"></i>
                                Column mapping appears once a sheet is loaded.
                            </div>
                        @else
                            @error('mapping')
                                <div class="pix-note pix-note--warn mb-3">
                                    <i class="fa fa-exclamation-triangle mt-1"></i>
                                    <div>{{ $message }}</div>
                                </div>
                            @enderror

                            <div class="pix-map">
                                @foreach ($this->fields as $field => $meta)
                                    <div @class(['pix-map__row', 'is-set' => ($mapping[$field] ?? '') !== ''])>
                                        <div class="pix-map__top">
                                            <span class="pix-map__name">
                                                {{ $meta['label'] }}
                                                @if (in_array($field, ['unit_price']))
                                                    <span class="pix-map__req">*</span>
                                                @endif
                                            </span>
                                            @if (($mapping[$field] ?? '') !== '')
                                                <span class="pix-chip pix-chip--ok"><i class="fa fa-check"></i></span>
                                            @endif
                                        </div>
                                        <select class="form-select" wire:model.live="mapping.{{ $field }}">
                                            <option value="" @selected(($mapping[$field] ?? '') === '')>— not in file —</option>
                                            @foreach ($columns as $index => $label)
                                                <option value="{{ $index }}" @selected((string) ($mapping[$field] ?? '') === (string) $index)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <span class="pix-hint">{{ $meta['hint'] }}</span>
                                    </div>
                                @endforeach
                            </div>

                            <div class="row g-2 mt-3">
                                <div class="col-md-4">
                                    <label class="pix-lbl">Match Products By</label>
                                    <select class="form-select" wire:model.live="matchBy">
                                        <option value="auto" @selected($matchBy === 'auto')>Auto (code → barcode → name)</option>
                                        <option value="code" @selected($matchBy === 'code')>Product code only</option>
                                        <option value="barcode" @selected($matchBy === 'barcode')>Barcode only</option>
                                        <option value="name" @selected($matchBy === 'name')>Name only</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="pix-lbl">Default Tax %</label>
                                    <input type="text" class="form-control text-end" wire:model="defaultTax" value="{{ $defaultTax }}">
                                    <span class="pix-hint">Used when the sheet has no tax column</span>
                                </div>
                                <div class="col-md-4">
                                    <label class="pix-lbl">Duplicate Products</label>
                                    <input type="text" class="form-control" disabled
                                        value="{{ $rowMode === 'separate' ? 'Kept as separate lines' : 'Merged into one line' }}">
                                    <span class="pix-hint">From Settings → Purchase row mode</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ====================================================== step 3 ====== --}}
    <div @class(['d-none' => $step !== 3])>
        <div class="row g-3">
            <div class="col-xl-9">
                <div class="pix-panel">
                    <div class="pix-panel__hd">
                        <i class="fa fa-list"></i>
                        <h6>Matched Lines</h6>
                        <div class="ms-auto d-flex align-items-center gap-2 flex-wrap">
                            @if ($mergedRows)
                                <span class="pix-chip pix-chip--mut" title="Repeated products were folded together">
                                    <i class="fa fa-compress"></i> {{ $mergedRows }} merged
                                </span>
                            @endif
                            @if ($this->issueCount)
                                <button type="button" class="btn btn-sm btn-outline-danger py-0" wire:click="dropUnmatched"
                                    wire:confirm="Remove all {{ $this->issueCount }} flagged line(s)?">
                                    <i class="fa fa-ban me-1"></i> Drop flagged
                                </button>
                            @endif
                            <div class="pix-seg">
                                <button type="button" @class(['is-on' => $rowFilter === 'all']) wire:click="$set('rowFilter', 'all')">
                                    All {{ count($items) }}
                                </button>
                                <button type="button" @class(['is-on' => $rowFilter === 'ready']) wire:click="$set('rowFilter', 'ready')">
                                    Ready {{ $this->readyCount }}
                                </button>
                                <button type="button" @class(['is-on' => $rowFilter === 'issues']) wire:click="$set('rowFilter', 'issues')">
                                    Issues {{ $this->issueCount }}
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="pix-panel__bd pix-panel__bd--flush">
                        @if (!count($items))
                            <div class="pix-empty">
                                <i class="fa fa-inbox"></i>
                                No lines yet — upload a sheet and map its columns.
                            </div>
                        @else
                            <div class="pix-tblwrap">
                                <table class="pix-tbl">
                                    <thead>
                                        <tr>
                                            <th style="width:44px">#</th>
                                            <th>Product</th>
                                            <th style="width:96px">Batch</th>
                                            <th class="num" style="width:84px">Qty</th>
                                            <th class="num" style="width:96px">Rate</th>
                                            <th class="num" style="width:90px">Discount</th>
                                            <th class="num" style="width:74px">Tax %</th>
                                            <th class="num" style="width:104px">Total</th>
                                            <th style="width:64px"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($this->visibleItems as $index => $item)
                                            <tr wire:key="line-{{ $index }}" @class(['is-bad' => $item['status'] !== 'ok'])>
                                                <td class="pix-tbl__line">{{ $item['line'] }}</td>
                                                <td>
                                                    <div class="pix-tbl__name">{{ $item['name'] }}</div>
                                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                                        @if ($item['code'])
                                                            <span class="pix-tbl__meta">{{ $item['code'] }}</span>
                                                        @endif
                                                        @if ($item['barcode'])
                                                            <span class="pix-tbl__meta">· {{ $item['barcode'] }}</span>
                                                        @endif
                                                        @if ($item['status'] === 'ok')
                                                            <span class="pix-chip pix-chip--ok">
                                                                <i class="fa fa-check"></i>
                                                                {{ $item['matched_on'] === 'manual' ? 'manual' : $item['matched_on'] }}
                                                            </span>
                                                        @elseif ($item['status'] === 'unmatched')
                                                            <span class="pix-chip pix-chip--bad"><i class="fa fa-question-circle"></i> no match</span>
                                                        @else
                                                            <span class="pix-chip pix-chip--warn"><i class="fa fa-exclamation-triangle"></i> check values</span>
                                                        @endif
                                                        @if (!empty($item['merged_lines']))
                                                            <span class="pix-chip pix-chip--mut">+{{ count($item['merged_lines']) }} merged</span>
                                                        @endif
                                                    </div>
                                                    @if ($item['message'])
                                                        <div class="pix-err">{{ $item['message'] }}</div>
                                                    @endif
                                                </td>
                                                <td>
                                                    <input type="text" class="pix-inp text-start" style="text-align:left"
                                                        wire:model.blur="items.{{ $index }}.batch" value="{{ $item['batch'] }}">
                                                </td>
                                                <td class="num">
                                                    <input type="text" class="pix-inp" wire:model.blur="items.{{ $index }}.quantity" value="{{ $item['quantity'] }}">
                                                </td>
                                                <td class="num">
                                                    <input type="text" class="pix-inp" wire:model.blur="items.{{ $index }}.unit_price" value="{{ $item['unit_price'] }}">
                                                </td>
                                                <td class="num">
                                                    <input type="text" class="pix-inp" wire:model.blur="items.{{ $index }}.discount" value="{{ $item['discount'] }}">
                                                </td>
                                                <td class="num">
                                                    <input type="text" class="pix-inp" wire:model.blur="items.{{ $index }}.tax" value="{{ $item['tax'] }}">
                                                </td>
                                                <td class="num fw-bold">{{ currency($item['total']) }}</td>
                                                <td>
                                                    <div class="d-flex gap-1">
                                                        <button type="button" class="pix-iconbtn" title="Match to a product"
                                                            wire:click="openResolve({{ $index }})">
                                                            <i class="fa fa-search"></i>
                                                        </button>
                                                        <button type="button" class="pix-iconbtn pix-iconbtn--bad" title="Remove line"
                                                            wire:click="removeItem({{ $index }})">
                                                            <i class="fa fa-trash-o"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="9">
                                                    <div class="pix-empty">
                                                        <i class="fa fa-filter"></i>
                                                        No line matches this filter.
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-xl-3">
                <div class="pix-rail">
                    <div class="pix-panel">
                        <div class="pix-panel__hd">
                            <i class="fa fa-calculator"></i>
                            <h6>Draft Summary</h6>
                        </div>
                        <div class="pix-panel__bd">
                            <div class="pix-sum"><span>Lines ready</span><span>{{ $this->readyCount }} / {{ count($items) }}</span></div>
                            <div class="pix-sum"><span>Total quantity</span><span>{{ currency($totals['quantity'] ?? 0, 3) }}</span></div>
                            <div class="pix-sum"><span>Gross</span><span>{{ currency($totals['gross_amount'] ?? 0) }}</span></div>
                            <div class="pix-sum"><span>Item discount</span><span>{{ currency($totals['item_discount'] ?? 0) }}</span></div>
                            <div class="pix-sum"><span>Tax</span><span>{{ currency($totals['tax_amount'] ?? 0) }}</span></div>
                            <div class="pix-sum"><span>Net total</span><span>{{ currency($totals['total'] ?? 0) }}</span></div>

                            <div class="row g-2 mt-2">
                                <div class="col-6">
                                    <label class="pix-lbl">Other Disc.</label>
                                    <input type="text" class="form-control text-end" wire:model.blur="other_discount" value="{{ $other_discount }}">
                                </div>
                                <div class="col-6">
                                    <label class="pix-lbl">Freight</label>
                                    <input type="text" class="form-control text-end" wire:model.blur="freight" value="{{ $freight }}">
                                </div>
                            </div>

                            <div class="pix-sum pix-sum--grand">
                                <span>Grand Total</span>
                                <span>{{ currency($totals['grand_total'] ?? 0) }}</span>
                            </div>

                            <div class="pix-switch mt-3">
                                <div>
                                    <div class="fw-bold">Skip unresolved lines</div>
                                    <div style="color:var(--pix-mut)">Import the ready lines and leave the rest out</div>
                                </div>
                                <div class="form-check form-switch m-0">
                                    <input class="form-check-input" type="checkbox" wire:model.live="skipUnmatched" @checked($skipUnmatched)>
                                </div>
                            </div>

                            <div class="pix-note mt-3">
                                <i class="fa fa-shield mt-1"></i>
                                <div>Saved as <strong>draft</strong> — no stock movement and no journal entry until you complete the purchase.</div>
                            </div>

                            <button type="button" class="btn btn-success w-100 mt-3" wire:click="save" wire:loading.attr="disabled" wire:target="save">
                                <span wire:loading.remove wire:target="save"><i class="fa fa-check me-1"></i> Create Draft Purchase</span>
                                <span wire:loading wire:target="save"><i class="fa fa-refresh fa-spin me-1"></i> Creating…</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ================================================ resolve overlay === --}}
    @if ($resolvingIndex !== null && isset($items[$resolvingIndex]))
        <div class="pix-resolve" wire:key="resolve-{{ $resolvingIndex }}">
            <div class="pix-resolve__box">
                <div class="pix-panel__hd">
                    <i class="fa fa-search"></i>
                    <h6>Match line {{ $items[$resolvingIndex]['line'] }} to a product</h6>
                    <button type="button" class="pix-iconbtn ms-auto" wire:click="closeResolve"><i class="fa fa-times"></i></button>
                </div>
                <div class="pix-panel__bd">
                    <div class="pix-note mb-3">
                        <i class="fa fa-file-text-o mt-1"></i>
                        <div>
                            <strong>From the sheet:</strong>
                            {{ $items[$resolvingIndex]['raw_name'] ?: '—' }}
                            @if ($items[$resolvingIndex]['raw_code'])
                                · code {{ $items[$resolvingIndex]['raw_code'] }}
                            @endif
                            @if ($items[$resolvingIndex]['raw_barcode'])
                                · barcode {{ $items[$resolvingIndex]['raw_barcode'] }}
                            @endif
                        </div>
                    </div>
                    <input type="text" class="form-control mb-3" placeholder="Search by name, code or barcode…"
                        wire:model.live.debounce.350ms="productSearch" value="{{ $productSearch }}" autofocus>
                </div>
                <div class="pix-panel__bd pt-0" style="overflow:auto">
                    @forelse ($productResults as $product)
                        <button type="button" class="pix-hit" wire:click="assignProduct({{ $product['id'] }})">
                            <i class="fa fa-cube" style="color:var(--pix-acc-ink)"></i>
                            <span class="flex-grow-1">
                                <span class="d-block fw-bold">{{ $product['name'] }}</span>
                                <span class="pix-tbl__meta">
                                    {{ $product['code'] ?: '—' }} · {{ $product['barcode'] ?: 'no barcode' }} · cost {{ currency($product['cost']) }}
                                </span>
                            </span>
                            <i class="fa fa-arrow-right" style="color:var(--pix-mut)"></i>
                        </button>
                    @empty
                        <div class="pix-empty py-4">
                            <i class="fa fa-search"></i>
                            {{ strlen(trim($productSearch)) < 2 ? 'Type at least two characters.' : 'No product matches that search.' }}
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    @push('scripts')
        @include('components.select.vendorSelect')
        <script>
            $(document).ready(function() {
                $('#vendor_id').on('change', function() {
                    @this.set('account_id', $(this).val() || null);
                });

                window.addEventListener('AddToVendorSelectBox', event => {
                    var data = event.detail[0];
                    var tomSelectInstance = document.querySelector('#vendor_id').tomselect;
                    if (data && data['name']) {
                        tomSelectInstance.addOption({
                            id: data['id'],
                            name: data['name']
                        });
                        tomSelectInstance.setValue(data['id']);
                        @this.set('account_id', data['id']);
                    }
                });
            });
        </script>
    @endpush
</div>

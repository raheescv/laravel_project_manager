{{--
    Scan a purchase invoice into the cart.

    Three steps: upload the vendor's PDF (or a photo of a paper copy), say which
    column is what, then look over what was found and send the good lines to the
    cart. Nothing is saved here — the lines land in the same cart typing would
    have filled, and the deck's own Save Draft / Submit still does the saving.

    Bootstrap only, and shown by rendering `.modal.show.d-block` rather than by
    Bootstrap's modal JS: Livewire re-renders this markup on every interaction,
    and a JS-managed modal loses its backdrop when that happens.
--}}
@can('purchase.scan invoice')
    <div>
        @if ($scanOpen)
            <div class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true"
                aria-labelledby="scanInvoiceTitle">
                <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content">

                        <div class="modal-header">
                            <h5 class="modal-title" id="scanInvoiceTitle">
                                <i class="fa fa-file-pdf-o text-primary me-2"></i>Scan Purchase Invoice
                            </h5>
                            <button type="button" class="btn-close" aria-label="Close"
                                wire:click="closeInvoiceScan"></button>
                        </div>

                        <div class="modal-body">

                            @if ($scanError)
                                <div class="alert alert-warning d-flex align-items-start gap-2">
                                    <i class="fa fa-exclamation-triangle mt-1"></i>
                                    <div>{{ $scanError }}</div>
                                </div>
                            @endif

                            {{-- ─────────────────────────────── step 1 · upload ── --}}
                            @if ($scanStep === 1)
                                <div class="text-center py-4">
                                    <div class="mb-3">
                                        <i class="fa fa-cloud-upload fa-3x text-body-tertiary"></i>
                                    </div>
                                    <h6 class="mb-1">Upload the vendor's invoice</h6>
                                    <p class="text-body-secondary small mb-4">
                                        A PDF reads exactly. A photo or scan is read by OCR, so check the figures
                                        before you add them.
                                    </p>

                                    <div class="mx-auto" style="max-width:420px">
                                        <input type="file" class="form-control" wire:model="scanFile"
                                            accept=".pdf,.jpg,.jpeg,.png,.webp,.bmp,.tif,.tiff">
                                        @error('scanFile')
                                            <div class="text-danger small mt-2">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div wire:loading wire:target="scanFile" class="mt-3 text-body-secondary small">
                                        <i class="fa fa-spinner fa-spin me-1"></i> Reading the invoice…
                                    </div>
                                </div>
                            @endif

                            {{-- ────────────────────────────── step 2 · mapping ── --}}
                            @if ($scanStep === 2)
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                                    <span class="badge text-bg-light border">
                                        <i class="fa fa-file-o me-1"></i>{{ $scanFileName }}
                                    </span>
                                    <span class="badge text-bg-light border">
                                        {{ count($scanRows) }} {{ Str::plural('line', count($scanRows)) }}
                                    </span>
                                    <span class="badge text-bg-light border">
                                        {{ count($scanBands) }} columns
                                    </span>
                                    @if ($scanSource === 'ocr')
                                        <span class="badge text-bg-warning">
                                            <i class="fa fa-camera me-1"></i>Read by OCR
                                        </span>
                                    @endif
                                </div>

                                <p class="text-body-secondary small">
                                    Tell us what each column is. Most invoices print no column headings we can read,
                                    so this is asked once — the layout is then remembered for this vendor.
                                </p>

                                <div class="row g-3 mb-4">
                                    @foreach ($this->fields as $field => $meta)
                                        <div class="col-md-3 col-sm-6">
                                            <label class="form-label small fw-semibold mb-1">{{ $meta['label'] }}</label>
                                            <select class="form-select form-select-sm"
                                                wire:model="scanMapping.{{ $field }}">
                                                <option value="" @selected(($scanMapping[$field] ?? '') === '')>— not in the file —</option>
                                                @foreach ($scanColumns as $index => $label)
                                                    <option value="{{ $index }}" @selected((string) ($scanMapping[$field] ?? '') === (string) $index)>
                                                        {{ $label }}
                                                        @if (($scanRows[0][$index] ?? '') !== '')
                                                            · {{ Str::limit($scanRows[0][$index], 18) }}
                                                        @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="form-text small">{{ $meta['hint'] }}</div>
                                        </div>
                                    @endforeach

                                    <div class="col-md-3 col-sm-6">
                                        <label class="form-label small fw-semibold mb-1">Line Total</label>
                                        <select class="form-select form-select-sm" wire:model="scanTotalColumn">
                                            <option value="" @selected($scanTotalColumn === '')>— not in the file —</option>
                                            @foreach ($scanColumns as $index => $label)
                                                <option value="{{ $index }}" @selected((string) $scanTotalColumn === (string) $index)>
                                                    {{ $label }}
                                                    @if (($scanRows[0][$index] ?? '') !== '')
                                                        · {{ Str::limit($scanRows[0][$index], 18) }}
                                                    @endif
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="form-text small">Checked against our arithmetic, never imported</div>
                                    </div>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-3 col-sm-6">
                                        <label class="form-label small fw-semibold mb-1">Match products by</label>
                                        <select class="form-select form-select-sm" wire:model="matchBy">
                                            @foreach (['auto' => 'Anything that fits', 'code' => 'Product code', 'barcode' => 'Barcode', 'name' => 'Name'] as $value => $label)
                                                <option value="{{ $value }}" @selected($matchBy === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <label class="form-label small fw-semibold mb-1">Tax % when the file has none</label>
                                        <input type="number" step="0.01" min="0" class="form-control form-control-sm"
                                            wire:model="defaultTax" value="{{ $defaultTax }}">
                                    </div>
                                </div>

                                <div class="table-responsive border rounded">
                                    <table class="table table-sm table-striped mb-0 small">
                                        <thead>
                                            <tr>
                                                @foreach ($scanColumns as $label)
                                                    <th class="text-nowrap">{{ $label }}</th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($this->scanPreview as $row)
                                                <tr>
                                                    @foreach ($scanColumns as $index => $label)
                                                        <td class="text-nowrap">{{ $row[$index] ?? '' }}</td>
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif

                            {{-- ─────────────────────────────── step 3 · review ── --}}
                            @if ($scanStep === 3)
                                @php
                                    $ready = collect($scanItems)->where('product_id', '!=', null);
                                    $issues = collect($scanItems)->whereNull('product_id');
                                    $flagged = collect($scanItems)->filter(fn($item) => !empty($item['uncertain']));
                                @endphp

                                <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                                    <span class="badge text-bg-success">{{ $ready->count() }} matched</span>
                                    @if ($issues->count())
                                        <span class="badge text-bg-danger">{{ $issues->count() }} need a product</span>
                                    @endif
                                    @if ($flagged->count())
                                        <span class="badge text-bg-warning">{{ $flagged->count() }} worth checking</span>
                                    @endif
                                    @if ($scanTemplateApplied)
                                        <span class="badge text-bg-info">
                                            <i class="fa fa-bookmark-o me-1"></i>Vendor layout applied
                                        </span>
                                    @endif
                                    @if ($scanSource === 'ocr')
                                        <span class="badge text-bg-warning">
                                            <i class="fa fa-camera me-1"></i>Read by OCR — check the figures
                                        </span>
                                    @endif
                                    <div class="ms-auto">
                                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                            wire:click="$set('scanStep', 2)">
                                            <i class="fa fa-pencil me-1"></i>Change the columns
                                        </button>
                                    </div>
                                </div>

                                <div class="table-responsive border rounded">
                                    <table class="table table-sm align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th style="width:36px">
                                                    <input type="checkbox" class="form-check-input"
                                                        wire:click="toggleAllScanLines"
                                                        @checked(count($scanSelected) && count($scanSelected) === $ready->count())>
                                                </th>
                                                <th>On the invoice</th>
                                                <th>Product</th>
                                                <th class="text-end" style="width:90px">Qty</th>
                                                <th class="text-end" style="width:110px">Rate</th>
                                                <th class="text-end" style="width:110px">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($scanItems as $index => $item)
                                                <tr wire:key="scan-line-{{ $index }}"
                                                    class="{{ $item['product_id'] ? '' : 'table-warning' }}">
                                                    <td>
                                                        <input type="checkbox" class="form-check-input"
                                                            wire:click="toggleScanLine({{ $index }})"
                                                            @checked(in_array($index, $scanSelected, true))
                                                            @disabled(!$item['product_id'])>
                                                    </td>
                                                    <td>
                                                        <div class="small">{{ $item['raw_name'] ?: '—' }}</div>
                                                        @if ($item['raw_code'] || $item['raw_barcode'])
                                                            <div class="text-body-secondary small">
                                                                {{ collect([$item['raw_code'], $item['raw_barcode']])->filter()->implode(' · ') }}
                                                            </div>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if ($item['product_id'])
                                                            <div class="fw-semibold small">{{ $item['name'] }}</div>
                                                            <div class="d-flex flex-wrap gap-1 mt-1">
                                                                @if (!empty($item['by_cost']))
                                                                    <span class="badge text-bg-light border">matched on rate</span>
                                                                @endif
                                                                @if (!empty($item['uncertain']))
                                                                    <span class="badge text-bg-warning">{{ $item['uncertain'] }}</span>
                                                                @endif
                                                                @if ($this->hasCostVariance($item))
                                                                    <span class="badge text-bg-light border text-warning-emphasis">
                                                                        our cost {{ number_format($item['product_cost'], 2) }}
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        @else
                                                            <div class="small text-danger">{{ $item['message'] }}</div>

                                                            @if (!empty($item['candidates']))
                                                                <div class="d-flex flex-wrap gap-1 mt-1">
                                                                    @foreach ($item['candidates'] as $candidate)
                                                                        <button type="button"
                                                                            class="btn btn-sm btn-outline-primary py-0"
                                                                            wire:click="chooseScanCandidate({{ $index }}, {{ $candidate['id'] }})">
                                                                            {{ Str::limit($candidate['name'], 34) }}
                                                                            <span class="text-body-secondary">
                                                                                {{ number_format($candidate['cost'], 2) }}
                                                                            </span>
                                                                        </button>
                                                                    @endforeach
                                                                    @if (($item['candidate_count'] ?? 0) > count($item['candidates']))
                                                                        <span class="badge text-bg-light border">
                                                                            +{{ $item['candidate_count'] - count($item['candidates']) }} more
                                                                        </span>
                                                                    @endif
                                                                </div>
                                                            @endif

                                                            @if ($scanResolvingIndex === $index)
                                                                <div class="mt-2">
                                                                    <input type="text"
                                                                        class="form-control form-control-sm"
                                                                        placeholder="Search the catalogue…"
                                                                        wire:model.live.debounce.400ms="scanSearch"
                                                                        value="{{ $scanSearch }}">
                                                                    <div class="list-group list-group-flush mt-1">
                                                                        @foreach ($scanResults as $result)
                                                                            <button type="button"
                                                                                class="list-group-item list-group-item-action py-1 small"
                                                                                wire:click="bindScanProduct({{ $index }}, {{ $result['id'] }})">
                                                                                {{ $result['name'] }}
                                                                                <span class="text-body-secondary">
                                                                                    {{ number_format($result['cost'], 2) }}
                                                                                </span>
                                                                            </button>
                                                                        @endforeach
                                                                    </div>
                                                                    <button type="button"
                                                                        class="btn btn-sm btn-link px-0 mt-1"
                                                                        wire:click="resolveScanLine(null)">Close</button>
                                                                </div>
                                                            @else
                                                                <button type="button" class="btn btn-sm btn-link px-0"
                                                                    wire:click="resolveScanLine({{ $index }})">
                                                                    <i class="fa fa-search me-1"></i>Find the product
                                                                </button>
                                                            @endif
                                                        @endif
                                                    </td>
                                                    <td class="text-end">{{ rtrim(rtrim(number_format($item['quantity'], 3, '.', ''), '0'), '.') }}</td>
                                                    <td class="text-end">{{ number_format($item['unit_price'], 2) }}</td>
                                                    <td class="text-end">{{ number_format($item['total'], 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                @if ($scanSkipped)
                                    <div class="form-text small mt-2">
                                        {{ $scanSkipped }} {{ Str::plural('line', $scanSkipped) }} of the file were not
                                        item rows (headings, totals, the address block) and were left out.
                                    </div>
                                @endif
                            @endif
                        </div>

                        <div class="modal-footer justify-content-between">
                            <div>
                                @if ($scanStep === 3 && $purchases['account_id'])
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="scanRemember"
                                            wire:model="scanRemember" @checked($scanRemember)>
                                        <label class="form-check-label small" for="scanRemember">
                                            Remember this layout for this vendor
                                        </label>
                                    </div>
                                @endif
                            </div>

                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-secondary"
                                    wire:click="closeInvoiceScan">Cancel</button>

                                @if ($scanStep === 2)
                                    <button type="button" class="btn btn-primary" wire:click="resolveScannedRows">
                                        <i class="fa fa-search me-1"></i>Match the products
                                    </button>
                                @endif

                                @if ($scanStep === 3)
                                    <button type="button" class="btn btn-primary" wire:click="addScannedToCart"
                                        @disabled(count($scanSelected) === 0)>
                                        <i class="fa fa-plus me-1"></i>
                                        Add {{ count($scanSelected) }} {{ Str::plural('item', count($scanSelected)) }}
                                        to the cart
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-backdrop fade show"></div>
        @endif
    </div>
@endcan

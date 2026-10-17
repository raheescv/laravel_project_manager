@php
    $segmentLooks = [
        'sale' => ['icon' => 'fa-shopping-cart', 'tone' => '#2f6fde'],
        'grn' => ['icon' => 'fa-truck', 'tone' => '#d4931c'],
        'tailoringorder' => ['icon' => 'fa-scissors', 'tone' => '#9b5de5'],
        'barcode' => ['icon' => 'fa-barcode', 'tone' => '#1f9a96'],
    ];
    $newCount = collect($rows)->where('is_new', true)->count();
    $groups = collect($rows)->map(fn (array $row, int $index): array => $row + ['index' => $index])->groupBy(fn (array $row): string => $row['segment'].'|'.$row['year']);
@endphp

<div class="scx" x-data="{ q: '' }">
    @include('livewire.settings.partials.panel-styles')

    <style>
        .unx-tools {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .6rem 1rem;
            margin-bottom: 1rem;
        }

        .unx-tools .input-group {
            max-width: 300px;
        }

        .unx-warn {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            margin-inline-start: auto;
            padding: .3rem .7rem;
            border-radius: 8px;
            background: color-mix(in srgb, #d4931c 10%, transparent);
            color: color-mix(in srgb, #d4931c 75%, var(--bs-emphasis-color));
            font-size: .74rem;
        }

        .unx-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1rem;
            align-items: start;
        }

        .unx-card {
            --tone: var(--scx-acc);
            border: 1px solid var(--scx-line-soft);
            border-radius: 14px;
            background: var(--scx-surface);
            overflow: hidden;
        }

        .unx-card-head {
            display: flex;
            align-items: center;
            gap: .6rem;
            padding: .7rem .9rem;
            border-bottom: 1px solid var(--scx-line-soft);
            background: color-mix(in srgb, var(--tone) 5%, var(--scx-surface));
        }

        .unx-card-head .scx-ic {
            width: 30px;
            height: 30px;
            border-radius: 9px;
            font-size: .85rem;
        }

        .unx-card-title {
            font-weight: 700;
            font-size: .88rem;
            color: var(--bs-emphasis-color);
            line-height: 1.2;
        }

        .unx-card-sub {
            font-size: .7rem;
            color: var(--bs-secondary-color);
        }

        .unx-year {
            margin-inline-start: auto;
            padding: .15rem .5rem;
            border-radius: 6px;
            background: color-mix(in srgb, var(--tone) 12%, transparent);
            color: var(--tone);
            font-size: .7rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .unx-cols,
        .unx-line {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 108px 64px;
            align-items: center;
            gap: .75rem;
            padding: .45rem .9rem;
        }

        .unx-cols {
            padding-block: .4rem;
            font-size: .64rem;
            font-weight: 700;
            letter-spacing: .05em;
            text-transform: uppercase;
            color: var(--bs-secondary-color);
            border-bottom: 1px solid var(--scx-line-soft);
        }

        .unx-cols span:last-child,
        .unx-next {
            text-align: end;
        }

        .unx-line {
            transition: background .15s, box-shadow .15s;
        }

        .unx-line + .unx-line {
            border-top: 1px solid var(--scx-line-soft);
        }

        .unx-line:hover {
            background: var(--scx-surface-2);
        }

        .unx-line.is-changed {
            background: color-mix(in srgb, #d4931c 8%, var(--scx-surface));
            box-shadow: inset 3px 0 0 #d4931c;
        }

        .unx-branch {
            display: flex;
            align-items: center;
            gap: .4rem;
            min-width: 0;
        }

        .unx-code {
            flex: none;
            max-width: 100%;
            padding: .12rem .5rem;
            border-radius: 6px;
            background: var(--scx-acc-soft);
            color: color-mix(in srgb, var(--scx-acc) 70%, var(--bs-emphasis-color));
            font-family: var(--bs-font-monospace);
            font-size: .74rem;
            font-weight: 700;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .unx-new {
            flex: none;
            padding: .05rem .4rem;
            border-radius: 5px;
            background: color-mix(in srgb, #2f9e62 14%, transparent);
            color: #2f9e62;
            font-size: .6rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .unx-line .form-control {
            font-weight: 600;
            font-variant-numeric: tabular-nums;
            text-align: end;
        }

        .unx-next {
            font-size: .78rem;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
            color: var(--bs-emphasis-color);
        }

        .unx-renumber-btn {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            padding: .15rem .5rem;
            border: 1px solid color-mix(in srgb, var(--tone) 35%, transparent);
            border-radius: 6px;
            background: transparent;
            color: var(--tone);
            font-size: .7rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .unx-renumber-btn:hover,
        .unx-renumber-btn.is-open {
            background: color-mix(in srgb, var(--tone) 12%, transparent);
        }

        .unx-renumber {
            padding: .75rem .9rem;
            border-bottom: 1px solid var(--scx-line-soft);
            background: color-mix(in srgb, var(--tone) 4%, var(--scx-surface));
        }

        .unx-renumber-map {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
            align-items: center;
            gap: .6rem;
            margin-bottom: .5rem;
        }

        .unx-renumber-map > div {
            padding: .4rem .6rem;
            border: 1px solid var(--scx-line-soft);
            border-radius: 9px;
            background: var(--scx-surface);
            min-width: 0;
        }

        .unx-renumber-map small {
            display: block;
            font-size: .6rem;
            font-weight: 700;
            letter-spacing: .05em;
            text-transform: uppercase;
            color: var(--bs-secondary-color);
        }

        .unx-renumber-map strong {
            display: block;
            font-size: .8rem;
            font-variant-numeric: tabular-nums;
            color: var(--bs-emphasis-color);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .unx-renumber-map .is-new {
            border-color: color-mix(in srgb, var(--tone) 40%, transparent);
        }

        .unx-renumber-map .is-new strong {
            color: var(--tone);
        }

        .unx-renumber-note {
            margin: 0 0 .6rem;
            font-size: .7rem;
            color: var(--bs-secondary-color);
        }

        .unx-renumber-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .5rem;
        }

        .unx-renumber-row label {
            font-size: .72rem;
            font-weight: 600;
            margin: 0;
        }

        .unx-renumber-row .form-control {
            width: 130px;
            font-weight: 600;
            font-variant-numeric: tabular-nums;
            text-align: end;
        }

        .unx-renumber-row .unx-renumber-actions {
            display: flex;
            gap: .4rem;
            margin-inline-start: auto;
        }

        @media (max-width: 575.98px) {
            .unx-grid {
                grid-template-columns: minmax(0, 1fr);
            }

            .unx-warn {
                margin-inline-start: 0;
            }
        }
    </style>

    <form wire:submit="save" class="scx-section">
        <div class="scx-head flex-wrap">
            <span class="scx-ic" style="--tone:#1f9a96"><i class="fa fa-list-ol"></i></span>
            <div class="flex-grow-1" style="min-width: 12rem;">
                <h6>Document Numbering</h6>
                <p>The last number used per document, branch and year. The next document takes the following number.</p>
            </div>
            <div class="scx-head-end">
                <span class="scx-pill is-off">{{ count($rows) }} {{ Str::plural('counter', count($rows)) }}</span>
                @if ($newCount)
                    <span class="scx-pill is-live">{{ $newCount }} new</span>
                @endif
            </div>
        </div>

        <div class="scx-body">
            @if (empty($rows))
                <div class="text-center text-body-secondary py-4">
                    <i class="fa fa-list-ol fa-2x d-block mb-2 opacity-50"></i>
                    No counters to show. Counters appear for the documents you have permission to create.
                </div>
            @else
                <div class="unx-tools">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="fa fa-search"></i></span>
                        <input type="search" class="form-control" placeholder="Search branch, year or document…" x-model.debounce.150ms="q" aria-label="Search counters">
                    </div>
                    <span class="unx-warn"><i class="fa fa-exclamation-triangle"></i>Lowering a number can create duplicate document numbers.</span>
                </div>

                <div class="unx-grid">
                    @foreach ($groups as $groupKey => $groupRows)
                        @php
                            $segment = $groupRows->first()['segment'];
                            $year = $groupRows->first()['year'];
                            $label = \App\Support\UniqueNoCounters::label($segment);
                            $look = $segmentLooks[strtolower($segment)] ?? ['icon' => 'fa-list-ol', 'tone' => '#8a94a6'];
                            $search = strtolower($label.' '.$year.' '.$groupRows->pluck('branch_code')->implode(' '));
                            $isBarcode = $segment === \App\Support\UniqueNoCounters::BARCODE;
                        @endphp
                        <div class="unx-card" style="--tone: {{ $look['tone'] }}" wire:key="segment-{{ Str::slug($groupKey) }}" x-show="!q || @js($search).includes(q.toLowerCase())">
                            <div class="unx-card-head">
                                <span class="scx-ic"><i class="fa {{ $look['icon'] }}"></i></span>
                                <div style="min-width: 0;">
                                    <div class="unx-card-title">{{ $label }}</div>
                                    <div class="unx-card-sub">{{ $groupRows->count() }} {{ Str::plural('branch', $groupRows->count()) }}</div>
                                </div>
                                <span class="unx-year">{{ $isBarcode && $year === '1' ? 'All years' : 'Year '.$year }}</span>
                                @if ($isBarcode)
                                    <button type="button" class="unx-renumber-btn {{ $barcodeSeries !== null ? 'is-open' : '' }}" wire:click="{{ $barcodeSeries !== null ? 'closeBarcodeRenumber' : 'openBarcodeRenumber' }}" wire:loading.attr="disabled" wire:target="openBarcodeRenumber" title="Renumber every barcode in the series from a new start number">
                                        <i class="fa fa-refresh" wire:loading.class="fa-spin" wire:target="openBarcodeRenumber"></i>Renumber
                                    </button>
                                @endif
                            </div>
                            @if ($isBarcode && $barcodeSeries !== null)
                                <div class="unx-renumber" wire:key="barcode-renumber" x-data="{ start: @js($barcodeStart), count: @js($barcodeSeries['count']) }">
                                    @if ($barcodeSeries['count'] === 0)
                                        <p class="unx-renumber-note">No barcodes have been issued from the series yet, so there is nothing to renumber.</p>
                                        <div class="unx-renumber-row">
                                            <div class="unx-renumber-actions">
                                                <button type="button" class="btn btn-sm btn-light" wire:click="closeBarcodeRenumber">Close</button>
                                            </div>
                                        </div>
                                    @else
                                        <div class="unx-renumber-map">
                                            <div>
                                                <small>Current</small>
                                                <strong>{{ $barcodeSeries['first'] }} – {{ $barcodeSeries['last'] }}</strong>
                                            </div>
                                            <i class="fa fa-long-arrow-right text-body-secondary"></i>
                                            <div class="is-new">
                                                <small>New</small>
                                                <strong x-text="Number(start) > 0 ? Number(start) + ' – ' + (Number(start) + count - 1) : '—'">{{ $barcodeStart }} – {{ (int) $barcodeStart + $barcodeSeries['count'] - 1 }}</strong>
                                            </div>
                                        </div>
                                        <p class="unx-renumber-note">{{ number_format($barcodeSeries['count']) }} {{ Str::plural('barcode', $barcodeSeries['count']) }} are renumbered in their current order; a product and its stock rows keep sharing one barcode. Barcodes above the counter, such as manufacturer codes, are not touched. Printed labels will need reprinting.</p>
                                        <div class="unx-renumber-row">
                                            <label for="barcode-renumber-start">Start from</label>
                                            <input type="number" id="barcode-renumber-start" class="form-control form-control-sm" min="1" step="1" wire:model="barcodeStart" x-on:input="start = $event.target.value" x-on:keydown.enter.prevent>
                                            <div class="unx-renumber-actions">
                                                <button type="button" class="btn btn-sm btn-light" wire:click="closeBarcodeRenumber" wire:loading.attr="disabled" wire:target="renumberBarcodes">Cancel</button>
                                                <button type="button" class="btn btn-sm btn-danger" wire:click="renumberBarcodes" wire:confirm="Renumber {{ number_format($barcodeSeries['count']) }} barcodes? Existing printed labels will no longer scan." wire:loading.attr="disabled" wire:target="renumberBarcodes">
                                                    <span wire:loading.remove wire:target="renumberBarcodes"><i class="fa fa-refresh me-1"></i>Renumber</span>
                                                    <span wire:loading wire:target="renumberBarcodes"><i class="fa fa-spinner fa-spin me-1"></i>Renumbering…</span>
                                                </button>
                                            </div>
                                        </div>
                                        @error('barcodeStart')
                                            <small class="text-danger d-block mt-1">{{ $message }}</small>
                                        @enderror
                                    @endif
                                </div>
                            @endif
                            <div class="unx-cols">
                                <span>Branch</span>
                                <span class="text-end">Last used</span>
                                <span>Next</span>
                            </div>
                            @foreach ($groupRows as $row)
                                <div class="unx-line" wire:key="counter-{{ $row['index'] }}-{{ $row['number'] }}"
                                    x-data="{ n: @js((int) $row['number']), saved: @js((int) $row['number']) }"
                                    :class="{ 'is-changed': Number(n) !== saved }"
                                    x-show="!q || @js(strtolower($label.' '.$year.' '.$row['branch_code'])).includes(q.toLowerCase())">
                                    <div class="unx-branch">
                                        <span class="unx-code" title="{{ $row['branch_code'] }}">{{ $row['branch_code'] }}</span>
                                        @if ($row['is_new'] ?? false)
                                            <span class="unx-new" title="Not created yet — suggested from this branch's existing documents. Saved on Update Counters.">New</span>
                                        @endif
                                    </div>
                                    <div>
                                        {{ html()->input('number')->class('form-control form-control-sm')->attribute('wire:model', 'rows.' . $row['index'] . '.number')->attribute('x-on:input', 'n = $event.target.value')->attribute('min', 0)->attribute('step', 1)->attribute('aria-label', $label . ' ' . $row['branch_code'] . ' ' . $year . ' last used number') }}
                                        @error('rows.' . $row['index'] . '.number')
                                            <small class="text-danger d-block">{{ $message }}</small>
                                        @enderror
                                    </div>
                                    <div class="unx-next" x-text="(Number(n) || 0) + 1">{{ (int) $row['number'] + 1 }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="scx-foot">
            <span class="scx-foot-note">Only the numbers can be changed; document, branch and year are fixed. Rows marked New are suggested from existing documents and created on update.</span>
            <button type="submit" class="btn btn-primary" @disabled(empty($rows)) wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save"><i class="fa fa-save me-1"></i>Update Counters</span>
                <span wire:loading wire:target="save"><i class="fa fa-spinner fa-spin me-1"></i>Saving…</span>
            </button>
        </div>
    </form>
</div>

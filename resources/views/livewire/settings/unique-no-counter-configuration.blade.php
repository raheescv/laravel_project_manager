@php
    $segmentIcons = ['sale' => 'fa-shopping-cart', 'barcode' => 'fa-barcode', 'purchase' => 'fa-truck', 'sale return' => 'fa-undo', 'purchase return' => 'fa-reply'];
    $groups = collect($rows)->map(fn (array $row, int $index): array => $row + ['index' => $index])->groupBy('segment');
@endphp

<div class="scx" x-data="{ q: '' }">
    @include('livewire.settings.partials.panel-styles')

    <style>
        .unx-tools {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .6rem;
            margin-bottom: 1rem;
        }

        .unx-tools .input-group {
            max-width: 320px;
        }

        .unx-group + .unx-group {
            margin-top: 1rem;
        }

        .unx-group-head {
            display: flex;
            align-items: center;
            gap: .6rem;
            margin-bottom: .5rem;
        }

        .unx-group-head .scx-ic {
            width: 30px;
            height: 30px;
            border-radius: 9px;
            font-size: .85rem;
        }

        .unx-group-name {
            font-weight: 700;
            color: var(--bs-emphasis-color);
        }

        .unx-list {
            border: 1px solid var(--scx-line-soft);
            border-radius: 14px;
            overflow: hidden;
        }

        .unx-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 180px 120px;
            align-items: center;
            gap: 1rem;
            padding: .6rem .9rem;
            transition: background .15s;
        }

        .unx-row + .unx-row {
            border-top: 1px solid var(--scx-line-soft);
        }

        .unx-row:hover {
            background: var(--scx-surface-2);
        }

        .unx-row.is-changed {
            background: color-mix(in srgb, #d4931c 8%, var(--scx-surface));
        }

        .unx-branch {
            display: inline-flex;
            align-items: center;
            padding: .15rem .55rem;
            border-radius: 7px;
            background: var(--scx-acc-soft);
            color: var(--scx-acc);
            font-family: var(--bs-font-monospace);
            font-size: .76rem;
            font-weight: 700;
        }

        .unx-year {
            margin-inline-start: .5rem;
            font-size: .74rem;
            color: var(--bs-secondary-color);
        }

        .unx-next {
            font-size: .74rem;
            color: var(--bs-secondary-color);
            text-align: end;
            white-space: nowrap;
        }

        .unx-next b {
            color: var(--bs-emphasis-color);
            font-variant-numeric: tabular-nums;
        }

        .unx-row .form-control {
            font-weight: 600;
            font-variant-numeric: tabular-nums;
        }

        @media (max-width: 575.98px) {
            .unx-row {
                grid-template-columns: minmax(0, 1fr) 130px;
            }

            .unx-next {
                grid-column: 1 / -1;
                text-align: start;
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
            </div>
        </div>

        <div class="scx-body">
            @if (empty($rows))
                <div class="text-center text-body-secondary py-4">
                    <i class="fa fa-list-ol fa-2x d-block mb-2 opacity-50"></i>
                    No counters yet. They appear here after the first numbered document is created.
                </div>
            @else
                <div class="unx-tools">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="fa fa-search"></i></span>
                        <input type="search" class="form-control" placeholder="Search branch, year or document…" x-model.debounce.150ms="q" aria-label="Search counters">
                    </div>
                    <span class="small text-body-secondary ms-auto"><i class="fa fa-exclamation-triangle text-warning me-1"></i>Lowering a number can create duplicate document numbers.</span>
                </div>

                @foreach ($groups as $segment => $segmentRows)
                    @php($search = strtolower($segment.' '.$segmentRows->map(fn ($row) => $row['branch_code'].' '.$row['year'])->implode(' ')))
                    <div class="unx-group" wire:key="segment-{{ Str::slug($segment) }}" x-show="!q || @js($search).includes(q.toLowerCase())">
                        <div class="unx-group-head">
                            <span class="scx-ic"><i class="fa {{ $segmentIcons[strtolower($segment)] ?? 'fa-list-ol' }}"></i></span>
                            <span class="unx-group-name">{{ $segment }}</span>
                            <span class="small text-body-secondary">{{ $segmentRows->count() }} {{ Str::plural('counter', $segmentRows->count()) }}</span>
                        </div>
                        <div class="unx-list">
                            @foreach ($segmentRows as $row)
                                <div class="unx-row" wire:key="counter-{{ $row['index'] }}-{{ $row['number'] }}"
                                    x-data="{ n: @js((int) $row['number']), saved: @js((int) $row['number']) }"
                                    :class="{ 'is-changed': Number(n) !== saved }"
                                    x-show="!q || @js(strtolower($segment.' '.$row['branch_code'].' '.$row['year'])).includes(q.toLowerCase())">
                                    <div class="text-truncate">
                                        <span class="unx-branch">{{ $row['branch_code'] }}</span>
                                        <span class="unx-year">Year {{ $row['year'] }}</span>
                                    </div>
                                    <div>
                                        {{ html()->input('number')->class('form-control form-control-sm')->attribute('wire:model', 'rows.' . $row['index'] . '.number')->attribute('x-on:input', 'n = $event.target.value')->attribute('min', 0)->attribute('step', 1)->attribute('aria-label', $segment . ' ' . $row['branch_code'] . ' ' . $row['year'] . ' current number') }}
                                        @error('rows.' . $row['index'] . '.number')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                    <div class="unx-next">Next <b x-text="(Number(n) || 0) + 1">{{ (int) $row['number'] + 1 }}</b></div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        <div class="scx-foot">
            <span class="scx-foot-note">Only the numbers can be changed; document, branch and year are fixed.</span>
            <button type="submit" class="btn btn-primary" @disabled(empty($rows)) wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save"><i class="fa fa-save me-1"></i>Update Counters</span>
                <span wire:loading wire:target="save"><i class="fa fa-spinner fa-spin me-1"></i>Saving…</span>
            </button>
        </div>
    </form>
</div>

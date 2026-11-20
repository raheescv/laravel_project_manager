{{-- Product page → "Variants" tab, "Swatch Board" design (scope .pvd). Preview: docs/html_samples/product-variants-design-samples.html (C).
     This component renders INSIDE the product page's <form wire:submit="save">: every button is type="button" and Enter in an input never submits the product form. --}}
<div class="pvd" x-data x-on:keydown.enter="if ($event.target.tagName === 'INPUT') { $event.preventDefault() }">
    @once
        <style>
            .pvd {
                --acc: var(--bs-primary);
                --acc-deep: color-mix(in srgb, var(--acc), #000 38%);
                --acc-soft: color-mix(in srgb, var(--acc) 9%, var(--bs-body-bg));
                --acc-soft-2: color-mix(in srgb, var(--acc) 17%, var(--bs-body-bg));
                --acc-line: color-mix(in srgb, var(--acc) 40%, var(--bs-border-color));
                --acc-ink: color-mix(in srgb, var(--acc) 72%, var(--bs-emphasis-color));
                --pvd-shadow: 0 1px 2px rgba(15, 23, 42, .05), 0 12px 30px -20px rgba(15, 23, 42, .4);
                padding-bottom: 76px;
            }
            [data-bs-theme="dark"] .pvd {
                --acc-ink: color-mix(in srgb, var(--acc) 35%, #fff);
                --acc-soft: color-mix(in srgb, var(--acc) 22%, var(--bs-body-bg));
                --acc-soft-2: color-mix(in srgb, var(--acc) 34%, var(--bs-body-bg));
            }
            .pvd .pvd-card { background: var(--bs-body-bg); border: 1px solid var(--bs-border-color-translucent); border-radius: 16px; box-shadow: var(--pvd-shadow); }
            .pvd .pvd-card__head { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; padding: 14px 18px; border-bottom: 1px solid var(--bs-border-color-translucent); }
            .pvd .pvd-card__head h6 { margin: 0; font-weight: 600; }
            .pvd .pvd-muted { color: var(--bs-secondary-color); }
            .pvd .pvd-opt { font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; padding: 2px 8px; border-radius: 6px; background: var(--bs-secondary-bg); color: var(--bs-secondary-color); }
            .pvd .pvd-tip { display: flex; gap: 9px; align-items: flex-start; padding: 10px 13px; border-radius: 12px; background: var(--acc-soft); color: var(--acc-ink); font-size: 12.5px; }

            .pvd .pvd-sw { display: inline-block; width: 14px; height: 14px; border-radius: 50%; background: var(--sw); box-shadow: inset 0 0 0 1px rgba(0, 0, 0, .18), 0 0 0 2px var(--bs-body-bg); flex: none; }
            .pvd .pvd-sw.none { background: linear-gradient(135deg, transparent 44%, var(--bs-secondary-color) 44% 56%, transparent 56%); box-shadow: inset 0 0 0 1.5px var(--bs-secondary-color); opacity: .8; }
            .pvd .pvd-sw.rainbow { background: conic-gradient(#ef4444, #f59e0b, #eab308, #22c55e, #06b6d4, #3b82f6, #8b5cf6, #ec4899, #ef4444); }
            .pvd .pvd-chip { display: inline-flex; align-items: center; gap: 7px; padding: 6px 6px 6px 12px; border-radius: 99px; background: var(--acc-soft); border: 1px solid var(--acc-line); color: var(--acc-ink); font-weight: 500; }
            .pvd .pvd-chip.lock { padding-right: 12px; }
            .pvd .pvd-chip .pvd-x { display: grid; place-items: center; width: 20px; height: 20px; border: 0; border-radius: 50%; background: none; color: inherit; opacity: .6; padding: 0; }
            .pvd .pvd-chip .pvd-x:hover { opacity: 1; background: var(--acc-soft-2); }
            .pvd .pvd-ghost { display: inline-flex; align-items: center; gap: 7px; padding: 6px 12px; border-radius: 99px; border: 1px dashed var(--bs-border-color); background: none; color: var(--bs-secondary-color); font-weight: 500; }
            .pvd .pvd-ghost:hover { border-color: var(--acc); color: var(--acc-ink); }
            .pvd .pvd-adder { display: inline-flex; align-items: center; border: 1px dashed var(--bs-border-color); border-radius: 99px; padding: 3px 3px 3px 12px; background: var(--bs-body-bg); }
            .pvd .pvd-adder:focus-within { border-color: var(--acc); border-style: solid; box-shadow: 0 0 0 3px var(--acc-soft-2); }
            .pvd .pvd-adder input { border: 0; outline: 0; background: transparent; width: 90px; font-size: 13px; color: var(--bs-body-color); }
            .pvd .pvd-adder button { border: 0; border-radius: 99px; background: var(--acc); color: #fff; padding: 4px 11px; font-size: 12px; font-weight: 500; }

            .pvd .pvd-sizes { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; padding: 14px 18px; }
            .pvd .pvd-board { display: flex; gap: 14px; overflow-x: auto; padding: 4px 2px 12px; scroll-snap-type: x proximity; }
            .pvd .pvd-col { flex: 0 0 190px; scroll-snap-align: start; border-radius: 20px; background: var(--bs-body-bg); border: 1px solid var(--bs-border-color-translucent); overflow: hidden; box-shadow: var(--pvd-shadow); display: flex; flex-direction: column; }
            .pvd .pvd-col__head { position: relative; height: 118px; padding: 14px 16px; background: var(--sw); color: var(--ink); display: flex; flex-direction: column; justify-content: space-between; }
            .pvd .pvd-col__head.none { background: repeating-linear-gradient(135deg, var(--bs-secondary-bg) 0 10px, var(--bs-tertiary-bg) 10px 20px); color: var(--bs-emphasis-color); }
            .pvd .pvd-col__head b { font-size: 17px; font-weight: 600; }
            .pvd .pvd-col__head small { opacity: .8; font-size: 11.5px; }
            .pvd .pvd-col__head .pvd-x { position: absolute; top: 10px; right: 10px; width: 26px; height: 26px; border-radius: 50%; border: 0; background: rgba(255, 255, 255, .22); color: inherit; padding: 0; }
            .pvd .pvd-col__head .pvd-lk { position: absolute; top: 12px; right: 14px; opacity: .8; }
            .pvd .pvd-col__list { padding: 10px; display: grid; gap: 7px; }
            .pvd .pvd-size { display: flex; align-items: center; justify-content: space-between; padding: 10px 13px; border-radius: 12px; border: 1.5px solid var(--bs-border-color); background: none; font-weight: 600; color: var(--bs-secondary-color); }
            .pvd .pvd-size i { font-size: 11px; opacity: .5; }
            .pvd .pvd-size:hover { border-color: var(--acc); }
            .pvd .pvd-size.on { border-color: var(--acc); background: var(--acc-soft); color: var(--acc-ink); }
            .pvd .pvd-size.on i { opacity: 1; }
            .pvd .pvd-size.done { border-style: solid; border-color: var(--acc-line); background: var(--acc-soft-2); color: var(--acc-ink); cursor: default; }
            .pvd .pvd-col__foot { margin-top: auto; padding: 0 12px 12px; font-size: 12px; }
            .pvd .pvd-link { border: 0; background: none; padding: 0; color: var(--acc-ink); font-weight: 500; }
            .pvd .pvd-link:hover { text-decoration: underline; }
            .pvd .pvd-col.add { border-style: dashed; background: transparent; box-shadow: none; justify-content: center; align-items: center; padding: 18px; gap: 12px; text-align: center; }
            .pvd .pvd-bigdot { width: 72px; height: 72px; border-radius: 50%; border: 0; box-shadow: 0 0 0 4px var(--bs-body-bg), 0 0 0 5px var(--bs-border-color); transition: transform .15s; }
            .pvd .pvd-bigdot:hover { transform: scale(1.05); }
            .pvd .pvd-col.quick { flex-basis: 160px; background: transparent; box-shadow: none; border-style: dashed; padding: 14px; gap: 8px; }
            .pvd .pvd-col.quick .pvd-ghost { width: 100%; justify-content: flex-start; }

            .pvd .pvd-table thead th { font-size: 11.5px; text-transform: uppercase; letter-spacing: .05em; color: var(--bs-secondary-color); font-weight: 600; background: var(--bs-tertiary-bg); border-bottom: 0; white-space: nowrap; }
            .pvd .pvd-table td { vertical-align: middle; border-color: var(--bs-border-color-translucent); }
            .pvd .pvd-table .form-control-sm { border-radius: 8px; min-width: 84px; }
            .pvd .pvd-vname { display: flex; align-items: center; gap: 9px; font-weight: 600; white-space: nowrap; }
            .pvd .pvd-pill { display: inline-flex; align-items: center; gap: 5px; padding: 2px 9px; border-radius: 99px; font-size: 11.5px; font-weight: 600; white-space: nowrap; }
            .pvd .pvd-pill.base { background: var(--acc-soft-2); color: var(--acc-ink); }
            .pvd .pvd-pill.edited { background: var(--bs-warning-bg-subtle); color: var(--bs-warning-text-emphasis); margin-left: 4px; }
            .pvd .pvd-pill.new { background: var(--bs-success-bg-subtle); color: var(--bs-success-text-emphasis); }
            .pvd .pvd-mono { font-family: ui-monospace, Menlo, monospace; font-size: 12.5px; }
            .pvd .pvd-ibtn { display: grid; place-items: center; width: 30px; height: 30px; border-radius: 8px; border: 0; background: transparent; color: var(--bs-secondary-color); padding: 0; }
            .pvd .pvd-ibtn:hover { background: var(--bs-danger-bg-subtle); color: var(--bs-danger); }
            .pvd .pvd-bulk { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; padding: 10px 18px; background: var(--acc-soft); border-bottom: 1px solid var(--bs-border-color-translucent); }
            .pvd .pvd-bulk .form-control { width: 110px; border-radius: 9px; }
            .pvd .pvd-seg { display: inline-flex; align-items: center; gap: 2px; padding: 3px 3px 3px 10px; border-radius: 10px; background: var(--bs-secondary-bg); }
            .pvd .pvd-seg button { border: 0; background: none; padding: 4px 11px; border-radius: 8px; font-size: 12.5px; font-weight: 500; color: var(--bs-secondary-color); }
            .pvd .pvd-seg button.on { background: var(--bs-body-bg); color: var(--acc-ink); box-shadow: 0 1px 4px rgba(0, 0, 0, .12); }
            .pvd .pvd-table tr.pvd-group td { background: var(--acc-soft); border-top: 2px solid var(--acc-line); padding-top: 10px; padding-bottom: 10px; }
            .pvd .pvd-group__size { display: inline-grid; place-items: center; min-width: 30px; height: 26px; padding: 0 8px; border-radius: 8px; background: var(--acc); color: #fff; font-weight: 600; font-size: 12.5px; }
            .pvd .pvd-group__in { width: 96px; border-radius: 8px; }

            .pvd .pvd-dock { position: fixed; left: 50%; bottom: 16px; transform: translateX(-50%); z-index: 1030; width: min(1100px, calc(100% - 32px)); display: flex; flex-wrap: wrap; align-items: center; gap: 12px; padding: 10px 12px 10px 18px; border-radius: 16px; background: color-mix(in srgb, var(--bs-body-bg) 92%, transparent); backdrop-filter: blur(12px); border: 1px solid var(--bs-border-color-translucent); box-shadow: 0 20px 40px -20px rgba(0, 0, 0, .45); }
            .pvd .pvd-dock .pvd-sum { margin-right: auto; display: flex; flex-wrap: wrap; gap: 4px 14px; align-items: center; }
            .pvd .pvd-dock .pvd-sum b { font-size: 18px; }
            .pvd .pvd-btn { background: var(--acc); border: 0; color: #fff; font-weight: 500; padding: 9px 18px; border-radius: 11px; box-shadow: 0 8px 18px -10px var(--acc); }
            .pvd .pvd-btn:hover { background: var(--acc-deep); color: #fff; }
            .pvd .pvd-btn-soft { background: var(--bs-secondary-bg); border: 0; color: var(--bs-body-color); font-weight: 500; padding: 9px 14px; border-radius: 11px; }

            .pvd .pvd-back { position: fixed; inset: 0; z-index: 1055; background: rgba(15, 23, 42, .45); backdrop-filter: blur(2px); }
            .pvd .pvd-chooser { position: fixed; z-index: 1056; left: 50%; top: 50%; transform: translate(-50%, -50%); width: min(400px, calc(100% - 32px)); max-height: calc(100vh - 40px); overflow: auto; border-radius: 20px; background: var(--bs-body-bg); box-shadow: 0 30px 60px -20px rgba(0, 0, 0, .5); border: 1px solid var(--bs-border-color-translucent); }
            .pvd .pvd-chooser__top { display: flex; align-items: center; gap: 14px; padding: 18px 18px 14px; }
            .pvd .pvd-chooser__sec { padding: 0 18px 14px; }
            .pvd .pvd-chooser__sec > small { display: block; font-weight: 600; color: var(--bs-secondary-color); margin-bottom: 8px; }
            .pvd .pvd-grid { display: grid; grid-template-columns: repeat(8, 1fr); gap: 8px; }
            .pvd .pvd-shade { aspect-ratio: 1; border-radius: 50%; border: 0; padding: 0; background: var(--sw); box-shadow: inset 0 0 0 1px rgba(0, 0, 0, .18); transition: transform .12s; }
            .pvd .pvd-shade:hover { transform: scale(1.12); }
            .pvd .pvd-any { display: flex; align-items: center; gap: 12px; padding: 10px 12px; border-radius: 12px; border: 1px dashed var(--bs-border-color); cursor: pointer; margin: 0; }
            .pvd .pvd-any:hover { border-color: var(--acc); }
            .pvd .pvd-any input { width: 34px; height: 34px; padding: 0; border: 0; border-radius: 50%; background: none; cursor: pointer; }
            .pvd .pvd-custom { display: flex; align-items: center; gap: 12px; margin-top: 10px; padding: 10px 12px; border-radius: 12px; background: var(--acc-soft); }
            .pvd .pvd-custom .pvd-prev { width: 40px; height: 40px; border-radius: 12px; flex: none; background: var(--sw); box-shadow: inset 0 0 0 1px rgba(0, 0, 0, .15); }

            .pvd .pvd-sib { display: flex; flex-wrap: wrap; gap: 8px; }
            .pvd .pvd-sib a { display: inline-flex; align-items: center; gap: 7px; padding: 7px 12px; border-radius: 11px; border: 1px solid var(--bs-border-color); color: var(--bs-body-color); text-decoration: none; font-weight: 500; }
            .pvd .pvd-sib a:hover { border-color: var(--acc); }
            .pvd .pvd-sib a.on { background: var(--acc); border-color: var(--acc); color: #fff; }
        </style>
    @endonce

    @php
        $swatch = fn (string $colour) => $colour === ''
            ? '<span class="pvd-sw none"></span>'
            : '<span class="pvd-sw" style="--sw:' . e($hex[$colour] ?? '#9ca3af') . '"></span>';
        $label = fn (string $size, string $colour) => \App\Models\Product::variantLabel($size, $colour);
    @endphp

    @if ($isVariant)
        {{-- A variant's own page: point back to the family head and the siblings. --}}
        <div class="pvd-card">
            <div class="pvd-card__head">
                <h6><i class="fa fa-sitemap me-1"></i> Variant of {{ $head['name'] }}</h6>
                <a href="{{ route('product::edit', $head['id']) }}" class="ms-auto small">Open {{ $head['name'] }} to add sizes or colours <i class="fa fa-chevron-right"></i></a>
            </div>
            <div class="p-3">
                <p class="pvd-muted small mb-2">Name, category, brand, unit, tax and accounts come from {{ $head['name'] }} and are changed there. Barcode, size, colour and prices belong to this variant.</p>
                <div class="pvd-sib">
                    @foreach ($siblings as $sibling)
                        <a href="{{ route('product::edit', $sibling['id']) }}" @class(['on' => $sibling['current']]) wire:key="sib-{{ $sibling['id'] }}">
                            {!! $swatch($sibling['colour']) !!} {{ $sibling['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @elseif (($head['size'] ?? '') === '')
        {{-- The original needs a size before it can anchor a family. --}}
        <div class="pvd-card p-4">
            <h6 class="fw-semibold mb-1">What size is {{ $head['name'] }}?</h6>
            <p class="pvd-muted mb-3">This product becomes the first variant of the family, so it needs a size first. You can add its colour in Attributes.</p>
            <span class="pvd-adder">
                <input wire:model="baseSize" wire:keydown.enter="saveBaseSize" placeholder="e.g. 41" />
                <button type="button" wire:click="saveBaseSize"><i class="fa fa-check"></i> Save size</button>
            </span>
        </div>
    @else
        {{-- 1 · Sizes --}}
        <div class="pvd-card">
            <div class="pvd-sizes">
                <span class="fw-semibold me-1">Sizes</span>
                @foreach ($sizes as $size)
                    @php $sizeLocked = collect($colours)->contains(fn ($colour) => isset($existing[$this->cellKey($size, $colour)])); @endphp
                    @if ($sizeLocked)
                        <span class="pvd-chip lock" wire:key="size-{{ md5($size) }}" title="Already made — remove its variants to drop this size"><i class="fa fa-lock"></i> {{ $size }}</span>
                    @else
                        <span class="pvd-chip" wire:key="size-{{ md5($size) }}">{{ $size }}
                            <button type="button" class="pvd-x" wire:click="removeSize(@js($size))" title="Remove"><i class="fa fa-times"></i></button>
                        </span>
                    @endif
                @endforeach
                <span class="pvd-adder">
                    <input wire:model="newSize" wire:keydown.enter="addSize" placeholder="e.g. 45" />
                    <button type="button" wire:click="addSize"><i class="fa fa-plus"></i> Size</button>
                </span>
                <span class="ms-auto pvd-muted small">Each colour is a column. Tap the sizes it comes in.</span>
            </div>
        </div>

        {{-- 2 · Colour board --}}
        <div class="d-flex flex-wrap align-items-center gap-2 mt-3 mb-2">
            <h6 class="m-0 fw-semibold">Colours</h6>
            <span class="pvd-opt">Optional</span>
            <span class="pvd-muted small">{{ $sizeOnly ? 'No colours: the grey column is "sizes only". Add a colour only if it really comes in colours.' : 'Add a "No colour" column for sizes sold without one.' }}</span>
        </div>

        <div
            x-data="{
                open: false, custom: false, hex: '#6b7280', name: '',
                shades: @js(\App\Support\ProductColours::SUGGESTED),
                rgb(h) { const n = parseInt(h.slice(1), 16); return [n >> 16, (n >> 8) & 255, n & 255]; },
                lum(h) { const [r, g, b] = this.rgb(h); return (0.2126 * r + 0.7152 * g + 0.0722 * b) / 255; },
                autoName(h) {
                    const [r, g, b] = this.rgb(h);
                    let best = this.shades[0], bd = Infinity;
                    this.shades.forEach(s => { const [r2, g2, b2] = this.rgb(s[1]); const d = (r - r2) ** 2 * 2 + (g - g2) ** 2 * 4 + (b - b2) ** 2 * 3; if (d < bd) { bd = d; best = s; } });
                    const diff = this.lum(h) - this.lum(best[1]);
                    let name = Math.abs(diff) > 0.12 ? (diff > 0 ? 'Light ' : 'Dark ') + best[0] : best[0];
                    const used = JSON.parse(this.$root.dataset.used || '[]');
                    const taken = n => used.includes(n.toLowerCase());
                    if (taken(name)) { let i = 2; while (taken(name + ' ' + i)) i++; name = name + ' ' + i; }
                    return name;
                },
                pick(name, hex) { this.$wire.addColour(name, hex); this.open = false; this.custom = false; },
            }"
            data-used="{{ json_encode(array_map(fn ($c) => mb_strtolower(\App\Support\ProductColours::displayName($c)), $colours)) }}"
            x-on:keydown.escape.window="open = false">
          <div class="pvd-board">
            @foreach ($colours as $colour)
                @php
                    $colourHex = $hex[$colour] ?? '#9ca3af';
                    $colourKeys = collect($sizes)->map(fn ($size) => $this->cellKey($size, $colour));
                    $madeCount = $colourKeys->filter(fn ($key) => isset($existing[$key]))->count();
                    $pickedCount = $colourKeys->filter(fn ($key) => isset($picked[$key]))->count();
                    $colourLocked = $madeCount > 0;
                    $openCount = $colourKeys->count() - $madeCount;
                @endphp
                <div class="pvd-col" wire:key="col-{{ md5('c' . $colour) }}">
                    <div @class(['pvd-col__head', 'none' => $colour === '']) style="--sw: {{ $colourHex }}; --ink: {{ $this->inkFor($colourHex) }}">
                        @if ($colourLocked)
                            <i class="fa fa-lock pvd-lk" title="Already has variants"></i>
                        @else
                            <button type="button" class="pvd-x" wire:click="removeColour(@js($colour))" title="Remove colour"><i class="fa fa-times"></i></button>
                        @endif
                        <small>{{ $colour === '' ? 'sizes only' : strtoupper($colourHex) }}</small>
                        <div>
                            <b>{{ $colour === '' ? 'No colour' : \App\Support\ProductColours::displayName($colour) }}</b><br />
                            <small>{{ $madeCount + $pickedCount }} of {{ count($sizes) }} sizes</small>
                        </div>
                    </div>
                    <div class="pvd-col__list">
                        @foreach ($sizes as $size)
                            @php $key = $this->cellKey($size, $colour); @endphp
                            @if (isset($existing[$key]))
                                <span class="pvd-size done" wire:key="cell-{{ $key }}" title="Already a product">
                                    <span>{{ $size }}</span><i class="fa fa-lock"></i>
                                </span>
                            @else
                                <button type="button" @class(['pvd-size', 'on' => isset($picked[$key])]) wire:click="toggle('{{ $key }}')" wire:key="cell-{{ $key }}">
                                    <span>{{ $size }}</span><i class="fa {{ isset($picked[$key]) ? 'fa-check' : 'fa-plus' }}"></i>
                                </button>
                            @endif
                        @endforeach
                    </div>
                    @if ($openCount > 0)
                        <div class="pvd-col__foot">
                            <button type="button" class="pvd-link" wire:click="toggleColour(@js($colour))">{{ $pickedCount === $openCount ? 'Clear' : 'All sizes' }}</button>
                        </div>
                    @endif
                </div>
            @endforeach

            <div class="pvd-col add">
                <button type="button" class="pvd-bigdot pvd-sw rainbow" x-on:click="open = true" title="Choose a colour"></button>
                <button type="button" class="btn btn-sm pvd-btn w-100" x-on:click="open = true"><i class="fa fa-plus"></i> Choose a colour</button>
                <small class="pvd-muted">Any shade you need</small>
            </div>
            <div class="pvd-col quick">
                <small class="pvd-muted fw-semibold">Quick add</small>
                @foreach ($quickAdd as $name)
                    <button type="button" class="pvd-ghost" wire:click="addColour(@js($name))" wire:key="quick-{{ md5($name) }}">
                        {!! $swatch($name) !!} {{ $name }}
                    </button>
                @endforeach
                @unless (in_array('', $colours, true))
                    <button type="button" class="pvd-ghost" wire:click="addNoColour">{!! $swatch('') !!} No colour</button>
                @endunless
            </div>
          </div>

            {{-- Colour chooser: tap a shade to add it; "Pick any shade" names it automatically. --}}
            <template x-if="open">
                <div>
                    <div class="pvd-back" x-on:click="open = false"></div>
                    <div class="pvd-chooser" role="dialog" aria-label="Choose a colour">
                        <div class="pvd-chooser__top">
                            <div class="flex-grow-1">
                                <b class="d-block fs-6">Choose a colour</b>
                                <small class="pvd-muted">Tap one and it's added straight away</small>
                            </div>
                            <button type="button" class="pvd-ibtn" x-on:click="open = false" title="Close"><i class="fa fa-times"></i></button>
                        </div>
                        <div class="pvd-chooser__sec">
                            <div class="pvd-grid">
                                @foreach ($chooserShades as [$shadeName, $shadeHex])
                                    <button type="button" class="pvd-shade" style="--sw: {{ $shadeHex }}" title="{{ $shadeName }}" x-on:click="pick(@js($shadeName), @js($shadeHex))"></button>
                                @endforeach
                            </div>
                        </div>
                        <div class="pvd-chooser__sec">
                            <small>Need a different shade?</small>
                            <label class="pvd-any">
                                <input type="color" x-model="hex" x-on:input="custom = true; name = autoName(hex)" />
                                <span><b class="d-block">Pick any shade</b><small class="pvd-muted">Opens the full colour picker</small></span>
                            </label>
                            <div class="pvd-custom" x-show="custom">
                                <span class="pvd-prev" x-bind:style="'--sw:' + hex"></span>
                                <span class="flex-grow-1"><b class="d-block" x-text="name"></b><small class="pvd-mono" x-text="hex.toUpperCase()"></small></span>
                                <button type="button" class="btn btn-sm pvd-btn" x-on:click="pick(name, hex)"><i class="fa fa-plus"></i> Add</button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- 3 · Barcode, price and opening stock for each new variant --}}
        <div class="pvd-card mt-3">
            <div class="pvd-card__head">
                <h6>Barcode, price and opening stock</h6>
                <span class="pvd-seg ms-2" role="group" aria-label="Group the table">
                    <span class="pvd-muted small me-1">Group by</span>
                    <button type="button" @class(['on' => $groupBy === 'colour']) wire:click="setGroupBy('colour')"><i class="fa fa-tint"></i> Colour</button>
                    <button type="button" @class(['on' => $groupBy === 'size']) wire:click="setGroupBy('size')"><i class="fa fa-arrows-h"></i> Size</button>
                </span>
                <span class="pvd-muted ms-auto small">Name, category, brand, tax and unit are copied from {{ $head['name'] }}</span>
            </div>
            @if ($orderedPicked || $existing)
                <div class="pvd-bulk">
                    <span class="fw-semibold small">Same for all:</span>
                    <input class="form-control form-control-sm" wire:model="bulk.cost" placeholder="Cost" />
                    <input class="form-control form-control-sm" wire:model="bulk.mrp" placeholder="MRP" />
                    <input class="form-control form-control-sm" wire:model="bulk.opening_stock" placeholder="Opening stock (optional)" style="width: 190px" />
                    <button type="button" class="btn btn-sm pvd-btn" wire:click="applyToAll"><i class="fa fa-check"></i> Apply</button>
                    <span class="pvd-muted small">Cost and MRP fill every row; stock only new ones. Nothing is saved until you press Save.</span>
                </div>
            @endif
            <div class="table-responsive">
                <table class="table pvd-table m-0">
                    <thead>
                        <tr>
                            <th>Variant</th>
                            <th></th>
                            <th>Barcode</th>
                            <th>Cost</th>
                            <th>MRP</th>
                            <th>Opening stock <span class="pvd-opt">Optional</span></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($groups as $group)
                            @php
                                $groupLabel = $groupBy === 'size' ? 'Size ' . $group['value'] : ($group['value'] === '' ? 'No colour' : \App\Support\ProductColours::displayName($group['value']));
                                $groupNew = count($group['picked']);
                            @endphp
                            <tr class="pvd-group" wire:key="group-{{ $group['key'] }}">
                                <td colspan="7">
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        @if ($groupBy === 'size')
                                            <span class="pvd-group__size">{{ $group['value'] }}</span>
                                        @else
                                            {!! $swatch($group['value']) !!}
                                        @endif
                                        <b>{{ $groupLabel }}</b>
                                        <span class="pvd-muted small">{{ $groupNew }} new{{ count($group['existing']) ? ' · ' . count($group['existing']) . ' already made' : '' }}</span>
                                        @if ($groupNew || count($group['existing']))
                                            <span class="ms-auto d-flex flex-wrap align-items-center gap-2">
                                                <span class="small fw-semibold pvd-muted">Same for {{ $groupLabel }}:</span>
                                                <input class="form-control form-control-sm pvd-group__in" wire:model="groupBulk.{{ $group['key'] }}.cost" placeholder="Cost" />
                                                <input class="form-control form-control-sm pvd-group__in" wire:model="groupBulk.{{ $group['key'] }}.mrp" placeholder="MRP" />
                                                @if ($groupNew)
                                                    <input class="form-control form-control-sm pvd-group__in" wire:model="groupBulk.{{ $group['key'] }}.opening_stock" placeholder="Stock" title="Opening stock for the new ones" />
                                                @endif
                                                <button type="button" class="btn btn-sm pvd-btn" wire:click="applyToGroup('{{ $group['key'] }}')"><i class="fa fa-check"></i> Apply</button>
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @foreach ($group['existing'] as $key)
                            @php $member = $existing[$key]; @endphp
                            <tr wire:key="made-{{ $key }}">
                                <td><div class="pvd-vname">{!! $swatch($cells[$key]['colour']) !!} {{ $label($cells[$key]['size'], $cells[$key]['colour']) }}</div></td>
                                <td>
                                    @if ($member['id'] === $head['id'])
                                        <span class="pvd-pill base"><i class="fa fa-lock"></i> Original</span>
                                    @else
                                        <a href="{{ route('product::edit', $member['id']) }}" class="pvd-pill base text-decoration-none" title="Open this variant"><i class="fa fa-check"></i> Made</a>
                                    @endif
                                    @if (in_array($key, $changed, true))
                                        <span class="pvd-pill edited"><i class="fa fa-pencil"></i> Edited</span>
                                    @endif
                                </td>
                                <td><input class="form-control form-control-sm pvd-mono" wire:model.live.debounce.500ms="edits.{{ $key }}.barcode_number" style="min-width: 140px" /></td>
                                <td><input class="form-control form-control-sm" wire:model.live.debounce.500ms="edits.{{ $key }}.cost" /></td>
                                <td><input class="form-control form-control-sm" wire:model.live.debounce.500ms="edits.{{ $key }}.mrp" /></td>
                                <td class="pvd-muted small">In stock — change through purchase / stock adjustment</td>
                                <td></td>
                            </tr>
                        @endforeach
                        @foreach ($group['picked'] as $key)
                            <tr wire:key="new-{{ $key }}">
                                <td><div class="pvd-vname">{!! $swatch($cells[$key]['colour']) !!} {{ $label($cells[$key]['size'], $cells[$key]['colour']) }}</div></td>
                                <td><span class="pvd-pill new"><i class="fa fa-plus"></i> New</span></td>
                                <td><input class="form-control form-control-sm pvd-mono" wire:model="picked.{{ $key }}.barcode_number" placeholder="Auto" style="min-width: 140px" /></td>
                                <td><input class="form-control form-control-sm" wire:model="picked.{{ $key }}.cost" /></td>
                                <td><input class="form-control form-control-sm" wire:model="picked.{{ $key }}.mrp" /></td>
                                <td><input class="form-control form-control-sm" wire:model="picked.{{ $key }}.opening_stock" placeholder="Optional" /></td>
                                <td class="text-end"><button type="button" class="pvd-ibtn" wire:click="toggle('{{ $key }}')" title="Don't create this one"><i class="fa fa-trash-o"></i></button></td>
                            </tr>
                        @endforeach
                        @endforeach
                        @if (! $orderedPicked && ! $existing)
                            <tr>
                                <td colspan="7" class="text-center pvd-muted py-4">Tap the sizes above to pick what to create.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        @if ($orderedPicked || $changed)
            @php
                $newCount = count($orderedPicked);
                $changedCount = count($changed);
                $saveLabel = collect([
                    $newCount ? 'Create ' . $newCount . ' variant' . ($newCount === 1 ? '' : 's') : null,
                    $changedCount ? ($newCount ? 'update ' : 'Update ') . $changedCount : null,
                ])->filter()->implode(' & ');
            @endphp
            <div class="pvd-dock">
                <div class="pvd-sum">
                    <span><b>{{ count($existing) + $newCount }}</b> variants</span>
                    <span class="pvd-muted">{{ $newCount }} new + {{ count($existing) }} already made{{ $changedCount ? ' · ' . $changedCount . ' edited' : '' }} · shows as <b class="fs-6">1</b> product in the list</span>
                </div>
                <button type="button" class="btn pvd-btn-soft" wire:click="cancel">Cancel</button>
                <button type="button" class="btn pvd-btn" wire:click="save" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save"><i class="fa fa-check"></i> {{ $saveLabel }}</span>
                    <span wire:loading wire:target="save"><i class="fa fa-spinner fa-spin"></i> Saving…</span>
                </button>
            </div>
        @endif
    @endif
</div>

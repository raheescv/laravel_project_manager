{{-- Product page → "Variants" tab, "Three steps" design (scope .pvd). Preview: docs/html_samples/product-variants-layers-preview.html (A3).
     This component renders INSIDE the product page's <form wire:submit="save">: every button is type="button" and Enter in an input never submits the product form. --}}
<div class="pvd" x-data x-on:keydown.enter="if ($event.target.tagName === 'INPUT') { $event.preventDefault() }">
    @once
        <style>
            .pvd {
                --acc: var(--bs-primary);
                --acc-deep: color-mix(in srgb, var(--acc), #000 30%);
                --acc-soft: color-mix(in srgb, var(--acc) 9%, var(--bs-body-bg));
                --acc-soft-2: color-mix(in srgb, var(--acc) 17%, var(--bs-body-bg));
                --acc-line: color-mix(in srgb, var(--acc) 40%, var(--bs-border-color));
                --acc-ink: color-mix(in srgb, var(--acc) 72%, var(--bs-emphasis-color));
                --ok: var(--bs-success);
                --ok-soft: color-mix(in srgb, var(--ok) 12%, var(--bs-body-bg));
                --line: var(--bs-border-color-translucent);
                --sunk: var(--bs-tertiary-bg);
                --pvd-shadow: 0 1px 2px rgba(15, 23, 42, .05), 0 14px 34px -22px rgba(15, 23, 42, .45);
            }
            [data-bs-theme="dark"] .pvd {
                --acc-ink: color-mix(in srgb, var(--acc) 40%, #fff);
                --acc-soft: color-mix(in srgb, var(--acc) 20%, var(--bs-body-bg));
                --acc-soft-2: color-mix(in srgb, var(--acc) 32%, var(--bs-body-bg));
                --pvd-shadow: 0 14px 34px -22px rgba(0, 0, 0, .8);
            }
            .pvd [x-cloak] { display: none !important; }
            .pvd .pvd-card { background: var(--bs-body-bg); border: 1px solid var(--line); border-radius: 16px; box-shadow: var(--pvd-shadow); }
            .pvd .pvd-muted { color: var(--bs-secondary-color); }
            .pvd .pvd-eyebrow { font-size: 11px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--bs-secondary-color); }
            .pvd h4.pvd-h { margin: 0 0 4px; font-size: 15px; font-weight: 600; color: var(--bs-emphasis-color); }
            .pvd .pvd-btn { display: inline-flex; align-items: center; gap: 7px; background: var(--acc); border: 0; color: #fff; font-weight: 500; padding: 9px 18px; border-radius: 11px; box-shadow: 0 8px 18px -10px var(--acc); }
            .pvd .pvd-btn:hover { background: var(--acc-deep); color: #fff; }
            .pvd .pvd-btn:disabled { opacity: .5; box-shadow: none; }
            .pvd .pvd-btn.sm { padding: 6px 12px; font-size: 12.5px; border-radius: 9px; }
            .pvd .pvd-btn-soft { display: inline-flex; align-items: center; gap: 7px; background: var(--bs-secondary-bg); border: 0; color: var(--bs-body-color); font-weight: 500; padding: 9px 14px; border-radius: 11px; }
            .pvd .pvd-link { border: 0; background: none; padding: 0; color: var(--acc-ink); font-weight: 600; font-size: 12px; }
            .pvd .pvd-link:hover { text-decoration: underline; }
            .pvd .pvd-sw { display: inline-block; width: 14px; height: 14px; border-radius: 50%; background: var(--sw); box-shadow: inset 0 0 0 1px rgba(0, 0, 0, .18), 0 0 0 2px var(--bs-body-bg); flex: none; }
            .pvd .pvd-sw.lg { width: 22px; height: 22px; }
            .pvd .pvd-sw.none { background: linear-gradient(135deg, transparent 44%, var(--bs-secondary-color) 44% 56%, transparent 56%); box-shadow: inset 0 0 0 1.5px var(--bs-secondary-color); opacity: .8; }
            .pvd .pvd-sw.rainbow { background: conic-gradient(#ef4444, #f59e0b, #eab308, #22c55e, #06b6d4, #3b82f6, #8b5cf6, #ec4899, #ef4444); }
            .pvd .pvd-pill { display: inline-flex; align-items: center; gap: 5px; padding: 2px 9px; border-radius: 99px; font-size: 11.5px; font-weight: 600; white-space: nowrap; }
            .pvd .pvd-pill.made { background: var(--ok-soft); color: var(--ok); }
            .pvd .pvd-pill.base { background: var(--sunk); color: var(--bs-secondary-color); }
            .pvd .pvd-pill.new { background: var(--acc-soft-2); color: var(--acc-ink); }
            .pvd .pvd-pill.edited { background: var(--bs-warning-bg-subtle); color: var(--bs-warning-text-emphasis); }

            /* Steps */
            .pvd .pvd-steps { display: flex; gap: 6px; padding: 8px; border-bottom: 1px solid var(--line); }
            .pvd .pvd-step { flex: 1; display: flex; align-items: center; gap: 10px; padding: 10px 14px; border-radius: 12px; border: 0; background: none; text-align: start; min-width: 0; }
            .pvd .pvd-step:hover { background: var(--sunk); }
            .pvd .pvd-step .n { width: 32px; height: 32px; border-radius: 10px; display: grid; place-items: center; background: var(--sunk); color: var(--bs-secondary-color); font-weight: 700; flex: none; }
            .pvd .pvd-step b { display: block; font-weight: 600; color: var(--bs-emphasis-color); }
            .pvd .pvd-step small { display: block; color: var(--bs-secondary-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .pvd .pvd-step.on { background: var(--acc-soft); }
            .pvd .pvd-step.on .n { background: var(--acc); color: #fff; }
            .pvd .pvd-step.done .n { background: var(--ok-soft); color: var(--ok); }
            .pvd .pvd-body { padding: 20px; }
            .pvd .pvd-foot { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; padding: 12px 20px; border-top: 1px solid var(--line); background: var(--sunk); border-radius: 0 0 16px 16px; }

            /* Step 1 */
            .pvd .pvd-two { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
            .pvd .pvd-box { border: 1px solid var(--line); border-radius: 14px; padding: 14px 16px; display: grid; gap: 10px; align-content: start; }
            .pvd .pvd-seg { display: inline-flex; flex-wrap: wrap; gap: 3px; padding: 3px; border-radius: 10px; background: var(--sunk); }
            .pvd .pvd-seg button { border: 0; background: none; padding: 4px 10px; border-radius: 8px; font-size: 12.5px; font-weight: 500; color: var(--bs-secondary-color); }
            .pvd .pvd-seg button.on { background: var(--bs-body-bg); color: var(--bs-emphasis-color); box-shadow: 0 1px 3px rgba(0, 0, 0, .12); }
            .pvd .pvd-chips { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
            .pvd .pvd-chip { display: inline-flex; align-items: center; gap: 7px; padding: 6px 6px 6px 13px; border-radius: 99px; border: 1px solid var(--bs-border-color); background: var(--bs-body-bg); font-weight: 500; }
            .pvd .pvd-chip.lock { padding-right: 13px; color: var(--bs-secondary-color); }
            .pvd .pvd-chip.on { border-color: var(--acc); background: var(--acc-soft); box-shadow: 0 0 0 3px var(--acc-soft-2); }
            .pvd .pvd-chip small { color: var(--bs-secondary-color); font-weight: 500; }
            .pvd .pvd-x { display: grid; place-items: center; width: 22px; height: 22px; border: 0; border-radius: 50%; background: none; color: var(--bs-secondary-color); padding: 0; }
            .pvd .pvd-x:hover { background: var(--bs-danger-bg-subtle); color: var(--bs-danger); }
            .pvd .pvd-adder { display: inline-flex; align-items: center; gap: 6px; padding: 3px 3px 3px 12px; border-radius: 99px; border: 1px dashed var(--acc-line); }
            .pvd .pvd-adder input { border: 0; outline: 0; background: transparent; width: 110px; font-size: 13px; color: var(--bs-body-color); }
            .pvd .pvd-adder button { border: 0; border-radius: 99px; background: var(--acc); color: #fff; padding: 4px 11px; font-size: 12px; font-weight: 500; }
            .pvd .pvd-ghost { display: inline-flex; align-items: center; gap: 7px; padding: 6px 12px; border-radius: 99px; border: 1px dashed var(--bs-border-color); background: none; color: var(--bs-secondary-color); font-weight: 500; }
            .pvd .pvd-ghost:hover { border-color: var(--acc); color: var(--acc-ink); }
            .pvd .pvd-choice { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 10px; }
            .pvd .pvd-opt { display: flex; align-items: center; gap: 12px; padding: 14px 16px; border-radius: 14px; border: 1.5px solid var(--bs-border-color); background: var(--bs-body-bg); text-align: start; }
            .pvd .pvd-opt .ico { width: 40px; height: 40px; border-radius: 12px; display: grid; place-items: center; background: var(--sunk); color: var(--bs-secondary-color); flex: none; font-size: 16px; }
            .pvd .pvd-opt b { display: block; color: var(--bs-emphasis-color); font-weight: 600; }
            .pvd .pvd-opt.on { border-color: var(--acc); background: var(--acc-soft); }
            .pvd .pvd-opt.on .ico { background: var(--acc); color: #fff; }
            .pvd .pvd-tip { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; padding: 10px 13px; border-radius: 12px; background: var(--acc-soft); color: var(--acc-ink); font-size: 12.5px; }

            /* Step 2 */
            .pvd .pvd-grid-wrap { overflow-x: auto; }
            .pvd .pvd-grid { border-collapse: separate; border-spacing: 8px; }
            .pvd .pvd-grid th { font-weight: 600; text-align: start; vertical-align: bottom; }
            .pvd .pvd-grid .corner { font-size: 11.5px; color: var(--bs-secondary-color); font-weight: 500; white-space: nowrap; padding: 0 8px 6px 4px; }
            .pvd .pvd-grid .colh { min-width: 132px; padding: 0 4px 2px; }
            .pvd .pvd-grid .colh .nm { display: flex; align-items: center; justify-content: space-between; gap: 8px; color: var(--bs-emphasis-color); font-size: 14px; }
            .pvd .pvd-grid .colh small { font-size: 11.5px; color: var(--bs-secondary-color); font-weight: 500; }
            .pvd .pvd-grid .rowh { white-space: nowrap; padding-inline-end: 10px; vertical-align: middle; color: var(--bs-emphasis-color); }
            .pvd .pvd-grid .rowh .pvd-link { display: block; font-weight: 500; opacity: .8; }
            .pvd .pvd-cell { width: 100%; min-width: 132px; height: 56px; border-radius: 12px; border: 1.5px solid var(--line); background: var(--sunk); display: flex; align-items: center; justify-content: center; gap: 7px; font-weight: 600; color: var(--bs-secondary-color); transition: background .12s, border-color .12s, transform .08s; }
            .pvd .pvd-cell .fa-plus { opacity: .35; }
            .pvd button.pvd-cell:hover { border-color: var(--acc); background: var(--acc-soft); color: var(--acc-ink); }
            .pvd button.pvd-cell:hover .fa-plus { opacity: 1; }
            .pvd button.pvd-cell:active { transform: scale(.97); }
            .pvd .pvd-cell.made { background: var(--ok-soft); border-color: transparent; color: var(--ok); text-decoration: none; }
            .pvd .pvd-cell.new { background: var(--acc); border-color: var(--acc); color: #fff; box-shadow: 0 10px 20px -12px var(--acc); }
            .pvd .pvd-legend { display: flex; flex-wrap: wrap; gap: 16px; color: var(--bs-secondary-color); font-size: 12px; }
            .pvd .pvd-legend i.k { display: inline-block; width: 14px; height: 14px; border-radius: 5px; margin-inline-end: 6px; vertical-align: -2px; }
            .pvd .pvd-showing { display: inline-flex; align-items: center; gap: 7px; padding: 4px 12px 4px 6px; border-radius: 99px; background: var(--sunk); font-weight: 600; font-size: 12.5px; }

            /* Step 3 */
            .pvd .pvd-bulk { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
            .pvd .pvd-bulk .form-control, .pvd .pvd-group .form-control { width: 104px; border-radius: 9px; }
            .pvd .pvd-group { border: 1px solid var(--line); border-radius: 14px; overflow: hidden; margin-top: 12px; }
            .pvd .pvd-group > header { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; padding: 10px 14px; background: linear-gradient(90deg, var(--acc-soft), transparent 70%); }
            .pvd .pvd-group__tag { display: inline-flex; align-items: center; gap: 6px; height: 26px; padding: 0 11px; border-radius: 8px; background: var(--acc); color: #fff; font-weight: 600; }
            .pvd .pvd-table { margin: 0; }
            .pvd .pvd-table thead th { font-size: 11px; text-transform: uppercase; letter-spacing: .06em; color: var(--bs-secondary-color); font-weight: 600; background: var(--sunk); border-bottom: 0; white-space: nowrap; }
            .pvd .pvd-table td { vertical-align: middle; border-color: var(--line); }
            .pvd .pvd-table .form-control-sm { border-radius: 8px; min-width: 84px; }
            .pvd .pvd-vname { display: flex; align-items: center; gap: 9px; font-weight: 600; white-space: nowrap; }
            .pvd .pvd-mono { font-family: ui-monospace, Menlo, monospace; font-size: 12.5px; }
            .pvd .pvd-ibtn { display: grid; place-items: center; width: 30px; height: 30px; border-radius: 8px; border: 0; background: transparent; color: var(--bs-secondary-color); padding: 0; }
            .pvd .pvd-ibtn:hover { background: var(--bs-danger-bg-subtle); color: var(--bs-danger); }

            /* Variant's own page */
            .pvd .pvd-sib { display: flex; flex-wrap: wrap; gap: 8px; }
            .pvd .pvd-sib a { display: inline-flex; align-items: center; gap: 7px; padding: 7px 12px; border-radius: 11px; border: 1px solid var(--bs-border-color); color: var(--bs-body-color); text-decoration: none; font-weight: 500; }
            .pvd .pvd-sib a:hover { border-color: var(--acc); }
            .pvd .pvd-sib a.on { background: var(--acc); border-color: var(--acc); color: #fff; }

            /* Colour chooser */
            .pvd .pvd-back { position: fixed; inset: 0; z-index: 1055; background: rgba(15, 23, 42, .45); backdrop-filter: blur(2px); }
            .pvd .pvd-chooser { position: fixed; z-index: 1056; left: 50%; top: 50%; transform: translate(-50%, -50%); width: min(400px, calc(100% - 32px)); max-height: calc(100vh - 40px); overflow: auto; border-radius: 20px; background: var(--bs-body-bg); box-shadow: 0 30px 60px -20px rgba(0, 0, 0, .5); border: 1px solid var(--line); }
            .pvd .pvd-chooser__top { display: flex; align-items: center; gap: 14px; padding: 18px 18px 14px; }
            .pvd .pvd-chooser__sec { padding: 0 18px 14px; }
            .pvd .pvd-chooser__sec > small { display: block; font-weight: 600; color: var(--bs-secondary-color); margin-bottom: 8px; }
            .pvd .pvd-shades { display: grid; grid-template-columns: repeat(8, 1fr); gap: 8px; }
            .pvd .pvd-shade { aspect-ratio: 1; border-radius: 50%; border: 0; padding: 0; background: var(--sw); box-shadow: inset 0 0 0 1px rgba(0, 0, 0, .18); transition: transform .12s; }
            .pvd .pvd-shade:hover { transform: scale(1.12); }
            .pvd .pvd-any { display: flex; align-items: center; gap: 12px; padding: 10px 12px; border-radius: 12px; border: 1px dashed var(--bs-border-color); cursor: pointer; margin: 0; }
            .pvd .pvd-any:hover { border-color: var(--acc); }
            .pvd .pvd-any input { width: 34px; height: 34px; padding: 0; border: 0; border-radius: 50%; background: none; cursor: pointer; }
            .pvd .pvd-custom { display: flex; align-items: center; gap: 12px; margin-top: 10px; padding: 10px 12px; border-radius: 12px; background: var(--acc-soft); }
            .pvd .pvd-custom .pvd-prev { width: 40px; height: 40px; border-radius: 12px; flex: none; background: var(--sw); box-shadow: inset 0 0 0 1px rgba(0, 0, 0, .15); }

            @media (max-width: 767.98px) {
                .pvd .pvd-two, .pvd .pvd-choice { grid-template-columns: 1fr; }
                .pvd .pvd-step small { display: none; }
                .pvd .pvd-body { padding: 16px; }
            }
        </style>
    @endonce

    @php
        $swatch = fn (string $colour, string $size = '') => $colour === ''
            ? '<span class="pvd-sw none ' . $size . '"></span>'
            : '<span class="pvd-sw ' . $size . '" style="--sw:' . e($hex[$colour] ?? '#9ca3af') . '"></span>';
        $colourName = fn (string $colour) => $colour === '' ? 'No colour' : \App\Support\ProductColours::displayName($colour);
        $sizeName = fn (string $size) => $size === '' ? 'No ' . $sizeLower : $size;
        $designName = fn (string $design) => $design === '' ? 'No ' . $optionLower : $design;
        $label = fn (string $key) => $this->cellLabel($key);
        $newCount = count($orderedPicked);
        $madeCount = count($existing);
        $colourTabs = $hasDesigns && $coloursOn;
    @endphp

    @if ($isVariant)
        {{-- A variant's own page: point back to the family head and the siblings. --}}
        <div class="pvd-card">
            <div class="d-flex flex-wrap align-items-center gap-2 px-3 py-3 border-bottom">
                <h6 class="m-0 fw-semibold"><i class="fa fa-sitemap me-1"></i> Variant of {{ $head['name'] }}</h6>
                <a href="{{ route('product::edit', $head['id']) }}" class="ms-auto small">Open {{ $head['name'] }} to add {{ $optionPlural }}, {{ $sizePlural }} or colours <i class="fa fa-chevron-right"></i></a>
            </div>
            <div class="p-3">
                <p class="pvd-muted small mb-2">Name, category, brand, unit, tax and accounts come from {{ $head['name'] }} and are changed there. Barcode, {{ $optionLower }}, {{ $sizeLower }}, colour and prices belong to this variant.</p>
                <div class="pvd-sib">
                    @foreach ($siblings as $sibling)
                        <a href="{{ route('product::edit', $sibling['id']) }}" @class(['on' => $sibling['current']]) wire:key="sib-{{ $sibling['id'] }}">
                            {!! $swatch($sibling['colour']) !!} {{ $sibling['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @elseif (($head['size'] ?? '') === '' && ($head['design'] ?? '') === '')
        {{-- The original needs a design or a size before it can anchor a family. --}}
        <div class="pvd-card p-4">
            <h4 class="pvd-h">Which {{ $optionLower }} and {{ $sizeLower }} is {{ $head['name'] }}?</h4>
            <p class="pvd-muted mb-3">This product becomes the first variant of the family. Fill in its {{ $optionLower }}, its {{ $sizeLower }}, or both. You can add its colour in Attributes.</p>
            <div class="pvd-chips">
                <span class="pvd-adder"><input wire:model="baseDesign" wire:keydown.enter="saveBaseSize" placeholder="{{ $option }}, e.g. {{ $designExample }}" /></span>
                <span class="pvd-adder">
                    <input wire:model="baseSize" wire:keydown.enter="saveBaseSize" placeholder="{{ $sizeOption }}, e.g. {{ $sizeExample }}" />
                    <button type="button" wire:click="saveBaseSize"><i class="fa fa-check"></i> Save</button>
                </span>
            </div>
        </div>
    @else
        @php
            $total = count($gridColumns) * count($sizes);
            $steps = [
                1 => ['What is it made of?', collect([
                    $hasDesigns ? count($designs) . ' ' . ($hasDesigns && count($designs) === 1 ? $optionLower : $optionPlural) : null,
                    $hasSizes ? count($sizes) . ' ' . (count($sizes) === 1 ? $sizeLower : $sizePlural) : null,
                    $coloursOn ? count($colours) . ' ' . (count($colours) === 1 ? 'colour' : 'colours') : null,
                ])->filter()->implode(' × ')],
                2 => ['Which ones exist?', $madeCount . ' made · ' . $newCount . ' new'],
                3 => ['Barcode and prices', $newCount ? $newCount . ' new to price' : 'Edit the made ones'],
            ];
            $saveLabel = collect([
                $newCount ? 'Create ' . $newCount . ' variant' . ($newCount === 1 ? '' : 's') : null,
                $changed ? ($newCount ? 'update ' : 'Update ') . count($changed) : null,
            ])->filter()->implode(' & ');
        @endphp
        <div class="pvd-card"
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

            <nav class="pvd-steps" aria-label="Variant steps">
                @foreach ($steps as $number => [$title, $sub])
                    <button type="button" @class(['pvd-step', 'on' => $step === $number, 'done' => $step > $number]) wire:click="goToStep({{ $number }})">
                        <span class="n">@if ($step > $number)<i class="fa fa-check"></i>@else{{ $number }}@endif</span>
                        <span class="min-w-0"><b>{{ $title }}</b><small>{{ $sub }}</small></span>
                    </button>
                @endforeach
            </nav>

            <div class="pvd-body">
                @if ($step === 1)
                    {{-- 1 · One or two layers, their names and values, colours yes / no --}}
                    @php
                        $layers = array_filter([
                            ['n' => 1, 'field' => 'design', 'name' => $option, 'choices' => $optionChoices, 'set' => 'setOption', 'custom' => 'customOption', 'values' => $designs, 'model' => 'newDesign', 'add' => 'addDesign', 'remove' => 'removeDesign', 'plural' => $optionPlural, 'example' => $designExample, 'blank' => $designName],
                            ['n' => 2, 'field' => 'size', 'name' => $sizeOption, 'choices' => $sizeChoices, 'set' => 'setSizeOption', 'custom' => 'customSizeOption', 'values' => $sizes, 'model' => 'newSize', 'add' => 'addSize', 'remove' => 'removeSize', 'plural' => $sizePlural, 'example' => $sizeExample, 'blank' => $sizeName],
                        ], fn (array $layer) => $twoLayers || $layer['field'] === $this->singleLayer());
                    @endphp
                    <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
                        <div class="me-auto">
                            <h4 class="pvd-h">What does {{ $head['name'] }} come in?</h4>
                            <p class="pvd-muted m-0">The till, receipts and stock reports use these words.</p>
                        </div>
                        <span class="pvd-seg" role="group" aria-label="How many layers">
                            <button type="button" @class(['on' => ! $twoLayers]) wire:click="useTwoLayers(false)" title="Just sizes, or just flavours">One layer</button>
                            <button type="button" @class(['on' => $twoLayers]) wire:click="useTwoLayers(true)" title="e.g. Base × Flavour, Design × Size">Two layers</button>
                        </span>
                    </div>
                    @if ($twoLayers)
                        <div class="pvd-tip mb-3">
                            <i class="fa fa-sort-amount-asc"></i>
                            <span class="flex-grow-1">The till asks for the <b>{{ $optionLower }}</b> first, then the <b>{{ $sizeLower }}</b>.</span>
                            @can('product.edit')
                                <button type="button" class="pvd-btn-soft" wire:click="swapLayers" wire:confirm="Ask for the {{ $sizeLower }} first, then the {{ $optionLower }}? Every variant of {{ $head['name'] }} is switched round.">
                                    <i class="fa fa-exchange"></i> {{ $sizeOption }} first
                                </button>
                            @endcan
                        </div>
                    @endif
                    <div @class(['pvd-two' => $twoLayers])>
                        @foreach ($layers as $layer)
                            <div class="pvd-box" wire:key="layer-{{ $layer['field'] }}" x-data="{ other: false }">
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <span class="pvd-eyebrow">{{ $twoLayers ? 'Layer ' . $layer['n'] . ($layer['n'] === 1 ? ' · asked first' : ' · then') : 'Comes in' }}</span>
                                    @can('product.edit')
                                        <span class="pvd-seg ms-auto" role="group" aria-label="What this layer is called">
                                            @foreach ($layer['choices'] as $choice)
                                                <button type="button" @class(['on' => mb_strtolower($layer['name']) === mb_strtolower($choice)]) wire:click="{{ $layer['set'] }}(@js($choice))" wire:key="name-{{ $layer['n'] }}-{{ md5($choice) }}">{{ $choice }}</button>
                                            @endforeach
                                            @unless (collect($layer['choices'])->contains(fn ($choice) => mb_strtolower($choice) === mb_strtolower($layer['name'])))
                                                <button type="button" class="on">{{ $layer['name'] }}</button>
                                            @endunless
                                            <button type="button" x-on:click="other = ! other" title="Another name">Other…</button>
                                        </span>
                                    @endcan
                                </div>
                                @can('product.edit')
                                    <span class="pvd-adder" style="justify-self: end" x-show="other" x-cloak>
                                        <input wire:model="{{ $layer['custom'] }}" wire:keydown.enter="{{ $layer['set'] }}" x-on:keydown.enter="other = false" placeholder="e.g. {{ $layer['n'] === 1 ? 'Scent' : 'Strength' }}" maxlength="40" style="width: 160px" />
                                        <button type="button" wire:click="{{ $layer['set'] }}" x-on:click="other = false"><i class="fa fa-check"></i> Rename</button>
                                    </span>
                                @endcan
                                <h4 class="pvd-h">{{ \Illuminate\Support\Str::ucfirst($layer['plural']) }}</h4>
                                <div class="pvd-chips">
                                    @foreach ($layer['values'] as $value)
                                        @continue($value === '' && ! $this->layerInUse($layer['field'], ''))
                                        @if ($this->layerInUse($layer['field'], $value))
                                            <span class="pvd-chip lock" wire:key="v-{{ $layer['n'] }}-{{ md5($value) }}" title="Already made — remove its variants to drop it"><i class="fa fa-lock"></i> {{ $layer['blank']($value) }}</span>
                                        @else
                                            <span class="pvd-chip" wire:key="v-{{ $layer['n'] }}-{{ md5($value) }}">{{ $value }}
                                                <button type="button" class="pvd-x" wire:click="{{ $layer['remove'] }}(@js($value))" title="Remove"><i class="fa fa-times"></i></button>
                                            </span>
                                        @endif
                                    @endforeach
                                    <span class="pvd-adder">
                                        <input wire:model="{{ $layer['model'] }}" wire:keydown.enter="{{ $layer['add'] }}" placeholder="e.g. {{ $layer['example'] }}" />
                                        <button type="button" wire:click="{{ $layer['add'] }}"><i class="fa fa-plus"></i> {{ $layer['name'] }}</button>
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if ($canMoveSizes)
                        <div class="pvd-tip mt-3">
                            <i class="fa fa-lightbulb-o"></i>
                            <span class="flex-grow-1">Typed {{ $optionPlural }} into the {{ $sizePlural }}? Move every {{ $sizeLower }} of {{ $head['name'] }} to its {{ $optionLower }}, then add real {{ $sizePlural }} under each one.</span>
                            <button type="button" class="pvd-btn-soft" wire:click="moveSizesToDesign" wire:confirm="Move every {{ $sizeLower }} of {{ $head['name'] }} into its {{ $optionLower }}?"><i class="fa fa-exchange"></i> {{ \Illuminate\Support\Str::ucfirst($sizePlural) }} are {{ $optionPlural }}</button>
                        </div>
                    @endif

                    <h4 class="pvd-h mt-4">Does it come in colours?</h4>
                    <div class="pvd-choice">
                        <button type="button" @class(['pvd-opt', 'on' => ! $coloursOn]) wire:click="useColours(false)">
                            <span class="ico"><i class="fa fa-cubes"></i></span>
                            <span><b>No colours</b><span class="pvd-muted small">Packs, drinks, food</span></span>
                        </button>
                        <button type="button" @class(['pvd-opt', 'on' => $coloursOn]) wire:click="useColours(true)">
                            <span class="ico"><i class="fa fa-paint-brush"></i></span>
                            <span><b>Yes, in colours</b><span class="pvd-muted small">Clothes, shoes, bags</span></span>
                        </button>
                    </div>
                    @if ($coloursOn)
                        <div class="pvd-chips mt-3">
                            @foreach ($colours as $colour)
                                @if ($this->layerInUse('colour', $colour))
                                    <span class="pvd-chip lock" wire:key="colour-{{ md5($colour) }}" title="Already made">{!! $swatch($colour) !!} {{ $colourName($colour) }} <i class="fa fa-lock"></i></span>
                                @elseif ($colour !== '')
                                    <span class="pvd-chip" wire:key="colour-{{ md5($colour) }}">{!! $swatch($colour) !!} {{ $colourName($colour) }}
                                        <button type="button" class="pvd-x" wire:click="removeColour(@js($colour))" title="Remove colour"><i class="fa fa-times"></i></button>
                                    </span>
                                @endif
                            @endforeach
                            <button type="button" class="pvd-btn sm" x-on:click="open = true"><span class="pvd-sw rainbow"></span> Choose a colour</button>
                            @foreach ($quickAdd as $name)
                                <button type="button" class="pvd-ghost" wire:click="addColour(@js($name))" wire:key="quick-{{ md5($name) }}">{!! $swatch($name) !!} {{ $name }}</button>
                            @endforeach
                            @unless (in_array('', $colours, true))
                                <button type="button" class="pvd-ghost" wire:click="addNoColour">{!! $swatch('') !!} No colour</button>
                            @endunless
                        </div>
                    @endif
                @elseif ($step === 2)
                    {{-- 2 · The grid: tap a box to make it --}}
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <h4 class="pvd-h m-0">Tap the ones you sell</h4>
                        @if ($colourTabs)
                            <span class="pvd-showing">{!! $swatch($activeColour, 'lg') !!} Showing {{ $colourName($activeColour) }}</span>
                        @endif
                        <span class="pvd-legend ms-auto">
                            <span><i class="k" style="background: var(--ok-soft)"></i>Made</span>
                            <span><i class="k" style="background: var(--acc)"></i>New</span>
                            <span><i class="k" style="background: var(--sunk); border: 1.5px solid var(--line)"></i>Not made, tap to make</span>
                        </span>
                    </div>
                    @if ($colourTabs)
                        <div class="pvd-chips mb-2">
                            @foreach ($colours as $colour)
                                @php $inColour = collect($designs)->crossJoin($sizes)->filter(fn ($pair) => isset($existing[$this->cellKey($pair[1], $colour, $pair[0])]) || isset($picked[$this->cellKey($pair[1], $colour, $pair[0])]))->count(); @endphp
                                <button type="button" @class(['pvd-chip', 'on' => $colour === $activeColour]) style="padding-right: 13px" wire:click="selectColour(@js($colour))" wire:key="tab-{{ md5($colour) }}">
                                    {!! $swatch($colour, 'lg') !!} {{ $colourName($colour) }} <small>{{ $inColour }}/{{ $total }}</small>
                                </button>
                            @endforeach
                            <button type="button" class="pvd-ghost" x-on:click="open = true"><span class="pvd-sw rainbow"></span> Add colour</button>
                        </div>
                    @endif
                    <div class="pvd-grid-wrap">
                        <table class="pvd-grid">
                            <thead>
                                <tr>
                                    <th class="corner">{{ collect([$hasSizes ? $sizeOption . ' ↓' : null, $hasDesigns ? $option . ' →' : ($coloursOn ? 'Colour →' : null)])->filter()->implode(' · ') }}</th>
                                    @foreach ($gridColumns as $column)
                                        @php $filled = collect($sizes)->filter(fn ($size) => isset($existing[$this->gridKey($size, $column)]) || isset($picked[$this->gridKey($size, $column)]))->count(); @endphp
                                        <th class="colh" wire:key="colh-{{ md5($column) }}">
                                            <div class="nm">
                                                <span class="d-inline-flex align-items-center gap-2">@if (! $hasDesigns && $coloursOn){!! $swatch($column) !!}@endif {{ $hasDesigns ? $designName($column) : ($coloursOn ? $colourName($column) : 'Make it') }}</span>
                                                @if (count($sizes) > 1)<button type="button" class="pvd-link" wire:click="toggleColumn(@js($column))">All</button>@endif
                                            </div>
                                            <small>{{ $filled }} of {{ count($sizes) }}</small>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($sizes as $size)
                                    <tr wire:key="row-{{ md5($size) }}">
                                        <th class="rowh">
                                            {{ $hasSizes ? $sizeName($size) : 'Make it' }}
                                            @if (count($gridColumns) > 1)<button type="button" class="pvd-link" wire:click="toggleRow(@js($size))">All {{ $hasDesigns ? $optionPlural : 'colours' }}</button>@endif
                                        </th>
                                        @foreach ($gridColumns as $column)
                                            @php $key = $this->gridKey($size, $column); @endphp
                                            <td wire:key="cell-{{ $key }}">
                                                @if (isset($existing[$key]))
                                                    <a class="pvd-cell made" href="{{ route('product::edit', $existing[$key]['id']) }}" title="{{ $label($key) }}: already made — open it">
                                                        <i class="fa {{ $existing[$key]['id'] === $head['id'] ? 'fa-lock' : 'fa-check-circle' }}"></i> {{ $existing[$key]['id'] === $head['id'] ? 'Original' : 'Made' }}
                                                    </a>
                                                @elseif (isset($picked[$key]))
                                                    <button type="button" class="pvd-cell new" wire:click="toggle('{{ $key }}')" title="Tap again to drop it"><i class="fa fa-check"></i> New</button>
                                                @else
                                                    <button type="button" class="pvd-cell" wire:click="toggle('{{ $key }}')" title="Make {{ $label($key) }}"><i class="fa fa-plus"></i></button>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="pvd-muted small mt-2 mb-0"><i class="fa fa-info-circle"></i> Missing a {{ $hasDesigns ? $optionLower : 'colour' }} or {{ $sizeLower }}? Add it in <button type="button" class="pvd-link" wire:click="goToStep(1)">step 1</button>.</p>
                @else
                    {{-- 3 · Barcode, price and opening stock, grouped --}}
                    <div class="pvd-bulk">
                        <h4 class="pvd-h m-0 me-2">Barcode and prices</h4>
                        @if ($orderedPicked || $existing)
                            <span class="pvd-eyebrow">Same for all</span>
                            <input class="form-control form-control-sm" wire:model="bulk.cost" placeholder="Cost" />
                            <input class="form-control form-control-sm" wire:model="bulk.mrp" placeholder="MRP" />
                            <input class="form-control form-control-sm" wire:model="bulk.opening_stock" placeholder="Opening stock" style="width: 140px" />
                            <button type="button" class="pvd-btn sm" wire:click="applyToAll"><i class="fa fa-check"></i> Apply</button>
                        @endif
                        <span class="pvd-muted small ms-auto">Name, category, brand, tax and unit come from {{ $head['name'] }}</span>
                    </div>
                    @forelse ($groups as $group)
                        @php
                            $groupLabel = match ($groupField) {
                                'design' => $designName($group['value']),
                                'colour' => $colourName($group['value']),
                                default => 'All',
                            };
                            $groupNew = count($group['picked']);
                        @endphp
                        <div class="pvd-group" wire:key="group-{{ $group['key'] }}">
                            <header>
                                <span class="pvd-group__tag">@if ($groupField === 'colour'){!! $swatch($group['value']) !!}@endif {{ $groupLabel }}</span>
                                <span class="pvd-muted small">{{ $groupNew }} new{{ count($group['existing']) ? ' · ' . count($group['existing']) . ' made' : '' }}</span>
                                <span class="ms-auto d-flex flex-wrap align-items-center gap-2">
                                    <span class="pvd-eyebrow">Same for {{ $groupLabel }}</span>
                                    <input class="form-control form-control-sm" wire:model="groupBulk.{{ $group['key'] }}.cost" placeholder="Cost" />
                                    <input class="form-control form-control-sm" wire:model="groupBulk.{{ $group['key'] }}.mrp" placeholder="MRP" />
                                    @if ($groupNew)
                                        <input class="form-control form-control-sm" wire:model="groupBulk.{{ $group['key'] }}.opening_stock" placeholder="Stock" title="Opening stock for the new ones" />
                                    @endif
                                    <button type="button" class="pvd-btn sm" wire:click="applyToGroup('{{ $group['key'] }}')" title="Apply to {{ $groupLabel }}"><i class="fa fa-check"></i></button>
                                </span>
                            </header>
                            <div class="table-responsive">
                                <table class="table pvd-table">
                                    <thead>
                                        <tr><th>Variant</th><th></th><th>Barcode</th><th>Cost</th><th>MRP</th><th>Opening stock</th><th></th></tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($group['picked'] as $key)
                                            <tr wire:key="new-{{ $key }}">
                                                <td><div class="pvd-vname">{!! $swatch($cells[$key]['colour']) !!} {{ $label($key) }}</div></td>
                                                <td><span class="pvd-pill new"><i class="fa fa-plus"></i> New</span></td>
                                                <td><input class="form-control form-control-sm pvd-mono" wire:model="picked.{{ $key }}.barcode_number" placeholder="Auto" style="min-width: 140px" /></td>
                                                <td><input class="form-control form-control-sm" wire:model="picked.{{ $key }}.cost" /></td>
                                                <td><input class="form-control form-control-sm" wire:model="picked.{{ $key }}.mrp" /></td>
                                                <td><input class="form-control form-control-sm" wire:model="picked.{{ $key }}.opening_stock" placeholder="Optional" /></td>
                                                <td class="text-end"><button type="button" class="pvd-ibtn" wire:click="toggle('{{ $key }}')" title="Don't create this one"><i class="fa fa-trash-o"></i></button></td>
                                            </tr>
                                        @endforeach
                                        @foreach ($group['existing'] as $key)
                                            @php $member = $existing[$key]; @endphp
                                            <tr wire:key="made-{{ $key }}">
                                                <td><div class="pvd-vname">{!! $swatch($cells[$key]['colour']) !!} {{ $label($key) }}</div></td>
                                                <td>
                                                    @if ($member['id'] === $head['id'])
                                                        <span class="pvd-pill base"><i class="fa fa-lock"></i> Original</span>
                                                    @else
                                                        <a href="{{ route('product::edit', $member['id']) }}" class="pvd-pill made text-decoration-none" title="Open this variant"><i class="fa fa-check"></i> Made</a>
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
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @empty
                        <p class="pvd-muted text-center py-4 m-0">Nothing to price yet. <button type="button" class="pvd-link" wire:click="goToStep(2)">Tap the ones you sell</button> first.</p>
                    @endforelse
                @endif
            </div>

            <div class="pvd-foot">
                @if ($step > 1)
                    <button type="button" class="pvd-btn-soft" wire:click="goToStep({{ $step - 1 }})"><i class="fa fa-chevron-left"></i> Back</button>
                @endif
                <span class="pvd-muted ms-auto small">{{ $newCount }} new · {{ $madeCount }} made{{ $changed ? ' · ' . count($changed) . ' edited' : '' }} · shows as 1 product in the list</span>
                @if ($orderedPicked || $changed)
                    <button type="button" class="pvd-btn-soft" wire:click="cancel">Cancel</button>
                @endif
                @if ($step < 3)
                    <button type="button" class="pvd-btn" wire:click="goToStep({{ $step + 1 }})">Next <i class="fa fa-chevron-right"></i></button>
                @else
                    <button type="button" class="pvd-btn" wire:click="save" wire:loading.attr="disabled" wire:target="save" @disabled(! $orderedPicked && ! $changed)>
                        <span wire:loading.remove wire:target="save"><i class="fa fa-check"></i> {{ $saveLabel ?: 'Nothing to save' }}</span>
                        <span wire:loading wire:target="save"><i class="fa fa-spinner fa-spin"></i> Saving…</span>
                    </button>
                @endif
            </div>

            {{-- Colour chooser: tap a shade to add it; "Pick any shade" names it automatically. --}}
            @if ($coloursOn)
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
                            <div class="pvd-shades">
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
                                <button type="button" class="pvd-btn sm" x-on:click="pick(name, hex)"><i class="fa fa-plus"></i> Add</button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            @endif
        </div>
    @endif
</div>

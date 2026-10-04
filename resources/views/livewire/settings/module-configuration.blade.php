@php
    $systemLooks = [
        'Tailor Module' => ['icon' => 'fa-scissors', 'tone' => '#7c4fd6'],
        'Property Management Module' => ['icon' => 'fa-building', 'tone' => '#1f9a96'],
        'POS Module' => ['icon' => 'fa-shopping-cart', 'tone' => '#2f6fd6'],
        'School Module' => ['icon' => 'fa-graduation-cap', 'tone' => '#d4931c'],
        'Issues Module' => ['icon' => 'fa-life-ring', 'tone' => '#d94848'],
    ];
    $lookFor = fn (string $system): array => $systemLooks[$system] ?? ['icon' => 'fa-cubes', 'tone' => 'var(--bs-primary)'];
    $labelFor = fn (string $key): string => $moduleLabels[$key] ?? Str::headline($key);
@endphp

<div class="mcx">
    <style>
        .mcx {
            --mcx-acc: var(--bs-primary);
            --mcx-acc-soft: color-mix(in srgb, var(--mcx-acc) 10%, transparent);
            --mcx-acc-ink: color-mix(in srgb, var(--mcx-acc) 85%, var(--bs-emphasis-color));
            --mcx-grad: linear-gradient(135deg, color-mix(in srgb, var(--mcx-acc) 80%, #fff), var(--mcx-acc) 45%, color-mix(in srgb, var(--mcx-acc) 70%, #000));
            --mcx-surface: var(--bs-component-bg, var(--bs-body-bg));
            --mcx-surface-2: color-mix(in srgb, var(--mcx-acc) 3%, var(--mcx-surface));
            --mcx-line: var(--bs-border-color);
            --mcx-line-soft: color-mix(in srgb, var(--bs-border-color) 60%, transparent);
            --mcx-lift: 0 1px 2px rgba(16, 24, 40, .06), 0 14px 30px -18px rgba(16, 24, 40, .35);
            --mcx-ok: #2f9e62;
            --mcx-bad: #d94848;
            --mcx-fz: .84rem;
            font-size: var(--mcx-fz);
        }

        [data-bs-theme="dark"] .mcx {
            --mcx-acc-soft: color-mix(in srgb, var(--mcx-acc) 22%, transparent);
            --mcx-acc-ink: color-mix(in srgb, var(--mcx-acc) 50%, #fff);
            --mcx-lift: 0 1px 2px rgba(0, 0, 0, .4), 0 14px 30px -16px rgba(0, 0, 0, .65);
        }

        /* ---- Intro ---- */
        .mcx-intro {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: .9rem 1.25rem;
            padding: 1rem 1.15rem;
            border: 1px solid var(--mcx-line-soft);
            border-radius: 16px;
            background:
                radial-gradient(120% 140% at 0% 0%, color-mix(in srgb, var(--mcx-acc) 12%, transparent), transparent 60%),
                var(--mcx-surface-2);
        }

        .mcx-intro-ic {
            display: grid;
            place-items: center;
            flex: none;
            width: 46px;
            height: 46px;
            border-radius: 14px;
            background: var(--mcx-grad);
            color: #fff;
            font-size: 1.15rem;
            box-shadow: 0 10px 22px -12px color-mix(in srgb, var(--mcx-acc) 80%, transparent);
        }

        .mcx-intro-text {
            flex: 1 1 260px;
            min-width: 0;
        }

        .mcx-intro-text h6 {
            margin: 0;
            font-size: .98rem;
            font-weight: 700;
            color: var(--bs-emphasis-color);
        }

        .mcx-intro-text p {
            margin: .15rem 0 0;
            color: var(--bs-secondary-color);
        }

        .mcx-status {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            padding: .4rem .8rem;
            border-radius: 999px;
            border: 1px solid var(--mcx-line-soft);
            background: var(--mcx-surface);
            font-weight: 600;
            font-size: .78rem;
            color: var(--bs-emphasis-color);
            white-space: nowrap;
        }

        .mcx-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--mcx-ok);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--mcx-ok) 22%, transparent);
        }

        .mcx-status.is-pending .mcx-dot {
            background: #d4931c;
            box-shadow: 0 0 0 3px color-mix(in srgb, #d4931c 25%, transparent);
        }

        .mcx-status.is-empty .mcx-dot {
            background: var(--bs-tertiary-color);
            box-shadow: none;
        }

        /* ---- System tiles ---- */
        .mcx-section-label {
            margin: 1.4rem 0 .65rem;
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--bs-secondary-color);
        }

        .mcx-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
            gap: .85rem;
        }

        .mcx-tile {
            --tone: var(--mcx-acc);
            position: relative;
            display: flex;
            flex-direction: column;
            gap: .7rem;
            margin: 0;
            padding: 1rem 1rem .9rem;
            border: 1px solid var(--mcx-line);
            border-radius: 16px;
            background: var(--mcx-surface);
            cursor: pointer;
            transition: border-color .18s, box-shadow .18s, transform .18s;
        }

        .mcx-tile:hover {
            border-color: color-mix(in srgb, var(--tone) 45%, var(--mcx-line));
            box-shadow: var(--mcx-lift);
            transform: translateY(-2px);
        }

        .mcx-tile:has(.mcx-radio:focus-visible) {
            outline: 2px solid var(--mcx-acc);
            outline-offset: 2px;
        }

        .mcx-tile.is-selected {
            border-color: var(--mcx-acc);
            background: linear-gradient(180deg, color-mix(in srgb, var(--mcx-acc) 7%, var(--mcx-surface)), var(--mcx-surface) 70%);
            box-shadow: 0 0 0 3px var(--mcx-acc-soft), var(--mcx-lift);
        }

        .mcx-radio {
            position: absolute;
            width: 1px;
            height: 1px;
            opacity: 0;
            pointer-events: none;
        }

        .mcx-tile-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .5rem;
        }

        .mcx-tile-ic {
            display: grid;
            place-items: center;
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: color-mix(in srgb, var(--tone) 13%, transparent);
            color: var(--tone);
            font-size: 1.05rem;
            transition: background .18s, color .18s;
        }

        .mcx-tile.is-selected .mcx-tile-ic {
            background: var(--tone);
            color: #fff;
            box-shadow: 0 8px 18px -10px var(--tone);
        }

        .mcx-check {
            display: grid;
            place-items: center;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            border: 2px solid var(--mcx-line);
            color: transparent;
            font-size: .68rem;
            transition: all .18s;
        }

        .mcx-tile.is-selected .mcx-check {
            border-color: var(--mcx-acc);
            background: var(--mcx-acc);
            color: #fff;
        }

        .mcx-tile-name {
            font-weight: 700;
            font-size: .95rem;
            color: var(--bs-emphasis-color);
            line-height: 1.25;
        }

        .mcx-tile-lede {
            margin-top: .2rem;
            color: var(--bs-secondary-color);
            line-height: 1.45;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .mcx-tile-foot {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
            margin-top: auto;
            padding-top: .65rem;
            border-top: 1px dashed var(--mcx-line-soft);
            font-size: .76rem;
            color: var(--bs-secondary-color);
        }

        .mcx-current {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            padding: .12rem .5rem;
            border-radius: 999px;
            background: color-mix(in srgb, var(--mcx-ok) 13%, transparent);
            color: color-mix(in srgb, var(--mcx-ok) 80%, var(--bs-emphasis-color));
            font-weight: 600;
            font-size: .7rem;
        }

        /* ---- Included modules ---- */
        .mcx-panel {
            margin-top: 1.4rem;
            border: 1px solid var(--mcx-line-soft);
            border-radius: 16px;
            background: var(--mcx-surface-2);
            overflow: hidden;
        }

        .mcx-panel-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: .5rem 1rem;
            padding: .85rem 1.1rem;
            border-bottom: 1px solid var(--mcx-line-soft);
        }

        .mcx-panel-title {
            display: flex;
            align-items: center;
            gap: .55rem;
            font-weight: 700;
            color: var(--bs-emphasis-color);
        }

        .mcx-count {
            padding: .1rem .5rem;
            border-radius: 999px;
            background: var(--mcx-acc-soft);
            color: var(--mcx-acc-ink);
            font-size: .72rem;
            font-weight: 700;
        }

        .mcx-legend {
            display: flex;
            gap: .85rem;
            font-size: .74rem;
            font-weight: 600;
        }

        .mcx-legend .is-added { color: var(--mcx-ok); }
        .mcx-legend .is-removed { color: var(--mcx-bad); }

        .mcx-chips {
            display: flex;
            flex-wrap: wrap;
            gap: .45rem;
            padding: 1rem 1.1rem 1.1rem;
        }

        .mcx-chip {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            padding: .38rem .7rem;
            border: 1px solid var(--mcx-line-soft);
            border-radius: 10px;
            background: var(--mcx-surface);
            color: var(--bs-emphasis-color);
            font-size: .78rem;
            font-weight: 500;
        }

        .mcx-chip i {
            font-size: .7rem;
            color: var(--mcx-acc);
        }

        .mcx-chip.is-added {
            border-color: color-mix(in srgb, var(--mcx-ok) 40%, transparent);
            background: color-mix(in srgb, var(--mcx-ok) 10%, var(--mcx-surface));
        }

        .mcx-chip.is-added i { color: var(--mcx-ok); }

        .mcx-chip.is-removed {
            border-style: dashed;
            border-color: color-mix(in srgb, var(--mcx-bad) 40%, transparent);
            background: transparent;
            color: var(--bs-secondary-color);
            text-decoration: line-through;
        }

        .mcx-chip.is-removed i { color: var(--mcx-bad); }

        .mcx-empty {
            padding: 2rem 1rem;
            text-align: center;
            color: var(--bs-secondary-color);
        }

        .mcx-empty i {
            display: block;
            margin-bottom: .4rem;
            font-size: 1.5rem;
            opacity: .5;
        }

        /* ---- Save bar ---- */
        .mcx-bar {
            position: sticky;
            bottom: 0;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: .75rem;
            margin-top: 1.25rem;
            padding: .75rem .85rem .75rem 1.1rem;
            border: 1px solid var(--mcx-line-soft);
            border-radius: 14px;
            background: color-mix(in srgb, var(--mcx-surface) 88%, transparent);
            backdrop-filter: blur(8px);
            box-shadow: var(--mcx-lift);
        }

        .mcx-bar-note {
            display: flex;
            align-items: center;
            gap: .5rem;
            color: var(--bs-secondary-color);
            font-size: .78rem;
        }

        .mcx-bar-note.is-pending {
            color: color-mix(in srgb, #d4931c 85%, var(--bs-emphasis-color));
            font-weight: 600;
        }

        .mcx-actions {
            display: flex;
            gap: .5rem;
            margin-inline-start: auto;
        }

        .mcx-actions .btn {
            border-radius: 10px;
            font-weight: 600;
            padding-inline: 1rem;
        }

        @media (max-width: 575.98px) {
            .mcx-grid {
                grid-template-columns: 1fr;
            }

            .mcx-actions {
                width: 100%;
            }

            .mcx-actions .btn {
                flex: 1;
            }
        }
    </style>

    <form wire:submit="save">
        <div class="mcx-intro">
            <span class="mcx-intro-ic"><i class="fa fa-cubes"></i></span>
            <div class="mcx-intro-text">
                <h6>Which system does this business run?</h6>
                <p>Pick one. Menus and role permissions are filtered to the modules it includes.</p>
            </div>
            @if ($isChanged)
                <span class="mcx-status is-pending"><span class="mcx-dot"></span>Unsaved: {{ $active_module }}</span>
            @elseif ($saved_module)
                <span class="mcx-status"><span class="mcx-dot"></span>Live: {{ $saved_module }}</span>
            @else
                <span class="mcx-status is-empty"><span class="mcx-dot"></span>No system chosen</span>
            @endif
        </div>

        <div class="mcx-section-label">Systems</div>
        <div class="mcx-grid" role="radiogroup" aria-label="System">
            @foreach ($systems as $systemName => $systemModules)
                @php($look = $lookFor($systemName))
                <label class="mcx-tile {{ $active_module === $systemName ? 'is-selected' : '' }}" style="--tone: {{ $look['tone'] }}" wire:key="system-{{ Str::slug($systemName) }}">
                    <input class="mcx-radio" type="radio" wire:model.live="active_module" value="{{ $systemName }}">
                    <div class="mcx-tile-top">
                        <span class="mcx-tile-ic"><i class="fa {{ $look['icon'] }}"></i></span>
                        <span class="mcx-check"><i class="fa fa-check"></i></span>
                    </div>
                    <div>
                        <div class="mcx-tile-name">{{ $systemName }}</div>
                        @if (! empty($ledes[$systemName]))
                            <div class="mcx-tile-lede">{{ $ledes[$systemName] }}</div>
                        @endif
                    </div>
                    <div class="mcx-tile-foot">
                        <span><i class="fa fa-th-large me-1"></i>{{ count($systemModules) }} modules</span>
                        @if ($saved_module === $systemName)
                            <span class="mcx-current"><i class="fa fa-check-circle"></i>Current</span>
                        @endif
                    </div>
                </label>
            @endforeach
        </div>

        <div class="mcx-panel">
            <div class="mcx-panel-head">
                <div class="mcx-panel-title">
                    <i class="fa fa-th-large text-primary"></i>
                    {{ $active_module ? 'Included in '.$active_module : 'Included modules' }}
                    @if ($active_module)
                        <span class="mcx-count">{{ count($selectedModules) }}</span>
                    @endif
                </div>
                @if ($isChanged)
                    <div class="mcx-legend">
                        <span class="is-added"><i class="fa fa-plus-circle me-1"></i>{{ count($addedModules) }} added</span>
                        <span class="is-removed"><i class="fa fa-minus-circle me-1"></i>{{ count($removedModules) }} removed</span>
                    </div>
                @endif
            </div>

            @if ($active_module)
                <div class="mcx-chips">
                    @foreach ($selectedModules as $moduleKey)
                        @if (in_array($moduleKey, $addedModules, true))
                            <span class="mcx-chip is-added"><i class="fa fa-plus"></i>{{ $labelFor($moduleKey) }}</span>
                        @else
                            <span class="mcx-chip"><i class="fa fa-check"></i>{{ $labelFor($moduleKey) }}</span>
                        @endif
                    @endforeach
                    @foreach ($removedModules as $moduleKey)
                        <span class="mcx-chip is-removed"><i class="fa fa-minus"></i>{{ $labelFor($moduleKey) }}</span>
                    @endforeach
                </div>
            @else
                <div class="mcx-empty">
                    <i class="fa fa-hand-o-up"></i>
                    Choose a system above to see the modules it switches on.
                </div>
            @endif
        </div>

        <div class="mcx-bar">
            @if ($isChanged)
                <div class="mcx-bar-note is-pending">
                    <i class="fa fa-exclamation-circle"></i>
                    Switching from {{ $saved_module }} changes what every role can see.
                </div>
            @else
                <div class="mcx-bar-note">
                    <i class="fa fa-info-circle"></i>
                    Takes effect on the next page load for every user.
                </div>
            @endif
            <div class="mcx-actions">
                @if ($isChanged)
                    <button type="button" class="btn btn-light border" wire:click="$set('active_module', @js($saved_module))">
                        <i class="fa fa-undo me-1"></i>Revert
                    </button>
                @endif
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save" @disabled(! $active_module || $active_module === $saved_module)>
                    <span wire:loading.remove wire:target="save"><i class="fa fa-save me-1"></i>Save Changes</span>
                    <span wire:loading wire:target="save"><i class="fa fa-spinner fa-spin me-1"></i>Saving…</span>
                </button>
            </div>
        </div>
    </form>
</div>

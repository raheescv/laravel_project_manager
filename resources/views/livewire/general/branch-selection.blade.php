<div class="bsx">
    <style>
        /* ── Branch Selection — premium click-and-go switcher ─────────────────
           Scoped under .bsx. All colour derives from the active settings theme
           (--bs-primary / --bs-* tokens) so it tracks the chosen scheme AND
           flips correctly in dark mode (data-bs-theme="dark"). */
        .bsx {
            --b: var(--bs-primary);
            --b-rgb: var(--bs-primary-rgb);
            --b-700: color-mix(in srgb, var(--bs-primary), #000 26%);
            --b-300: color-mix(in srgb, var(--bs-primary), #fff 16%);
            --surface: var(--bs-body-bg);
            --border: var(--bs-border-color);
            --text: var(--bs-emphasis-color);
            --text-2: var(--bs-secondary-color);
            --r-lg: 16px;
            --ease: cubic-bezier(.22, 1, .36, 1);
            --glow: 0 12px 34px -12px rgba(var(--b-rgb), .42), 0 4px 12px -6px rgba(16, 24, 40, .18);
            color: var(--text);
            -webkit-font-smoothing: antialiased;
            letter-spacing: -.006em;
        }

        /* ── Header ── */
        .bsx-head {
            position: relative;
            isolation: isolate;
            overflow: hidden;
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 22px 24px;
            color: #fff;
            background:
                radial-gradient(120% 150% at 0% 0%, rgba(255, 255, 255, .20), transparent 55%),
                linear-gradient(120deg, var(--b-700) 0%, var(--b) 70%, var(--b-300) 130%);
        }

        .bsx-head::after {
            content: "";
            position: absolute;
            z-index: -1;
            right: -40px;
            top: -60px;
            width: 180px;
            height: 180px;
            border-radius: 50%;
            filter: blur(26px);
            background: rgba(255, 255, 255, .22);
        }

        .bsx-head-ic {
            width: 46px;
            height: 46px;
            border-radius: 13px;
            flex: 0 0 auto;
            display: grid;
            place-items: center;
            font-size: 20px;
            background: rgba(255, 255, 255, .16);
            border: 1px solid rgba(255, 255, 255, .28);
            backdrop-filter: blur(6px);
        }

        .bsx-title {
            font-size: 17px;
            font-weight: 700;
            line-height: 1.2;
        }

        .bsx-sub {
            font-size: 12px;
            opacity: .85;
            margin-top: 3px;
        }

        .bsx-x {
            margin-left: auto;
            width: 34px;
            height: 34px;
            flex: 0 0 auto;
            border-radius: 10px;
            display: grid;
            place-items: center;
            cursor: pointer;
            color: #fff;
            background: rgba(255, 255, 255, .14);
            border: 1px solid rgba(255, 255, 255, .28);
            transition: background .15s ease, transform .15s ease;
        }

        .bsx-x:hover {
            background: rgba(255, 255, 255, .28);
            transform: rotate(90deg);
        }

        /* ── Body ── */
        .bsx-body {
            padding: 18px 20px 22px;
            background: var(--surface);
        }

        .bsx-overline {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--text-2);
            margin: 2px 4px 12px;
        }

        .bsx-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-height: 54vh;
            overflow: auto;
            padding: 2px;
        }

        /* ── Branch row (click-and-go) ── */
        .bsx-item {
            display: flex;
            align-items: center;
            gap: 14px;
            width: 100%;
            text-align: left;
            padding: 13px 15px;
            border-radius: var(--r-lg);
            border: 1px solid var(--border);
            background: var(--surface);
            color: inherit;
            cursor: pointer;
            transition: transform .18s var(--ease), box-shadow .18s var(--ease),
                border-color .18s var(--ease), background .18s var(--ease);
        }

        .bsx-item:hover {
            transform: translateY(-2px);
            border-color: color-mix(in srgb, var(--b), transparent 35%);
            box-shadow: 0 10px 26px -12px rgba(var(--b-rgb), .45);
        }

        .bsx-item:focus-visible {
            outline: none;
            box-shadow: 0 0 0 3px rgba(var(--b-rgb), .30);
        }

        .bsx-item.is-active {
            border-color: var(--b);
            background: color-mix(in srgb, var(--b), transparent 92%);
            box-shadow: inset 0 0 0 1px rgba(var(--b-rgb), .35);
        }

        .bsx-item:disabled {
            cursor: default;
        }

        .bsx-av {
            width: 46px;
            height: 46px;
            border-radius: 13px;
            flex: 0 0 auto;
            display: grid;
            place-items: center;
            font-weight: 800;
            font-size: 17px;
            color: #fff;
            background: linear-gradient(135deg, var(--b) 0%, var(--b-300) 100%);
            box-shadow: var(--glow);
        }

        .bsx-info {
            min-width: 0;
            flex: 1 1 auto;
        }

        .bsx-name {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 15px;
            font-weight: 700;
            color: var(--text);
            line-height: 1.25;
        }

        .bsx-meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 6px;
            margin-top: 6px;
        }

        .bsx-code {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11.5px;
            font-weight: 700;
            color: var(--b-700);
            background: color-mix(in srgb, var(--b), transparent 88%);
            padding: 3px 9px;
            border-radius: 999px;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        }

        .bsx-loc {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11.5px;
            color: var(--text-2);
        }

        .bsx-current {
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .07em;
            text-transform: uppercase;
            color: var(--b-700);
            background: color-mix(in srgb, var(--b), transparent 86%);
            border: 1px solid color-mix(in srgb, var(--b), transparent 70%);
            padding: 2px 7px;
            border-radius: 999px;
        }

        /* ── Trailing state: chevron / check / spinner ── */
        .bsx-state {
            flex: 0 0 auto;
            display: grid;
            place-items: center;
            min-width: 30px;
        }

        .bsx-arrow {
            font-size: 18px;
            color: var(--text-2);
            transition: transform .18s var(--ease), color .18s ease;
        }

        .bsx-item:hover .bsx-arrow {
            transform: translateX(3px);
            color: var(--b);
        }

        .bsx-check {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: var(--b);
            color: #fff;
            font-size: 13px;
            box-shadow: 0 4px 12px -3px rgba(var(--b-rgb), .6);
        }

        .bsx-spin {
            color: var(--b);
            font-size: 17px;
        }

        /* ── Empty + error ── */
        .bsx-empty {
            text-align: center;
            padding: 36px 16px;
            color: var(--text-2);
        }

        .bsx-empty i {
            display: block;
            font-size: 34px;
            opacity: .45;
            margin-bottom: 10px;
        }

        .bsx-alert {
            display: flex;
            gap: 9px;
            align-items: flex-start;
            background: var(--bs-danger-bg-subtle);
            color: var(--bs-danger-text-emphasis);
            border: 1px solid var(--bs-danger-border-subtle);
            border-radius: 12px;
            padding: 10px 13px;
            font-size: 13px;
            margin-bottom: 14px;
        }

        @media (prefers-reduced-motion: reduce) {
            .bsx * {
                transition: none !important;
            }

            .bsx-item:hover {
                transform: none;
            }

            .bsx-x:hover {
                transform: none;
            }
        }
    </style>

    <div class="bsx-head">
        <div class="bsx-head-ic"><i class="fa fa-building"></i></div>
        <div>
            <div class="bsx-title">Branch Selection</div>
            <div class="bsx-sub">Tap a branch to switch &mdash; applies instantly</div>
        </div>
        <button type="button" class="bsx-x" data-bs-dismiss="modal" aria-label="Close">
            <i class="fa fa-times"></i>
        </button>
    </div>

    <div class="bsx-body">
        @if ($this->getErrorBag()->count())
            <div class="bsx-alert" role="alert">
                <i class="fa fa-exclamation-triangle mt-1"></i>
                <div>
                    @foreach ($this->getErrorBag()->toArray() as $value)
                        <div>{{ $value[0] }}</div>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($assigned_branches->isEmpty())
            <div class="bsx-empty">
                <i class="fa fa-building-o"></i>
                No branches are assigned to your account.
            </div>
        @else
            <div class="bsx-overline">Available branches</div>
            <div class="bsx-list">
                @foreach ($assigned_branches as $branch)
                    @php $active = $branch_id == $branch->id; @endphp
                    <button type="button" wire:key="branch-{{ $branch->id }}" wire:click="select({{ $branch->id }})" wire:loading.attr="disabled"
                        wire:target="select({{ $branch->id }})" class="bsx-item {{ $active ? 'is-active' : '' }}">
                        <span class="bsx-av">{{ strtoupper(mb_substr($branch->name, 0, 1)) }}</span>
                        <span class="bsx-info">
                            <span class="bsx-name">
                                {{ $branch->name }}
                                @if ($active)
                                    <span class="bsx-current">Current</span>
                                @endif
                            </span>
                            <span class="bsx-meta">
                                <span class="bsx-code"><i class="fa fa-code"></i>{{ $branch->code }}</span>
                                @if ($branch->location)
                                    <span class="bsx-loc"><i class="fa fa-map-marker"></i>{{ $branch->location }}</span>
                                @endif
                            </span>
                        </span>
                        <span class="bsx-state">
                            <span wire:loading.remove wire:target="select({{ $branch->id }})">
                                @if ($active)
                                    <span class="bsx-check"><i class="fa fa-check"></i></span>
                                @else
                                    <i class="fa fa-angle-right bsx-arrow"></i>
                                @endif
                            </span>
                            <span class="bsx-spin" wire:loading wire:target="select({{ $branch->id }})">
                                <i class="fa fa-spinner fa-spin"></i>
                            </span>
                        </span>
                    </button>
                @endforeach
            </div>
        @endif
    </div>
</div>

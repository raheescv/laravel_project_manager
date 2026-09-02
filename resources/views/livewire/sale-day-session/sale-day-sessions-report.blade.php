<div>
    @php
        $canViewTailoring = auth()->user()->can('tailoring order.view');
        $canViewSession = auth()->user()->can('day session.view');
        $colspan = 9 + ($canViewTailoring ? 1 : 0);
        $initialsOf = fn (string $name) => collect(preg_split('/\s+/', trim($name)))->filter()->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('');
        $diff = (float) $summary['total_difference'];
        $diffTone = abs($diff) < 0.005 ? 'ok' : ($diff < 0 ? 'short' : 'over');
    @endphp

    <style>
        /* =========================================================
           Day Sessions Report — premium compact ("dsr")
           Sibling of the Day Session Manager ("dsx") — same tokens.
           Accent follows the settings theme colour (--bs-primary);
           dark mode follows Bootstrap [data-bs-theme="dark"].
           ========================================================= */
        .dsr {
            --dsr-accent: var(--bs-primary, #3457d5);
            --dsr-accent-rgb: var(--bs-primary-rgb, 52, 87, 213);
            --dsr-bright: color-mix(in srgb, var(--dsr-accent), #fff 24%);
            --dsr-deep: color-mix(in srgb, var(--dsr-accent), #000 34%);
            --dsr-ink: #111826;
            --dsr-ink-soft: #2c3648;
            --dsr-muted: #64708a;
            --dsr-muted-2: #93a0b8;
            --dsr-line: #e2e8f2;
            --dsr-line-soft: #eef2f8;
            --dsr-card: #ffffff;
            --dsr-card-2: #f5f8fc;
            --dsr-green: #1f9d63;
            --dsr-green-rgb: 31, 157, 99;
            --dsr-amber: #c98a12;
            --dsr-amber-rgb: 201, 138, 18;
            --dsr-red: #d64545;
            --dsr-red-rgb: 214, 69, 69;
            --dsr-mono: ui-monospace, 'SF Mono', 'Cascadia Code', Menlo, monospace;
            --dsr-ease: cubic-bezier(0.22, 1, 0.36, 1);
            --dsr-shadow-sm: 0 1px 2px rgba(17, 24, 38, 0.04), 0 4px 14px -8px rgba(17, 24, 38, 0.10);
            --dsr-shadow-md: 0 8px 30px -14px rgba(17, 24, 38, 0.22);
            --dsr-fz: 12.5px;
            color: var(--dsr-ink);
            font-size: var(--dsr-fz);
            display: grid;
            gap: 10px;
        }

        [data-bs-theme="dark"] .dsr {
            color-scheme: dark;
            --dsr-ink: #eaf0fb;
            --dsr-ink-soft: #c2cbdd;
            --dsr-muted: #8b96ad;
            --dsr-muted-2: #626d84;
            --dsr-line: rgba(255, 255, 255, 0.10);
            --dsr-line-soft: rgba(255, 255, 255, 0.06);
            --dsr-card: #161b28;
            --dsr-card-2: #1b2130;
            --dsr-bright: color-mix(in srgb, var(--dsr-accent), #fff 30%);
            --dsr-deep: color-mix(in srgb, var(--dsr-accent), #fff 8%);
            --dsr-green: #43c98a;
            --dsr-green-rgb: 67, 201, 138;
            --dsr-amber: #e0ab4a;
            --dsr-amber-rgb: 224, 171, 74;
            --dsr-red: #f16b6b;
            --dsr-red-rgb: 241, 107, 107;
            --dsr-shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.4), 0 4px 14px -8px rgba(0, 0, 0, 0.6);
            --dsr-shadow-md: 0 10px 34px -14px rgba(0, 0, 0, 0.7);
        }

        .dsr * { box-sizing: border-box; }
        .dsr a { text-decoration: none; }
        .dsr button { font: inherit; }
        .dsr .mono { font-family: var(--dsr-mono); }
        .dsr .muted { color: var(--dsr-muted); }

        /* ── Filter bar ─────────────────────────────────────────── */
        .dsr .bar {
            display: flex; align-items: center; flex-wrap: wrap; gap: 8px;
            padding: 9px 12px; border-radius: 13px;
            background: var(--dsr-card); border: 1px solid var(--dsr-line); box-shadow: var(--dsr-shadow-sm);
        }
        .dsr .bar__lead { display: inline-flex; align-items: center; gap: 7px; font-weight: 700; font-size: 12px; color: var(--dsr-ink-soft); padding-inline-end: 4px; }
        .dsr .bar__lead i { color: var(--dsr-accent); }
        .dsr .bar__end { display: inline-flex; align-items: center; gap: 8px; }
        .dsr .bar__busy { color: var(--dsr-accent); font-size: 12px; }

        .dsr .seg { display: inline-flex; align-items: center; gap: 2px; padding: 3px; border-radius: 9px; background: var(--dsr-card-2); border: 1px solid var(--dsr-line); }
        .dsr .seg__btn {
            border: none; background: none; cursor: pointer; white-space: nowrap;
            padding: 4px 9px; border-radius: 7px; font-size: 11.5px; font-weight: 600; line-height: 1.3;
            color: var(--dsr-muted); display: inline-flex; align-items: center; gap: 6px;
            transition: color 0.15s, background 0.15s, box-shadow 0.15s;
        }
        .dsr .seg__btn:hover { color: var(--dsr-ink); }
        .dsr .seg__btn.is-on { background: var(--dsr-card); color: var(--dsr-accent); box-shadow: var(--dsr-shadow-sm); }
        .dsr .seg__btn.is-on.tone-green { color: var(--dsr-green); }
        [data-bs-theme="dark"] .dsr .seg__btn.is-on { background: rgba(var(--dsr-accent-rgb), 0.18); box-shadow: none; }
        [data-bs-theme="dark"] .dsr .seg__btn.is-on.tone-green { background: rgba(var(--dsr-green-rgb), 0.16); }
        .dsr .seg__btn .dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; opacity: 0.85; }

        .dsr .range {
            display: inline-flex; align-items: center; gap: 6px; height: 32px; padding: 0 10px;
            border: 1px solid var(--dsr-line); border-radius: 9px; background: var(--dsr-card-2);
            transition: border-color 0.18s, box-shadow 0.18s;
        }
        .dsr .range:focus-within { border-color: var(--dsr-accent); box-shadow: 0 0 0 3px rgba(var(--dsr-accent-rgb), 0.14); }
        .dsr .range > i { color: var(--dsr-accent); font-size: 12px; }
        .dsr .range input[type="date"] {
            border: none; background: none; outline: none; padding: 0; margin: 0; width: 122px;
            font: inherit; font-size: 12px; font-weight: 600; color: var(--dsr-ink);
        }
        .dsr .range input[type="date"]::-webkit-calendar-picker-indicator { opacity: 0.55; cursor: pointer; }
        .dsr .range__to { font-family: var(--dsr-mono); font-size: 9px; letter-spacing: 0.1em; text-transform: uppercase; color: var(--dsr-muted-2); }
        .dsr .range.is-custom { border-color: rgba(var(--dsr-accent-rgb), 0.45); }

        .dsr .ctl-wrap { position: relative; display: inline-flex; align-items: center; }
        .dsr .ctl-wrap > i.lead-ic { position: absolute; inset-inline-start: 10px; color: var(--dsr-accent); font-size: 12px; pointer-events: none; }
        .dsr .ctl-wrap > i.tail-ic { position: absolute; inset-inline-end: 9px; color: var(--dsr-muted); font-size: 12px; pointer-events: none; }
        .dsr select.ctl {
            appearance: none; -webkit-appearance: none; height: 32px; min-width: 150px; max-width: 220px;
            padding: 0 26px 0 28px; border: 1px solid var(--dsr-line); border-radius: 9px;
            background: var(--dsr-card-2); color: var(--dsr-ink); font: inherit; font-size: 12px; font-weight: 600;
            outline: none; cursor: pointer; text-overflow: ellipsis;
            transition: border-color 0.18s, box-shadow 0.18s;
        }
        .dsr select.ctl.ctl--plain { padding-inline-start: 10px; min-width: 0; }
        .dsr select.ctl.ctl--xs { height: 28px; font-size: 11.5px; border-radius: 8px; }
        .dsr select.ctl:focus { border-color: var(--dsr-accent); box-shadow: 0 0 0 3px rgba(var(--dsr-accent-rgb), 0.14); }
        [dir="rtl"] .dsr select.ctl { padding: 0 28px 0 26px; }

        .dsr .reset {
            display: inline-flex; align-items: center; gap: 6px; height: 32px; padding: 0 11px; cursor: pointer;
            border: 1px dashed var(--dsr-line); border-radius: 9px; background: transparent;
            color: var(--dsr-muted); font-size: 11.5px; font-weight: 600; transition: all 0.15s var(--dsr-ease);
        }
        .dsr .reset:hover { color: var(--dsr-red); border-color: var(--dsr-red); background: rgba(var(--dsr-red-rgb), 0.06); }

        /* ── KPI strip ──────────────────────────────────────────── */
        .dsr .kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
        .dsr .kpi {
            position: relative; overflow: hidden; padding: 10px 12px 9px 14px; border-radius: 12px;
            background: var(--dsr-card); border: 1px solid var(--dsr-line); box-shadow: var(--dsr-shadow-sm);
            transition: transform 0.25s var(--dsr-ease), box-shadow 0.25s var(--dsr-ease);
        }
        .dsr .kpi:hover { transform: translateY(-2px); box-shadow: var(--dsr-shadow-md); }
        .dsr .kpi__rail { position: absolute; inset-inline-start: 0; top: 0; bottom: 0; width: 3px; }
        .dsr .kpi__top { display: flex; align-items: center; gap: 8px; }
        .dsr .kpi__ic { width: 24px; height: 24px; border-radius: 7px; display: grid; place-items: center; font-size: 11px; flex: none; }
        .dsr .kpi__lbl { font-family: var(--dsr-mono); font-size: 9px; letter-spacing: 0.09em; text-transform: uppercase; color: var(--dsr-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .dsr .kpi__val { font-size: clamp(16px, 1.7vw, 20px); font-weight: 700; letter-spacing: -0.02em; margin-top: 5px; line-height: 1.1; color: var(--dsr-ink); display: flex; align-items: center; gap: 6px; }
        .dsr .kpi__val i { font-size: 12px; }
        .dsr .kpi__foot { margin-top: 5px; font-size: 10.5px; color: var(--dsr-muted); display: flex; align-items: center; gap: 5px; flex-wrap: wrap; }
        .dsr .kpi__foot b { color: var(--dsr-ink-soft); font-weight: 600; }
        .dsr .kpi__foot .sep { color: var(--dsr-muted-2); }
        .dsr .kpi__foot .dot { width: 6px; height: 6px; border-radius: 50%; display: inline-block; }
        .dsr .i-green { color: var(--dsr-green); background: rgba(var(--dsr-green-rgb), 0.12); }
        .dsr .i-accent { color: var(--dsr-accent); background: rgba(var(--dsr-accent-rgb), 0.12); }
        .dsr .i-amber { color: var(--dsr-amber); background: rgba(var(--dsr-amber-rgb), 0.12); }
        .dsr .i-red { color: var(--dsr-red); background: rgba(var(--dsr-red-rgb), 0.12); }
        .dsr .i-deep { color: var(--dsr-deep); background: rgba(var(--dsr-accent-rgb), 0.14); }
        .dsr .i-muted { color: var(--dsr-muted); background: var(--dsr-card-2); }
        .dsr .r-green { background: var(--dsr-green); } .dsr .r-accent { background: var(--dsr-accent); }
        .dsr .r-amber { background: var(--dsr-amber); } .dsr .r-red { background: var(--dsr-red); }
        .dsr .r-deep { background: var(--dsr-deep); } .dsr .r-muted { background: var(--dsr-muted-2); }
        .dsr .t-green { color: var(--dsr-green); } .dsr .t-red { color: var(--dsr-red); } .dsr .t-amber { color: var(--dsr-amber); }

        /* ── Table panel ────────────────────────────────────────── */
        .dsr .panel { background: var(--dsr-card); border: 1px solid var(--dsr-line); border-radius: 13px; box-shadow: var(--dsr-shadow-sm); overflow: hidden; }
        .dsr .panel__head { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 9px 14px; border-bottom: 1px solid var(--dsr-line-soft); flex-wrap: wrap; }
        .dsr .panel__head h3 { font-size: 13.5px; font-weight: 700; display: flex; align-items: center; gap: 8px; margin: 0; color: var(--dsr-ink); }
        .dsr .panel__head h3 i { color: var(--dsr-accent); font-size: 12.5px; }
        .dsr .panel__meta { font-size: 11.5px; color: var(--dsr-muted); white-space: nowrap; }
        .dsr .panel__tools { display: inline-flex; align-items: center; gap: 10px; margin-inline-start: auto; }
        .dsr .panel__foot { display: flex; align-items: center; justify-content: flex-end; padding: 8px 12px; border-top: 1px solid var(--dsr-line-soft); }
        .dsr .panel__foot nav { margin: 0; }
        .dsr .pagination { margin: 0; --bs-pagination-padding-y: 0.22rem; --bs-pagination-padding-x: 0.6rem; --bs-pagination-font-size: 11.5px; --bs-pagination-border-radius: 7px; }
        .dsr .panel.is-busy .tbl-wrap { opacity: 0.5; transition: opacity 0.2s; pointer-events: none; }

        .dsr .cnt { font-family: var(--dsr-mono); font-size: 10px; letter-spacing: 0.06em; padding: 3px 8px; border-radius: 99px; color: var(--dsr-accent); background: rgba(var(--dsr-accent-rgb), 0.10); border: 1px solid rgba(var(--dsr-accent-rgb), 0.24); }

        .dsr .tbl-wrap { overflow-x: auto; }
        .dsr table.sx { width: 100%; border-collapse: collapse; min-width: 940px; margin: 0; }
        .dsr table.sx thead th {
            text-align: start; font-family: var(--dsr-mono); font-size: 9px; letter-spacing: 0.1em; text-transform: uppercase; font-weight: 600;
            color: var(--dsr-muted); padding: 7px 12px; border-bottom: 1px solid var(--dsr-line); background: var(--dsr-card-2); white-space: nowrap;
        }
        .dsr table.sx tbody td { padding: 6px 12px; border-bottom: 1px solid var(--dsr-line-soft); vertical-align: middle; color: var(--dsr-ink); font-size: 12.5px; line-height: 1.3; }
        .dsr table.sx tbody tr { transition: background 0.15s; }
        .dsr table.sx tbody tr:hover { background: var(--dsr-card-2); }
        .dsr table.sx tbody tr:last-child td { border-bottom: none; }
        .dsr table.sx .num { text-align: end; }
        .dsr table.sx .act { width: 44px; text-align: end; }
        .dsr table.sx .w-id { width: 56px; }

        .dsr .th-sort { border: none; background: none; padding: 0; cursor: pointer; color: inherit; font: inherit; letter-spacing: inherit; text-transform: inherit; display: inline-flex; align-items: center; gap: 5px; }
        .dsr .th-sort:hover { color: var(--dsr-ink); }
        .dsr .th-sort i { font-size: 10px; opacity: 0.45; }
        .dsr .th-sort.is-on { color: var(--dsr-accent); }
        .dsr .th-sort.is-on i { opacity: 1; }

        .dsr .id { font-family: var(--dsr-mono); font-size: 11px; color: var(--dsr-muted); }
        .dsr .br { display: inline-flex; align-items: center; gap: 8px; font-weight: 600; }
        .dsr .br__ic { width: 24px; height: 24px; border-radius: 7px; display: grid; place-items: center; background: rgba(var(--dsr-accent-rgb), 0.12); color: var(--dsr-accent); font-size: 11px; flex: none; }
        .dsr .dt { display: flex; flex-direction: column; line-height: 1.2; }
        .dsr .dt b { font-weight: 600; }
        .dsr .dt small { color: var(--dsr-muted); font-size: 10.5px; font-family: var(--dsr-mono); }
        .dsr .who { display: inline-flex; align-items: center; gap: 7px; }
        .dsr .who .av { width: 22px; height: 22px; border-radius: 50%; display: grid; place-items: center; font-size: 9px; font-weight: 700; color: #fff; background: linear-gradient(135deg, var(--dsr-bright), var(--dsr-deep)); text-transform: uppercase; flex: none; }
        .dsr .who--closer .av { background: var(--dsr-card-2); color: var(--dsr-ink-soft); border: 1px solid var(--dsr-line); }
        .dsr .pill { display: inline-flex; align-items: center; gap: 6px; font-family: var(--dsr-mono); font-size: 9.5px; letter-spacing: 0.08em; text-transform: uppercase; padding: 3px 8px; border-radius: 99px; border: 1px solid var(--dsr-line); line-height: 1.2; white-space: nowrap; }
        .dsr .pill--green { color: var(--dsr-green); background: rgba(var(--dsr-green-rgb), 0.10); border-color: rgba(var(--dsr-green-rgb), 0.28); }
        .dsr .pill--muted { color: var(--dsr-muted); background: var(--dsr-card-2); }
        .dsr .pill i { font-size: 9px; }
        .dsr .pulse { width: 6px; height: 6px; border-radius: 50%; background: currentColor; animation: dsrpulse 2s infinite; }
        @keyframes dsrpulse { 0% { box-shadow: 0 0 0 0 rgba(var(--dsr-green-rgb), 0.5); } 70% { box-shadow: 0 0 0 6px rgba(var(--dsr-green-rgb), 0); } 100% { box-shadow: 0 0 0 0 rgba(var(--dsr-green-rgb), 0); } }
        .dsr .muted-line { color: var(--dsr-muted); font-size: 10.5px; margin-top: 2px; font-family: var(--dsr-mono); }
        .dsr .money { display: inline-flex; align-items: baseline; gap: 7px; justify-content: flex-end; }
        .dsr .money .n { font-family: var(--dsr-mono); font-size: 10px; color: var(--dsr-muted); background: var(--dsr-card-2); border: 1px solid var(--dsr-line); padding: 1px 6px; border-radius: 6px; }
        .dsr .money .a { font-weight: 700; font-variant-numeric: tabular-nums; }
        .dsr .money.is-zero .a { color: var(--dsr-muted-2); font-weight: 500; }
        .dsr .diff { display: inline-flex; align-items: center; gap: 4px; font-weight: 700; font-variant-numeric: tabular-nums; }
        .dsr .diff i { font-size: 10px; }
        .dsr .diff.is-ok { color: var(--dsr-muted); font-weight: 500; }
        .dsr .diff.is-short { color: var(--dsr-red); }
        .dsr .diff.is-over { color: var(--dsr-green); }
        .dsr .icon-btn { width: 28px; height: 28px; border-radius: 8px; display: inline-grid; place-items: center; border: 1px solid var(--dsr-line); background: var(--dsr-card); color: var(--dsr-ink-soft); font-size: 12px; transition: all 0.15s var(--dsr-ease); }
        .dsr .icon-btn:hover { border-color: var(--dsr-accent); color: var(--dsr-accent); transform: translateY(-1px); }

        .dsr .empty { text-align: center; padding: 28px 16px; color: var(--dsr-muted); }
        .dsr .empty__ring { width: 44px; height: 44px; border-radius: 13px; margin: 0 auto 10px; display: grid; place-items: center; font-size: 18px; color: var(--dsr-accent); background: rgba(var(--dsr-accent-rgb), 0.10); }
        .dsr .empty h4 { font-size: 14px; font-weight: 700; color: var(--dsr-ink); margin: 0 0 3px; }
        .dsr .empty p { margin: 0; font-size: 12px; }
        .dsr .empty .reset { margin-top: 12px; }

        @media (max-width: 1100px) { .dsr .kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 720px) {
            .dsr .bar { padding: 8px; gap: 6px; }
            .dsr .bar__lead { display: none; }
            .dsr .seg { max-width: 100%; overflow-x: auto; scrollbar-width: none; }
            .dsr .seg::-webkit-scrollbar { display: none; }
            .dsr .range { flex: 1 1 100%; justify-content: space-between; }
            .dsr .bar .ctl-wrap { flex: 1 1 100%; }
            .dsr .bar select.ctl { width: 100%; max-width: none; }
            .dsr .panel__tools { margin-inline-start: 0; width: 100%; justify-content: space-between; }
        }
        @media (max-width: 480px) {
            .dsr .kpis { gap: 8px; }
            .dsr .kpi { padding: 9px 10px 8px 12px; }
            .dsr .kpi__val { font-size: 16px; }
            .dsr .kpi__foot { font-size: 10px; }
        }
    </style>

    <div class="dsr">
        {{-- ── Filter bar ─────────────────────────────────────── --}}
        <section class="bar" aria-label="Report filters">
            <span class="bar__lead"><i class="fa fa-filter"></i> Filters</span>

            <div class="seg" role="group" aria-label="Quick date range">
                @foreach ($presets as $key => $label)
                    <button type="button" class="seg__btn {{ $preset === $key ? 'is-on' : '' }}" wire:click="setRange('{{ $key }}')">{{ $label }}</button>
                @endforeach
            </div>

            <div class="range {{ $preset === 'custom' ? 'is-custom' : '' }}" title="Opened between">
                <i class="fa fa-calendar-o"></i>
                <input type="date" wire:model.live="dateFrom" aria-label="From date" max="{{ $dateTo }}">
                <span class="range__to">to</span>
                <input type="date" wire:model.live="dateTo" aria-label="To date" min="{{ $dateFrom }}">
            </div>

            <label class="ctl-wrap" title="Branch">
                <i class="fa fa-building-o lead-ic"></i>
                <select wire:model.live="branchId" class="ctl" aria-label="Branch">
                    <option value="">All branches</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
                <i class="fa fa-angle-down tail-ic"></i>
            </label>

            <div class="seg" role="group" aria-label="Session status">
                @foreach ($statuses as $key => $label)
                    <button type="button" class="seg__btn {{ $status === $key ? 'is-on' : '' }} {{ $key === 'open' ? 'tone-green' : '' }}" wire:click="setStatus('{{ $key }}')">
                        @if ($key === 'open')
                            <span class="dot"></span>
                        @endif
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <div class="bar__end">
                @if ($hasActiveFilters)
                    <button type="button" class="reset" wire:click="resetFilters" title="Back to all branches, all statuses, last 30 days">
                        <i class="fa fa-times"></i> Reset
                    </button>
                @endif
                <span class="bar__busy" wire:loading><i class="fa fa-refresh fa-spin"></i></span>
            </div>
        </section>

        {{-- ── KPI strip ──────────────────────────────────────── --}}
        <section class="kpis" aria-label="Summary">
            <div class="kpi">
                <span class="kpi__rail r-accent"></span>
                <div class="kpi__top"><span class="kpi__ic i-accent"><i class="fa fa-calendar-o"></i></span><span class="kpi__lbl">Sessions</span></div>
                <div class="kpi__val">{{ number_format($summary['total_sessions']) }}</div>
                <div class="kpi__foot">
                    <span class="dot r-green"></span><b>{{ number_format($summary['open_sessions']) }}</b> open
                    <span class="sep">·</span>
                    <span class="dot r-muted"></span><b>{{ number_format($summary['closed_sessions']) }}</b> closed
                </div>
            </div>
            <div class="kpi">
                <span class="kpi__rail r-deep"></span>
                <div class="kpi__top"><span class="kpi__ic i-deep"><i class="fa fa-shopping-cart"></i></span><span class="kpi__lbl">Invoices</span></div>
                <div class="kpi__val">{{ number_format($summary['total_invoices']) }}</div>
                <div class="kpi__foot">
                    Sales <b>{{ number_format($summary['total_sales']) }}</b>
                    @if ($canViewTailoring)
                        <span class="sep">·</span> Tailoring <b>{{ number_format($summary['total_tailoring']) }}</b>
                    @endif
                </div>
            </div>
            <div class="kpi">
                <span class="kpi__rail r-amber"></span>
                <div class="kpi__top"><span class="kpi__ic i-amber"><i class="fa fa-money"></i></span><span class="kpi__lbl">Collected</span></div>
                <div class="kpi__val">{{ currency($summary['total_collection_amount']) }}</div>
                <div class="kpi__foot">
                    Sales <b>{{ currency($summary['total_sales_amount']) }}</b>
                    @if ($canViewTailoring)
                        <span class="sep">·</span> Tailoring <b>{{ currency($summary['total_tailoring_amount']) }}</b>
                    @endif
                </div>
            </div>
            <div class="kpi">
                <span class="kpi__rail {{ $diffTone === 'short' ? 'r-red' : ($diffTone === 'over' ? 'r-green' : 'r-muted') }}"></span>
                <div class="kpi__top">
                    <span class="kpi__ic {{ $diffTone === 'short' ? 'i-red' : ($diffTone === 'over' ? 'i-green' : 'i-muted') }}"><i class="fa fa-calculator"></i></span>
                    <span class="kpi__lbl">Cash difference</span>
                </div>
                <div class="kpi__val {{ $diffTone === 'short' ? 't-red' : ($diffTone === 'over' ? 't-green' : '') }}">
                    @if ($diffTone !== 'ok')
                        <i class="fa fa-arrow-{{ $diffTone === 'over' ? 'up' : 'down' }}"></i>
                    @endif
                    {{ currency($diff) }}
                </div>
                <div class="kpi__foot">
                    @if ($diffTone === 'ok')
                        Drawers balanced across <b>{{ number_format($summary['closed_sessions']) }}</b> closed
                    @else
                        {{ $diffTone === 'short' ? 'Short' : 'Over' }} across <b>{{ number_format($summary['closed_sessions']) }}</b> closed sessions
                    @endif
                </div>
            </div>
        </section>

        {{-- ── Sessions table ─────────────────────────────────── --}}
        <section class="panel" wire:loading.class="is-busy">
            <div class="panel__head">
                <h3><i class="fa fa-table"></i> Sessions <span class="cnt">{{ number_format($sessions->total()) }}</span></h3>
                <div class="panel__tools">
                    @if ($sessions->total())
                        <span class="panel__meta">Showing {{ number_format($sessions->firstItem()) }}–{{ number_format($sessions->lastItem()) }} of {{ number_format($sessions->total()) }}</span>
                    @endif
                    <label class="ctl-wrap" title="Rows per page">
                        <select wire:model.live="perPage" class="ctl ctl--plain ctl--xs" aria-label="Rows per page">
                            @foreach ($perPageOptions as $n)
                                <option value="{{ $n }}">{{ $n }} rows</option>
                            @endforeach
                        </select>
                        <i class="fa fa-angle-down tail-ic"></i>
                    </label>
                </div>
            </div>
            <div class="tbl-wrap">
                <table class="sx">
                    <thead>
                        <tr>
                            <th class="w-id">
                                <button type="button" class="th-sort {{ $sortField === 'id' ? 'is-on' : '' }}" wire:click="sortBy('id')">
                                    # <i class="fa fa-sort{{ $sortField === 'id' ? '-' . $sortDirection : '' }}"></i>
                                </button>
                            </th>
                            <th>Branch</th>
                            <th>
                                <button type="button" class="th-sort {{ $sortField === 'opened_at' ? 'is-on' : '' }}" wire:click="sortBy('opened_at')">
                                    Opened <i class="fa fa-sort{{ $sortField === 'opened_at' ? '-' . $sortDirection : '' }}"></i>
                                </button>
                            </th>
                            <th>Opened by</th>
                            <th>Closed by</th>
                            <th>Status</th>
                            <th class="num">Sales</th>
                            @if ($canViewTailoring)
                                <th class="num">Tailoring</th>
                            @endif
                            <th class="num">
                                <button type="button" class="th-sort {{ $sortField === 'difference_amount' ? 'is-on' : '' }}" wire:click="sortBy('difference_amount')">
                                    Difference <i class="fa fa-sort{{ $sortField === 'difference_amount' ? '-' . $sortDirection : '' }}"></i>
                                </button>
                            </th>
                            <th class="act"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sessions as $session)
                            @php
                                $opener = $session->opener?->name ?? 'Unknown';
                                $closer = $session->status === 'closed' ? ($session->closer?->name ?? 'Unknown') : null;
                                $salesAmt = (float) ($session->sales_sum_paid ?? 0);
                                $tailAmt = (float) ($session->tailoring_orders_sum_paid ?? 0);
                                $rowDiff = (float) $session->difference_amount;
                            @endphp
                            <tr wire:key="dsr-{{ $session->id }}">
                                <td class="id">{{ $session->id }}</td>
                                <td>
                                    <span class="br"><span class="br__ic"><i class="fa fa-building-o"></i></span>{{ $session->branch?->name ?? '—' }}</span>
                                </td>
                                <td>
                                    <div class="dt">
                                        <b>{{ systemDate($session->opened_at) }}</b>
                                        <small>{{ $session->opened_at?->format('h:i A') }}</small>
                                    </div>
                                </td>
                                <td>
                                    <span class="who"><span class="av">{{ $initialsOf($opener) ?: '?' }}</span>{{ $opener }}</span>
                                </td>
                                <td>
                                    @if ($closer !== null)
                                        <span class="who who--closer"><span class="av">{{ $initialsOf($closer) ?: '?' }}</span>{{ $closer }}</span>
                                    @else
                                        <span class="muted" title="Still open">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($session->status == 'open')
                                        <span class="pill pill--green"><span class="pulse"></span>Open</span>
                                    @else
                                        <span class="pill pill--muted"><i class="fa fa-check"></i>Closed</span>
                                        @if ($session->closed_at)
                                            <div class="muted-line">{{ systemDate($session->closed_at) }} {{ $session->closed_at->format('h:i A') }}</div>
                                        @endif
                                    @endif
                                </td>
                                <td class="num">
                                    <span class="money {{ $salesAmt == 0 ? 'is-zero' : '' }}">
                                        <span class="n" title="Completed sales">{{ number_format($session->sales_count) }}</span>
                                        <span class="a">{{ currency($salesAmt) }}</span>
                                    </span>
                                </td>
                                @if ($canViewTailoring)
                                    <td class="num">
                                        <span class="money {{ $tailAmt == 0 ? 'is-zero' : '' }}">
                                            <span class="n" title="Tailoring orders">{{ number_format($session->tailoring_orders_count) }}</span>
                                            <span class="a">{{ currency($tailAmt) }}</span>
                                        </span>
                                    </td>
                                @endif
                                <td class="num">
                                    @if ($session->status == 'closed')
                                        <span class="diff {{ abs($rowDiff) < 0.005 ? 'is-ok' : ($rowDiff < 0 ? 'is-short' : 'is-over') }}">
                                            @if (abs($rowDiff) >= 0.005)
                                                <i class="fa fa-arrow-{{ $rowDiff > 0 ? 'up' : 'down' }}"></i>
                                            @endif
                                            {{ currency($rowDiff) }}
                                        </span>
                                    @else
                                        <span class="muted">—</span>
                                    @endif
                                </td>
                                <td class="act">
                                    @if ($canViewSession)
                                        <a href="{{ route('sale::day-session', $session->id) }}" class="icon-btn" title="View session #{{ $session->id }}"><i class="fa fa-eye"></i></a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $colspan }}">
                                    <div class="empty">
                                        <div class="empty__ring"><i class="fa fa-inbox"></i></div>
                                        <h4>No sessions match these filters</h4>
                                        <p>Try a wider date range, another branch, or clear the status.</p>
                                        @if ($hasActiveFilters)
                                            <button type="button" class="reset" wire:click="resetFilters"><i class="fa fa-times"></i> Reset filters</button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($sessions->hasPages())
                <div class="panel__foot">
                    {{ $sessions->links() }}
                </div>
            @endif
        </section>
    </div>
</div>

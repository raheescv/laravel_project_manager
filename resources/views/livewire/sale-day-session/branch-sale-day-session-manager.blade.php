<div>
    <style>
        /* =========================================================
           Day Session Manager — premium compact ("dsx")
           Accent follows the app settings theme colour (--bs-primary)
           Dark mode follows Bootstrap [data-bs-theme="dark"]
           ========================================================= */
        .dsx {
            --dsx-accent: var(--bs-primary, #3457d5);
            --dsx-accent-rgb: var(--bs-primary-rgb, 52, 87, 213);
            --dsx-bright: color-mix(in srgb, var(--dsx-accent), #fff 24%);
            --dsx-deep: color-mix(in srgb, var(--dsx-accent), #000 34%);
            --dsx-ink: #111826;
            --dsx-ink-soft: #2c3648;
            --dsx-muted: #64708a;
            --dsx-muted-2: #93a0b8;
            --dsx-line: #e2e8f2;
            --dsx-line-soft: #eef2f8;
            --dsx-card: #ffffff;
            --dsx-card-2: #f5f8fc;
            --dsx-green: #1f9d63;
            --dsx-green-rgb: 31, 157, 99;
            --dsx-amber: #c98a12;
            --dsx-amber-rgb: 201, 138, 18;
            --dsx-red: #d64545;
            --dsx-red-rgb: 214, 69, 69;
            --dsx-mono: ui-monospace, 'SF Mono', 'Cascadia Code', Menlo, monospace;
            --dsx-ease: cubic-bezier(0.22, 1, 0.36, 1);
            --dsx-shadow-sm: 0 1px 2px rgba(17, 24, 38, 0.04), 0 4px 14px -8px rgba(17, 24, 38, 0.10);
            --dsx-shadow-md: 0 8px 30px -14px rgba(17, 24, 38, 0.22);
            color: var(--dsx-ink);
        }

        [data-bs-theme="dark"] .dsx {
            --dsx-ink: #eaf0fb;
            --dsx-ink-soft: #c2cbdd;
            --dsx-muted: #8b96ad;
            --dsx-muted-2: #626d84;
            --dsx-line: rgba(255, 255, 255, 0.10);
            --dsx-line-soft: rgba(255, 255, 255, 0.06);
            --dsx-card: #161b28;
            --dsx-card-2: #1b2130;
            --dsx-bright: color-mix(in srgb, var(--dsx-accent), #fff 30%);
            --dsx-deep: color-mix(in srgb, var(--dsx-accent), #fff 8%);
            --dsx-green: #43c98a;
            --dsx-amber: #e0ab4a;
            --dsx-red: #f16b6b;
            --dsx-shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.4), 0 4px 14px -8px rgba(0, 0, 0, 0.6);
            --dsx-shadow-md: 0 10px 34px -14px rgba(0, 0, 0, 0.7);
        }

        .dsx * { box-sizing: border-box; }
        .dsx a { text-decoration: none; }

        /* pills */
        .dsx .pill {
            display: inline-flex; align-items: center; gap: 6px;
            font-family: var(--dsx-mono); font-size: 10px; letter-spacing: 0.1em; text-transform: uppercase;
            padding: 4px 10px; border-radius: 99px; border: 1px solid var(--dsx-line); line-height: 1;
        }
        .dsx .pill--green { color: var(--dsx-green); background: rgba(var(--dsx-green-rgb), 0.10); border-color: rgba(var(--dsx-green-rgb), 0.28); }
        .dsx .pill--amber { color: var(--dsx-amber); background: rgba(var(--dsx-amber-rgb), 0.10); border-color: rgba(var(--dsx-amber-rgb), 0.28); }
        .dsx .pill--accent { color: var(--dsx-accent); background: rgba(var(--dsx-accent-rgb), 0.10); border-color: rgba(var(--dsx-accent-rgb), 0.28); }
        .dsx .pill--red { color: var(--dsx-red); background: rgba(var(--dsx-red-rgb), 0.10); border-color: rgba(var(--dsx-red-rgb), 0.28); }
        .dsx .pulse { width: 7px; height: 7px; border-radius: 50%; background: currentColor; animation: dsxpulse 2s infinite; }
        @keyframes dsxpulse { 0% { box-shadow: 0 0 0 0 rgba(var(--dsx-green-rgb), 0.5); } 70% { box-shadow: 0 0 0 7px rgba(var(--dsx-green-rgb), 0); } 100% { box-shadow: 0 0 0 0 rgba(var(--dsx-green-rgb), 0); } }

        /* alerts */
        .dsx .dsx-alert { display: flex; align-items: center; gap: 10px; padding: 11px 14px; border-radius: 11px; font-size: 13px; font-weight: 500; margin-bottom: 14px; border: 1px solid; }
        .dsx .dsx-alert.ok { color: var(--dsx-green); background: rgba(var(--dsx-green-rgb), 0.10); border-color: rgba(var(--dsx-green-rgb), 0.24); }
        .dsx .dsx-alert.err { color: var(--dsx-red); background: rgba(var(--dsx-red-rgb), 0.10); border-color: rgba(var(--dsx-red-rgb), 0.24); }

        /* hero */
        .dsx .hero {
            position: relative; overflow: hidden; border-radius: 18px; border: 1px solid var(--dsx-line);
            background: radial-gradient(120% 140% at 100% 0%, rgba(var(--dsx-accent-rgb), 0.16), transparent 55%), linear-gradient(150deg, var(--dsx-card), var(--dsx-card-2));
            box-shadow: var(--dsx-shadow-md); padding: clamp(14px, 2vw, 20px);
        }
        .dsx .hero::after { content: ''; position: absolute; right: -60px; top: -80px; width: 300px; height: 300px; background: radial-gradient(circle, rgba(var(--dsx-accent-rgb), 0.22), transparent 62%); pointer-events: none; }
        .dsx .hero__top { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; position: relative; z-index: 1; }
        .dsx .hero__id { display: flex; align-items: center; gap: 14px; }
        .dsx .hero__glyph { width: 46px; height: 46px; border-radius: 13px; flex: none; display: grid; place-items: center; font-size: 19px; color: #fff; background: linear-gradient(150deg, var(--dsx-bright), var(--dsx-deep)); box-shadow: 0 10px 22px -10px rgba(var(--dsx-accent-rgb), 0.7); }
        .dsx .hero__glyph.is-empty { background: linear-gradient(150deg, var(--dsx-green), #0f6a41); box-shadow: 0 10px 22px -10px rgba(var(--dsx-green-rgb), 0.6); }
        .dsx .hero__title { font-size: clamp(18px, 2.4vw, 24px); font-weight: 700; line-height: 1.05; letter-spacing: -0.02em; margin: 0; }
        .dsx .hero__sub { color: var(--dsx-muted); font-size: 12.5px; margin-top: 4px; display: flex; gap: 12px; flex-wrap: wrap; }
        .dsx .hero__sub i { color: var(--dsx-accent); margin-right: 5px; }
        .dsx .hero__date { display: flex; align-items: center; gap: 8px; background: var(--dsx-card); border: 1px solid var(--dsx-line); padding: 6px 10px 6px 12px; border-radius: 11px; box-shadow: var(--dsx-shadow-sm); }
        .dsx .hero__date > .ic { color: var(--dsx-accent); font-size: 15px; }
        .dsx .hero__date label { font-family: var(--dsx-mono); font-size: 9px; letter-spacing: 0.12em; text-transform: uppercase; color: var(--dsx-muted-2); margin: 0; display: block; }
        .dsx .hero__date input { border: none; background: none; color: var(--dsx-ink); font-weight: 600; font-size: 14px; outline: none; width: 140px; padding: 0; }

        /* kpis */
        .dsx .kpis { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-top: 16px; position: relative; z-index: 1; }
        .dsx .kpi { background: var(--dsx-card); border: 1px solid var(--dsx-line); border-radius: 13px; padding: 11px 12px 10px; position: relative; overflow: hidden; transition: transform 0.25s var(--dsx-ease), box-shadow 0.25s var(--dsx-ease); }
        .dsx .kpi:hover { transform: translateY(-2px); box-shadow: var(--dsx-shadow-md); }
        .dsx .kpi__rail { position: absolute; left: 0; top: 0; bottom: 0; width: 3px; border-radius: 3px; }
        .dsx .kpi__ic { width: 28px; height: 28px; border-radius: 8px; display: grid; place-items: center; font-size: 13px; margin-bottom: 8px; }
        .dsx .kpi__lbl { font-family: var(--dsx-mono); font-size: 9px; letter-spacing: 0.09em; text-transform: uppercase; color: var(--dsx-muted); }
        .dsx .kpi__val { font-size: clamp(17px, 2vw, 22px); font-weight: 700; letter-spacing: -0.02em; margin-top: 2px; color: var(--dsx-ink); }
        .dsx .kpi__val small { font-size: 11px; color: var(--dsx-muted); font-weight: 500; margin-left: 3px; }
        .dsx .kpi__foot { margin-top: 7px; font-size: 10.5px; color: var(--dsx-muted); display: flex; align-items: center; gap: 5px; }
        .dsx .i-green { color: var(--dsx-green); background: rgba(var(--dsx-green-rgb), 0.12); }
        .dsx .i-accent { color: var(--dsx-accent); background: rgba(var(--dsx-accent-rgb), 0.12); }
        .dsx .i-amber { color: var(--dsx-amber); background: rgba(var(--dsx-amber-rgb), 0.12); }
        .dsx .i-deep { color: var(--dsx-deep); background: rgba(var(--dsx-accent-rgb), 0.14); }
        .dsx .r-green { background: var(--dsx-green); } .dsx .r-accent { background: var(--dsx-accent); } .dsx .r-amber { background: var(--dsx-amber); } .dsx .r-deep { background: var(--dsx-deep); }

        /* grid + panels */
        .dsx .grid2 { display: grid; grid-template-columns: 1.15fr 0.85fr; gap: 12px; margin-top: 12px; align-items: start; }
        .dsx .panel { background: var(--dsx-card); border: 1px solid var(--dsx-line); border-radius: 14px; box-shadow: var(--dsx-shadow-sm); overflow: hidden; }
        .dsx .panel__head { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 11px 16px; border-bottom: 1px solid var(--dsx-line-soft); }
        .dsx .panel__head h3 { font-size: 15px; font-weight: 700; display: flex; align-items: center; gap: 8px; margin: 0; color: var(--dsx-ink); }
        .dsx .panel__head h3 i { color: var(--dsx-accent); font-size: 14px; }
        .dsx .panel__body { padding: 16px; }

        /* reconciliation */
        .dsx .recon { display: grid; gap: 11px; }
        .dsx .recon__row { display: flex; align-items: baseline; justify-content: space-between; }
        .dsx .recon__row .k { color: var(--dsx-muted); font-size: 13px; }
        .dsx .recon__row .v { font-weight: 700; font-size: 17px; color: var(--dsx-ink); }
        .dsx .recon__row.big .v { font-size: 21px; }
        .dsx .recon__bar { height: 10px; border-radius: 99px; background: var(--dsx-card-2); border: 1px solid var(--dsx-line); overflow: hidden; display: flex; }
        .dsx .recon__bar span { display: block; height: 100%; }
        .dsx .b-open { background: linear-gradient(90deg, var(--dsx-accent), var(--dsx-bright)); }
        .dsx .b-sales { background: linear-gradient(90deg, var(--dsx-green), #37c98a); }
        .dsx .recon__legend { display: flex; gap: 16px; font-size: 11.5px; color: var(--dsx-muted); flex-wrap: wrap; }
        .dsx .recon__legend span { display: inline-flex; align-items: center; gap: 6px; }
        .dsx .sw { width: 10px; height: 10px; border-radius: 3px; }
        .dsx .variance { margin-top: 2px; display: flex; align-items: center; justify-content: space-between; padding: 10px 13px; border-radius: 11px; border: 1px dashed; }
        .dsx .variance .lab { font-family: var(--dsx-mono); font-size: 10px; letter-spacing: 0.1em; text-transform: uppercase; color: var(--dsx-muted); }
        .dsx .variance .amt { font-weight: 800; font-size: 19px; }
        .dsx .variance .tag { font-size: 11px; font-weight: 600; }
        .dsx .variance.ok { border-color: rgba(var(--dsx-green-rgb), 0.4); background: rgba(var(--dsx-green-rgb), 0.08); }
        .dsx .variance.ok .amt, .dsx .variance.ok .tag { color: var(--dsx-green); }
        .dsx .variance.short { border-color: rgba(var(--dsx-red-rgb), 0.4); background: rgba(var(--dsx-red-rgb), 0.08); }
        .dsx .variance.short .amt, .dsx .variance.short .tag { color: var(--dsx-red); }
        .dsx .variance.over { border-color: rgba(var(--dsx-amber-rgb), 0.4); background: rgba(var(--dsx-amber-rgb), 0.08); }
        .dsx .variance.over .amt, .dsx .variance.over .tag { color: var(--dsx-amber); }

        /* fields */
        .dsx .field { margin-bottom: 11px; }
        .dsx .field > label { display: flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 600; color: var(--dsx-ink-soft); margin-bottom: 5px; }
        .dsx .field > label i { color: var(--dsx-green); font-size: 12px; }
        .dsx .field > label .req { color: var(--dsx-red); }
        .dsx .field > label .opt { color: var(--dsx-muted-2); font-weight: 500; }
        .dsx .control { display: flex; align-items: stretch; border: 1.5px solid var(--dsx-line); border-radius: 10px; overflow: hidden; background: var(--dsx-card-2); transition: border-color 0.18s, box-shadow 0.18s; }
        .dsx .control:focus-within { border-color: var(--dsx-accent); box-shadow: 0 0 0 3px rgba(var(--dsx-accent-rgb), 0.14); }
        .dsx .control .cur { display: grid; place-items: center; padding: 0 13px; color: var(--dsx-accent); background: rgba(var(--dsx-accent-rgb), 0.08); border-right: 1px solid var(--dsx-line); font-size: 13px; }
        .dsx .control input { flex: 1; border: none; background: none; padding: 9px 12px; color: var(--dsx-ink); font-size: 15px; font-weight: 600; outline: none; width: 100%; }
        .dsx .control input::placeholder { color: var(--dsx-muted-2); font-weight: 400; }
        .dsx textarea.ctl { width: 100%; border: 1.5px solid var(--dsx-line); border-radius: 10px; background: var(--dsx-card-2); padding: 9px 12px; color: var(--dsx-ink); resize: vertical; min-height: 48px; outline: none; transition: border-color 0.18s, box-shadow 0.18s; }
        .dsx textarea.ctl:focus { border-color: var(--dsx-accent); box-shadow: 0 0 0 3px rgba(var(--dsx-accent-rgb), 0.14); }
        .dsx .err-text { color: var(--dsx-red); font-size: 11.5px; margin-top: 4px; }

        /* buttons */
        .dsx .actions { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-top: 14px; padding-top: 13px; border-top: 1px solid var(--dsx-line-soft); flex-wrap: wrap; }
        .dsx .dbtn { display: inline-flex; align-items: center; gap: 7px; padding: 9px 16px; border-radius: 10px; font-weight: 600; font-size: 13px; border: none; cursor: pointer; transition: transform 0.15s var(--dsx-ease), box-shadow 0.2s; color: #fff; }
        .dsx .dbtn i { font-size: 12px; }
        .dsx .dbtn:hover { transform: translateY(-2px); color: #fff; }
        .dsx .dbtn--danger { background: linear-gradient(135deg, var(--dsx-red), #b02f2f); box-shadow: 0 10px 24px -12px rgba(var(--dsx-red-rgb), 0.9); }
        .dsx .dbtn--green { background: linear-gradient(135deg, var(--dsx-green), #0f7a4a); box-shadow: 0 10px 24px -12px rgba(var(--dsx-green-rgb), 0.9); }
        .dsx .dbtn--accent { background: linear-gradient(135deg, var(--dsx-bright), var(--dsx-deep)); box-shadow: 0 10px 24px -12px rgba(var(--dsx-accent-rgb), 0.9); }
        .dsx .dbtn--block { width: 100%; justify-content: center; padding: 11px; font-size: 14px; }
        .dsx .dbtn-group { display: flex; gap: 8px; flex-wrap: wrap; }
        .dsx .hint { margin-top: 10px; display: flex; gap: 8px; align-items: flex-start; padding: 9px 11px; border-radius: 9px; background: rgba(var(--dsx-green-rgb), 0.08); color: var(--dsx-muted); font-size: 11.5px; }
        .dsx .hint i { color: var(--dsx-green); margin-top: 1px; }

        /* empty state */
        .dsx .empty { text-align: center; padding: clamp(18px, 3vw, 34px) 16px; }
        .dsx .empty__ring { width: 68px; height: 68px; border-radius: 20px; margin: 0 auto 14px; display: grid; place-items: center; font-size: 28px; color: #fff; background: linear-gradient(150deg, var(--dsx-green), #0f6a41); box-shadow: 0 14px 32px -16px rgba(var(--dsx-green-rgb), 0.7); }
        .dsx .empty h2 { font-size: clamp(20px, 2.6vw, 26px); font-weight: 700; color: var(--dsx-ink); margin: 0; }
        .dsx .empty p { color: var(--dsx-muted); max-width: 440px; margin: 8px auto 0; font-size: 13.5px; line-height: 1.5; }
        .dsx .start-card { max-width: 460px; margin: 18px auto 0; text-align: left; }

        /* sessions table */
        .dsx .section-title { display: flex; align-items: center; gap: 8px; margin: 18px 2px 9px; }
        .dsx .section-title > i { color: var(--dsx-accent); }
        .dsx .section-title h3 { font-size: 15px; font-weight: 700; margin: 0; color: var(--dsx-ink); }
        .dsx .section-title .cnt { margin-left: auto; }
        .dsx .tbl-wrap { overflow-x: auto; }
        .dsx table.sx { width: 100%; border-collapse: collapse; min-width: 640px; margin: 0; }
        .dsx table.sx thead th { text-align: left; font-family: var(--dsx-mono); font-size: 9px; letter-spacing: 0.1em; text-transform: uppercase; color: var(--dsx-muted); padding: 9px 14px; border-bottom: 1px solid var(--dsx-line); background: transparent; }
        .dsx table.sx tbody td { padding: 9px 14px; border-bottom: 1px solid var(--dsx-line-soft); vertical-align: middle; color: var(--dsx-ink); }
        .dsx table.sx tbody tr { transition: background 0.15s; }
        .dsx table.sx tbody tr:hover { background: var(--dsx-card-2); }
        .dsx table.sx tbody tr:last-child td { border-bottom: none; }
        .dsx .br-cell { display: flex; align-items: center; gap: 9px; }
        .dsx .br-ic { width: 30px; height: 30px; border-radius: 8px; display: grid; place-items: center; background: rgba(var(--dsx-accent-rgb), 0.12); color: var(--dsx-accent); font-size: 13px; flex: none; }
        .dsx .br-name { font-weight: 600; font-size: 13px; }
        .dsx .muted-line { color: var(--dsx-muted); font-size: 11.5px; }
        .dsx .who { display: inline-flex; align-items: center; gap: 7px; font-size: 13px; }
        .dsx .who .av { width: 24px; height: 24px; border-radius: 50%; display: grid; place-items: center; font-size: 10px; font-weight: 700; color: #fff; background: linear-gradient(135deg, var(--dsx-bright), var(--dsx-deep)); text-transform: uppercase; }
        .dsx .amt-chip { font-family: var(--dsx-mono); font-size: 11.5px; font-weight: 700; color: var(--dsx-amber); background: rgba(var(--dsx-amber-rgb), 0.12); padding: 4px 8px; border-radius: 7px; }
        .dsx .row-actions { display: flex; gap: 6px; justify-content: flex-end; }
        .dsx .icon-btn { width: 30px; height: 30px; border-radius: 8px; display: grid; place-items: center; border: 1px solid var(--dsx-line); background: var(--dsx-card); color: var(--dsx-ink-soft); font-size: 12px; transition: all 0.15s var(--dsx-ease); cursor: pointer; }
        .dsx .icon-btn:hover { border-color: var(--dsx-accent); color: var(--dsx-accent); transform: translateY(-2px); }
        .dsx .icon-btn.solid { background: linear-gradient(135deg, var(--dsx-bright), var(--dsx-deep)); color: #fff; border: none; }
        .dsx .icon-btn.solid:hover { color: #fff; }

        @media (max-width: 900px) {
            .dsx .kpis { grid-template-columns: repeat(2, 1fr); }
            .dsx .grid2 { grid-template-columns: 1fr; }
        }
        @media (max-width: 560px) {
            .dsx .kpis { grid-template-columns: 1fr 1fr; }
        }
    </style>

    <div class="dsx">
        <!-- Alerts -->
        @if (session()->has('success'))
            <div class="dsx-alert ok"><i class="fa fa-check-circle"></i><span>{{ session('success') }}</span></div>
        @endif
        @if (session()->has('error'))
            <div class="dsx-alert err"><i class="fa fa-exclamation-circle"></i><span>{{ session('error') }}</span></div>
        @endif

        @if ($currentSession)
            @php
                $isPastSession = isset($sessionStats['opened_at']) && \Carbon\Carbon::parse($sessionStats['opened_at'])->lt(\Carbon\Carbon::today());
                $expected = (float) ($sessionStats['expected_amount'] ?? 0);
                $counted = (float) $closing_amount;
                $variance = $counted - $expected;
                $openAmt = (float) ($sessionStats['opening_amount'] ?? 0);
                $openPct = $expected > 0 ? max(0, min(100, ($openAmt / $expected) * 100)) : 0;
                $salesPct = 100 - $openPct;
                $vClass = abs($variance) < 0.01 ? 'ok' : ($variance < 0 ? 'short' : 'over');
                $vTag = abs($variance) < 0.01 ? 'Balanced — drawer matches expected' : ($variance < 0 ? 'Short — counted less than expected' : 'Over — counted more than expected');
            @endphp

            <!-- HERO -->
            <section class="hero">
                <div class="hero__top">
                    <div class="hero__id">
                        <div class="hero__glyph"><i class="fa fa-bolt"></i></div>
                        <div>
                            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                                <h4 class="hero__title">{{ $currentSession->branch->name }}</h4>
                                <span class="pill pill--green"><span class="pulse"></span> Session #{{ $currentSession->id }} · Live</span>
                            </div>
                            <div class="hero__sub">
                                <span><i class="fa fa-clock-o"></i> Opened {{ systemDateTime($sessionStats['opened_at']) }}</span>
                                <span><i class="fa fa-user"></i> by {{ $sessionStats['opened_by'] }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="hero__date">
                        <i class="fa fa-calendar ic"></i>
                        <div>
                            <label>Business date</label>
                            <input type="date" wire:model="date">
                        </div>
                    </div>
                </div>

                <div class="kpis">
                    <div class="kpi">
                        <span class="kpi__rail r-green"></span>
                        <div class="kpi__ic i-green"><i class="fa fa-university"></i></div>
                        <div class="kpi__lbl">Opening balance</div>
                        <div class="kpi__val">{{ currency($sessionStats['opening_amount']) }}</div>
                        <div class="kpi__foot"><i class="fa fa-lock"></i> Counted at open</div>
                    </div>
                    <div class="kpi">
                        <span class="kpi__rail r-accent"></span>
                        <div class="kpi__ic i-accent"><i class="fa fa-shopping-cart"></i></div>
                        <div class="kpi__lbl">Total sales</div>
                        <div class="kpi__val">{{ $sessionStats['total_sales'] }} <small>invoices</small></div>
                        <div class="kpi__foot"><i class="fa fa-scissors"></i> incl. {{ $sessionStats['total_tailoring_orders'] }} tailoring orders</div>
                    </div>
                    <div class="kpi">
                        <span class="kpi__rail r-amber"></span>
                        <div class="kpi__ic i-amber"><i class="fa fa-money"></i></div>
                        <div class="kpi__lbl">Collected revenue</div>
                        <div class="kpi__val">{{ currency($sessionStats['total_amount']) }}</div>
                        <div class="kpi__foot"><i class="fa fa-arrow-up"></i> Cash + card received</div>
                    </div>
                    <div class="kpi">
                        <span class="kpi__rail r-deep"></span>
                        <div class="kpi__ic i-deep"><i class="fa fa-calculator"></i></div>
                        <div class="kpi__lbl">Expected in drawer</div>
                        <div class="kpi__val">{{ currency($sessionStats['expected_amount']) }}</div>
                        <div class="kpi__foot"><i class="fa fa-info-circle"></i> Opening + revenue</div>
                    </div>
                </div>
            </section>

            <div class="grid2">
                <!-- Reconciliation -->
                <div class="panel">
                    <div class="panel__head">
                        <h3><i class="fa fa-balance-scale"></i> Cash reconciliation</h3>
                        <span class="pill pill--accent">Live</span>
                    </div>
                    <div class="panel__body">
                        <div class="recon">
                            <div class="recon__row"><span class="k">Opening balance</span><span class="v">{{ currency($sessionStats['opening_amount']) }}</span></div>
                            <div class="recon__row"><span class="k">+ Collected today</span><span class="v" style="color:var(--dsx-green)">{{ currency($sessionStats['total_amount']) }}</span></div>
                            <div class="recon__bar">
                                <span class="b-open" style="width:{{ $openPct }}%"></span>
                                <span class="b-sales" style="width:{{ $salesPct }}%"></span>
                            </div>
                            <div class="recon__legend">
                                <span><span class="sw" style="background:var(--dsx-accent)"></span> Opening {{ round($openPct) }}%</span>
                                <span><span class="sw" style="background:var(--dsx-green)"></span> Sales {{ round($salesPct) }}%</span>
                            </div>
                            <div class="recon__row big" style="border-top:1px solid var(--dsx-line-soft);padding-top:12px;">
                                <span class="k">Expected in drawer</span><span class="v">{{ currency($sessionStats['expected_amount']) }}</span>
                            </div>
                            <div class="recon__row"><span class="k">Counted (closing)</span><span class="v" style="color:var(--dsx-accent)">{{ currency($counted) }}</span></div>
                            <div class="variance {{ $vClass }}">
                                <div>
                                    <div class="lab">Variance (over / short)</div>
                                    <div class="tag">{{ $vTag }}</div>
                                </div>
                                <div class="amt">{{ currency($variance) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Close session -->
                <div class="panel">
                    <div class="panel__head">
                        <h3><i class="fa fa-lock"></i> Close session</h3>
                        <span class="pill pill--red">Critical action</span>
                    </div>
                    <div class="panel__body">
                        <form wire:submit.prevent="closeDay">
                            <div class="field">
                                <label><i class="fa fa-money"></i> Closing amount <span class="req">*</span></label>
                                <div class="control"><span class="cur"><i class="fa fa-money"></i></span><input type="number" step="0.01" wire:model.live="closing_amount" placeholder="0.00"></div>
                                @error('closing_amount') <div class="err-text">{{ $message }}</div> @enderror
                            </div>
                            @can('day close.sync amount')
                                <div class="field">
                                    <label><i class="fa fa-refresh"></i> Sync amount <span class="req">*</span></label>
                                    <div class="control"><span class="cur"><i class="fa fa-money"></i></span><input type="number" step="0.01" wire:model="sync_amount" placeholder="0.00"></div>
                                    @error('sync_amount') <div class="err-text">{{ $message }}</div> @enderror
                                </div>
                            @endcan
                            <div class="field">
                                <label><i class="fa fa-comment-o" style="color:var(--dsx-muted)"></i> Notes <span class="opt">(optional)</span></label>
                                <textarea class="ctl" wire:model="notes" rows="2" placeholder="Anything worth noting about today's session…"></textarea>
                                @error('notes') <div class="err-text">{{ $message }}</div> @enderror
                            </div>
                            <div class="hint"><i class="fa fa-info-circle"></i> Recount all cash in the drawer before closing. This action can't be undone.</div>
                            <div class="actions">
                                <button type="button" class="dbtn dbtn--danger" onclick="confirmCloseSession()"><i class="fa fa-lock"></i> Close session</button>
                                <div class="dbtn-group">
                                    <a href="{{ route('sale::create') }}" class="dbtn dbtn--green"><i class="fa fa-plus"></i> New sale</a>
                                    <a href="{{ route('sale::day-session', $currentSession->id) }}" class="dbtn dbtn--accent"><i class="fa fa-eye"></i> Details</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @else
            <!-- EMPTY STATE -->
            <section class="hero">
                <div class="hero__top">
                    <div class="hero__id">
                        <div class="hero__glyph is-empty"><i class="fa fa-unlock-alt"></i></div>
                        <div>
                            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                                <h4 class="hero__title">Day Session</h4>
                                <span class="pill pill--amber"><i class="fa fa-moon-o"></i> Day not started</span>
                            </div>
                            <div class="hero__sub"><span><i class="fa fa-calendar"></i> No open session for this branch on the selected date</span></div>
                        </div>
                    </div>
                    <div class="hero__date">
                        <i class="fa fa-calendar ic"></i>
                        <div>
                            <label>Business date</label>
                            <input type="date" wire:model="date">
                        </div>
                    </div>
                </div>
            </section>

            <div class="empty">
                <div class="empty__ring"><i class="fa fa-unlock-alt"></i></div>
                <h2>Start the day session</h2>
                <p>Count the cash in your drawer, enter the opening float below, and unlock sales &amp; tailoring for this branch.</p>

                <div class="panel start-card">
                    <div class="panel__head">
                        <h3><i class="fa fa-unlock"></i> Open new session</h3>
                        <span class="pill pill--green">Step 1 of 1</span>
                    </div>
                    <div class="panel__body">
                        <form wire:submit.prevent="openDay">
                            <div class="field">
                                <label><i class="fa fa-money"></i> Opening cash amount <span class="req">*</span></label>
                                <div class="control"><span class="cur"><i class="fa fa-money"></i></span><input type="number" step="0.01" wire:model="opening_amount" placeholder="0.00"></div>
                                @error('opening_amount') <div class="err-text">{{ $message }}</div> @enderror
                            </div>
                            <button type="submit" class="dbtn dbtn--green dbtn--block"><i class="fa fa-unlock"></i> Start session</button>
                            <div class="hint"><i class="fa fa-info-circle"></i> Count all physical cash before starting so your closing reconciliation is accurate.</div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <!-- ALL OPEN SESSIONS -->
        @if (count($openSessions) > 0)
            <div class="section-title">
                <i class="fa fa-th-list"></i>
                <h3>All open sessions</h3>
                <span class="pill pill--green cnt"><span class="pulse"></span> {{ count($openSessions) }} active</span>
            </div>
            <div class="panel">
                <div class="tbl-wrap">
                    <table class="sx">
                        <thead>
                            <tr>
                                <th>Branch</th>
                                <th>Started</th>
                                <th>Opened by</th>
                                <th>Opening</th>
                                <th style="text-align:right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($openSessions as $session)
                                <tr>
                                    <td>
                                        <div class="br-cell">
                                            <div class="br-ic"><i class="fa fa-building"></i></div>
                                            <div>
                                                <div class="br-name">{{ $session->branch->name }}</div>
                                                <span class="pill pill--green" style="padding:2px 8px;font-size:9px;">Active</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="br-name">{{ $session->opened_at->format('M d, Y') }}</div>
                                        <div class="muted-line">{{ $session->opened_at->format('h:i A') }}</div>
                                    </td>
                                    <td>
                                        <span class="who">
                                            <span class="av">{{ \Illuminate\Support\Str::substr($session->opener->name ?? 'U', 0, 2) }}</span>
                                            {{ $session->opener->name ?? 'Unknown' }}
                                        </span>
                                    </td>
                                    <td><span class="amt-chip">{{ currency($session->opening_amount) }}</span></td>
                                    <td>
                                        <div class="row-actions">
                                            <button class="icon-btn" title="Manage" wire:click="changeBranch({{ $session->branch_id }})"><i class="fa fa-cog"></i></button>
                                            <a href="{{ route('sale::day-session', $session->id) }}" class="icon-btn solid" title="Details"><i class="fa fa-eye"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>
@push('scripts')
    <script>
        function confirmCloseSession() {
            Swal.fire({
                title: 'Are you sure?',
                html: "Are you sure you want to close the session? This action cannot be undone. @if($currentSession?->branch?->moq_sync) <br><i> The API sync amount is " + @this.get('sync_amount') + "</i> @endif ",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fa fa-lock me-2"></i>Yes, Close Session',
                cancelButtonText: '<i class="fa fa-times me-2"></i>Cancel',
                reverseButtons: true,
                focusCancel: true,
                customClass: {
                    confirmButton: 'btn btn-danger',
                    cancelButton: 'btn btn-secondary'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    @this.call('closeDay');
                }
            });
        }
    </script>
@endpush

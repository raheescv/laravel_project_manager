<div>
    @use('Illuminate\Support\Str')

    {{--
        ╔══════════════════════════════════════════════════════════════════════╗
        ║  LPO Purchase — View · "Premium Compact" design system                ║
        ║  Shares the .lpox scoped system used by the Local Purchase Order view. ║
        ║  Accent derives from the active SETTINGS THEME (--bs-primary / --bs-*) ║
        ║  so it tracks the colour scheme AND light / dark mode automatically.   ║
        ╚══════════════════════════════════════════════════════════════════════╝
    --}}
    @once
        <style>
            .lpox{
                /* Brand → settings theme primary */
                --acc: var(--bs-primary);
                --acc-rgb: var(--bs-primary-rgb);
                --acc-d: color-mix(in srgb, var(--bs-primary), #000 14%);
                --acc-deep: color-mix(in srgb, var(--bs-primary), #000 42%);
                --acc-tint: color-mix(in srgb, var(--bs-primary), transparent 90%);
                --acc-tint-2: color-mix(in srgb, var(--bs-primary), transparent 95%);

                --surface:#ffffff; --surface-2:#f5f7fa;
                --ink: var(--bs-emphasis-color);
                --ink-2: var(--bs-body-color);
                --muted: var(--bs-secondary-color);
                --faint: var(--bs-tertiary-color);
                --line:#e7ebf1; --line-soft:#eff2f6;

                --ok: var(--bs-success);   --ok-rgb: var(--bs-success-rgb);
                --info: var(--bs-info);    --info-rgb: var(--bs-info-rgb);
                --warn: var(--bs-warning); --warn-rgb: var(--bs-warning-rgb);
                --bad: var(--bs-danger);   --bad-rgb: var(--bs-danger-rgb);

                --shadow: 0 1px 2px rgba(16,24,40,.05), 0 8px 24px -10px rgba(16,24,40,.12);
                --shadow-lg: 0 18px 42px -18px rgba(var(--acc-rgb),.40), 0 8px 18px -12px rgba(16,24,40,.20);

                color: var(--ink);
                font-size: 12.5px; line-height: 1.5;
                -webkit-font-smoothing: antialiased;
            }
            .lpox *{ box-sizing:border-box; }

            [data-bs-theme="dark"] .lpox{
                --surface:#272d34; --surface-2:#2e353d;
                --line:#3a424c; --line-soft:#343c45;
                --acc-tint: color-mix(in srgb, var(--bs-primary), transparent 84%);
                --acc-tint-2: color-mix(in srgb, var(--bs-primary), transparent 90%);
                --shadow: 0 1px 2px rgba(0,0,0,.4), 0 10px 28px -10px rgba(0,0,0,.5);
                --shadow-lg: 0 18px 44px -18px rgba(0,0,0,.6), 0 8px 18px -12px rgba(0,0,0,.5);
            }

            /* shared */
            .lpox .l-card{ background:var(--surface); border:1px solid var(--line); border-radius:16px; box-shadow:var(--shadow); overflow:hidden; }
            .lpox .l-sec{ margin-bottom:14px; }
            .lpox .l-head{ display:flex; align-items:center; gap:11px; padding:13px 16px; border-bottom:1px solid var(--line-soft); }
            .lpox .l-ic{ width:32px; height:32px; border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:14px; background:var(--acc-tint); color:var(--acc-d); flex:0 0 auto; }
            .lpox .l-ic.t-ok{ background:rgba(var(--ok-rgb),.13); color:var(--ok); }
            .lpox .l-ic.t-info{ background:rgba(var(--info-rgb),.13); color:var(--info); }
            .lpox .l-ic.t-bad{ background:rgba(var(--bad-rgb),.13); color:var(--bad); }
            .lpox .l-ic.t-warn{ background:rgba(var(--warn-rgb),.13); color:var(--warn); }
            .lpox .l-ic.t-purple{ background:rgba(111,66,193,.13); color:#6f42c1; }
            .lpox .l-title{ font-size:13.5px; font-weight:750; letter-spacing:.1px; }
            .lpox .l-sub{ font-size:11px; color:var(--muted); font-weight:500; margin-top:-1px; }
            .lpox .l-pill{ display:inline-flex; align-items:center; gap:5px; padding:4px 11px; border-radius:999px; font-size:11px; font-weight:700; letter-spacing:.3px; }
            .lpox .pill-acc{ background:var(--acc-tint); color:var(--acc-d); }
            .lpox .pill-ok{ background:rgba(var(--ok-rgb),.13); color:var(--ok); }
            .lpox .pill-warn{ background:rgba(var(--warn-rgb),.14); color:var(--warn); }
            .lpox .pill-bad{ background:rgba(var(--bad-rgb),.13); color:var(--bad); }
            .lpox .pill-muted{ background:var(--surface-2); color:var(--muted); }
            .lpox .pill-purple{ background:rgba(111,66,193,.12); color:#6f42c1; }

            /* HERO — compact, with integrated KPI rail */
            .lpox-hero{ position:relative; border-radius:16px; overflow:hidden; margin-bottom:12px; box-shadow:var(--shadow-lg);
                background:
                    radial-gradient(120% 165% at 100% 0, color-mix(in srgb, var(--acc) 28%, transparent), transparent 55%),
                    linear-gradient(125deg, var(--acc-deep), var(--acc-d)); }
            .lpox-hero .glow{ position:absolute; right:-60px; top:-90px; width:300px; height:300px; border-radius:50%;
                background:radial-gradient(circle, rgba(255,255,255,.16), transparent 65%); pointer-events:none; }
            .lpox-hero-inner{ position:relative; display:flex; align-items:center; gap:15px; padding:15px 18px; flex-wrap:wrap; }
            .lpox-hero .doc-ic{ width:46px; height:46px; border-radius:13px; flex:0 0 auto; background:rgba(255,255,255,.14);
                border:1px solid rgba(255,255,255,.22); display:flex; align-items:center; justify-content:center; font-size:20px; color:#fff;
                box-shadow:inset 0 1px 0 rgba(255,255,255,.25); }
            .lpox-hero .h-main{ flex:1; min-width:200px; color:#fff; }
            .lpox-hero .h-eyebrow{ font-size:9.5px; font-weight:700; letter-spacing:2px; text-transform:uppercase; color:rgba(255,255,255,.72); }
            .lpox-hero .h-ref{ font-size:19px; font-weight:800; letter-spacing:.3px; line-height:1.15; margin-top:2px; }
            .lpox-hero .h-meta{ display:flex; gap:14px; flex-wrap:wrap; margin-top:6px; font-size:11.5px; color:rgba(255,255,255,.86); }
            .lpox-hero .h-meta i{ opacity:.82; margin-right:5px; }
            .lpox-hero .h-meta a{ color:#fff; text-decoration:none; border-bottom:1px dotted rgba(255,255,255,.5); }
            .lpox-hero .h-right{ display:flex; flex-direction:column; align-items:flex-end; gap:8px; }
            .lpox-hero .status-pill{ background:rgba(255,255,255,.16); color:#fff; border:1px solid rgba(255,255,255,.3);
                padding:5px 12px; border-radius:999px; font-size:11.5px; font-weight:750; letter-spacing:.4px; display:inline-flex; align-items:center; gap:6px; white-space:nowrap; }
            .lpox-hero .status-pill .dot{ width:7px; height:7px; border-radius:50%; box-shadow:0 0 0 3px rgba(255,255,255,.18); }
            .lpox-hero .btn-print{ background:#fff; color:var(--acc-deep); border:0; padding:6px 13px; border-radius:9px; font-size:11.5px; font-weight:700;
                cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:6px; box-shadow:0 4px 12px rgba(0,0,0,.18); transition:transform .12s; }
            .lpox-hero .btn-print:hover{ transform:translateY(-1px); color:var(--acc-deep); }
            .lpox-hero .btn-ghost{ background:rgba(255,255,255,.16); color:#fff; border:1px solid rgba(255,255,255,.3); padding:6px 13px; border-radius:9px;
                font-size:11.5px; font-weight:700; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:6px; transition:transform .12s; }
            .lpox-hero .btn-ghost:hover{ transform:translateY(-1px); color:#fff; background:rgba(255,255,255,.24); }
            /* integrated stat rail */
            .lpox-hstats{ position:relative; display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:1px;
                background:rgba(255,255,255,.14); border-top:1px solid rgba(255,255,255,.14); }
            .lpox-hs{ background:linear-gradient(180deg, rgba(255,255,255,.05), transparent); padding:10px 16px; }
            .lpox-hs .s-k{ font-size:9px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; color:rgba(255,255,255,.66); display:flex; align-items:center; gap:5px; }
            .lpox-hs .s-k i{ color:rgba(255,255,255,.75); }
            .lpox-hs .s-v{ font-size:16px; font-weight:800; color:#fff; margin-top:3px; letter-spacing:.2px; }
            .lpox-hs .s-v small{ font-size:10px; font-weight:600; color:rgba(255,255,255,.7); }
            @media(max-width:600px){ .lpox-hero .h-right{ align-items:flex-start; width:100%; } }

            /* INFO PANELS */
            .lpox-grid{ display:grid; grid-template-columns:1.05fr .95fr; gap:12px; margin-bottom:12px; }
            .lpox-grid.one{ grid-template-columns:1fr; }
            @media(max-width:820px){ .lpox-grid{ grid-template-columns:1fr; } }
            .lpox .kv-grid{ display:grid; grid-template-columns:1fr 1fr; gap:7px; padding:12px 14px; }
            .lpox .kv{ background:var(--surface-2); border:1px solid var(--line-soft); border-radius:10px; padding:9px 11px; }
            .lpox .kv.full{ grid-column:1 / -1; }
            .lpox .kv .kk{ font-size:10px; font-weight:700; letter-spacing:.6px; text-transform:uppercase; color:var(--muted); display:flex; align-items:center; gap:6px; margin-bottom:3px; }
            .lpox .kv .kk i{ color:var(--acc); font-size:11px; }
            .lpox .kv .vv{ font-size:13px; font-weight:650; color:var(--ink); word-break:break-word; }
            .lpox .kv .vv a{ color:var(--acc-d); text-decoration:none; font-weight:700; }
            .lpox .tone-ok .kv .kk i{ color:var(--ok); } .lpox .tone-info .kv .kk i{ color:var(--info); } .lpox .tone-bad .kv .kk i{ color:var(--bad); }

            /* TABLE */
            .lpox .l-tblwrap{ overflow-x:auto; }
            .lpox table.l-tbl{ width:100%; border-collapse:collapse; font-size:12.5px; }
            .lpox table.l-tbl thead th{ background:var(--surface-2); color:var(--muted); font-size:10px; font-weight:750; letter-spacing:.6px;
                text-transform:uppercase; padding:9px 12px; text-align:left; border-bottom:1px solid var(--line); white-space:nowrap; }
            .lpox table.l-tbl th.num, .lpox table.l-tbl td.num{ text-align:right; }
            .lpox table.l-tbl th.ctr, .lpox table.l-tbl td.ctr{ text-align:center; }
            .lpox table.l-tbl tbody td{ padding:10px 12px; border-bottom:1px solid var(--line-soft); vertical-align:middle; }
            .lpox table.l-tbl tbody tr:hover td{ background:var(--acc-tint-2); }
            .lpox table.l-tbl tbody td .pname{ font-weight:700; color:var(--ink); }
            .lpox table.l-tbl tbody td .psub{ color:var(--muted); font-size:11px; }
            .lpox table.l-tbl tbody td .amt{ font-weight:800; }
            .lpox .idx{ display:inline-flex; align-items:center; justify-content:center; width:24px; height:24px; border-radius:7px; background:var(--acc-tint); color:var(--acc-d); font-weight:800; font-size:11px; }
            .lpox .tag{ display:inline-block; padding:2px 8px; border-radius:6px; font-size:10.5px; font-weight:650; line-height:1.6; }
            .lpox .tag-cat{ background:var(--acc-tint); color:var(--acc-d); }
            .lpox .tag-sub{ background:var(--surface-2); color:var(--ink-2); border:1px solid var(--line); }
            .lpox table.l-tbl tfoot td{ padding:9px 12px; font-weight:700; color:var(--ink); background:var(--surface-2); border-top:1px solid var(--line-soft); }
            .lpox table.l-tbl tfoot tr.grand td{ padding:11px 12px; font-weight:800; border-top:2px solid var(--line); }
            .lpox .tfoot-acc td{ color:var(--acc-d); }
            .lpox .tf-lbl{ text-align:right; color:var(--muted); font-weight:650; }

            /* STATUS BADGES */
            .lpox .stbadge{ display:inline-flex; align-items:center; gap:5px; padding:3px 9px; border-radius:999px; font-size:10.5px; font-weight:700; }
            .lpox .st-full{ background:rgba(var(--ok-rgb),.13); color:var(--ok); }
            .lpox .st-part{ background:rgba(var(--warn-rgb),.14); color:var(--warn); }
            .lpox .st-excess{ background:rgba(var(--bad-rgb),.13); color:var(--bad); }
            .lpox .st-zero{ background:var(--surface-2); color:var(--muted); }

            /* JOURNAL BLOCK */
            .lpox .jnl{ border:1px solid var(--line); border-radius:12px; overflow:hidden; margin:0 16px 12px; }
            .lpox .jnl:first-of-type{ margin-top:4px; }
            .lpox .jnl-head{ display:flex; align-items:center; justify-content:space-between; padding:9px 13px; background:var(--surface-2); border-bottom:1px solid var(--line-soft); flex-wrap:wrap; gap:6px; }
            .lpox .jnl-head .jttl{ font-weight:750; color:var(--ink); font-size:12.5px; }
            .lpox .jnl-head .jref{ background:var(--surface); border:1px solid var(--line); color:var(--muted); font-size:10px; font-weight:700; padding:2px 8px; border-radius:999px; margin-left:8px; }
            .lpox .jnl-head .jdate{ color:var(--muted); font-size:11px; }
            .lpox .amt-dr{ color:var(--ok); font-weight:750; }
            .lpox .amt-cr{ color:var(--bad); font-weight:750; }
            .lpox .dash{ color:var(--muted); }

            /* TIMELINE — premium */
            .lpox .tl{ padding:20px 20px 8px; }
            .lpox .tl-item{ --tc: var(--acc); --tc-rgb: var(--acc-rgb); position:relative; display:flex; gap:16px; padding-bottom:20px; }
            .lpox .tl-item.t-ok{ --tc: var(--ok); --tc-rgb: var(--ok-rgb); }
            .lpox .tl-item.t-info{ --tc: var(--info); --tc-rgb: var(--info-rgb); }
            .lpox .tl-item.t-bad{ --tc: var(--bad); --tc-rgb: var(--bad-rgb); }
            .lpox .tl-item.t-muted{ --tc: var(--muted); --tc-rgb: var(--faint); }
            .lpox .tl-item:last-child{ padding-bottom:6px; }
            .lpox .tl-rail{ position:relative; flex:0 0 auto; display:flex; flex-direction:column; align-items:center; width:40px; }
            .lpox .tl-dot{ position:relative; width:40px; height:40px; border-radius:13px; display:flex; align-items:center; justify-content:center;
                font-size:15px; z-index:1; color:#fff;
                background:linear-gradient(145deg, color-mix(in srgb,var(--tc),#fff 12%), color-mix(in srgb,var(--tc),#000 20%));
                box-shadow:0 6px 16px -6px rgba(var(--tc-rgb),.65), inset 0 1px 0 rgba(255,255,255,.35);
                border:1px solid color-mix(in srgb,var(--tc),#000 8%); }
            .lpox .tl-dot::after{ content:""; position:absolute; inset:-4px; border-radius:16px; border:1.5px solid rgba(var(--tc-rgb),.22); }
            .lpox .tl-item.t-muted .tl-dot{ color:var(--muted);
                background:linear-gradient(145deg, var(--surface), var(--surface-2)); box-shadow:0 3px 10px -6px rgba(0,0,0,.3); border-color:var(--line); }
            .lpox .tl-item.t-muted .tl-dot::after{ border-color:var(--line); }
            .lpox .tl-item.is-latest .tl-dot::before{ content:""; position:absolute; inset:-4px; border-radius:16px;
                box-shadow:0 0 0 0 rgba(var(--tc-rgb),.45); animation:tlpulse 2.4s ease-out infinite; }
            @keyframes tlpulse{ 0%{ box-shadow:0 0 0 0 rgba(var(--tc-rgb),.4); } 70%{ box-shadow:0 0 0 10px rgba(var(--tc-rgb),0); } 100%{ box-shadow:0 0 0 0 rgba(var(--tc-rgb),0); } }
            .lpox .tl-line{ flex:1; width:2px; margin:6px 0 -4px; border-radius:2px;
                background:linear-gradient(to bottom, rgba(var(--tc-rgb),.5), var(--line) 70%); }
            .lpox .tl-item:last-child .tl-line{ display:none; }

            .lpox .tl-body{ flex:1; min-width:0; background:var(--surface); border:1px solid var(--line-soft); border-radius:13px;
                padding:12px 15px; box-shadow:0 1px 2px rgba(16,24,40,.04); position:relative; overflow:hidden; transition:box-shadow .16s, transform .16s, border-color .16s; }
            .lpox .tl-body::before{ content:""; position:absolute; left:0; top:0; bottom:0; width:3px; background:var(--tc); opacity:.8; }
            .lpox .tl-item:hover .tl-body{ transform:translateX(2px); border-color:color-mix(in srgb,var(--tc),transparent 65%); box-shadow:0 10px 26px -14px rgba(var(--tc-rgb),.5); }
            .lpox .tl-item.is-latest .tl-body{ border-color:color-mix(in srgb,var(--tc),transparent 60%); background:linear-gradient(180deg, rgba(var(--tc-rgb),.05), var(--surface) 60%); }
            .lpox .tl-top{ display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap; }
            .lpox .tl-ttlwrap{ display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
            .lpox .tl-ttl{ font-size:13.5px; font-weight:800; color:var(--ink); letter-spacing:.1px; }
            .lpox .tl-badge{ font-size:9px; font-weight:800; letter-spacing:.6px; text-transform:uppercase; padding:2px 8px; border-radius:999px;
                background:rgba(var(--tc-rgb),.13); color:var(--tc); border:1px solid rgba(var(--tc-rgb),.22); }
            .lpox .tl-item.t-muted .tl-badge{ color:var(--muted); background:var(--surface-2); border-color:var(--line); }
            .lpox .tl-time{ display:inline-flex; align-items:center; gap:5px; font-size:10.5px; color:var(--muted); font-weight:650; white-space:nowrap;
                background:var(--surface-2); border:1px solid var(--line-soft); padding:3px 9px; border-radius:999px; }
            .lpox .tl-time i{ color:var(--tc); font-size:10px; }
            .lpox .tl-who{ font-size:11.5px; color:var(--muted); margin-top:7px; display:inline-flex; align-items:center; gap:8px; }
            .lpox .tl-ava{ width:20px; height:20px; border-radius:50%; flex:0 0 auto; display:flex; align-items:center; justify-content:center;
                font-size:9px; font-weight:800; color:#fff; letter-spacing:.2px;
                background:linear-gradient(145deg, color-mix(in srgb,var(--tc),#fff 10%), color-mix(in srgb,var(--tc),#000 22%)); box-shadow:0 2px 6px -2px rgba(var(--tc-rgb),.6); }
            .lpox .tl-who b{ color:var(--ink); font-weight:750; }
            .lpox .tl-note{ margin-top:9px; background:var(--surface-2); border:1px solid var(--line-soft); border-left:3px solid var(--tc);
                border-radius:9px; padding:9px 12px; font-size:12px; color:var(--ink-2); line-height:1.5; }
            .lpox .tl-note .tl-note-k{ font-size:9.5px; font-weight:800; letter-spacing:.7px; text-transform:uppercase; color:var(--tc); display:flex; align-items:center; gap:5px; margin-bottom:3px; opacity:.9; }

            /* ACTION PANELS */
            .lpox-action{ border:1px solid var(--line); border-radius:16px; overflow:hidden; box-shadow:var(--shadow); margin-bottom:14px; background:var(--surface); }
            .lpox-action.tone-warn{ border-top:3px solid var(--warn); }
            .lpox-action.tone-primary{ border-top:3px solid var(--acc); }
            .lpox-action .a-body{ padding:16px 18px; }
            .lpox-action .a-head{ display:flex; align-items:center; gap:11px; margin-bottom:14px; }
            .lpox-action .a-ic{ width:36px; height:36px; border-radius:11px; display:flex; align-items:center; justify-content:center; font-size:16px; }
            .lpox-action.tone-warn .a-ic{ background:rgba(var(--warn-rgb),.14); color:var(--warn); }
            .lpox-action.tone-primary .a-ic{ background:var(--acc-tint); color:var(--acc-d); }
            .lpox-action .a-title{ font-size:14px; font-weight:750; }
            .lpox-action .a-sub{ font-size:11.5px; color:var(--muted); }
            .lpox .lpox-lbl{ font-size:11.5px; font-weight:700; color:var(--ink-2); display:flex; align-items:center; gap:6px; margin-bottom:6px; }
            .lpox .lpox-ta{ width:100%; border:1px solid var(--line); border-radius:10px; background:var(--surface-2); color:var(--ink);
                padding:10px 12px; font-size:12.5px; font-family:inherit; resize:vertical; min-height:64px; }
            .lpox .lpox-ta:focus{ outline:none; border-color:var(--acc); box-shadow:0 0 0 3px var(--acc-tint); }
            .lpox .a-actions{ display:flex; justify-content:flex-end; gap:9px; margin-top:14px; flex-wrap:wrap; }
            .lpox .l-btn{ border:0; padding:9px 18px; border-radius:10px; font-size:12.5px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:7px; transition:transform .12s,filter .12s; }
            .lpox .l-btn:hover{ transform:translateY(-1px); filter:brightness(1.05); }
            .lpox .l-btn-ok{ background:var(--ok); color:#fff; } .lpox .l-btn-bad{ background:var(--bad); color:#fff; } .lpox .l-btn-acc{ background:var(--acc); color:#fff; }

            .lpox .l-empty{ text-align:center; padding:34px 0; color:var(--muted); }
            .lpox .l-empty i{ font-size:34px; opacity:.3; display:block; margin-bottom:8px; }

            @media (max-width:760px){
                .lpox-grid{ grid-template-columns:1fr; }
                .lpox .kv-grid{ grid-template-columns:1fr; }
                .lpox-hero .h-right{ align-items:flex-start; width:100%; }
            }
        </style>
    @endonce

    @if ($errors->any())
        <div class="mb-4 alert alert-danger alert-dismissible fade show">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @php
        $statusBs = match ($purchase->status) {
            'accepted', 'completed' => 'success',
            'rejected' => 'danger',
            'pending' => 'warning',
            default => 'secondary',
        };
        $statusIcon = match ($purchase->status) {
            'accepted', 'completed' => 'fa fa-check-circle',
            'rejected' => 'fa fa-times-circle',
            'pending' => 'fa fa-clock-o',
            default => 'fa fa-info-circle',
        };

        $refNo = $purchase->invoice_no ?? '#' . $purchase->id;
        $itemCount = $purchase->items->count();
        $totalQty = $purchase->items->sum('quantity');
    @endphp

    <div class="lpox" data-status="{{ $purchase->status }}">

        {{-- ===================== HERO ===================== --}}
        <div class="lpox-hero">
            <div class="glow"></div>
            <div class="lpox-hero-inner">
                <div class="doc-ic"><i class="fa fa-shopping-cart"></i></div>
                <div class="h-main">
                    <div class="h-eyebrow">LPO Purchase</div>
                    <div class="h-ref">{{ $refNo }}</div>
                    <div class="h-meta">
                        <span><i class="fa fa-home"></i>{{ $purchase->branch?->name ?? '—' }}</span>
                        <span><i class="fa fa-building"></i>{{ $purchase->account?->name ?? '—' }}</span>
                        <span><i class="fa fa-calendar"></i>{{ $purchase->date ? \Carbon\Carbon::parse($purchase->date)->format('d M Y') : '—' }}</span>
                        @if ($purchase->localPurchaseOrder)
                            <span><i class="fa fa-shopping-cart"></i><a href="{{ route('lpo::view', $purchase->localPurchaseOrder->id) }}">LPO #{{ $purchase->localPurchaseOrder->id }}</a></span>
                        @endif
                    </div>
                </div>
                <div class="h-right">
                    <span class="status-pill">
                        <span class="dot" style="background: var(--bs-{{ $statusBs }})"></span>
                        <i class="{{ $statusIcon }}"></i> {{ strtoupper($purchase->status) }}
                    </span>
                    @if ($purchase->status === 'pending')
                        @can('lpo-purchase.edit')
                            <a href="{{ route('lpo-purchase::edit', $purchase->id) }}" class="btn-ghost">
                                <i class="fa fa-pencil"></i> Edit
                            </a>
                        @endcan
                        @can('lpo-purchase.decide')
                            <a href="{{ route('lpo-purchase::decision', $purchase->id) }}" class="btn-print">
                                <i class="fa fa-gavel"></i> Accept / Reject
                            </a>
                        @endcan
                    @endif
                </div>
            </div>

            {{-- integrated KPI rail --}}
            <div class="lpox-hstats">
                <div class="lpox-hs">
                    <div class="s-k"><i class="fa fa-cubes"></i> Line Items</div>
                    <div class="s-v">{{ $itemCount }} <small>{{ Str::plural('product', $itemCount) }}</small></div>
                </div>
                <div class="lpox-hs">
                    <div class="s-k"><i class="fa fa-sort-amount-asc"></i> Total Qty</div>
                    <div class="s-v">{{ number_format($totalQty, 0) }} <small>units</small></div>
                </div>
                <div class="lpox-hs">
                    <div class="s-k"><i class="fa fa-money"></i> Grand Total</div>
                    <div class="s-v">{{ number_format($purchase->grand_total, 2) }}</div>
                </div>
                @if ($purchase->journals->count())
                    <div class="lpox-hs">
                        <div class="s-k"><i class="fa fa-book"></i> Journals</div>
                        <div class="s-v">{{ $purchase->journals->count() }} <small>{{ Str::plural('entry', $purchase->journals->count()) }}</small></div>
                    </div>
                @endif
            </div>
        </div>

        {{-- ===================== INFO PANELS ===================== --}}
        @php
            // Build the chronological activity timeline.
            $timeline = [];

            // 1. Created
            $timeline[] = [
                'ts' => $purchase->created_at,
                'title' => 'Purchase Created',
                'badge' => 'Draft',
                'icon' => 'fa fa-file-text-o',
                'tone' => 'acc',
                'who' => $purchase->createdUser?->name,
                'note' => null,
                'note_label' => null,
            ];

            // 2. Accepted / Rejected decision
            if ($purchase->decision_at && in_array($purchase->status, ['accepted', 'rejected', 'completed'])) {
                $isReject = $purchase->status === 'rejected';
                $timeline[] = [
                    'ts' => $purchase->decision_at,
                    'title' => $isReject ? 'Rejected' : 'Accepted',
                    'badge' => $isReject ? 'Rejected' : 'Accepted',
                    'icon' => $isReject ? 'fa fa-times-circle' : 'fa fa-check-circle',
                    'tone' => $isReject ? 'bad' : 'ok',
                    'who' => $purchase->decisionMaker?->name,
                    'note' => $purchase->decision_note,
                    'note_label' => $isReject ? 'Rejection Reason' : 'Remarks',
                ];
            }

            // 3. Last updated (only if meaningfully after the last recorded event)
            $lastEventTs = collect($timeline)->max('ts');
            if ($purchase->updated_at && $lastEventTs && $purchase->updated_at->gt($lastEventTs->copy()->addMinute())) {
                $timeline[] = [
                    'ts' => $purchase->updated_at,
                    'title' => 'Last Updated',
                    'badge' => 'Edited',
                    'icon' => 'fa fa-pencil',
                    'tone' => 'muted',
                    'who' => null,
                    'note' => null,
                    'note_label' => null,
                ];
            }

            // Chronological order.
            $timeline = collect($timeline)->sortBy('ts')->values();
        @endphp

        <div class="lpox-grid">
            {{-- Purchase Information --}}
            <div class="l-card" style="margin-bottom:0">
                <div class="l-head">
                    <div class="l-ic"><i class="fa fa-file-text-o"></i></div>
                    <div><div class="l-title">Purchase Information</div><div class="l-sub">Vendor &amp; invoice metadata</div></div>
                </div>
                <div class="kv-grid">
                    <div class="kv"><div class="kk"><i class="fa fa-barcode"></i>Invoice No</div><div class="vv">{{ $purchase->invoice_no ?? '-' }}</div></div>
                    <div class="kv"><div class="kk"><i class="fa fa-calendar"></i>Date</div><div class="vv">{{ $purchase->date ? \Carbon\Carbon::parse($purchase->date)->format('d M Y') : '-' }}</div></div>
                    <div class="kv"><div class="kk"><i class="fa fa-building"></i>Vendor</div><div class="vv">{{ $purchase->account?->name ?? '-' }}</div></div>
                    <div class="kv"><div class="kk"><i class="fa fa-shopping-cart"></i>LPO</div><div class="vv">
                        @if ($purchase->localPurchaseOrder)
                            <a href="{{ route('lpo::view', $purchase->localPurchaseOrder->id) }}">LPO #{{ $purchase->localPurchaseOrder->id }} <i class="fa fa-external-link" style="font-size:9px"></i></a>
                        @else
                            -
                        @endif
                    </div></div>
                    <div class="kv"><div class="kk"><i class="fa fa-user"></i>Created By</div><div class="vv">{{ $purchase->createdUser?->name ?? '-' }}</div></div>
                    <div class="kv"><div class="kk"><i class="fa fa-home"></i>Branch</div><div class="vv">{{ $purchase->branch?->name ?? '-' }}</div></div>
                    <div class="kv"><div class="kk"><i class="fa fa-cubes"></i>Total Items</div><div class="vv">{{ $itemCount }} items</div></div>
                    <div class="kv"><div class="kk"><i class="fa fa-money"></i>Grand Total</div><div class="vv" style="color:var(--ok)">{{ number_format($purchase->grand_total, 2) }}</div></div>
                </div>
            </div>

            {{-- Activity Timeline --}}
            <div class="l-card" style="margin-bottom:0">
                <div class="l-head">
                    <div class="l-ic"><i class="fa fa-history"></i></div>
                    <div style="flex:1"><div class="l-title">Activity Timeline</div><div class="l-sub">Who did what, and when</div></div>
                    <span class="l-pill pill-acc">{{ $timeline->count() }} {{ Str::plural('event', $timeline->count()) }}</span>
                </div>
                <div class="tl">
                @foreach ($timeline as $ev)
                    @php $isLatest = $loop->last; @endphp
                    <div class="tl-item t-{{ $ev['tone'] }} {{ $isLatest ? 'is-latest' : '' }}">
                        <div class="tl-rail">
                            <div class="tl-dot"><i class="{{ $ev['icon'] }}"></i></div>
                            <div class="tl-line"></div>
                        </div>
                        <div class="tl-body">
                            <div class="tl-top">
                                <div class="tl-ttlwrap">
                                    <span class="tl-ttl">{{ $ev['title'] }}</span>
                                    <span class="tl-badge">{{ $ev['badge'] }}</span>
                                </div>
                                <div class="tl-time"><i class="fa fa-calendar"></i> {{ $ev['ts'] ? \Carbon\Carbon::parse($ev['ts'])->format('d M Y, h:i A') : '—' }}</div>
                            </div>
                            @if ($ev['who'])
                                <div class="tl-who">
                                    <span class="tl-ava">{{ Str::of($ev['who'])->explode(' ')->take(2)->map(fn($p) => Str::substr($p, 0, 1))->implode('') ?: 'U' }}</span>
                                    Action by <b>{{ $ev['who'] }}</b>
                                </div>
                            @endif
                            @if (!empty($ev['note']))
                                <div class="tl-note">
                                    <span class="tl-note-k"><i class="fa fa-comment-o"></i> {{ $ev['note_label'] }}</span>
                                    {{ $ev['note'] }}
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
                </div>
            </div>
        </div>

        {{-- ===================== PURCHASE ITEMS ===================== --}}
        <div class="l-sec l-card">
            <div class="l-head">
                <div class="l-ic t-info"><i class="fa fa-cubes"></i></div>
                <div style="flex:1"><div class="l-title">Purchase Items</div></div>
                <span class="l-pill pill-acc">{{ $itemCount }} items · {{ number_format($purchase->grand_total, 2) }}</span>
            </div>
            <div class="l-tblwrap">
                <table class="l-tbl">
                    <thead>
                        <tr>
                            <th class="ctr" style="width:46px">#</th>
                            <th>Product</th>
                            <th>Expense Account</th>
                            <th>Category</th>
                            <th class="num">Qty</th>
                            <th class="num">Unit Price</th>
                            <th class="num">Discount</th>
                            <th class="num">Tax %</th>
                            <th class="num">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($purchase->items as $index => $item)
                            @php
                                $gross = $item->quantity * $item->unit_price;
                                $net = $gross - $item->discount;
                                $taxAmt = $net * ($item->tax / 100);
                                $total = $net + $taxAmt;
                            @endphp
                            <tr>
                                <td class="ctr"><span class="idx">{{ $index + 1 }}</span></td>
                                <td>
                                    <div class="pname">{{ $item->product->name }}</div>
                                    <div class="psub">
                                        {{ collect([
                                            $item->product->code ? '#' . $item->product->code : null,
                                            $item->product->brand?->name,
                                            $item->unit?->name ?? $item->product->unit?->name,
                                        ])->filter()->implode(' · ') ?: '—' }}
                                    </div>
                                </td>
                                <td><span class="psub">{{ $item->account?->name ?? '-' }}</span></td>
                                <td>
                                    @if ($item->product->mainCategory?->name)
                                        <span class="tag tag-cat">{{ $item->product->mainCategory->name }}</span>
                                    @else
                                        <span class="psub">—</span>
                                    @endif
                                </td>
                                <td class="num"><span class="pname">{{ $item->quantity }}</span></td>
                                <td class="num"><span class="psub">{{ number_format($item->unit_price, 2) }}</span></td>
                                <td class="num">{{ $item->discount > 0 ? number_format($item->discount, 2) : '—' }}</td>
                                <td class="num">{{ $item->tax > 0 ? $item->tax . '%' : '—' }}</td>
                                <td class="num amt">{{ number_format($total, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">
                                    <div class="l-empty"><i class="fa fa-cubes"></i> No items</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($itemCount)
                        <tfoot>
                            <tr>
                                <td colspan="7"></td>
                                <td class="tf-lbl">Gross Amount</td>
                                <td class="num">{{ number_format($purchase->gross_amount, 2) }}</td>
                            </tr>
                            @if ($purchase->item_discount > 0)
                                <tr>
                                    <td colspan="7"></td>
                                    <td class="tf-lbl">Item Discount</td>
                                    <td class="num" style="color:var(--bad)">- {{ number_format($purchase->item_discount, 2) }}</td>
                                </tr>
                            @endif
                            @if ($purchase->tax_amount > 0)
                                <tr>
                                    <td colspan="7"></td>
                                    <td class="tf-lbl">Tax</td>
                                    <td class="num">+ {{ number_format($purchase->tax_amount, 2) }}</td>
                                </tr>
                            @endif
                            @if ($purchase->other_discount > 0)
                                <tr>
                                    <td colspan="7"></td>
                                    <td class="tf-lbl">Other Discount</td>
                                    <td class="num" style="color:var(--bad)">- {{ number_format($purchase->other_discount, 2) }}</td>
                                </tr>
                            @endif
                            @if ($purchase->freight > 0)
                                <tr>
                                    <td colspan="7"></td>
                                    <td class="tf-lbl">Freight</td>
                                    <td class="num">+ {{ number_format($purchase->freight, 2) }}</td>
                                </tr>
                            @endif
                            <tr class="grand tfoot-acc">
                                <td colspan="7"></td>
                                <td class="tf-lbl" style="color:var(--acc-d)">Grand Total</td>
                                <td class="num" style="color:var(--ok)">{{ number_format($purchase->grand_total, 2) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

        {{-- ===================== JOURNAL ENTRIES ===================== --}}
        @if ($purchase->journals->count())
            <div class="l-sec l-card">
                <div class="l-head">
                    <div class="l-ic t-purple"><i class="fa fa-book"></i></div>
                    <div style="flex:1"><div class="l-title">Journal Entries</div><div class="l-sub">Accounting postings for this purchase</div></div>
                    <span class="l-pill pill-purple">{{ $purchase->journals->count() }} {{ Str::plural('journal', $purchase->journals->count()) }}</span>
                </div>
                @foreach ($purchase->journals as $journal)
                    @php $filteredEntries = $journal->entries->where('account_id', '!=', $purchase->account_id); @endphp
                    <div class="jnl">
                        <div class="jnl-head">
                            <div>
                                <span class="jttl"><i class="fa fa-file-text-o"></i> {{ $journal->description }}</span>
                                @if ($journal->reference_number)
                                    <span class="jref">Ref: {{ $journal->reference_number }}</span>
                                @endif
                            </div>
                            <span class="jdate"><i class="fa fa-calendar"></i> {{ $journal->date ? \Carbon\Carbon::parse($journal->date)->format('d M Y') : '-' }}</span>
                        </div>
                        <div class="l-tblwrap">
                            <table class="l-tbl">
                                <thead>
                                    <tr>
                                        <th>Account</th>
                                        <th>Remarks</th>
                                        <th class="num" style="width:130px">Debit</th>
                                        <th class="num" style="width:130px">Credit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($journal->entries as $entry)
                                        <tr>
                                            <td>
                                                <span class="pname">
                                                    <i class="fa fa-{{ $entry->debit > 0 ? 'arrow-up' : 'arrow-down' }}" style="font-size:10px;color:{{ $entry->debit > 0 ? 'var(--ok)' : 'var(--bad)' }};margin-right:5px"></i>
                                                    {{ $entry->account?->name ?? '-' }}
                                                </span>
                                            </td>
                                            <td><span class="psub">{{ $entry->remarks ?? '-' }}</span></td>
                                            <td class="num">
                                                @if ($entry->debit > 0)
                                                    <span class="amt-dr">{{ number_format($entry->debit, 2) }}</span>
                                                @else
                                                    <span class="dash">-</span>
                                                @endif
                                            </td>
                                            <td class="num">
                                                @if ($entry->credit > 0)
                                                    <span class="amt-cr">{{ number_format($entry->credit, 2) }}</span>
                                                @else
                                                    <span class="dash">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="2">Total</td>
                                        <td class="num" style="color:var(--ok)">{{ number_format($filteredEntries->sum('debit'), 2) }}</td>
                                        <td class="num" style="color:var(--bad)">{{ number_format($filteredEntries->sum('credit'), 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- ===================== ACCEPT / REJECT ===================== --}}
        @if ($purchase->status === 'pending' && $is_approvable)
            <div class="lpox-action tone-warn">
                <div class="a-body">
                    <div class="a-head">
                        <div class="a-ic"><i class="fa fa-gavel"></i></div>
                        <div><div class="a-title">Take Action</div><div class="a-sub">Accept or reject this LPO purchase</div></div>
                    </div>
                    <div class="lpox-lbl"><i class="fa fa-comment-o"></i> Remarks</div>
                    <textarea class="lpox-ta" rows="3" wire:model="remarks" placeholder="Enter remarks (required for rejection)"></textarea>
                    <div class="a-actions">
                        <button type="button" class="l-btn l-btn-bad" wire:click="reject" wire:confirm="Are you sure you want to reject this LPO Purchase?">
                            <i class="fa fa-times"></i> Reject
                        </button>
                        <button type="button" class="l-btn l-btn-ok" wire:click="accept" wire:confirm="Are you sure you want to accept this LPO Purchase? Journal entries will be created.">
                            <i class="fa fa-check"></i> Accept
                        </button>
                    </div>
                </div>
            </div>
        @endif

        {{-- ===================== REVERSE (accepted bill) ===================== --}}
        @if ($purchase->status === 'accepted')
            @can('lpo-purchase.reverse')
                <div class="lpox-action tone-warn">
                    <div class="a-body">
                        <div class="a-head">
                            <div class="a-ic"><i class="fa fa-undo"></i></div>
                            <div><div class="a-title">Reverse Purchase</div><div class="a-sub">Roll back the journal entries for this bill</div></div>
                        </div>
                        <div class="lpox-lbl"><i class="fa fa-comment-o"></i> Remarks</div>
                        <textarea class="lpox-ta" rows="3" wire:model="remarks" placeholder="Reason for reversal (required)"></textarea>
                        <div class="a-actions">
                            <button type="button" class="l-btn l-btn-bad" wire:click="reverse" wire:confirm="This will roll back the journal entries for this bill. Continue?">
                                <i class="fa fa-undo"></i> Reverse
                            </button>
                        </div>
                    </div>
                </div>
            @endcan
        @endif
    </div>
</div>

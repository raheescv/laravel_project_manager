<div>
    @use('Illuminate\Support\Str')
    @use('App\Enums\LocalPurchaseOrder\LocalPurchaseOrderStatus')

    {{--
        ╔══════════════════════════════════════════════════════════════════════╗
        ║  Local Purchase Order — View · "Premium Compact" design system        ║
        ║  Scoped under .lpox. Accent derives from the active SETTINGS THEME     ║
        ║  (--bs-primary / --bs-* tokens) so it tracks the colour scheme AND     ║
        ║  light / dark mode automatically. All Livewire bindings preserved.     ║
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
            .lpox .l-title{ font-size:13.5px; font-weight:750; letter-spacing:.1px; }
            .lpox .l-sub{ font-size:11px; color:var(--muted); font-weight:500; margin-top:-1px; }
            .lpox .l-pill{ display:inline-flex; align-items:center; gap:5px; padding:4px 11px; border-radius:999px; font-size:11px; font-weight:700; letter-spacing:.3px; }
            .lpox .pill-acc{ background:var(--acc-tint); color:var(--acc-d); }
            .lpox .pill-ok{ background:rgba(var(--ok-rgb),.13); color:var(--ok); }
            .lpox .pill-warn{ background:rgba(var(--warn-rgb),.14); color:var(--warn); }
            .lpox .pill-bad{ background:rgba(var(--bad-rgb),.13); color:var(--bad); }
            .lpox .pill-muted{ background:var(--surface-2); color:var(--muted); }

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
            .lpox-hero .h-right{ display:flex; flex-direction:column; align-items:flex-end; gap:8px; }
            .lpox-hero .status-pill{ background:rgba(255,255,255,.16); color:#fff; border:1px solid rgba(255,255,255,.3);
                padding:5px 12px; border-radius:999px; font-size:11.5px; font-weight:750; letter-spacing:.4px; display:inline-flex; align-items:center; gap:6px; white-space:nowrap; }
            .lpox-hero .status-pill .dot{ width:7px; height:7px; border-radius:50%; box-shadow:0 0 0 3px rgba(255,255,255,.18); }
            .lpox-hero .btn-print{ background:#fff; color:var(--acc-deep); border:0; padding:6px 13px; border-radius:9px; font-size:11.5px; font-weight:700;
                cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:6px; box-shadow:0 4px 12px rgba(0,0,0,.18); transition:transform .12s; }
            .lpox-hero .btn-print:hover{ transform:translateY(-1px); color:var(--acc-deep); }
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
            .lpox table.l-tbl tfoot td{ padding:11px 12px; font-weight:800; color:var(--ink); background:var(--surface-2); border-top:2px solid var(--line); }
            .lpox .tfoot-acc td{ color:var(--acc-d); }

            /* FULFILLMENT */
            .lpox .ff-top{ padding:14px 16px 2px; }
            .lpox .ff-bar{ height:9px; border-radius:999px; background:var(--surface-2); border:1px solid var(--line-soft); overflow:hidden; }
            .lpox .ff-bar > span{ display:block; height:100%; border-radius:999px; background:linear-gradient(90deg,var(--acc),var(--acc-d)); }
            .lpox .ff-legend{ display:flex; justify-content:space-between; margin-top:7px; font-size:11.5px; color:var(--muted); }
            .lpox .ff-legend b{ color:var(--ink); }
            .lpox .mini-bar{ height:6px; border-radius:999px; background:var(--surface-2); overflow:hidden; min-width:70px; flex:1; }
            .lpox .mini-bar > span{ display:block; height:100%; border-radius:999px; }
            .lpox .bar-ok > span{ background:var(--ok); } .lpox .bar-warn > span{ background:var(--warn); } .lpox .bar-none > span{ background:var(--faint); }
            .lpox .stbadge{ display:inline-flex; align-items:center; gap:5px; padding:3px 9px; border-radius:999px; font-size:10.5px; font-weight:700; }
            .lpox .st-full{ background:rgba(var(--ok-rgb),.13); color:var(--ok); }
            .lpox .st-part{ background:rgba(var(--warn-rgb),.14); color:var(--warn); }
            .lpox .st-excess{ background:rgba(var(--bad-rgb),.13); color:var(--bad); }
            .lpox .st-zero{ background:var(--surface-2); color:var(--muted); }

            /* GRN */
            .lpox .grn{ border:1px solid var(--line); border-radius:12px; overflow:hidden; margin:0 16px 12px; }
            .lpox .grn:first-of-type{ margin-top:4px; }
            .lpox .grn-head{ display:flex; align-items:center; justify-content:space-between; padding:9px 13px; background:var(--surface-2); border-bottom:1px solid var(--line-soft); flex-wrap:wrap; gap:6px; }
            .lpox .grn-head a{ color:var(--acc-d); font-weight:750; text-decoration:none; font-size:12.5px; }
            .lpox .grn-head .gdate{ color:var(--muted); font-size:11px; margin-left:12px; }

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
            /* latest event gets a soft pulse */
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
        $statusBs = match ($order->status) {
            LocalPurchaseOrderStatus::APPROVED => 'success',
            LocalPurchaseOrderStatus::CONFIRMED => 'primary',
            LocalPurchaseOrderStatus::REJECTED => 'danger',
            default => 'warning',
        };
        $statusIcon = match ($order->status) {
            LocalPurchaseOrderStatus::APPROVED => 'fa fa-check-circle',
            LocalPurchaseOrderStatus::CONFIRMED => 'fa fa-check-square-o',
            LocalPurchaseOrderStatus::REJECTED => 'fa fa-times-circle',
            default => 'fa fa-clock-o',
        };

        $refNo = 'BASB-LPO-' . \Carbon\Carbon::parse($order->date ?? now())->format('m-y') . '-' . str_pad($order->id, 3, '0', STR_PAD_LEFT);
        $itemCount = $order->items->count();
        $totalQty = $order->items->sum('quantity');
        $subtotal = $order->items->sum(fn($i) => $i->quantity * $i->rate);

        $hasGrn = $order->grns->count() > 0;
        $receivedByProduct = $order->grns->flatMap->items->groupBy('product_id')->map(fn($items) => $items->sum('quantity'));
        $totalReceived = $receivedByProduct->sum();
        $overallPercent = $totalQty > 0 ? round(($totalReceived / $totalQty) * 100) : 0;
    @endphp

    <div class="lpox" data-status="{{ $order->status->value }}">

        {{-- ===================== HERO ===================== --}}
        <div class="lpox-hero">
            <div class="glow"></div>
            <div class="lpox-hero-inner">
                <div class="doc-ic"><i class="fa fa-file-text-o"></i></div>
                <div class="h-main">
                    <div class="h-eyebrow">Local Purchase Order</div>
                    <div class="h-ref">{{ $refNo }}</div>
                    <div class="h-meta">
                        <span><i class="demo-psi-home"></i>{{ $order->branch?->name ?? '—' }}</span>
                        <span><i class="demo-psi-shop"></i>{{ $order->vendor?->name ?? '—' }}</span>
                        <span><i class="demo-psi-calendar-4"></i>{{ $order->date ? \Carbon\Carbon::parse($order->date)->format('d M Y') : '—' }}</span>
                    </div>
                </div>
                <div class="h-right">
                    <span class="status-pill">
                        <span class="dot" style="background: var(--bs-{{ $statusBs }})"></span>
                        <i class="{{ $statusIcon }}"></i> {{ strtoupper($order->status->label()) }}
                    </span>
                    @can('print', $order)
                        <a href="{{ route('lpo::print', $order->id) }}" target="_blank" class="btn-print">
                            <i class="fa fa-print"></i> Print / Save PDF
                        </a>
                    @endcan
                </div>
            </div>

            {{-- integrated KPI rail --}}
            <div class="lpox-hstats">
                <div class="lpox-hs">
                    <div class="s-k"><i class="demo-psi-basket-coins"></i> Line Items</div>
                    <div class="s-v">{{ $itemCount }} <small>{{ Str::plural('product', $itemCount) }}</small></div>
                </div>
                <div class="lpox-hs">
                    <div class="s-k"><i class="fa fa-sort-amount-asc"></i> Total Qty</div>
                    <div class="s-v">{{ number_format($totalQty, 0) }} <small>units</small></div>
                </div>
                <div class="lpox-hs">
                    <div class="s-k"><i class="demo-psi-coin"></i> Order Value</div>
                    <div class="s-v">{{ number_format($subtotal, 2) }}</div>
                </div>
                @if ($hasGrn)
                    <div class="lpox-hs">
                        <div class="s-k"><i class="fa fa-tasks"></i> Fulfilled</div>
                        <div class="s-v">{{ $overallPercent }}<small>%</small></div>
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
                'ts' => $order->created_at,
                'title' => 'Order Created',
                'badge' => 'Draft',
                'icon' => 'demo-psi-file',
                'tone' => 'acc',
                'who' => $order->creator?->name,
                'note' => null,
                'note_label' => null,
            ];

            // 2. Confirmed
            if ($order->confirmation_at) {
                $timeline[] = [
                    'ts' => $order->confirmation_at,
                    'title' => 'Confirmed',
                    'badge' => 'Confirmed',
                    'icon' => 'fa fa-check-square-o',
                    'tone' => 'info',
                    'who' => $order->confirmedBy?->name,
                    'note' => $order->confirmation_note,
                    'note_label' => 'Confirmation Note',
                ];
            }

            // 3. Approved / Rejected decision
            if ($order->decision_at && in_array($order->status, [LocalPurchaseOrderStatus::APPROVED, LocalPurchaseOrderStatus::REJECTED])) {
                $isReject = $order->status === LocalPurchaseOrderStatus::REJECTED;
                $timeline[] = [
                    'ts' => $order->decision_at,
                    'title' => $isReject ? 'Rejected' : 'Approved',
                    'badge' => $isReject ? 'Rejected' : 'Approved',
                    'icon' => $isReject ? 'fa fa-times-circle' : 'fa fa-check-circle',
                    'tone' => $isReject ? 'bad' : 'ok',
                    'who' => $order->decisionMaker?->name,
                    'note' => $order->decision_note,
                    'note_label' => $isReject ? 'Rejection Reason' : 'Remarks',
                ];
            }

            // 4. Last updated (only if meaningfully after the last recorded event)
            $lastEventTs = collect($timeline)->max('ts');
            if ($order->updated_at && $lastEventTs && $order->updated_at->gt($lastEventTs->copy()->addMinute())) {
                $timeline[] = [
                    'ts' => $order->updated_at,
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
            {{-- Order Information --}}
            <div class="l-card" style="margin-bottom:0">
                <div class="l-head">
                    <div class="l-ic"><i class="demo-psi-file"></i></div>
                    <div><div class="l-title">Order Information</div><div class="l-sub">Vendor &amp; order metadata</div></div>
                </div>
                <div class="kv-grid">
                    <div class="kv"><div class="kk"><i class="demo-psi-shop"></i>Vendor</div><div class="vv">{{ $order->vendor?->name ?? '-' }}</div></div>
                    <div class="kv"><div class="kk"><i class="demo-psi-calendar-4"></i>Date</div><div class="vv">{{ $order->date ? \Carbon\Carbon::parse($order->date)->format('d M Y') : '-' }}</div></div>
                    <div class="kv"><div class="kk"><i class="demo-psi-male"></i>Created By</div><div class="vv">{{ $order->creator?->name ?? '-' }}</div></div>
                    <div class="kv"><div class="kk"><i class="demo-psi-home"></i>Branch</div><div class="vv">{{ $order->branch?->name ?? '-' }}</div></div>
                    <div class="kv"><div class="kk"><i class="demo-psi-basket-coins"></i>Total Products</div><div class="vv">{{ $itemCount }} items</div></div>
                    <div class="kv"><div class="kk"><i class="demo-psi-coin"></i>Total Amount</div><div class="vv">{{ number_format($subtotal, 2) }}</div></div>
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
                                <div class="tl-time"><i class="demo-psi-calendar-4"></i> {{ $ev['ts'] ? \Carbon\Carbon::parse($ev['ts'])->format('d M Y, h:i A') : '—' }}</div>
                            </div>
                            @if ($ev['who'])
                                <div class="tl-who">
                                    <span class="tl-ava">{{ Str::of($ev['who'])->explode(' ')->take(2)->map(fn($p) => Str::substr($p, 0, 1))->implode('') ?: 'U' }}</span>
                                    Action by <b>{{ $ev['who'] }}</b>
                                </div>
                            @endif
                            @if (!empty($ev['note']))
                                <div class="tl-note">
                                    <span class="tl-note-k"><i class="demo-psi-speech-bubble-3"></i> {{ $ev['note_label'] }}</span>
                                    {{ $ev['note'] }}
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
                </div>
            </div>
        </div>

        {{-- ===================== PRODUCTS ===================== --}}
        <div class="l-sec l-card">
            <div class="l-head">
                <div class="l-ic"><i class="demo-psi-basket-coins"></i></div>
                <div style="flex:1"><div class="l-title">Products</div></div>
                <span class="l-pill pill-acc">{{ $itemCount }} items · {{ number_format($subtotal, 2) }}</span>
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
                            <th class="num">Rate</th>
                            <th class="num">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($order->items as $index => $item)
                            <tr>
                                <td class="ctr"><span class="idx">{{ $index + 1 }}</span></td>
                                <td>
                                    <div class="pname">{{ $item->product->name }}</div>
                                    <div class="psub">
                                        {{ collect([
                                            $item->product->code ? '#' . $item->product->code : null,
                                            $item->product->brand?->name,
                                            $item->product->unit?->name,
                                        ])->filter()->implode(' · ') ?: '—' }}
                                    </div>
                                </td>
                                <td><span class="psub">{{ $item->account?->name ?? '-' }}</span></td>
                                <td>
                                    @if ($item->product->mainCategory?->name)
                                        <span class="tag tag-cat">{{ $item->product->mainCategory->name }}</span>
                                    @endif
                                    @if ($item->product->subCategory?->name)
                                        <span class="tag tag-sub">{{ $item->product->subCategory->name }}</span>
                                    @endif
                                    @unless ($item->product->mainCategory?->name || $item->product->subCategory?->name)
                                        <span class="psub">—</span>
                                    @endunless
                                </td>
                                <td class="num"><span class="pname">{{ $item->quantity }}</span></td>
                                <td class="num"><span class="psub">{{ number_format($item->rate, 2) }}</span></td>
                                <td class="num amt">{{ number_format($item->quantity * $item->rate, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="l-empty"><i class="demo-psi-basket-coins"></i> No products added</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($itemCount)
                        <tfoot>
                            <tr class="tfoot-acc">
                                <td colspan="4">Total</td>
                                <td class="num">{{ number_format($totalQty, 0) }}</td>
                                <td></td>
                                <td class="num">{{ number_format($subtotal, 2) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

        {{-- ===================== TERMS & CONDITIONS ===================== --}}
        @php
            $terms = $order->payment_terms ?? [];
            $quickLabels = [
                ['label' => 'Payment Terms', 'icon' => 'fa fa-credit-card'],
                ['label' => 'Delivery',       'icon' => 'fa fa-truck'],
                ['label' => 'Validity',       'icon' => 'fa fa-calendar'],
                ['label' => 'Warranty',       'icon' => 'fa fa-shield'],
                ['label' => 'Remarks',        'icon' => 'fa fa-comment-o'],
                ['label' => 'Penalty',        'icon' => 'fa fa-exclamation-triangle'],
            ];
        @endphp
        <div class="l-sec l-card">
            @if (!$editing_terms)
                {{-- READ --}}
                <div class="l-head">
                    <div class="l-ic"><i class="fa fa-file-text-o"></i></div>
                    <div style="flex:1">
                        <div class="l-title">Terms &amp; Conditions</div>
                        <div class="l-sub">Payment terms &amp; remarks for this order</div>
                    </div>
                    @if (count($terms))
                        <span class="l-pill pill-acc">{{ count($terms) }} {{ Str::plural('term', count($terms)) }}</span>
                    @endif
                    @can('editTerms', $order)
                        <button type="button" class="l-btn" style="background:var(--surface-2);color:var(--ink-2);border:1px solid var(--line);padding:5px 12px;font-size:11.5px;"
                            wire:click="openTermsEdit">
                            <i class="fa fa-pencil"></i> {{ count($terms) ? 'Edit' : 'Add Terms' }}
                        </button>
                    @endcan
                </div>
                @if (count($terms))
                    <div style="padding:14px 16px;display:flex;flex-direction:column;gap:10px;">
                        @foreach ($terms as $ti => $term)
                            <div style="display:flex;align-items:flex-start;gap:13px;">
                                <div class="idx" style="width:26px;height:26px;border-radius:50%;flex:0 0 auto;margin-top:1px;">{{ $ti + 1 }}</div>
                                <div style="flex:1;">
                                    <div style="font-size:10.5px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:var(--muted);margin-bottom:3px;">{{ $term['label'] }}</div>
                                    <div style="font-size:13px;font-weight:600;color:{{ filled($term['value']) ? 'var(--ink)' : 'var(--muted)' }};font-style:{{ filled($term['value']) ? 'normal' : 'italic' }};">
                                        {{ filled($term['value']) ? $term['value'] : '—' }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="l-empty">
                        <i class="fa fa-file-text-o"></i>
                        No terms &amp; conditions added yet
                    </div>
                @endif

            @else
                {{-- EDIT --}}
                <div class="l-head">
                    <div class="l-ic t-warn"><i class="fa fa-pencil"></i></div>
                    <div style="flex:1">
                        <div class="l-title">Edit Terms &amp; Conditions</div>
                        <div class="l-sub">Add, remove or fill in terms — saved per order</div>
                    </div>
                </div>

                <div style="padding:12px 16px 4px;display:flex;flex-direction:column;gap:8px;">
                    @foreach ($terms_buffer as $ti => $term)
                        <div style="display:flex;align-items:center;gap:8px;background:var(--surface-2);border:1px solid var(--line-soft);border-radius:11px;padding:9px 10px;">
                            <div class="idx" style="width:24px;height:24px;border-radius:50%;flex:0 0 auto;font-size:11px;">{{ $ti + 1 }}</div>
                            <input type="text" wire:model="terms_buffer.{{ $ti }}.label"
                                placeholder="Label"
                                style="width:155px;flex:0 0 auto;border:1px solid var(--line);border-radius:8px;background:var(--surface);color:var(--ink);padding:6px 10px;font-size:12.5px;font-family:inherit;outline:none;font-weight:600;">
                            <input type="text" wire:model="terms_buffer.{{ $ti }}.value"
                                placeholder="e.g. Net 30 days"
                                style="flex:1;border:1px solid var(--line);border-radius:8px;background:var(--surface);color:var(--ink);padding:6px 10px;font-size:12.5px;font-family:inherit;outline:none;">
                            <button type="button" wire:click="removeTermRow({{ $ti }})"
                                style="flex:0 0 auto;width:28px;height:28px;border-radius:7px;border:0;background:rgba(var(--bad-rgb),.1);color:var(--bad);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:12px;">
                                <i class="fa fa-times"></i>
                            </button>
                        </div>
                    @endforeach
                </div>

                {{-- Quick-add chips --}}
                <div style="padding:10px 16px 2px;border-top:1px dashed var(--line-soft);">
                    <div style="font-size:10px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:var(--muted);margin-bottom:7px;display:flex;align-items:center;gap:6px;">
                        <i class="fa fa-bolt" style="color:var(--acc-d)"></i> Quick add
                    </div>
                    <div style="display:flex;flex-wrap:wrap;gap:6px;">
                        @foreach ($quickLabels as $ql)
                            @php
                                $qlUsed = collect($terms_buffer)->contains(fn($t) => strtolower($t['label']) === strtolower($ql['label']));
                            @endphp
                            <button type="button"
                                wire:click="{{ $qlUsed ? '' : 'addQuickTerm(\'' . $ql['label'] . '\')' }}"
                                style="background:{{ $ql['label'] === 'Payment Terms' ? 'var(--acc)' : 'var(--acc-tint)' }};color:{{ $ql['label'] === 'Payment Terms' ? '#fff' : 'var(--acc-d)' }};border:1px solid color-mix(in srgb,var(--acc),transparent 55%);padding:5px 11px;border-radius:999px;font-size:11.5px;font-weight:700;cursor:{{ $qlUsed ? 'default' : 'pointer' }};display:inline-flex;align-items:center;gap:5px;opacity:{{ $qlUsed ? '.35' : '1' }};white-space:nowrap;transition:.12s;">
                                <i class="{{ $ql['icon'] }}" style="font-size:10px;"></i>
                                {{ $ql['label'] }}
                                @if ($ql['label'] === 'Payment Terms')
                                    <small style="font-size:9px;opacity:.75">DEFAULT</small>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Footer --}}
                <div style="padding:10px 16px 14px;display:flex;align-items:center;flex-wrap:wrap;gap:8px;margin-top:4px;">
                    <button type="button" wire:click="addTermRow"
                        style="background:var(--surface-2);color:var(--ink-2);border:1px dashed var(--line);padding:7px 13px;border-radius:9px;font-size:12px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
                        <i class="fa fa-plus"></i> Custom Term
                    </button>
                    <div style="flex:1"></div>
                    <button type="button" wire:click="cancelTermsEdit" class="l-btn" style="background:var(--surface-2);color:var(--ink-2);border:1px solid var(--line);">
                        Cancel
                    </button>
                    <button type="button" wire:click="saveTerms" class="l-btn l-btn-acc">
                        <i class="fa fa-check"></i> Save
                    </button>
                </div>
            @endif
        </div>

        {{-- ===================== FULFILLMENT ===================== --}}
        @if ($hasGrn && $itemCount)
            <div class="l-sec l-card">
                <div class="l-head">
                    <div class="l-ic"><i class="fa fa-tasks"></i></div>
                    <div style="flex:1"><div class="l-title">Fulfillment Summary</div><div class="l-sub">Ordered vs received across all GRNs</div></div>
                    <span class="l-pill {{ $overallPercent >= 100 ? 'pill-ok' : ($overallPercent > 0 ? 'pill-warn' : 'pill-muted') }}">{{ $overallPercent }}% Fulfilled</span>
                </div>
                <div class="ff-top">
                    <div class="ff-bar"><span style="width: {{ min($overallPercent, 100) }}%"></span></div>
                    <div class="ff-legend">
                        <span>Total Ordered: <b>{{ number_format($totalQty, 0) }}</b></span>
                        <span>Total Received: <b style="color:var(--ok)">{{ number_format($totalReceived, 0) }}</b></span>
                    </div>
                </div>
                <div class="l-tblwrap" style="padding-top:8px">
                    <table class="l-tbl">
                        <thead>
                            <tr>
                                <th class="ctr" style="width:46px">#</th>
                                <th>Product</th>
                                <th class="num">Ordered</th>
                                <th class="num">Received</th>
                                <th class="num">Pending</th>
                                <th style="width:180px">Progress</th>
                                <th class="ctr">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order->items as $index => $item)
                                @php
                                    $received = $receivedByProduct->get($item->product_id, 0);
                                    $pending = $item->quantity - $received;
                                    $percent = $item->quantity > 0 ? round(($received / $item->quantity) * 100) : 0;
                                @endphp
                                <tr>
                                    <td class="ctr"><span class="idx">{{ $index + 1 }}</span></td>
                                    <td><div class="pname">{{ $item->product->name }}</div></td>
                                    <td class="num">{{ $item->quantity }}</td>
                                    <td class="num" style="color:var(--ok); font-weight:800">{{ $received }}</td>
                                    <td class="num">
                                        @if ($pending != 0)
                                            <span style="color:var(--bad); font-weight:700">{{ $pending }}</span>
                                        @else
                                            <span style="color:var(--muted)">0</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div style="display:flex; align-items:center; gap:9px">
                                            <div class="mini-bar {{ $percent >= 100 ? 'bar-ok' : ($percent > 0 ? 'bar-warn' : 'bar-none') }}"><span style="width: {{ min($percent, 100) }}%"></span></div>
                                            <b style="font-size:11px">{{ $percent }}%</b>
                                        </div>
                                    </td>
                                    <td class="ctr">
                                        @if ($percent > 100)
                                            <span class="stbadge st-excess"><i class="fa fa-exclamation-circle"></i> Excess</span>
                                        @elseif ($percent == 100)
                                            <span class="stbadge st-full"><i class="fa fa-check-circle"></i> Full</span>
                                        @elseif ($percent > 0)
                                            <span class="stbadge st-part"><i class="fa fa-clock-o"></i> Partial</span>
                                        @else
                                            <span class="stbadge st-zero"><i class="fa fa-minus-circle"></i> None</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2">Total</td>
                                <td class="num">{{ number_format($totalQty, 0) }}</td>
                                <td class="num" style="color:var(--ok)">{{ number_format($totalReceived, 0) }}</td>
                                <td class="num" style="color: {{ $totalQty - $totalReceived > 0 ? 'var(--bad)' : 'inherit' }}">{{ number_format($totalQty - $totalReceived, 0) }}</td>
                                <td></td><td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        @endif

        {{-- ===================== GRN ===================== --}}
        @if ($hasGrn)
            <div class="l-sec l-card">
                <div class="l-head">
                    <div class="l-ic t-ok"><i class="fa fa-cubes"></i></div>
                    <div style="flex:1"><div class="l-title">Goods Received (GRN)</div></div>
                    <span class="l-pill pill-ok">{{ $order->grns->count() }} GRN{{ $order->grns->count() > 1 ? 's' : '' }}</span>
                </div>
                @foreach ($order->grns as $grn)
                    <div class="grn">
                        <div class="grn-head">
                            <div>
                                <a href="{{ route('grn::view', $grn->id) }}"><i class="fa fa-file-text-o"></i> {{ $grn->grn_no }}</a>
                                <span class="gdate"><i class="demo-psi-calendar-4"></i> {{ \Carbon\Carbon::parse($grn->date)->format('d M Y') }}</span>
                            </div>
                            <span class="stbadge {{ $grn->status->value === 'accepted' ? 'st-full' : ($grn->status->value === 'pending' ? 'st-part' : 'st-excess') }}">
                                {{ $grn->status->label() }}
                            </span>
                        </div>
                        <div class="l-tblwrap">
                            <table class="l-tbl">
                                <thead>
                                    <tr>
                                        <th class="ctr" style="width:46px">#</th>
                                        <th>Product</th>
                                        <th class="num">Received Qty</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($grn->items as $grnIndex => $grnItem)
                                        <tr>
                                            <td class="ctr"><span class="idx">{{ $grnIndex + 1 }}</span></td>
                                            <td><div class="pname">{{ $grnItem->product?->name }}</div></td>
                                            <td class="num" style="color:var(--ok); font-weight:800">{{ $grnItem->quantity }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- ===================== APPROVE / REJECT ===================== --}}
        @if ($order->status == LocalPurchaseOrderStatus::CONFIRMED && $is_approvable)
            <div class="lpox-action tone-warn">
                <div class="a-body">
                    <div class="a-head">
                        <div class="a-ic"><i class="fa fa-gavel"></i></div>
                        <div><div class="a-title">Take Action</div><div class="a-sub">Approve or reject this local purchase order</div></div>
                    </div>
                    <div class="lpox-lbl"><i class="demo-psi-speech-bubble-3"></i> Remarks</div>
                    <textarea class="lpox-ta" rows="3" wire:model="remarks" placeholder="Enter remarks (required for rejection)"></textarea>
                    <div class="a-actions">
                        <button type="button" class="l-btn l-btn-bad" wire:click="reject" wire:confirm="Are you sure you want to reject this order?">
                            <i class="fa fa-times"></i> Reject
                        </button>
                        <button type="button" class="l-btn l-btn-ok" wire:click="approve" wire:confirm="Are you sure you want to approve this order?">
                            <i class="fa fa-check"></i> Approve
                        </button>
                    </div>
                </div>
            </div>
        @endif

        {{-- ===================== CONFIRM ===================== --}}
        @if ($order->status == LocalPurchaseOrderStatus::PENDING && $is_confirmable)
            <div class="lpox-action tone-primary">
                <div class="a-body">
                    <div class="a-head">
                        <div class="a-ic"><i class="fa fa-check-square-o"></i></div>
                        <div><div class="a-title">Confirm Order</div><div class="a-sub">Confirm this approved local purchase order</div></div>
                    </div>
                    <div class="lpox-lbl"><i class="demo-psi-speech-bubble-3"></i> Confirmation Note</div>
                    <textarea class="lpox-ta" rows="3" wire:model="confirm_remarks" placeholder="Enter a confirmation note (optional)"></textarea>
                    <div class="a-actions">
                        <button type="button" class="l-btn l-btn-acc" wire:click="confirm" wire:confirm="Are you sure you want to confirm this order?">
                            <i class="fa fa-check-square-o"></i> Confirm
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

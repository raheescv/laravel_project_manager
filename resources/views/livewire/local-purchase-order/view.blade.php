<div>
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
                font-size: 13px; line-height: 1.5;
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

            /* HERO */
            .lpox-hero{ position:relative; border-radius:18px; overflow:hidden; margin-bottom:14px; box-shadow:var(--shadow-lg);
                background:
                    radial-gradient(120% 165% at 100% 0, color-mix(in srgb, var(--acc) 26%, transparent), transparent 55%),
                    linear-gradient(125deg, var(--acc-deep), var(--acc-d)); }
            .lpox-hero .glow{ position:absolute; right:-60px; top:-90px; width:300px; height:300px; border-radius:50%;
                background:radial-gradient(circle, rgba(255,255,255,.16), transparent 65%); pointer-events:none; }
            .lpox-hero-inner{ position:relative; display:flex; align-items:center; gap:18px; padding:18px 22px; flex-wrap:wrap; }
            .lpox-hero .doc-ic{ width:52px; height:52px; border-radius:14px; flex:0 0 auto; background:rgba(255,255,255,.14);
                border:1px solid rgba(255,255,255,.22); display:flex; align-items:center; justify-content:center; font-size:22px; color:#fff;
                box-shadow:inset 0 1px 0 rgba(255,255,255,.25); }
            .lpox-hero .h-main{ flex:1; min-width:210px; color:#fff; }
            .lpox-hero .h-eyebrow{ font-size:10.5px; font-weight:700; letter-spacing:2px; text-transform:uppercase; color:rgba(255,255,255,.72); }
            .lpox-hero .h-ref{ font-size:21px; font-weight:800; letter-spacing:.3px; line-height:1.15; margin-top:2px; }
            .lpox-hero .h-meta{ display:flex; gap:16px; flex-wrap:wrap; margin-top:7px; font-size:12px; color:rgba(255,255,255,.86); }
            .lpox-hero .h-meta i{ opacity:.82; margin-right:5px; }
            .lpox-hero .h-right{ display:flex; flex-direction:column; align-items:flex-end; gap:10px; }
            .lpox-hero .status-pill{ background:rgba(255,255,255,.16); color:#fff; border:1px solid rgba(255,255,255,.3);
                padding:6px 14px; border-radius:999px; font-size:12px; font-weight:750; letter-spacing:.4px; display:inline-flex; align-items:center; gap:7px; white-space:nowrap; }
            .lpox-hero .status-pill .dot{ width:8px; height:8px; border-radius:50%; box-shadow:0 0 0 3px rgba(255,255,255,.18); }
            .lpox-hero .btn-print{ background:#fff; color:var(--acc-deep); border:0; padding:8px 16px; border-radius:10px; font-size:12.5px; font-weight:700;
                cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:7px; box-shadow:0 4px 14px rgba(0,0,0,.18); transition:transform .12s; }
            .lpox-hero .btn-print:hover{ transform:translateY(-1px); color:var(--acc-deep); }

            /* STAT STRIP */
            .lpox-stats{ display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:10px; margin-bottom:14px; }
            .lpox-stat{ background:var(--surface); border:1px solid var(--line); border-radius:13px; padding:12px 14px; box-shadow:var(--shadow); position:relative; overflow:hidden; }
            .lpox-stat::before{ content:""; position:absolute; left:0; top:0; bottom:0; width:3px; background:var(--acc); opacity:.85; }
            .lpox-stat .s-k{ font-size:10px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; color:var(--muted); display:flex; align-items:center; gap:6px; }
            .lpox-stat .s-k i{ color:var(--acc); }
            .lpox-stat .s-v{ font-size:18px; font-weight:800; color:var(--ink); margin-top:4px; letter-spacing:.2px; }
            .lpox-stat .s-v small{ font-size:11px; font-weight:600; color:var(--muted); }

            /* INFO PANELS */
            .lpox-grid{ display:grid; grid-template-columns:repeat(2,1fr); gap:14px; margin-bottom:14px; }
            .lpox-grid.one{ grid-template-columns:1fr; }
            .lpox .kv-grid{ display:grid; grid-template-columns:1fr 1fr; gap:8px; padding:14px 16px; }
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
        </div>

        {{-- ===================== STAT STRIP ===================== --}}
        <div class="lpox-stats">
            <div class="lpox-stat">
                <div class="s-k"><i class="demo-psi-basket-coins"></i> Line Items</div>
                <div class="s-v">{{ $itemCount }} <small>{{ \Illuminate\Support\Str::plural('product', $itemCount) }}</small></div>
            </div>
            <div class="lpox-stat">
                <div class="s-k"><i class="fa fa-sort-amount-asc"></i> Total Qty</div>
                <div class="s-v">{{ number_format($totalQty, 0) }} <small>units</small></div>
            </div>
            <div class="lpox-stat">
                <div class="s-k"><i class="demo-psi-coin"></i> Order Value</div>
                <div class="s-v">{{ number_format($subtotal, 2) }}</div>
            </div>
            @if ($hasGrn)
                <div class="lpox-stat">
                    <div class="s-k"><i class="fa fa-tasks"></i> Fulfilled</div>
                    <div class="s-v">{{ $overallPercent }}<small>%</small></div>
                </div>
            @endif
        </div>

        {{-- ===================== INFO PANELS ===================== --}}
        <div class="lpox-grid {{ $order->status === LocalPurchaseOrderStatus::PENDING ? 'one' : '' }}">
            {{-- Order Information --}}
            <div class="l-card">
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

            {{-- Decision Details --}}
            @if (in_array($order->status, [LocalPurchaseOrderStatus::APPROVED, LocalPurchaseOrderStatus::REJECTED]))
                @php $decTone = $order->status === LocalPurchaseOrderStatus::REJECTED ? 'bad' : 'ok'; @endphp
                <div class="l-card tone-{{ $decTone }}">
                    <div class="l-head">
                        <div class="l-ic t-{{ $decTone }}"><i class="{{ $statusIcon }}"></i></div>
                        <div>
                            <div class="l-title">{{ $order->status === LocalPurchaseOrderStatus::REJECTED ? 'Rejection Details' : 'Decision Details' }}</div>
                            <div class="l-sub">Approval / rejection record</div>
                        </div>
                    </div>
                    <div class="kv-grid">
                        <div class="kv"><div class="kk"><i class="demo-psi-male"></i>Action By</div><div class="vv">{{ $order->decisionMaker?->name ?? '-' }}</div></div>
                        <div class="kv"><div class="kk"><i class="demo-psi-calendar-4"></i>Action On</div><div class="vv">{{ $order->decision_at?->format('d M Y, h:i A') ?? '-' }}</div></div>
                        @if ($order->decision_note)
                            <div class="kv full"><div class="kk"><i class="demo-psi-speech-bubble-3"></i>Remarks</div><div class="vv">{{ $order->decision_note }}</div></div>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        {{-- Confirmation Details --}}
        @if ($order->status !== LocalPurchaseOrderStatus::PENDING)
            <div class="lpox-grid one">
                <div class="l-card tone-info">
                    <div class="l-head">
                        <div class="l-ic t-info"><i class="fa fa-check-square-o"></i></div>
                        <div><div class="l-title">Confirmation Details</div><div class="l-sub">Pre-approval confirmation record</div></div>
                    </div>
                    <div class="kv-grid">
                        <div class="kv"><div class="kk"><i class="demo-psi-male"></i>Confirmed By</div><div class="vv">{{ $order->confirmedBy?->name ?? '-' }}</div></div>
                        <div class="kv"><div class="kk"><i class="demo-psi-calendar-4"></i>Confirmed On</div><div class="vv">{{ $order->confirmation_at?->format('d M Y, h:i A') ?? '-' }}</div></div>
                        @if ($order->confirmation_note)
                            <div class="kv full"><div class="kk"><i class="demo-psi-speech-bubble-3"></i>Confirmation Note</div><div class="vv">{{ $order->confirmation_note }}</div></div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

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
                        <span class="l-pill pill-acc">{{ count($terms) }} {{ \Illuminate\Support\Str::plural('term', count($terms)) }}</span>
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

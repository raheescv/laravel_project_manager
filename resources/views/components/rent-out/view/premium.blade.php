{{--
    ╔══════════════════════════════════════════════════════════════════════╗
    ║  RentOut View — "Premium Hero" design system                         ║
    ║                                                                      ║
    ║  Everything is scoped under .rvx so it only affects the RentOut      ║
    ║  view page (the main panels AND every management tab rendered inside ║
    ║  it) — the rest of the app and the shared booking-view are untouched.║
    ║                                                                      ║
    ║  Colour derives from the active SETTINGS THEME (--bs-primary/--bs-*  ║
    ║  subtle tokens) so it tracks the scheme + dark mode. Typography is    ║
    ║  scaled to match the app's dense sizing; the whole system honours     ║
    ║  --rvx-fz (base font size) so it can be nudged from one place.        ║
    ╚══════════════════════════════════════════════════════════════════════╝
--}}
@once
    <style>
        .rvx{
            /* ── Brand: single source → settings theme primary ───────────── */
            --brand: var(--bs-primary);
            --brand-rgb: var(--bs-primary-rgb);
            --brand-600: color-mix(in srgb, var(--bs-primary), #000 12%);
            --brand-700: color-mix(in srgb, var(--bs-primary), #000 28%);
            --brand-400: color-mix(in srgb, var(--bs-primary), #fff 22%);

            --hero-1: color-mix(in srgb, var(--bs-primary), #000 42%);
            --hero-2: color-mix(in srgb, var(--bs-primary), #000 4%);
            --hero-3: color-mix(in srgb, var(--bs-primary), #fff 10%);

            /* Neutral surfaces pinned to a crisp light ramp (white cards on a soft page),
               independent of the app's greyer body tokens. Dark ramp set in the dark block. */
            --surface:   #ffffff;
            --surface-2: #f5f7fa;
            --surface-3: #eceff4;
            --border:        #e4e8ee;
            --border-strong: #d3d9e1;
            --text:   var(--bs-emphasis-color);
            --text-2: var(--bs-secondary-color);
            --text-3: var(--bs-tertiary-color);

            --info: var(--bs-info);       --info-bg: var(--bs-info-bg-subtle);       --info-rgb: var(--bs-info-rgb);
            --success: var(--bs-success); --success-bg: var(--bs-success-bg-subtle); --success-rgb: var(--bs-success-rgb);
            --danger: var(--bs-danger);   --danger-bg: var(--bs-danger-bg-subtle);   --danger-rgb: var(--bs-danger-rgb);
            --warning: var(--bs-warning); --warning-bg: var(--bs-warning-bg-subtle); --warning-rgb: var(--bs-warning-rgb);
            --purple: #7c3aed;            --purple-bg: color-mix(in srgb, #7c3aed, transparent 88%); --purple-rgb: 124,58,237;

            --rvx-fz: 12.5px;            /* base font size — matches the app's dense pages */
            --r-sm: 7px; --r-md: 10px; --r-lg: 13px; --r-xl: 18px;
            --shadow-sm: 0 1px 2px rgba(16,24,40,.05), 0 1px 3px rgba(16,24,40,.05);
            --shadow-md: 0 4px 14px -4px rgba(16,24,40,.12), 0 2px 6px -2px rgba(16,24,40,.07);
            --shadow-lg: 0 16px 38px -16px rgba(16,24,40,.28), 0 7px 16px -10px rgba(16,24,40,.16);
            --shadow-glass: 0 10px 28px -12px rgba(var(--brand-rgb), .42), 0 4px 12px -6px rgba(16,24,40,.18);
        }
        [data-bs-theme="dark"] .rvx{
            --hero-1: color-mix(in srgb, var(--bs-primary), #000 64%);
            --hero-2: color-mix(in srgb, var(--bs-primary), #000 48%);
            --hero-3: color-mix(in srgb, var(--bs-primary), #000 30%);
            /* Dark surface ramp — panels sit slightly above the app's dark page */
            --surface:   #272d34;
            --surface-2: #2e353d;
            --surface-3: #353d46;
            --border:        #3a424c;
            --border-strong: #4a535e;
            --shadow-sm: 0 1px 2px rgba(0,0,0,.4);
            --shadow-md: 0 6px 18px -6px rgba(0,0,0,.55);
            --shadow-lg: 0 16px 38px -16px rgba(0,0,0,.6), 0 7px 16px -10px rgba(0,0,0,.5);
        }

        .rvx{ color: var(--text); font-size: var(--rvx-fz); line-height: 1.45; -webkit-font-smoothing: antialiased; letter-spacing: -0.004em; }
        .rvx *{ scrollbar-width: thin; }

        /* ═══════════════════════════  HERO  ═══════════════════════════ */
        .rvx .hero{
            position: relative; border-radius: var(--r-xl); color:#fff; overflow:hidden; isolation:isolate;
            padding: 20px clamp(16px,2.4vw,30px) 74px;
            background:
                radial-gradient(120% 160% at 12% -10%, rgba(255,255,255,.20), transparent 50%),
                radial-gradient(90% 140% at 100% 0%, var(--hero-3), transparent 55%),
                linear-gradient(118deg, var(--hero-1) 0%, var(--hero-2) 58%, var(--hero-3) 130%);
            box-shadow: var(--shadow-lg);
        }
        .rvx .hero::after{
            content:""; position:absolute; inset:0; z-index:-1; opacity:.5;
            background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,.10) 1px, transparent 0);
            background-size: 22px 22px;
            -webkit-mask-image: linear-gradient(180deg,#000,transparent 70%); mask-image: linear-gradient(180deg,#000,transparent 70%);
        }
        .rvx .hero-glow{ position:absolute; z-index:-1; border-radius:50%; filter:blur(34px); }
        .rvx .hero-glow.a{ width:240px; height:240px; top:-80px; right:8%; background:rgba(255,255,255,.30); opacity:.55; }
        .rvx .hero-glow.b{ width:190px; height:190px; bottom:-70px; left:-30px; background:var(--brand-400); opacity:.4; }

        .rvx .crumb{ font-size:11px; color:rgba(255,255,255,.78); display:flex; align-items:center; gap:6px; flex-wrap:wrap; }
        .rvx .crumb a{ color:rgba(255,255,255,.78); text-decoration:none; }
        .rvx .crumb a:hover{ color:#fff; }
        .rvx .crumb .sep{ opacity:.55; }
        .rvx .crumb .here{ color:#fff; font-weight:600; }

        .rvx .pill{ display:inline-flex; align-items:center; gap:5px; font-size:9.5px; font-weight:700; letter-spacing:.07em; text-transform:uppercase; padding:4px 9px; border-radius:999px; line-height:1; }
        .rvx .pill-rental{ background:rgba(255,255,255,.16); color:#fff; border:1px solid rgba(255,255,255,.28); backdrop-filter:blur(6px); }
        .rvx .pill-status{ background:rgba(255,255,255,.95); color:var(--brand-700); }
        .rvx .pill-status .dot{ width:6px; height:6px; border-radius:50%; background:var(--success); box-shadow:0 0 0 3px rgba(var(--success-rgb),.22); }

        .rvx .hero-title{ font-size:clamp(20px,2.5vw,29px); font-weight:800; line-height:1.05; letter-spacing:-0.022em; margin:0; text-shadow:0 1px 16px rgba(0,0,0,.18); }
        .rvx .hero-title .hash{ color:rgba(255,255,255,.62); font-weight:700; }
        .rvx .hero-meta{ color:rgba(255,255,255,.86); font-size:11.5px; font-weight:500; }

        .rvx .hero-actions .btn{ font-weight:600; font-size:11.5px; padding:6px 11px; border-width:1px; border-radius:9px; display:inline-flex; align-items:center; gap:6px; transition:transform .15s ease, box-shadow .15s ease, background .15s ease; }
        .rvx .hero-actions .btn:active{ transform:translateY(1px); }
        .rvx .btn-glass{ background:rgba(255,255,255,.14); border:1px solid rgba(255,255,255,.28); color:#fff; backdrop-filter:blur(8px); }
        .rvx .btn-glass:hover{ background:rgba(255,255,255,.24); color:#fff; box-shadow:0 6px 18px -8px rgba(0,0,0,.4); }
        .rvx .btn-glass-warn{ background:rgba(255,255,255,.12); border:1px solid rgba(var(--warning-rgb),.55); color:#ffe7b8; }
        .rvx .btn-glass-warn:hover{ background:rgba(var(--warning-rgb),.92); color:#3a2400; border-color:transparent; }
        .rvx .btn-on-hero{ background:#fff; color:var(--brand-700); border:1px solid #fff; }
        .rvx .btn-on-hero:hover{ background:#f3f6ff; color:var(--brand-700); box-shadow:0 8px 22px -8px rgba(0,0,0,.45); }

        .rvx .hero-prog-label{ font-size:10.5px; color:rgba(255,255,255,.82); }
        .rvx .hero-prog-val{ font-weight:800; font-size:12px; color:#fff; }
        .rvx .hero-track{ height:7px; border-radius:999px; background:rgba(255,255,255,.20); overflow:hidden; box-shadow:inset 0 1px 2px rgba(0,0,0,.25); }
        .rvx .hero-fill{ height:100%; width:0%; border-radius:999px; background:linear-gradient(90deg,#fff,rgba(255,255,255,.7)); transition:width 1.1s cubic-bezier(.22,1,.36,1); }
        .rvx .hero-fill.empty{ width:4px; min-width:4px; background:rgba(255,255,255,.6); }
        .rvx .theme-toggle{ width:34px; height:34px; border-radius:9px; border:1px solid rgba(255,255,255,.28); background:rgba(255,255,255,.14); color:#fff; backdrop-filter:blur(8px); display:inline-flex; align-items:center; justify-content:center; }
        .rvx .theme-toggle:hover{ background:rgba(255,255,255,.26); }

        /* ═══════════════════════════  KPI CARDS  ═══════════════════════════ */
        .rvx .kpi-row{ margin-top:-54px; position:relative; z-index:5; }
        .rvx .kpi{ background:var(--surface); border:1px solid var(--border); border-radius:var(--r-lg); padding:13px 15px; box-shadow:var(--shadow-md); height:100%; position:relative; overflow:hidden; transition:transform .2s cubic-bezier(.22,1,.36,1), box-shadow .2s ease, border-color .2s ease; }
        .rvx .kpi::before{ content:""; position:absolute; left:0; top:0; bottom:0; width:3px; background:var(--accent); opacity:.9; }
        .rvx .kpi:hover{ transform:translateY(-3px); box-shadow:var(--shadow-lg); border-color:var(--border-strong); }
        .rvx .kpi .ic{ width:36px; height:36px; border-radius:10px; display:inline-flex; align-items:center; justify-content:center; font-size:15px; color:var(--accent); background:var(--accent-bg); }
        .rvx .kpi-label{ font-size:10px; font-weight:700; letter-spacing:.05em; text-transform:uppercase; color:var(--text-3); }
        .rvx .kpi-value{ font-size:20px; font-weight:800; letter-spacing:-0.02em; line-height:1.05; color:var(--text); }
        .rvx .kpi-sub{ font-size:10.5px; color:var(--text-2); font-weight:500; }
        .rvx .kpi.k-info{ --accent:var(--info); --accent-bg:var(--info-bg); }
        .rvx .kpi.k-purple{ --accent:var(--purple); --accent-bg:var(--purple-bg); }
        .rvx .kpi.k-success{ --accent:var(--success); --accent-bg:var(--success-bg); }
        .rvx .kpi.k-danger{ --accent:var(--danger); --accent-bg:var(--danger-bg); }

        /* ═══════════════════════════  PANELS  ═══════════════════════════ */
        .rvx .panel{ background:var(--surface); border:1px solid var(--border); border-radius:var(--r-lg); box-shadow:var(--shadow-sm); }
        .rvx .panel-pad{ padding:clamp(13px,1.6vw,17px); }
        .rvx .panel-head{ display:flex; align-items:center; gap:9px; padding:12px 15px; border-bottom:1px solid var(--border); }
        .rvx .panel-head .ph-ic{ width:29px; height:29px; border-radius:8px; display:inline-flex; align-items:center; justify-content:center; background:rgba(var(--brand-rgb),.10); color:var(--brand-600); font-size:13px; flex:0 0 auto; }
        .rvx .panel-title{ font-size:12.5px; font-weight:700; letter-spacing:-0.01em; margin:0; color:var(--text); }
        .rvx .panel-sub{ font-size:10.5px; color:var(--text-3); margin:0; }
        .rvx .section-eyebrow{ font-size:9.5px; font-weight:700; letter-spacing:.12em; text-transform:uppercase; color:var(--brand-600); }
        .rvx .section-h2{ font-size:15px; font-weight:800; letter-spacing:-0.02em; margin:0; color:var(--text); }

        /* Definition rows */
        .rvx .dl{ display:grid; grid-template-columns:auto 1fr; gap:0; }
        .rvx .dl .dt{ font-size:11px; color:var(--text-2); padding:7px 0; display:flex; align-items:center; gap:7px; }
        .rvx .dl .dt i{ color:var(--text-3); width:14px; text-align:center; }
        .rvx .dl .dd{ font-size:11.5px; font-weight:600; color:var(--text); padding:7px 0; text-align:right; display:flex; align-items:center; justify-content:flex-end; gap:6px; }
        .rvx .dl .row-line + .row-line .dt, .rvx .dl .row-line + .row-line .dd{ border-top:1px dashed var(--border); }
        .rvx .dl .dd .muted{ color:var(--text-3); font-weight:500; }

        /* Chips */
        .rvx .chip{ display:inline-flex; align-items:center; gap:4px; font-size:10px; font-weight:700; padding:2px 8px; border-radius:999px; line-height:1.4; white-space:nowrap; }
        .rvx .chip-soft{ background:var(--surface-3); color:var(--text-2); border:1px solid var(--border); }
        .rvx .chip-success{ background:var(--success-bg); color:var(--success); }
        .rvx .chip-danger{ background:var(--danger-bg); color:var(--danger); }
        .rvx .chip-info{ background:var(--info-bg); color:var(--info); }
        .rvx .chip-warning{ background:var(--warning-bg); color:var(--warning); }
        .rvx .chip-occupied{ background:var(--success-bg); color:var(--success); }
        .rvx .chip-occupied .dot{ width:5px; height:5px; border-radius:50%; background:var(--success); }

        .rvx .btn-mini{ font-size:10px; font-weight:700; padding:2px 8px; border-radius:6px; line-height:1.4; border:1px dashed var(--brand-400); color:var(--brand-600); background:transparent; transition:background .15s ease, color .15s ease; }
        .rvx .btn-mini:hover{ background:rgba(var(--brand-rgb),.10); color:var(--brand-700); }

        /* Customer block */
        .rvx .cust{ display:flex; align-items:center; gap:11px; background:var(--surface-2); border:1px solid var(--border); border-radius:var(--r-md); padding:11px 13px; }
        .rvx .avatar{ width:38px; height:38px; border-radius:11px; flex:0 0 auto; display:inline-flex; align-items:center; justify-content:center; font-weight:800; font-size:14px; color:#fff; background:linear-gradient(135deg, var(--brand) 0%, var(--hero-3) 100%); box-shadow:var(--shadow-glass); }
        .rvx .cust .nm{ font-weight:700; font-size:12.5px; color:var(--text); }
        .rvx .cust .sub{ font-size:10.5px; color:var(--text-2); }

        /* Financial mini cells */
        .rvx .fin-grid{ display:grid; grid-template-columns:repeat(3,1fr); gap:8px; }
        .rvx .fin-grid-2{ grid-template-columns:repeat(2,1fr); }
        .rvx .fin{ background:var(--surface-2); border:1px solid var(--border); border-radius:var(--r-md); padding:9px 11px; text-align:left; }
        .rvx .fin .lab{ font-size:9.5px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:var(--text-3); }
        .rvx .fin .val{ font-size:14.5px; font-weight:800; letter-spacing:-0.02em; color:var(--text); }
        .rvx .fin.is-paid .val{ color:var(--success); }

        /* Big rent display */
        .rvx .rent-hero{ background:linear-gradient(120deg, rgba(var(--brand-rgb),.10), rgba(var(--purple-rgb),.07)); border:1px solid var(--border); border-radius:var(--r-md); padding:12px 14px; display:flex; align-items:flex-end; justify-content:space-between; gap:12px; }
        .rvx .rent-hero .amount{ font-size:22px; font-weight:800; letter-spacing:-0.03em; line-height:1; color:var(--text); }
        .rvx .rent-hero .amount .cur{ font-size:12px; font-weight:700; color:var(--text-2); vertical-align:5px; margin-right:2px; }
        .rvx .rent-hero .per{ font-size:10.5px; color:var(--text-2); font-weight:600; }

        /* Collection breakdown mini table */
        .rvx .mini-table{ width:100%; font-size:11.5px; border-collapse:separate; border-spacing:0; }
        .rvx .mini-table th{ font-size:9.5px; text-transform:uppercase; letter-spacing:.06em; color:var(--text-3); font-weight:700; padding:5px 9px; text-align:left; }
        .rvx .mini-table td{ padding:7px 9px; border-top:1px solid var(--border); font-weight:600; color:var(--text); }
        .rvx .mini-table tr:first-child td{ border-top:none; }
        .rvx .mini-table .t-paid{ color:var(--success); }
        .rvx .mini-table .t-pend{ color:var(--danger); }
        .rvx .mini-table .ico-cell{ display:flex; align-items:center; gap:7px; }
        .rvx .mini-table .ico-cell .b{ width:23px; height:23px; border-radius:6px; display:inline-flex; align-items:center; justify-content:center; font-size:11px; }

        .rvx .alert-pending{ background:var(--warning-bg); border:1px solid rgba(var(--warning-rgb),.25); border-radius:var(--r-md); color:var(--warning); font-size:10.5px; font-weight:700; padding:8px 11px; }

        /* ═══════════════  MANAGEMENT WORKSPACE (hooks added to shared partial)  ═══════════════ */
        .rvx .rvx-mgmt-card{ border:1px solid var(--border) !important; border-radius:var(--r-lg) !important; box-shadow:var(--shadow-md) !important; overflow:hidden; }
        .rvx .rvx-mgmt-head{ background:linear-gradient(110deg, rgba(var(--brand-rgb),.10), var(--surface) 55%) !important; border-bottom:1px solid var(--border) !important; padding:11px 15px !important; }
        .rvx .rvx-mgmt-head-ic{ width:27px !important; height:27px !important; background:rgba(var(--brand-rgb),.14) !important; }
        .rvx .rvx-mgmt-head-ic i{ color:var(--brand-600) !important; font-size:.72rem !important; }
        .rvx .rvx-mgmt-head span{ font-size:.82rem !important; font-weight:700 !important; color:var(--text) !important; letter-spacing:-.01em; }

        /* Tab bar */
        .rvx .rvx-mgmt-tabbar{ background:var(--surface-2) !important; border-bottom:1px solid var(--border) !important; padding:10px 12px !important; }
        .rvx .rvx-mgmt-tabbar > div{ gap:7px !important; }

        /* Tab pills — bordered + elevated so inactive tabs clearly read as buttons */
        .rvx .mgmt-tab-btn{ background:var(--surface); border:1px solid var(--border); color:var(--text-2); padding:6px 12px; border-radius:8px; font-size:11.5px; font-weight:600; line-height:1; display:inline-flex; align-items:center; gap:6px; box-shadow:var(--shadow-sm); transition:transform .12s ease, background .15s ease, color .15s ease, border-color .15s ease, box-shadow .15s ease; }
        .rvx .mgmt-tab-btn i{ opacity:.7; }
        .rvx .mgmt-tab-btn:hover{ border-color:var(--brand-400); color:var(--brand-700); background:rgba(var(--brand-rgb),.07); transform:translateY(-1px); }
        .rvx .mgmt-tab-btn.active{ background:var(--brand) !important; border-color:var(--brand); color:#fff !important; box-shadow:0 7px 16px -7px rgba(var(--brand-rgb),.6); transform:translateY(-1px); }
        .rvx .mgmt-tab-btn.active i{ opacity:1; }

        /* Tab content */
        .rvx .rvx-mgmt-content{ padding:15px !important; }
        .rvx .rvx-mgmt-content .table-responsive{ border:1px solid var(--border); border-radius:var(--r-md); }
        .rvx .rvx-mgmt-content .table thead th{ background:linear-gradient(180deg, var(--surface-2), var(--surface-3)) !important; border-bottom:2px solid rgba(var(--brand-rgb),.22) !important; }
        /* Filter rows (label + select/date) become a soft inset bar */
        .rvx .rvx-mgmt-content .row.align-items-end{ background:var(--surface-2); border:1px solid var(--border); border-radius:var(--r-md); padding:11px 12px 12px; margin-inline:0; }
        .rvx .rvx-mgmt-content .row.align-items-end > [class*="col"]{ padding-inline:6px; }

        /* ═══════════════════════  BOOTSTRAP RE-SKIN (tabs inherit premium)  ═══════════════════════ */
        .rvx .card{ border:1px solid var(--border); border-radius:var(--r-lg); background:var(--surface); box-shadow:var(--shadow-sm); }
        .rvx .card-header{ background:var(--surface-2); border-bottom:1px solid var(--border); color:var(--text); }

        /* Tables */
        .rvx .table{ --bs-table-bg:transparent; --bs-table-color:var(--text); margin:0; font-size:12px; border-color:var(--border); color:var(--text); }
        .rvx .table > :not(caption) > * > *{ padding:.45rem .6rem; }
        .rvx .table thead th{ text-transform:uppercase; font-size:9.5px; letter-spacing:.05em; font-weight:700; color:var(--text-3) !important; background:var(--surface-2) !important; border-bottom:1px solid var(--border); white-space:nowrap; }
        .rvx .table tbody td{ border-color:var(--border); vertical-align:middle; }
        .rvx .table.table-hover tbody tr:hover > *{ background:var(--surface-2); }
        .rvx .table tfoot td{ background:var(--surface-2); border-color:var(--border); color:var(--text); }
        .rvx .table-success{ --bs-table-bg:var(--success-bg); --bs-table-color:var(--text); }
        .rvx .table-danger{ --bs-table-bg:var(--danger-bg); --bs-table-color:var(--text); }
        .rvx .table-info{ --bs-table-bg:var(--info-bg); --bs-table-color:var(--text); }
        .rvx .table-warning{ --bs-table-bg:var(--warning-bg); --bs-table-color:var(--text); }
        .rvx .table-light{ --bs-table-bg:var(--surface-2); --bs-table-color:var(--text); }

        /* Buttons */
        .rvx .btn{ border-radius:8px; font-weight:600; }
        .rvx .btn-sm{ border-radius:7px; }
        .rvx .btn-primary{ --bs-btn-bg:var(--brand); --bs-btn-border-color:var(--brand); --bs-btn-hover-bg:var(--brand-600); --bs-btn-hover-border-color:var(--brand-600); --bs-btn-active-bg:var(--brand-700); --bs-btn-active-border-color:var(--brand-700); }
        .rvx .btn-outline-primary{ --bs-btn-color:var(--brand); --bs-btn-border-color:color-mix(in srgb, var(--brand), transparent 55%); --bs-btn-hover-bg:var(--brand); --bs-btn-hover-border-color:var(--brand); --bs-btn-active-bg:var(--brand-700); }
        .rvx .btn-light{ --bs-btn-bg:var(--surface-2); --bs-btn-border-color:var(--border); --bs-btn-color:var(--text); --bs-btn-hover-bg:var(--surface-3); --bs-btn-hover-border-color:var(--border-strong); --bs-btn-hover-color:var(--text); }

        /* Forms */
        .rvx .form-control, .rvx .form-select{ border-radius:8px; border-color:var(--border); background:var(--surface); color:var(--text); }
        .rvx .form-control:focus, .rvx .form-select:focus{ border-color:var(--brand-400); box-shadow:0 0 0 .2rem rgba(var(--brand-rgb),.18); }
        .rvx .form-control::placeholder{ color:var(--text-3); }
        .rvx .form-label{ font-size:11px; font-weight:600; color:var(--text-2); margin-bottom:.25rem; }
        .rvx .form-check-input{ border-color:var(--border-strong); }
        .rvx .form-check-input:checked{ background-color:var(--brand); border-color:var(--brand); }
        .rvx .form-check-input:focus{ box-shadow:0 0 0 .2rem rgba(var(--brand-rgb),.18); border-color:var(--brand-400); }
        .rvx .input-group-text{ border-radius:8px; background:var(--surface-2); border-color:var(--border); color:var(--text-2); }

        /* Badges / utilities that must survive dark mode */
        .rvx .badge{ border-radius:999px; font-weight:700; }
        .rvx .badge.bg-light{ background:var(--surface-3) !important; color:var(--text-2) !important; border:1px solid var(--border); }
        .rvx .bg-light{ background:var(--surface-2) !important; }
        .rvx .text-dark{ color:var(--text) !important; }
        .rvx .text-muted{ color:var(--text-2) !important; }
        .rvx .text-success{ color:var(--success) !important; }
        .rvx .text-danger{ color:var(--danger) !important; }
        .rvx .border{ border-color:var(--border) !important; }

        /* Alerts, nav, pagination inside tabs */
        .rvx .alert{ border-radius:var(--r-md); }
        .rvx .nav-tabs{ border-bottom-color:var(--border); }
        .rvx .nav-tabs .nav-link{ color:var(--text-2); border-radius:8px 8px 0 0; }
        .rvx .nav-tabs .nav-link.active{ color:var(--brand); background:var(--surface); border-color:var(--border) var(--border) var(--surface); }
        .rvx .nav-pills .nav-link.active{ background:var(--brand); }
        .rvx .page-link{ color:var(--brand); border-radius:8px; }
        .rvx .page-item.active .page-link{ background:var(--brand); border-color:var(--brand); }

        /* Modals (SOA / Vacate) live inside .rvx */
        .rvx .modal-content{ border:1px solid var(--border); border-radius:var(--r-lg); overflow:hidden; }
        .rvx .rv-modal-header{ background:linear-gradient(135deg, var(--brand), var(--brand-600)); }
        .rvx .rv-modal-title{ font-size:.82rem; }

        /* ═══════════════════════════  RESPONSIVE  ═══════════════════════════ */
        /* Every data table scrolls horizontally on tablet/phone (override any inline overflow:visible). */
        @media (max-width: 1199.98px){
            .rvx .table-responsive{ overflow-x:auto !important; -webkit-overflow-scrolling:touch; }
            .rvx .table-responsive > .table{ min-width:720px; }   /* keep columns legible; scroll instead of crushing */
        }
        @media (max-width: 991.98px){
            .rvx .hero{ padding:18px 18px 70px; }
            .rvx .kpi-row{ margin-top:-50px; }
            .rvx .hero-actions{ justify-content:flex-start !important; }
        }
        @media (max-width: 767.98px){
            .rvx .hero{ padding:16px 14px 68px; }
            .rvx .hero-actions{ width:100%; }
            .rvx .hero-actions .btn{ flex:1 1 auto; justify-content:center; }
            .rvx .kpi-row{ margin-top:-48px; }
            .rvx .rvx-mgmt-content .row.align-items-end > [class*="col"]{ flex:0 0 100%; max-width:100%; }
            .rvx .rvx-mgmt-content .row.align-items-end > .col-auto{ flex:0 0 auto; max-width:none; }
            .rvx .rvx-mgmt-content{ padding:12px !important; }
            .rvx .rvx-mgmt-tabbar{ padding:9px !important; }
        }
        @media (max-width: 575.98px){
            .rvx .hero{ padding-bottom:66px; }
            .rvx .kpi-row{ margin-top:-46px; }
            .rvx .kpi{ padding:12px; }
            .rvx .fin{ padding:8px 7px; }
            .rvx .mgmt-tab-btn{ padding:6px 10px; }
        }
        /* keep KPIs 2-up on normal phones; only stack 1-up on very small screens */
        @media (max-width: 359.98px){
            .rvx .kpi-row .col-6{ flex:0 0 100%; max-width:100%; }
            .rvx .fin-grid{ grid-template-columns:1fr 1fr; }
        }
    </style>
@endonce

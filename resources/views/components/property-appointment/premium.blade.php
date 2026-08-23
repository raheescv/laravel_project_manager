{{--
    ╔══════════════════════════════════════════════════════════════════════╗
    ║  Appointment Scheduler — premium design system                           ║
    ║                                                                      ║
    ║  Everything is scoped under .apx so it only affects the appointment      ║
    ║  screens: the tab on the RentOut lease/sale view, the calendars,     ║
    ║  the appointments list, the Settings tab and the public appointment page.    ║
    ║                                                                      ║
    ║  Colour derives from the active SETTINGS THEME (--bs-primary) so it  ║
    ║  tracks the tenant's scheme and dark mode, exactly like .rvx. The    ║
    ║  public page has no app chrome, so it sets --bs-primary itself.      ║
    ╚══════════════════════════════════════════════════════════════════════╝
--}}
@once
    <style>
        .apx{
            --brand: var(--bs-primary);
            --brand-rgb: var(--bs-primary-rgb);
            --brand-600: color-mix(in srgb, var(--bs-primary), #000 12%);
            --brand-700: color-mix(in srgb, var(--bs-primary), #000 28%);
            --brand-400: color-mix(in srgb, var(--bs-primary), #fff 22%);

            --hero-1: color-mix(in srgb, var(--bs-primary), #000 42%);
            --hero-2: color-mix(in srgb, var(--bs-primary), #000 4%);
            --hero-3: color-mix(in srgb, var(--bs-primary), #fff 10%);

            --surface:   #ffffff;
            --surface-2: #f5f7fa;
            --surface-3: #eceff4;
            --border:        #e4e8ee;
            --border-strong: #d3d9e1;
            --text:   var(--bs-emphasis-color);
            --text-2: var(--bs-secondary-color);
            --text-3: var(--bs-tertiary-color);

            --success: var(--bs-success); --success-bg: var(--bs-success-bg-subtle); --success-rgb: var(--bs-success-rgb);
            --danger:  var(--bs-danger);  --danger-bg:  var(--bs-danger-bg-subtle);  --danger-rgb:  var(--bs-danger-rgb);
            --warning: var(--bs-warning); --warning-bg: var(--bs-warning-bg-subtle); --warning-rgb: var(--bs-warning-rgb);
            --info:    var(--bs-info);    --info-bg:    var(--bs-info-bg-subtle);    --info-rgb:    var(--bs-info-rgb);
            --purple:  #7c3aed;           --purple-bg: color-mix(in srgb, #7c3aed, transparent 88%);
            /* brand as TEXT: on a dark surface the raw theme colour can fall below
               contrast on 11px mono values, so dark mode lifts it toward white. */
            --brand-ink: var(--brand);

            --apx-fz: 12.5px;
            --r-sm: 7px; --r-md: 10px; --r-lg: 13px; --r-xl: 18px;
            --shadow-sm: 0 1px 2px rgba(16,24,40,.05), 0 1px 3px rgba(16,24,40,.05);
            --shadow-md: 0 4px 14px -4px rgba(16,24,40,.12), 0 2px 6px -2px rgba(16,24,40,.07);
            --shadow-lg: 0 16px 38px -16px rgba(16,24,40,.28), 0 7px 16px -10px rgba(16,24,40,.16);

            color: var(--text); font-size: var(--apx-fz); line-height: 1.45;
            -webkit-font-smoothing: antialiased; letter-spacing: -0.004em;
        }
        [data-bs-theme="dark"] .apx{
            --hero-1: color-mix(in srgb, var(--bs-primary), #000 64%);
            --hero-2: color-mix(in srgb, var(--bs-primary), #000 48%);
            --hero-3: color-mix(in srgb, var(--bs-primary), #000 30%);
            --surface:   #272d34;
            --surface-2: #2e353d;
            --surface-3: #353d46;
            --border:        #3a424c;
            --border-strong: #4a535e;
            --brand-ink: color-mix(in srgb, var(--bs-primary), #fff 45%);
            --shadow-sm: 0 1px 2px rgba(0,0,0,.4);
            --shadow-md: 0 6px 18px -6px rgba(0,0,0,.55);
            --shadow-lg: 0 16px 38px -16px rgba(0,0,0,.6), 0 7px 16px -10px rgba(0,0,0,.5);
        }

        /* ── primitives ─────────────────────────────────────────────── */
        .apx .apx-panel{ background:var(--surface); border:1px solid var(--border); border-radius:var(--r-lg); box-shadow:var(--shadow-sm); }
        .apx .apx-panel-h{ display:flex; align-items:center; gap:9px; padding:11px 14px; border-bottom:1px solid var(--border); }
        .apx .apx-panel-h h4{ margin:0; font-size:12.5px; font-weight:700; letter-spacing:-.01em; }
        .apx .apx-panel-h .sub{ font-size:10.5px; color:var(--text-3); font-weight:500; }
        .apx .apx-panel-b{ padding:14px; }
        .apx .apx-ico{ width:26px; height:26px; border-radius:8px; display:inline-flex; align-items:center; justify-content:center;
                       background:rgba(var(--brand-rgb),.10); color:var(--brand); font-size:12px; flex:none; }

        .apx .apx-btn{ display:inline-flex; align-items:center; justify-content:center; gap:6px; font-weight:600; font-size:11.5px;
                       padding:7px 12px; border-radius:9px; border:1px solid transparent; cursor:pointer; font-family:inherit;
                       transition:transform .14s ease, box-shadow .14s ease, background .14s ease; white-space:nowrap; }
        .apx .apx-btn:active{ transform:translateY(1px); }
        .apx .apx-btn:disabled{ opacity:.55; cursor:not-allowed; }
        .apx .apx-btn-primary{ background:var(--brand); color:#fff; box-shadow:0 6px 16px -8px rgba(var(--brand-rgb),.75); }
        .apx .apx-btn-primary:hover{ background:var(--brand-600); color:#fff; }
        .apx .apx-btn-ghost{ background:var(--surface); color:var(--text-2); border-color:var(--border-strong); }
        .apx .apx-btn-ghost:hover{ background:var(--surface-2); color:var(--text); }
        .apx .apx-btn-soft{ background:rgba(var(--brand-rgb),.09); color:var(--brand-ink); border-color:rgba(var(--brand-rgb),.22); }
        .apx .apx-btn-soft:hover{ background:rgba(var(--brand-rgb),.16); color:var(--brand-ink); }
        .apx .apx-btn-danger{ background:var(--danger-bg); color:var(--danger); border-color:rgba(var(--danger-rgb),.28); }
        .apx .apx-btn-lg{ padding:11px 20px; font-size:13px; border-radius:11px; }
        .apx .apx-btn-xs{ padding:5px 10px; font-size:11px; border-radius:8px; }
        .apx .apx-btn-block{ width:100%; }

        .apx .apx-chip{ display:inline-flex; align-items:center; gap:5px; font-size:9.5px; font-weight:700; letter-spacing:.06em;
                        text-transform:uppercase; padding:4px 9px; border-radius:999px; line-height:1; }
        .apx .apx-chip .dot{ width:6px; height:6px; border-radius:50%; }
        .apx .chip-scheduled{ background:rgba(var(--brand-rgb),.10); color:var(--brand-ink); }
        .apx .chip-scheduled .dot{ background:var(--brand); }
        .apx .chip-awaiting{ background:var(--warning-bg); color:var(--warning); }
        .apx .chip-awaiting .dot{ background:var(--warning); }
        .apx .chip-completed{ background:var(--success-bg); color:var(--success); }
        .apx .chip-completed .dot{ background:var(--success); }
        .apx .chip-no_show{ background:var(--danger-bg); color:var(--danger); }
        .apx .chip-no_show .dot{ background:var(--danger); }
        .apx .chip-cancelled{ background:var(--surface-3); color:var(--text-3); }
        .apx .chip-cancelled .dot{ background:var(--text-3); }

        .apx .apx-avatar{ width:30px; height:30px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center;
                          font-size:11px; font-weight:800; color:#fff; flex:none; background:linear-gradient(135deg,var(--hero-2),var(--hero-3)); }
        .apx .apx-kv{ display:flex; align-items:baseline; gap:8px; padding:7px 0; border-bottom:1px dashed var(--border); }
        .apx .apx-kv:last-child{ border-bottom:0; }
        .apx .apx-kv .k{ font-size:11px; color:var(--text-3); min-width:104px; }
        .apx .apx-kv .v{ font-size:12px; font-weight:600; }
        .apx .apx-sect{ font-size:9.5px; font-weight:800; letter-spacing:.1em; text-transform:uppercase; color:var(--text-3); margin-bottom:8px; }
        .apx .apx-hint{ font-size:10.5px; color:var(--text-3); margin-top:5px; }
        .apx .apx-codev{ font-family:ui-monospace,Menlo,Consolas,monospace; font-size:11px; padding:1px 5px; border-radius:4px;
                         background:rgba(var(--brand-rgb),.10); color:var(--brand); }

        .apx .apx-hero{ position:relative; border-radius:var(--r-xl); color:#fff; overflow:hidden; isolation:isolate;
            padding:18px clamp(16px,2.4vw,28px); box-shadow:var(--shadow-lg);
            background:
              radial-gradient(120% 160% at 12% -10%, rgba(255,255,255,.20), transparent 50%),
              radial-gradient(90% 140% at 100% 0%, var(--hero-3), transparent 55%),
              linear-gradient(118deg, var(--hero-1) 0%, var(--hero-2) 58%, var(--hero-3) 130%); }
        .apx .apx-hero::after{ content:""; position:absolute; inset:0; z-index:-1; opacity:.5;
            background-image:radial-gradient(circle at 1px 1px, rgba(255,255,255,.10) 1px, transparent 0); background-size:22px 22px;
            -webkit-mask-image:linear-gradient(180deg,#000,transparent 70%); mask-image:linear-gradient(180deg,#000,transparent 70%); }
        .apx .apx-hero-title{ font-size:clamp(17px,2.1vw,23px); font-weight:800; line-height:1.1; letter-spacing:-.022em; margin:0; }
        .apx .apx-hero-meta{ color:rgba(255,255,255,.86); font-size:11.5px; font-weight:500; }
        .apx .apx-pill{ display:inline-flex; align-items:center; gap:5px; font-size:9.5px; font-weight:700; letter-spacing:.07em;
            text-transform:uppercase; padding:4px 9px; border-radius:999px; line-height:1;
            background:rgba(255,255,255,.16); color:#fff; border:1px solid rgba(255,255,255,.28); }
        .apx .apx-btn-glass{ background:rgba(255,255,255,.14); border-color:rgba(255,255,255,.28); color:#fff; }
        .apx .apx-btn-glass:hover{ background:rgba(255,255,255,.24); color:#fff; }

        .apx .apx-empty{ text-align:center; padding:30px 20px; }
        .apx .apx-empty .art{ width:66px; height:66px; border-radius:20px; margin:0 auto 14px; display:flex; align-items:center;
            justify-content:center; font-size:26px; color:var(--brand); background:rgba(var(--brand-rgb),.09);
            border:1px solid rgba(var(--brand-rgb),.18); }
        .apx .apx-empty h3{ margin:0 0 5px; font-size:14px; font-weight:750; letter-spacing:-.015em; }
        .apx .apx-empty p{ margin:0 auto 16px; font-size:12px; color:var(--text-2); max-width:390px; line-height:1.55; }

        .apx .apx-alert{ display:flex; gap:11px; padding:12px 14px; border-radius:var(--r-md); align-items:flex-start; }
        .apx .apx-alert i.lead{ font-size:14px; margin-top:1px; flex:none; }
        .apx .apx-alert .t{ font-weight:750; font-size:12.5px; margin-bottom:2px; }
        .apx .apx-alert .s{ font-size:11.5px; color:var(--text-2); line-height:1.5; }
        .apx .alert-bad{ background:var(--danger-bg); border:1px solid rgba(var(--danger-rgb),.3); }
        .apx .alert-bad i.lead{ color:var(--danger); }
        .apx .alert-warn{ background:var(--warning-bg); border:1px solid rgba(var(--warning-rgb),.3); }
        .apx .alert-warn i.lead{ color:var(--warning); }
        .apx .alert-info{ background:var(--info-bg); border:1px solid rgba(var(--info-rgb),.26); }
        .apx .alert-info i.lead{ color:var(--info); }

        /* ── timeline (link & delivery trail) ───────────────────────── */
        .apx .apx-timeline{ position:relative; padding-inline-start:22px; }
        .apx .apx-timeline::before{ content:""; position:absolute; inset-block:5px 5px; inset-inline-start:6px; width:2px; background:var(--border); }
        .apx .apx-tl{ position:relative; padding-bottom:14px; }
        .apx .apx-tl:last-child{ padding-bottom:0; }
        .apx .apx-tl::before{ content:""; position:absolute; inset-inline-start:-22px; top:3px; width:13px; height:13px; border-radius:50%;
            background:var(--surface); border:2px solid var(--border-strong); }
        .apx .apx-tl.on::before{ border-color:var(--brand); background:var(--brand); }
        .apx .apx-tl.ok::before{ border-color:var(--success); background:var(--success); }
        .apx .apx-tl.bad::before{ border-color:var(--danger); background:var(--danger); }
        .apx .apx-tl .tt{ font-size:12px; font-weight:700; }
        .apx .apx-tl .ts{ font-size:10.5px; color:var(--text-3); margin-top:1px; unicode-bidi:plaintext; }
        .apx .apx-timeline.tight{ padding-inline-start:19px; }
        .apx .apx-timeline.tight::before{ inset-inline-start:5px; }
        .apx .apx-timeline.tight .apx-tl{ padding-bottom:11px; }
        .apx .apx-timeline.tight .apx-tl::before{ inset-inline-start:-19px; width:11px; height:11px; }
        .apx .apx-timeline.tight .apx-tl .tt{ font-size:11.5px; }
        .apx .apx-timeline.tight .apx-tl .ts{ font-size:10px; }

        .apx .apx-linkbox{ display:flex; align-items:center; gap:8px; padding:8px 10px; border-radius:var(--r-sm); background:var(--surface-2);
            border:1px dashed var(--border-strong); font-family:ui-monospace,Menlo,Consolas,monospace; font-size:10.5px;
            color:var(--text-2); direction:ltr; }
        .apx .apx-linkbox > span{ flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .apx .apx-copy{ flex:none; display:inline-flex; align-items:center; gap:5px; font-size:10px; font-weight:700;
            font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;
            letter-spacing:.04em; text-transform:uppercase; padding:4px 9px; border-radius:7px; cursor:pointer; line-height:1;
            background:rgba(var(--brand-rgb),.09); color:var(--brand-ink); border:1px solid rgba(var(--brand-rgb),.22);
            transition:background .14s ease, color .14s ease, border-color .14s ease; }
        .apx .apx-copy:hover{ background:rgba(var(--brand-rgb),.16); }
        .apx .apx-copy:active{ transform:translateY(1px); }
        .apx .apx-copy.ok{ background:var(--success-bg); color:var(--success); border-color:rgba(var(--success-rgb),.28); }

        /* ── cockpit strip ──────────────────────────────────────────────
           The employee is one line, not a panel: avatar, name, the picker
           itself and the status all sit on the same 46px row so the record
           below it starts within the first screenful. */
        .apx .apx-ck{ display:flex; align-items:center; gap:11px; flex-wrap:wrap; padding:8px 11px;
            background:var(--surface); border:1px solid var(--border); border-radius:var(--r-lg); box-shadow:var(--shadow-sm); }
        .apx .apx-ck .who{ display:flex; align-items:center; gap:9px; min-width:0; }
        .apx .apx-ck .nm{ font-size:12.5px; font-weight:750; letter-spacing:-.015em; }
        .apx .apx-ck .nm a{ color:inherit; text-decoration:none; }
        .apx .apx-ck .nm a:hover{ color:var(--brand); }
        .apx .apx-ck .nm i{ font-size:9px; color:var(--text-3); }
        .apx .apx-ck .sb{ font-size:10.5px; color:var(--text-3); margin-top:1px; }
        .apx .apx-ck .vr{ width:1px; align-self:stretch; background:var(--border); }
        .apx .apx-ck .picker{ flex:1 1 200px; min-width:170px; max-width:300px; }
        .apx .apx-ck .rgt{ margin-inline-start:auto; display:flex; align-items:center; gap:7px; flex-wrap:wrap; }
        .apx .apx-ck.idle .apx-avatar{ background:var(--surface-3); color:var(--text-3); }
        .apx .apx-ck.idle .nm{ color:var(--text-3); }

        /* ── record card (the appointment itself, dense) ─────────────── */
        .apx .apx-rec{ background:var(--surface); border:1px solid var(--border); border-radius:var(--r-lg);
            box-shadow:var(--shadow-sm); overflow:hidden; height:100%; display:flex; flex-direction:column; }
        .apx .apx-rec-h{ display:flex; align-items:center; gap:11px; padding:11px 13px; border-bottom:1px solid var(--border); }
        .apx .apx-rec-h .tt{ font-size:14px; font-weight:800; letter-spacing:-.02em; }
        .apx .apx-rec-h .ss{ font-size:10.5px; color:var(--text-3); margin-top:2px; }
        .apx .apx-minih{ display:flex; align-items:center; gap:8px; padding:9px 13px; border-bottom:1px solid var(--border); }
        .apx .apx-minih .rt{ margin-inline-start:auto; }
        .apx .apx-minih .apx-sect, .apx .apx-minih .apx-hint{ margin:0; }
        .apx .apx-rec-b{ padding:12px 13px; }

        /* the navy band is now a 46px tile — the only gradient left on the tab */
        .apx .apx-dt{ width:46px; flex:none; text-align:center; border-radius:11px; padding:5px 0 6px; color:#fff;
            background:linear-gradient(140deg,var(--hero-1),var(--hero-3)); box-shadow:0 6px 14px -8px rgba(var(--brand-rgb),.9); }
        .apx .apx-dt .mo{ font-size:8.5px; font-weight:800; letter-spacing:.12em; text-transform:uppercase; opacity:.85; }
        .apx .apx-dt .dy{ font-size:18px; font-weight:800; line-height:1; margin-top:1px; }
        .apx .apx-dt.wait{ background:var(--surface-3); color:var(--text-3); box-shadow:none; height:44px;
            display:flex; align-items:center; justify-content:center; font-size:17px; padding:0; }

        /* four facts in a 2×2 hairline grid instead of four full-width rows */
        .apx .apx-facts{ display:grid; grid-template-columns:repeat(2,1fr); gap:1px; background:var(--border); }
        .apx .apx-facts .f{ background:var(--surface); padding:9px 13px; min-width:0; }
        .apx .apx-facts .f .k{ font-size:9px; font-weight:800; letter-spacing:.1em; text-transform:uppercase; color:var(--text-3); }
        .apx .apx-facts .f .v{ font-size:12px; font-weight:650; margin-top:2px; overflow:hidden; text-overflow:ellipsis;
            white-space:nowrap; unicode-bidi:plaintext; }
        .apx .apx-facts .f .v.mono{ font-family:ui-monospace,Menlo,Consolas,monospace; font-size:11px; color:var(--brand-ink); font-weight:700; }

        .apx .apx-bar{ display:flex; gap:7px; flex-wrap:wrap; align-items:center; margin-top:auto;
            padding:9px 13px; border-top:1px solid var(--border); background:var(--surface-2); }
        .apx .apx-bar .spacer{ flex:1; }

        .apx .apx-prompt{ display:flex; align-items:center; gap:11px; padding:13px; border-radius:var(--r-lg);
            background:rgba(var(--brand-rgb),.05); border:1px dashed rgba(var(--brand-rgb),.3); }
        .apx .apx-prompt .ic{ width:34px; height:34px; border-radius:11px; display:flex; align-items:center; justify-content:center;
            flex:none; background:rgba(var(--brand-rgb),.12); color:var(--brand); font-size:15px; }
        .apx .apx-prompt .t{ font-size:12.5px; font-weight:750; }
        .apx .apx-prompt .s{ font-size:11px; color:var(--text-2); margin-top:1px; line-height:1.5; }


        /* ── slot grid (public page + staff appointment) ────────────────── */
        .apx .apx-slots{ display:grid; grid-template-columns:repeat(auto-fill,minmax(96px,1fr)); gap:8px; }
        .apx .apx-slot{ padding:11px 8px; border-radius:var(--r-md); border:1px solid var(--border-strong); background:var(--surface);
            font-size:12.5px; font-weight:700; color:var(--text); cursor:pointer; text-align:center; font-family:inherit;
            transition:border-color .14s, background .14s, transform .14s; letter-spacing:-.01em; }
        .apx .apx-slot:hover{ border-color:var(--brand); background:rgba(var(--brand-rgb),.06); transform:translateY(-1px); }
        .apx .apx-slot.sel{ background:var(--brand); border-color:var(--brand); color:#fff; box-shadow:0 8px 18px -8px rgba(var(--brand-rgb),.8); }
        .apx .apx-slot small{ display:block; font-size:9.5px; font-weight:600; color:var(--text-3); margin-top:2px; }
        .apx .apx-slot.sel small{ color:rgba(255,255,255,.8); }

        .apx .apx-daybtn{ padding:9px 6px; border-radius:9px; border:1px solid var(--border); background:var(--surface);
            cursor:pointer; font-family:inherit; text-align:center; min-width:64px; }
        .apx .apx-daybtn .dw{ font-size:9.5px; font-weight:800; letter-spacing:.07em; text-transform:uppercase; color:var(--text-3); }
        .apx .apx-daybtn .dd{ font-size:15px; font-weight:750; margin-top:2px; color:var(--text); }
        .apx .apx-daybtn .dc{ font-size:9px; color:var(--text-3); margin-top:1px; }
        .apx .apx-daybtn:hover{ border-color:var(--brand); }
        .apx .apx-daybtn.sel{ background:var(--brand); border-color:var(--brand); }
        .apx .apx-daybtn.sel .dw, .apx .apx-daybtn.sel .dd, .apx .apx-daybtn.sel .dc{ color:#fff; }

        /* ── table ──────────────────────────────────────────────────── */
        .apx .apx-tbl{ width:100%; border-collapse:separate; border-spacing:0; font-size:11.5px; }
        .apx .apx-tbl th{ text-align:start; font-size:9.5px; font-weight:800; letter-spacing:.08em; text-transform:uppercase;
            color:var(--text-3); padding:9px 11px; background:var(--surface-2); border-bottom:1px solid var(--border); white-space:nowrap; }
        .apx .apx-tbl td{ padding:10px 11px; border-bottom:1px solid var(--border); vertical-align:middle; }
        .apx .apx-tbl tbody tr:hover td{ background:rgba(var(--brand-rgb),.035); }
        .apx .apx-tbl .ref{ font-family:ui-monospace,Menlo,Consolas,monospace; font-size:10.5px; color:var(--brand); font-weight:650; }
        .apx .apx-tbl .strong{ font-weight:700; font-size:12px; }
        .apx .apx-tbl .dim{ font-size:10.5px; color:var(--text-3); }
        .apx .apx-tbl .who{ display:flex; align-items:center; gap:8px; }
        .apx .apx-tbl .who .apx-avatar{ width:25px; height:25px; font-size:9.5px; }

        .apx .apx-kpi{ display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:10px; }
        .apx .apx-kpi .k{ padding:12px 13px; border-radius:var(--r-md); background:var(--surface); border:1px solid var(--border); }
        .apx .apx-kpi .k .lb{ font-size:9.5px; font-weight:800; letter-spacing:.08em; text-transform:uppercase; color:var(--text-3); }
        .apx .apx-kpi .k .vl{ font-size:21px; font-weight:800; letter-spacing:-.03em; margin-top:4px; }
        .apx .apx-kpi .k .dl{ font-size:10.5px; color:var(--text-3); margin-top:1px; }
        .apx .apx-kpi .k.accent{ border-color:rgba(var(--brand-rgb),.3); background:rgba(var(--brand-rgb),.05); }
        .apx .apx-kpi .k.accent .vl{ color:var(--brand); }

        /* ── calendar (FullCalendar 6 skin) ─────────────────────────── */
        /* FullCalendar ships its own CSS at runtime and reads these variables,
           so binding them to the premium tokens is what makes the grid follow
           the tenant theme and dark mode instead of its stock blue. */
        .apx .fc{
            --fc-page-bg-color: var(--surface);
            --fc-border-color: var(--border);
            --fc-neutral-bg-color: var(--surface-2);
            --fc-neutral-text-color: var(--text-3);
            --fc-today-bg-color: rgba(var(--brand-rgb),.07);
            --fc-highlight-color: rgba(var(--brand-rgb),.14);
            --fc-now-indicator-color: var(--danger);
            --fc-event-bg-color: var(--brand);
            --fc-event-border-color: var(--brand);
            --fc-event-text-color: #fff;
            --fc-small-font-size: 10.5px;
            font-size:12px; color:var(--text);
        }

        .apx .fc .fc-toolbar.fc-header-toolbar{ margin-bottom:13px; gap:9px; flex-wrap:wrap; }
        .apx .fc .fc-toolbar-title{ font-size:15px; font-weight:750; letter-spacing:-.022em; color:var(--text); }
        .apx .fc .fc-button{
            background:var(--surface); border:1px solid var(--border-strong); color:var(--text-2);
            font-size:11.5px; font-weight:650; line-height:1.4; padding:7px 12px; border-radius:9px;
            box-shadow:none; text-transform:capitalize;
            transition:background .14s ease, color .14s ease, border-color .14s ease;
        }
        .apx .fc .fc-button:hover{ background:var(--surface-2); color:var(--text); border-color:var(--border-strong); }
        .apx .fc .fc-button:focus{ box-shadow:0 0 0 3px rgba(var(--brand-rgb),.18); }
        .apx .fc .fc-button:disabled{ opacity:.45; box-shadow:none; }
        .apx .fc .fc-button-primary:not(:disabled).fc-button-active,
        .apx .fc .fc-button-primary:not(:disabled):active{
            background:var(--brand); border-color:var(--brand); color:#fff;
            box-shadow:0 6px 16px -8px rgba(var(--brand-rgb),.75);
        }
        /* v6 butts grouped buttons together; the premium chrome wants them apart */
        .apx .fc .fc-button-group{ gap:6px; }
        .apx .fc .fc-button-group > .fc-button{ border-radius:9px; }
        .apx .fc .fc-icon{ font-size:15px; vertical-align:-2px; }

        .apx .fc-theme-standard td,
        .apx .fc-theme-standard th,
        .apx .fc-theme-standard .fc-scrollgrid{ border-color:var(--border); }
        .apx .fc .fc-col-header-cell{ background:var(--surface-2); }
        .apx .fc .fc-col-header-cell-cushion{
            padding:9px 6px; font-size:10px; font-weight:800; letter-spacing:.1em;
            text-transform:uppercase; color:var(--text-3); text-decoration:none;
        }
        .apx .fc .fc-day-today .fc-col-header-cell-cushion{ color:var(--brand); }
        .apx .fc .fc-daygrid-day-number{ padding:6px 8px; font-size:11.5px; font-weight:700; color:var(--text-2); text-decoration:none; }
        .apx .fc .fc-day-today .fc-daygrid-day-number{ color:var(--brand); font-weight:800; }
        .apx .fc .fc-day-other .fc-daygrid-day-number{ opacity:.4; }
        .apx .fc .fc-timegrid-slot{ height:2.3em; }
        .apx .fc .fc-timegrid-slot-label-cushion,
        .apx .fc .fc-timegrid-axis-cushion{
            font-size:10px; font-weight:700; letter-spacing:.03em; color:var(--text-3);
        }
        .apx .fc .fc-timegrid-now-indicator-line{ border-color:var(--danger); border-width:1.5px 0 0; }
        .apx .fc .fc-timegrid-now-indicator-arrow{ border-color:var(--danger); border-width:5px; }

        .apx .fc .fc-event{
            border:0; border-radius:7px; padding:2px 6px; cursor:pointer;
            font-size:11px; font-weight:650; letter-spacing:-.01em;
            box-shadow:0 2px 6px -3px rgba(16,24,40,.45);
        }
        .apx .fc .fc-event:hover{ filter:brightness(1.07); }
        .apx .fc .fc-event-time{ font-weight:800; opacity:.92; }
        .apx .fc .fc-event-title{ font-weight:650; }
        .apx .fc .fc-daygrid-more-link{ font-size:10.5px; font-weight:750; color:var(--brand); }
        .apx .fc .fc-popover{
            background:var(--surface); border:1px solid var(--border); border-radius:var(--r-lg);
            box-shadow:var(--shadow-lg); overflow:hidden;
        }
        .apx .fc .fc-popover-header{ background:var(--surface-2); color:var(--text); font-size:11.5px; font-weight:750; padding:8px 11px; }

        .apx .fc .fc-list{ border-color:var(--border); }
        .apx .fc .fc-list-day-cushion{
            background:var(--surface-2); font-size:10.5px; font-weight:800; letter-spacing:.08em;
            text-transform:uppercase; color:var(--text-3);
        }
        .apx .fc .fc-list-event td{ font-size:12px; color:var(--text); }
        .apx .fc .fc-list-event[class*="pv-event-"]{ background:var(--surface)!important; color:var(--text)!important; }
        .apx .fc .fc-list-event.pv-event-scheduled .fc-list-event-dot{ border-color:var(--brand); }
        .apx .fc .fc-list-event.pv-event-completed .fc-list-event-dot{ border-color:var(--success); }
        .apx .fc .fc-list-event.pv-event-cancelled .fc-list-event-dot{ border-color:var(--text-3); }
        .apx .fc .fc-list-event.pv-event-no-show .fc-list-event-dot{ border-color:var(--danger); }
        .apx .fc .fc-list-event.pv-event-awaiting .fc-list-event-dot{ border-color:var(--warning); }
        .apx .fc .fc-list-event:hover td{ background:var(--surface-2); }
        .apx .fc .fc-list-event-title a{ color:var(--text); text-decoration:none; font-weight:650; }
        .apx .fc .fc-list-event-time{ color:var(--text-3); font-weight:700; }
        .apx .fc .fc-list-empty{ background:var(--surface); color:var(--text-3); font-size:12px; }

        .apx .fc .fc-multimonth{ border-color:var(--border); background:var(--surface); }
        .apx .fc .fc-multimonth-title{ font-size:12.5px; font-weight:800; letter-spacing:-.02em; color:var(--text); padding:12px 0 8px; }
        .apx .fc .fc-multimonth-daygrid{ background:var(--surface); }
        .apx .fc .fc-multimonth-header-table th{ font-size:9.5px; font-weight:800; letter-spacing:.06em; color:var(--text-3); }
        .apx .fc .fc-daygrid-event-dot{ border-color:currentColor; }

        .apx .pv-event-scheduled{ background:var(--brand)!important; border-color:var(--brand)!important; color:#fff!important; }
        .apx .pv-event-completed{ background:var(--success)!important; border-color:var(--success)!important; color:#fff!important; }
        .apx .pv-event-cancelled{ background:var(--text-3)!important; border-color:var(--text-3)!important; color:#fff!important; }
        .apx .pv-event-no-show{ background:var(--danger)!important; border-color:var(--danger)!important; color:#fff!important; }
        .apx .pv-event-awaiting{ background:var(--warning)!important; border-color:var(--warning)!important; color:#fff!important; }
        .apx .apx-legend{ display:flex; gap:14px; flex-wrap:wrap; padding:10px 13px; border-top:1px solid var(--border);
            font-size:10.5px; color:var(--text-3); }
        .apx .apx-legend span{ display:inline-flex; align-items:center; gap:6px; }
        .apx .apx-legend i{ width:11px; height:11px; border-radius:4px; display:inline-block; }

        /* native select, dressed to sit beside the premium chips (Bootstrap
           paints the caret through background-image, so only the colours and
           the frame are restated here) */
        .apx .apx-select{
            background-color:var(--surface); border:1px solid var(--border-strong); color:var(--text);
            font-size:11.5px; font-weight:600; line-height:1.4; border-radius:9px;
            padding:7px 30px 7px 12px; font-family:inherit;
            background-image:url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
            background-repeat:no-repeat; background-position:right 10px center; background-size:11px;
            appearance:none; -webkit-appearance:none;
        }
        [dir="rtl"] .apx .apx-select{ padding:7px 12px 7px 30px; background-position:left 10px center; }
        .apx .apx-select:focus{ outline:0; border-color:var(--brand); box-shadow:0 0 0 3px rgba(var(--brand-rgb),.16); }

        /* ── settings: template rail + editor ───────────────────────── */
        .apx .tpl-i{ display:flex; align-items:flex-start; gap:10px; padding:11px 13px; border-bottom:1px solid var(--border); cursor:pointer; }
        .apx .tpl-i:hover{ background:var(--surface-3); }
        .apx .tpl-i.on{ background:var(--surface); box-shadow:inset 3px 0 0 var(--brand); }
        [dir="rtl"] .apx .tpl-i.on{ box-shadow:inset -3px 0 0 var(--brand); }
        .apx .tpl-i .nm{ font-size:12px; font-weight:700; line-height:1.25; }
        .apx .tpl-i .ty{ font-size:10px; color:var(--text-3); font-family:ui-monospace,Menlo,Consolas,monospace; margin-top:2px; }
        .apx .apx-var{ display:inline-block; padding:1px 6px; border-radius:5px; font-family:ui-monospace,Menlo,Consolas,monospace;
            font-size:11px; font-weight:600; background:rgba(var(--brand-rgb),.12); color:var(--brand);
            border:1px solid rgba(var(--brand-rgb),.24); cursor:pointer; }
        .apx .apx-var:hover{ background:rgba(var(--brand-rgb),.22); }

        @media (max-width: 767.98px){
            .apx .apx-slots{ grid-template-columns:repeat(auto-fill,minmax(82px,1fr)); }
        }
    </style>
@endonce

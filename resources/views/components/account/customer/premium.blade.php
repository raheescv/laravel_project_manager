{{--
    ╔══════════════════════════════════════════════════════════════════════╗
    ║  Customer View — "Portrait Hero" premium design system                ║
    ║                                                                      ║
    ║  Scoped under .cvx so it styles the customer view shell AND every    ║
    ║  tab component rendered inside it, without leaking into the rest of  ║
    ║  the app.                                                            ║
    ║                                                                      ║
    ║  LAYOUT IS BOOTSTRAP. This file only styles the *look* of the pieces  ║
    ║  (panels, cards, fields, tables, tags). All columns/rows/gutters use  ║
    ║  Bootstrap's own grid — row / col-md-* / g-* — so the breakpoints     ║
    ║  behave exactly like the rest of the app. Do not add custom CSS grid  ║
    ║  layouts here.                                                       ║
    ║                                                                      ║
    ║  Every colour derives from the active SETTINGS THEME (--bs-primary   ║
    ║  and the Bootstrap subtle/emphasis tokens) so it follows the chosen  ║
    ║  colour scheme and light/dark mode automatically.                    ║
    ║                                                                      ║
    ║  Preview: docs/customer-view-premium-preview.html (direction A)      ║
    ╚══════════════════════════════════════════════════════════════════════╝
--}}
@once
    {{-- Pushed to the layout's styles stack, NOT emitted inline: a <style> tag here
         would become a second root element of the Livewire component that includes
         this, and Livewire would bind the component to the <style> tag instead of
         the real .cvx root (breaking wire:click and every DOM update). --}}
    @push('styles')
        <style>
        .cvx{
            /* ── Accent: single source → settings theme primary ───────────── */
            --acc: var(--bs-primary);
            --acc-rgb: var(--bs-primary-rgb);
            --acc-d: color-mix(in srgb, var(--bs-primary), #000 15%);
            --acc-deep: color-mix(in srgb, var(--bs-primary), #000 46%);
            --acc-soft: color-mix(in srgb, var(--bs-primary), #fff 26%);
            --acc-tint: color-mix(in srgb, var(--bs-primary), transparent 92%);
            --acc-tint-2: color-mix(in srgb, var(--bs-primary), transparent 96%);

            /* Neutral surfaces — crisp light ramp, dark ramp set below */
            --surface: #ffffff;
            --surface-2: #f6f8fb;
            --surface-3: #eef1f6;
            --line: #e5e9f0;
            --line-soft: #eff2f7;
            --ink: var(--bs-emphasis-color);
            --ink-2: var(--bs-body-color);
            --muted: var(--bs-secondary-color);
            --faint: var(--bs-tertiary-color);

            --ok: var(--bs-success);   --ok-t: var(--bs-success-bg-subtle);   --ok-e: var(--bs-success-text-emphasis);
            --warn: var(--bs-warning); --warn-t: var(--bs-warning-bg-subtle); --warn-e: var(--bs-warning-text-emphasis);
            --bad: var(--bs-danger);   --bad-t: var(--bs-danger-bg-subtle);   --bad-e: var(--bs-danger-text-emphasis);
            --info: var(--bs-info);    --info-t: var(--bs-info-bg-subtle);    --info-e: var(--bs-info-text-emphasis);
            --plum: #7c3aed;           --plum-t: color-mix(in srgb, #7c3aed, transparent 90%); --plum-e: #6d28d9;

            --r-sm: 7px; --r-md: 10px; --r-lg: 14px; --r-xl: 20px;
            --sh-sm: 0 1px 2px rgba(16,24,40,.05), 0 1px 3px rgba(16,24,40,.04);
            --sh-md: 0 4px 14px -4px rgba(16,24,40,.12), 0 2px 6px -2px rgba(16,24,40,.06);
            --sh-lg: 0 18px 42px -18px rgba(16,24,40,.30), 0 8px 18px -12px rgba(16,24,40,.16);

            color: var(--ink); font-size: 12.5px; line-height: 1.5;
            -webkit-font-smoothing: antialiased; letter-spacing: -.004em;
        }
        [data-bs-theme="dark"] .cvx{
            --surface: #252b34;
            --surface-2: #2c333c;
            --surface-3: #343c46;
            --line: #3a424d;
            --line-soft: #333b45;
            --plum: #a78bfa; --plum-e: #c4b5fd;
            --sh-sm: 0 1px 2px rgba(0,0,0,.4);
            --sh-md: 0 6px 18px -6px rgba(0,0,0,.55);
            --sh-lg: 0 18px 42px -18px rgba(0,0,0,.65), 0 8px 18px -12px rgba(0,0,0,.5);
        }
        .cvx *{ scrollbar-width: thin; }
        .cvx .num{ font-variant-numeric: tabular-nums; }
        /* small helpers Bootstrap doesn't ship */
        .cvx .min-w-0{ min-width: 0; }
        .cvx .hint-text{ font-size: 10.5px; color: var(--faint); }

        /* ═══════════════════════════  HERO  ═══════════════════════════ */
        .cvx .cv-hero{
            position: relative; border-radius: var(--r-xl); overflow: hidden; isolation: isolate; color: #fff;
            padding: 18px clamp(16px, 2.4vw, 28px) 78px;
            background:
                radial-gradient(115% 150% at 10% -20%, rgba(255,255,255,.20), transparent 52%),
                radial-gradient(85% 130% at 100% 0%, color-mix(in srgb, var(--bs-primary), #fff 12%), transparent 58%),
                linear-gradient(120deg, var(--acc-deep) 0%, var(--acc-d) 55%, var(--acc) 125%);
            box-shadow: var(--sh-lg);
        }
        .cvx .cv-hero::after{
            content: ""; position: absolute; inset: 0; z-index: -1; opacity: .5;
            background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,.10) 1px, transparent 0);
            background-size: 22px 22px;
            -webkit-mask-image: linear-gradient(180deg, #000, transparent 72%); mask-image: linear-gradient(180deg, #000, transparent 72%);
        }
        .cvx .glow{ position: absolute; z-index: -1; border-radius: 50%; filter: blur(38px); }
        .cvx .glow.a{ width: 250px; height: 250px; top: -90px; right: 6%; background: rgba(255,255,255,.28); opacity: .55; }
        .cvx .glow.b{ width: 200px; height: 200px; bottom: -80px; left: -40px; background: var(--acc-soft); opacity: .38; }

        .cvx .ava{ position: relative; }
        .cvx .ava .disc{
            width: 74px; height: 74px; border-radius: 22px; display: grid; place-items: center; overflow: hidden;
            font-size: 25px; font-weight: 800; letter-spacing: -.02em; color: var(--acc-deep);
            background: linear-gradient(150deg, #fff, #e8eef9);
            box-shadow: 0 10px 26px -12px rgba(0,0,0,.55), inset 0 0 0 1px rgba(255,255,255,.7);
        }
        .cvx .ava .disc img{ width: 100%; height: 100%; object-fit: cover; }
        .cvx .ava .badge-dot{
            position: absolute; right: -4px; bottom: -4px; width: 24px; height: 24px; border-radius: 50%;
            display: grid; place-items: center; font-size: 10px; color: #fff; background: var(--ok);
            box-shadow: 0 0 0 3px var(--acc-deep), 0 2px 8px rgba(0,0,0,.3);
        }
        .cvx .hname{ font-size: clamp(19px, 2.3vw, 26px); font-weight: 800; line-height: 1.1; letter-spacing: -.022em; margin: 0 0 7px; color: #fff; text-shadow: 0 1px 14px rgba(0,0,0,.2); }
        .cvx .hname .code{ color: rgba(255,255,255,.55); font-weight: 700; }
        .cvx .chip{
            display: inline-flex; align-items: center; gap: 5px; font-size: 10px; font-weight: 700; letter-spacing: .05em;
            text-transform: uppercase; padding: 4px 9px; border-radius: 999px; line-height: 1;
            background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.26); color: #fff;
        }
        .cvx .chip.solid{ background: #fff; border-color: #fff; color: var(--acc-deep); }
        .cvx .chip .dot{ width: 6px; height: 6px; border-radius: 50%; background: var(--ok); }
        .cvx .hmeta{ color: rgba(255,255,255,.85); font-size: 11.5px; }
        .cvx .hmeta i{ opacity: .7; margin-right: 5px; }
        .cvx .hmeta a{ color: inherit; text-decoration: none; }
        .cvx .hmeta a:hover{ color: #fff; text-decoration: underline; }

        .cvx .hacts .b{
            display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 600; padding: 7px 12px;
            border-radius: 9px; cursor: pointer; border: 1px solid rgba(255,255,255,.28);
            background: rgba(255,255,255,.14); color: #fff; text-decoration: none;
            transition: transform .14s, background .14s, box-shadow .14s;
        }
        .cvx .hacts .b:hover{ background: rgba(255,255,255,.25); color: #fff; box-shadow: 0 8px 20px -10px rgba(0,0,0,.5); }
        .cvx .hacts .b:active{ transform: translateY(1px); }
        .cvx .hacts .b.pri{ background: #fff; border-color: #fff; color: var(--acc-d); }
        .cvx .hacts .b.pri:hover{ background: #eef3fd; color: var(--acc-d); }

        /* ═══════════════════════════  KPI CARDS  ═══════════════════════════ */
        /* Layout = Bootstrap row/col; this only lifts the row over the hero. */
        .cvx .kpi-row{ margin-top: -58px; position: relative; z-index: 5; }
        .cvx .kpi{
            background: var(--surface); border: 1px solid var(--line); border-radius: var(--r-lg); padding: 12px 14px;
            box-shadow: var(--sh-md); position: relative; overflow: hidden; height: 100%;
            transition: transform .2s cubic-bezier(.22,1,.36,1), box-shadow .2s;
        }
        .cvx .kpi::before{ content: ""; position: absolute; inset: 0 auto 0 0; width: 3px; background: var(--k); }
        .cvx .kpi:hover{ transform: translateY(-3px); box-shadow: var(--sh-lg); }
        .cvx .kpi .ic{ width: 34px; height: 34px; border-radius: 10px; display: grid; place-items: center; font-size: 14px; color: var(--k); background: var(--kt); flex: 0 0 auto; }
        .cvx .kpi .lab{ font-size: 9.5px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--faint); }
        .cvx .kpi .val{ font-size: 19px; font-weight: 800; letter-spacing: -.02em; line-height: 1.1; font-variant-numeric: tabular-nums; }
        .cvx .kpi .sub{ font-size: 10.5px; color: var(--muted); margin-top: 6px; }
        .cvx .kpi.k1{ --k: var(--info); --kt: var(--info-t); }
        .cvx .kpi.k2{ --k: var(--ok); --kt: var(--ok-t); }
        .cvx .kpi.k3{ --k: var(--warn); --kt: var(--warn-t); }
        .cvx .kpi.k4{ --k: var(--plum); --kt: var(--plum-t); }
        .cvx .track{ height: 5px; border-radius: 999px; background: var(--surface-3); overflow: hidden; margin-top: 8px; }
        .cvx .track > i{ display: block; height: 100%; border-radius: 999px; background: linear-gradient(90deg, var(--k), color-mix(in srgb, var(--k), #fff 40%)); }

        /* ═══════════════════════════  TAB RAIL  ═══════════════════════════ */
        .cvx .rail-pill{
            margin: 16px 0 14px; padding: 6px; display: flex; gap: 4px; overflow-x: auto;
            background: var(--surface); border: 1px solid var(--line); border-radius: 13px; box-shadow: var(--sh-sm);
        }
        .cvx .rail-pill button{
            flex: 0 0 auto; display: inline-flex; align-items: center; gap: 7px;
            font-size: 12px; font-weight: 600; color: var(--ink-2); background: transparent; border: 0;
            padding: 8px 13px; border-radius: 9px; cursor: pointer; white-space: nowrap; transition: background .15s, color .15s;
        }
        .cvx .rail-pill button i{ font-size: 12.5px; opacity: .65; }
        .cvx .rail-pill button:hover{ background: var(--surface-2); color: var(--ink); }
        .cvx .rail-pill button.active{ background: var(--acc); color: #fff; box-shadow: 0 6px 16px -8px rgba(var(--acc-rgb), .8); }
        .cvx .rail-pill button.active i{ opacity: 1; }
        .cvx .rail-pill .cnt{ font-size: 9.5px; font-weight: 800; padding: 2px 6px; border-radius: 999px; background: var(--surface-3); color: var(--muted); }
        .cvx .rail-pill button.active .cnt{ background: rgba(255,255,255,.24); color: #fff; }

        /* ═══════════════════════════  PANELS  ═══════════════════════════ */
        .cvx .panel{ background: var(--surface); border: 1px solid var(--line); border-radius: var(--r-lg); box-shadow: var(--sh-sm); overflow: hidden; }
        .cvx .panel + .panel{ margin-top: 12px; }
        .cvx .panel.h-100{ display: flex; flex-direction: column; }
        .cvx .phead{ display: flex; align-items: center; gap: 9px; padding: 11px 14px; border-bottom: 1px solid var(--line); background: var(--surface-2); flex-wrap: wrap; }
        .cvx .phead .ic{ width: 28px; height: 28px; border-radius: 9px; display: grid; place-items: center; font-size: 12.5px; background: var(--pt, var(--acc-tint)); color: var(--pc, var(--acc)); flex: 0 0 auto; }
        .cvx .phead h4{ margin: 0; font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: var(--ink-2); }
        .cvx .phead .hint{ font-size: 10.5px; color: var(--faint); font-weight: 500; letter-spacing: 0; text-transform: none; }
        .cvx .phead .right{ margin-left: auto; display: flex; gap: 6px; align-items: center; flex-wrap: wrap; }
        .cvx .pbody{ padding: 13px 14px; }
        .cvx .p-ok{ --pt: var(--ok-t); --pc: var(--ok); }
        .cvx .p-info{ --pt: var(--info-t); --pc: var(--info); }
        .cvx .p-warn{ --pt: var(--warn-t); --pc: var(--warn); }
        .cvx .p-plum{ --pt: var(--plum-t); --pc: var(--plum); }

        /* ═══════════════════════════  LABEL / VALUE ROWS  ═══════════════════════════ */
        .cvx .dl{ display: flex; align-items: baseline; justify-content: space-between; gap: 12px; padding: 7px 2px; border-bottom: 1px dashed var(--line); margin: 0; }
        .cvx .dl.last, .cvx .dl:last-child{ border-bottom: 0; }
        .cvx .dl dt{ font-size: 11px; color: var(--muted); font-weight: 500; margin: 0; flex: 0 0 auto; }
        .cvx .dl dd{ margin: 0; font-size: 12px; font-weight: 600; text-align: right; min-width: 0; overflow-wrap: anywhere; }
        .cvx .dl dd.empty{ color: var(--faint); font-weight: 500; }

        /* ═══════════════════════  SPEC SHEET (read-only details)  ═══════════════════════ */
        /* Compact label-over-value cells — fits ~3× more per screen than label/value rows. */
        .cvx .specgroup + .specgroup{ margin-top: 14px; }
        .cvx .sect{
            display: flex; align-items: center; gap: 8px; margin: 0 0 8px;
            font-size: 9px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; color: var(--acc);
        }
        .cvx .sect i{ opacity: .75; }
        .cvx .sect::after{ content: ""; flex: 1 1 auto; height: 1px; background: var(--line); }
        .cvx .spec{ padding: 6px 9px; border: 1px solid var(--line); border-radius: 8px; background: var(--surface-2); height: 100%; }
        .cvx .spec .k{ font-size: 8.5px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: var(--faint); line-height: 1.5; }
        .cvx .spec .v{ font-size: 12.5px; font-weight: 600; line-height: 1.35; overflow-wrap: anywhere; }
        .cvx .spec .v.empty{ color: var(--faint); font-weight: 500; }
        .cvx .spec.wide .v{ font-weight: 500; }
        .cvx .spec a{ color: var(--acc); text-decoration: none; }
        .cvx .spec a:hover{ text-decoration: underline; }

        /* ═══════════════════════════  FIELDS  ═══════════════════════════ */
        .cvx .fld label{ display: block; font-size: 9.5px; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; color: var(--faint); margin-bottom: 4px; }
        .cvx .fld input, .cvx .fld select, .cvx .fld textarea{
            width: 100%; font-size: 12.5px; color: var(--ink); background: var(--surface);
            border: 1px solid var(--line); border-radius: 9px; padding: 7px 10px;
            transition: border-color .15s, box-shadow .15s;
        }
        .cvx .fld textarea{ min-height: 58px; resize: vertical; }
        .cvx .fld input:hover, .cvx .fld select:hover{ border-color: var(--faint); }
        .cvx .fld input:focus, .cvx .fld select:focus, .cvx .fld textarea:focus{
            outline: 0; border-color: var(--acc); box-shadow: 0 0 0 3.5px rgba(var(--acc-rgb), .16);
        }
        .cvx .fld input:disabled, .cvx .fld select:disabled, .cvx .fld textarea:disabled{ background: var(--surface-2); color: var(--muted); cursor: not-allowed; }
        .cvx .fld .err{ display: block; font-size: 10px; font-weight: 600; color: var(--bad); margin-top: 3px; }
        .cvx .expiry{ font-size: 10px; margin-top: 4px; font-weight: 600; }
        .cvx .expiry.soon{ color: var(--warn-e); }
        .cvx .expiry.gone{ color: var(--bad-e); }
        .cvx .expiry.fine{ color: var(--ok-e); }

        .cvx .savebar{
            position: sticky; bottom: 0; z-index: 6; margin-top: 12px;
            display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
            padding: 10px 13px; border: 1px solid var(--line); border-radius: var(--r-lg);
            background: var(--surface); box-shadow: var(--sh-md);
        }
        .cvx .savebar .note{ font-size: 11px; color: var(--muted); margin-right: auto; }

        /* ═══════════════════════════  BUTTONS & TAGS  ═══════════════════════════ */
        .cvx .btn{
            display: inline-flex; align-items: center; justify-content: center; gap: 7px; cursor: pointer; text-decoration: none;
            font-size: 11.5px; font-weight: 600; padding: 7px 12px; border-radius: 9px;
            border: 1px solid var(--line); background: var(--surface); color: var(--ink-2);
            transition: transform .14s, background .14s, box-shadow .14s, border-color .14s;
        }
        .cvx .btn:hover{ background: var(--surface-2); border-color: var(--faint); color: var(--ink); }
        .cvx .btn:active{ transform: translateY(1px); }
        .cvx .btn:disabled{ opacity: .6; cursor: not-allowed; }
        .cvx .btn.pri{ background: var(--acc); border-color: var(--acc); color: #fff; box-shadow: 0 8px 18px -10px rgba(var(--acc-rgb), .9); }
        .cvx .btn.pri:hover{ background: var(--acc-d); border-color: var(--acc-d); color: #fff; }
        .cvx .btn.ok{ background: var(--ok); border-color: var(--ok); color: #fff; }
        .cvx .btn.ok:hover{ background: color-mix(in srgb, var(--ok), #000 14%); color: #fff; }
        .cvx .btn.ghost-bad{ color: var(--bad-e); border-color: color-mix(in srgb, var(--bad), transparent 65%); background: var(--bad-t); }
        .cvx .btn.sm{ font-size: 11px; padding: 5px 10px; }

        .cvx .tag{ display: inline-flex; align-items: center; gap: 5px; font-size: 9.5px; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; padding: 3px 8px; border-radius: 999px; }
        .cvx .tag.ok{ background: var(--ok-t); color: var(--ok-e); }
        .cvx .tag.warn{ background: var(--warn-t); color: var(--warn-e); }
        .cvx .tag.bad{ background: var(--bad-t); color: var(--bad-e); }
        .cvx .tag.info{ background: var(--info-t); color: var(--info-e); }
        .cvx .tag.plum{ background: var(--plum-t); color: var(--plum-e); }
        .cvx .tag.acc{ background: var(--acc-tint); color: var(--acc); }
        .cvx .tag.mute{ background: var(--surface-3); color: var(--muted); }

        /* ═══════════════════════════  FILTER BAR  ═══════════════════════════ */
        .cvx .filters{ padding: 11px 14px; background: var(--surface-2); border-bottom: 1px solid var(--line); }
        .cvx .quick{ display: flex; gap: 4px; flex-wrap: wrap; }
        .cvx .quick button{ cursor: pointer; font-size: 10.5px; font-weight: 700; padding: 6px 9px; border-radius: 8px; border: 1px solid var(--line); background: var(--surface); color: var(--muted); }
        .cvx .quick button:hover{ color: var(--ink); border-color: var(--faint); }
        .cvx .quick button.active{ background: var(--acc-tint); border-color: color-mix(in srgb, var(--acc), transparent 60%); color: var(--acc); }

        /* ═══════════════════════════  TABLES  ═══════════════════════════ */
        .cvx .tw{ overflow-x: auto; }
        .cvx table.t{ width: 100%; border-collapse: separate; border-spacing: 0; font-size: 12px; margin: 0; }
        .cvx table.t th{
            text-align: left; font-size: 9.5px; font-weight: 800; letter-spacing: .07em; text-transform: uppercase; color: var(--muted);
            padding: 9px 12px; background: var(--surface-2); border-bottom: 1px solid var(--line); white-space: nowrap;
        }
        .cvx table.t td{ padding: 9px 12px; border-bottom: 1px solid var(--line-soft); vertical-align: middle; }
        .cvx table.t tbody tr:last-child td{ border-bottom: 0; }
        .cvx table.t tbody tr:hover td{ background: var(--acc-tint-2); }
        .cvx table.t .r{ text-align: right; font-variant-numeric: tabular-nums; }
        .cvx table.t .strong{ font-weight: 700; }
        .cvx table.t a{ color: var(--acc); font-weight: 600; text-decoration: none; }
        .cvx table.t a:hover{ text-decoration: underline; }
        .cvx table.t tfoot td{ padding: 9px 12px; background: var(--surface-2); font-weight: 800; border-top: 1px solid var(--line); border-bottom: 0; font-variant-numeric: tabular-nums; }
        .cvx .fb{ font-size: 11px; color: var(--muted); background: var(--surface-2); border-left: 2px solid var(--warn); padding: 6px 10px; border-radius: 0 7px 7px 0; }
        .cvx .stars i{ font-size: 11px; color: var(--line); }
        .cvx .stars i.f{ color: #f59e0b; }
        .cvx .v-ok{ color: var(--ok-e); }
        .cvx .v-warn{ color: var(--warn-e); }
        .cvx .v-bad{ color: var(--bad-e); }

        /* ═══════════════════════════  TILES / METER  ═══════════════════════════ */
        .cvx .tile{ border: 1px solid var(--line); border-radius: var(--r-md); padding: 11px 12px; background: var(--surface); height: 100%; }
        .cvx .tile .lab{ font-size: 9.5px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--faint); display: flex; align-items: center; gap: 6px; }
        .cvx .tile .lab i{ color: var(--k); }
        .cvx .tile .val{ font-size: 17px; font-weight: 800; letter-spacing: -.02em; margin-top: 2px; font-variant-numeric: tabular-nums; }
        .cvx .tile .bar{ height: 4px; border-radius: 999px; background: var(--surface-3); overflow: hidden; margin-top: 8px; }
        .cvx .tile .bar > i{ display: block; height: 100%; background: var(--k); }
        .cvx .tile.k1{ --k: var(--info); }
        .cvx .tile.k2{ --k: var(--ok); }
        .cvx .tile.k3{ --k: var(--warn); }

        .cvx .meter{ padding: 11px 12px; border-radius: var(--r-md); background: var(--surface-2); border: 1px solid var(--line); }
        .cvx .meter .hd{ display: flex; justify-content: space-between; gap: 10px; font-size: 10.5px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .05em; }
        .cvx .meter .bar{ height: 6px; border-radius: 999px; background: var(--surface-3); overflow: hidden; margin-top: 8px; }
        .cvx .meter .bar > i{ display: block; height: 100%; background: linear-gradient(90deg, var(--acc), var(--acc-soft)); }
        .cvx .meter .note{ font-size: 10.5px; color: var(--muted); margin-top: 7px; }

        /* ═══════════════════════════  AGREEMENT CARDS  ═══════════════════════════ */
        .cvx .ag{ border: 1px solid var(--line); border-radius: var(--r-md); background: var(--surface); overflow: hidden; height: 100%; display: flex; flex-direction: column; transition: transform .18s, box-shadow .18s, border-color .18s; }
        .cvx .ag:hover{ transform: translateY(-2px); box-shadow: var(--sh-md); border-color: var(--faint); }
        .cvx .ag .h{ display: flex; align-items: center; gap: 9px; padding: 10px 12px; border-bottom: 1px solid var(--line); background: var(--surface-2); }
        .cvx .ag .h .ic{ width: 30px; height: 30px; border-radius: 9px; display: grid; place-items: center; font-size: 13px; background: var(--acc-tint); color: var(--acc); flex: 0 0 auto; }
        .cvx .ag.sale .h .ic{ background: var(--plum-t); color: var(--plum); }
        .cvx .ag .h .t{ font-weight: 800; font-size: 12.5px; line-height: 1.2; }
        .cvx .ag .h .s{ font-size: 10.5px; color: var(--muted); }
        .cvx .ag .b{ padding: 4px 12px 8px; flex: 1 1 auto; }
        .cvx .ag .b .r{ display: flex; justify-content: space-between; gap: 10px; padding: 6px 0; border-bottom: 1px dashed var(--line); font-size: 11.5px; }
        .cvx .ag .b .r:last-child{ border-bottom: 0; }
        .cvx .ag .b .r span:first-child{ color: var(--muted); white-space: nowrap; }
        .cvx .ag .b .r span:last-child{ font-weight: 600; text-align: right; min-width: 0; overflow-wrap: anywhere; }
        .cvx .ag .b .r a{ color: var(--acc); text-decoration: none; }
        .cvx .ag .b .r a:hover{ text-decoration: underline; }
        .cvx .ag .f{ display: flex; gap: 6px; padding: 9px 12px; border-top: 1px solid var(--line); background: var(--surface-2); }
        .cvx .ag .f .btn{ flex: 1; }

        /* ═══════════════════════════  BAR LIST  ═══════════════════════════ */
        /* .barrow, not .row — .row is Bootstrap's and must keep its own meaning. */
        .cvx .barrow{ display: flex; align-items: center; gap: 12px; padding: 8px 0; border-bottom: 1px solid var(--line-soft); }
        .cvx .barrow:last-child{ border-bottom: 0; }
        .cvx .barrow .bl{ flex: 1 1 auto; min-width: 0; }
        .cvx .barrow .nm{ font-size: 12px; font-weight: 600; overflow-wrap: anywhere; }
        .cvx .barrow .nm a{ color: inherit; text-decoration: none; }
        .cvx .barrow .nm a:hover{ color: var(--acc); text-decoration: underline; }
        .cvx .barrow .bar{ height: 5px; border-radius: 999px; background: var(--surface-3); overflow: hidden; margin-top: 5px; }
        .cvx .barrow .bar > i{ display: block; height: 100%; border-radius: 999px; background: linear-gradient(90deg, var(--acc), var(--acc-soft)); }
        .cvx .barrow .qt{ flex: 0 0 56px; text-align: right; font-weight: 800; font-variant-numeric: tabular-nums; }

        /* ═══════════════════════════  NOTES  ═══════════════════════════ */
        .cvx .note{
            border: 1px solid var(--line); border-left: 3px solid var(--acc); border-radius: var(--r-md);
            background: var(--surface); padding: 11px 12px; height: 100%;
            transition: box-shadow .18s, border-color .18s;
        }
        .cvx .note:hover{ box-shadow: var(--sh-md); }
        .cvx .note.done{ border-left-color: var(--ok); background: var(--surface-2); }
        .cvx .note.done .note-body{ color: var(--muted); }
        .cvx .note-ic{ width: 30px; height: 30px; border-radius: 9px; display: grid; place-items: center; font-size: 13px; padding: 0; letter-spacing: 0; flex: 0 0 auto; }
        .cvx .note-body{ margin: 7px 0 6px; font-size: 12.5px; line-height: 1.55; white-space: pre-line; overflow-wrap: anywhere; }
        .cvx .note-foot{ font-size: 10.5px; color: var(--faint); }
        .cvx .note-foot i{ margin-right: 4px; }
        .cvx .note-hint{ font-size: 11px; color: var(--muted); margin-right: auto; }

        /* Livewire-driven sheet (no JS / no Bootstrap modal needed) */
        .cvx .cvx-sheet{ position: fixed; inset: 0; z-index: 1090; display: flex; align-items: center; justify-content: center; padding: 16px; }
        .cvx .cvx-sheet-backdrop{ position: absolute; inset: 0; background: rgba(8, 12, 20, .55); animation: cvxFade .18s ease; }
        .cvx .cvx-sheet-card{
            position: relative; width: 100%; max-width: 620px; max-height: calc(100vh - 32px); overflow: auto;
            background: var(--surface); border: 1px solid var(--line); border-radius: var(--r-lg);
            box-shadow: var(--sh-lg); animation: cvxRise .22s cubic-bezier(.22,1,.36,1);
        }
        .cvx .cvx-sheet-foot{
            display: flex; align-items: center; gap: 7px; flex-wrap: wrap; justify-content: flex-end;
            padding: 11px 14px; border-top: 1px solid var(--line); background: var(--surface-2);
            position: sticky; bottom: 0;
        }
        @keyframes cvxFade{ from{ opacity: 0; } to{ opacity: 1; } }
        @keyframes cvxRise{ from{ opacity: 0; transform: translateY(12px) scale(.99); } to{ opacity: 1; transform: none; } }

        .cvx .cvx-pager .pagination{ margin: 0; justify-content: center; flex-wrap: wrap; gap: 3px; --bs-pagination-font-size: 11.5px; }
        .cvx .cvx-pager .page-link{ border-radius: 8px; border-color: var(--line); color: var(--ink-2); }
        .cvx .cvx-pager .page-item.active .page-link{ background: var(--acc); border-color: var(--acc); color: #fff; }

        /* ═══════════════════════════  MISC  ═══════════════════════════ */
        .cvx .empty{ text-align: center; padding: 34px 16px; color: var(--faint); }
        .cvx .empty i{ font-size: 26px; display: block; margin-bottom: 9px; opacity: .5; }
        .cvx .alert-cv{ display: flex; gap: 9px; align-items: flex-start; padding: 9px 12px; border-radius: var(--r-md); font-size: 11.5px; margin-bottom: 11px; }
        .cvx .alert-cv.info{ background: var(--info-t); color: var(--info-e); }
        .cvx .alert-cv.bad{ background: var(--bad-t); color: var(--bad-e); }
        .cvx .tab-pane{ animation: cvxIn .28s cubic-bezier(.22,1,.36,1); }
        @keyframes cvxIn{ from{ opacity: 0; transform: translateY(6px); } to{ opacity: 1; transform: none; } }
        .cvx .loading-tab{ text-align: center; padding: 40px 16px; color: var(--faint); font-size: 12px; }

        /* ═══════════════════════════  RESPONSIVE  ═══════════════════════════ */
        /* Columns are Bootstrap's job; these only tune spacing/chrome. */
        @media (max-width: 767px){
            .cvx .cvx-sheet{ padding: 0; align-items: flex-end; }
            .cvx .cvx-sheet-card{ max-width: none; max-height: 92vh; border-radius: var(--r-lg) var(--r-lg) 0 0; }
        }
        @media (max-width: 575px){
            .cvx .cv-hero{ padding: 16px 14px 74px; border-radius: var(--r-lg); }
            .cvx .phead{ padding: 10px 12px; }
            .cvx .phead .right{ width: 100%; margin-left: 0; }
            .cvx .pbody{ padding: 11px 12px; }
            .cvx .filters{ padding: 10px 12px; }
            .cvx .hacts .b{ flex: 1 1 auto; justify-content: center; }
            .cvx .cvx-sheet-foot .btn{ flex: 1 1 auto; justify-content: center; }
        }
        </style>
    @endpush
@endonce

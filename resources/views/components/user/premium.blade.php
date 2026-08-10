{{--
    ╔══════════════════════════════════════════════════════════════════════╗
    ║  Users list — "Facet Rail" premium design system                      ║
    ║                                                                      ║
    ║  Scoped under .usrx so it cannot leak into the rest of the app.      ║
    ║                                                                      ║
    ║  Every colour derives from the active SETTINGS THEME (--bs-primary   ║
    ║  and the Bootstrap emphasis / secondary tokens), so the screen        ║
    ║  follows the tenant's chosen colour scheme and light / dark mode      ║
    ║  automatically. No hardcoded palette.                                ║
    ║                                                                      ║
    ║  Logical properties (inset-inline-*, margin-inline-*) are used        ║
    ║  throughout so the layout mirrors correctly under dir="rtl".         ║
    ║                                                                      ║
    ║  Preview: docs/users-premium-preview.html (direction C)              ║
    ╚══════════════════════════════════════════════════════════════════════╝
--}}
@once
    {{-- Pushed to the layout's styles stack, NOT emitted inline: a <style> tag
         rendered next to the component root would become a second root element
         and Livewire would bind to it instead of the real .usrx root. --}}
    @push('styles')
        <style>
            .usrx {
                /* ── Accent: single source → settings theme primary ───────── */
                --acc: var(--bs-primary);
                --acc-rgb: var(--bs-primary-rgb);
                --acc-d: color-mix(in srgb, var(--bs-primary), #000 14%);
                --acc-deep: color-mix(in srgb, var(--bs-primary), #000 44%);
                --acc-tint: color-mix(in srgb, var(--bs-primary), transparent 90%);
                --acc-tint-2: color-mix(in srgb, var(--bs-primary), transparent 82%);

                /* Neutral ramp — light; the dark ramp is redefined below */
                --surface: #ffffff;
                --surface-2: #f5f7fa;
                --surface-3: #eef1f6;
                --line: #e7ebf1;
                --line-soft: #eff2f6;
                --ink: var(--bs-emphasis-color);
                --ink-2: var(--bs-body-color);
                --muted: var(--bs-secondary-color);
                --faint: var(--bs-tertiary-color);

                --ok: var(--bs-success);
                --ok-rgb: var(--bs-success-rgb);
                --info: var(--bs-info);
                --info-rgb: var(--bs-info-rgb);
                --warn: var(--bs-warning);
                --warn-rgb: var(--bs-warning-rgb);
                --bad: var(--bs-danger);
                --bad-rgb: var(--bs-danger-rgb);

                --shadow: 0 1px 2px rgba(16, 24, 40, .05), 0 8px 24px -10px rgba(16, 24, 40, .12);
                --shadow-lg: 0 18px 42px -18px rgba(var(--acc-rgb), .40), 0 8px 18px -12px rgba(16, 24, 40, .20);

                /* One base size so density is tunable from a single place */
                --usrx-fz: 12.5px;

                color: var(--ink);
                font-size: var(--usrx-fz);
                line-height: 1.5;
                -webkit-font-smoothing: antialiased;
            }

            .usrx *, .usrx *::before, .usrx *::after { box-sizing: border-box; }

            [data-bs-theme="dark"] .usrx {
                --surface: #272d34;
                --surface-2: #2e353d;
                --surface-3: #333b44;
                --line: #3a424c;
                --line-soft: #343c45;
                --acc-tint: color-mix(in srgb, var(--bs-primary), transparent 85%);
                --acc-tint-2: color-mix(in srgb, var(--bs-primary), transparent 75%);
                --shadow: 0 1px 2px rgba(0, 0, 0, .4), 0 10px 28px -10px rgba(0, 0, 0, .5);
                --shadow-lg: 0 18px 44px -18px rgba(0, 0, 0, .6), 0 8px 18px -12px rgba(0, 0, 0, .5);
            }

            .usrx .u-card {
                background: var(--surface);
                border: 1px solid var(--line);
                border-radius: 16px;
                box-shadow: var(--shadow);
                overflow: hidden;
            }

            /* ── HERO ─────────────────────────────────────────────────────── */
            .usrx-hero {
                position: relative;
                border-radius: 16px;
                overflow: hidden;
                margin-bottom: 12px;
                box-shadow: var(--shadow-lg);
                background:
                    radial-gradient(120% 165% at 100% 0, color-mix(in srgb, var(--acc) 30%, transparent), transparent 55%),
                    linear-gradient(125deg, var(--acc-deep), var(--acc-d));
            }
            .usrx-hero .glow {
                position: absolute; inset-inline-end: -60px; top: -90px;
                width: 300px; height: 300px; border-radius: 50%;
                background: radial-gradient(circle, rgba(255, 255, 255, .16), transparent 65%);
                pointer-events: none;
            }
            .usrx-hero .mesh {
                position: absolute; inset: 0; opacity: .5; pointer-events: none;
                background-image:
                    linear-gradient(rgba(255, 255, 255, .05) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(255, 255, 255, .05) 1px, transparent 1px);
                background-size: 34px 34px;
                -webkit-mask-image: radial-gradient(120% 100% at 0 0, #000, transparent 70%);
                mask-image: radial-gradient(120% 100% at 0 0, #000, transparent 70%);
            }
            .usrx-hero-inner {
                position: relative; display: flex; align-items: center;
                gap: 15px; padding: 16px 18px; flex-wrap: wrap;
            }
            .usrx-hero .doc-ic {
                width: 46px; height: 46px; border-radius: 13px; flex: 0 0 auto;
                background: rgba(255, 255, 255, .14); border: 1px solid rgba(255, 255, 255, .22);
                display: flex; align-items: center; justify-content: center;
                font-size: 20px; color: #fff; box-shadow: inset 0 1px 0 rgba(255, 255, 255, .25);
            }
            .usrx-hero .h-main { flex: 1; min-width: 200px; color: #fff; }
            .usrx-hero .h-eyebrow {
                font-size: 9.5px; font-weight: 800; letter-spacing: 2px;
                text-transform: uppercase; color: rgba(255, 255, 255, .72);
            }
            .usrx-hero .h-ref {
                font-size: 21px; font-weight: 800; letter-spacing: .2px;
                line-height: 1.15; margin-top: 2px; color: #fff;
            }
            .usrx-hero .h-meta {
                display: flex; gap: 14px; flex-wrap: wrap; margin-top: 5px;
                font-size: 11.5px; color: rgba(255, 255, 255, .86);
            }
            .usrx-hero .h-meta i { opacity: .82; margin-inline-end: 5px; }
            .usrx-hero .h-right { display: flex; gap: 8px; flex-wrap: wrap; }
            .usrx-hero .btn-hero {
                background: #fff; color: var(--acc-deep); border: 0;
                padding: 9px 15px; border-radius: 10px; font-size: 11.5px; font-weight: 800;
                cursor: pointer; text-decoration: none; display: inline-flex; align-items: center;
                gap: 7px; box-shadow: 0 4px 12px rgba(0, 0, 0, .18); transition: transform .12s;
            }
            .usrx-hero .btn-hero:hover { transform: translateY(-1px); color: var(--acc-deep); }
            .usrx-hero .btn-hero.ghost {
                background: rgba(255, 255, 255, .14); color: #fff;
                border: 1px solid rgba(255, 255, 255, .28); box-shadow: none;
            }
            .usrx-hero .btn-hero.ghost:hover { color: #fff; }

            /* ── LAYOUT: facet rail + roster ──────────────────────────────── */
            .usrx .u-layout {
                display: grid;
                grid-template-columns: 240px minmax(0, 1fr);
                gap: 12px;
                align-items: start;
            }
            @media (max-width: 991.98px) {
                .usrx .u-layout { grid-template-columns: minmax(0, 1fr); }
            }

            /* ── FACET RAIL ───────────────────────────────────────────────── */
            .usrx .rail {
                background: var(--surface); border: 1px solid var(--line);
                border-radius: 16px; box-shadow: var(--shadow); overflow: hidden;
            }
            .usrx .rail + .rail { margin-top: 12px; }
            .usrx .rail .rh {
                padding: 11px 14px; border-bottom: 1px solid var(--line-soft);
                display: flex; align-items: center; justify-content: space-between; gap: 8px;
            }
            .usrx .rail .rh .t {
                font-size: 9.5px; font-weight: 800; letter-spacing: .9px; text-transform: uppercase;
                color: var(--muted); display: flex; align-items: center; gap: 6px;
            }
            .usrx .rail .rh .t i { color: var(--acc); }
            .usrx .rail .rh .rst {
                font-size: 10.5px; color: var(--faint); font-weight: 700;
                background: none; border: 0; padding: 0; cursor: pointer;
            }
            .usrx .rail .rh .rst:hover { color: var(--acc-d); }
            .usrx .facets { padding: 7px; max-height: 320px; overflow-y: auto; }
            .usrx .facet {
                display: flex; align-items: center; gap: 9px; width: 100%;
                padding: 8px 10px; border: 0; border-radius: 10px; background: transparent;
                cursor: pointer; font-size: 12px; font-weight: 700; color: var(--ink-2);
                text-align: start; transition: background .13s, color .13s;
            }
            .usrx .facet:hover { background: var(--surface-2); }
            .usrx .facet.on { background: var(--acc-tint); color: var(--acc-d); }
            .usrx .facet.is-empty { color: var(--faint); }
            .usrx .facet .sw {
                width: 8px; height: 8px; border-radius: 3px;
                background: var(--faint); flex: 0 0 auto;
            }
            .usrx .facet.on .sw { background: var(--acc); }
            .usrx .facet .n { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .usrx .facet .c {
                font-size: 10.5px; font-weight: 800; color: var(--muted);
                background: var(--surface-2); border: 1px solid var(--line);
                border-radius: 20px; padding: 1px 7px; flex: 0 0 auto;
            }
            .usrx .facet.on .c { background: var(--surface); border-color: transparent; color: var(--acc-d); }

            /* ── TOOLBAR ──────────────────────────────────────────────────── */
            .usrx .u-toolbar {
                display: flex; align-items: center; gap: 9px; flex-wrap: wrap;
                padding: 13px 15px; border-bottom: 1px solid var(--line-soft);
            }
            .usrx .u-search {
                flex: 1; min-width: 190px; display: flex; align-items: center; gap: 9px;
                background: var(--surface-2); border: 1px solid var(--line); border-radius: 11px;
                padding: 9px 13px; transition: border-color .15s, box-shadow .15s;
            }
            .usrx .u-search:focus-within { border-color: var(--acc); box-shadow: 0 0 0 3px var(--acc-tint); }
            .usrx .u-search i { color: var(--muted); }
            .usrx .u-search input {
                border: 0; background: transparent; outline: none; width: 100%;
                font-size: var(--usrx-fz); color: var(--ink); font-family: inherit;
            }
            .usrx .u-select { position: relative; }
            .usrx .u-select select {
                appearance: none; -webkit-appearance: none;
                border: 1px solid var(--line); background: var(--surface); color: var(--ink);
                padding-block: 9px; padding-inline: 13px 32px; border-radius: 10px;
                font-size: 11.5px; font-weight: 700; cursor: pointer; font-family: inherit; width: 100%;
            }
            .usrx .u-select select:focus { outline: none; border-color: var(--acc); box-shadow: 0 0 0 3px var(--acc-tint); }
            .usrx .u-select::after {
                content: "\f107"; font-family: FontAwesome; position: absolute;
                inset-inline-end: 11px; top: 50%; transform: translateY(-50%);
                color: var(--muted); pointer-events: none; font-size: 12px;
            }

            /* segmented view switch */
            .usrx .seg {
                display: inline-flex; background: var(--surface-2); border: 1px solid var(--line);
                border-radius: 10px; padding: 3px; gap: 2px;
            }
            .usrx .seg button {
                border: 0; background: transparent; color: var(--muted); font-size: 11px;
                font-weight: 750; padding: 6px 11px; border-radius: 8px; cursor: pointer;
                font-family: inherit; display: inline-flex; align-items: center; gap: 5px; transition: .15s;
            }
            .usrx .seg button.on { background: var(--surface); color: var(--acc-d); box-shadow: 0 1px 3px rgba(16, 24, 40, .14); }

            /* ── ACTIVE FILTER CHIPS ──────────────────────────────────────── */
            .usrx .chips {
                display: flex; align-items: center; gap: 7px; flex-wrap: wrap;
                padding: 10px 15px; border-bottom: 1px solid var(--line-soft);
            }
            .usrx .chips .lbl {
                font-size: 9.5px; font-weight: 800; letter-spacing: .6px;
                text-transform: uppercase; color: var(--faint);
            }
            .usrx .chip {
                display: inline-flex; align-items: center; gap: 6px;
                background: var(--acc-tint); color: var(--acc-d);
                border: 1px solid color-mix(in srgb, var(--acc), transparent 70%);
                border-radius: 20px; padding-block: 4px; padding-inline: 10px 6px;
                font-size: 11px; font-weight: 750;
            }
            .usrx .chip b { font-weight: 800; }
            .usrx .chip button {
                border: 0; background: transparent; color: inherit; cursor: pointer;
                opacity: .6; width: 16px; height: 16px; border-radius: 50%; padding: 0;
                display: inline-flex; align-items: center; justify-content: center; font-size: 10px;
            }
            .usrx .chip button:hover { opacity: 1; background: rgba(var(--acc-rgb), .18); }
            .usrx .chip-clear {
                background: transparent; border: 0; color: var(--muted); padding: 4px 8px;
                cursor: pointer; text-decoration: underline; font-size: 11px; font-weight: 700;
            }
            .usrx .chip-clear:hover { color: var(--bad); }

            /* ── AVATARS ──────────────────────────────────────────────────── */
            .usrx .av { position: relative; flex: 0 0 auto; }
            .usrx .av img, .usrx .av .ini { display: block; border-radius: 50%; object-fit: cover; }
            .usrx .av .ini {
                display: flex; align-items: center; justify-content: center;
                font-weight: 800; color: #fff; letter-spacing: .4px;
                background: linear-gradient(135deg, var(--acc-d), var(--acc-deep));
            }
            .usrx .av.s-lg img, .usrx .av.s-lg .ini { width: 56px; height: 56px; font-size: 18px; }
            .usrx .av.s-md img, .usrx .av.s-md .ini { width: 40px; height: 40px; font-size: 14px; }
            .usrx .av .dot {
                position: absolute; inset-inline-end: 0; bottom: 0;
                width: 12px; height: 12px; border-radius: 50%;
                border: 2.5px solid var(--surface); background: var(--faint);
            }
            .usrx .av.on .dot { background: var(--ok); }
            .usrx .av.off .ini, .usrx .av.off img { filter: grayscale(.55); opacity: .85; }

            /* ── BADGES ───────────────────────────────────────────────────── */
            .usrx .bdg {
                display: inline-flex; align-items: center; gap: 5px;
                padding: 3px 9px; border-radius: 20px; font-size: 10.5px;
                font-weight: 750; white-space: nowrap;
            }
            .usrx .bdg.role {
                background: var(--acc-tint); color: var(--acc-d);
                border: 1px solid color-mix(in srgb, var(--acc), transparent 76%);
            }
            .usrx .bdg.role.admin {
                background: rgba(var(--warn-rgb), .12);
                color: color-mix(in srgb, var(--warn), #000 18%);
                border-color: rgba(var(--warn-rgb), .3);
            }
            .usrx .bdg.desig {
                background: rgba(var(--info-rgb), .11);
                color: color-mix(in srgb, var(--info), #000 16%);
                border: 1px solid rgba(var(--info-rgb), .26);
            }
            .usrx .bdg.ok {
                background: rgba(var(--ok-rgb), .12);
                color: color-mix(in srgb, var(--ok), #000 8%);
                border: 1px solid rgba(var(--ok-rgb), .28);
            }
            .usrx .bdg.off { background: var(--surface-2); color: var(--muted); border: 1px solid var(--line); }
            .usrx .bdg.none { background: transparent; color: var(--faint); border: 1px dashed var(--line); }
            [data-bs-theme="dark"] .usrx .bdg.desig { color: color-mix(in srgb, var(--info), #fff 24%); }
            [data-bs-theme="dark"] .usrx .bdg.role.admin { color: color-mix(in srgb, var(--warn), #fff 26%); }
            [data-bs-theme="dark"] .usrx .bdg.ok { color: color-mix(in srgb, var(--ok), #fff 24%); }

            /* ── ROSTER ROWS (list view) ──────────────────────────────────── */
            .usrx .rowlist { display: grid; }
            .usrx .urow {
                display: flex; align-items: center; gap: 13px; padding: 12px 15px;
                border-bottom: 1px solid var(--line-soft); position: relative;
                transition: background .12s;
            }
            .usrx .urow:last-child { border-bottom: 0; }
            .usrx .urow:hover { background: var(--acc-tint); }
            .usrx .urow::before {
                content: ""; position: absolute; inset-inline-start: 0; top: 0; bottom: 0;
                width: 3px; background: transparent; transition: background .14s;
            }
            .usrx .urow:hover::before { background: var(--acc); }
            .usrx .urow .main { flex: 1 1 200px; min-width: 0; }
            .usrx .urow .main .nm { font-size: 13.5px; font-weight: 800; color: var(--ink); letter-spacing: -.1px; }
            .usrx .urow .main .nm a { color: inherit; text-decoration: none; }
            .usrx .urow .main .nm a:hover { color: var(--acc-d); }
            .usrx .urow .main .sub {
                display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
                margin-top: 3px; font-size: 11px; color: var(--muted);
            }
            .usrx .urow .main .sub i { margin-inline-end: 5px; opacity: .75; }
            .usrx .urow .tags { display: flex; gap: 5px; flex-wrap: wrap; flex: 0 1 auto; }
            .usrx .urow .meta { text-align: end; flex: 0 0 auto; min-width: 96px; }
            .usrx .urow .meta .k {
                font-size: 9px; font-weight: 800; letter-spacing: .6px;
                text-transform: uppercase; color: var(--faint);
            }
            .usrx .urow .meta .v { font-size: 11.5px; font-weight: 750; color: var(--ink-2); margin-top: 2px; }
            .usrx .urow .go {
                width: 30px; height: 30px; border-radius: 9px; border: 1px solid var(--line);
                background: var(--surface); color: var(--muted); flex: 0 0 auto;
                display: inline-flex; align-items: center; justify-content: center; text-decoration: none;
            }
            .usrx .urow:hover .go { border-color: var(--acc); color: var(--acc-d); }
            [dir="rtl"] .usrx .urow .go i { transform: scaleX(-1); }
            @media (max-width: 575.98px) {
                .usrx .urow .meta { display: none; }
                .usrx .urow .tags { flex-basis: 100%; }
            }

            /* ── ROSTER CARDS (grid view) ─────────────────────────────────── */
            .usrx .u-grid {
                display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 12px; padding: 15px;
            }
            .usrx .ucard {
                position: relative; background: var(--surface); border: 1px solid var(--line);
                border-radius: 15px; overflow: hidden;
                transition: transform .16s, box-shadow .16s, border-color .16s;
            }
            .usrx .ucard:hover {
                transform: translateY(-3px);
                border-color: color-mix(in srgb, var(--acc), transparent 55%);
                box-shadow: 0 16px 34px -18px rgba(var(--acc-rgb), .5);
            }
            .usrx .ucard .cap {
                height: 52px; position: relative;
                background:
                    radial-gradient(90% 140% at 100% 0, color-mix(in srgb, var(--acc) 34%, transparent), transparent 60%),
                    linear-gradient(120deg, var(--acc-deep), var(--acc-d));
            }
            .usrx .ucard .cap .st { position: absolute; top: 9px; inset-inline-end: 10px; }
            .usrx .ucard .cap .st .bdg {
                background: rgba(255, 255, 255, .18); color: #fff;
                border: 1px solid rgba(255, 255, 255, .3);
            }
            .usrx .ucard .body { padding: 0 14px 13px; margin-top: -26px; }
            .usrx .ucard .av img, .usrx .ucard .av .ini {
                border: 3px solid var(--surface); box-shadow: 0 4px 12px rgba(16, 24, 40, .16);
            }
            .usrx .ucard .nm { font-size: 14.5px; font-weight: 800; color: var(--ink); margin-top: 9px; letter-spacing: -.2px; }
            .usrx .ucard .nm a { color: inherit; text-decoration: none; }
            .usrx .ucard .nm a:hover { color: var(--acc-d); }
            .usrx .ucard .dg { font-size: 11px; font-weight: 750; color: var(--muted); margin-top: 2px; display: flex; align-items: center; gap: 5px; }
            .usrx .ucard .dg i { color: var(--acc); font-size: 11px; }
            .usrx .ucard .roles { display: flex; gap: 5px; flex-wrap: wrap; margin-top: 10px; }
            .usrx .ucard .lines { margin-top: 11px; padding-top: 10px; border-top: 1px dashed var(--line); display: grid; gap: 6px; }
            .usrx .ucard .ln { display: flex; align-items: center; gap: 8px; font-size: 11.5px; color: var(--ink-2); min-width: 0; }
            .usrx .ucard .ln i {
                width: 22px; height: 22px; border-radius: 7px; background: var(--surface-2);
                color: var(--muted); display: inline-flex; align-items: center;
                justify-content: center; font-size: 11px; flex: 0 0 auto;
            }
            .usrx .ucard .ln span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .usrx .ucard .acts {
                display: flex; gap: 6px; padding: 10px 14px;
                border-top: 1px solid var(--line-soft); background: var(--surface-2);
            }
            .usrx .ucard .acts a {
                flex: 1; text-align: center; padding: 7px 0; border-radius: 9px;
                font-size: 11px; font-weight: 750; text-decoration: none; color: var(--ink-2);
                background: var(--surface); border: 1px solid var(--line); transition: .14s;
            }
            .usrx .ucard .acts a:hover { border-color: var(--acc); color: var(--acc-d); }
            .usrx .ucard .acts a.key { background: var(--acc-tint); border-color: transparent; color: var(--acc-d); }

            /* ── EMPTY STATE ──────────────────────────────────────────────── */
            .usrx .empty { padding: 52px 20px; text-align: center; }
            .usrx .empty > i { font-size: 34px; color: var(--faint); opacity: .5; }
            .usrx .empty h5 { margin: 12px 0 4px; font-size: 14px; font-weight: 800; color: var(--ink); }
            .usrx .empty p { margin: 0 0 12px; font-size: 12px; color: var(--muted); }

            /* ── FOOTER + PAGINATION ──────────────────────────────────────── */
            .usrx .u-foot {
                display: flex; align-items: center; justify-content: space-between;
                gap: 12px; flex-wrap: wrap; padding: 12px 15px;
                border-top: 1px solid var(--line-soft); background: var(--surface-2);
            }
            .usrx .u-foot .cnt { font-size: 11.5px; color: var(--muted); font-weight: 650; }
            .usrx .u-foot nav { margin: 0; }
            .usrx .u-foot .pagination { margin: 0; gap: 5px; flex-wrap: wrap; }
            .usrx .u-foot .page-link {
                border: 1px solid var(--line); background: var(--surface); color: var(--ink-2);
                border-radius: 9px; font-size: 11.5px; font-weight: 750; padding: 6px 11px;
            }
            .usrx .u-foot .page-link:hover { border-color: var(--acc); color: var(--acc-d); background: var(--acc-tint); }
            .usrx .u-foot .page-item.active .page-link {
                background: var(--acc); border-color: var(--acc); color: #fff;
            }
            .usrx .u-foot .page-item.disabled .page-link { color: var(--faint); background: var(--surface); }

            /* Buttons shared by the empty state / hero fallbacks */
            .usrx .btn-x {
                display: inline-flex; align-items: center; gap: 7px; border: 1px solid var(--line);
                background: var(--surface); color: var(--ink-2); padding: 9px 13px; border-radius: 10px;
                font-size: 11.5px; font-weight: 750; cursor: pointer; font-family: inherit;
                white-space: nowrap; text-decoration: none; transition: .15s;
            }
            .usrx .btn-x:hover { border-color: var(--acc); color: var(--acc-d); }
        </style>
    @endpush
@endonce

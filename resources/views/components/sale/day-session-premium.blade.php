{{--
    ╔══════════════════════════════════════════════════════════════════════╗
    ║  Day Session — view page · ".dsv" premium compact design system       ║
    ║  Sibling of the Day Session Management ".dsx" system: same token      ║
    ║  vocabulary, accent = SETTINGS THEME colour (--bs-primary), dark mode ║
    ║  via [data-bs-theme="dark"]. Included by sale/day-session-details and ║
    ║  the day-session-sales-list Livewire view (@once dedupes).            ║
    ╚══════════════════════════════════════════════════════════════════════╝
--}}
@once
    <style>
        .dsv {
            --acc: var(--bs-primary, #3457d5);
            --acc-rgb: var(--bs-primary-rgb, 52, 87, 213);
            --acc-bright: color-mix(in srgb, var(--acc), #fff 24%);
            --acc-deep: color-mix(in srgb, var(--acc), #000 34%);
            --ink: #111826; --ink-soft: #2c3648; --muted: #64708a; --muted-2: #93a0b8;
            --line: #e2e8f2; --line-soft: #eef2f8; --card: #ffffff; --card-2: #f5f8fc;
            --green: #1f9d63; --green-rgb: 31, 157, 99;
            --amber: #c98a12; --amber-rgb: 201, 138, 18;
            --red: #d64545; --red-rgb: 214, 69, 69;
            --mono: ui-monospace, 'SF Mono', 'Cascadia Code', Menlo, monospace;
            --ease: cubic-bezier(.22, 1, .36, 1);
            --sh-sm: 0 1px 2px rgba(17, 24, 38, .04), 0 4px 14px -8px rgba(17, 24, 38, .10);
            --sh-md: 0 8px 30px -14px rgba(17, 24, 38, .22);
            --fz: 12.5px;
            color: var(--ink); font-size: var(--fz); line-height: 1.45; -webkit-font-smoothing: antialiased;
        }
        [data-bs-theme="dark"] .dsv {
            --ink: #eaf0fb; --ink-soft: #c2cbdd; --muted: #8b96ad; --muted-2: #626d84;
            --line: rgba(255, 255, 255, .10); --line-soft: rgba(255, 255, 255, .06);
            --card: #161b28; --card-2: #1b2130;
            --acc-bright: color-mix(in srgb, var(--acc), #fff 30%);
            --acc-deep: color-mix(in srgb, var(--acc), #fff 8%);
            --green: #43c98a; --amber: #e0ab4a; --red: #f16b6b;
            --sh-sm: 0 1px 2px rgba(0, 0, 0, .4), 0 4px 14px -8px rgba(0, 0, 0, .6);
            --sh-md: 0 10px 34px -14px rgba(0, 0, 0, .7);
        }
        .dsv * { box-sizing: border-box; }
        .dsv a { text-decoration: none; color: inherit; }
        .dsv [x-cloak] { display: none !important; }
        .dsv .num { font-variant-numeric: tabular-nums; white-space: nowrap; }
        .dsv .t-green { color: var(--green); } .dsv .t-red { color: var(--red); } .dsv .t-amber { color: var(--amber); }
        .dsv .t-acc { color: var(--acc); } .dsv .t-muted { color: var(--muted); }
        .dsv .fw { font-weight: 700; }
        .dsv .stack > * + * { margin-top: 12px; }

        /* ---- pills ---- */
        .dsv .pill { display: inline-flex; align-items: center; gap: 6px; font-family: var(--mono); font-size: 10px; letter-spacing: .1em; text-transform: uppercase; padding: 4px 10px; border-radius: 99px; border: 1px solid var(--line); line-height: 1; white-space: nowrap; color: var(--muted); background: var(--card); }
        .dsv .pill--green { color: var(--green); background: rgba(var(--green-rgb), .10); border-color: rgba(var(--green-rgb), .28); }
        .dsv .pill--amber { color: var(--amber); background: rgba(var(--amber-rgb), .10); border-color: rgba(var(--amber-rgb), .28); }
        .dsv .pill--accent { color: var(--acc); background: rgba(var(--acc-rgb), .10); border-color: rgba(var(--acc-rgb), .28); }
        .dsv .pill--red { color: var(--red); background: rgba(var(--red-rgb), .10); border-color: rgba(var(--red-rgb), .28); }
        .dsv .pulse { width: 7px; height: 7px; border-radius: 50%; background: currentColor; animation: dsvpulse 2s infinite; }
        @keyframes dsvpulse { 0% { box-shadow: 0 0 0 0 rgba(var(--green-rgb), .5); } 70% { box-shadow: 0 0 0 7px rgba(var(--green-rgb), 0); } 100% { box-shadow: 0 0 0 0 rgba(var(--green-rgb), 0); } }

        /* ---- hero ---- */
        .dsv .hero { position: relative; overflow: hidden; border-radius: 18px; border: 1px solid var(--line); background: radial-gradient(120% 140% at 100% 0%, rgba(var(--acc-rgb), .16), transparent 55%), linear-gradient(150deg, var(--card), var(--card-2)); box-shadow: var(--sh-md); padding: clamp(14px, 2vw, 20px); }
        .dsv .hero::after { content: ''; position: absolute; inset-inline-end: -60px; top: -80px; width: 300px; height: 300px; background: radial-gradient(circle, rgba(var(--acc-rgb), .22), transparent 62%); pointer-events: none; }
        .dsv .hero__top { display: flex; align-items: flex-start; justify-content: space-between; gap: 14px; flex-wrap: wrap; position: relative; z-index: 1; }
        .dsv .hero__id { display: flex; align-items: center; gap: 14px; min-width: 0; }
        .dsv .hero__glyph { width: 46px; height: 46px; border-radius: 13px; flex: none; display: grid; place-items: center; font-size: 19px; color: #fff; background: linear-gradient(150deg, var(--acc-bright), var(--acc-deep)); box-shadow: 0 10px 22px -10px rgba(var(--acc-rgb), .7); }
        .dsv .hero__glyph.is-open { background: linear-gradient(150deg, var(--green), #0f6a41); box-shadow: 0 10px 22px -10px rgba(var(--green-rgb), .6); }
        .dsv .hero__row { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .dsv .hero__title { font-size: clamp(18px, 2.4vw, 24px); font-weight: 700; line-height: 1.05; letter-spacing: -.02em; margin: 0; color: var(--ink); }
        .dsv .hero__sub { color: var(--muted); font-size: 12.5px; margin-top: 5px; display: flex; gap: 12px; flex-wrap: wrap; }
        .dsv .hero__sub i { color: var(--acc); margin-inline-end: 5px; }
        .dsv .hero__sub b { color: var(--ink-soft); font-weight: 600; }
        .dsv .hero__actions { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
        .dsv .hbtn { display: inline-flex; align-items: center; gap: 7px; height: 34px; padding: 0 14px; border-radius: 10px; font-weight: 600; font-size: 12.5px; border: 1px solid var(--line); background: var(--card); color: var(--ink-soft); box-shadow: var(--sh-sm); transition: transform .15s var(--ease), box-shadow .2s, border-color .15s, color .15s; white-space: nowrap; cursor: pointer; }
        .dsv .hbtn i { font-size: 12px; }
        .dsv .hbtn:hover { transform: translateY(-2px); border-color: var(--acc); color: var(--acc); }
        .dsv .hbtn--green { background: linear-gradient(135deg, var(--green), #0f7a4a); color: #fff; border: none; box-shadow: 0 10px 24px -12px rgba(var(--green-rgb), .9); }
        .dsv .hbtn--accent { background: linear-gradient(135deg, var(--acc-bright), var(--acc-deep)); color: #fff; border: none; box-shadow: 0 10px 24px -12px rgba(var(--acc-rgb), .9); }
        .dsv .hbtn--green:hover, .dsv .hbtn--accent:hover { color: #fff; }

        /* ---- kpis ---- */
        .dsv .kpis { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-top: 16px; position: relative; z-index: 1; }
        .dsv .kpi { background: var(--card); border: 1px solid var(--line); border-radius: 13px; padding: 11px 12px 10px; position: relative; overflow: hidden; transition: transform .25s var(--ease), box-shadow .25s var(--ease); }
        .dsv .kpi:hover { transform: translateY(-2px); box-shadow: var(--sh-md); }
        .dsv .kpi__rail { position: absolute; inset-inline-start: 0; top: 0; bottom: 0; width: 3px; border-radius: 3px; }
        .dsv .kpi__ic { width: 28px; height: 28px; border-radius: 8px; display: grid; place-items: center; font-size: 13px; margin-bottom: 8px; }
        .dsv .kpi__lbl { font-family: var(--mono); font-size: 9px; letter-spacing: .09em; text-transform: uppercase; color: var(--muted); }
        .dsv .kpi__val { font-size: clamp(17px, 2vw, 22px); font-weight: 700; letter-spacing: -.02em; margin-top: 2px; color: var(--ink); font-variant-numeric: tabular-nums; }
        .dsv .kpi__val small { font-size: 11px; color: var(--muted); font-weight: 500; margin-inline-start: 3px; }
        .dsv .kpi__foot { margin-top: 7px; font-size: 10.5px; color: var(--muted); display: flex; align-items: center; gap: 5px; flex-wrap: wrap; }
        .dsv .kpi__foot b { font-weight: 700; }
        .dsv .i-green { color: var(--green); background: rgba(var(--green-rgb), .12); }
        .dsv .i-accent { color: var(--acc); background: rgba(var(--acc-rgb), .12); }
        .dsv .i-amber { color: var(--amber); background: rgba(var(--amber-rgb), .12); }
        .dsv .i-deep { color: var(--acc-deep); background: rgba(var(--acc-rgb), .14); }
        .dsv .i-red { color: var(--red); background: rgba(var(--red-rgb), .12); }
        .dsv .i-muted { color: var(--muted); background: var(--card-2); }
        .dsv .r-green { background: var(--green); } .dsv .r-accent { background: var(--acc); } .dsv .r-amber { background: var(--amber); } .dsv .r-deep { background: var(--acc-deep); } .dsv .r-red { background: var(--red); }

        /* ---- panels ---- */
        .dsv .grid2 { display: grid; grid-template-columns: 1.05fr .95fr; gap: 12px; margin-top: 12px; align-items: start; }
        .dsv .panel { background: var(--card); border: 1px solid var(--line); border-radius: 14px; box-shadow: var(--sh-sm); overflow: hidden; }
        .dsv .panel__head { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 10px 14px; border-bottom: 1px solid var(--line-soft); flex-wrap: wrap; }
        .dsv .panel__head h3 { font-size: 14px; font-weight: 700; display: flex; align-items: center; gap: 8px; margin: 0; color: var(--ink); }
        .dsv .panel__head h3 i { color: var(--acc); font-size: 13px; }
        .dsv .panel__aside { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
        .dsv .panel__body { padding: 14px; }

        /* ---- reconciliation ---- */
        .dsv .recon { display: grid; gap: 9px; }
        .dsv .recon__row { display: flex; align-items: baseline; justify-content: space-between; gap: 10px; }
        .dsv .recon__row .k { color: var(--muted); font-size: 12.5px; }
        .dsv .recon__row .v { font-weight: 700; font-size: 15px; color: var(--ink); font-variant-numeric: tabular-nums; }
        .dsv .recon__row.big { border-top: 1px solid var(--line-soft); padding-top: 10px; }
        .dsv .recon__row.big .v { font-size: 19px; }
        .dsv .recon__bar { height: 9px; border-radius: 99px; background: var(--card-2); border: 1px solid var(--line); overflow: hidden; display: flex; }
        .dsv .recon__bar span { display: block; height: 100%; }
        .dsv .b-open { background: linear-gradient(90deg, var(--acc), var(--acc-bright)); }
        .dsv .b-sales { background: linear-gradient(90deg, var(--green), #37c98a); }
        .dsv .recon__legend { display: flex; gap: 14px; font-size: 11px; color: var(--muted); flex-wrap: wrap; }
        .dsv .recon__legend span { display: inline-flex; align-items: center; gap: 6px; }
        .dsv .sw { width: 10px; height: 10px; border-radius: 3px; }
        .dsv .variance { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 9px 12px; border-radius: 11px; border: 1px dashed var(--line); background: var(--card-2); }
        .dsv .variance .lab { font-family: var(--mono); font-size: 9.5px; letter-spacing: .1em; text-transform: uppercase; color: var(--muted); }
        .dsv .variance .tag { font-size: 11px; font-weight: 600; color: var(--muted); margin-top: 1px; }
        .dsv .variance .amt { font-weight: 800; font-size: 18px; font-variant-numeric: tabular-nums; color: var(--muted); white-space: nowrap; }
        .dsv .variance.ok { border-color: rgba(var(--green-rgb), .4); background: rgba(var(--green-rgb), .08); }
        .dsv .variance.ok .amt, .dsv .variance.ok .tag { color: var(--green); }
        .dsv .variance.short { border-color: rgba(var(--red-rgb), .4); background: rgba(var(--red-rgb), .08); }
        .dsv .variance.short .amt, .dsv .variance.short .tag { color: var(--red); }
        .dsv .variance.over { border-color: rgba(var(--amber-rgb), .4); background: rgba(var(--amber-rgb), .08); }
        .dsv .variance.over .amt, .dsv .variance.over .tag { color: var(--amber); }

        /* ---- timeline ---- */
        .dsv .tl { position: relative; padding-inline-start: 24px; display: grid; gap: 14px; }
        .dsv .tl::before { content: ''; position: absolute; inset-inline-start: 7px; top: 6px; bottom: 6px; width: 2px; background: var(--line); border-radius: 2px; }
        .dsv .tl__item { position: relative; }
        .dsv .tl__dot { position: absolute; inset-inline-start: -24px; top: 1px; width: 16px; height: 16px; border-radius: 50%; display: grid; place-items: center; font-size: 7px; color: #fff; background: var(--muted-2); box-shadow: 0 0 0 3px var(--card); }
        .dsv .tl__dot.green { background: var(--green); } .dsv .tl__dot.red { background: var(--red); } .dsv .tl__dot.acc { background: var(--acc); }
        .dsv .tl__dot.ghost { background: var(--card); border: 2px dashed var(--muted-2); color: var(--muted-2); }
        .dsv .tl__head { display: flex; align-items: baseline; justify-content: space-between; gap: 8px; flex-wrap: wrap; }
        .dsv .tl__title { font-weight: 700; font-size: 13px; color: var(--ink); }
        .dsv .tl__time { font-family: var(--mono); font-size: 10.5px; color: var(--muted); white-space: nowrap; }
        .dsv .tl__meta { color: var(--muted); font-size: 12px; margin-top: 3px; display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
        .dsv .av { width: 20px; height: 20px; border-radius: 50%; display: inline-grid; place-items: center; font-size: 9px; font-weight: 700; color: #fff; background: linear-gradient(135deg, var(--acc-bright), var(--acc-deep)); text-transform: uppercase; flex: none; }
        .dsv .tl__chips { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 7px; }
        .dsv .chip { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 7px; background: var(--card-2); border: 1px solid var(--line); color: var(--ink-soft); font-variant-numeric: tabular-nums; white-space: nowrap; }
        .dsv .chip b { font-family: var(--mono); font-size: 9px; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); font-weight: 600; }
        .dsv .chip--green { color: var(--green); background: rgba(var(--green-rgb), .10); border-color: rgba(var(--green-rgb), .28); }
        .dsv .chip--red { color: var(--red); background: rgba(var(--red-rgb), .10); border-color: rgba(var(--red-rgb), .28); }
        .dsv .chip--amber { color: var(--amber); background: rgba(var(--amber-rgb), .10); border-color: rgba(var(--amber-rgb), .28); }
        .dsv .chip--acc { color: var(--acc); background: rgba(var(--acc-rgb), .10); border-color: rgba(var(--acc-rgb), .28); }
        .dsv .tl__note { margin-top: 7px; padding: 8px 11px; border-radius: 9px; background: var(--card-2); border: 1px solid var(--line-soft); color: var(--ink-soft); font-size: 12px; line-height: 1.5; white-space: pre-line; overflow-wrap: anywhere; }

        /* ---- payment methods ---- */
        .dsv .methods { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 10px; }
        .dsv .method { background: var(--card-2); border: 1px solid var(--line); border-radius: 12px; padding: 10px 12px 9px; min-width: 0; }
        .dsv .method__top { display: flex; align-items: center; gap: 9px; }
        .dsv .method__ic { width: 28px; height: 28px; border-radius: 8px; display: grid; place-items: center; font-size: 12px; flex: none; }
        .dsv .method__name { font-weight: 600; font-size: 12.5px; color: var(--ink); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; min-width: 0; flex: 1; }
        .dsv .method__pct { font-family: var(--mono); font-size: 10px; color: var(--muted); white-space: nowrap; }
        .dsv .method__amt { font-size: 17px; font-weight: 700; margin-top: 6px; letter-spacing: -.01em; color: var(--ink); font-variant-numeric: tabular-nums; }
        .dsv .method__meta { font-size: 10.5px; color: var(--muted); margin-top: 1px; }
        .dsv .method__bar { height: 4px; border-radius: 99px; background: var(--line); margin-top: 8px; overflow: hidden; }
        .dsv .method__bar span { display: block; height: 100%; border-radius: 99px; background: linear-gradient(90deg, var(--amber), #e6b45a); }
        .dsv .method.is-cash .method__bar span { background: linear-gradient(90deg, var(--green), #37c98a); }
        .dsv .method.is-card .method__bar span { background: linear-gradient(90deg, var(--acc), var(--acc-bright)); }

        /* ---- tabs + tools ---- */
        .dsv .tabs { display: inline-flex; gap: 2px; padding: 3px; border-radius: 11px; background: var(--card-2); border: 1px solid var(--line); max-width: 100%; overflow-x: auto; }
        .dsv .tab { display: inline-flex; align-items: center; gap: 7px; height: 30px; padding: 0 12px; border-radius: 8px; border: none; background: transparent; color: var(--muted); font-weight: 600; font-size: 12.5px; cursor: pointer; transition: all .18s var(--ease); white-space: nowrap; }
        .dsv .tab i { font-size: 11px; }
        .dsv .tab .n { font-family: var(--mono); font-size: 10px; padding: 2px 6px; border-radius: 6px; background: var(--line); color: var(--muted); line-height: 1.2; }
        .dsv .tab:hover { color: var(--ink); }
        .dsv .tab.on { background: var(--card); color: var(--acc); box-shadow: var(--sh-sm); }
        .dsv .tab.on .n { background: rgba(var(--acc-rgb), .12); color: var(--acc); }
        .dsv .tools { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-inline-start: auto; }
        .dsv .search { display: flex; align-items: center; gap: 8px; height: 32px; padding: 0 10px; border-radius: 9px; border: 1.5px solid var(--line); background: var(--card-2); min-width: 250px; margin: 0; transition: border-color .18s, box-shadow .18s; }
        .dsv .search:focus-within { border-color: var(--acc); box-shadow: 0 0 0 3px rgba(var(--acc-rgb), .14); }
        .dsv .search i { color: var(--muted-2); font-size: 12px; }
        .dsv .search input { flex: 1; border: none; background: none; outline: none; color: var(--ink); font-size: 12.5px; min-width: 0; padding: 0; box-shadow: none; }
        .dsv .search input::placeholder { color: var(--muted-2); }
        .dsv .sel { height: 32px; padding: 0 28px 0 10px; border-radius: 9px; border: 1.5px solid var(--line); background: var(--card-2) url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 10 6'%3E%3Cpath d='M1 1l4 4 4-4' fill='none' stroke='%2393a0b8' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E") no-repeat right 10px center / 10px; color: var(--ink); font-size: 12.5px; font-weight: 600; outline: none; appearance: none; -webkit-appearance: none; cursor: pointer; }
        .dsv .sel:focus { border-color: var(--acc); box-shadow: 0 0 0 3px rgba(var(--acc-rgb), .14); }
        [dir="rtl"] .dsv .sel { padding: 0 10px 0 28px; background-position: left 10px center; }

        /* ---- tables ---- */
        .dsv .tbl-wrap { overflow-x: auto; }
        .dsv table.sx { width: 100%; border-collapse: collapse; min-width: 760px; margin: 0; }
        .dsv table.sx thead th { text-align: start; font-family: var(--mono); font-size: 9px; letter-spacing: .1em; text-transform: uppercase; color: var(--muted); padding: 8px 12px; border-bottom: 1px solid var(--line); background: transparent; white-space: nowrap; font-weight: 600; }
        .dsv table.sx th.end, .dsv table.sx td.end { text-align: end; }
        .dsv table.sx thead th a { color: var(--muted) !important; display: inline-flex; align-items: center; gap: 4px; }
        .dsv table.sx thead th a:hover { color: var(--acc) !important; }
        .dsv table.sx thead th a small { font-size: 9px; }
        .dsv table.sx tbody td { padding: 8px 12px; border-bottom: 1px solid var(--line-soft); vertical-align: middle; color: var(--ink); font-size: 12.5px; }
        .dsv table.sx tbody tr { transition: background .15s; }
        .dsv table.sx tbody tr:hover { background: var(--card-2); }
        .dsv table.sx tfoot td { padding: 10px 12px; border-top: 1px solid var(--line); background: var(--card-2); font-weight: 700; font-size: 13px; color: var(--ink); font-variant-numeric: tabular-nums; white-space: nowrap; }
        .dsv table.sx tfoot .lbl { font-family: var(--mono); font-size: 9.5px; letter-spacing: .1em; text-transform: uppercase; color: var(--muted); font-weight: 600; }
        .dsv .ref { font-weight: 700; color: var(--acc); white-space: nowrap; }
        .dsv .ref:hover { text-decoration: underline; color: var(--acc); }
        .dsv .sub { display: block; font-size: 10.5px; color: var(--muted); margin-top: 1px; font-weight: 400; white-space: nowrap; }
        .dsv .sub i { font-size: 9px; margin-inline-end: 2px; }
        .dsv .cust { font-weight: 600; white-space: nowrap; }
        .dsv .mth { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 7px; background: rgba(var(--acc-rgb), .10); color: var(--acc); white-space: nowrap; }
        .dsv .src { display: inline-flex; font-family: var(--mono); font-size: 9.5px; letter-spacing: .08em; text-transform: uppercase; padding: 3px 7px; border-radius: 6px; font-weight: 700; }
        .dsv .src--sale { color: var(--acc); background: rgba(var(--acc-rgb), .12); }
        .dsv .src--tailoring { color: var(--green); background: rgba(var(--green-rgb), .12); }
        .dsv td.empty-row { text-align: center; padding: 28px 16px; color: var(--muted); }
        .dsv td.empty-row i { display: grid; place-items: center; width: 40px; height: 40px; border-radius: 12px; background: var(--card-2); margin: 0 auto 8px; font-size: 16px; color: var(--muted-2); }
        .dsv td.empty-row b { display: block; color: var(--ink-soft); font-size: 13px; margin-bottom: 2px; }

        /* ---- pager (Livewire bootstrap theme, compacted) ---- */
        .dsv .pager { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; padding: 8px 12px; border-top: 1px solid var(--line-soft); }
        .dsv .pager__info { font-size: 11.5px; color: var(--muted); }
        .dsv .pager__info b { color: var(--ink-soft); font-weight: 600; }
        .dsv .pager nav p { display: none; }
        .dsv .pager .pagination { margin: 0; gap: 3px; --bs-pagination-padding-x: .5rem; --bs-pagination-padding-y: .2rem; --bs-pagination-font-size: 11.5px; --bs-pagination-bg: var(--card); --bs-pagination-color: var(--ink-soft); --bs-pagination-border-color: var(--line); --bs-pagination-hover-bg: var(--card-2); --bs-pagination-hover-color: var(--acc); --bs-pagination-hover-border-color: var(--line); --bs-pagination-focus-bg: var(--card-2); --bs-pagination-focus-color: var(--acc); --bs-pagination-focus-box-shadow: 0 0 0 3px rgba(var(--acc-rgb), .14); --bs-pagination-active-bg: var(--acc); --bs-pagination-active-border-color: var(--acc); --bs-pagination-active-color: #fff; --bs-pagination-disabled-bg: var(--card); --bs-pagination-disabled-color: var(--muted-2); --bs-pagination-disabled-border-color: var(--line-soft); }
        .dsv .pager .page-item .page-link { border-radius: 7px; line-height: 1.3; }

        /* ---- responsive ---- */
        @media (max-width: 900px) {
            .dsv .kpis { grid-template-columns: repeat(2, 1fr); }
            .dsv .grid2 { grid-template-columns: 1fr; }
        }
        @media (max-width: 640px) {
            .dsv .hero__actions { width: 100%; }
            .dsv .hbtn { flex: 1; justify-content: center; }
            .dsv .tools { width: 100%; margin-inline-start: 0; }
            .dsv .search { min-width: 0; flex: 1; }
        }
    </style>
@endonce

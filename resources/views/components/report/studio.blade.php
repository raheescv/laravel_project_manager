@once
    <style>
        /* =========================================================
           Report "Studio" system (.wrx)
           Shared chrome for the school reports: a permanent left
           filter rail, a row of compact summary cards, then the
           table at full width.

           Accent is emerald so money reads as money next to the
           app's blue chrome — set --wrx-ac to var(--bs-primary) to
           make it follow the settings theme instead.
           Dark mode follows Bootstrap [data-bs-theme="dark"].

           Inner class names are deliberately NOT bare where the
           global Nifty sheet already claims them (.ribbon is
           absolutely positioned there — hence .wrxsum).
           ========================================================= */
        .wrx {
            --wrx-ac: #0f9d76;
            --wrx-ac-rgb: 15, 157, 118;
            --wrx-ac-soft: #e7f6f1;
            --wrx-ink: #171a20;
            --wrx-ink-2: #4c5361;
            --wrx-mut: #8b93a3;
            --wrx-faint: #b9bfcb;
            --wrx-line: #e6e8ec;
            --wrx-line-soft: #f1f2f5;
            --wrx-card: #ffffff;
            --wrx-rail: #fafbfc;
            --wrx-red: #d94b4b;
            --wrx-red-rgb: 217, 75, 75;
            --wrx-red-soft: #fdeaea;
            --wrx-amber: #b4791a;
            --wrx-amber-soft: #fdf3e3;
            --wrx-blue: #3d6ad6;
            --wrx-blue-soft: #eaf0fd;
            --wrx-mono: ui-monospace, 'SF Mono', 'Cascadia Code', Menlo, monospace;
            --wrx-ease: cubic-bezier(.22, 1, .36, 1);
            color: var(--wrx-ink);
            font-size: 12.5px;
        }

        [data-bs-theme="dark"] .wrx {
            color-scheme: dark;
            --wrx-ac: #2ec294;
            --wrx-ac-rgb: 46, 194, 148;
            --wrx-ac-soft: rgba(46, 194, 148, .14);
            --wrx-ink: #eaf0fb;
            --wrx-ink-2: #c2cbdd;
            --wrx-mut: #8b96ad;
            --wrx-faint: #5b6478;
            --wrx-line: rgba(255, 255, 255, .10);
            --wrx-line-soft: rgba(255, 255, 255, .06);
            --wrx-card: #161b28;
            --wrx-rail: #12161f;
            --wrx-red: #f16b6b;
            --wrx-red-rgb: 241, 107, 107;
            --wrx-red-soft: rgba(241, 107, 107, .14);
            --wrx-amber: #e0ab4a;
            --wrx-amber-soft: rgba(224, 171, 74, .14);
            --wrx-blue: #7f9ef0;
            --wrx-blue-soft: rgba(127, 158, 240, .14);
        }

        .wrx * { box-sizing: border-box; }
        .wrx button { font: inherit; }
        .wrx .muted { color: var(--wrx-mut); }
        .wrx .mono { font-family: var(--wrx-mono); }

        /* ── Shell ──────────────────────────────────────────────── */
        .wrx .shell {
            display: grid; grid-template-columns: 224px minmax(0, 1fr);
            background: var(--wrx-card); border: 1px solid var(--wrx-line); border-radius: 12px; overflow: hidden;
            box-shadow: 0 1px 2px rgba(16, 20, 30, .04), 0 18px 40px -32px rgba(16, 20, 30, .3);
        }
        [data-bs-theme="dark"] .wrx .shell { box-shadow: 0 1px 2px rgba(0, 0, 0, .4), 0 18px 40px -30px rgba(0, 0, 0, .7); }

        /* ── Filter rail ────────────────────────────────────────── */
        .wrx .rail {
            background: var(--wrx-rail); border-inline-end: 1px solid var(--wrx-line);
            padding: 13px 12px; display: flex; flex-direction: column; gap: 14px;
        }
        .wrx .grp { display: flex; flex-direction: column; gap: 6px; }
        .wrx .grp > h4 { margin: 0 0 1px; font-size: 9.5px; font-weight: 800; letter-spacing: .13em; text-transform: uppercase; color: var(--wrx-mut); }
        .wrx .f { display: flex; flex-direction: column; gap: 3px; }
        .wrx .f > label { font-size: 11px; font-weight: 600; color: var(--wrx-ink-2); }
        .wrx .f input, .wrx .f select {
            width: 100%; height: 30px; border: 1px solid var(--wrx-line); border-radius: 7px;
            background: var(--wrx-card); font: inherit; font-size: 12px; font-weight: 500; color: var(--wrx-ink);
            padding: 0 8px; outline: none; transition: border-color .15s, box-shadow .15s;
        }
        .wrx .f input:focus, .wrx .f select:focus { border-color: var(--wrx-ac); box-shadow: 0 0 0 3px rgba(var(--wrx-ac-rgb), .14); }
        .wrx .f input[type="date"]::-webkit-calendar-picker-indicator { opacity: .55; cursor: pointer; }

        .wrx .quick { display: flex; gap: 4px; flex-wrap: wrap; }
        .wrx .quick button {
            border: 1px solid var(--wrx-line); background: var(--wrx-card); border-radius: 99px;
            font-size: 10.5px; font-weight: 600; color: var(--wrx-ink-2); padding: .2rem .52rem; cursor: pointer;
            transition: color .15s, border-color .15s, background .15s;
        }
        .wrx .quick button:hover { border-color: var(--wrx-ac); color: var(--wrx-ac); }
        .wrx .quick button.is-on { background: var(--wrx-ac-soft); border-color: transparent; color: var(--wrx-ac); }

        /* segmented radio group (one choice, always one active) */
        .wrx .seg { display: flex; flex-direction: column; gap: 2px; padding: 2px; border: 1px solid var(--wrx-line); border-radius: 8px; background: var(--wrx-card); }
        .wrx .seg label {
            display: flex; align-items: center; gap: 6px; margin: 0; padding: .3rem .45rem; border-radius: 6px;
            font-size: 11px; font-weight: 600; color: var(--wrx-ink-2); cursor: pointer; transition: background .15s, color .15s;
        }
        .wrx .seg label:hover { color: var(--wrx-ink); }
        .wrx .seg input { position: absolute; opacity: 0; width: 0; height: 0; }
        .wrx .seg label:has(input:checked) { background: var(--wrx-ac-soft); color: var(--wrx-ac); }
        .wrx .seg label i { width: 12px; text-align: center; opacity: .8; font-size: 10.5px; }

        .wrx .sw {
            display: flex; align-items: center; justify-content: space-between; gap: 8px; margin: 0;
            padding: 6px 9px; border: 1px solid var(--wrx-line); border-radius: 8px; background: var(--wrx-card);
            cursor: pointer; font-size: 11px; font-weight: 600; color: var(--wrx-ink-2); transition: all .15s;
        }
        .wrx .sw i { color: var(--wrx-red); }
        .wrx .sw__in { position: absolute; opacity: 0; width: 0; height: 0; }
        .wrx .sw__tr { width: 27px; height: 15px; border-radius: 99px; background: var(--wrx-line); position: relative; flex: none; transition: background .2s; }
        .wrx .sw__tr::after {
            content: ''; position: absolute; top: 2px; inset-inline-start: 2px; width: 11px; height: 11px; border-radius: 50%;
            background: #fff; box-shadow: 0 1px 2px rgba(0, 0, 0, .25); transition: transform .2s var(--wrx-ease);
        }
        .wrx .sw__in:checked ~ .sw__tr { background: var(--wrx-red); }
        .wrx .sw__in:checked ~ .sw__tr::after { transform: translateX(12px); }
        [dir="rtl"] .wrx .sw__in:checked ~ .sw__tr::after { transform: translateX(-12px); }
        .wrx .sw.is-on { border-color: rgba(var(--wrx-red-rgb), .4); background: var(--wrx-red-soft); color: var(--wrx-red); }

        .wrx .rail-foot { margin-top: auto; padding-top: 2px; display: flex; flex-direction: column; gap: 6px; }
        .wrx .btn-x {
            height: 31px; border-radius: 8px; border: none; font-size: 11.5px; font-weight: 700; cursor: pointer;
            display: inline-flex; align-items: center; justify-content: center; gap: 6px; transition: filter .15s, color .15s, border-color .15s;
        }
        .wrx .btn-x.solid { background: var(--wrx-ac); color: #fff; }
        .wrx .btn-x.solid:hover { filter: brightness(1.07); }
        .wrx .btn-x.ghost { background: transparent; border: 1px solid var(--wrx-line); color: var(--wrx-ink-2); }
        .wrx .btn-x.ghost:hover { border-color: var(--wrx-red); color: var(--wrx-red); }

        /* ── Main column ────────────────────────────────────────── */
        .wrx .main { display: flex; flex-direction: column; min-width: 0; }

        /* ── Summary cards ──────────────────────────────────────── */
        .wrx .wrxsum { padding: 11px 14px 10px; border-bottom: 1px solid var(--wrx-line); }
        .wrx .wrxsum__row { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 7px; }
        .wrx .stat {
            display: grid; grid-template-columns: auto minmax(0, 1fr); grid-template-areas: "ic k" "v v";
            align-items: center; column-gap: 6px;
            padding: 8px 10px 9px; border-radius: 9px;
            background: var(--wrx-card); border: 1px solid var(--wrx-line);
            transition: transform .18s var(--wrx-ease), box-shadow .18s var(--wrx-ease);
        }
        .wrx .stat:hover { transform: translateY(-1px); box-shadow: 0 10px 20px -16px rgba(16, 20, 30, .5); }
        [data-bs-theme="dark"] .wrx .stat:hover { box-shadow: 0 10px 20px -14px rgba(0, 0, 0, .8); }
        .wrx .stat__ic {
            grid-area: ic; width: 20px; height: 20px; border-radius: 6px; display: grid; place-items: center; font-size: 9.5px;
            background: var(--wrx-line-soft); color: var(--wrx-mut);
        }
        .wrx .stat .k { grid-area: k; font-size: 9.5px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--wrx-mut); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .wrx .stat .v { grid-area: v; font-size: 16px; font-weight: 700; letter-spacing: -.025em; margin-top: 5px; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .wrx .stat .v small { font-size: 10.5px; font-weight: 600; color: var(--wrx-mut); }

        /* the one figure the report exists to answer */
        .wrx .stat.hero {
            border-color: transparent; color: #fff;
            background: linear-gradient(135deg, var(--wrx-ac), color-mix(in srgb, var(--wrx-ac), #000 30%));
            box-shadow: 0 8px 16px -12px rgba(var(--wrx-ac-rgb), .85);
        }
        .wrx .stat.hero .k { color: rgba(255, 255, 255, .8); }
        .wrx .stat.hero .v { color: #fff; font-size: 18px; }
        .wrx .stat.hero .v small { color: rgba(255, 255, 255, .72); }
        .wrx .stat.hero .stat__ic { background: rgba(255, 255, 255, .22); color: #fff; }
        .wrx .stat.hero:hover { box-shadow: 0 14px 24px -14px rgba(var(--wrx-ac-rgb), .95); }

        .wrx .stat.in .v { color: var(--wrx-ac); }
        .wrx .stat.in .stat__ic { background: var(--wrx-ac-soft); color: var(--wrx-ac); }
        .wrx .stat.out .v, .wrx .stat.bad .v { color: var(--wrx-red); }
        .wrx .stat.out .stat__ic, .wrx .stat.bad .stat__ic { background: var(--wrx-red-soft); color: var(--wrx-red); }
        .wrx .stat.warn .v { color: var(--wrx-amber); }
        .wrx .stat.warn .stat__ic { background: var(--wrx-amber-soft); color: var(--wrx-amber); }
        .wrx .stat.off .v { color: var(--wrx-mut); }
        .wrx .wrxsum__note { margin: 9px 0 0; font-size: 11px; color: var(--wrx-mut); line-height: 1.45; }

        /* ── Toolbar ────────────────────────────────────────────── */
        .wrx .tools { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; padding: 8px 14px; border-bottom: 1px solid var(--wrx-line); }
        .wrx .search { position: relative; flex: 1 1 220px; max-width: 340px; }
        .wrx .search input {
            width: 100%; height: 30px; border: 1px solid var(--wrx-line); border-radius: 7px; background: var(--wrx-card);
            color: var(--wrx-ink); padding-inline: 29px 10px; font: inherit; font-size: 12px; outline: none; transition: border-color .15s, box-shadow .15s;
        }
        .wrx .search input:focus { border-color: var(--wrx-ac); box-shadow: 0 0 0 3px rgba(var(--wrx-ac-rgb), .14); }
        .wrx .search > i { position: absolute; inset-inline-start: 10px; top: 50%; transform: translateY(-50%); color: var(--wrx-mut); font-size: 11.5px; }
        .wrx .tools__end { display: inline-flex; align-items: center; gap: 8px; margin-inline-start: auto; }
        .wrx .tools__cnt { font-size: 11.5px; color: var(--wrx-mut); white-space: nowrap; }
        .wrx .tools select {
            height: 30px; border: 1px solid var(--wrx-line); border-radius: 7px; background: var(--wrx-card); color: var(--wrx-ink);
            font: inherit; font-size: 11.5px; font-weight: 600; padding: 0 7px; outline: none; cursor: pointer;
        }
        .wrx .busy { color: var(--wrx-ac); font-size: 11.5px; }

        /* ── Table ──────────────────────────────────────────────── */
        .wrx .tbl-wrap { overflow-x: auto; transition: opacity .2s; }
        .wrx.is-busy .tbl-wrap { opacity: .5; pointer-events: none; }
        .wrx table.wt { width: 100%; border-collapse: collapse; margin: 0; min-width: 800px; }
        .wrx table.wt.wide { min-width: 1020px; }
        .wrx table.wt thead th {
            text-align: start; background: var(--wrx-rail); font-size: 9.5px; font-weight: 800; letter-spacing: .08em;
            text-transform: uppercase; color: var(--wrx-mut); padding: 8px 12px; border-bottom: 1px solid var(--wrx-line); white-space: nowrap;
        }
        .wrx table.wt thead th.num { text-align: end; }
        .wrx table.wt tbody td {
            padding: 8px 12px; border-bottom: 1px solid var(--wrx-line-soft); font-size: 12px;
            font-weight: 500; color: var(--wrx-ink); vertical-align: middle;
        }
        .wrx table.wt tbody tr:last-child td { border-bottom: none; }
        .wrx table.wt tbody tr:hover td { background: var(--wrx-rail); }
        .wrx table.wt tbody td:first-child { border-inline-start: 3px solid transparent; }
        .wrx table.wt tbody tr.is-flagged td:first-child { border-inline-start-color: var(--wrx-red); }
        .wrx table.wt .num { text-align: end; font-variant-numeric: tabular-nums; }
        .wrx table.wt .nowrap { white-space: nowrap; }
        .wrx table.wt .adm { color: var(--wrx-mut); font-size: 11px; font-weight: 600; }
        .wrx table.wt .sub { color: var(--wrx-mut); font-size: 10.5px; margin-top: 1px; }
        .wrx table.wt .nm { font-weight: 700; color: var(--wrx-ink); text-decoration: none; }
        .wrx table.wt .nm:hover { color: var(--wrx-ac); text-decoration: underline; }
        .wrx table.wt .in { color: var(--wrx-ac); }
        .wrx table.wt .out { color: var(--wrx-red); }
        .wrx table.wt .zero { color: var(--wrx-faint); }
        .wrx table.wt .bal { font-weight: 800; }
        .wrx table.wt .bal.neg { color: var(--wrx-red); }

        .wrx .th-sort {
            border: none; background: none; padding: 0; cursor: pointer; color: inherit; font: inherit;
            font-size: inherit; font-weight: inherit; letter-spacing: inherit; text-transform: inherit;
            display: inline-flex; align-items: center; gap: 4px;
        }
        .wrx .th-sort:hover { color: var(--wrx-ink-2); }
        .wrx .th-sort i { font-size: 8.5px; opacity: .5; }
        .wrx .th-sort.is-on { color: var(--wrx-ac); }
        .wrx .th-sort.is-on i { opacity: 1; }

        .wrx .tag {
            display: inline-flex; align-items: center; gap: 4px; font-size: 10.5px; font-weight: 700;
            padding: .14rem .45rem; border-radius: 5px; background: var(--wrx-ac-soft); color: var(--wrx-ac); white-space: nowrap;
        }
        .wrx .tag.off { background: var(--wrx-line-soft); color: var(--wrx-mut); }
        .wrx .tag.bad { background: var(--wrx-red-soft); color: var(--wrx-red); }
        .wrx .tag.warn { background: var(--wrx-amber-soft); color: var(--wrx-amber); }
        .wrx .tag.info { background: var(--wrx-blue-soft); color: var(--wrx-blue); }

        .wrx .icon-btn {
            display: inline-flex; align-items: center; gap: 5px; height: 25px; padding: 0 9px; border-radius: 7px;
            border: 1px solid var(--wrx-line); background: var(--wrx-card); color: var(--wrx-ink-2);
            font-size: 11px; font-weight: 600; cursor: pointer; white-space: nowrap; transition: all .15s var(--wrx-ease);
        }
        .wrx .icon-btn:hover { border-color: var(--wrx-ac); color: var(--wrx-ac); }
        .wrx .icon-btn[disabled] { opacity: .5; cursor: default; }

        .wrx .empty { text-align: center; padding: 28px 16px; color: var(--wrx-mut); }
        .wrx .empty__ring { width: 40px; height: 40px; border-radius: 12px; margin: 0 auto 9px; display: grid; place-items: center; font-size: 16px; color: var(--wrx-ac); background: var(--wrx-ac-soft); }
        .wrx .empty h4 { font-size: 13px; font-weight: 700; color: var(--wrx-ink); margin: 0 0 2px; }
        .wrx .empty p { margin: 0; font-size: 11.5px; }

        .wrx .foot { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; padding: 8px 14px; border-top: 1px solid var(--wrx-line); font-size: 11.5px; color: var(--wrx-mut); }
        .wrx .foot nav { margin: 0; }
        .wrx .pagination { margin: 0; --bs-pagination-padding-y: .18rem; --bs-pagination-padding-x: .5rem; --bs-pagination-font-size: 11.5px; --bs-pagination-border-radius: 6px; }

        @media (max-width: 1280px) {
            .wrx .wrxsum__row { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }
        @media (max-width: 1100px) {
            .wrx .shell { grid-template-columns: 1fr; }
            .wrx .rail { border-inline-end: none; border-bottom: 1px solid var(--wrx-line); display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; align-items: start; }
            .wrx .rail-foot { grid-column: 1 / -1; margin-top: 0; flex-direction: row; }
            .wrx .rail-foot .btn-x { flex: 1; }
        }
        @media (max-width: 720px) {
            .wrx .rail { grid-template-columns: 1fr; }
            .wrx .wrxsum__row { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
    </style>
@endonce

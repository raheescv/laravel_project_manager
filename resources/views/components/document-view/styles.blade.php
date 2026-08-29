{{--
    ═══════════════════════════════════════════════════════════════════════════
    ║  "SPLIT LEDGER" — the premium view design system for transaction         ║
    ║  documents (Sale invoice, Purchase bill, …).                             ║
    ║                                                                          ║
    ║  Scoped under .dvx. The accent derives from the active SETTINGS THEME    ║
    ║  (--bs-primary), so it follows whatever palette the tenant picked, and   ║
    ║  every surface flips with Bootstrap's light/dark attribute.              ║
    ║                                                                          ║
    ║  Push it once per page, inside the layout's styles stack.                ║
    ═══════════════════════════════════════════════════════════════════════════
--}}
<style>
    .dvx {
        --acc: var(--bs-primary);
        --acc-rgb: var(--bs-primary-rgb);
        --acc-d: color-mix(in srgb, var(--bs-primary), #000 15%);
        --tint: color-mix(in srgb, var(--bs-primary), transparent 91%);
        --tint-2: color-mix(in srgb, var(--bs-primary), transparent 96%);

        --sf: #ffffff;
        --sf-2: #f7f8fb;
        --sf-3: #eff2f7;
        --ink: var(--bs-emphasis-color);
        --ink-2: var(--bs-body-color);
        --mut: var(--bs-secondary-color);
        --ln: #e4e9f0;
        --ln-s: #eef1f6;

        --ok: var(--bs-success);
        --ok-rgb: var(--bs-success-rgb);
        --info: var(--bs-info);
        --info-rgb: var(--bs-info-rgb);
        /* Bootstrap's raw warning is unreadable as text on white — darken the
           ink but keep the raw rgb for the tinted backgrounds. */
        --warn: color-mix(in srgb, var(--bs-warning), #000 28%);
        --warn-rgb: var(--bs-warning-rgb);
        --bad: var(--bs-danger);
        --bad-rgb: var(--bs-danger-rgb);

        --shadow: 0 1px 2px rgba(16, 24, 40, .04), 0 6px 20px -10px rgba(16, 24, 40, .13);

        color: var(--ink);
        font-size: 12px;
        line-height: 1.5;
        -webkit-font-smoothing: antialiased;
    }

    .dvx *{ box-sizing: border-box; }
    .dvx a { text-decoration: none; }
    .dvx .mono { font-variant-numeric: tabular-nums; }
    .dvx .num { text-align: right; }

    [data-bs-theme="dark"] .dvx {
        --sf: #242a32;
        --sf-2: #2a313a;
        --sf-3: #313943;
        --ln: #39424d;
        --ln-s: #333b45;
        /* --acc-d is the accent used for TEXT: it darkens on white and must
           lighten on dark, or every accented label goes near-invisible. */
        --acc-d: color-mix(in srgb, var(--bs-primary), #fff 22%);
        --tint: color-mix(in srgb, var(--bs-primary), transparent 85%);
        --tint-2: color-mix(in srgb, var(--bs-primary), transparent 92%);
        --warn: color-mix(in srgb, var(--bs-warning), #fff 6%);
        --shadow: 0 1px 2px rgba(0, 0, 0, .35), 0 8px 24px -10px rgba(0, 0, 0, .5);
    }

    /* ── LEAD: identity on the left, money on the right ───────────────────── */
    .dvx .s-lead {
        background: var(--sf); border: 1px solid var(--ln); border-left: 3px solid var(--acc);
        border-radius: 11px; box-shadow: var(--shadow);
        display: flex; align-items: center; gap: 13px; padding: 11px 14px; flex-wrap: wrap; margin-bottom: 8px;
    }
    .dvx .s-lead .l-ic {
        width: 36px; height: 36px; border-radius: 10px; flex: 0 0 auto; font-size: 16px;
        background: var(--tint); color: var(--acc); display: flex; align-items: center; justify-content: center;
    }
    .dvx .s-lead .l-main { flex: 1; min-width: 220px; }
    .dvx .s-eyebrow { font-size: 9px; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase; color: var(--mut); }
    .dvx .s-no { font-size: 18px; font-weight: 800; letter-spacing: -.2px; line-height: 1.2; }
    .dvx .s-meta { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 4px; font-size: 11.5px; color: var(--mut); align-items: center; }
    .dvx .s-meta b { color: var(--ink-2); font-weight: 650; }
    .dvx .s-meta i { margin-right: 5px; opacity: .7; }
    .dvx .s-lead .l-right { text-align: end; }
    .dvx .s-lead .l-right .lb { font-size: 9px; font-weight: 800; letter-spacing: 1.2px; text-transform: uppercase; color: var(--mut); }
    .dvx .s-lead .l-right .big { font-size: 22px; font-weight: 850; letter-spacing: -.5px; line-height: 1.15; }
    .dvx .s-badge {
        display: inline-flex; align-items: center; gap: 5px; padding: 2px 9px; border-radius: 999px;
        font-size: 10px; font-weight: 800; letter-spacing: .4px; margin-top: 5px;
    }
    .dvx .b-ok { background: rgba(var(--ok-rgb), .12); color: var(--ok); }
    .dvx .b-wn { background: rgba(var(--warn-rgb), .16); color: var(--warn); }
    .dvx .b-bad { background: rgba(var(--bad-rgb), .11); color: var(--bad); }
    .dvx .b-acc { background: var(--tint); color: var(--acc-d); }

    /* ── KPI TILES ────────────────────────────────────────────────────────── */
    .dvx .s-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(148px, 1fr)); gap: 8px; margin-bottom: 8px; }
    .dvx .s-kpi {
        background: var(--sf); border: 1px solid var(--ln); border-radius: 10px; box-shadow: var(--shadow);
        padding: 8px 11px; display: flex; align-items: center; gap: 9px;
    }
    .dvx .s-kpi .k-ic { width: 30px; height: 30px; border-radius: 8px; font-size: 13px; flex: 0 0 auto; display: flex; align-items: center; justify-content: center; }
    .dvx .s-kpi .k-k { font-size: 9px; font-weight: 800; letter-spacing: .8px; text-transform: uppercase; color: var(--mut); }
    .dvx .s-kpi .k-v { font-size: 15px; font-weight: 800; margin-top: -1px; }
    .dvx .s-kpi .k-v small { font-size: 10.5px; font-weight: 600; color: var(--mut); }

    /* ── CARD ─────────────────────────────────────────────────────────────── */
    .dvx .s-card {
        background: var(--sf); border: 1px solid var(--ln); border-radius: 11px;
        box-shadow: var(--shadow); overflow: hidden; margin-bottom: 8px;
    }
    .dvx .s-ch { display: flex; align-items: center; gap: 8px; padding: 8px 12px; border-bottom: 1px solid var(--ln-s); flex-wrap: wrap; }
    .dvx .s-ch h4 { margin: 0; font-size: 12.5px; font-weight: 750; font-family: inherit; }
    .dvx .s-ch .cs { font-size: 11px; color: var(--mut); margin-inline-start: auto; }
    .dvx .s-ch i.hi { color: var(--acc-d); }

    .dvx .s-two { display: grid; grid-template-columns: 1fr 1fr; }
    .dvx .s-two > div { padding: 4px 12px 9px; }
    .dvx .s-two > div + div { border-inline-start: 1px solid var(--ln-s); }
    @media(max-width:800px) {
        .dvx .s-two { grid-template-columns: 1fr; }
        .dvx .s-two > div + div { border-inline-start: 0; border-top: 1px solid var(--ln-s); }
    }
    .dvx .s-fl {
        font-size: 9px; font-weight: 800; letter-spacing: 1.1px; text-transform: uppercase;
        color: var(--mut); margin-bottom: 6px; padding-top: 8px; display: flex; align-items: center; gap: 7px;
    }
    .dvx .s-fl i { color: var(--acc-d); font-size: 11px; }
    .dvx .s-fl .s-btn { text-transform: none; letter-spacing: normal; }
    .dvx .s-dr { display: flex; justify-content: space-between; gap: 14px; padding: 4.5px 0; border-bottom: 1px solid var(--ln-s); }
    .dvx .s-dr:last-child { border-bottom: 0; }
    .dvx .s-dr .l { color: var(--mut); display: flex; align-items: center; gap: 8px; }
    .dvx .s-dr .l i { color: var(--acc-d); width: 13px; text-align: center; opacity: .85; }
    .dvx .s-dr .v { font-weight: 680; text-align: end; }
    .dvx .s-dr .v a { color: var(--acc-d); }

    /* ── ITEMS TABLE ──────────────────────────────────────────────────────── */
    .dvx .s-tw { overflow-x: auto; }
    .dvx table.s-tbl { width: 100%; border-collapse: collapse; font-size: 12px; margin: 0; }
    .dvx table.s-tbl thead th {
        background: var(--sf-2); color: var(--mut); font-size: 9px; font-weight: 800; letter-spacing: .8px;
        text-transform: uppercase; padding: 6px 12px; text-align: start; border-bottom: 1px solid var(--ln); white-space: nowrap;
    }
    .dvx table.s-tbl th.num, .dvx table.s-tbl td.num { text-align: end; }
    .dvx table.s-tbl tbody td { padding: 6px 12px; border-bottom: 1px solid var(--ln-s); vertical-align: middle; }
    .dvx table.s-tbl tbody tr:hover td { background: var(--tint-2); }
    .dvx table.s-tbl tr.s-grp td { background: var(--sf-2); padding: 4px 12px; }
    .dvx .s-gn { font-size: 10px; font-weight: 800; letter-spacing: .8px; text-transform: uppercase; color: var(--acc-d); display: flex; align-items: center; gap: 7px; }
    .dvx .s-gn .av { width: 19px; height: 19px; border-radius: 50%; background: var(--acc); color: #fff; font-size: 9px; font-weight: 800; display: inline-flex; align-items: center; justify-content: center; }
    .dvx .s-gn em { font-style: normal; font-weight: 600; letter-spacing: 0; text-transform: none; color: var(--mut); font-size: 11px; }
    .dvx .s-pn { font-weight: 700; color: var(--ink); }
    .dvx .s-pn.acc { color: var(--acc-d); }
    .dvx .s-ps { font-size: 10.5px; color: var(--mut); margin-top: 1px; }
    .dvx table.s-tbl tr.s-ret td { background: rgba(var(--warn-rgb), .07); color: var(--mut); font-size: 11px; padding: 4px 12px; }
    .dvx table.s-tbl tr.s-ret a { color: var(--warn); font-weight: 750; }
    .dvx table.s-tbl tfoot td { padding: 7px 12px; font-weight: 800; background: var(--sf-2); border-top: 2px solid var(--ln); }
    .dvx table.s-tbl tfoot tr.s-rr td { background: rgba(var(--warn-rgb), .09); color: var(--warn); border-top: 1px solid var(--ln-s); font-size: 11.5px; padding: 5px 12px; }
    .dvx .s-chip { display: inline-block; padding: 0 7px; border-radius: 6px; font-size: 10px; font-weight: 750; line-height: 17px; }
    .dvx .c-w { background: rgba(var(--warn-rgb), .16); color: var(--warn); }
    .dvx .c-i { background: rgba(var(--info-rgb), .14); color: var(--info); }
    .dvx .c-m { background: var(--sf-3); color: var(--mut); }
    .dvx .c-a { background: var(--tint); color: var(--acc-d); }

    /* ── CARD FOOTER: payments | summary ──────────────────────────────────── */
    .dvx .s-foot { display: grid; grid-template-columns: 1fr 1fr; }
    .dvx .s-foot > div { padding: 10px 12px; }
    .dvx .s-foot > div + div { border-inline-start: 1px solid var(--ln-s); }
    @media(max-width:800px) {
        .dvx .s-foot { grid-template-columns: 1fr; }
        .dvx .s-foot > div + div { border-inline-start: 0; border-top: 1px solid var(--ln-s); }
    }
    .dvx .s-pay { display: flex; align-items: center; gap: 9px; padding: 6px 0; border-bottom: 1px solid var(--ln-s); }
    .dvx .s-pay:last-child { border-bottom: 0; }
    .dvx .s-pd { width: 28px; height: 28px; border-radius: 8px; font-size: 12px; flex: 0 0 auto; display: flex; align-items: center; justify-content: center; }
    .dvx .s-sr { display: flex; justify-content: space-between; gap: 10px; padding: 3px 0; }
    .dvx .s-sr .l { color: var(--mut); }
    .dvx .s-sr .v { font-weight: 700; }
    .dvx .s-sr.neg .v { color: var(--bad); }
    .dvx .s-sr.wv .l, .dvx .s-sr.wv .v { color: var(--warn); }
    .dvx .s-sr.tot { border-top: 1px solid var(--ln); margin-top: 5px; padding-top: 8px; }
    .dvx .s-sr.tot .l { font-weight: 800; color: var(--ink); }
    .dvx .s-sr.tot .v { font-size: 16px; font-weight: 850; color: var(--acc-d); }
    .dvx .s-due {
        margin-top: 8px; padding: 8px 11px; border-radius: 9px; display: flex; justify-content: space-between; align-items: center;
        background: rgba(var(--bad-rgb), .08); border: 1px solid rgba(var(--bad-rgb), .22);
    }
    .dvx .s-due .l { font-size: 9px; font-weight: 800; letter-spacing: 1.2px; text-transform: uppercase; color: var(--bad); }
    .dvx .s-due .v { font-size: 15px; font-weight: 850; color: var(--bad); }
    .dvx .s-due.settled { background: rgba(var(--ok-rgb), .08); border-color: rgba(var(--ok-rgb), .22); }
    .dvx .s-due.settled .l, .dvx .s-due.settled .v { color: var(--ok); }

    /* ── TOOLBAR + TABS ───────────────────────────────────────────────────── */
    .dvx .s-toolbar { display: flex; gap: 7px; flex-wrap: wrap; justify-content: flex-end; margin-bottom: 8px; }
    .dvx .s-btn {
        border: 1px solid var(--ln); background: var(--sf); color: var(--ink-2); padding: 6px 12px; border-radius: 8px;
        font-size: 11.5px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: var(--shadow);
    }
    .dvx .s-btn:hover { border-color: var(--acc); color: var(--acc); }
    .dvx .s-btn:disabled { opacity: .5; cursor: not-allowed; }
    .dvx .s-btn.pri { background: var(--acc); border-color: var(--acc); color: #fff; }
    .dvx .s-btn.pri:hover { color: #fff; filter: brightness(1.06); }
    .dvx .s-btn.wn { color: var(--warn); }
    .dvx .s-btn.wn:hover { border-color: var(--warn); color: var(--warn); }
    .dvx .s-btn.bad { color: var(--bad); }
    .dvx .s-btn.bad:hover { border-color: var(--bad); color: var(--bad); }
    .dvx .s-btn.flat { box-shadow: none; }

    .dvx .s-tabs { display: flex; gap: 4px; padding: 0 8px; border-bottom: 1px solid var(--ln); background: var(--sf-2); overflow-x: auto; }
    .dvx .s-tab {
        padding: 8px 13px; font-size: 11.5px; font-weight: 750; color: var(--mut); background: transparent;
        border: 0; border-bottom: 2px solid transparent; cursor: pointer; display: flex; align-items: center; gap: 6px; white-space: nowrap;
    }
    .dvx .s-tab.active { color: var(--acc-d); border-bottom-color: var(--acc); background: var(--sf); }
    .dvx .s-cnt { background: var(--sf-3); color: var(--mut); border-radius: 999px; padding: 0 6px; font-size: 10px; font-weight: 800; }
    .dvx .s-pane { padding: 0; }
    .dvx .s-note { padding: 9px 12px; font-size: 12px; color: var(--ink-2); }

    /* Combo offers */
    .dvx .s-combos { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 8px; padding: 9px 12px; }
    .dvx .s-combo { border: 1px solid var(--ln); border-radius: 9px; overflow: hidden; }
    .dvx .s-combo .ch { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 6px 10px; background: var(--tint); }
    .dvx .s-combo .ch .nm { font-weight: 750; color: var(--acc-d); }
    .dvx .s-combo .li { display: flex; justify-content: space-between; gap: 10px; padding: 5px 10px; border-top: 1px solid var(--ln-s); }
    .dvx .s-combo .li .sub { font-size: 10.5px; color: var(--mut); }
</style>

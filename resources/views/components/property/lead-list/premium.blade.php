{{--
    Lead List — "Pipeline Strip" (direction A of docs/lead-list-premium-preview.html),
    sibling of the lead form (.lfx) and board systems, scoped to .llx.

    Accent follows the settings theme (--bs-primary); dark mode keys off
    [data-bs-theme="dark"]. Stage tones come from LeadPipeline::stages().
--}}
@once
    <style>
        .llx {
            --llx-fz: 12.5px;
            --acc: var(--bs-primary);
            --surface: #fff; --surface-2: #f5f7fa; --surface-3: #e9edf3;
            --ink: var(--bs-emphasis-color); --ink-2: var(--bs-body-color);
            --muted: var(--bs-secondary-color); --faint: var(--bs-tertiary-color);
            --line: #e3e7ee; --line-soft: #eef1f5; --mix: #000;
            --r: 14px;
            --shadow: 0 1px 2px rgba(16, 24, 40, .05), 0 10px 30px -12px rgba(16, 24, 40, .14);
            --float: 0 18px 40px -12px rgba(16, 24, 40, .38), 0 4px 12px -4px rgba(16, 24, 40, .18);
            font-size: var(--llx-fz); line-height: 1.45; color: var(--ink-2);
        }
        [data-bs-theme="dark"] .llx {
            --surface: #262c33; --surface-2: #1f252b; --surface-3: #323a43;
            --line: #373f49; --line-soft: #30373f; --mix: #fff;
            --shadow: 0 1px 2px rgba(0, 0, 0, .4), 0 12px 30px -12px rgba(0, 0, 0, .55);
            --float: 0 20px 44px -12px rgba(0, 0, 0, .8), 0 4px 12px -4px rgba(0, 0, 0, .5);
        }
        .llx *, .llx *::before, .llx *::after { box-sizing: border-box; }

        /* tones */
        .llx .tn { --tn-ink: color-mix(in srgb, var(--tn) 76%, var(--mix)); --tn-soft: color-mix(in srgb, var(--tn) 13%, transparent); }
        [data-bs-theme="dark"] .llx .tn { --tn-ink: color-mix(in srgb, var(--tn) 55%, #fff); --tn-soft: color-mix(in srgb, var(--tn) 22%, transparent); }
        .llx .t-primary { --tn: var(--acc); } .llx .t-info { --tn: var(--bs-info); } .llx .t-warning { --tn: var(--bs-warning); }
        .llx .t-success { --tn: var(--bs-success); } .llx .t-secondary { --tn: var(--bs-secondary); } .llx .t-danger { --tn: var(--bs-danger); }

        .llx .llx-card { background: var(--surface); border: 1px solid var(--line); border-radius: var(--r); box-shadow: var(--shadow); margin-bottom: 12px; }
        .llx .ctl { height: 32px; border: 1px solid var(--line); background-color: var(--surface); color: var(--ink); border-radius: 9px; padding: 0 10px; font-size: 12px; outline: 0; min-width: 0; }
        .llx .ctl:focus { border-color: var(--acc); box-shadow: 0 0 0 3px color-mix(in srgb, var(--acc) 16%, transparent); }
        .llx .ctl:disabled { opacity: .55; }
        .llx select.ctl { padding-inline-end: 26px; appearance: none; background-image: linear-gradient(45deg, transparent 50%, var(--muted) 50%), linear-gradient(135deg, var(--muted) 50%, transparent 50%); background-position: calc(100% - 13px) 14px, calc(100% - 9px) 14px; background-size: 4px 4px; background-repeat: no-repeat; }
        [dir="rtl"] .llx select.ctl { background-position: 13px 14px, 9px 14px; }
        .llx .btn-l { height: 32px; border: 1px solid var(--line); background: var(--surface); color: var(--ink-2); border-radius: 9px; padding: 0 11px; font-size: 12px; font-weight: 500; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; white-space: nowrap; text-decoration: none; }
        .llx .btn-l:hover { border-color: color-mix(in srgb, var(--acc) 40%, var(--line)); color: var(--ink); }
        .llx .btn-l.on { border-color: var(--acc); color: var(--acc); background: color-mix(in srgb, var(--acc) 7%, var(--surface)); }
        .llx .btn-l.ok { color: var(--bs-success); }
        .llx .btn-l .k { font-size: 10.5px; background: var(--acc); color: #fff; border-radius: 5px; padding: 0 5px; line-height: 16px; }
        .llx .search { position: relative; flex: 1 1 auto; min-width: 0; }
        .llx .search > i { position: absolute; inset-inline-start: 11px; top: 50%; transform: translateY(-50%); color: var(--faint); }
        .llx .search .ctl { width: 100%; padding-inline-start: 30px; }

        /* stage strip */
        .llx .strip { display: grid; grid-template-columns: repeat(var(--stages, 5), minmax(0, 1fr)) 1.3fr; overflow: hidden; }
        .llx .stg { padding: 11px 14px; border: 0; border-inline-end: 1px solid var(--line-soft); background: transparent; color: inherit; font: inherit; text-align: start; cursor: pointer; position: relative; }
        .llx .stg:hover { background: var(--surface-2); }
        .llx .stg.on { background: var(--tn-soft); }
        .llx .stg.on::after { content: ""; position: absolute; inset-inline: 0; bottom: 0; height: 3px; background: var(--tn); }
        .llx .stg .h { display: flex; align-items: center; gap: 7px; font-size: 11px; color: var(--muted); text-transform: uppercase; letter-spacing: .7px; font-weight: 600; }
        .llx .stg .h i { color: var(--tn-ink); }
        .llx .stg .v { font-size: 20px; font-weight: 600; color: var(--ink); margin-top: 2px; font-variant-numeric: tabular-nums; line-height: 1.3; }
        .llx .stg .v small { white-space: nowrap; font-size: 11px; color: var(--muted); font-weight: 500; margin-inline-start: 4px; }
        .llx .stg.tot { border-inline-end: 0; cursor: default; }
        .llx .stg.tot:hover { background: transparent; }
        .llx .stg.tot .h i { color: var(--acc); }
        .llx .mixbar { display: flex; height: 6px; border-radius: 6px; overflow: hidden; margin-top: 8px; gap: 2px; background: var(--surface-3); }
        .llx .mixbar span { background: var(--tn); }

        /* matrix */
        .llx details.mxd > summary { list-style: none; cursor: pointer; display: flex; align-items: center; gap: 8px; padding: 10px 14px; font-weight: 600; color: var(--ink); }
        .llx details.mxd > summary::-webkit-details-marker { display: none; }
        .llx details.mxd > summary > i:first-child { color: var(--acc); }
        .llx details.mxd > summary .hint { font-weight: 400; color: var(--muted); font-size: 11.5px; }
        .llx details.mxd > summary .fa-chevron-down { margin-inline-start: auto; color: var(--muted); transition: transform .15s; }
        .llx details.mxd[open] > summary .fa-chevron-down { transform: rotate(180deg); }
        .llx details.mxd[open] > summary { border-bottom: 1px solid var(--line); }
        .llx .mx-wrap { overflow: auto; max-height: 420px; }
        .llx .mx { border-collapse: separate; border-spacing: 0; font-size: 11.5px; width: 100%; }
        .llx .mx th, .llx .mx td { padding: 6px 8px; text-align: center; border-bottom: 1px solid var(--line-soft); white-space: nowrap; }
        .llx .mx thead th { font-size: 10px; font-weight: 600; color: var(--muted); background: var(--surface-2); text-transform: uppercase; letter-spacing: .4px; }
        .llx .mx thead tr.sg th { color: var(--tn-ink); border-bottom: 2px solid var(--tn); background: var(--surface); font-size: 10.5px; }
        .llx .mx thead tr.sg th.blank { border-bottom-color: transparent; }
        .llx .mx th.pj { text-align: start; position: sticky; inset-inline-start: 0; z-index: 1; min-width: 190px; }
        .llx .mx tbody th.pj { font-weight: 500; color: var(--ink); background: var(--surface); }
        .llx .mx tbody tr:hover th, .llx .mx tbody tr:hover td { background: color-mix(in srgb, var(--acc) 4%, var(--surface)); }
        .llx .mx .c { display: inline-grid; place-items: center; min-width: 26px; height: 22px; padding: 0 5px; border-radius: 6px; font-variant-numeric: tabular-nums; font-weight: 600; color: var(--tn-ink); text-decoration: none; }
        .llx .mx a.c:hover { outline: 2px solid var(--tn); }
        .llx .mx .c.z { color: var(--faint); font-weight: 400; }
        .llx .mx .tt { font-weight: 700; color: var(--ink); }

        /* filter bar */
        .llx .fbar { display: flex; flex-wrap: nowrap; gap: 8px; padding: 10px 12px; border-bottom: 1px solid var(--line); align-items: center; }
        .llx .fbar:has(+ .fgrid) { border-bottom: 0; padding-bottom: 8px; }
        .llx .dgrp { display: flex; border: 1px solid var(--line); border-radius: 9px; overflow: hidden; height: 32px; background: var(--surface); min-width: 0; }
        .llx .dgrp .ctl { border: 0; border-radius: 0; height: 30px; box-shadow: none; flex: 1 1 0; min-width: 0; }
        .llx .dgrp select.ctl { flex: 0 0 auto; width: 112px; }
        .llx .dgrp input.ctl { border-inline-start: 1px solid var(--line); }
        .llx .fgrid { padding: 0 12px 10px; border-bottom: 1px solid var(--line); }
        .llx .fgrid .ctl, .llx .fgrid .fts, .llx .fgrid .dgrp { width: 100%; }

        /* TomSelect filters painted as .ctl */
        .llx .fts { min-width: 0; }
        .llx .ts-wrapper.lead-filter-ts { height: 32px; min-height: 32px; padding: 0 26px 0 10px; display: flex; align-items: center; overflow: visible; border: 1px solid var(--line); border-radius: 9px; background-color: var(--surface); color: var(--ink); font-size: 12px; }
        .llx .ts-wrapper.lead-filter-ts .ts-control,
        .llx .ts-wrapper.lead-filter-ts.input-active .ts-control { padding: 0; min-height: 0; border: 0; border-radius: 0; background: transparent; box-shadow: none; color: inherit; font-size: inherit; line-height: inherit; flex-wrap: nowrap; overflow: hidden; }
        .llx .ts-wrapper.lead-filter-ts .ts-control > input { padding: 0; margin: 0; min-height: 0; font-size: inherit; line-height: inherit; color: inherit; }
        .llx .ts-wrapper.lead-filter-ts .ts-control > .item { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .llx .ts-wrapper.lead-filter-ts.single .ts-control::after { display: none; }
        .llx .ts-wrapper.lead-filter-ts.focus { border-color: var(--acc); box-shadow: 0 0 0 3px color-mix(in srgb, var(--acc) 16%, transparent); }
        .llx .ts-wrapper.lead-filter-ts .ts-dropdown { width: 100%; min-width: 200px; margin-top: 4px; font-size: inherit; color: var(--ink); background: var(--surface); border: 1px solid var(--line); border-radius: 10px; box-shadow: var(--shadow); }
        .llx .ts-wrapper.lead-filter-ts .ts-dropdown .ts-dropdown-content { max-height: 280px; }
        .llx .ts-wrapper.lead-filter-ts .ts-dropdown .option { padding: 6px 12px; }
        .llx .ts-wrapper.lead-filter-ts .ts-dropdown .active { color: inherit; background: var(--surface-2); }
        .llx .ts-wrapper.lead-filter-ts .ts-dropdown .selected { font-weight: 600; color: var(--acc); }

        /* chips */
        .llx .chips { display: flex; flex-wrap: wrap; gap: 6px; padding: 8px 12px; border-bottom: 1px solid var(--line); align-items: center; min-height: 41px; }
        .llx .chip { display: inline-flex; align-items: center; gap: 5px; padding: 3px 5px 3px 9px; border-radius: 999px; font-size: 11.5px; background: color-mix(in srgb, var(--acc) 10%, transparent); color: color-mix(in srgb, var(--acc) 78%, var(--mix)); max-width: 260px; }
        .llx .chip b { font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .llx .chip button { border: 0; background: transparent; color: inherit; cursor: pointer; opacity: .7; width: 18px; height: 18px; display: grid; place-items: center; border-radius: 50%; padding: 0; }
        .llx .chip button:hover { background: color-mix(in srgb, var(--acc) 20%, transparent); opacity: 1; }
        .llx .chips .clr { border: 0; background: transparent; font-size: 11.5px; color: var(--muted); cursor: pointer; padding: 0 4px; }
        .llx .chips .clr:hover { color: var(--bs-danger); }
        .llx .chips .count { margin-inline-start: auto; font-size: 11.5px; color: var(--muted); }
        .llx .chips .count b { color: var(--ink); }

        /* table */
        .llx .tbl-wrap { overflow: auto; }
        .llx .tbl { width: 100%; border-collapse: separate; border-spacing: 0; }
        .llx .tbl th { font-size: 10.5px; text-transform: uppercase; letter-spacing: .7px; color: var(--muted); font-weight: 600; text-align: start; padding: 9px 10px; background: var(--surface-2); border-bottom: 1px solid var(--line); white-space: nowrap; }
        .llx .tbl th a { color: inherit !important; }
        .llx .tbl td { padding: 8px 10px; border-bottom: 1px solid var(--line-soft); vertical-align: middle; }
        .llx .tbl tbody tr:hover td { background: color-mix(in srgb, var(--acc) 4%, transparent); }
        .llx .tbl tbody tr.sel td { background: color-mix(in srgb, var(--acc) 8%, transparent); }
        .llx .tbl .nw { white-space: nowrap; }
        .llx .tbl .num { font-variant-numeric: tabular-nums; }
        .llx .tbl .ck { width: 34px; padding-inline-end: 0; }
        .llx .cb { width: 15px; height: 15px; accent-color: var(--acc); cursor: pointer; margin: 0; vertical-align: middle; }
        .llx .who { display: flex; align-items: center; gap: 9px; min-width: 190px; }
        .llx .av { width: 28px; height: 28px; flex: none; border-radius: 50%; display: grid; place-items: center; font-size: 10.5px; font-weight: 600; color: var(--tn-ink); background: var(--tn-soft); }
        .llx .av.sm { width: 22px; height: 22px; font-size: 9.5px; }
        .llx .nm { color: var(--ink); font-weight: 600; text-decoration: none; white-space: nowrap; }
        .llx .nm:hover { color: var(--acc); }
        .llx .lid { font-size: 10.5px; color: var(--faint); margin-inline-start: 3px; }
        .llx .sub { font-size: 11px; color: var(--muted); }
        .llx .fnt { color: var(--faint); }
        .llx .pill { display: inline-flex; align-items: center; gap: 5px; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 500; background: var(--tn-soft); color: var(--tn-ink); white-space: nowrap; }
        .llx .pill .d { width: 6px; height: 6px; border-radius: 50%; background: var(--tn); }
        .llx .tag { display: inline-flex; align-items: center; padding: 1px 7px; border-radius: 6px; font-size: 11px; border: 1px solid var(--line); background: var(--surface-2); color: var(--ink-2); white-space: nowrap; }
        .llx .owner { display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; }
        .llx .today { color: var(--acc); font-weight: 600; }
        .llx .go { width: 30px; height: 30px; display: inline-grid; place-items: center; border-radius: 8px; color: var(--muted); text-decoration: none; }
        .llx .go:hover { background: var(--surface-3); color: var(--acc); }
        .llx .empty { text-align: center; padding: 48px 12px; color: var(--muted); }
        .llx .empty i { font-size: 34px; opacity: .3; display: block; margin-bottom: 10px; }

        /* pager */
        .llx .pager { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 10px 12px; flex-wrap: wrap; font-size: 12px; color: var(--muted); }
        .llx .pager .rows { display: inline-flex; align-items: center; gap: 6px; }
        .llx .pager .rows .ctl { height: 28px; width: 72px; }
        .llx .pager nav { margin: 0; }
        .llx .pager .pagination { margin: 0; }

        /* floating bulk bar */
        .llx .fbulk { position: fixed; inset-inline-start: 50%; bottom: 22px; transform: translateX(-50%); z-index: 1040; display: flex; align-items: center; gap: 6px; padding: 6px 6px 6px 14px; border-radius: 14px; background: #141a24; color: #e6eaf1; box-shadow: var(--float); animation: llx-rise .18s ease-out; max-width: calc(100vw - 32px); }
        [dir="rtl"] .llx .fbulk { transform: translateX(50%); padding: 6px 14px 6px 6px; }
        [data-bs-theme="dark"] .llx .fbulk { background: #e9edf3; color: #141a24; }
        .llx .fbulk .n { display: inline-grid; place-items: center; min-width: 24px; height: 24px; padding: 0 6px; border-radius: 7px; background: var(--acc); color: #fff; font-weight: 700; font-size: 12px; font-variant-numeric: tabular-nums; }
        .llx .fbulk .lbl { font-size: 12.5px; font-weight: 500; margin-inline-end: 8px; white-space: nowrap; }
        .llx .fbulk .sep { width: 1px; align-self: stretch; margin: 4px 2px; background: currentColor; opacity: .18; }
        .llx .fbulk button { height: 34px; border: 0; border-radius: 10px; padding: 0 12px; font-size: 12.5px; font-weight: 500; display: inline-flex; align-items: center; gap: 7px; cursor: pointer; background: transparent; color: inherit; white-space: nowrap; }
        .llx .fbulk button:hover { background: color-mix(in srgb, currentColor 12%, transparent); }
        .llx .fbulk button.del { background: var(--bs-danger); color: #fff; }
        .llx .fbulk button.del:hover { background: color-mix(in srgb, var(--bs-danger) 85%, #000); }
        .llx .fbulk button:disabled { opacity: .6; cursor: progress; }
        @keyframes llx-rise { from { opacity: 0; transform: translate(-50%, 12px); } to { opacity: 1; transform: translate(-50%, 0); } }
        [dir="rtl"] .llx .fbulk { animation-name: llx-rise-rtl; }
        @keyframes llx-rise-rtl { from { opacity: 0; transform: translate(50%, 12px); } to { opacity: 1; transform: translate(50%, 0); } }

        @media (max-width: 1199px) {
            .llx .strip { grid-template-columns: repeat(var(--stages, 5), minmax(0, 1fr)); }
            .llx .stg.tot { grid-column: 1 / -1; border-top: 1px solid var(--line-soft); }
        }
        @media (max-width: 767px) {
            .llx .strip { grid-template-columns: repeat(var(--stages, 5), minmax(100px, 1fr)); overflow-x: auto; }
            .llx .stg .v { font-size: 17px; }
            .llx .hide-sm { display: none; }
            .llx .fbulk .lbl { display: none; }
        }
    </style>
@endonce

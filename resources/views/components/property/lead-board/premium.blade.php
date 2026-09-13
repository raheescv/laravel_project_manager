{{--
    Lead Board — "Focus deck" design system, scoped to .lbx.

    Accent follows the settings theme (--bs-primary). Status colours use the
    Bootstrap tones leadStatusBadgeClass() already assigns, so cards match the
    list badges. The details panel appears only while a lead is selected; it
    docks beside the columns when the board card itself is wide (a container
    query), so an open or collapsed sidebar needs no viewport guess.
    The board fills the window below its own top edge (--lbx-top, measured by
    Alpine), and the full screen toggle lays it over the whole app. Tune
    density from --lbx-fz.
--}}
@once
    <style>
        .lbx {
            --lbx-fz: 12.5px;
            --acc: var(--bs-primary);
            --surface: #fff; --surface-2: #f4f6fa; --surface-3: #e9edf3;
            --ink: var(--bs-emphasis-color); --ink-2: var(--bs-body-color);
            --muted: var(--bs-secondary-color); --faint: var(--bs-tertiary-color);
            --line: #e3e7ee; --line-soft: #edf0f5;
            --mix: #000;
            --ink-strength: 72%;
            --r: 14px;
            --shadow: 0 1px 2px rgba(16, 24, 40, .05), 0 10px 30px -12px rgba(16, 24, 40, .14);
            --card-shadow: 0 1px 2px rgba(16, 24, 40, .06);
            --lift: 0 14px 30px -12px rgba(16, 24, 40, .28), 0 4px 10px -4px rgba(16, 24, 40, .12);
            display: flex; flex-direction: column; gap: 12px;
            height: max(620px, calc(100vh - var(--lbx-top, 80px) - 20px));
            font-size: var(--lbx-fz); line-height: 1.45; color: var(--ink-2);
        }
        .lbx.is-full { position: fixed; inset: 0; z-index: 1045; height: 100vh; padding: 12px; background: #eef1f6; overflow: auto; }
        [data-bs-theme="dark"] .lbx.is-full { background: #1b2027; }
        [data-bs-theme="dark"] .lbx {
            --surface: #262c33; --surface-2: #1f252b; --surface-3: #323a43;
            --line: #373f49; --line-soft: #30373f;
            --mix: #fff;
            --ink-strength: 50%;
            --shadow: 0 1px 2px rgba(0, 0, 0, .4), 0 12px 30px -12px rgba(0, 0, 0, .55);
            --card-shadow: 0 1px 2px rgba(0, 0, 0, .35);
            --lift: 0 18px 36px -14px rgba(0, 0, 0, .75);
        }
        .lbx *, .lbx *::before, .lbx *::after { box-sizing: border-box; }
        .lbx button, .lbx input, .lbx select, .lbx textarea { font-family: inherit; }
        .lbx .tn { --tn-ink: color-mix(in srgb, var(--tn) var(--ink-strength), var(--mix)); --tn-soft: color-mix(in srgb, var(--tn) 13%, transparent); }
        .lbx .tn-primary { --tn: var(--bs-primary); }
        .lbx .tn-info { --tn: var(--bs-info); }
        .lbx .tn-success { --tn: var(--bs-success); }
        .lbx .tn-warning { --tn: var(--bs-warning); }
        .lbx .tn-danger { --tn: var(--bs-danger); }
        .lbx .tn-secondary { --tn: var(--bs-secondary); }

        .lbx .lbx-card { background: var(--surface); border: 1px solid var(--line); border-radius: var(--r); box-shadow: var(--shadow); }
        .lbx .icon-btn { width: 24px; height: 24px; border: 0; background: transparent; color: var(--faint); border-radius: 7px; cursor: pointer; display: grid; place-items: center; flex: 0 0 auto; font-size: 11px; padding: 0; }
        .lbx .icon-btn:hover { background: var(--surface-2); color: var(--ink); }
        .lbx .dot { width: 8px; height: 8px; border-radius: 50%; background: var(--tn); flex: 0 0 auto; box-shadow: 0 0 0 3px var(--tn-soft); }
        .lbx .muted { color: var(--faint); }

        /* ---- toolbar: neutral, it sits over the header band ---- */
        .lbx .lbx-top { padding: 12px 14px; position: relative; z-index: 8; flex: none; }
        .lbx .tb { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
        .lbx .srch { position: relative; flex: 1 1 220px; max-width: 320px; margin: 0; }
        .lbx .srch i { position: absolute; inset-inline-start: 11px; top: 50%; transform: translateY(-50%); color: var(--faint); }
        .lbx .srch input { width: 100%; height: 34px; border: 1px solid var(--line); background: var(--surface-2); border-radius: 10px; padding-inline: 32px 10px; color: var(--ink); font-size: 12.5px; }
        .lbx .srch input:focus { outline: none; background: var(--surface); border-color: color-mix(in srgb, var(--ink) 30%, var(--line)); }
        .lbx .seg { display: inline-flex; background: var(--surface-2); border: 1px solid var(--line); border-radius: 10px; padding: 2px; height: 34px; }
        .lbx .seg button { border: 0; background: transparent; color: var(--muted); font-size: 12px; padding: 0 11px; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; font-weight: 500; }
        .lbx .seg button.on { background: var(--surface); color: var(--ink); box-shadow: 0 1px 3px rgba(16, 24, 40, .14); font-weight: 600; }
        [data-bs-theme="dark"] .lbx .seg button.on { background: var(--surface-3); }
        .lbx .lbx-ts { width: 180px; flex: 0 1 180px; min-width: 140px; }
        .lbx .lbx-ts .ts-wrapper { min-height: 34px; }
        .lbx .lbx-ts .ts-wrapper .ts-control, .lbx .lbx-ts .ts-wrapper.single.input-active .ts-control { min-height: 34px; height: 34px; padding-block: 0; padding-inline: 10px 30px; border: 1px solid var(--line); background: var(--surface-2); border-radius: 10px; font-size: 12px; color: var(--ink-2); box-shadow: none; display: flex; align-items: center; flex-wrap: nowrap; overflow: hidden; cursor: pointer; }
        .lbx .lbx-ts .ts-wrapper.focus .ts-control { background: var(--surface); border-color: color-mix(in srgb, var(--ink) 30%, var(--line)); box-shadow: none; }
        .lbx .lbx-ts .ts-control > input { font-size: 12px; color: var(--ink); min-width: 3rem; }
        .lbx .lbx-ts .ts-control > input::placeholder { color: var(--ink-2); opacity: 1; }
        .lbx .lbx-ts .ts-control > .item { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--ink); font-weight: 500; }
        .lbx .lbx-ts .ts-wrapper.single .ts-control::after, .lbx .lbx-ts .ts-wrapper.single.dropdown-active .ts-control::after { content: ""; position: absolute; top: 50%; right: auto; inset-inline-end: 10px; width: 10px; height: 6px; margin-top: -3px; border: 0; transition: transform .15s;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%238b95a5' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E") no-repeat center; }
        .lbx .lbx-ts .ts-wrapper.single.dropdown-active .ts-control::after { transform: rotate(180deg); }
        .lbx .lbx-ts .ts-wrapper.plugin-clear_button .clear { right: auto; inset-inline-end: 24px; color: var(--faint); font-size: 15px; }
        .lbx .lbx-ts .ts-wrapper.plugin-clear_button.has-items .ts-control { padding-inline-end: 42px; }
        .lbx .lbx-ts .ts-dropdown { margin-top: 4px; min-width: 220px; border: 1px solid var(--line); border-radius: 10px; background: var(--surface); color: var(--ink-2); box-shadow: var(--lift); font-size: 12px; overflow: hidden; z-index: 30; }
        .lbx .lbx-ts .ts-dropdown .ts-dropdown-content { max-height: 300px; }
        .lbx .lbx-ts .ts-dropdown .option, .lbx .lbx-ts .ts-dropdown .no-results { padding: 7px 11px; }
        .lbx .lbx-ts .ts-dropdown .active { background: var(--surface-2); color: var(--ink); }
        .lbx .lbx-ts .ts-dropdown .selected { color: var(--ink); font-weight: 600; }
        .lbx .lbx-ts .lbx-ts-sub { margin-inline-start: 6px; color: var(--faint); font-size: 10.5px; }
        .lbx .seg a { display: inline-flex; align-items: center; gap: 6px; padding: 0 11px; border-radius: 8px; color: var(--muted); font-size: 12px; font-weight: 500; text-decoration: none; white-space: nowrap; }
        .lbx .seg a:hover { color: var(--ink); }
        .lbx .seg a.on { background: var(--surface); color: var(--ink); box-shadow: 0 1px 3px rgba(16, 24, 40, .14); font-weight: 600; }
        [data-bs-theme="dark"] .lbx .seg a.on { background: var(--surface-3); }
        .lbx .btn-new { height: 34px; display: inline-flex; align-items: center; gap: 7px; padding-inline: 13px; border-radius: 10px; background: var(--acc); color: #fff; font-size: 12px; font-weight: 600; text-decoration: none; white-space: nowrap; }
        .lbx .btn-new:hover { color: #fff; background: color-mix(in srgb, var(--acc), #000 12%); }
        .lbx .pill, .lbx .ghost { height: 34px; display: inline-flex; align-items: center; gap: 7px; padding-inline: 11px; border: 1px solid var(--line); border-radius: 10px; font-size: 12px; color: var(--ink-2); white-space: nowrap; }
        .lbx .pill { background: var(--surface-2); }
        .lbx .pill i { color: var(--faint); }
        .lbx .pill input[type="date"] { border: 0; background: transparent; color: var(--ink-2); font-size: 12px; padding: 0; width: 116px; outline: none; }
        .lbx .ghost { background: var(--surface); cursor: pointer; }
        .lbx .ghost.icon { width: 34px; padding: 0; justify-content: center; }
        .lbx .ghost:hover { color: var(--ink); border-color: color-mix(in srgb, var(--ink) 22%, var(--line)); }
        .lbx .tgl { display: inline-flex; align-items: center; gap: 8px; font-size: 12px; color: var(--ink-2); cursor: pointer; height: 34px; padding-inline: 4px; user-select: none; position: relative; margin: 0; }
        .lbx .tgl input { position: absolute; opacity: 0; pointer-events: none; }
        .lbx .tgl .trk { width: 30px; height: 18px; border-radius: 99px; background: var(--surface-3); border: 1px solid var(--line); position: relative; transition: .2s; }
        .lbx .tgl .trk::after { content: ""; position: absolute; top: 2px; inset-inline-start: 2px; width: 12px; height: 12px; border-radius: 50%; background: #fff; box-shadow: 0 1px 2px rgba(0, 0, 0, .25); transition: .2s; }
        .lbx .tgl input:checked + .trk { background: var(--ink); border-color: var(--ink); }
        .lbx .tgl input:checked + .trk::after { inset-inline-start: 14px; background: var(--surface); }
        .lbx .tb-end { margin-inline-start: auto; display: flex; gap: 8px; align-items: center; }

        /* ---- pipeline ribbon + quick filters ---- */
        .lbx .rib-wrap { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 12px 24px; align-items: center; margin-top: 12px; padding-top: 12px; border-top: 1px dashed var(--line); }
        .lbx .rib-wrap > * { min-width: 0; }
        .lbx .rib { display: flex; height: 9px; border-radius: 99px; overflow: hidden; gap: 2px; background: var(--surface-3); }
        .lbx .rib i { display: block; background: var(--tn); min-width: 4px; transition: flex-grow .45s ease; }
        .lbx .rib-legend { display: flex; flex-wrap: wrap; gap: 4px 18px; margin-top: 9px; align-items: baseline; }
        .lbx .rl { display: flex; align-items: baseline; gap: 6px; font-size: 11.5px; color: var(--muted); }
        .lbx .rl .d { width: 8px; height: 8px; border-radius: 3px; background: var(--tn); align-self: center; }
        .lbx .rl b { color: var(--ink); font-weight: 600; font-variant-numeric: tabular-nums; font-size: 13px; }
        .lbx .rl em { font-style: normal; color: var(--faint); font-size: 11px; }
        .lbx .rl.total b { font-size: 15px; }
        .lbx .quick { display: flex; gap: 8px; flex-wrap: wrap; }
        .lbx .qf { border: 1px solid var(--line); background: var(--surface); border-radius: 11px; padding: 6px 12px 6px 7px; display: flex; align-items: center; gap: 9px; cursor: pointer; text-align: start; color: var(--ink-2); transition: .15s; }
        .lbx .qf:hover { border-color: color-mix(in srgb, var(--tn) 40%, var(--line)); }
        .lbx .qf .ic { width: 28px; height: 28px; border-radius: 8px; display: grid; place-items: center; background: var(--tn-soft); color: var(--tn-ink); }
        .lbx .qf b { display: block; font-size: 14px; color: var(--ink); font-variant-numeric: tabular-nums; line-height: 1.1; }
        .lbx .qf small { display: block; font-size: 10.5px; color: var(--muted); white-space: nowrap; }
        .lbx .qf.on { border-color: var(--tn); box-shadow: 0 0 0 3px var(--tn-soft); }

        /* ---- board card: rail | columns | details ---- */
        .lbx .lbx-boardcard { flex: 1 1 auto; min-height: 420px; overflow: hidden; position: relative; container-type: inline-size; container-name: lbx; }
        .lbx .lbx-main { display: grid; grid-template-columns: 224px minmax(0, 1fr); height: 100%; }
        .lbx .lbx-rail { border-inline-end: 1px solid var(--line); padding: 8px 8px 14px; overflow-y: auto; scrollbar-width: thin; min-height: 0; }
        .lbx .rail-sec { font-size: 9.5px; letter-spacing: 1.4px; text-transform: uppercase; color: var(--faint); font-weight: 600; padding: 10px 8px 5px; display: flex; justify-content: space-between; }
        .lbx .rail-sec em { font-style: normal; letter-spacing: 0; }
        .lbx .rv { width: 100%; display: flex; align-items: center; gap: 9px; border: 0; background: transparent; padding: 7px 8px; border-radius: 9px; color: var(--ink-2); font-size: 12.5px; cursor: pointer; text-align: start; white-space: nowrap; }
        .lbx .rv i { width: 16px; text-align: center; color: var(--faint); }
        .lbx .rv span { margin-inline-start: auto; font-size: 11px; color: var(--faint); font-variant-numeric: tabular-nums; }
        .lbx .rv:hover { background: var(--surface-2); }
        .lbx .rv.on { background: color-mix(in srgb, var(--acc) 10%, transparent); color: color-mix(in srgb, var(--acc) var(--ink-strength), var(--mix)); font-weight: 600; }
        .lbx .rv.on i, .lbx .rv.on span { color: inherit; }
        .lbx .rs-stage { display: flex; align-items: center; gap: 6px; font-size: 10.5px; color: var(--tn-ink); padding: 9px 8px 3px; font-weight: 600; }
        .lbx .rs { width: 100%; display: flex; align-items: center; gap: 8px; padding: 5px 8px; border: 0; background: transparent; border-radius: 8px; font-size: 12px; color: var(--muted); cursor: pointer; user-select: none; white-space: nowrap; text-align: start; }
        .lbx .rs:hover { background: var(--surface-2); color: var(--ink-2); }
        .lbx .rs .rs-name { overflow: hidden; text-overflow: ellipsis; }
        .lbx .rs .n { margin-inline-start: auto; font-size: 10.5px; color: var(--faint); font-variant-numeric: tabular-nums; }
        .lbx .rs .eye { color: var(--faint); width: 14px; text-align: center; font-size: 11px; }
        .lbx .rs.pinned { color: var(--ink); font-weight: 500; }
        .lbx .rs.pinned .eye { color: var(--tn-ink); }
        .lbx .rs:not(.pinned) .dot { box-shadow: none; opacity: .4; }

        .lbx .lbx-bscroll { overflow-x: auto; overflow-y: hidden; padding: 14px; scrollbar-width: thin; min-width: 0; min-height: 0; }
        .lbx .lbx-board { display: flex; gap: 12px; align-items: flex-start; height: 100%; width: max-content; min-width: 100%; transition: opacity .15s; }
        .lbx .lbx-board.is-busy { opacity: .55; }
        .lbx .board-empty { margin: auto; display: flex; flex-direction: column; align-items: center; gap: 6px; color: var(--faint); text-align: center; padding: 40px 20px; }
        .lbx .board-empty i { font-size: 24px; }
        .lbx .board-empty b { color: var(--ink); font-weight: 600; }

        /* ---- status column ---- */
        .lbx .lbx-col { width: 288px; max-height: 100%; flex: 0 0 auto; background: var(--surface-2); border: 1px solid var(--line); border-radius: 12px; display: flex; flex-direction: column; transition: border-color .15s, box-shadow .15s, background .15s; }
        .lbx .col-h { padding: 11px 10px 8px; border-bottom: 1px solid var(--line-soft); position: relative; }
        .lbx .col-h::before { content: ""; position: absolute; inset-inline: 12px; top: 0; height: 2px; border-radius: 0 0 3px 3px; background: var(--tn); }
        .lbx .col-row { display: flex; align-items: center; gap: 7px; min-width: 0; }
        .lbx .col-name { font-weight: 600; color: var(--ink); font-size: 12.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .lbx .col-name i { color: var(--tn-ink); }
        .lbx .col-count { font-size: 11px; font-weight: 600; color: var(--tn-ink); background: var(--tn-soft); border-radius: 99px; padding: 1px 8px; font-variant-numeric: tabular-nums; }
        .lbx .col-row .icon-btn { margin-inline-start: auto; }
        .lbx .col-meter { height: 3px; background: var(--surface-3); border-radius: 99px; margin-top: 8px; overflow: hidden; }
        .lbx .col-meter i { display: block; height: 100%; background: var(--tn); border-radius: 99px; opacity: .75; transition: width .4s; }
        .lbx .col-hint { font-size: 10.5px; color: var(--tn-ink); margin-top: 6px; }
        .lbx .lbx-col-body { padding: 8px; display: flex; flex-direction: column; gap: 8px; min-height: 90px; overflow-y: auto; scrollbar-width: thin; flex: 1 1 auto; }
        .lbx .col-f { padding: 6px 8px 8px; border-top: 1px solid var(--line-soft); display: flex; align-items: center; justify-content: space-between; gap: 8px; font-size: 11px; color: var(--muted); font-variant-numeric: tabular-nums; min-height: 38px; }
        .lbx .load { border: 1px dashed var(--line); background: transparent; color: var(--ink-2); font-size: 11px; padding: 3px 10px; border-radius: 8px; cursor: pointer; font-weight: 500; white-space: nowrap; }
        .lbx .load:hover { border-style: solid; color: var(--ink); background: var(--surface); }
        .lbx .done { color: var(--faint); }
        .lbx .empty { border: 1.5px dashed var(--line); border-radius: 10px; padding: 18px 10px; text-align: center; color: var(--faint); font-size: 11.5px; }
        .lbx .empty i { display: block; font-size: 16px; margin-bottom: 5px; opacity: .7; }
        .lbx .lbx-col.over { border-color: var(--tn); box-shadow: 0 0 0 3px var(--tn-soft); background: color-mix(in srgb, var(--tn) 6%, var(--surface)); }

        /* ---- lead card ---- */
        .lbx .lc { position: relative; background: var(--surface); border: 1px solid var(--line); border-radius: 11px; padding: 9px 10px 8px; padding-inline-start: 13px; box-shadow: var(--card-shadow); cursor: pointer; transition: transform .15s, box-shadow .15s, border-color .15s, opacity .15s; outline: none; }
        .lbx .lc[draggable="true"] { cursor: grab; }
        .lbx .lc::before { content: ""; position: absolute; inset-block: 10px; inset-inline-start: -1px; width: 3px; border-radius: 0 3px 3px 0; background: var(--tn); }
        [dir="rtl"] .lbx .lc::before { border-radius: 3px 0 0 3px; }
        .lbx .lc:hover { transform: translateY(-1px); box-shadow: var(--lift); border-color: color-mix(in srgb, var(--tn) 35%, var(--line)); }
        .lbx .lc:focus-visible { border-color: var(--ink); }
        .lbx .lc.sel { border-color: var(--acc); box-shadow: 0 0 0 3px color-mix(in srgb, var(--acc) 20%, transparent); }
        .lbx .lc.drag-src { opacity: .4; transform: rotate(-1.5deg); }
        .lbx .lc.is-moving { opacity: .6; pointer-events: none; }
        .lbx .lc-top { display: flex; align-items: center; gap: 6px; margin-bottom: 2px; }
        .lbx .lc-id { font-size: 10.5px; color: var(--faint); font-variant-numeric: tabular-nums; }
        .lbx .ty { font-size: 9.5px; font-weight: 600; letter-spacing: .6px; text-transform: uppercase; padding: 1px 6px; border-radius: 5px; }
        .lbx .ty-sales { background: color-mix(in srgb, var(--acc) 12%, transparent); color: color-mix(in srgb, var(--acc) var(--ink-strength), var(--mix)); }
        .lbx .ty-rent { background: color-mix(in srgb, var(--bs-info) 14%, transparent); color: color-mix(in srgb, var(--bs-info) var(--ink-strength), var(--mix)); }
        .lbx .stale { font-size: 10px; font-weight: 600; color: color-mix(in srgb, var(--bs-warning) var(--ink-strength), var(--mix)); display: inline-flex; gap: 3px; align-items: center; white-space: nowrap; }
        .lbx .lc-name { font-weight: 600; color: var(--ink); font-size: 13px; line-height: 1.3; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .lbx .lc-co { font-size: 11.5px; color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 1px; }
        .lbx .lc-co i { margin-inline-end: 5px; opacity: .7; font-size: 10.5px; }
        .lbx .lc-chips { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 7px; }
        .lbx .lc-chips:empty { display: none; }
        .lbx .chip { display: inline-flex; align-items: center; gap: 4px; font-size: 10.5px; color: var(--ink-2); background: var(--surface-2); border: 1px solid var(--line-soft); padding: 1px 7px; border-radius: 6px; max-width: 100%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .lbx .chip i { color: var(--faint); font-size: 10.5px; }
        .lbx .chip.legacy { background: color-mix(in srgb, var(--bs-danger) 11%, transparent); color: color-mix(in srgb, var(--bs-danger) var(--ink-strength), var(--mix)); border-color: transparent; font-weight: 600; }
        .lbx .chip.legacy i { color: inherit; }
        .lbx .lc-foot { display: flex; align-items: center; gap: 6px; margin-top: 8px; padding-top: 7px; border-top: 1px dashed var(--line-soft); min-width: 0; }
        .lbx .av { width: 22px; height: 22px; border-radius: 50%; display: grid; place-items: center; font-size: 9px; font-weight: 600; background: hsl(var(--h) 62% 91%); color: hsl(var(--h) 45% 30%); flex: 0 0 auto; letter-spacing: .2px; }
        [data-bs-theme="dark"] .lbx .av { background: hsl(var(--h) 26% 30%); color: hsl(var(--h) 70% 84%); }
        .lbx .av.none { background: var(--surface-3); color: var(--faint); }
        .lbx .who { font-size: 11px; color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; min-width: 0; }
        .lbx .sp { flex: 1; }
        .lbx .due { font-size: 10.5px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; padding: 1px 6px; border-radius: 6px; white-space: nowrap; }
        .lbx .due.today { background: color-mix(in srgb, var(--bs-warning) 17%, transparent); color: color-mix(in srgb, var(--bs-warning) var(--ink-strength), var(--mix)); }
        .lbx .due.over { background: color-mix(in srgb, var(--bs-danger) 13%, transparent); color: color-mix(in srgb, var(--bs-danger) var(--ink-strength), var(--mix)); }
        .lbx .due.soon { background: var(--surface-2); color: var(--ink-2); }
        .lbx .mini { font-size: 10.5px; color: var(--faint); display: inline-flex; align-items: center; gap: 3px; white-space: nowrap; font-variant-numeric: tabular-nums; }
        .lbx[data-density="compact"] .lc-co, .lbx[data-density="compact"] .lc-chips { display: none; }
        .lbx[data-density="compact"] .lc { padding-block: 7px 6px; }
        .lbx[data-density="compact"] .lc-foot { margin-top: 4px; padding-top: 0; border: 0; }
        .lbx[data-density="compact"] .lbx-col-body { gap: 6px; }

        /* ---- details panel: slides over the board, docks when the card is wide ---- */
        .lbx .lbx-peek { background: var(--surface); display: flex; flex-direction: column; min-height: 0; position: absolute; inset-block: 0; inset-inline-end: 0; width: min(380px, 100%); z-index: 6; box-shadow: 0 0 50px -12px rgba(0, 0, 0, .4); border-inline-start: 1px solid var(--line); transform: translateX(105%); visibility: hidden; transition: transform .28s cubic-bezier(.2, .8, .2, 1), visibility .28s; }
        [dir="rtl"] .lbx .lbx-peek { transform: translateX(-105%); }
        .lbx.peek-open .lbx-peek { transform: none; visibility: visible; }
        .lbx .lbx-scrim { position: absolute; inset: 0; background: rgba(10, 14, 22, .28); z-index: 5; opacity: 0; pointer-events: none; transition: opacity .25s; }
        .lbx.peek-open .lbx-scrim { opacity: 1; pointer-events: auto; }
        .lbx .lbx-peek.is-loading .pk-h, .lbx .lbx-peek.is-loading .pk-body { opacity: .55; transition: opacity .15s; }
        .lbx .pk-empty { margin: auto; display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 40px 28px; text-align: center; color: var(--faint); font-size: 12px; }
        .lbx .pk-empty i { font-size: 26px; margin-bottom: 4px; }
        .lbx .pk-empty b { color: var(--ink); font-weight: 600; font-size: 13px; }
        .lbx .pk-h { padding: 16px 16px 13px; border-bottom: 1px solid var(--line-soft); position: relative; }
        .lbx .pk-close { position: absolute; top: 12px; inset-inline-end: 12px; font-size: 13px; width: 28px; height: 28px; }
        .lbx .pk-id { display: flex; gap: 11px; align-items: center; padding-inline-end: 30px; }
        .lbx .pk-who { min-width: 0; }
        .lbx .pk-av { width: 44px; height: 44px; font-size: 14px; }
        .lbx .pk-name { font-size: 16px; font-weight: 600; color: var(--ink); line-height: 1.2; overflow-wrap: anywhere; }
        .lbx .pk-sub { font-size: 11.5px; color: var(--muted); margin-top: 3px; display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
        .lbx .pk-cur { display: inline-flex; align-items: center; gap: 7px; margin-top: 11px; font-size: 11.5px; font-weight: 600; color: var(--tn-ink); background: var(--tn-soft); padding: 3px 11px 3px 9px; border-radius: 99px; }
        .lbx .pk-cur .dot { box-shadow: none; }
        .lbx .pk-cur small { font-weight: 400; color: var(--muted); font-size: 11px; }
        .lbx .pk-acts { display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; margin-top: 12px; }
        .lbx .pk-act { display: flex; flex-direction: column; align-items: center; gap: 4px; padding: 8px 4px; border: 1px solid var(--line); border-radius: 10px; background: var(--surface); color: var(--ink-2); text-decoration: none; font-size: 10.5px; font-weight: 500; }
        .lbx .pk-act i { font-size: 14px; color: var(--muted); }
        .lbx a.pk-act:hover { border-color: color-mix(in srgb, var(--acc) 45%, var(--line)); color: var(--ink); }
        .lbx .pk-act.wa i { color: #25a366; }
        .lbx .pk-act.is-off { opacity: .45; cursor: not-allowed; }
        .lbx .pk-body { overflow-y: auto; padding: 2px 16px 18px; flex: 1; scrollbar-width: thin; }
        .lbx .pk-sec { margin-top: 15px; }
        .lbx .pk-lbl { font-size: 9.5px; letter-spacing: 1.3px; text-transform: uppercase; color: var(--faint); font-weight: 600; margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between; gap: 8px; }
        .lbx .pk-lbl span { text-transform: none; letter-spacing: 0; font-weight: 400; font-size: 10.5px; }
        .lbx .pk-lbl .badge { background: var(--surface-2); color: var(--muted); border-radius: 99px; padding: 0 7px; }
        .lbx .stpick { display: flex; flex-direction: column; gap: 7px; }
        .lbx .stpick-row { display: flex; flex-wrap: wrap; gap: 5px; align-items: center; }
        .lbx .stpick-row > small { width: 100%; font-size: 10px; color: var(--muted); }
        .lbx .stc { border: 1px solid var(--line); background: var(--surface); color: var(--ink-2); font-size: 11px; padding: 3px 9px; border-radius: 99px; cursor: pointer; display: inline-flex; gap: 5px; align-items: center; transition: .12s; }
        .lbx .stc .dot { width: 6px; height: 6px; box-shadow: none; }
        .lbx button.stc:hover { border-color: var(--tn); color: var(--tn-ink); }
        .lbx .stc.on { background: var(--tn); border-color: var(--tn); color: #fff; font-weight: 600; cursor: default; }
        .lbx .stc.on .dot { background: #fff; }
        .lbx .kv { display: grid; grid-template-columns: 96px minmax(0, 1fr); gap: 6px 10px; font-size: 12px; margin: 0; }
        .lbx .kv dt { color: var(--muted); font-weight: 400; }
        .lbx .kv dd { margin: 0; color: var(--ink); font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .lbx .kv dd i { color: var(--faint); margin-inline-end: 4px; }
        .lbx .addnote { border: 1px solid var(--line); border-radius: 10px; background: var(--surface-2); padding: 6px; margin-bottom: 12px; }
        .lbx .addnote:focus-within { border-color: color-mix(in srgb, var(--acc) 45%, var(--line)); }
        .lbx .addnote textarea { width: 100%; border: 0; background: transparent; resize: none; color: var(--ink); min-height: 40px; outline: none; padding: 4px; font-size: 12px; }
        .lbx .addnote-f { display: flex; gap: 6px; align-items: center; }
        .lbx .addnote input[type="date"] { border: 1px solid var(--line); border-radius: 7px; background: var(--surface); color: var(--ink-2); font-size: 11px; padding: 3px 6px; }
        .lbx .btn-acc { margin-inline-start: auto; background: var(--acc); color: #fff; border: 0; border-radius: 8px; padding: 5px 12px; font-size: 11.5px; font-weight: 600; cursor: pointer; }
        .lbx .btn-acc:disabled { opacity: .6; }
        .lbx .note { position: relative; padding-inline-start: 17px; padding-bottom: 11px; }
        .lbx .note::before { content: ""; position: absolute; inset-inline-start: 0; top: 5px; width: 9px; height: 9px; border-radius: 50%; background: var(--surface); border: 2px solid var(--faint); }
        .lbx .note::after { content: ""; position: absolute; inset-inline-start: 4px; top: 16px; bottom: 0; width: 1px; background: var(--line); }
        .lbx .note:last-child::after { display: none; }
        .lbx .note:first-child::before { border-color: var(--acc); }
        .lbx .note time { font-size: 10.5px; color: var(--faint); }
        .lbx .note p { margin: 1px 0 0; font-size: 12px; color: var(--ink-2); white-space: pre-line; overflow-wrap: anywhere; }
        .lbx .log { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 10px; }
        .lbx .log li { display: flex; gap: 10px; font-size: 12px; color: var(--ink-2); }
        .lbx .log li > i { width: 26px; height: 26px; border-radius: 50%; background: var(--surface-2); color: var(--muted); display: grid; place-items: center; flex: 0 0 auto; font-size: 11px; }
        .lbx .log small { display: block; color: var(--faint); font-size: 10.5px; }
        .lbx .log b { color: var(--ink); font-weight: 600; }

        /* ---- last move strip ---- */
        .lbx .lbx-last { position: absolute; bottom: 14px; left: 50%; transform: translateX(-50%); z-index: 7; display: flex; align-items: center; gap: 10px; background: #141a24; color: #e9edf3; border-radius: 12px; padding: 7px 8px 7px 13px; font-size: 12px; box-shadow: 0 18px 40px -12px rgba(0, 0, 0, .45); max-width: calc(100% - 28px); white-space: nowrap; }
        .lbx .lbx-last span { overflow: hidden; text-overflow: ellipsis; }
        .lbx .lbx-last .ok { color: #4cd18a; }
        .lbx .lbx-last b { font-weight: 600; }
        .lbx .lbx-last button { background: rgba(255, 255, 255, .12); border: 0; color: #fff; border-radius: 8px; padding: 4px 11px; font-size: 11.5px; font-weight: 600; cursor: pointer; }
        .lbx .lbx-last button.x { background: transparent; padding: 4px 6px; opacity: .7; }
        [data-bs-theme="dark"] .lbx .lbx-last { background: #eef1f6; color: #141a24; }
        [data-bs-theme="dark"] .lbx .lbx-last .ok { color: #1f9d63; }
        [data-bs-theme="dark"] .lbx .lbx-last button { background: rgba(0, 0, 0, .08); color: #141a24; }

        /* ---- responsive (container = the board card) ---- */
        @container lbx (min-width: 1180px) {
            .lbx.peek-open .lbx-main { grid-template-columns: 224px minmax(0, 1fr) 340px; }
            .lbx.peek-open .lbx-peek, [dir="rtl"] .lbx.peek-open .lbx-peek { position: static; width: auto; transform: none; visibility: visible; box-shadow: none; transition: none; }
            .lbx .lbx-scrim { display: none; }
        }
        @container lbx (max-width: 760px) {
            .lbx .lbx-main { grid-template-columns: minmax(0, 1fr); grid-template-rows: auto minmax(0, 1fr); }
            .lbx .lbx-rail { display: flex; gap: 6px; overflow-x: auto; overflow-y: hidden; border-inline-end: 0; border-bottom: 1px solid var(--line); padding: 10px; }
            .lbx .lbx-rail .rail-sec, .lbx .lbx-rail .rs-stage { display: none; }
            .lbx .lbx-rail .rv, .lbx .lbx-rail .rs { width: auto; flex: 0 0 auto; border: 1px solid var(--line); border-radius: 99px; padding: 5px 11px; }
            .lbx .lbx-rail .rv span, .lbx .lbx-rail .rs .n { margin-inline-start: 6px; }
            .lbx .lbx-bscroll { scroll-snap-type: x proximity; padding: 10px; }
            .lbx .lbx-col { width: min(280px, 82cqw); scroll-snap-align: start; }
        }
        @media (max-width: 767.98px) {
            .lbx .srch { max-width: none; flex-basis: 100%; }
            .lbx { height: auto; }
            .lbx .lbx-boardcard { flex: none; height: 85vh; }
            .lbx .tb .lbx-ts { flex: 1 1 calc(50% - 8px); width: auto; min-width: 0; }
            .lbx .tb-end { margin-inline-start: 0; }
            .lbx .rib-wrap { grid-template-columns: minmax(0, 1fr); }
            .lbx .quick { flex-wrap: nowrap; overflow-x: auto; }
        }
        @media (prefers-reduced-motion: reduce) {
            .lbx *, .lbx .lbx-peek { transition: none !important; }
        }
    </style>
@endonce

{{--
    RentOut / Booking editor — "Summary Rail" design system.

    Scoped under .bkx so only livewire/rent-out/page.blade.php is affected.
    Accent follows the settings theme (--bs-primary); the neutral ramp is pinned
    (light + [data-bs-theme="dark"]) so it matches docs/booking-edit-premium-preview.html.
--}}
@once
    <style>
        .bkx {
            --acc: var(--bs-primary);
            /* Neutral ramp pinned (independent of the app's greyer body tokens) so the form
               reads crisp in every theme; only the accent follows the settings colour. */
            --ink: #0f1522; --ink-2: #333b49; --muted: #697488; --faint: #98a1b0;
            --surface: #ffffff; --surface-2: #f6f8fb; --surface-3: #eceff4;
            --line: #e1e6ee; --line-soft: #eceff4;
            --soft: color-mix(in srgb, var(--acc) 11%, transparent);
            --r: 14px;
            --shadow: 0 1px 2px rgba(16, 24, 40, .05), 0 12px 30px -16px rgba(16, 24, 40, .18);
            min-width: 0; color: var(--ink-2); line-height: 1.45; font-size: 12.5px;
        }
        [data-bs-theme="dark"] .bkx {
            --ink: #eef1f6; --ink-2: #c4ccd8; --muted: #8b95a5; --faint: #5f6a7a;
            --surface: #252b32; --surface-2: #1f252b; --surface-3: #313941;
            --line: #37404a; --line-soft: #2e363f;
            --shadow: 0 1px 2px rgba(0, 0, 0, .4), 0 14px 30px -14px rgba(0, 0, 0, .6);
        }
        .bkx [x-cloak] { display: none !important; }

        /* ---- atoms ---- */
        .bkx .bk-card { background: var(--surface); border: 1px solid var(--line); border-radius: var(--r); box-shadow: var(--shadow); }
        .bkx .bk-lbl { display: flex; align-items: center; justify-content: space-between; gap: 6px; min-height: 18px; font-size: 11px; font-weight: 600; color: var(--muted); margin-bottom: 4px; }
        .bkx .bk-lbl .req { color: var(--bs-danger); margin-inline-start: 2px; }
        .bkx .bk-lbl .bk-note { font-weight: 400; color: var(--faint); }
        .bkx .ctl, .bkx .bk-fld .ts-wrapper .ts-control { width: 100%; min-height: 34px; border: 1px solid var(--line); background: var(--surface); color: var(--ink); border-radius: 9px; padding: 5px 10px; font-size: 12.5px; box-shadow: none; outline: 0; transition: border-color .15s, box-shadow .15s; }
        .bkx textarea.ctl { resize: vertical; }
        .bkx select.ctl { appearance: none; padding-inline-end: 30px; cursor: pointer;
            background: var(--surface) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%238b95a5' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E") no-repeat right 11px center; }
        .bkx .ctl:focus, .bkx .bk-fld .ts-wrapper.focus .ts-control { border-color: var(--acc); box-shadow: 0 0 0 3px color-mix(in srgb, var(--acc) 16%, transparent); }
        .bkx .ctl::placeholder { color: var(--faint); }
        .bkx .bk-fld .ts-wrapper { min-height: 34px; }
        .bkx .bk-fld .ts-wrapper .ts-control, .bkx .bk-fld .ts-wrapper .ts-control input, .bkx .bk-fld .ts-wrapper .ts-control .item { font-size: 12.5px; color: var(--ink); }
        .bkx .bk-fld .ts-wrapper .ts-control input::placeholder { color: var(--faint); }
        .bkx .bk-fld .ts-wrapper .ts-control { display: flex; align-items: center; padding-block: 3px; }
        .bkx .bk-fld .ts-dropdown { margin-top: 4px; border: 1px solid var(--line); border-radius: 9px; background: var(--surface); font-size: 12.5px; overflow: hidden; box-shadow: var(--shadow); }
        .bkx .bk-fld .ts-dropdown .active { background: var(--surface-2); color: var(--ink); }
        .bkx .bk-pre { position: relative; }
        .bkx .bk-pre .ctl { padding-inline-start: 50px; }
        .bkx .bk-pre > span { position: absolute; inset-inline-start: 1px; top: 1px; bottom: 1px; width: 42px; display: grid; place-items: center; font-size: 10.5px; font-weight: 600; color: var(--muted); background: var(--surface-2); border-inline-end: 1px solid var(--line); border-radius: 8px 0 0 8px; }
        .bkx .bk-eyebrow { font-size: 10px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; color: var(--acc); }
        .bkx .bk-chip { display: inline-flex; align-items: center; gap: 5px; font-size: 10.5px; font-weight: 600; padding: 3px 9px; border-radius: 20px; white-space: nowrap; color: var(--acc); background: var(--soft); }
        .bkx .bk-chip.tone-info { color: var(--bs-info); background: color-mix(in srgb, var(--bs-info) 14%, transparent); }
        .bkx .bk-chip.tone-success { color: var(--bs-success); background: color-mix(in srgb, var(--bs-success) 14%, transparent); }
        .bkx .bk-chip.tone-warning { color: var(--bs-warning); background: color-mix(in srgb, var(--bs-warning) 15%, transparent); }
        .bkx .bk-chip.tone-danger { color: var(--bs-danger); background: color-mix(in srgb, var(--bs-danger) 13%, transparent); }
        .bkx .bk-chip.tone-secondary { color: var(--muted); background: var(--surface-3); }
        .bkx .bk-chip .fa-circle { font-size: 7px; }
        .bkx .bk-btn { display: inline-flex; align-items: center; justify-content: center; gap: 7px; height: 34px; padding: 0 14px; border-radius: 9px; font-size: 12.5px; font-weight: 600; border: 1px solid transparent; cursor: pointer; white-space: nowrap; text-decoration: none; transition: filter .15s, background .15s; }
        .bkx .bk-btn.pri { background: var(--acc); color: #fff; }
        .bkx .bk-btn.ok { background: var(--bs-success); color: #fff; }
        .bkx .bk-btn.pri:hover, .bkx .bk-btn.ok:hover { filter: brightness(1.07); color: #fff; }
        .bkx .bk-btn.ghost { background: var(--surface); color: var(--ink-2); border-color: var(--line); }
        .bkx .bk-btn.ghost:hover { background: var(--surface-2); color: var(--ink); }
        .bkx .bk-btn.dng { background: transparent; color: var(--bs-danger); border-color: color-mix(in srgb, var(--bs-danger) 35%, transparent); }
        .bkx .bk-btn.dng:hover { background: color-mix(in srgb, var(--bs-danger) 8%, transparent); }
        .bkx .bk-btn.link { height: auto; padding: 0; border: 0; background: none; color: var(--acc); font-size: 11px; }
        .bkx .bk-g { display: grid; gap: 10px 12px; grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .bkx .bk-g.c3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .bkx .span2 { grid-column: span 2; } .bkx .span3 { grid-column: span 3; } .bkx .spanall { grid-column: 1 / -1; }
        .bkx .bk-tgl { display: inline-flex; align-items: center; gap: 6px; margin: 0; font-size: 11px; font-weight: 500; color: var(--muted); cursor: pointer; }
        .bkx .bk-tgl input { position: absolute; opacity: 0; pointer-events: none; }
        .bkx .bk-tgl .k { width: 26px; height: 15px; border-radius: 20px; background: var(--surface-3); position: relative; transition: .15s; }
        .bkx .bk-tgl .k::after { content: ""; position: absolute; top: 2px; left: 2px; width: 11px; height: 11px; border-radius: 50%; background: #fff; box-shadow: 0 1px 2px rgba(0, 0, 0, .25); transition: .15s; }
        .bkx .bk-tgl input:checked + .k { background: var(--acc); }
        .bkx .bk-tgl input:checked + .k::after { left: 13px; }
        .bkx .bk-seg { display: inline-flex; padding: 2px; border-radius: 9px; background: var(--surface-2); border: 1px solid var(--line); }
        .bkx .bk-seg button { border: 0; background: none; padding: 3px 11px; border-radius: 7px; font-size: 11.5px; font-weight: 600; color: var(--muted); cursor: pointer; }
        .bkx .bk-seg button.on { background: var(--surface); color: var(--ink); box-shadow: 0 1px 2px rgba(16, 24, 40, .12); }
        .bkx .bk-pills { display: flex; flex-wrap: wrap; gap: 6px; }
        .bkx .bk-pill { display: inline-flex; align-items: center; gap: 6px; height: 32px; padding: 0 12px; border-radius: 8px; border: 1px solid var(--line); background: var(--surface); color: var(--ink-2); font-size: 12px; font-weight: 500; cursor: pointer; transition: border-color .15s, background .15s; }
        .bkx .bk-pill:hover { border-color: color-mix(in srgb, var(--acc) 45%, var(--line)); }
        .bkx .bk-pill.on { border-color: var(--acc); background: var(--soft); color: var(--acc); font-weight: 600; }
        .bkx .bk-pill.off { color: var(--faint); text-decoration: line-through; }
        .bkx .bk-pill i { font-size: 11px; }

        /* ---- hero ---- */
        .bkx .bk-hero { display: flex; align-items: center; gap: 14px; padding: 14px 16px; margin-bottom: 12px; background: linear-gradient(120deg, color-mix(in srgb, var(--acc) 10%, var(--surface)) 0%, var(--surface) 60%); }
        .bkx .bk-hero .ic { width: 42px; height: 42px; flex: 0 0 42px; border-radius: 12px; display: grid; place-items: center; font-size: 17px; color: #fff; background: linear-gradient(135deg, var(--acc), color-mix(in srgb, var(--acc) 65%, #000)); box-shadow: 0 6px 16px -6px color-mix(in srgb, var(--acc) 70%, transparent); }
        .bkx .bk-hero h1 { margin: 0; font-size: 16px; font-weight: 600; color: var(--ink); display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .bkx .bk-hero h1 .no { font-weight: 500; color: var(--faint); }
        .bkx .bk-hero .sub { display: flex; flex-wrap: wrap; gap: 4px 14px; margin-top: 3px; font-size: 11.5px; color: var(--muted); }
        .bkx .bk-hero .sub i { color: var(--faint); margin-inline-end: 4px; }
        .bkx .bk-hero .acts { margin-inline-start: auto; display: flex; gap: 7px; }

        /* ---- layout ---- */
        .bkx .bk-grid { display: grid; grid-template-columns: minmax(0, 1fr) 312px; gap: 12px; align-items: start; }
        .bkx .bk-sec { margin-bottom: 12px; }
        .bkx .bk-sec > header { display: flex; align-items: center; gap: 9px; padding: 11px 14px; border-bottom: 1px solid var(--line-soft); }
        .bkx .bk-sec > header .n { width: 22px; height: 22px; flex: none; border-radius: 7px; display: grid; place-items: center; font-size: 10.5px; font-weight: 700; color: var(--acc); background: var(--soft); }
        .bkx .bk-sec > header h3 { margin: 0; font-size: 13px; font-weight: 600; color: var(--ink); }
        .bkx .bk-sec > header .aside { margin-inline-start: auto; display: flex; align-items: center; gap: 8px; }
        .bkx .bk-sec > header .meta { font-size: 11px; color: var(--muted); }
        .bkx .bk-sec > header .chev { margin-inline-start: auto; color: var(--faint); transition: transform .2s; }
        .bkx .bk-sec.is-collapsible > header { cursor: pointer; user-select: none; }
        .bkx .bk-sec.closed > header { border-bottom: 0; }
        .bkx .bk-sec.closed > header .chev { transform: rotate(-90deg); }
        .bkx .bk-sec > .bd { padding: 12px 14px 14px; }
        .bkx .bk-sub { display: flex; align-items: center; gap: 8px; margin: 14px 0 8px; font-size: 10px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: var(--faint); }
        .bkx .bk-sub::after { content: ""; flex: 1; height: 1px; background: var(--line-soft); }
        .bkx .bk-sub:first-child { margin-top: 0; }

        /* ---- rail ---- */
        .bkx .bk-rail { position: sticky; top: 76px; display: flex; flex-direction: column; gap: 12px; }
        .bkx .bk-unit, .bkx .bk-money { padding: 14px; }
        .bkx .bk-unit .top { display: flex; gap: 11px; align-items: center; margin-bottom: 8px; }
        .bkx .bk-unit .badge-no { min-width: 46px; height: 46px; padding: 0 6px; border-radius: 12px; display: grid; place-items: center; background: var(--soft); color: var(--acc); font-weight: 700; font-size: 13px; flex: none; }
        .bkx .bk-unit .badge-no.empty { color: var(--faint); background: var(--surface-2); }
        .bkx .bk-unit .t { font-weight: 600; color: var(--ink); font-size: 13px; line-height: 1.25; }
        .bkx .bk-unit .s { font-size: 11px; color: var(--muted); }
        .bkx .bk-kv { display: flex; justify-content: space-between; gap: 10px; padding: 6px 0; font-size: 12px; border-top: 1px dashed var(--line-soft); }
        .bkx .bk-kv:first-child { border-top: 0; }
        .bkx .bk-kv span { color: var(--muted); }
        .bkx .bk-kv b { color: var(--ink); font-weight: 600; text-align: end; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .bkx .bk-kv b.none { color: var(--faint); font-weight: 500; }
        .bkx .bk-money .big { font-size: 24px; font-weight: 700; color: var(--ink); letter-spacing: -.01em; line-height: 1.15; margin-top: 2px; word-break: break-all; }
        .bkx .bk-money .big small { font-size: 11px; font-weight: 600; color: var(--muted); margin-inline-start: 3px; }
        .bkx .bk-bar { height: 6px; border-radius: 6px; background: color-mix(in srgb, var(--acc) 50%, transparent); overflow: hidden; margin: 10px 0 5px; }
        .bkx .bk-bar i { display: block; height: 100%; background: var(--bs-success); }
        .bkx .bk-legend { display: flex; gap: 12px; font-size: 10.5px; color: var(--muted); margin-bottom: 6px; }
        .bkx .bk-legend i { display: inline-block; width: 7px; height: 7px; border-radius: 2px; margin-inline-end: 4px; }
        .bkx .bk-actions { padding: 12px; display: grid; gap: 7px; }
        .bkx .bk-actions .row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 7px; }
        .bkx .bk-actions .bk-btn { width: 100%; }
        .bkx .bk-actions .note { font-size: 10.5px; color: var(--faint); text-align: center; }

        /* ---- errors ---- */
        .bkx .bk-errors { display: flex; gap: 10px; padding: 10px 14px; margin-bottom: 12px; border-radius: 12px; color: var(--bs-danger); background: color-mix(in srgb, var(--bs-danger) 8%, var(--surface)); border: 1px solid color-mix(in srgb, var(--bs-danger) 30%, transparent); }
        .bkx .bk-errors ul { margin: 0; padding-inline-start: 16px; }

        @media (max-width: 1199.98px) {
            .bkx .bk-grid { grid-template-columns: 1fr; }
            .bkx .bk-rail { position: static; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .bkx .bk-rail .bk-actions { grid-column: 1 / -1; }
        }
        @media (max-width: 767.98px) {
            .bkx .bk-g, .bkx .bk-g.c3 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .bkx .span3 { grid-column: 1 / -1; }
            .bkx .bk-hero .acts { display: none; }
            .bkx .bk-sec > header .meta { display: none; }
        }
        @media (max-width: 575.98px) {
            .bkx .bk-g, .bkx .bk-g.c3, .bkx .bk-rail { grid-template-columns: 1fr; }
            .bkx .span2 { grid-column: auto; }
        }
    </style>
@endonce

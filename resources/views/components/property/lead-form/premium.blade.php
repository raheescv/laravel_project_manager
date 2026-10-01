{{--
    Lead Form — sibling of the lead board's "Focus deck" design system, scoped
    to .lfx so it shares tokens (surface, line, ink, tones) without leaking.

    Accent follows the settings theme (--bs-primary); dark mode keys off
    [data-bs-theme="dark"] like the board. Every field, native or TomSelect,
    sits under the same small caps label so the grid reads as one surface.
--}}
@once
    <style>
        .lfx {
            --acc: var(--bs-primary);
            --acc-soft: color-mix(in srgb, var(--bs-primary) 12%, transparent);
            --surface: #fff; --surface-2: #f4f6fa; --surface-3: #e9edf3;
            --ink: var(--bs-emphasis-color); --ink-2: var(--bs-body-color);
            --muted: var(--bs-secondary-color); --faint: var(--bs-tertiary-color);
            --line: #e3e7ee; --line-soft: #edf0f5;
            --r: 12px; --rc: 8px;
            --shadow: 0 1px 2px rgba(16, 24, 40, .05), 0 10px 30px -12px rgba(16, 24, 40, .14);
            --lift: 0 14px 30px -12px rgba(16, 24, 40, .28), 0 4px 10px -4px rgba(16, 24, 40, .12);
            font-size: 12.5px; line-height: 1.4; color: var(--ink-2);
        }
        [data-bs-theme="dark"] .lfx {
            --surface: #262c33; --surface-2: #1f252b; --surface-3: #323a43;
            --line: #373f49; --line-soft: #30373f;
            --shadow: 0 1px 2px rgba(0, 0, 0, .4), 0 12px 30px -12px rgba(0, 0, 0, .55);
            --lift: 0 18px 36px -14px rgba(0, 0, 0, .75);
        }
        .lfx *, .lfx *::before, .lfx *::after { box-sizing: border-box; }
        .lfx .tn { --tn-soft: color-mix(in srgb, var(--tn) 13%, transparent); }
        .lfx .tn-primary { --tn: var(--bs-primary); }
        .lfx .tn-info { --tn: var(--bs-info); }
        .lfx .tn-success { --tn: var(--bs-success); }
        .lfx .tn-warning { --tn: var(--bs-warning); }
        .lfx .tn-danger { --tn: var(--bs-danger); }
        .lfx .tn-secondary { --tn: var(--bs-secondary); }

        .lfx .lfx-card { background: var(--surface); border: 1px solid var(--line); border-radius: var(--r); box-shadow: var(--shadow); margin-bottom: 10px; }

        /* ---- identity header ---- */
        .lfx .hero { display: flex; gap: 10px 12px; align-items: center; padding: 10px 14px; border-bottom: 1px solid var(--line-soft); flex-wrap: wrap; }
        .lfx .avatar { width: 38px; height: 38px; border-radius: 11px; display: grid; place-items: center; font-weight: 700; font-size: 14px; letter-spacing: .02em; color: #fff; flex: none;
            background: linear-gradient(135deg, hsl(var(--hue, 220) 70% 58%), hsl(calc(var(--hue, 220) + 40) 70% 46%)); box-shadow: 0 6px 16px -6px hsl(var(--hue, 220) 70% 40% / .55); }
        .lfx .hero-main { flex: 1 1 200px; min-width: 0; }
        .lfx .hero-name { width: 100%; border: 0; border-bottom: 1px dashed transparent; background: transparent; color: var(--ink); font-size: 16px; font-weight: 700; padding: 0; letter-spacing: -.01em; }
        .lfx .hero-name:hover { border-bottom-color: var(--line); }
        .lfx .hero-name:focus { outline: none; border-bottom-color: var(--acc); }
        .lfx .hero-name.is-invalid { border-bottom-color: var(--bs-danger); }
        .lfx .hero-meta { display: flex; flex-wrap: wrap; gap: 4px 12px; color: var(--faint); font-size: 11px; margin-top: 1px; }
        .lfx .hero-meta i { margin-inline-end: 4px; }
        .lfx .pill { display: inline-flex; align-items: center; gap: 6px; height: 24px; padding: 0 10px; border-radius: 999px; font-size: 11.5px; font-weight: 600; background: var(--tn-soft); color: var(--tn); white-space: nowrap; }
        .lfx .pill .dot { width: 7px; height: 7px; border-radius: 50%; background: var(--tn); box-shadow: 0 0 0 3px var(--tn-soft); }

        /* ---- segmented control (lead type, rental type, location) ---- */
        .lfx .seg { display: inline-flex; flex-wrap: wrap; background: var(--surface-2); border: 1px solid var(--line); border-radius: var(--rc); padding: 2px; gap: 2px; }
        .lfx .seg input { position: absolute; opacity: 0; pointer-events: none; }
        .lfx .seg label { margin: 0; display: inline-flex; align-items: center; gap: 6px; height: 26px; padding: 0 10px; border-radius: 6px; color: var(--muted); font-size: 12px; font-weight: 500; cursor: pointer; white-space: nowrap; transition: background .12s, color .12s; }
        .lfx .seg label:hover { color: var(--ink); }
        .lfx .seg input:checked + label { background: var(--surface); color: var(--ink); font-weight: 600; box-shadow: 0 1px 3px rgba(16, 24, 40, .14); }
        .lfx .seg input:checked + label i { color: var(--acc); }
        .lfx .seg input:focus-visible + label { outline: 2px solid var(--acc); outline-offset: 1px; }
        [data-bs-theme="dark"] .lfx .seg input:checked + label { background: var(--surface-3); }
        .lfx .seg.block { display: flex; }
        .lfx .seg.block label { flex: 1 1 0; justify-content: center; }

        /* ---- sections ---- */
        .lfx .sec { padding: 10px 14px 2px; }
        .lfx .sec + .sec { border-top: 1px solid var(--line-soft); }
        .lfx .sec-h { display: flex; align-items: baseline; gap: 8px; margin-bottom: 6px; }
        .lfx .sec-ic { width: 20px; height: 20px; border-radius: 6px; display: grid; place-items: center; background: var(--acc-soft); color: var(--acc); font-size: 10.5px; flex: none; align-self: center; }
        .lfx .sec-t { margin: 0; font-size: 12.5px; font-weight: 700; color: var(--ink); }
        .lfx .sec-s { margin: 0; font-size: 11px; color: var(--faint); }
        .lfx .sec-h > div { display: flex; align-items: baseline; gap: 8px; flex-wrap: wrap; }

        /* ---- fields ---- */
        .lfx .fld { margin-bottom: 8px; }
        .lfx .fld > label, .lfx .fld-l { display: block; margin-bottom: 3px; font-size: 10px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--muted); }
        .lfx .fld .req { color: var(--bs-danger); margin-inline-start: 2px; }
        .lfx .ctl, .lfx .fld .ts-wrapper .ts-control { width: 100%; min-height: 32px; border: 1px solid var(--line); background: var(--surface-2); border-radius: var(--rc); padding: 5px 10px; color: var(--ink); font-size: 12.5px; box-shadow: none; transition: border-color .12s, background .12s, box-shadow .12s; }
        .lfx select.ctl { appearance: none; padding-inline-end: 32px; cursor: pointer;
            background: var(--surface-2) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%238b95a5' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E") no-repeat right 12px center; }
        .lfx .ctl:hover, .lfx .fld .ts-wrapper .ts-control:hover { border-color: color-mix(in srgb, var(--ink) 18%, var(--line)); }
        .lfx .ctl:focus, .lfx .fld .ts-wrapper.focus .ts-control { outline: none; background: var(--surface); border-color: var(--acc); box-shadow: 0 0 0 3px var(--acc-soft); }
        .lfx .ctl::placeholder { color: var(--faint); }
        .lfx .ctl.is-invalid { border-color: var(--bs-danger); background-image: none; }
        .lfx .ctl:disabled { opacity: .55; cursor: not-allowed; }
        .lfx .err { margin-top: 4px; font-size: 11.5px; color: var(--bs-danger); }
        .lfx .hint { margin-top: 4px; font-size: 11.5px; color: var(--faint); }
        .lfx .fld .ts-wrapper { min-height: 32px; }
        .lfx .fld .ts-wrapper .ts-control { padding-block: 3px; }
        .lfx textarea.ctl { resize: vertical; }
        .lfx .fld .ts-wrapper .ts-control { display: flex; align-items: center; }
        .lfx .fld .ts-dropdown { margin-top: 4px; border: 1px solid var(--line); border-radius: var(--rc); background: var(--surface); box-shadow: var(--lift); font-size: 13px; overflow: hidden; }
        .lfx .fld .ts-dropdown .active { background: var(--surface-2); color: var(--ink); }

        /* input with a leading adornment */
        .lfx .adorn { position: relative; }
        .lfx .adorn > i, .lfx .adorn > span { position: absolute; inset-inline-start: 10px; top: 50%; transform: translateY(-50%); color: var(--faint); font-size: 11px; pointer-events: none; }
        .lfx .adorn .ctl { padding-inline-start: 28px; }

        /* budget range */
        .lfx .budget { display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: 6px; }
        .lfx .budget .dash { color: var(--faint); }
        .lfx .budget-read { margin-top: 6px; font-size: 12px; color: var(--muted); }
        .lfx .budget-read b { color: var(--ink); }

        /* ---- footer ---- */
        .lfx .foot { position: sticky; bottom: 0; z-index: 5; display: flex; flex-wrap: wrap; gap: 8px; align-items: center; justify-content: space-between; padding: 8px 14px; border-top: 1px solid var(--line); background: color-mix(in srgb, var(--surface) 92%, transparent); backdrop-filter: blur(6px); border-radius: 0 0 var(--r) var(--r); }
        .lfx .btn-x { display: inline-flex; align-items: center; gap: 6px; height: 32px; padding: 0 13px; border-radius: var(--rc); font-size: 12.5px; font-weight: 600; border: 1px solid var(--line); background: var(--surface); color: var(--ink-2); text-decoration: none; cursor: pointer; }
        .lfx .btn-x:hover { background: var(--surface-2); color: var(--ink); }
        .lfx .btn-x.pri { background: var(--acc); border-color: var(--acc); color: #fff; box-shadow: 0 6px 14px -6px color-mix(in srgb, var(--acc) 70%, transparent); }
        .lfx .btn-x.pri:hover { filter: brightness(1.06); }
        .lfx .btn-x.ok { background: var(--bs-success); border-color: var(--bs-success); color: #fff; }
        .lfx .btn-x[disabled] { opacity: .6; cursor: progress; }

        /* ---- side rail ---- */
        .lfx .rail-h { display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; border-bottom: 1px solid var(--line-soft); }
        .lfx .rail-h h6 { margin: 0; font-size: 12.5px; font-weight: 700; color: var(--ink); }
        .lfx .rail-b { padding: 10px 12px; }
        .lfx .snap { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; }
        .lfx .snap-i { background: var(--surface-2); border: 1px solid var(--line-soft); border-radius: var(--rc); padding: 6px 9px; min-width: 0; }
        .lfx .snap-i.wide { grid-column: 1 / -1; }
        .lfx .snap-k { font-size: 10px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--faint); }
        .lfx .snap-v { margin-top: 2px; font-weight: 600; color: var(--ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .lfx .snap-v.empty { color: var(--faint); font-weight: 500; }
        .lfx .count { min-width: 22px; height: 22px; padding: 0 7px; border-radius: 999px; display: inline-grid; place-items: center; background: var(--acc-soft); color: var(--acc); font-size: 11px; font-weight: 700; }

        .lfx .note-in { display: grid; grid-template-columns: 130px 1fr; gap: 6px; }
        .lfx .note-in textarea, .lfx .note-in button { grid-column: 1 / -1; }
        .lfx .timeline { list-style: none; margin: 10px 0 0; max-height: 420px; overflow-y: auto; padding: 0; position: relative; }
        .lfx .timeline::before { content: ""; position: absolute; inset-inline-start: 11px; top: 4px; bottom: 4px; width: 2px; background: var(--line-soft); }
        .lfx .timeline li { position: relative; display: flex; gap: 8px; padding: 0 0 8px; }
        .lfx .timeline .tdot { position: relative; z-index: 1; flex: none; width: 24px; height: 24px; border-radius: 50%; display: grid; place-items: center; background: var(--surface); border: 2px solid var(--acc-soft); color: var(--acc); font-size: 11px; }
        .lfx .timeline .tbody { flex: 1; min-width: 0; background: var(--surface-2); border: 1px solid var(--line-soft); border-radius: var(--rc); padding: 6px 9px; }
        .lfx .timeline .tnote { color: var(--ink); white-space: pre-line; word-break: break-word; }
        .lfx .timeline .tmeta { margin-top: 4px; font-size: 11px; color: var(--faint); display: flex; flex-wrap: wrap; gap: 2px 10px; align-items: center; }
        .lfx .timeline .tdel { margin-inline-start: auto; border: 0; background: transparent; color: var(--faint); padding: 0; cursor: pointer; }
        .lfx .timeline .tdel:hover { color: var(--bs-danger); }
        .lfx .empty-s { text-align: center; color: var(--faint); padding: 22px 0 8px; font-size: 12px; }
        .lfx .empty-s i { display: block; font-size: 26px; opacity: .35; margin-bottom: 6px; }

        .lfx .alert-x { display: flex; gap: 10px; margin: 8px 14px 0; padding: 7px 12px; border-radius: var(--rc); background: color-mix(in srgb, var(--bs-danger) 10%, transparent); color: var(--bs-danger); font-size: 12.5px; }
        .lfx .alert-x ul { margin: 0; padding-inline-start: 16px; }

        @media (max-width: 575.98px) {
            .lfx .hero, .lfx .sec, .lfx .foot { padding-inline: 10px; }
            .lfx .seg.block label { padding: 0 8px; }
            .lfx .foot .btn-x { flex: 1 1 auto; justify-content: center; }
        }

        /* ---- change history: rows are changes, columns are fields ---- */
        [x-cloak] { display: none !important; }
        .lfx .atx-scroll { overflow-x: auto; }
        .lfx .atx-table { width: max-content; min-width: 100%; border-collapse: separate; border-spacing: 0; font-size: 12px; }
        .lfx .atx-table thead th { position: sticky; top: 0; z-index: 2; background: var(--surface-2); color: var(--muted); font-size: 10px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; white-space: nowrap; padding: 8px 12px; border-bottom: 1px solid var(--line); text-align: start; }
        .lfx .atx-table td, .lfx .atx-table tbody th { padding: 8px 12px; border-bottom: 1px solid var(--line-soft); vertical-align: top; min-width: 130px; max-width: 240px; font-weight: 400; text-align: start; }
        .lfx .atx-table tbody tr:hover td, .lfx .atx-table tbody tr:hover th { background: color-mix(in srgb, var(--acc) 3%, var(--surface)); }
        .lfx .atx-table tbody tr:last-child > * { border-bottom: 0; }
        .lfx .atx-sticky { position: sticky; inset-inline-start: 0; z-index: 1; background: var(--surface); box-shadow: 1px 0 0 var(--line); min-width: 260px !important; max-width: 300px !important; }
        .lfx thead .atx-sticky { z-index: 3; background: var(--surface-2); }
        .lfx .atx-who { display: flex; align-items: flex-start; gap: 8px; }
        .lfx .atx-av { width: 26px; height: 26px; border-radius: 8px; flex: none; display: grid; place-items: center; font-size: 10.5px; font-weight: 700; color: #fff; background: hsl(var(--hue, 220) 55% 52%); }
        .lfx .atx-name { font-weight: 600; color: var(--ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .lfx .atx-when { font-size: 11px; color: var(--faint); white-space: nowrap; }
        .lfx .atx-chip { margin-inline-start: auto; display: inline-flex; align-items: center; gap: 4px; height: 20px; padding: 0 7px; border-radius: 999px; font-size: 10.5px; font-weight: 600; background: var(--tn-soft); color: var(--tn); white-space: nowrap; flex: none; }
        .lfx td.is-changed { background: color-mix(in srgb, var(--bs-success) 4%, transparent); }
        .lfx .atx-none { color: var(--line); font-weight: 700; }
        .lfx .atx-old { color: var(--faint); text-decoration: line-through; text-decoration-color: color-mix(in srgb, var(--bs-danger) 60%, transparent); font-size: 11px; word-break: break-word; }
        .lfx .atx-new { color: var(--ink); font-weight: 600; word-break: break-word; display: flex; align-items: center; gap: 6px; }
        .lfx .atx-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--tn); flex: none; }
        .lfx .atx-note { display: flex; gap: 6px; align-items: baseline; color: var(--ink); white-space: normal; word-break: break-word; }
        .lfx .atx-note i { font-size: 9px; color: var(--bs-success); }
        .lfx .atx-note.is-removed { color: var(--faint); text-decoration: line-through; }
        .lfx .atx-note.is-removed i { color: var(--bs-danger); }
        .lfx .atx-more { display: flex; justify-content: center; padding: 8px; border-top: 1px solid var(--line-soft); }
        .lfx .atx .empty-s { padding: 22px 12px; }
    </style>
@endonce

{{--
    Tenant Control list — "Fleet Console" premium design system.

    Scoped under .tnx so it cannot leak into the rest of the app. Colour derives
    from the settings theme (--bs-primary + Bootstrap emphasis tokens) with a dark
    ramp under [data-bs-theme="dark"]. Logical properties keep it RTL-safe.
    Breakpoints are container queries on .tnx, so the layout follows the content
    width (sidebar open or collapsed), not the viewport.

    Preview: docs/tenant-control-premium-preview.html (direction A)
--}}
@once
    {{-- Pushed to the styles stack: an inline <style> would become a second Livewire root. --}}
    @push('styles')
        <style>
            .tnx {
                --acc: var(--bs-primary);
                --acc-rgb: var(--bs-primary-rgb);
                --acc-deep: color-mix(in srgb, var(--bs-primary), #000 45%);
                --acc-tint: color-mix(in srgb, var(--bs-primary), transparent 90%);
                --surface: #ffffff;
                --surface-2: #f6f8fb;
                --surface-3: #eef1f6;
                --ink: var(--bs-emphasis-color);
                --ink-2: var(--bs-body-color);
                --muted: var(--bs-secondary-color);
                --faint: var(--bs-tertiary-color);
                --line: #e6eaf0;
                --line-soft: #f0f2f6;
                --ok: var(--bs-success);
                --warn: var(--bs-warning);
                --bad: var(--bs-danger);
                --r: 14px;
                --r-sm: 9px;
                --shadow: 0 1px 2px rgba(16, 24, 40, .05), 0 10px 28px -14px rgba(16, 24, 40, .18);
                --tnx-fz: 12.5px;
                container-type: inline-size;
                color: var(--ink-2);
                font-size: var(--tnx-fz);
                line-height: 1.5;
            }

            [data-bs-theme="dark"] .tnx {
                --surface: #232a33;
                --surface-2: #1f252d;
                --surface-3: #2a323c;
                --line: #333c48;
                --line-soft: #2b333e;
                --acc-tint: color-mix(in srgb, var(--bs-primary), transparent 84%);
                --shadow: 0 1px 2px rgba(0, 0, 0, .3), 0 12px 30px -14px rgba(0, 0, 0, .55);
            }

            .tnx a { color: inherit; text-decoration: none; }
            .tnx .tnx-shell { background: var(--surface); border: 1px solid var(--line); border-radius: var(--r); box-shadow: var(--shadow); overflow: hidden; }

            /* ── Atoms ───────────────────────────────────────────── */
            .tnx .tnx-btn { display: inline-flex; align-items: center; gap: 7px; border: 1px solid transparent; border-radius: var(--r-sm); padding: 8px 14px; font-weight: 600; font-size: 12.5px; cursor: pointer; white-space: nowrap; line-height: 1.4; }
            .tnx .tnx-btn-acc { background: var(--acc); color: #fff; box-shadow: 0 6px 16px -8px rgba(var(--acc-rgb), .8); }
            .tnx .tnx-btn-acc:hover { background: color-mix(in srgb, var(--acc), #000 10%); }
            .tnx .tnx-btn-danger { background: color-mix(in srgb, var(--bad), transparent 88%); color: var(--bad); }
            .tnx .tnx-btn-danger:not([disabled]):hover { background: var(--bad); color: #fff; }
            .tnx .tnx-btn-ghost { background: var(--surface); border-color: var(--line); color: var(--ink-2); }
            .tnx .tnx-btn[disabled] { opacity: .45; cursor: not-allowed; }
            .tnx .tnx-search { display: flex; align-items: center; gap: 8px; background: var(--surface-2); border: 1px solid var(--line); border-radius: var(--r-sm); padding: 0 12px; min-width: 0; flex: 0 1 360px; margin: 0; }
            .tnx .tnx-search:focus-within { border-color: var(--acc); box-shadow: 0 0 0 3px var(--acc-tint); }
            .tnx .tnx-search input { border: 0; background: none; outline: none; font: inherit; color: var(--ink); padding: 8px 0; width: 100%; min-width: 0; }
            .tnx .tnx-sel { appearance: none; background: var(--surface) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%23889' fill='none' stroke-width='1.6'/%3E%3C/svg%3E") no-repeat right 10px center; border: 1px solid var(--line); border-radius: var(--r-sm); padding: 7px 28px 7px 11px; font: inherit; color: var(--ink); min-width: 0; }
            [dir="rtl"] .tnx .tnx-sel { background-position: left 10px center; padding: 7px 11px 7px 28px; }
            .tnx .tnx-cb { width: 16px; height: 16px; margin: 0; accent-color: var(--acc); cursor: pointer; }
            .tnx .tnx-avatar { width: 38px; height: 38px; flex: 0 0 38px; border-radius: 11px; display: grid; place-items: center; font-weight: 700; font-size: 15px; color: #fff; background: linear-gradient(135deg, var(--acc), var(--acc-deep)); }
            .tnx .tnx-avatar.is-muted { background: linear-gradient(135deg, #9aa3b2, #5e6776); }
            .tnx .tnx-name { font-weight: 600; color: var(--ink); font-size: 13.5px; }
            .tnx a.tnx-name:hover { color: var(--acc); }
            .tnx .tnx-host { color: var(--muted); font-size: 11.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; }
            .tnx a.tnx-host:hover { color: var(--acc); }
            .tnx .tnx-code { font: 500 11px ui-monospace, Menlo, monospace; color: var(--muted); background: var(--surface-3); padding: 1px 6px; border-radius: 5px; }
            .tnx .tnx-here { display: inline-flex; align-items: center; gap: 4px; font-size: 10.5px; font-weight: 600; color: var(--acc); background: var(--acc-tint); padding: 1px 8px; border-radius: 20px; margin-top: 2px; }
            [data-bs-theme="dark"] .tnx .tnx-here { color: color-mix(in srgb, var(--acc), #fff 30%); }
            .tnx .tnx-chip { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 600; padding: 2px 9px; border-radius: 20px; background: var(--surface-3); color: var(--ink-2); white-space: nowrap; }
            .tnx .tnx-pill { display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 20px; white-space: nowrap; }
            .tnx .tnx-pill::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
            .tnx .tnx-pill.is-ok { color: var(--ok); background: color-mix(in srgb, var(--ok), transparent 88%); }
            .tnx .tnx-pill.is-off { color: var(--muted); background: var(--surface-3); }
            .tnx .tnx-pill.is-del { color: var(--bad); background: color-mix(in srgb, var(--bad), transparent 88%); }
            .tnx .tnx-when { color: var(--ink); font-weight: 500; white-space: nowrap; }
            .tnx .tnx-sub { color: var(--faint); font-size: 11px; white-space: nowrap; }
            .tnx .tnx-renew { font-size: 11px; font-weight: 600; white-space: nowrap; }
            .tnx .tnx-renew.is-ok { color: var(--muted); }
            .tnx .tnx-renew.is-due { color: var(--warn); }
            .tnx .tnx-renew.is-overdue { color: var(--bad); }
            .tnx .tnx-bar { height: 4px; border-radius: 4px; background: var(--surface-3); overflow: hidden; margin: 5px 0 3px; width: 110px; max-width: 100%; }
            .tnx .tnx-bar i { display: block; height: 100%; border-radius: 4px; background: var(--acc); }
            .tnx .tnx-bar.is-due i { background: var(--warn); }
            .tnx .tnx-bar.is-overdue i { background: var(--bad); }
            .tnx .tnx-num { font-weight: 600; font-size: 14px; color: var(--ink); font-variant-numeric: tabular-nums; }
            .tnx .tnx-num-l, .tnx .tnx-lbl { font-size: 10.5px; color: var(--faint); text-transform: uppercase; letter-spacing: .5px; }
            .tnx .tnx-lbl { display: none; }
            .tnx .tnx-dim { color: var(--faint); }

            /* ── KPI strip ───────────────────────────────────────── */
            .tnx .tnx-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-bottom: 14px; }
            .tnx .tnx-kpi { background: var(--surface); border: 1px solid var(--line); border-radius: var(--r); padding: 14px 16px; box-shadow: var(--shadow); display: flex; gap: 12px; align-items: center; min-width: 0; text-align: start; width: 100%; }
            button.tnx-kpi { cursor: pointer; }
            .tnx button.tnx-kpi:hover { border-color: var(--acc); }
            .tnx .tnx-kpi .tnx-ic { width: 40px; height: 40px; flex: 0 0 40px; border-radius: 12px; display: grid; place-items: center; background: var(--acc-tint); color: var(--acc); font-size: 16px; }
            .tnx .tnx-kpi .tnx-ic.is-ok { background: color-mix(in srgb, var(--ok), transparent 88%); color: var(--ok); }
            .tnx .tnx-kpi .tnx-ic.is-warn { background: color-mix(in srgb, var(--warn), transparent 86%); color: var(--warn); }
            [data-bs-theme="dark"] .tnx .tnx-kpi .tnx-ic { color: color-mix(in srgb, var(--acc), #fff 30%); }
            [data-bs-theme="dark"] .tnx .tnx-kpi .tnx-ic.is-ok { color: var(--ok); }
            [data-bs-theme="dark"] .tnx .tnx-kpi .tnx-ic.is-warn { color: var(--warn); }
            .tnx .tnx-kpi .tnx-v { font-weight: 700; font-size: 22px; color: var(--ink); line-height: 1.1; }
            .tnx .tnx-kpi .tnx-l { font-size: 11.5px; color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

            /* ── Toolbar + filters ───────────────────────────────── */
            .tnx .tnx-toolbar { display: flex; gap: 10px; align-items: center; padding: 14px 18px; flex-wrap: wrap; border-bottom: 1px solid var(--line-soft); }
            .tnx .tnx-grow { flex: 1 1 auto; }
            .tnx .tnx-narrow-only { display: none; }
            .tnx .tnx-filters { display: flex; gap: 10px; align-items: center; padding: 10px 18px; background: var(--surface-2); border-bottom: 1px solid var(--line-soft); flex-wrap: wrap; }
            .tnx .tnx-segs { display: flex; gap: 6px; overflow-x: auto; scrollbar-width: none; padding-bottom: 2px; flex: 1 1 320px; min-width: 0; }
            .tnx .tnx-segs::-webkit-scrollbar { display: none; }
            .tnx .tnx-seg { display: inline-flex; align-items: center; gap: 7px; padding: 6px 12px; border-radius: 30px; border: 1px solid var(--line); background: var(--surface); color: var(--ink-2); font-weight: 500; white-space: nowrap; cursor: pointer; font-size: 12.5px; }
            .tnx .tnx-seg:hover { border-color: var(--acc); }
            .tnx .tnx-seg b { font-weight: 700; font-size: 11px; background: var(--surface-3); color: var(--muted); border-radius: 20px; padding: 1px 7px; }
            .tnx .tnx-seg.is-warn b { background: color-mix(in srgb, var(--warn), transparent 82%); color: var(--warn); }
            .tnx .tnx-seg.is-on { background: var(--acc); border-color: var(--acc); color: #fff; }
            .tnx .tnx-seg.is-on b { background: rgba(255, 255, 255, .22); color: #fff; }
            .tnx .tnx-sels { display: flex; gap: 8px; flex-wrap: wrap; }

            /* ── Table ───────────────────────────────────────────── */
            .tnx .tnx-table { width: 100%; border-collapse: collapse; margin: 0; }
            .tnx .tnx-table th { text-align: start; font-size: 10.5px; letter-spacing: .6px; text-transform: uppercase; color: var(--faint); font-weight: 600; padding: 10px 14px; background: var(--surface); border-bottom: 1px solid var(--line-soft); white-space: nowrap; }
            .tnx .tnx-table td { padding: 12px 14px; border-bottom: 1px solid var(--line-soft); vertical-align: middle; }
            .tnx .tnx-row:hover td { background: var(--surface-2); }
            .tnx .tnx-row.is-current td { background: color-mix(in srgb, var(--acc), transparent 95%); }
            .tnx .tnx-row.is-current td:first-child { box-shadow: inset 3px 0 0 var(--acc); }
            [dir="rtl"] .tnx .tnx-row.is-current td:first-child { box-shadow: inset -3px 0 0 var(--acc); }
            .tnx .tnx-row.is-deleted { opacity: .65; }
            .tnx .tnx-who { display: flex; gap: 11px; align-items: center; min-width: 0; }
            .tnx .tnx-meta { min-width: 0; }
            .tnx .tnx-top { display: flex; gap: 7px; align-items: center; flex-wrap: wrap; }
            .tnx .tnx-size { display: flex; gap: 16px; }
            .tnx .tnx-size > div { text-align: center; min-width: 42px; }
            .tnx .tnx-act { display: flex; flex-direction: column; gap: 2px; font-size: 11.5px; white-space: nowrap; color: var(--muted); }
            .tnx .tnx-act i { width: 14px; color: var(--faint); }
            .tnx .tnx-empty { text-align: center; color: var(--muted); padding: 40px 16px; }
            .tnx .tnx-empty > i { font-size: 28px; color: var(--faint); display: block; margin-bottom: 8px; }
            .tnx .tnx-pager { display: flex; justify-content: space-between; align-items: center; gap: 10px 16px; padding: 12px 18px; border-top: 1px solid var(--line-soft); color: var(--muted); font-size: 12px; flex-wrap: wrap; }
            .tnx .tnx-pager nav { margin-inline-start: auto; }
            .tnx .tnx-pager .pagination { margin: 0; flex-wrap: wrap; }

            /* ── Responsive (container width) ────────────────────── */
            @container (max-width: 1180px) {
                .tnx .tnx-table th, .tnx .tnx-table td { padding-inline: 10px; }
                .tnx .tnx-size { gap: 10px; }
            }

            @container (max-width: 900px) {
                .tnx .tnx-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
                .tnx .tnx-narrow-only { display: inline-flex; }
                .tnx .tnx-table, .tnx .tnx-table tbody, .tnx .tnx-table tr, .tnx .tnx-table td { display: block; width: 100%; }
                .tnx .tnx-table thead { display: none; }
                .tnx .tnx-row { display: grid !important; grid-template-columns: auto minmax(0, 1fr) minmax(0, 1fr) auto; gap: 10px 12px; padding: 14px 16px; border-bottom: 1px solid var(--line-soft); }
                .tnx .tnx-row td { padding: 0; border: 0; background: none !important; box-shadow: none !important; }
                .tnx .tnx-row.is-current { background: color-mix(in srgb, var(--acc), transparent 95%); box-shadow: inset 3px 0 0 var(--acc); }
                [dir="rtl"] .tnx .tnx-row.is-current { box-shadow: inset -3px 0 0 var(--acc); }
                .tnx .tnx-row .c-cb { grid-row: 1; grid-column: 1; width: auto; padding-top: 11px; }
                .tnx .tnx-row .c-who { grid-row: 1; grid-column: 2 / 4; }
                .tnx .tnx-row .c-status { grid-row: 1; grid-column: 4; width: auto; }
                .tnx .tnx-row .c-sys { grid-column: 1 / -1; }
                .tnx .tnx-row .c-size, .tnx .tnx-row .c-act { grid-column: 1 / -1; }
                .tnx .tnx-row .c-start { grid-column: 1 / 3; }
                .tnx .tnx-row .c-renew { grid-column: 3 / -1; }
                .tnx .tnx-size { justify-content: space-around; background: var(--surface-2); border-radius: var(--r-sm); padding: 8px 6px; }
                .tnx .tnx-act { flex-direction: row; justify-content: space-between; flex-wrap: wrap; gap: 4px 12px; }
                .tnx .tnx-lbl { display: block; margin-bottom: 2px; }
                .tnx .tnx-bar { width: 100%; }
                .tnx .tnx-empty { display: block; }
            }

            @container (max-width: 560px) {
                .tnx .tnx-kpis { gap: 8px; }
                .tnx .tnx-kpi { padding: 10px 12px; gap: 10px; }
                .tnx .tnx-kpi .tnx-ic { width: 34px; height: 34px; flex-basis: 34px; font-size: 14px; }
                .tnx .tnx-kpi .tnx-v { font-size: 18px; }
                .tnx .tnx-toolbar { padding: 12px 14px; }
                .tnx .tnx-toolbar .tnx-txt { display: none; }
                .tnx .tnx-toolbar .tnx-search { order: 3; flex: 1 1 100%; }
                .tnx .tnx-filters { padding: 10px 14px; }
                .tnx .tnx-sels { width: 100%; }
                .tnx .tnx-sels .tnx-sel { flex: 1 1 0; }
                .tnx .tnx-row { padding: 14px; }
                .tnx .tnx-pager { justify-content: center; }
                .tnx .tnx-pager nav { margin-inline-start: 0; }
            }
        </style>
    @endpush
@endonce

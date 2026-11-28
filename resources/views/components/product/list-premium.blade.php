{{--
    Product list — "Compact Ledger" premium design system (.pdx).
    Scoped under .pdx; every colour derives from the settings theme (--bs-primary and
    the Bootstrap body / border tokens) so it follows the tenant colour and dark mode.
    Rows turn into stacked cards when the list itself is narrow (container query on
    .pdx-shell, which is kept off the fixed bulk bar / FAB on purpose: containment
    would trap their position: fixed).
    Preview: docs/product-list-premium-preview.html (direction A).
--}}
@once
    @push('styles')
        <style>
            .pdx {
                --acc: var(--bs-primary);
                --acc-rgb: var(--bs-primary-rgb);
                --acc-soft: color-mix(in srgb, var(--acc) 9%, transparent);
                --acc-ring: color-mix(in srgb, var(--acc) 24%, transparent);
                /* Ink on accent-tinted surfaces; Bootstrap lightens it in dark mode. */
                --acc-ink: var(--bs-primary-text-emphasis);
                --ink: var(--bs-body-color);
                --ink-2: var(--bs-secondary-color);
                --line: var(--bs-border-color-translucent);
                --panel: var(--bs-body-bg);
                --panel-2: var(--bs-tertiary-bg);
                /* Input fields stay white on the grey page; dark mode uses the panel. */
                --field: #fff;
                --mono: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
                --shadow: 0 1px 2px rgba(16, 24, 40, .05), 0 8px 24px -18px rgba(16, 24, 40, .35);
                color: var(--ink);
            }

            [data-bs-theme="dark"] .pdx {
                --field: var(--panel);
                --shadow: 0 1px 2px rgba(0, 0, 0, .4), 0 10px 26px -18px rgba(0, 0, 0, .9);
            }

            [data-bs-theme="dark"] .pdx .sw { box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .35); }

            .pdx [x-cloak] { display: none !important; }
            .pdx .pdx-shell { background: var(--panel); border: 1px solid var(--line); border-radius: 14px; box-shadow: var(--shadow); container-type: inline-size; container-name: pdx; }
            .pdx .eyebrow { font-size: .66rem; letter-spacing: .08em; text-transform: uppercase; font-weight: 600; color: var(--ink-2); }
            .pdx .count-pill { font-size: .72rem; font-weight: 600; background: var(--acc-soft); color: var(--acc-ink); border-radius: 99px; padding: 2px 10px; }
            .pdx .sku { font-family: var(--mono); font-size: .74rem; color: var(--ink); background: var(--panel-2); border: 1px solid var(--line); border-radius: 6px; padding: 1px 6px; white-space: nowrap; }
            .pdx .bc { font-family: var(--mono); font-size: .7rem; color: var(--ink-2); white-space: nowrap; }
            .pdx .mono { font-family: var(--mono); }
            .pdx .sw { display: inline-block; width: 11px; height: 11px; border-radius: 50%; box-shadow: inset 0 0 0 1px rgba(0, 0, 0, .18); vertical-align: -1px; flex: none; }
            .pdx .chip-size { font-size: .66rem; font-weight: 600; line-height: 1; padding: 3px 6px; border-radius: 5px; border: 1px solid var(--line); background: var(--panel-2); color: var(--ink); }
            .pdx .thumb { width: 36px; height: 36px; border-radius: 9px; object-fit: cover; display: grid; place-items: center; background: var(--panel-2); color: var(--ink-2); border: 1px solid var(--line); }

            .pdx .tool-input { border-color: var(--line); background: var(--field); box-shadow: 0 1px 2px rgba(16, 24, 40, .06); }
            .pdx .tool-input:focus { border-color: var(--acc); box-shadow: 0 0 0 .2rem var(--acc-ring); }
            .pdx .input-group-text { background: var(--field); border-color: var(--line); color: var(--ink-2); }
            .pdx .btn-ghost { border: 1px solid var(--line); background: var(--panel); color: var(--ink); }
            .pdx .btn-ghost:hover, .pdx .btn-ghost:focus-visible { border-color: var(--acc); color: var(--acc-ink); }
            .pdx .btn-ghost.on { background: var(--acc-soft); border-color: var(--acc-ring); color: var(--acc-ink); }
            .pdx .seg { display: inline-flex; padding: 3px; border-radius: 10px; background: var(--panel-2); border: 1px solid var(--line); }
            .pdx .seg button { border: 0; background: transparent; padding: 3px 10px; border-radius: 7px; font-size: .76rem; font-weight: 500; color: var(--ink-2); white-space: nowrap; }
            .pdx .seg button.on { background: var(--panel); color: var(--ink); box-shadow: 0 1px 2px rgba(0, 0, 0, .12); }

            .pdx .fchip { display: inline-flex; align-items: center; gap: 6px; font-size: .74rem; padding: 3px 4px 3px 10px; border-radius: 99px; background: var(--acc-soft); color: var(--acc-ink); border: 1px solid var(--acc-ring); }
            .pdx .fchip b { font-weight: 600; }
            .pdx .fchip button { border: 0; background: transparent; color: inherit; width: 18px; height: 18px; border-radius: 50%; line-height: 1; padding: 0; }
            .pdx .fchip button:hover { background: var(--acc-ring); }
            .pdx .filter-panel { background: var(--panel-2); border-top: 1px solid var(--line); border-bottom: 1px solid var(--line); }
            .pdx .filter-panel .form-label { font-size: .68rem; text-transform: uppercase; letter-spacing: .06em; font-weight: 600; color: var(--ink-2); margin-bottom: 4px; }
            .pdx .filter-panel .ts-control { background: var(--field); }
            .pdx .filter-panel .form-select, .pdx .filter-panel .form-control { font-size: .8rem; border-color: var(--line); background-color: var(--field); }

            .pdx .ptable { width: 100%; border-collapse: separate; border-spacing: 0; margin: 0; transition: opacity .15s ease; }
            .pdx .ptable.is-loading { opacity: .5; pointer-events: none; }
            .pdx .pdx-head { border-bottom: 1px solid var(--line); border-radius: 14px 14px 0 0; }
            .pdx .pdx-head .filter-panel { background: transparent; border-bottom: 0; }
            .pdx .col-menu { min-width: 210px; max-height: 70vh; overflow-y: auto; }
            .pdx .sku-badge { font-family: var(--mono); font-weight: 600; font-size: .74rem; }
            .pdx .ptable th { background: var(--bs-light); font-size: .82rem; font-weight: 600; color: var(--ink); padding: 10px 12px; border-bottom: 1px solid var(--line); white-space: nowrap; }
            .pdx .ptable th a { color: inherit !important; }
            [data-bs-theme="dark"] .pdx .ptable th { background: var(--panel-2); }
            .pdx .ptable td { padding: 9px 12px; border-bottom: 1px solid var(--line); vertical-align: middle; background: var(--panel); }
            .pdx .ptable.is-compact td { padding-top: 4px; padding-bottom: 4px; }
            .pdx .ptable.is-compact .ar { display: none; }
            .pdx .ptable tr.row-main:hover td { background: color-mix(in srgb, var(--acc) 4%, var(--panel)); }
            .pdx .ptable tr.is-selected td { background: color-mix(in srgb, var(--acc) 9%, var(--panel)); }
            .pdx .ptable .num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
            .pdx .ptable .name { font-weight: 600; color: var(--ink); text-decoration: none; }
            .pdx .ptable .name:hover { color: var(--acc-ink); }
            .pdx .ptable .ar { display: table; font-size: .74rem; color: var(--ink-2); }
            .pdx .crumb { font-size: .76rem; color: var(--ink-2); }
            .pdx .crumb b { color: var(--ink); font-weight: 500; }
            .pdx .margin { font-size: .68rem; }
            .pdx .fam-toggle { border: 0; font-size: .68rem; font-weight: 600; padding: 2px 8px; border-radius: 99px; background: var(--acc); color: #fff; }
            .pdx .fam-row td { background: var(--panel-2) !important; padding: 0; }
            .pdx .fam-inner { border-inline-start: 3px solid var(--acc); margin-inline-start: 12px; padding: 6px 12px 8px; }
            .pdx .fam-inner table { width: 100%; }
            .pdx .fam-inner th, .pdx .fam-inner td { padding: 5px 10px; border: 0; background: transparent !important; font-size: .78rem; }
            .pdx .fam-inner th { font-size: .62rem; letter-spacing: .06em; text-transform: uppercase; color: var(--ink-2); font-weight: 600; }
            .pdx .empty { padding: 56px 16px; text-align: center; color: var(--ink-2); }
            .pdx .empty .fa { font-size: 2rem; opacity: .45; }

            .pdx .pdx-foot { background: var(--panel-2); border-top: 1px solid var(--line); font-size: .78rem; color: var(--ink-2); border-radius: 0 0 14px 14px; }
            .pdx .pdx-foot .pagination { margin: 0; }
            .pdx .pdx-foot .page-link { font-size: .76rem; padding: 3px 9px; }
            .pdx .pdx-foot nav p.small { display: none; }

            .product-bulk-bar { position: fixed; bottom: 1.5rem; left: 50%; transform: translateX(-50%); z-index: 1060; max-width: calc(100vw - 2rem); }

            @media (max-width: 767.98px) {
                .product-bulk-bar { left: 1rem; right: 5.5rem; transform: none; }
            }

            @container pdx (max-width: 1100px) {
                .pdx .hide-lg { display: none !important; }
            }

            @container pdx (max-width: 860px) {
                .pdx .hide-md { display: none !important; }
            }

            /* Narrow list: every row becomes a stacked card. */
            @container pdx (max-width: 640px) {
                .pdx .hide-sm { display: none !important; }
                .pdx .toolbar .search-wrap { flex: 1 1 100% !important; max-width: none !important; }
                .pdx .ptable thead { display: none; }
                .pdx .ptable, .pdx .ptable tbody, .pdx .ptable tr, .pdx .ptable td { display: block; width: 100%; }
                .pdx .ptable tr.row-main { position: relative; padding: 10px 12px 10px 44px; border-bottom: 1px solid var(--line); background: var(--panel); }
                .pdx .ptable tr.row-main.is-selected { background: color-mix(in srgb, var(--acc) 9%, var(--panel)); }
                .pdx .ptable tr.row-main td { border: 0; padding: 0; background: transparent !important; }
                .pdx .ptable td.c-check { position: absolute; left: 14px; top: 13px; width: auto; }
                .pdx .ptable td.c-thumb { display: none; }
                .pdx .ptable td.c-price { position: absolute; right: 12px; top: 10px; width: auto; }
                .pdx .ptable td.c-product { padding-right: 96px !important; }
                .pdx .ptable td.c-codes, .pdx .ptable td.c-cat { margin-top: 4px; }
                .pdx .ptable td.c-design, .pdx .ptable td.c-size, .pdx .ptable td.c-colour { display: inline-block; width: auto; margin: 4px 10px 0 0; }
                .pdx .ptable td.c-cost, .pdx .ptable td.c-unit, .pdx .ptable td.c-brand { display: none; }
                .pdx .fam-row { display: block; }
                .pdx .fam-inner { margin-inline-start: 0; }
            }
        </style>
    @endpush
@endonce

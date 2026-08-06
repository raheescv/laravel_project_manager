{{--
    ".bcx" — the Console design system for the three barcode screens:
    the template list, the template designer, and the print cart.

    Scoped entirely to the .bcx prefix so it cannot leak into the rest of the
    admin UI. Colour derives from the active settings theme (--bs-primary), and
    both light and dark are first class. Include once per page:

        <x-barcode.premium />
--}}
@once
    <style>
        .bcx {
            --bcx-brand: var(--bs-primary, #2563eb);
            --bcx-brand-ink: #fff;

            --bcx-bg: #f6f7f9;
            --bcx-panel: #ffffff;
            --bcx-panel-2: #f9fafb;
            --bcx-line: #e3e7ee;
            --bcx-line-soft: #eef1f6;
            --bcx-ink: #17202f;
            --bcx-ink-2: #3d4859;
            --bcx-muted: #6b7789;
            --bcx-faint: #97a1b0;
            --bcx-shadow: 0 1px 2px rgba(15, 23, 42, .05), 0 14px 30px -14px rgba(15, 23, 42, .22);

            --bcx-fz: 12.5px;
            --bcx-radius: 10px;
            --bcx-mono: ui-monospace, Menlo, Consolas, "DejaVu Sans Mono", "Liberation Mono", monospace;

            font-size: var(--bcx-fz);
            color: var(--bcx-ink);
        }

        [data-bs-theme="dark"] .bcx {
            --bcx-bg: #0b0f17;
            --bcx-panel: #121826;
            --bcx-panel-2: #0e1420;
            --bcx-line: #1f2937;
            --bcx-line-soft: #18202e;
            --bcx-ink: #dbe3ef;
            --bcx-ink-2: #b6c1d2;
            --bcx-muted: #7d8ba1;
            --bcx-faint: #5d6b80;
            --bcx-shadow: 0 1px 2px rgba(0, 0, 0, .5), 0 18px 40px -18px rgba(0, 0, 0, .85);
        }

        /* ---------- shell ---------------------------------------------- */
        .bcx-shell {
            background: var(--bcx-bg);
            border: 1px solid var(--bcx-line);
            border-radius: 12px;
            overflow: hidden;
            max-width: 100%;
            box-shadow: var(--bcx-shadow);
        }

        .bcx-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 46px;
            padding: 6px 12px;
            background: var(--bcx-panel);
            border-bottom: 1px solid var(--bcx-line);
            flex-wrap: wrap;
        }

        .bcx-bar__title {
            font-size: 13px;
            font-weight: 750;
            letter-spacing: -.01em;
            margin: 0;
        }

        .bcx-bar__sub {
            font-size: 11px;
            color: var(--bcx-muted);
        }

        .bcx-spacer {
            margin-inline-start: auto;
        }

        /* ---------- buttons -------------------------------------------- */
        .bcx-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 650;
            line-height: 1;
            padding: 7px 11px;
            border-radius: 7px;
            border: 1px solid var(--bcx-line);
            background: transparent;
            color: var(--bcx-ink);
            cursor: pointer;
            text-decoration: none;
            white-space: nowrap;
            transition: border-color .15s, background .15s, color .15s;
        }

        .bcx-btn:hover:not(:disabled) {
            border-color: color-mix(in srgb, var(--bcx-brand) 45%, var(--bcx-line));
            color: var(--bcx-ink);
            background: color-mix(in srgb, var(--bcx-brand) 7%, transparent);
        }

        .bcx-btn:disabled,
        .bcx-btn.disabled {
            opacity: .45;
            cursor: not-allowed;
        }

        .bcx-btn--primary {
            background: var(--bcx-brand);
            border-color: var(--bcx-brand);
            color: var(--bcx-brand-ink);
        }

        .bcx-btn--primary:hover:not(:disabled) {
            background: color-mix(in srgb, var(--bcx-brand) 86%, #000);
            border-color: color-mix(in srgb, var(--bcx-brand) 86%, #000);
            color: var(--bcx-brand-ink);
        }

        .bcx-btn--danger {
            color: #e11d48;
            border-color: color-mix(in srgb, #e11d48 32%, var(--bcx-line));
        }

        .bcx-btn--danger:hover:not(:disabled) {
            background: color-mix(in srgb, #e11d48 10%, transparent);
            border-color: #e11d48;
            color: #e11d48;
        }

        .bcx-btn--icon {
            padding: 7px 9px;
        }

        .bcx-btn--sm {
            font-size: 11.5px;
            padding: 5px 9px;
        }

        /* ---------- chips ---------------------------------------------- */
        .bcx-chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 10px;
            font-weight: 750;
            letter-spacing: .06em;
            text-transform: uppercase;
            padding: 4px 8px;
            border-radius: 5px;
            border: 1px solid var(--bcx-line);
            color: var(--bcx-muted);
            white-space: nowrap;
        }

        .bcx-chip--brand {
            border-color: color-mix(in srgb, var(--bcx-brand) 50%, transparent);
            color: var(--bcx-brand);
            background: color-mix(in srgb, var(--bcx-brand) 12%, transparent);
        }

        .bcx-chip--ok {
            border-color: color-mix(in srgb, #10b981 45%, transparent);
            color: #059669;
            background: color-mix(in srgb, #10b981 12%, transparent);
        }

        [data-bs-theme="dark"] .bcx-chip--ok {
            color: #34d399;
        }

        /* ---------- inline editable title ------------------------------ */
        .bcx-name {
            font: inherit;
            font-size: 13px;
            font-weight: 750;
            background: transparent;
            border: 1px solid transparent;
            color: var(--bcx-ink);
            padding: 5px 7px;
            border-radius: 6px;
            min-width: 180px;
        }

        .bcx-name:hover {
            background: color-mix(in srgb, var(--bcx-ink) 6%, transparent);
        }

        .bcx-name:focus {
            outline: 0;
            border-color: var(--bcx-brand);
            background: var(--bcx-panel);
        }

        /* ---------- rails, drawers, panes ------------------------------ */
        .bcx-rail {
            background: var(--bcx-panel);
            border-inline-end: 1px solid var(--bcx-line);
            padding: 8px 0;
            display: flex;
            flex-direction: column;
            gap: 4px;
            align-items: center;
        }

        .bcx-rail__btn {
            width: 38px;
            height: 38px;
            border-radius: 9px;
            border: 0;
            background: transparent;
            color: var(--bcx-muted);
            font-size: 15px;
            cursor: pointer;
            position: relative;
            display: grid;
            place-items: center;
        }

        .bcx-rail__btn:hover {
            color: var(--bcx-ink);
            background: color-mix(in srgb, var(--bcx-ink) 7%, transparent);
        }

        .bcx-rail__btn.is-active {
            background: color-mix(in srgb, var(--bcx-brand) 16%, transparent);
            color: var(--bcx-brand);
        }

        .bcx-rail__btn.is-active::before {
            content: "";
            position: absolute;
            inset-inline-start: -8px;
            top: 9px;
            bottom: 9px;
            width: 3px;
            border-radius: 0 3px 3px 0;
            background: var(--bcx-brand);
        }

        .bcx-drawer {
            background: var(--bcx-panel);
            border-inline-end: 1px solid var(--bcx-line);
            padding: 12px;
            min-width: 0;
            overflow-y: auto;
        }

        .bcx-drawer--end {
            border-inline-end: 0;
            border-inline-start: 1px solid var(--bcx-line);
        }

        .bcx-drawer__title {
            font-size: 10.5px;
            font-weight: 800;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--bcx-muted);
            margin: 4px 2px 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .bcx-drawer__title span {
            margin-inline-start: auto;
            letter-spacing: 0;
            text-transform: none;
            font-weight: 600;
            color: var(--bcx-faint);
            font-variant-numeric: tabular-nums;
        }

        .bcx-drawer__title+.bcx-drawer__title,
        .bcx-drawer__group+.bcx-drawer__group {
            margin-top: 18px;
        }

        /* ---------- fields --------------------------------------------- */
        .bcx-field {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 4px 2px;
        }

        .bcx-field>span {
            font-size: 11.5px;
            color: var(--bcx-muted);
            width: 96px;
            flex: none;
        }

        .bcx-field input,
        .bcx-field select,
        .bcx-input {
            flex: 1;
            min-width: 0;
            font: inherit;
            font-size: 12px;
            font-variant-numeric: tabular-nums;
            background: color-mix(in srgb, var(--bcx-ink) 6%, transparent);
            border: 1px solid transparent;
            color: var(--bcx-ink);
            border-radius: 6px;
            padding: 6px 8px;
        }

        .bcx-field input:focus,
        .bcx-field select:focus,
        .bcx-input:focus {
            outline: 0;
            border-color: var(--bcx-brand);
            background: var(--bcx-panel);
        }

        .bcx-field input::placeholder,
        .bcx-input::placeholder {
            color: var(--bcx-faint);
        }

        .bcx-field--stack {
            flex-direction: column;
            align-items: stretch;
            gap: 4px;
        }

        .bcx-field--stack>span {
            width: auto;
        }

        .bcx-field__unit {
            position: relative;
            flex: 1;
            display: flex;
        }

        .bcx-field__unit input {
            padding-inline-end: 28px;
        }

        .bcx-field__unit em {
            position: absolute;
            inset-inline-end: 8px;
            top: 50%;
            transform: translateY(-50%);
            font-style: normal;
            font-size: 10.5px;
            font-weight: 700;
            color: var(--bcx-faint);
            pointer-events: none;
        }

        .bcx-note {
            font-size: 11px;
            line-height: 1.55;
            color: var(--bcx-muted);
            margin: 8px 2px 0;
        }

        /* ---------- rows ----------------------------------------------- */
        .bcx-row {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 7px 8px;
            border-radius: 7px;
            cursor: pointer;
            border: 1px solid transparent;
            width: 100%;
            text-align: start;
            background: transparent;
            color: inherit;
        }

        .bcx-row:hover {
            background: color-mix(in srgb, var(--bcx-ink) 6%, transparent);
        }

        .bcx-row.is-active {
            background: color-mix(in srgb, var(--bcx-brand) 14%, transparent);
            border-color: color-mix(in srgb, var(--bcx-brand) 30%, transparent);
            color: var(--bcx-brand);
        }

        .bcx-row.is-off {
            opacity: .5;
        }

        .bcx-row__label {
            font-size: 12.5px;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .bcx-row__meta {
            font-size: 10.5px;
            color: var(--bcx-faint);
        }

        .bcx-row__tools {
            margin-inline-start: auto;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .bcx-row__n {
            margin-inline-start: auto;
            font-family: var(--bcx-mono);
            font-size: 11px;
            color: var(--bcx-faint);
        }

        .bcx-ord {
            width: 22px;
            height: 22px;
            border-radius: 6px;
            border: 1px solid var(--bcx-line);
            background: transparent;
            color: var(--bcx-muted);
            line-height: 1;
            padding: 0;
            cursor: pointer;
        }

        .bcx-ord:disabled {
            opacity: .3;
            cursor: default;
        }

        /* ---------- switch --------------------------------------------- */
        .bcx-switch {
            position: relative;
            width: 32px;
            height: 18px;
            flex: none;
            display: inline-block;
            cursor: pointer;
            margin: 0;
        }

        .bcx-switch input {
            display: none;
        }

        .bcx-switch span {
            position: absolute;
            inset: 0;
            border-radius: 999px;
            background: color-mix(in srgb, var(--bcx-ink) 20%, transparent);
            transition: background .15s;
        }

        .bcx-switch span::after {
            content: "";
            position: absolute;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: #fff;
            top: 2px;
            inset-inline-start: 2px;
            transition: inset-inline-start .15s;
            box-shadow: 0 1px 2px rgba(0, 0, 0, .35);
        }

        .bcx-switch input:checked+span {
            background: var(--bcx-brand);
        }

        .bcx-switch input:checked+span::after {
            inset-inline-start: 16px;
        }

        /* ---------- stage ---------------------------------------------- */
        .bcx-stage {
            position: relative;
            display: grid;
            place-items: center;
            padding: 28px;
            min-width: 0;
            background:
                linear-gradient(color-mix(in srgb, var(--bcx-ink) 4%, transparent), color-mix(in srgb, var(--bcx-ink) 4%, transparent)),
                repeating-linear-gradient(0deg, transparent 0 15px, color-mix(in srgb, var(--bcx-ink) 6%, transparent) 15px 16px),
                repeating-linear-gradient(90deg, transparent 0 15px, color-mix(in srgb, var(--bcx-ink) 6%, transparent) 15px 16px);
        }

        .bcx-stage__sheet {
            background: #fff;
            box-shadow: 0 1px 1px rgba(15, 23, 42, .2), 0 20px 38px -16px rgba(15, 23, 42, .5);
            overflow: hidden;
        }

        .bcx-stage__frame {
            width: 100%;
            height: 100%;
            border: 0;
            display: block;
            background: #fff;
            overflow: hidden;
        }

        .bcx-stage__empty {
            color: var(--bcx-faint);
            font-size: 12px;
        }

        /* ---------- status bar ------------------------------------------ */
        .bcx-status {
            display: flex;
            align-items: center;
            gap: 16px;
            min-height: 30px;
            padding: 4px 12px;
            border-top: 1px solid var(--bcx-line);
            background: var(--bcx-panel);
            font-family: var(--bcx-mono);
            font-size: 10.5px;
            letter-spacing: .04em;
            color: var(--bcx-faint);
            flex-wrap: wrap;
        }

        .bcx-status b {
            color: var(--bcx-ink-2);
            font-weight: 600;
        }

        /* ---------- cards, tables (list + cart screens) ----------------- */
        .bcx-card {
            background: var(--bcx-panel);
            border: 1px solid var(--bcx-line);
            border-radius: 12px;
            box-shadow: var(--bcx-shadow);
            overflow: hidden;
        }

        .bcx-pad {
            padding: 14px;
        }

        .bcx-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12.5px;
        }

        .bcx-table th {
            text-align: start;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .11em;
            text-transform: uppercase;
            color: var(--bcx-muted);
            padding: 10px 12px;
            border-bottom: 1px solid var(--bcx-line);
            background: var(--bcx-panel-2);
            white-space: nowrap;
        }

        .bcx-table td {
            padding: 11px 12px;
            border-bottom: 1px solid var(--bcx-line-soft);
            vertical-align: middle;
        }

        .bcx-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .bcx-table tbody tr:hover td {
            background: color-mix(in srgb, var(--bcx-brand) 5%, transparent);
        }

        .bcx-table__name {
            font-weight: 650;
            color: var(--bcx-ink);
        }

        .bcx-table__meta {
            font-size: 10.5px;
            color: var(--bcx-faint);
            font-family: var(--bcx-mono);
        }

        .bcx-num {
            font-family: var(--bcx-mono);
            font-variant-numeric: tabular-nums;
        }

        .bcx-scroll {
            overflow-x: auto;
        }

        .bcx-empty {
            padding: 46px 20px;
            text-align: center;
            color: var(--bcx-muted);
        }

        .bcx-empty i {
            font-size: 26px;
            color: var(--bcx-faint);
            display: block;
            margin-bottom: 10px;
        }

        /* ---------- product tiles + cart rows (print cart) --------------- */
        .bcx-tiles {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(158px, 1fr));
            gap: 8px;
            padding: 12px;
        }

        .bcx-tile {
            display: flex;
            gap: 9px;
            padding: 9px;
            border: 1px solid var(--bcx-line);
            border-radius: 9px;
            background: var(--bcx-panel);
            cursor: pointer;
            text-align: start;
            transition: border-color .15s, transform .15s, background .15s;
        }

        .bcx-tile:hover {
            border-color: var(--bcx-brand);
            background: color-mix(in srgb, var(--bcx-brand) 6%, var(--bcx-panel));
            transform: translateY(-1px);
        }

        .bcx-tile__img {
            width: 34px;
            height: 34px;
            border-radius: 6px;
            object-fit: cover;
            border: 1px solid var(--bcx-line);
            flex: none;
            background: var(--bcx-panel-2);
        }

        .bcx-tile__body {
            min-width: 0;
            flex: 1;
        }

        .bcx-tile__name {
            font-size: 11.5px;
            font-weight: 650;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .bcx-tile__meta {
            font-family: var(--bcx-mono);
            font-size: 10px;
            color: var(--bcx-faint);
            margin-top: 3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .bcx-tile__foot {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 5px;
            font-size: 10.5px;
        }

        .bcx-tile__price {
            font-weight: 750;
            color: var(--bcx-ink);
            font-variant-numeric: tabular-nums;
        }

        .bcx-cartrow {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 9px 12px;
            border-bottom: 1px solid var(--bcx-line-soft);
        }

        .bcx-cartrow:hover {
            background: color-mix(in srgb, var(--bcx-brand) 5%, transparent);
        }

        .bcx-stepper {
            display: inline-flex;
            align-items: center;
            border: 1px solid var(--bcx-line);
            border-radius: 7px;
            overflow: hidden;
            flex: none;
        }

        .bcx-stepper button {
            width: 22px;
            height: 22px;
            border: 0;
            background: transparent;
            color: var(--bcx-muted);
            font-size: 10px;
            cursor: pointer;
            padding: 0;
        }

        .bcx-stepper button:hover {
            background: color-mix(in srgb, var(--bcx-brand) 14%, transparent);
            color: var(--bcx-brand);
        }

        .bcx-stepper b {
            min-width: 26px;
            text-align: center;
            font-family: var(--bcx-mono);
            font-size: 11px;
            font-weight: 700;
            border-inline: 1px solid var(--bcx-line);
            line-height: 22px;
        }

        .bcx-kbd {
            font-family: var(--bcx-mono);
            font-size: 10px;
            padding: 2px 5px;
            border-radius: 4px;
            border: 1px solid var(--bcx-line);
            background: var(--bcx-panel-2);
            color: var(--bcx-muted);
        }

        /* ---------- misc ------------------------------------------------ */
        .bcx-sep {
            width: 1px;
            align-self: stretch;
            margin: 4px 2px;
            background: var(--bcx-line);
        }

        .bcx-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #10b981;
            box-shadow: 0 0 0 3px color-mix(in srgb, #10b981 22%, transparent);
            display: inline-block;
        }

        .bcx-dot--idle {
            background: var(--bcx-faint);
            box-shadow: none;
        }
    </style>
@endonce

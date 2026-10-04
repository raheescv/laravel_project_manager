{{-- .scx — simple sectioned settings panel (Sale, Printers). Theme-var accent, light + dark. Included inside the component root (no @once: Livewire morphing would strip it on re-render). --}}
    <style>
        .scx {
            --scx-acc: var(--bs-primary);
            --scx-acc-soft: color-mix(in srgb, var(--scx-acc) 11%, transparent);
            --scx-surface: var(--bs-component-bg, var(--bs-body-bg));
            --scx-surface-2: color-mix(in srgb, var(--scx-acc) 3%, var(--scx-surface));
            --scx-line: var(--bs-border-color);
            --scx-line-soft: color-mix(in srgb, var(--bs-border-color) 60%, transparent);
            --scx-lift: 0 1px 2px rgba(16, 24, 40, .06), 0 14px 30px -18px rgba(16, 24, 40, .35);
            display: flex;
            flex-direction: column;
            gap: 1rem;
            font-size: .84rem;
        }

        [data-bs-theme="dark"] .scx {
            --scx-acc-soft: color-mix(in srgb, var(--scx-acc) 22%, transparent);
            --scx-lift: 0 1px 2px rgba(0, 0, 0, .4), 0 14px 30px -16px rgba(0, 0, 0, .65);
        }

        .scx-section {
            border: 1px solid var(--scx-line-soft);
            border-radius: 16px;
            background: var(--scx-surface);
        }

        .scx-head {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .85rem 1.1rem;
            border-bottom: 1px solid var(--scx-line-soft);
            background: var(--scx-surface-2);
            border-radius: 16px 16px 0 0;
        }

        .scx-ic {
            --tone: var(--scx-acc);
            display: grid;
            place-items: center;
            flex: none;
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: color-mix(in srgb, var(--tone) 13%, transparent);
            color: var(--tone);
            font-size: .95rem;
        }

        .scx-head h6 {
            margin: 0;
            font-size: .92rem;
            font-weight: 700;
            color: var(--bs-emphasis-color);
        }

        .scx-head p {
            margin: .1rem 0 0;
            font-size: .76rem;
            color: var(--bs-secondary-color);
        }

        .scx-head-end {
            margin-inline-start: auto;
        }

        .scx-body {
            padding: 1rem 1.1rem 1.1rem;
        }

        .scx-body .form-label {
            margin-bottom: .3rem;
            font-size: .78rem;
            font-weight: 600;
            color: var(--bs-emphasis-color);
        }

        .scx-body .form-text {
            font-size: .72rem;
        }

        .scx-sub {
            margin: 1.1rem 0 .6rem;
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--bs-secondary-color);
        }

        .scx-sub:first-child {
            margin-top: 0;
        }

        /* Switch rows: two columns of label + hint + switch. */
        .scx-toggles {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: .6rem;
        }

        .scx-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin: 0;
            padding: .7rem .85rem;
            border: 1px solid var(--scx-line-soft);
            border-radius: 12px;
            cursor: pointer;
            transition: border-color .15s, background .15s;
        }

        .scx-toggle:hover {
            border-color: color-mix(in srgb, var(--scx-acc) 40%, var(--scx-line));
        }

        .scx-toggle:has(:checked) {
            background: color-mix(in srgb, var(--scx-acc) 5%, var(--scx-surface));
            border-color: color-mix(in srgb, var(--scx-acc) 30%, var(--scx-line-soft));
        }

        .scx-toggle-text {
            min-width: 0;
        }

        .scx-toggle-label {
            display: block;
            font-weight: 600;
            color: var(--bs-emphasis-color);
        }

        .scx-toggle-hint {
            display: block;
            margin-top: .1rem;
            font-size: .72rem;
            line-height: 1.4;
            color: var(--bs-secondary-color);
        }

        .scx-toggle .form-switch .form-check-input {
            width: 2.4em;
            height: 1.3em;
            margin: 0;
            cursor: pointer;
        }

        /* Sticky save bar */
        .scx-bar {
            position: sticky;
            bottom: 0;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            padding: .65rem .75rem .65rem 1.1rem;
            border: 1px solid var(--scx-line-soft);
            border-radius: 14px;
            background: color-mix(in srgb, var(--scx-surface) 88%, transparent);
            backdrop-filter: blur(8px);
            box-shadow: var(--scx-lift);
            color: var(--bs-secondary-color);
            font-size: .78rem;
        }

        .scx-bar .btn {
            border-radius: 10px;
            font-weight: 600;
            padding-inline: 1rem;
        }

        /* Section footer (per-form save), info note, status pill, inline switch, sub-panel */
        .scx-foot {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: .5rem;
            padding: .7rem 1.1rem;
            border-top: 1px solid var(--scx-line-soft);
            background: var(--scx-surface-2);
            border-radius: 0 0 16px 16px;
        }

        .scx-foot .btn {
            border-radius: 10px;
            font-weight: 600;
            padding-inline: 1rem;
        }

        .scx-foot-note {
            margin-inline-end: auto;
            font-size: .74rem;
            color: var(--bs-secondary-color);
        }

        .scx-note {
            display: flex;
            gap: .65rem;
            padding: .7rem .85rem;
            border-radius: 12px;
            background: var(--scx-acc-soft);
            color: var(--bs-body-color);
            font-size: .78rem;
            line-height: 1.5;
        }

        .scx-note > i {
            margin-top: .2rem;
            color: var(--scx-acc);
        }

        .scx-pill {
            --pill: var(--bs-secondary-color);
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .25rem .65rem;
            border-radius: 999px;
            background: color-mix(in srgb, var(--pill) 13%, transparent);
            color: color-mix(in srgb, var(--pill) 85%, var(--bs-emphasis-color));
            font-size: .72rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .scx-pill::before {
            content: "";
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--pill);
        }

        .scx-pill.is-live { --pill: #2f9e62; }
        .scx-pill.is-test { --pill: #d4931c; }
        .scx-pill.is-off { --pill: #8a94a6; }

        .scx-switch {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            margin: 0;
            padding: .3rem .4rem .3rem .75rem;
            border: 1px solid var(--scx-line-soft);
            border-radius: 999px;
            background: var(--scx-surface);
            font-size: .76rem;
            font-weight: 600;
            color: var(--bs-emphasis-color);
            cursor: pointer;
            white-space: nowrap;
        }

        .scx-switch .form-check-input {
            width: 2.3em;
            height: 1.25em;
            margin: 0;
            float: none;
            cursor: pointer;
        }

        .scx-panel {
            border: 1px solid var(--scx-line-soft);
            border-radius: 14px;
            transition: opacity .15s;
        }

        .scx-panel-head {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .85rem 1rem;
            border-bottom: 1px solid var(--scx-line-soft);
        }

        .scx-panel-body {
            padding: 1rem;
        }

        .scx-panel.is-off .scx-panel-body {
            opacity: .5;
        }

        .scx-days {
            display: flex;
            flex-wrap: wrap;
            gap: .4rem;
        }

        .scx-days .btn {
            min-width: 3.4rem;
            border-radius: 999px;
            font-weight: 600;
        }

        @media (max-width: 575.98px) {
            .scx-toggles {
                grid-template-columns: 1fr;
            }
        }
    </style>

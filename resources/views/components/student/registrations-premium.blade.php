{{-- Students → Registrations and Parents (scope .rgx): the "Soft Ledger" picked in
     docs/student-registrations-premium-preview.html. Every colour comes from the
     settings theme, so it follows the tenant's colour and dark mode. --}}
@once
    <style>
        .rgx {
            --rgx-brand: var(--bs-primary);
            --rgx-surface: var(--bs-component-bg, var(--bs-body-bg));
            --rgx-surface-2: color-mix(in srgb, var(--rgx-ink) 4%, var(--rgx-surface));
            --rgx-ink: var(--bs-emphasis-color);
            --rgx-ink-2: var(--bs-secondary-color);
            --rgx-line: var(--bs-border-color);
            --rgx-brand-soft: color-mix(in srgb, var(--rgx-brand) 11%, var(--rgx-surface));
            --rgx-brand-line: color-mix(in srgb, var(--rgx-brand) 28%, var(--rgx-line));
            --rgx-brand-ink: var(--rgx-brand);
            --rgx-shadow: 0 1px 2px rgba(16, 24, 40, .05), 0 14px 30px -20px rgba(16, 24, 40, .28);
            --rgx-fz: 13.5px;
            color: var(--rgx-ink);
            font-size: var(--rgx-fz);
        }

        [data-bs-theme="dark"] .rgx {
            --rgx-brand-ink: color-mix(in srgb, var(--rgx-brand), white 35%);
            --rgx-shadow: 0 18px 34px -22px #000;
        }

        .rgx-head {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 12px;
        }

        .rgx b {
            color: var(--rgx-ink);
        }

        .rgx-hero b,
        .rgx-float b {
            color: inherit;
        }

        .rgx-stats {
            display: flex;
            gap: 10px;
        }

        .rgx-stat {
            min-width: 104px;
            padding: 9px 14px;
            border-radius: 13px;
            background: var(--rgx-surface);
            box-shadow: var(--rgx-shadow);
        }

        .rgx-stat b {
            display: block;
            font-size: 20px;
            font-weight: 600;
            line-height: 1.1;
        }

        .rgx-stat span {
            color: var(--rgx-ink-2);
            font-size: 11.5px;
            white-space: nowrap;
        }

        .rgx-filters {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            padding: 12px;
            margin-bottom: 12px;
            border-radius: 16px;
            background: var(--rgx-surface);
            box-shadow: var(--rgx-shadow);
        }

        .rgx-search {
            position: relative;
            display: block;
            flex: 1 1 300px;
            min-width: 0;
            margin: 0;
        }

        .rgx-search .fa {
            position: absolute;
            top: 50%;
            left: 14px;
            transform: translateY(-50%);
            color: var(--rgx-ink-2);
        }

        .rgx-search input {
            width: 100%;
            height: 42px;
            padding: 0 14px 0 38px;
            border: 1px solid var(--rgx-line);
            border-radius: 11px;
            outline: 0;
            background: var(--rgx-surface);
            color: var(--rgx-ink);
        }

        .rgx-search input:focus {
            border-color: var(--rgx-brand);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--rgx-brand) 18%, transparent);
        }

        .rgx-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .rgx-chip {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            height: 36px;
            padding: 0 13px;
            border: 1px solid var(--rgx-line);
            border-radius: 10px;
            background: var(--rgx-surface);
            color: var(--rgx-ink-2);
            font-size: 12.5px;
            font-weight: 500;
            white-space: nowrap;
        }

        .rgx-chip i {
            font-size: 11px;
            font-style: normal;
            opacity: .75;
        }

        .rgx-chip:hover {
            border-color: var(--rgx-brand-line);
            color: var(--rgx-ink);
        }

        .rgx-chip.is-on {
            border-color: var(--rgx-brand);
            background: var(--rgx-brand);
            color: #fff;
        }

        .rgx-avatar {
            flex: none;
            display: grid;
            place-items: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--rgx-brand-soft);
            color: var(--rgx-brand-ink);
            font-size: 13px;
            font-weight: 600;
        }

        .rgx-check {
            appearance: none;
            display: block;
            width: 18px;
            height: 18px;
            margin: 0;
            border: 1.5px solid color-mix(in srgb, var(--rgx-ink-2) 55%, var(--rgx-surface));
            border-radius: 5px;
            background: var(--rgx-surface);
            cursor: pointer;
        }

        .rgx-check:checked {
            border-color: var(--rgx-brand);
            background-color: var(--rgx-brand);
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath fill='none' stroke='white' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round' d='M3.5 8.5l3 3 6-7'/%3E%3C/svg%3E");
        }

        .rgx-check:focus-visible {
            outline: 2px solid var(--rgx-brand);
            outline-offset: 2px;
        }

        .rgx-list {
            overflow: hidden;
            border-radius: 16px;
            background: var(--rgx-surface);
            box-shadow: var(--rgx-shadow);
        }

        .rgx-row-head,
        .rgx-row {
            display: grid;
            grid-template-columns: 48px minmax(220px, 1fr) minmax(0, 1.4fr) 116px 280px;
            align-items: center;
        }

        .rgx-row-head.rgx-no-check,
        .rgx-row.rgx-no-check {
            grid-template-columns: minmax(230px, 1fr) minmax(0, 1.45fr) 104px 110px;
            padding-left: 16px;
        }

        .rgx-row-head {
            padding: 10px 0;
            border-bottom: 1px solid var(--rgx-line);
            background: var(--rgx-surface-2);
            color: var(--rgx-ink-2);
            font-size: 11.5px;
            font-weight: 600;
            letter-spacing: .02em;
        }

        .rgx-row-head span > * {
            color: inherit;
            font-weight: inherit;
        }

        .rgx-cell-check {
            justify-self: center;
        }

        .rgx-row {
            padding: 13px 0;
            border-bottom: 1px solid var(--rgx-line);
            transition: background .12s;
        }

        .rgx-row:last-child {
            border-bottom: 0;
        }

        .rgx-row:hover {
            background: var(--rgx-surface-2);
        }

        .rgx-row.is-selected {
            background: var(--rgx-brand-soft);
        }

        .rgx-row.is-selected .rgx-avatar,
        .rgx-row.is-selected .rgx-tag {
            background: var(--rgx-surface);
        }

        .rgx-parent {
            display: flex;
            align-items: flex-start;
            gap: 11px;
            min-width: 0;
            padding-right: 14px;
        }

        .rgx-parent > div {
            min-width: 0;
        }

        .rgx-parent b {
            display: block;
            font-size: 14px;
            font-weight: 600;
            overflow-wrap: anywhere;
        }

        .rgx-parent small {
            display: block;
            color: var(--rgx-ink-2);
            font-size: 12px;
            overflow-wrap: anywhere;
        }

        .rgx-flag {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-top: 5px;
            padding: 2px 8px;
            border-radius: 7px;
            background: var(--bs-info-bg-subtle);
            color: var(--bs-info-text-emphasis);
            font-size: 11px;
            font-style: normal;
            font-weight: 500;
        }

        .rgx-kids {
            display: grid;
            gap: 7px;
            min-width: 0;
            padding-right: 14px;
        }

        .rgx-kid {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 3px 8px;
            min-width: 0;
        }

        .rgx-kid b {
            font-weight: 500;
        }

        .rgx-tag {
            padding: 1px 7px;
            border-radius: 6px;
            background: var(--rgx-surface-2);
            box-shadow: inset 0 0 0 1px var(--rgx-line);
            color: var(--rgx-ink-2);
            font-size: 11px;
            white-space: nowrap;
        }

        .rgx-kid-note {
            flex-basis: 100%;
            color: var(--bs-info-text-emphasis);
            font-size: 11.5px;
            overflow-wrap: anywhere;
        }

        .rgx-when {
            padding-right: 10px;
            color: var(--rgx-ink-2);
            font-size: 12px;
        }

        .rgx-actions {
            display: flex;
            justify-content: flex-end;
            gap: 6px;
            padding-right: 16px;
        }

        .rgx-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            height: 34px;
            padding: 0 12px;
            border: 1px solid var(--rgx-line);
            border-radius: 9px;
            background: var(--rgx-surface);
            color: var(--rgx-ink);
            font-size: 12.5px;
            font-weight: 500;
            white-space: nowrap;
        }

        .rgx-btn:hover {
            border-color: var(--rgx-brand-line);
        }

        .rgx-btn-go {
            border-color: var(--rgx-brand);
            background: var(--rgx-brand);
            color: #fff;
        }

        .rgx-btn-go:hover {
            background: color-mix(in srgb, var(--rgx-brand), black 12%);
        }

        .rgx-btn-view {
            color: var(--rgx-brand-ink);
        }

        .rgx-btn-bad:hover {
            border-color: var(--bs-danger);
            color: var(--bs-danger);
        }

        .rgx-empty {
            padding: 56px 16px;
            color: var(--rgx-ink-2);
            text-align: center;
        }

        .rgx-empty > .fa {
            display: block;
            margin-bottom: 8px;
            font-size: 28px;
        }

        .rgx-foot {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
            margin-top: 12px;
        }

        .rgx-foot-clear {
            padding-bottom: 76px;
        }

        .rgx-pages {
            flex: 1;
            min-width: 0;
            color: var(--rgx-ink-2);
            font-size: 12px;
        }

        .rgx-limit {
            flex: none;
            height: 32px;
            padding: 0 8px;
            border: 1px solid var(--rgx-line);
            border-radius: 8px;
            background: var(--rgx-surface);
            color: var(--rgx-ink);
        }

        @keyframes rgx-rise {
            from {
                opacity: 0;
                transform: translate(-50%, 14px);
            }
        }

        .rgx-float {
            position: fixed;
            z-index: 1040;
            bottom: 22px;
            left: 50%;
            display: flex;
            align-items: center;
            gap: 16px;
            width: max-content;
            max-width: calc(100vw - 24px);
            padding: 9px 10px 9px 20px;
            border-radius: 999px;
            background: var(--rgx-brand);
            box-shadow: 0 18px 38px -14px rgba(10, 20, 50, .6);
            color: #fff;
            transform: translateX(-50%);
            animation: rgx-rise .24s cubic-bezier(.2, .9, .25, 1);
        }

        .rgx-float-count {
            white-space: nowrap;
        }

        .rgx-float-count b {
            font-weight: 600;
        }

        .rgx-float-count em {
            font-style: normal;
            opacity: .75;
        }

        .rgx-float-acts {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .rgx-float-clear {
            padding: 7px 12px;
            border: 0;
            background: none;
            color: #fff;
            font-size: 12.5px;
            opacity: .85;
        }

        .rgx-float-clear:hover {
            opacity: 1;
        }

        .rgx-float-do {
            height: 36px;
            padding: 0 16px;
            border: 0;
            border-radius: 999px;
            background: #fff;
            color: #16294e;
            font-size: 12.5px;
            font-weight: 600;
            white-space: nowrap;
        }

        .rgx-dialog {
            max-width: 640px;
        }

        .rgx-sheet {
            overflow: hidden;
            border: 0;
            border-radius: 18px;
            background: var(--rgx-surface);
            box-shadow: 0 34px 70px -24px rgba(5, 10, 25, .65);
        }

        .rgx-hero {
            position: relative;
            flex: none;
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 20px 52px 20px 22px;
            background: linear-gradient(135deg, var(--rgx-brand), color-mix(in srgb, var(--rgx-brand), black 28%));
            color: #fff;
        }

        .rgx-hero .rgx-avatar {
            width: 52px;
            height: 52px;
            background: rgba(255, 255, 255, .18);
            color: #fff;
            font-size: 16px;
        }

        .rgx-hero h2 {
            margin: 0;
            color: #fff;
            font-size: 18px;
            font-weight: 600;
            line-height: 1.2;
            overflow-wrap: anywhere;
        }

        .rgx-hero p {
            margin: 3px 0 0;
            font-size: 12px;
            opacity: .85;
        }

        .rgx-close {
            position: absolute;
            top: 14px;
            right: 14px;
            width: 32px;
            height: 32px;
            border: 0;
            border-radius: 9px;
            background: rgba(255, 255, 255, .16);
            color: #fff;
        }

        .rgx-sheet-body {
            display: grid;
            align-content: start;
            gap: 18px;
            padding: 18px 22px;
        }

        .rgx-section {
            margin: 0 0 8px;
            color: var(--rgx-ink-2);
            font-size: 11.5px;
            font-weight: 600;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .rgx-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px 14px;
        }

        .rgx-grid-3 {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .rgx-span {
            grid-column: 1 / -1;
        }

        .rgx-grid small {
            display: block;
            color: var(--rgx-ink-2);
            font-size: 11.5px;
        }

        .rgx-grid span {
            font-weight: 500;
            overflow-wrap: anywhere;
        }

        .rgx-detail {
            padding: 13px 15px;
            border: 1px solid var(--rgx-line);
            border-radius: 13px;
            background: var(--rgx-surface-2);
        }

        .rgx-detail + .rgx-detail {
            margin-top: 9px;
        }

        .rgx-detail-name {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 11px;
            font-weight: 600;
        }

        .rgx-detail-name .rgx-avatar {
            width: 32px;
            height: 32px;
            font-size: 11.5px;
        }

        .rgx-note {
            display: flex;
            gap: 7px;
            margin-top: 11px;
            padding: 8px 10px;
            border-radius: 9px;
            background: var(--bs-info-bg-subtle);
            color: var(--bs-info-text-emphasis);
            font-size: 12px;
            overflow-wrap: anywhere;
        }

        .rgx-sheet-foot {
            flex: none;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            padding: 12px 22px;
            border-top: 1px solid var(--rgx-line);
        }

        .rgx-guardians .rgx-row-head,
        .rgx-guardians .rgx-row {
            grid-template-columns: minmax(180px, 1fr) minmax(190px, 1fr) minmax(0, 1.1fr) 190px 170px;
            padding-left: 16px;
        }

        .rgx-guardians .rgx-parent {
            align-items: center;
        }

        .rgx-contact {
            min-width: 0;
            padding-right: 14px;
        }

        .rgx-contact b {
            display: block;
            font-weight: 500;
        }

        .rgx-contact small {
            display: block;
            color: var(--rgx-ink-2);
            font-size: 12px;
            overflow-wrap: anywhere;
        }

        .rgx-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            min-width: 0;
            padding-right: 14px;
        }

        .rgx-tag-link {
            color: var(--rgx-ink);
            font-size: 12px;
            text-decoration: none;
        }

        .rgx-tag-link:hover {
            box-shadow: inset 0 0 0 1px var(--rgx-brand);
            color: var(--rgx-brand-ink);
        }

        .rgx-status {
            display: grid;
            justify-items: start;
            gap: 4px;
            min-width: 0;
            padding-right: 10px;
        }

        .rgx-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 2px 8px;
            border-radius: 7px;
            font-size: 11px;
            font-weight: 500;
        }

        .rgx-pill-brand {
            background: var(--rgx-brand-soft);
            color: var(--rgx-brand-ink);
        }

        .rgx-pill-ok {
            background: var(--bs-success-bg-subtle);
            color: var(--bs-success-text-emphasis);
        }

        .rgx-pill-warn {
            background: var(--bs-warning-bg-subtle);
            color: var(--bs-warning-text-emphasis);
        }

        .rgx-pill-off {
            background: var(--rgx-surface-2);
            color: var(--rgx-ink-2);
        }

        .rgx-invite {
            grid-column: 1 / -1;
            display: flex;
            gap: 6px;
            padding: 10px 16px 0 0;
        }

        .rgx-invite-link {
            flex: 1;
            min-width: 0;
            height: 34px;
            padding: 0 10px;
            border: 1px solid var(--rgx-line);
            border-radius: 9px;
            background: var(--rgx-surface-2);
            color: var(--rgx-ink);
            font-family: var(--bs-font-monospace);
            font-size: 12px;
        }

        @media (max-width: 1199.98px) {
            .rgx-row-head {
                display: none;
            }

            .rgx-row,
            .rgx-row.rgx-no-check {
                grid-template-columns: auto minmax(0, 1fr) auto;
                grid-template-areas:
                    "check parent when"
                    "kids kids kids"
                    "actions actions actions";
                gap: 11px 10px;
                padding: 14px;
            }

            .rgx-row.rgx-no-check {
                grid-template-areas:
                    "parent parent when"
                    "kids kids kids"
                    "actions actions actions";
            }

            .rgx-cell-check {
                grid-area: check;
                align-self: start;
                margin-top: 3px;
            }

            .rgx-parent {
                grid-area: parent;
                padding: 0;
            }

            .rgx-when {
                grid-area: when;
                align-self: start;
                font-size: 11px;
            }

            .rgx-kids {
                grid-area: kids;
                padding: 0;
            }

            .rgx-actions {
                grid-area: actions;
                padding: 0;
            }

            .rgx-guardians .rgx-row {
                grid-template-columns: minmax(0, 1fr) auto;
                grid-template-areas:
                    "parent status"
                    "contact contact"
                    "tags tags"
                    "actions actions";
                padding-left: 14px;
            }

            .rgx-contact {
                grid-area: contact;
                padding: 0;
            }

            .rgx-tags {
                grid-area: tags;
                padding: 0;
            }

            .rgx-status {
                grid-area: status;
                justify-items: end;
                padding: 0;
            }

            .rgx-invite {
                padding: 0;
            }
        }

        @media (max-width: 767.98px) {
            .rgx-stats {
                width: 100%;
            }

            .rgx-stat {
                flex: 1;
                min-width: 0;
            }

            .rgx-stat span {
                white-space: normal;
            }

            .rgx-search {
                flex-basis: 100%;
            }

            .rgx-actions .rgx-btn {
                flex: 1;
                height: 40px;
            }

            .rgx-float {
                right: 10px;
                bottom: 10px;
                left: 10px;
                justify-content: space-between;
                gap: 8px;
                width: auto;
                max-width: none;
                padding: 8px 8px 8px 14px;
                border-radius: 14px;
                transform: none;
                animation: none;
            }

            .rgx-float-count em {
                display: block;
            }

            .rgx-dialog {
                display: flex;
                align-items: flex-end;
                height: 100%;
                max-width: none;
                min-height: 100%;
                margin: 0;
            }

            .rgx-sheet {
                max-height: 92%;
                border-radius: 18px 18px 0 0;
            }

            .rgx-sheet-body {
                padding: 15px;
            }

            .rgx-grid-3 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .rgx-sheet-foot {
                padding: 10px 15px;
            }

            .rgx-sheet-foot .rgx-btn {
                flex: 1;
                height: 42px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .rgx-float {
                animation: none;
            }

            .rgx-row {
                transition: none;
            }
        }
    </style>
@endonce

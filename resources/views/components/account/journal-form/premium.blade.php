{{-- Shared Premium "Flow" design system + behaviour for the Expense and Income modals (scope .exx).
     Include once inside each modal's Livewire root, then call window.exxForm({...}) from the page's pushed script. --}}
@once
    <style>
        /* ════════════  Expense / Income form — "Premium" design system (scoped under .exx)  ════════════
           Compact "Flow" layout: the amount is the headline, then one row per question, everything
           sized so the whole form (Description included) fits a laptop viewport without scrolling.
           Colour derives from the active settings theme (--bs-primary) and tracks dark mode, sibling of
           the Branch (.brx), Employee (.empx) and General Voucher (.gvx) systems. Font Awesome 4 only;
           logical properties throughout so RTL needs no overrides. Preview: docs/expense-modal-premium-preview.html
           Include <x-account.journal-form.premium /> once inside each modal's Livewire root; wire the page with window.exxForm({...}). */

        #ExpenseModal .modal-dialog,
        #IncomeModal .modal-dialog {
            max-width: 680px;
        }

        #ExpenseModal .modal-content,
        #IncomeModal .modal-content {
            border: none;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 28px 70px -24px rgba(16, 24, 40, .55);
        }

        [data-bs-theme="dark"] #ExpenseModal .modal-content,
        [data-bs-theme="dark"] #IncomeModal .modal-content {
            background: #272d34;
        }

        .exx {
            --brand: var(--bs-primary, #2563eb);
            --brand-rgb: var(--bs-primary-rgb, 37, 99, 235);
            --brand-600: color-mix(in srgb, var(--brand), #000 12%);
            --brand-700: color-mix(in srgb, var(--brand), #000 28%);
            --brand-400: color-mix(in srgb, var(--brand), #fff 22%);
            --brand-ink: var(--brand-700);
            --hero-1: color-mix(in srgb, var(--brand), #000 40%);
            --hero-2: color-mix(in srgb, var(--brand), #000 4%);
            --hero-3: color-mix(in srgb, var(--brand), #fff 8%);

            --surface: #ffffff;
            --surface-2: #f5f7fa;
            --surface-3: #eceff4;
            --border: #e4e8ee;
            --border-strong: #d3d9e1;
            --text: #1f2937;
            --text-2: #5b6573;
            --text-3: #8a94a3;

            --success: #059669;
            --success-bg: #ecfdf5;
            --success-rgb: 5, 150, 105;
            --danger: #dc2626;
            --danger-bg: #fef2f2;
            --danger-rgb: 220, 38, 38;
            --amber: #b45309;
            --amber-rgb: 180, 83, 9;

            --r-sm: 8px;
            --r-md: 10px;
            --r-lg: 12px;
            --shadow-sm: 0 1px 2px rgba(16, 24, 40, .05), 0 1px 3px rgba(16, 24, 40, .05);
            --exx-fz: 12.5px;
            --exx-ctl: 34px;

            font-size: var(--exx-fz);
            color: var(--text);
            line-height: 1.4;
            -webkit-font-smoothing: antialiased;
            letter-spacing: -.003em;
        }

        [data-bs-theme="dark"] .exx {
            --brand-ink: color-mix(in srgb, var(--brand), #fff 42%);
            --hero-1: color-mix(in srgb, var(--brand), #000 60%);
            --hero-2: color-mix(in srgb, var(--brand), #000 44%);
            --hero-3: color-mix(in srgb, var(--brand), #000 26%);
            --surface: #272d34;
            --surface-2: #2e353d;
            --surface-3: #353d46;
            --border: #3a424c;
            --border-strong: #4a535e;
            --text: #e8ebef;
            --text-2: #aab2bd;
            --text-3: #7c8693;
            --success-bg: color-mix(in srgb, var(--success), #000 72%);
            --danger-bg: color-mix(in srgb, var(--danger), #000 72%);
            --amber: #fbbf24;
        }

        [data-bs-theme="dark"] .exx input[type="date"] {
            color-scheme: dark;
        }

        .exx [hidden] {
            display: none !important;
        }

        /* ═══════════  HERO  ═══════════ */
        .exx-hero {
            position: relative;
            overflow: hidden;
            isolation: isolate;
            padding: 10px 16px;
            color: #fff;
            background:
                radial-gradient(120% 160% at 12% -10%, rgba(255, 255, 255, .20), transparent 50%),
                radial-gradient(90% 140% at 100% 0%, var(--hero-3), transparent 55%),
                linear-gradient(118deg, var(--hero-1) 0%, var(--hero-2) 60%, var(--hero-3) 130%);
        }

        .exx-hero::after {
            content: "";
            position: absolute;
            inset: 0;
            z-index: -1;
            opacity: .5;
            background-image: radial-gradient(circle at 1px 1px, rgba(255, 255, 255, .10) 1px, transparent 0);
            background-size: 22px 22px;
            -webkit-mask-image: linear-gradient(180deg, #000, transparent 80%);
            mask-image: linear-gradient(180deg, #000, transparent 80%);
        }

        .exx-glow {
            position: absolute;
            z-index: -1;
            border-radius: 50%;
            filter: blur(30px);
        }

        .exx-glow.a {
            width: 180px;
            height: 180px;
            top: -80px;
            inset-inline-end: 6%;
            background: rgba(255, 255, 255, .28);
            opacity: .5;
        }

        .exx-glow.b {
            width: 140px;
            height: 140px;
            bottom: -70px;
            inset-inline-start: -20px;
            background: var(--brand-400);
            opacity: .4;
        }

        .exx-hero-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .exx-hero-id {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }

        .exx-hero-ic {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            color: #fff;
            background: rgba(255, 255, 255, .16);
            border: 1px solid rgba(255, 255, 255, .28);
            backdrop-filter: blur(6px);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .25);
        }

        .exx-eyebrow {
            font-size: 8.5px;
            font-weight: 700;
            letter-spacing: .14em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, .78);
        }

        .exx-hero-title {
            font-size: 15px;
            font-weight: 800;
            letter-spacing: -.02em;
            margin: 0;
            line-height: 1.1;
            color: #fff;
            text-shadow: 0 1px 14px rgba(0, 0, 0, .18);
        }

        .exx-hero-tools {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .exx-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 9.5px;
            font-weight: 700;
            letter-spacing: .02em;
            padding: 4px 9px;
            border-radius: 999px;
            line-height: 1;
            color: var(--brand-700);
            background: rgba(255, 255, 255, .95);
            box-shadow: 0 4px 12px -4px rgba(0, 0, 0, .3);
            white-space: nowrap;
        }

        .exx-pill .dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--brand);
            box-shadow: 0 0 0 3px rgba(var(--brand-rgb), .2);
        }

        .exx-x {
            width: 28px;
            height: 28px;
            flex: 0 0 auto;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            color: #fff;
            cursor: pointer;
            background: rgba(255, 255, 255, .14);
            border: 1px solid rgba(255, 255, 255, .26);
            backdrop-filter: blur(6px);
            transition: background .15s ease, transform .15s ease;
        }

        .exx-x:hover {
            background: rgba(255, 255, 255, .28);
        }

        .exx-x:active {
            transform: scale(.94);
        }

        /* ═══════════  BODY  ═══════════ */
        .exx-body {
            padding: 12px;
            background: var(--surface-2);
            /* hero ≈ 54px, footer ≈ 52px, Bootstrap's modal margins ≈ 56px */
            max-height: calc(100vh - 175px);
            overflow-y: auto;
        }

        .exx-errors {
            display: flex;
            gap: 9px;
            align-items: flex-start;
            background: var(--danger-bg);
            border: 1px solid rgba(var(--danger-rgb), .28);
            border-radius: var(--r-md);
            padding: 7px 10px;
            margin-bottom: 10px;
        }

        .exx-errors-ic {
            color: var(--danger);
            font-size: 13px;
            margin-top: 1px;
        }

        .exx-errors-title {
            font-weight: 700;
            color: var(--danger);
            font-size: 11.5px;
        }

        .exx-errors-list {
            margin: 2px 0 0;
            padding-inline-start: 15px;
            color: var(--danger);
            font-size: 11px;
        }

        /* labels / inputs */
        .exx-label {
            display: block;
            font-size: 9.5px;
            font-weight: 700;
            letter-spacing: .05em;
            text-transform: uppercase;
            color: var(--text-3);
            margin: 0;
        }

        .exx-label i {
            margin-inline-end: 4px;
            opacity: .8;
        }

        .exx .req {
            color: var(--danger);
        }

        .exx-count {
            font-size: 9.5px;
            color: var(--text-3);
            font-variant-numeric: tabular-nums;
            direction: ltr;
            unicode-bidi: isolate;
            white-space: nowrap;
        }

        .exx-count.warn {
            color: var(--amber);
        }

        .exx-input {
            position: relative;
            display: flex;
            align-items: stretch;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--r-sm);
            overflow: hidden;
            min-height: var(--exx-ctl);
            transition: border-color .15s ease, box-shadow .15s ease;
        }

        .exx-input:hover {
            border-color: var(--border-strong);
        }

        .exx-input:focus-within {
            border-color: var(--brand-400);
            box-shadow: 0 0 0 3px rgba(var(--brand-rgb), .16);
        }

        .exx-input.is-invalid {
            border-color: var(--danger);
        }

        .exx-input.is-invalid:focus-within {
            box-shadow: 0 0 0 3px rgba(var(--danger-rgb), .14);
        }

        .exx-prefix {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            flex: 0 0 auto;
            padding: 0 9px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: var(--text-3);
            background: var(--surface-2);
            border-inline-end: 1px solid var(--border);
            white-space: nowrap;
        }

        .exx-suffix {
            display: inline-flex;
            align-items: center;
            flex: 0 0 auto;
            padding: 0 8px;
        }

        .exx-control {
            flex: 1;
            min-width: 0;
            width: 100%;
            border: none;
            outline: none;
            background: transparent;
            padding: 7px 10px;
            font-size: var(--exx-fz);
            color: var(--text);
            font-family: inherit;
            line-height: 1.4;
            box-shadow: none;
        }

        .exx-control:focus {
            outline: none;
            box-shadow: none;
        }

        .exx-control::placeholder {
            color: var(--text-3);
        }

        textarea.exx-control {
            resize: none;
            min-height: 52px;
            padding-inline-end: 52px;
        }

        .exx-input.area .exx-suffix {
            position: absolute;
            inset-inline-end: 0;
            bottom: 0;
            padding: 4px 8px;
        }

        .exx-err {
            display: flex;
            gap: 5px;
            align-items: center;
            font-size: 10.5px;
            color: var(--danger);
            margin-top: 4px;
            font-weight: 600;
        }

        /* amount headline */
        .exx-amount-card {
            position: relative;
            overflow: hidden;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--r-lg);
            box-shadow: var(--shadow-sm);
            padding: 9px 14px 9px 16px;
            margin-bottom: 10px;
        }

        .exx-amount-card::before {
            content: "";
            position: absolute;
            inset-inline-start: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: linear-gradient(180deg, var(--brand), var(--brand-400));
        }

        .exx-amount-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 2px;
        }

        .exx-amount-line {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .exx-amount-code {
            flex: 0 0 auto;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .1em;
            color: var(--brand-ink);
            background: rgba(var(--brand-rgb), .10);
            border: 1px solid rgba(var(--brand-rgb), .22);
            border-radius: 7px;
            padding: 5px 9px;
        }

        .exx-amount-big {
            flex: 1;
            min-width: 0;
            width: 100%;
            border: none;
            outline: none;
            background: transparent;
            font-family: inherit;
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -.03em;
            line-height: 1.15;
            color: var(--text);
            padding: 1px 0;
            font-variant-numeric: tabular-nums;
            border-bottom: 2px solid var(--border);
            border-radius: 0;
            box-shadow: none;
            transition: border-color .15s ease;
        }

        .exx-amount-big::placeholder {
            color: var(--text-3);
            font-weight: 600;
        }

        .exx-amount-big:focus {
            border-bottom-color: var(--brand);
            outline: none;
            box-shadow: none;
        }

        .exx-amount-big::-webkit-outer-spin-button,
        .exx-amount-big::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .exx-amount-big[type=number] {
            -moz-appearance: textfield;
        }

        .exx-amount-card.is-invalid .exx-amount-big {
            border-bottom-color: var(--danger);
        }

        /* quick chips */
        .exx-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            align-items: center;
        }

        .exx-chips-k {
            font-size: 9.5px;
            color: var(--text-3);
            font-weight: 600;
        }

        .exx-chip {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-family: inherit;
            font-size: 10.5px;
            font-weight: 600;
            padding: 4px 9px;
            border-radius: 999px;
            cursor: pointer;
            line-height: 1.2;
            color: var(--text-2);
            background: var(--surface);
            border: 1px solid var(--border);
            transition: border-color .15s ease, background .15s ease, color .15s ease;
        }

        .exx-chip:hover {
            border-color: var(--border-strong);
            color: var(--text);
        }

        .exx-chip.on {
            color: var(--brand-ink);
            background: rgba(var(--brand-rgb), .10);
            border-color: rgba(var(--brand-rgb), .35);
        }

        .exx-chip .fa-check {
            display: none;
        }

        .exx-chip.on .fa-check {
            display: inline-block;
        }

        /* flow rows */
        .exx-rows {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--r-lg);
            box-shadow: var(--shadow-sm);
            margin-bottom: 10px;
        }

        .exx-row {
            display: grid;
            grid-template-columns: 140px minmax(0, 1fr);
            gap: 6px 12px;
            align-items: center;
            padding: 6px 10px;
            border-bottom: 1px solid var(--border);
            transition: background .15s ease;
        }

        .exx-row:first-child {
            border-start-start-radius: var(--r-lg);
            border-start-end-radius: var(--r-lg);
        }

        .exx-row:last-child {
            border-bottom: none;
            border-end-start-radius: var(--r-lg);
            border-end-end-radius: var(--r-lg);
        }

        .exx-row:focus-within {
            background: rgba(var(--brand-rgb), .035);
        }

        .exx-row.top {
            align-items: start;
        }

        .exx-row.top .exx-row-l {
            min-height: var(--exx-ctl);
        }

        .exx-row.full {
            grid-template-columns: 1fr;
        }

        .exx-row-l {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
        }

        .exx-row-ic {
            width: 26px;
            height: 26px;
            flex: 0 0 auto;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            background: var(--surface-3);
            color: var(--text-2);
            transition: background .15s ease, color .15s ease;
        }

        .exx-row:focus-within .exx-row-ic {
            background: rgba(var(--brand-rgb), .14);
            color: var(--brand-ink);
        }

        .exx-row-t {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: var(--text);
            line-height: 1.2;
        }

        .exx-row-r {
            min-width: 0;
        }

        /* control + chips on one line, wrapping when tight */
        .exx-inline {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 6px 8px;
        }

        .exx-inline > .exx-grow {
            flex: 1 1 200px;
            min-width: 0;
        }

        .exx-inline > .exx-fixed {
            flex: 0 1 170px;
            min-width: 150px;
        }

        .exx-pair {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        /* TomSelect reskin (category + payment method) */
        .exx .ts-wrapper {
            min-width: 0;
        }

        .exx .ts-control {
            border: 1px solid var(--border) !important;
            border-radius: var(--r-sm) !important;
            background: var(--surface) !important;
            color: var(--text) !important;
            min-height: var(--exx-ctl);
            padding: 6px 10px !important;
            box-shadow: none !important;
            font-size: var(--exx-fz);
            line-height: 1.4;
        }

        .exx .ts-wrapper:hover .ts-control {
            border-color: var(--border-strong) !important;
        }

        .exx .ts-wrapper.focus .ts-control {
            border-color: var(--brand-400) !important;
            box-shadow: 0 0 0 3px rgba(var(--brand-rgb), .16) !important;
        }

        .exx .ts-control .item {
            background: transparent !important;
            color: var(--text) !important;
            font-weight: 600;
        }

        .exx .ts-control .item .remove {
            border-inline-start: 1px solid var(--border) !important;
            color: var(--text-3);
            padding: 0 6px;
        }

        .exx .ts-control .item .remove:hover {
            background: transparent !important;
            color: var(--danger);
        }

        .exx .ts-control input {
            color: var(--text) !important;
            font-size: var(--exx-fz) !important;
        }

        .exx .ts-control input::placeholder {
            color: var(--text-3);
        }

        .exx .ts-wrapper.single .ts-control::after {
            border-top-color: var(--text-3);
            inset-inline-end: 12px;
        }

        .exx .ts-dropdown {
            border: 1px solid var(--border) !important;
            background: var(--surface) !important;
            color: var(--text) !important;
            border-radius: var(--r-sm) !important;
            box-shadow: 0 16px 40px -16px rgba(16, 24, 40, .4) !important;
            font-size: var(--exx-fz);
        }

        .exx .ts-dropdown .option {
            padding: 7px 10px;
        }

        .exx .ts-dropdown .option.active {
            background: rgba(var(--brand-rgb), .12) !important;
            color: var(--brand-ink) !important;
        }

        /* slim "what will be recorded" line */
        .exx-sentence {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 6px 10px;
            border-radius: var(--r-md);
            background: linear-gradient(110deg, rgba(var(--brand-rgb), .09), var(--surface) 70%);
            border: 1px solid rgba(var(--brand-rgb), .22);
        }

        .exx-sentence-ic {
            width: 24px;
            height: 24px;
            flex: 0 0 auto;
            border-radius: 7px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(var(--brand-rgb), .14);
            color: var(--brand-ink);
            font-size: 11px;
        }

        .exx-sentence-t {
            font-size: 11.5px;
            color: var(--text-2);
            line-height: 1.45;
            min-width: 0;
        }

        .exx-sentence-t b {
            color: var(--text);
            font-weight: 700;
        }

        .exx-sentence-t b.empty {
            color: var(--text-3);
            font-weight: 600;
            border-bottom: 1px dashed var(--border-strong);
        }

        /* ═══════════  FOOTER  ═══════════ */
        .exx-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 9px 12px;
            background: var(--surface);
            border-top: 1px solid var(--border);
        }

        .exx-footer-right {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 0 0 auto;
        }

        .exx-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-family: inherit;
            font-size: 12px;
            font-weight: 700;
            padding: 8px 14px;
            border-radius: 8px;
            cursor: pointer;
            border: 1px solid transparent;
            white-space: nowrap;
            transition: background .15s ease, border-color .15s ease, color .15s ease, box-shadow .15s ease, transform .1s ease;
        }

        .exx-btn:active {
            transform: translateY(1px);
        }

        .exx-btn.ghost {
            background: var(--surface);
            border-color: var(--border);
            color: var(--text-2);
        }

        .exx-btn.ghost:hover {
            background: var(--surface-2);
            border-color: var(--border-strong);
            color: var(--text);
        }

        .exx-btn.soft {
            background: var(--success-bg);
            color: var(--success);
            border-color: rgba(var(--success-rgb), .3);
        }

        .exx-btn.soft:hover {
            background: color-mix(in srgb, var(--success-bg), var(--success) 12%);
            border-color: rgba(var(--success-rgb), .5);
        }

        .exx-btn.primary {
            color: #fff;
            border: none;
            background: linear-gradient(120deg, var(--brand), var(--brand-600));
            box-shadow: 0 8px 18px -7px rgba(var(--brand-rgb), .6);
        }

        .exx-btn.primary:hover {
            background: linear-gradient(120deg, var(--brand-600), var(--brand-700));
            box-shadow: 0 10px 22px -7px rgba(var(--brand-rgb), .7);
        }

        .exx-btn:disabled {
            opacity: .65;
            cursor: not-allowed;
        }

        .exx-kbd {
            font: 700 9.5px/1 ui-monospace, SFMono-Regular, Menlo, monospace;
            padding: 3px 5px;
            border-radius: 5px;
            background: rgba(255, 255, 255, .22);
            border: 1px solid rgba(255, 255, 255, .28);
        }

        /* ═══════════  RESPONSIVE  ═══════════ */
        @media (max-width: 720px) {
            .exx-row {
                grid-template-columns: 1fr;
            }

            .exx-row.top .exx-row-l {
                min-height: 0;
            }

            .exx-pair {
                grid-template-columns: 1fr;
            }

            .exx-footer {
                flex-direction: column-reverse;
                align-items: stretch;
            }

            .exx-footer-right {
                flex-direction: column-reverse;
                align-items: stretch;
            }

            .exx-btn {
                justify-content: center;
            }
        }
    </style>
    @push('scripts')
        <script>
            /* Shared behaviour for the Expense / Income "Flow" modals: live summary line, character
               counters, Today / Yesterday chips, Recent-category chips, focus handling. Each page calls
               window.exxForm({...}) once with its own modal selector, element ids and journal keys. */
            (function() {
                function fmtAmount(raw, decimals) {
                    var n = parseFloat(raw);
                    if (isNaN(n)) return null;
                    return n.toLocaleString('en-US', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
                }

                function fmtDate(iso) {
                    if (!iso) return '—';
                    var d = new Date(iso + 'T00:00:00');
                    if (isNaN(d)) return iso;
                    return d.toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
                }

                function shiftDate(iso, days) {
                    var d = new Date(iso + 'T00:00:00');
                    d.setDate(d.getDate() + days);
                    var m = String(d.getMonth() + 1).padStart(2, '0'), day = String(d.getDate()).padStart(2, '0');
                    return d.getFullYear() + '-' + m + '-' + day;
                }

                window.exxForm = function(cfg) {
                    var ids = cfg.ids;
                    var categoryProp = 'journals.' + cfg.categoryKey;
                    var methodProp = 'journals.' + cfg.methodKey;

                    function modal() {
                        return document.querySelector(cfg.modal);
                    }

                    function root() {
                        var m = modal();
                        return m ? m.querySelector('.exx') : null;
                    }

                    function el(id) {
                        var m = modal();
                        return m ? m.querySelector('#' + id) : null;
                    }

                    function setProp(key, value) {
                        var wire = window.Livewire ? window.Livewire.find(cfg.wireId) : null;
                        if (wire) wire.set(key, value);
                    }

                    function selectedName(id) {
                        var node = el(id);
                        if (!node) return '';
                        var ts = node.tomselect;
                        if (ts) {
                            var v = ts.getValue();
                            var o = v ? ts.options[v] : null;
                            return o ? (o.name || o.text || '') : '';
                        }
                        var opt = node.options ? node.options[node.selectedIndex] : null;
                        return opt && opt.value ? opt.text : '';
                    }

                    function setOut(r, key, value, emptyText) {
                        r.querySelectorAll('[data-exx-out="' + key + '"]').forEach(function(node) {
                            if (value) {
                                node.textContent = value;
                                node.classList.remove('empty');
                            } else if (emptyText !== undefined) {
                                node.textContent = emptyText;
                                node.classList.add('empty');
                            } else {
                                node.textContent = '';
                            }
                        });
                    }

                    function refresh() {
                        var r = root();
                        if (!r) return;
                        var cat = selectedName(ids.category);
                        var method = selectedName(ids.method);
                        var amountEl = el(ids.amount);
                        var raw = amountEl ? amountEl.value : '';
                        var amount = fmtAmount(raw, cfg.decimals);
                        var dateEl = el(ids.date);
                        var date = dateEl ? dateEl.value : '';

                        setOut(r, 'amount', amount !== null && parseFloat(raw) >= 1 ? (cfg.currency + ' ' + amount).trim() : null, 'an amount');
                        setOut(r, 'cat', cat, 'a category');
                        setOut(r, 'method', method, 'a payment method');
                        setOut(r, 'date', fmtDate(date));

                        r.querySelectorAll('.exx-date-chip').forEach(function(chip) {
                            chip.classList.toggle('on', !!date && date === shiftDate(cfg.today, parseInt(chip.dataset.shift, 10)));
                        });
                        var catEl = el(ids.category);
                        var catValue = catEl && catEl.tomselect ? String(catEl.tomselect.getValue()) : '';
                        r.querySelectorAll('.exx-recent').forEach(function(chip) {
                            chip.classList.toggle('on', !!catValue && chip.dataset.id === catValue);
                        });
                        r.querySelectorAll('[data-exx-count]').forEach(function(input) {
                            var out = r.querySelector('[data-exx-count-out="' + input.dataset.exxCount + '"]');
                            var max = parseInt(input.getAttribute('maxlength'), 10) || 0;
                            if (!out) return;
                            out.textContent = input.value.length + '/' + max;
                            out.classList.toggle('warn', max > 0 && input.value.length >= max - 5);
                        });
                    }

                    function refreshSoon() {
                        setTimeout(refresh, 0);
                        setTimeout(refresh, 150);
                    }

                    var scope = cfg.modal + ' ';

                    // live typing
                    $(document).on('input change', scope + '#' + ids.amount + ', ' + scope + '#' + ids.date + ', ' + scope + '[data-exx-count]', refresh);

                    // Today / Yesterday — write the input and let wire:model pick it up
                    $(document).on('click', scope + '.exx-date-chip', function() {
                        var input = el(ids.date);
                        if (!input) return;
                        input.value = shiftDate(cfg.today, parseInt(this.dataset.shift, 10));
                        input.dispatchEvent(new Event('input', { bubbles: true }));
                        refresh();
                    });

                    // Recent category chips → select in TomSelect (its change handler syncs Livewire)
                    $(document).on('click', scope + '.exx-recent', function() {
                        var node = el(ids.category);
                        var ts = node && node.tomselect;
                        if (!ts) return;
                        ts.addOption({ id: this.dataset.id, name: this.dataset.name });
                        ts.setValue(this.dataset.id);
                    });

                    $(el(ids.category)).on('change', function() {
                        setProp(categoryProp, $(this).val() || null);
                        var amount = el(ids.amount);
                        if (amount && !parseFloat(amount.value)) amount.focus();
                        refresh();
                    });
                    $(el(ids.method)).on('change', function() {
                        setProp(methodProp, $(this).val() || null);
                        refresh();
                    });

                    // fired by create() and save(): preselect (or clear) the category TomSelect
                    window.addEventListener('SelectDropDownValues', function(event) {
                        var journals = (event.detail && event.detail[0]) || {};
                        var value = journals[cfg.categoryKey];
                        var node = el(ids.category);
                        var ts = node && node.tomselect;
                        if (ts) {
                            if (value) {
                                ts.addOption({ id: value, name: journals[cfg.categoryKey + '_name'] });
                                ts.addItem(value, true);
                                setProp(categoryProp, value);
                            } else {
                                ts.clear(true);
                            }
                        }
                        refreshSoon();
                    });
                    window.addEventListener(cfg.toggleEvent, refreshSoon);
                    $(cfg.modal).on('shown.bs.modal', function() {
                        refresh();
                        var amount = el(ids.amount);
                        if (amount) {
                            amount.focus();
                            amount.select();
                        }
                    });
                    refresh();
                };
            })();
        </script>
    @endpush
@endonce

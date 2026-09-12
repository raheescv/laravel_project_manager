<div class="exx">
    <style>
        /* ════════════  Expense form — "Premium" design system (scoped under .exx)  ════════════
           Compact "Flow" layout: the amount is the headline, then one row per question, everything
           sized so the whole form (Description included) fits a laptop viewport without scrolling.
           Colour derives from the active settings theme (--bs-primary) and tracks dark mode, sibling of
           the Branch (.brx), Employee (.empx) and General Voucher (.gvx) systems. Font Awesome 4 only;
           logical properties throughout so RTL needs no overrides. Preview: docs/expense-modal-premium-preview.html */

        #ExpenseModal .modal-dialog {
            max-width: 680px;
        }

        #ExpenseModal .modal-content {
            border: none;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 28px 70px -24px rgba(16, 24, 40, .55);
        }

        [data-bs-theme="dark"] #ExpenseModal .modal-content {
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

    @php
        $currencyCode = base_currency()['code'] ?? null;
        $amountValue = (float) ($journals['amount'] ?? 0);
        $dateLabel = ! empty($journals['date']) ? \Carbon\Carbon::parse($journals['date'])->format('D, j M Y') : '—';
    @endphp

    <!-- ═══════════  HERO  ═══════════ -->
    <div class="exx-hero">
        <span class="exx-glow a"></span>
        <span class="exx-glow b"></span>
        <div class="exx-hero-row">
            <div class="exx-hero-id">
                <span class="exx-hero-ic">
                    <i class="fa {{ $table_id ? 'fa-pencil-square-o' : 'fa-money' }}"></i>
                </span>
                <div>
                    <div class="exx-eyebrow">Accounts &middot; Expense</div>
                    <h1 class="exx-hero-title">{{ $table_id ? 'Edit Expense' : 'Record Expense' }}</h1>
                </div>
            </div>
            <div class="exx-hero-tools">
                <span class="exx-pill">
                    <span class="dot"></span>
                    {{ $table_id ? 'Editing #'.$table_id : 'New' }}
                </span>
                <button type="button" class="exx-x" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa fa-times"></i>
                </button>
            </div>
        </div>
    </div>

    <form wire:submit="save">
        <!-- ═══════════  BODY  ═══════════ -->
        <div class="exx-body">
            @if ($this->getErrorBag()->count())
                <div class="exx-errors">
                    <i class="fa fa-exclamation-triangle exx-errors-ic"></i>
                    <div>
                        <div class="exx-errors-title">Please correct the following:</div>
                        <ul class="exx-errors-list">
                            @foreach ($this->getErrorBag()->toArray() as $field => $fieldErrors)
                                <li>{{ $fieldErrors[0] }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <!-- Amount headline -->
            <div class="exx-amount-card @error('journals.amount') is-invalid @enderror">
                <div class="exx-amount-top">
                    <label for="journal_amount" class="exx-label"><i class="fa fa-money"></i> Amount <span class="req">*</span></label>
                    <span class="exx-count">total paid incl. tax &middot; min 1 &middot; max 999,999</span>
                </div>
                <div class="exx-amount-line">
                    <span class="exx-amount-code">{{ $currencyCode ?: '' }}@if (! $currencyCode)<i class="fa fa-money"></i>@endif</span>
                    <input type="number" id="journal_amount" class="exx-amount-big" inputmode="decimal" step="0.01" min="1" max="999999" placeholder="0.00" wire:model="journals.amount" autocomplete="off">
                </div>
                @error('journals.amount')
                    <div class="exx-err"><i class="fa fa-exclamation-circle"></i> {{ $message }}</div>
                @enderror
            </div>

            <!-- One row per question -->
            <div class="exx-rows">
                <div class="exx-row top">
                    <div class="exx-row-l">
                        <span class="exx-row-ic"><i class="fa fa-tags"></i></span>
                        <span class="exx-row-t">Category <span class="req">*</span></span>
                    </div>
                    <div class="exx-row-r">
                        <div class="exx-inline">
                            <div class="exx-grow" wire:ignore>
                                <select id="category_id" class="select-account_id" account_type="expense" placeholder="Search expense category…" wire:model="journals.debit"></select>
                            </div>
                            @if (count($recentCategories))
                                <div class="exx-chips">
                                    <span class="exx-chips-k">Recent</span>
                                    @foreach ($recentCategories as $recent)
                                        <button type="button" class="exx-chip exx-recent {{ ($journals['debit'] ?? null) == $recent['id'] ? 'on' : '' }}" data-id="{{ $recent['id'] }}" data-name="{{ $recent['name'] }}">
                                            <i class="fa fa-check"></i>{{ $recent['name'] }}
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        @error('journals.debit')
                            <div class="exx-err"><i class="fa fa-exclamation-circle"></i> {{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="exx-row top">
                    <div class="exx-row-l">
                        <span class="exx-row-ic"><i class="fa fa-credit-card"></i></span>
                        <span class="exx-row-t">Paid from <span class="req">*</span></span>
                    </div>
                    <div class="exx-row-r">
                        <div wire:ignore>
                            <select id="payment_method_id" class="select-payment_method_id-list" placeholder="Choose payment method…" wire:model="journals.credit">
                                @foreach ($paymentMethods ?? [] as $id => $name)
                                    <option value="{{ $id }}" @selected($id == ($default_payment_method_id ?? null))>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @error('journals.credit')
                            <div class="exx-err"><i class="fa fa-exclamation-circle"></i> {{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="exx-row top">
                    <div class="exx-row-l">
                        <span class="exx-row-ic"><i class="fa fa-calendar"></i></span>
                        <span class="exx-row-t">Date <span class="req">*</span></span>
                    </div>
                    <div class="exx-row-r">
                        <div class="exx-inline">
                            <div class="exx-input exx-fixed @error('journals.date') is-invalid @enderror">
                                <input type="date" id="expense_date" class="exx-control" wire:model="journals.date">
                            </div>
                            <div class="exx-chips" wire:ignore>
                                <button type="button" class="exx-chip exx-date-chip" data-shift="0"><i class="fa fa-check"></i>Today</button>
                                <button type="button" class="exx-chip exx-date-chip" data-shift="-1"><i class="fa fa-check"></i>Yesterday</button>
                            </div>
                        </div>
                        @error('journals.date')
                            <div class="exx-err"><i class="fa fa-exclamation-circle"></i> {{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="exx-row full">
                    <div class="exx-pair">
                        <div>
                            <div class="exx-input @error('journals.person_name') is-invalid @enderror">
                                <span class="exx-prefix"><i class="fa fa-user"></i> Payee</span>
                                <input type="text" id="person_name" class="exx-control" maxlength="30" placeholder="Vendor or person" data-exx-count="payee" wire:model="journals.person_name" autocomplete="off">
                                <span class="exx-suffix exx-count" wire:ignore data-exx-count-out="payee">{{ mb_strlen($journals['person_name'] ?? '') }}/30</span>
                            </div>
                            @error('journals.person_name')
                                <div class="exx-err"><i class="fa fa-exclamation-circle"></i> {{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            <div class="exx-input @error('journals.reference_number') is-invalid @enderror">
                                <span class="exx-prefix"><i class="fa fa-hashtag"></i> Ref</span>
                                <input type="text" id="reference_number" class="exx-control" maxlength="30" placeholder="Bill / receipt no." data-exx-count="ref" wire:model="journals.reference_number" autocomplete="off">
                                <span class="exx-suffix exx-count" wire:ignore data-exx-count-out="ref">{{ mb_strlen($journals['reference_number'] ?? '') }}/30</span>
                            </div>
                            @error('journals.reference_number')
                                <div class="exx-err"><i class="fa fa-exclamation-circle"></i> {{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="exx-row top">
                    <div class="exx-row-l">
                        <span class="exx-row-ic"><i class="fa fa-pencil"></i></span>
                        <span class="exx-row-t">Description <span class="req">*</span></span>
                    </div>
                    <div class="exx-row-r">
                        <div class="exx-input area @error('journals.description') is-invalid @enderror">
                            <textarea id="description" class="exx-control" rows="2" maxlength="100" placeholder="e.g. A4 paper and toner for the front desk" data-exx-count="desc" wire:model="journals.description"></textarea>
                            <span class="exx-suffix exx-count" wire:ignore data-exx-count-out="desc">{{ mb_strlen($journals['description'] ?? '') }}/100</span>
                        </div>
                        @error('journals.description')
                            <div class="exx-err"><i class="fa fa-exclamation-circle"></i> {{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- What will be recorded (kept by JS; server paints the first state) -->
            <div class="exx-sentence" wire:ignore>
                <span class="exx-sentence-ic"><i class="fa fa-book"></i></span>
                <div class="exx-sentence-t">
                    Recording
                    <b data-exx-out="amount" class="{{ $amountValue >= 1 ? '' : 'empty' }}">{{ $amountValue >= 1 ? trim($currencyCode.' '.currency($amountValue)) : 'an amount' }}</b>
                    for
                    <b data-exx-out="cat" class="{{ ! empty($journals['debit_name']) ? '' : 'empty' }}">{{ $journals['debit_name'] ?? 'a category' }}</b>,
                    paid from
                    <b data-exx-out="method" class="{{ ! empty($journals['credit_name']) ? '' : 'empty' }}">{{ $journals['credit_name'] ?? 'a payment method' }}</b>
                    on
                    <b data-exx-out="date">{{ $dateLabel }}</b>.
                </div>
            </div>
        </div>

        <!-- ═══════════  FOOTER  ═══════════ -->
        <div class="exx-footer">
            <button type="button" class="exx-btn ghost" data-bs-dismiss="modal">
                <i class="fa fa-times"></i> Cancel
            </button>
            <div class="exx-footer-right">
                @if (! $table_id)
                    <button type="button" wire:click="save(1)" class="exx-btn soft" wire:loading.attr="disabled" wire:target="save">
                        <i class="fa fa-plus"></i> Save &amp; Add New
                    </button>
                @endif
                <button type="submit" class="exx-btn primary" wire:loading.attr="disabled" wire:target="save">
                    <i class="fa fa-check" wire:loading.remove wire:target="save"></i>
                    <i class="fa fa-spinner fa-spin" wire:loading wire:target="save"></i>
                    {{ $table_id ? 'Update Expense' : 'Save Expense' }}
                    <span class="exx-kbd">&#9166;</span>
                </button>
            </div>
        </div>
    </form>

    @push('scripts')
        <script>
            $(document).ready(function() {
                var EXX_TODAY = @json(date('Y-m-d'));
                var EXX_CUR = @json($currencyCode ?? '');
                var EXX_DEC = @json(currency_decimals());
                var EXX_WIRE_ID = @json($this->getId());

                // Look the component up by id so this pushed block never depends on directive compilation.
                function exxWire() {
                    return window.Livewire ? window.Livewire.find(EXX_WIRE_ID) : null;
                }

                function exxSet(key, value) {
                    var wire = exxWire();
                    if (wire) wire.set(key, value);
                }

                function exxRoot() {
                    return document.querySelector('#ExpenseModal .exx') || document.querySelector('.exx');
                }

                function exxSelectedName(id) {
                    var el = document.getElementById(id);
                    if (!el) return '';
                    var ts = el.tomselect;
                    if (ts) {
                        var v = ts.getValue();
                        var o = v ? ts.options[v] : null;
                        return o ? (o.name || o.text || '') : '';
                    }
                    var opt = el.options[el.selectedIndex];
                    return opt && opt.value ? opt.text : '';
                }

                function exxFormatAmount(raw) {
                    var n = parseFloat(raw);
                    if (isNaN(n)) return null;
                    return n.toLocaleString('en-US', { minimumFractionDigits: EXX_DEC, maximumFractionDigits: EXX_DEC });
                }

                function exxFormatDate(iso) {
                    if (!iso) return '—';
                    var d = new Date(iso + 'T00:00:00');
                    if (isNaN(d)) return iso;
                    return d.toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
                }

                function exxShiftDate(iso, days) {
                    var d = new Date(iso + 'T00:00:00');
                    d.setDate(d.getDate() + days);
                    var m = String(d.getMonth() + 1).padStart(2, '0'), day = String(d.getDate()).padStart(2, '0');
                    return d.getFullYear() + '-' + m + '-' + day;
                }

                function exxSetOut(root, key, value, emptyText) {
                    root.querySelectorAll('[data-exx-out="' + key + '"]').forEach(function(el) {
                        if (value) {
                            el.textContent = value;
                            el.classList.remove('empty');
                        } else if (emptyText !== undefined) {
                            el.textContent = emptyText;
                            el.classList.add('empty');
                        } else {
                            el.textContent = '';
                        }
                    });
                }

                function exxRefresh() {
                    var root = exxRoot();
                    if (!root) return;
                    var cat = exxSelectedName('category_id');
                    var method = exxSelectedName('payment_method_id');
                    var amountEl = document.getElementById('journal_amount');
                    var raw = amountEl ? amountEl.value : '';
                    var amount = exxFormatAmount(raw);
                    var dateEl = document.getElementById('expense_date');
                    var date = dateEl ? dateEl.value : '';

                    exxSetOut(root, 'amount', amount !== null && parseFloat(raw) >= 1 ? (EXX_CUR + ' ' + amount).trim() : null, 'an amount');
                    exxSetOut(root, 'cat', cat, 'a category');
                    exxSetOut(root, 'method', method, 'a payment method');
                    exxSetOut(root, 'date', exxFormatDate(date));

                    root.querySelectorAll('.exx-date-chip').forEach(function(chip) {
                        chip.classList.toggle('on', !!date && date === exxShiftDate(EXX_TODAY, parseInt(chip.dataset.shift, 10)));
                    });
                    var catEl = document.getElementById('category_id');
                    var catValue = catEl && catEl.tomselect ? String(catEl.tomselect.getValue()) : '';
                    root.querySelectorAll('.exx-recent').forEach(function(chip) {
                        chip.classList.toggle('on', !!catValue && chip.dataset.id === catValue);
                    });

                    root.querySelectorAll('[data-exx-count]').forEach(function(input) {
                        var out = root.querySelector('[data-exx-count-out="' + input.dataset.exxCount + '"]');
                        var max = parseInt(input.getAttribute('maxlength'), 10) || 0;
                        if (!out) return;
                        out.textContent = input.value.length + '/' + max;
                        out.classList.toggle('warn', max > 0 && input.value.length >= max - 5);
                    });
                }

                function exxRefreshSoon() {
                    setTimeout(exxRefresh, 0);
                    setTimeout(exxRefresh, 150);
                }

                // live typing
                $(document).on('input change', '#ExpenseModal #journal_amount, #ExpenseModal #expense_date, #ExpenseModal [data-exx-count]', exxRefresh);

                // Today / Yesterday — write the input and let wire:model pick it up
                $(document).on('click', '#ExpenseModal .exx-date-chip', function() {
                    var input = document.getElementById('expense_date');
                    if (!input) return;
                    input.value = exxShiftDate(EXX_TODAY, parseInt(this.dataset.shift, 10));
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    exxRefresh();
                });

                // Recent category chips → select in TomSelect (its change handler syncs Livewire)
                $(document).on('click', '#ExpenseModal .exx-recent', function() {
                    var el = document.getElementById('category_id');
                    var ts = el && el.tomselect;
                    if (!ts) return;
                    ts.addOption({ id: this.dataset.id, name: this.dataset.name });
                    ts.setValue(this.dataset.id);
                });

                $('#category_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    exxSet('journals.debit', value);
                    if (!parseFloat($('#journal_amount').val())) {
                        $('#journal_amount').trigger('focus');
                    }
                    exxRefresh();
                });
                $('#payment_method_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    exxSet('journals.credit', value);
                    exxRefresh();
                });
                window.addEventListener('SelectDropDownValues', event => {
                    var journals = event.detail[0] || {};
                    exxSet('journals.debit', journals.debit);
                    var tomSelectInstance = document.querySelector('#category_id').tomselect;
                    if (!tomSelectInstance) {
                        exxRefreshSoon();
                        return;
                    }
                    if (journals.debit) {
                        var preselectedData = {
                            id: journals.debit,
                            name: journals.debit_name,
                        };
                        tomSelectInstance.addOption(preselectedData);
                        tomSelectInstance.addItem(preselectedData.id, true);
                    } else {
                        tomSelectInstance.clear(true);
                    }
                    exxRefreshSoon();
                });
                window.addEventListener('ToggleExpenseModal', exxRefreshSoon);
                $('#ExpenseModal').on('shown.bs.modal', function() {
                    exxRefresh();
                    var amount = document.getElementById('journal_amount');
                    if (amount) {
                        amount.focus();
                        amount.select();
                    }
                });
                exxRefresh();
            });
        </script>
    @endpush
</div>

{{--
    ".pix" — the premium layer for the Purchase Invoice Uploader.

    A sibling of the ".pcx" Command Deck used by purchase create/edit: same
    accent source (--bs-primary from Settings → Colors), same compact density,
    same sticky-deck-plus-rail skeleton. Scoped entirely to `.pix` so nothing
    can leak into the rest of the admin. Include once per page:

        <x-purchase.import-premium />
--}}
@once
    <style>
        .pix {
            /* --- semantic: never rebranded --- */
            --pix-ok: #16a34a;
            --pix-ok-rgb: 22, 163, 74;
            --pix-bad: #dc2626;
            --pix-bad-rgb: 220, 38, 38;
            --pix-warn: #d97706;
            --pix-warn-rgb: 217, 119, 6;

            /* --- accent: the app theme colour --- */
            --pix-acc: var(--bs-primary, #4f46e5);
            --pix-acc-rgb: var(--bs-primary-rgb, 79, 70, 229);
            --pix-acc-d: color-mix(in srgb, var(--pix-acc), #000 18%);
            --pix-acc-ink: var(--pix-acc-d);
            --pix-tint: color-mix(in srgb, var(--pix-acc), transparent 91%);
            --pix-tint-2: color-mix(in srgb, var(--pix-acc), transparent 96%);
            --pix-line-acc: color-mix(in srgb, var(--pix-acc), transparent 76%);

            /* --- surfaces (light) --- */
            --pix-sf: #fff;
            --pix-sf2: #f7f9fc;
            --pix-sf3: #eef2f7;
            --pix-ink: #0b1220;
            --pix-ink2: #334155;
            --pix-mut: #748196;
            --pix-ln: #e3e8ef;
            --pix-lns: #eef1f6;
            --pix-shadow: 0 1px 2px rgba(16, 24, 40, .04), 0 10px 24px -18px rgba(16, 24, 40, .3);
            --pix-shadow-lg: 0 1px 2px rgba(16, 24, 40, .05), 0 16px 34px -22px rgba(16, 24, 40, .38);

            --pix-h-field: 35px;
            --pix-r: 12px;
            --pix-mono: ui-monospace, Menlo, Consolas, "DejaVu Sans Mono", monospace;

            font-size: 12.5px;
            color: var(--pix-ink);
        }

        [data-bs-theme="dark"] .pix {
            --pix-acc-d: color-mix(in srgb, var(--pix-acc), #fff 12%);
            --pix-acc-ink: color-mix(in srgb, var(--pix-acc), #fff 28%);
            --pix-tint: color-mix(in srgb, var(--pix-acc), transparent 86%);
            --pix-tint-2: color-mix(in srgb, var(--pix-acc), transparent 93%);
            --pix-line-acc: color-mix(in srgb, var(--pix-acc), transparent 68%);

            --pix-sf: #121826;
            --pix-sf2: #0e1420;
            --pix-sf3: #1a2233;
            --pix-ink: #e7ecf3;
            --pix-ink2: #b9c3d3;
            --pix-mut: #8794a8;
            --pix-ln: #1f2937;
            --pix-lns: #182031;
            --pix-shadow: 0 1px 2px rgba(0, 0, 0, .4), 0 10px 26px -18px rgba(0, 0, 0, .8);
            --pix-shadow-lg: 0 1px 2px rgba(0, 0, 0, .45), 0 18px 38px -22px rgba(0, 0, 0, .9);
        }

        /* ============================================================ deck === */

        .pix-deck {
            position: sticky;
            top: .5rem;
            z-index: 30;
            background: linear-gradient(180deg, var(--pix-sf), var(--pix-sf2));
            border: 1px solid var(--pix-ln);
            border-radius: var(--pix-r);
            box-shadow: var(--pix-shadow-lg);
            padding: .7rem .9rem;
            margin-bottom: .9rem;
        }

        .hd--sticky .pix-deck {
            top: 4.6rem;
        }

        .pix-deck__top {
            display: flex;
            align-items: center;
            gap: .9rem;
            flex-wrap: wrap;
        }

        .pix-deck__title {
            display: flex;
            align-items: center;
            gap: .55rem;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: -.01em;
            margin: 0;
        }

        .pix-deck__title i {
            width: 28px;
            height: 28px;
            display: grid;
            place-items: center;
            border-radius: 9px;
            background: var(--pix-tint);
            color: var(--pix-acc-ink);
            border: 1px solid var(--pix-line-acc);
            font-size: 13px;
        }

        .pix-deck__sub {
            color: var(--pix-mut);
            font-size: 11.5px;
            margin: .15rem 0 0 0;
        }

        .pix-deck__spacer {
            flex: 1 1 auto;
        }

        .pix-deck__actions {
            display: flex;
            align-items: center;
            gap: .4rem;
            flex-wrap: wrap;
        }

        /* --------------------------------------------------------- stepper --- */

        .pix-steps {
            display: flex;
            align-items: center;
            gap: .25rem;
            flex-wrap: wrap;
        }

        .pix-step {
            display: flex;
            align-items: center;
            gap: .45rem;
            padding: .3rem .6rem .3rem .35rem;
            border-radius: 999px;
            border: 1px solid transparent;
            color: var(--pix-mut);
            font-weight: 600;
            font-size: 11.5px;
            white-space: nowrap;
        }

        .pix-step__n {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: var(--pix-sf3);
            color: var(--pix-mut);
            font-size: 10.5px;
            font-weight: 700;
            border: 1px solid var(--pix-ln);
        }

        .pix-step.is-done {
            color: var(--pix-ink2);
        }

        .pix-step.is-done .pix-step__n {
            background: color-mix(in srgb, var(--pix-ok), transparent 88%);
            border-color: color-mix(in srgb, var(--pix-ok), transparent 65%);
            color: var(--pix-ok);
        }

        .pix-step.is-active {
            color: var(--pix-acc-ink);
            background: var(--pix-tint);
            border-color: var(--pix-line-acc);
        }

        .pix-step.is-active .pix-step__n {
            background: var(--pix-acc);
            border-color: var(--pix-acc);
            color: #fff;
        }

        .pix-steps__sep {
            width: 14px;
            height: 1px;
            background: var(--pix-ln);
        }

        /* -------------------------------------------------------- stat pod --- */

        .pix-pods {
            display: flex;
            gap: .4rem;
            flex-wrap: wrap;
            margin-top: .65rem;
            padding-top: .6rem;
            border-top: 1px dashed var(--pix-ln);
        }

        .pix-pod {
            min-width: 92px;
            padding: .35rem .6rem;
            border-radius: 9px;
            background: var(--pix-sf2);
            border: 1px solid var(--pix-lns);
        }

        .pix-pod__k {
            display: block;
            font-size: 9.5px;
            letter-spacing: .07em;
            text-transform: uppercase;
            color: var(--pix-mut);
            font-weight: 700;
        }

        .pix-pod__v {
            display: block;
            font-size: 13.5px;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
            line-height: 1.25;
        }

        .pix-pod--acc {
            background: var(--pix-tint);
            border-color: var(--pix-line-acc);
        }

        .pix-pod--acc .pix-pod__v {
            color: var(--pix-acc-ink);
        }

        .pix-pod--ok .pix-pod__v {
            color: var(--pix-ok);
        }

        .pix-pod--bad .pix-pod__v {
            color: var(--pix-bad);
        }

        /* =========================================================== panel === */

        .pix-panel {
            background: var(--pix-sf);
            border: 1px solid var(--pix-ln);
            border-radius: var(--pix-r);
            box-shadow: var(--pix-shadow);
            margin-bottom: .9rem;
            overflow: hidden;
        }

        .pix-panel__hd {
            display: flex;
            align-items: center;
            gap: .55rem;
            padding: .6rem .85rem;
            border-bottom: 1px solid var(--pix-lns);
            background: var(--pix-sf2);
        }

        .pix-panel__hd i {
            color: var(--pix-acc-ink);
        }

        .pix-panel__hd h6 {
            margin: 0;
            font-size: 12.5px;
            font-weight: 700;
        }

        .pix-panel__hd small {
            color: var(--pix-mut);
            font-size: 11px;
        }

        .pix-panel__bd {
            padding: .85rem;
        }

        .pix-panel__bd--flush {
            padding: 0;
        }

        /* =========================================================== field === */

        .pix-lbl {
            display: block;
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: .05em;
            text-transform: uppercase;
            color: var(--pix-mut);
            margin-bottom: .25rem;
        }

        .pix .form-control,
        .pix .form-select {
            min-height: var(--pix-h-field);
            height: var(--pix-h-field);
            font-size: 12.5px;
            border-radius: 8px;
            border-color: var(--pix-ln);
            background: var(--pix-sf);
            color: var(--pix-ink);
        }

        .pix textarea.form-control {
            height: auto;
        }

        .pix .form-control:focus,
        .pix .form-select:focus {
            border-color: var(--pix-acc);
            box-shadow: 0 0 0 3px rgba(var(--pix-acc-rgb), .14);
        }

        .pix .ts-wrapper .ts-control {
            min-height: var(--pix-h-field);
            border-radius: 8px;
            border-color: var(--pix-ln);
            font-size: 12.5px;
            background: var(--pix-sf);
        }

        .pix-hint {
            display: block;
            color: var(--pix-mut);
            font-size: 10.5px;
            margin-top: .2rem;
        }

        .pix-err {
            display: block;
            color: var(--pix-bad);
            font-size: 10.5px;
            margin-top: .2rem;
            font-weight: 600;
        }

        /* ============================================================ drop === */

        .pix-drop {
            position: relative;
            border: 1.5px dashed var(--pix-line-acc);
            border-radius: var(--pix-r);
            background: linear-gradient(180deg, var(--pix-tint-2), transparent);
            padding: 2rem 1rem;
            text-align: center;
            transition: border-color .15s ease, background .15s ease;
        }

        .pix-drop:hover {
            border-color: var(--pix-acc);
            background: var(--pix-tint);
        }

        .pix-drop input[type=file] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }

        .pix-drop__icon {
            width: 46px;
            height: 46px;
            margin: 0 auto .6rem;
            border-radius: 14px;
            display: grid;
            place-items: center;
            background: var(--pix-tint);
            border: 1px solid var(--pix-line-acc);
            color: var(--pix-acc-ink);
            font-size: 19px;
        }

        .pix-drop__t {
            font-weight: 700;
            font-size: 13px;
        }

        .pix-drop__s {
            color: var(--pix-mut);
            font-size: 11.5px;
        }

        .pix-file {
            display: flex;
            align-items: center;
            gap: .65rem;
            padding: .6rem .75rem;
            border-radius: 10px;
            border: 1px solid color-mix(in srgb, var(--pix-ok), transparent 70%);
            background: color-mix(in srgb, var(--pix-ok), transparent 93%);
        }

        .pix-file i.pix-file__ic {
            width: 30px;
            height: 30px;
            border-radius: 9px;
            display: grid;
            place-items: center;
            background: color-mix(in srgb, var(--pix-ok), transparent 84%);
            color: var(--pix-ok);
        }

        /* ========================================================= mapping === */

        .pix-map {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(232px, 1fr));
            gap: .55rem;
        }

        .pix-map__row {
            border: 1px solid var(--pix-ln);
            border-radius: 10px;
            padding: .55rem .6rem;
            background: var(--pix-sf2);
        }

        .pix-map__row.is-set {
            border-color: var(--pix-line-acc);
            background: var(--pix-tint-2);
        }

        .pix-map__top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .4rem;
            margin-bottom: .3rem;
        }

        .pix-map__name {
            font-weight: 700;
            font-size: 11.5px;
        }

        .pix-map__req {
            color: var(--pix-bad);
        }

        /* ========================================================== chips === */

        .pix-chip {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            padding: .12rem .45rem;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .02em;
            border: 1px solid transparent;
            white-space: nowrap;
        }

        .pix-chip--ok {
            background: color-mix(in srgb, var(--pix-ok), transparent 89%);
            border-color: color-mix(in srgb, var(--pix-ok), transparent 70%);
            color: var(--pix-ok);
        }

        .pix-chip--bad {
            background: color-mix(in srgb, var(--pix-bad), transparent 89%);
            border-color: color-mix(in srgb, var(--pix-bad), transparent 70%);
            color: var(--pix-bad);
        }

        .pix-chip--warn {
            background: color-mix(in srgb, var(--pix-warn), transparent 89%);
            border-color: color-mix(in srgb, var(--pix-warn), transparent 70%);
            color: var(--pix-warn);
        }

        .pix-chip--mut {
            background: var(--pix-sf3);
            border-color: var(--pix-ln);
            color: var(--pix-mut);
        }

        .pix-chip--acc {
            background: var(--pix-tint);
            border-color: var(--pix-line-acc);
            color: var(--pix-acc-ink);
        }

        /* ========================================================== table === */

        .pix-tblwrap {
            max-height: 62vh;
            overflow: auto;
        }

        .pix-tbl {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 12px;
        }

        .pix-tbl thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            background: var(--pix-sf2);
            border-bottom: 1px solid var(--pix-ln);
            padding: .45rem .55rem;
            font-size: 10px;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: var(--pix-mut);
            font-weight: 700;
            white-space: nowrap;
        }

        .pix-tbl tbody td {
            padding: .4rem .55rem;
            border-bottom: 1px solid var(--pix-lns);
            vertical-align: middle;
        }

        .pix-tbl tbody tr:hover td {
            background: var(--pix-tint-2);
        }

        .pix-tbl tbody tr.is-bad td {
            background: color-mix(in srgb, var(--pix-bad), transparent 96%);
        }

        .pix-tbl tbody tr.is-bad:hover td {
            background: color-mix(in srgb, var(--pix-bad), transparent 93%);
        }

        .pix-tbl .num {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        .pix-tbl__line {
            font-family: var(--pix-mono);
            font-size: 10.5px;
            color: var(--pix-mut);
        }

        .pix-tbl__name {
            font-weight: 600;
            line-height: 1.3;
        }

        .pix-tbl__meta {
            color: var(--pix-mut);
            font-size: 10.5px;
            font-family: var(--pix-mono);
        }

        .pix-inp {
            width: 100%;
            min-width: 62px;
            height: 27px;
            padding: .1rem .4rem;
            text-align: right;
            font-size: 11.5px;
            font-variant-numeric: tabular-nums;
            border: 1px solid transparent;
            border-radius: 6px;
            background: var(--pix-sf3);
            color: var(--pix-ink);
        }

        .pix-inp:focus {
            outline: none;
            background: var(--pix-sf);
            border-color: var(--pix-acc);
            box-shadow: 0 0 0 3px rgba(var(--pix-acc-rgb), .14);
        }

        .pix-iconbtn {
            width: 26px;
            height: 26px;
            display: inline-grid;
            place-items: center;
            border-radius: 7px;
            border: 1px solid var(--pix-ln);
            background: var(--pix-sf);
            color: var(--pix-mut);
            font-size: 11px;
            line-height: 1;
        }

        .pix-iconbtn:hover {
            color: var(--pix-acc-ink);
            border-color: var(--pix-line-acc);
            background: var(--pix-tint);
        }

        .pix-iconbtn--bad:hover {
            color: var(--pix-bad);
            border-color: color-mix(in srgb, var(--pix-bad), transparent 60%);
            background: color-mix(in srgb, var(--pix-bad), transparent 90%);
        }

        /* ====================================================== candidates === */

        .pix-cands {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .3rem;
            margin-top: .3rem;
        }

        .pix-cand {
            display: inline-flex;
            align-items: baseline;
            gap: .4rem;
            padding: .18rem .5rem;
            border-radius: 7px;
            border: 1px solid var(--pix-ln);
            background: var(--pix-sf);
            color: var(--pix-ink2);
            font-size: 11px;
            line-height: 1.5;
            cursor: pointer;
        }

        .pix-cand:hover {
            border-color: var(--pix-acc);
            background: var(--pix-tint);
            color: var(--pix-acc-ink);
        }

        /* the candidate whose cost equals the line's rate — the likely one */
        .pix-cand.is-rate {
            border-color: color-mix(in srgb, var(--pix-ok), transparent 55%);
            background: color-mix(in srgb, var(--pix-ok), transparent 92%);
        }

        .pix-cand__n {
            font-weight: 600;
        }

        .pix-cand__c {
            font-family: var(--pix-mono);
            font-size: 10px;
            color: var(--pix-mut);
        }

        .pix-cand__p {
            font-variant-numeric: tabular-nums;
            font-weight: 700;
        }

        .pix-cand.is-rate .pix-cand__p {
            color: var(--pix-ok);
        }

        /* =========================================================== rail === */

        .pix-rail {
            position: sticky;
            top: 8.6rem;
        }

        .hd--sticky .pix-rail {
            top: 12.6rem;
        }

        .pix-sum {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: .5rem;
            padding: .3rem 0;
            font-size: 12px;
        }

        .pix-sum+.pix-sum {
            border-top: 1px dashed var(--pix-lns);
        }

        .pix-sum span:first-child {
            color: var(--pix-mut);
        }

        .pix-sum span:last-child {
            font-weight: 600;
            font-variant-numeric: tabular-nums;
        }

        .pix-sum--grand {
            margin-top: .35rem;
            padding: .55rem .65rem;
            border-radius: 10px;
            background: var(--pix-tint);
            border: 1px solid var(--pix-line-acc);
        }

        .pix-sum--grand span:first-child {
            color: var(--pix-acc-ink);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 10.5px;
            letter-spacing: .06em;
        }

        .pix-sum--grand span:last-child {
            font-size: 17px;
            font-weight: 800;
            color: var(--pix-acc-ink);
        }

        /* ======================================================== filters === */

        .pix-seg {
            display: inline-flex;
            padding: 2px;
            gap: 2px;
            border-radius: 999px;
            background: var(--pix-sf3);
            border: 1px solid var(--pix-ln);
        }

        .pix-seg button {
            border: 0;
            background: transparent;
            color: var(--pix-mut);
            font-size: 11px;
            font-weight: 700;
            padding: .2rem .65rem;
            border-radius: 999px;
            line-height: 1.6;
        }

        .pix-seg button.is-on {
            background: var(--pix-sf);
            color: var(--pix-acc-ink);
            box-shadow: var(--pix-shadow);
        }

        /* ======================================================== resolve === */

        .pix-resolve {
            position: fixed;
            inset: 0;
            z-index: 1080;
            background: rgba(9, 14, 26, .5);
            backdrop-filter: blur(2px);
            display: grid;
            place-items: center;
            padding: 1rem;
        }

        .pix-resolve__box {
            width: min(640px, 100%);
            max-height: 82vh;
            display: flex;
            flex-direction: column;
            background: var(--pix-sf);
            border: 1px solid var(--pix-ln);
            border-radius: 14px;
            box-shadow: var(--pix-shadow-lg);
            overflow: hidden;
        }

        .pix-hit {
            display: flex;
            align-items: center;
            gap: .6rem;
            width: 100%;
            text-align: left;
            padding: .5rem .6rem;
            border: 1px solid var(--pix-ln);
            border-radius: 10px;
            background: var(--pix-sf2);
            margin-bottom: .35rem;
        }

        .pix-hit:hover {
            border-color: var(--pix-acc);
            background: var(--pix-tint);
        }

        /* ========================================================== misc === */

        .pix-empty {
            text-align: center;
            padding: 2.4rem 1rem;
            color: var(--pix-mut);
        }

        .pix-empty i {
            font-size: 26px;
            opacity: .5;
            display: block;
            margin-bottom: .5rem;
        }

        .pix-note {
            display: flex;
            gap: .5rem;
            padding: .5rem .65rem;
            border-radius: 10px;
            font-size: 11.5px;
            background: var(--pix-sf2);
            border: 1px solid var(--pix-ln);
            color: var(--pix-ink2);
        }

        .pix-note--warn {
            background: color-mix(in srgb, var(--pix-warn), transparent 92%);
            border-color: color-mix(in srgb, var(--pix-warn), transparent 72%);
            color: color-mix(in srgb, var(--pix-warn), #000 22%);
        }

        [data-bs-theme="dark"] .pix-note--warn {
            color: color-mix(in srgb, var(--pix-warn), #fff 30%);
        }

        .pix-prev {
            width: 100%;
            font-size: 11px;
            border-collapse: collapse;
        }

        .pix-prev th,
        .pix-prev td {
            padding: .3rem .5rem;
            border: 1px solid var(--pix-lns);
            white-space: nowrap;
            max-width: 190px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .pix-prev th {
            background: var(--pix-sf2);
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: var(--pix-mut);
        }

        .pix-switch {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .6rem;
            padding: .5rem .6rem;
            border: 1px solid var(--pix-ln);
            border-radius: 10px;
            background: var(--pix-sf2);
        }

        .pix .form-check-input:checked {
            background-color: var(--pix-acc);
            border-color: var(--pix-acc);
        }

        @media (max-width: 991.98px) {

            .pix-deck,
            .pix-rail {
                position: static;
            }
        }
    </style>
@endonce

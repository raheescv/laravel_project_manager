{{--
    ".stx" — Studio, the premium layer for the Student Import wizard.

    A vertical step rail beside a roomy stage (picked from
    docs/student-import-premium-preview.html, direction C). Scoped entirely to
    `.stx`; the accent is --bs-primary from Settings → Colors and both themes are
    styled. Layout answers to the width the page actually gets (container
    queries), not the viewport, because the sidebar takes a varying share of it.

        <x-student.import-premium />
--}}
@once
    <style>
        .stx {
            --stx-acc: var(--bs-primary, #2f4d8a);
            --stx-acc-ink: color-mix(in srgb, var(--stx-acc), #000 16%);
            --stx-on-acc: #fff;
            --stx-tint: color-mix(in srgb, var(--stx-acc), transparent 90%);
            --stx-tint-2: color-mix(in srgb, var(--stx-acc), transparent 95%);
            --stx-line-acc: color-mix(in srgb, var(--stx-acc), transparent 70%);

            --stx-ok: #15803d;
            --stx-ok-t: rgba(22, 163, 74, .11);
            --stx-ok-l: rgba(22, 163, 74, .28);
            --stx-bad: #b91c1c;
            --stx-bad-t: rgba(220, 38, 38, .09);
            --stx-bad-l: rgba(220, 38, 38, .26);
            --stx-warn: #b45309;

            --stx-sf: #fff;
            --stx-sf2: #f7f9fc;
            --stx-sf3: #eef2f7;
            --stx-ink: #0f172a;
            --stx-ink2: #334155;
            --stx-mut: #6b7a90;
            --stx-ln: #e2e7ee;
            --stx-lns: #eef1f6;
            --stx-card-sh: 0 1px 2px rgba(15, 23, 42, .04), 0 6px 18px -12px rgba(15, 23, 42, .18);
            --stx-frame-sh: 0 1px 2px rgba(15, 23, 42, .05), 0 24px 50px -30px rgba(15, 23, 42, .35);
            --stx-mono: ui-monospace, Menlo, Consolas, "DejaVu Sans Mono", monospace;

            container-type: inline-size;
            font-size: 13px;
            color: var(--stx-ink);
        }

        [data-bs-theme="dark"] .stx {
            --stx-acc-ink: color-mix(in srgb, var(--stx-acc), #fff 42%);
            --stx-on-acc: #fff;
            --stx-tint: color-mix(in srgb, var(--stx-acc), transparent 80%);
            --stx-tint-2: color-mix(in srgb, var(--stx-acc), transparent 90%);
            --stx-line-acc: color-mix(in srgb, var(--stx-acc), #fff 10%);

            --stx-ok: #4ade80;
            --stx-ok-t: rgba(74, 222, 128, .12);
            --stx-ok-l: rgba(74, 222, 128, .3);
            --stx-bad: #f87171;
            --stx-bad-t: rgba(248, 113, 113, .12);
            --stx-bad-l: rgba(248, 113, 113, .3);
            --stx-warn: #fbbf24;

            --stx-sf: #161b26;
            --stx-sf2: #1b2130;
            --stx-sf3: #222a3b;
            --stx-ink: #e8edf5;
            --stx-ink2: #b9c3d3;
            --stx-mut: #8793a8;
            --stx-ln: #2a3243;
            --stx-lns: #212838;
            --stx-card-sh: 0 1px 2px rgba(0, 0, 0, .35);
            --stx-frame-sh: 0 24px 50px -30px rgba(0, 0, 0, .8);
        }

        .stx h2,
        .stx h3,
        .stx p {
            margin: 0;
        }

        .stx small {
            font-size: .86em;
            color: var(--stx-mut);
        }

        .stx code {
            font-family: var(--stx-mono);
            font-size: .92em;
            color: var(--stx-ink2);
        }

        /* ============================================================ frame === */

        .stx-frame {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            grid-template-areas: "head" "rail" "stage";
            background: var(--stx-sf);
            border: 1px solid var(--stx-ln);
            border-radius: 18px;
            box-shadow: var(--stx-frame-sh);
            overflow: hidden;
        }

        @container (min-width: 1040px) {
            .stx-frame {
                grid-template-columns: 224px minmax(0, 1fr);
                grid-template-rows: auto 1fr;
                grid-template-areas: "rail head" "rail stage";
            }
        }

        .stx-head {
            grid-area: head;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: .75rem 1rem;
            padding: 1.15rem 1.4rem 1rem;
            border-bottom: 1px solid var(--stx-ln);
        }

        .stx-title {
            display: flex;
            align-items: center;
            gap: .8rem;
            min-width: 0;
            flex: 1 1 260px;
        }

        .stx-title__ico {
            flex: none;
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            border-radius: 13px;
            background: var(--stx-tint);
            color: var(--stx-acc-ink);
            font-size: 18px;
        }

        .stx-title h2 {
            font-size: 17px;
            font-weight: 600;
            letter-spacing: -.01em;
            color: var(--stx-ink);
        }

        .stx-title p {
            font-size: 12.5px;
            color: var(--stx-mut);
            margin-top: 1px;
        }

        .stx-actions {
            display: flex;
            gap: .4rem;
            flex-wrap: wrap;
        }

        /* ------------------------------------------------------------ rail --- */

        .stx-rail {
            grid-area: rail;
            list-style: none;
            margin: 0;
            display: flex;
            gap: 1rem;
            overflow-x: auto;
            padding: .8rem 1.2rem;
            background: var(--stx-sf2);
            border-bottom: 1px solid var(--stx-ln);
        }

        .stx-rail__label {
            display: none;
        }

        .stx-step {
            display: flex;
            align-items: flex-start;
            gap: .7rem;
            width: 100%;
            padding: .15rem;
            border: 0;
            border-radius: 12px;
            background: none;
            font: inherit;
            color: var(--stx-mut);
            text-align: start;
            cursor: pointer;
        }

        .stx-step:disabled {
            cursor: default;
        }

        .stx-step__n {
            flex: none;
            width: 32px;
            height: 32px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            border: 2px solid var(--stx-ln);
            background: var(--stx-sf);
            color: var(--stx-mut);
            font-size: 12.5px;
            font-weight: 600;
        }

        .stx-step.is-done .stx-step__n {
            background: var(--stx-ok);
            border-color: var(--stx-ok);
            color: #fff;
        }

        [data-bs-theme="dark"] .stx-step.is-done .stx-step__n {
            color: #0b1220;
        }

        .stx-step.is-active .stx-step__n {
            background: var(--stx-acc);
            border-color: var(--stx-acc);
            color: var(--stx-on-acc);
            box-shadow: 0 0 0 4px var(--stx-tint);
        }

        .stx-step__t {
            display: none;
            padding-top: .1rem;
            min-width: 0;
        }

        .stx-step__t b {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--stx-ink2);
            white-space: nowrap;
        }

        .stx-step.is-active .stx-step__t b {
            color: var(--stx-ink);
        }

        .stx-step__t small {
            display: none;
            margin-top: 1px;
            overflow-wrap: anywhere;
        }

        .stx-step.is-active .stx-step__t small {
            color: var(--stx-acc-ink);
            font-weight: 500;
        }

        @container (min-width: 640px) {
            .stx-step__t {
                display: block;
            }
        }

        @container (min-width: 1040px) {
            .stx-rail {
                flex-direction: column;
                gap: 0;
                overflow: visible;
                padding: 1.35rem 1rem;
                border-bottom: 0;
                border-inline-end: 1px solid var(--stx-ln);
            }

            .stx-rail__label {
                display: block;
                margin: 0 0 1rem;
                padding-inline-start: .2rem;
                font-size: 10.5px;
                font-weight: 600;
                letter-spacing: .08em;
                text-transform: uppercase;
                color: var(--stx-mut);
            }

            .stx-rail li {
                position: relative;
                padding-bottom: 1.3rem;
            }

            .stx-rail li:last-child {
                padding-bottom: 0;
            }

            .stx-rail li:not(:last-child)::after {
                content: "";
                position: absolute;
                inset-inline-start: 17px;
                top: 38px;
                bottom: 4px;
                width: 2px;
                border-radius: 2px;
                background: var(--stx-ln);
            }

            .stx-rail li.is-done::after {
                background: var(--stx-ok);
            }

            .stx-step__t small {
                display: block;
            }
        }

        .stx-stage {
            grid-area: stage;
            min-width: 0;
            padding: 1.25rem 1.4rem 1.5rem;
            background: linear-gradient(180deg, var(--stx-sf), var(--stx-sf) 60%, var(--stx-sf2));
        }

        @container (max-width: 639px) {
            .stx-head,
            .stx-stage {
                padding-inline: 1rem;
            }
        }

        .stx-split {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 1rem;
            align-items: start;
        }

        .stx-col {
            display: grid;
            gap: 1rem;
            min-width: 0;
        }

        @container (min-width: 760px) {
            .stx-split {
                grid-template-columns: minmax(0, 1fr) 290px;
            }

            .stx-split > aside {
                position: sticky;
                top: 1rem;
            }
        }

        /* ----------------------------------------------------------- atoms --- */

        .stx-card {
            min-width: 0;
            background: var(--stx-sf);
            border: 1px solid var(--stx-lns);
            border-radius: 14px;
            box-shadow: var(--stx-card-sh);
        }

        [data-bs-theme="dark"] .stx-card {
            border-color: var(--stx-ln);
        }

        .stx-card__head {
            display: flex;
            align-items: center;
            gap: .5rem;
            padding: .8rem 1rem;
            border-bottom: 1px solid var(--stx-lns);
        }

        .stx-card__head h3 {
            flex: 1;
            display: flex;
            align-items: center;
            gap: .45rem;
            font-size: 13px;
            font-weight: 600;
            color: var(--stx-ink);
        }

        .stx-card__head h3 .fa {
            color: var(--stx-acc-ink);
        }

        .stx-card__body {
            padding: .9rem 1rem;
        }

        .stx-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .4rem;
            padding: .48rem .9rem;
            border: 1px solid transparent;
            border-radius: 10px;
            font: inherit;
            font-size: 12.5px;
            font-weight: 500;
            line-height: 1.3;
            text-decoration: none;
            white-space: nowrap;
            cursor: pointer;
        }

        .stx-btn:disabled {
            opacity: .55;
            cursor: not-allowed;
        }

        .stx-btn--primary {
            background: var(--stx-acc);
            border-color: var(--stx-acc);
            color: var(--stx-on-acc);
        }

        .stx-btn--primary:hover:not(:disabled) {
            background: color-mix(in srgb, var(--stx-acc), #000 10%);
            color: var(--stx-on-acc);
        }

        .stx-btn--soft {
            background: var(--stx-tint);
            border-color: var(--stx-line-acc);
            color: var(--stx-acc-ink);
        }

        .stx-btn--soft:hover {
            background: color-mix(in srgb, var(--stx-acc), transparent 84%);
            color: var(--stx-acc-ink);
        }

        .stx-btn--ghost {
            background: var(--stx-sf);
            border-color: var(--stx-ln);
            color: var(--stx-ink2);
        }

        .stx-btn--ghost:hover {
            background: var(--stx-sf2);
            color: var(--stx-ink);
        }

        [dir="rtl"] .stx .fa-arrow-right,
        [dir="rtl"] .stx .fa-arrow-left,
        [dir="rtl"] .stx .fa-long-arrow-left {
            transform: scaleX(-1);
        }

        .stx-chip {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            padding: .12rem .5rem;
            border-radius: 999px;
            background: var(--stx-sf3);
            color: var(--stx-ink2);
            font-size: 11px;
            font-weight: 500;
            white-space: nowrap;
        }

        .stx-pill {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            padding: .14rem .5rem;
            border: 1px solid transparent;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
        }

        .stx-pill--new {
            background: var(--stx-ok-t);
            border-color: var(--stx-ok-l);
            color: var(--stx-ok);
        }

        .stx-pill--update {
            background: var(--stx-tint);
            border-color: var(--stx-line-acc);
            color: var(--stx-acc-ink);
        }

        .stx-pill--skip {
            background: var(--stx-sf3);
            border-color: var(--stx-ln);
            color: var(--stx-mut);
        }

        .stx-pill--error {
            background: var(--stx-bad-t);
            border-color: var(--stx-bad-l);
            color: var(--stx-bad);
        }

        .stx-seg {
            display: inline-flex;
            gap: 2px;
            padding: 3px;
            border-radius: 999px;
            background: var(--stx-sf3);
        }

        .stx-seg button {
            padding: .3rem .75rem;
            border: 0;
            border-radius: 999px;
            background: none;
            color: var(--stx-mut);
            font: inherit;
            font-size: 12px;
            font-weight: 500;
            white-space: nowrap;
            cursor: pointer;
        }

        .stx-seg button em {
            margin-inline-start: .25rem;
            font-size: 11px;
            font-style: normal;
            opacity: .75;
        }

        .stx-seg button.is-on {
            background: var(--stx-sf);
            color: var(--stx-ink);
            box-shadow: 0 1px 2px rgba(15, 23, 42, .12);
        }

        .stx-error {
            display: flex;
            gap: .45rem;
            align-items: baseline;
            padding: .6rem .8rem;
            border: 1px solid var(--stx-bad-l);
            border-radius: 10px;
            background: var(--stx-bad-t);
            color: var(--stx-bad);
            font-size: 12.5px;
        }

        .stx-busy {
            transition: opacity .15s;
        }

        .stx-busy.is-busy {
            opacity: .45;
            pointer-events: none;
        }

        /* ---------------------------------------------------------- step 1 --- */

        .stx-choice {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: .6rem;
        }

        @container (min-width: 560px) {
            .stx-choice {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        .stx-opt {
            display: flex;
            align-items: flex-start;
            gap: .65rem;
            margin: 0;
            padding: .75rem;
            border: 1px solid var(--stx-ln);
            border-radius: 14px;
            background: var(--stx-sf);
            cursor: pointer;
        }

        .stx-opt input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .stx-opt__ico {
            flex: none;
            width: 32px;
            height: 32px;
            display: grid;
            place-items: center;
            border-radius: 9px;
            background: var(--stx-sf3);
            color: var(--stx-mut);
        }

        .stx-opt b {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--stx-ink);
        }

        .stx-opt.is-on {
            border-color: var(--stx-acc);
            background: var(--stx-tint-2);
            box-shadow: 0 0 0 1px var(--stx-acc) inset;
        }

        .stx-opt.is-on .stx-opt__ico {
            background: var(--stx-acc);
            color: var(--stx-on-acc);
        }

        .stx-drop {
            position: relative;
            display: grid;
            place-items: center;
            gap: .35rem;
            margin: 0;
            padding: 2.6rem 1rem;
            border: 2px dashed var(--stx-ln);
            border-radius: 16px;
            background: var(--stx-sf2);
            text-align: center;
            cursor: pointer;
        }

        .stx-drop:hover {
            border-color: var(--stx-acc);
            background: var(--stx-tint-2);
        }

        .stx-drop__input {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            font-size: 0;
            cursor: pointer;
        }

        .stx-drop__ico {
            width: 64px;
            height: 64px;
            display: grid;
            place-items: center;
            margin-bottom: .35rem;
            border-radius: 20px;
            background: var(--stx-acc);
            color: var(--stx-on-acc);
            font-size: 27px;
            box-shadow: 0 12px 24px -12px var(--stx-acc);
        }

        .stx-drop b {
            font-size: 14px;
            font-weight: 600;
        }

        .stx-drop u {
            color: var(--stx-acc-ink);
            text-decoration: none;
        }

        .stx-file {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: .75rem;
            padding: .9rem 1rem;
        }

        .stx-file__ico {
            flex: none;
            width: 44px;
            height: 44px;
            display: grid;
            place-items: center;
            border-radius: 12px;
            background: var(--stx-ok-t);
            color: var(--stx-ok);
            font-size: 20px;
        }

        .stx-file__name {
            flex: 1 1 200px;
            min-width: 0;
        }

        .stx-file__name b {
            display: block;
            font-weight: 600;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .stx-file__acts {
            display: flex;
            gap: .4rem;
        }

        .stx-file__acts label {
            position: relative;
            margin: 0;
            overflow: hidden;
        }

        .stx-tpl {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .75rem 1rem;
        }

        .stx-tpl > .fa {
            font-size: 22px;
            color: var(--stx-ok);
        }

        .stx-tpl div {
            flex: 1;
            min-width: 0;
        }

        .stx-tpl b {
            display: block;
            font-weight: 600;
        }

        .stx-how {
            list-style: none;
            margin: 0;
            padding: .3rem 1rem .8rem;
        }

        .stx-how li {
            display: flex;
            gap: .7rem;
            padding: .65rem 0;
            border-bottom: 1px dashed var(--stx-ln);
        }

        .stx-how li:last-child {
            border-bottom: 0;
        }

        .stx-how__ico {
            flex: none;
            width: 30px;
            height: 30px;
            display: grid;
            place-items: center;
            border-radius: 8px;
            background: var(--stx-tint);
            color: var(--stx-acc-ink);
            font-size: 13px;
        }

        .stx-how b {
            display: block;
            font-size: 12.5px;
            font-weight: 600;
        }

        .stx-how small {
            display: block;
            line-height: 1.45;
        }

        .stx-how em {
            padding: 0 .25rem;
            border-radius: 4px;
            background: var(--stx-sf3);
            color: var(--stx-ink2);
            font-family: var(--stx-mono);
            font-size: 11px;
            font-style: normal;
        }

        /* ---------------------------------------------------------- step 2 --- */

        .stx-map__row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            gap: .35rem .6rem;
            align-items: center;
            padding: .5rem 1rem;
            border-bottom: 1px solid var(--stx-lns);
        }

        .stx-map__row:last-child {
            border-bottom: 0;
        }

        @container (min-width: 900px) {
            .stx-map__row {
                grid-template-columns: minmax(120px, 1fr) 16px minmax(150px, 1.1fr) minmax(110px, 1fr);
            }
        }

        .stx-map__field {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: .1rem .45rem;
            min-width: 0;
        }

        .stx-map__field .fa {
            color: var(--stx-ln);
            font-size: 14px;
        }

        .stx-map__row.is-mapped .stx-map__field .fa {
            color: var(--stx-ok);
        }

        .stx-map__field b {
            font-size: 12.5px;
            font-weight: 500;
            color: var(--stx-ink);
        }

        .stx-req {
            font-size: 10px;
            font-weight: 600;
            letter-spacing: .03em;
            text-transform: uppercase;
            color: var(--stx-bad);
        }

        .stx-map__arrow {
            display: none;
            color: var(--stx-mut);
            text-align: center;
        }

        .stx-map__row .form-select {
            font-size: 12.5px;
            border-radius: 9px;
        }

        .stx-map__row.is-mapped .form-select {
            border-color: var(--stx-ok-l);
        }

        .stx-map__sample {
            grid-column: 1 / -1;
            display: flex;
            align-items: center;
            gap: .4rem;
            min-width: 0;
            font-size: 12px;
            color: var(--stx-ink2);
        }

        @container (min-width: 900px) {
            .stx-map__arrow {
                display: block;
            }

            .stx-map__sample {
                grid-column: auto;
            }
        }

        .stx-map__sample .v {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .stx-map__sample .none {
            color: var(--stx-mut);
            font-style: italic;
        }

        .stx-auto {
            flex: none;
            padding: .05rem .4rem;
            border-radius: 999px;
            background: var(--stx-tint);
            color: var(--stx-acc-ink);
            font-size: 10px;
            font-weight: 600;
        }

        .stx-meter {
            display: flex;
            align-items: center;
            gap: .8rem;
            padding: 1rem;
        }

        .stx-meter > div b {
            display: block;
            font-weight: 600;
        }

        .stx-ring {
            --p: 0;
            position: relative;
            flex: none;
            width: 62px;
            height: 62px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: conic-gradient(var(--stx-acc) calc(var(--p) * 1%), var(--stx-sf3) 0);
        }

        .stx-ring::before {
            content: "";
            position: absolute;
            inset: 7px;
            border-radius: 50%;
            background: var(--stx-sf);
        }

        .stx-ring > span {
            position: relative;
            line-height: 1.05;
            text-align: center;
        }

        .stx-ring b {
            display: block;
            font-size: 16px;
            font-weight: 700;
        }

        .stx-ring small {
            font-size: 10px;
        }

        .stx-checks {
            display: grid;
            gap: .4rem;
            margin: 0;
            padding: 0 1rem .8rem;
            list-style: none;
        }

        .stx-checks li {
            display: flex;
            align-items: baseline;
            gap: .45rem;
            font-size: 12px;
            color: var(--stx-ink2);
        }

        .stx-checks .ok .fa {
            color: var(--stx-ok);
        }

        .stx-checks .bad .fa {
            color: var(--stx-bad);
        }

        .stx-checks .info .fa {
            color: var(--stx-acc-ink);
        }

        .stx-unused {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .3rem;
            padding: .7rem 1rem;
            border-top: 1px solid var(--stx-lns);
        }

        .stx-unused small {
            width: 100%;
            margin-bottom: .1rem;
        }

        .stx-foot {
            display: flex;
            gap: .5rem;
            padding: .8rem 1rem;
            border-top: 1px solid var(--stx-lns);
        }

        .stx-foot .stx-btn--primary {
            flex: 1;
        }

        /* ---------------------------------------------------------- step 3 --- */

        .stx-kpis {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .6rem;
            margin-bottom: 1rem;
        }

        @container (min-width: 640px) {
            .stx-kpis {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @container (min-width: 1100px) {
            .stx-kpis {
                grid-template-columns: repeat(5, minmax(0, 1fr)) minmax(0, 1.4fr);
            }
        }

        .stx-kpi {
            min-width: 0;
            padding: .7rem .9rem;
            border: 1px solid var(--stx-lns);
            border-radius: 14px;
            background: var(--stx-sf2);
        }

        [data-bs-theme="dark"] .stx-kpi {
            border-color: var(--stx-ln);
        }

        .stx-kpi small {
            display: block;
            font-size: 10.5px;
            font-weight: 600;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .stx-kpi b {
            display: block;
            font-size: 21px;
            font-weight: 700;
            line-height: 1.25;
            color: var(--stx-ink);
            font-variant-numeric: tabular-nums;
        }

        .stx-kpi b em {
            font-size: 11px;
            font-style: normal;
            font-weight: 500;
            color: var(--stx-mut);
        }

        .stx-kpi span {
            font-size: 11px;
            color: var(--stx-mut);
        }

        .stx-kpi--new {
            background: var(--stx-ok-t);
            border-color: transparent !important;
        }

        .stx-kpi--new b {
            color: var(--stx-ok);
        }

        .stx-kpi--update {
            background: var(--stx-tint);
            border-color: transparent !important;
        }

        .stx-kpi--update b {
            color: var(--stx-acc-ink);
        }

        .stx-kpi--error {
            background: var(--stx-bad-t);
            border-color: transparent !important;
        }

        .stx-kpi--error b {
            color: var(--stx-bad);
        }

        .stx-ready {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .7rem 1rem;
            margin-bottom: 1rem;
            padding: .8rem 1rem;
        }

        .stx-ready__count {
            flex: 1 1 280px;
            display: grid;
            grid-template-columns: auto minmax(0, 1fr);
            align-items: center;
            column-gap: .8rem;
        }

        .stx-ready__count > b {
            grid-row: span 2;
            font-size: 28px;
            font-weight: 700;
            line-height: 1;
            color: var(--stx-ink);
            font-variant-numeric: tabular-nums;
        }

        .stx-ready__count p {
            font-size: 12px;
            line-height: 1.45;
            color: var(--stx-mut);
        }

        .stx-ready__acts {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .5rem;
        }

        .stx-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .5rem;
            padding: .65rem 1rem;
            border-bottom: 1px solid var(--stx-lns);
        }

        .stx-search {
            display: flex;
            align-items: center;
            gap: .4rem;
            min-width: 200px;
            margin: 0 0 0 auto;
            margin-inline-start: auto;
            padding: .22rem .75rem;
            border: 1px solid var(--stx-ln);
            border-radius: 999px;
            background: var(--stx-sf);
        }

        .stx-search .fa {
            color: var(--stx-mut);
        }

        .stx-search input {
            width: 100%;
            border: 0;
            outline: 0;
            background: none;
            color: var(--stx-ink);
            font: inherit;
            font-size: 12px;
        }

        .stx-table-wrap {
            overflow-x: auto;
        }

        .stx-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12.5px;
        }

        .stx-table th {
            padding: .5rem .7rem;
            border-bottom: 1px solid var(--stx-ln);
            color: var(--stx-mut);
            font-size: 10.5px;
            font-weight: 600;
            letter-spacing: .04em;
            text-align: start;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .stx-table td {
            padding: .55rem .7rem;
            border-bottom: 1px solid var(--stx-lns);
            color: var(--stx-ink);
            vertical-align: top;
        }

        .stx-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .stx-table tr.is-error td {
            background: color-mix(in srgb, var(--stx-bad-t), transparent 40%);
        }

        .stx-table .ln {
            color: var(--stx-mut);
            font-family: var(--stx-mono);
            font-size: 11px;
        }

        .stx-table .who {
            min-width: 150px;
        }

        .stx-table .who b {
            display: block;
            font-weight: 500;
            white-space: nowrap;
        }

        .stx-table .who small,
        .stx-table .par small {
            display: block;
        }

        .stx-table .par + .par {
            margin-top: .3rem;
        }

        .stx-table .par {
            white-space: nowrap;
            color: var(--stx-ink2);
        }

        .stx-table td.chk {
            min-width: 230px;
        }

        /* A phone gets one card per row: row · result · admission, then student, parents, check. */
        @container (max-width: 639px) {
            .stx-table thead {
                display: none;
            }

            .stx-table,
            .stx-table tbody {
                display: block;
            }

            .stx-table tr {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: .3rem .6rem;
                padding: .7rem 1rem;
                border-bottom: 1px solid var(--stx-lns);
            }

            .stx-table tr.is-error {
                background: color-mix(in srgb, var(--stx-bad-t), transparent 40%);
            }

            .stx-table td {
                padding: 0;
                border: 0;
                background: none !important;
            }

            .stx-table td.who,
            .stx-table td.parents,
            .stx-table td.chk {
                flex-basis: 100%;
                min-width: 0;
            }

            .stx-table .par {
                white-space: normal;
            }

            .stx-table td.stx-empty {
                flex-basis: 100%;
                padding: 1.5rem 0;
            }
        }

        .stx-msg {
            display: flex;
            align-items: baseline;
            gap: .35rem;
            font-size: 12px;
            line-height: 1.4;
        }

        .stx-msg + .stx-msg {
            margin-top: .2rem;
        }

        .stx-msg--error {
            color: var(--stx-bad);
        }

        .stx-msg--note {
            color: var(--stx-warn);
        }

        .stx-msg--ok {
            color: var(--stx-ok);
        }

        .stx-msg--mut {
            color: var(--stx-mut);
        }

        .stx-empty {
            padding: 2rem 1rem;
            color: var(--stx-mut);
            text-align: center;
        }

        .stx-pager {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
            padding: .65rem 1rem;
            border-top: 1px solid var(--stx-lns);
            font-size: 12px;
            color: var(--stx-mut);
        }

        .stx-pages {
            display: flex;
            gap: 3px;
        }

        .stx-pages button,
        .stx-pages span {
            min-width: 28px;
            height: 28px;
            display: grid;
            place-items: center;
            padding: 0 .4rem;
            border: 1px solid var(--stx-ln);
            border-radius: 8px;
            background: var(--stx-sf);
            color: var(--stx-ink2);
            font: inherit;
            font-size: 12px;
        }

        .stx-pages span {
            border-color: transparent;
            background: none;
        }

        .stx-pages button.is-on {
            background: var(--stx-acc);
            border-color: var(--stx-acc);
            color: var(--stx-on-acc);
        }

        .stx-pages button:disabled {
            opacity: .45;
        }

        /* ---------------------------------------------------------- step 4 --- */

        .stx-run {
            padding: 2.4rem 1.2rem;
            text-align: center;
        }

        .stx-run .stx-ring {
            width: 104px;
            height: 104px;
            margin: 0 auto 1rem;
        }

        .stx-run .stx-ring::before {
            inset: 9px;
        }

        .stx-run .stx-ring b {
            font-size: 24px;
        }

        .stx-run .stx-ring.is-ok,
        .stx-run .stx-ring.is-ok::before {
            background: var(--stx-ok);
        }

        .stx-run .stx-ring.is-bad,
        .stx-run .stx-ring.is-bad::before {
            background: var(--stx-bad);
        }

        .stx-run .stx-ring > .fa {
            position: relative;
            font-size: 38px;
            color: #fff;
        }

        [data-bs-theme="dark"] .stx-run .stx-ring > .fa {
            color: #0b1220;
        }

        .stx-run > h3 {
            font-size: 18px;
            font-weight: 600;
        }

        .stx-run > p {
            max-width: 480px;
            margin: .35rem auto 0;
            font-size: 13px;
            line-height: 1.5;
            color: var(--stx-mut);
        }

        .stx-bar {
            max-width: 460px;
            height: 10px;
            margin: 1.1rem auto 0;
            overflow: hidden;
            border-radius: 999px;
            background: var(--stx-sf3);
        }

        .stx-bar span {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: repeating-linear-gradient(45deg, var(--stx-acc) 0 12px, color-mix(in srgb, var(--stx-acc), #fff 16%) 12px 24px);
            transition: width .4s ease;
        }

        .stx-run__meta,
        .stx-run__acts {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: .5rem;
            margin-top: 1.1rem;
        }

        .stx-run__acts {
            margin-top: 1.4rem;
        }

        .stx-run__tiles {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .6rem;
            max-width: 560px;
            margin: 1.2rem auto 0;
            text-align: start;
        }

        @container (min-width: 640px) {
            .stx-run__tiles {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        .stx-run__errors {
            max-width: 640px;
            margin: 1.2rem auto 0;
            text-align: start;
        }

        .stx-run__errors li {
            display: flex;
            gap: .6rem;
            padding: .45rem .8rem;
            border-bottom: 1px solid var(--stx-lns);
            font-size: 12px;
        }

        .stx-run__errors li:last-child {
            border-bottom: 0;
        }

        .stx-run__errors ul {
            margin: 0;
            padding: 0;
            list-style: none;
        }
    </style>
@endonce

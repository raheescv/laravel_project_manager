{{--
    ╔══════════════════════════════════════════════════════════════════════╗
    ║  Add / Edit Student — premium form system                             ║
    ║                                                                      ║
    ║  Scoped under .sfx. Built for non-technical office staff: numbered    ║
    ║  sections that say what they are for, tap cards instead of dropdowns  ║
    ║  for short choices (real .btn-check radios, so wire:model works),     ║
    ║  "Required"/"Optional" in words, a live preview and a checklist.      ║
    ║                                                                      ║
    ║  Layout is Bootstrap grid; colours come from the settings theme       ║
    ║  (--bs-primary + Bootstrap subtle/emphasis tokens) and dark mode.     ║
    ║                                                                      ║
    ║  Preview: docs/student-view-premium-preview.html → Add student        ║
    ╚══════════════════════════════════════════════════════════════════════╝
--}}
@once
    @push('styles')
        <style>
            .sfx {
                --acc: var(--bs-primary);
                --acc-rgb: var(--bs-primary-rgb);
                /* Surfaces pinned to a crisp ramp (white panels on the app's soft page), like .rvx;
                   the app's own body tokens are greyer. Dark ramp in the dark block. */
                --sf: #ffffff;
                --ln: #e4e8ee;
                --mut: var(--bs-secondary-color);
                --ink: var(--bs-emphasis-color);
                --soft: #f5f7fa;
                --mono: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
                --sh: 0 1px 2px rgba(15, 23, 42, .05), 0 14px 34px -18px rgba(15, 23, 42, .22);
                --sfx-top: .75rem;
                /* Hero gradient stops, same as the .apx hero (Email log) and the student view. */
                --hero-1: color-mix(in srgb, var(--bs-primary), #000 42%);
                --hero-2: color-mix(in srgb, var(--bs-primary), #000 4%);
                --hero-3: color-mix(in srgb, var(--bs-primary), #fff 10%);
            }

            [data-bs-theme="dark"] .sfx { --sf: #272d34; --soft: #2e353d; --ln: #3a424c; --sh: 0 1px 2px rgba(0, 0, 0, .4), 0 18px 40px -20px rgba(0, 0, 0, .7);
                --hero-1: color-mix(in srgb, var(--bs-primary), #000 64%);
                --hero-2: color-mix(in srgb, var(--bs-primary), #000 48%);
                --hero-3: color-mix(in srgb, var(--bs-primary), #000 30%);
            }
            .hd--sticky .sfx { --sfx-top: calc(var(--nf-header-height, 3.125rem) + .75rem); }

            @keyframes sfxRise { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }
            @keyframes sfxRing { from { transform: scale(1); opacity: .7; } to { transform: scale(1.7); opacity: 0; } }
            @media (prefers-reduced-motion: reduce) { .sfx *, .sfx *::before, .sfx *::after { animation: none !important; transition: none !important; } }

            .sfx .min-w-0 { min-width: 0; }
            .sfx .rise { animation: sfxRise .55s cubic-bezier(.2, .75, .25, 1) both; animation-delay: calc(var(--i, 0) * 60ms); }
            .sfx .sheet { background: var(--sf); border-radius: 18px; box-shadow: var(--sh); border: 1px solid color-mix(in srgb, var(--ln) 60%, transparent); }

            /* top bar + steps: dark theme-colour gradient with a fading dot grid, matching the
               .apx hero on the Email log; its contents are white/glass. */
            .sfx .top { padding: 14px 20px; display: flex; gap: 12px 16px; align-items: center; flex-wrap: wrap; position: relative; overflow: hidden; isolation: isolate; color: #fff; border-color: transparent;
                box-shadow: 0 16px 38px -16px rgba(16, 24, 40, .28), 0 7px 16px -10px rgba(16, 24, 40, .16);
                background:
                    radial-gradient(120% 160% at 12% -10%, rgba(255, 255, 255, .20), transparent 50%),
                    radial-gradient(90% 140% at 100% 0%, var(--hero-3), transparent 55%),
                    linear-gradient(118deg, var(--hero-1) 0%, var(--hero-2) 58%, var(--hero-3) 130%); }
            .sfx .top::after { content: ""; position: absolute; inset: 0; z-index: -1; opacity: .5; pointer-events: none;
                background-image: radial-gradient(circle at 1px 1px, rgba(255, 255, 255, .10) 1px, transparent 0); background-size: 22px 22px;
                -webkit-mask-image: linear-gradient(180deg, #000, transparent 70%); mask-image: linear-gradient(180deg, #000, transparent 70%); }
            .sfx .top .eyebrow { display: inline-flex; align-items: center; gap: 6px; padding: 5px 10px; border-radius: 999px; font-size: 10px; font-weight: 700; line-height: 1; letter-spacing: .1em; text-transform: uppercase; text-decoration: none; color: #fff; background: rgba(255, 255, 255, .16); border: 1px solid rgba(255, 255, 255, .28); margin-bottom: 8px; transition: background .15s; }
            .sfx .top .eyebrow:hover { background: rgba(255, 255, 255, .24); color: #fff; }
            .sfx .top h4 { margin: 0; font-size: clamp(17px, 1.8vw, 20px); font-weight: 700; line-height: 1.15; letter-spacing: -.02em; color: #fff; overflow-wrap: anywhere; }
            .sfx .top p { margin: 4px 0 0; font-size: 12.5px; color: rgba(255, 255, 255, .84); }
            .sfx .steps { display: flex; gap: 6px; flex-wrap: wrap; margin-inline-start: auto; }
            .sfx .steps button { border: 1px solid rgba(255, 255, 255, .26); background: rgba(255, 255, 255, .12); border-radius: 99px; padding: 5px 12px 5px 5px; font-size: 12px; color: #fff; display: inline-flex; align-items: center; gap: 7px; transition: all .2s; }
            .sfx .steps button:hover { background: rgba(255, 255, 255, .22); }
            .sfx .steps button .n { width: 22px; height: 22px; border-radius: 50%; display: grid; place-items: center; background: rgba(255, 255, 255, .2); font-size: 11px; font-weight: 600; color: #fff; }
            .sfx .steps button .o { font-size: 10.5px; color: rgba(255, 255, 255, .7); }
            .sfx .steps button.done { border-color: rgba(var(--bs-success-rgb), .6); background: rgba(var(--bs-success-rgb), .3); color: #fff; }
            .sfx .steps button.done .n { background: var(--bs-success); color: #fff; }
            .sfx .steps button.done .o { display: none; }

            /* sections */
            .sfx .fsec { padding: 16px 20px 18px; scroll-margin-top: calc(var(--sfx-top) + 8px); }
            .sfx .fsec + .fsec { margin-top: 12px; }
            .sfx .fsec > .row { --bs-gutter-y: .75rem; }
            @media (max-width: 575.98px) { .sfx .fsec, .sfx .top { padding: 14px; } }
            .sfx .sec-h { display: flex; gap: 10px; align-items: center; margin-bottom: 12px; }
            .sfx .sec-h .num { width: 28px; height: 28px; border-radius: 9px; flex: none; display: grid; place-items: center; font-size: 13px; font-weight: 600; background: var(--bs-primary-bg-subtle); color: var(--bs-primary-text-emphasis); }
            .sfx .sec-h h5 { margin: 0; font-size: 14.5px; font-weight: 600; color: var(--ink); }
            .sfx .sec-h p { margin: 0; font-size: 12px; color: var(--mut); }

            /* fields */
            .sfx .form-label { font-size: 12.5px; font-weight: 500; color: var(--ink); margin-bottom: 4px; display: flex; align-items: center; gap: 8px; }
            .sfx .req, .sfx .opt { font-size: 10px; font-weight: 500; letter-spacing: .06em; text-transform: uppercase; padding: 2px 7px; border-radius: 6px; }
            .sfx .req { background: var(--bs-danger-bg-subtle); color: var(--bs-danger-text-emphasis); }
            .sfx .opt { background: var(--soft); color: var(--mut); }
            .sfx .form-control, .sfx .form-select { min-height: 36px; border-radius: 10px; border-color: var(--ln); font-size: 13px; padding: 6px 11px; }
            .sfx textarea.form-control { min-height: 36px; }
            .sfx .form-control::placeholder { color: color-mix(in srgb, var(--mut) 70%, transparent); }
            .sfx .form-control:focus, .sfx .form-select:focus { border-color: var(--bs-primary-border-subtle); box-shadow: 0 0 0 3px rgba(var(--acc-rgb), .14); }
            .sfx .input-group > :not(:first-child) { border-start-start-radius: 0; border-end-start-radius: 0; }
            .sfx .input-group > :not(:last-child) { border-start-end-radius: 0; border-end-end-radius: 0; }
            .sfx .input-group-text { border-color: var(--ln); background: var(--soft); font-size: 12.5px; color: var(--mut); border-radius: 10px; min-width: 96px; justify-content: center; }
            .sfx .hint { font-size: 11px; color: var(--mut); margin-top: 3px; display: flex; gap: 5px; line-height: 1.4; }
            .sfx .hint i { margin-top: 1px; opacity: .7; }
            .sfx .uidf { font-family: var(--mono); letter-spacing: .08em; }

            /* tap cards (label after a .btn-check radio) */
            .sfx .tapc { display: flex; align-items: center; gap: 10px; padding: 7px 10px; border: 1.5px solid var(--ln); border-radius: 11px; cursor: pointer; background: var(--sf); height: 100%; margin: 0; transition: border-color .2s, background .2s, box-shadow .2s; user-select: none; }
            .sfx .tapc:hover { border-color: var(--bs-primary-border-subtle); }
            .sfx .tapc .ti { width: 28px; height: 28px; border-radius: 8px; display: grid; place-items: center; flex: none; background: var(--soft); color: var(--mut); font-size: 13px; transition: all .2s; }
            .sfx .tapc .tt { flex: 1; min-width: 0; line-height: 1.25; }
            .sfx .tapc .tt b { display: block; font-size: 13px; font-weight: 500; color: var(--ink); }
            .sfx .tapc .tt small { display: block; font-size: 11px; color: var(--mut); margin-top: 1px; }
            .sfx .tapc .tk { width: 18px; height: 18px; border-radius: 50%; border: 1.5px solid var(--ln); display: grid; place-items: center; color: transparent; font-size: 10px; flex: none; transition: all .2s; }
            .sfx .btn-check:checked + .tapc { border-color: var(--acc); background: var(--bs-primary-bg-subtle); box-shadow: 0 8px 20px -14px rgba(var(--acc-rgb), .8); }
            [data-bs-theme="dark"] .sfx .btn-check:checked + .tapc { border-color: var(--bs-primary-border-subtle); }
            .sfx .btn-check:checked + .tapc .ti, .sfx .btn-check:checked + .tapc .tk { background: var(--acc); border-color: var(--acc); color: #fff; }
            .sfx .btn-check:focus-visible + .tapc, .sfx .btn-check:focus-visible + label { outline: 3px solid rgba(var(--acc-rgb), .35); outline-offset: 2px; }
            .sfx .tapc.bad .ti { color: var(--bs-danger-text-emphasis); }
            .sfx .btn-check:checked + .tapc.bad { border-color: var(--bs-danger); background: var(--bs-danger-bg-subtle); }
            .sfx .btn-check:checked + .tapc.bad .ti, .sfx .btn-check:checked + .tapc.bad .tk { background: var(--bs-danger); border-color: var(--bs-danger); }

            /* quick picks */
            .sfx .qch { display: flex; gap: 5px; flex-wrap: wrap; margin-top: 6px; align-items: center; }
            .sfx .qch > span { font-size: 11px; color: var(--mut); margin-inline-end: 2px; }
            .sfx .qch button { border: 1px solid var(--ln); background: var(--sf); border-radius: 99px; padding: 2px 9px; font-size: 11.5px; color: var(--bs-body-color); transition: all .15s; }
            .sfx .qch button:hover { border-color: var(--bs-primary-border-subtle); }
            .sfx .qch button.on { background: var(--acc); border-color: var(--acc); color: #fff; }
            .sfx .subbox { background: var(--soft); border-radius: 12px; padding: 10px 12px; }
            .sfx .subbox .form-control { background: var(--sf); }

            /* parents */
            .sfx .explain { display: flex; gap: 10px; border-radius: 12px; padding: 8px 12px; background: var(--bs-info-bg-subtle); color: var(--bs-info-text-emphasis); font-size: 12px; margin-bottom: 12px; line-height: 1.45; }
            .sfx .explain i { font-size: 14px; margin-top: 1px; }
            .sfx .pcard { border: 1.5px solid var(--ln); border-radius: 13px; overflow: hidden; transition: border-color .2s; }
            .sfx .pcard + .pcard { margin-top: 10px; }
            .sfx .pcard.main { border-color: color-mix(in srgb, var(--bs-warning) 55%, var(--ln)); }
            .sfx .pc-h { display: flex; align-items: center; gap: 8px; padding: 6px 12px; background: var(--soft); flex-wrap: wrap; }
            .sfx .pc-h .pn { font-weight: 600; font-size: 13px; color: var(--ink); }
            .sfx .mainb { display: inline-flex; align-items: center; gap: 5px; font-size: 11.5px; font-weight: 500; padding: 3px 10px; border-radius: 99px; background: var(--bs-warning-bg-subtle); color: var(--bs-warning-text-emphasis); }
            .sfx .linkb { border: 0; background: none; font-size: 12px; color: var(--bs-link-color); padding: 3px 6px; border-radius: 8px; display: inline-flex; align-items: center; gap: 5px; }
            .sfx .linkb:hover { background: var(--sf); }
            .sfx .linkb.danger { color: var(--bs-danger-text-emphasis); }
            .sfx .pc-b { padding: 12px; }
            .sfx .pc-b > .row { --bs-gutter-y: .6rem; }
            .sfx .seg { display: flex; gap: 6px; flex-wrap: wrap; }
            .sfx .seg label { border: 1.5px solid var(--ln); border-radius: 9px; padding: 4px 11px; font-size: 12.5px; cursor: pointer; display: inline-flex; align-items: center; gap: 7px; color: var(--bs-body-color); margin: 0; transition: all .15s; }
            .sfx .seg label i { color: var(--mut); }
            .sfx .seg .btn-check:checked + label { border-color: var(--acc); background: var(--acc); color: #fff; }
            .sfx .seg .btn-check:checked + label i { color: #fff; }
            .sfx .addp { margin-top: 10px; width: 100%; border: 1.5px dashed var(--ln); border-radius: 12px; background: none; padding: 9px; font-size: 13px; font-weight: 500; color: var(--bs-link-color); transition: all .2s; }
            .sfx .addp:hover { border-color: var(--acc); background: var(--bs-primary-bg-subtle); }
            .sfx .empty { text-align: center; padding: 12px; border-radius: 14px; background: var(--bs-warning-bg-subtle); color: var(--bs-warning-text-emphasis); font-size: 12.5px; }

            /* side column */
            /* The column stretches to the form's height (no align-items-start on the row) so the side
               panel has room to stick; it scrolls itself if it is ever taller than the screen. */
            @media (min-width: 992px) {
                .sfx .side { position: sticky; top: var(--sfx-top); max-height: calc(100vh - var(--sfx-top) - 12px); overflow-y: auto; overscroll-behavior: contain; scrollbar-width: thin; padding: 4px 8px 16px; margin: -4px -8px -16px; }
            }
            .sfx .side .sheet + .sheet { margin-top: 10px; }
            .sfx .lbl { font-size: 10.5px; font-weight: 500; line-height: 1; letter-spacing: .14em; text-transform: uppercase; color: var(--mut); margin-bottom: 9px; display: flex; align-items: center; gap: 6px; }
            .sfx .live, .sfx .tapzone, .sfx .chkbox { padding: 14px; }
            .sfx .lv { display: flex; gap: 12px; align-items: center; padding: 10px; border-radius: 13px; background: linear-gradient(135deg, var(--bs-primary-bg-subtle), var(--soft)); }
            .sfx .lv-av { width: 56px; height: 56px; border-radius: 16px; flex: none; position: relative; display: grid; place-items: center; font-size: 19px; font-weight: 600; line-height: 1; color: var(--bs-primary-text-emphasis); background: var(--sf); box-shadow: 0 0 0 3px var(--sf); }
            .sfx .lv-av img { width: 100%; height: 100%; object-fit: cover; border-radius: inherit; }
            .sfx .lv-av .cam { position: absolute; inset-inline-end: -5px; bottom: -5px; width: 24px; height: 24px; border-radius: 50%; display: grid; place-items: center; background: var(--acc); color: #fff; font-size: 11px; border: 2.5px solid var(--sf); cursor: pointer; margin: 0; }
            .sfx .lv .nm { font-size: 14.5px; font-weight: 600; line-height: 1.25; color: var(--ink); overflow-wrap: anywhere; }
            .sfx .lv .sm { font-size: 11.5px; color: var(--mut); margin-top: 1px; }
            .sfx .lv .ph { color: var(--mut); font-weight: 400; font-style: italic; }
            .sfx .photo-act { display: flex; gap: 6px; margin-top: 10px; flex-wrap: wrap; }
            .sfx .photo-act .btn { border-radius: 9px; font-size: 12px; }
            .sfx .tz { display: flex; gap: 10px; align-items: center; padding: 8px 10px; border-radius: 12px; border: 1.5px dashed var(--ln); margin-bottom: 10px; transition: all .2s; }
            .sfx .tz.solid { border-style: solid; }
            .sfx .tz .ic { width: 34px; height: 34px; border-radius: 50%; display: grid; place-items: center; background: var(--bs-primary-bg-subtle); color: var(--bs-primary-text-emphasis); flex: none; position: relative; }
            .sfx .tz .ic i { transform: rotate(90deg); font-size: 14px; }
            .sfx .tz .ic::after { content: ""; position: absolute; inset: 0; border-radius: 50%; border: 2px solid var(--acc); opacity: 0; }
            .sfx .tapzone:focus-within .tz { border-color: var(--acc); background: var(--bs-primary-bg-subtle); }
            .sfx .tapzone:focus-within .tz .ic::after { animation: sfxRing 1.4s infinite; }
            .sfx .tz b { display: block; font-size: 12.5px; font-weight: 500; color: var(--ink); }
            .sfx .tz small { font-size: 11px; color: var(--mut); }
            .sfx .chk { list-style: none; margin: 0 0 12px; padding: 0; }
            .sfx .chk li { display: flex; gap: 8px; align-items: flex-start; padding: 5px 0; font-size: 12px; color: var(--bs-body-color); }
            .sfx .chk li + li { border-top: 1px dashed var(--ln); }
            .sfx .chk .ck { width: 17px; height: 17px; border-radius: 50%; display: grid; place-items: center; flex: none; font-size: 8px; border: 1.5px solid var(--ln); color: transparent; margin-top: 1px; }
            .sfx .chk .ok .ck { background: var(--bs-success); border-color: var(--bs-success); color: #fff; }
            .sfx .chk .warn .ck { background: var(--bs-warning-bg-subtle); border-color: var(--bs-warning); color: var(--bs-warning-text-emphasis); }
            .sfx .chk .bad .ck { background: var(--bs-danger-bg-subtle); border-color: var(--bs-danger); color: var(--bs-danger-text-emphasis); }
            .sfx .chk small { display: block; color: var(--mut); font-size: 11px; }
            .sfx .save { border-radius: 11px; padding: 9px; font-size: 14px; font-weight: 500; }

            /* phone save bar */
            @media (max-width: 991.98px) { .sfx { padding-bottom: 76px; } }
            .sfx .savebar { position: fixed; inset-inline: 0; bottom: 0; z-index: 1020; padding: 10px 16px calc(10px + env(safe-area-inset-bottom, 0px)); background: color-mix(in srgb, var(--sf) 92%, transparent); backdrop-filter: blur(12px); border-top: 1px solid var(--ln); display: flex; gap: 10px; align-items: center; }
            .sfx .savebar .st { font-size: 12px; color: var(--mut); flex: 1; min-width: 0; }
            .sfx .savebar .st b { color: var(--ink); font-weight: 500; }

            /* Tom Select (nationality) */
            .sfx .ts-wrapper .ts-control { min-height: 36px; border-radius: 10px; border-color: var(--ln); background: var(--sf); color: var(--bs-body-color); padding: 6px 11px; font-size: 13px; box-shadow: none; }
            .sfx .ts-wrapper.focus .ts-control { border-color: var(--bs-primary-border-subtle); box-shadow: 0 0 0 3px rgba(var(--acc-rgb), .14); }
            .sfx .ts-wrapper .ts-control input { color: var(--bs-body-color); font-size: 13px; }
            .sfx .ts-wrapper .ts-control input::placeholder { color: color-mix(in srgb, var(--mut) 70%, transparent); }
            .sfx .ts-dropdown { border-radius: 12px; border-color: var(--ln); background: var(--sf); color: var(--bs-body-color); box-shadow: var(--sh); overflow: hidden; margin-top: 6px; font-size: 13px; }
            .sfx .ts-dropdown .option { padding: 6px 11px; }
            .sfx .ts-dropdown .active { background: var(--bs-primary-bg-subtle); color: var(--bs-primary-text-emphasis); }
            .sfx .ts-dropdown .highlight { background: rgba(var(--bs-warning-rgb), .35); border-radius: 3px; }
        </style>
    @endpush
@endonce

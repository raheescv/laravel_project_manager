{{--
    ╔══════════════════════════════════════════════════════════════════════╗
    ║  Canteen Menu — premium "Menu sheet" system                          ║
    ║                                                                      ║
    ║  Scoped under .cmx (shared shell) + .cma (the sheet itself). Sibling ║
    ║  of the student form (.sfx) and student view (.svx): same white      ║
    ║  sheets, same theme-gradient hero, same tap-chip language.           ║
    ║                                                                      ║
    ║  Courses run down, school days across, the course column frozen.     ║
    ║  Every dish field offers the canteen's own products; the chips for   ║
    ║  the focused cell sit in one strip at the foot, so opening them      ║
    ║  never pushes a row taller.                                          ║
    ║                                                                      ║
    ║  Colours come from the settings theme (--bs-primary + Bootstrap's    ║
    ║  subtle/emphasis tokens) and follow dark mode.                       ║
    ║                                                                      ║
    ║  Preview: docs/canteen-menu-premium-preview.html (direction A)       ║
    ╚══════════════════════════════════════════════════════════════════════╝
--}}
@once
    @push('styles')
        <style>
            .cmx {
                --acc: var(--bs-primary);
                --acc-rgb: var(--bs-primary-rgb);
                /* Surfaces pinned to a crisp ramp (white panels on the app's soft page), like .sfx. */
                --sf: #ffffff;
                --ln: #e4e8ee;
                --soft: #f5f7fa;
                --mut: var(--bs-secondary-color);
                --ink: var(--bs-emphasis-color);
                --sh: 0 1px 2px rgba(15, 23, 42, .05), 0 14px 34px -18px rgba(15, 23, 42, .22);
                --hero-1: color-mix(in srgb, var(--bs-primary), #000 42%);
                --hero-2: color-mix(in srgb, var(--bs-primary), #000 4%);
                --hero-3: color-mix(in srgb, var(--bs-primary), #fff 10%);
            }

            [data-bs-theme="dark"] .cmx { --sf: #272d34; --soft: #2e353d; --ln: #3a424c;
                --sh: 0 1px 2px rgba(0, 0, 0, .4), 0 18px 40px -20px rgba(0, 0, 0, .7);
                --hero-1: color-mix(in srgb, var(--bs-primary), #000 64%);
                --hero-2: color-mix(in srgb, var(--bs-primary), #000 48%);
                --hero-3: color-mix(in srgb, var(--bs-primary), #000 30%);
            }

            @keyframes cmRise { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }
            @keyframes cmFlash { from { background: rgba(var(--bs-success-rgb), .28); } to { background: transparent; } }
            @media (prefers-reduced-motion: reduce) { .cmx *, .cmx *::before, .cmx *::after { animation: none !important; transition: none !important; } }

            .cmx .rise { animation: cmRise .5s cubic-bezier(.2, .75, .25, 1) both; animation-delay: calc(var(--i, 0) * 55ms); }
            .cmx .sheet { background: var(--sf); border-radius: 18px; box-shadow: var(--sh); border: 1px solid color-mix(in srgb, var(--ln) 60%, transparent); }
            .cmx .min-w-0 { min-width: 0; }
            .cmx [x-cloak] { display: none !important; }

            /* hero — theme gradient with a fading dot grid, as on the student pages */
            .cmx .top { padding: 15px 20px; display: flex; gap: 12px 18px; align-items: center; flex-wrap: wrap; position: relative; overflow: hidden; isolation: isolate; color: #fff; border-color: transparent;
                box-shadow: 0 16px 38px -16px rgba(16, 24, 40, .28), 0 7px 16px -10px rgba(16, 24, 40, .16);
                background:
                    radial-gradient(120% 160% at 12% -10%, rgba(255, 255, 255, .20), transparent 50%),
                    radial-gradient(90% 140% at 100% 0%, var(--hero-3), transparent 55%),
                    linear-gradient(118deg, var(--hero-1) 0%, var(--hero-2) 58%, var(--hero-3) 130%); }
            .cmx .top::after { content: ""; position: absolute; inset: 0; z-index: -1; opacity: .5; pointer-events: none;
                background-image: radial-gradient(circle at 1px 1px, rgba(255, 255, 255, .10) 1px, transparent 0); background-size: 22px 22px;
                -webkit-mask-image: linear-gradient(180deg, #000, transparent 70%); mask-image: linear-gradient(180deg, #000, transparent 70%); }
            .cmx .top .eyebrow { display: inline-flex; align-items: center; gap: 6px; padding: 5px 10px; border-radius: 999px; font-size: 10px; font-weight: 700; line-height: 1; letter-spacing: .1em; text-transform: uppercase; text-decoration: none; color: #fff; background: rgba(255, 255, 255, .16); border: 1px solid rgba(255, 255, 255, .28); margin-bottom: 8px; transition: background .15s; }
            .cmx .top .eyebrow:hover { background: rgba(255, 255, 255, .24); color: #fff; }
            .cmx .top h4 { margin: 0; font-size: clamp(17px, 1.8vw, 20px); font-weight: 700; line-height: 1.15; letter-spacing: -.02em; color: #fff; overflow-wrap: anywhere; }
            .cmx .top p { margin: 4px 0 0; font-size: 12.5px; color: rgba(255, 255, 255, .84); }
            .cmx .top .stat { display: flex; gap: 18px; margin-inline-start: auto; flex-wrap: wrap; }
            .cmx .top .stat div { text-align: center; }
            .cmx .top .stat b { display: block; font-size: 19px; font-weight: 700; line-height: 1.1; }
            .cmx .top .stat span { font-size: 10.5px; letter-spacing: .07em; text-transform: uppercase; color: rgba(255, 255, 255, .72); }

            /* section headers */
            .cmx .secline { display: flex; align-items: center; gap: 9px; padding: 12px 18px 0; flex-wrap: wrap; }
            .cmx .secline .n { width: 26px; height: 26px; border-radius: 8px; display: grid; place-items: center; font-size: 12px; background: var(--bs-primary-bg-subtle); color: var(--bs-primary-text-emphasis); flex: none; }
            .cmx .secline h5 { margin: 0; font-size: 14px; font-weight: 600; color: var(--ink); }
            .cmx .secline p { margin: 0; font-size: 11.5px; color: var(--mut); }
            .cmx .secline .r { margin-inline-start: auto; display: flex; gap: 6px; align-items: center; flex-wrap: wrap; }

            /* meal picker — real .btn-check radios dressed as tap cards */
            .cmx .meals { display: flex; gap: 8px; flex-wrap: wrap; }
            .cmx .mealc { display: flex; align-items: center; gap: 9px; padding: 8px 13px 8px 9px; border: 1.5px solid var(--ln); border-radius: 12px; cursor: pointer; background: var(--sf); margin: 0; transition: all .18s; }
            .cmx .mealc:hover { border-color: var(--bs-primary-border-subtle); }
            .cmx .mealc .mi { width: 30px; height: 30px; border-radius: 9px; display: grid; place-items: center; background: var(--soft); color: var(--mut); font-size: 14px; flex: none; transition: all .18s; }
            .cmx .mealc b { display: block; font-size: 13px; font-weight: 500; color: var(--ink); line-height: 1.2; }
            .cmx .mealc small { display: block; font-size: 11px; color: var(--mut); }
            .cmx .btn-check:checked + .mealc { border-color: var(--acc); background: var(--bs-primary-bg-subtle); box-shadow: 0 8px 20px -14px rgba(var(--acc-rgb), .8); }
            [data-bs-theme="dark"] .cmx .btn-check:checked + .mealc { border-color: var(--bs-primary-border-subtle); }
            .cmx .btn-check:checked + .mealc .mi { background: var(--acc); color: #fff; }
            .cmx .btn-check:focus-visible + .mealc { outline: 3px solid rgba(var(--acc-rgb), .35); outline-offset: 2px; }

            /* fields */
            .cmx .form-control { border-radius: 10px; border-color: var(--ln); font-size: 13px; padding: 6px 10px; min-height: 34px; background: var(--sf); color: var(--ink); }
            .cmx .form-control:focus { border-color: var(--bs-primary-border-subtle); box-shadow: 0 0 0 3px rgba(var(--acc-rgb), .14); background: var(--sf); }
            .cmx .form-control::placeholder { color: color-mix(in srgb, var(--mut) 65%, transparent); }
            .cmx .form-control:disabled { background: var(--soft); }
            .cmx .dishn { font-weight: 600; }
            .cmx .dishd { font-size: 12px; resize: none; color: var(--mut); }
            .cmx .lbl { font-size: 11px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--mut); }
            .cmx .sel { border: 1px solid var(--ln); border-radius: 10px; background: var(--sf); color: var(--ink); font-size: 12.5px; padding: 5px 9px; min-height: 32px; max-width: 220px; }

            /* chips — the auto-fill language */
            .cmx .chips { display: flex; gap: 5px; flex-wrap: wrap; align-items: center; }
            .cmx .chip { border: 1px solid var(--ln); background: var(--soft); color: var(--ink); border-radius: 999px; padding: 3px 10px; font-size: 11.5px; font-weight: 500; line-height: 1.5; display: inline-flex; align-items: center; gap: 5px; transition: all .15s; white-space: nowrap; }
            .cmx .chip:hover { border-color: var(--acc); background: var(--bs-primary-bg-subtle); color: var(--bs-primary-text-emphasis); }
            .cmx .chip i { font-size: 10px; opacity: .65; }
            .cmx .chip.tmpl { padding: 5px 12px; font-size: 12px; }
            .cmx .chipgrp { font-size: 10px; font-weight: 700; letter-spacing: .09em; text-transform: uppercase; color: var(--mut); margin-inline-end: 2px; }

            /* hover / focus tools */
            .cmx .tool { border: 1px solid transparent; background: transparent; color: var(--mut); width: 26px; height: 26px; border-radius: 8px; display: inline-grid; place-items: center; font-size: 12px; transition: all .15s; }
            .cmx .tool:hover { background: var(--soft); color: var(--ink); border-color: var(--ln); }
            .cmx .tool.danger:hover { background: var(--bs-danger-bg-subtle); color: var(--bs-danger-text-emphasis); border-color: var(--bs-danger-border-subtle); }

            /* quick fill deck */
            .cmx .qf { padding: 13px 18px; display: grid; gap: 10px; }
            .cmx .qfrow { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
            .cmx .qfrow .ttl { display: flex; align-items: center; gap: 7px; font-size: 12px; font-weight: 600; color: var(--ink); min-width: 132px; }
            .cmx .qfrow .ttl i { width: 22px; height: 22px; border-radius: 7px; display: grid; place-items: center; background: var(--bs-primary-bg-subtle); color: var(--bs-primary-text-emphasis); font-size: 11px; }
            .cmx .qfrow .hint { font-size: 11px; color: var(--mut); }

            /* footer */
            .cmx .foot { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between; padding: 12px 18px; border-top: 1px solid var(--ln); }
            .cmx .foot .note { font-size: 12px; color: var(--mut); }
            .cmx .pill { display: inline-flex; align-items: center; gap: 6px; padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 600; }
            .cmx .pill.ok { background: var(--bs-success-bg-subtle); color: var(--bs-success-text-emphasis); }
            .cmx .pill.warn { background: var(--bs-warning-bg-subtle); color: var(--bs-warning-text-emphasis); }

            /* ── the sheet ─────────────────────────────────────────────── */
            .cma .grid { overflow: auto; }
            .cma table { border-collapse: separate; border-spacing: 0; margin: 0; width: 100%; }
            .cma th, .cma td { padding: 10px; vertical-align: top; border-bottom: 1px solid var(--ln); }
            .cma thead th { position: sticky; top: 0; z-index: 3; background: var(--soft); padding: 9px 12px; }
            .cma .cx { position: sticky; inset-inline-start: 0; z-index: 2; background: var(--sf); border-inline-end: 1px solid var(--ln); width: 196px; min-width: 196px; }
            .cma thead .cx { z-index: 4; background: var(--soft); }
            .cma tbody tr:last-child td { border-bottom: 0; }
            .cma tbody tr:hover .cx { background: color-mix(in srgb, var(--bs-primary-bg-subtle) 45%, var(--sf)); }
            .cma .dayh { display: flex; align-items: center; gap: 8px; }
            .cma .dayh .dn { font-size: 13px; font-weight: 600; color: var(--ink); line-height: 1.15; }
            .cma .dayh .dc { font-size: 10.5px; color: var(--mut); }
            .cma .dayh .dt { margin-inline-start: auto; display: flex; gap: 2px; opacity: 0; transition: opacity .15s; }
            .cma thead th:hover .dt, .cma thead th:focus-within .dt { opacity: 1; }
            .cma .idx { width: 22px; height: 22px; border-radius: 7px; display: grid; place-items: center; font-size: 11px; font-weight: 600; background: var(--bs-primary-bg-subtle); color: var(--bs-primary-text-emphasis); flex: none; }
            .cma .ctools { display: flex; gap: 2px; margin-top: 7px; opacity: .25; transition: opacity .15s; }
            .cma tr:hover .ctools, .cma .cx:focus-within .ctools { opacity: 1; }
            .cma .cell { border-radius: 12px; padding: 7px; background: color-mix(in srgb, var(--soft) 55%, transparent); border: 1px solid transparent; transition: all .15s; }
            .cma .cell:hover { border-color: var(--ln); }
            .cma .cell.focused { border-color: var(--bs-primary-border-subtle); background: var(--sf); box-shadow: 0 10px 26px -18px rgba(var(--acc-rgb), .9); }
            .cma .cell .form-control { border-color: transparent; background: transparent; }
            .cma .cell .form-control:focus { background: var(--sf); border-color: var(--bs-primary-border-subtle); }
            .cma .cell.empty { background: repeating-linear-gradient(135deg, transparent 0 7px, color-mix(in srgb, var(--ln) 45%, transparent) 7px 8px); }

            /* the suggestion strip: chips for the focused cell, always in the same place */
            .cma .sugbar { padding: 10px 18px; border-top: 1px dashed var(--ln); background: color-mix(in srgb, var(--soft) 60%, transparent); animation: cmRise .18s ease-out both; }
            .cma .sugbar .who { font-size: 11px; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; color: var(--mut); margin-bottom: 6px; }
            .cma .sugbar .chip { background: var(--sf); }
            .cma .sugbar .find { border: 1px solid var(--ln); border-radius: 999px; background: var(--sf); color: var(--ink); font-size: 12px; padding: 3px 12px; min-height: 28px; width: 190px; }
            .cma .sugbar .find:focus { outline: none; border-color: var(--bs-primary-border-subtle); box-shadow: 0 0 0 3px rgba(var(--acc-rgb), .14); }

            /* what parents see */
            .cmx .phones { display: flex; gap: 12px; overflow-x: auto; padding-bottom: 4px; }
            .cmx .phone { border: 1px solid var(--ln); border-radius: 20px; background: var(--soft); padding: 12px; flex: 0 0 214px; }
            .cmx .phone.dim { opacity: .62; }
            .cmx .phone .ph { display: flex; align-items: center; gap: 8px; margin-bottom: 9px; }
            .cmx .phone .ph b { font-size: 13px; color: var(--ink); }
            .cmx .phone .ph small { font-size: 11px; color: var(--mut); }
            .cmx .phone .prow { background: var(--sf); border-radius: 12px; padding: 8px 10px; margin-bottom: 6px; }
            .cmx .phone .prow .pc { font-size: 10px; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; color: var(--mut); }
            .cmx .phone .prow .pn { font-size: 13px; font-weight: 600; color: var(--ink); }
            .cmx .phone .prow .pd { font-size: 11.5px; color: var(--mut); }
            .cmx .phone .pempty { font-size: 12px; color: var(--mut); text-align: center; padding: 14px 6px; }

            @media (max-width: 575.98px) { .cmx .top, .cmx .secline, .cmx .qf, .cmx .foot, .cma .sugbar { padding-inline: 14px; } }
        </style>
    @endpush
@endonce

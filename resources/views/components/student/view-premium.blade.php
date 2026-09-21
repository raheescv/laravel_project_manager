{{--
    ╔══════════════════════════════════════════════════════════════════════╗
    ║  Student View — "Campus ID" design system                             ║
    ║                                                                      ║
    ║  Scoped under .svx so it styles the student view shell AND every tab  ║
    ║  component rendered inside it (Card, Statement, Purchases, Top-ups)   ║
    ║  without leaking into the rest of the app.                           ║
    ║                                                                      ║
    ║  Layout is Bootstrap (row / col-* / g-*); this file only styles the   ║
    ║  look of the pieces. Every colour comes from the settings theme       ║
    ║  (--bs-primary and the Bootstrap subtle/emphasis tokens), so it       ║
    ║  follows the chosen colour scheme and dark mode.                      ║
    ║                                                                      ║
    ║  Preview: docs/student-view-premium-preview.html (direction A)        ║
    ╚══════════════════════════════════════════════════════════════════════╝
--}}
@once
    {{-- Pushed, not emitted inline: a <style> tag inside a Livewire component would
         become a second root element and break wire:click and DOM updates. --}}
    @push('styles')
        <style>
            .svx {
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
                /* Hero gradient stops, same as the .apx hero (Email log, appointments). */
                --hero-1: color-mix(in srgb, var(--bs-primary), #000 42%);
                --hero-2: color-mix(in srgb, var(--bs-primary), #000 4%);
                --hero-3: color-mix(in srgb, var(--bs-primary), #fff 10%);
                font-variant-numeric: tabular-nums;
            }

            [data-bs-theme="dark"] .svx { --sf: #272d34; --soft: #2e353d; --ln: #3a424c;
                --sh: 0 1px 2px rgba(0, 0, 0, .4), 0 18px 40px -20px rgba(0, 0, 0, .7);
                --hero-1: color-mix(in srgb, var(--bs-primary), #000 64%);
                --hero-2: color-mix(in srgb, var(--bs-primary), #000 48%);
                --hero-3: color-mix(in srgb, var(--bs-primary), #000 30%);
            }

            @keyframes svxRise { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }
            @keyframes svxPulse { 0%, 100% { box-shadow: 0 0 0 0 rgba(var(--bs-warning-rgb), .45); } 50% { box-shadow: 0 0 0 5px rgba(var(--bs-warning-rgb), 0); } }
            @media (prefers-reduced-motion: reduce) { .svx *, .svx *::before, .svx *::after { animation: none !important; transition: none !important; } }

            .svx .min-w-0 { min-width: 0; }
            .svx .mono { font-family: var(--mono); letter-spacing: .02em; }
            .svx .rise { animation: svxRise .55s cubic-bezier(.2, .75, .25, 1) both; animation-delay: calc(var(--i, 0) * 55ms); }
            .svx .sheet { background: var(--sf); border-radius: 18px; box-shadow: var(--sh); border: 1px solid color-mix(in srgb, var(--ln) 60%, transparent); }
            .svx .form-control, .svx .form-select, .svx .btn { border-radius: 10px; }
            .svx .input-group > :not(:first-child) { border-start-start-radius: 0; border-end-start-radius: 0; }
            .svx .input-group > :not(:last-child) { border-start-end-radius: 0; border-end-end-radius: 0; }

            /* ── Hero ─────────────────────────────────────────────────────── */
            /* Dark theme-colour gradient with a fading dot grid, matching the .apx hero on the
               Email log; everything inside it is re-inked white/glass below. */
            .svx .hero { padding: 16px 20px; position: relative; overflow: hidden; isolation: isolate; color: #fff; border-color: transparent;
                box-shadow: 0 16px 38px -16px rgba(16, 24, 40, .28), 0 7px 16px -10px rgba(16, 24, 40, .16);
                background:
                    radial-gradient(120% 160% at 12% -10%, rgba(255, 255, 255, .20), transparent 50%),
                    radial-gradient(90% 140% at 100% 0%, var(--hero-3), transparent 55%),
                    linear-gradient(118deg, var(--hero-1) 0%, var(--hero-2) 58%, var(--hero-3) 130%); }
            .svx .hero::after { content: ""; position: absolute; inset: 0; z-index: -1; opacity: .5; pointer-events: none;
                background-image: radial-gradient(circle at 1px 1px, rgba(255, 255, 255, .10) 1px, transparent 0); background-size: 22px 22px;
                -webkit-mask-image: linear-gradient(180deg, #000, transparent 70%); mask-image: linear-gradient(180deg, #000, transparent 70%); }
            .svx .av { width: 64px; height: 64px; border-radius: 19px; flex: none; position: relative; display: grid; place-items: center; font-size: 23px; font-weight: 600; line-height: 1; letter-spacing: .02em; color: #fff; background: linear-gradient(145deg, rgba(255, 255, 255, .24), rgba(255, 255, 255, .08)); box-shadow: 0 0 0 3px rgba(255, 255, 255, .10), 0 0 0 4.5px rgba(255, 255, 255, .22); }
            .svx .av img { width: 100%; height: 100%; object-fit: cover; border-radius: inherit; }
            .svx .av .dot { position: absolute; inset-inline-end: -4px; bottom: -4px; width: 20px; height: 20px; border-radius: 50%; border: 2.5px solid var(--hero-1); display: grid; place-items: center; color: #fff; font-size: 8.5px; }
            .svx .eyebrow { display: inline-flex; align-items: center; gap: 6px; padding: 5px 10px; border-radius: 999px; font-size: 10px; font-weight: 700; line-height: 1; letter-spacing: .1em; text-transform: uppercase; text-decoration: none; color: #fff; background: rgba(255, 255, 255, .16); border: 1px solid rgba(255, 255, 255, .28); transition: background .15s; }
            .svx .eyebrow:hover { background: rgba(255, 255, 255, .24); color: #fff; }
            .svx .eyebrow b { color: rgba(255, 255, 255, .78); font-weight: 500; letter-spacing: .06em; }
            .svx .nm { font-size: 21px; font-weight: 700; line-height: 1.15; letter-spacing: -.02em; color: #fff; margin: 6px 0 8px; overflow-wrap: anywhere; }
            .svx .hero .chip { background: rgba(255, 255, 255, .12); border-color: rgba(255, 255, 255, .22); color: #fff; }
            .svx .hero .chip i { color: rgba(255, 255, 255, .7); }
            .svx .hero .chip.ok { background: rgba(var(--bs-success-rgb), .28); border-color: rgba(var(--bs-success-rgb), .55); color: #fff; }
            .svx .hero .chip.ok i { color: color-mix(in srgb, var(--bs-success), #fff 45%); }
            .svx .hero .chip.off { background: rgba(0, 0, 0, .18); border-color: rgba(255, 255, 255, .18); color: rgba(255, 255, 255, .8); }
            .svx .chip { display: inline-flex; align-items: center; gap: 6px; padding: 4px 11px; border-radius: 999px; background: var(--soft); border: 1px solid var(--ln); font-size: 12px; color: var(--bs-body-color); white-space: nowrap; }
            .svx .chip i { color: var(--mut); }
            .svx .chip .fa-circle { font-size: 7px; }
            .svx .chip.ok { background: var(--bs-success-bg-subtle); border-color: var(--bs-success-border-subtle); color: var(--bs-success-text-emphasis); }
            .svx .chip.off { background: var(--bs-secondary-bg-subtle); border-color: var(--bs-secondary-border-subtle); color: var(--bs-secondary-text-emphasis); }
            .svx .chip.ok i, .svx .chip.off i { color: inherit; }
            .svx .pline { display: flex; align-items: center; gap: 6px 10px; margin-top: 10px; padding-top: 10px; border-top: 1px dashed rgba(255, 255, 255, .22); font-size: 12.5px; color: rgba(255, 255, 255, .8); flex-wrap: wrap; }
            .svx .pline b { color: #fff; font-weight: 600; }
            .svx .pline a:not(.btn) { color: #fff; text-decoration: none; }
            .svx .pline a:not(.btn):hover { text-decoration: underline; }
            .svx .hbtn { border-radius: 9px; font-weight: 500; padding: 4px 12px; font-size: 12px; }
            /* A theme-coloured button disappears on the theme-coloured hero: flip it to white. */
            .svx .hero .btn-primary { background: #fff; border-color: #fff; color: var(--hero-1); font-weight: 600; }
            .svx .hero .btn-primary:hover, .svx .hero .btn-primary:focus-visible { background: rgba(255, 255, 255, .88); border-color: transparent; color: var(--hero-1); }
            .svx .hero .idc-face { box-shadow: 0 22px 44px -18px rgba(0, 0, 0, .55), inset 0 0 0 1px rgba(255, 255, 255, .22), inset 0 1px 0 rgba(255, 255, 255, .25); }
            .svx .hero .idc.is-empty { border-color: rgba(255, 255, 255, .32); background: rgba(255, 255, 255, .08); color: rgba(255, 255, 255, .78); }
            .svx .hero .idc.is-empty b { color: #fff; }
            /* The hero carries a pocket-size card so the header stays short; the Card tab keeps the large one. */
            .svx .hero .idc { width: 250px; max-width: 100%; border-radius: 14px; }
            .svx .hero .idc-face { padding: 12px 15px; }
            .svx .hero .idc-top { font-size: 9px; letter-spacing: .16em; }
            .svx .hero .idc-top .fa-wifi { font-size: 13px; }
            .svx .hero .idc-chip { width: 32px; height: 24px; border-radius: 5px; margin-top: 8px; }
            .svx .hero .idc-bal small { font-size: 8.5px; }
            .svx .hero .idc-bal strong { font-size: 19px; }
            .svx .hero .idc-foot { margin-top: 6px; font-size: 9.5px; }
            .svx .hero .idc-stamp { font-size: 10px; padding: 7px 0; }
            .svx .hero .idc.is-empty { padding: 10px; font-size: 12px; }
            .svx .hero .idc.is-empty .big { font-size: 22px; margin-bottom: 4px; }

            /* ── The physical student card (components/student/id-card) ───── */
            .svx .idc { position: relative; aspect-ratio: 1.586; width: 100%; max-width: 360px; border-radius: 18px; color: #fff; transform: rotate(-2.5deg); transition: transform .45s cubic-bezier(.2, .75, .25, 1); }
            .svx .idc:hover { transform: rotate(0) translateY(-3px); }
            .svx .idc.lg { max-width: 420px; margin-inline: auto; }
            .svx .idc-face { position: absolute; inset: 0; border-radius: inherit; overflow: hidden; padding: 18px 20px; display: flex; flex-direction: column; transition: filter .3s;
                background:
                    radial-gradient(90% 80% at 100% 0%, rgba(255, 255, 255, .26), transparent 55%),
                    repeating-linear-gradient(118deg, rgba(255, 255, 255, .05) 0 2px, transparent 2px 10px),
                    radial-gradient(120% 120% at 0% 100%, color-mix(in srgb, var(--acc), #000 45%), transparent 60%),
                    linear-gradient(135deg, color-mix(in srgb, var(--acc), #000 30%), var(--acc) 55%, color-mix(in srgb, var(--acc), #fff 22%));
                box-shadow: 0 22px 44px -18px rgba(var(--acc-rgb), .75), inset 0 1px 0 rgba(255, 255, 255, .25); }
            .svx .idc-face::after { content: ""; position: absolute; inset: -40%; background: linear-gradient(105deg, transparent 42%, rgba(255, 255, 255, .28) 50%, transparent 58%); transform: translateX(-60%); transition: transform .9s ease; pointer-events: none; }
            .svx .idc:hover .idc-face::after { transform: translateX(60%); }
            .svx .idc-top { display: flex; justify-content: space-between; align-items: center; gap: 10px; font-size: 10.5px; font-weight: 500; line-height: 1.2; letter-spacing: .18em; text-transform: uppercase; opacity: .92; }
            .svx .idc-top span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .svx .idc-top .fa-wifi { transform: rotate(90deg); font-size: 17px; opacity: .9; }
            .svx .idc-chip { width: 42px; height: 31px; border-radius: 7px; margin-top: 14px;
                background: linear-gradient(90deg, transparent 32%, rgba(90, 60, 10, .35) 32% 34%, transparent 34% 66%, rgba(90, 60, 10, .35) 66% 68%, transparent 68%), linear-gradient(0deg, transparent 46%, rgba(90, 60, 10, .35) 46% 54%, transparent 54%), linear-gradient(135deg, #f7e2a3, #c9a14a 55%, #f3d98b);
                box-shadow: inset 0 0 0 1px rgba(90, 60, 10, .25); }
            .svx .idc-bal { margin-top: auto; }
            .svx .idc-bal small { display: block; font-size: 9.5px; letter-spacing: .14em; text-transform: uppercase; opacity: .75; }
            .svx .idc-bal strong { color: #fff; font-size: 25px; font-weight: 600; line-height: 1.1; letter-spacing: -.01em; }
            .svx .idc-bal strong em { font-style: normal; font-size: 12px; font-weight: 500; opacity: .75; margin-inline-start: 4px; }
            .svx .idc-foot { display: flex; justify-content: space-between; align-items: flex-end; gap: 10px; margin-top: 10px; font-size: 11px; }
            .svx .idc-foot .who { font-weight: 600; letter-spacing: .12em; text-transform: uppercase; line-height: 1.3; min-width: 0; }
            .svx .idc-foot .who span { display: block; font-weight: 400; letter-spacing: .06em; opacity: .75; }
            .svx .idc-foot .uid { font-family: var(--mono); letter-spacing: .08em; opacity: .9; text-align: end; white-space: nowrap; }
            @media (max-width: 575.98px) { .svx .idc-face { padding: 14px 16px; } .svx .idc-foot { font-size: 10px; } .svx .idc-bal strong { font-size: 22px; } }
            .svx .idc.is-blocked .idc-face { filter: grayscale(1) brightness(.78); }
            .svx .idc-stamp { position: absolute; top: 44%; inset-inline: -8px; transform: rotate(-9deg); background: var(--bs-danger); color: #fff; text-align: center; font-size: 12px; font-weight: 600; line-height: 1; letter-spacing: .3em; padding: 9px 0; box-shadow: 0 10px 22px -10px rgba(var(--bs-danger-rgb), .9); }
            .svx .idc.is-empty { transform: none; border: 2px dashed var(--ln); background: var(--soft); color: var(--mut); display: grid; place-items: center; text-align: center; padding: 16px; }
            .svx .idc.is-empty .big { font-size: 30px; opacity: .5; display: block; margin-bottom: 8px; }
            .svx .idc.is-empty b { color: var(--ink); font-weight: 500; }

            /* ── KPI tiles ────────────────────────────────────────────────── */
            .svx .kpi { padding: 16px 18px; height: 100%; }
            .svx .kpi .ic { width: 38px; height: 38px; border-radius: 12px; display: grid; place-items: center; font-size: 15px; flex: none; }
            .svx .kpi .lab { font-size: 10.5px; font-weight: 500; line-height: 1.2; letter-spacing: .1em; text-transform: uppercase; color: var(--mut); }
            .svx .kpi .val { font-size: 23px; font-weight: 600; line-height: 1.15; letter-spacing: -.02em; color: var(--ink); margin-top: 4px; }
            .svx .kpi .val small { font-size: 11px; font-weight: 500; color: var(--mut); margin-inline-start: 3px; letter-spacing: 0; }
            .svx .kpi .ft { font-size: 11.5px; color: var(--mut); margin-top: 6px; }
            .svx .meter { height: 6px; border-radius: 99px; background: var(--soft); overflow: hidden; margin-top: 10px; display: flex; gap: 2px; }
            .svx .meter i { display: block; height: 100%; border-radius: 99px; }

            /* ── Tab rail + panes ─────────────────────────────────────────── */
            .svx .rail { display: flex; gap: 2px; overflow-x: auto; padding: 0 14px; border-bottom: 1px solid var(--ln); scrollbar-width: none; }
            .svx .rail::-webkit-scrollbar { display: none; }
            .svx .rail button { position: relative; border: 0; background: none; padding: 15px 14px 13px; font-size: 13px; font-weight: 500; color: var(--mut); white-space: nowrap; display: inline-flex; align-items: center; gap: 7px; transition: color .2s; }
            .svx .rail button:hover, .svx .rail button.on { color: var(--ink); }
            .svx .rail button.on::after { content: ""; position: absolute; inset-inline: 10px; bottom: -1px; height: 2.5px; border-radius: 3px 3px 0 0; background: var(--bs-link-color); }
            .svx .rail button.on i { color: var(--bs-link-color); }
            .svx .rail .cnt { font-size: 10.5px; padding: 1px 7px; border-radius: 99px; background: var(--soft); color: var(--mut); font-weight: 500; }
            .svx .rail button.on .cnt { background: var(--bs-primary-bg-subtle); color: var(--bs-primary-text-emphasis); }
            .svx .pane { padding: 22px 24px 24px; animation: svxRise .4s cubic-bezier(.2, .75, .25, 1) both; }
            @media (max-width: 575.98px) { .svx .hero { padding: 14px 14px; } .svx .pane { padding: 18px 16px; } }
            .svx .ph { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; }
            .svx .ph .pi { width: 30px; height: 30px; border-radius: 9px; display: grid; place-items: center; background: var(--bs-primary-bg-subtle); color: var(--bs-primary-text-emphasis); font-size: 13px; flex: none; }
            .svx .ph h6 { margin: 0; font-size: 14px; font-weight: 600; color: var(--ink); }
            .svx .ph .hint { font-size: 11.5px; color: var(--mut); }

            /* ── Profile & parents ────────────────────────────────────────── */
            .svx .fld { background: var(--soft); border-radius: 12px; padding: 10px 13px; height: 100%; }
            .svx .fld .k { font-size: 10.5px; letter-spacing: .08em; text-transform: uppercase; color: var(--mut); display: flex; align-items: center; gap: 6px; }
            .svx .fld .v { font-size: 13.5px; font-weight: 500; color: var(--ink); margin-top: 3px; overflow-wrap: anywhere; }
            .svx .fld .v.none { color: var(--mut); font-weight: 400; }
            .svx .fld.note { background: var(--bs-warning-bg-subtle); }
            .svx .fld.note .k, .svx .fld.note .v { color: var(--bs-warning-text-emphasis); }
            .svx .gd { border: 1px solid var(--ln); border-radius: 15px; padding: 14px 16px; transition: border-color .2s, box-shadow .2s; }
            .svx .gd:hover { border-color: var(--bs-primary-border-subtle); box-shadow: 0 10px 24px -18px rgba(var(--acc-rgb), .6); }
            .svx .gd + .gd, .svx .lnk + .gd { margin-top: 10px; }
            .svx .gav { width: 44px; height: 44px; border-radius: 50%; display: grid; place-items: center; font-weight: 600; font-size: 14px; flex: none; background: var(--bs-primary-bg-subtle); color: var(--bs-primary-text-emphasis); }
            .svx .gd .gn { font-weight: 600; font-size: 14px; color: var(--ink); }
            .svx .gd .rel, .svx .gd .pri { font-size: 11px; padding: 2px 8px; border-radius: 99px; margin-inline-start: 6px; white-space: nowrap; }
            .svx .gd .rel { background: var(--soft); color: var(--mut); }
            .svx .gd .pri { background: var(--bs-warning-bg-subtle); color: var(--bs-warning-text-emphasis); }
            .svx .gd .ct { font-size: 12px; color: var(--mut); margin-top: 3px; display: flex; gap: 4px 14px; flex-wrap: wrap; }
            .svx .trk { display: flex; align-items: center; margin-top: 12px; font-size: 11px; color: var(--mut); flex-wrap: wrap; row-gap: 6px; }
            .svx .trk .st { display: inline-flex; align-items: center; gap: 6px; }
            .svx .trk .st i { width: 16px; height: 16px; border-radius: 50%; display: grid; place-items: center; font-size: 8px; background: var(--soft); border: 1.5px solid var(--ln); color: transparent; }
            .svx .trk .st.done { color: var(--bs-success-text-emphasis); }
            .svx .trk .st.done i { background: var(--bs-success); border-color: var(--bs-success); color: #fff; }
            .svx .trk .st.now { color: var(--bs-warning-text-emphasis); font-weight: 500; }
            .svx .trk .st.now i { border-color: var(--bs-warning); background: var(--bs-warning-bg-subtle); animation: svxPulse 1.8s infinite; }
            .svx .trk .bar { width: 26px; height: 1.5px; background: var(--ln); margin: 0 7px; }
            .svx .trk .bar.done { background: var(--bs-success); }
            .svx .trk .meta { margin-inline-start: 10px; }
            .svx .trk.off { color: var(--bs-danger-text-emphasis); }
            .svx .lnk { margin-top: 8px; border-radius: 12px; background: var(--bs-info-bg-subtle); color: var(--bs-info-text-emphasis); padding: 10px 12px; font-size: 12px; }
            .svx .empty { border: 1.5px dashed var(--ln); border-radius: 15px; padding: 18px; text-align: center; color: var(--mut); font-size: 12.5px; }
            .svx .empty i { display: block; font-size: 22px; opacity: .5; margin-bottom: 6px; }

            /* ── Card tab ─────────────────────────────────────────────────── */
            .svx .act { border: 1px solid var(--ln); border-radius: 15px; padding: 16px; }
            .svx .act + .act { margin-top: 12px; }
            .svx .act h6 { font-size: 13px; font-weight: 600; margin: 0 0 3px; color: var(--ink); }
            .svx .act p { font-size: 12px; color: var(--mut); margin: 0 0 12px; }
            .svx .act.danger { border-color: var(--bs-danger-border-subtle); background: var(--bs-danger-bg-subtle); }
            .svx .act.danger h6, .svx .act.danger p { color: var(--bs-danger-text-emphasis); }
            .svx .tl { position: relative; padding-inline-start: 26px; }
            .svx .tl::before { content: ""; position: absolute; inset-inline-start: 7px; top: 6px; bottom: 6px; width: 2px; background: var(--ln); border-radius: 2px; }
            .svx .tl .it { position: relative; padding: 0 0 16px; }
            .svx .tl .it:last-child { padding-bottom: 0; }
            .svx .tl .it::before { content: ""; position: absolute; inset-inline-start: -25px; top: 3px; width: 14px; height: 14px; border-radius: 50%; background: var(--sf); border: 3px solid var(--mut); }
            .svx .tl .it.link::before { border-color: var(--bs-primary); }
            .svx .tl .it.block::before { border-color: var(--bs-danger); }
            .svx .tl .it.unblock::before { border-color: var(--bs-success); }
            .svx .tl .t { font-size: 13px; font-weight: 500; color: var(--ink); overflow-wrap: anywhere; }
            .svx .tl .m { font-size: 11.5px; color: var(--mut); margin-top: 2px; }

            /* ── Statement / purchases / top-ups ──────────────────────────── */
            .svx .presets { display: inline-flex; gap: 4px; padding: 3px; border-radius: 11px; background: var(--soft); flex-wrap: wrap; }
            .svx .presets button { border: 0; background: none; border-radius: 8px; padding: 5px 11px; font-size: 12px; color: var(--mut); }
            .svx .presets button:hover { color: var(--ink); }
            .svx .presets button.on { background: var(--sf); color: var(--ink); box-shadow: 0 1px 3px rgba(15, 23, 42, .12); }
            .svx .range { display: flex; gap: 8px; align-items: center; }
            .svx .range .form-control { width: 150px; }
            .svx .eq { display: flex; align-items: stretch; gap: 8px; flex-wrap: wrap; }
            .svx .eq .c { flex: 1 1 130px; border-radius: 14px; padding: 12px 14px; background: var(--soft); }
            .svx .eq .c .k { font-size: 10.5px; letter-spacing: .08em; text-transform: uppercase; color: var(--mut); }
            .svx .eq .c .v { font-size: 18px; font-weight: 600; line-height: 1.2; color: var(--ink); margin-top: 4px; }
            .svx .eq .c.in .v { color: var(--bs-success-text-emphasis); }
            .svx .eq .c.out .v { color: var(--bs-danger-text-emphasis); }
            .svx .eq .c.close { background: var(--bs-primary-bg-subtle); }
            .svx .eq .c.close .k, .svx .eq .c.close .v { color: var(--bs-primary-text-emphasis); }
            .svx .eq .op { align-self: center; width: 26px; height: 26px; border-radius: 50%; border: 1px solid var(--ln); display: grid; place-items: center; font-size: 11px; color: var(--mut); flex: none; }
            @media (max-width: 575.98px) {
                .svx .eq .op { display: none; }
                .svx .eq .c { flex-basis: calc(50% - 4px); }
                .svx .range { width: 100%; }
                .svx .range .form-control { width: auto; flex: 1; min-width: 0; }
            }
            .svx .mini { border-radius: 14px; padding: 12px 14px; background: var(--soft); height: 100%; }
            .svx .mini .k { font-size: 10.5px; letter-spacing: .08em; text-transform: uppercase; color: var(--mut); }
            .svx .mini .v { font-size: 17px; font-weight: 600; line-height: 1.2; color: var(--ink); margin-top: 3px; overflow-wrap: anywhere; }
            .svx .mini .v small { font-size: 11px; font-weight: 400; color: var(--mut); }
            .svx .tblw { border: 1px solid var(--ln); border-radius: 14px; overflow: hidden; }
            .svx .tbl { margin: 0; --bs-table-bg: transparent; font-size: 12.5px; }
            .svx .tbl thead th { background: var(--soft); font-size: 10.5px; font-weight: 500; line-height: 1; letter-spacing: .1em; text-transform: uppercase; color: var(--mut); padding: 11px 14px; border-bottom: 1px solid var(--ln); white-space: nowrap; }
            .svx .tbl td { padding: 11px 14px; border-color: var(--ln); vertical-align: middle; }
            .svx .tbl tbody tr:last-child td { border-bottom: 0; }
            .svx .tbl tbody tr { transition: background .15s; }
            .svx .tbl tbody tr:hover { background: color-mix(in srgb, var(--acc) 4%, transparent); }
            .svx .tbl .bf td { background: color-mix(in srgb, var(--soft) 60%, transparent); color: var(--mut); font-style: italic; }
            .svx .tbl tfoot td { background: var(--soft); font-weight: 600; color: var(--ink); padding: 11px 14px; border: 0; }
            .svx .tbl .none td { text-align: center; color: var(--mut); padding: 26px 14px; }
            .svx .tc { display: inline-flex; align-items: center; gap: 6px; padding: 3px 10px 3px 4px; border-radius: 99px; font-size: 11.5px; font-weight: 500; white-space: nowrap; background: var(--soft); color: var(--ink); }
            .svx .tc i { width: 19px; height: 19px; border-radius: 50%; display: grid; place-items: center; font-size: 9px; color: #fff; background: var(--mut); }
            .svx .tc.plain { padding-inline-start: 10px; }
            .svx .tc.topup { background: var(--bs-success-bg-subtle); color: var(--bs-success-text-emphasis); }
            .svx .tc.topup i { background: var(--bs-success); }
            .svx .tc.purchase { background: var(--bs-primary-bg-subtle); color: var(--bs-primary-text-emphasis); }
            .svx .tc.purchase i { background: var(--acc); }
            .svx .tc.return { background: var(--bs-warning-bg-subtle); color: var(--bs-warning-text-emphasis); }
            .svx .tc.return i { background: var(--bs-warning); }
            .svx .tc.refund { background: var(--bs-danger-bg-subtle); color: var(--bs-danger-text-emphasis); }
            .svx .tc.refund i { background: var(--bs-danger); }
            .svx .sp { display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 500; padding: 3px 10px; border-radius: 99px; white-space: nowrap; background: var(--soft); color: var(--mut); }
            .svx .sp::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
            .svx .sp.success { background: var(--bs-success-bg-subtle); color: var(--bs-success-text-emphasis); }
            .svx .sp.failed { background: var(--bs-danger-bg-subtle); color: var(--bs-danger-text-emphasis); }
            .svx .sp.pending { background: var(--bs-warning-bg-subtle); color: var(--bs-warning-text-emphasis); }
            .svx .sp.pending::before { animation: svxPulse 1.8s infinite; }
            .svx a.inv { color: var(--bs-link-color); text-decoration: none; font-weight: 500; }
            .svx a.inv:hover { text-decoration: underline; }
            .svx .pagination { margin: 14px 0 0; }
            .svx [x-cloak] { display: none !important; }

            /* ── Sortable column headers ──────────────────────────────────
               The shared x-sortable-header link, wearing the table head's own
               ink instead of .text-dark (which is !important, hence the override). */
            .svx thead th a { color: inherit !important; text-decoration: none; display: inline-flex; align-items: baseline; gap: 5px; transition: color .15s; }
            .svx thead th a:hover { color: var(--acc) !important; }
            .svx thead th a small { font-size: 10px; line-height: 1; }
            .svx thead th a .fa { font-size: 10px !important; line-height: 1; }
            .svx .tbl thead th:has(a) { padding-block: 8px; }

            /* ── Record top-up modal (.tpm) ───────────────────────────────
               Premium "Flow" sheet: a coloured head that follows the entry
               direction, the amount as the headline, then one row per question. */
            .svx .tpm .modal-dialog { max-width: 560px; }
            .svx .tpm .modal-content { border: 0; border-radius: 18px; overflow: hidden; background: var(--sf); box-shadow: 0 30px 70px -26px rgba(15, 23, 42, .55); }
            .svx .tpm .mh { display: flex; gap: 14px; align-items: center; padding: 18px 20px; color: #fff; }
            .svx .tpm .mh.in { background: linear-gradient(120deg, var(--hero-1), var(--hero-2) 58%, var(--hero-3)); }
            .svx .tpm .mh.out { background: linear-gradient(120deg, color-mix(in srgb, var(--bs-danger), #000 46%), color-mix(in srgb, var(--bs-danger), #000 10%) 58%, color-mix(in srgb, var(--bs-danger), #fff 8%)); }
            .svx .tpm .mh .mi { width: 42px; height: 42px; border-radius: 14px; background: rgba(255, 255, 255, .18); display: grid; place-items: center; font-size: 16px; flex: none; }
            /* #fff explicitly: a bare h5 otherwise takes --bs-heading-color, which is ink on the light theme. */
            .svx .tpm .mh h5 { margin: 0; font-size: 16px; font-weight: 600; color: #fff; }
            .svx .tpm .mh p { margin: 2px 0 0; font-size: 12px; opacity: .85; }
            .svx .tpm .mh .btn-close { margin-inline-start: auto; filter: invert(1) grayscale(100%) brightness(200%); opacity: .75; }
            .svx .tpm .modal-body { padding: 20px; }
            .svx .tpm .fl { margin-bottom: 16px; }
            .svx .tpm .row .fl { margin-bottom: 0; }
            .svx .tpm .lb { display: block; font-size: 10.5px; letter-spacing: .09em; text-transform: uppercase; font-weight: 600; color: var(--mut); margin-bottom: 7px; }
            .svx .tpm .form-control, .svx .tpm .form-select { border-radius: 12px; padding: 9px 12px; border-color: var(--ln); background-color: var(--sf); color: var(--ink); }
            .svx .tpm .form-control:focus, .svx .tpm .form-select:focus { border-color: var(--acc); box-shadow: 0 0 0 3px rgba(var(--acc-rgb), .16); }

            /* Entry: two tap targets, not a dropdown — the office clerk picks a side. */
            .svx .tpm .seg { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
            .svx .tpm .seg:has(.opt:only-child) { grid-template-columns: 1fr; }
            .svx .tpm .opt { position: relative; display: flex; gap: 10px; align-items: center; padding: 12px; margin: 0; border: 1px solid var(--ln); border-radius: 14px; background: var(--sf); cursor: pointer; transition: border-color .15s, background .15s, box-shadow .15s; }
            .svx .tpm .opt:hover { border-color: color-mix(in srgb, var(--acc) 45%, var(--ln)); }
            .svx .tpm .opt .ic { width: 34px; height: 34px; border-radius: 11px; display: grid; place-items: center; background: var(--soft); color: var(--mut); font-size: 13px; flex: none; transition: background .15s, color .15s; }
            .svx .tpm .opt .tx b { display: block; font-size: 13px; font-weight: 600; line-height: 1.2; color: var(--ink); }
            .svx .tpm .opt .tx small { display: block; margin-top: 2px; font-size: 10.5px; color: var(--mut); }
            .svx .tpm .opt .tk { position: absolute; inset-block-start: 9px; inset-inline-end: 10px; font-size: 13px; opacity: 0; transition: opacity .15s; }
            .svx .tpm .opt:has(input:focus-visible) { box-shadow: 0 0 0 3px rgba(var(--acc-rgb), .2); }
            .svx .tpm .opt.in.on { border-color: var(--bs-success); background: var(--bs-success-bg-subtle); }
            .svx .tpm .opt.in.on .ic { background: var(--bs-success); color: #fff; }
            .svx .tpm .opt.in.on .tk { opacity: 1; color: var(--bs-success); }
            .svx .tpm .opt.out.on { border-color: var(--bs-danger); background: var(--bs-danger-bg-subtle); }
            .svx .tpm .opt.out.on .ic { background: var(--bs-danger); color: #fff; }
            .svx .tpm .opt.out.on .tk { opacity: 1; color: var(--bs-danger); }

            /* Amount: the headline of the form. */
            .svx .tpm .amt { display: flex; align-items: center; gap: 10px; padding: 6px 8px 6px 14px; border: 1px solid var(--ln); border-radius: 14px; background: var(--soft); transition: border-color .15s, box-shadow .15s; }
            .svx .tpm .amt:focus-within { border-color: var(--acc); box-shadow: 0 0 0 3px rgba(var(--acc-rgb), .16); }
            .svx .tpm .amt.bad, .svx .tpm .amt.bad:focus-within { border-color: var(--bs-danger); box-shadow: 0 0 0 3px rgba(var(--bs-danger-rgb), .16); }
            .svx .tpm .amt input { flex: 1; min-width: 0; border: 0; outline: 0; background: transparent; font-size: 24px; font-weight: 600; color: var(--ink); appearance: textfield; }
            .svx .tpm .amt input::-webkit-outer-spin-button, .svx .tpm .amt input::-webkit-inner-spin-button { appearance: none; margin: 0; }
            .svx .tpm .qk { display: flex; gap: 6px; flex: none; }
            .svx .tpm .qk button { border: 1px solid var(--ln); background: var(--sf); color: var(--mut); border-radius: 9px; padding: 5px 10px; font-size: 12px; font-weight: 600; transition: border-color .15s, color .15s; }
            .svx .tpm .qk button:hover { border-color: var(--acc); color: var(--acc); }
            .svx .tpm .hint { margin-top: 7px; font-size: 11.5px; color: var(--mut); }
            .svx .tpm .hint b { color: var(--ink); }
            .svx .tpm .hint .bad, .svx .tpm .hint .bad b { color: var(--bs-danger-text-emphasis); }
            .svx .tpm .sug { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
            .svx .tpm .sug button { border: 1px dashed var(--ln); background: transparent; color: var(--mut); border-radius: 99px; padding: 3px 10px; font-size: 11px; transition: border-color .15s, color .15s; }
            .svx .tpm .sug button:hover { border-style: solid; border-color: var(--acc); color: var(--acc); }
            .svx .tpm .note { margin: 0; padding: 10px 12px; border-radius: 12px; background: var(--soft); color: var(--mut); font-size: 11.5px; }
            .svx .tpm .mf { display: flex; justify-content: flex-end; gap: 8px; padding: 14px 20px; border-top: 1px solid var(--ln); background: var(--soft); }
            .svx .tpm .mf .btn { border-radius: 11px; padding: 8px 18px; font-size: 13px; font-weight: 500; }
            .svx .tpm .mf .btn-light { background: var(--sf); border: 1px solid var(--ln); color: var(--ink); }
            @media (max-width: 575.98px) {
                .svx .tpm .seg { grid-template-columns: 1fr; }
                .svx .tpm .amt { flex-wrap: wrap; }
                .svx .tpm .amt input { font-size: 20px; }
            }
        </style>
    @endpush
@endonce

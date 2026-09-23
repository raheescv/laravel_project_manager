{{--
    ╔══════════════════════════════════════════════════════════════════════╗
    ║  Employee Stock Transfer — "Dispatch Desk" premium design system      ║
    ║                                                                      ║
    ║  Scoped under .ivx so it cannot leak into the rest of the app.       ║
    ║  LAYOUT IS BOOTSTRAP (row / col-* / d-flex / gap-*); this file only   ║
    ║  styles the look of the pieces — deck, picker, table, rail.          ║
    ║                                                                      ║
    ║  Every colour derives from the active SETTINGS THEME (--bs-primary   ║
    ║  and Bootstrap's own tokens), so it follows the tenant's chosen      ║
    ║  scheme and light/dark mode automatically.                           ║
    ║                                                                      ║
    ║  Preview: docs/inventory-employee-transfer-preview.html (design A)   ║
    ╚══════════════════════════════════════════════════════════════════════╝
--}}
@once
    @push('styles')
        <style>
            .ivx {
                /* ── Accent: single source → settings theme primary ───────── */
                --ivx-acc: var(--bs-primary);
                --ivx-acc-d: color-mix(in srgb, var(--ivx-acc), #000 16%);
                --ivx-acc-deep: color-mix(in srgb, var(--ivx-acc), #000 46%);
                --ivx-tint: color-mix(in srgb, var(--ivx-acc), transparent 92%);
                --ivx-tint2: color-mix(in srgb, var(--ivx-acc), transparent 84%);

                /* ── Neutrals ─────────────────────────────────────────────── */
                --ivx-sf: var(--bs-body-bg);
                --ivx-sf2: color-mix(in srgb, var(--bs-body-color), transparent 96%);
                --ivx-sf3: color-mix(in srgb, var(--bs-body-color), transparent 92%);
                --ivx-ink: var(--bs-body-color);
                --ivx-ink2: color-mix(in srgb, var(--bs-body-color), transparent 22%);
                --ivx-mut: color-mix(in srgb, var(--bs-body-color), transparent 46%);
                --ivx-ln: color-mix(in srgb, var(--bs-body-color), transparent 88%);
                --ivx-lns: color-mix(in srgb, var(--bs-body-color), transparent 93%);

                --ivx-r: 14px;
                --ivx-sh: 0 1px 2px rgba(16, 24, 40, .05), 0 14px 32px -22px rgba(16, 24, 40, .4);
                --ivx-fz: 13px;

                font-size: var(--ivx-fz);
                color: var(--ivx-ink);
            }

            [data-bs-theme="dark"] .ivx {
                /* The settings themes ship a deep primary that reads as ink on a dark
                   canvas — lift it so accent text, pills and totals stay legible. */
                --ivx-acc: color-mix(in srgb, var(--bs-primary), #fff 42%);
                --ivx-acc-d: color-mix(in srgb, var(--bs-primary), #fff 18%);
                --ivx-acc-deep: var(--bs-primary);
                --ivx-sf2: color-mix(in srgb, #fff, transparent 95%);
                --ivx-sf3: color-mix(in srgb, #fff, transparent 91%);
                --ivx-ln: color-mix(in srgb, #fff, transparent 88%);
                --ivx-lns: color-mix(in srgb, #fff, transparent 93%);
                --ivx-tint: color-mix(in srgb, var(--ivx-acc), transparent 88%);
                --ivx-tint2: color-mix(in srgb, var(--ivx-acc), transparent 78%);
                --ivx-sh: 0 1px 2px rgba(0, 0, 0, .3), 0 16px 34px -24px rgba(0, 0, 0, .9);
            }

            .ivx [x-cloak] { display: none !important; }

            .ivx .ivx-card {
                background: var(--ivx-sf);
                border: 1px solid var(--ivx-ln);
                border-radius: var(--ivx-r);
                box-shadow: var(--ivx-sh);
            }

            .ivx .ivx-num { font-variant-numeric: tabular-nums; }

            .ivx .ivx-k {
                font-size: 9.5px;
                font-weight: 800;
                letter-spacing: 1px;
                text-transform: uppercase;
                color: var(--ivx-mut);
            }

            /* ── Buttons ──────────────────────────────────────────────────── */
            .ivx .ivx-btn {
                border: 1px solid var(--ivx-ln);
                background: var(--ivx-sf);
                color: var(--ivx-ink2);
                padding: 8px 14px;
                border-radius: 10px;
                font-size: 12.5px;
                font-weight: 700;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                gap: 7px;
                white-space: nowrap;
                transition: .15s;
            }

            .ivx a.ivx-btn,
            .ivx a.ivx-btn:hover { text-decoration: none; }

            .ivx .ivx-btn:hover { background: var(--ivx-sf2); transform: translateY(-1px); }

            .ivx .ivx-btn-pri {
                background: linear-gradient(180deg, var(--ivx-acc), var(--ivx-acc-d));
                border-color: var(--ivx-acc-d);
                color: #fff;
                box-shadow: 0 9px 20px -10px color-mix(in srgb, var(--ivx-acc), transparent 15%);
            }

            .ivx .ivx-btn-pri:disabled,
            .ivx .ivx-btn:disabled {
                background: var(--ivx-sf3);
                border-color: var(--ivx-ln);
                color: var(--ivx-mut);
                box-shadow: none;
                cursor: not-allowed;
                transform: none;
            }

            .ivx .ivx-btn-gho { background: transparent; }
            .ivx .ivx-btn-lg { padding: 12px 18px; font-size: 13.5px; border-radius: 12px; }
            .ivx .ivx-btn-ico { width: 30px; height: 30px; padding: 0; justify-content: center; border-radius: 9px; }

            /* ── Chips ────────────────────────────────────────────────────── */
            .ivx .ivx-pill {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                padding: 3px 9px;
                border-radius: 999px;
                font-size: 10px;
                font-weight: 800;
                letter-spacing: .4px;
                background: var(--ivx-sf3);
                color: var(--ivx-ink2);
            }

            .ivx .ivx-pill-acc { background: var(--ivx-tint2); color: var(--ivx-acc); }
            .ivx .ivx-pill-ok { background: rgba(var(--bs-success-rgb), .14); color: var(--bs-success); }
            .ivx .ivx-pill-warn { background: rgba(var(--bs-warning-rgb), .16); color: var(--bs-warning); }
            .ivx .ivx-pill-bad { background: rgba(var(--bs-danger-rgb), .14); color: var(--bs-danger); }

            .ivx .ivx-bc {
                font-size: 10.5px;
                background: var(--ivx-sf3);
                border: 1px solid var(--ivx-lns);
                border-radius: 6px;
                padding: 1px 6px;
                color: var(--ivx-ink2);
            }

            /* ── Avatar ───────────────────────────────────────────────────── */
            .ivx .ivx-av {
                flex: none;
                width: 38px;
                height: 38px;
                border-radius: 12px;
                display: grid;
                place-items: center;
                color: #fff;
                font-weight: 800;
                font-size: 13px;
                background: linear-gradient(145deg, var(--ivx-acc), var(--ivx-acc-deep));
                object-fit: cover;
            }

            .ivx .ivx-av-sm { width: 30px; height: 30px; border-radius: 10px; font-size: 11px; }
            .ivx .ivx-av-lg { width: 50px; height: 50px; border-radius: 15px; font-size: 17px; }
            .ivx .ivx-av-quiet { background: var(--ivx-sf3); color: var(--ivx-mut); }

            /* ── Deck (sticky header) ─────────────────────────────────────── */
            .ivx .ivx-deck { position: sticky; top: 0; z-index: 20; overflow: hidden; }
            .ivx .ivx-deck-top { display: flex; align-items: center; gap: 13px; padding: 12px 15px; flex-wrap: wrap; }

            .ivx .ivx-mark {
                width: 40px;
                height: 40px;
                border-radius: 13px;
                display: grid;
                place-items: center;
                font-size: 17px;
                color: #fff;
                background: linear-gradient(145deg, var(--ivx-acc-d), var(--ivx-acc-deep));
            }

            .ivx .ivx-title { font-size: 16px; font-weight: 800; letter-spacing: -.25px; }
            .ivx .ivx-readout .v { font-size: 19px; font-weight: 800; letter-spacing: -.4px; line-height: 1.15; }

            .ivx .ivx-flow { display: flex; border-top: 1px solid var(--ivx-lns); background: var(--ivx-sf2); }
            .ivx .ivx-flow .node { display: flex; align-items: center; gap: 9px; padding: 9px 15px; flex: 1; min-width: 0; }
            .ivx .ivx-flow .node+.node { border-inline-start: 1px solid var(--ivx-lns); }
            .ivx .ivx-flow .arrow { display: grid; place-items: center; width: 40px; background: var(--ivx-sf3); color: var(--ivx-mut); flex: none; }
            .ivx .ivx-flow .nm { font-weight: 700; font-size: 12.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .ivx .ivx-flow .node.ghost .nm { color: var(--ivx-mut); font-weight: 600; font-style: italic; }

            /* ── Section head ─────────────────────────────────────────────── */
            .ivx .ivx-sec-h {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 12px 15px 11px;
                border-bottom: 1px solid var(--ivx-lns);
                flex-wrap: wrap;
            }

            .ivx .ivx-step {
                width: 23px;
                height: 23px;
                border-radius: 8px;
                display: grid;
                place-items: center;
                font-size: 11px;
                font-weight: 800;
                background: var(--ivx-tint2);
                color: var(--ivx-acc);
                flex: none;
            }

            .ivx .ivx-sec-h h3 { margin: 0; font-size: 13.5px; font-weight: 800; letter-spacing: -.15px; }
            .ivx .ivx-sec-b { padding: 14px 15px; }

            /* ── Searchable picker ────────────────────────────────────────── */
            .ivx .ivx-pick { position: relative; }

            .ivx .ivx-pick-field {
                display: flex;
                align-items: center;
                gap: 9px;
                background: var(--ivx-sf2);
                border: 1.5px solid var(--ivx-ln);
                border-radius: 12px;
                padding: 0 12px;
                transition: .15s;
            }

            .ivx .ivx-pick-field:focus-within {
                border-color: var(--ivx-acc);
                background: var(--ivx-sf);
                box-shadow: 0 0 0 4px var(--ivx-tint);
            }

            .ivx .ivx-pick-field>i { color: var(--ivx-mut); font-size: 14px; }

            .ivx .ivx-pick-field input {
                flex: 1;
                border: 0;
                background: transparent;
                outline: none;
                font-size: 13px;
                color: var(--ivx-ink);
                padding: 12px 0;
                min-width: 0;
            }

            .ivx .ivx-pick-field input::placeholder { color: var(--ivx-mut); }

            .ivx .ivx-pick-menu {
                position: absolute;
                inset-inline: 0;
                top: calc(100% + 7px);
                z-index: 30;
                background: var(--ivx-sf);
                border: 1px solid var(--ivx-ln);
                border-radius: 13px;
                box-shadow: 0 22px 48px -18px rgba(16, 24, 40, .42);
                overflow: hidden;
            }

            [data-bs-theme="dark"] .ivx .ivx-pick-menu { box-shadow: 0 22px 48px -14px rgba(0, 0, 0, .8); }

            .ivx .ivx-pick-scroll { max-height: 310px; overflow: auto; }

            .ivx .ivx-pick-head,
            .ivx .ivx-pick-foot {
                padding: 7px 13px;
                background: var(--ivx-sf2);
                display: flex;
                justify-content: space-between;
                gap: 12px;
                flex-wrap: wrap;
            }

            .ivx .ivx-pick-head { border-bottom: 1px solid var(--ivx-lns); }
            .ivx .ivx-pick-foot { border-top: 1px solid var(--ivx-lns); font-size: 10.5px; color: var(--ivx-mut); }

            .ivx .ivx-pick-opt {
                display: flex;
                align-items: center;
                gap: 11px;
                padding: 9px 13px;
                cursor: pointer;
                border-bottom: 1px solid var(--ivx-lns);
                width: 100%;
                text-align: start;
                background: transparent;
                border-inline: 0;
                border-top: 0;
            }

            .ivx .ivx-pick-opt:last-child { border-bottom: 0; }
            .ivx .ivx-pick-opt:hover { background: var(--ivx-tint); }
            .ivx .ivx-pick-opt .nm { font-weight: 700; font-size: 12.5px; }
            .ivx .ivx-pick-opt .sub { font-size: 11px; color: var(--ivx-mut); margin-top: 1px; display: flex; gap: 7px; flex-wrap: wrap; }
            .ivx .ivx-pick-empty { padding: 22px 14px; text-align: center; color: var(--ivx-mut); font-size: 12px; }

            /* ── Recipient card ───────────────────────────────────────────── */
            .ivx .ivx-who {
                display: flex;
                align-items: center;
                gap: 13px;
                padding: 13px 14px;
                border-radius: 13px;
                background: linear-gradient(135deg, var(--ivx-tint), transparent 70%);
                border: 1.5px solid var(--ivx-acc);
                flex-wrap: wrap;
            }

            .ivx .ivx-who .nm { font-size: 14.5px; font-weight: 800; letter-spacing: -.2px; }
            .ivx .ivx-who .sub { font-size: 11.5px; color: var(--ivx-mut); margin-top: 2px; }

            .ivx .ivx-qchip {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 6px 11px 6px 6px;
                border-radius: 999px;
                cursor: pointer;
                background: var(--ivx-sf2);
                border: 1px solid var(--ivx-ln);
                font-size: 12px;
                font-weight: 700;
                color: var(--ivx-ink2);
                transition: .15s;
            }

            .ivx .ivx-qchip:hover { border-color: var(--ivx-acc); color: var(--ivx-acc); transform: translateY(-1px); }

            /* ── Tables ───────────────────────────────────────────────────── */
            .ivx .ivx-tbl { width: 100%; border-collapse: collapse; }

            .ivx .ivx-tbl th {
                font-size: 9.5px;
                font-weight: 800;
                letter-spacing: .9px;
                text-transform: uppercase;
                color: var(--ivx-mut);
                text-align: start;
                padding: 9px 12px;
                background: var(--ivx-sf2);
                border-bottom: 1px solid var(--ivx-ln);
                white-space: nowrap;
            }

            .ivx .ivx-tbl td { padding: 11px 12px; border-bottom: 1px solid var(--ivx-lns); vertical-align: middle; }
            .ivx .ivx-tbl tr:last-child td { border-bottom: 0; }
            .ivx .ivx-tbl .e { text-align: end; }
            .ivx .ivx-pname { font-weight: 700; font-size: 12.5px; }
            .ivx .ivx-pmeta { display: flex; gap: 6px; align-items: center; flex-wrap: wrap; margin-top: 4px; }

            .ivx .ivx-thumb {
                width: 40px;
                height: 40px;
                border-radius: 10px;
                background: var(--ivx-sf3);
                border: 1px solid var(--ivx-lns);
                display: grid;
                place-items: center;
                color: var(--ivx-mut);
                font-size: 15px;
                flex: none;
            }

            .ivx .ivx-rowlabel { display: none; }

            /* ── Quantity stepper ─────────────────────────────────────────── */
            .ivx .ivx-qty {
                display: inline-flex;
                align-items: center;
                background: var(--ivx-sf2);
                border: 1.5px solid var(--ivx-ln);
                border-radius: 11px;
                overflow: hidden;
            }

            .ivx .ivx-qty:focus-within { border-color: var(--ivx-acc); box-shadow: 0 0 0 4px var(--ivx-tint); }

            .ivx .ivx-qty button {
                width: 32px;
                height: 34px;
                border: 0;
                background: transparent;
                color: var(--ivx-ink2);
                font-size: 13px;
                cursor: pointer;
            }

            .ivx .ivx-qty button:hover { background: var(--ivx-tint2); color: var(--ivx-acc); }

            .ivx .ivx-qty input {
                width: 62px;
                border: 0;
                background: transparent;
                text-align: center;
                font-size: 13.5px;
                font-weight: 800;
                color: var(--ivx-ink);
                outline: none;
                font-variant-numeric: tabular-nums;
                -moz-appearance: textfield;
            }

            .ivx .ivx-qty input::-webkit-outer-spin-button,
            .ivx .ivx-qty input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }

            /* ── Inputs ───────────────────────────────────────────────────── */
            .ivx .ivx-label {
                display: block;
                font-size: 11px;
                font-weight: 800;
                letter-spacing: .4px;
                text-transform: uppercase;
                color: var(--ivx-mut);
                margin-bottom: 6px;
            }

            .ivx .ivx-inp {
                width: 100%;
                border: 1.5px solid var(--ivx-ln);
                background: var(--ivx-sf2);
                border-radius: 11px;
                padding: 10px 12px;
                font-size: 13px;
                color: var(--ivx-ink);
                outline: none;
                transition: .15s;
            }

            .ivx .ivx-inp:focus { border-color: var(--ivx-acc); background: var(--ivx-sf); box-shadow: 0 0 0 4px var(--ivx-tint); }
            .ivx textarea.ivx-inp { resize: vertical; min-height: 74px; }

            .ivx .ivx-rchip {
                font-size: 11px;
                font-weight: 700;
                padding: 5px 10px;
                border-radius: 999px;
                background: var(--ivx-sf2);
                border: 1px dashed var(--ivx-ln);
                color: var(--ivx-ink2);
                cursor: pointer;
            }

            .ivx .ivx-rchip:hover,
            .ivx .ivx-rchip.on { border-style: solid; border-color: var(--ivx-acc); background: var(--ivx-tint); color: var(--ivx-acc); }

            /* ── Summary rail ─────────────────────────────────────────────── */
            .ivx .ivx-rail { position: sticky; top: 150px; }

            .ivx .ivx-sum {
                display: flex;
                flex-direction: column;
                gap: 1px;
                background: var(--ivx-lns);
                border-radius: 12px;
                overflow: hidden;
                border: 1px solid var(--ivx-ln);
            }

            .ivx .ivx-sum .r { display: flex; justify-content: space-between; align-items: center; gap: 10px; padding: 10px 13px; background: var(--ivx-sf); }
            .ivx .ivx-sum .r .lbl { font-size: 11.5px; color: var(--ivx-ink2); font-weight: 600; }
            .ivx .ivx-sum .r .val { font-size: 13.5px; font-weight: 800; font-variant-numeric: tabular-nums; }
            .ivx .ivx-sum .r.total { background: var(--ivx-tint); }
            .ivx .ivx-sum .r.total .val { font-size: 17px; color: var(--ivx-acc); }

            .ivx .ivx-guard {
                display: flex;
                gap: 9px;
                padding: 10px 12px;
                border-radius: 11px;
                background: rgba(var(--bs-warning-rgb), .1);
                border: 1px solid rgba(var(--bs-warning-rgb), .28);
                font-size: 11.5px;
                color: var(--ivx-ink2);
                line-height: 1.5;
            }

            .ivx .ivx-guard i { color: var(--bs-warning); font-size: 14px; margin-top: 1px; }

            /* ── Empty states ─────────────────────────────────────────────── */
            .ivx .ivx-empty { padding: 40px 18px; text-align: center; }

            .ivx .ivx-empty .big {
                width: 56px;
                height: 56px;
                border-radius: 18px;
                margin: 0 auto 12px;
                display: grid;
                place-items: center;
                background: var(--ivx-tint2);
                color: var(--ivx-acc);
                font-size: 23px;
            }

            .ivx .ivx-empty h4 { margin: 0 0 5px; font-size: 14px; font-weight: 800; }
            .ivx .ivx-empty p { margin: 0; font-size: 12px; color: var(--ivx-mut); }

            /* ── Phone: the tables fold into cards ────────────────────────── */
            @media (max-width: 767.98px) {
                .ivx { --ivx-fz: 12.5px; }
                .ivx .ivx-rail { position: static; }
                .ivx .ivx-flow { flex-direction: column; }
                .ivx .ivx-flow .node+.node { border-inline-start: 0; border-top: 1px solid var(--ivx-lns); }
                .ivx .ivx-flow .arrow { width: 100%; height: 26px; }
                .ivx .ivx-flow .arrow i { transform: rotate(90deg); display: block; }

                .ivx .ivx-tbl,
                .ivx .ivx-tbl tbody,
                .ivx .ivx-tbl tr,
                .ivx .ivx-tbl td { display: block; width: 100%; }

                .ivx .ivx-tbl thead { display: none; }

                .ivx .ivx-tbl tr {
                    border: 1px solid var(--ivx-ln);
                    border-radius: 13px;
                    margin-bottom: 9px;
                    background: var(--ivx-sf2);
                    overflow: hidden;
                }

                .ivx .ivx-tbl td {
                    border-bottom: 1px solid var(--ivx-lns);
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 12px;
                    padding: 9px 12px;
                }

                .ivx .ivx-tbl td:last-child { border-bottom: 0; }
                .ivx .ivx-tbl td.e { text-align: start; }

                .ivx .ivx-rowlabel {
                    display: block;
                    font-size: 9.5px;
                    font-weight: 800;
                    letter-spacing: .9px;
                    text-transform: uppercase;
                    color: var(--ivx-mut);
                }
            }
        </style>
    @endpush
@endonce

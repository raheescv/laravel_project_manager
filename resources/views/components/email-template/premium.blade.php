{{--
    ╔══════════════════════════════════════════════════════════════════════╗
    ║  Email Template Console — ".etx" design system                       ║
    ║                                                                      ║
    ║  Extends the .apx token set (the page root carries "apx etx"), so    ║
    ║  colour tracks the settings theme (--bs-primary) and dark mode.      ║
    ║                                                                      ║
    ║  Layout: module icon rail · live preview stage · right inspector,    ║
    ║  topped by a console bar and finished with a mono status bar.        ║
    ║                                                                      ║
    ║  The .etx-mail block is deliberately LIGHT-ONLY: it mirrors the      ║
    ║  Editorial wrapper (resources/views/mail/appointment/template.blade  ║
    ║  .php), and a customer's inbox has no dark mode we control.          ║
    ╚══════════════════════════════════════════════════════════════════════╝
--}}
@once
    <style>
        .etx [x-cloak]{ display:none !important; }

        /* ── shell ─────────────────────────────────────────────────── */
        .etx .etx-shell{ background:var(--surface); border:1px solid var(--border); border-radius:14px; overflow:hidden; box-shadow:var(--shadow-md); }
        .etx .etx-bar{ display:flex; align-items:center; gap:10px; min-height:52px; padding:8px 14px; flex-wrap:wrap;
                       background:var(--surface-2); border-bottom:1px solid var(--border); }
        .etx .etx-bar h4{ margin:0; font-size:13px; font-weight:800; letter-spacing:-.01em; }
        .etx .etx-bar .sub{ font-size:10.5px; color:var(--text-3); }
        .etx .etx-sp{ flex:1; }

        .etx .etx-grid{ display:grid; grid-template-columns:236px minmax(0,1fr) minmax(0,50%);
                        height:clamp(540px, calc(100vh - 300px), 860px); min-height:0; }

        /* ── template list rail ────────────────────────────────────── */
        .etx .etx-rail{ background:var(--surface-2); border-inline-end:1px solid var(--border); display:flex;
                        flex-direction:column; min-height:0; min-width:0; }
        .etx .etx-rail-search{ position:relative; padding:10px 10px 8px; border-bottom:1px solid var(--border); }
        .etx .etx-rail-search i{ position:absolute; inset-inline-start:20px; top:19px; font-size:10.5px; color:var(--text-3); }
        .etx .etx-rail-search input{ width:100%; background:var(--surface); border:1px solid var(--border-strong);
                        color:var(--text); border-radius:8px; height:30px; padding:0 10px 0 28px; font-size:11.5px;
                        font-family:inherit; }
        .etx .etx-rail-search input:focus{ outline:none; border-color:var(--brand);
                        box-shadow:0 0 0 3px rgba(var(--brand-rgb),.14); }
        .etx .etx-rail-list{ flex:1; min-height:0; overflow:auto; padding-bottom:6px; }
        .etx .etx-rail-g{ display:flex; align-items:center; gap:6px; font-size:9px; font-weight:800; letter-spacing:.11em;
                        text-transform:uppercase; color:var(--text-3); padding:12px 12px 5px; }
        .etx .etx-rail-g i{ font-size:10px; color:var(--brand); }
        .etx .etx-tpl{ display:flex; align-items:center; gap:8px; width:100%; text-align:start; padding:9px 12px;
                        cursor:pointer; border:0; background:transparent; font-family:inherit; color:var(--text);
                        border-inline-start:3px solid transparent; }
        .etx .etx-tpl:hover{ background:var(--surface-3); }
        .etx .etx-tpl.on{ background:var(--surface); border-inline-start-color:var(--brand); }
        .etx .etx-tpl .fg{ flex:1; min-width:0; }
        .etx .etx-tpl .nm{ font-size:11.5px; font-weight:700; line-height:1.25; overflow:hidden;
                        text-overflow:ellipsis; white-space:nowrap; }
        .etx .etx-tpl .ty{ font-size:9.5px; color:var(--text-3); margin-top:1px; overflow:hidden;
                        text-overflow:ellipsis; white-space:nowrap; }
        .etx .etx-rail-foot{ padding:10px; border-top:1px solid var(--border); display:grid; gap:6px; }
        .etx .etx-rail-empty{ padding:12px; }

        /* ── preview stage ─────────────────────────────────────────── */
        .etx .etx-pv{ display:flex; flex-direction:column; min-width:0; min-height:0; height:100%; }
        .etx .etx-pv-bar{ display:flex; align-items:center; gap:9px; padding:8px 12px; border-bottom:1px solid var(--border);
                      background:var(--surface-2); flex-wrap:wrap; }
        .etx .etx-pv-bar .t{ font-size:9.5px; font-weight:800; letter-spacing:.12em; text-transform:uppercase;
                      color:var(--text-3); display:inline-flex; align-items:center; gap:7px; }
        .etx .etx-pv-bar .live{ width:6px; height:6px; border-radius:50%; background:var(--success); box-shadow:0 0 0 3px var(--success-bg); }
        .etx .etx-pv-seg{ display:flex; background:var(--surface); border:1px solid var(--border-strong); border-radius:7px;
                      padding:2px; gap:1px; margin-inline-start:auto; }
        .etx .etx-pv-seg button{ border:0; background:transparent; color:var(--text-3); font-size:11px; padding:3px 9px;
                      border-radius:5px; cursor:pointer; }
        .etx .etx-pv-seg button.on{ background:rgba(var(--brand-rgb),.12); color:var(--brand); }
        .etx .etx-pv-meta{ padding:8px 14px; border-bottom:1px solid var(--border); background:var(--surface);
                      font-size:10.5px; color:var(--text-3); }
        .etx .etx-pv-meta .s{ font-size:12.5px; font-weight:700; color:var(--text); margin-bottom:1px; overflow-wrap:anywhere; }
        .etx .etx-pv-canvas{ flex:1; min-height:0; overflow:auto; padding:24px 14px; background:
                      linear-gradient(rgba(var(--brand-rgb),.03), rgba(var(--brand-rgb),.03)),
                      repeating-linear-gradient(0deg, transparent 0 15px, rgba(127,137,150,.08) 15px 16px),
                      repeating-linear-gradient(90deg, transparent 0 15px, rgba(127,137,150,.08) 15px 16px); }

        /* ── the Editorial email (light-only on purpose) ───────────── */
        .etx .etx-mail{ max-width:600px; margin:0 auto; background:#fffdf9; border:1px solid #ece5da;
                      box-shadow:0 18px 40px -20px rgba(30,25,15,.35); transition:max-width .25s ease; }
        .etx .etx-pv-canvas.is-mob .etx-mail{ max-width:370px; }
        .etx .etx-mail-hd{ padding:32px 40px 0; text-align:center; }
        .etx .etx-mail-hd img{ width:52px; height:52px; border-radius:50%; object-fit:contain; background:#fff; }
        .etx .etx-mail-hd .lg{ width:50px; height:50px; border-radius:50%; background:var(--em-acc,#1D4ED8); color:#fff;
                      display:inline-flex; align-items:center; justify-content:center;
                      font-family:Georgia,'Times New Roman',serif; font-size:21px; font-weight:700; }
        .etx .etx-mail-hd .co{ margin-top:14px; font-size:10px; letter-spacing:.28em; text-transform:uppercase;
                      color:#a89880; font-weight:700; }
        .etx .etx-mail-hd .rule{ width:44px; height:2px; background:var(--em-acc,#1D4ED8); margin:16px auto 0; }
        .etx .etx-mail-bd{ padding:24px 40px 30px; font-family:Georgia,'Times New Roman',serif; font-size:14.5px;
                      line-height:1.8; color:#4a4238; text-align:start; overflow-wrap:anywhere; }
        .etx .etx-pv-canvas.is-mob .etx-mail-hd{ padding:24px 22px 0; }
        .etx .etx-pv-canvas.is-mob .etx-mail-bd{ padding:20px 22px 24px; }
        .etx .etx-mail-bd p{ margin:0 0 15px; }
        .etx .etx-mail-bd h1, .etx .etx-mail-bd h2, .etx .etx-mail-bd h3{ margin:0 0 12px; font-weight:400;
                      letter-spacing:-.02em; color:#1a1a1a; }
        .etx .etx-mail-bd h1{ font-size:24px; } .etx .etx-mail-bd h2{ font-size:20px; } .etx .etx-mail-bd h3{ font-size:17px; }
        .etx .etx-mail-bd ul, .etx .etx-mail-bd ol{ margin:0 0 15px; padding-inline-start:20px; }
        .etx .etx-mail-bd strong, .etx .etx-mail-bd b{ color:#1a1a1a; }
        .etx .etx-mail-bd a{ color:var(--em-acc,#1D4ED8); }
        .etx .etx-mail-bd .etx-mail-empty{ color:#b3a794; font-style:italic; }
        .etx .etx-mail-ft{ padding:18px 40px 26px; border-top:1px solid #ece5da;
                      font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:10.5px; line-height:1.9;
                      color:#a89880; text-align:center; }
        .etx .etx-mail-ft b{ color:#8c7f6d; }

        /* ── inspector ─────────────────────────────────────────────── */
        .etx .etx-insp{ border-inline-start:1px solid var(--border); background:var(--surface); min-height:0; height:100%;
                      overflow:auto; display:flex; flex-direction:column; }
        .etx .etx-insp-hd{ display:flex; align-items:center; gap:9px; padding:11px 14px; border-bottom:1px solid var(--border);
                      background:var(--surface-2); position:sticky; top:0; z-index:2; }
        .etx .etx-insp-hd > i{ color:var(--brand); }
        .etx .etx-insp-hd .t{ font-size:12px; font-weight:800; flex:1; min-width:0; overflow:hidden;
                      text-overflow:ellipsis; white-space:nowrap; }
        .etx .etx-acc{ border-bottom:1px solid var(--border); }
        .etx .etx-acc .ah{ display:flex; align-items:center; gap:8px; width:100%; padding:10px 14px; font-size:9.5px;
                      font-weight:800; letter-spacing:.11em; text-transform:uppercase; color:var(--text-3); cursor:pointer;
                      background:transparent; border:0; font-family:inherit; }
        .etx .etx-acc .ah:hover{ color:var(--text); }
        .etx .etx-acc .ah i{ margin-inline-start:auto; font-size:9px; }
        .etx .etx-acc .ab{ padding:2px 14px 13px; }
        .etx .etx-insp-ft{ margin-top:auto; display:flex; align-items:center; gap:8px; padding:12px 14px; flex-wrap:wrap; }

        /* ── active switch ─────────────────────────────────────────── */
        .etx .etx-sw{ display:inline-flex; align-items:center; gap:7px; cursor:pointer; font-size:11px; font-weight:700;
                      margin:0; user-select:none; }
        .etx .etx-sw input{ position:absolute; opacity:0; pointer-events:none; }
        .etx .etx-sw .tr{ width:30px; height:17px; border-radius:999px; background:var(--surface-3);
                      border:1px solid var(--border-strong); position:relative; transition:.15s; flex:none; }
        .etx .etx-sw .tr::after{ content:""; position:absolute; top:1.5px; inset-inline-start:2px; width:12px; height:12px;
                      border-radius:50%; background:#fff; box-shadow:0 1px 2px rgba(0,0,0,.3); transition:.15s; }
        .etx .etx-sw.on .tr{ background:var(--success); border-color:var(--success); }
        .etx .etx-sw.on .tr::after{ inset-inline-start:14px; }

        /* ── status bar ────────────────────────────────────────────── */
        .etx .etx-status{ display:flex; align-items:center; gap:14px; min-height:30px; padding:4px 14px;
                      border-top:1px solid var(--border); background:var(--surface-2);
                      font-family:ui-monospace,Menlo,Consolas,monospace; font-size:10px; letter-spacing:.04em;
                      color:var(--text-3); flex-wrap:wrap; }
        .etx .etx-status b{ color:var(--text-2); font-weight:600; }
        .etx .etx-status .ok{ color:var(--success); }
        .etx .etx-status .bad{ color:var(--danger); }

        /* ── responsive ────────────────────────────────────────────── */
        @media (max-width: 1099.98px){
            .etx .etx-grid{ grid-template-columns:1fr; height:auto; }
            .etx .etx-rail{ border-inline-end:0; border-bottom:1px solid var(--border); }
            .etx .etx-rail-list{ max-height:240px; }
            .etx .etx-pv{ min-height:460px; max-height:70vh; }
            .etx .etx-insp{ border-inline-start:0; border-top:1px solid var(--border);
                      height:auto; overflow:visible; }
        }
        @media (max-width: 575.98px){
            .etx .etx-pv-canvas{ padding:14px 8px; }
        }

        /* ── full-screen page mode (.etx-page wraps the component) ───
           The console fills everything below the app header: flush edges,
           no radius, and the grid flexes to whatever height remains.
           --etx-top is measured by the host page (settings/email-template/
           index.blade.php) so banners and resizes are accounted for. ── */
        .etx-page{ margin:0; }
        /* The console is the whole page — the app footer below it would just
           add a scrollbar's worth of dead space. */
        .etx-page ~ footer{ display:none; }
        .etx-page .etx-shell{ display:flex; flex-direction:column;
                      height:calc(100dvh - var(--etx-top, 66px));
                      border-radius:0; border-inline:0; border-top:0; box-shadow:none; }
        .etx-page .etx-grid{ flex:1; height:auto; min-height:0; }
        @media (max-width: 1099.98px){
            /* Stacked panes need to scroll the window, not fight a fixed height. */
            .etx-page .etx-shell{ height:auto; min-height:calc(100dvh - var(--etx-top, 66px)); }
            .etx-page .etx-grid{ flex:none; }
        }

        /* ── standalone (chromeless) mode: the console IS the window ── */
        .etx-standalone{ --etx-top: 0px; }
        .etx-standalone .etx-shell{ border:0; }
    </style>
@endonce

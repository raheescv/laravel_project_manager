@once
    <style>
        /* Online payment details (.opd) — sits over the .wrx studio page, same emerald accent. */
        .opd {
            --opd-ac: #0f9d76;
            --opd-ac-soft: #e7f6f1;
            --opd-ink: #171a20;
            --opd-ink-2: #4c5361;
            --opd-mut: #8b93a3;
            --opd-line: #e6e8ec;
            --opd-soft: #f6f7f9;
            --opd-card: #ffffff;
            --opd-red: #d94b4b;
            --opd-red-soft: #fdeaea;
            --opd-amber: #b4791a;
            --opd-amber-soft: #fdf3e3;
            --opd-blue: #3d6ad6;
            --opd-blue-soft: #eaf0fd;
            --opd-code: #0f1420;
            --opd-mono: ui-monospace, 'SF Mono', 'Cascadia Code', Menlo, monospace;
            color: var(--opd-ink);
            font-size: 13px;
        }

        [data-bs-theme="dark"] .opd {
            color-scheme: dark;
            --opd-ac: #2ec294;
            --opd-ac-soft: rgba(46, 194, 148, .14);
            --opd-ink: #eaf0fb;
            --opd-ink-2: #c2cbdd;
            --opd-mut: #8b96ad;
            --opd-line: rgba(255, 255, 255, .10);
            --opd-soft: rgba(255, 255, 255, .04);
            --opd-card: #161b28;
            --opd-red: #f16b6b;
            --opd-red-soft: rgba(241, 107, 107, .14);
            --opd-amber: #e0ab4a;
            --opd-amber-soft: rgba(224, 171, 74, .14);
            --opd-blue: #7fa2f5;
            --opd-blue-soft: rgba(127, 162, 245, .14);
            --opd-code: #0b0f18;
        }

        .opd .modal-content {
            background: var(--opd-card);
            border: 0;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 30px 80px -20px rgba(15, 20, 32, .45);
        }

        .opd-hero {
            position: relative;
            padding: 20px 24px 0;
            background: linear-gradient(180deg, var(--opd-ac-soft), transparent);
            border-bottom: 1px solid var(--opd-line);
        }

        .opd-close {
            position: absolute;
            top: 14px;
            inset-inline-end: 14px;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            border: 1px solid var(--opd-line);
            background: var(--opd-card);
            color: var(--opd-ink-2);
            display: grid;
            place-items: center;
        }

        .opd-close:hover { color: var(--opd-ink); border-color: var(--opd-mut); }

        .opd-eyebrow {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            color: var(--opd-mut);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            padding-inline-end: 44px;
        }

        .opd-amount {
            display: flex;
            align-items: baseline;
            gap: 10px;
            flex-wrap: wrap;
            margin: 6px 0 4px;
        }

        .opd-amount b {
            font-size: 30px;
            font-weight: 800;
            letter-spacing: -.02em;
            line-height: 1.1;
            font-variant-numeric: tabular-nums;
        }

        .opd-amount small { color: var(--opd-mut); font-size: 14px; font-weight: 700; }

        .opd-who { color: var(--opd-ink-2); font-size: 14px; }
        .opd-who strong { color: var(--opd-ink); }

        .opd-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 11.5px;
            font-weight: 700;
            letter-spacing: 0;
            text-transform: none;
            background: var(--opd-soft);
            color: var(--opd-ink-2);
            border: 1px solid var(--opd-line);
        }

        .opd-pill.ok { background: var(--opd-ac-soft); color: var(--opd-ac); border-color: transparent; }
        .opd-pill.warn { background: var(--opd-amber-soft); color: var(--opd-amber); border-color: transparent; }
        .opd-pill.bad { background: var(--opd-red-soft); color: var(--opd-red); border-color: transparent; }
        .opd-pill.info { background: var(--opd-blue-soft); color: var(--opd-blue); border-color: transparent; }

        .opd-chips { display: flex; gap: 6px; flex-wrap: wrap; margin: 12px 0 14px; }
        .opd-chips a.opd-pill { text-decoration: none; }
        .opd-chips a.opd-pill:hover { border-color: var(--opd-ac); color: var(--opd-ac); }

        .opd-tabs { display: flex; gap: 2px; overflow-x: auto; scrollbar-width: none; margin-bottom: -1px; }
        .opd-tabs::-webkit-scrollbar { display: none; }

        .opd-tabs button {
            border: 0;
            background: none;
            padding: 10px 14px;
            color: var(--opd-mut);
            font-weight: 700;
            font-size: 12.5px;
            border-bottom: 2px solid transparent;
            white-space: nowrap;
        }

        .opd-tabs button:hover { color: var(--opd-ink); }
        .opd-tabs button.is-on { color: var(--opd-ac); border-bottom-color: var(--opd-ac); }
        .opd-tabs .n { color: var(--opd-mut); font-weight: 600; margin-inline-start: 3px; }

        .opd .modal-body { padding: 20px 24px; background: var(--opd-soft); }

        .opd-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }

        .opd-card {
            background: var(--opd-card);
            border: 1px solid var(--opd-line);
            border-radius: 14px;
            padding: 14px 16px;
            min-width: 0;
        }

        .opd-card h6 {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0 0 8px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: var(--opd-mut);
        }

        .opd-card h6 i { color: var(--opd-ac); font-size: 13px; }

        .opd-kv {
            display: grid;
            grid-template-columns: 118px minmax(0, 1fr);
            gap: 10px;
            padding: 7px 0;
            border-top: 1px dashed var(--opd-line);
            align-items: start;
        }

        .opd-kv:first-of-type { border-top: 0; }
        .opd-kv > span { color: var(--opd-mut); }
        .opd-kv > div { display: flex; align-items: flex-start; gap: 6px; min-width: 0; font-weight: 600; }
        .opd-kv .v { overflow-wrap: anywhere; }
        .opd-mono { font-family: var(--opd-mono); font-size: 12px; font-weight: 500; }

        .opd-copy {
            flex: none;
            border: 0;
            background: none;
            padding: 0 2px;
            color: var(--opd-mut);
            line-height: 1.4;
        }

        .opd-copy:hover, .opd-copy.is-done { color: var(--opd-ac); }

        .opd-plates { display: flex; gap: 6px; margin: 2px 0 6px; }

        .opd-plate {
            flex: 1;
            text-align: center;
            border: 1.5px solid #1f5fae;
            border-radius: 8px;
            padding: 4px 6px;
            background: var(--opd-card);
        }

        .opd-plate span { display: block; font-size: 10px; color: var(--opd-mut); text-transform: uppercase; font-weight: 700; }
        .opd-plate b { font-family: var(--opd-mono); font-size: 16px; color: #1f5fae; }
        [data-bs-theme="dark"] .opd-plate { border-color: #6f9fe0; }
        [data-bs-theme="dark"] .opd-plate b { color: #9dc0f0; }

        .opd-map { display: inline-flex; gap: 6px; align-items: center; font-weight: 700; color: var(--opd-ac); text-decoration: none; margin-top: 4px; }

        .opd-lines { width: 100%; border-collapse: collapse; }
        .opd-lines th { font-size: 11px; text-transform: uppercase; letter-spacing: .05em; color: var(--opd-mut); font-weight: 700; padding: 0 8px 8px; }
        .opd-lines td { padding: 10px 8px; border-top: 1px solid var(--opd-line); font-variant-numeric: tabular-nums; }
        .opd-lines .r { text-align: end; }
        .opd-lines tfoot td { font-weight: 800; border-top: 2px solid var(--opd-line); }

        .opd-log { display: grid; gap: 12px; }

        .opd-ex { background: var(--opd-card); border: 1px solid var(--opd-line); border-radius: 14px; overflow: hidden; }

        .opd-ex-head {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding: 12px 14px;
            background: none;
            border: 0;
            text-align: start;
            color: var(--opd-ink);
        }

        .opd-verb {
            flex: none;
            font-family: var(--opd-mono);
            font-size: 10.5px;
            font-weight: 800;
            padding: 3px 7px;
            border-radius: 6px;
            background: var(--opd-blue-soft);
            color: var(--opd-blue);
        }

        .opd-verb.in { background: var(--opd-ac-soft); color: var(--opd-ac); }
        .opd-ex-head b { display: block; font-size: 13px; }
        .opd-ex-head small { display: block; color: var(--opd-mut); overflow-wrap: anywhere; }
        .opd-ex-head .opd-mono { color: var(--opd-mut); }
        .opd-ex-head .chev { margin-inline-start: auto; color: var(--opd-mut); transition: transform .2s; }
        .opd-ex.is-open .chev { transform: rotate(180deg); }

        .opd-code { position: relative; background: var(--opd-code); }

        .opd-code pre {
            margin: 0;
            padding: 14px 16px;
            max-height: 380px;
            overflow: auto;
            color: #d6deeb;
            font-family: var(--opd-mono);
            font-size: 11.5px;
            line-height: 1.55;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .opd-code .opd-copy-btn {
            position: absolute;
            top: 8px;
            inset-inline-end: 10px;
            border: 1px solid rgba(255, 255, 255, .15);
            background: rgba(255, 255, 255, .06);
            color: #d6deeb;
            border-radius: 8px;
            padding: 3px 10px;
            font-size: 11.5px;
        }

        .opd-code .opd-copy-btn:hover { background: rgba(255, 255, 255, .12); }
        .opd-empty { padding: 12px 14px; color: var(--opd-mut); border-top: 1px solid var(--opd-line); }

        .opd .modal-footer {
            padding: 12px 24px;
            border-top: 1px solid var(--opd-line);
            background: var(--opd-card);
            gap: 8px;
        }

        .opd-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            border-radius: 10px;
            padding: 8px 14px;
            font-weight: 700;
            font-size: 12.5px;
            border: 1px solid var(--opd-line);
            background: var(--opd-card);
            color: var(--opd-ink);
            text-decoration: none;
        }

        .opd-btn:hover { border-color: var(--opd-mut); color: var(--opd-ink); }
        .opd-btn.danger { color: var(--opd-red); border-color: var(--opd-red-soft); background: var(--opd-red-soft); }
        .opd-btn.danger:hover { border-color: var(--opd-red); }
        .opd-btn.solid { background: var(--opd-ink); border-color: var(--opd-ink); color: var(--opd-card); }

        .opd-note {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            padding: 10px 14px;
            border-radius: 12px;
            background: var(--opd-red-soft);
            color: var(--opd-red);
            font-weight: 600;
            margin-bottom: 14px;
        }

        .opd-why {
            --why: var(--opd-red);
            --why-soft: var(--opd-red-soft);
            border: 1px solid var(--opd-line);
            border-left: 4px solid var(--why);
            border-radius: 12px;
            background: var(--opd-card);
            margin-bottom: 14px;
            overflow: hidden;
        }

        .opd-why.warn { --why: var(--opd-amber); --why-soft: var(--opd-amber-soft); }
        .opd-why.ok { --why: var(--opd-ac); --why-soft: var(--opd-ac-soft); }
        .opd-why-head { display: flex; gap: 10px; align-items: flex-start; padding: 12px 14px; background: var(--why-soft); }
        .opd-why-head > i { color: var(--why); margin-top: 3px; }
        .opd-why-head b { display: block; color: var(--why); font-size: 14.5px; }
        .opd-why-head p { margin: 2px 0 0; color: var(--opd-ink); font-size: 13px; }
        .opd-why-own { padding: 8px 14px; font-size: 12.5px; color: var(--opd-ink-2); border-bottom: 1px solid var(--opd-line); }
        .opd-why-body { display: grid; grid-template-columns: minmax(0, 1.6fr) minmax(0, 1fr); gap: 14px; padding: 12px 14px; }
        .opd-why-facts { margin: 0; padding-left: 18px; color: var(--opd-ink-2); font-size: 12.5px; }
        .opd-why-facts li + li { margin-top: 4px; }
        .opd-why-trail { list-style: none; margin: 0; padding: 0 0 0 14px; border-left: 2px solid var(--opd-line); font-size: 12px; }
        .opd-why-trail li { position: relative; padding-bottom: 8px; }
        .opd-why-trail li::before { content: ''; position: absolute; left: -19px; top: 4px; width: 8px; height: 8px; border-radius: 50%; background: var(--opd-ac); }
        .opd-why-trail li.end::before { background: var(--why); }
        .opd-why-trail b { display: block; color: var(--opd-ink); }
        .opd-why-trail span { color: var(--opd-mut); }
        .opd-why-next { padding: 9px 14px; border-top: 1px solid var(--opd-line); background: var(--opd-soft); font-size: 12.5px; font-weight: 600; color: var(--opd-ink); }
        .opd-why-next i { color: var(--why); margin-right: 4px; }

        @media (max-width: 767.98px) {
            .opd-hero { padding: 16px 16px 0; }
            .opd .modal-body { padding: 14px 16px; }
            .opd .modal-footer { padding: 10px 16px; }
            .opd-grid { grid-template-columns: minmax(0, 1fr); }
            .opd-why-body { grid-template-columns: minmax(0, 1fr); }
            .opd-amount b { font-size: 26px; }
            .opd-kv { grid-template-columns: 100px minmax(0, 1fr); }
            .opd .modal-content { border-radius: 0; }
            .opd .modal-footer .opd-btn { flex: 1; justify-content: center; }
            .opd-lines .hide-sm { display: none; }
        }
    </style>
@endonce

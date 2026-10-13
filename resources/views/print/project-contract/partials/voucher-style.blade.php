{{-- Shared look of the project-contract vouchers: the rent-out receipt / voucher design. --}}
<style>
    :root {
        --accent:      #0E8A4F;
        --accent-dark: #0A6B3C;
        --gold:        #B8860B;
        --ink:         #1a1a1a;
        --ink-soft:    #555;
        --line:        #e0e0e0;
    }

    body {
        margin: 0;
        padding: 28px 36px;
        color: var(--ink);
        background: #fff;
        font-family: 'DejaVu Sans', Arial, sans-serif;
        font-size: 11px;
        line-height: 1.5;
    }

    .b-head { width: 100%; border-collapse: collapse; border-bottom: 2px solid var(--ink); padding-bottom: 12px; }
    .b-head td { vertical-align: middle; padding: 0; }
    .b-eyebrow { font-size: 8px; letter-spacing: 3px; text-transform: uppercase; color: var(--accent); font-weight: 700; margin-bottom: 3px; }
    .b-head .co-name { font-size: 17px; font-weight: 700; letter-spacing: .5px; color: var(--ink); margin: 0 0 3px; }
    .b-head .co-meta { font-size: 9.5px; color: var(--ink-soft); line-height: 1.7; }
    .b-head .logo-cell { text-align: right; width: 200px; vertical-align: top; }
    .b-head .logo-cell img { max-width: 190px; max-height: 90px; }

    .b-title { text-align: center; margin: 18px 0 6px; }
    .b-title .t { font-size: 15px; font-weight: 700; letter-spacing: 5px; text-transform: uppercase; color: var(--ink); }
    .b-title .rule { width: 60px; height: 3px; background: var(--gold); margin: 7px auto 0; }
    .b-title .stamp { display: inline-block; margin-top: 8px; padding: 2px 12px; border: 2px solid #b3261e; color: #b3261e; font-weight: 700; letter-spacing: 3px; font-size: 11px; }

    .b-meta { width: 100%; border-collapse: collapse; margin: 14px 0 18px; }
    .b-meta td { font-size: 10.5px; padding: 3px 0; }
    .b-meta .k { color: var(--ink-soft); }
    .b-meta .v { font-weight: 700; text-align: right; }

    .b-amount { text-align: center; padding: 14px 0; border-top: 1px solid var(--line); border-bottom: 1px solid var(--line); margin-bottom: 8px; }
    .b-amount .lbl { font-size: 8.5px; letter-spacing: 1.6px; text-transform: uppercase; color: var(--ink-soft); margin-bottom: 3px; }
    .b-amount .fig { font-size: 30px; font-weight: 700; color: var(--ink); letter-spacing: 1px; }
    .b-amount .cur { font-size: 14px; color: var(--accent); font-weight: 700; }

    .b-words { text-align: center; font-style: italic; color: var(--ink-soft); font-size: 10.5px; margin-bottom: 22px; }
    .b-words b { color: var(--ink); font-style: normal; }

    .b-fields { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    .b-fields td { padding: 7px 0; border-bottom: 1px dotted #ccc; vertical-align: top; }
    .b-fields .f-label { width: 26%; font-size: 10px; font-weight: 600; color: var(--ink-soft); white-space: nowrap; }
    .b-fields .f-colon { width: 1%; padding: 7px 8px; color: #aaa; }
    .b-fields .f-value { font-size: 11px; color: var(--ink); word-break: break-word; unicode-bidi: plaintext; }
    .b-fields .muted { color: var(--ink-soft); font-size: 9.5px; }
    .b-fields .neg { color: #b3261e; font-weight: 700; }

    .sig-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
    .sig-table td { width: 50%; text-align: center; padding-top: 38px; vertical-align: bottom; }
    .sig-table .sig-line { border-top: 1px solid #777; width: 74%; margin: 0 auto 5px; }
    .sig-table .sig-lbl { font-size: 9px; color: var(--ink-soft); text-transform: uppercase; letter-spacing: 0.8px; }

    .footer { margin-top: 18px; border-top: 1px solid #ddd; padding-top: 6px; text-align: center; font-size: 8px; color: #aaa; }
</style>

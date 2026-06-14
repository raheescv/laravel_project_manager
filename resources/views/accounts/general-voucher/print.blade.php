@php
    $voucherTypes = [
        'receipt' => 'Receipt Voucher',
        'payment' => 'Payment Voucher',
        'general' => 'General Voucher',
    ];
    $voucherLabel = $voucherTypes[$voucherType] ?? 'General Voucher';
    $vtClass = 'vt-' . (in_array($voucherType, ['receipt', 'payment', 'general']) ? $voucherType : 'general');

    $totalDebit = 0;
    $totalCredit = 0;
    foreach ($journal->entries as $e) {
        $totalDebit += $e->debit;
        $totalCredit += $e->credit;
    }
    $hasPersonColumn = $journal->entries->where('person_name', '!=', null)->count() > 0;
    $grandTotal = max($totalDebit, $totalCredit);
    $isBalanced = round($totalDebit, 2) == round($totalCredit, 2);

    // Amount in words (e.g. "One Thousand Two Hundred Thirty Four and 50/100 Only")
    $whole = (int) floor($grandTotal);
    $cents = (int) round(($grandTotal - $whole) * 100);
    $amountInWords = 'Zero';
    if (class_exists('NumberFormatter')) {
        try {
            $amountInWords = ucwords((new \NumberFormatter('en', \NumberFormatter::SPELLOUT))->format($whole));
        } catch (\Throwable $th) {
            $amountInWords = number_format($whole);
        }
    } else {
        $amountInWords = number_format($whole);
    }
    $amountInWords .= $cents > 0 ? ' and ' . str_pad($cents, 2, '0', STR_PAD_LEFT) . '/100' : '';
    $amountInWords .= ' Only';
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $voucherLabel }} - {{ $journal->reference_number ?? '#' . $journal->id }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --ink: #0f172a;
            --ink-soft: #334155;
            --muted: #64748b;
            --faint: #94a3b8;
            --line: #e6e9ef;
            --line-soft: #f1f4f8;
            --paper: #ffffff;
            --gold: #b08d57;
            --gold-soft: #d8c39c;
            /* General voucher (sapphire) — overridden per type below */
            --accent: #1e3a8a;
            --accent-2: #3b82f6;
            --accent-tint: #eef2fb;
            --accent-ink: #1e3a8a;
        }

        body.vt-receipt {
            --accent: #0f766e;
            --accent-2: #14b8a6;
            --accent-tint: #ecfbf8;
            --accent-ink: #115e52;
        }

        body.vt-payment {
            --accent: #9f1239;
            --accent-2: #e11d48;
            --accent-tint: #fdeef2;
            --accent-ink: #881337;
        }

        body.vt-general {
            --accent: #1e3a8a;
            --accent-2: #3b82f6;
            --accent-tint: #eef2fb;
            --accent-ink: #1e3a8a;
        }

        html {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body {
            font-family: -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.45;
            color: var(--ink);
            background: var(--paper);
            font-size: 12px;
            -webkit-font-smoothing: antialiased;
        }

        @media screen {
            body {
                background:
                    radial-gradient(1200px 500px at 50% -10%, #ffffff, transparent),
                    linear-gradient(135deg, #eef2f7 0%, #e3e8f0 100%);
                padding: 34px 16px 60px;
                min-height: 100vh;
            }
        }

        /* ---------- Sheet ---------- */
        .sheet {
            position: relative;
            width: 100%;
            max-width: 820px;
            margin: 0 auto;
            background: var(--paper);
            border: 1px solid var(--line);
            border-radius: 10px;
            overflow: hidden;
        }

        @media screen {
            .sheet {
                box-shadow: 0 24px 60px -20px rgba(15, 23, 42, .35), 0 6px 18px -8px rgba(15, 23, 42, .18);
            }
        }

        .accent-strip {
            height: 6px;
            background: linear-gradient(90deg, var(--accent) 0%, var(--accent-2) 100%);
        }

        .gold-strip {
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--gold-soft) 18%, var(--gold) 50%, var(--gold-soft) 82%, transparent);
        }

        .pad {
            padding: 26px 30px 22px;
            position: relative;
            z-index: 1;
        }

        /* watermark */
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-24deg);
            font-size: 120px;
            font-weight: 800;
            letter-spacing: 8px;
            color: var(--accent);
            opacity: .04;
            white-space: nowrap;
            z-index: 0;
            pointer-events: none;
            text-transform: uppercase;
        }

        /* ---------- Header ---------- */
        .head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 18px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
        }

        .brand-logo {
            max-width: 96px;
            max-height: 64px;
            object-fit: contain;
            flex: none;
        }

        .brand-text {
            min-width: 0;
        }

        .company-name {
            font-family: Georgia, "Times New Roman", serif;
            font-size: 22px;
            font-weight: 700;
            color: var(--ink);
            line-height: 1.15;
            letter-spacing: .2px;
        }

        .company-details {
            font-size: 10.5px;
            color: var(--muted);
            line-height: 1.5;
            margin-top: 3px;
        }

        .company-details .sep {
            color: var(--gold);
            margin: 0 5px;
        }

        .type-badge {
            flex: none;
            text-align: right;
        }

        .type-pill {
            display: inline-block;
            padding: 8px 16px;
            background: var(--accent);
            color: #fff;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            border-radius: 6px;
            box-shadow: 0 6px 14px -6px var(--accent);
        }

        .type-sub {
            margin-top: 7px;
            font-size: 9px;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            color: var(--faint);
        }

        .divider {
            height: 1px;
            background: linear-gradient(90deg, var(--line), var(--line) 100%);
            margin: 16px 0 0;
        }

        /* ---------- Meta cards ---------- */
        .meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            margin-top: 16px;
        }

        .meta-card {
            background: var(--line-soft);
            border: 1px solid var(--line);
            border-left: 3px solid var(--accent);
            border-radius: 6px;
            padding: 8px 11px;
        }

        .meta-label {
            display: block;
            font-size: 8.5px;
            font-weight: 700;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: var(--faint);
        }

        .meta-value {
            display: block;
            margin-top: 3px;
            font-size: 12.5px;
            font-weight: 700;
            color: var(--ink);
            word-break: break-word;
        }

        /* ---------- Hero amount ---------- */
        .hero {
            margin-top: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            background:
                linear-gradient(120deg, var(--accent-tint), #ffffff 85%);
            border: 1px solid var(--line);
            border-left: 4px solid var(--accent);
            border-radius: 8px;
            padding: 14px 18px;
        }

        .hero-amount-wrap {
            min-width: 0;
        }

        .hero-label {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--accent-ink);
        }

        .hero-amount {
            font-family: Georgia, "Times New Roman", serif;
            font-size: 30px;
            font-weight: 700;
            color: var(--ink);
            line-height: 1.1;
            font-variant-numeric: tabular-nums;
            letter-spacing: .5px;
        }

        .hero-words {
            margin-top: 4px;
            font-size: 11px;
            font-style: italic;
            color: var(--ink-soft);
        }

        .hero-words b {
            font-style: normal;
            font-weight: 700;
            color: var(--accent-ink);
            text-transform: uppercase;
            letter-spacing: .5px;
            font-size: 8.5px;
            margin-right: 4px;
        }

        .balance-chip {
            flex: none;
            text-align: center;
            padding: 8px 12px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 11px;
            letter-spacing: .4px;
            white-space: nowrap;
        }

        .balance-chip.ok {
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }

        .balance-chip.no {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        .balance-chip small {
            display: block;
            font-size: 8px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            opacity: .7;
        }

        /* ---------- Entries table ---------- */
        .table-wrap {
            margin-top: 18px;
            border: 1px solid var(--line);
            border-radius: 8px;
            overflow: hidden;
        }

        .entries {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        .entries thead th {
            background: var(--ink);
            color: #fff;
            padding: 9px 12px;
            text-align: left;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .entries thead th.r {
            text-align: right;
        }

        .entries thead th.c {
            text-align: center;
        }

        .entries tbody td {
            padding: 9px 12px;
            border-bottom: 1px solid var(--line);
            color: var(--ink-soft);
            vertical-align: top;
        }

        .entries tbody tr:nth-child(even) td {
            background: #fafbfd;
        }

        .entries tbody tr:last-child td {
            border-bottom: none;
        }

        .entries .r {
            text-align: right;
        }

        .entries .c {
            text-align: center;
        }

        .acct {
            font-weight: 700;
            color: var(--ink);
        }

        .idx {
            color: var(--faint);
            font-weight: 700;
            font-variant-numeric: tabular-nums;
        }

        .num {
            font-variant-numeric: tabular-nums;
            font-weight: 700;
        }

        .debit {
            color: #be123c;
        }

        .credit {
            color: #047857;
        }

        .dash {
            color: var(--faint);
        }

        .desc {
            color: var(--muted);
        }

        .entries tfoot td {
            background: linear-gradient(90deg, var(--accent), var(--accent-2));
            color: #fff;
            padding: 11px 12px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .3px;
        }

        .entries tfoot .total-label {
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-size: 11px;
        }

        .entries tfoot .num {
            font-size: 13px;
        }

        /* ---------- Remarks ---------- */
        .remarks {
            margin-top: 16px;
            background: var(--line-soft);
            border: 1px solid var(--line);
            border-left: 3px solid var(--gold);
            border-radius: 6px;
            padding: 10px 14px;
        }

        .remarks-label {
            font-size: 8.5px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 4px;
        }

        .remarks-text {
            font-size: 11.5px;
            color: var(--ink-soft);
            line-height: 1.5;
        }

        /* ---------- Signatures ---------- */
        .signs {
            margin-top: 34px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 26px;
        }

        .sign {
            text-align: center;
        }

        .sign-line {
            border-top: 1.5px solid var(--ink-soft);
            margin: 0 auto;
            width: 80%;
            padding-top: 6px;
        }

        .sign-role {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .8px;
            text-transform: uppercase;
            color: var(--ink);
        }

        .sign-name {
            font-size: 9.5px;
            color: var(--muted);
            margin-top: 2px;
            min-height: 12px;
        }

        /* ---------- Footer ---------- */
        .foot {
            margin-top: 22px;
            padding-top: 12px;
            border-top: 1px solid var(--line);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            font-size: 9px;
            color: var(--faint);
            letter-spacing: .3px;
        }

        .foot .gen {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .foot .dot {
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: var(--accent-2);
        }

        /* ---------- Toolbar (screen only) ---------- */
        .toolbar {
            position: fixed;
            top: 18px;
            right: 18px;
            display: flex;
            gap: 10px;
            z-index: 50;
        }

        .btn {
            border: none;
            cursor: pointer;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .5px;
            padding: 11px 20px;
            border-radius: 8px;
            transition: transform .12s ease, box-shadow .2s ease, background .2s ease;
        }

        .btn:active {
            transform: translateY(1px);
        }

        .btn-print {
            background: linear-gradient(120deg, var(--accent), var(--accent-2));
            color: #fff;
            box-shadow: 0 10px 22px -10px var(--accent);
        }

        .btn-print:hover {
            box-shadow: 0 14px 28px -10px var(--accent);
        }

        .btn-close {
            background: #fff;
            color: var(--ink-soft);
            border: 1px solid var(--line);
            box-shadow: 0 6px 16px -8px rgba(15, 23, 42, .3);
        }

        .btn-close:hover {
            background: #f8fafc;
        }

        /* ---------- Print ---------- */
        @media print {
            body {
                background: #fff;
            }

            .no-print {
                display: none !important;
            }

            .sheet {
                max-width: none;
                width: auto;
                border: none;
                border-radius: 0;
                box-shadow: none;
            }

            .pad {
                padding: 4mm 2mm 2mm;
            }

            .table-wrap,
            .signs,
            .hero,
            .foot {
                page-break-inside: avoid;
            }

            .watermark {
                opacity: .05;
            }

            @page {
                size: A4;
                margin: 10mm;
            }
        }
    </style>
</head>

<body class="{{ $vtClass }}">
    <div class="toolbar no-print">
        <button class="btn btn-print" onclick="window.print()">Print Voucher</button>
        <button class="btn btn-close" onclick="window.close()">Close</button>
    </div>

    <div class="sheet">
        <div class="accent-strip"></div>
        <div class="gold-strip"></div>

        <div class="pad">
            <div class="watermark">{{ $voucherLabel }}</div>

            <!-- Header -->
            <div class="head">
                <div class="brand">
                    @if ($enableLogoInPrint == 'yes' && $companyLogo)
                        <img src="{{ $companyLogo }}" alt="Company Logo" class="brand-logo">
                    @endif
                    <div class="brand-text">
                        <div class="company-name">{{ $companyName }}</div>
                        @if ($companyAddress)
                            <div class="company-details">{{ $companyAddress }}</div>
                        @endif
                        @if ($companyPhone || $companyEmail)
                            <div class="company-details">
                                @if ($companyPhone)
                                    <span>{{ $companyPhone }}</span>
                                @endif
                                @if ($companyPhone && $companyEmail)
                                    <span class="sep">&bull;</span>
                                @endif
                                @if ($companyEmail)
                                    <span>{{ $companyEmail }}</span>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
                <div class="type-badge">
                    <div class="type-pill">{{ $voucherLabel }}</div>
                    <div class="type-sub">Accounting Voucher</div>
                </div>
            </div>

            <div class="divider"></div>

            <!-- Meta cards -->
            <div class="meta">
                <div class="meta-card">
                    <span class="meta-label">Date</span>
                    <span class="meta-value">{{ \Carbon\Carbon::parse($journal->date)->format('d M Y') }}</span>
                </div>
                <div class="meta-card">
                    <span class="meta-label">Voucher No</span>
                    <span class="meta-value">#{{ $journal->id }}</span>
                </div>
                @if ($journal->reference_number)
                    <div class="meta-card">
                        <span class="meta-label">Reference No</span>
                        <span class="meta-value">{{ $journal->reference_number }}</span>
                    </div>
                @endif
                @if ($journal->person_name)
                    <div class="meta-card">
                        <span class="meta-label">Person Name</span>
                        <span class="meta-value">{{ $journal->person_name }}</span>
                    </div>
                @endif
            </div>

            <!-- Hero amount -->
            <div class="hero">
                <div class="hero-amount-wrap">
                    <div class="hero-label">Voucher Amount</div>
                    <div class="hero-amount">{{ currency($grandTotal) }}</div>
                    <div class="hero-words"><b>In words</b>{{ $amountInWords }}</div>
                </div>
                <div class="balance-chip {{ $isBalanced ? 'ok' : 'no' }}">
                    <small>Status</small>
                    {{ $isBalanced ? 'Balanced' : 'Unbalanced' }}
                </div>
            </div>

            <!-- Entries -->
            <div class="table-wrap">
                <table class="entries">
                    <thead>
                        <tr>
                            <th class="c" style="width:36px;">#</th>
                            <th style="width:24%;">Account</th>
                            <th class="r" style="width:15%;">Debit</th>
                            <th class="r" style="width:15%;">Credit</th>
                            <th>Description</th>
                            @if ($hasPersonColumn)
                                <th style="width:14%;">Name</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($journal->entries as $index => $entry)
                            <tr>
                                <td class="c idx">{{ $index + 1 }}</td>
                                <td class="acct">{{ $entry->account->name ? ucfirst($entry->account->name) : 'N/A' }}</td>
                                <td class="r num {{ $entry->debit > 0 ? 'debit' : 'dash' }}">
                                    {{ $entry->debit > 0 ? currency($entry->debit) : '—' }}
                                </td>
                                <td class="r num {{ $entry->credit > 0 ? 'credit' : 'dash' }}">
                                    {{ $entry->credit > 0 ? currency($entry->credit) : '—' }}
                                </td>
                                <td class="desc">{{ $entry->remarks ?? ($entry->description ?? '—') }}</td>
                                @if ($hasPersonColumn)
                                    <td class="desc">{{ $entry->person_name ?? '—' }}</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2" class="r total-label">Total</td>
                            <td class="r num">{{ currency($totalDebit) }}</td>
                            <td class="r num">{{ currency($totalCredit) }}</td>
                            <td colspan="{{ $hasPersonColumn ? '2' : '1' }}"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Remarks -->
            @if ($journal->remarks)
                <div class="remarks">
                    <div class="remarks-label">Remarks</div>
                    <div class="remarks-text">{{ $journal->remarks }}</div>
                </div>
            @endif

            <!-- Signatures -->
            <div class="signs">
                <div class="sign">
                    <div class="sign-line">
                        <div class="sign-role">Prepared By</div>
                        <div class="sign-name">{{ $journal->createdBy->name ?? '—' }}</div>
                    </div>
                </div>
                <div class="sign">
                    <div class="sign-line">
                        <div class="sign-role">Received By</div>
                        <div class="sign-name">&nbsp;</div>
                    </div>
                </div>
                <div class="sign">
                    <div class="sign-line">
                        <div class="sign-role">Authorized By</div>
                        <div class="sign-name">&nbsp;</div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="foot">
                <div class="gen">
                    <span class="dot"></span>
                    <span>This is a computer-generated voucher and does not require a physical signature.</span>
                </div>
                <div>Printed on {{ \Carbon\Carbon::now()->format('d M Y, h:i A') }}</div>
            </div>
        </div>
    </div>

    <script>
        window.addEventListener('load', function() {
            setTimeout(function() {
                window.print();
            }, 450);
        });
        // If opened as a popup from the voucher list, close after the print dialog is handled.
        window.onafterprint = function() {
            if (window.opener && !window.opener.closed) {
                window.close();
            }
        };
    </script>
</body>

</html>

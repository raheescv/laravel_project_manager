<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Receipt - #{{ $payment->voucher_no ?? $payment->id }}</title>
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

        /* ── Header ── */
        .b-head { width: 100%; border-collapse: collapse; border-bottom: 2px solid var(--ink); padding-bottom: 12px; }
        .b-head td { vertical-align: middle; padding: 0; }
        .b-eyebrow { font-size: 8px; letter-spacing: 3px; text-transform: uppercase; color: var(--accent); font-weight: 700; margin-bottom: 3px; }
        .b-head .co-name { font-size: 17px; font-weight: 700; letter-spacing: .5px; color: var(--ink); margin: 0 0 3px; }
        .b-head .co-meta { font-size: 9.5px; color: var(--ink-soft); line-height: 1.7; }
        .b-head .logo-cell { text-align: right; width: 200px; vertical-align: top; }
        .b-head .logo-cell img { max-width: 190px; max-height: 90px; }

        /* ── Title ── */
        .b-title { text-align: center; margin: 18px 0 6px; }
        .b-title .t { font-size: 15px; font-weight: 700; letter-spacing: 5px; text-transform: uppercase; color: var(--ink); }
        .b-title .rule { width: 60px; height: 3px; background: var(--gold); margin: 7px auto 0; }

        /* ── Meta (No. + Date) ── */
        .b-meta { width: 100%; border-collapse: collapse; margin: 14px 0 18px; }
        .b-meta td { font-size: 10.5px; padding: 3px 0; }
        .b-meta .k { color: var(--ink-soft); }
        .b-meta .v { font-weight: 700; text-align: right; }

        /* ── Amount ── */
        .b-amount { text-align: center; padding: 14px 0; border-top: 1px solid var(--line); border-bottom: 1px solid var(--line); margin-bottom: 8px; }
        .b-amount .lbl { font-size: 8.5px; letter-spacing: 1.6px; text-transform: uppercase; color: var(--ink-soft); margin-bottom: 3px; }
        .b-amount .fig { font-size: 30px; font-weight: 700; color: var(--ink); letter-spacing: 1px; }
        .b-amount .cur { font-size: 14px; color: var(--accent); font-weight: 700; }

        /* ── Words ── */
        .b-words { text-align: center; font-style: italic; color: var(--ink-soft); font-size: 10.5px; margin-bottom: 22px; }
        .b-words b { color: var(--ink); font-style: normal; }

        /* ── Field rows ── */
        .b-fields { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .b-fields td { padding: 8px 0; border-bottom: 1px dotted #ccc; vertical-align: top; }
        .b-fields .f-label { width: 26%; font-size: 10px; font-weight: 600; color: var(--ink-soft); white-space: nowrap; }
        .b-fields .f-colon { width: 1%; padding: 8px 8px; color: #aaa; }
        .b-fields .f-value { font-size: 11px; color: var(--ink); word-break: break-word; }
        .b-fields .pos { color: var(--accent-dark); font-weight: 700; }
        .b-fields .neg { color: #b3261e; font-weight: 700; }

        /* ── Signatures ── */
        .sig-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .sig-table td { width: 50%; text-align: center; padding-top: 38px; vertical-align: bottom; }
        .sig-table .sig-line { border-top: 1px solid #777; width: 74%; margin: 0 auto 5px; }
        .sig-table .sig-lbl { font-size: 9px; color: var(--ink-soft); text-transform: uppercase; letter-spacing: 0.8px; }

        /* ── Footer ── */
        .footer { margin-top: 18px; border-top: 1px solid #ddd; padding-top: 6px; text-align: center; font-size: 8px; color: #aaa; }
    </style>
</head>
<body>
    @php
        $receiptAmount = $payment->credit > 0 ? $payment->credit : $payment->debit;
        $voucherNo     = $payment->voucher_no ?? $payment->id;
    @endphp

    {{-- ── Header ── --}}
    <table class="b-head">
        <tr>
            <td>
                <div class="b-eyebrow">
                    @if ($rentOut->agreement_type === 'sale')
                        Sale Income
                    @else
                        Rental Income
                    @endif
                </div>
                <div class="co-name">{{ $companyName }}</div>
                <div class="co-meta">
                    @if ($companyAddress){{ $companyAddress }}<br>@endif
                    @if ($companyPhone)Tel: {{ $companyPhone }}@endif
                    @if ($companyEmail) &bull; {{ $companyEmail }}@endif
                </div>
            </td>
            <td class="logo-cell">
                @if ($companyLogo)
                    <img src="{{ $companyLogo }}" alt="Logo">
                @endif
            </td>
        </tr>
    </table>

    {{-- ── Title ── --}}
    <div class="b-title">
        <div class="t">Receipt Voucher</div>
        <div class="rule"></div>
    </div>

    {{-- ── Receipt No. & Date ── --}}
    <table class="b-meta">
        <tr><td class="k">Receipt No.</td><td class="v">{{ $voucherNo }}</td></tr>
        <tr><td class="k">Date</td><td class="v">{{ $payment->date?->format('d-m-Y') }}</td></tr>
    </table>

    {{-- ── Amount ── --}}
    <div class="b-amount">
        <div class="lbl">Amount Received</div>
        <div class="fig"><span class="cur">QR</span> {{ currency($receiptAmount) }}</div>
    </div>

    {{-- ── Amount in Words ── --}}
    <div class="b-words">in words — <b>{{ convertCurrencyToWords($receiptAmount) }}</b></div>

    {{-- ── Fields ── --}}
    <table class="b-fields">
        <tbody>
            @if ($rentOut->customer)
            <tr>
                <td class="f-label">Received From</td>
                <td class="f-colon">:</td>
                <td class="f-value">{{ $rentOut->customer->name }}</td>
            </tr>
            @endif
            @if ($rentOut->property)
            <tr>
                <td class="f-label">Property / Unit No.</td>
                <td class="f-colon">:</td>
                <td class="f-value">{{ $rentOut->property->number }}@if ($rentOut->building) ({{ $rentOut->building->name }})@endif</td>
            </tr>
            @endif
            <tr>
                <td class="f-label">Agreement No.</td>
                <td class="f-colon">:</td>
                <td class="f-value">{{ $rentOut->agreement_no ?: '—' }}</td>
            </tr>
            <tr>
                <td class="f-label">Payment Mode</td>
                <td class="f-colon">:</td>
                <td class="f-value">{{ $payment->account?->name ?: '—' }}</td>
            </tr>
            <tr>
                <td class="f-label">Source</td>
                <td class="f-colon">:</td>
                <td class="f-value">{{ $payment->source ?: '—' }}@if ($payment->group) &mdash; {{ $payment->group }}@endif</td>
            </tr>
            @if ($payment->category)
            <tr>
                <td class="f-label">Category</td>
                <td class="f-colon">:</td>
                <td class="f-value">{{ $payment->category }}</td>
            </tr>
            @endif
            @if ($payment->credit > 0 || $payment->debit > 0)
            <tr>
                <td class="f-label">Entry</td>
                <td class="f-colon">:</td>
                <td class="f-value">
                    @if ($payment->credit > 0)<span class="pos">Credit QR {{ currency($payment->credit) }}</span>@endif
                    @if ($payment->debit > 0)<span class="neg">Debit QR {{ currency($payment->debit) }}</span>@endif
                </td>
            </tr>
            @endif
            <tr>
                <td class="f-label">Remarks</td>
                <td class="f-colon">:</td>
                <td class="f-value">{{ $payment->remark ?: '—' }}</td>
            </tr>
        </tbody>
    </table>

    {{-- ── Signatures ── --}}
    <table class="sig-table">
        <tr>
            <td>
                <div class="sig-line"></div>
                <div class="sig-lbl">Receiver's Signature</div>
            </td>
            <td>
                <div class="sig-line"></div>
                <div class="sig-lbl">Authorised Signature</div>
            </td>
        </tr>
    </table>

    {{-- ── Footer ── --}}
    <div class="footer">Computer generated receipt &bull; {{ now()->format('d/m/Y h:i A') }}</div>

</body>
</html>

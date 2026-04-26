<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Payment Voucher</title>
    <style>
        body {
            margin: 0;
            padding: 28px 36px;
            color: #1a1a1a;
            background: #fff;
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            line-height: 1.5;
        }

        /* ── Header ── */
        .hdr { width: 100%; border-collapse: collapse; margin-bottom: 14px; border-bottom: 2px solid #1a1a1a; padding-bottom: 10px; }
        .hdr td { vertical-align: middle; padding: 0; }
        .hdr .co-name { font-size: 15px; font-weight: 700; color: #1a1a1a; margin: 0 0 3px; }
        .hdr .co-meta { font-size: 9.5px; color: #555; line-height: 1.7; }
        .hdr .logo-cell { text-align: right; width: 130px; }
        .hdr .logo-cell img { max-width: 120px; max-height: 55px; }

        /* ── Title ── */
        .doc-title {
            text-align: center;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: #1a1a1a;
            border-top: 1px solid #1a1a1a;
            border-bottom: 1px solid #1a1a1a;
            padding: 6px 0;
            margin-bottom: 16px;
        }

        /* ── Meta row (No. + Date) ── */
        .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .meta-table td { padding: 0; vertical-align: top; }
        .meta-table .meta-right { text-align: right; }
        .meta-item { font-size: 10.5px; }
        .meta-item .lbl { color: #666; }
        .meta-item .val { font-weight: 700; color: #1a1a1a; }

        /* ── Amount block ── */
        .amount-block {
            border: 1.5px solid #1a1a1a;
            padding: 9px 14px;
            margin-bottom: 14px;
            overflow: hidden;
        }
        .amount-block::after { content: ''; display: table; clear: both; }
        .amount-block .lbl { font-size: 8.5px; color: #666; text-transform: uppercase; letter-spacing: 1.2px; margin-bottom: 2px; }
        .amount-block .fig { font-size: 20px; font-weight: 700; color: #1a1a1a; }

        /* ── Field rows ── */
        .fields { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .fields tr { border-bottom: 1px solid #e0e0e0; }
        .fields tr:first-child { border-top: 1px solid #e0e0e0; }
        .fields .f-label {
            width: 26%;
            padding: 7px 10px 7px 0;
            font-size: 10px;
            font-weight: 600;
            color: #555;
            vertical-align: top;
            white-space: nowrap;
        }
        .fields .f-colon { width: 1%; padding: 7px 6px; color: #999; vertical-align: top; }
        .fields .f-value {
            padding: 7px 0 7px 4px;
            font-size: 11px;
            color: #1a1a1a;
            vertical-align: top;
            word-break: break-word;
        }

        /* ── Words row ── */
        .words-row {
            border: 1px solid #ccc;
            padding: 8px 12px;
            margin-bottom: 28px;
            font-size: 10.5px;
        }
        .words-row .lbl { font-size: 8.5px; color: #666; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 3px; }
        .words-row .val { font-weight: 600; font-style: italic; color: #1a1a1a; }

        /* ── Signatures ── */
        .sig-table { width: 100%; border-collapse: collapse; }
        .sig-table td { width: 33.33%; text-align: center; padding-top: 38px; vertical-align: bottom; }
        .sig-table .sig-line { border-top: 1px solid #555; width: 70%; margin: 0 auto 5px; }
        .sig-table .sig-lbl { font-size: 9px; color: #555; text-transform: uppercase; letter-spacing: 0.8px; }

        /* ── Footer ── */
        .footer { margin-top: 18px; border-top: 1px solid #ddd; padding-top: 6px; text-align: center; font-size: 8px; color: #aaa; }
    </style>
</head>
<body>
    @php
        $paymentEntry = $journal->entries->first(
            fn ($entry) => (int) $entry->account_id === (int) $vendor->id
                && $entry->model === 'PurchasePayment'
                && (float) $entry->debit > 0
        );
        $voucherAmount = $paymentEntry ? ((float) $paymentEntry->debit > 0 ? $paymentEntry->debit : $paymentEntry->credit) : 0;
        $paymentMode   = $paymentEntry?->counterAccount?->name ?? $journal->entries->firstWhere('credit', '>', 0)?->account?->name;
        $paidTo        = array_values(array_unique(array_filter([$journal->person_name, $vendor->name])));
        $remarks       = array_values(array_unique(array_filter([$journal->remarks, $paymentEntry?->remarks])));
        $reasons       = array_values(array_unique(array_filter([$journal->description])));
        $voucherNo     = $journal->reference_number ?: $journal->id;
    @endphp

    {{-- ── Header ── --}}
    <table class="hdr">
        <tr>
            <td>
                <div class="co-name">{{ $companyName }}</div>
                <div class="co-meta">
                    @if ($companyPhone)Tel: {{ $companyPhone }}<br>@endif
                    @if ($companyAddress){{ $companyAddress }}<br>@endif
                    @if ($companyEmail){{ $companyEmail }}@endif
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
    <div class="doc-title">Payment Voucher</div>

    {{-- ── Voucher No. & Date ── --}}
    <table class="meta-table">
        <tr>
            <td></td>
            <td class="meta-right">
                <div class="meta-item">
                    <span class="lbl">Voucher No: </span><span class="val">{{ $voucherNo }}</span>
                    &nbsp;&nbsp;&nbsp;
                    <span class="lbl">Date: </span><span class="val">{{ systemDate($journal->date) }}</span>
                </div>
            </td>
        </tr>
    </table>

    {{-- ── Amount ── --}}
    <div class="amount-block">
        <div class="lbl">Amount Paid</div>
        <div class="fig">QR {{ currency($voucherAmount) }}</div>
    </div>

    {{-- ── Fields ── --}}
    <table class="fields">
        <tbody>
            <tr>
                <td class="f-label">Paid To</td>
                <td class="f-colon">:</td>
                <td class="f-value">{{ implode(', ', $paidTo ?: [$vendor->name]) }}</td>
            </tr>
            <tr>
                <td class="f-label">Payment Mode</td>
                <td class="f-colon">:</td>
                <td class="f-value">{{ $paymentMode ?: '—' }}</td>
            </tr>
            <tr>
                <td class="f-label">Remarks</td>
                <td class="f-colon">:</td>
                <td class="f-value">{{ implode(', ', $remarks ?: ['—']) }}</td>
            </tr>
            @if (! empty($reasons))
            <tr>
                <td class="f-label">Reason</td>
                <td class="f-colon">:</td>
                <td class="f-value">{{ implode(', ', $reasons) }}</td>
            </tr>
            @endif
            <tr>
                <td class="f-label">Prepared By</td>
                <td class="f-colon">:</td>
                <td class="f-value">{{ $journal->createdBy->name ?? '—' }}</td>
            </tr>
        </tbody>
    </table>

    {{-- ── Amount in Words ── --}}
    <div class="words-row">
        <div class="lbl">Amount in Words</div>
        <div class="val">{{ convertCurrencyToWords($voucherAmount) }}</div>
    </div>

    {{-- ── Signatures ── --}}
    <table class="sig-table">
        <tr>
            <td>
                <div class="sig-line"></div>
                <div class="sig-lbl">Prepared By</div>
            </td>
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
    <div class="footer">Computer generated document &bull; {{ now()->format('d/m/Y h:i A') }}</div>

</body>
</html>

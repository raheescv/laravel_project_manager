@php
    use Carbon\Carbon;
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>LPO #{{ str_pad($order->id, 3, '0', STR_PAD_LEFT) }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Arial', 'DejaVu Sans', sans-serif;
        }

        body {
            background: #fff;
            color: #000;
            font-size: 11px;
        }

        .lpo-wrap {
            width: 190mm;
            margin: 0 auto;
        }

        /* ── Outer border ─────────────────────────────────── */
        .lpo-outer {
            border: 2.5px solid #000;
        }

        /* ── Row utility ──────────────────────────────────── */
        .row-flex {
            display: flex;
        }

        /* ── Header bar: REF + DATE ───────────────────────── */
        .header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 5px 8px;
            border-bottom: 1.5px solid #000;
        }

        .ref-no {
            font-weight: bold;
            font-size: 12px;
            color: #00B050;
            letter-spacing: 0.5px;
        }

        .date-area {
            font-weight: bold;
            font-size: 12px;
        }

        /* ── Logo row ─────────────────────────────────────── */
        .logo-row {
            border-bottom: 1.5px solid #000;
            line-height: 0;
        }

        .logo-row img {
            width: 100%;
            display: block;
            object-fit: contain;
        }

        .logo-placeholder {
            font-size: 18px;
            font-weight: 100%;
            color: #00B050;
        }

        /* ── Company name banner ──────────────────────────── */
        .company-name-bar {
            padding: 6px 10px;
            font-size: 26px;
            font-weight: 900;
            color: #CC9900;
            letter-spacing: 1px;
            border-bottom: 1.5px solid #000;
            line-height: 1.1;
        }

        /* ── Company info + PO label row ─────────────────── */
        .info-po-row {
            display: flex;
            border-bottom: 1.5px solid #000;
        }

        .company-info-col {
            flex: 0 0 55%;
            padding: 5px 8px;
            border-right: 1.5px solid #000;
        }

        .company-info-col div {
            font-size: 10.5px;
            font-weight: bold;
            line-height: 1.6;
        }

        .po-label-col {
            flex: 0 0 45%;
            display: flex;
            flex-direction: column;
        }

        .po-purchase-order {
            background: #00B050;
            color: #fff;
            font-weight: bold;
            font-size: 18px;
            text-align: center;
            padding: 5px 4px;
            letter-spacing: 1px;
        }

        .po-branch {
            background: #E2EFDA;
            color: #00B050;
            font-weight: bold;
            font-size: 13px;
            text-align: center;
            padding: 3px 4px;
        }

        .po-requested {
            font-size: 10px;
            font-weight: bold;
            text-align: center;
            padding: 3px 4px;
            color: #444;
        }

        /* ── Vendor / Delivery section ────────────────────── */
        .vendor-delivery-row {
            display: flex;
            border-bottom: 1.5px solid #000;
        }

        .vendor-col {
            flex: 0 0 50%;
            border-right: 1.5px solid #000;
        }

        .delivery-col {
            flex: 0 0 50%;
        }

        .section-green-header {
            background: #00B050;
            color: #fff;
            font-weight: bold;
            font-size: 12.5px;
            text-align: center;
            padding: 5px 6px;
            letter-spacing: 0.5px;
        }

        .field-row {
            display: flex;
            align-items: flex-start;
            padding: 3px 6px;
            border-bottom: 1px solid #e0e0e0;
            min-height: 22px;
            font-size: 10.5px;
        }

        .field-row:last-child {
            border-bottom: none;
        }

        .field-label {
            font-weight: bold;
            white-space: nowrap;
            margin-right: 4px;
        }

        .field-value {
            font-weight: normal;
            color: #111;
        }

        .vendor-name-value {
            padding: 4px 6px;
            font-size: 13px;
            font-weight: bold;
            border-bottom: 1px solid #e0e0e0;
        }

        /* ── Items table ──────────────────────────────────── */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: none;
        }

        .items-table thead th {
            background: #00B050;
            color: #fff;
            font-weight: bold;
            font-size: 10.5px;
            padding: 6px 4px;
            text-align: center;
            border: 1px solid #006400;
        }

        .items-table tbody td {
            border: 1px solid #ccc;
            padding: 5px 4px;
            font-size: 10px;
            vertical-align: middle;
        }

        .items-table tbody td.text-center { text-align: center; }
        .items-table tbody td.text-right  { text-align: right; }
        .items-table tbody td.text-left   { text-align: left; }

        .items-table .empty-row td {
            height: 28px;
        }

        /* Discount row */
        .discount-row td {
            border: 1px solid #ccc;
            padding: 5px 8px;
            font-size: 10.5px;
        }

        /* Total row */
        .total-row td {
            background: #00B050;
            color: #fff;
            font-weight: bold;
            font-size: 15px;
            padding: 6px 8px;
            border: 1.5px solid #006400;
        }

        /* ── Notice bar ───────────────────────────────────── */
        .notice-bar {
            padding: 5px 8px;
            font-size: 10px;
            text-align: center;
            border-top: 1.5px solid #000;
            border-bottom: 1px solid #ccc;
        }

        /* ── Terms section ────────────────────────────────── */
        .terms-section {
            border-top: 1px solid #ccc;
        }

        .term-row {
            display: flex;
            align-items: flex-start;
            padding: 4px 8px;
            border-bottom: 1px solid #e0e0e0;
            min-height: 26px;
            font-size: 11px;
        }

        .term-number {
            flex: 0 0 22px;
            font-weight: bold;
        }

        .term-text {
            flex: 1;
        }

        .payment-mode-text {
            color: #FF0000;
            font-size: 13px;
            font-weight: bold;
        }

        /* ── Signature block ──────────────────────────────── */
        .signature-block {
            display: flex;
            border-top: 1.5px solid #000;
            min-height: 85px;
        }

        .sig-left-col {
            flex: 0 0 45%;
            border-right: 1.5px solid #000;
            padding: 8px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .sig-right-col {
            flex: 0 0 55%;
            padding: 8px;
        }

        .sig-name {
            color: #00B050;
            font-weight: bold;
            font-size: 11px;
            line-height: 1.4;
        }

        .sig-title {
            color: #00B050;
            font-size: 10px;
            margin-bottom: 6px;
        }

        .sig-line {
            border-top: 1px solid #aaa;
            margin-top: 30px;
            margin-bottom: 8px;
        }

        .sig-divider {
            border-top: 1px dashed #ccc;
            margin: 6px 0;
        }
    </style>
</head>
<body>
<div class="lpo-wrap">
<div class="lpo-outer">

    {{-- ── 1. HEADER BAR: REF NO + DATE ──────────────────────── --}}
    <div class="header-bar">
        <div class="ref-no">
            REF NO:- {{ 'BASB-LPO-' . Carbon::parse($order->date ?? now())->format('m-y') . '-' . str_pad($order->id, 3, '0', STR_PAD_LEFT) }}
        </div>
        <div class="date-area">
            DATE:- {{ $order->date ? Carbon::parse($order->date)->format('j-M-y') : now()->format('j-M-y') }}
        </div>
    </div>

    {{-- ── 2. LOGO ROW ────────────────────────────────────────── --}}
    <div class="logo-row">
        @if($companyLogo)
            <img src="{{ $companyLogo }}" alt="Company Logo">
        @else
            <div class="logo-placeholder" style="padding: 8px 12px; min-height: 75px; display: flex; align-items: center;">{{ strtoupper($companyName) }}</div>
        @endif
    </div>

    {{-- ── 4. COMPANY INFO + PO LABEL ─────────────────────────── --}}
    <div class="info-po-row">
        <div class="company-info-col">
            @if($companyAddress)
                <div>{{ $companyAddress }}</div>
            @endif
            @if($companyPhone)
                <div>TEL. {{ $companyPhone }}</div>
            @endif
        </div>
        <div class="po-label-col">
            <div class="po-purchase-order">PURCHASE ORDER</div>
            <div class="po-branch">{{ strtoupper($order->branch?->name ?? 'OFFICE') }}</div>
            <div class="po-requested">Requested by: {{ strtoupper($order->creator?->name ?? 'N/A') }}</div>
        </div>
    </div>

    {{-- ── 5. VENDOR + DELIVERY ADDRESS ───────────────────────── --}}
    <div class="vendor-delivery-row">
        {{-- Left: Vendor Details --}}
        <div class="vendor-col">
            <div class="section-green-header">VENDOR'S NAME:-</div>
            <div class="vendor-name-value">{{ strtoupper($order->vendor?->name ?? '') }}</div>
            <div class="field-row">
                <span class="field-label">CONTACT PERSON:-</span>
                <span class="field-value">{{ $order->vendor?->contact_person ?? '' }}</span>
            </div>
            <div class="field-row">
                <span class="field-label">MOBILE:-</span>
                <span class="field-value">{{ $order->vendor?->mobile ?? '' }}</span>
            </div>
            <div class="field-row">
                <span class="field-label">ADDRESS:-</span>
                <span class="field-value">{{ $order->vendor?->place ?? '' }}</span>
            </div>
            <div class="field-row">
                <span class="field-label">E-MAIL:-</span>
                <span class="field-value">{{ $order->vendor?->email ?? '' }}</span>
            </div>
            <div class="field-row">
                <span class="field-label">ATTACHMENT REF:-</span>
                <span class="field-value"></span>
            </div>
        </div>

        {{-- Right: Our Delivery Address --}}
        <div class="delivery-col">
            <div class="section-green-header">{{ strtoupper($companyName) }}</div>
            <div class="field-row">
                <span class="field-label">LOCATION:-</span>
                <span class="field-value">{{ strtoupper($order->branch?->name ?? '') }}</span>
            </div>
            <div class="field-row">
                <span class="field-label">BRANCH:-</span>
                <span class="field-value">{{ strtoupper($order->branch?->name ?? '') }}</span>
            </div>
            <div class="field-row">
                <span class="field-label">DATE:-</span>
                <span class="field-value">{{ $order->date ? \Carbon\Carbon::parse($order->date)->format('d M Y') : '' }}</span>
            </div>
            <div class="field-row">
                <span class="field-label">STATUS:-</span>
                <span class="field-value">{{ strtoupper($order->status->label()) }}</span>
            </div>
            <div class="field-row">
                <span class="field-label">PREPARED BY:-</span>
                <span class="field-value">{{ strtoupper($order->creator?->name ?? '') }}</span>
            </div>
            <div class="field-row">
                <span class="field-label">QTN:-</span>
                <span class="field-value"></span>
            </div>
        </div>
    </div>

    {{-- ── 6. LINE ITEMS TABLE ──────────────────────────────────── --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:5%">S/NO</th>
                <th style="width:38%">ITEM DESCRIPTION</th>
                <th style="width:9%">UNIT</th>
                <th style="width:10%">QUANTITY</th>
                <th style="width:14%">UNIT RATE (QAR)</th>
                <th style="width:14%">PRICE (QAR)</th>
            </tr>
        </thead>
        <tbody>
            @php $itemCount = $order->items->count(); @endphp

            @forelse($order->items as $i => $item)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td class="text-left" style="font-weight:600;">{{ $item->product?->name ?? '-' }}</td>
                    <td class="text-center">{{ $item->product?->unit?->name ?? '-' }}</td>
                    <td class="text-right">{{ number_format($item->quantity, 0) }}</td>
                    <td class="text-right">{{ number_format($item->rate, 2) }}</td>
                    <td class="text-right" style="font-weight:600;">{{ number_format($item->quantity * $item->rate, 2) }}</td>
                </tr>
            @empty
                <tr class="empty-row">
                    <td colspan="6" class="text-center" style="color:#aaa; font-style:italic;">No items</td>
                </tr>
            @endforelse

            {{-- Pad with blank rows so the table always has at least 3 rows --}}
            @for($p = $itemCount; $p < 3; $p++)
                <tr class="empty-row">
                    <td></td><td></td><td></td><td></td><td></td><td></td><td></td>
                </tr>
            @endfor

            {{-- Discount row --}}
            <tr class="discount-row">
                <td colspan="4" style="border:none;"></td>
                <td class="text-center" style="font-weight:bold; background:#f9f9f9;">DISCOUNT</td>
                <td class="text-right">–</td>
            </tr>

            {{-- TOTAL row --}}
            <tr class="total-row">
                <td colspan="4" style="border:none; background:#00B050;"></td>
                <td class="text-center" style="letter-spacing:1px;">TOTAL</td>
                <td class="text-right">
                    QAR {{ number_format($order->total ?? $order->items->sum(fn($i) => $i->quantity * $i->rate), 2) }}
                </td>
            </tr>
        </tbody>
    </table>

    {{-- ── 7. NOTICE BAR ───────────────────────────────────────── --}}
    <div class="notice-bar">
        IF YOU HAVE ANY QUESTIONS ABOUT THIS PURCHASE ORDER, PLEASE CONTACT THE ABOVE MENTIONED REPRESENTATIVE
    </div>

    {{-- ── 8. TERMS & CONDITIONS ───────────────────────────────── --}}
    <div class="terms-section">
        <div class="term-row">
            <div class="term-number">1</div>
            <div class="term-text">
                <strong>PAYMENT TERMS:-</strong>
                {{ $order->payment_terms ?? '' }}
            </div>
        </div>
        <div class="term-row">
            <div class="term-number">2</div>
            <div class="term-text">
                <strong>REMARKS:-</strong>
                {{ $order->decision_note ?? '' }}
            </div>
        </div>
        <div class="term-row">
            <div class="term-number">3</div>
            <div class="term-text payment-mode-text">
                <strong>PAYMENT MODE:-</strong>
            </div>
        </div>
    </div>

    {{-- ── 9. SIGNATURE BLOCK ─────────────────────────────────── --}}
    <div class="signature-block">
        <div class="sig-left-col">
            {{-- Prepared By --}}
            <div>
                <div class="sig-name">PREPARED BY: {{ strtoupper($order->creator?->name ?? '___________________') }}</div>
                <div class="sig-title">(FINANCE OFFICER)</div>
                <div class="sig-line"></div>
            </div>
            <div class="sig-divider"></div>
            {{-- Reviewed By --}}
            <div>
                <div class="sig-name">REVIEWED &amp; CONFIRMED BY: {{ strtoupper($order->decisionMaker?->name ?? '___________________') }}</div>
                <div class="sig-title">(FINANCE MANAGER)</div>
                <div class="sig-line"></div>
            </div>
        </div>

        <div class="sig-right-col">
            <div class="sig-name">
                APPROVED BY: {{ strtoupper($order->decisionMaker?->name ?? '___________________') }}
            </div>
            <div class="sig-title">
                (CEO) ........................................
            </div>
            @if($order->decision_at)
                <div style="font-size:10px; margin-top:4px; color:#555;">
                    Date: {{ $order->decision_at->format('d M Y, h:i A') }}
                </div>
            @endif
            <div class="sig-line" style="margin-top:45px;"></div>
        </div>
    </div>

</div>{{-- /lpo-outer --}}
</div>{{-- /lpo-wrap --}}
</body>
</html>

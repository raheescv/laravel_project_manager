<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tailoring Order Receipt - {{ $order->order_no }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        @page {
            size: A4 portrait;
            margin: 10mm;
        }

        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            font-size: 11px;
            line-height: 1.45;
            color: #1f2937;
            background: #ffffff;
        }

        .sheet {
            width: 100%;
            border: 1px solid #d1d5db;
        }

        .header {
            border-bottom: 2px solid #1e40af;
            padding: 16px 18px 12px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: top;
        }

        .company-name {
            font-size: 20px;
            font-weight: 700;
            color: #111827;
            letter-spacing: 0.2px;
            margin-bottom: 3px;
        }

        .company-logo {
            display: block;
            max-width: 130px;
            max-height: 58px;
            margin-bottom: 6px;
        }

        .company-meta {
            font-size: 10px;
            color: #4b5563;
            margin-bottom: 2px;
        }

        .receipt-title {
            text-align: right;
        }

        .receipt-title .label {
            font-size: 10px;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: #6b7280;
            margin-bottom: 4px;
        }

        .receipt-title .title {
            font-size: 18px;
            font-weight: 700;
            color: #1e40af;
            margin-bottom: 5px;
        }

        .receipt-title .no {
            display: inline-block;
            font-size: 11px;
            font-weight: 700;
            background: #eff6ff;
            color: #1e40af;
            border: 1px solid #bfdbfe;
            border-radius: 20px;
            padding: 4px 12px;
        }

        .meta-wrap {
            padding: 12px 18px;
            border-bottom: 1px solid #e5e7eb;
        }

        .meta-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 8px;
        }

        .meta-box {
            width: 49%;
            vertical-align: top;
            border: 1px solid #e5e7eb;
            background: #f9fafb;
            padding: 10px 12px;
        }

        .meta-box h4 {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.9px;
            color: #374151;
            margin-bottom: 8px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 5px;
        }

        .meta-row {
            margin-bottom: 4px;
        }

        .meta-row:last-child {
            margin-bottom: 0;
        }

        .meta-key {
            color: #6b7280;
            font-size: 10px;
        }

        .meta-value {
            color: #111827;
            font-size: 11px;
            font-weight: 600;
        }

        .section {
            padding: 12px 18px 0;
        }

        .section-title {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #1f2937;
            margin-bottom: 8px;
            font-weight: 700;
        }

        .items-table,
        .payments-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border: 1px solid #d1d5db;
            margin-bottom: 12px;
        }

        .items-table th,
        .payments-table th {
            background: #f3f4f6;
            color: #111827;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            font-weight: 700;
            border-bottom: 1px solid #d1d5db;
            padding: 8px 7px;
            text-align: left;
        }

        .items-table td,
        .payments-table td {
            border-bottom: 1px solid #e5e7eb;
            padding: 7px;
            vertical-align: top;
        }

        .items-table tr:last-child td,
        .payments-table tr:last-child td {
            border-bottom: none;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .product-name {
            font-weight: 700;
            color: #111827;
            margin-bottom: 2px;
            font-size: 11px;
        }

        .product-sub {
            color: #6b7280;
            font-size: 9px;
        }

        .summary-wrap {
            padding: 0 18px 10px;
        }

        .summary-table {
            width: 320px;
            margin-left: auto;
            border-collapse: collapse;
            border: 1px solid #d1d5db;
            background: #f9fafb;
        }

        .summary-table td {
            padding: 7px 10px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 11px;
        }

        .summary-table tr:last-child td {
            border-bottom: none;
        }

        .summary-table .k {
            color: #4b5563;
        }

        .summary-table .v {
            text-align: right;
            font-weight: 700;
            color: #111827;
        }

        .summary-table .grand td {
            background: #eaf2ff;
            color: #1e40af;
            font-size: 12px;
            font-weight: 800;
        }

        .note-wrap {
            padding: 0 18px 12px;
        }

        .note-box {
            border: 1px dashed #cbd5e1;
            padding: 10px;
            background: #f8fafc;
            color: #374151;
            font-size: 10px;
            min-height: 48px;
        }

        .signatures {
            padding: 10px 18px 14px;
        }

        .signatures-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .signatures-table td {
            width: 50%;
            vertical-align: bottom;
            padding-top: 24px;
        }

        .sign-label {
            border-top: 1px solid #9ca3af;
            padding-top: 5px;
            color: #4b5563;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: center;
            margin: 0 16px;
        }

        .footer {
            border-top: 1px solid #e5e7eb;
            background: #f9fafb;
            color: #6b7280;
            padding: 8px 18px;
            font-size: 9px;
            text-align: center;
        }

        .no-print {
            margin-top: 10px;
            text-align: center;
        }

        .no-print button {
            border: none;
            background: #1e40af;
            color: #fff;
            font-weight: 700;
            padding: 8px 18px;
            border-radius: 4px;
            cursor: pointer;
        }

        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    @php
        $orderDiscount = (float) ($order->discount ?? (($order->item_discount ?? 0) + ($order->other_discount ?? 0)));
    @endphp

    <div class="sheet">
        <div class="header">
            <table class="header-table">
                <tr>
                    <td style="width:66%;">
                        @if (($enableLogoInPrint ?? 'no') === 'yes' && !empty($companyLogo))
                            <img src="{{ $companyLogo }}" alt="Company Logo" class="company-logo">
                        @endif
                        <div class="company-name">{{ $companyName ?? config('app.name') }}</div>
                        @if (!empty($companyAddress))
                            <div class="company-meta">{{ $companyAddress }}</div>
                        @endif
                        <div class="company-meta">
                            @if (!empty($companyPhone))
                                Phone: {{ $companyPhone }}
                            @endif
                            @if (!empty($companyPhone) && !empty($companyEmail))
                                &nbsp;|&nbsp;
                            @endif
                            @if (!empty($companyEmail))
                                Email: {{ $companyEmail }}
                            @endif
                        </div>
                        @if (!empty($gstNo))
                            <div class="company-meta">GST No: {{ $gstNo }}</div>
                        @endif
                    </td>
                    <td style="width:34%;" class="receipt-title">
                        <div class="label">Tailoring</div>
                        <div class="title">Order Receipt</div>
                        <div class="no">#{{ $order->order_no }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="meta-wrap">
            <table class="meta-grid">
                <tr>
                    <td class="meta-box" style="padding-right: 10px;">
                        <h4>Customer Details</h4>
                        <div class="meta-row">
                            <div class="meta-key">Customer Name</div>
                            <div class="meta-value">{{ $order->customer_name ?: ($order->account->name ?? 'Walk-in Customer') }}</div>
                        </div>
                        <div class="meta-row">
                            <div class="meta-key">Mobile</div>
                            <div class="meta-value">{{ $order->customer_mobile ?: ($order->account->mobile ?? '-') }}</div>
                        </div>
                    </td>
                    <td class="meta-box" style="padding-left: 10px;">
                        <h4>Order Details</h4>
                        <div class="meta-row">
                            <div class="meta-key">Order Date</div>
                            <div class="meta-value">{{ $order->order_date ? $order->order_date->format('d/m/Y') : '-' }}</div>
                        </div>
                        <div class="meta-row">
                            <div class="meta-key">Delivery Date</div>
                            <div class="meta-value">{{ $order->delivery_date ? $order->delivery_date->format('d/m/Y') : '-' }}</div>
                        </div>
                        <div class="meta-row">
                            <div class="meta-key">Branch / Salesman</div>
                            <div class="meta-value">{{ $order->branch->name ?? '-' }} / {{ $order->salesman->name ?? '-' }}</div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Order Items</div>
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 6%;" class="text-center">#</th>
                        <th style="width: 30%;">Product</th>
                        <th style="width: 24%;">Category / Model</th>
                        <th style="width: 12%;" class="text-right">Qty</th>
                        <th style="width: 14%;" class="text-right">Rate</th>
                        <th style="width: 14%;" class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($order->items as $item)
                        <tr>
                            <td class="text-center">{{ $item->item_no }}</td>
                            <td>
                                <div class="product-name">{{ $item->product_name }}</div>
                                @if (!empty($item->product_color))
                                    <div class="product-sub">Color: {{ $item->product_color }}</div>
                                @endif
                            </td>
                            <td>
                                {{ $item->category->name ?? '-' }}
                                / {{ $item->categoryModel->name ?? $item->tailoring_category_model_name ?? 'Standard' }}
                            </td>
                            <td class="text-right">{{ number_format((float) $item->quantity, 2) }}</td>
                            <td class="text-right">{{ currency($item->stitch_rate) }}</td>
                            <td class="text-right">{{ currency($item->total) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">No items available.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="summary-wrap">
            <table class="summary-table">
                @if(isset($order->gross_amount) && (float) $order->gross_amount != (float) $order->total)
                    <tr>
                        <td class="k">Gross</td>
                        <td class="v">{{ currency($order->gross_amount ?? 0) }}</td>
                    </tr>
                @endif
                @if($orderDiscount > 0)
                    <tr>
                        <td class="k">Discount</td>
                        <td class="v">-{{ currency($orderDiscount) }}</td>
                    </tr>
                @endif
                <tr class="grand">
                    <td>Grand Total</td>
                    <td class="v">{{ currency($order->total) }}</td>
                </tr>
                <tr>
                    <td class="k">Paid</td>
                    <td class="v">{{ currency($order->paid) }}</td>
                </tr>
                <tr>
                    <td class="k">Balance</td>
                    <td class="v">{{ currency($order->balance) }}</td>
                </tr>
            </table>
        </div>

        @if($order->payments->count() > 0)
            <div class="section">
                <div class="section-title">Payment History</div>
                <table class="payments-table">
                    <thead>
                        <tr>
                            <th style="width: 16%;">Date</th>
                            <th style="width: 20%;">Method</th>
                            <th style="width: 16%;" class="text-right">Amount</th>
                            <th style="width: 48%;">Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->payments as $payment)
                            <tr>
                                <td>{{ $payment->date ? $payment->date->format('d/m/Y') : '-' }}</td>
                                <td>{{ $payment->paymentMethod->name ?? '-' }}</td>
                                <td class="text-right">{{ currency($payment->amount) }}</td>
                                <td>{{ $payment->remarks ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div class="note-wrap">
            <div class="section-title">Notes</div>
            <div class="note-box">{{ trim((string) ($order->notes ?? '')) !== '' ? $order->notes : 'No additional notes.' }}</div>
        </div>

        <div class="signatures">
            <table class="signatures-table">
                <tr>
                    <td>
                        <div class="sign-label">Customer Signature</div>
                    </td>
                    <td>
                        <div class="sign-label">Authorized Signature</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="footer">
            Printed on {{ now()->format('d/m/Y h:i A') }} | Receipt Ref: {{ $order->order_no }}
        </div>
    </div>
</body>

</html>

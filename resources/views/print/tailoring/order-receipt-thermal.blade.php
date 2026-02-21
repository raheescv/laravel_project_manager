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
            size: 80mm auto;
            margin: 0;
        }

        html,
        body {
            width: 80mm;
            max-width: 80mm;
            background: #fff;
            color: #000;
            font-family: "Arial", "Helvetica", sans-serif;
            font-size: 11.4px;
            line-height: 1.3;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body {
            margin: 0;
            padding: 0;
            font-weight: 600;
        }

        .receipt {
            width: 80mm;
            max-width: 80mm;
            padding: 2.2mm;
        }

        .header-card {
            border: 1px solid #000;
            padding: 1.6mm 1.8mm;
            margin-bottom: 1.6mm;
        }

        .shop-name {
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.2px;
            text-transform: uppercase;
            line-height: 1.2;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .title {
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.65px;
            text-transform: uppercase;
            margin-top: 1.2mm;
        }

        .receipt-chip {
            margin-top: 1mm;
            display: inline-block;
            border: 1px solid #000;
            padding: 0.35mm 1.3mm;
            font-size: 9.4px;
            font-weight: 700;
            letter-spacing: 0.35px;
            text-transform: uppercase;
        }

        .order-ref {
            margin-top: 1mm;
            font-size: 12.4px;
            font-weight: 700;
        }

        .branch {
            margin-top: 0.35mm;
            font-size: 10.8px;
            font-weight: 600;
        }

        .meta-line {
            margin-top: 0.3mm;
            font-size: 10.8px;
            font-weight: 700;
        }

        .divider {
            border-top: 1px solid #000;
            margin: 1.7mm 0;
        }

        .section-title {
            text-align: center;
            font-size: 10.2px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.35px;
            margin-bottom: 0.9mm;
        }

        .info-table,
        .items-table,
        .totals-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .info-table td {
            border-bottom: 1px solid #000;
            padding: 0.75mm 0;
            vertical-align: top;
            font-size: 10.8px;
        }

        .info-table tr:last-child td {
            border-bottom: none;
        }

        .info-key {
            width: 38%;
            font-weight: 700;
            color: #222;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.2px;
        }

        .info-value {
            width: 62%;
            font-weight: 700;
            word-break: break-word;
            text-align: right;
        }

        .items-table th {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 0.8mm 0.65mm;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.2px;
        }

        .items-table td {
            border-bottom: 1px solid #000;
            padding: 0.8mm 0.65mm;
            font-size: 11px;
            font-weight: 700;
            vertical-align: top;
        }

        .items-table tr:last-child td {
            border-bottom: none;
        }

        .col-item {
            width: 60%;
            text-align: left;
        }

        .col-qty {
            width: 14%;
            text-align: right;
            white-space: nowrap;
        }

        .col-amt {
            width: 26%;
            text-align: right;
            white-space: nowrap;
        }

        .category-row td {
            font-weight: 700;
            text-transform: uppercase;
            border-bottom: 1px solid #000;
            padding-top: 0.9mm;
            padding-bottom: 0.65mm;
            font-size: 10px;
        }

        .item-detail td {
            border-bottom: none;
            padding-top: 0.42mm;
            padding-bottom: 0.36mm;
            font-weight: 600;
            padding-left: 1.45mm;
        }

        .item-sub {
            display: block;
            font-size: 9.8px;
            margin-top: 0.2mm;
            font-weight: 600;
            opacity: 0.9;
            word-break: break-word;
        }

        .totals-wrap {
            border: 1px solid #000;
        }

        .totals-table td {
            border-bottom: 1px solid #000;
            padding: 0.95mm 0.7mm;
            font-size: 11.2px;
            font-weight: 700;
        }

        .totals-table tr:last-child td {
            border-bottom: none;
            font-size: 12px;
            font-weight: 700;
            padding-top: 1.05mm;
            padding-bottom: 1.05mm;
        }

        .totals-label {
            width: 56%;
        }

        .totals-value {
            width: 44%;
            text-align: right;
            white-space: nowrap;
        }

        .status-chip {
            margin-top: 1mm;
            display: inline-block;
            border: 1px solid #000;
            padding: 0.35mm 1.6mm;
            font-size: 9.2px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.45px;
        }

        .footer {
            margin-top: 1.5mm;
            text-align: center;
        }

        .print-time {
            font-size: 10.6px;
            font-weight: 700;
            margin-bottom: 0.7mm;
        }

        .footer-note {
            font-size: 10.1px;
            font-weight: 600;
            line-height: 1.3;
            margin-top: 0.55mm;
        }

        .print-actions {
            margin: 8px 0;
            text-align: center;
        }

        .print-actions button {
            border: 1px solid #000;
            background: #fff;
            color: #000;
            padding: 5px 12px;
            font-size: 11.5px;
            font-weight: 700;
            border-radius: 3px;
            cursor: pointer;
        }

        @media print {
            .print-actions {
                display: none !important;
            }

            .receipt {
                padding: 1.8mm;
            }
        }
    </style>
</head>

<body onload="window.print();">
    <div class="receipt">
        <div class="header-card center">
            @if (($enable_logo_in_print ?? '') == 'yes' && cache('logo'))
                <img src="{{ cache('logo') }}" alt="Logo" style="max-width: 22mm; max-height: 11.5mm; margin-bottom: 0.75mm;">
            @endif
            @if ($order->branch && ($order->branch->name ?? null))
                <div class="shop-name">{{ $order->branch->name }}</div>
            @endif
            @if ($order->branch && ($order->branch->location ?? null))
                <div class="shop-name">{{ $order->branch->location }}</div>
            @endif
            @if ($order->branch && ($order->branch->mobile ?? null))
                <div class="meta-line">Mobile: {{ $order->branch->mobile }}</div>
            @endif
            @if (!empty($gst_no))
                <div class="meta-line">GST: {{ $gst_no }}</div>
            @endif

            <div class="title">Tailoring Receipt</div>
            <div class="order-ref">Order #{{ $order->order_no }}</div>
        </div>

        <div class="section-title">Order Details</div>
        <table class="info-table">
            <tr>
                <td class="info-key">Order No</td>
                <td class="info-value">{{ $order->order_no }}</td>
            </tr>
            <tr>
                <td class="info-key">Order Date</td>
                <td class="info-value">{{ $order->order_date ? $order->order_date->format('d/m/Y') : '-' }}</td>
            </tr>
            <tr>
                <td class="info-key">Delivery</td>
                <td class="info-value">{{ $order->delivery_date ? $order->delivery_date->format('d/m/Y') : '-' }}</td>
            </tr>
            <tr>
                <td class="info-key">Customer</td>
                <td class="info-value">{{ $order->customer_name ?: 'Walk-in Customer' }}</td>
            </tr>
            @if ($order->customer_mobile)
                <tr>
                    <td class="info-key">Mobile</td>
                    <td class="info-value">{{ $order->customer_mobile }}</td>
                </tr>
            @endif
            <tr>
                <td class="info-key">Salesman</td>
                <td class="info-value">{{ $order->salesman->name ?? '-' }}</td>
            </tr>
        </table>

        <div class="divider"></div>

        <div class="section-title">Item Summary</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th class="col-item">Item / Category</th>
                    <th class="col-qty right">Qty</th>
                    <th class="col-amt right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($order->items->groupBy('tailoring_category_id') as $categoryId => $items)
                    @php
                        $first = $items->first();
                        $catName = $first->category->name ?? 'Other';
                        $catQty = $items->sum('quantity');
                        $catAmount = $items->sum('total');
                    @endphp
                    <tr class="category-row">
                        <td class="col-item">{{ $catName }}</td>
                        <td class="col-qty right">{{ round($catQty) }}</td>
                        <td class="col-amt right">{{ currency($catAmount) }}</td>
                    </tr>
                    @foreach ($items as $item)
                        <tr class="item-detail">
                            <td colspan="3" class="col-item">
                                {{ $item->product_name }} x {{ round($item->quantity) }}
                                @if ($item->product_color)
                                    <span class="item-sub">{{ $item->product_color }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="3" class="center">No items available.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="divider"></div>

        <div class="section-title">Payment Summary</div>
        <div class="totals-wrap">
            <table class="totals-table">
                <tr>
                    <td class="totals-label">Grand Total</td>
                    <td class="totals-value">{{ currency($order->total) }}</td>
                </tr>
                <tr>
                    <td class="totals-label">Paid</td>
                    <td class="totals-value">{{ currency($order->paid) }}</td>
                </tr>
                <tr>
                    <td class="totals-label">Balance</td>
                    <td class="totals-value">{{ currency($order->balance) }}</td>
                </tr>
            </table>
        </div>

        <div class="divider"></div>

        <div class="footer">
            <div class="print-time">Printed: {{ now()->format('d/m/Y h:i A') }}</div>
            @if (!empty($thermal_printer_footer_english))
                <div class="footer-note">{!! $thermal_printer_footer_english !!}</div>
            @endif
            @if (($thermal_printer_style ?? '') == 'with_arabic' && !empty($thermal_printer_footer_arabic))
                <div class="footer-note" dir="rtl">{!! $thermal_printer_footer_arabic !!}</div>
            @endif
        </div>
    </div>

    <div class="print-actions">
        <button type="button" onclick="window.print()">Print</button>
    </div>

    <script>
        window.addEventListener('afterprint', function() {
            setTimeout(function() {
                window.close();
            }, 5000);
        });

        setTimeout(function() {
            window.close();
        }, 60000);
    </script>
</body>

</html>

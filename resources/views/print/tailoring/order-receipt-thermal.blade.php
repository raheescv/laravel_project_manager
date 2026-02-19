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
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            line-height: 1.34;
            color: #000;
            background: #fff;
            font-weight: 600;
            text-rendering: geometricPrecision;
        }

        body {
            padding: 0;
            margin: 0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .receipt {
            width: 80mm;
            max-width: 80mm;
            padding: 3mm 3.5mm;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .left {
            text-align: left;
        }

        .bold {
            font-weight: 700;
        }

        .tiny {
            font-size: 10px;
        }

        .small {
            font-size: 11px;
        }

        .normal {
            font-size: 12px;
        }

        .title {
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 0.4px;
            text-transform: uppercase;
            margin: 1.2mm 0 0.8mm;
        }

        .subtitle {
            font-size: 12px;
            font-weight: 700;
            margin: 0.8mm 0 0;
        }

        .line {
            border-top: 1.2px solid #000;
            margin: 2.5mm 0;
            height: 0;
        }

        .meta-table,
        .items-table,
        .totals-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .meta-table td {
            padding: 0.8mm 0;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .meta-key {
            width: 34%;
            font-weight: 700;
        }

        .meta-sep {
            width: 4%;
            text-align: center;
        }

        .meta-value {
            width: 62%;
        }

        .items-table thead th {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            border-top: 1.2px solid #000;
            border-bottom: 1.2px solid #000;
            padding: 1.4mm 0.9mm;
        }

        .items-table td {
            padding: 1.35mm 0.9mm;
            vertical-align: top;
            border-bottom: 1px solid #000;
            font-weight: 700;
        }

        .items-table tr:last-child td {
            border-bottom: none;
        }

        .col-item {
            width: 60%;
        }

        .col-qty {
            width: 16%;
            text-align: right;
            white-space: nowrap;
        }

        .col-amt {
            width: 24%;
            text-align: right;
            white-space: nowrap;
        }

        .category-row td {
            border-bottom: 1.2px solid #000;
            padding-top: 1.7mm;
            padding-bottom: 1.1mm;
            font-weight: 700;
        }

        .item-name {
            word-break: break-word;
        }

        .item-sub {
            display: block;
            font-size: 10px;
            margin-top: 0.6mm;
            font-weight: 700;
        }

        .totals-table td {
            padding: 1.35mm 0.9mm;
            border-bottom: 1px solid #000;
            font-size: 12px;
        }

        .totals-table tr:last-child td {
            border-bottom: none;
        }

        .totals-label {
            width: 55%;
            font-weight: 700;
        }

        .totals-value {
            width: 45%;
            text-align: right;
            font-weight: 800;
            white-space: nowrap;
        }

        .totals-table tr:last-child td {
            border-bottom: 1.5px solid #000;
        }

        .footer {
            margin-top: 2.2mm;
            text-align: center;
        }

        .print-actions {
            text-align: center;
            margin: 8px 0;
        }

        .print-actions button {
            border: 1.2px solid #000;
            background: #fff;
            color: #000;
            padding: 6px 14px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 700;
        }

        @media print {
            .print-actions {
                display: none !important;
            }

            .receipt {
                padding: 2.5mm 3mm;
            }
        }
    </style>
</head>

<body onload="window.print();">
    <div class="receipt">
        <div class="center">
            @if (($enable_logo_in_print ?? '') == 'yes' && cache('logo'))
                <img src="{{ cache('logo') }}" alt="Logo" style="max-width: 26mm; max-height: 14mm; margin-bottom: 1mm;">
            @endif
            <div class="subtitle">{{ $order->branch->location ?? ($order->branch->name ?? 'TAILORING') }}</div>
            @if ($order->branch && ($order->branch->mobile ?? null))
                <div class="small bold">Mobile: {{ $order->branch->mobile }}</div>
            @endif
            @if (!empty($gst_no))
                <div class="small">GST: {{ $gst_no }}</div>
            @endif
            <div class="title">Tailoring Receipt</div>
            <div class="normal bold">Order #{{ $order->order_no }}</div>
        </div>

        <div class="line"></div>

        <table class="meta-table">
            <tr>
                <td class="meta-key">Order No</td>
                <td class="meta-sep">:</td>
                <td class="meta-value bold">{{ $order->order_no }}</td>
            </tr>
            <tr>
                <td class="meta-key">Order Date</td>
                <td class="meta-sep">:</td>
                <td class="meta-value">{{ $order->order_date ? $order->order_date->format('d/m/Y') : '-' }}</td>
            </tr>
            <tr>
                <td class="meta-key">Delivery</td>
                <td class="meta-sep">:</td>
                <td class="meta-value">{{ $order->delivery_date ? $order->delivery_date->format('d/m/Y') : '-' }}</td>
            </tr>
            <tr>
                <td class="meta-key">Customer</td>
                <td class="meta-sep">:</td>
                <td class="meta-value bold">{{ $order->customer_name ?: 'Walk-in Customer' }}</td>
            </tr>
            @if ($order->customer_mobile)
                <tr>
                    <td class="meta-key">Mobile</td>
                    <td class="meta-sep">:</td>
                    <td class="meta-value">{{ $order->customer_mobile }}</td>
                </tr>
            @endif
            <tr>
                <td class="meta-key">Salesman</td>
                <td class="meta-sep">:</td>
                <td class="meta-value">{{ $order->salesman->name ?? '-' }}</td>
            </tr>
        </table>

        <div class="line"></div>

        <table class="items-table">
            <thead>
                <tr>
                    <th class="col-item left">Item / Category</th>
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
                        <td class="col-item left">{{ $catName }}</td>
                        <td class="col-qty right">{{ number_format((float) $catQty, 2) }}</td>
                        <td class="col-amt right">{{ currency($catAmount) }}</td>
                    </tr>
                    @foreach ($items as $item)
                        <tr>
                            <td class="col-item left item-name">
                                {{ $item->product_name }}
                                <span class="item-sub">
                                    @if ($item->product_color)
                                        {{ $item->product_color }} |
                                    @endif
                                    {{ number_format((float) $item->quantity, 2) }} x {{ currency($item->stitch_rate) }}
                                </span>
                            </td>
                            <td class="col-qty right">{{ number_format((float) $item->quantity, 2) }}</td>
                            <td class="col-amt right">{{ currency($item->total) }}</td>
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="3" class="center">No items available.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="line"></div>

        <table class="totals-table">
            <tr>
                <td class="totals-label">Total</td>
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

        <div class="line"></div>

        <div class="footer">
            <div class="small bold">Printed: {{ now()->format('d/m/Y h:i A') }}</div>
            @if (!empty($thermal_printer_footer_english))
                <div class="small" style="margin-top: 1mm;">{!! $thermal_printer_footer_english !!}</div>
            @endif
            @if (($thermal_printer_style ?? '') == 'with_arabic' && !empty($thermal_printer_footer_arabic))
                <div class="small" dir="rtl" style="margin-top: 1mm;">{!! $thermal_printer_footer_arabic !!}</div>
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

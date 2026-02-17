<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tailoring Order Receipt - {{ $order->order_no }}</title>
    <style>
        h1,
        h2,
        h3 {
            margin: 5px 0;
            text-align: center;
            font-size: 15px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #000;
        }

        body {
            font-family: 'Arial', 'Courier New', monospace;
            line-height: 1.2;
            font-size: 14px;
            margin: 0 auto;
            width: 80mm;
            background-color: #fff;
            padding: 5px 10px;
            color: #000;
        }

        @page {
            margin: 0;
            size: 80mm auto;
        }

        .receipt-container {
            background-color: #fff;
            width: 100%;
            padding: 5px 5px;
        }

        h1,
        h2,
        h3 {
            margin: 3px 0;
            text-align: center;
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: black;
        }

        .divider {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }

        .store-info {
            text-align: center;
            margin-bottom: 8px;
        }

        .invoice-header {
            background-color: transparent;
            border-radius: 4px;
            padding: 5px;
            margin-bottom: 8px;
            border-left: 2px solid #000;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
            font-size: 11px;
        }

        .nowrap {
            white-space: nowrap !important;
        }

        .table th,
        .table td {
            border: 1px solid #000;
            padding: 3px 3px;
            text-align: center;
        }

        .table tr.borderless td {
            border-top: none;
            border-bottom: none;
            border-left: 1px solid #000;
            border-right: 1px solid #000;
        }

        .table th {
            background-color: transparent;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #000;
        }

        .text-left {
            text-align: left !important;
        }

        .text-right {
            text-align: right !important;
        }

        .text-center {
            text-align: center !important;
        }

        .bold {
            font-weight: bold;
        }

        .footer {
            text-align: center;
            font-size: 10px;
            margin-top: 8px;
            padding: 6px 0;
            border-top: 1px dashed #000;
            color: #000;
        }

        @media print {
            @page {
                margin: 0 auto;
                width: 80mm;
                height: 100% !important;
                sheet-size: 80mm auto;
            }

            .receipt-container {
                border: none;
                box-shadow: none;
            }

            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                color: #000 !important;
                background: #fff !important;
            }

            .no-print {
                display: none;
            }

            * {
                color: #000 !important;
                background-color: transparent !important;
                border-color: #000 !important;
            }

            .table tr.borderless td {
                border-top: none !important;
                border-bottom: none !important;
                border-left: 1px solid #000 !important;
                border-right: 1px solid #000 !important;
            }

            body,
            .receipt-container {
                background-color: #fff !important;
            }

            .table th,
            .table td {
                border-color: #000 !important;
                color: #000 !important;
            }

            .footer {
                border-top: 1px dashed #000 !important;
                color: #000 !important;
            }

            .divider {
                border-top: 1px dashed #000 !important;
            }
        }
    </style>
</head>

<body onload="window.print();">
    <div class="receipt-container">
        <div class="store-info">
            @if (($enable_logo_in_print ?? '') == 'yes' && cache('logo'))
                <img src="{{ cache('logo') }}" alt="Logo" style="width: 70%; max-width: 80px; margin-bottom: 3px;">
            @endif
            <h3>{{ $order->branch->location ?? ($order->branch->name ?? 'Tailoring') }}</h3>
            @if ($order->branch && ($order->branch->mobile ?? null))
                <div style="font-size: 11px; margin-top: 2px;"><strong>Mobile: {{ $order->branch->mobile }}</strong></div>
            @endif
            @if (!empty($gst_no))
                <div style="font-size: 10px;"><strong>GST:</strong> {{ $gst_no }}</div>
            @endif
        </div>

        <div class="divider"></div>

        <div class="invoice-header">
            <h3 style="margin: 2px 0;">Tailoring Order Receipt</h3>
        </div>

        <table class="table">
            <tr>
                <td class="text-left" width="35%"><b>Order No</b></td>
                <td class="text-left"><b>{{ $order->order_no }}</b></td>
            </tr>
            <tr>
                <td class="nowrap text-left"><b>Date</b></td>
                <td class="text-left"><b>{{ $order->order_date ? $order->order_date->format('d/m/Y') : '-' }}</b></td>
            </tr>
            <tr>
                <td class="nowrap text-left"><b>Delivery</b></td>
                <td class="text-left"><b>{{ $order->delivery_date ? $order->delivery_date->format('d/m/Y') : '-' }}</b></td>
            </tr>
            <tr>
                <td class="nowrap text-left"><b>Customer</b></td>
                <td class="text-left"><b>{{ $order->customer_name }}</b></td>
            </tr>
            @if ($order->customer_mobile)
                <tr>
                    <td class="nowrap text-left"><b>Mobile</b></td>
                    <td class="text-left"><b>{{ $order->customer_mobile }}</b></td>
                </tr>
            @endif
        </table>

        <div class="divider"></div>

        <table class="table">
            <thead>
                <tr>
                    <th class="text-left">Category / Product</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items->groupBy('tailoring_category_id') as $categoryId => $items)
                    @php
                        $first = $items->first();
                        $catName = $first->category->name ?? 'Other';
                        $catCount = $items->sum('quantity');
                        $catAmount = $items->sum('total');
                    @endphp
                    <tr>
                        <td class="text-left"><b>{{ $catName }}</b> </td>
                        <td class="text-right"><b>{{ number_format($catCount) }}</b></td>
                        <td class="text-right"><b>{{ currency($catAmount) }}</b></td>
                    </tr>
                    @foreach ($items as $item)
                        <tr class="borderless">
                            <td class="text-left" style="padding-left: 8px;" colspan="3">
                                {{ $item->product_name }}
                                @if ($item->product_color)
                                    <span style="font-size: 10px;">({{ $item->product_color }})</span>
                                @endif
                                <span style="font-size: 10px;"> X <b>{{ number_format($item->quantity) }}</b> </span>
                            </td>
                        </tr>
                    @endforeach
                @endforeach
                <tr>
                    <td colspan="3"></td>
                </tr>
            </tbody>
        </table>

        <div class="divider"></div>

        <table class="table">
            <tr>
                <td class="text-left" width="45%"><b>Total</b></td>
                <td class="text-right"><b>{{ currency($order->total) }}</b></td>
            </tr>
            <tr>
                <td class="text-left"><b>Paid</b></td>
                <td class="text-right"><b>{{ currency($order->paid) }}</b></td>
            </tr>
            <tr>
                <td class="text-left"><b>Balance</b></td>
                <td class="text-right"><b>{{ currency($order->balance) }}</b></td>
            </tr>
        </table>

        @if ($order->payments->count() > 0)
            <div class="divider"></div>
            <table class="table">
                <tr>
                    <td colspan="2" class="text-center bold">Payments</td>
                </tr>
                @foreach ($order->payments as $payment)
                    <tr>
                        <td class="text-left">{{ $payment->date ? $payment->date->format('d/m/Y') : '-' }} {{ $payment->paymentMethod->name ?? '-' }}</td>
                        <td class="text-right"><b>{{ currency($payment->amount) }}</b></td>
                    </tr>
                @endforeach
            </table>
        @endif

        <div class="divider"></div>
        <div style="font-size: 10px; margin: 3px 0;"><b>Salesman:</b> {{ $order->salesman->name ?? '-' }}</div>
        <div style="font-size: 10px; margin: 3px 0;"><b>{{ now()->format('d/m/Y h:i A') }}</b></div>

        <div class="footer">
            @if (!empty($thermal_printer_footer_english))
                <p style="font-weight: bold; margin-bottom: 5px;">{!! $thermal_printer_footer_english !!}</p>
            @endif
            @if (($thermal_printer_style ?? '') == 'with_arabic' && !empty($thermal_printer_footer_arabic))
                <p dir="rtl">{!! $thermal_printer_footer_arabic !!}</p>
            @endif
            <div style="margin-top: 4px; font-size: 9px;">{{ now()->format('d/m/Y h:i A') }}</div>
        </div>
    </div>
</body>

<script>
    window.addEventListener('afterprint', function() {
        // setTimeout(function() {
        //     window.close();
        // }, 5000);
    });
    // setTimeout(function() {
    //     window.close();
    // }, 60000);
</script>

</html>

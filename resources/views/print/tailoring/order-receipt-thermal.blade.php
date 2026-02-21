<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tailoring Order Receipt - {{ $order->order_no }}</title>
</head>

<body onload="window.print();">
    <font face="Tahoma, Arial, sans-serif" style="font-size: 12px;">
        <center>
            @if (($enable_logo_in_print ?? '') == 'yes' && cache('logo'))
                <img src="{{ cache('logo') }}" alt="Logo" width="120">
            @endif

            @if ($order->branch && ($order->branch->name ?? null))
                <div><strong>{{ $order->branch->name }}</strong></div>
            @endif
            @if ($order->branch && ($order->branch->location ?? null))
                <div><strong>{{ $order->branch->location }}</strong></div>
            @endif
            @if ($order->branch && ($order->branch->mobile ?? null))
                <div><strong>Mobile : {{ $order->branch->mobile }}</strong></div>
            @endif
            @if (!empty($gst_no))
                <div>GST: {{ $gst_no }}</div>
            @endif

            <h3>TAILORING RECEIPT</h3>
        </center>

        <hr>

        <center><strong>ORDER DETAILS</strong></center>
        <table width="100%" cellpadding="2" cellspacing="0">
            <tr>
                <td><strong>Order No</strong></td>
                <td align="right"><strong>{{ $order->order_no }}</strong></td>
            </tr>
            <tr>
                <td><strong>Order Date</strong></td>
                <td align="right"><strong>{{ $order->order_date ? $order->order_date->format('d/m/Y') : '-' }}</strong></td>
            </tr>
            <tr>
                <td><strong>Delivery</strong></td>
                <td align="right"><strong>{{ $order->delivery_date ? $order->delivery_date->format('d/m/Y') : '-' }}</strong></td>
            </tr>
            <tr>
                <td><strong>Customer</strong></td>
                <td align="right"><strong>{{ $order->customer_name ?: 'Walk-in Customer' }}</strong></td>
            </tr>
            @if ($order->customer_mobile)
                <tr>
                    <td><strong>Mobile</strong></td>
                    <td align="right"><strong>{{ $order->customer_mobile }}</strong></td>
                </tr>
            @endif
            <tr>
                <td><strong>Salesman</strong></td>
                <td align="right"><strong>{{ $order->salesman->name ?? '-' }}</strong></td>
            </tr>
        </table>

        <hr>

        <center><strong>ITEM SUMMARY</strong></center>
        <table width="100%" cellpadding="2" cellspacing="0" border="1">
            <tr>
                <th align="left">Item / Category</th>
                <th align="right">Qty</th>
                <th align="right">Amount</th>
            </tr>
            @forelse ($order->items->groupBy('tailoring_category_id') as $categoryId => $items)
                @php
                    $first = $items->first();
                    $catName = $first->category->name ?? 'Other';
                    $catQty = $items->sum('quantity');
                    $catAmount = $items->sum('total');
                @endphp
                <tr>
                    <td><strong>{{ $catName }}</strong></td>
                    <td align="right"><strong>{{ round($catQty) }}</strong></td>
                    <td align="right"><strong>{{ currency($catAmount) }}</strong></td>
                </tr>
                @foreach ($items as $item)
                    <tr>
                        <td colspan="3">
                            {{ $item->product_name }} x {{ round($item->quantity) }}
                            @if ($item->product_color)
                                - {{ $item->product_color }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="3" align="center">No items available.</td>
                </tr>
            @endforelse
        </table>

        <hr>

        @php
            $paymentMethods = collect($order->payments ?? [])
                ->pluck('paymentMethod.name')
                ->filter()
                ->unique()
                ->values();
            $paymentMethodText = $paymentMethods->isNotEmpty() ? $paymentMethods->implode(', ') : '-';
        @endphp

        <center><strong>PAYMENT SUMMARY</strong></center>
        <table width="100%" cellpadding="2" cellspacing="0" border="1">
            <tr>
                <td><strong>Payment Method</strong></td>
                <td align="right"><strong>{{ $paymentMethodText }}</strong></td>
            </tr>
            <tr>
                <td><strong>Grand Total</strong></td>
                <td align="right"><strong>{{ currency($order->total) }}</strong></td>
            </tr>
            <tr>
                <td><strong>Paid</strong></td>
                <td align="right"><strong>{{ currency($order->paid) }}</strong></td>
            </tr>
            <tr>
                <td><strong>Balance</strong></td>
                <td align="right"><strong>{{ currency($order->balance) }}</strong></td>
            </tr>
        </table>

        <hr>

        <center>
            <div><strong>Printed: {{ now()->format('d/m/Y h:i A') }}</strong></div>
            @if (!empty($thermal_printer_footer_english))
                <div>{!! $thermal_printer_footer_english !!}</div>
            @endif
            @if (($thermal_printer_style ?? '') == 'with_arabic' && !empty($thermal_printer_footer_arabic))
                <div dir="rtl">{!! $thermal_printer_footer_arabic !!}</div>
            @endif
        </center>

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
    </font>
</body>

</html>

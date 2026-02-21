@php
    use Carbon\Carbon;
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $pageTitle ?? 'Tailoring Customer Receipt' }}</title>
</head>

<body onload="window.print();">
    <font face="Tahoma, Arial, sans-serif" style="font-size: 12px;">
        <center>
            @if (($enable_logo_in_print ?? '') == 'yes' && cache('logo'))
                <img src="{{ cache('logo') }}" alt="Logo" width="120">
            @endif

            @if (!empty($companyName))
                <div><strong>{{ $companyName }}</strong></div>
            @endif
            @if (!empty($companyAddress))
                <div><strong>{{ $companyAddress }}</strong></div>
            @endif
            @if (!empty($companyPhone))
                <div><strong>Mobile : {{ $companyPhone }}</strong></div>
            @endif
            @if (!empty($gstNo))
                <div>GST: {{ $gstNo }}</div>
            @endif

            <h3>{{ $receiptTitle ?? 'TAILORING PAYMENT RECEIPT' }}</h3>
        </center>

        <hr>

        <center><strong>CUSTOMER DETAILS</strong></center>
        <table width="100%" cellpadding="2" cellspacing="0">
            <tr>
                <td><strong>Customer</strong></td>
                <td align="right"><strong>{{ $customerName ?: 'Walk-in Customer' }}</strong></td>
            </tr>
            <tr>
                <td><strong>Date</strong></td>
                <td align="right"><strong>{{ $paymentDate ? Carbon::parse($paymentDate)->format('d/m/Y') : '-' }}</strong></td>
            </tr>
            <tr>
                <td><strong>Time</strong></td>
                <td align="right"><strong>{{ Carbon::now()->format('h:i A') }}</strong></td>
            </tr>
            <tr>
                <td><strong>Payment Method</strong></td>
                <td align="right"><strong>{{ $paymentMethodName ?: '-' }}</strong></td>
            </tr>
        </table>

        <hr>

        <center><strong>PAYMENT DETAILS</strong></center>
        <table width="100%" cellpadding="2" cellspacing="0" border="1">
            <tr>
                <th align="left">{{ $referenceColumnLabel ?? 'Order No' }}</th>
                <th align="right">Amount</th>
            </tr>
            @forelse ($receiptData as $item)
                <tr>
                    <td>{{ ($item[$referenceKey] ?? '') ?: ($item['id'] ?? 'N/A') }}</td>
                    <td align="right">{{ currency($item['amount'] ?? 0) }}</td>
                </tr>
                @if (!empty($item['discount']) && (float) $item['discount'] > 0)
                    <tr>
                        <td><strong>Discount</strong></td>
                        <td align="right"><strong>-{{ currency($item['discount']) }}</strong></td>
                    </tr>
                @endif
            @empty
                <tr>
                    <td colspan="2" align="center">No payment rows available.</td>
                </tr>
            @endforelse
        </table>

        <hr>

        <center><strong>TOTAL SUMMARY</strong></center>
        <table width="100%" cellpadding="2" cellspacing="0" border="1">
            @if (($totalDiscount ?? 0) > 0)
                <tr>
                    <td><strong>Total Discount</strong></td>
                    <td align="right"><strong>-{{ currency($totalDiscount) }}</strong></td>
                </tr>
            @endif
            <tr>
                <td><strong>TOTAL AMOUNT</strong></td>
                <td align="right"><strong>{{ currency($totalAmount ?? 0) }}</strong></td>
            </tr>
        </table>

        <hr>

        <center>
            <div><strong>Printed: {{ now()->format('d/m/Y h:i A') }}</strong></div>
            <div>{{ $footerMessage ?? 'THANK YOU FOR YOUR PAYMENT' }}</div>
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

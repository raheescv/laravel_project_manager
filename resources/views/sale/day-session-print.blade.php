@php
    use Carbon\Carbon;
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sale Bill Report</title>
</head>

<body onload="window.print();">
    <font face="Tahoma, Arial, sans-serif" style="font-size: 12px;">
        <center>
            @if (($enable_logo_in_print ?? '') == 'yes' && cache('logo'))
                <img src="{{ cache('logo') }}" alt="Logo" width="120">
            @endif

            @if ($session->branch && ($session->branch->name ?? null))
                <div><strong>{{ $session->branch->name }}</strong></div>
            @endif
            @if ($session->branch && ($session->branch->location ?? null))
                <div><strong>{{ $session->branch->location }}</strong></div>
            @endif
            @if ($session->branch && ($session->branch->mobile ?? null))
                <div><strong>Mobile : {{ $session->branch->mobile }}</strong></div>
            @endif
            @if (!empty($gst_no))
                <div>GST: {{ $gst_no }}</div>
            @endif

            <h3>SALE BILL REPORT</h3>
            <div>
                <strong>
                    {{ Carbon::parse($session->opened_at)->format('d-m-Y') }} TO
                    {{ Carbon::parse($session->closed_at)->format('d-m-Y') }}
                </strong>
            </div>
        </center>

        <hr>

        <center><strong>SESSION DETAILS</strong></center>
        <table width="100%" cellpadding="2" cellspacing="0">
            <tr>
                <td><strong>Session No</strong></td>
                <td align="right"><strong>#{{ $session->id }}</strong></td>
            </tr>
            <tr>
                <td><strong>Branch</strong></td>
                <td align="right"><strong>{{ $session->branch->name ?? 'N/A' }}</strong></td>
            </tr>
            <tr>
                <td><strong>Status</strong></td>
                <td align="right"><strong>{{ strtoupper($session->status) }}</strong></td>
            </tr>
            <tr>
                <td><strong>Opened By</strong></td>
                <td align="right"><strong>{{ $session->opener->name ?? 'N/A' }}</strong></td>
            </tr>
            @if ($session->closer)
                <tr>
                    <td><strong>Closed By</strong></td>
                    <td align="right"><strong>{{ $session->closer->name }}</strong></td>
                </tr>
            @endif
            <tr>
                <td><strong>Printed Time</strong></td>
                <td align="right"><strong>{{ Carbon::now()->format('d/m/Y h:i A') }}</strong></td>
            </tr>
        </table>

        <hr>

        @php
            $saleTransactions = collect($transactions)->where('source', 'Sale')->values();
            $tailoringTransactions = collect($transactions)->where('source', 'Tailoring')->values();
            $salePendingPayments = collect($pendingPayments)->where('source', 'Sale')->values();
            $tailoringPendingPayments = collect($pendingPayments)->where('source', 'Tailoring')->values();
        @endphp

        <center><strong>SALE TRANSACTIONS</strong></center>
        <table width="100%" cellpadding="2" cellspacing="0" border="1">
            <tr>
                <th align="left">Date</th>
                <th align="left">Reference</th>
                <th align="left">Payment</th>
                <th align="right">Total</th>
            </tr>
            @forelse ($saleTransactions as $transaction)
                <tr>
                    <td><strong>{{ Carbon::parse($transaction['date'])->format('d/m') }}</strong></td>
                    <td><strong>{{ $transaction['reference_no'] ?? 'N/A' }}</strong></td>
                    <td><strong>{{ $transaction['payment_method'] }}</strong></td>
                    <td align="right"><strong>{{ currency($transaction['amount']) }}</strong></td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" align="center">No sale transactions.</td>
                </tr>
            @endforelse

            @foreach ($salePendingPayments as $pendingPayment)
                <tr>
                    <td><strong>{{ Carbon::parse($pendingPayment['date'])->format('d/m') }}</strong></td>
                    <td><strong>{{ $pendingPayment['reference_no'] ?? 'N/A' }}</strong></td>
                    <td><strong>{{ $pendingPayment['payment_method'] }} (Pending)</strong></td>
                    <td align="right"><strong>{{ currency($pendingPayment['amount']) }}</strong></td>
                </tr>
            @endforeach
        </table>

        <hr>

        <center><strong>TAILORING TRANSACTIONS</strong></center>
        <table width="100%" cellpadding="2" cellspacing="0" border="1">
            <tr>
                <th align="left">Date</th>
                <th align="left">Reference</th>
                <th align="left">Payment</th>
                <th align="right">Total</th>
            </tr>
            @forelse ($tailoringTransactions as $transaction)
                <tr>
                    <td><strong>{{ Carbon::parse($transaction['date'])->format('d/m') }}</strong></td>
                    <td><strong>{{ $transaction['reference_no'] ?? 'N/A' }}</strong></td>
                    <td><strong>{{ $transaction['payment_method'] }}</strong></td>
                    <td align="right"><strong>{{ currency($transaction['amount']) }}</strong></td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" align="center">No tailoring transactions.</td>
                </tr>
            @endforelse

            @foreach ($tailoringPendingPayments as $pendingPayment)
                <tr>
                    <td><strong>{{ Carbon::parse($pendingPayment['date'])->format('d/m') }}</strong></td>
                    <td><strong>{{ $pendingPayment['reference_no'] ?? 'N/A' }}</strong></td>
                    <td><strong>{{ $pendingPayment['payment_method'] }} (Pending)</strong></td>
                    <td align="right"><strong>{{ currency($pendingPayment['amount']) }}</strong></td>
                </tr>
            @endforeach
        </table>

        <hr>

        <center><strong>TOTAL SUMMARY</strong></center>
        <table width="100%" cellpadding="2" cellspacing="0" border="1">
            @forelse ($totals as $method => $total)
                <tr>
                    <td><strong>{{ strtoupper($method) }}</strong></td>
                    <td align="right"><strong>{{ currency($total) }}</strong></td>
                </tr>
            @empty
                <tr>
                    <td><strong>TOTAL</strong></td>
                    <td align="right"><strong>{{ currency(0) }}</strong></td>
                </tr>
            @endforelse
            <tr>
                <td><strong>GRAND TOTAL</strong></td>
                <td align="right"><strong>{{ currency(array_sum($totals)) }}</strong></td>
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

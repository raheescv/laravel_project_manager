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
            $dueRows = collect($dueTransactions ?? [])->values();
            $pendingPaymentRows = collect($pendingPayments ?? [])->values();
        @endphp

        <center><strong>SALE + TAILORING TRANSACTIONS</strong></center>
        <table width="100%" cellpadding="2" cellspacing="0" border="1">
            <tr>
                <th align="left">Type</th>
                <th align="left">Reference</th>
                <th align="right">Amount</th>
                <th align="right">Payment</th>
            </tr>
            @forelse ($transactions as $transaction)
                <tr>
                    <td><strong>Invoice</strong></td>
                    <td><strong>{{ $transaction['reference_no'] ?? 'N/A' }}</strong></td>
                    <td align="right"><strong>{{ currency($transaction['amount']) }}</strong></td>
                    <td align="right"><strong>_</strong></td>
                </tr>
                @foreach ($transaction['payment_rows'] ?? [] as $paymentRow)
                    <tr>
                        <td align="right"><strong>{{ $paymentRow['method'] }}</strong></td>
                        <td><strong>{{ $transaction['reference_no'] ?? 'N/A' }}</strong></td>
                        <td align="right"><strong>_</strong></td>
                        <td align="right"><strong>{{ currency($paymentRow['amount']) }}</strong></td>
                    </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="4" align="center">No transactions.</td>
                </tr>
            @endforelse
        </table>

        <center><strong>DUE AMOUNT DETAILS</strong></center>
        <table width="100%" cellpadding="2" cellspacing="0" border="1">
            <tr>
                <th align="left">Type</th>
                <th align="left">Reference</th>
                <th align="right">Due Amount</th>
            </tr>
            @forelse ($dueRows as $dueRow)
                <tr>
                    <td><strong>{{ $dueRow['source'] }}</strong></td>
                    <td><strong>{{ $dueRow['reference_no'] ?? 'N/A' }}</strong></td>
                    <td align="right"><strong>{{ currency($dueRow['due_amount']) }}</strong></td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" align="center">No due amounts.</td>
                </tr>
            @endforelse
        </table>

        <center><strong>DUE PAYMENT RECEIVED</strong></center>
        <table width="100%" cellpadding="2" cellspacing="0" border="1">
            <tr>
                <th align="left">Type</th>
                <th align="left">Reference</th>
                <th align="left">Payment Method</th>
                <th align="right">Amount</th>
            </tr>
            @forelse ($pendingPaymentRows as $pendingPayment)
                <tr>
                    <td><strong>{{ $pendingPayment['source'] ?? 'N/A' }}</strong></td>
                    <td><strong>{{ $pendingPayment['reference_no'] ?? 'N/A' }}</strong></td>
                    <td><strong>{{ $pendingPayment['payment_method'] }}</strong></td>
                    <td align="right"><strong>{{ currency($pendingPayment['amount']) }}</strong></td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" align="center">No due payment receipts.</td>
                </tr>
            @endforelse
        </table>

        <hr>

        <center><strong>TOTAL SUMMARY</strong></center>
        <table width="100%" cellpadding="2" cellspacing="0" border="1">
            <tr>
                <td><strong>TOTAL CREDIT (UNPAID)</strong></td>
                <td align="right"><strong>{{ currency($totals['credit']) }}</strong></td>
            </tr>
            <tr>
                <td><strong>TOTAL CASH (INVOICE)</strong></td>
                <td align="right"><strong>{{ currency($totals['cash']) }}</strong></td>
            </tr>
            <tr>
                <td><strong>TOTAL CARD (INVOICE)</strong></td>
                <td align="right"><strong>{{ currency($totals['card']) }}</strong></td>
            </tr>
            <tr>
                <td><strong>TOTAL SALE + TAILORING AMOUNT</strong></td>
                <td align="right"><strong>{{ currency($totals['sale_tailoring_amount']) }}</strong></td>
            </tr>
            <tr>
                <td><strong>TOTAL PAYMENT (INVOICE)</strong></td>
                <td align="right"><strong>{{ currency($totals['payment_total']) }}</strong></td>
            </tr>
            <tr>
                <td><strong>TOTAL DUE PAYMENT CASH</strong></td>
                <td align="right"><strong>{{ currency($totals['due_total_cash']) }}</strong></td>
            </tr>
            <tr>
                <td><strong>TOTAL DUE PAYMENT CARD</strong></td>
                <td align="right"><strong>{{ currency($totals['due_total_card']) }}</strong></td>
            </tr>
            <tr>
                <td><strong>TOTAL DUE PAYMENT</strong></td>
                <td align="right"><strong>{{ currency($totals['due_total']) }}</strong></td>
            </tr>
            <tr>
                <td><strong>TOTAL CARD (INVOICE + DUE)</strong></td>
                <td align="right"><strong>{{ currency($totals['card'] + $totals['due_total_card']) }}</strong></td>
            </tr>
            <tr>
                <td><strong>TOTAL CASH (INVOICE + DUE)</strong></td>
                <td align="right"><strong>{{ currency($totals['cash'] + $totals['due_total_cash']) }}</strong></td>
            </tr>
            <tr>
                <td><strong>GRAND TOTAL PAYMENT</strong></td>
                <td align="right"><strong>{{ currency($totals['payment_total'] + $totals['due_total']) }}</strong></td>
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

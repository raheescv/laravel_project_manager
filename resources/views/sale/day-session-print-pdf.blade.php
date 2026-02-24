@php
    use Carbon\Carbon;
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Day Session Sale Bill Report (PDF)</title>
    <style>
        @page {
            size: A4;
            margin: 14mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #0f172a;
            margin: 0;
            padding: 0;
            background: #fff;
        }

        .report-wrap {
            width: 100%;
        }

        .title {
            text-align: center;
            margin-bottom: 8px;
        }

        .title h2 {
            margin: 0 0 4px;
            font-size: 20px;
            letter-spacing: 0.3px;
        }

        .title p {
            margin: 0;
            color: #475569;
            font-size: 12px;
        }

        .meta-grid {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0 14px;
        }

        .meta-grid td {
            width: 25%;
            border: 1px solid #e2e8f0;
            padding: 8px;
            vertical-align: top;
        }

        .meta-label {
            display: block;
            font-size: 11px;
            color: #64748b;
            margin-bottom: 2px;
            text-transform: uppercase;
        }

        .meta-value {
            font-weight: 700;
        }

        .section-title {
            margin: 14px 0 6px;
            font-size: 13px;
            font-weight: 700;
            color: #1d4ed8;
            text-transform: uppercase;
            letter-spacing: 0.2px;
        }

        table.report-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .report-table th,
        .report-table td {
            border: 1px solid #dbe2ea;
            padding: 7px 8px;
            vertical-align: top;
        }

        .report-table thead th {
            background: #f1f5f9;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.2px;
        }

        .text-right {
            text-align: right;
        }

        .muted {
            color: #64748b;
        }

        .payment-breakdown span {
            display: inline-block;
            margin-right: 8px;
            white-space: nowrap;
        }

        .summary-table td:first-child {
            width: 72%;
            font-weight: 600;
        }

        .summary-table td:last-child {
            width: 28%;
            text-align: right;
            font-weight: 700;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
            margin-bottom: 12px;
        }

        .summary-card {
            border: 1px solid #dbe2ea;
            border-left: 4px solid #2563eb;
            border-radius: 8px;
            padding: 8px 10px;
            background: #f8fbff;
        }

        .summary-card.warm {
            border-left-color: #ea580c;
            background: #fffaf5;
        }

        .summary-card.success {
            border-left-color: #059669;
            background: #f3fff9;
        }

        .summary-card.payment-group {
            border-left-color: #0ea5e9;
            background: #f6fbff;
        }

        .summary-label {
            display: block;
            color: #64748b;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.2px;
            font-weight: 700;
            margin-bottom: 2px;
        }

        .summary-value {
            display: block;
            color: #0f172a;
            font-size: 14px;
            font-weight: 800;
        }

        .group-values {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
            margin-top: 6px;
        }

        .group-chip {
            border: 1px solid #dbe2ea;
            border-radius: 6px;
            padding: 6px 8px;
            background: #fff;
        }

        .group-chip .summary-label {
            margin-bottom: 1px;
            font-size: 9px;
        }

        .group-chip .summary-value {
            font-size: 13px;
        }

        .footer {
            margin-top: 12px;
            text-align: right;
            color: #64748b;
            font-size: 11px;
        }
    </style>
</head>

<body onload="window.print();">
    <div class="report-wrap">
        <div class="title">
            <h2>SALE BILL REPORT</h2>
            <p>
                Session #{{ $session->id }} | {{ $session->branch->name ?? 'N/A' }} |
                {{ Carbon::parse($session->opened_at)->format('d-m-Y') }} to {{ Carbon::parse($session->closed_at)->format('d-m-Y') }}
            </p>
        </div>

        <table class="meta-grid">
            <tr>
                <td>
                    <span class="meta-label">Branch</span>
                    <span class="meta-value">{{ $session->branch->name ?? 'N/A' }}</span>
                </td>
                <td>
                    <span class="meta-label">Status</span>
                    <span class="meta-value">{{ strtoupper($session->status) }}</span>
                </td>
                <td>
                    <span class="meta-label">Opened By</span>
                    <span class="meta-value">{{ $session->opener->name ?? 'N/A' }}</span>
                </td>
                <td>
                    <span class="meta-label">Closed By</span>
                    <span class="meta-value">{{ $session->closer->name ?? 'N/A' }}</span>
                </td>
            </tr>
        </table>

        <div class="section-title">Sales Transactions</div>
        <table class="report-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Reference</th>
                    <th class="text-right">Amount</th>
                    <th>Payment</th>
                    <th class="text-right">Balance</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($transactions as $transaction)
                    <tr>
                        <td>{{ systemDate($transaction['date']) }}</td>
                        <td>{{ $transaction['source'] }}</td>
                        <td>{{ $transaction['reference_no'] ?? 'N/A' }}</td>
                        <td class="text-right">{{ currency($transaction['amount']) }}</td>
                        <td>
                            @if (!empty($transaction['payment_rows']))
                                <div class="payment-breakdown">
                                    @foreach ($transaction['payment_rows'] as $row)
                                        <span>{{ $row['method'] }}: {{ currency($row['amount']) }}</span>
                                    @endforeach
                                </div>
                            @else
                                <span class="muted">No payment</span>
                            @endif
                        </td>
                        <td class="text-right">{{ currency($transaction['due_amount']) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center muted">No transactions found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="section-title">Due Payment Report</div>
        <table class="report-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Reference</th>
                    <th>Payment Method</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pendingPayments as $pendingPayment)
                    <tr>
                        <td>{{ systemDate($pendingPayment['date']) }}</td>
                        <td>{{ $pendingPayment['source'] ?? 'N/A' }}</td>
                        <td>{{ $pendingPayment['reference_no'] ?? 'N/A' }}</td>
                        <td>{{ $pendingPayment['payment_method'] ?? 'N/A' }}</td>
                        <td class="text-right">{{ currency($pendingPayment['amount']) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center muted">No due payment receipts found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="section-title">Total Summary</div>
        <div class="summary-grid">
            <div class="summary-card">
                <span class="summary-label">Total Sale Amount</span>
                <span class="summary-value">{{ currency($totals['sale_tailoring_amount']) }}</span>
            </div>
            <div class="summary-card success">
                <span class="summary-label">Total Payment (Invoice)</span>
                <span class="summary-value">{{ currency($totals['payment_total']) }}</span>
            </div>
            <div class="summary-card warm">
                <span class="summary-label">Total Credit (Unpaid)</span>
                <span class="summary-value">{{ currency($totals['credit']) }}</span>
            </div>
            <div class="summary-card">
                <span class="summary-label">Total Due Payment</span>
                <span class="summary-value">{{ currency($totals['due_total']) }}</span>
            </div>
            <div class="summary-card payment-group" style="grid-column: span 2;">
                <span class="summary-label">Cash Payments</span>
                <div class="group-values">
                    <div class="group-chip">
                        <span class="summary-label">Due Cash</span>
                        <span class="summary-value">{{ currency($totals['due_total_cash']) }}</span>
                    </div>
                    <div class="group-chip">
                        <span class="summary-label">Invoice Cash</span>
                        <span class="summary-value">{{ currency($totals['cash']) }}</span>
                    </div>
                    <div class="group-chip">
                        <span class="summary-label">Total Cash</span>
                        <span class="summary-value">{{ currency($totals['cash'] + $totals['due_total_cash']) }}</span>
                    </div>
                </div>
            </div>
            <div class="summary-card payment-group" style="grid-column: span 2;">
                <span class="summary-label">Card Payments</span>
                <div class="group-values">
                    <div class="group-chip">
                        <span class="summary-label">Due Card</span>
                        <span class="summary-value">{{ currency($totals['due_total_card']) }}</span>
                    </div>
                    <div class="group-chip">
                        <span class="summary-label">Invoice Card</span>
                        <span class="summary-value">{{ currency($totals['card']) }}</span>
                    </div>
                    <div class="group-chip">
                        <span class="summary-label">Total Card</span>
                        <span class="summary-value">{{ currency($totals['card'] + $totals['due_total_card']) }}</span>
                    </div>
                </div>
            </div>
            <div class="summary-card success" style="grid-column: span 2;">
                <span class="summary-label">Grand Total Payment</span>
                <span class="summary-value">{{ currency($totals['payment_total'] + $totals['due_total']) }}</span>
            </div>
        </div>

        <div class="footer">Printed: {{ now()->format('d/m/Y h:i A') }}</div>
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

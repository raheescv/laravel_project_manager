<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Receipt - {{ $order->order_no }}</title>
    <style>
        * { box-sizing: border-box; }
        html, body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f1f5f9;
            color: #1e293b;
            font-size: 11px;
        }
        body { padding: 16px; }
        .print-container {
            max-width: 210mm;
            margin: 0 auto;
            padding: 0;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        /* Header */
        .header-strip {
            background: #0f766e;
            color: #ffffff;
            padding: 20px 24px 18px;
            text-align: center;
        }
        .header-strip .receipt-label {
            font-size: 10px;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            opacity: 0.9;
            margin-bottom: 4px;
        }
        .header-strip h1 {
            margin: 0;
            font-size: 22px;
            font-weight: bold;
            letter-spacing: 0.02em;
        }
        .header-strip .order-badge {
            display: inline-block;
            margin-top: 10px;
            padding: 6px 14px;
            background: rgba(255,255,255,0.2);
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 0.05em;
        }

        /* Info cards */
        .info-section {
            padding: 20px 24px;
            border-bottom: 1px solid #e2e8f0;
        }
        .info-cards {
            display: table;
            width: 100%;
            table-layout: fixed;
            border-spacing: 0 12px;
        }
        .info-card {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding: 14px 16px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
        }
        .info-card:first-child { padding-right: 20px; }
        .info-card .card-title {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #64748b;
            margin: 0 0 10px 0;
            padding-bottom: 6px;
            border-bottom: 1px solid #e2e8f0;
        }
        .info-card .info-row { margin-bottom: 6px; }
        .info-card .info-row:last-child { margin-bottom: 0; }
        .info-card .info-label {
            font-size: 9px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            display: block;
            margin-bottom: 2px;
        }
        .info-card .info-value {
            font-size: 12px;
            font-weight: bold;
            color: #0f766e;
        }
        .info-card .info-value.plain { font-weight: normal; color: #1e293b; font-size: 11px; }

        /* Section titles */
        .section-title {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color: #0f766e;
            margin: 0 0 12px 0;
            padding: 16px 24px 0;
        }
        .section-title .section-num {
            display: inline-block;
            width: 22px;
            height: 22px;
            line-height: 22px;
            text-align: center;
            background: #0f766e;
            color: #fff;
            border-radius: 50%;
            margin-right: 10px;
            font-size: 11px;
        }

        /* Items table */
        .table-wrap { padding: 0 24px 16px; }
        .receipt-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            overflow: hidden;
        }
        .receipt-table thead tr {
            background: #0f766e;
            color: #ffffff;
        }
        .receipt-table th {
            padding: 10px 12px;
            text-align: left;
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .receipt-table th.text-right { text-align: right; }
        .receipt-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #f1f5f9;
        }
        .receipt-table tbody tr:nth-child(even) { background: #f8fafc; }
        .receipt-table tbody tr:last-child td { border-bottom: none; }
        .receipt-table .text-right { text-align: right; }
        .receipt-table .product-name { font-weight: bold; color: #1e293b; }
        .receipt-table .product-meta { font-size: 9px; color: #64748b; margin-top: 2px; }

        /* Totals box */
        .totals-wrap { padding: 0 24px 20px; text-align: right; }
        .totals-box {
            display: inline-block;
            width: 260px;
            padding: 16px 20px;
            background: #f0fdfa;
            border: 2px solid #0f766e;
            border-radius: 8px;
            text-align: left;
        }
        .totals-box .row {
            display: table;
            width: 100%;
            padding: 5px 0;
            font-size: 11px;
        }
        .totals-box .row span:first-child { color: #64748b; }
        .totals-box .row span:last-child { font-weight: bold; text-align: right; display: table-cell; }
        .totals-box .row.grand {
            font-size: 14px;
            border-top: 2px solid #0f766e;
            margin-top: 10px;
            padding-top: 12px;
            color: #0f766e;
        }
        .totals-box .row.balance-paid { color: #059669; }
        .totals-box .row.balance-due { color: #b91c1c; }

        /* Payments */
        .payments-section { padding: 0 24px 20px; }
        .payments-section .section-title { padding-top: 8px; }
        .payments-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            overflow: hidden;
        }
        .payments-table thead tr { background: #475569; color: #fff; }
        .payments-table th {
            padding: 9px 12px;
            text-align: left;
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }
        .payments-table th.amount { text-align: right; }
        .payments-table td {
            padding: 9px 12px;
            border-bottom: 1px solid #f1f5f9;
        }
        .payments-table tbody tr:nth-child(even) { background: #f8fafc; }
        .payments-table .amount { text-align: right; font-weight: bold; color: #0f766e; }

        /* Footer */
        .footer {
            padding: 14px 24px;
            background: #f8fafc;
            border-top: 3px solid #0f766e;
            font-size: 9px;
            color: #64748b;
            text-align: center;
            letter-spacing: 0.04em;
        }

        .no-print { text-align: center; margin-top: 16px; }
        .no-print button {
            padding: 12px 30px; cursor: pointer; background: #0f766e; color: #fff; border: none; border-radius: 6px; font-weight: bold; font-size: 1rem;
        }
        @media print {
            @page { size: A4; margin: 10mm; }
            html, body { background: #fff; margin: 0; padding: 0; font-size: 10px; }
            .print-container { border: none; max-width: none; box-shadow: none; }
            .no-print { display: none !important; }
        }
    </style>
</head>

<body>
    <div class="print-container">
        <div class="header-strip">
            <div class="receipt-label">Tailoring</div>
            <h1>Order Receipt</h1>
            <span class="order-badge">#{{ $order->order_no }}</span>
        </div>

        <div class="info-section">
            <div class="info-cards">
                <div class="info-card">
                    <div class="card-title">Customer</div>
                    <div class="info-row">
                        <span class="info-label">Name</span>
                        <span class="info-value">{{ $order->customer_name }}</span>
                    </div>
                    @if($order->customer_mobile)
                    <div class="info-row">
                        <span class="info-label">Mobile</span>
                        <span class="info-value plain">{{ $order->customer_mobile }}</span>
                    </div>
                    @endif
                </div>
                <div class="info-card">
                    <div class="card-title">Order details</div>
                    <div class="info-row">
                        <span class="info-label">Order date</span>
                        <span class="info-value plain">{{ $order->order_date ? $order->order_date->format('d/m/Y') : '-' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Delivery date</span>
                        <span class="info-value plain">{{ $order->delivery_date ? $order->delivery_date->format('d/m/Y') : '-' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Branch</span>
                        <span class="info-value plain">{{ $order->branch->name ?? '-' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Salesman</span>
                        <span class="info-value plain">{{ $order->salesman->name ?? '-' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <h2 class="section-title"><span class="section-num">1</span> Order items</h2>
        <div class="table-wrap">
            <table class="receipt-table">
                <thead>
                    <tr>
                        <th style="width:28px">#</th>
                        <th>Product</th>
                        <th>Category / Model</th>
                        <th class="text-right" style="width:70px">Qty</th>
                        <th class="text-right" style="width:72px">Rate</th>
                        <th class="text-right" style="width:80px">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                    <tr>
                        <td>{{ $item->item_no }}</td>
                        <td>
                            <span class="product-name">{{ $item->product_name }}</span>
                            @if($item->product_color)<br><span class="product-meta">{{ $item->product_color }}</span>@endif
                        </td>
                        <td>{{ $item->category->name ?? '-' }} / {{ $item->categoryModel->name ?? $item->tailoring_category_model_name ?? 'Standard' }}</td>
                        <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
                        <td class="text-right">{{ currency($item->stitch_rate) }}</td>
                        <td class="text-right">{{ currency($item->total) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <h2 class="section-title"><span class="section-num">2</span> Summary</h2>
        <div class="totals-wrap">
            <div class="totals-box">
                @if(isset($order->gross_amount) && (float) $order->gross_amount != (float) $order->total)
                    <div class="row"><span>Gross</span><span>{{ currency($order->gross_amount ?? 0) }}</span></div>
                @endif
                @php $orderDiscount = (float) ($order->discount ?? (($order->item_discount ?? 0) + ($order->other_discount ?? 0))); @endphp
                @if($orderDiscount > 0)
                    <div class="row"><span>Discount</span><span>-{{ currency($orderDiscount) }}</span></div>
                @endif
                <div class="row grand"><span>Total</span><span>{{ currency($order->total) }}</span></div>
                <div class="row balance-paid"><span>Paid</span><span>{{ currency($order->paid) }}</span></div>
                <div class="row {{ $order->balance > 0 ? 'balance-due' : 'balance-paid' }}"><span>Balance</span><span>{{ currency($order->balance) }}</span></div>
            </div>
        </div>

        @if($order->payments->count() > 0)
        <h2 class="section-title"><span class="section-num">3</span> Payments</h2>
        <div class="payments-section">
            <table class="payments-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Method</th>
                        <th class="amount">Amount</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->payments as $payment)
                    <tr>
                        <td>{{ $payment->date ? $payment->date->format('d/m/Y') : '-' }}</td>
                        <td>{{ $payment->paymentMethod->name ?? '-' }}</td>
                        <td class="amount">{{ currency($payment->amount) }}</td>
                        <td>{{ $payment->remarks ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <div class="footer">
            Printed on {{ now()->format('d/m/Y H:i') }} &mdash; Order #{{ $order->order_no }}
        </div>
    </div>
</body>

</html>

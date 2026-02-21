<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta charset="UTF-8">
    <title>Tailoring Non-Delivery Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            color: #1f2937;
            background: #ffffff;
            padding: 16px;
        }

        .report-shell {
            border: 1px solid #dbe2ea;
            border-radius: 10px;
            overflow: hidden;
        }

        .header {
            padding: 14px 16px 10px;
            background: #f8fafc;
            border-bottom: 1px solid #e5e7eb;
        }

        .header-top {
            width: 100%;
            border-collapse: collapse;
        }

        .header-top td {
            border: none;
            padding: 0;
            vertical-align: top;
        }

        .company {
            font-size: 11px;
            color: #64748b;
            font-weight: 700;
            letter-spacing: 0.4px;
            text-transform: uppercase;
            margin-bottom: 3px;
        }

        .title {
            font-size: 18px;
            color: #0f172a;
            font-weight: 700;
            line-height: 1.2;
        }

        .meta {
            text-align: right;
            font-size: 9px;
            color: #475569;
            line-height: 1.6;
        }

        .filters {
            padding: 10px 16px 12px;
            border-bottom: 1px solid #e5e7eb;
            background: #ffffff;
        }

        .chip {
            display: inline-block;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            border-radius: 999px;
            padding: 3px 8px;
            margin: 0 6px 6px 0;
            font-size: 8.5px;
            color: #334155;
            line-height: 1.2;
        }

        .chip b {
            color: #0f172a;
        }

        .table-wrap {
            padding: 0 16px 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: auto;
        }

        thead th {
            background: #1e293b;
            color: #ffffff;
            border: 1px solid #d1d5db;
            padding: 6px 5px;
            text-align: left;
            font-size: 8.5px;
            font-weight: 700;
        }

        tbody td {
            border: 1px solid #e5e7eb;
            padding: 5px;
            font-size: 8.5px;
            color: #1f2937;
            vertical-align: middle;
        }

        tbody tr:nth-child(even) td {
            background: #f8fafc;
        }

        tfoot th {
            border: 1px solid #d1d5db;
            padding: 6px 5px;
            background: #e2e8f0;
            color: #0f172a;
            font-size: 8.5px;
            font-weight: 700;
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .status {
            display: inline-block;
            border: 1px solid #cbd5e1;
            border-radius: 999px;
            padding: 2px 6px;
            font-size: 8px;
            color: #334155;
            background: #f8fafc;
        }

        .footer {
            padding: 8px 16px 12px;
            font-size: 8px;
            color: #64748b;
            text-align: right;
        }
    </style>
</head>

<body>
    @php
        $defaultVisible = [
            'order_no' => true,
            'order_date' => true,
            'delivery_date' => true,
            'customer' => true,
            'mobile' => true,
            'bill_amount' => true,
            'paid_amount' => true,
            'balance_amount' => true,
            'item_quantity' => true,
            'completed_qty' => true,
            'pending_qty' => true,
            'delivery_qty' => true,
            'order_status' => true,
        ];
        $visibleColumns = array_merge($defaultVisible, (array) ($filters['visible_columns'] ?? []));
        $visibleCount = 1 + collect($visibleColumns)->filter()->count();
    @endphp

    <div class="report-shell">
        <div class="header">
            <table class="header-top">
                <tr>
                    <td>
                        <div class="company">Tailoring and Stores</div>
                        <div class="title">Non-Delivery Report</div>
                    </td>
                    <td class="meta">
                        <div><b>Generated:</b> {{ now()->format('d M Y h:i A') }}</div>
                        <div><b>Rows:</b> {{ number_format((int) ($totals['total_orders'] ?? 0)) }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="filters">
            <span class="chip"><b>Date:</b> {{ $filters['from_date'] ?: 'All' }} to {{ $filters['to_date'] ?: 'All' }}</span>
            <span class="chip"><b>Branch:</b> {{ $filters['branch_name'] ?? 'All Branches' }}</span>
            <span class="chip"><b>Customer:</b> {{ $filters['customer_name'] ?? 'All Customers' }}</span>
            <span class="chip"><b>Category:</b> {{ $filters['category_name'] ?? 'All Categories' }}</span>
            <span class="chip"><b>Product:</b> {{ $filters['product_name'] ?? 'All Products' }}</span>
            <span class="chip"><b>Status:</b>
                {{ collect((array) ($filters['status'] ?? []))->map(fn ($status) => $statusOptions[$status] ?? ucfirst((string) $status))->implode(', ') ?: 'All' }}
            </span>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        @if ($visibleColumns['order_no'] ?? true)
                            <th>Order Ref</th>
                        @endif
                        @if ($visibleColumns['order_date'] ?? true)
                            <th>Order Date</th>
                        @endif
                        @if ($visibleColumns['delivery_date'] ?? true)
                            <th>Delivery Date</th>
                        @endif
                        @if ($visibleColumns['customer'] ?? true)
                            <th>Customer</th>
                        @endif
                        @if ($visibleColumns['mobile'] ?? true)
                            <th>Mobile</th>
                        @endif
                        @if ($visibleColumns['bill_amount'] ?? true)
                            <th class="text-right">Bill Amount</th>
                        @endif
                        @if ($visibleColumns['paid_amount'] ?? true)
                            <th class="text-right">Paid</th>
                        @endif
                        @if ($visibleColumns['balance_amount'] ?? true)
                            <th class="text-right">Balance</th>
                        @endif
                        @if ($visibleColumns['item_quantity'] ?? true)
                            <th class="text-right">Item Qty</th>
                        @endif
                        @if ($visibleColumns['completed_qty'] ?? true)
                            <th class="text-right">Completed Qty</th>
                        @endif
                        @if ($visibleColumns['pending_qty'] ?? true)
                            <th class="text-right">Pending Qty</th>
                        @endif
                        @if ($visibleColumns['delivery_qty'] ?? true)
                            <th class="text-right">Delivery Qty</th>
                        @endif
                        @if ($visibleColumns['order_status'] ?? true)
                            <th>Order Status</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td>{{ $row->id }}</td>
                            @if ($visibleColumns['order_no'] ?? true)
                                <td># {{ $row->order_no }}</td>
                            @endif
                            @if ($visibleColumns['order_date'] ?? true)
                                <td>{{ $row->order_date ? systemDate($row->order_date) : '-' }}</td>
                            @endif
                            @if ($visibleColumns['delivery_date'] ?? true)
                                <td>{{ $row->delivery_date ? systemDate($row->delivery_date) : '-' }}</td>
                            @endif
                            @if ($visibleColumns['customer'] ?? true)
                                <td>{{ $row->customer_name ?? '-' }}</td>
                            @endif
                            @if ($visibleColumns['mobile'] ?? true)
                                <td>{{ $row->customer_mobile ?? '-' }}</td>
                            @endif
                            @if ($visibleColumns['bill_amount'] ?? true)
                                <td class="text-right">{{ number_format((float) ($row->bill_amount ?? 0), 2) }}</td>
                            @endif
                            @if ($visibleColumns['paid_amount'] ?? true)
                                <td class="text-right">{{ number_format((float) ($row->paid_amount ?? 0), 2) }}</td>
                            @endif
                            @if ($visibleColumns['balance_amount'] ?? true)
                                <td class="text-right">{{ number_format((float) ($row->balance_amount ?? 0), 2) }}</td>
                            @endif
                            @if ($visibleColumns['item_quantity'] ?? true)
                                <td class="text-right">{{ round($row->item_quantity) }}</td>
                            @endif
                            @if ($visibleColumns['completed_qty'] ?? true)
                                <td class="text-right">{{ round($row->completed_qty) }}</td>
                            @endif
                            @if ($visibleColumns['pending_qty'] ?? true)
                                <td class="text-right">{{ round($row->pending_qty) }}</td>
                            @endif
                            @if ($visibleColumns['delivery_qty'] ?? true)
                                <td class="text-right">{{ round($row->delivery_qty) }}</td>
                            @endif
                            @if ($visibleColumns['order_status'] ?? true)
                                <td><span class="status">{{ ucWords($row->order_status) }}</span></td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $visibleCount }}" class="text-center">No records found.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <th>Total</th>
                        @if ($visibleColumns['order_no'] ?? true)
                            <th></th>
                        @endif
                        @if ($visibleColumns['order_date'] ?? true)
                            <th></th>
                        @endif
                        @if ($visibleColumns['delivery_date'] ?? true)
                            <th></th>
                        @endif
                        @if ($visibleColumns['customer'] ?? true)
                            <th></th>
                        @endif
                        @if ($visibleColumns['mobile'] ?? true)
                            <th></th>
                        @endif
                        @if ($visibleColumns['bill_amount'] ?? true)
                            <th class="text-right">{{ number_format((float) ($totals['bill_amount'] ?? 0), 2) }}</th>
                        @endif
                        @if ($visibleColumns['paid_amount'] ?? true)
                            <th class="text-right">{{ number_format((float) ($totals['paid_amount'] ?? 0), 2) }}</th>
                        @endif
                        @if ($visibleColumns['balance_amount'] ?? true)
                            <th class="text-right">{{ number_format((float) ($totals['balance_amount'] ?? 0), 2) }}</th>
                        @endif
                        @if ($visibleColumns['item_quantity'] ?? true)
                            <th class="text-right">{{ round($totals['item_quantity']) }}</th>
                        @endif
                        @if ($visibleColumns['completed_qty'] ?? true)
                            <th class="text-right">{{ round($totals['completed_qty']) }}</th>
                        @endif
                        @if ($visibleColumns['pending_qty'] ?? true)
                            <th class="text-right">{{ round($totals['pending_qty']) }}</th>
                        @endif
                        @if ($visibleColumns['delivery_qty'] ?? true)
                            <th class="text-right">{{ round($totals['delivery_qty']) }}</th>
                        @endif
                        @if ($visibleColumns['order_status'] ?? true)
                            <th></th>
                        @endif
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="footer">This is a system generated report.</div>
    </div>
</body>

</html>

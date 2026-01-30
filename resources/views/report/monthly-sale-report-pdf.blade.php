<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monthly Sale Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            line-height: 1.5;
            color: #2c3e50;
            background: #ffffff;
            padding: 15px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #4F46E5;
        }

        .title {
            font-size: 16px;
            font-weight: bold;
            color: #1e293b;
            margin-bottom: 5px;
        }

        .subtitle {
            font-size: 11px;
            color: #64748b;
        }

        .report-info {
            text-align: right;
            margin-bottom: 15px;
            font-size: 9px;
            color: #64748b;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        table th {
            background-color: #4F46E5;
            color: #ffffff;
            padding: 6px;
            text-align: left;
            font-weight: bold;
            font-size: 9px;
        }

        table th.text-right {
            text-align: right;
        }

        table td {
            padding: 5px 6px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 9px;
        }

        table td.text-right {
            text-align: right;
        }

        table tr:nth-child(even) {
            background-color: #f8fafc;
        }

        table tfoot th {
            background-color: #E6F3FF;
            color: #1e293b;
            font-weight: bold;
        }

        table tbody {
            display: table-row-group;
        }

        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 8px;
            color: #64748b;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="title">Monthly Sale Report</div>
        <div class="subtitle">{{ e($fromMonth) }} to {{ e($toMonth) }}</div>
    </div>

    <div class="report-info">
        Generated on: {{ e(now()->format('d-M-Y h:i A')) }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Month</th>
                <th class="text-right">Gross Sales</th>
                <th class="text-right">Discount</th>
                <th class="text-right">Net Sale</th>
                <th class="text-right">Paid (Total)</th>
                <th class="text-right">Credit</th>
                <th class="text-right">Card</th>
                <th class="text-right">Cash</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $item)
                <tr>
                    <td>{{ e($item['month_name']) }}</td>
                    <td class="text-right">{{ number_format((float) $item['gross_sales'], 2) }}</td>
                    <td class="text-right">{{ number_format((float) $item['discount'], 2) }}</td>
                    <td class="text-right">{{ number_format((float) $item['net_sale'], 2) }}</td>
                    <td class="text-right">{{ number_format((float) $item['paid_total'], 2) }}</td>
                    <td class="text-right">{{ number_format((float) $item['credit'], 2) }}</td>
                    <td class="text-right">{{ number_format((float) $item['card'], 2) }}</td>
                    <td class="text-right">{{ number_format((float) $item['cash'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th>Total</th>
                <th class="text-right">{{ number_format((float) $total['gross_sales'], 2) }}</th>
                <th class="text-right">{{ number_format((float) $total['discount'], 2) }}</th>
                <th class="text-right">{{ number_format((float) $total['net_sale'], 2) }}</th>
                <th class="text-right">{{ number_format((float) $total['paid_total'], 2) }}</th>
                <th class="text-right">{{ number_format((float) $total['credit'], 2) }}</th>
                <th class="text-right">{{ number_format((float) $total['card'], 2) }}</th>
                <th class="text-right">{{ number_format((float) $total['cash'], 2) }}</th>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        This report was generated automatically by the system.
    </div>
</body>

</html>

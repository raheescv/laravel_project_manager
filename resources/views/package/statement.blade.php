<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Package Statement - #{{ $package->id }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
            background-color: #fff;
            padding: 20px;
        }

        .statement-container {
            max-width: 900px;
            margin: 0 auto;
            background: #fff;
            padding: 30px;
        }

        /* Header Section */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }

        .company-info {
            flex: 1;
        }

        .company-name {
            font-size: 18px;
            font-weight: 700;
            color: #000;
            margin-bottom: 8px;
        }

        .company-details {
            font-size: 11px;
            color: #666;
            line-height: 1;
        }

        .statement-info {
            text-align: right;
            flex: 1;
        }

        .statement-title {
            font-size: 20px;
            font-weight: 700;
            color: #000;
            margin-bottom: 15px;
            text-transform: uppercase;
        }

        .statement-details {
            font-size: 11px;
            color: #333;
            line-height: 1;
        }

        .statement-details strong {
            font-weight: 600;
        }

        /* Bill To Section */
        .bill-to-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }

        .bill-to {
            flex: 1;
        }

        .bill-to-label {
            font-size: 11px;
            font-weight: 700;
            color: #000;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .customer-info {
            font-size: 11px;
            color: #333;
            line-height: 1.3;
        }

        .customer-info strong {
            font-weight: 600;
        }

        .account-number {
            text-align: right;
            flex: 1;
        }

        .account-number-label {
            font-size: 11px;
            font-weight: 700;
            color: #000;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .account-number-value {
            font-size: 13px;
            font-weight: 700;
            color: #000;
        }

        /* Service History Section */
        .section-title {
            font-size: 12px;
            font-weight: 700;
            color: #000;
            margin-bottom: 12px;
            text-transform: uppercase;
        }

        .service-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }

        /* .service-table th:nth-child(1),
        .service-table td:nth-child(1) {
            width: 15%;
        }

        .service-table th:nth-child(2),
        .service-table td:nth-child(2) {
            width: 12%;
        }

        .service-table th:nth-child(3),
        .service-table td:nth-child(3) {
            width: 38%;
        }

        .service-table th:nth-child(4),
        .service-table td:nth-child(4),
        .service-table th:nth-child(5),
        .service-table td:nth-child(5),
        .service-table th:nth-child(6),
        .service-table td:nth-child(6) {
            width: 16%;
        } */

        .service-table thead {
            background-color: #f5f5f5;
        }

        .service-table th {
            padding: 10px 8px;
            text-align: left;
            font-size: 11px;
            font-weight: 700;
            color: #000;
            border: 1px solid #ddd;
            border-bottom: 2px solid #ddd;
        }

        .service-table th.text-right {
            text-align: right;
        }

        .service-table td {
            padding: 10px 8px;
            font-size: 11px;
            color: #333;
            border: 1px solid #ddd;
            border-top: none;
        }

        .service-table td.text-right {
            text-align: right;
        }

        .service-table tbody tr:first-child td {
            border-top: 1px solid #ddd;
        }

        .service-table tbody tr:last-child {
            background-color: #f5f5f5;
            border-top: 2px solid #ddd;
        }

        .service-table tbody tr:last-child td {
            border-top: 2px solid #ddd;
            font-weight: 700;
        }

        /* Payments & Adjustments Section */
        .payments-section {
            margin-bottom: 25px;
        }

        .payments-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }

        .payments-table thead {
            background-color: #f5f5f5;
        }

        .payments-table th {
            padding: 10px 8px;
            text-align: left;
            font-size: 11px;
            font-weight: 700;
            color: #000;
            border: 1px solid #ddd;
            border-bottom: 2px solid #ddd;
        }

        .payments-table th.text-right {
            text-align: right;
        }

        .payments-table td {
            padding: 10px 8px;
            font-size: 11px;
            color: #333;
            border: 1px solid #ddd;
            border-top: none;
        }

        .payments-table td.text-right {
            text-align: right;
        }

        .payments-table tbody tr:first-child td {
            border-top: 1px solid #ddd;
        }



        .balance-due {
            font-size: 13px;
            font-weight: 700;
            text-decoration: underline;
        }

        /* Footer */
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            text-align: center;
            font-size: 11px;
            color: #666;
            line-height: 2;
        }

        /* Print Styles */
        @media print {
            body {
                padding: 0;
                background: #fff;
            }

            .statement-container {
                padding: 20px;
                max-width: 100%;
            }

            @page {
                margin: 1cm;
            }
        }

        /* Responsive */
        @media screen and (max-width: 768px) {
            body {
                padding: 10px;
            }

            .statement-container {
                padding: 20px;
            }

            .header {
                flex-direction: column;
            }

            .statement-info {
                text-align: left;
                margin-top: 20px;
            }

            .bill-to-section {
                flex-direction: column;
            }

            .account-number {
                text-align: left;
                margin-top: 15px;
            }

        }
    </style>
</head>

<body>
    <div class="statement-container">
        <!-- Header -->
        <div class="header">
            <div class="company-info">
                <div class="company-name">{{ $companyName }}</div>
                @if ($companyAddress)
                    <div class="company-details">{{ $companyAddress }}</div>
                @endif
                @if ($companyPhone || $companyEmail)
                    <div class="company-details">
                        @if ($companyPhone)
                            {{ $companyPhone }}
                        @endif
                        @if ($companyPhone && $companyEmail)
                            &nbsp;|&nbsp;
                        @endif
                        @if ($companyEmail)
                            {{ $companyEmail }}
                        @endif
                    </div>
                @endif
            </div>
            <div class="statement-info">
                <div class="statement-title">Statement</div>
                <div class="statement-details">
                    <div><strong>Statement #:</strong> PKG-{{ $package->id }}</div>
                    <div><strong>Date:</strong> {{ now()->format('F d, Y') }}</div>
                    @if ($fromDate && $toDate)
                        <div><strong>Period:</strong> {{ date('F d, Y', strtotime($fromDate)) }} - {{ date('F d, Y', strtotime($toDate)) }}</div>
                    @elseif($fromDate)
                        <div><strong>From:</strong> {{ date('F d, Y', strtotime($fromDate)) }}</div>
                    @elseif($toDate)
                        <div><strong>Until:</strong> {{ date('F d, Y', strtotime($toDate)) }}</div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Bill To Section -->
        <div class="bill-to-section">
            <div class="bill-to">
                <div class="bill-to-label">Bill To</div>
                <div class="customer-info">
                    @if ($package->account)
                        <div><strong>{{ $package->account->name }}</strong></div>
                        @if ($package->account->place)
                            <div>{{ $package->account->place }}</div>
                        @endif
                        @if ($package->account->mobile)
                            <div>{{ $package->account->mobile }}</div>
                        @endif
                        @if ($package->account->email)
                            <div>{{ $package->account->email }}</div>
                        @endif
                    @else
                        <div>N/A</div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Transaction History -->
        @if ($ledgerEntries->count() > 0)
            <div class="service-section">
                <div class="section-title">Transaction History</div>
                <table class="service-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Reference</th>
                            <th>Description</th>
                            <th class="text-right">Debit</th>
                            <th class="text-right">Credit</th>
                            <th class="text-right">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($ledgerEntries as $entry)
                            @php
                                $isVisited = $entry->type === 'visit' && isset($entry->model) && $entry->model->status === 'visited';
                                $isFutureNotVisited = $entry->type === 'visit' && isset($entry->model) && $entry->model->status !== 'visited' && strtotime($entry->date) > strtotime('today');

                                if ($isVisited) {
                                    $rowStyle = 'background-color: #f0fdf4; color: #059669;';
                                    $textColor = '#059669';
                                } elseif ($isFutureNotVisited) {
                                    $rowStyle = 'background-color: #f5f5f5; color: #999;';
                                    $textColor = '#999';
                                } else {
                                    $rowStyle = '';
                                    $textColor = '#333';
                                }
                            @endphp
                            <tr style="{{ $rowStyle }}">
                                <td>{{ systemDate($entry->date) }}</td>
                                <td style="font-weight: 600; color: {{ $textColor }};">{{ $entry->reference }}</td>
                                <td>
                                    {{ $entry->description }}
                                    @if ($entry->type === 'visit' && $entry->model)
                                        <span style="font-size: 10px; color: {{ $isFutureNotVisited ? '#999' : '#666' }};">
                                            ({{ ucwords($entry->model->status) }})
                                        </span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    @if ($entry->debit > 0)
                                        <span style="color: {{ $textColor }}; font-weight: 600;">{{ currency($entry->debit) }}</span>
                                    @else
                                        <span style="color: #999;">-</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    @if ($entry->credit > 0)
                                        <span
                                            style="color: {{ $entry->type === 'payment' && !$isFutureNotVisited ? '#059669' : $textColor }}; font-weight: 600;">{{ currency($entry->credit) }}</span>
                                    @else
                                        <span style="color: #999;">-</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <span style="color: {{ $textColor }}; font-weight: 600;">{{ currency($entry->balance) }}</span>
                                </td>
                            </tr>
                        @endforeach
                        <tr style="background-color: #f5f5f5; border-top: 2px solid #ddd;">
                            <td colspan="3" class="text-right" style="font-weight: 700; padding: 12px 8px;">Closing Balance</td>
                            <td class="text-right" style="font-weight: 700; padding: 12px 8px;">{{ currency($totalDebit) }}</td>
                            <td class="text-right" style="font-weight: 700; padding: 12px 8px;">{{ currency($totalCredit) }}</td>
                            <td class="text-right" style="font-weight: 700; padding: 12px 8px;">{{ currency($closingBalance) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif
        <!-- Footer -->
        <div class="footer">
            <div>Thank you for trusting us with your care.</div>
        </div>
    </div>
</body>

</html>

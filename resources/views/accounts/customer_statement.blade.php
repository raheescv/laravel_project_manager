<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="format-detection" content="telephone=no">
    <title>Customer Statement</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #2c3e50;
            background: #f8f9fa;
            padding: 10px 8px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: #f8fafc;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.06);
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e9ecef;
        }

        .header {
            background: linear-gradient(135deg, #475569 0%, #64748b 100%);
            color: #ffffff;
            padding: 15px 20px;
            position: relative;
            overflow: hidden;
            border-bottom: 2px solid #475569;
        }

        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: pulse 20s ease-in-out infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
                opacity: 0.3;
            }

            50% {
                transform: scale(1.1);
                opacity: 0.5;
            }
        }

        .company-info {
            text-align: center;
            margin-bottom: 8px;
            position: relative;
            z-index: 1;
        }

        .company-name {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 4px;
            letter-spacing: -0.5px;
            color: #ffffff;
        }

        .company-details {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.95);
            font-weight: 400;
            line-height: 1.5;
        }

        .statement-title {
            text-align: center;
            font-size: 13px;
            font-weight: 600;
            margin-top: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            z-index: 1;
            color: #ffffff;
        }

        .content-section {
            padding: 15px 20px;
        }

        .customer-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding: 12px 15px;
            background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
            border-left: 3px solid #94a3b8;
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }

        .customer-details {
            flex: 1;
        }

        .customer-details h3 {
            font-size: 16px;
            margin-bottom: 6px;
            color: #1e293b;
            font-weight: 600;
            letter-spacing: -0.3px;
        }

        .customer-details p {
            margin: 3px 0;
            font-size: 11px;
            color: #64748b;
            display: flex;
            align-items: center;
        }

        .customer-details p strong {
            min-width: 80px;
            color: #475569;
            font-weight: 500;
        }

        .statement-period {
            text-align: center;
            text-color: white;
            font-size: 11px;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 15px;
            background: #f8fafc;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
        }

        table thead {
            background: linear-gradient(135deg, #475569 0%, #64748b 100%);
            color: #ffffff;
            border-bottom: 2px solid #475569;
        }

        table th {
            padding: 8px 10px;
            text-align: left;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
            color: #ffffff;
        }

        table th.text-right {
            text-align: right;
        }

        table td {
            padding: 6px 10px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 11px;
            color: #475569;
            transition: background-color 0.2s ease;
        }

        table tbody tr {
            transition: all 0.2s ease;
        }

        table tbody tr:hover {
            background: #f1f5f9;
            transform: translateY(-1px);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        table tbody tr:last-child td {
            border-bottom: none;
        }

        .ledger-row {
            background: #f8fafc;
        }

        .debit-row {
            background: linear-gradient(90deg, rgba(148, 163, 184, 0.12) 0%, #f1f5f9 100%);
            border-left: 3px solid #94a3b8;
        }

        .credit-row {
            background: linear-gradient(90deg, rgba(203, 213, 225, 0.12) 0%, #f1f5f9 100%);
            border-left: 3px solid #cbd5e1;
        }

        .sale-return-row {
            background: linear-gradient(90deg, rgba(239, 68, 68, 0.12) 0%, #fef2f2 100%);
            border-left: 3px solid #ef4444;
        }

        .sale-return-row td {
            color: #dc2626;
        }

        .sale-return-row .reference-no {
            color: #dc2626;
        }

        .payment-row {
            background: linear-gradient(90deg, rgba(16, 185, 129, 0.12) 0%, #f0fdf4 100%);
            border-left: 3px solid #10b981;
        }

        .payment-row td {
            color: #059669;
        }

        .payment-row .reference-no {
            color: #059669;
        }

        .opening-balance-row {
            background: linear-gradient(90deg, rgba(226, 232, 240, 0.6) 0%, #f1f5f9 100%);
            font-weight: 600;
            border-left: 3px solid #94a3b8;
        }

        .closing-balance-row {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            color: #1e293b;
            font-weight: 600;
            font-size: 12px;
            border-top: 2px solid #cbd5e1;
        }

        .closing-balance-row td {
            color: #1e293b;
            border-bottom: none;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .footer {
            margin-top: 15px;
            padding: 10px 20px;
            background: #f8fafc;
            text-align: center;
            font-size: 10px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
        }

        .footer p {
            margin: 5px 0;
        }

        .no-data {
            text-align: center;
            padding: 30px 20px;
            color: #94a3b8;
        }

        .no-data svg {
            width: 60px;
            height: 60px;
            margin-bottom: 12px;
            opacity: 0.4;
        }

        .no-data p {
            font-size: 13px;
            font-style: italic;
            color: #64748b;
        }

        .reference-no {
            font-weight: 600;
            color: #64748b;
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }

        .description {
            font-size: 13px;
            color: #64748b;
            line-height: 1.6;
        }

        .balance-positive {
            color: #475569;
            font-weight: 600;
        }

        .balance-negative {
            color: #64748b;
            font-weight: 600;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-dr {
            background: rgba(148, 163, 184, 0.15);
            color: #475569;
        }

        .badge-cr {
            background: rgba(203, 213, 225, 0.15);
            color: #64748b;
        }

        /* Responsive Table Wrapper */
        .table-wrapper {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin: 0 -20px;
            padding: 0 20px;
        }

        .table-wrapper::-webkit-scrollbar {
            height: 8px;
        }

        .table-wrapper::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .table-wrapper::-webkit-scrollbar-thumb {
            background: #94a3b8;
            border-radius: 4px;
        }

        .table-wrapper::-webkit-scrollbar-thumb:hover {
            background: #64748b;
        }

        /* Mobile Card View for Table */
        .mobile-card-view {
            display: none;
        }

        .mobile-card {
            background: #f8fafc;
            border-radius: 6px;
            padding: 10px 12px;
            margin-bottom: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            border-left: 3px solid #94a3b8;
        }

        .mobile-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            padding-bottom: 6px;
            border-bottom: 1px solid #e2e8f0;
        }

        .mobile-card-date {
            font-weight: 600;
            color: #2d3748;
            font-size: 12px;
        }

        .mobile-card-reference {
            font-weight: 600;
            color: #64748b;
            font-family: 'Courier New', monospace;
            font-size: 10px;
        }

        .mobile-card-body {
            margin-bottom: 8px;
        }

        .mobile-card-description {
            color: #4a5568;
            font-size: 11px;
            margin-bottom: 6px;
        }

        .mobile-card-amounts {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-bottom: 8px;
        }

        .mobile-card-amount {
            padding: 6px;
            border-radius: 4px;
            background: #f7fafc;
        }

        .mobile-card-amount-label {
            font-size: 9px;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }

        .mobile-card-amount-value {
            font-size: 12px;
            font-weight: 600;
        }

        .mobile-card-balance {
            padding: 8px;
            background: linear-gradient(135deg, #f1f5f9 0%, #f8fafc 100%);
            border-radius: 4px;
            text-align: center;
        }

        .mobile-card-balance-label {
            font-size: 9px;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .mobile-card-balance-value {
            font-size: 14px;
            font-weight: 700;
        }

        /* Responsive Design */
        @media screen and (max-width: 1024px) {
            .content-section {
                padding: 12px 15px;
            }

            .header {
                padding: 12px 15px;
            }

            .footer {
                padding: 8px 15px;
            }

            .table-wrapper {
                margin: 0 -15px;
                padding: 0 15px;
            }
        }

        @media screen and (max-width: 768px) {
            body {
                padding: 8px 5px;
            }

            .container {
                border-radius: 6px;
            }

            .header {
                padding: 10px 12px;
            }

            .company-name {
                font-size: 18px;
            }

            .company-details {
                font-size: 10px;
            }

            .statement-title {
                font-size: 11px;
                margin-top: 6px;
            }

            .content-section {
                padding: 10px 12px;
            }

            .customer-info {
                flex-direction: column;
                padding: 10px 12px;
                gap: 10px;
            }

            .customer-details h3 {
                font-size: 14px;
            }

            .customer-details p {
                flex-direction: column;
                align-items: flex-start;
            }

            .customer-details p strong {
                min-width: auto;
                margin-bottom: 2px;
            }

            .statement-period {
                text-align: left;
                min-width: auto;
                width: 100%;
                padding: 8px 10px;
            }

            .statement-period strong {
                margin-top: 8px;
            }

            .statement-period strong:first-child {
                margin-top: 0;
            }

            /* Hide desktop table, show mobile cards */
            .table-wrapper {
                display: none;
            }

            .mobile-card-view {
                display: block;
            }

            .footer {
                padding: 8px 12px;
                font-size: 9px;
            }

            .no-data {
                padding: 20px 15px;
            }

            .no-data svg {
                width: 50px;
                height: 50px;
            }
        }

        @media screen and (max-width: 480px) {
            body {
                padding: 5px 3px;
            }

            .header {
                padding: 8px 10px;
            }

            .company-name {
                font-size: 16px;
            }

            .content-section {
                padding: 8px 10px;
            }

            .customer-info {
                padding: 8px 10px;
            }

            .mobile-card {
                padding: 8px 10px;
            }

            .mobile-card-amounts {
                grid-template-columns: 1fr;
            }

            .badge {
                font-size: 9px;
                padding: 2px 6px;
            }
        }

        /* Tablet Landscape */
        @media screen and (min-width: 769px) and (max-width: 1024px) {
            table {
                font-size: 11px;
            }

            table th,
            table td {
                padding: 6px 8px;
                font-size: 11px;
            }
        }

        /* Large Screens */
        @media screen and (min-width: 1400px) {
            .container {
                max-width: 1400px;
            }
        }

        /* Print Styles */
        @media print {
            @page {
                margin: 1cm;
                size: A4;
            }

            body {
                background: #ffffff;
                padding: 0;
                font-size: 11px;
            }

            .container {
                box-shadow: none;
                border-radius: 0;
                max-width: 100%;
            }

            .header {
                background: #64748b !important;
                color: #ffffff !important;
                padding: 12px 15px;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .header::before {
                display: none;
            }

            .content-section {
                padding: 12px 15px;
            }

            .customer-info {
                page-break-inside: avoid;
            }

            .table-wrapper {
                margin: 0;
                padding: 0;
            }

            table {
                page-break-inside: auto;
            }

            table tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }

            table thead {
                display: table-header-group;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            table tfoot {
                display: table-footer-group;
            }

            .mobile-card-view {
                display: none !important;
            }

            .table-wrapper {
                display: block !important;
            }

            .footer {
                page-break-inside: avoid;
            }

            .no-print {
                display: none;
            }
        }

        /* Browser Compatibility */
        @supports not (display: grid) {
            .mobile-card-amounts {
                display: block;
            }

            .mobile-card-amount {
                margin-bottom: 8px;
            }
        }

        /* High Contrast Mode Support */
        @media (prefers-contrast: high) {
            .header {
                background: #475569;
            }

            table thead {
                background: #475569;
            }

            .balance-positive {
                color: #006400;
            }

            .balance-negative {
                color: #8b0000;
            }
        }

        /* Reduced Motion Support */
        @media (prefers-reduced-motion: reduce) {

            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }

            .header::before {
                animation: none;
            }

            table tbody tr:hover {
                transform: none;
            }
        }
    </style>
</head>

<body>
    <div class="container">
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
                            •
                        @endif
                        @if ($companyEmail)
                            {{ $companyEmail }}
                        @endif
                    </div>
                @endif
            </div>
            <div class="statement-title">Customer Statement</div>
            <div class="statement-period">
                <strong>Statement Period</strong>
                <span>
                    @if ($fromDate && $toDate)
                        {{ date('d M Y', strtotime($fromDate)) }} - {{ date('d M Y', strtotime($toDate)) }}
                    @elseif($fromDate)
                        From: {{ date('d M Y', strtotime($fromDate)) }}
                    @elseif($toDate)
                        Until: {{ date('d M Y', strtotime($toDate)) }}
                    @else
                        All Time
                    @endif
                </span>
            </div>
        </div>

        <div class="content-section">
            <div class="customer-info">
                <div class="customer-details">
                    <h3>{{ $customer->name }}</h3>
                    @if ($customer->mobile)
                        <p><strong>Mobile:</strong> <span>{{ $customer->mobile }}</span></p>
                    @endif
                    @if ($customer->email)
                        <p><strong>Email:</strong> <span>{{ $customer->email }}</span></p>
                    @endif
                    @if ($customer->place)
                        <p><strong>Address:</strong> <span>{{ $customer->place }}</span></p>
                    @endif
                    @if ($customer->customerType)
                        <p><strong>Customer Type:</strong> <span>{{ $customer->customerType->name }}</span></p>
                    @endif
                </div>
            </div>

            @if ($ledgerEntries->count() > 0)
                <!-- Desktop Table View -->
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Reference</th>
                                <th class="text-right">Debit</th>
                                <th class="text-right">Credit</th>
                                <th class="text-right">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($ledgerEntries as $entry)
                                @php
                                    $rowClass = 'ledger-row ';
                                    $isPayment = $entry->type === 'payment';
                                    $isSaleReturn = $entry->type === 'sale_return' || $entry->type === 'return_payment';

                                    if ($entry->type === 'opening_balance') {
                                        $rowClass .= 'opening-balance-row';
                                    } elseif ($isSaleReturn) {
                                        $rowClass .= 'sale-return-row';
                                    } elseif ($isPayment) {
                                        $rowClass .= 'payment-row';
                                    } elseif ($entry->debit > 0) {
                                        $rowClass .= 'debit-row';
                                    } else {
                                        $rowClass .= 'credit-row';
                                    }
                                @endphp
                                <tr class="{{ $rowClass }}">
                                    <td>{{ $entry->date === 'Opening' ? 'Opening' : date('d M Y', strtotime($entry->date)) }}</td>
                                    <td class="reference-no">{{ $entry->reference }}</td>
                                    <td class="text-right">
                                        @if ($entry->debit > 0)
                                            <span style="color: {{ $isSaleReturn ? '#dc2626' : ($isPayment ? '#059669' : '#475569') }}; font-weight: 500;">{{ currency($entry->debit) }}</span>
                                        @else
                                            <span style="color: #cbd5e0;">-</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        @if ($entry->credit > 0)
                                            <span style="color: {{ $isSaleReturn ? '#dc2626' : ($isPayment ? '#059669' : '#64748b') }}; font-weight: 500;">{{ currency($entry->credit) }}</span>
                                        @else
                                            <span style="color: #cbd5e0;">-</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <span style="color: {{ $isSaleReturn ? '#dc2626' : ($isPayment ? '#059669' : ($entry->balance >= 0 ? '#475569' : '#64748b')) }}; font-weight: 600;">
                                            {{ currency($entry->balance) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="closing-balance-row">
                                <td colspan="2" class="text-right"><strong>Closing Balance</strong></td>
                                <td class="text-right"><strong>{{ currency($totalDebit) }}</strong></td>
                                <td class="text-right"><strong>{{ currency($totalCredit) }}</strong></td>
                                <td class="text-right">
                                    <strong>
                                        {{ currency($closingBalance) }}
                                    </strong>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Card View -->
                <div class="mobile-card-view">
                    @foreach ($ledgerEntries as $entry)
                        @php
                            $isPayment = $entry->type === 'payment';
                            $isSaleReturn = $entry->type === 'sale_return' || $entry->type === 'return_payment';
                            $borderColor = $entry->type === 'opening_balance' ? '#94a3b8' : ($isSaleReturn ? '#ef4444' : ($isPayment ? '#10b981' : ($entry->debit > 0 ? '#94a3b8' : '#cbd5e1')));
                            $textColor = $isSaleReturn ? '#dc2626' : ($isPayment ? '#059669' : '#475569');
                            $creditColor = $isSaleReturn ? '#dc2626' : ($isPayment ? '#059669' : '#64748b');
                            $bgStyle = $isSaleReturn
                                ? 'background: linear-gradient(90deg, rgba(239, 68, 68, 0.12) 0%, #fef2f2 100%);'
                                : ($isPayment
                                    ? 'background: linear-gradient(90deg, rgba(16, 185, 129, 0.12) 0%, #f0fdf4 100%);'
                                    : '');
                        @endphp
                        <div class="mobile-card" style="border-left-color: {{ $borderColor }}; {{ $bgStyle }}">
                            <div class="mobile-card-header">
                                <span class="mobile-card-date"
                                    style="color: {{ $isSaleReturn ? '#dc2626' : ($isPayment ? '#059669' : 'inherit') }};">{{ $entry->date === 'Opening' ? 'Opening' : date('d M Y', strtotime($entry->date)) }}</span>
                                <span class="mobile-card-reference" style="color: {{ $isSaleReturn ? '#dc2626' : ($isPayment ? '#059669' : 'inherit') }};">{{ $entry->reference }}</span>
                            </div>
                            <div class="mobile-card-body">
                                <div class="mobile-card-description" style="color: {{ $isSaleReturn ? '#dc2626' : ($isPayment ? '#059669' : 'inherit') }};">
                                    {{ $entry->description ?? 'Opening Balance' }}</div>
                                <div class="mobile-card-amounts">
                                    <div class="mobile-card-amount">
                                        <div class="mobile-card-amount-label">Debit</div>
                                        <div class="mobile-card-amount-value" style="color: {{ $entry->debit > 0 ? $textColor : '#cbd5e0' }};">
                                            {{ $entry->debit > 0 ? currency($entry->debit) : '-' }}
                                        </div>
                                    </div>
                                    <div class="mobile-card-amount">
                                        <div class="mobile-card-amount-label">Credit</div>
                                        <div class="mobile-card-amount-value" style="color: {{ $entry->credit > 0 ? $creditColor : '#cbd5e0' }};">
                                            {{ $entry->credit > 0 ? currency($entry->credit) : '-' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mobile-card-balance">
                                <div class="mobile-card-balance-label">Balance</div>
                                <div class="mobile-card-balance-value" style="color: {{ $isSaleReturn ? '#dc2626' : ($isPayment ? '#059669' : ($entry->balance >= 0 ? '#475569' : '#64748b')) }};">
                                    {{ currency($entry->balance) }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                    <div class="mobile-card" style="border-left-color: #94a3b8; background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%); color: #1e293b;">
                        <div class="mobile-card-header" style="border-bottom-color: rgba(0,0,0,0.1);">
                            <span class="mobile-card-date" style="color: #1e293b; font-size: 16px;"><strong>Closing Balance</strong></span>
                        </div>
                        <div class="mobile-card-body">
                            <div class="mobile-card-amounts">
                                <div class="mobile-card-amount" style="background: rgba(241, 245, 249, 0.8);">
                                    <div class="mobile-card-amount-label" style="color: #64748b;">Total Debit</div>
                                    <div class="mobile-card-amount-value" style="color: #475569; font-size: 16px;">{{ currency($totalDebit) }}</div>
                                </div>
                                <div class="mobile-card-amount" style="background: rgba(241, 245, 249, 0.8);">
                                    <div class="mobile-card-amount-label" style="color: #64748b;">Total Credit</div>
                                    <div class="mobile-card-amount-value" style="color: #475569; font-size: 16px;">{{ currency($totalCredit) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="mobile-card-balance" style="background: rgba(241, 245, 249, 0.9);">
                            <div class="mobile-card-balance-label" style="color: #64748b;">Closing Balance</div>
                            <div class="mobile-card-balance-value" style="color: #1e293b; font-size: 20px;">
                                {{ currency(abs($closingBalance)) }}
                                <span class="badge" style="background: rgba(148, 163, 184, 0.2); color: #475569; margin-left: 8px;">
                                    {{ $closingBalance >= 0 ? 'Dr' : 'Cr' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="no-data">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p>No transactions found for this customer in the selected period.</p>
                </div>
            @endif
        </div>

        <div class="footer">
            <p>This is a computer-generated statement. No signature is required.</p>
            <p>Generated on {{ now()->format('d M Y, h:i A') }}</p>
        </div>
    </div>
</body>

</html>

@php
    use Carbon\Carbon;
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $pageTitle ?? 'Payment Receipt' }}</title>
    <style type="text/css">
        /* 80mm Thermal Printer Correct Ratio */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @page {
            size: 80mm auto;
            width: 80mm;
            height: auto;
            margin: 0;
        }

        html,
        body {
            width: 80mm;
            height: auto;
            margin: 0;
            padding: 0;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.3;
            color: #000;
            background: #fff;
        }

        .receipt-container {
            width: 80mm;
            max-width: 80mm;
            min-width: 80mm;
            height: auto;
            padding: 3mm 4mm;
            margin: 0;
            box-sizing: border-box;
        }

        /* Headers */
        h1 {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            margin: 0 0 3mm 0;
            text-transform: uppercase;
        }

        h2 {
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            margin: 0 0 2mm 0;
            text-transform: uppercase;
        }

        p {
            margin: 1mm 0;
            font-size: 12px;
        }

        /* Layout */
        .header-info {
            text-align: center;
            margin-bottom: 4mm;
        }

        .header-info p {
            font-size: 11px;
            margin: 1mm 0;
        }

        .divider {
            border: none;
            border-bottom: 1px dashed #000;
            margin: 3mm 0;
            height: 0;
            width: 100%;
        }

        .customer-info {
            margin-bottom: 4mm;
        }

        .customer-info p {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            margin: 1.5mm 0;
        }

        .customer-info strong {
            font-weight: bold;
            flex: 0 0 50%;
        }

        /* Table */
        .receipt-table {
            width: 100%;
            border-collapse: collapse;
            margin: 3mm 0;
            font-size: 10px;
        }

        .receipt-table th {
            font-weight: bold;
            padding: 2mm 1mm;
            border-bottom: 1px solid #000;
            text-align: left;
            font-size: 11px;
        }

        .receipt-table td {
            padding: 1mm;
            border: none;
            text-align: left;
            font-size: 10px;
            vertical-align: top;
        }

        .receipt-table .text-right {
            text-align: right;
        }

        /* Totals */
        .totals-section {
            margin-top: 4mm;
            border-top: 1px solid #000;
            padding-top: 3mm;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin: 1.5mm 0;
            font-size: 11px;
        }

        .total-row.grand-total {
            font-weight: bold;
            font-size: 13px;
            border-top: 1px solid #000;
            padding-top: 3mm;
            margin-top: 3mm;
        }

        /* Footer */
        .footer {
            text-align: center;
            margin-top: 5mm;
            padding-top: 3mm;
            border-top: 1px dashed #000;
        }

        .footer p {
            font-size: 10px;
            margin: 1.5mm 0;
        }

        /* Utility Classes */
        .text-left {
            text-align: left !important;
        }

        .text-right {
            text-align: right !important;
        }

        .text-center {
            text-align: center !important;
        }

        .text-uppercase {
            text-transform: uppercase;
        }

        /* Thermal Printer Print Styles */
        @media print {
            @page {
                size: 80mm auto !important;
                width: 80mm !important;
                height: auto !important;
                margin: 0mm !important;
            }

            html,
            body {
                width: 80mm !important;
                height: auto !important;
                margin: 0 !important;
                padding: 0 !important;
                font-family: 'Courier New', monospace !important;
                font-size: 11px !important;
                overflow: visible !important;
            }

            .receipt-container {
                width: 80mm !important;
                max-width: 80mm !important;
                min-width: 80mm !important;
                height: auto !important;
                padding: 2mm 3mm !important;
                margin: 0 !important;
                box-sizing: border-box !important;
            }

            * {
                color: #000 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>

<body>
    <div class="receipt-container">
        <!-- Header -->
        <div class="header-info">
            <h1>{{ $companyName }}</h1>
            @if ($companyAddress)
                <p>{{ $companyAddress }}</p>
            @endif
            @if ($companyPhone)
                <p>Phone: {{ $companyPhone }}</p>
            @endif
            @if ($companyEmail)
                <p>Email: {{ $companyEmail }}</p>
            @endif
            @if ($gstNo)
                <p>GST No: {{ $gstNo }}</p>
            @endif
        </div>

        <div class="divider"></div>

        <!-- Receipt Title -->
        <h2>{{ $receiptTitle ?? 'PAYMENT RECEIPT' }}</h2>

        <div class="divider"></div>

        <!-- Customer Information -->
        <div class="customer-info">
            <p>
                <strong>Customer:</strong>
                <span>{{ $customerName }}</span>
            </p>
            <p>
                <strong>Date:</strong>
                <span>{{ Carbon::parse($paymentDate)->format('d/m/Y') }}</span>
            </p>
            <p>
                <strong>Time:</strong>
                <span>{{ Carbon::now()->format('H:i') }}</span>
            </p>
            <p>
                <strong>Payment Method:</strong>
                <span>{{ $paymentMethodName }}</span>
            </p>
        </div>

        <div class="divider"></div>

        <!-- Details Table -->
        <table class="receipt-table">
            <thead>
                <tr>
                    <th>{{ $referenceColumnLabel ?? 'Invoice No' }}</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($receiptData as $item)
                    <tr>
                        <td>{{ ($item[$referenceKey] ?? '') ?: ($item['id'] ?? 'N/A') }}</td>
                        <td class="text-right">{{ currency($item['amount'] ?? 0) }}</td>
                    </tr>
                    @if (isset($item['discount']) && $item['discount'] > 0)
                        <tr>
                            <td style="padding-left: 5mm;">Discount</td>
                            <td class="text-right">-{{ currency($item['discount']) }}</td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>

        <!-- Totals Section -->
        <div class="totals-section">
            @if ($totalDiscount > 0)
                <div class="total-row">
                    <span>Total Discount:</span>
                    <span>-{{ currency($totalDiscount) }}</span>
                </div>
            @endif
            <div class="total-row grand-total">
                <span><strong>TOTAL AMOUNT</strong></span>
                <span><strong>{{ currency($totalAmount) }}</strong></span>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p style="font-weight: bold;">{{ Carbon::now()->format('d/m/Y H:i') }}</p>
            <p>{{ $footerMessage ?? 'THANK YOU FOR YOUR PAYMENT' }}</p>
            <p style="margin-top: 3mm;">This is a computer generated receipt</p>
        </div>
    </div>

    <script>
        // 80mm Thermal Printer Exact Ratio Fix
        document.addEventListener('DOMContentLoaded', function() {
            // Force exact 80mm width and auto height
            document.documentElement.style.width = '80mm';
            document.documentElement.style.height = 'auto';
            document.body.style.width = '80mm';
            document.body.style.height = 'auto';
            document.body.style.margin = '0';
            document.body.style.padding = '0';

            // Set viewport for thermal printing
            const viewport = document.querySelector('meta[name="viewport"]');
            if (viewport) {
                viewport.setAttribute('content', 'width=80mm, initial-scale=1, user-scalable=no');
            }

            // Auto-print with correct dimensions
            setTimeout(function() {
                window.print();
            }, 500);
        });
    </script>
</body>

</html>

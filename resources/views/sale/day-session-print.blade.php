@php
    use Carbon\Carbon;
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        Sale Bill Report
    </title>
    <style type="text/css">
        /* 80mm Thermal Printer Correct Ratio */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @page {
            size: 80mm auto;
            margin: 0;
        }

        html,
        body {
            width: 80mm;
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
            padding: 3mm 4mm;
            margin: 0;
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

        .session-info {
            margin-bottom: 4mm;
        }

        .session-info p {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            margin: 1.5mm 0;
        }

        .session-info strong {
            font-weight: bold;
            flex: 0 0 50%;
        }

        /* Table */
        .sales-table {
            width: 100%;
            border-collapse: collapse;
            margin: 3mm 0;
            font-size: 10px;
        }

        .sales-table th {
            font-weight: bold;
            padding: 2mm 1mm;
            border-bottom: 1px solid #000;
            text-align: left;
            font-size: 11px;
        }

        .sales-table td {
            padding: 1mm;
            border: none;
            text-align: left;
            font-size: 10px;
            vertical-align: top;
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
                size: 80mm auto;
                margin: 0mm;
            }

            @media print {
                @page {
                    margin: 0 auto;
                    width: 80mm;
                    height: 100% !important;
                    sheet-size: 80mm 80mm;
                }

                .text-center {
                    text-align: center;
                }
            }

            html,
            body {
                width: 80mm !important;
                margin: 0 !important;
                padding: 0 !important;
                font-size: 11px !important;
                color: #000 !important;
                background: #fff !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }

            .receipt-container {
                width: 80mm !important;
                padding: 2mm 3mm !important;
                margin: 0 !important;
                max-width: none !important;
            }

            h1 {
                font-size: 14px !important;
            }

            h2 {
                font-size: 12px !important;
            }

            p {
                font-size: 10px !important;
            }

            .header-info p {
                font-size: 9px !important;
            }

            .session-info p {
                font-size: 9px !important;
            }

            .sales-table {
                font-size: 8px !important;
                width: 100% !important;
            }

            .sales-table th {
                font-size: 9px !important;
                padding: 1mm 0.5mm !important;
            }

            .sales-table td {
                font-size: 8px !important;
                padding: 0.5mm !important;
            }

            .total-row {
                font-size: 9px !important;
            }

            .total-row.grand-total {
                font-size: 11px !important;
            }

            .footer p {
                font-size: 8px !important;
            }

            /* Force visibility and proper colors */
            * {
                color: #000 !important;
                background: transparent !important;
                text-shadow: none !important;
                box-shadow: none !important;
                border-color: #000 !important;
            }

            /* Ensure borders print correctly */
            .divider {
                border-bottom: 1px dashed #000 !important;
                page-break-inside: avoid !important;
            }

            .sales-table th {
                border-bottom: 1px solid #000 !important;
            }

            .totals-section {
                border-top: 1px solid #000 !important;
                page-break-inside: avoid !important;
            }

            .total-row.grand-total {
                border-top: 1px solid #000 !important;
            }

            .footer {
                border-top: 1px dashed #000 !important;
                page-break-inside: avoid !important;
            }
        }
    </style>
</head>

<body>
    <div class="receipt-container">
        <!-- Thermal Header -->
        <div class="header-info">
            <h1>{{ strtoupper($session->branch->name ?? 'STORE') }}</h1>
            <h2>SALE BILL REPORT</h2>
            <b>{{ Carbon::parse($session->created_at)->format('d-m-Y') }} TO {{ Carbon::parse($session->updated_at ?? $session->created_at)->format('d-m-Y') }}</b>
        </div>

        <div class="divider"></div>

        <!-- Session Info - Compact -->
        <div class="session-info">
            <p><strong>SESSION:</strong> <b>#{{ $session->id }}</b></p>
            <p><strong>BRANCH:</strong> <b>{{ strtoupper($session->branch->name ?? 'N/A') }}</b></p>
            <p><strong>STATUS:</strong> <b>{{ strtoupper($session->status) }}</b></p>
            <p><strong>OPENED:</strong> <b>{{ $session->opener->name ?? 'N/A' }}</b></p>
            @if ($session->closer)
                <p><strong>CLOSED:</strong> <b>{{ $session->closer->name }}</b></p>
            @endif
            <p><strong>TIME:</strong> <b>{{ Carbon::parse($session->created_at)->format('d/m H:i') }}</b></p>
        </div>

        <div class="divider"></div>

        <!-- Sales Table - Thermal Optimized -->
        <table class="sales-table">
            <thead>
                <tr>
                    <th style="width: 15%;">Date</th>
                    <th style="width: 35%;">Invoice</th>
                    <th style="width: 20%;">Payment</th>
                    <th style="width: 30%;" class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sales as $sale)
                    <tr>
                        <td><b>{{ Carbon::parse($sale->created_at)->format('d/m') }}</b></td>
                        <td><b>{{ $sale->invoice_no ?? 'N/A' }}</b></td>
                        <td>
                            <b>{{ $sale->payment_method_name }}</b>
                        </td>
                        <td class="text-right"><b>{{ currency($sale->grand_total) }}</b></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align: center; font-style: italic; font-size: 7px;">No sales found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="divider"></div>

        <!-- Totals Section - Thermal Optimized -->
        <div class="totals-section">
            @foreach ($totals as $method => $total)
                <div class="total-row">
                    <b>{{ strtoupper($method) }}</b>
                    <b>{{ currency($total) }}</b>
                </div>
            @endforeach

            <div class="total-row grand-total">
                <span>TOTAL</span>
                <span>{{ currency($sales->sum('grand_total')) }}</span>
            </div>
        </div>
        <div class="footer">
            <div class="divider"></div>
            <p style="font-weight: bold;">{{ Carbon::now()->format('d/m/Y H:i') }}</p>
            <p>THANK YOU</p>
        </div>
    </div>

    <script>
        // 80mm Thermal Printer Exact Ratio Fix
        document.addEventListener('DOMContentLoaded', function() {
            // Force exact 80mm width
            document.documentElement.style.width = '80mm';
            document.body.style.width = '80mm';
            document.body.style.margin = '0';
            document.body.style.padding = '0';

            // Set viewport for thermal printing
            const viewport = document.querySelector('meta[name="viewport"]');
            if (viewport) {
                viewport.setAttribute('content', 'width=80mm, initial-scale=1, user-scalable=no');
            }

            // Auto-print with correct dimensions
            setTimeout(function() {
                // Inject final thermal print styles
                const finalStyle = document.createElement('style');
                finalStyle.innerHTML = `
                    @media print {
                        @page {
                            size: 80mm auto !important;
                            margin: 0mm !important;
                        }
                        html, body {
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
                            padding: 2mm 3mm !important;
                            margin: 0 !important;
                            box-sizing: border-box !important;
                        }
                        * {
                            color: #000 !important;
                            background: transparent !important;
                            -webkit-print-color-adjust: exact !important;
                            print-color-adjust: exact !important;
                        }
                    }
                `;
                document.head.appendChild(finalStyle);

                // Print with exact thermal specifications
                window.print();
            }, 800);
        });

        // Handle print events with exact width
        window.addEventListener('beforeprint', function() {
            document.documentElement.style.width = '80mm';
            document.body.style.width = '80mm';
            document.body.style.margin = '0';
            document.body.style.padding = '0';
            document.body.style.fontSize = '11px';
            document.body.style.fontFamily = 'Courier New, monospace';

            // Force container width
            const container = document.querySelector('.receipt-container');
            if (container) {
                container.style.width = '80mm';
                container.style.maxWidth = '80mm';
                container.style.minWidth = '80mm';
                container.style.padding = '2mm 3mm';
                container.style.margin = '0';
                container.style.boxSizing = 'border-box';
            }
        });

        // Clean up after print
        window.addEventListener('afterprint', function() {
            setTimeout(function() {
                window.close();
            }, 6000);
        });
    </script>
</body>

</html>

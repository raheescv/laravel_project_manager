<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Package Payment Receipt - #{{ $payment->id }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', 'Helvetica', sans-serif;
            line-height: 1.3;
            color: #333;
            background: #fff;
            padding: 10px;
            font-size: 11px;
        }

        @media print {
            body {
                padding: 5px;
            }

            .no-print {
                display: none;
            }

            @page {
                margin: 0.3cm;
                size: A4;
            }
        }

        .receipt-container {
            max-width: 100%;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #2c3e50;
            border-radius: 4px;
            padding: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .receipt-header {
            text-align: center;
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }

        .company-logo {
            max-width: 120px;
            max-height: 60px;
            margin: 0 auto 6px;
            display: block;
        }

        .company-name {
            font-size: 16px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .company-details {
            font-size: 9px;
            color: #666;
            line-height: 1.4;
            margin: 2px 0;
        }

        .receipt-title {
            font-size: 14px;
            font-weight: bold;
            color: #2c3e50;
            margin: 6px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .receipt-number {
            font-size: 11px;
            color: #2c3e50;
            font-weight: bold;
            margin-top: 4px;
        }

        .receipt-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-bottom: 10px;
            padding: 8px;
            background: #f8f9fa;
            border-radius: 3px;
            font-size: 10px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-weight: 600;
            color: #666;
            font-size: 9px;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        .info-value {
            color: #2c3e50;
            font-weight: bold;
            font-size: 10px;
        }

        .payment-details {
            margin: 10px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 3px;
            border-left: 3px solid #2c3e50;
        }

        .payment-details-title {
            font-size: 11px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            border-bottom: 1px dotted #ccc;
            font-size: 10px;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            color: #666;
            font-weight: 500;
        }

        .detail-value {
            color: #2c3e50;
            font-weight: bold;
        }

        .amount-section {
            margin-top: 15px;
            padding: 12px;
            background: #2c3e50;
            color: #fff;
            border-radius: 3px;
            text-align: center;
        }

        .amount-label {
            font-size: 9px;
            text-transform: uppercase;
            margin-bottom: 4px;
            opacity: 0.9;
        }

        .amount-value {
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .receipt-footer {
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 9px;
            color: #666;
        }

        .print-button {
            text-align: center;
            margin: 20px 0;
        }

        .btn-print {
            background: #27ae60;
            color: #fff;
            border: none;
            padding: 12px 30px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 5px;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: background 0.3s;
        }

        .btn-print:hover {
            background: #229954;
        }

        @media print {
            .receipt-container {
                padding: 8px;
                border: 1px solid #000;
            }

            .company-logo {
                max-width: 100px;
                max-height: 50px;
            }
        }
    </style>
</head>

<body>
    <div class="receipt-container">
        <!-- Header -->
        <div class="receipt-header">
            @if ($enableLogoInPrint == 'yes' && $companyLogo)
                <img src="{{ $companyLogo }}" alt="Company Logo" class="company-logo">
            @endif
            <div class="company-name">{{ $companyName }}</div>
            @if ($companyAddress)
                <div class="company-details">{{ $companyAddress }}</div>
            @endif
            @if ($companyPhone || $companyEmail)
                <div class="company-details">
                    @if ($companyPhone)
                        <span>Phone: {{ $companyPhone }}</span>
                    @endif
                    @if ($companyPhone && $companyEmail)
                        <span> | </span>
                    @endif
                    @if ($companyEmail)
                        <span>Email: {{ $companyEmail }}</span>
                    @endif
                </div>
            @endif
            <div class="receipt-title">Package Payment Receipt</div>
            <div class="receipt-number">Receipt No: #{{ $payment->id }}</div>
        </div>

        <!-- Receipt Information -->
        <div class="receipt-info">
            <div class="info-item">
                <span class="info-label">Date</span>
                <span class="info-value">{{ systemDate($payment->date) }}</span>
            </div>
            @if ($payment->package && $payment->package->account)
                <div class="info-item">
                    <span class="info-label">Customer</span>
                    <span class="info-value">{{ $payment->package->account->name }}</span>
                </div>
            @endif
            @if ($payment->package)
                <div class="info-item">
                    <span class="info-label">Package ID</span>
                    <span class="info-value">#{{ $payment->package->id }}</span>
                </div>
            @endif
            @if ($payment->paymentMethod)
                <div class="info-item">
                    <span class="info-label">Payment Method</span>
                    <span class="info-value">{{ $payment->paymentMethod->name }}</span>
                </div>
            @endif
        </div>

        <!-- Payment Details -->
        <div class="payment-details">
            <div class="payment-details-title">Package Details</div>
            @if ($payment->package)
                @if ($payment->package->packageCategory)
                    <div class="detail-row">
                        <span class="detail-label">Package Category:</span>
                        <span class="detail-value">{{ $payment->package->packageCategory->name }}</span>
                    </div>
                @endif
                <div class="detail-row">
                    <span class="detail-label">Package Amount:</span>
                    <span class="detail-value">{{ currency($payment->package->amount) }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Total Paid:</span>
                    <span class="detail-value">{{ currency($payment->package->paid) }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Balance:</span>
                    <span class="detail-value">{{ currency($payment->package->balance) }}</span>
                </div>
                @if ($payment->package->start_date)
                    <div class="detail-row">
                        <span class="detail-label">Start Date:</span>
                        <span class="detail-value">{{ systemDate($payment->package->start_date) }}</span>
                    </div>
                @endif
                @if ($payment->package->end_date)
                    <div class="detail-row">
                        <span class="detail-label">End Date:</span>
                        <span class="detail-value">{{ systemDate($payment->package->end_date) }}</span>
                    </div>
                @endif
            @endif
        </div>

        <!-- Amount Section -->
        <div class="amount-section">
            <div class="amount-label">Payment Amount</div>
            <div class="amount-value">{{ currency($payment->amount) }}</div>
        </div>

        <!-- Footer -->
        <div class="receipt-footer">
            <p>Thank you for your payment!</p>
            <p>Generated on {{ now()->format('d M Y, h:i A') }}</p>
        </div>
    </div>

    <div class="print-button no-print">
        <button class="btn-print" onclick="window.print()">Print Receipt</button>
    </div>
</body>

</html>



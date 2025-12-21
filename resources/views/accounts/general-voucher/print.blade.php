<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>General Voucher - {{ $journal->reference_number ?? '#' . $journal->id }}</title>
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

        .voucher-container {
            max-width: 100%;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #2c3e50;
            border-radius: 4px;
            padding: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .voucher-header {
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

        .voucher-title {
            font-size: 14px;
            font-weight: bold;
            color: #2c3e50;
            margin: 6px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .voucher-info {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
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
            font-size: 8px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 2px;
            font-weight: 600;
        }

        .info-value {
            font-size: 10px;
            color: #2c3e50;
            font-weight: bold;
        }

        .entries-table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
            background: #fff;
            font-size: 9px;
        }

        .entries-table thead {
            background: #2c3e50;
            color: #fff;
        }

        .entries-table th {
            padding: 4px 6px;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border: 1px solid #1a252f;
        }

        .entries-table th.text-right {
            text-align: right;
        }

        .entries-table th.text-center {
            text-align: center;
        }

        .entries-table td {
            padding: 4px 6px;
            border: 1px solid #ddd;
            font-size: 9px;
        }

        .entries-table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }

        .entries-table tbody tr:hover {
            background: #e9ecef;
        }

        .entries-table .text-right {
            text-align: right;
        }

        .entries-table .text-center {
            text-align: center;
        }

        .amount {
            font-weight: bold;
            color: #2c3e50;
        }

        .debit-amount {
            color: #e74c3c;
        }

        .credit-amount {
            color: #27ae60;
        }

        .totals-row {
            background: #2c3e50 !important;
            color: #fff !important;
            font-weight: bold;
        }

        .totals-row td {
            border-color: #1a252f !important;
            font-size: 10px;
        }

        .voucher-footer {
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px solid #2c3e50;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .signature-section {
            text-align: center;
        }

        .signature-line {
            border-top: 1px solid #2c3e50;
            margin-top: 20px;
            padding-top: 3px;
            font-size: 9px;
            color: #666;
        }

        .remarks-section {
            margin-top: 8px;
            padding: 6px;
            background: #f8f9fa;
            border-radius: 3px;
            border-left: 3px solid #2c3e50;
        }

        .remarks-label {
            font-size: 8px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 3px;
            font-weight: 600;
        }

        .remarks-text {
            font-size: 9px;
            color: #333;
            line-height: 1.4;
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

        .voucher-number {
            font-size: 11px;
            color: #2c3e50;
            font-weight: bold;
            margin-top: 4px;
        }

        @media print {
            .voucher-container {
                padding: 8px;
                border: 1px solid #000;
            }

            .company-logo {
                max-width: 100px;
                max-height: 50px;
            }

            .entries-table {
                page-break-inside: avoid;
            }

            .voucher-footer {
                page-break-inside: avoid;
            }
        }
    </style>
</head>

<body>
    <div class="voucher-container">
        <!-- Header -->
        <div class="voucher-header">
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
            <div class="voucher-title">General Voucher</div>
            <div class="voucher-number">
                @if ($journal->reference_number)
                    Voucher No: {{ $journal->reference_number }}
                @else
                    Voucher No: #{{ $journal->id }}
                @endif
            </div>
        </div>

        <!-- Voucher Information -->
        <div class="voucher-info">
            <div class="info-item">
                <span class="info-label">Date</span>
                <span class="info-value">{{ \Carbon\Carbon::parse($journal->date)->format('d M Y') }}</span>
            </div>
            @if ($journal->person_name)
                <div class="info-item">
                    <span class="info-label">Person Name</span>
                    <span class="info-value">{{ $journal->person_name }}</span>
                </div>
            @endif
            @if ($journal->reference_number)
                <div class="info-item">
                    <span class="info-label">Reference Number</span>
                    <span class="info-value">{{ $journal->reference_number }}</span>
                </div>
            @endif
        </div>

        <!-- Journal Entries Table -->
        <table class="entries-table">
            <thead>
                <tr>
                    <th style="width: 30px;" class="text-center">#</th>
                    <th style="width: 25%;">Account</th>
                    <th class="text-right" style="width: 15%;">Debit</th>
                    <th class="text-right" style="width: 15%;">Credit</th>
                    <th style="width: 30%;">Description</th>
                    @if ($journal->entries->where('person_name', '!=', null)->count() > 0)
                        <th>Name</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @php
                    $totalDebit = 0;
                    $totalCredit = 0;
                @endphp
                @foreach ($journal->entries as $index => $entry)
                    @php
                        $totalDebit += $entry->debit;
                        $totalCredit += $entry->credit;
                    @endphp
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>
                            <strong>{{ $entry->account->name ? ucfirst($entry->account->name) : 'N/A' }}</strong>
                        </td>
                        <td class="text-right amount {{ $entry->debit > 0 ? 'debit-amount' : '' }}">
                            @if ($entry->debit > 0)
                                {{ currency($entry->debit) }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-right amount {{ $entry->credit > 0 ? 'credit-amount' : '' }}">
                            @if ($entry->credit > 0)
                                {{ currency($entry->credit) }}
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $entry->remarks ?? ($entry->description ?? '-') }}</td>
                        @if ($journal->entries->where('person_name', '!=', null)->count() > 0)
                            <td>{{ $entry->person_name ?? '-' }}</td>
                        @endif
                    </tr>
                @endforeach
                <!-- Totals Row -->
                <tr class="totals-row">
                    <td colspan="2" class="text-right"><strong>TOTAL</strong></td>
                    <td class="text-right"><strong>{{ currency($totalDebit) }}</strong></td>
                    <td class="text-right"><strong>{{ currency($totalCredit) }}</strong></td>
                    <td colspan="{{ $journal->entries->where('person_name', '!=', null)->count() > 0 ? '2' : '1' }}"></td>
                </tr>
            </tbody>
        </table>

        <!-- Remarks Section -->
        @if ($journal->remarks)
            <div class="remarks-section">
                <div class="remarks-label">Remarks</div>
                <div class="remarks-text">{{ $journal->remarks }}</div>
            </div>
        @endif

        <!-- Footer with Signatures -->
        <div class="voucher-footer">
            <div class="signature-section">
                <div class="signature-line">
                    Prepared By<br>
                    {{ $journal->createdBy->name ?? 'N/A' }}
                </div>
            </div>
            <div class="signature-section">
                <div class="signature-line">
                    Authorized By<br>
                    ________________
                </div>
            </div>
        </div>

        <!-- Print Date -->
        <div style="text-align: center; margin-top: 8px; font-size: 8px; color: #666;">
            Printed on: {{ \Carbon\Carbon::now()->format('d M Y, h:i A') }}
        </div>
    </div>

    <script>
        // Auto print when page loads (only in print mode)
        window.onload = function() {
            // Uncomment the line below if you want auto-print
            window.print();
        };
    </script>
</body>

</html>

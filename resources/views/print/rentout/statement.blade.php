<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }

        .header {
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }

        .header h2 {
            font-size: 1.4em;
            color: #2c3e50;
            margin: 0 0 5px 0;
        }

        .header .contact {
            font-size: 11px;
            color: #555;
            line-height: 1.6;
        }

        .title-bar {
            background: #eee;
            text-align: center;
            padding: 10px;
            font-size: 16px;
            color: #3c3d4e;
            margin-bottom: 5px;
        }

        .info-table {
            width: 100%;
            margin: 10px 0;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 4px 8px;
            font-size: 13px;
            vertical-align: top;
        }

        .info-table b {
            color: #2c3e50;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .data-table th {
            font-size: 13px;
            background: #1b7bbc;
            color: #fff;
            padding: 6px 10px;
            border: 0;
        }

        .data-table td {
            font-size: 12px;
            padding: 5px 10px;
            color: #333;
        }

        .data-table tr:nth-child(odd) td {
            background: #fff;
        }

        .data-table tr:nth-child(even) td {
            background: #d9f0fb;
        }

        .data-table tfoot th {
            background: #1b7bbc;
            color: #fff;
            padding: 6px 10px;
            font-size: 13px;
        }

        .text-right {
            text-align: right;
        }

        .text-left {
            text-align: left;
        }

        .text-center {
            text-align: center;
        }

        .text-upper {
            text-transform: uppercase;
        }
    </style>
</head>

<body>
    @php
        $companyName = \App\Models\Configuration::where('key', 'company_name')->value('value') ?? config('app.name');
        $companyPhone = \App\Models\Configuration::where('key', 'company_phone')->value('value') ?? '';
        $companyAddress = \App\Models\Configuration::where('key', 'company_address')->value('value') ?? '';
        $companyEmail = \App\Models\Configuration::where('key', 'company_email')->value('value') ?? '';
        $totalPaid = $rentOut->paymentTerms->where('status', 'paid')->sum('total');
        $totalAmount = $rentOut->paymentTerms->sum('amount');
        $balance = $totalAmount - $totalPaid;
        $isRental = $rentOut->agreement_type?->value === 'rental';
    @endphp

    <div class="header">
        <h2>{{ $companyName }}</h2>
        <div class="contact">
            @if($companyPhone)<strong>Phone:</strong> {{ $companyPhone }}<br>@endif
            @if($companyAddress){{ $companyAddress }}<br>@endif
            @if($companyEmail){{ $companyEmail }}@endif
        </div>
    </div>

    <div class="title-bar">
        Statement for <b>{{ $rentOut->reference_no }}</b>
    </div>
    <div class="title-bar">
        <b>{{ $rentOut->customer?->name }}</b>
    </div>

    <table class="info-table">
        <tr>
            <td width="55%" valign="top">
                <table width="100%">
                    <tr>
                        <td width="35%"><b>Agreement Type</b></td>
                        <td width="65%"><b>: {{ $isRental ? 'Rental' : 'Sales' }}</b></td>
                    </tr>
                    <tr>
                        <td><b>Group</b></td>
                        <td class="text-upper"><b>: {{ $rentOut->group?->name }}</b></td>
                    </tr>
                    <tr>
                        <td><b>Building</b></td>
                        <td class="text-upper"><b>: {{ $rentOut->building?->name }}</b></td>
                    </tr>
                    <tr>
                        <td><b>Type</b></td>
                        <td class="text-upper"><b>: {{ $rentOut->type?->name }}</b></td>
                    </tr>
                    <tr>
                        <td><b>Property/Unit</b></td>
                        <td class="text-upper"><b>: {{ $rentOut->property?->number }}</b></td>
                    </tr>
                    <tr>
                        <td><b>Payout Frequency</b></td>
                        <td class="text-upper"><b>: {{ $rentOut->payment_frequency }}</b></td>
                    </tr>
                </table>
            </td>
            <td width="45%" valign="top">
                <table width="100%">
                    <tr>
                        <td width="60%">Agreement Begins</td>
                        <td width="40%" class="text-right">{{ $rentOut->start_date?->format('d-m-Y') }}</td>
                    </tr>
                    <tr>
                        <td>Agreement Ends</td>
                        <td class="text-right">{{ $rentOut->end_date?->format('d-m-Y') }}</td>
                    </tr>
                    @if($rentOut->vacate_date)
                        <tr>
                            <td>Vacate Date</td>
                            <td class="text-right">{{ $rentOut->vacate_date->format('d-m-Y') }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td>{{ $isRental ? 'Rent' : 'Unit Sale Price' }}</td>
                        <td class="text-right">{{ number_format($rentOut->rent, 2) }}</td>
                    </tr>
                    @if($isRental)
                        <tr>
                            <td>Total Amount To Pay</td>
                            <td class="text-right">{{ number_format($totalAmount, 2) }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td>Total Paid</td>
                        <td class="text-right">{{ number_format($totalPaid, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Remaining To Be Paid</td>
                        <td class="text-right">{{ number_format($balance, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th width="15%" class="text-left">Date</th>
                <th width="20%" class="text-left">Payment Mode</th>
                <th width="20%" class="text-right">Debit</th>
                <th width="20%" class="text-right">Credit</th>
                <th width="25%" class="text-left">Remarks</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payments as $payment)
                <tr>
                    <td class="text-left">{{ $payment['date'] }}</td>
                    <td class="text-left">{{ $payment['payment_mode'] }}{{ !empty($payment['cheque_no']) ? ' : ' . $payment['cheque_no'] : '' }}</td>
                    <td class="text-right">{{ number_format($payment['debit'], 2) }}</td>
                    <td class="text-right">{{ number_format($payment['credit'], 2) }}</td>
                    <td class="text-left" style="font-style: italic;">{{ $payment['remark'] ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="2" class="text-left">Total</th>
                <th class="text-right">{{ number_format($payments->sum('debit'), 2) }}</th>
                <th class="text-right">{{ number_format($payments->sum('credit'), 2) }}</th>
                <th></th>
            </tr>
        </tfoot>
    </table>
</body>

</html>

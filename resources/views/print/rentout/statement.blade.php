<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        .data-table th {
            font-size: 14px;
            page-break-after: always;
            background: #1b7bbc;
            text-align: right;
            border: 0;
            color: #fff;
            padding: 5px 10px;
        }

        .data-table td {
            font-size: 13px;
            padding: 4px 10px;
            color: #333;
        }

        .data-table tr:nth-child(odd) td {
            background: #fff;
        }

        .data-table tr:nth-child(even) td {
            background: #d9f0fb;
        }

        .header-container {
            padding: 10px 0;
            margin-bottom: 10px;
            border-bottom: 2px solid #e0e0e0;
            overflow: hidden;
        }

        .header-container::after {
            content: "";
            display: table;
            clear: both;
        }

        .company-info {
            float: left;
            width: 55%;
            padding-right: 10px;
            box-sizing: border-box;
        }

        .company-info h2 {
            font-size: 1.6em;
            font-weight: 600;
            color: #2c3e50;
            margin: 0 0 6px 0;
            letter-spacing: 0.5px;
        }

        .company-info .contact-details {
            font-size: 11px;
            color: #555;
            line-height: 1.8;
        }

        .company-info .contact-details a {
            color: #0087C3;
            text-decoration: none;
        }

        .logo-container {
            float: right;
            width: 200px;
            min-height: 80px;
            background: linear-gradient(135deg, rgba(0, 135, 195, 0.03) 0%, rgba(0, 174, 239, 0.03) 100%);
            border-radius: 12px;
            padding: 5px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            position: relative;
            overflow: hidden;
            text-align: center;
        }

        .logo-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            border: 1px solid rgba(0, 135, 195, 0.1);
            border-radius: 12px;
            pointer-events: none;
        }

        .logo-container img {
            max-width: 180px;
            max-height: 80px;
            height: auto;
            width: auto;
            position: relative;
            z-index: 1;
            display: block;
            margin: 5px auto;
        }

        #details {
            margin-bottom: 8px !important;
        }

        table {
            margin-bottom: 3px !important;
        }

        body {
            margin: 0;
            padding: 0;
        }
    </style>
</head>
@php
    $total_paid = $payments->sum('credit');
    $isRental = $rentOut->agreement_type?->value === 'rental';
@endphp

<body style="page-break-after: initial;">
    <div class="wrapper">
        <header class="header-container clearfix">
            <div class="company-info">
                <h2 class="name">{{ $companyName }}</h2>
                <div class="contact-details">
                    @if($companyPhone)<strong>Phone:</strong> {{ $companyPhone }}<br>@endif
                    @if($companyAddress){{ $companyAddress }}<br>@endif
                    @if($companyEmail)<a href="mailto:{{ $companyEmail }}">{{ $companyEmail }}</a>@endif
                </div>
            </div>
            @if($companyLogo)
                <div class="logo-container">
                    <img src="{{ $companyLogo }}" alt="Company Logo">
                </div>
            @endif
        </header>
        <table width="100%" style="page-break-after: initial;">
            <tr>
                <td style="font-family: 'Arial', sans-serif; text-align: center; background: #eee; font-size: 16px; padding: 10px 0 10px; color: #3c3d4e;">
                    Statement for <b>{{ $rentOut->reference_no }}</b>
                </td>
            </tr>
            <tr>
                <td style="font-family: 'Arial', sans-serif; text-align: center; background: #eee; font-size: 16px; padding: 10px 0 10px; color: #3c3d4e;">
                    <b>{{ $rentOut->customer?->name }}</b>
                </td>
            </tr>
            @if(isset($fromDate) && isset($toDate) && $fromDate && $toDate)
                <tr>
                    <td style="font-family: 'Arial', sans-serif; text-align: center; background: #f8f9fa; font-size: 14px; padding: 8px 0 8px; color: #495057; border-top: 1px solid #dee2e6;">
                        <b>Period: {{ systemDate($fromDate) }} to {{ systemDate($toDate) }}</b>
                    </td>
                </tr>
            @endif
            <tr style="page-break-after: auto;">
                <td style="color: #3c3c3d; padding: 0 0 20px;">
                    <div style="border-radius: 3px; background: #fdfdfd; padding: 0; border-bottom: 1px solid #eee;">
                        <table width="100%" style="margin: 10px 0;">
                            <tr>
                                <td width="55%" valign="top" style="font-family: 'Arial', sans-serif; text-align: left; font-size: 14px;">
                                    <table width="100%">
                                        <tr>
                                            <td width="35%"><b>Agreement Type</b></td>
                                            <td width="65%">
                                                <b>:
                                                    @if(!$isRental)
                                                        Sales
                                                    @else
                                                        Rental
                                                    @endif
                                                </b>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td width="35%" valign="top"><b>Group</b></td>
                                            <td width="65%" style="text-transform: uppercase;"><b>: {{ $rentOut->group?->name }}</b></td>
                                        </tr>
                                        <tr>
                                            <td width="35%" valign="top"><b>Building</b></td>
                                            <td width="65%" style="text-transform: uppercase;"><b>: {{ $rentOut->building?->name }}</b></td>
                                        </tr>
                                        <tr>
                                            <td width="35%" valign="top"><b>Type</b></td>
                                            <td width="65%" style="text-transform: uppercase;"><b>: {{ $rentOut->type?->name }}</b></td>
                                        </tr>
                                        <tr>
                                            <td width="35%" valign="top"><b>Property/Unit</b></td>
                                            <td width="65%" style="text-transform: uppercase;"><b>: {{ $rentOut->property?->number }}</b></td>
                                        </tr>
                                        <tr>
                                            <td width="35%" valign="top"><b>Payout Frequency</b></td>
                                            <td width="65%" style="text-transform: uppercase;"><b>: {{ $rentOut->payment_frequency }}</b></td>
                                        </tr>
                                    </table>
                                </td>
                                <td width="45%" valign="top" style="font-family: 'Arial', sans-serif; text-align: center; font-size: 14px;">
                                    <table width="100%">
                                        <tr>
                                            <td width="60%">Agreement Begins</td>
                                            <td width="40%" style="text-align: right;">{{ systemDate($rentOut->start_date) }}</td>
                                        </tr>
                                        <tr>
                                            <td width="60%">Agreement Ends</td>
                                            <td width="40%" style="text-align: right;">{{ systemDate($rentOut->end_date) }}</td>
                                        </tr>
                                        @if($rentOut->vacate_date)
                                            <tr>
                                                <td width="60%">Vacate Date</td>
                                                <td width="40%" style="text-align: right;">{{ systemDate($rentOut->vacate_date) }}</td>
                                            </tr>
                                        @endif
                                        <tr>
                                            @if(!$isRental)
                                                <td width="60%">Unit Sale Price</td>
                                            @else
                                                <td width="60%">Rent</td>
                                            @endif
                                            <td width="40%" style="text-align: right;">{{ currency($rentOut->rent) }}</td>
                                        </tr>
                                        @php
                                            $totalAmountToPay = $rentOut->paymentTerms->sum('amount');
                                            $balance = $totalAmountToPay - $total_paid;
                                        @endphp
                                        @if($isRental)
                                            <tr>
                                                <td width="60%">Total Amount To Pay</td>
                                                <td width="40%" style="text-align: right;">{{ currency($totalAmountToPay) }}</td>
                                            </tr>
                                        @endif
                                        <tr>
                                            <td width="60%">Total Paid</td>
                                            <td width="40%" style="text-align: right;">{{ currency($total_paid) }}</td>
                                        </tr>
                                        <tr>
                                            <td width="60%">Remaining To Be Paid</td>
                                            <td width="40%" style="text-align: right;">{{ currency($balance) }}</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>
    </div>
    <div style="border-radius: 3px; padding: 0; overflow: hidden; border: 1px solid #eee; page-break-after: auto;">
        <table class="data-table" border="0" cellpadding="0" cellspacing="0" width="100%"
            style="font-family: 'Arial', sans-serif; text-align: right; font-size: 14px; page-break-after: inherit;">
            <thead>
                <tr>
                    <th width="20%" style="text-align: left;">Date</th>
                    <th width="25%" style="text-align: left;">Payment Mode</th>
                    <th width="20%" style="text-align: right;">Debit</th>
                    <th width="20%" style="text-align: right;">Credit</th>
                    <th width="15%" style="text-align: left;">Remarks</th>
                </tr>
            </thead>
            <tbody>
                <tr></tr>
                @foreach($payments as $single)
                    <tr>
                        <td width="20%" style="text-align: left">{{ systemDate($single['date']) }}</td>
                        <td width="25%" style="text-align: left">
                            {{ $single['payment_mode'] }}
                            @if(!empty($single['cheque_no']))
                                : {{ $single['cheque_no'] }}
                            @endif
                        </td>
                        <td width="20%">{{ currency($single['debit']) }}</td>
                        <td width="20%">{{ currency($single['credit']) }}</td>
                        <td width="15%" style="text-align: left; font-style: italic;">{{ $single['remark'] ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="2">Total</th>
                    <th>{{ currency($payments->sum('debit')) }}</th>
                    <th>{{ currency($payments->sum('credit')) }}</th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
    </div>
</body>

</html>

@php
    $footerLogo = \App\Models\Configuration::where('key', 'rentout_agreement_logo_footer')->value('value');
    $footerLogoUrl = $footerLogo ? asset('storage/' . $footerLogo) : null;
    $headerLogo = \App\Models\Configuration::where('key', 'residential_lease_logo')->value('value');
    $headerLogoUrl = $headerLogo ? asset('storage/' . $headerLogo) : null;
    $agreementImagesJson = \App\Models\Configuration::where('key', 'rentout_agreement_images')->value('value');
    $agreementImages = $agreementImagesJson ? json_decode($agreementImagesJson, true) : [];
    $agreementImages = is_array($agreementImages) ? $agreementImages : [];
    $bondPaperMode = \App\Models\Configuration::where('key', 'reservation_bond_paper_mode')->value('value') === 'yes';
    $logoHeight = (int) (\App\Models\Configuration::where('key', 'reservation_logo_height')->value('value') ?: 80);
    $footerHeight = (int) (\App\Models\Configuration::where('key', 'reservation_footer_height')->value('value') ?: 50);
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: "DejaVu Sans", sans-serif;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 5px;
            font-size: 9px;
        }

        .data-table th {
            font-size: 10px;
            background: #1b7bbc;
            text-align: right;
            border: 0;
            color: #fff;
            padding: 2px 2px;
            z-index: 1;
        }

        .data-table td {
            font-size: 9px;
            border: 1px;
            padding: 2px 2px;
            color: #333;
        }

        .data-table tr:nth-child(odd) td {
            background: #fff;
        }

        .data-table tr:nth-child(even) td {
            background: #d9f0fb;
        }

        .page-break {
            page-break-after: always;
        }

        .no-page-break {
            page-break-after: auto;
        }

        .keep-together {
            page-break-inside: avoid;
        }

        footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
        }

        footer img {
            width: 100%;
        }
    </style>
</head>

<body style="page-break-after: initial;">
    <div class="wrapper">
        <header class="clearfix">
            <div id="logo" style="text-align: center;">
                <div style="position: relative;">
                    @if($bondPaperMode)
                        <div style="width: 100%; height: {{ $logoHeight }}px;"></div>
                    @elseif($headerLogoUrl)
                        <img width="100%" src="{{ $headerLogoUrl }}" alt="Logo">
                    @endif
                    <div style="position: absolute; top: 118%; left: 51%; transform: translate(-60%, -40%); text-align: center;" id='qr_code_container'>
                        <div style="margin-bottom: 1px; display: inline-block; padding: 10px; border: 1px solid #ddd; background: white;">
                            @if($rentOut->agreement_type?->value === 'lease')
                                <img src="data:image/png;base64,{{ DNS2D::getBarcodePNG(url('property/sale/booking/view/' . $rentOut->id), 'QRCODE', 5, 5) }}" alt="QR Code"
                                    style="width: 70px; height: 60px;">
                            @else
                                <img src="data:image/png;base64,{{ DNS2D::getBarcodePNG(url('property/rent/booking/view/' . $rentOut->id), 'QRCODE', 5, 5) }}" alt="QR Code"
                                    style="width: 70px; height: 60px;">
                            @endif
                            <br>
                            <small>969897909-{{ str_pad($rentOut->id, 4, '0', STR_PAD_LEFT) }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        <table width="100%" style="page-break-after: initial;">
            <tr>
                <td style="text-align: right; background: #eee; font-size: 9px; padding: 5px">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="text-align: left; direction: ltr;">
                                <b>{{ $title }}</b>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td style="text-align: right; background: #eee; font-size: 9px; padding: 5px">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="text-align: left; direction: ltr;">
                                <b>{{ $rentOut->group?->name }}</b>
                            </td>
                            <td style="text-align: right; direction: rtl;">
                                <b>{{ $rentOut->group?->arabic_name }}</b>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    {{-- FIRST PARTY - LESSOR --}}
    <div style="border-radius: 3px; padding: 0; overflow: hidden; border: 1px solid #eee; page-break-after: auto;" class="keep-together">
        <table class="data-table" border="0" cellpadding="0" cellspacing="0" width="100%" style="text-align: right; font-size: 10px;">
            <tr>
                <th style="text-align: left; direction: ltr;">
                    <b>FIRST PARTY- LESSOR/ LANDLORD</b>
                </th>
                <th style="text-align: right; direction: rtl;">
                    <b>{{ 'الطرف الأول - المؤجر/المالك' }}</b>
                </th>
            </tr>
        </table>
        <table border="0" class="data-table" cellpadding="0" cellspacing="0" width="100%" style="text-align: right; font-size: 12px;">
            <tbody>
                @foreach($lessorData as $item)
                    <tr>
                        <td align="left">{{ $item['english']['title'] }}</td>
                        <td align="left"><b>{{ $item['english']['value'] }}</b></td>
                        @if(isset($item['arabic']['title']))
                            <td align="right">
                                <b>{{ $item['arabic']['value'] ?? $item['english']['value'] }}</b>
                            </td>
                            <td align="right">{{ $item['arabic']['title'] }}</td>
                        @endif
                    </tr>
                @endforeach
                <tr>
                    <td colspan="2" align="left">Here in after referred to the first party or lessor</td>
                    <td colspan="2" align="right">{{ 'إليها فيما بعد بالطرف الأول أو المؤجر' }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- SECOND PARTY - LESSEE --}}
    <div style="border-radius: 3px; padding: 0; overflow: hidden; border: 1px solid #eee; page-break-after: auto;" class="keep-together">
        <table class="data-table" border="0" cellpadding="0" cellspacing="0" width="100%" style="text-align: right; font-size: 10px;">
            <tr>
                <th style="text-align: left; direction: ltr;">
                    <b>SECOND PARTY / LESSEE</b>
                </th>
                <th style="text-align: right; direction: rtl;">
                    <b>{{ 'الطرف الثاني / المستأجر' }}</b>
                </th>
            </tr>
        </table>
        <table border="0" class="data-table" cellpadding="0" cellspacing="0" width="100%" style="text-align: right; font-size: 12px;">
            <tbody>
                @foreach($lesseeData as $item)
                    <tr>
                        <td align="left">{{ $item['english']['title'] }}</td>
                        <td align="left"><b>{{ $item['english']['value'] }}</b></td>
                        <td align="right">
                            @if(!isset($item['english']['keep_original']))
                                <b>{{ $item['arabic']['value'] ?? '' }}</b>
                            @endif
                        </td>
                        <td align="right"><b>{{ $item['arabic']['title'] }}</b></td>
                    </tr>
                @endforeach
                <tr>
                    <td colspan="2" align="left">Hereinafter referred to the second party or lessee.</td>
                    <td colspan="2" align="right">{{ 'ويشار إليه فيما بعد بالطرف الثاني أو المستأجر' }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- PREMISES DETAILS --}}
    <div style="border-radius: 3px; padding: 0; overflow: hidden; border: 1px solid #eee; page-break-after: auto;" class="keep-together">
        <table class="data-table" border="0" cellpadding="0" cellspacing="0" width="100%" style="text-align: right; font-size: 10px;">
            <tr>
                <th style="text-align: left; direction: ltr;">
                    <b>PREMISES DETAILS</b>
                </th>
                <th style="text-align: right; direction: rtl;">
                    <b>{{ 'لمستأجر الطرف الثاني' }}</b>
                </th>
            </tr>
        </table>
        <table border="0" class="data-table" cellpadding="0" cellspacing="0" width="100%" style="text-align: right; font-size: 12px;">
            <tbody>
                @foreach($premisesDetails as $item)
                    <tr>
                        <td align="left">{{ $item['english']['title'] }}</td>
                        <td align="left"><b>{{ $item['english']['value'] }}</b></td>
                        <td align="right">
                            <b>{{ $item['arabic']['value'] ?? $item['english']['value'] }}</b>
                        </td>
                        <td align="right">{{ $item['arabic']['title'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- CONTRACT DETAILS --}}
    <div style="border-radius: 3px; padding: 0; overflow: hidden; border: 1px solid #eee; page-break-after: auto;" class="keep-together">
        <table class="data-table" border="0" cellpadding="0" cellspacing="0" width="100%" style="text-align: right; font-size: 10px;">
            <tr>
                <th style="text-align: left; direction: ltr;">
                    <b>CONTRACT DETAILS</b>
                </th>
                <th style="text-align: right; direction: rtl;">
                    <b>{{ 'تفاصيل العقد' }}</b>
                </th>
            </tr>
        </table>
        <table border="0" class="data-table" cellpadding="0" cellspacing="0" width="100%" style="text-align: right; font-size: 12px;">
            <tbody>
                @foreach($contractDetails as $item)
                    <tr>
                        <td align="left">{{ $item['english']['title'] }}</td>
                        <td align="left">
                            @if($item['english']['title'] === 'Security Deposit')
                                <b>{{ $item['english']['value1'] ?? '' }} Qatari Riyal ({{ $item['english']['value'] }})</b>
                            @else
                                <b>{{ $item['english']['value'] }}</b>
                            @endif
                        </td>
                        <td align="right">
                            @if($item['english']['title'] === 'Rent')
                                <b>{{ 'ريال قطري' }} {{ $item['arabic']['value'] ?? '' }}</b>
                            @elseif($item['english']['title'] === 'Security Deposit')
                                <b>({{ $item['arabic']['value'] ?? '' }}) {{ 'ريال قطري' }} {{ $item['english']['value1'] ?? '' }}</b>
                            @else
                                <b>{{ $item['arabic']['value'] ?? '' }}</b>
                            @endif
                        </td>
                        <td align="right">{{ $item['arabic']['title'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- CONTRACT PREAMBLE --}}
    <div style="border-radius: 0px; padding: 0; overflow: hidden; border: 1px solid #eee; page-break-after: auto;">
        <table border="0" width="100%" style="padding:0px !important; font-size: 9px; page-break-after: inherit;">
            <tr>
                <td style="text-align: left;">
                    <ul style="list-style-type: none; list-style-position: inside; text-align: left; padding-left: 0; margin: 0;">
                        @php
                            $startDate = $type === 'normal' ? $rentOut->start_date : $rentOut->extends()->latest()->first()?->start_date;
                        @endphp
                        <li>This Contract is made on <b>{{ systemDate($startDate) }}</b> by and between the First Party - Lessor and the Second Party - Lessee (details as above).</li>
                        <li style="margin-right: 0; text-align: right; direction: rtl;">
                            ({{ 'التفاصيل على النحو الوارد أعلاه' }}). {{ 'بين الطرف الأول - المؤجر والطرف الثاني - المستأجر' }}
                            {{ $startDate }}{{ 'تم إبرام هذا العقد على' }}
                        </li>
                        <li>Both the parties agree to conclude this contract according to the terms and conditions attached in page numbers 2 to 8.</li>
                        <li style="margin-right: 0; text-align: right; direction: rtl;">
                            {{ 'يتفق الطرفان على إبرام هذا العقد وفقاً للشروط والأحكام المرفقة في الصفحات من 2 إلى 8.' }}
                        </li>
                    </ul>
                </td>
            </tr>
        </table>
    </div>

    {{-- SIGNATURES --}}
    <div class="keep-together">
        <table width="100%" style="page-break-after: initial;">
            <tr>
                <td>
                    <table border="1" style="width: 100%; border-collapse: collapse; font-size:10px">
                        <tr>
                            <td style="text-align: left">
                                <table border="0" style="width: 100%; border-collapse: collapse;">
                                    <tr>
                                        <td><b>Signature (Second Party - Lessee)</b></td>
                                    </tr>
                                    <tr>
                                        <td style="text-align: left">
                                            <b>{{ 'التوقيع (الطرف الثاني - المستأجر)' }}</b>
                                        </td>
                                    </tr>
                                    <br>
                                    <tr>
                                        <td style="text-align: left">
                                            <b>Date</b>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                            <td style="text-align: left">
                                <table border="0" style="width: 100%; border-collapse: collapse;">
                                    <tr>
                                        <td><b>Signature (First Party - Lessor)</b></td>
                                    </tr>
                                    <tr>
                                        <td style="text-align: left">
                                            <b>{{ 'التوقيع (الطرف الأول - المؤجر)' }}</b>
                                        </td>
                                    </tr>
                                    <br>
                                    <tr>
                                        <td style="text-align: left">
                                            <b>Date</b>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    @if($footerLogoUrl)
        <footer>
            @if($bondPaperMode)
                <div style="width: 100%; height: {{ $footerHeight }}px;"></div>
            @else
                <img width="100%" src="{{ $footerLogoUrl }}">
            @endif
        </footer>
    @endif

    @foreach($agreementImages as $imgPath)
        @if($imgPath)
            <div class="page-break"></div>
            <img width="100%" src="{{ asset('storage/' . $imgPath) }}">
        @endif
    @endforeach
</body>

</html>

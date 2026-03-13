@php
    $companyLogo = \App\Models\Configuration::where('key', 'company_logo')->value('value');
    $companyLogoUrl = null;
    if ($companyLogo) {
        $companyLogoPath = storage_path('app/public/' . $companyLogo);
        if (file_exists($companyLogoPath)) {
            $companyLogoUrl = 'data:image/' . pathinfo($companyLogoPath, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($companyLogoPath));
        }
    }

    $footerLogo = \App\Models\Configuration::where('key', 'rent_out_agreement_logo_footer')->value('value');
    $footerLogoUrl = null;
    if ($footerLogo) {
        $footerLogoPath = storage_path('app/public/' . $footerLogo);
        if (file_exists($footerLogoPath)) {
            $footerLogoUrl = 'data:image/' . pathinfo($footerLogoPath, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($footerLogoPath));
        }
    }

    $bondPaperMode = \App\Models\Configuration::where('key', 'reservation_bond_paper_mode')->value('value') === 'yes';
    $logoHeight = (int) (\App\Models\Configuration::where('key', 'reservation_logo_height')->value('value') ?: 80);
    $footerHeight = (int) (\App\Models\Configuration::where('key', 'reservation_footer_height')->value('value') ?: 50);
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Reservation Form</title>
    <style>
        body {
            font-family: "DejaVu Sans", sans-serif;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 5px;
            font-size: 9px;
        }

        .header {
            text-align: center;
            margin-bottom: 15px;
        }

        .logo img {
            max-width: 150px;
            height: auto;
        }

        .section {
            margin-bottom: 1px;
            border: 1px solid #ddd;
            border-radius: 3px;
        }

        .section-header {
            background: #1b7bbc;
            color: white;
            padding: 0px 0px;
            font-weight: bold;
            font-size: 10px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1px;
        }

        .data-table td {
            padding: 0px;
            font-size: 9px;
            border: 1px solid #eee;
        }

        .data-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .disclaimer {
            font-size: 7px;
            padding: 1px;
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 3px;
            margin: 1px 0;
        }

        footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding-top: 5px;
        }

        ul {
            padding-left: 10px;
            margin: 5px 0;
        }

        li {
            margin-bottom: 0px;
        }

        .remarks {
            padding: 8px;
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 3px;
            margin-top: 10px;
        }

        .compact-table td {
            padding: 1px 1px;
        }

        .document-title {
            background: #1b7bbc;
            color: white;
            padding: 2px;
            margin: 2px 0;
            border-radius: 3px;
            text-align: center;
            font-size: 11px;
        }

        @page {
            margin: 40px;
            size: A4;
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
                    @elseif($companyLogoUrl)
                        <img width="100%" src="{{ $companyLogoUrl }}">
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
                                <b>Reservation Form For An Apartment</b>
                            </td>
                            <td style="text-align: right; direction: rtl;">
                                <b>{{ 'نموذج تأكيد وحجز شقة' }}</b>
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

        <div class="document-title">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="text-align: left; direction: ltr;">
                        <b>Property Details</b>
                    </td>
                    <td style="text-align: right; direction: rtl;">
                        <b>{{ 'تفاصيل الوحدة' }}</b>
                    </td>
                </tr>
            </table>
        </div>

        <div class="section">
            <table class="data-table compact-table" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tbody>
                    @foreach($propertyDetails as $item)
                        <tr>
                            <td align="left" width="18%">{{ $item['english']['title'] }}</td>
                            <td align="left"><b>{{ $item['english']['value'] }}</b></td>
                            <td align="right">
                                <b>{{ $item['arabic']['value'] ?? $item['english']['value'] }}</b>
                            </td>
                            <td align="right" width="18%">{{ $item['arabic']['title'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="document-title">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    @if($rentOut->agreement_type?->value === 'lease')
                        <td style="text-align: left; direction: ltr;">
                            <b>Buyer's Details</b>
                        </td>
                        <td style="text-align: right; direction: rtl;">
                            <b>{{ 'تفاصيل المشتري' }}</b>
                        </td>
                    @else
                        <td style="text-align: left; direction: ltr;">
                            <b>Tenant's Details</b>
                        </td>
                        <td style="text-align: right; direction: rtl;">
                            <b>{{ 'تفاصيل المستأجر' }}</b>
                        </td>
                    @endif
                </tr>
            </table>
        </div>

        <div class="section">
            <table class="data-table compact-table" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tbody>
                    @foreach($buyerDetails as $item)
                        <tr>
                            <td align="left" width="18%">{{ $item['english']['title'] }}</td>
                            @if(!isset($item['english']['keep_original']))
                                <td align="left"><b>{{ $item['english']['value'] }}</b></td>
                                <td align="right">
                                    <b>{{ $item['arabic']['value'] ?? '' }}</b>
                                </td>
                            @else
                                <td colspan="2" align="center" width="50%"><b>{{ $item['english']['value'] }}</b></td>
                            @endif
                            <td align="right" width="18%">{{ $item['arabic']['title'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="document-title">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="text-align: left; direction: ltr;">
                        <b>Agent Details</b>
                    </td>
                    <td style="text-align: right; direction: rtl;">
                        <b>{{ 'تفاصيل العميل' }}</b>
                    </td>
                </tr>
            </table>
        </div>
        <div class="section">
            <table class="data-table compact-table" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tbody>
                    @foreach($agentDetails as $item)
                        <tr>
                            <td align="left" width="18%">{{ $item['english']['title'] }}</td>
                            <td align="left"><b>{{ $item['english']['value'] }}</b></td>
                            <td align="right">
                                <b>{{ $item['arabic']['value'] ?? $item['english']['value'] }}</b>
                            </td>
                            <td align="right" width="18%">{{ $item['arabic']['title'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="document-title">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="text-align: left; direction: ltr;">
                        <b>Reservation Fees & Disclaimer</b>
                    </td>
                    <td style="text-align: right; direction: rtl;">
                        <b>{{ 'رسوم الحجز وإخلاء المسؤولية' }}</b>
                    </td>
                </tr>
            </table>
        </div>
        <div class="disclaimer">
            <ul style="list-style-position: outside; list-style-type: disc; text-align: left; padding-left: 20px; margin: 0;">
                @foreach($rentOut->reservation_fees_disclaimer_en ?? [] as $point)
                    <li>{!! $point !!}</li>
                @endforeach
            </ul>
            <ul style="list-style-type: none; font-size:9px; text-align: right; margin: 0px 0 0 0; direction: rtl;">
                @foreach($rentOut->reservation_fees_disclaimer_ar ?? [] as $point)
                    <li style="position: relative; padding-right: 20px;">
                        <span style="display: inline-block; position: absolute; right: 5px; top: 0;">&#8226;</span>
                        <span style="display: inline-block;">{!! $point !!}</span>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="section" style="margin-top: 0px;">
            <table width="100%" cellspacing="0" cellpadding="5" style="border-collapse: collapse;">
                <tr>
                    <td width="50%" style="border: 1px solid #ddd; padding: 8px;">
                        <div style="margin-bottom: 1px;">
                            <div style="margin-bottom: 0px;">
                                <b>
                                    @if($rentOut->agreement_type?->value === 'lease')
                                        Sales Person Signature
                                    @else
                                        Leasing Consultant Signature
                                    @endif
                                </b>
                            </div>
                            <div style="margin-bottom: 0px; text-align: right;">
                                <b>
                                    @if($rentOut->agreement_type?->value === 'lease')
                                        {{ 'توقيع مندوب المبيعات' }}
                                    @else
                                        {{ 'توقيع مستشار التأجير' }}
                                    @endif
                                </b>
                            </div>
                            <div style="border-bottom: 1px solid #999; height: 25px;"></div>
                            <div style="margin-top: 5px;">
                                <b>Date: ________________</b>
                            </div>
                        </div>
                    </td>
                    <td width="50%" style="border: 1px solid #ddd; padding: 8px;">
                        <div style="margin-bottom: 1px;">
                            <div style="margin-bottom: 0px;">
                                <b>
                                    @if($rentOut->agreement_type?->value === 'lease')
                                        Buyer Signature
                                    @else
                                        Lessee Signature
                                    @endif
                                </b>
                            </div>
                            <div style="margin-bottom: 0px; text-align: right;">
                                <b>
                                    @if($rentOut->agreement_type?->value === 'lease')
                                        {{ 'توقيع المشتري' }}
                                    @else
                                        {{ 'توقيع المستأجر' }}
                                    @endif
                                </b>
                            </div>
                            <div style="border-bottom: 1px solid #999; height: 25px;"></div>
                            <div style="margin-top: 5px;">
                                <b>Date: ________________</b>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td width="50%" style="border: 1px solid #ddd; padding: 8px;">
                        <div style="margin-bottom: 1px;">
                            <div style="margin-bottom: 0px;">
                                <b>Accounting</b>
                            </div>
                            <div style="margin-bottom: 0px; text-align: right;">
                                <b>{{ 'الحسابات' }}</b>
                            </div>
                            <div style="border-bottom: 1px solid #999; height: 25px;"></div>
                            <div style="margin-top: 5px;">
                                <b>Date: ________________</b>
                            </div>
                        </div>
                    </td>
                    <td width="50%" style="border: 1px solid #ddd; padding: 8px;">
                        <div style="margin-bottom: 1px;">
                            <div style="margin-bottom: 0px;">
                                <b>Management</b>
                            </div>
                            <div style="margin-bottom: 0px; text-align: right;">
                                <b>{{ 'الإدارة' }}</b>
                            </div>
                            <div style="border-bottom: 1px solid #999; height: 25px;"></div>
                            <div style="margin-top: 5px;">
                                <b>Date: ________________</b>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        @if($rentOut->remark)
            <div class="remarks">
                <b>Remarks:</b><br>
                {{ $rentOut->remark }}
            </div>
        @endif
    </div>

    @if($footerLogoUrl)
        <footer style="position: fixed; bottom: 0; left: 0; right: 0; margin-bottom: 10px;">
            @if($bondPaperMode)
                <div style="width: 100%; height: {{ $footerHeight }}px;"></div>
            @else
                <img width="100%" style="max-height: 50px;" src="{{ $footerLogoUrl }}">
            @endif
        </footer>
    @endif
</body>

</html>

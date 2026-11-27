@php
    // The repeating <thead> reserves the next-pages height on every page, page 1
    // included, so page 1 is pulled back up by the difference.
    $firstPageOffset = $logoHeight - $nextPagesHeaderHeight;

    // A blank value keeps a line to fill in by hand. Values without Arabic letters
    // (dates, numbers, emails) are isolated left-to-right so they don't flip.
    $value = function ($text) {
        if (blank($text)) {
            return '______________';
        }

        return preg_match('/\p{Arabic}/u', (string) $text) ? '<bdi>' . e($text) . '</bdi>' : '<bdi dir="ltr">' . e($text) . '</bdi>';
    };
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Exclusive Leasing Brokerage Agreement</title>
    <style>
        body {
            font-family: "DejaVu Sans", sans-serif;
            line-height: 1.3;
            color: #333;
            margin: 0;
            padding: 5px;
            font-size: 9px;
        }

        .data-table th {
            font-size: 10px;
            background: {{ $agreementColors['primary'] }};
            text-align: right;
            border: 0;
            color: {{ $agreementColors['primaryInk'] }};
            padding: 2px 2px;
        }

        .data-table td {
            font-size: 9px;
            padding: 2px 2px;
            color: #333;
        }

        .data-table tr:nth-child(odd) td {
            background: #fff;
        }

        .data-table tr:nth-child(even) td {
            background: {{ $agreementColors['secondary'] }};
        }

        .section {
            border-radius: 3px;
            overflow: hidden;
            border: 1px solid #eee;
            page-break-inside: avoid;
        }

        .clause-table td {
            width: 50%;
            vertical-align: top;
            padding: 3px 4px;
            font-size: 9px;
            text-align: justify;
        }

        .clause-table td.ar {
            direction: rtl;
        }

        .title-block {
            border-collapse: separate;
            border-spacing: 0 3px;
            margin: 4px 0 2px;
        }

        .title-cell {
            width: 42%;
            background: #eee;
            font-size: 9px;
            padding: 6px 8px;
            vertical-align: middle;
        }

        .qr-cell {
            width: 16%;
            text-align: center;
            vertical-align: middle;
            background: #eee;
            border-left: 3px solid #fff;
            border-right: 3px solid #fff;
            padding: 3px 0;
        }

        .qr-box {
            display: inline-block;
            background: #fff;
            border: 1px solid {{ $agreementColors['primary'] }};
            border-radius: 3px;
            padding: 3px 3px 1px;
        }

        .qr-box img {
            display: block;
            width: 46px;
            height: 46px;
            margin: 0 auto;
        }

        .qr-ref {
            font-size: 6.5px;
            letter-spacing: 0.2px;
            color: #555;
            margin-top: 1px;
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

<body>
    {{-- Chrome repeats <thead>/<tfoot> on every printed page, so their spacers keep the content
         clear of the letterhead header (bond paper mode) and the fixed footer on every page. --}}
    <table class="page-layout" width="100%" cellpadding="0" cellspacing="0">
        @if ($bondPaperMode)
            <thead>
                <tr>
                    <td>
                        <div style="width: 100%; height: {{ $nextPagesHeaderHeight }}px;" aria-hidden="true"></div>
                    </td>
                </tr>
            </thead>
        @endif
        <tfoot>
            <tr>
                <td>
                    <div style="visibility: hidden;" aria-hidden="true">
                        @if ($bondPaperMode)
                            <div style="width: 100%; height: {{ $footerHeight }}px;"></div>
                        @elseif ($footerLogoUrl)
                            <img width="100%" src="{{ $footerLogoUrl }}">
                        @endif
                    </div>
                </td>
            </tr>
        </tfoot>
        <tbody>
            <tr>
                <td>
                    <div class="wrapper" @if ($bondPaperMode && $firstPageOffset) style="margin-top: {{ $firstPageOffset }}px;" @endif>
                        @if (! $bondPaperMode && $headerLogoUrl)
                            <header style="text-align: center;">
                                <img width="100%" src="{{ $headerLogoUrl }}" alt="Logo">
                            </header>
                        @endif
                        <table class="title-block" width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td class="title-cell" style="text-align: left; direction: ltr;">
                                    <b>EXCLUSIVE LEASING BROKERAGE AGREEMENT</b>
                                </td>
                                <td class="qr-cell" rowspan="2">
                                    <div class="qr-box">
                                        <img src="data:image/png;base64,{{ DNS2D::getBarcodePNG(route('property::sale::view', $rentOut->id), 'QRCODE', 5, 5) }}" alt="QR Code">
                                        <div class="qr-ref">969897909-{{ str_pad($rentOut->id, 4, '0', STR_PAD_LEFT) }}</div>
                                    </div>
                                </td>
                                <td class="title-cell" style="text-align: right; direction: rtl;">
                                    <b>عقد وساطة حصري لتأجير وحدة عقارية</b>
                                </td>
                            </tr>
                            <tr>
                                <td class="title-cell" style="text-align: left; direction: ltr;">
                                    <b>{{ $projectNameEnglish }}</b>
                                </td>
                                <td class="title-cell" style="text-align: right; direction: rtl;">
                                    <b>{{ $projectNameArabic }}</b>
                                </td>
                            </tr>
                        </table>
                    </div>

                    @foreach ($sections as $section)
                        <div class="section">
                            <table class="data-table" border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <th style="text-align: left; direction: ltr;"><b>{{ $section['english'] }}</b></th>
                                    <th style="text-align: right; direction: rtl;"><b>{{ $section['arabic'] }}</b></th>
                                </tr>
                            </table>
                            <table class="data-table" border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tbody>
                                    @foreach ($section['rows'] as [$englishTitle, $englishValue, $arabicValue, $arabicTitle])
                                        <tr>
                                            <td align="left" style="width: 22%;">{{ $englishTitle }}</td>
                                            <td align="left" style="width: 28%;"><b>{!! $value($englishValue) !!}</b></td>
                                            <td align="right" style="width: 28%; direction: rtl;"><b>{!! $value($arabicValue) !!}</b></td>
                                            <td align="right" style="width: 22%; direction: rtl;">{{ $arabicTitle }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endforeach

                    @foreach ($clauses as $index => $clause)
                        <div class="section">
                            <table class="data-table" border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <th style="text-align: left; direction: ltr;"><b>{{ $index + 1 }}. {{ $clause['heading_en'] }}</b></th>
                                    <th style="text-align: right; direction: rtl;"><b>{{ $index + 1 }}. {{ $clause['heading_ar'] }}</b></th>
                                </tr>
                            </table>
                            <table class="clause-table" border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td>{{ $clause['text_en'] }}</td>
                                    <td class="ar">{{ $clause['text_ar'] }}</td>
                                </tr>
                            </table>
                        </div>
                    @endforeach

                    <div style="page-break-inside: avoid;">
                        <table class="data-table" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-top: 4px;">
                            <tr>
                                <th style="text-align: left; direction: ltr;"><b>SIGNATURES</b></th>
                                <th style="text-align: right; direction: rtl;"><b>التوقيعات</b></th>
                            </tr>
                        </table>
                        <table border="1" style="width: 100%; border-collapse: collapse; font-size: 10px;">
                            <tr>
                                <td style="text-align: left; width: 50%; padding: 4px; vertical-align: top;">
                                    <b>For {{ $brokerNameEnglish ?: '____________________' }}</b><br>
                                    <b>عن {{ $brokerNameArabic ?: '____________________' }}</b><br><br>
                                    Name / الاسم: ____________________<br><br>
                                    Signature / Stamp / التوقيع / الختم: ____________________<br><br>
                                    Date / التاريخ: ____________________
                                </td>
                                <td style="text-align: left; width: 50%; padding: 4px; vertical-align: top;">
                                    <b>Owner / Authorized Representative</b><br>
                                    <b>المالك / الممثل المفوض</b><br><br>
                                    Name / الاسم: @if (filled($signatoryName))<b>{{ $signatoryName }}</b>@else ____________________ @endif<br><br>
                                    Signature / التوقيع: ____________________<br><br>
                                    Date / التاريخ: ____________________
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
    <footer>
        @if ($bondPaperMode)
            <div style="width: 100%; height: {{ $footerHeight }}px;"></div>
        @elseif ($footerLogoUrl)
            <img width="100%" src="{{ $footerLogoUrl }}">
        @endif
    </footer>
</body>

</html>

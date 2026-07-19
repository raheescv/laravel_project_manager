<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Vendor Statement</title>
    <style>
        /*
         | Vendor Account Statement — "Premium Graphite" (DomPDF-safe)
         | Table-based layout, solid colour bands, hard-coded hex (DomPDF has
         | no var()/flex/grid/box-shadow). Crisp square corners for fidelity.
         | Palette: deep #1F2937 · band #334155 · accent #334155 · soft #F0F2F5
         |          line #D5DBE2 · gold #9A7B25 · zebra #F6F8FA · Dr #C0392B · Cr #1A7A3C
         */
        * { box-sizing: border-box; }

        @page { margin: 0; }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            color: #1a1a1a;
            margin: 0;
            padding: 0;
            background: #fff;
        }

        /* ── Masthead ── */
        .vs-hdr { width: 100%; border-collapse: collapse; background: #1F2937; }
        .vs-hdr td { padding: 12px 20px; vertical-align: middle; }
        .vs-hdr td.logo-td { width: 120px; text-align: right; }
        .vs-co { font-size: 16px; font-weight: 700; color: #fff; margin: 0 0 3px; letter-spacing: .3px; }
        .vs-co-meta { font-size: 8.5px; color: rgba(255,255,255,.68); line-height: 1.65; }
        .vs-logo { background: #fff; padding: 5px 7px; display: inline-block; }
        .vs-logo img { max-width: 96px; max-height: 46px; display: block; }
        .vs-logo .ph { font-size: 20px; font-weight: 800; color: #1F2937; padding: 4px 8px; display: inline-block; }
        .vs-gold { height: 3px; background: #9A7B25; }

        /* ── Title bar ── */
        .vs-title { width: 100%; border-collapse: collapse; background: #334155; }
        .vs-title td { padding: 7px 20px; vertical-align: middle; }
        .vs-doc { font-size: 12px; font-weight: 700; color: #fff; letter-spacing: 2.5px; text-transform: uppercase; }
        .vs-title td.tr { text-align: right; width: 48%; font-size: 8.5px; color: rgba(255,255,255,.8); line-height: 1.7; white-space: nowrap; }
        .vs-title td.tr strong { color: #fff; font-size: 10px; }

        /* ── Info zone ── */
        .vs-info { width: 100%; border-collapse: collapse; margin-top: 12px; }
        .vs-info td { vertical-align: top; }
        .vs-pad { width: 20px; }
        .vs-gap { width: 10px; }

        .vs-card { border: 1px solid #D5DBE2; background: #F0F2F5; }
        .vs-card-h {
            font-size: 8px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.3px; color: #334155;
            padding: 7px 11px; border-bottom: 1px solid #D5DBE2; background: #fff;
        }
        .vs-card-b { padding: 9px 11px; }

        .vrow { width: 100%; border-collapse: collapse; }
        .vrow td { padding: 3px 0; font-size: 9.5px; vertical-align: top; }
        .vrow td.vk { color: #6b7280; width: 34%; }
        .vrow td.vv { font-weight: 700; color: #1a1a1a; }

        /* Summary stat grid */
        .vs-sum { width: 100%; border-collapse: collapse; }
        .vs-sum td { padding: 7px 9px; border: 1px solid #D5DBE2; background: #fff; vertical-align: top; }
        .vs-sum tr:first-child td { border-top: none; }
        .vs-sum td:first-child { border-left: none; }
        .vs-sum td:last-child { border-right: none; }
        .vs-sum .sl { font-size: 7.8px; font-weight: 700; text-transform: uppercase; letter-spacing: .7px; color: #6b7280; margin-bottom: 3px; }
        .vs-sum .sv { font-size: 12px; font-weight: 800; }
        .sv.dr { color: #C0392B; } .sv.cr { color: #1A7A3C; } .sv.nl { color: #1a1a1a; }
        .vs-sum td.closing { background: #F0F2F5; border: 1.5px solid #334155; }
        .vs-sum td.closing .sl { color: #334155; }

        /* ── Statement table ── */
        .vs-wrap { padding: 14px 20px 10px; }
        .stmt { width: 100%; border-collapse: collapse; }
        .stmt thead th {
            background: #1F2937; color: #fff; font-size: 8.8px; font-weight: 600; text-transform: uppercase; letter-spacing: .4px;
            padding: 7px 7px; text-align: left; border: 1px solid #1F2937; white-space: nowrap;
        }
        .stmt thead th.r { text-align: right; }
        .stmt tbody td { padding: 5px 7px; border: 1px solid #D5DBE2; font-size: 9.5px; vertical-align: top; color: #1a1a1a; }
        .stmt tbody tr.even td { background: #F6F8FA; }
        .stmt tbody tr.opening td { background: #fff; font-weight: 700; }
        .stmt .rmk { display: block; color: #6b7280; font-size: 8.3px; margin-top: 1px; }
        /* Cheque detail chips (DomPDF-safe: inline-block, solid hex, no flex) */
        .chq { margin-top: 3px; }
        .chq-chip {
            display: inline-block; padding: 1px 5px; margin: 1px 3px 0 0;
            font-size: 7.8px; font-weight: 700; line-height: 1.5;
            border: 1px solid #D5DBE2; background: #F0F2F5; color: #334155;
            border-radius: 2px; white-space: nowrap;
        }
        .chq-chip .k { color: #9aa3af; font-weight: 700; }
        .chq-chip.no   { background: #EEF1F6; color: #1F2937; border-color: #D5DBE2; }
        .chq-chip.bank { background: #F0F2F5; color: #334155; border-color: #D5DBE2; }
        .chq-chip.date { background: #F6F3EA; color: #9A7B25; border-color: #E7DFC9; }
        .stmt tfoot td { padding: 6px 7px; border: 1px solid #D5DBE2; font-size: 9.5px; font-weight: 700; }
        .stmt tfoot tr.ft1 td { background: #F0F2F5; }
        .stmt tfoot tr.ft2 td { background: #1F2937; color: #fff; border-color: #1F2937; }

        .r { text-align: right; } .c { text-align: center; }
        .dr { color: #C0392B; font-weight: 700; } .cr { color: #1A7A3C; font-weight: 700; }
        .muted { color: #c2c8d0; }

        /* ── Footer ── */
        .vs-foot { border-top: 1px solid #D5DBE2; margin: 8px 20px 0; padding: 6px 0; text-align: center; font-size: 7.8px; color: #9aa3af; }
    </style>
</head>
<body>

    {{-- ── Masthead ── --}}
    <table class="vs-hdr" cellpadding="0" cellspacing="0">
        <tr>
            <td>
                <div class="vs-co">{{ $companyName }}</div>
                <div class="vs-co-meta">
                    @if ($companyAddress){{ $companyAddress }}@endif
                    @if ($companyPhone) &bull; Tel: {{ $companyPhone }}@endif
                    @if ($companyEmail) &bull; {{ $companyEmail }}@endif
                </div>
            </td>
            <td class="logo-td">
                <div class="vs-logo">
                    @if ($companyLogo)
                        <img src="{{ $companyLogo }}" alt="Logo">
                    @else
                        <span class="ph">{{ strtoupper(mb_substr($companyName ?: 'C', 0, 1)) }}</span>
                    @endif
                </div>
            </td>
        </tr>
    </table>
    <div class="vs-gold"></div>

    {{-- ── Title bar ── --}}
    <table class="vs-title" cellpadding="0" cellspacing="0">
        <tr>
            <td><div class="vs-doc">Vendor Account Statement</div></td>
            <td class="tr">
                <strong>{{ $vendor->name }}</strong><br>
                Period: {{ systemDate($fromDate) }} &ndash; {{ systemDate($toDate) }}
            </td>
        </tr>
    </table>

    {{-- ── Vendor + Summary ── --}}
    <table class="vs-info" cellpadding="0" cellspacing="0">
        <tr>
            <td class="vs-pad"></td>
            <td style="width:34%;">
                <div class="vs-card">
                    <div class="vs-card-h">Vendor Details</div>
                    <div class="vs-card-b">
                        <table class="vrow" cellpadding="0" cellspacing="0">
                            <tr><td class="vk">Name</td><td class="vv">{{ $vendor->name }}</td></tr>
                            <tr><td class="vk">Vendor ID</td><td class="vv">#{{ $vendor->id }}</td></tr>
                            @if ($vendor->mobile)
                                <tr><td class="vk">Mobile</td><td class="vv">{{ $vendor->mobile }}</td></tr>
                            @endif
                            @if ($vendor->email)
                                <tr><td class="vk">Email</td><td class="vv">{{ $vendor->email }}</td></tr>
                            @endif
                            @if ($vendor->place)
                                <tr><td class="vk">Place</td><td class="vv">{{ $vendor->place }}</td></tr>
                            @endif
                        </table>
                    </div>
                </div>
            </td>
            <td class="vs-gap"></td>
            <td style="width:62%;">
                <div class="vs-card">
                    <div class="vs-card-h">Balance Summary</div>
                    <div class="vs-card-b" style="padding:0;">
                        <table class="vs-sum" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="width:25%;"><div class="sl">Opening Debit</div><div class="sv dr">{{ currency($openingDebit) }}</div></td>
                                <td style="width:25%;"><div class="sl">Opening Credit</div><div class="sv cr">{{ currency($openingCredit) }}</div></td>
                                <td style="width:25%;"><div class="sl">Period Debit</div><div class="sv dr">{{ currency($periodDebit) }}</div></td>
                                <td style="width:25%;"><div class="sl">Period Credit</div><div class="sv cr">{{ currency($periodCredit) }}</div></td>
                            </tr>
                            <tr>
                                <td colspan="2"><div class="sl">Net Opening Balance</div><div class="sv nl">{{ $openingBalanceLabel }}</div></td>
                                <td colspan="2" class="closing"><div class="sl">Closing Balance</div><div class="sv nl">{{ $closingBalanceLabel }}</div></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </td>
            <td class="vs-pad"></td>
        </tr>
    </table>

    {{-- ── Statement table ── --}}
    <div class="vs-wrap">
        <table class="stmt" cellpadding="0" cellspacing="0">
            <thead>
                <tr>
                    <th style="width:11%;">Date</th>
                    <th style="width:33%;">Particulars</th>
                    <th style="width:15%;">Invoice No</th>
                    <th class="r" style="width:13%;">Debit</th>
                    <th class="r" style="width:13%;">Credit</th>
                    <th class="r" style="width:15%;">Balance</th>
                </tr>
            </thead>
            <tbody>
                <tr class="opening">
                    <td style="white-space:nowrap;">{{ systemDate($fromDate) }}</td>
                    <td>Opening Balance b/f</td>
                    <td><span class="muted">—</span></td>
                    <td class="r"><span class="muted">—</span></td>
                    <td class="r"><span class="muted">—</span></td>
                    <td class="r">{{ $openingBalanceLabel }}</td>
                </tr>
                @forelse ($statementRows as $i => $row)
                    <tr class="{{ $i % 2 === 0 ? 'even' : '' }}">
                        <td style="white-space:nowrap;">{{ systemDate($row->date) }}</td>
                        <td>
                            {{ $row->particulars ?: '—' }}
                            @if ($row->remarks)
                                <span class="rmk">{{ $row->remarks }}</span>
                            @endif
                            @if (! empty($row->has_cheque))
                                <div class="chq">
                                    @if (! empty($row->cheque_no))
                                        <span class="chq-chip no"><span class="k">Cheque</span> {{ $row->cheque_no }}</span>
                                    @endif
                                    @if (! empty($row->bank_name))
                                        <span class="chq-chip bank">{{ $row->bank_name }}</span>
                                    @endif
                                    @if (! empty($row->cheque_date))
                                        <span class="chq-chip date"><span class="k">Dated</span> {{ systemDate($row->cheque_date) }}</span>
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td>{{ $row?->model_invoice_no ?: '—' }}</td>
                        <td class="r">
                            @if ($row->debit > 0)
                                <span class="dr">{{ currency($row->debit) }}</span>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                        <td class="r">
                            @if ($row->credit > 0)
                                <span class="cr">{{ currency($row->credit) }}</span>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                        <td class="r">{{ $row->balance_label }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="c muted" style="padding:10px 0;">No entries found for this period.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="ft1">
                    <td colspan="3" class="r">Period Total</td>
                    <td class="r dr">{{ currency($periodDebit) }}</td>
                    <td class="r cr">{{ currency($periodCredit) }}</td>
                    <td></td>
                </tr>
                <tr class="ft2">
                    <td colspan="3" class="r">Grand Total &bull; Closing Balance</td>
                    <td class="r">{{ currency($totalDebit) }}</td>
                    <td class="r">{{ currency($totalCredit) }}</td>
                    <td class="r">{{ $closingBalanceLabel }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- ── Footer ── --}}
    <div class="vs-foot">Computer generated document &bull; {{ now()->format('d/m/Y h:i A') }}</div>

</body>
</html>

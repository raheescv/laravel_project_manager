<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Vendor Statement</title>
    <style>
        * { box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            color: #1a1a1a;
            margin: 0;
            padding: 0;
            background: #fff;
        }

        /* ── Page header (table-based, no floats) ── */
        .hdr-table { width: 100%; border-collapse: collapse; background: #1e3a5f; padding: 10px 18px; }
        .hdr-table td { padding: 10px 18px; vertical-align: middle; }
        .hdr-table td.logo-td { width: 110px; text-align: right; padding: 6px 18px; }
        .logo-wrap { background: #fff; padding: 4px; text-align: center; display: inline-block; }
        .logo-wrap img { max-width: 90px; max-height: 44px; display: block; }
        .co-name { font-size: 14px; font-weight: 700; color: #fff; margin: 0 0 3px; }
        .co-meta  { font-size: 8.5px; color: #8db4d8; line-height: 1.6; }

        /* ── Title bar (table-based) ── */
        .title-table { width: 100%; border-collapse: collapse; background: #2563a8; }
        .title-table td { padding: 6px 18px; vertical-align: middle; }
        .title-table td.title-right { text-align: right; width: 45%; font-size: 8.5px; color: #b8d4f0; line-height: 1.7; white-space: nowrap; }
        .title-table td.title-right strong { color: #fff; }
        .doc-name { font-size: 11px; font-weight: 700; color: #fff; letter-spacing: 2.5px; text-transform: uppercase; }

        /* ── Info section ── */
        .info-table { width: 100%; border-collapse: collapse; margin: 10px 0 8px; }
        .info-table td { vertical-align: top; padding: 0; }
        .info-table td.info-pad { padding: 0 18px; }
        .info-table td.info-gap { width: 8px; }

        .box { border: 1px solid #c8d8e8; padding: 8px 10px; background: #f7fbff; }
        .box-heading {
            font-size: 7.5px; font-weight: 700; text-transform: uppercase;
            letter-spacing: 1.2px; color: #2563a8;
            border-bottom: 1px solid #d0e4f4; padding-bottom: 4px; margin-bottom: 6px;
        }

        /* Vendor rows */
        .vrow-table { width: 100%; border-collapse: collapse; }
        .vrow-table td { padding: 2px 0; font-size: 9.5px; vertical-align: top; }
        .vrow-table td.vk { color: #666; width: 38%; }
        .vrow-table td.vv { font-weight: 700; color: #1a1a1a; }

        /* Summary 2-col grid */
        .sum-grid { width: 100%; border-collapse: collapse; }
        .sum-grid td {
            width: 50%; padding: 5px 8px; font-size: 9.5px;
            border: 1px solid #deeaf4; vertical-align: top; background: #fff;
        }
        .sum-grid tr:first-child td { border-top: none; }
        .sum-grid td:first-child { border-left: none; }
        .sum-grid td:last-child  { border-right: none; }
        .sum-grid .sg-lbl { font-size: 8px; color: #666; margin-bottom: 1px; }
        .sum-grid .sg-val { font-size: 11px; font-weight: 700; }
        .sg-val.dr { color: #c0392b; }
        .sg-val.cr { color: #1a7a3c; }
        .sg-val.nl { color: #1a1a1a; }
        .sg-closing td { background: #edf4fb !important; border-top: 1.5px solid #b8d0e8 !important; }

        /* ── Statement table ── */
        .stmt-wrap { padding: 0 18px 14px; }

        .stmt { width: 100%; border-collapse: collapse; }
        .stmt thead th {
            background: #1e3a5f; color: #fff; font-size: 9px; font-weight: 600;
            padding: 5px 6px; text-align: left; border: 1px solid #2d5480; white-space: nowrap;
        }
        .stmt thead th.r { text-align: right; }

        .stmt tbody td {
            padding: 4px 6px; border: 1px solid #dce8f0;
            font-size: 9.5px; vertical-align: top; color: #1a1a1a;
        }
        .stmt tbody tr.even td { background: #f4f8fc; }

        .stmt tfoot td {
            padding: 5px 6px; border: 1px solid #c0d4e8;
            font-size: 9.5px; font-weight: 700;
        }
        .stmt tfoot tr.ft1 td { background: #e8f0f8; }
        .stmt tfoot tr.ft2 td { background: #d4e4f4; }

        .r { text-align: right; }
        .c { text-align: center; }
        .dr { color: #c0392b; font-weight: 700; }
        .cr { color: #1a7a3c; font-weight: 700; }
        .muted { color: #bbb; }

        /* ── Page footer ── */
        .page-footer {
            border-top: 1px solid #dce8f0; padding: 5px 18px;
            text-align: center; font-size: 7.5px; color: #aaa;
        }
    </style>
</head>
<body>

    {{-- ── Header ── --}}
    <table class="hdr-table" cellpadding="0" cellspacing="0">
        <tr>
            <td>
                <div class="co-name">{{ $companyName }}</div>
                <div class="co-meta">
                    @if ($companyAddress){{ $companyAddress }}@endif
                    @if ($companyPhone) &bull; Tel: {{ $companyPhone }}@endif
                    @if ($companyEmail) &bull; {{ $companyEmail }}@endif
                </div>
            </td>
            <td class="logo-td">
                @if ($companyLogo)
                    <div class="logo-wrap"><img src="{{ $companyLogo }}" alt="Logo"></div>
                @endif
            </td>
        </tr>
    </table>

    {{-- ── Title bar ── --}}
    <table class="title-table" cellpadding="0" cellspacing="0">
        <tr>
            <td><div class="doc-name">Vendor Account Statement</div></td>
            <td class="title-right">
                <strong>{{ $vendor->name }}</strong><br>
                Period: {{ systemDate($fromDate) }} &ndash; {{ systemDate($toDate) }}
            </td>
        </tr>
    </table>

    {{-- ── Vendor + Summary ── --}}
    <table class="info-table" cellpadding="0" cellspacing="0">
        <tr>
            <td class="info-pad" style="width:1px; padding-right:4px;">
                {{-- left margin only --}}
            </td>
            <td style="width:36%;">
                <div class="box">
                    <div class="box-heading">Vendor Details</div>
                    <table class="vrow-table" cellpadding="0" cellspacing="0">
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
            </td>
            <td class="info-gap"></td>
            <td style="width:62%;">
                <div class="box">
                    <div class="box-heading">Balance Summary</div>
                    <table class="sum-grid" cellpadding="0" cellspacing="0">
                        <tr>
                            <td>
                                <div class="sg-lbl">Opening Debit</div>
                                <div class="sg-val dr">{{ currency($openingDebit) }}</div>
                            </td>
                            <td>
                                <div class="sg-lbl">Opening Credit</div>
                                <div class="sg-val cr">{{ currency($openingCredit) }}</div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="sg-lbl">Period Debit</div>
                                <div class="sg-val dr">{{ currency($periodDebit) }}</div>
                            </td>
                            <td>
                                <div class="sg-lbl">Period Credit</div>
                                <div class="sg-val cr">{{ currency($periodCredit) }}</div>
                            </td>
                        </tr>
                        <tr class="sg-closing">
                            <td>
                                <div class="sg-lbl">Net Opening Balance</div>
                                <div class="sg-val nl">{{ $openingBalanceLabel }}</div>
                            </td>
                            <td>
                                <div class="sg-lbl">Closing Balance</div>
                                <div class="sg-val nl">{{ $closingBalanceLabel }}</div>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
            <td class="info-pad" style="width:1px; padding-left:4px;">
                {{-- right margin only --}}
            </td>
        </tr>
    </table>

    {{-- ── Statement table ── --}}
    <div class="stmt-wrap">
        <table class="stmt" cellpadding="0" cellspacing="0">
            <thead>
                <tr>
                    <th style="width:9%;">Date</th>
                    <th style="width:20%;">Particulars</th>
                    <th style="width:20%;">Invoice No</th>
                    <th class="r" style="width:10%;">Debit</th>
                    <th class="r" style="width:10%;">Credit</th>
                    <th style="width:31%;">Remarks</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($statementRows as $i => $row)
                    <tr class="{{ $i % 2 === 1 ? 'even' : '' }}">
                        <td style="white-space:nowrap;">{{ systemDate($row->date) }}</td>
                        <td>{{ $row->particulars ?: '—' }}</td>
                        <td> {{ $row?->model_invoice_no ?: '—' }} </td>
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
                        <td>{{ $row->remarks ?: '—' }}</td>
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
                    <td colspan="3" class="r">Grand Total</td>
                    <td class="r dr">{{ currency($totalDebit) }}</td>
                    <td class="r cr">{{ currency($totalCredit) }}</td>
                    <td class="r">{{ $closingBalanceLabel }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- ── Footer ── --}}
    <div class="page-footer">
        Computer generated document &bull; {{ now()->format('d/m/Y h:i A') }}
    </div>

</body>
</html>

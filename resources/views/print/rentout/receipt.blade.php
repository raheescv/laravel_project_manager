<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Receipt - #{{ $payment->voucher_no ?? $payment->id }}</title>
    <style>
        .clearfix:after{content:"";display:table;clear:both}
        a{color:#0087C3;text-decoration:none}
        body{margin:0;padding:10px 20px;color:#444;background:#fff;font-family:'DejaVu Sans',Arial,sans-serif;font-size:11px}

        .hdr{padding:5px 0 6px;margin-bottom:5px;border-bottom:2px solid #2c3e50;overflow:hidden}
        .hdr::after{content:"";display:table;clear:both}
        .hdr-l{float:left;width:60%}
        .hdr-l h2{font-size:1.4em;font-weight:700;color:#1a252f;margin:0 0 2px}
        .hdr-l p{font-size:10px;color:#666;line-height:1.5;margin:0}
        .hdr-r{float:right;width:130px;border:1px solid #e8e8e8;border-radius:6px;padding:3px;text-align:center;background:#fafbfc}
        .hdr-r img{max-width:120px;max-height:50px;display:block;margin:0 auto}

        .title{text-align:center;margin:5px 0 6px;padding:4px 0;background:#2c3e50}
        .title h3{font-size:1.3em;font-weight:700;color:#fff;margin:0;letter-spacing:2px;text-transform:uppercase}

        .amt-row{margin-bottom:5px;overflow:hidden}
        .amt-row::after{content:"";display:table;clear:both}
        .amt-box{float:left;width:50%;background:#eaf4fb;border-left:3px solid #0087C3;padding:4px 8px}
        .amt-box .lb{font-size:8px;color:#999;text-transform:uppercase;letter-spacing:1px}
        .amt-box .vl{font-size:18px;font-weight:700;color:#0087C3}
        .meta{float:right;width:38%}
        .meta table{margin:0;width:100%}
        .meta td{padding:2px 5px;font-size:11px;background:transparent;border-bottom:1px solid #eee}
        .meta .k{font-weight:600;color:#888;text-align:right;width:35%;background:#f8f9fa}
        .meta .v{font-weight:600;color:#333;text-align:left}

        .row{margin-bottom:0;border-left:3px solid #0087C3;background:#f8f9fa;padding:3px 8px;font-size:11px}
        .row b{color:#333}
        .row2{overflow:hidden;margin-bottom:0}
        .row2::after{content:"";display:table;clear:both}
        .row2 .c{float:left;width:50%;border-left:3px solid #0087C3;background:#f8f9fa;padding:3px 8px;font-size:11px;box-sizing:border-box}

        .sigs{margin-top:25px;padding-top:6px;border-top:1px dashed #bbb;overflow:hidden}
        .sigs::after{content:"";display:table;clear:both}
        .sig{float:left;width:45%;text-align:center;padding-top:22px}
        .sig:last-child{float:right}
        .sig span{display:block;border-top:1px solid #999;width:65%;margin:0 auto;padding-top:3px;font-size:9px;color:#777}

        .ft{margin-top:12px;padding-top:3px;border-top:1px solid #eee;text-align:center;font-size:8px;color:#bbb}
    </style>
</head>
<body>
    <header class="hdr clearfix">
        <div class="hdr-l">
            <h2>{{ $companyName }}</h2>
            <p>
                @if($companyPhone)<strong>Tel:</strong> {{ $companyPhone }}<br>@endif
                @if($companyAddress){{ $companyAddress }}<br>@endif
                @if($companyEmail)<a href="mailto:{{ $companyEmail }}">{{ $companyEmail }}</a>@endif
            </p>
        </div>
        <div class="hdr-r">
            @if($companyLogo)<img src="{{ $companyLogo }}" alt="Logo">@endif
        </div>
    </header>

    <div class="title"><h3>Receipt Voucher</h3></div>

    <div class="amt-row">
        <div class="amt-box">
            <div class="lb">Amount</div>
            <div class="vl">QR {{ currency($payment->credit > 0 ? $payment->credit : $payment->debit) }}</div>
        </div>
        <div class="meta">
            <table border="0" cellspacing="0" cellpadding="0">
                <tr><td class="k">#</td><td class="v">{{ $payment->voucher_no ?? $payment->id }}</td></tr>
                <tr><td class="k">Date</td><td class="v">{{ $payment->date?->format('d-m-Y') }}</td></tr>
            </table>
        </div>
    </div>

    @if($rentOut->property)
        <div class="row"><b>Property/Unit No:</b> {{ $rentOut->property->number }}@if($rentOut->building) ({{ $rentOut->building->name }})@endif</div>
    @endif
    @if($rentOut->customer)
        <div class="row"><b>Received from Mr/M/s:</b> {{ $rentOut->customer->name }}</div>
    @endif
    <div class="row"><b>Remarks:</b> {{ $payment->remark ?? '-' }}</div>
    @if($payment->category)
        <div class="row"><b>Category:</b> {{ $payment->category }}</div>
    @endif
    <div class="row2">
        <div class="c"><b>Payment Mode:</b> {{ $payment->account?->name ?? '-' }}</div>
        <div class="c"><b>Agreement No:</b> {{ $rentOut->agreement_no }}</div>
    </div>
    <div class="row2">
        <div class="c"><b>Source:</b> {{ $payment->source }}@if($payment->group) &mdash; {{ $payment->group }}@endif</div>
        <div class="c">
            @if($payment->credit > 0)<b>Credit:</b> <span style="color:#28a745;font-weight:700">QR {{ currency($payment->credit) }}</span>@endif
            @if($payment->debit > 0)<b>Debit:</b> <span style="color:#dc3545;font-weight:700">QR {{ currency($payment->debit) }}</span>@endif
        </div>
    </div>

    <div class="sigs">
        <div class="sig"><span>Receiver's Sign</span></div>
        <div class="sig"><span>Authorized Signature</span></div>
    </div>
    <div class="ft">Computer-generated receipt &bull; {{ now()->format('d/m/Y h:i A') }}</div>
</body>
</html>

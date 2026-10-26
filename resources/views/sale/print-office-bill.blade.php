@php
    $billNo = $sale->bill_no ?: $sale->invoice_no;
    $heading = filled($company_name) ? $company_name : $sale->branch?->name;
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bill {{ $billNo }}</title>
    {!! $font_css !!}
    {{-- "Stamp" office bill: pure black on white so it prints crisp on a thermal roll. --}}
    <style>
        @page {
            margin: 0;
            size: 80mm auto;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'IBM Plex Sans Arabic', 'Helvetica Neue', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.45;
            width: 80mm;
            margin: 0 auto;
            padding: 18px 16px 22px;
            background: #fff;
            color: #000;
            font-variant-numeric: tabular-nums;
        }

        .brand {
            text-align: center;
        }

        .brand .en {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: .22em;
            padding-left: .22em;
            text-transform: uppercase;
        }

        .stamp {
            display: table;
            width: 100%;
            margin-top: 12px;
            border: 2px solid #000;
            border-radius: 10px;
            border-collapse: separate;
            overflow: hidden;
        }

        .stamp > div {
            display: table-cell;
            vertical-align: middle;
        }

        .stamp .no {
            width: 96px;
            background: #000;
            color: #fff;
            padding: 7px 10px;
            text-align: center;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .stamp .no small {
            display: block;
            font-size: 8.5px;
            font-weight: 700;
            letter-spacing: .2em;
        }

        .stamp .no b {
            display: block;
            font-size: 22px;
            line-height: 1.1;
            word-break: break-all;
        }

        .stamp .who {
            padding: 6px 10px;
        }

        .stamp .who b {
            display: block;
            font-size: 13px;
        }

        .stamp .who span {
            font-size: 10.5px;
        }

        .split {
            display: table;
            width: 100%;
        }

        .split > * {
            display: table-cell;
        }

        .split > :last-child {
            text-align: right;
        }

        .invoice {
            font-size: 9.5px;
            margin-top: 6px;
        }

        .items {
            margin-top: 14px;
        }

        .items .head {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: .14em;
            text-transform: uppercase;
            padding-bottom: 4px;
            border-bottom: 1.5px solid #000;
        }

        .item {
            padding: 8px 0;
        }

        .item + .item {
            border-top: 1px dashed #000;
        }

        .item .name {
            font-weight: 700;
            padding-right: 10px;
        }

        .item .price {
            width: 30%;
            font-weight: 700;
            white-space: nowrap;
            vertical-align: top;
        }

        .sums {
            border-top: 1.5px solid #000;
            padding-top: 6px;
        }

        .grand {
            text-align: center;
            margin-top: 12px;
            padding-top: 10px;
            border-top: 1px dashed #000;
        }

        .grand small {
            display: block;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: .24em;
        }

        .grand b {
            display: block;
            font-size: 30px;
            line-height: 1.1;
        }
    </style>
</head>

<body onload="window.print();">
    <div class="brand">
        @if (filled($heading))
            <div class="en">{{ $heading }}</div>
        @endif
    </div>

    <div class="stamp">
        <div class="no">
            <small>BILL NO</small>
            <b>{{ $billNo }}</b>
        </div>
        <div class="who">
            <b>Biller : {{ $sale->createdUser?->name ?? '-' }}</b>
            <span>{{ \Carbon\Carbon::parse($sale->date)->format('d-m-Y') }} · {{ $sale->created_at?->format('H:i') }}</span>
        </div>
    </div>
    @if ($sale->bill_no)
        <div class="split invoice">
            <span>Invoice No</span>
            <span>{{ $sale->invoice_no }}</span>
        </div>
    @endif

    <div class="items">
        <div class="split head">
            <span>Item</span>
            <span>Price</span>
        </div>
        @foreach ($sale->items as $item)
            @php
                $isCategory = $print_item_label === 'category' && $item->product?->mainCategory;
                $name = $isCategory ? $item->product->mainCategory->name : $item->product?->name;
            @endphp
            <div class="split item">
                <div class="name">{{ $name }}@if ((float) $item->quantity != 1) × {{ round($item->quantity, 3) }}@endif</div>
                <div class="price">{{ currency($item->gross_amount) }}</div>
            </div>
        @endforeach
    </div>

    <div class="sums">
        <div class="split"><span>Sub Total</span><span>{{ currency($sale->gross_amount) }}</span></div>
        <div class="split"><span>Discount</span><span>{{ currency($sale->item_discount + $sale->other_discount) }}</span></div>
        @if ($sale->tax_amount != 0)
            <div class="split"><span>Tax</span><span>{{ currency($sale->tax_amount) }}</span></div>
        @endif
        @if ($sale->freight != 0)
            <div class="split"><span>Freight</span><span>{{ currency($sale->freight) }}</span></div>
        @endif
        @if ($sale->tip != 0)
            <div class="split"><span>Tip</span><span>{{ currency($sale->tip) }}</span></div>
        @endif
        @if ($sale->round_off != 0)
            <div class="split"><span>Round Off</span><span>{{ currency($sale->round_off) }}</span></div>
        @endif
    </div>

    <div class="grand">
        <small>GRAND TOTAL</small>
        <b>{{ currency($sale->grand_total) }}</b>
    </div>
</body>

</html>

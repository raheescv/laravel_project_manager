<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bill {{ $sale->bill_no ?: $sale->invoice_no }}</title>
    <style>
        @page {
            margin: 0;
            size: 80mm auto;
        }

        body {
            font-family: 'Courier New', 'Arial', monospace;
            font-size: 14px;
            line-height: 1.35;
            width: 80mm;
            margin: 0 auto;
            padding: 8px 12px 14px;
            background: #fff;
            color: #000;
            box-sizing: border-box;
        }

        .head {
            text-align: center;
            margin-bottom: 26px;
        }

        .head .company {
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .head .company-ar {
            font-family: 'Arial', sans-serif;
            font-size: 15px;
            font-weight: 700;
        }

        .head .line {
            font-weight: 700;
        }

        .head .invoice {
            font-size: 11px;
        }

        .items {
            width: 100%;
            border-collapse: collapse;
        }

        .items th {
            font-weight: 400;
            text-align: left;
            padding-bottom: 10px;
        }

        .items td {
            vertical-align: top;
            padding: 0;
        }

        .items .price {
            width: 32%;
            text-align: left;
        }

        .items .name-ar {
            font-family: 'Arial', sans-serif;
            font-weight: 700;
            text-align: left;
            padding-bottom: 6px;
        }

        .totals {
            margin: 22px 0 0 auto;
            border-collapse: collapse;
        }

        .totals td {
            padding: 0;
            text-align: right;
            font-weight: 700;
        }

        .totals .sep {
            padding: 0 6px;
        }
    </style>
</head>

<body onload="window.print();">
    <div class="head">
        @if (filled($company_name))
            <div class="company">{{ $company_name }}</div>
        @endif
        @if (filled($company_name_arabic))
            <div class="company-ar" dir="rtl">{{ $company_name_arabic }}</div>
        @endif
        <div class="line">Date : {{ \Carbon\Carbon::parse($sale->date)->format('d-m-Y') }}</div>
        <div class="line">Bill No : {{ $sale->bill_no ?: $sale->invoice_no }}</div>
        <div class="line">Biller : {{ $sale->createdUser?->name ?? '-' }}-{{ $sale->created_at?->format('H:i') }}</div>
        @if ($sale->bill_no)
            <div class="invoice">Invoice No : {{ $sale->invoice_no }}</div>
        @endif
    </div>

    <table class="items">
        <thead>
            <tr>
                <th>Item</th>
                <th class="price">Price</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sale->items as $item)
                @php
                    $isCategory = $print_item_label === 'category' && $item->product?->mainCategory;
                    $name = $isCategory ? $item->product->mainCategory->name : $item->product?->name;
                    $nameArabic = $isCategory ? $item->product->mainCategory->name_arabic : $item->product?->name_arabic;
                @endphp
                <tr>
                    <td>
                        {{ $name }}
                        @if ((float) $item->quantity != 1)
                            x {{ round($item->quantity, 3) }}
                        @endif
                    </td>
                    <td class="price">{{ currency($item->gross_amount) }}</td>
                </tr>
                @if (filled($nameArabic))
                    <tr>
                        <td colspan="2" class="name-ar" dir="rtl" style="text-align: left;">{{ $nameArabic }}</td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td>Sub Total</td>
            <td class="sep">:</td>
            <td>{{ currency($sale->gross_amount) }}</td>
        </tr>
        <tr>
            <td>Discount</td>
            <td class="sep">:</td>
            <td>{{ currency($sale->item_discount + $sale->other_discount) }}</td>
        </tr>
        @if ($sale->tax_amount != 0)
            <tr>
                <td>Tax</td>
                <td class="sep">:</td>
                <td>{{ currency($sale->tax_amount) }}</td>
            </tr>
        @endif
        @if ($sale->freight != 0)
            <tr>
                <td>Freight</td>
                <td class="sep">:</td>
                <td>{{ currency($sale->freight) }}</td>
            </tr>
        @endif
        @if ($sale->tip != 0)
            <tr>
                <td>Tip</td>
                <td class="sep">:</td>
                <td>{{ currency($sale->tip) }}</td>
            </tr>
        @endif
        @if ($sale->round_off != 0)
            <tr>
                <td>Round Off</td>
                <td class="sep">:</td>
                <td>{{ currency($sale->round_off) }}</td>
            </tr>
        @endif
        <tr>
            <td>Grand Total</td>
            <td class="sep">:</td>
            <td>{{ currency($sale->grand_total) }}</td>
        </tr>
    </table>
</body>

</html>

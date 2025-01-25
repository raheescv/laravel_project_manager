<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Invoice
        @if ($thermal_printer_style == 'with_arabic')
            | فاتورة العميل
        @endif
    </title>
    <style>
        body {
            font-family: "Courier New", Courier, monospace;
            line-height: 1.2;
            font-size: 12px;
            margin: 0 auto;
            width: 80mm;
            background-color: #fff;
            padding: 15px;
            color: #000;
        }

        @page {
            margin: 0;
            size: 80mm auto;
        }

        .receipt-container {
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            width: 100%;
            padding: 1px;
        }

        h1,
        h2,
        h3 {
            margin: 5px 0;
            text-align: center;
            font-size: 14px;
            font-weight: bold;
        }

        .divider {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 12px;
        }

        .nowrap {
            white-space: nowrap !important;
        }

        .table th,
        .table td {
            border: 1px solid #0e0d0d;
            padding: 3px;
            text-align: center;
        }

        .table th {
            background-color: #f0f0f0;
            font-size: 12px;
            font-weight: bold;
        }

        .text-left {
            text-align: left !important;
        }

        .text-right {
            text-align: right !important;
        }

        .text-center {
            text-align: center !important;
        }

        .bold {
            font-weight: bold;
        }

        .barcode {
            text-align: center;
            margin: 5px 0;
        }

        .footer {
            text-align: center;
            font-size: 10px;
            margin-top: 5px;
        }

        @media print {
            @page {
                margin: 0 auto;
                width: 80mm;
                height: 100% !important;
                sheet-size: 80mm 80mm;
            }

            .receipt-container {
                border: none;
                box-shadow: none;
            }

            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body onload="window.print();">
    <div class="receipt-container">
        <h1>
            @if ($enable_logo_in_print == 'yes')
                <img src="#" alt="Logo" style="width: 50px; height: auto;">
                Store Name
            @endif
        </h1>
        @if ($thermal_printer_style == 'with_arabic')
            <h1> اسم المتجر </h1>
        @endif
        <h3 class="divider">Invoice
            @if ($thermal_printer_style == 'with_arabic')
                | {{ __('lang.invoice', [], 'ar') }}
            @endif
        </h3>
        @if ($thermal_printer_style == 'with_arabic')
            <table class="table">
                <tr>
                    <td class="text-left"><b>Invoice No</b> <br></td>
                    <td colspan="2" class="text-left"><b>{{ $sale->invoice_no }}</b></td>
                    <td class="nowrap text-right"> <b>{{ __('lang.invoice_no', [], 'ar') }}</b> </td>
                </tr>
            </table>
            <table class="table">
                <tr>
                    <td class="nowrap text-left" width="28%"><b>Date</b></td>
                    <td colspan="2" class="text-left"><b>{{ systemDate($sale->date) }}</b></td>
                    <td class="nowrap text-right"> <b>{{ __('lang.date', [], 'ar') }}</b> </td>
                </tr>
            </table>
            <table class="table">
                <tr>
                    <td class="nowrap text-left" width="28%"><b>Customer</b></td>
                    <td colspan="2" class="text-left">
                        <b>
                            @if ($sale->customer_name)
                                {{ ucFirst($sale->customer_name) }}
                            @else
                                {{ ucFirst($sale->account?->name) }}
                            @endif
                        </b>
                    </td>
                    <td class="nowrap text-right">
                        <b>{{ __('lang.customer', [], 'ar') }}</b>
                    </td>
                </tr>
            </table>
            <table class="table">
                <tr>
                    <td class="text-left"><b>Payment Mode</b> <br></td>
                    <td class="nowrap text-right">
                        <b>{{ __('lang.payment_mode', [], 'ar') }}</b>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" class="text-center"><b>{{ $payments }}</b></td>
                </tr>
            </table>
        @endif
        @if ($thermal_printer_style == 'english_only')
            <table class="table">
                <tr>
                    <td class="text-left"><b>Invoice No</b> <br></td>
                    <td class="text-left"><b>{{ $sale->invoice_no }}</b></td>
                </tr>
                <tr>
                    <td class="nowrap text-left"><b>Date</b></td>
                    <td class="text-left"><b>{{ systemDate($sale->date) }}</b></td>
                </tr>
                <tr>
                    <td class="nowrap text-left"><b>Customer</b></td>
                    <td class="text-left">
                        <b>
                            @if ($sale->customer_name)
                                {{ ucFirst($sale->customer_name) }}
                            @else
                                {{ ucFirst($sale->account?->name) }}
                            @endif
                        </b>
                    </td>
                </tr>
                <tr>
                    <td class="text-left"><b>Payment Mode</b> <br></td>
                    <td class="text-left"><b>Cash</b></td>
                </tr>
            </table>
        @endif
        <div class="divider"></div>
        <table class="table">
            <thead>
                @if ($thermal_printer_style == 'with_arabic')
                    <tr>
                        <th>#</th>
                        <th class="text-right"> Price <br> {{ __('lang.price', [], 'ar') }} </th>
                        <th class="text-right"> Qty <br> {{ __('lang.quantity', [], 'ar') }} </th>
                        <th class="text-right"> Amount <br> {{ __('lang.amount', [], 'ar') }} </th>
                    </tr>
                @endif
                @if ($thermal_printer_style == 'english_only')
                    <tr>
                        <th>#</th>
                        <th>Price</th>
                        <th>Qty</th>
                        <th>Amount</th>
                    </tr>
                @endif
            </thead>
            <tbody>
                @foreach ($sale->items as $item)
                    <tr>
                        <td colspan="4" class="text-left"><b>{{ $item->product->name }}</b></td>
                    </tr>
                    @if ($item->product->name_arabic)
                        <tr>
                            <td colspan="4" class="text-left"><b>{{ $item->product->name_arabic }}</b></td>
                        </tr>
                    @endif
                    <tr>
                        <td class="text-right"> <b>{{ $loop->iteration }}</b> </td>
                        <td class="text-right"> <b>{{ currency($item->unit_price) }}</b> </td>
                        <td class="text-right"> <b>{{ round($item->quantity, 3) }}</b> </td>
                        <td class="text-right"> <b>{{ currency($item->total) }}</b> </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="divider"></div>

        <table class="table">
            @if ($enable_total_quantity_in_print == 'yes')
                <tr>
                    <td class="text-left"><b>Total Qty</b></td>
                    <td class="text-right"><b>{{ round($sale->items()->sum('quantity'), 3) }}</b></td>
                    @if ($thermal_printer_style == 'with_arabic')
                        <td width="39%" class="text-right"> <b>{{ __('lang.total_quantity', [], 'ar') }}</b> </td>
                    @endif
                </tr>
            @endif
            @if ($enable_discount_in_print == 'yes')
                <tr>
                    <td class="text-left" width="39%"><b>Net Value</b></td>
                    <td class="text-right"><b>{{ currency($sale->total) }}</b></td>
                    @if ($thermal_printer_style == 'with_arabic')
                        <td width="39%" class="text-right"> <b>{{ __('lang.net_value', [], 'ar') }}</b> </td>
                    @endif
                </tr>
                <tr>
                    <td class="text-left" width="39%"><b>Discount</b></td>
                    <td class="text-right"><b>{{ currency($sale->other_discount + $sale->item_discount) }}</b></td>
                    @if ($thermal_printer_style == 'with_arabic')
                        <td width="39%" class="text-right"> <b>{{ __('lang.discount', [], 'ar') }}</b> </td>
                    @endif
                </tr>
            @endif
            <tr>
                <td class="text-left" width="39%"><b>Total</b></td>
                <td class="text-right"><b>{{ $sale->grand_total }}</b></td>
                @if ($thermal_printer_style == 'with_arabic')
                    <td width="39%" class="text-right"> <b>{{ __('lang.total', [], 'ar') }}</b> </td>
                @endif
            </tr>
            <tr>
                <td class="text-left" width="39%"><b>Paid</b></td>
                <td class="text-right"><b>{{ currency($sale->paid) }}</b></td>
                @if ($thermal_printer_style == 'with_arabic')
                    <td width="39%" class="text-right"> <b>{{ __('lang.paid', [], 'ar') }}</b> </td>
                @endif
            </tr>
            @if ($sale->balance)
                <tr>
                    <td class="text-left" width="39%"><b>Balance</b></td>
                    <td class="text-right"><b>{{ currency($sale->balance) }}</b></td>
                    @if ($thermal_printer_style == 'with_arabic')
                        <td width="39%" class="text-right"> <b>{{ __('lang.balance', [], 'ar') }}</b> </td>
                    @endif
                </tr>
            @endif
        </table>
        <div class="barcode">
            <img src="data:image/png;base64,{{ DNS1D::getBarcodePNG($barcode_string, 'C128') }}" alt="barcode" />
            <p> <b>{{ $barcode_string }}</b> </p>
        </div>
        <div class="footer">
            <div>
                <span class="text-left"> <b>Served By</b> </span> : <b>{{ $sale->createdUser->name }}</b>
                @if ($thermal_printer_style == 'with_arabic')
                    <span class="text-right">
                        <b>:{{ __('lang.served_by', [], 'ar') }}</b>
                    </span>
                @endif
            </div>
            <div>
                <b><?= date('D d-M-Y h:i A', strtotime($sale->updated_at)) ?></b></b>
            </div>
        </div>
        <div class="footer">
            <p> <b>{{ $thermal_printer_footer_english }}</b> </p>
            @if ($thermal_printer_style == 'with_arabic')
                <b>
                    <p dir="rtl">{{ $thermal_printer_footer_arabic }}</p>
                </b>
            @endif
        </div>
    </div>
</body>

<script>
    window.onFocus = function() {
        window.close();
    };
</script>

</html>

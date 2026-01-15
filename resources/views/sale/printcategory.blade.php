<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        Customer Invoice
        @if ($thermal_printer_style == 'with_arabic')
            | فاتورة العميل
        @endif
    </title>
    <style>
        h1,
        h2,
        h3 {
            margin: 5px 0;
            text-align: center;
            font-size: 15px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #000;
        }

        body {
            font-family: 'Arial', 'Courier New', monospace;
            line-height: 1.2;
            font-size: 14px;
            margin: 0 auto;
            width: 80mm;
            background-color: #fff;
            padding: 5px 10px;
            color: #000;
        }

        @page {
            margin: 0;
            size: 80mm auto;
        }

        .receipt-container {
            background-color: #fff;
            width: 100%;
            padding: 5px 5px;
        }

        h1,
        h2,
        h3 {
            margin: 3px 0;
            text-align: center;
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: black;
        }

        .divider {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }

        .store-info {
            text-align: center;
            margin-bottom: 8px;
        }

        .invoice-header {
            background-color: transparent;
            border-radius: 4px;
            padding: 5px;
            margin-bottom: 8px;
            border-left: 2px solid #000;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
            font-size: 11px;
        }

        .nowrap {
            white-space: nowrap !important;
        }

        .table th,
        .table td {
            border: 1px solid #000;
            padding: 3px 3px;
            text-align: center;
        }

        .table th {
            background-color: transparent;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #000;
        }

        .table-striped tr:nth-child(even) {
            background-color: transparent;
        }

        .item-name {
            font-weight: 700;
            color: #000;
            font-size: 11px;
        }

        .item-description {
            font-size: 9px;
            color: #000;
            font-style: italic;
            margin-top: 1px;
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
            margin: 8px 0;
            padding: 5px;
            background: transparent;
            border: 1px solid #000;
        }

        .barcode img {
            max-width: 100%;
            height: auto;
        }

        .barcode p {
            margin: 2px 0 0;
            font-size: 10px;
            color: #000;
            letter-spacing: 0.3px;
        }

        .qrcode {
            text-align: center;
            margin: 8px 0;
        }

        .qrcode img {
            max-width: 80px;
            height: auto;
        }

        .codes-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 8px 0;
        }

        .codes-container .barcode {
            flex: 1;
            margin: 0 3px;
        }

        .codes-container .qrcode {
            max-width: 30%;
            margin: 0 3px;
        }

        .footer {
            text-align: center;
            font-size: 10px;
            margin-top: 8px;
            padding: 6px 0;
            border-top: 1px dashed #000;
            color: #000;
        }

        .thank-you {
            text-align: center;
            font-size: 12px;
            font-weight: 700;
            margin: 8px 0 5px;
            color: #000;
        }

        .policies {
            font-size: 11px;
            color: #000;
            text-align: center;
            margin: 5px 0;
            padding: 5px;
            background-color: transparent;
            border: 1px solid #000;
            line-height: 1.2;
        }

        .meta-info {
            font-size: 10px;
            color: #000;
            text-align: right;
            margin-top: 3px;
        }

        .highlight-box {
            background-color: transparent;
            border: 1px solid #000;
            padding: 5px;
            margin: 5px 0;
        }

        .payment-badge {
            display: inline-block;
            background-color: transparent;
            color: #000;
            font-size: 10px;
            padding: 2px 5px;
            border: 1px solid #000;
            margin: 1px;
        }

        .payment-badge.cash {
            background-color: transparent;
            color: #000;
        }

        .payment-badge.card {
            background-color: transparent;
            color: #000;
        }

        @media print {
            @page {
                margin: 0 auto;
                width: 80mm;
                height: 100% !important;
                sheet-size: 80mm auto;
            }

            .receipt-container {
                border: none;
                box-shadow: none;
            }

            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                color-adjust: exact;
                color: #000 !important;
                background: #fff !important;
            }

            .no-print {
                display: none;
            }

            /* Force all elements to use black color only */
            * {
                color: #000 !important;
                background-color: transparent !important;
                border-color: #000 !important;
            }

            /* Keep white background only for body and main container */
            body,
            .receipt-container {
                background-color: #fff !important;
            }

            .table th {
                background-color: transparent !important;
                color: #000 !important;
            }

            .table td,
            .table th {
                border-color: #000 !important;
                color: #000 !important;
            }

            .highlight-box {
                background-color: transparent !important;
                border-color: #000 !important;
                color: #000 !important;
            }

            .payment-badge {
                background-color: transparent !important;
                color: #000 !important;
                border: 1px solid #000 !important;
            }

            .payment-badge.cash,
            .payment-badge.card {
                background-color: transparent !important;
                color: #000 !important;
                border: 1px solid #000 !important;
            }

            .barcode {
                background: transparent !important;
                border: 1px solid #000 !important;
            }

            .footer {
                border-top: 1px dashed #000 !important;
                color: #000 !important;
            }

            .divider {
                border-top: 1px dashed #000 !important;
            }
        }
    </style>
</head>

<body onload="window.print();">
    <div class="receipt-container">
        <div class="store-info">
            @if ($enable_logo_in_print == 'yes')
                <img src="{{ cache('logo') }}" alt="Logo" style="width: 70%; max-width: 80px; margin-bottom: 3px;">
            @endif
            <h3>
                {{ $sale->branch?->location }}
            </h3>
            <div style="font-size: 13px; margin-top: 2px;">
                <strong>Mobile: {{ $sale->branch?->mobile }} </strong>
                @if ($sale->branch?->email)
                    <br><strong>Email:</strong> {{ $sale->branch?->email }}
                @endif
                @if ($gst_no)
                    <br><strong>GST:</strong> {{ $gst_no }}
                @endif
            </div>
        </div>

        <div class="divider"></div>

        <div class="invoice-header">
            <h3 style="margin: 2px 0;">
                Invoice
                @if ($thermal_printer_style == 'with_arabic')
                    | {{ __('lang.invoice', [], 'ar') }}
                @endif
            </h3>
        </div>
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
                    <td colspan="2" class="text-center">
                        @if (isset($payments))
                            @foreach ($payments as $payment)
                                <span class="payment-badge">{{ $payment['payment_method']['alias_name'] ?? $payment['payment_method']['name'] }} : {{ currency($payment['amount']) }}</span>
                            @endforeach
                        @endif
                    </td>
                </tr>
            </table>
        @endif
        @if ($thermal_printer_style == 'english_only')
            <table class="table table-striped">
                <tr>
                    <td class="text-left"><b>Invoice No</b></td>
                    <td class="text-left"><b>{{ $sale->invoice_no }}</b></td>
                </tr>
                <tr>
                    <td class="nowrap text-left"><b>Date</b></td>
                    <td class="text-left"><b>{{ systemDate($sale->date) }}</b></td>
                </tr>
                <tr>
                    <td class="nowrap text-left"><b>Time</b></td>
                    <td class="text-left"><b>{{ date('h:i A', strtotime($sale->created_at)) }}</b></td>
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
                    <td class="text-left"><b>Payment Mode</b></td>
                    <td class="text-left">
                        @if (isset($payments))
                            @foreach ($payments as $payment)
                                <span class="payment-badge">{{ $payment['payment_method']['name'] }} : {{ currency($payment['amount']) }}</span>
                            @endforeach
                        @endif
                    </td>
                </tr>
            </table>
        @endif
        <div class="divider"></div>

        {{-- Enhanced Customer Measurements Section --}}
        @if(isset($customerMeasurements) && count($customerMeasurements) > 0)
            <div class="divider"></div>
            <table class="table table-striped table-hover table-sm align-middle">
                <thead>
                    <tr>
                        <th class="text-center">SL No</th>
                        <th>Measurement Category</th>
                        <th>Model</th>
                        <th>Size</th>
                        <th>Width</th>
                      
                        <th>Measurement</th>
                        <th class="text-end">Value</th>
                    </tr>
                </thead>
                <tbody>
                @php
                    $catIds = collect($customerMeasurements)->pluck('category_id')->unique()->filter()->values()->all();
                    $subcatIds = collect($customerMeasurements)->pluck('sub_category_id')->unique()->filter()->values()->all();
                    $categoryNames = $catIds ? \App\Models\MeasurementCategory::whereIn('id', $catIds)->pluck('name', 'id')->toArray() : [];
                    $subCategoryNames = $subcatIds ? \App\Models\MeasurementSubCategory::whereIn('id', $subcatIds)->pluck('name', 'id')->toArray() : [];
                    $grouped = collect($customerMeasurements)->groupBy(function($m) {
                        return $m['category_id'].'-'.$m['sub_category_id'];
                    });
                    $rowIndex = 1;
                @endphp
                @foreach ($grouped as $groupKey => $group)
                    @foreach ($group as $j => $measurement)
                        <tr>
                            <td>{{ $rowIndex++ }}</td>
                            @if ($j === 0)
                                <td rowspan="{{ count($group) }}">{{ $categoryNames[$measurement['category_id']] ?? '-' }}</td>
                                <td rowspan="{{ count($group) }}">{{ $subCategoryNames[$measurement['sub_category_id']] ?? '-' }}</td>
                                <td rowspan="{{ count($group) }}">{{ $measurement['size'] ?? '-' }}</td>
                                <td rowspan="{{ count($group) }}">{{ $measurement['width'] ?? '-' }}</td>
                              
                            @endif
                            <td><i class="fa fa-ruler me-1 text-secondary"></i> {{ $measurement['template']['name'] ?? '-' }}</td>
                            <td class="text-end fw-bold">{{ $measurement['value'] ?? '-' }}</td>
                        </tr>
                    @endforeach
                @endforeach
                </tbody>
            </table>
        @endif

        {{-- Items Table: Only show items for this category --}}
        @php
            // $categoryId should be available in the view context (passed from controller)
            $categoryItems = $sale->items()->whereNull('sale_combo_offer_id')->where('category_id', $categoryId)->get();
        @endphp
        <table class="table table-striped">
            <thead>
                @if ($thermal_printer_style == 'with_arabic')
                    <tr>
                        <th>#</th>
                        <th class="text-right"> Price <br> {{ __('lang.price', [], 'ar') }} </th>
                        <th class="text-right"> {{ $print_quantity_label == 'quantity' ? 'Qty' : 'Weight' }} <br> {{ __('lang.quantity', [], 'ar') }} </th>
                        <th class="text-right"> Amount <br> {{ __('lang.amount', [], 'ar') }} </th>
                    </tr>
                @endif
                @if ($thermal_printer_style == 'english_only')
                    <tr>
                        <th>#</th>
                        <th class="text-right">Price</th>
                        <th class="text-right">{{ $print_quantity_label == 'quantity' ? 'Qty' : 'Weight' }}</th>
                        <th class="text-right">Amount</th>
                    </tr>
                @endif
            </thead>
            <tbody>
                @foreach ($categoryItems as $item)
                    <tr>
                        <td colspan="4" class="text-left">
                            <div class="item-name">
                                @if ($print_item_label == 'product')
                                     {{ $item->product->name }}
                                @else
                                    {{ $item->product->code }}) {{ $item->product->mainCategory->name }}
                                @endif
                            </div>
                            @if ($item->product->description)
                                <div class="item-description">{{ Str::limit($item->product->description, 30) }}</div>
                            @endif
                        </td>
                    </tr>
                    @if ($thermal_printer_style == 'with_arabic')
                        @if ($print_item_label == 'product' && $item->product->name_arabic)
                            <tr>
                                <td colspan="4" class="text-right"><b>{{ $item->product->name_arabic }}</b></td>
                            </tr>
                        @elseif ($print_item_label == 'category' && optional($item->product->mainCategory)->name_arabic)
                            <tr>
                                <td colspan="4" class="text-right"><b>{{ optional($item->product->mainCategory)->name_arabic }}</b></td>
                            </tr>
                        @endif
                    @endif
                    <tr>
                        <td class="text-right"> <b>{{ $loop->iteration }}</b> </td>
                        @if ($enable_discount_in_print == 'yes')
                            <td class="text-right"> <b>{{ currency($item->unit_price) }}</b> </td>
                        @else
                            @php
                                $difference = $item->total - $item->effective_total;
                                $unit_price = round(($item->unit_price - $difference - $item->discount) / $item->quantity, 2);
                            @endphp
                            <td class="text-right"> <b>{{ currency($unit_price) }}</b> </td>
                        @endif
                        <td class="text-right"> <b>{{ round($item->quantity, 3) }}</b> </td>
                        @if ($enable_discount_in_print == 'yes')
                            <td class="text-right"> <b>{{ currency($item->total) }}</b> </td>
                        @else
                            <td class="text-right"> <b>{{ currency($item->effective_total) }}</b> </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="divider"></div>

        <table class="table">
            @if ($enable_total_quantity_in_print == 'yes')
                <tr>
                    <td class="text-left"><b>{{ $print_quantity_label == 'quantity' ? 'Total Qty' : 'Total Weight' }}</b></td>
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
                @if ($sale->other_discount + $sale->item_discount != 0)
                    @php
                        $total_discount = $sale->other_discount + $sale->item_discount;
                        $discount_percentage = $sale->total > 0 ? round(($total_discount / $sale->total) * 100, 2) : 0;
                    @endphp
                    <tr>
                        <td class="text-left" width="39%"><b>Discount</b></td>
                        <td class="text-right"><b>{{ currency($total_discount) }} ({{ $discount_percentage }}%)</b></td>
                        @if ($thermal_printer_style == 'with_arabic')
                            <td width="39%" class="text-right"> <b>{{ __('lang.discount', [], 'ar') }}</b> </td>
                        @endif
                    </tr>
                @endif
            @else
                @if ($sale->other_discount != 0)
                    @php
                        $discount_percentage = $sale->total > 0 ? round(($sale->other_discount / $sale->total) * 100, 2) : 0;
                    @endphp
                    <tr>
                        <td class="text-left" width="39%"><b>Discount</b></td>
                        <td class="text-right"><b>{{ currency($sale->other_discount) }} ({{ $discount_percentage }}%)</b></td>
                        @if ($thermal_printer_style == 'with_arabic')
                            <td width="39%" class="text-right"> <b>{{ __('lang.discount', [], 'ar') }}</b> </td>
                        @endif
                    </tr>
                @endif
            @endif
            @if ($sale->tax_amount != 0)
                <tr>
                    <td class="text-left" width="39%"><b>Tax</b></td>
                    <td class="text-right"><b>{{ currency($sale->tax_amount) }}</b></td>
                    @if ($thermal_printer_style == 'with_arabic')
                        <td width="39%" class="text-right"> <b>{{ __('lang.tax', [], 'ar') }}</b> </td>
                    @endif
                </tr>
            @endif
            @if ($sale->round_off != 0)
                <tr>
                    <td class="text-left" width="39%"><b>Round Off</b></td>
                    <td class="text-right"><b>{{ currency($sale->round_off) }}</b></td>
                    @if ($thermal_printer_style == 'with_arabic')
                        <td width="39%" class="text-right"> <b>{{ __('lang.round_off', [], 'ar') }}</b> </td>
                    @endif
                </tr>
            @endif
            <tr>
                <td class="text-left" width="39%"><b>Total</b></td>
                <td class="text-right"><b>{{ currency($sale->grand_total) }}</b></td>
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

        {{-- Enhanced Customer Measurements Section --}}
        <!-- @if(isset($customerMeasurements) && count($customerMeasurements) > 0)
            <div class="divider"></div>
            <table class="table table-striped table-hover table-sm align-middle">
                <thead>
                    <tr>
                        <th class="text-center">SL No</th>
                        <th>Measurement Category</th>
                        <th>Subcategory</th>
                        <th>Size</th>
                        <th>Width</th>
                        <th>Quantity</th>
                        <th>Measurement</th>
                        <th class="text-end">Value</th>
                    </tr>
                </thead>
                <tbody>
                @php
                    $catIds = collect($customerMeasurements)->pluck('category_id')->unique()->filter()->values()->all();
                    $subcatIds = collect($customerMeasurements)->pluck('sub_category_id')->unique()->filter()->values()->all();
                    $categoryNames = $catIds ? \App\Models\MeasurementCategory::whereIn('id', $catIds)->pluck('name', 'id')->toArray() : [];
                    $subCategoryNames = $subcatIds ? \App\Models\MeasurementSubCategory::whereIn('id', $subcatIds)->pluck('name', 'id')->toArray() : [];
                    $grouped = collect($customerMeasurements)->groupBy(function($m) {
                        return $m['category_id'].'-'.$m['sub_category_id'];
                    });
                    $rowIndex = 1;
                @endphp
                @foreach ($grouped as $groupKey => $group)
                    @foreach ($group as $j => $measurement)
                        <tr>
                            <td>{{ $rowIndex++ }}</td>
                            @if ($j === 0)
                                <td rowspan="{{ count($group) }}">{{ $categoryNames[$measurement['category_id']] ?? '-' }}</td>
                                <td rowspan="{{ count($group) }}">{{ $subCategoryNames[$measurement['sub_category_id']] ?? '-' }}</td>
                                <td rowspan="{{ count($group) }}">{{ $measurement['size'] ?? '-' }}</td>
                                <td rowspan="{{ count($group) }}">{{ $measurement['width'] ?? '-' }}</td>
                                <td rowspan="{{ count($group) }}">{{ $measurement['quantity'] ?? '-' }}</td>
                            @endif
                            <td><i class="fa fa-ruler me-1 text-secondary"></i> {{ $measurement['template']['name'] ?? '-' }}</td>
                            <td class="text-end fw-bold">{{ $measurement['value'] ?? '-' }}</td>
                        </tr>
                    @endforeach
                @endforeach
                </tbody>
            </table>
        @endif -->

        @if ($enable_barcode_in_print == 'yes')
            <div class="codes-container">
                <div class="barcode">
                    <img src="data:image/png;base64,{{ DNS1D::getBarcodePNG($barcode_string, 'C128') }}" alt="barcode" />
                    <p> <b>{{ $barcode_string }}</b> </p>
                </div>
                <div class="qrcode">
                    <img src="data:image/png;base64,{{ DNS2D::getBarcodePNG(url('/') . '/invoice/' . $sale->id, 'QRCODE', 2, 2) }}" alt="QR Code" />
                </div>
            </div>
        @endif
        <div class="highlight-box">
            <div>
                <span class="text-left"> <b>Served By</b> </span>: <b>{!! $sale->employeeNames() !!}</b>
                @if ($thermal_printer_style == 'with_arabic')
                    <span class="text-right">
                        <b>:{{ __('lang.served_by', [], 'ar') }}</b>
                    </span>
                @endif
            </div>
            <div style="margin-top: 2px;">
                <b><?= date('d-M-Y h:i A', strtotime($sale->updated_at)) ?></b>
            </div>
        </div>

        <div class="footer">
            <p style="font-weight: bold; margin-bottom: 5px;">{!! $thermal_printer_footer_english !!}</p>
            @if ($thermal_printer_style == 'with_arabic')
                <b>
                    <p dir="rtl">{!! $thermal_printer_footer_arabic !!}</p>
                </b>
            @endif
            <div style="margin-top: 4px; font-size: 9px; color: #777;">
                {{ date('d/m/Y h:i A') }}
            </div>
        </div>
    </div>
</body>

<script>
    window.onFocus = function() {
        window.close();
    };

    // Auto close after printing or after 60 seconds
    window.addEventListener('afterprint', function() {
        setTimeout(function() {
            window.close();
        }, 5000);
    });

    setTimeout(function() {
        window.close();
    }, 60000);
</script>

</html>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cutting Slip - {{ $order->order_no }}</title>
    <style>
        * {
            box-sizing: border-box;
        }

        html, body {
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #e8e8e8;
            color: #000;
        }

        body {
            padding: 16px;
        }

        /* Fit to A4 landscape: 297mm × 210mm */
        .print-container {
            width: 297mm;
            height: 210mm;
            max-width: 297mm;
            max-height: 210mm;
            margin: auto;
            border: 1px solid #000;
            padding: 10mm 12mm;
            box-sizing: border-box;
            background: #fff;
            overflow: hidden;
        }

        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 14px;
            border-bottom: 2px solid #000;
            padding-bottom: 8px;
        }

        .header-left h1 {
            margin: 0;
            font-size: 1.6rem;
            text-transform: uppercase;
        }

        .header-left p {
            margin: 2px 0;
            font-size: 1rem;
            font-weight: bold;
            color: #333;
        }

        .header-center {
            text-align: center;
        }

        .header-center h2 {
            margin: 0;
            font-size: 1.2rem;
            border-bottom: 1px solid #000;
            display: inline-block;
            padding-bottom: 2px;
        }

        .header-right {
            text-align: right;
            font-size: 0.9rem;
            font-weight: bold;
            line-height: 1.4;
        }

        .measure-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 10px;
        }

        .measure-box {
            flex: 0 0 auto;
            min-width: 90px;
            width: max-content;
            max-width: 100%;
            border: none;
            border-radius: 0;
            display: flex;
            flex-direction: column;
            align-items: stretch;
            gap: 0;
        }

        .measure-label {
            font-size: 0.65rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            white-space: nowrap;
            color: #555;
            margin-bottom: 2px;
            line-height: 1.2;
        }

        .measure-val {
            border: 1px solid #000;
            border-radius: 4px;
            background: #f8f8f8;
            min-height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.95rem;
            padding: 3px 5px;
            text-align: center;
        }

        .measure-val-inner {
            white-space: nowrap;
            text-align: center;
        }

        .measure-box--empty .measure-label,
        .measure-box--empty .measure-val {
            border-color: transparent;
            background: transparent;
        }

        .slip-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0 12px 0;
            font-size: 0.85rem;
        }

        .slip-table th {
            border: 1px solid #000;
            padding: 6px 5px;
            background-color: #f2f2f2;
            text-transform: capitalize;
            font-weight: bold;
        }

        .slip-table td {
            border: 1px solid #000;
            padding: 6px 6px;
            text-align: center;
        }

        .col-desc {
            text-align: left !important;
            width: 35%;
            font-weight: bold;
        }

        .highlight-yellow {
            background-color: #ffff00;
            font-weight: bold;
            padding: 2px 4px;
        }

        .info-bar {
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            border: 1px solid #000;
            margin-top: 8px;
            background: #fff;
        }

        .info-bar div {
            padding: 5px 4px;
            text-align: center;
            border-right: 1px solid #000;
            font-size: 0.7rem;
            font-weight: bold;
            background-color: #f9f9f9;
        }

        .info-values {
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            border: 1px solid #000;
            border-top: none;
            margin-bottom: 12px;
            min-height: 26px;
        }

        .info-values div {
            border-right: 1px solid #000;
            padding: 4px;
            text-align: center;
            font-weight: bold;
        }

        .bottom-section {
            display: flex;
            gap: 24px;
            margin-top: 12px;
        }

        .field-row {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            font-size: 0.9rem;
            font-weight: bold;
        }

        .field-input {
            flex: 1;
            border-bottom: 1px dashed #000;
            margin-left: 8px;
            min-height: 18px;
        }

        .qr-placeholder {
            width: 90px;
            height: 90px;
            border: 1px solid #eee;
            padding: 4px;
        }

        .signature-row {
            display: flex;
            justify-content: space-between;
            margin-top: 24px;
            padding-top: 8px;
            border-top: 1px solid #eee;
            font-size: 0.9rem;
            font-weight: bold;
        }

        @media print {
            @page {
                size: A4 landscape;
                margin: 0;
            }

            html, body {
                width: 297mm;
                height: 210mm;
                margin: 0;
                padding: 0;
                background-color: #fff;
                overflow: visible;
            }

            .print-container {
                width: 297mm;
                min-width: 297mm;
                height: 210mm;
                max-height: 210mm;
                margin: 0;
                border: none;
                padding: 10mm 12mm;
                border-radius: 0;
                overflow: visible;
            }

            .no-print {
                display: none !important;
            }

            /* Single-line display when printing */
            .measure-grid {
                grid-template-columns: repeat(auto-fill, minmax(80px, max-content));
            }

            .measure-box {
                min-width: min-content;
            }

            .measure-val {
                min-width: 0;
                overflow: visible;
            }

            .measure-val-inner {
                white-space: nowrap;
            }

            .slip-table td,
            .slip-table th {
                white-space: nowrap;
            }
        }
    </style>
</head>

<body>

    @php
        $items = $items ?? $order->items;
        $firstItem = $items->first();
        $activeMeasurements = $firstItem->category->activeMeasurements ?? collect();
        $sectionGroups = ['dimensions' => 'basic_body', 'components' => 'collar_cuff', 'styles' => 'specifications'];
        $common = [];
        $separate = [];
        foreach ($sectionGroups as $sectionId) {
            $fields = $activeMeasurements->where('section', $sectionId)->sortBy('sort_order');
            foreach ($fields as $m) {
                $key = $m->field_key;
                $values = $items->map(fn($item) => ($v = $item->$key ?? null) === '' ? null : $v)->unique()->values();
                if ($values->count() <= 1) {
                    $common[$key] = ['label' => $m->label, 'value' => $values->first()];
                } else {
                    $separate[$key] = [
                        'label' => $m->label,
                        'per_item' => $items->mapWithKeys(fn($item) => [$item->item_no => ($v = $item->$key ?? null) === '' ? null : $v])->all(),
                    ];
                }
            }
        }
        $itemsWithNotes = $items->filter(fn($item) => !empty(trim($item->tailoring_notes ?? '')));
    @endphp

    <div class="print-container">
        <div class="header">
            <div class="header-left">
                <p>{{ $order->customer_mobile ?? 'No Mobile' }}</p>
                <h1>{{ $order->customer_name }}</h1>
                <p>Work Order: {{ $order->order_no }}</p>
            </div>
            <div class="header-center">
                <h2>{{ strtoupper($firstItem->category->name ?? 'TAILORING') }} CUTTING SLIP</h2>
            </div>
            <div class="header-right">
                Order Date: {{ $order->order_date ? $order->order_date->format('d/m/Y') : '-' }}<br>
                Delivery Date: {{ $order->delivery_date ? $order->delivery_date->format('d/m/Y') : '-' }}
            </div>
        </div>

        {{-- Common measurements (same for all items in this category) — responsive grid by label/value --}}
        <div class="measure-grid">
            @foreach (collect($common)->filter(fn($e) => !empty($e['value'])) as $key => $entry)
                <div class="measure-box">
                    <div class="measure-label">{{ $entry['label'] }}</div>
                    <div class="measure-val"><span class="measure-val-inner">{{ $entry['value'] ?? '-' }}</span></div>
                </div>
            @endforeach
        </div>

        {{-- Per-item reference (e.g. Mar Size, Mar Model) --}}
        @if (count($separate) > 0)
            <div style="margin-bottom: 10px;">
                <table class="slip-table" style="margin: 0 0 10px 0;">
                    <thead>
                        <tr>
                            <th style="width: 10px;">#</th>
                            @foreach ($separate as $entry)
                                <th>{{ $entry['label'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
                            <tr>
                                <td style="font-weight: bold;">#{{ $item->item_no }}</td>
                                @foreach ($separate as $fieldKey => $entry)
                                    <td><b>{{ $item->$fieldKey ?? '-' }}</b> </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div class="measure-grid" style="grid-template-columns: 100fr 1fr;">
            <div class="field-row" style="margin-bottom: 0;">
                Notes:
                <div class="field-input" style="min-height: auto;">
                    @if ($itemsWithNotes->isNotEmpty())
                        @foreach ($itemsWithNotes as $item)
                            <span style="font-weight: bold;">#{{ $item->item_no }}</span>
                             {{ $item->tailoring_notes }}@if (!$loop->last) @endif
                        @endforeach
                    @else
                        -
                    @endif
                </div>
            </div>
        </div>

        <div style="text-align: right; margin-bottom: 6px;">
            <strong>Total Qty: {{ number_format(collect($items)->sum('quantity'), 1) }}</strong>
        </div>
        <table class="slip-table">
            <thead>
                <tr>
                    <th class="col-desc">Description</th>
                    <th>Barcode</th>
                    <th>Qty</th>
                    <th>Type</th>
                    <th>Model</th>
                    <th>Color</th>
                    <th>Used</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    <tr>
                        <td class="col-desc">{{ $item->product_name }}</td>
                        <td><b>{{ $item->product->barcode ?? '-' }}</b></td>
                        <td><b>{{ number_format($item->quantity, 1) }}</b></td>
                        <td><b>{{ $item->category->name ?? '-' }}</b></td>
                        <td><span class="highlight-yellow">{{ $item->categoryModel->name ?? 'Standard' }}</span></td>
                        <td><b>{{ $item->product_color ?? '-' }}</b></td>
                        <td><b></b></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="bottom-section">
            <div style="flex: 2;">
                <div class="field-row">Cutting Master: <div class="field-input">{{ $order->cutter->name ?? '' }}</div>
                </div>
                <div class="field-row">Tailor Name: <div class="field-input">{{ $firstItem->tailor->name ?? '' }}</div>
                </div>
                <div class="field-row" style="margin-top: 12px; gap: 32px;">
                    <label><input type="checkbox" {{ $order->status == 'confirmed' ? 'checked' : '' }}> Booking</label>
                    <label><input type="checkbox" {{ $order->status == 'completed' ? 'checked' : '' }}> Finished</label>
                    <label><input type="checkbox" {{ $order->status == 'delivered' ? 'checked' : '' }}> Delivered</label>
                </div>
            </div>
        </div>

        <div class="signature-row">
            <div>Prepared By: {{ $order->salesman->name ?? '' }}</div>
            <div>Approved By: {{ $order->customer_name }}</div>
        </div>
    </div>

    <div class="no-print" style="text-align: center; margin-top: 16px; padding-bottom: 32px;">
        <button onclick="window.print()" style="padding: 12px 30px; cursor: pointer; background: #000; color: #fff; border: none; border-radius: 5px; font-weight: bold; font-size: 1rem;">Print Cutting
            Slip</button>
    </div>

</body>

</html>

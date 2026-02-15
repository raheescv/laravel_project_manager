<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cutting Slip - {{ $order->order_no }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #fff;
            color: #000;
        }

        .print-container {
            width: 900px;
            margin: auto;
            border: 1px solid #000;
            padding: 25px;
            box-sizing: border-box;
            background: #fff;
        }

        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
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
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 8px;
            margin-bottom: 12px;
        }

        .measure-box {
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
            color: #555;
            margin-bottom: 3px;
            line-height: 1.2;
        }

        .measure-val {
            border: 1px solid #000;
            border-radius: 4px;
            background: #f8f8f8;
            min-height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.95rem;
        }

        .measure-box--empty .measure-label,
        .measure-box--empty .measure-val {
            border-color: transparent;
            background: transparent;
        }

        .slip-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 0.85rem;
        }

        .slip-table th {
            border: 1px solid #000;
            padding: 10px 5px;
            background-color: #f2f2f2;
            text-transform: capitalize;
            font-weight: bold;
        }

        .slip-table td {
            border: 1px solid #000;
            padding: 10px 8px;
            text-align: center;
        }

        .col-desc {
            text-align: left !important;
            width: 25%;
            font-weight: bold;
        }

        .highlight-yellow {
            background-color: #ffff00;
            font-weight: bold;
            padding: 2px 5px;
        }

        .info-bar {
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            border: 1px solid #000;
            margin-top: 10px;
            background: #fff;
        }

        .info-bar div {
            padding: 8px 4px;
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
            margin-bottom: 20px;
            min-height: 30px;
        }

        .info-values div {
            border-right: 1px solid #000;
            padding: 5px;
            text-align: center;
            font-weight: bold;
        }

        .bottom-section {
            display: flex;
            gap: 30px;
            margin-top: 20px;
        }

        .field-row {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            font-size: 0.9rem;
            font-weight: bold;
        }

        .field-input {
            flex: 1;
            border-bottom: 1px dashed #000;
            margin-left: 10px;
            min-height: 22px;
        }

        .qr-placeholder {
            width: 110px;
            height: 110px;
            border: 1px solid #eee;
            padding: 5px;
        }

        .signature-row {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
            padding-top: 10px;
            border-top: 1px solid #eee;
            font-size: 0.9rem;
            font-weight: bold;
        }

        @media print {
            body {
                padding: 0;
            }

            .print-container {
                border: 1px solid #000;
                width: 100%;
            }

            .no-print {
                display: none !important;
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
                <p>ID: {{ $order->order_no }}</p>
            </div>
            <div class="header-center">
                <h2>{{ strtoupper($firstItem->category->name ?? 'TAILORING') }} CUTTING SLIP</h2>
            </div>
            <div class="header-right">
                Order Date: {{ $order->order_date ? $order->order_date->format('d/m/Y') : '-' }}<br>
                Delivery Date: {{ $order->delivery_date ? $order->delivery_date->format('d/m/Y') : '-' }}
            </div>
        </div>

        {{-- Common measurements (same for all items in this category) --}}
        @foreach (collect($common)->chunk(6) as $chunk)
            <div class="measure-grid">
                @foreach ($chunk as $key => $entry)
                    @if ($entry['value'])
                        <div class="measure-box">
                            <div class="measure-label">{{ $entry['label'] }}</div>
                            <div class="measure-val">{{ $entry['value'] ?? '-' }}</div>
                        </div>
                    @endif
                @endforeach
            </div>
        @endforeach

        {{-- Per-item reference (e.g. Mar Size, Mar Model) --}}
        @if (count($separate) > 0)
            <div style="margin-bottom: 12px;">
                <table class="slip-table" style="margin: 0 0 12px 0;">
                    <thead>
                        <tr>
                            <th style="width: 120px;">Product No</th>
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
                                    <td>{{ $item->$fieldKey ?? '-' }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div class="measure-grid" style="grid-template-columns: 2fr 1fr;">
            <div class="field-row" style="margin-bottom: 0;">
                Notes:
                <div class="field-input" style="min-height: auto;">
                    @if ($itemsWithNotes->isNotEmpty())
                        @foreach ($itemsWithNotes as $item)
                            <span style="font-weight: bold;">#{{ $item->item_no }}</span> {{ $item->tailoring_notes }}@if (!$loop->last)
                                <br>
                            @endif
                        @endforeach
                    @else
                        -
                    @endif
                </div>
            </div>
            <div class="measure-box measure-box--empty">
                <div class="measure-label">&nbsp;</div>
                <div class="measure-val">&nbsp;</div>
            </div>
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
                        <td>{{ $item->product->barcode ?? '-' }}</td>
                        <td>{{ number_format($item->quantity, 1) }}</td>
                        <td>{{ $item->category->name ?? '-' }}</td>
                        <td><span class="highlight-yellow">{{ $item->categoryModel->name ?? 'Standard' }}</span></td>
                        <td>{{ $item->product_color ?? '-' }}</td>
                        <td></td>
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
                <div class="field-row" style="margin-top: 20px; gap: 40px;">
                    <label><input type="checkbox" {{ $order->status == 'confirmed' ? 'checked' : '' }}> Booking</label>
                    <label><input type="checkbox" {{ $order->status == 'completed' ? 'checked' : '' }}> Finished</label>
                    <label><input type="checkbox" {{ $order->status == 'delivered' ? 'checked' : '' }}> Delivered</label>
                </div>
            </div>
            <div style="flex: 1; text-align: right;">
                <div class="field-row">Remarks: <div class="field-input"></div>
                </div>
                <div style="display: inline-block; margin-top: 10px;">
                    <div class="qr-placeholder">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ $order->order_no }}-{{ $categoryId }}" alt="QR Code" width="100">
                    </div>
                </div>
            </div>
        </div>

        <div class="signature-row">
            <div>Prepared By: {{ $order->salesman->name ?? '' }}</div>
            <div>Approved By: {{ $order->customer_name }}</div>
        </div>
    </div>

    <div class="no-print" style="text-align: center; margin-top: 20px; padding-bottom: 40px;">
        <button onclick="window.print()" style="padding: 12px 30px; cursor: pointer; background: #000; color: #fff; border: none; border-radius: 5px; font-weight: bold; font-size: 1rem;">Print Cutting
            Slip</button>
    </div>

</body>

</html>

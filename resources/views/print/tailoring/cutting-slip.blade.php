<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cutting Slip - {{ $order->order_no }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        @page {
            size: A4 landscape;
            margin: 8mm;
        }

        html,
        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            background: #fff;
            color: #111;
            font-size: 14px;
            line-height: 1.35;
            font-weight: 700;
            text-rendering: geometricPrecision;
        }

        body {
            padding: 10px;
        }

        .sheet {
            width: 281mm;
            min-height: 194mm;
            max-height: 194mm;
            border: 1.6px solid #111;
            padding: 10px 12px;
            overflow: hidden;
        }

        .header {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            border-bottom: 2.2px solid #111;
            padding-bottom: 6px;
        }

        .header td {
            vertical-align: top;
        }

        .left-title {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 2px;
        }

        .subtle {
            color: #111;
            font-size: 12px;
        }

        .center-title {
            text-align: center;
            font-size: 15px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-top: 2px;
        }

        .right-meta {
            text-align: right;
            font-size: 10px;
            line-height: 1.5;
        }

        .section-title {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            font-weight: 700;
            margin-bottom: 5px;
            color: #1f2937;
        }

        .measure-grid {
            display: flex;
            flex-wrap: wrap;
            align-items: stretch;
            gap: 5px;
            margin-bottom: 8px;
        }

        .measure-cell {
            border: 1.4px solid #111;
            background: #fff;
            padding: 5px 6px;
            min-height: 28px;
            display: flex;
            align-items: center;
            width: max-content;
            max-width: 100%;
        }

        .measure-line {
            display: flex;
            align-items: center;
            gap: 4px;
            white-space: nowrap;
            font-size: 14px;
        }

        .measure-label {
            text-transform: uppercase;
            color: #111;
            font-weight: 700;
            flex: 0 0 auto;
            font-size: 11px;
        }

        .measure-value {
            font-weight: 700;
            color: #111;
            flex: 0 0 auto;
            white-space: nowrap;
            font-size: 15px;
        }

        .measure-line--stacked {
            flex-direction: column;
            align-items: flex-start;
            gap: 2px;
        }

        .std-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-bottom: 8px;
            border: 1.6px solid #111;
        }

        .std-table th {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: #fff;
            border-bottom: 1.4px solid #111;
            border-right: 1.2px solid #111;
            padding: 5px 4px;
            text-align: left;
        }

        .std-table td {
            border-top: 1.2px solid #111;
            border-right: 1.2px solid #111;
            padding: 5px 4px;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
            font-weight: 700;
        }

        .std-table th:last-child,
        .std-table td:last-child {
            border-right: none;
        }

        .ref-table {
            table-layout: fixed;
        }

        .ref-table th,
        .ref-table td {
            padding: 6px 7px;
            font-size: 13px;
            vertical-align: top;
            line-height: 1.25;
            white-space: normal;
        }

        .ref-table th {
            font-size: 12px;
            overflow-wrap: break-word;
            word-break: normal;
            hyphens: auto;
        }

        .ref-table td {
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .ref-col-item {
            width: 8%;
            text-align: center;
            white-space: nowrap;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .bold {
            font-weight: 700;
        }

        .notes-box {
            border: 1.4px dashed #111;
            background: #fff;
            padding: 6px 8px;
            min-height: 26px;
            margin-bottom: 8px;
            font-weight: 700;
        }

        .meta-strip {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .meta-strip td {
            border: 1.4px solid #111;
            padding: 5px 6px;
            font-size: 13px;
            width: 25%;
            font-weight: 700;
        }

        .bottom {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        .bottom td {
            vertical-align: top;
            width: 50%;
            padding-right: 10px;
        }

        .line-field {
            margin-bottom: 7px;
            font-size: 13px;
        }

        .line-value {
            border-bottom: 1.2px dashed #111;
            display: inline-block;
            min-width: 180px;
            padding-left: 5px;
            font-weight: 700;
        }

        .personnel-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }

        .personnel-lines {
            flex: 1 1 auto;
            min-width: 0;
        }

        .checklist {
            margin-top: 10px;
            font-size: 12px;
            display: inline-block;
        }

        .checklist span {
            margin-right: 18px;
            font-weight: 700;
        }

        .check-item {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-right: 18px;
        }

        .check-input {
            width: 13px;
            height: 13px;
            margin: 0;
            accent-color: #000;
            vertical-align: middle;
            border: 1.4px solid #111;
        }

        .rating-box {
            flex: 0 0 210px;
            width: 210px;
            margin-top: 10px;
            text-align: center;
        }

        .rating-box .label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #111;
            font-weight: 700;
            margin-bottom: 3px;
        }

        .rating-box .square {
            display: none;
        }

        .rating-stars {
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .rating-star {
            font-size: 24px;
            line-height: 1;
            color: #111;
            font-weight: 700;
        }

        .signatures {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .signatures td {
            width: 50%;
            vertical-align: bottom;
            padding-top: 16px;
        }

        .sign-line {
            border-top: 1.4px solid #111;
            margin: 0 20px;
            padding-top: 5px;
            text-align: center;
            font-size: 13px;
            color: #111;
            text-transform: uppercase;
            font-weight: 700;
        }

        .no-print {
            text-align: center;
            margin-top: 14px;
        }

        .no-print button {
            border: none;
            background: #111827;
            color: #fff;
            font-size: 13px;
            font-weight: 700;
            padding: 9px 20px;
            border-radius: 4px;
            cursor: pointer;
        }

        @media print {
            body {
                padding: 0;
            }

            .sheet {
                border: none;
                width: 281mm;
                min-height: 194mm;
                max-height: 194mm;
                padding: 0;
                overflow: hidden;
            }

            .no-print {
                display: none !important;
            }

            .measure-grid {
                gap: 4px;
            }
        }
    </style>
</head>

<body>
    @php
        $items = $items ?? $order->items;
        $firstItem = $items->first();
        $activeMeasurements = $firstItem?->category?->activeMeasurements ?? collect();
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
        $totalQty = collect($items)->sum('quantity');

        $bookingChecked = $order->status == 'confirmed';
        $finishedChecked = $order->status == 'completed';
        $deliveredChecked = $order->status == 'delivered';

        // Dynamic width allocation for per-item measurement columns
        $separateColumnWidths = [];
        if (count($separate) > 0) {
            $itemColWidth = 7; // compact fixed width for item index
            $remainingWidth = 100 - $itemColWidth;
            $weights = [];
            $contentMeta = [];

            foreach ($separate as $fieldKey => $entry) {
                $label = (string) ($entry['label'] ?? '');
                $labelLen = mb_strlen($label);
                $labelLongestToken = collect(preg_split('/\s+/', trim($label)) ?: [])->map(fn($part) => mb_strlen($part))->max() ?? 0;
                $maxValueLen = collect($items)->map(function ($item) use ($fieldKey) {
                    return mb_strlen((string) ($item->$fieldKey ?? ''));
                })->max() ?? 0;
                $maxValueTokenLen = collect($items)->map(function ($item) use ($fieldKey) {
                    $value = trim((string) ($item->$fieldKey ?? ''));
                    $parts = preg_split('/[\s\-\/]+/', $value) ?: [];

                    return collect($parts)->map(fn($part) => mb_strlen($part))->max() ?? 0;
                })->max() ?? 0;

                // Consider both TH and TD needs so short columns stay compact and long tokens get room.
                $contentMeta[$fieldKey] = [
                    'label' => $labelLen,
                    'label_token' => $labelLongestToken,
                    'value' => $maxValueLen,
                    'value_token' => $maxValueTokenLen,
                ];

                $headerNeed = max((int) round($labelLen * 0.6), (int) round($labelLongestToken * 1.4));
                $valueNeed = max((int) round($maxValueLen * 0.45), (int) round($maxValueTokenLen * 1.3));
                $combinedNeed = max($headerNeed, $valueNeed);

                if ($combinedNeed <= 8) {
                    $weights[$fieldKey] = 5; // compact columns (e.g. type/model short codes)
                } elseif ($combinedNeed <= 13) {
                    $weights[$fieldKey] = 8;
                } else {
                    $weights[$fieldKey] = max(10, $combinedNeed);
                }
            }

            $totalWeight = array_sum($weights) ?: 1;

            foreach ($weights as $fieldKey => $weight) {
                $width = ($weight / $totalWeight) * $remainingWidth;
                $meta = $contentMeta[$fieldKey];
                $isCompact = ($meta['value_token'] <= 6 && $meta['label_token'] <= 8);
                $minWidth = $isCompact ? 6 : 8;
                $maxWidth = ($meta['value_token'] >= 16 || $meta['value'] >= 24) ? 42 : 36;
                $separateColumnWidths[$fieldKey] = max($minWidth, min($maxWidth, round($width, 2)));
            }
        }
    @endphp

    <div class="sheet">
        <table class="header">
            <tr>
                <td style="width: 34%;">
                    <div class="left-title">{{ $order->customer_name ?: 'Walk-in Customer' }}</div>
                    <div class="subtle">Mobile: {{ $order->customer_mobile ?? '-' }}</div>
                    <div class="subtle">Work Order: {{ $order->order_no }}</div>
                </td>
                <td style="width: 32%;">
                    <div class="center-title">{{ strtoupper($firstItem?->category?->name ?? 'TAILORING') }} CUTTING SLIP</div>
                </td>
                <td style="width: 34%;" class="right-meta">
                    <div><span class="bold">Order Date:</span> {{ $order->order_date ? $order->order_date->format('d/m/Y') : '-' }}</div>
                    <div><span class="bold">Delivery:</span> {{ $order->delivery_date ? $order->delivery_date->format('d/m/Y') : '-' }}</div>
                    <div><span class="bold">Printed:</span> {{ now()->format('d/m/Y h:i A') }}</div>
                </td>
            </tr>
        </table>

        @if (collect($common)->filter(fn($e) => !empty($e['value']))->isNotEmpty())
            <div class="section-title">Common Measurements</div>
            <div class="measure-grid">
                @foreach (collect($common)->filter(fn($e) => !empty($e['value'])) as $entry)
                    <div class="measure-cell">
                        <div class="measure-line measure-line--stacked">
                            <span class="measure-label">{{ $entry['label'] }}</span>
                            <span class="measure-value">{{ $entry['value'] ?? '-' }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @if (count($separate) > 0)
            <div class="section-title">Per Item Measurement Reference</div>
            <table class="std-table ref-table">
                <thead>
                    <tr>
                        <th class="ref-col-item" style="width: {{ $itemColWidth ?? 7 }}%;">Item</th>
                        @foreach ($separate as $fieldKey => $entry)
                            @php
                                $width = $separateColumnWidths[$fieldKey] ?? 12;
                            @endphp
                            <th style="width: {{ $width }}%;">{{ $entry['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $item)
                        <tr>
                            <td class="ref-col-item bold">#{{ $item->item_no }}</td>
                            @foreach ($separate as $fieldKey => $entry)
                                @php
                                    $width = $separateColumnWidths[$fieldKey] ?? 12;
                                @endphp
                                <td class="bold" style="width: {{ $width }}%;">{{ $item->$fieldKey ?? '-' }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <div class="section-title">Notes</div>
        <div class="notes-box">
            @if ($itemsWithNotes->isNotEmpty())
                @foreach ($itemsWithNotes as $item)
                    <span class="bold">#{{ $item->item_no }}</span> {{ $item->tailoring_notes }}@if (!$loop->last)
                        |
                    @endif
                @endforeach
            @else
                -
            @endif
        </div>

        <table class="meta-strip">
            <tr>
                <td><span class="bold">Branch:</span> {{ $order->branch->name ?? '-' }}</td>
                <td style="text-align: right"><span class="bold">Total Quantity: {{ number_format((float) $totalQty, 2) }}</span></td>
            </tr>
        </table>

        <div class="section-title">Cutting Items</div>
        <table class="std-table">
            <thead>
                <tr>
                    <th style="width: 21%;">Description</th>
                    <th style="width: 12%;">Barcode</th>
                    <th style="width: 8%;" class="text-right">Qty</th>
                    <th style="width: 13%;">Model</th>
                    <th style="width: 12%;">Type</th>
                    <th style="width: 10%;">Color</th>
                    <th style="width: 12%;">Used Qty</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    <tr>
                        <td class="bold">{{ $item->product_name }}</td>
                        <td>{{ $item->product->barcode ?? '-' }}</td>
                        <td class="text-right bold">{{ number_format((float) $item->quantity, 2) }}</td>
                        <td class="bold">{{ $item->categoryModel->name ?? ($item->tailoring_category_model_name ?? 'Standard') }}</td>
                        <td class="bold">{{ $item->categoryModelType->name ?? ($item->tailoring_category_model_type_name ?? '-') }}</td>
                        <td>{{ $item->product_color ?? '-' }}</td>
                        <td></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="bottom">
            <tr>
                <td>
                    <div class="personnel-row">
                        <div class="personnel-lines">
                            <div class="line-field"><span class="bold">Cutting Master:</span><span class="line-value">{{ $order->cutter->name ?? '' }}</span></div>
                            <div class="line-field"><span class="bold">Tailor Name:</span><span class="line-value">{{ $firstItem?->latestTailorAssignment?->tailor?->name ?? '' }}</span></div>
                        </div>
                        <div class="rating-box">
                            <div class="label">Rating</div>
                            <div class="rating-stars">
                                <span class="rating-star">☆</span>
                                <span class="rating-star">☆</span>
                                <span class="rating-star">☆</span>
                                <span class="rating-star">☆</span>
                                <span class="rating-star">☆</span>
                            </div>
                        </div>
                    </div>
                    <div class="checklist">
                        <label class="check-item">
                            <input type="checkbox" class="check-input" {{ $bookingChecked ? 'checked' : '' }}>
                            <span>Booking</span>
                        </label>
                        <label class="check-item">
                            <input type="checkbox" class="check-input" {{ $finishedChecked ? 'checked' : '' }}>
                            <span>Finished</span>
                        </label>
                        <label class="check-item">
                            <input type="checkbox" class="check-input" {{ $deliveredChecked ? 'checked' : '' }}>
                            <span>Delivered</span>
                        </label>
                    </div>
                </td>
                <td>
                    <table class="signatures">
                        <tr>
                            <td>
                                <div class="sign-line">Prepared By: {{ $order->salesman->name ?? '' }}</div>
                            </td>
                            <td>
                                <div class="sign-line">Approved By: {{ $order->customer_name ?: 'Customer' }}</div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <div class="no-print">
        <button onclick="window.print()">Print Cutting Slip</button>
    </div>
</body>

</html>

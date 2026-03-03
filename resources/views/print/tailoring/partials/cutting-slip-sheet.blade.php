@php
    $firstItem = $items->first();
    $activeMeasurements = $firstItem?->category?->activeMeasurements ?? collect();
    $sectionGroups = ['dimensions' => 'basic_body', 'components' => 'collar_cuff', 'styles' => 'specifications'];
    $common = [];
    $separate = [];

    foreach ($sectionGroups as $sectionId) {
        $fields = $activeMeasurements->where('section', $sectionId)->sortBy('sort_order');
        foreach ($fields as $m) {
            $key = $m->field_key;
            $values = $items->map(fn ($item) => ($v = $item->$key ?? null) === '' ? null : $v)->unique()->values();
            if ($values->count() <= 1) {
                $common[$key] = ['label' => $m->label, 'value' => $values->first()];
            } else {
                $separate[$key] = [
                    'label' => $m->label,
                    'per_item' => $items->mapWithKeys(fn ($item) => [$item->item_no => ($v = $item->$key ?? null) === '' ? null : $v])->all(),
                ];
            }
        }
    }

    $itemsWithNotes = $items->filter(fn ($item) => ! empty(trim($item->tailoring_notes ?? '')));
    $totalQty = collect($items)->sum('quantity');

    $bookingChecked = $order->status == 'confirmed';
    $finishedChecked = $order->status == 'completed';
    $deliveredChecked = $order->status == 'delivered';

    $separateColumnWidths = [];
    if (count($separate) > 0) {
        $itemColWidth = 7;
        $remainingWidth = 100 - $itemColWidth;
        $weights = [];
        $contentMeta = [];

        foreach ($separate as $fieldKey => $entry) {
            $label = (string) ($entry['label'] ?? '');
            $labelLen = mb_strlen($label);
            $labelLongestToken = collect(preg_split('/\s+/', trim($label)) ?: [])->map(fn ($part) => mb_strlen($part))->max() ?? 0;
            $maxValueLen = collect($items)->map(function ($item) use ($fieldKey) {
                return mb_strlen((string) ($item->$fieldKey ?? ''));
            })->max() ?? 0;
            $maxValueTokenLen = collect($items)->map(function ($item) use ($fieldKey) {
                $value = trim((string) ($item->$fieldKey ?? ''));
                $parts = preg_split('/[\s\-\/]+/', $value) ?: [];

                return collect($parts)->map(fn ($part) => mb_strlen($part))->max() ?? 0;
            })->max() ?? 0;

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
                $weights[$fieldKey] = 5;
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

<div class="sheet{{ ($sheetClass ?? '') ? ' '.$sheetClass : '' }}">
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

    @if (collect($common)->filter(fn ($e) => ! empty($e['value']))->isNotEmpty())
        <div class="section-title">Common Measurements</div>
        <div class="measure-grid">
            @foreach (collect($common)->filter(fn ($e) => ! empty($e['value'])) as $entry)
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
                <span class="bold">#{{ $item->item_no }}</span> {{ $item->tailoring_notes }}@if (! $loop->last)
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

@push('styles')
    <style>
        .issue-view-page {
            --iv-accent: #4f46e5;
            --iv-muted: #64748b;
            --iv-bg: #f8fafc;
        }

        .issue-view-page .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .06);
        }

        .issue-view-page .card-header {
            font-size: 0.9rem;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #e2e8f0;
            background: #fff;
        }

        .issue-view-page .card-header .badge-id {
            background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
            color: var(--iv-accent);
            font-weight: 600;
        }

        .issue-view-page .btn-header {
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.8125rem;
            padding: 0.35rem 0.75rem;
        }

        .issue-view-page .btn-print {
            color: var(--iv-muted);
            border-color: #e2e8f0;
        }

        .issue-view-page .btn-print:hover {
            background: #f1f5f9;
            color: var(--iv-accent);
            border-color: #c7d2fe;
        }

        .issue-view-page .btn-edit {
            background: var(--iv-accent);
            color: #fff;
            border: none;
        }

        .issue-view-page .btn-edit:hover {
            background: #4338ca;
            color: #fff;
        }

        .issue-view-page .info-box {
            border-radius: 8px;
            padding: 0.9rem 1rem;
            border: 1px solid #e2e8f0;
            background: var(--iv-bg);
        }

        .issue-view-page .info-box.customer {
            border-left: 3px solid var(--iv-accent);
        }

        .issue-view-page .info-box.date {
            border-left: 3px solid #6366f1;
            background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);
        }

        .issue-view-page .info-box.date .date-meta {
            margin-top: 0.25rem;
            font-size: 0.74rem;
            color: #64748b;
            font-weight: 500;
        }

        .issue-view-page .info-box.balance {
            border-left: 3px solid #10b981;
        }

        .issue-view-page .info-box .label {
            font-size: 0.68rem;
            letter-spacing: 0.08em;
            color: var(--iv-muted);
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 0.35rem;
        }

        .issue-view-page .info-box .value {
            font-weight: 600;
            color: #1e293b;
            font-size: 1rem;
        }

        .issue-view-page .info-box .value-lg {
            font-weight: 700;
            font-size: 1.35rem;
            color: #059669;
        }

        .issue-view-page .remarks-box {
            background: #f1f5f9;
            border-radius: 8px;
            padding: 0.65rem 1rem;
            border: 1px solid #e2e8f0;
            margin-top: 0.75rem;
        }

        .issue-view-page .remarks-box .label {
            font-size: 0.68rem;
            letter-spacing: 0.08em;
            color: var(--iv-muted);
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 0.25rem;
        }

        .issue-view-page .items-card .card-header {
            padding: 0.6rem 1rem;
        }

        .issue-view-page .items-card .badge-count {
            background: var(--iv-accent);
            font-size: 0.75rem;
            font-weight: 500;
            padding: 0.25rem 0.6rem;
        }

        .issue-view-page .table-issue-view {
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.875rem;
        }

        .issue-view-page .table-issue-view thead th {
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.03em;
            color: var(--iv-muted);
            text-transform: uppercase;
            background: #f1f5f9;
            border: none;
            padding: 0.5rem 0.75rem;
        }

        .issue-view-page .table-issue-view thead th:first-child {
            border-radius: 6px 0 0 0;
            padding-left: 1rem;
        }

        .issue-view-page .table-issue-view thead th:last-child {
            padding-right: 1rem;
            border-radius: 0 6px 0 0;
        }

        .issue-view-page .table-issue-view tbody tr {
            transition: background .12s ease;
        }

        .issue-view-page .table-issue-view tbody tr:hover {
            background: #f8fafc;
        }

        .issue-view-page .table-issue-view tbody td {
            padding: 0.5rem 0.75rem;
            border-color: #f1f5f9;
        }

        .issue-view-page .table-issue-view tbody td:first-child {
            padding-left: 1rem;
            color: var(--iv-muted);
        }

        .issue-view-page .table-issue-view tbody td:last-child {
            padding-right: 1rem;
        }

        .issue-view-page .table-issue-view tbody tr.return-ref-row td {
            background: #ecfdf3;
            border-top-color: #d1fae5;
            border-bottom-color: #d1fae5;
        }

        .issue-view-page .table-issue-view tbody tr.return-ref-row td:first-child {
            color: #047857;
            font-weight: 700;
        }

        .issue-view-page .table-issue-view tbody tr.return-ref-total-row td {
            background: #f0fdf4;
            border-top: 1px dashed #86efac;
            color: #166534;
            font-weight: 600;
        }

        .issue-view-page .table-issue-view tbody tr.source-ref-row td {
            background: #eff6ff;
            border-top-color: #dbeafe;
            border-bottom-color: #dbeafe;
        }

        .issue-view-page .table-issue-view tbody tr.source-ref-row td:first-child {
            color: #1d4ed8;
            font-weight: 700;
        }

        .issue-view-page .table-issue-view tfoot th {
            font-size: 0.8rem;
            font-weight: 600;
            color: #334155;
            background: #f1f5f9;
            padding: 0.5rem 0.75rem;
            border: none;
            border-top: 1px solid #e2e8f0;
        }

        .issue-view-page .table-issue-view tfoot th:first-child {
            padding-left: 1rem;
            border-radius: 0 0 0 6px;
        }

        .issue-view-page .table-issue-view tfoot th:last-child {
            padding-right: 1rem;
            border-radius: 0 0 6px 0;
        }
    </style>
@endpush

<div class="issue-view-page">
    @if ($model)
        <div class="card shadow-sm mb-3">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0 d-flex align-items-center gap-2">
                    <span class="badge badge-id rounded-pill px-3 py-1">#{{ $model->id }}</span>
                    <i class="fa fa-file-text-o text-primary opacity-75" style="font-size: 1rem;"></i>
                    <span class="fw-semibold text-body">{{ucFirst( $model->type) }} details</span>
                </h5>
                <div class="d-flex gap-2">
                    @if ($model->type === 'issue')
                        <a href="{{ route('issue::create', ['type' => 'return', 'source_issue_id' => $model->id]) }}" class="btn btn-sm btn-outline-success btn-header">
                            <i class="fa fa-undo me-1"></i> Return Items
                        </a>
                    @endif
                    <a href="{{ route('issue::print', $model->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary btn-header btn-print">
                        <i class="fa fa-print me-1"></i> Print
                    </a>
                    <a href="{{ route('issue::edit', $model->id) }}" class="btn btn-sm btn-edit btn-header">
                        <i class="fa fa-pencil me-1"></i> Edit
                    </a>
                </div>
            </div>
            <div class="card-body p-3">
                <div class="row g-2 mb-0">
                    <div class="col-md-9">
                        <div class="info-box customer">
                            <div class="label d-flex align-items-center gap-1">
                                <i class="fa fa-user opacity-75" style="font-size: 0.7rem;"></i> Customer
                            </div>
                            <div class="value">{{ $model->account?->name ?? '—' }}</div>
                            @if ($model->account?->mobile)
                                <div class="small text-muted mt-1" style="font-size: 0.8rem;"><i class="fa fa-phone opacity-50 me-1"></i>{{ $model->account->mobile }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box date">
                            <div class="label d-flex align-items-center gap-1">
                                <i class="fa fa-calendar opacity-75" style="font-size: 0.7rem;"></i> Date
                            </div>
                            <div class="value">{{ systemDate($model->date) }}</div>
                            @if ($model->date)
                                <div class="date-meta">
                                    <i class="fa fa-clock-o me-1 opacity-75"></i>{{ relativeDayLabel($model->date) }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                @if ($model->remarks)
                    <div class="remarks-box">
                        <div class="label d-flex align-items-center gap-1">
                            <i class="fa fa-comment-o opacity-75" style="font-size: 0.7rem;"></i> Remarks
                        </div>
                        <p class="mb-0 text-body" style="font-size: 0.9rem;">{{ $model->remarks }}</p>
                    </div>
                @endif
                @if ($model->type === 'return' && $model->source_issue_id)
                    <div class="remarks-box mt-2">
                        <div class="label d-flex align-items-center gap-1">
                            <i class="fa fa-link opacity-75" style="font-size: 0.7rem;"></i> Source Issue
                        </div>
                        <p class="mb-0 text-body" style="font-size: 0.9rem;">
                            #{{ $model->source_issue_id }}
                            @if ($model->sourceIssue?->date)
                                ({{ systemDate($model->sourceIssue->date) }})
                            @endif
                        </p>
                    </div>
                @endif
            </div>
        </div>

        <div class="card shadow-sm items-card overflow-hidden">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0 small text-uppercase fw-semibold d-flex align-items-center gap-2" style="color: var(--iv-muted); letter-spacing: 0.05em;">
                    <i class="fa fa-cubes" style="color: var(--iv-accent);"></i> Items
                </h5>
                <span class="badge badge-count rounded-pill"><i class="fa fa-list me-1"></i>{{ $model->items->count() }} {{ $model->items->count() === 1 ? 'item' : 'items' }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-sm table-issue-view mb-0">
                        <thead>
                            <tr>
                                <th><i class="fa fa-hashtag text-muted me-1" style="font-size: 0.65rem;"></i>#</th>
                                @if ($model->type === 'return')
                                    <th><i class="fa fa-list-ol text-muted me-1" style="font-size: 0.65rem;"></i>Source Order</th>
                                    <th><i class="fa fa-link text-muted me-1" style="font-size: 0.65rem;"></i>Source Item ID</th>
                                @endif
                                <th><i class="fa fa-barcode text-muted me-1" style="font-size: 0.65rem;"></i>Inventory ID</th>
                                <th><i class="fa fa-cube text-muted me-1" style="font-size: 0.65rem;"></i>Product</th>
                                <th class="text-end"><i class="fa fa-arrow-up text-muted me-1" style="font-size: 0.65rem;"></i>Qty Out</th>
                                <th class="text-end"><i class="fa fa-arrow-down text-muted me-1" style="font-size: 0.65rem;"></i>Qty In</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($model->items as $item)
                                @php
                                    $returnRefs = $model->type === 'issue' ? $item->returnedItems->filter(fn($r) => $r->issue?->type === 'return') : collect();
                                    $sourceRef = $model->type === 'return' ? $item->sourceIssueItem : null;
                                @endphp
                                <tr>
                                    <td class="text-muted">{{ $loop->iteration }}</td>
                                    @if ($model->type === 'return')
                                        <td class="text-muted">{{ $item->source_item_order ?? '—' }}</td>
                                        <td class="text-muted">#{{ $item->source_issue_item_id ?? '—' }}</td>
                                    @endif
                                    <td class="text-muted">#{{ $item->inventory_id ?? '—' }}</td>
                                    <td>
                                        <div class="d-flex align-items-start gap-2">
                                            @if ($item->product?->thumbnail)
                                                <img src="{{ url($item->product->thumbnail) }}" alt="{{ $item->product->name }}" class="rounded border" style="width: 40px; height: 40px; object-fit: cover; flex-shrink: 0;">
                                            @endif
                                            <div>
                                        <span class="fw-medium">{{ $item->product?->name ?? '—' }}</span>
                                        <div class="small text-muted mt-1">
                                            @if ($item->product?->code)
                                                <span class="me-2">Code: {{ $item->product->code }}</span>
                                            @endif
                                            @if ($item->inventory?->barcode)
                                                <span class="me-2">Barcode: {{ $item->inventory->barcode }}</span>
                                            @endif
                                            @if ($item->inventory?->batch)
                                                <span>Batch: {{ $item->inventory->batch }}</span>
                                            @endif
                                        </div>
                                        @if ($model->type === 'return' && $item->sourceIssueItem?->issue_id)
                                            <div class="small text-muted">
                                                Source Issue: #{{ $item->sourceIssueItem->issue_id }}
                                            </div>
                                        @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end">{{ $item->quantity_out > 0 ? currency($item->quantity_out) : '—' }}</td>
                                    <td class="text-end">{{ $item->quantity_in > 0 ? currency($item->quantity_in) : '—' }}</td>
                                </tr>
                                @if ($model->type === 'issue' && $returnRefs->isNotEmpty())
                                    @foreach ($returnRefs as $ref)
                                        <tr class="return-ref-row">
                                            <td>↳</td>
                                            <td class="text-muted">#{{ $item->inventory_id ?? '—' }}</td>
                                            <td>
                                                <span class="fw-semibold text-success">Returned Item</span>
                                                <span class="ms-1">Ref</span>
                                                <a href="{{ route('issue::view', $ref->issue_id) }}" class="text-decoration-none fw-semibold ms-1">#{{ $ref->issue_id }}</a>
                                                <div class="small text-muted">
                                                    Date: {{ $ref->issue?->date ? systemDate($ref->issue->date) : '—' }}
                                                </div>
                                            </td>
                                            <td class="text-end">—</td>
                                            <td class="text-end text-success fw-semibold">{{ number_format((float) $ref->quantity_in, 2) }}</td>
                                        </tr>
                                    @endforeach
                                    <tr class="return-ref-total-row">
                                        <td></td>
                                        <td></td>
                                        <td>Total Returned For This Item</td>
                                        <td class="text-end">—</td>
                                        <td class="text-end">{{ number_format((float) $returnRefs->sum('quantity_in'), 2) }}</td>
                                    </tr>
                                @endif
                                @if ($model->type === 'return' && $sourceRef)
                                    <tr class="source-ref-row">
                                        <td>↳</td>
                                        <td class="text-muted">{{ $item->source_item_order ?? '—' }}</td>
                                        <td class="text-muted">#{{ $item->source_issue_item_id ?? '—' }}</td>
                                        <td class="text-muted">#{{ $sourceRef->inventory_id ?? '—' }}</td>
                                        <td>
                                            <span class="fw-semibold text-primary">Source Issued Item</span>
                                            <div class="small text-muted">
                                                Ref:
                                                @if ($sourceRef->issue_id)
                                                    <a href="{{ route('issue::view', $sourceRef->issue_id) }}" class="text-decoration-none fw-semibold">#{{ $sourceRef->issue_id }}</a>
                                                @else
                                                    —
                                                @endif
                                                @if ($sourceRef->issue?->date)
                                                    | Date: {{ systemDate($sourceRef->issue->date) }}
                                                @endif
                                            </div>
                                            @if ($sourceRef->product?->name)
                                                <div class="small text-muted">
                                                    Product: {{ $sourceRef->product->name }}
                                                    @if ($sourceRef->product?->code)
                                                        ({{ $sourceRef->product->code }})
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                        <td class="text-end text-primary fw-semibold">{{ number_format((float) $sourceRef->quantity_out, 2) }}</td>
                                        <td class="text-end">—</td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="fw-semibold">
                                <th colspan="{{ $model->type === 'return' ? 5 : 3 }}" class="text-end"><i class="fa fa-calculator text-muted me-1"></i>Total</th>
                                <th class="text-end">{{ number_format($model->items->sum('quantity_out'), 2) }}</th>
                                <th class="text-end">{{ number_format($model->items->sum('quantity_in'), 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        @php
            $summaryRows = collect();

            if ($model->type === 'issue') {
                $summaryRows = $model->items
                    ->groupBy(fn($item) => (string) ($item->inventory_id ?? '0'))
                    ->map(function ($rows) {
                        $first = $rows->first();
                        $issued = (float) $rows->sum(fn($r) => (float) $r->quantity_out);
                        $returned = (float) $rows->sum(fn($r) => (float) $r->returnedItems->filter(fn($x) => $x->issue?->type === 'return')->sum('quantity_in'));

                        return [
                            'inventory_id' => $first->inventory_id,
                            'product_name' => $first->product?->name ?? '—',
                            'issued' => $issued,
                            'returned' => $returned,
                            'balance' => $issued - $returned,
                        ];
                    })
                    ->values();
            } else {
                $summaryRows = $model->items
                    ->groupBy(fn($item) => (string) ($item->inventory_id ?? '0'))
                    ->map(function ($rows) {
                        $first = $rows->first();
                        $issued = (float) $rows->sum(fn($r) => (float) ($r->sourceIssueItem?->quantity_out ?? 0));
                        $returned = (float) $rows->sum(fn($r) => (float) $r->quantity_in);

                        return [
                            'inventory_id' => $first->inventory_id,
                            'product_name' => $first->product?->name ?? ($first->sourceIssueItem?->product?->name ?? '—'),
                            'issued' => $issued,
                            'returned' => $returned,
                            'balance' => $issued - $returned,
                        ];
                    })
                    ->values();
            }
        @endphp

        <div class="card shadow-sm mt-3 overflow-hidden">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0 small text-uppercase fw-semibold d-flex align-items-center gap-2" style="color: var(--iv-muted); letter-spacing: 0.05em;">
                    <i class="fa fa-bar-chart" style="color: var(--iv-accent);"></i> Item Summary
                </h5>
                <span class="small text-muted">Total Issued vs Returned</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-sm mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Inventory ID</th>
                                <th>Product</th>
                                <th class="text-end">Total Issued</th>
                                <th class="text-end">Total Returned</th>
                                <th class="text-end">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($summaryRows as $row)
                                <tr>
                                    <td class="text-muted">{{ $loop->iteration }}</td>
                                    <td class="text-muted">#{{ $row['inventory_id'] ?? '—' }}</td>
                                    <td>{{ $row['product_name'] }}</td>
                                    <td class="text-end fw-semibold">{{ number_format((float) $row['issued'], 2) }}</td>
                                    <td class="text-end text-success fw-semibold">{{ number_format((float) $row['returned'], 2) }}</td>
                                    <td class="text-end fw-semibold {{ (float) $row['balance'] < 0 ? 'text-danger' : 'text-primary' }}">{{ number_format((float) $row['balance'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-3">No summary data.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="fw-semibold bg-light">
                                <th colspan="3" class="text-end">Grand Total</th>
                                <th class="text-end">{{ number_format((float) $summaryRows->sum('issued'), 2) }}</th>
                                <th class="text-end text-success">{{ number_format((float) $summaryRows->sum('returned'), 2) }}</th>
                                <th class="text-end {{ (float) $summaryRows->sum('balance') < 0 ? 'text-danger' : 'text-primary' }}">{{ number_format((float) $summaryRows->sum('balance'), 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>

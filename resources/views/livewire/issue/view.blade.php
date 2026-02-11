@push('styles')
<style>
    .issue-view-page { --iv-accent: #4f46e5; --iv-muted: #64748b; --iv-bg: #f8fafc; }
    .issue-view-page .card { border: none; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
    .issue-view-page .card-header { font-size: 0.9rem; padding: 0.75rem 1rem; border-bottom: 1px solid #e2e8f0; background: #fff; }
    .issue-view-page .card-header .badge-id { background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%); color: var(--iv-accent); font-weight: 600; }
    .issue-view-page .btn-header { border-radius: 6px; font-weight: 500; font-size: 0.8125rem; padding: 0.35rem 0.75rem; }
    .issue-view-page .btn-print { color: var(--iv-muted); border-color: #e2e8f0; }
    .issue-view-page .btn-print:hover { background: #f1f5f9; color: var(--iv-accent); border-color: #c7d2fe; }
    .issue-view-page .btn-edit { background: var(--iv-accent); color: #fff; border: none; }
    .issue-view-page .btn-edit:hover { background: #4338ca; color: #fff; }
    .issue-view-page .info-box { border-radius: 8px; padding: 0.9rem 1rem; border: 1px solid #e2e8f0; background: var(--iv-bg); }
    .issue-view-page .info-box.customer { border-left: 3px solid var(--iv-accent); }
    .issue-view-page .info-box.balance { border-left: 3px solid #10b981; }
    .issue-view-page .info-box .label { font-size: 0.68rem; letter-spacing: 0.08em; color: var(--iv-muted); font-weight: 600; text-transform: uppercase; margin-bottom: 0.35rem; }
    .issue-view-page .info-box .value { font-weight: 600; color: #1e293b; font-size: 1rem; }
    .issue-view-page .info-box .value-lg { font-weight: 700; font-size: 1.35rem; color: #059669; }
    .issue-view-page .remarks-box { background: #f1f5f9; border-radius: 8px; padding: 0.65rem 1rem; border: 1px solid #e2e8f0; margin-top: 0.75rem; }
    .issue-view-page .remarks-box .label { font-size: 0.68rem; letter-spacing: 0.08em; color: var(--iv-muted); font-weight: 600; text-transform: uppercase; margin-bottom: 0.25rem; }
    .issue-view-page .items-card .card-header { padding: 0.6rem 1rem; }
    .issue-view-page .items-card .badge-count { background: var(--iv-accent); font-size: 0.75rem; font-weight: 500; padding: 0.25rem 0.6rem; }
    .issue-view-page .table-issue-view { border-collapse: separate; border-spacing: 0; font-size: 0.875rem; }
    .issue-view-page .table-issue-view thead th { font-size: 0.7rem; font-weight: 600; letter-spacing: 0.03em; color: var(--iv-muted); text-transform: uppercase; background: #f1f5f9; border: none; padding: 0.5rem 0.75rem; }
    .issue-view-page .table-issue-view thead th:first-child { border-radius: 6px 0 0 0; padding-left: 1rem; }
    .issue-view-page .table-issue-view thead th:last-child { padding-right: 1rem; border-radius: 0 6px 0 0; }
    .issue-view-page .table-issue-view tbody tr { transition: background .12s ease; }
    .issue-view-page .table-issue-view tbody tr:hover { background: #f8fafc; }
    .issue-view-page .table-issue-view tbody td { padding: 0.5rem 0.75rem; border-color: #f1f5f9; }
    .issue-view-page .table-issue-view tbody td:first-child { padding-left: 1rem; color: var(--iv-muted); }
    .issue-view-page .table-issue-view tbody td:last-child { padding-right: 1rem; }
    .issue-view-page .table-issue-view tfoot th { font-size: 0.8rem; font-weight: 600; color: #334155; background: #f1f5f9; padding: 0.5rem 0.75rem; border: none; border-top: 1px solid #e2e8f0; }
    .issue-view-page .table-issue-view tfoot th:first-child { padding-left: 1rem; border-radius: 0 0 0 6px; }
    .issue-view-page .table-issue-view tfoot th:last-child { padding-right: 1rem; border-radius: 0 0 6px 0; }
</style>
@endpush

<div class="issue-view-page">
    @if ($model)
    <div class="card shadow-sm mb-3">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0 d-flex align-items-center gap-2">
                <span class="badge badge-id rounded-pill px-3 py-1">#{{ $model->id }}</span>
                <i class="fa fa-file-text-o text-primary opacity-75" style="font-size: 1rem;"></i>
                <span class="fw-semibold text-body">Issue details</span>
            </h5>
            <div class="d-flex gap-2">
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
                <div class="col-md-8">
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
                <div class="col-md-4">
                    <div class="info-box balance">
                        <div class="label d-flex align-items-center gap-1">
                            <i class="fa fa-calculator opacity-75" style="font-size: 0.7rem;"></i> Balance (Out − In)
                        </div>
                        <div class="value-lg">{{ number_format($model->balance ?? 0, 2) }}</div>
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
                            <th><i class="fa fa-cube text-muted me-1" style="font-size: 0.65rem;"></i>Product</th>
                            <th><i class="fa fa-calendar text-muted me-1" style="font-size: 0.65rem;"></i>Date</th>
                            <th class="text-end"><i class="fa fa-arrow-up text-muted me-1" style="font-size: 0.65rem;"></i>Qty Out</th>
                            <th class="text-end"><i class="fa fa-arrow-down text-muted me-1" style="font-size: 0.65rem;"></i>Qty In</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($model->items as $item)
                            <tr>
                                <td class="text-muted">{{ $loop->iteration }}</td>
                                <td>
                                    <span class="fw-medium">{{ $item->product?->name ?? '—' }}</span>
                                    @if($item->product?->code)
                                        <small class="text-muted">({{ $item->product->code }})</small>
                                    @endif
                                </td>
                                <td>{{ systemDate($item->date) }}</td>
                                <td class="text-end">{{ $item->quantity_out>0?currency($item->quantity_out):'—' }}</td>
                                <td class="text-end">{{ $item->quantity_in>0?currency($item->quantity_in):'—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="fw-semibold">
                            <th colspan="3" class="text-end"><i class="fa fa-calculator text-muted me-1"></i>Total</th>
                            <th class="text-end">{{ number_format($model->items->sum('quantity_out'), 2) }}</th>
                            <th class="text-end">{{ number_format($model->items->sum('quantity_in'), 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>

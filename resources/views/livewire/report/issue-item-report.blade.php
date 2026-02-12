@php
    use Carbon\Carbon;
@endphp
<div class="issue-item-report">
    <style>
        /* Report container */
        .issue-item-report { --report-radius: 0.75rem; --report-shadow: 0 1px 3px rgba(0,0,0,0.06); --report-shadow-lg: 0 10px 40px -10px rgba(0,0,0,0.1); }

        /* Title block */
        .issue-item-report .report-title-wrap { position: relative; padding-left: 1rem; }
        .issue-item-report .report-title-wrap::before { content: ''; position: absolute; left: 0; top: 50%; transform: translateY(-50%); width: 4px; height: 1.5rem; background: linear-gradient(180deg, #6366f1 0%, #8b5cf6 100%); border-radius: 4px; }
        .issue-item-report .report-title { font-weight: 700; letter-spacing: -0.03em; color: #0f172a; font-size: 1.25rem; }

        /* Rows selector */
        .issue-item-report .rows-select-wrap { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: var(--report-radius); padding: 0.25rem 0.5rem; }
        .issue-item-report .rows-select-wrap .form-select-sm { border: none; background: transparent; font-weight: 500; }

        /* Filters card */
        .issue-item-report .filters-card {
            background: linear-gradient(145deg, #ffffff 0%, #f8fafc 50%, #f1f5f9 100%);
            border: 1px solid #e2e8f0;
            box-shadow: var(--report-shadow);
            transition: box-shadow 0.25s ease, border-color 0.25s ease;
        }
        .issue-item-report .filters-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.06); border-color: #cbd5e1; }
        .issue-item-report .filters-label { color: #64748b; letter-spacing: 0.08em; font-size: 0.6875rem; font-weight: 600; }

        /* Form inputs */
        .issue-item-report .form-control-sm,
        .issue-item-report .form-select-sm {
            border-radius: 0.5rem;
            border: 1px solid #e2e8f0;
            background: #fff;
            font-size: 0.875rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .issue-item-report .form-control-sm:focus,
        .issue-item-report .form-select-sm:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12);
            outline: none;
        }
        .issue-item-report .form-label { font-size: 0.8125rem; color: #64748b; font-weight: 500; }

        /* Aging summary box */
        .issue-item-report .aging-summary-box {
            border: 1px solid #e2e8f0;
            box-shadow: var(--report-shadow);
            background: #fff;
        }
        .issue-item-report .aging-summary-header {
            background: linear-gradient(90deg, #f8fafc 0%, #f1f5f9 100%);
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.8125rem;
        }
        .issue-item-report .aging-summary-header .badge-date { background: rgba(99, 102, 241, 0.12); color: #4f46e5; font-weight: 500; font-size: 0.75rem; padding: 0.25rem 0.5rem; border-radius: 0.375rem; }

        /* Aging cards */
        .issue-item-report .aging-card {
            border: 1px solid #e2e8f0;
            border-left-width: 4px;
            border-left-style: solid;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            background: #fff;
        }
        .issue-item-report .aging-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px -8px rgba(0,0,0,0.12);
        }
        .issue-item-report .aging-card .aging-label { font-size: 0.75rem; font-weight: 600; color: #64748b; letter-spacing: 0.02em; }
        .issue-item-report .aging-card .aging-value { font-size: 1rem; font-weight: 700; color: #0f172a; }
        .issue-item-report .aging-card .aging-sub { font-size: 0.8125rem; color: #94a3b8; }
        .issue-item-report .aging-card.bucket-0 { border-left-color: #10b981; background: linear-gradient(135deg, #fff 0%, rgba(16, 185, 129, 0.04) 100%); }
        .issue-item-report .aging-card.bucket-1 { border-left-color: #f59e0b; background: linear-gradient(135deg, #fff 0%, rgba(245, 158, 11, 0.04) 100%); }
        .issue-item-report .aging-card.bucket-2 { border-left-color: #f97316; background: linear-gradient(135deg, #fff 0%, rgba(249, 115, 22, 0.04) 100%); }
        .issue-item-report .aging-card.bucket-3 { border-left-color: #ef4444; background: linear-gradient(135deg, #fff 0%, rgba(239, 68, 68, 0.04) 100%); }

        /* Data table */
        .issue-item-report .table-wrap {
            border: 1px solid #e2e8f0;
            border-radius: var(--report-radius);
            overflow: hidden;
            box-shadow: var(--report-shadow);
            background: #fff;
        }
        .issue-item-report .table-report {
            --bs-table-striped-bg: rgba(248, 250, 252, 0.6);
            --bs-table-hover-bg: rgba(99, 102, 241, 0.04);
            margin-bottom: 0;
        }
        .issue-item-report .table-report thead th {
            font-weight: 700;
            font-size: 0.6875rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #64748b;
            border-bottom: 2px solid #e2e8f0;
            padding: 0.875rem 1rem;
            background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%) !important;
        }
        .issue-item-report .table-report tbody td {
            padding: 0.75rem 1rem;
            vertical-align: middle;
            color: #334155;
            font-size: 0.875rem;
            border-bottom: 1px solid #f1f5f9;
        }
        .issue-item-report .table-report tbody tr:hover { background: var(--bs-table-hover-bg) !important; }
        .issue-item-report .table-report tbody tr:last-child td { border-bottom: none; }
        .issue-item-report .table-report tfoot th {
            font-size: 0.8125rem;
            font-weight: 700;
            padding: 0.875rem 1rem;
            border-top: 2px solid #cbd5e1;
            background: linear-gradient(180deg, #f1f5f9 0%, #e2e8f0 100%) !important;
            color: #475569;
        }
        .issue-item-report .table-report .link-issue { color: #4f46e5; font-weight: 500; transition: color 0.2s ease; }
        .issue-item-report .table-report .link-issue:hover { color: #6366f1; }

        /* Empty state */
        .issue-item-report .empty-state {
            padding: 4rem 1.5rem;
            color: #94a3b8;
            background: linear-gradient(180deg, #fafbfc 0%, #f8fafc 100%);
        }
        .issue-item-report .empty-state .empty-icon {
            width: 4rem; height: 4rem;
            margin: 0 auto 1rem;
            background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
            border-radius: 1rem;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.75rem;
            opacity: 0.8;
        }
        .issue-item-report .empty-state .empty-text { font-size: 0.9375rem; color: #64748b; }
    </style>

    <div class="card-header bg-white border-0 pb-0">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div class="report-title-wrap">
                <h2 class="report-title mb-0">Issue / Return Items by Product & Date</h2>
            </div>
            <div class="rows-select-wrap d-flex align-items-center gap-2">
                <label class="text-muted small mb-0">Rows</label>
                <select wire:model.live="limit" class="form-select form-select-sm" style="width: auto; min-width: 5rem;">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>

        <div class="filters-card rounded-3 mb-4">
            <div class="p-4">
                <p class="filters-label mb-3 text-uppercase">Filters</p>
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label text-muted small mb-1" for="from_date">From date</label>
                        <input type="date" wire:model.live="from_date" class="form-control form-control-sm" id="from_date">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label text-muted small mb-1" for="to_date">To date</label>
                        <input type="date" wire:model.live="to_date" class="form-control form-control-sm" id="to_date">
                    </div>
                    <div class="col-md-5" wire:ignore>
                        <label class="form-label text-muted small mb-1" for="product_id">Product</label>
                        {{ html()->select('product_id', [])->value('')->class('select-product_id-list')->attribute('type', 'product')->id('issue_report_product_id')->placeholder('All products') }}
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted small mb-1" for="search">Search</label>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control form-control-sm" id="search" placeholder="Product or customer…">
                    </div>
                </div>
            </div>
        </div>

        <div class="aging-summary-box rounded-3 overflow-hidden mb-4">
            <div class="aging-summary-header px-4 py-3 d-flex flex-wrap align-items-center gap-2">
                <span class="fw-semibold text-muted">Aging summary</span>
                <span class="badge-date">{{ $to_date ? Carbon::parse($to_date)->format('M d, Y') : 'Today' }}</span>
            </div>
            <div class="p-3 p-md-4">
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <div class="aging-card bucket-0 rounded-3 p-3 h-100">
                            <div class="aging-label mb-2">0–30 days</div>
                            <div class="aging-value">Out: {{ number_format($aging['0_30']['quantity_out'], 2) }}</div>
                            <div class="aging-sub mt-1">In: {{ number_format($aging['0_30']['quantity_in'], 2) }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="aging-card bucket-1 rounded-3 p-3 h-100">
                            <div class="aging-label mb-2">31–60 days</div>
                            <div class="aging-value">Out: {{ number_format($aging['31_60']['quantity_out'], 2) }}</div>
                            <div class="aging-sub mt-1">In: {{ number_format($aging['31_60']['quantity_in'], 2) }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="aging-card bucket-2 rounded-3 p-3 h-100">
                            <div class="aging-label mb-2">61–90 days</div>
                            <div class="aging-value">Out: {{ number_format($aging['61_90']['quantity_out'], 2) }}</div>
                            <div class="aging-sub mt-1">In: {{ number_format($aging['61_90']['quantity_in'], 2) }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="aging-card bucket-3 rounded-3 p-3 h-100">
                            <div class="aging-label mb-2">90+ days</div>
                            <div class="aging-value">Out: {{ number_format($aging['90_plus']['quantity_out'], 2) }}</div>
                            <div class="aging-sub mt-1">In: {{ number_format($aging['90_plus']['quantity_in'], 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card-body px-0 pb-0 pt-0">
        <div class="table-responsive table-wrap">
            <table class="table table-report table-striped table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="issue_items.id" label="#" /></th>
                        <th colspan="2"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="issue_items.date" label="Date" /></th>
                        <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="issue_items.issue_id" label="Issue ID" /></th>
                        <th>Customer</th>
                        <th>Product</th>
                        <th class="text-end">0–30</th>
                        <th class="text-end">31–60</th>
                        <th class="text-end">61–90</th>
                        <th class="text-end pe-4">90+</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $item)
                        @php
                            $asOf = $to_date ? Carbon::parse($to_date) : now();
                            $itemDate = $item->date ? Carbon::parse($item->date) : null;
                            $ageDays = $itemDate ? $itemDate->diffInDays($asOf, true) : null;
                            $bucket = $ageDays === null ? null : ($ageDays <= 30 ? '30' : ($ageDays <= 60 ? '60' : ($ageDays <= 90 ? '90' : '90plus')));
                            $rowQty = $item->quantity_out > 0 ? $item->quantity_out : $item->quantity_in;
                        @endphp
                        <tr>
                            <td class="ps-4 text-muted">{{ $item->id }}</td>
                            <td>{{ systemDate($item->date) }}</td>
                            <td>{{ Carbon::parse($item->date)->diffForHumans() }}</td>
                            <td>
                                @can('issue.view')
                                    <a href="{{ route('issue::view', $item->issue_id) }}" class="link-issue text-decoration-none">{{ $item->issue_id }}</a>
                                @else
                                    {{ $item->issue_id }}
                                @endcan
                            </td>
                            <td>{{ $item->issue?->account?->name ?? '—' }}</td>
                            <td>
                                {{ $item->product?->name ?? '—' }}
                                @if($item->product?->code)
                                    <small class="text-muted">({{ $item->product->code }})</small>
                                @endif
                            </td>
                            <td class="text-end">{{ $bucket === '30' ? number_format($rowQty, 2) : '—' }}</td>
                            <td class="text-end">{{ $bucket === '60' ? number_format($rowQty, 2) : '—' }}</td>
                            <td class="text-end">{{ $bucket === '90' ? number_format($rowQty, 2) : '—' }}</td>
                            <td class="text-end pe-4">{{ $bucket === '90plus' ? number_format($rowQty, 2) : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="empty-state text-center">
                                <div class="empty-icon">📋</div>
                                <p class="empty-text mb-0">No issue items found for the selected filters.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($data->isNotEmpty())
                    <tfoot class="table-group-divider">
                        <tr class="fw-semibold">
                            <th colspan="6" class="ps-4 text-end">Total</th>
                            <th class="text-end">{{ number_format($aging['0_30']['quantity_out'] + $aging['0_30']['quantity_in'], 2) }}</th>
                            <th class="text-end">{{ number_format($aging['31_60']['quantity_out'] + $aging['31_60']['quantity_in'], 2) }}</th>
                            <th class="text-end">{{ number_format($aging['61_90']['quantity_out'] + $aging['61_90']['quantity_in'], 2) }}</th>
                            <th class="text-end pe-4">{{ number_format($aging['90_plus']['quantity_out'] + $aging['90_plus']['quantity_in'], 2) }}</th>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
        <div class="mt-3 px-0">
            {{ $data->links() }}
        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#issue_report_product_id').on('change', function() {
                    @this.set('product_id', $(this).val() || '');
                });
            });
        </script>
    @endpush
</div>

<div class="issue-item-report">
    <style>
        .issue-item-report {
            --report-radius: 0.75rem;
            --report-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
        }

        .issue-item-report .report-title-wrap {
            position: relative;
            padding-left: 1rem;
        }

        .issue-item-report .report-title-wrap::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 1.5rem;
            background: linear-gradient(180deg, #0ea5e9 0%, #6366f1 100%);
            border-radius: 4px;
        }

        .issue-item-report .report-title {
            font-weight: 700;
            letter-spacing: -0.03em;
            color: #0f172a;
            font-size: 1.25rem;
        }

        .issue-item-report .rows-select-wrap {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: var(--report-radius);
            padding: 0.25rem 0.5rem;
        }

        .issue-item-report .rows-select-wrap .form-select-sm {
            border: none;
            background: transparent;
            font-weight: 500;
        }

        .issue-item-report .filters-card {
            background: linear-gradient(145deg, #ffffff 0%, #f8fafc 50%, #f1f5f9 100%);
            border: 1px solid #e2e8f0;
            box-shadow: var(--report-shadow);
        }

        .issue-item-report .filters-label {
            color: #64748b;
            letter-spacing: 0.08em;
            font-size: 0.6875rem;
            font-weight: 600;
        }

        .issue-item-report .form-control-sm,
        .issue-item-report .form-select-sm {
            border-radius: 0.5rem;
            border: 1px solid #e2e8f0;
            background: #fff;
            font-size: 0.875rem;
        }

        .issue-item-report .form-control-sm:focus,
        .issue-item-report .form-select-sm:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12);
            outline: none;
        }

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

        .issue-item-report .table-report tbody tr:last-child td {
            border-bottom: none;
        }

        .issue-item-report .qty-in {
            color: #047857;
            font-weight: 700;
        }

        .issue-item-report .qty-out {
            color: #b91c1c;
            font-weight: 700;
        }

        .issue-item-report .cell-with-icon {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .issue-item-report .icon-chip {
            width: 1.6rem;
            height: 1.6rem;
            border-radius: 0.45rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #eef2ff;
            color: #4f46e5;
            font-size: 0.875rem;
        }

        .issue-item-report .empty-state {
            padding: 4rem 1.5rem;
            color: #94a3b8;
            background: linear-gradient(180deg, #fafbfc 0%, #f8fafc 100%);
        }

        .issue-item-report .empty-state .empty-icon {
            width: 4rem;
            height: 4rem;
            margin: 0 auto 1rem;
            background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            opacity: 0.8;
        }

        .issue-item-report .empty-state .empty-text {
            font-size: 0.9375rem;
            color: #64748b;
        }
    </style>

    <div class="card-header bg-white border-0 pb-0">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div class="report-title-wrap">
                <h2 class="report-title mb-0">Issue Item Wise Report</h2>
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
                        {{ html()->select('product_id', [])->value('')->class('select-product_id-list')->attribute('type', 'product')->id('issue_item_wise_product_id')->placeholder('All products') }}
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted small mb-1" for="search">Search</label>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control form-control-sm" id="search" placeholder="Product or customer...">
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
                        <th class="ps-4"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="issues.date" label="Date" /></th>
                        <th class="pe-4"><i class="fa fa-building-o me-1 text-warning"></i>Customer</th>
                        <th><i class="fa fa-cube me-1 text-success"></i>Product</th>
                        <th class="text-end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="issue_items.quantity_in" label="Qty In" /></th>
                        <th class="text-end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="issue_items.quantity_out" label="Qty Out" /></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $item)
                        <tr>
                            <td class="ps-4 text-muted">
                                <span class="cell-with-icon">
                                    <span class="icon-chip"><i class="fa fa-list-ol"></i></span>
                                    <span>{{ $item->id }}</span>
                                </span>
                            </td>
                            <td class="ps-4">
                                <span class="cell-with-icon">
                                    <span class="icon-chip"><i class="fa fa-calendar"></i></span>
                                    <span>{{ systemDate($item->issue_date) }}</span>
                                </span>
                            </td>
                            <td class="pe-4">
                                <span class="cell-with-icon">
                                    <i class="fa fa-building-o text-warning"></i>
                                    <span>{{ $item->issue?->account?->name ?? '-' }}</span>
                                </span>
                            </td>
                            <td>
                                <span class="cell-with-icon">
                                    <i class="fa fa-cube text-success"></i>
                                    <span>
                                        <span class="fw-medium">{{ $item->product?->name ?? '-' }}</span>
                                        @if ($item->product?->code)
                                            <small class="text-muted">({{ $item->product->code }})</small>
                                        @endif
                                    </span>
                                </span>
                            </td>
                            <td class="text-end qty-in">
                                @if ($item->quantity_in!=0)
                                    <span class="cell-with-icon justify-content-end">
                                        <i class="fa fa-arrow-down text-success"></i>
                                        <span>{{ number_format($item->quantity_in, 2) }}</span>
                                    </span>
                                @endif
                            </td>
                            <td class="text-end qty-out">
                                @if ($item->quantity_out!=0)
                                    <span class="cell-with-icon justify-content-end">
                                        <i class="fa fa-arrow-up text-danger"></i>
                                        <span>{{ number_format($item->quantity_out, 2) }}</span>
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-state text-center">
                                <div class="empty-icon">📋</div>
                                <p class="empty-text mb-0">No issue items found for the selected filters.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3 px-0">
            {{ $data->links() }}
        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#issue_item_wise_product_id').on('change', function() {
                    @this.set('product_id', $(this).val() || '');
                });
            });
        </script>
    @endpush
</div>

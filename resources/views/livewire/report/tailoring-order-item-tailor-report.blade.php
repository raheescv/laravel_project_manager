<div class="tailoring-item-tailor-report">
    <div class="card-header bg-white border-0 pb-0">
        <div class="row g-2 align-items-center">
            <div class="col flex-shrink-0 d-flex align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-outline-primary" title="Export to Excel" wire:click="export()">
                    <i class="demo-pli-file-excel me-1"></i> Export
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" title="Clear all filters" wire:click="resetFilters()">
                    <i class="fa fa-undo me-1"></i> Reset
                </button>
            </div>
            <div class="col d-flex gap-2 justify-content-end align-items-center flex-wrap">
                <select wire:model.live="limit" class="form-select form-select-sm" style="width: 6.5rem;">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="500">500</option>
                </select>
                <span class="text-muted small">rows</span>
                <div class="input-group input-group-sm" style="width: 12rem;">
                    <span class="input-group-text bg-white border-end-0"><i class="demo-pli-magnifi-glass text-muted"></i></span>
                    <input type="text" wire:model.live.debounce.300ms="search" class="form-control border-start-0" placeholder="Search...">
                </div>
                <div class="dropdown">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown" aria-expanded="false" title="Column visibility">
                        <i class="demo-pli-layout-grid me-1"></i> Columns
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm p-2 min-w-200">
                        @foreach ($columnDefinitions as $columnKey => $columnLabel)
                            <li class="dropdown-item p-0 mb-1">
                                <label class="d-flex align-items-center w-100 px-2 py-1 cursor-pointer mb-0 rounded">
                                    <input type="checkbox" class="form-check-input me-2" wire:model.live="tailoring_order_item_tailor_report_visible_column.{{ $columnKey }}">
                                    <span class="small">{{ $columnLabel }}</span>
                                </label>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        <hr class="my-3">
        <div class="filter-panel bg-body-tertiary rounded-2 border py-3 px-3">
            <div class="row g-2 g-md-3">
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted mb-1">Date type</label>
                    <select wire:model.live="date_type" class="form-select form-select-sm">
                        <option value="order_date">Order Date</option>
                        <option value="delivery_date">Delivery Date</option>
                        <option value="completion_date">Completion Date</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted mb-1">From</label>
                    <input type="date" wire:model.live="from_date" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted mb-1">To</label>
                    <input type="date" wire:model.live="to_date" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted mb-1">Branch</label>
                    <select wire:model.live="branch_id" class="form-select form-select-sm">
                        @foreach ($branchOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-4" wire:ignore>
                    <label class="form-label small text-muted mb-1">Status</label>
                    {{ html()->select('status', $tailorStatusOptions)->value($status ?? '')->class('tomSelect')->multiple(true)->id('tailoring_item_tailor_report_status')->attribute('wire:model.live', 'status') }}
                </div>
                <div class="col-12 col-md-4" wire:ignore>
                    <label class="form-label small text-muted mb-1">Customer</label>
                    {{ html()->select('customer_id', [])->value($customer_id ?? '')->class('select-customer_id-list')->id('tailoring_item_tailor_report_customer_id')->placeholder('All Customers') }}
                </div>
                <div class="col-12 col-md-6" wire:ignore>
                    <label class="form-label small text-muted mb-1">Product</label>
                    {{ html()->select('product_id', [])->value($product_id ?? '')->class('select-product_id-list')->attribute('type', 'product')->id('tailoring_item_tailor_report_product_id')->placeholder('All Products') }}
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted mb-1">Category</label>
                    <select wire:model.live="category_id" class="form-select form-select-sm">
                        @foreach ($categoryOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4" wire:ignore>
                    <label class="form-label small text-muted mb-1">Tailor</label>
                    {{ html()->select('tailor_id', [])->value($tailor_id ?? '')->class('select-employee_id-list')->id('tailoring_item_tailor_report_tailor_id')->placeholder('All Tailors') }}
                </div>
            </div>
        </div>
    </div>

    <div class="card-body pt-2 px-0 pb-0">
        <div class="px-3 pb-2">
            <div class="row g-2 report-metrics">
                <div class="col-6 col-lg-3">
                    <div class="metric-card">
                        <small class="text-muted d-block">Assignments</small>
                        <div class="metric-value">{{ number_format($total['total_assignments'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="metric-card">
                        <small class="text-muted d-block">Total Commission</small>
                        <div class="metric-value text-primary">{{ currency($total['total_commission'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="metric-card">
                        <small class="text-muted d-block">Completed</small>
                        <div class="metric-value">{{ number_format($total['completed_count'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="metric-card">
                        <small class="text-muted d-block">Avg Rating</small>
                        <div class="metric-value">{{ number_format((float) ($total['avg_rating'] ?? 0), 2) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover table-sm align-middle mb-0">
                <thead class="bg-light text-nowrap">
                    <tr>
                        <th class="ps-3">#</th>
                        @if ($tailoring_order_item_tailor_report_visible_column['order_no'] ?? true)
                            <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_orders.order_no" label="Order No" /></th>
                        @endif
                        @if ($tailoring_order_item_tailor_report_visible_column['order_date'] ?? true)
                            <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_orders.order_date" label="Order Date" /></th>
                        @endif
                        @if ($tailoring_order_item_tailor_report_visible_column['customer'] ?? true)
                            <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_orders.customer_name" label="Customer" /></th>
                        @endif
                        @if ($tailoring_order_item_tailor_report_visible_column['item_no'] ?? true)
                            <th class="text-center"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_order_items.item_no" label="Item #" /></th>
                        @endif
                        @if ($tailoring_order_item_tailor_report_visible_column['category'] ?? true)
                            <th>Category</th>
                        @endif
                        @if ($tailoring_order_item_tailor_report_visible_column['category_model'] ?? true)
                            <th>Model</th>
                        @endif
                        @if ($tailoring_order_item_tailor_report_visible_column['category_model_type'] ?? true)
                            <th>Type</th>
                        @endif
                        @if ($tailoring_order_item_tailor_report_visible_column['product_name'] ?? true)
                            <th>Product</th>
                        @endif
                        @if ($tailoring_order_item_tailor_report_visible_column['product_color'] ?? true)
                            <th>Color</th>
                        @endif
                        @if ($tailoring_order_item_tailor_report_visible_column['item_quantity'] ?? true)
                            <th class="text-end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_order_items.quantity" label="Item Qty" /></th>
                        @endif
                        @if ($tailoring_order_item_tailor_report_visible_column['tailor'] ?? true)
                            <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailors.name" label="Tailor" /></th>
                        @endif
                        @if ($tailoring_order_item_tailor_report_visible_column['tailor_commission'] ?? true)
                            <th class="text-end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_order_item_tailors.tailor_commission" label="Commission" /></th>
                        @endif
                        @if ($tailoring_order_item_tailor_report_visible_column['completion_date'] ?? true)
                            <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_order_item_tailors.completion_date" label="Completion Date" /></th>
                        @endif
                        @if ($tailoring_order_item_tailor_report_visible_column['rating'] ?? true)
                            <th class="text-center"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_order_item_tailors.rating" label="Rating" /></th>
                        @endif
                        @if ($tailoring_order_item_tailor_report_visible_column['status'] ?? true)
                            <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_order_item_tailors.status" label="Tailor Status" /></th>
                        @endif
                        @if ($tailoring_order_item_tailor_report_visible_column['created_at'] ?? true)
                            <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_order_item_tailors.created_at" label="Assigned At" /></th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $item)
                        @php
                            $orderItem = $item->tailoringOrderItem;
                            $order = $orderItem?->order;
                        @endphp
                        <tr>
                            <td class="ps-3 text-muted">{{ $item->id }}</td>
                            @if ($tailoring_order_item_tailor_report_visible_column['order_no'] ?? true)
                                <td class="text-nowrap">
                                    @if ($order?->id)
                                        <a href="{{ route('tailoring::order::show', $order->id) }}" class="text-primary fw-semibold text-decoration-none">{{ $order->order_no }}</a>
                                    @else
                                        {{ $order?->order_no ?? '–' }}
                                    @endif
                                </td>
                            @endif
                            @if ($tailoring_order_item_tailor_report_visible_column['order_date'] ?? true)
                                <td class="text-nowrap">{{ $order?->order_date ? systemDate($order->order_date) : '–' }}</td>
                            @endif
                            @if ($tailoring_order_item_tailor_report_visible_column['customer'] ?? true)
                                <td class="text-nowrap">{{ $order?->account?->name ?? ($order?->customer_name ?? '–') }}</td>
                            @endif
                            @if ($tailoring_order_item_tailor_report_visible_column['item_no'] ?? true)
                                <td class="text-center">{{ $orderItem?->item_no ?? '–' }}</td>
                            @endif
                            @if ($tailoring_order_item_tailor_report_visible_column['category'] ?? true)
                                <td>{{ $orderItem?->category?->name ?? '–' }}</td>
                            @endif
                            @if ($tailoring_order_item_tailor_report_visible_column['category_model'] ?? true)
                                <td class="text-nowrap">{{ $orderItem?->categoryModel?->name ?? '–' }}</td>
                            @endif
                            @if ($tailoring_order_item_tailor_report_visible_column['category_model_type'] ?? true)
                                <td class="text-nowrap">{{ $orderItem?->categoryModelType?->name ?? '–' }}</td>
                            @endif
                            @if ($tailoring_order_item_tailor_report_visible_column['product_name'] ?? true)
                                <td class="text-nowrap">{{ $orderItem?->product_name ?? '–' }}</td>
                            @endif
                            @if ($tailoring_order_item_tailor_report_visible_column['product_color'] ?? true)
                                <td>{{ $orderItem?->product_color ?? '–' }}</td>
                            @endif
                            @if ($tailoring_order_item_tailor_report_visible_column['item_quantity'] ?? true)
                                <td class="text-end">{{ $orderItem?->quantity !== null ? number_format((float) $orderItem->quantity, 3) : '–' }}</td>
                            @endif
                            @if ($tailoring_order_item_tailor_report_visible_column['tailor'] ?? true)
                                <td>{{ $item->tailor?->name ?? '–' }}</td>
                            @endif
                            @if ($tailoring_order_item_tailor_report_visible_column['tailor_commission'] ?? true)
                                <td class="text-end">{{ currency($item->tailor_commission) }}</td>
                            @endif
                            @if ($tailoring_order_item_tailor_report_visible_column['completion_date'] ?? true)
                                <td class="text-nowrap">{{ $item->completion_date ? systemDate($item->completion_date) : '–' }}</td>
                            @endif
                            @if ($tailoring_order_item_tailor_report_visible_column['rating'] ?? true)
                                <td class="text-center">{{ $item->rating ?? '–' }}</td>
                            @endif
                            @if ($tailoring_order_item_tailor_report_visible_column['status'] ?? true)
                                <td>
                                    @php
                                        $s = $item->status;
                                    @endphp
                                    <span
                                        class="badge bg-{{ $s === 'delivered' || $s === 'completed' ? 'success' : 'warning' }} bg-opacity-10 text-{{ $s === 'delivered' || $s === 'completed' ? 'success' : 'warning' }} text-capitalize">
                                        {{ ucfirst($s) }}
                                    </span>
                                </td>
                            @endif
                            @if ($tailoring_order_item_tailor_report_visible_column['created_at'] ?? true)
                                <td class="text-nowrap">{{ $item->created_at ? systemDateTime($item->created_at) : '–' }}</td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="17" class="text-center py-4 text-muted">No tailor assignments found.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-group-divider bg-light">
                    <tr>
                        <th class="ps-3">Total</th>
                        @if ($tailoring_order_item_tailor_report_visible_column['order_no'] ?? true)
                            <th></th>
                        @endif
                        @if ($tailoring_order_item_tailor_report_visible_column['order_date'] ?? true)
                            <th></th>
                        @endif
                        @if ($tailoring_order_item_tailor_report_visible_column['customer'] ?? true)
                            <th></th>
                        @endif
                        @if ($tailoring_order_item_tailor_report_visible_column['item_no'] ?? true)
                            <th></th>
                        @endif
                        @if ($tailoring_order_item_tailor_report_visible_column['category'] ?? true)
                            <th></th>
                        @endif
                        @if ($tailoring_order_item_tailor_report_visible_column['category_model'] ?? true)
                            <th></th>
                        @endif
                        @if ($tailoring_order_item_tailor_report_visible_column['category_model_type'] ?? true)
                            <th></th>
                        @endif
                        @if ($tailoring_order_item_tailor_report_visible_column['product_name'] ?? true)
                            <th></th>
                        @endif
                        @if ($tailoring_order_item_tailor_report_visible_column['product_color'] ?? true)
                            <th></th>
                        @endif
                        @if ($tailoring_order_item_tailor_report_visible_column['item_quantity'] ?? true)
                            <th></th>
                        @endif
                        @if ($tailoring_order_item_tailor_report_visible_column['tailor'] ?? true)
                            <th></th>
                        @endif
                        @if ($tailoring_order_item_tailor_report_visible_column['tailor_commission'] ?? true)
                            <th class="text-end fw-semibold text-primary">{{ currency($total['total_commission'] ?? 0) }}</th>
                        @endif
                        @if ($tailoring_order_item_tailor_report_visible_column['completion_date'] ?? true)
                            <th></th>
                        @endif
                        @if ($tailoring_order_item_tailor_report_visible_column['rating'] ?? true)
                            <th class="text-center fw-semibold">{{ number_format((float) ($total['avg_rating'] ?? 0), 2) }}</th>
                        @endif
                        @if ($tailoring_order_item_tailor_report_visible_column['status'] ?? true)
                            <th></th>
                        @endif
                        @if ($tailoring_order_item_tailor_report_visible_column['created_at'] ?? true)
                            <th></th>
                        @endif
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="px-3 pb-2">{{ $data->links() }}</div>
    </div>

    @push('scripts')
        <script>
            $(function() {
                $(document).on('change', '#tailoring_item_tailor_report_customer_id', function() {
                    @this.set('customer_id', $(this).val() || '');
                });
                $(document).on('change', '#tailoring_item_tailor_report_product_id', function() {
                    @this.set('product_id', $(this).val() || '');
                });
                $(document).on('change', '#tailoring_item_tailor_report_tailor_id', function() {
                    @this.set('tailor_id', $(this).val() || '');
                });
                $(document).on('change', '#tailoring_item_tailor_report_status', function() {
                    @this.set('status', $(this).val() || '');
                });

                Livewire.on('tailoring-item-tailor-report-filters-reset', function() {
                    var cust = document.getElementById('tailoring_item_tailor_report_customer_id');
                    var prod = document.getElementById('tailoring_item_tailor_report_product_id');
                    var tailor = document.getElementById('tailoring_item_tailor_report_tailor_id');
                    var status = document.getElementById('tailoring_item_tailor_report_status');
                    if (cust && cust.tomselect) cust.tomselect.clear();
                    if (prod && prod.tomselect) prod.tomselect.clear();
                    if (tailor && tailor.tomselect) tailor.tomselect.clear();
                    if (status && status.tomselect) status.tomselect.clear();
                });
            });
        </script>
    @endpush

    @push('styles')
        <style>
            .tailoring-item-tailor-report .table-responsive {
                max-height: 70vh;
            }

            .tailoring-item-tailor-report thead th {
                position: sticky;
                top: 0;
                z-index: 2;
                background: #f8fafc;
                box-shadow: inset 0 -1px 0 #e9ecef;
            }

            .tailoring-item-tailor-report .cursor-pointer {
                cursor: pointer;
            }

            .tailoring-item-tailor-report .min-w-200 {
                min-width: 12rem;
            }

            .tailoring-item-tailor-report .report-metrics .metric-card {
                background: linear-gradient(135deg, #f8fafc 0%, #edf2ff 100%);
                border: 1px solid #e9ecef;
                border-radius: 0.5rem;
                padding: 0.6rem 0.75rem;
            }

            .tailoring-item-tailor-report .report-metrics .metric-value {
                font-weight: 700;
                line-height: 1.2;
            }

            .tailoring-item-tailor-report .filter-panel .form-label {
                font-size: 0.75rem;
            }

            .tailoring-item-tailor-report tbody tr:hover td {
                background-color: #f8fbff;
            }
        </style>
    @endpush
</div>

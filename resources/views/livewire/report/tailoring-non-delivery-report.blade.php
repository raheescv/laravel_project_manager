<div class="tailoring-non-delivery-report">
    <div class="card-header bg-white border-0 p-3 no-print">
        <div class="rounded-4 border bg-light-subtle p-3 p-md-4 mb-3 shadow-sm">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-xl-6">
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-sm btn-success rounded-pill px-3 shadow-sm" title="Export to Excel" wire:click="exportExcel">
                            <i class="demo-pli-file-excel me-1"></i> Excel
                        </button>
                        <button type="button" class="btn btn-sm btn-danger rounded-pill px-3 shadow-sm" title="Export to PDF" wire:click="exportPdf">
                            <i class="demo-pli-file me-1"></i> PDF
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" title="Clear all filters" wire:click="resetFilters()">
                            <i class="fa fa-undo me-1"></i> Reset
                        </button>
                    </div>
                </div>
                <div class="col-12 col-xl-6">
                    <div class="row g-2 justify-content-xl-end">
                        <div class="col-12 col-md">
                            <div class="input-group input-group-sm shadow-sm">
                                <span class="input-group-text bg-white"><i class="demo-pli-magnifi-glass text-muted"></i></span>
                                <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Search orders...">
                            </div>
                        </div>
                        <div class="col-6 col-md-auto">
                            <div class="input-group input-group-sm shadow-sm">
                                <span class="input-group-text bg-white">Rows</span>
                                <select wire:model.live="limit" class="form-select">
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                    <option value="500">500</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-6 col-md-auto">
                            <div class="dropdown d-grid">
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" data-bs-toggle="dropdown" aria-expanded="false" title="Column visibility">
                                    <i class="demo-pli-layout-grid me-1"></i> Columns
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow border-0 p-2">
                                    @foreach ($columnDefinitions as $columnKey => $columnLabel)
                                        <li>
                                            <label class="dropdown-item d-flex align-items-center gap-2 rounded py-1 mb-0">
                                                <input type="checkbox" class="form-check-input mt-0" wire:model.live="tailoring_non_delivery_report_visible_column.{{ $columnKey }}">
                                                <span class="small">{{ $columnLabel }}</span>
                                            </label>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card border-0 shadow-sm rounded-4 position-relative z-3">
            <div class="card-body p-3 p-md-4 bg-white position-relative">
                <div class="row g-3 g-md-3">
                    <div class="col-6 col-md-2">
                        <div class="p-2 rounded-3 border bg-light h-100">
                            <label class="form-label small fw-semibold text-secondary mb-1">Date type</label>
                            <select wire:model.live="date_type" class="form-select form-select-sm">
                                <option value="order_date">Order Date</option>
                                <option value="delivery_date">Delivery Date</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="p-2 rounded-3 border bg-light h-100">
                            <label class="form-label small fw-semibold text-secondary mb-1">From</label>
                            <input type="date" wire:model.live="from_date" class="form-control form-control-sm">
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="p-2 rounded-3 border bg-light h-100">
                            <label class="form-label small fw-semibold text-secondary mb-1">To</label>
                            <input type="date" wire:model.live="to_date" class="form-control form-control-sm">
                        </div>
                    </div>
                    <div class="col-12 col-md-3" wire:ignore>
                        <div class="p-2 rounded-3 border bg-light h-100">
                            <label class="form-label small fw-semibold text-secondary mb-1">Branch</label>
                            {{ html()->select('branch_id', [])->value($branch_id ?? '')->class('select-assigned-branch_id-list')->id('tailoring_non_delivery_report_branch_id')->placeholder('All Branches') }}
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2 rounded-3 border bg-light h-100">
                            <label class="form-label small fw-semibold text-secondary mb-1">Product Category</label>
                            <select wire:model.live="category_id" class="form-select form-select-sm">
                                @foreach ($categoryOptions as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-12 col-md-4 position-relative" wire:ignore>
                        <div class="p-2 rounded-3 border bg-light h-100">
                            <label class="form-label small fw-semibold text-secondary mb-1">Customer</label>
                            {{ html()->select('customer_id', [])->value($customer_id ?? '')->class('select-customer_id-list')->id('tailoring_non_delivery_report_customer_id')->placeholder('All Customers') }}
                        </div>
                    </div>
                    <div class="col-6 col-md-4 position-relative" wire:ignore>
                        <div class="p-2 rounded-3 border bg-light h-100">
                            <label class="form-label small fw-semibold text-secondary mb-1">Status</label>
                            {{ html()->select('status', tailoringOrderStatuses())->value($status ?? [])->class('tomSelect')->multiple(true)->id('tailoring_non_delivery_report_status')->attribute('wire:model.live', 'status') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card-body p-3 pt-0">
        <div class="row g-2 mb-3">
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100 bg-primary-subtle">
                    <div class="card-body py-3">
                        <small class="text-primary-emphasis d-block"><i class="fa fa-file-text-o me-1"></i>Orders</small>
                        <div class="h4 mb-0 fw-bold text-primary-emphasis">{{ number_format($total['total_orders'] ?? 0) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100 bg-info-subtle">
                    <div class="card-body py-3">
                        <small class="text-info-emphasis d-block"><i class="fa fa-cubes me-1"></i>Item Qty</small>
                        <div class="h4 mb-0 fw-bold text-info-emphasis">{{ round($total['item_quantity']) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100 bg-success-subtle">
                    <div class="card-body py-3">
                        <small class="text-success-emphasis d-block"><i class="fa fa-check-circle me-1"></i>Completed Qty</small>
                        <div class="h4 mb-0 fw-bold text-success-emphasis">{{ round($total['completed_qty']) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100 bg-warning-subtle">
                    <div class="card-body py-3">
                        <small class="text-warning-emphasis d-block"><i class="fa fa-clock-o me-1"></i>Pending Qty</small>
                        <div class="h4 mb-0 fw-bold text-warning-emphasis">{{ round($total['pending_qty']) }}</div>
                    </div>
                </div>
            </div>
        </div>

        @php $visibleCount = 1 + collect($visibleColumns)->filter()->count(); @endphp
        <div class="table-responsive rounded-3 border shadow-sm bg-white">
            <table class="table table-striped table-hover table-sm align-middle mb-0">
                <thead class="table-light text-nowrap">
                    <tr>
                        <th class="ps-3">#</th>
                        @if ($visibleColumns['order_no'] ?? true)
                            <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_orders.order_no" label="Order Ref" /></th>
                        @endif
                        @if ($visibleColumns['order_date'] ?? true)
                            <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_orders.order_date" label="Order Date" /></th>
                        @endif
                        @if ($visibleColumns['delivery_date'] ?? true)
                            <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_orders.delivery_date" label="Delivery Date" /></th>
                        @endif
                        @if ($visibleColumns['customer'] ?? true)
                            <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="customer_name" label="Customer" /></th>
                        @endif
                        @if ($visibleColumns['mobile'] ?? true)
                            <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="customer_mobile" label="Mobile" /></th>
                        @endif
                        @if ($visibleColumns['bill_amount'] ?? true)
                            <th class="text-end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="bill_amount" label="Bill Amount" /></th>
                        @endif
                        @if ($visibleColumns['paid_amount'] ?? true)
                            <th class="text-end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="paid_amount" label="Paid" /></th>
                        @endif
                        @if ($visibleColumns['balance_amount'] ?? true)
                            <th class="text-end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="balance_amount" label="Balance" /></th>
                        @endif
                        @if ($visibleColumns['item_quantity'] ?? true)
                            <th class="text-end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="item_quantity" label="Item Qty" /></th>
                        @endif
                        @if ($visibleColumns['completed_qty'] ?? true)
                            <th class="text-end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="completed_qty" label="Completed Qty" /></th>
                        @endif
                        @if ($visibleColumns['pending_qty'] ?? true)
                            <th class="text-end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="pending_qty" label="Pending Qty" /></th>
                        @endif
                        @if ($visibleColumns['delivery_qty'] ?? true)
                            <th class="text-end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="delivery_qty" label="Delivery Qty" /></th>
                        @endif
                        @if ($visibleColumns['order_status'] ?? true)
                            <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_orders.status" label="Order Status" /></th>
                        @endif
                        @if ($visibleColumns['delivery_status'] ?? true)
                            <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_orders.delivery_status" label="Delivery Status" /></th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $item)
                        <tr>
                            <td class="ps-3 text-muted">{{ $item->id }}</td>
                            @if ($visibleColumns['order_no'] ?? true)
                                <td class="text-nowrap">
                                    <a href="{{ route('tailoring::order::show', $item->id) }}" class="text-primary fw-semibold text-decoration-none"><i
                                            class="fa fa-file-text-o me-1 small"></i>{{ $item->order_no }}</a>
                                </td>
                            @endif
                            @if ($visibleColumns['order_date'] ?? true)
                                <td class="text-nowrap"><i class="fa fa-calendar me-1 text-muted"></i>{{ $item->order_date ? systemDate($item->order_date) : '–' }}</td>
                            @endif
                            @if ($visibleColumns['delivery_date'] ?? true)
                                <td class="text-nowrap"><i class="fa fa-calendar me-1 text-muted"></i>{{ $item->delivery_date ? systemDate($item->delivery_date) : '–' }}</td>
                            @endif
                            @if ($visibleColumns['customer'] ?? true)
                                <td class="text-nowrap"><i class="fa fa-user me-1 text-muted"></i>{{ $item->customer_name ?? '–' }}</td>
                            @endif
                            @if ($visibleColumns['mobile'] ?? true)
                                <td class="text-nowrap"><i class="fa fa-phone me-1 text-muted"></i>{{ $item->customer_mobile ?? '–' }}</td>
                            @endif
                            @if ($visibleColumns['bill_amount'] ?? true)
                                <td class="text-nowrap text-end"><i class="fa fa-money me-1 text-muted"></i>{{ currency((float) ($item->bill_amount ?? 0)) }}</td>
                            @endif
                            @if ($visibleColumns['paid_amount'] ?? true)
                                <td class="text-nowrap text-end"><i class="fa fa-check-circle me-1 text-success"></i>{{ currency((float) ($item->paid_amount ?? 0)) }}</td>
                            @endif
                            @if ($visibleColumns['balance_amount'] ?? true)
                                <td class="text-nowrap text-end"><i class="fa fa-money me-1 text-warning"></i>{{ currency((float) ($item->balance_amount ?? 0)) }}</td>
                            @endif
                            @if ($visibleColumns['item_quantity'] ?? true)
                                <td class="text-nowrap text-end"><i class="fa fa-cubes me-1 text-muted"></i>{{ round($item->item_quantity) }}</td>
                            @endif
                            @if ($visibleColumns['completed_qty'] ?? true)
                                <td class="text-nowrap text-end"><i class="fa fa-check me-1 text-success"></i>{{ round($item->completed_qty) }}</td>
                            @endif
                            @if ($visibleColumns['pending_qty'] ?? true)
                                <td class="text-nowrap text-end"><i class="fa fa-clock-o me-1 text-warning"></i>{{ round($item->pending_qty) }}</td>
                            @endif
                            @if ($visibleColumns['delivery_qty'] ?? true)
                                <td class="text-nowrap text-end"><i class="fa fa-truck me-1 text-info"></i>{{ round($item->delivery_qty) }}</td>
                            @endif
                            @if ($visibleColumns['order_status'] ?? true)
                                <td>
                                    @php $s = $item->order_status; @endphp
                                    <span
                                        class="badge bg-{{ $s === 'delivered' || $s === 'completed' ? 'success' : ($s === 'pending' ? 'warning' : 'secondary') }} bg-opacity-10 text-{{ $s === 'delivered' || $s === 'completed' ? 'success' : ($s === 'pending' ? 'warning' : 'secondary') }}">
                                        <i class="fa fa-{{ $s === 'delivered' ? 'truck' : ($s === 'completed' ? 'check' : ($s === 'pending' ? 'clock-o' : 'tag')) }} me-1"></i>{{ ucWords($s) }}
                                    </span>
                                </td>
                            @endif
                            @if ($visibleColumns['delivery_status'] ?? true)
                                <td>
                                    @php $s = $item->delivery_status; @endphp
                                    <span class="badge bg-{{ $s === 'delivered' ? 'success' : ($s === 'partially delivered' ? 'warning' : 'secondary') }} bg-opacity-10 text-{{ $s === 'delivered' ? 'success' : ($s === 'partially delivered' ? 'warning' : 'secondary') }}">
                                        <i class="fa fa-{{ $s === 'delivered' ? 'truck' : ($s === 'partially delivered' ? 'clock-o' : 'tag') }} me-1"></i>{{ ucWords($s) }}
                                    </span>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $visibleCount }}" class="text-center py-4 text-muted">No non-delivery orders found.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-group-divider bg-light">
                    <tr>
                        <th class="ps-3">Total</th>
                        @if ($visibleColumns['order_no'] ?? true)
                            <th></th>
                        @endif
                        @if ($visibleColumns['order_date'] ?? true)
                            <th></th>
                        @endif
                        @if ($visibleColumns['delivery_date'] ?? true)
                            <th></th>
                        @endif
                        @if ($visibleColumns['customer'] ?? true)
                            <th></th>
                        @endif
                        @if ($visibleColumns['mobile'] ?? true)
                            <th></th>
                        @endif
                        @if ($visibleColumns['bill_amount'] ?? true)
                            <th class="text-end fw-semibold">{{ currency((float) ($total['bill_amount'] ?? 0)) }}</th>
                        @endif
                        @if ($visibleColumns['paid_amount'] ?? true)
                            <th class="text-end fw-semibold">{{ currency((float) ($total['paid_amount'] ?? 0)) }}</th>
                        @endif
                        @if ($visibleColumns['balance_amount'] ?? true)
                            <th class="text-end fw-semibold">{{ currency((float) ($total['balance_amount'] ?? 0)) }}</th>
                        @endif
                        @if ($visibleColumns['item_quantity'] ?? true)
                            <th class="text-end fw-semibold">{{ round($total['item_quantity']) }}</th>
                        @endif
                        @if ($visibleColumns['completed_qty'] ?? true)
                            <th class="text-end fw-semibold">{{ round($total['completed_qty']) }}</th>
                        @endif
                        @if ($visibleColumns['pending_qty'] ?? true)
                            <th class="text-end fw-semibold">{{ round($total['pending_qty']) }}</th>
                        @endif
                        @if ($visibleColumns['delivery_qty'] ?? true)
                            <th class="text-end fw-semibold">{{ round($total['delivery_qty']) }}</th>
                        @endif
                        @if ($visibleColumns['order_status'] ?? true)
                            <th></th>
                        @endif
                        @if ($visibleColumns['delivery_status'] ?? true)
                            <th></th>
                        @endif
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="pt-3 no-print">{{ $data->links() }}</div>
    </div>

    @push('scripts')
        <script>
            $(function() {
                $(document).on('change', '#tailoring_non_delivery_report_customer_id', function() {
                    @this.set('customer_id', $(this).val() || '');
                });
                $(document).on('change', '#tailoring_non_delivery_report_product_id', function() {
                    @this.set('product_id', $(this).val() || '');
                });
                $(document).on('change', '#tailoring_non_delivery_report_status', function() {
                    @this.set('status', $(this).val() || []);
                });
                $(document).on('change', '#tailoring_non_delivery_report_branch_id', function() {
                    @this.set('branch_id', $(this).val() || '');
                });

                Livewire.on('tailoring-non-delivery-report-filters-reset', function() {
                    var customer = document.getElementById('tailoring_non_delivery_report_customer_id');
                    var status = document.getElementById('tailoring_non_delivery_report_status');
                    var branch = document.getElementById('tailoring_non_delivery_report_branch_id');
                    if (customer && customer.tomselect) customer.tomselect.clear();
                    if (branch && branch.tomselect) branch.tomselect.clear();
                    if (status && status.tomselect) {
                        status.tomselect.clear();
                        status.tomselect.setValue(['pending', 'completed'], true);
                    }
                });
            });
        </script>
    @endpush

</div>

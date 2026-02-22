<div>
    <div class="card-header bg-white">
        <div class="row g-3">
            <div class="col-md-4 d-flex align-items-center">
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-secondary" type="button" wire:click="clearFilters()" title="Clear search filters">
                        <i class="demo-pli-recycling me-1"></i> Clear
                    </button>
                </div>
            </div>
            <div class="col-md-8">
                <div class="d-flex gap-2 justify-content-md-end align-items-center">
                    <div class="form-group" style="width: 280px;">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text border-end-0">
                                <i class="demo-pli-magnifi-glass"></i>
                            </span>
                            <input type="text" wire:model.live="search" class="form-control border-start-0" placeholder="Search name / mobile / work order..." autofocus>
                        </div>
                    </div>
                    <div class="form-group">
                        <select wire:model.live="pending_only" class="form-select form-select-sm">
                            <option value="1">Pending Status Only</option>
                            <option value="2">Pending Balance Only</option>
                            <option value="0">All Orders</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <select wire:model.live="limit" class="form-select form-select-sm">
                            <option value="10">10 rows</option>
                            <option value="25">25 rows</option>
                            <option value="50">50 rows</option>
                            <option value="100">100 rows</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <hr class="mt-3 mb-0">
        {{-- Filter area --}}
        <div class="col-12 mt-3">
            <div class="bg-light rounded-3 border shadow-sm">
                <div class="p-3">
                    <div class="row g-3">
                        <div class="col-md-4" wire:ignore>
                            <div class="form-group">
                                <label class="form-label text-muted fw-semibold small mb-2" for="order_management_customer_id">
                                    <i class="demo-psi-building me-1"></i> Customer
                                </label>
                                {{ html()->select('customer_id', [])->value($customer_id ?? '')->class('select-customer_id-list')->id('order_management_customer_id')->placeholder('All Customers') }}
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label class="form-label text-muted fw-semibold small mb-2" for="quick_search_hint">
                                    <i class="fa fa-search me-1"></i> Quick Search
                                </label>
                                <input type="text" wire:model.live="search" class="form-control form-control-sm" id="quick_search_hint"
                                    placeholder="Type customer name, mobile number, or work order number...">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body border-bottom">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0">Customer Quick View</h6>
            <small class="text-muted">Click "Orders" to filter that customer, or "Pay" to collect receipt</small>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="bg-light text-nowrap">
                    <tr>
                        <th class="ps-3">Customer</th>
                        <th class="text-end">Orders</th>
                        <th class="text-end">Total Bill</th>
                        <th class="text-end">Paid</th>
                        <th class="text-end">Balance</th>
                        <th class="text-nowrap">Latest Order</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customerSummary as $customer)
                        <tr>
                            <td class="ps-3">
                                <div class="fw-semibold">{{ $customer->customer_display ?: ($customer->customer_name ?: 'Walk-in Customer') }}</div>
                                <small class="text-muted">{{ $customer->customer_mobile ?: ($customer->order_customer_mobile ?: 'No mobile') }}</small>
                            </td>
                            <td class="text-end">{{ $customer->order_count }}</td>
                            <td class="text-end fw-semibold text-primary">{{ currency($customer->grand_total) }}</td>
                            <td class="text-end fw-semibold text-success">{{ currency($customer->paid) }}</td>
                            <td class="text-end fw-bold {{ (float) $customer->balance > 0 ? 'text-danger' : 'text-success' }}">{{ currency($customer->balance) }}</td>
                            <td>{{ $customer->latest_order_date ? systemDate($customer->latest_order_date) : '_' }}</td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-light border"
                                        wire:click="viewCustomerOrders({{ $customer->account_id ?? 'null' }}, {{ json_encode($customer->customer_name ?? '') }}, {{ json_encode($customer->order_customer_mobile ?? '') }})">
                                        <i class="fa fa-list text-primary me-1"></i> Orders
                                    </button>
                                    <button type="button" class="btn btn-success border"
                                        wire:click="openReceiptModal({{ $customer->account_id ?? 'null' }}, {{ json_encode($customer->customer_name ?? '') }}, {{ json_encode($customer->order_customer_mobile ?? '') }}, {{ json_encode($customer->customer_display ?? ($customer->customer_name ?? 'Customer')) }})">
                                        <i class="fa fa-file-text-o me-1"></i> Pay
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-3">No customers found for current filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-body px-0 pb-0">
        <div class="px-3 pb-2">
            <div class="d-flex flex-wrap align-items-center gap-2 small">
                <span class="text-muted fw-semibold me-1">Action Legend:</span>
                <span class="badge bg-light text-dark border"><i class="fa fa-list text-dark me-1"></i>Items</span>
                <span class="badge bg-light text-dark border"><i class="fa fa-users text-info me-1"></i>Tailor Status</span>
                <span class="badge bg-light text-success border border-success"><i class="fa fa-file-text-o me-1"></i>Collect Payment</span>
                <span class="badge bg-light text-dark border"><i class="fa fa-check-circle text-success me-1"></i>Job Completion</span>
                <span class="badge bg-light text-dark border"><i class="fa fa-edit text-warning me-1"></i>Edit</span>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover table-sm align-middle mb-0 border-bottom">
                <thead class="bg-light text-nowrap">
                    <tr>
                        <th class="ps-3">
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_orders.id" label="#" />
                        </th>
                        <th class="text-nowrap">
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_orders.order_date" label="Order Date" />
                        </th>
                        <th class="text-nowrap">
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_orders.order_no" label="Order Details" />
                        </th>
                        <th class="text-nowrap">
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_orders.account_id" label="Customer" />
                        </th>
                        <th class="text-nowrap">
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_orders.customer_mobile" label="Mobile" />
                        </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_orders.status" label="Status" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_orders.delivery_status" label="Delivery Status" /> </th>
                        <th class="text-nowrap text-end">
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_orders.grand_total" label="Grand Total" />
                        </th>
                        <th class="text-nowrap text-end">
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_orders.paid" label="Paid" />
                        </th>
                        <th class="text-nowrap text-end">
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_orders.balance" label="Balance" />
                        </th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $order)
                        <tr>
                            <td class="ps-3">
                                <button type="button" class="btn btn-sm btn-light border me-2" title="{{ in_array($order->id, $expandedOrderIds, true) ? 'Hide items' : 'Show items' }}"
                                    wire:click="toggleOrderItems({{ $order->id }})">
                                    <i class="fa {{ in_array($order->id, $expandedOrderIds, true) ? 'fa-chevron-up' : 'fa-chevron-down' }}"></i>
                                </button>
                                <span class="text-muted">#{{ $order->id }}</span>
                            </td>
                            <td class="text-nowrap">
                                {{ systemDate($order->order_date ?? $order->created_at) }}
                            </td>
                            <td class="text-nowrap">
                                <div>
                                    <a href="{{ route('tailoring::order::show', $order->id) }}" class="text-primary fw-semibold text-decoration-none">
                                        {{ $order->order_no }}
                                    </a>
                                    <br>
                                    <small class="text-muted">
                                        <i class="demo-psi-calendar-4 fs-6 text-primary"></i>
                                        {{ systemDate($order->order_date ?? $order->created_at) }}
                                    </small>
                                    @if ($order->delivery_date)
                                        <small class="text-muted ms-2">
                                            <i class="fa fa-truck fs-6 text-info"></i>
                                            {{ systemDate($order->delivery_date) }}
                                        </small>
                                    @endif
                                </div>
                            </td>
                            <td class="text-nowrap">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="demo-psi-building fs-5 text-warning"></i>
                                    <div>
                                        <div>{{ $order->account?->name ?? ($order->customer_name ?? 'Walk-in Customer') }}</div>
                                        @if ($order->salesman?->name)
                                            <small class="text-muted">Sales: {{ $order->salesman->name }}</small>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="text-nowrap">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fa fa-phone fs-5 text-info"></i>
                                    <div>{{ $order->customer_mobile ?? '—' }}</div>
                                </div>
                            </td>
                            <td>
                                <span
                                    class="badge bg-{{ $order->status === 'completed' ? 'success' : ($order->status === 'pending' ? 'warning' : ($order->status === 'delivered' ? 'dark' : ($order->status === 'cancelled' ? 'danger' : 'info'))) }} bg-opacity-10 text-{{ $order->status === 'completed' ? 'success' : ($order->status === 'pending' ? 'warning' : ($order->status === 'delivered' ? 'dark' : ($order->status === 'cancelled' ? 'danger' : 'info'))) }}">
                                    {{ ucFirst($order->status) }}
                                </span>
                            </td>
                            <td>
                                    {{ ucFirst($order->delivery_status) }}
                            </td>
                            <td>
                                <div class="text-end fw-bold text-primary">{{ currency($order->grand_total) }}</div>
                            </td>
                            <td>
                                <div class="text-end fw-bold text-success">{{ currency($order->paid) }}</div>
                            </td>
                            <td>
                                <div class="text-end fw-bold @if ($order->balance > 0) text-danger @else text-success @endif">{{ currency($order->balance) }}</div>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-light border" title="View Full Items" wire:click="openItemsModal({{ $order->id }})">
                                        <i class="fa fa-list text-dark"></i>
                                    </button>
                                    <button type="button" class="btn btn-light border" title="Order Action (Tailor Status)" wire:click="openTailorActionModal({{ $order->id }})">
                                        <i class="fa fa-users text-info"></i>
                                    </button>
                                    <button type="button" class="btn btn-success border" title="Collect Payment"
                                        wire:click="openReceiptModal({{ $order->account_id ?? 'null' }}, {{ json_encode($order->customer_name ?? '') }}, {{ json_encode($order->customer_mobile ?? '') }}, {{ json_encode($order->account?->name ?? ($order->customer_name ?? 'Customer')) }})">
                                        <i class="fa fa-file-text-o"></i>
                                    </button>
                                    <a href="{{ route('tailoring::job-completion::index') }}?order_no={{ $order->order_no }}" class="btn btn-light border" title="Job Completion">
                                        <i class="fa fa-check-circle text-success"></i>
                                    </a>
                                    <a href="{{ route('tailoring::order::edit', $order->id) }}" class="btn btn-light border" title="Edit Order">
                                        <i class="fa fa-edit text-warning"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @if (in_array($order->id, $expandedOrderIds, true))
                            <tr class="bg-white">
                                <td colspan="11" class="px-3 py-2">
                                    <div class="border rounded-3 shadow-sm overflow-hidden">
                                        <div class="px-3 py-2 bg-light border-bottom d-flex justify-content-between align-items-center">
                                            <div class="fw-semibold">Order Items</div>
                                            <small class="text-muted">Work Order: {{ $order->order_no }}</small>
                                        </div>
                                        <div class="nested-items-wrapper">
                                            <table class="table table-sm mb-0 nested-items-table">
                                                <thead class="table-light text-nowrap">
                                                    <tr>
                                                        <th style="width: 48px;">#</th>
                                                        <th>Product Name</th>
                                                        <th class="text-end">Total Qty</th>
                                                        <th class="text-end">Completed Qty</th>
                                                        <th class="text-end">Delivered Qty</th>
                                                        <th class="text-end">Pending Qty</th>
                                                        <th>Completed Status</th>
                                                        <th>Delivery Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse (($orderItemsByOrder[$order->id] ?? []) as $item)
                                                        @php
                                                            $status = $item['status'] ?? '';
                                                            $statusClass = $status === 'delivered' ? 'dark' : ($status === 'completed' ? 'success' : ($status === 'partially completed' ? 'warning' : 'secondary'));
                                                        @endphp
                                                        <tr>
                                                            <td>{{ $item['item_no'] ?? '-' }}</td>
                                                            <td class="fw-semibold">{{ $item['product_name'] ?: '-' }}</td>
                                                            <td class="text-end">{{ round($item['quantity']) }}</td>
                                                            <td class="text-end text-success">{{ round($item['completed_quantity']) }}</td>
                                                            <td class="text-end text-info">{{ round($item['delivered_quantity']) }}</td>
                                                            <td class="text-end text-warning">{{ round($item['pending_quantity']) }}</td>
                                                            <td>
                                                                <span class="badge bg-info bg-opacity-10 text-info">
                                                                    {{ ucWords($item['completion_status']) }}
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                                                    {{ ucWords($item['delivery_status']) }}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="8" class="text-center text-muted py-3">No order items.</td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">
                                <i class="demo-pli-magnifi-glass fs-1 d-block mb-2"></i>
                                @if (trim($customer_id ?? '') !== '' || trim($search ?? '') !== '')
                                    No orders found. Try different search criteria.
                                @else
                                    Start searching by customer name, mobile, or work order.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $data->links() }}
    </div>

    <div class="modal" id="TailoringOrderItemsPreviewModal" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="mb-0">Order Items</h5>
                        <small class="text-muted">
                            Order: {{ $selectedOrderDetails['order_no'] ?? '-' }}
                            | Date: {{ !empty($selectedOrderDetails['order_date']) ? systemDate($selectedOrderDetails['order_date']) : '-' }}
                            | Customer: {{ $selectedOrderDetails['customer_name'] ?? '-' }}
                        </small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div id="tailoring_order_preview_context" class="d-none"
                        data-account-id="{{ $selectedOrderDetails['account_id'] ?? '' }}"
                        data-customer-name="{{ $selectedOrderDetails['customer_name'] ?? '' }}"
                        data-customer-mobile="{{ $selectedOrderDetails['customer_mobile'] ?? '' }}"></div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-sm align-middle mb-0">
                            <thead class="bg-light text-nowrap">
                                <tr>
                                    <th class="ps-3" style="width: 44px;">
                                        <input type="checkbox" id="tailoring_select_all_items" class="form-check-input">
                                    </th>
                                    <th class="ps-3">#</th>
                                    <th>Product</th>
                                    <th>Category / Model</th>
                                    <th>Color</th>
                                    <th class="text-end">Qty</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Total</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($selectedOrderItems as $item)
                                    <tr>
                                        <td class="ps-3">
                                            <input type="checkbox" class="form-check-input tailoring-order-item-checkbox"
                                                data-item-id="{{ $item['id'] }}"
                                                data-prefill='@json($item['prefill'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)'>
                                        </td>
                                        <td class="ps-3">{{ $item['item_no'] }}</td>
                                        <td class="fw-semibold">{{ $item['product_name'] }}</td>
                                        <td>
                                            <div>{{ $item['category'] ?: '-' }}</div>
                                            <small class="text-muted">
                                                {{ $item['model'] ?: '-' }}
                                                @if (!empty($item['model_type']))
                                                    / {{ $item['model_type'] }}
                                                @endif
                                            </small>
                                        </td>
                                        <td>{{ $item['color'] ?: '-' }}</td>
                                        <td class="text-end">{{ number_format((float) $item['quantity'], 3) }} {{ $item['unit'] ?: '' }}</td>
                                        <td class="text-end">{{ currency($item['unit_price']) }}</td>
                                        <td class="text-end fw-bold">{{ currency($item['total']) }}</td>
                                        <td>{{ $item['notes'] ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-3">No items in this order.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <small class="text-muted">
                        Selected:
                        <span id="tailoring_selected_items_count">0</span>
                    </small>
                    <button type="button" id="tailoring_create_order_from_selected" class="btn btn-primary btn-sm" disabled>
                        <i class="fa fa-plus-circle me-1"></i>
                        Create New Order With Selected
                    </button>
                </div>
            </div>
        </div>
    </div>

    @livewire('tailoring.order-tailor-action-modal')

    @push('scripts')
        <script>
            document.addEventListener('livewire:navigated', function() {
                bindOrderManagementCustomer();
            });
            document.addEventListener('DOMContentLoaded', function() {
                bindOrderManagementCustomer();
            });

            function bindOrderManagementCustomer() {
                const el = document.getElementById('order_management_customer_id');
                if (!el || el._orderManagementBound) return;
                el._orderManagementBound = true;
                $(el).off('change.order_management').on('change.order_management', function() {
                    const value = $(this).val() || '';
                    @this.set('customer_id', value);
                });
            }

            function bindTailoringOrderItemsSelection() {
                const modal = document.getElementById('TailoringOrderItemsPreviewModal');
                if (!modal || modal._tailoringSelectionBound) return;
                modal._tailoringSelectionBound = true;

                const getItemCheckboxes = () => Array.from(modal.querySelectorAll('.tailoring-order-item-checkbox'));
                const getSelectedCheckboxes = () => getItemCheckboxes().filter(cb => cb.checked);

                const refreshSelectionState = () => {
                    const selectAll = document.getElementById('tailoring_select_all_items');
                    const createBtn = document.getElementById('tailoring_create_order_from_selected');
                    const selectedCountEl = document.getElementById('tailoring_selected_items_count');
                    const checkboxes = getItemCheckboxes();
                    const selected = getSelectedCheckboxes();
                    const hasItems = checkboxes.length > 0;

                    if (selectedCountEl) selectedCountEl.textContent = String(selected.length);
                    if (createBtn) createBtn.disabled = selected.length === 0;

                    if (selectAll) {
                        selectAll.checked = hasItems && selected.length === checkboxes.length;
                        selectAll.indeterminate = selected.length > 0 && selected.length < checkboxes.length;
                    }
                };

                const resetSelectionState = () => {
                    const selectAll = document.getElementById('tailoring_select_all_items');
                    getItemCheckboxes().forEach(cb => {
                        cb.checked = false;
                    });
                    if (selectAll) {
                        selectAll.checked = false;
                        selectAll.indeterminate = false;
                    }
                    refreshSelectionState();
                };

                modal.addEventListener('change', function(event) {
                    if (!event.target) return;

                    if (event.target.id === 'tailoring_select_all_items') {
                        getItemCheckboxes().forEach(cb => {
                            cb.checked = !!event.target.checked;
                        });
                        refreshSelectionState();
                        return;
                    }

                    if (event.target.classList.contains('tailoring-order-item-checkbox')) {
                        refreshSelectionState();
                    }
                });

                modal.addEventListener('click', function(event) {
                    if (event.target?.id === 'tailoring_create_order_from_selected' || event.target?.closest?.('#tailoring_create_order_from_selected')) {
                        const selectedItems = getSelectedCheckboxes()
                            .map(cb => {
                                try {
                                    return JSON.parse(cb.getAttribute('data-prefill') || '{}');
                                } catch (e) {
                                    return null;
                                }
                            })
                            .filter(Boolean);

                        if (selectedItems.length === 0) {
                            return;
                        }

                        const payload = {
                            source: 'TailoringOrderItemsPreviewModal',
                            created_at: new Date().toISOString(),
                            customer: {
                                account_id: document.getElementById('tailoring_order_preview_context')?.getAttribute('data-account-id') || null,
                                customer_name: document.getElementById('tailoring_order_preview_context')?.getAttribute('data-customer-name') || '',
                                customer_mobile: document.getElementById('tailoring_order_preview_context')?.getAttribute('data-customer-mobile') || ''
                            },
                            items: selectedItems
                        };

                        sessionStorage.setItem('tailoring_order_prefill_v1', JSON.stringify(payload));
                        window.location.href = '/tailoring/order/create';
                    }
                });

                modal.addEventListener('hidden.bs.modal', resetSelectionState);
                refreshSelectionState();
            }

            document.addEventListener('livewire:init', function() {
                Livewire.on('order-management-clear-customer', function() {
                    const el = document.getElementById('order_management_customer_id');
                    if (el && el.tomselect) el.tomselect.clear();
                });
                Livewire.on('toggle-order-items-modal', function() {
                    bindTailoringOrderItemsSelection();
                    $('#TailoringOrderItemsPreviewModal').modal('show');
                    setTimeout(() => {
                        const selectAll = document.getElementById('tailoring_select_all_items');
                        if (selectAll) {
                            selectAll.checked = false;
                            selectAll.indeterminate = false;
                        }
                        document.querySelectorAll('#TailoringOrderItemsPreviewModal .tailoring-order-item-checkbox').forEach(cb => {
                            cb.checked = false;
                        });
                        const count = document.getElementById('tailoring_selected_items_count');
                        if (count) count.textContent = '0';
                        const btn = document.getElementById('tailoring_create_order_from_selected');
                        if (btn) btn.disabled = true;
                    }, 0);
                });
            });
        </script>
    @endpush
    @push('styles')
        <style>
            .nested-items-wrapper {
                background: #f8fbff;
                border: 1px solid #e6f0fb;
                padding: 0.5rem;
                border-radius: 0.375rem;
                box-shadow: 0 6px 18px rgba(15, 23, 42, 0.03);
            }

            .nested-items-table thead th {
                background: #ffffff;
                color: #334155;
                font-size: 0.78rem;
                border-bottom: 1px solid #e6eef6;
            }

            .nested-items-table td {
                font-size: 0.92rem;
                vertical-align: middle;
                padding-top: 0.55rem;
                padding-bottom: 0.55rem;
            }
        </style>
    @endpush
</div>

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
                                <label class="form-label text-muted fw-semibold small mb-2" for="order_search_customer_id">
                                    <i class="demo-psi-building me-1"></i> Customer
                                </label>
                                {{ html()->select('customer_id', [])->value($customer_id ?? '')->class('select-customer_id-list')->id('order_search_customer_id')->placeholder('All Customers') }}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label text-muted fw-semibold small mb-2" for="mobile">
                                    <i class="fa fa-phone me-1"></i> Mobile No
                                </label>
                                <input type="text" wire:model.live="mobile" class="form-control form-control-sm" id="mobile" placeholder="Mobile number...">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label text-muted fw-semibold small mb-2" for="order_no">
                                    <i class="demo-pli-file me-1"></i>Work Order No
                                </label>
                                <input type="text" wire:model.live="order_no" class="form-control form-control-sm" id="order_no" placeholder="Order number...">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body px-0 pb-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover table-sm align-middle mb-0 border-bottom">
                <thead class="bg-light text-nowrap">
                    <tr>
                        <th class="ps-3">
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_orders.id" label="#" />
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
                        <th>
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_orders.status" label="Status" />
                        </th>
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
                @if ($data->isNotEmpty())
                    <tbody class="table-group-divider">
                        <tr class="bg-light">
                            <th colspan="5" class="ps-3 text-end"><strong>TOTALS</strong></th>
                            <th>
                                <div class="text-end fw-bold text-primary">{{ currency($total['grand_total']) }}</div>
                            </th>
                            <th>
                                <div class="text-end fw-bold text-success">{{ currency($total['paid']) }}</div>
                            </th>
                            <th>
                                <div class="text-end fw-bold text-danger">{{ currency($total['balance']) }}</div>
                            </th>
                            <th></th>
                        </tr>
                    </tbody>
                @endif
                <tbody>
                    @forelse ($data as $order)
                        <tr>
                            <td class="ps-3">
                                <span class="text-muted">#{{ $order->id }}</span>
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
                                    <a href="{{ route('tailoring::order::show', $order->id) }}" class="btn btn-light border" title="View Details">
                                        <i class="fa fa-eye text-primary"></i>
                                    </a>
                                    <a href="{{ route('tailoring::job-completion::index') }}?order_no={{ $order->order_no }}" class="btn btn-light border" title="Job Completion">
                                        <i class="fa fa-check-circle text-success"></i>
                                    </a>
                                    <a href="{{ route('tailoring::order::edit', $order->id) }}" class="btn btn-light border" title="Edit Order">
                                        <i class="fa fa-edit text-warning"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                <i class="demo-pli-magnifi-glass fs-1 d-block mb-2"></i>
                                @if (trim($customer_id ?? '') !== '' || trim($mobile ?? '') !== '' || trim($order_no ?? '') !== '')
                                    No orders found. Try different search criteria.
                                @else
                                    Enter Customer, Mobile or Order No above to search.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $data->links() }}
    </div>
    @push('scripts')
        <script>
            document.addEventListener('livewire:navigated', function() {
                bindOrderSearchCustomer();
            });
            document.addEventListener('DOMContentLoaded', function() {
                bindOrderSearchCustomer();
            });

            function bindOrderSearchCustomer() {
                const el = document.getElementById('order_search_customer_id');
                if (!el || el._orderSearchBound) return;
                el._orderSearchBound = true;
                $(el).off('change.order_search').on('change.order_search', function() {
                    const value = $(this).val() || '';
                    @this.set('customer_id', value);
                });
            }
            document.addEventListener('livewire:init', function() {
                Livewire.on('order-search-clear-customer', function() {
                    const el = document.getElementById('order_search_customer_id');
                    if (el && el.tomselect) el.tomselect.clear();
                });
            });
        </script>
    @endpush
</div>

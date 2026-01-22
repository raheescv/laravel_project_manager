<div>
    <div class="card-header bg-white">
        <div class="row g-3">
            <div class="col-md-4 d-flex align-items-center">
                <div class="btn-group">
                    @can('tailoring.order.export')
                        <button class="btn btn-sm btn-outline-primary" title="Export as Excel" wire:click="export()">
                            <i class="demo-pli-file-excel me-1"></i> Export
                        </button>
                    @endcan
                    @can('tailoring.order.delete')
                        <button class="btn btn-sm btn-outline-danger" title="Delete selected items" wire:click="delete()" wire:confirm="Are you sure you want to delete the selected items?">
                            <i class="demo-pli-recycling me-1"></i> Delete
                        </button>
                    @endcan
                </div>
            </div>
            <div class="col-md-8">
                <div class="d-flex gap-2 justify-content-md-end align-items-center">
                    <div class="form-group">
                        <select wire:model.live="limit" class="form-select form-select-sm">
                            <option value="10">10 rows</option>
                            <option value="50">50 rows</option>
                            <option value="100">100 rows</option>
                            <option value="500">500 rows</option>
                        </select>
                    </div>
                    <div class="form-group" style="width: 250px;">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text border-end-0">
                                <i class="demo-pli-magnifi-glass"></i>
                            </span>
                            <input type="text" wire:model.live="search" class="form-control border-start-0" placeholder="Search orders..." autofocus>
                        </div>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="demo-pli-layout-grid me-1"></i> Columns
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm p-2">
                            @foreach ($columnDefinitions as $columnKey => $columnLabel)
                                <li class="dropdown-item p-0 mb-1">
                                    <label class="d-flex align-items-center w-100 px-2 py-1 cursor-pointer">
                                        <input type="checkbox" class="form-check-input me-2" wire:model.live="tailoring_visible_column.{{ $columnKey }}">
                                        {{ $columnLabel }}
                                    </label>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <hr class="mt-3 mb-0">
        {{-- filter area --}}
        <div class="col-12 mt-3">
            <div class="bg-light rounded-3 border shadow-sm">
                <div class="p-3">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label text-muted fw-semibold small mb-2" for="date_type">
                                    <i class="demo-psi-calendar-4 me-1"></i> Date Type
                                </label>
                                <select wire:model.live="date_type" class="form-select form-select-sm" id="date_type">
                                    <option value="order_date">Order Date</option>
                                    <option value="delivery_date">Delivery Date</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label text-muted fw-semibold small mb-2" for="from_date">
                                    <i class="demo-psi-calendar-4 me-1"></i> From Date
                                </label>
                                {{ html()->date('from_date')->value('')->class('form-control form-control-sm')->id('from_date')->attribute('wire:model.live', 'from_date') }}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label text-muted fw-semibold small mb-2" for="to_date">
                                    <i class="demo-psi-calendar-4 me-1"></i> To Date
                                </label>
                                {{ html()->date('to_date')->value('')->class('form-control form-control-sm')->id('to_date')->attribute('wire:model.live', 'to_date') }}
                            </div>
                        </div>
                        <div class="col-md-3" wire:ignore>
                            <div class="form-group">
                                <label class="form-label text-muted fw-semibold small mb-2" for="customer_id">
                                    <i class="demo-psi-building me-1"></i> Customer
                                </label>
                                {{ html()->select('customer_id', [])->value('')->class('select-customer_id-list')->id('customer_id')->placeholder('All Customers') }}
                            </div>
                        </div>
                        <div class="col-md-3" wire:ignore>
                            <div class="form-group">
                                <label class="form-label text-muted fw-semibold small mb-2" for="branch_id">
                                    <i class="demo-psi-home me-1"></i> Branch
                                </label>
                                {{ html()->select('branch_id', [session('branch_id') => session('branch_name')])->value(session('branch_id'))->class('select-assigned-branch_id-list')->id('branch_id')->placeholder('All Branches') }}
                            </div>
                        </div>
                        <div class="col-md-3" wire:ignore>
                            <div class="form-group">
                                <label class="form-label text-muted fw-semibold small mb-2" for="status">
                                    <i class="fa fa-flag me-1"></i> Status
                                </label>
                                <select class="form-select form-select-sm" id="status">
                                    <option value="">All Statuses</option>
                                    <option value="pending">Pending</option>
                                    <option value="confirmed">Confirmed</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="completed">Completed</option>
                                    <option value="delivered">Delivered</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label text-muted fw-semibold small mb-2" for="payment_status">
                                    <i class="demo-pli-credit-card-2 me-1"></i> Payment Status
                                </label>
                                <select wire:model.live="payment_status" class="form-select form-select-sm" id="payment_status">
                                    <option value="">All</option>
                                    <option value="paid">Paid</option>
                                    <option value="balance">Balance</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body px-0 pb-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover table-sm align-middle mb-0 border-bottom table-sm">
                <thead class="bg-light text-nowrap">
                    <tr>
                        <th class="ps-3">
                            <div class="d-flex align-items-center">
                                <div class="form-check me-2">
                                    <input type="checkbox" class="form-check-input" wire:model.live="selectAll" id="selectAll">
                                    <label class="form-check-label" for="selectAll"></label>
                                </div>
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_orders.id" label="#" />
                            </div>
                        </th>
                        @if ($tailoring_visible_column['details'] ?? true)
                            <th class="text-nowrap">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_orders.order_no" label="Order Details" />
                            </th>
                        @endif
                        @if ($tailoring_visible_column['customer'] ?? true)
                            <th class="text-nowrap">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_orders.account_id" label="Customer" />
                            </th>
                        @endif
                        @if ($tailoring_visible_column['status'] ?? true)
                            <th>
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_orders.status" label="Status" />
                            </th>
                        @endif
                        @if ($tailoring_visible_column['grand_total'] ?? true)
                            <th class="text-nowrap text-end">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_orders.grand_total" label="Grand Total" />
                            </th>
                        @endif
                        @if ($tailoring_visible_column['paid'] ?? true)
                            <th class="text-nowrap text-end">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_orders.paid" label="Paid" />
                            </th>
                        @endif
                        @if ($tailoring_visible_column['balance'] ?? true)
                            <th class="text-nowrap text-end">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_orders.balance" label="Balance" />
                            </th>
                        @endif
                        @if ($tailoring_visible_column['actions'] ?? true)
                            <th class="text-end">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data as $order)
                        <tr>
                            <td class="ps-3">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="form-check mb-0">
                                        <input type="checkbox" class="form-check-input" value="{{ $order->id }}" wire:model.live="selected">
                                    </div>
                                    <span class="text-muted">#{{ $order->id }}</span>
                                </div>
                            </td>
                            @if ($tailoring_visible_column['details'] ?? true)
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
                            @endif
                            @if ($tailoring_visible_column['customer'] ?? true)
                                <td class="text-nowrap">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="demo-psi-building fs-5 text-warning"></i>
                                        <div>
                                            <div>{{ $order->account?->name ?? $order->customer_name ?? 'Walk-in Customer' }}</div>
                                            @if ($order->salesman?->name)
                                                <small class="text-muted">Sales: {{ $order->salesman->name }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            @endif
                            @if ($tailoring_visible_column['status'] ?? true)
                                <td>
                                    <div class="badge bg-{{ $order->status === 'completed' ? 'success' : ($order->status === 'pending' ? 'warning' : ($order->status === 'delivered' ? 'dark' : ($order->status === 'cancelled' ? 'danger' : 'info'))) }} bg-opacity-10 text-{{ $order->status === 'completed' ? 'success' : ($order->status === 'pending' ? 'warning' : ($order->status === 'delivered' ? 'dark' : ($order->status === 'cancelled' ? 'danger' : 'info'))) }}">
                                        {{ ucFirst($order->status) }}
                                    </div>
                                </td>
                            @endif
                            @if ($tailoring_visible_column['grand_total'] ?? true)
                                <td>
                                    <div class="text-end fw-bold text-primary">{{ currency($order->grand_total) }}</div>
                                    <div class="text-end {{ $order->balance > 0 ? 'text-danger' : 'text-success' }} fw-semibold">
                                        {{ $order->balance > 0 ? currency($order->balance) : 'Paid' }}
                                    </div>
                                </td>
                            @endif
                            @if ($tailoring_visible_column['paid'] ?? true)
                                <td> <div class="text-end fw-bold text-primary">{{ currency($order->paid) }}</div> </td>
                            @endif
                            @if ($tailoring_visible_column['balance'] ?? true)
                                <td>
                                    <div class="text-end fw-bold text-primary">{{ currency($order->balance) }}</div>
                                </td>
                            @endif
                            @if ($tailoring_visible_column['actions'] ?? true)
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('tailoring::order::show', $order->id) }}"
                                            class="btn btn-light border" title="View Details">
                                            <i class="fa fa-eye text-primary"></i>
                                        </a>
                                        <a href="{{ route('tailoring::order::edit', $order->id) }}"
                                            class="btn btn-light border" title="Edit Order">
                                            <i class="fa fa-edit text-warning"></i>
                                        </a>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-group-divider">
                    <tr class="bg-light">
                        <th colspan="1" class="ps-3"><strong>TOTALS</strong></th>
                        @if ($tailoring_visible_column['details'] ?? true)
                            <th></th>
                        @endif
                        @if ($tailoring_visible_column['customer'] ?? true)
                            <th></th>
                        @endif
                        @if ($tailoring_visible_column['status'] ?? true)
                            <th></th>
                        @endif
                        @if ($tailoring_visible_column['amount'] ?? true)
                            <th>
                                <div class="text-end fw-bold text-primary">{{ currency($total['grand_total']) }}</div>
                            </th>
                        @endif
                        @if ($tailoring_visible_column['paid'] ?? true)
                            <th>
                                <div class="text-end fw-bold text-primary">{{ currency($total['paid']) }}</div>
                            </th>
                        @endif
                        @if ($tailoring_visible_column['balance'] ?? true)
                            <th>
                                <div class="text-end fw-bold text-primary">{{ currency($total['balance']) }}</div>
                            </th>
                        @endif
                        @if ($tailoring_visible_column['actions'] ?? true)
                            <th></th>
                        @endif
                    </tr>
                </tfoot>
            </table>
        </div>
        {{ $data->links() }}
    </div>
    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#branch_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('branch_id', value);
                });
                $('#status').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('status', value);
                });
                $('#customer_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('customer_id', value);
                });
            });
        </script>
        <style>
            .cursor-pointer {
                cursor: pointer;
            }
        </style>
    @endpush
</div>

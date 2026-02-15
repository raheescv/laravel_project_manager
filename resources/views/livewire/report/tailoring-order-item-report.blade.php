<div class="tailoring-item-report">
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
                                    <input type="checkbox" class="form-check-input me-2" wire:model.live="tailoring_order_item_report_visible_column.{{ $columnKey }}">
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
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted mb-1">Tailor</label>
                    <select wire:model.live="tailor_id" class="form-select form-select-sm">
                        @foreach ($tailorOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted mb-1">Status</label>
                    {{ html()->select('status', tailoringOrderItemStatuses())->value($status ?? '')->class('form-select form-select-sm')->id('tailoring_item_report_status')->placeholder('All Status') }}

                </div>
                <div class="col-12 col-md-4" wire:ignore>
                    <label class="form-label small text-muted mb-1">Customer</label>
                    {{ html()->select('customer_id', [])->value($customer_id ?? '')->class('select-customer_id-list')->id('tailoring_item_report_customer_id')->placeholder('All Customers') }}
                </div>
                <div class="col-12 col-md-6" wire:ignore>
                    <label class="form-label small text-muted mb-1">Product</label>
                    {{ html()->select('product_id', [])->value($product_id ?? '')->class('select-product_id-list')->attribute('type', 'product')->id('tailoring_item_report_product_id')->placeholder('All Products') }}
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted mb-1">Category</label>
                    <select wire:model.live="category_id" class="form-select form-select-sm">
                        @foreach ($categoryOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body pt-2 px-0 pb-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover table-sm align-middle mb-0">
                <thead class="bg-light text-nowrap">
                    <tr>
                        <th class="ps-3">#</th>
                        @if ($tailoring_order_item_report_visible_column['order_no'] ?? true)
                            <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_orders.order_no" label="Order No" /></th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['order_date'] ?? true)
                            <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_orders.order_date" label="Order Date" /></th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['customer'] ?? true)
                            <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_orders.customer_name" label="Customer" /></th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['item_no'] ?? true)
                            <th class="text-center"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_order_items.item_no" label="Item #" /></th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['category'] ?? true)
                            <th>Category</th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['category_model'] ?? true)
                            <th>Model</th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['product_name'] ?? true)
                            <th>Product</th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['product_color'] ?? true)
                            <th>Color</th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['unit'] ?? true)
                            <th>Unit</th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['quantity'] ?? true)
                            <th class="text-end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_order_items.quantity" label="Qty" /></th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['quantity_per_item'] ?? true)
                            <th class="text-end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_order_items.quantity_per_item" label="Meter Per Item" /></th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['completed_quantity'] ?? true)
                            <th class="text-end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_order_items.completed_quantity" label="Completed Qty" /></th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['unit_price'] ?? true)
                            <th class="text-end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_order_items.unit_price" label="Unit Price" /></th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['stitch_rate'] ?? true)
                            <th class="text-end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_order_items.stitch_rate" label="Stitch Rate" /></th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['gross_amount'] ?? true)
                            <th class="text-end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_order_items.gross_amount" label="Gross" /></th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['discount'] ?? true)
                            <th class="text-end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_order_items.discount" label="Discount" /></th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['net_amount'] ?? true)
                            <th class="text-end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_order_items.net_amount" label="Net" /></th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['tax'] ?? true)
                            <th class="text-end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_order_items.tax" label="Tax %" /></th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['tax_amount'] ?? true)
                            <th class="text-end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_order_items.tax_amount" label="Tax Amt" /></th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['total'] ?? true)
                            <th class="text-end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_order_items.total" label="Total" /></th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['tailor'] ?? true)
                            <th>Tailor</th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['tailor_commission'] ?? true)
                            <th class="text-end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_order_items.tailor_commission" label="Tailor Commission" /></th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['used_quantity'] ?? true)
                            <th class="text-end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_order_items.used_quantity" label="Used Qty" /></th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['wastage'] ?? true)
                            <th class="text-end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_order_items.wastage" label="Wastage" /></th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['item_completion_date'] ?? true)
                            <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_order_items.item_completion_date" label="Completion Date" /></th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['is_selected_for_completion'] ?? true)
                            <th class="text-center">Selected for Completion</th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['tailoring_notes'] ?? true)
                            <th>Notes</th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['rating'] ?? true)
                            <th class="text-center">Rating</th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['status'] ?? true)
                            <th>Item Status</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $item)
                        <tr>
                            <td class="ps-3 text-muted">{{ $item->id }}</td>
                            @if ($tailoring_order_item_report_visible_column['order_no'] ?? true)
                                <td class="text-nowrap">
                                    <a href="{{ route('tailoring::order::show', $item->order?->id) }}" class="text-primary fw-semibold text-decoration-none">{{ $item->order?->order_no }}</a>
                                </td>
                            @endif
                            @if ($tailoring_order_item_report_visible_column['order_date'] ?? true)
                                <td class="text-nowrap">{{ $item->order?->order_date ? systemDate($item->order->order_date) : '-' }}</td>
                            @endif
                            @if ($tailoring_order_item_report_visible_column['customer'] ?? true)
                                <td class="text-nowrap">{{ $item->order?->account?->name ?? ($item->order?->customer_name ?? '–') }}</td>
                            @endif
                            @if ($tailoring_order_item_report_visible_column['item_no'] ?? true)
                                <td class="text-center">{{ $item->item_no }}</td>
                            @endif
                            @if ($tailoring_order_item_report_visible_column['category'] ?? true)
                                <td>{{ $item->category?->name ?? '–' }}</td>
                            @endif
                            @if ($tailoring_order_item_report_visible_column['category_model'] ?? true)
                                <td>{{ $item->categoryModel?->name ?? '–' }}</td>
                            @endif
                            @if ($tailoring_order_item_report_visible_column['product_name'] ?? true)
                                <td class="text-nowrap">{{ $item->product_name }}</td>
                            @endif
                            @if ($tailoring_order_item_report_visible_column['product_color'] ?? true)
                                <td>{{ $item->product_color ?? '–' }}</td>
                            @endif
                            @if ($tailoring_order_item_report_visible_column['unit'] ?? true)
                                <td>{{ $item->unit?->name ?? '–' }}</td>
                            @endif
                            @if ($tailoring_order_item_report_visible_column['quantity'] ?? true)
                                <td class="text-end">{{ number_format((float) $item->quantity, 3) }}</td>
                            @endif
                            @if ($tailoring_order_item_report_visible_column['quantity_per_item'] ?? true)
                                <td class="text-end">{{ $item->quantity_per_item !== null ? number_format((float) $item->quantity_per_item, 3) : '–' }}</td>
                            @endif
                            @if ($tailoring_order_item_report_visible_column['completed_quantity'] ?? true)
                                <td class="text-end">{{ $item->completed_quantity !== null ? number_format((float) $item->completed_quantity, 3) : '–' }}</td>
                            @endif
                            @if ($tailoring_order_item_report_visible_column['unit_price'] ?? true)
                                <td class="text-end">{{ currency($item->unit_price) }}</td>
                            @endif
                            @if ($tailoring_order_item_report_visible_column['stitch_rate'] ?? true)
                                <td class="text-end">{{ $item->stitch_rate ? currency($item->stitch_rate) : '–' }}</td>
                            @endif
                            @if ($tailoring_order_item_report_visible_column['gross_amount'] ?? true)
                                <td class="text-end">{{ currency($item->gross_amount) }}</td>
                            @endif
                            @if ($tailoring_order_item_report_visible_column['discount'] ?? true)
                                <td class="text-end">{{ $item->discount ? currency($item->discount) : '–' }}</td>
                            @endif
                            @if ($tailoring_order_item_report_visible_column['net_amount'] ?? true)
                                <td class="text-end">{{ currency($item->net_amount) }}</td>
                            @endif
                            @if ($tailoring_order_item_report_visible_column['tax'] ?? true)
                                <td class="text-end">{{ $item->tax !== null && $item->tax != '' ? number_format((float) $item->tax, 2).'%' : '–' }}</td>
                            @endif
                            @if ($tailoring_order_item_report_visible_column['tax_amount'] ?? true)
                                <td class="text-end">{{ $item->tax_amount ? currency($item->tax_amount) : '–' }}</td>
                            @endif
                            @if ($tailoring_order_item_report_visible_column['total'] ?? true)
                                <td class="text-end fw-semibold">{{ currency($item->total) }}</td>
                            @endif
                            @if ($tailoring_order_item_report_visible_column['tailor'] ?? true)
                                <td>{{ $item->tailor?->name ?? '–' }}</td>
                            @endif
                            @if ($tailoring_order_item_report_visible_column['tailor_commission'] ?? true)
                                <td class="text-end">{{ $item->tailor_commission ? currency($item->tailor_commission) : '–' }}</td>
                            @endif
                            @if ($tailoring_order_item_report_visible_column['used_quantity'] ?? true)
                                <td class="text-end">{{ $item->used_quantity !== null ? number_format((float) $item->used_quantity, 3) : '–' }}</td>
                            @endif
                            @if ($tailoring_order_item_report_visible_column['wastage'] ?? true)
                                <td class="text-end">{{ $item->wastage !== null ? number_format((float) $item->wastage, 3) : '–' }}</td>
                            @endif
                            @if ($tailoring_order_item_report_visible_column['item_completion_date'] ?? true)
                                <td class="text-nowrap">{{ $item->item_completion_date ? systemDate($item->item_completion_date) : '–' }}</td>
                            @endif
                            @if ($tailoring_order_item_report_visible_column['is_selected_for_completion'] ?? true)
                                <td class="text-center">{{ $item->is_selected_for_completion ? 'Yes' : 'No' }}</td>
                            @endif
                            @if ($tailoring_order_item_report_visible_column['tailoring_notes'] ?? true)
                                <td class="text-truncate" style="max-width: 12rem;" title="{{ $item->tailoring_notes }}">{{ $item->tailoring_notes ?? '–' }}</td>
                            @endif
                            @if ($tailoring_order_item_report_visible_column['rating'] ?? true)
                                <td class="text-center">
                                    @if ($item->rating !== null && $item->rating > 0)
                                        <span class="text-warning" title="{{ $item->rating }}/5">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <i class="fa fa-star{{ $i <= $item->rating ? '' : '-o' }}"></i>
                                            @endfor
                                        </span>
                                    @else
                                        <span class="text-muted">–</span>
                                    @endif
                                </td>
                            @endif
                            @if ($tailoring_order_item_report_visible_column['status'] ?? true)
                                <td>
                                    @php $s = $item->status ?? 'pending'; @endphp
                                    <span class="badge bg-{{ $s === 'completed' ? 'success' : ($s === 'pending' ? 'warning' : 'info') }} bg-opacity-10 text-{{ $s === 'completed' ? 'success' : ($s === 'pending' ? 'warning' : 'info') }}">
                                        {{ (tailoringOrderItemStatuses())[$item->status] ?? $item->status }}
                                    </span>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="35" class="text-center py-4 text-muted">No items found.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-group-divider bg-light">
                    <tr>
                        <th class="ps-3">Total</th>
                        @if ($tailoring_order_item_report_visible_column['order_no'] ?? true)
                            <th></th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['order_date'] ?? true)
                            <th></th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['customer'] ?? true)
                            <th></th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['item_no'] ?? true)
                            <th></th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['category'] ?? true)
                            <th></th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['category_model'] ?? true)
                            <th></th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['product_name'] ?? true)
                            <th></th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['product_color'] ?? true)
                            <th></th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['unit'] ?? true)
                            <th></th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['quantity'] ?? true)
                            <th class="text-end fw-semibold">{{ number_format((float) $total['quantity'], 3) }}</th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['quantity_per_item'] ?? true)
                            <th></th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['completed_quantity'] ?? true)
                            <th class="text-end fw-semibold">{{ number_format((float) ($total['completed_quantity'] ?? 0), 3) }}</th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['unit_price'] ?? true)
                            <th></th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['stitch_rate'] ?? true)
                            <th></th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['gross_amount'] ?? true)
                            <th class="text-end fw-semibold">{{ currency($total['gross_amount']) }}</th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['discount'] ?? true)
                            <th class="text-end fw-semibold">{{ currency($total['discount']) }}</th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['net_amount'] ?? true)
                            <th class="text-end fw-semibold">{{ currency($total['net_amount']) }}</th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['tax'] ?? true)
                            <th></th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['tax_amount'] ?? true)
                            <th class="text-end fw-semibold">{{ currency($total['tax_amount']) }}</th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['total'] ?? true)
                            <th class="text-end fw-semibold text-primary">{{ currency($total['total']) }}</th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['tailor'] ?? true)
                            <th></th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['tailor_commission'] ?? true)
                            <th class="text-end fw-semibold">{{ currency($total['tailor_commission'] ?? 0) }}</th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['used_quantity'] ?? true)
                            <th class="text-end fw-semibold">{{ number_format((float) ($total['used_quantity'] ?? 0), 3) }}</th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['wastage'] ?? true)
                            <th class="text-end fw-semibold">{{ number_format((float) ($total['wastage'] ?? 0), 3) }}</th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['item_completion_date'] ?? true)
                            <th></th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['is_selected_for_completion'] ?? true)
                            <th></th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['tailoring_notes'] ?? true)
                            <th></th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['rating'] ?? true)
                            <th></th>
                        @endif
                        @if ($tailoring_order_item_report_visible_column['status'] ?? true)
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
                $(document).on('change', '#tailoring_item_report_customer_id', function() {
                    @this.set('customer_id', $(this).val() || '');
                });
                $(document).on('change', '#tailoring_item_report_product_id', function() {
                    @this.set('product_id', $(this).val() || '');
                });
                Livewire.on('tailoring-item-report-filters-reset', function() {
                    var cust = document.getElementById('tailoring_item_report_customer_id');
                    var prod = document.getElementById('tailoring_item_report_product_id');
                    if (cust && cust.tomselect) cust.tomselect.clear();
                    if (prod && prod.tomselect) prod.tomselect.clear();
                });
            });
        </script>
    @endpush
    @push('styles')
        <style>
            .tailoring-item-report .cursor-pointer {
                cursor: pointer;
            }

            .tailoring-item-report .min-w-200 {
                min-width: 12rem;
            }

            .tailoring-item-report .filter-panel .form-label {
                font-size: 0.75rem;
            }
        </style>
    @endpush
</div>

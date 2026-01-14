<div>
    <div class="card-header">
        <div class="row">
            <div class="col-md-6 d-flex gap-1 align-items-center mb-3">
                <div class="btn-group">
                    <button class="btn btn-icon btn-outline-light" title="To export the items as excel" wire:click="export()"><i class="demo-pli-file-excel fs-5"></i></button>
                </div>
            </div>
            <div class="col-md-6 d-flex gap-2 align-items-center justify-content-md-end mb-3">
                <div class="form-group">
                    <select wire:model.live="limit" class="form-control">
                        <option value="10">10</option>
                        <option value="100">100</option>
                        <option value="500">500</option>
                    </select>
                </div>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="demo-pli-layout-grid me-1"></i> Columns
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li>
                            <a class="dropdown-item" data-bs-toggle="offcanvas" data-bs-target="#purchaseItemReportColumnVisibility" aria-controls="purchaseItemReportColumnVisibility">
                                <i class="demo-pli-column-width me-2"></i>Column Visibility
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <hr>
        <div class="col-lg-12">
            <div class="row">
                <div class="col-md-2">
                    <label for="from_date">From Date</label>
                    {{ html()->date('from_date')->value('')->class('form-control')->id('from_date')->attribute('wire:model.live', 'from_date') }}
                </div>
                <div class="col-md-2">
                    <label for="to_date">To Date</label>
                    {{ html()->date('to_date')->value('')->class('form-control')->id('from_date')->attribute('wire:model.live', 'to_date') }}
                </div>
                <div class="col-md-2" wire:ignore>
                    <label for="branch_id">Branch</label>
                    {{ html()->select('branch_id', [session('branch_id') => session('branch_name')])->class('select-assigned-branch_id-list')->id('branch_id')->placeholder('All') }}
                </div>
                <div class="col-md-6" wire:ignore>
                    <label for="product_id">Product</label>
                    {{ html()->select('product_id', [])->value('')->class('select-product_id-list')->attribute('type', 'product')->id('product_id')->placeholder('Product') }}
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-sm">
                <thead>
                    <tr class="text-capitalize">
                        @if ($purchase_item_report_visible_column['id'] ?? true)
                            <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="purchase_items.id" label="id" /> </th>
                        @endif
                        @if ($purchase_item_report_visible_column['date'] ?? true)
                            <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="purchases.date" label="date" /> </th>
                        @endif
                        @if ($purchase_item_report_visible_column['invoice_no'] ?? true)
                            <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="purchases.invoice_no" label="invoice no" /> </th>
                        @endif
                        @if ($purchase_item_report_visible_column['product_name'] ?? true)
                            <th> product </th>
                        @endif
                        @if ($purchase_item_report_visible_column['unit_name'] ?? true)
                            <th> unit </th>
                        @endif
                        @if ($purchase_item_report_visible_column['unit_price'] ?? true)
                            <th class="text-end">
                                <div class="d-flex justify-content-end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="unit_price" label="unit price" /></div>
                            </th>
                        @endif
                        @if ($purchase_item_report_visible_column['quantity'] ?? true)
                            <th class="text-end">
                                <div class="d-flex justify-content-end align-items-center gap-1">
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="quantity" label="quantity" />
                                    <i class="demo-pli-information fs-6 text-info" data-bs-toggle="tooltip" data-bs-placement="top" title="Quantity will always show the base unit"></i>
                                </div>
                            </th>
                        @endif
                        @if ($purchase_item_report_visible_column['quantity'] ?? true)
                            <th class="text-end">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="base_unit_quantity" label="base unit quantity" />
                            </th>
                        @endif
                        @if ($purchase_item_report_visible_column['gross_amount'] ?? true)
                            <th class="text-end">
                                <div class="d-flex justify-content-end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="gross_amount" label="gross amount" /></div>
                            </th>
                        @endif
                        @if ($purchase_item_report_visible_column['discount'] ?? true)
                            <th class="text-end">
                                <div class="d-flex justify-content-end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="discount" label="discount" /></div>
                            </th>
                        @endif
                        @if ($purchase_item_report_visible_column['net_amount'] ?? true)
                            <th class="text-end">
                                <div class="d-flex justify-content-end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="net_amount" label="net amount" /></div>
                            </th>
                        @endif
                        @if ($purchase_item_report_visible_column['tax_amount'] ?? true)
                            <th class="text-end">
                                <div class="d-flex justify-content-end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tax_amount" label="tax amount" /></div>
                            </th>
                        @endif
                        @if ($purchase_item_report_visible_column['total'] ?? true)
                            <th class="text-end">
                                <div class="d-flex justify-content-end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="total" label="total" /></div>
                            </th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data as $item)
                        <tr>
                            @if ($purchase_item_report_visible_column['id'] ?? true)
                                <td>{{ $item->id }}</td>
                            @endif
                            @if ($purchase_item_report_visible_column['date'] ?? true)
                                <td>{{ systemDate($item->date) }}</td>
                            @endif
                            @if ($purchase_item_report_visible_column['invoice_no'] ?? true)
                                <td>{{ $item->invoice_no }}</td>
                            @endif
                            @if ($purchase_item_report_visible_column['product_name'] ?? true)
                                <td>{{ $item->product?->name }}</td>
                            @endif
                            @if ($purchase_item_report_visible_column['unit_name'] ?? true)
                                <td>
                                    @if ($item->product?->unit_id != $item->unit_id)
                                        {{ $item->unit?->name }}|{{ $item->product?->unit?->name }}
                                    @else
                                        {{ $item->unit?->name }}
                                    @endif
                                </td>
                            @endif
                            @if ($purchase_item_report_visible_column['unit_price'] ?? true)
                                <td class="text-end">{{ $item->unit_price }}</td>
                            @endif
                            @if ($purchase_item_report_visible_column['quantity'] ?? true)
                                <td class="text-end"> {{ $item->quantity }} </td>
                            @endif
                            @if ($purchase_item_report_visible_column['base_unit_quantity'] ?? true)
                                <td class="text-end"> {{ $item->base_unit_quantity }} </td>
                            @endif
                            @if ($purchase_item_report_visible_column['gross_amount'] ?? true)
                                <td class="text-end">{{ $item->gross_amount }}</td>
                            @endif
                            @if ($purchase_item_report_visible_column['discount'] ?? true)
                                <td class="text-end">{{ $item->discount != 0 ? currency($item->discount) : '-' }}</td>
                            @endif
                            @if ($purchase_item_report_visible_column['net_amount'] ?? true)
                                <td class="text-end">{{ $item->net_amount }}</td>
                            @endif
                            @if ($purchase_item_report_visible_column['tax_amount'] ?? true)
                                <td class="text-end">{{ $item->tax_amount != 0 ? currency($item->tax_amount) : '-' }}</td>
                            @endif
                            @if ($purchase_item_report_visible_column['total'] ?? true)
                                <td class="text-end">{{ $item->total }}</td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        @php
                            $colspan = 1;
                            $colspan += $purchase_item_report_visible_column['id'] ?? true ? 1 : 0;
                            $colspan += $purchase_item_report_visible_column['date'] ?? true ? 1 : 0;
                            $colspan += $purchase_item_report_visible_column['invoice_no'] ?? true ? 1 : 0;
                            $colspan += $purchase_item_report_visible_column['product_name'] ?? true ? 1 : 0;
                            $colspan += $purchase_item_report_visible_column['unit_name'] ?? true ? 1 : 0;
                        @endphp
                        <th colspan="{{ max($colspan, 1) }}">Total</th>
                        @if ($purchase_item_report_visible_column['quantity'] ?? true)
                            <th class="text-end">{{ currency($total['quantity'], 3) }}</th>
                        @endif
                        @if ($purchase_item_report_visible_column['base_unit_quantity'] ?? true)
                            <th class="text-end">{{ currency($total['base_unit_quantity'], 3) }}</th>
                        @endif
                        @if ($purchase_item_report_visible_column['gross_amount'] ?? true)
                            <th class="text-end">{{ currency($total['gross_amount']) }}</th>
                        @endif
                        @if ($purchase_item_report_visible_column['discount'] ?? true)
                            <th class="text-end">{{ currency($total['discount']) }}</th>
                        @endif
                        @if ($purchase_item_report_visible_column['net_amount'] ?? true)
                            <th class="text-end">{{ currency($total['net_amount']) }}</th>
                        @endif
                        @if ($purchase_item_report_visible_column['tax_amount'] ?? true)
                            <th class="text-end">{{ currency($total['tax_amount']) }}</th>
                        @endif
                        @if ($purchase_item_report_visible_column['total'] ?? true)
                            <th class="text-end">{{ currency($total['total']) }}</th>
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
                $('#product_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('product_id', value);
                });
                $('#employee_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('employee_id', value);
                });
                $('#branch_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('branch_id', value);
                });

                // Initialize tooltips
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            });
        </script>
    @endpush
</div>

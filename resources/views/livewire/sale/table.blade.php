<div>
    <div class="card-header">
        <div class="row">
            <div class="col-md-6 d-flex gap-1 align-items-center mb-3">
                <div class="btn-group">
                    @can('sale.export')
                        <button class="btn btn-icon btn-outline-light" title="To export the items as excel" wire:click="export()"><i class="demo-pli-file-excel fs-5"></i></button>
                    @endcan
                    @can('sale.delete')
                        <button class="btn btn-icon btn-outline-light" title="To delete the selected items" wire:click="delete()" wire:confirm="Are you sure you want to delete the selected items?">
                            <i class="demo-pli-recycling fs-5"></i>
                        </button>
                    @endcan
                </div>
            </div>
            <div class="col-md-6 d-flex gap-1 align-items-center justify-content-md-end mb-3">
                <div class="form-group">
                    <select wire:model.live="limit" class="form-control">
                        <option value="10">10</option>
                        <option value="100">100</option>
                        <option value="500">500</option>
                    </select>
                </div>
                <div class="form-group">
                    <input type="text" wire:model.live="search" autofocus placeholder="Search..." class="form-control" autocomplete="off">
                </div>
                <div class="dropdown">
                    <button class="btn btn-icon btn-outline-light" data-bs-toggle="dropdown" aria-expanded="true">
                        <i class="demo-pli-dot-horizontal fs-5"></i>
                        <span class="visually-hidden">Toggle Dropdown</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" style="position: absolute; inset: auto 0px 0px auto; margin: 0px; transform: translate3d(-0.5px, -40px, 0px);"
                        data-popper-placement="top-end">
                        <li><a class="dropdown-item" data-bs-toggle="offcanvas" data-bs-target="#saleColumnVisibility" aria-controls="saleColumnVisibility">Column Visibility</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <hr>
        <div class="col-lg-12">
            <div class="row">
                <div class="col-md-3">
                    <b><label for="from_date">From Date</label></b>
                    {{ html()->date('from_date')->value('')->class('form-control')->attribute('wire:model.live', 'from_date') }}
                </div>
                <div class="col-md-3">
                    <b><label for="to_date">To Date</label></b>
                    {{ html()->date('to_date')->value('')->class('form-control')->attribute('wire:model.live', 'to_date') }}
                </div>
                <div class="col-md-3" wire:ignore>
                    <b><label for="customer_id">Customer</label></b>
                    {{ html()->select('customer_id', [])->value('')->class('select-customer_id-list')->id('customer_id')->placeholder('All') }}
                </div>
                <div class="col-md-3" wire:ignore>
                    <b><label for="sale_type">Sale Type</label></b>
                    {{ html()->select('sale_type', priceTypes())->value('')->class('tomSelect')->id('sale_type')->placeholder('All') }}
                </div>
            </div>
            <div class="row my-2">
                <div class="col-md-3" wire:ignore>
                    <b><label for="branch_id">Branch</label></b>
                    {{ html()->select('branch_id', [auth()->user()->default_branch_id => auth()->user()->branch?->name])->value(auth()->user()->default_branch_id)->class('select-assigned-branch_id-list')->id('branch_id')->placeholder('All') }}
                </div>
                <div class="col-md-3" wire:ignore>
                    <b><label for="created_by">Created By</label></b>
                    {{ html()->select('created_by', [])->value('')->class('select-user_id-list')->id('created_by')->placeholder('All') }}
                </div>
                <div class="col-md-3" wire:ignore>
                    <b><label for="payment_method_id">Payment Method</label></b>
                    {{ html()->select('payment_method_id', [])->value('')->class('select-payment_method_id-list')->id('payment_method_id')->placeholder('All') }}
                </div>
                <div class="col-md-3" wire:ignore>
                    <b><label for="status">Status</label></b>
                    {{ html()->select('status', saleStatuses())->value($status)->class('tomSelect')->id('status')->placeholder('All') }}
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped align-middle table-sm table-bordered">
                <thead>
                    <tr class="text-capitalize">
                        <th>
                            <input type="checkbox" wire:model.live="selectAll" />
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="sales.id" label="#" />
                        </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="date" label="date" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="invoice_no" label="invoice no" /> </th>
                        @if ($sale_visible_column['reference_no'])
                            <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="reference_no" label="reference no" /> </th>
                        @endif
                        @if ($sale_visible_column['branch_id'])
                            <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="branch_id" label="branch" /> </th>
                        @endif
                        @if ($sale_visible_column['created_by'] ?? '')
                            <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="created_by" label="created By" /> </th>
                        @endif
                        @if ($sale_visible_column['customer'])
                            <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="accounts.name" label="Customer" /> </th>
                        @endif
                        @if ($sale_visible_column['payment_method_name'] ?? '')
                            <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="payment_method_name" label="payment method" /> </th>
                        @endif
                        @if ($sale_visible_column['gross_amount'])
                            <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="gross_amount" label="Gross Amount" /> </th>
                        @endif
                        @if ($sale_visible_column['item_discount'])
                            <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="item_discount" label="item discount" /> </th>
                        @endif
                        @if ($sale_visible_column['tax_amount'])
                            <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tax_amount" label="tax amount" /> </th>
                        @endif
                        @if ($sale_visible_column['total'])
                            <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="total" label="total" /> </th>
                        @endif
                        @if ($sale_visible_column['other_discount'])
                            <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="other_discount" label="other discount" /> </th>
                        @endif
                        @if ($sale_visible_column['freight'])
                            <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="freight" label="freight" /> </th>
                        @endif
                        @if ($sale_visible_column['grand_total'])
                            <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="grand_total" label="grand total" /> </th>
                        @endif
                        @if ($sale_visible_column['paid'])
                            <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="paid" label="paid" /> </th>
                        @endif
                        @if ($sale_visible_column['balance'])
                            <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="balance" label="balance" /> </th>
                        @endif
                        @if ($sale_visible_column['status'])
                            <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="status" label="status" /> </th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data as $item)
                        <tr>
                            <td>
                                <input type="checkbox" value="{{ $item->id }}" wire:model.live="selected" />
                                {{ $item->id }}
                            </td>
                            <td>{{ systemDate($item->date) }}</td>
                            <td>
                                @if (in_array($item->status, ['completed', 'cancelled']))
                                    <a href="{{ route('sale::view', $item->id) }}">{{ $item->invoice_no }} </a>
                                @else
                                    <a href="{{ route('sale::edit', $item->id) }}">{{ $item->invoice_no }} </a>
                                @endif
                            </td>
                            @if ($sale_visible_column['reference_no'])
                                <td>{{ $item->reference_no }}</td>
                            @endif
                            @if ($sale_visible_column['branch_id'])
                                <td>{{ $item->branch?->name }}</td>
                            @endif
                            @if ($sale_visible_column['created_by'] ?? '')
                                <td>{{ $item->createdUser?->name }}</td>
                            @endif
                            @if ($sale_visible_column['customer'])
                                <td>{{ $item->name }}</td>
                            @endif
                            @if ($sale_visible_column['payment_method_name'] ?? '')
                                <td class="text-end">{{ $item->payment_method_name }}</td>
                            @endif
                            @if ($sale_visible_column['gross_amount'])
                                <td class="text-end">{{ currency($item->gross_amount) }}</td>
                            @endif
                            @if ($sale_visible_column['item_discount'])
                                <td class="text-end">{{ currency($item->item_discount) }}</td>
                            @endif
                            @if ($sale_visible_column['tax_amount'])
                                <td class="text-end">{{ currency($item->tax_amount) }}</td>
                            @endif
                            @if ($sale_visible_column['total'])
                                <td class="text-end">{{ currency($item->total) }}</td>
                            @endif
                            @if ($sale_visible_column['other_discount'])
                                <td class="text-end">{{ currency($item->other_discount) }}</td>
                            @endif
                            @if ($sale_visible_column['freight'])
                                <td class="text-end">{{ currency($item->freight) }}</td>
                            @endif
                            @if ($sale_visible_column['grand_total'])
                                <td class="text-end">{{ currency($item->grand_total) }}</td>
                            @endif
                            @if ($sale_visible_column['paid'])
                                <td class="text-end">{{ currency($item->paid) }}</td>
                            @endif
                            @if ($sale_visible_column['balance'])
                                <td class="text-end">{{ currency($item->balance) }}</td>
                            @endif
                            @if ($sale_visible_column['status'])
                                <td>{{ ucFirst($item->status) }}</td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th></th>
                        <th></th>
                        <th></th>
                        @if ($sale_visible_column['reference_no'])
                            <th></th>
                        @endif
                        @if ($sale_visible_column['branch_id'])
                            <th></th>
                        @endif
                        @if ($sale_visible_column['created_by'] ?? '')
                            <th></th>
                        @endif
                        @if ($sale_visible_column['customer'])
                            <th></th>
                        @endif
                        @if ($sale_visible_column['payment_method_name'] ?? '')
                            <th></th>
                        @endif
                        @if ($sale_visible_column['gross_amount'])
                            <th class="text-end">{{ currency($total['gross_amount']) }}</th>
                        @endif
                        @if ($sale_visible_column['item_discount'])
                            <th class="text-end">{{ currency($total['item_discount']) }}</th>
                        @endif
                        @if ($sale_visible_column['tax_amount'])
                            <th class="text-end">{{ currency($total['tax_amount']) }}</th>
                        @endif
                        @if ($sale_visible_column['total'])
                            <th class="text-end">{{ currency($total['total']) }}</th>
                        @endif
                        @if ($sale_visible_column['other_discount'])
                            <th class="text-end">{{ currency($total['other_discount']) }}</th>
                        @endif
                        @if ($sale_visible_column['freight'])
                            <th class="text-end">{{ currency($total['freight']) }}</th>
                        @endif
                        @if ($sale_visible_column['grand_total'])
                            <th class="text-end">{{ currency($total['grand_total']) }}</th>
                        @endif
                        @if ($sale_visible_column['paid'])
                            <th class="text-end">{{ currency($total['paid']) }}</th>
                        @endif
                        @if ($sale_visible_column['balance'])
                            <th class="text-end">{{ currency($total['balance']) }}</th>
                        @endif
                        @if ($sale_visible_column['status'])
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
                $('#created_by').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('created_by', value);
                });
                $('#payment_method_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('payment_method_id', value);
                });
                $('#sale_type').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('sale_type', value);
                });
            });
        </script>
    @endpush
</div>

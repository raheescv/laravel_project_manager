<div>
    <div class="card-header bg-white">
        <div class="row g-3">
            <div class="col-md-4 d-flex align-items-center">
                <div class="btn-group">
                    @can('sale.export')
                        <button class="btn btn-sm btn-outline-primary" title="Export as Excel" wire:click="export()">
                            <i class="demo-pli-file-excel me-1"></i> Export
                        </button>
                    @endcan
                    @can('sale.delete')
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
                            <option value="100">100 rows</option>
                            <option value="500">500 rows</option>
                        </select>
                    </div>
                    <div class="form-group" style="width: 250px;">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text border-end-0">
                                <i class="demo-pli-magnifi-glass"></i>
                            </span>
                            <input type="text" wire:model.live="search" class="form-control border-start-0" placeholder="Search sales..." autofocus>
                        </div>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="demo-pli-layout-grid me-1"></i> Columns
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li>
                                <a class="dropdown-item" data-bs-toggle="offcanvas" data-bs-target="#saleColumnVisibility" aria-controls="saleColumnVisibility">
                                    <i class="demo-pli-column-width me-2"></i>Column Visibility
                                </a>
                            </li>
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
                                <label class="form-label text-muted fw-semibold small mb-2" for="sale_type">
                                    <i class="demo-pli-tag me-1"></i> Sale Type
                                </label>
                                {{ html()->select('sale_type', priceTypes())->value('')->class('tomSelect')->id('sale_type')->placeholder('All Types') }}
                            </div>
                        </div>
                        <div class="col-md-3" wire:ignore>
                            <div class="form-group">
                                <label class="form-label text-muted fw-semibold small mb-2" for="branch_id">
                                    <i class="demo-psi-home me-1"></i> Branch
                                </label>
                                {{ html()->select('branch_id', [auth()->user()->default_branch_id => auth()->user()->branch?->name])->value(auth()->user()->default_branch_id)->class('select-assigned-branch_id-list')->id('branch_id')->placeholder('All Branches') }}
                            </div>
                        </div>
                        <div class="col-md-3" wire:ignore>
                            <div class="form-group">
                                <label class="form-label text-muted fw-semibold small mb-2" for="created_by">
                                    <i class="demo-pli-add-user me-1"></i> Created By
                                </label>
                                {{ html()->select('created_by', [])->value('')->class('select-user_id-list')->id('created_by')->placeholder('All Users') }}
                            </div>
                        </div>
                        <div class="col-md-3" wire:ignore>
                            <div class="form-group">
                                <label class="form-label text-muted fw-semibold small mb-2" for="payment_method_id">
                                    <i class="demo-pli-credit-card-2 me-1"></i> Payment Method
                                </label>
                                {{ html()->select('payment_method_id', [])->value('')->class('select-payment_method_id-list')->id('payment_method_id')->placeholder('All Methods') }}
                            </div>
                        </div>
                        <div class="col-md-3" wire:ignore>
                            <div class="form-group">
                                <label class="form-label text-muted fw-semibold small mb-2" for="status">
                                    <i class="fa fa-flag me-1"></i> Status
                                </label>
                                {{ html()->select('status', purchaseStatuses())->value($status)->class('form-select form-select-sm')->id('status')->placeholder('All Statuses') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body px-0 pb-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0 border-bottom">
                <thead class="bg-light text-nowrap">
                    <tr>
                        <th class="ps-3">
                            <div class="d-flex align-items-center">
                                <div class="form-check me-2">
                                    <input type="checkbox" class="form-check-input" wire:model.live="selectAll" id="selectAll">
                                    <label class="form-check-label" for="selectAll"></label>
                                </div>
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="id" label="#" />
                            </div>
                        </th>
                        @if ($sale_visible_column['created_at'])
                            <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="created_at" label="created at" /> </th>
                        @endif
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="date" label="date" /> </th>
                        <th class="text-nowrap"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="invoice_no" label="invoice no" /> </th>
                        @if ($sale_visible_column['reference_no'])
                            <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="reference_no" label="reference no" /> </th>
                        @endif
                        @if ($sale_visible_column['branch_id'])
                            <th class="text-nowrap"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="branch_id" label="branch" /> </th>
                        @endif
                        @if ($sale_visible_column['created_by'] ?? '')
                            <th class="text-nowrap"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="created_by" label="created By" /> </th>
                        @endif
                        @if ($sale_visible_column['customer'])
                            <th class="text-nowrap"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="accounts.name" label="customer" /> </th>
                        @endif
                        @if ($sale_visible_column['payment_method_name'] ?? '')
                            <th class="text-nowrap"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="payment_method_name" label="payment method" /> </th>
                        @endif
                        @if ($sale_visible_column['gross_amount'])
                            <th class="text-nowrap text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="gross_amount" label="Gross Amount" /> </th>
                        @endif
                        @if ($sale_visible_column['item_discount'])
                            <th class="text-nowrap text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="item_discount" label="item discount" /> </th>
                        @endif
                        @if ($sale_visible_column['tax_amount'])
                            <th class="text-nowrap text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tax_amount" label="tax amount" /> </th>
                        @endif
                        @if ($sale_visible_column['total'])
                            <th class="text-nowrap text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="total" label="total" /> </th>
                        @endif
                        @if ($sale_visible_column['other_discount'])
                            <th class="text-nowrap text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="other_discount" label="other discount" /> </th>
                        @endif
                        @if ($sale_visible_column['freight'])
                            <th class="text-nowrap text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="freight" label="freight" /> </th>
                        @endif
                        @if ($sale_visible_column['grand_total'])
                            <th class="text-nowrap text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="grand_total" label="grand total" /> </th>
                        @endif
                        @if ($sale_visible_column['paid'])
                            <th class="text-nowrap text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="paid" label="paid" /> </th>
                        @endif
                        @if ($sale_visible_column['balance'])
                            <th class="text-nowrap text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="balance" label="balance" /> </th>
                        @endif
                        @if ($sale_visible_column['status'])
                            <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="status" label="status" /> </th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data as $item)
                        <tr>
                            <td class="ps-3">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="form-check mb-0">
                                        <input type="checkbox" class="form-check-input" value="{{ $item->id }}" wire:model.live="selected">
                                    </div>
                                    <span class="text-muted">#{{ $item->id }}</span>
                                </div>
                            </td>
                            <td class="text-nowrap">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="demo-psi-calendar-4 fs-5 text-primary"></i>
                                    <span>{{ systemDate($item->date) }}</span>
                                </div>
                            </td>
                            @if ($sale_visible_column['created_at'])
                                <td>{{ systemDateTime($item->created_at) }}</td>
                            @endif
                            <td class="text-nowrap">
                                <a href="{{ route('sale::view', $item->id) }}" class="text-primary fw-semibold text-decoration-none">
                                    {{ $item->invoice_no }}
                                </a>
                            </td>
                            @if ($sale_visible_column['reference_no'])
                                <td>{{ $item->reference_no }}</td>
                            @endif
                            @if ($sale_visible_column['branch_id'])
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="demo-psi-home fs-5 text-info"></i>
                                        <span>{{ $item->branch?->name }}</span>
                                    </div>
                                </td>
                            @endif
                            @if ($sale_visible_column['created_by'] ?? '')
                                <td class="text-nowrap">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fa fa-user fs-5 text-primary"></i>
                                        <span>{{ $item->createdUser?->name }}</span>
                                    </div>
                                </td>
                            @endif
                            @if ($sale_visible_column['customer'])
                                <td class="text-nowrap">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="demo-psi-building fs-5 text-warning"></i>
                                        <span>{{ $item->name }}</span>
                                    </div>
                                </td>
                            @endif
                            @if ($sale_visible_column['payment_method_name'])
                                <td class="text-nowrap">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fa fa-money fs-5 text-success"></i>
                                        <span>{{ $item->payment_method_name ?? '' }}</span>
                                    </div>
                                </td>
                            @endif
                            @if ($sale_visible_column['gross_amount'])
                                <td>
                                    <div class="text-end fw-medium">{{ currency($item->gross_amount) }}</div>
                                </td>
                            @endif
                            @if ($sale_visible_column['item_discount'])
                                <td>
                                    <div class="text-end text-danger fw-medium">-{{ currency($item->item_discount) }}</div>
                                </td>
                            @endif
                            @if ($sale_visible_column['tax_amount'])
                                <td>
                                    <div class="text-end fw-medium">{{ currency($item->tax_amount) }}</div>
                                </td>
                            @endif
                            @if ($sale_visible_column['total'])
                                <td>
                                    <div class="text-end fw-semibold">{{ currency($item->total) }}</div>
                                </td>
                            @endif
                            @if ($sale_visible_column['other_discount'])
                                <td>
                                    <div class="text-end text-danger fw-medium">-{{ currency($item->other_discount) }}</div>
                                </td>
                            @endif
                            @if ($sale_visible_column['freight'])
                                <td>
                                    <div class="text-end fw-medium">{{ currency($item->freight) }}</div>
                                </td>
                            @endif
                            @if ($sale_visible_column['grand_total'])
                                <td>
                                    <div class="text-end fw-bold text-primary">{{ currency($item->grand_total) }}</div>
                                </td>
                            @endif
                            @if ($sale_visible_column['paid'])
                                <td>
                                    <div class="text-end text-success fw-semibold">{{ currency($item->paid) }}</div>
                                </td>
                            @endif
                            @if ($sale_visible_column['balance'])
                                <td>
                                    <div class="text-end text-danger fw-semibold">{{ currency($item->balance) }}</div>
                                </td>
                            @endif
                            @if ($sale_visible_column['status'])
                                <td>
                                    <div
                                        class="badge bg-{{ $item->status === 'completed' ? 'success' : ($item->status === 'draft' ? 'warning' : 'danger') }} bg-opacity-10 text-{{ $item->status === 'completed' ? 'success' : ($item->status === 'draft' ? 'warning' : 'danger') }}">
                                        {{ ucFirst($item->status) }}
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-group-divider">
                    <tr class="bg-light">
                        <th colspan="3" class="ps-3"><strong>TOTALS</strong></th>
                        @if ($sale_visible_column['created_at'])
                            <th></th>
                        @endif
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
                        @if ($sale_visible_column['payment_method_name'])
                            <th></th>
                        @endif
                        @if ($sale_visible_column['gross_amount'])
                            <th>
                                <div class="text-end fw-bold">{{ currency($total['gross_amount']) }}</div>
                            </th>
                        @endif
                        @if ($sale_visible_column['item_discount'])
                            <th>
                                <div class="text-end text-danger fw-bold">-{{ currency($total['item_discount']) }}</div>
                            </th>
                        @endif
                        @if ($sale_visible_column['tax_amount'])
                            <th>
                                <div class="text-end fw-bold">{{ currency($total['tax_amount']) }}</div>
                            </th>
                        @endif
                        @if ($sale_visible_column['total'])
                            <th>
                                <div class="text-end fw-bold">{{ currency($total['total']) }}</div>
                            </th>
                        @endif
                        @if ($sale_visible_column['other_discount'])
                            <th>
                                <div class="text-end text-danger fw-bold">-{{ currency($total['other_discount']) }}</div>
                            </th>
                        @endif
                        @if ($sale_visible_column['freight'])
                            <th>
                                <div class="text-end fw-bold">{{ currency($total['freight']) }}</div>
                            </th>
                        @endif
                        @if ($sale_visible_column['grand_total'])
                            <th>
                                <div class="text-end fw-bold text-primary">{{ currency($total['grand_total']) }}</div>
                            </th>
                        @endif
                        @if ($sale_visible_column['paid'])
                            <th>
                                <div class="text-end text-success fw-bold">{{ currency($total['paid']) }}</div>
                            </th>
                        @endif
                        @if ($sale_visible_column['balance'])
                            <th>
                                <div class="text-end text-danger fw-bold">{{ currency($total['balance']) }}</div>
                            </th>
                        @endif
                        @if ($sale_visible_column['status'])
                            <th>
                            </th>
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

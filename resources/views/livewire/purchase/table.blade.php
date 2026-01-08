<div>
    <div class="card-header bg-white">
        <div class="row g-3">
            <div class="col-md-4 d-flex align-items-center">
                <div class="btn-group">
                    @can('purchase.export')
                        <button class="btn btn-sm btn-outline-primary" title="Export as Excel" wire:click="export()">
                            <i class="demo-pli-file-excel me-1"></i> Export
                        </button>
                    @endcan
                    @can('purchase.delete')
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
                            <input type="text" wire:model.live="search" class="form-control border-start-0" placeholder="Search purchase..." autofocus>
                        </div>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="demo-pli-layout-grid me-1"></i> Columns
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li>
                                <a class="dropdown-item" data-bs-toggle="offcanvas" data-bs-target="#purchaseColumnVisibility" aria-controls="purchaseColumnVisibility">
                                    <i class="demo-pli-column-width me-2"></i>Column Visibility
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <hr class="mt-3 mb-0">
        <div class="col-12  mt-3">
            <div class="bg-light p-3 rounded">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label" for="from_date">
                            <i class="demo-psi-calendar-4 me-1"></i> From Date
                        </label>
                        {{ html()->date('from_date')->value('')->class('form-control form-control-sm')->id('unit_id')->attribute('wire:model.live', 'from_date') }}
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="to_date">
                            <i class="demo-psi-calendar-4 me-1"></i> To Date
                        </label>
                        {{ html()->date('to_date')->value('')->class('form-control form-control-sm')->id('unit_id')->attribute('wire:model.live', 'to_date') }}
                    </div>
                    <div class="col-md-6" wire:ignore>
                        <label class="form-label" for="vendor_id">
                            <i class="demo-psi-building me-1"></i> Vendor
                        </label>
                        {{ html()->select('vendor_id', [])->value('')->class('select-vendor_id-list')->id('vendor_id')->placeholder('All Vendors') }}
                    </div>
                    <div class="col-md-3" wire:ignore>
                        <label class="form-label" for="branch_id">
                            <i class="demo-psi-home me-1"></i> Branch
                        </label>
                        {{ html()->select('branch_id', [session('branch_id') => session('branch_name')])->class('select-assigned-branch_id-list')->id('branch_id')->placeholder('All Branches') }}
                    </div>
                    <div class="col-md-3" wire:ignore>
                        <label class="form-label" for="status">
                            <i class="fa fa-flag me-1"></i> Status
                        </label>
                        {{ html()->select('status', purchaseStatuses())->value($status)->class('form-control form-control-sm')->id('status')->placeholder('All Statuses') }}
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
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="date" label="date" /> </th>
                        <th class="text-nowrap"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="invoice_no" label="invoice no" /> </th>
                        @if ($purchase_visible_column['branch_id'])
                            <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="branch_id" label="branch" /> </th>
                        @endif
                        @if ($purchase_visible_column['vendor'])
                            <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="accounts.name" label="Vendor" /> </th>
                        @endif
                        @if ($purchase_visible_column['gross_amount'])
                            <th class="text-nowrap text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="gross_amount" label="Gross Amount" /> </th>
                        @endif
                        @if ($purchase_visible_column['item_discount'])
                            <th class="text-nowrap text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="item_discount" label="item discount" /> </th>
                        @endif
                        @if ($purchase_visible_column['tax_amount'])
                            <th class="text-nowrap text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tax_amount" label="tax amount" /> </th>
                        @endif
                        @if ($purchase_visible_column['total'])
                            <th class="text-nowrap text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="total" label="total" /> </th>
                        @endif
                        @if ($purchase_visible_column['other_discount'])
                            <th class="text-nowrap text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="other_discount" label="other discount" /> </th>
                        @endif
                        @if ($purchase_visible_column['freight'])
                            <th class="text-nowrap text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="freight" label="freight" /> </th>
                        @endif
                        @if ($purchase_visible_column['grand_total'])
                            <th class="text-nowrap text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="grand_total" label="grand total" /> </th>
                        @endif
                        @if ($purchase_visible_column['paid'])
                            <th class="text-nowrap text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="paid" label="paid" /> </th>
                        @endif
                        @if ($purchase_visible_column['balance'])
                            <th class="text-nowrap text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="balance" label="balance" /> </th>
                        @endif
                        @if ($purchase_visible_column['status'])
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
                            <td class="text-nowrap">
                                <a href="{{ route('purchase::edit', $item->id) }}" class="text-primary fw-semibold text-decoration-none">
                                    {{ $item->invoice_no }}
                                </a>
                            </td>
                            @if ($purchase_visible_column['branch_id'])
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="demo-psi-home fs-5 text-info"></i>
                                        <span>{{ $item->branch?->name }}</span>
                                    </div>
                                </td>
                            @endif
                            @if ($purchase_visible_column['vendor'])
                                <td class="text-nowrap">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="demo-psi-building fs-5 text-warning"></i>
                                        <span>{{ $item->name }}</span>
                                    </div>
                                </td>
                            @endif
                            @if ($purchase_visible_column['gross_amount'])
                                <td>
                                    <div class="text-end fw-medium">{{ currency($item->gross_amount) }}</div>
                                </td>
                            @endif
                            @if ($purchase_visible_column['item_discount'])
                                <td>
                                    <div class="text-end text-danger fw-medium">-{{ currency($item->item_discount) }}</div>
                                </td>
                            @endif
                            @if ($purchase_visible_column['tax_amount'])
                                <td>
                                    <div class="text-end fw-medium">{{ currency($item->tax_amount) }}</div>
                                </td>
                            @endif
                            @if ($purchase_visible_column['total'])
                                <td>
                                    <div class="text-end fw-semibold">{{ currency($item->total) }}</div>
                                </td>
                            @endif
                            @if ($purchase_visible_column['other_discount'])
                                <td>
                                    <div class="text-end text-danger fw-medium">-{{ currency($item->other_discount) }}</div>
                                </td>
                            @endif
                            @if ($purchase_visible_column['freight'])
                                <td>
                                    <div class="text-end fw-medium">{{ currency($item->freight) }}</div>
                                </td>
                            @endif
                            @if ($purchase_visible_column['grand_total'])
                                <td>
                                    <div class="text-end fw-bold text-primary">{{ currency($item->grand_total) }}</div>
                                </td>
                            @endif
                            @if ($purchase_visible_column['paid'])
                                <td>
                                    <div class="text-end text-success fw-semibold">{{ currency($item->paid) }}</div>
                                </td>
                            @endif
                            @if ($purchase_visible_column['balance'])
                                <td>
                                    <div class="text-end text-danger fw-semibold">{{ currency($item->balance) }}</div>
                                </td>
                            @endif
                            @if ($purchase_visible_column['status'])
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
                        @if ($purchase_visible_column['branch_id'])
                            <th></th>
                        @endif
                        @if ($purchase_visible_column['vendor'])
                            <th></th>
                        @endif
                        @if ($purchase_visible_column['gross_amount'])
                            <th>
                                <div class="text-end fw-bold">{{ currency($total['gross_amount']) }}</div>
                            </th>
                        @endif
                        @if ($purchase_visible_column['item_discount'])
                            <th>
                                <div class="text-end text-danger fw-bold">-{{ currency($total['item_discount']) }}</div>
                            </th>
                        @endif
                        @if ($purchase_visible_column['tax_amount'])
                            <th>
                                <div class="text-end fw-bold">{{ currency($total['tax_amount']) }}</div>
                            </th>
                        @endif
                        @if ($purchase_visible_column['total'])
                            <th>
                                <div class="text-end fw-bold">{{ currency($total['total']) }}</div>
                            </th>
                        @endif
                        @if ($purchase_visible_column['other_discount'])
                            <th>
                                <div class="text-end text-danger fw-bold">-{{ currency($total['other_discount']) }}</div>
                            </th>
                        @endif
                        @if ($purchase_visible_column['freight'])
                            <th>
                                <div class="text-end fw-bold">{{ currency($total['freight']) }}</div>
                            </th>
                        @endif
                        @if ($purchase_visible_column['grand_total'])
                            <th>
                                <div class="text-end fw-bold text-primary">{{ currency($total['grand_total']) }}</div>
                            </th>
                        @endif
                        @if ($purchase_visible_column['paid'])
                            <th>
                                <div class="text-end text-success fw-bold">{{ currency($total['paid']) }}</div>
                            </th>
                        @endif
                        @if ($purchase_visible_column['balance'])
                            <th>
                                <div class="text-end text-danger fw-bold">{{ currency($total['balance']) }}</div>
                            </th>
                        @endif
                        @if ($purchase_visible_column['status'])
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
                $('#vendor_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('vendor_id', value);
                });
            });
        </script>
    @endpush
</div>

<div>
    <div class="card-header bg-white">
        <div class="row g-3">
            <div class="col-md-4 d-flex align-items-center">
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-primary" title="Export as Excel" wire:click="export()">
                        <i class="demo-pli-file-excel me-1"></i> Export
                    </button>
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
                                <label class="form-label text-muted fw-semibold small mb-2" for="branch_id">
                                    <i class="demo-psi-home me-1"></i> Branch
                                </label>
                                {{ html()->select('branch_id', [session('branch_id') => session('branch_name')])->value(session('branch_id'))->class('select-assigned-branch_id-list')->id('branch_id')->placeholder('All Branches') }}
                            </div>
                        </div>
                        <div class="col-md-3" wire:ignore>
                            <div class="form-group">
                                <label class="form-label text-muted fw-semibold small mb-2" for="product_id">
                                    <i class="demo-pli-tag me-1"></i> Product
                                </label>
                                {{ html()->select('product_id', [])->value('')->class('select-product_id-list')->attribute('type', '')->id('product_id')->placeholder('All Products') }}
                            </div>
                        </div>
                        <div class="col-md-3" wire:ignore>
                            <div class="form-group">
                                <label class="form-label text-muted fw-semibold small mb-2" for="employee_id">
                                    <i class="fa fa-user me-1"></i> Employee
                                </label>
                                {{ html()->select('employee_id', [])->value('')->class('select-employee_id-list')->id('employee_id')->placeholder('All Employees') }}
                            </div>
                        </div>
                        <div class="col-md-3" wire:ignore>
                            <div class="form-group">
                                <label class="form-label text-muted fw-semibold small mb-2" for="status">
                                    <i class="fa fa-flag me-1"></i> Status
                                </label>
                                {{ html()->select('status', saleStatuses())->value('completed')->class('form-select form-select-sm')->id('status') }}
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
                        <th class="ps-3"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="sale_items.id" label="#" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="sales.date" label="date" /> </th>
                        <th class="text-nowrap"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="sales.invoice_no" label="invoice no" /> </th>
                        <th class="text-nowrap"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="employee_id" label="employee" /> </th>
                        <th class="text-nowrap"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="assistant_id" label="assistant" /> </th>
                        <th class="text-nowrap"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="product_id" label="product" /> </th>
                        <th class="text-nowrap"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="unit_id" label="unit" /> </th>
                        <th class="text-nowrap text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="unit_price" label="unit price" /> </th>
                        <th class="text-nowrap text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="quantity" label="quantity" /> </th>
                        <th class="text-nowrap text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="gross_amount" label="gross amount" /> </th>
                        <th class="text-nowrap text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="discount" label="discount" /> </th>
                        <th class="text-nowrap text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="net_amount" label="net amount" /> </th>
                        <th class="text-nowrap text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tax_amount" label="tax amount" /> </th>
                        <th class="text-nowrap text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="total" label="total" /> </th>
                        <th class="text-nowrap text-end">Effective Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data as $item)
                        <tr>
                            <td class="ps-3">
                                <span class="text-muted">#{{ $item->id }}</span>
                            </td>
                            <td class="text-nowrap">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="demo-psi-calendar-4 fs-5 text-primary"></i>
                                    <span>{{ systemDate($item->date) }}</span>
                                </div>
                            </td>
                            <td class="text-nowrap">
                                <a href="{{ route('sale::view', $item->sale_id) }}" class="text-primary fw-semibold text-decoration-none">
                                    {{ $item->invoice_no }}
                                </a>
                            </td>
                            <td class="text-nowrap">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fa fa-user fs-5 text-primary"></i>
                                    <span>{{ $item->employee?->name }}</span>
                                </div>
                            </td>
                            <td class="text-nowrap">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fa fa-user fs-5 text-info"></i>
                                    <span>{{ $item->assistant?->name }}</span>
                                </div>
                            </td>
                            <td class="text-nowrap">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="demo-pli-tag fs-5 text-warning"></i>
                                    <span>{{ $item->product?->name }}</span>
                                </div>
                            </td>
                            <td class="text-nowrap">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="demo-pli-tag fs-5 text-warning"></i>
                                    <span>{{ $item->unit?->name }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="text-end fw-medium">{{ currency($item->unit_price) }}</div>
                            </td>
                            <td>
                                <div class="text-end fw-medium">{{ currency($item->quantity) }}</div>
                            </td>
                            <td>
                                <div class="text-end fw-medium">{{ currency($item->gross_amount) }}</div>
                            </td>
                            <td>
                                <div class="text-end text-danger fw-medium">{{ $item->discount != 0 ? '-' : '' }}{{ $item->discount != 0 ? currency($item->discount) : '_' }}</div>
                            </td>
                            <td>
                                <div class="text-end fw-medium">{{ currency($item->net_amount) }}</div>
                            </td>
                            <td>
                                <div class="text-end fw-medium">{{ $item->tax_amount != 0 ? currency($item->tax_amount) : '_' }}</div>
                            </td>
                            <td>
                                <div class="text-end fw-semibold">{{ currency($item->total) }}</div>
                            </td>
                            <td>
                                <div class="text-end fw-bold text-primary">{{ $item->total != $item->effective_total ? currency($item->effective_total) : '_' }}</div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-group-divider">
                    <tr class="bg-light">
                        <th colspan="8" class="ps-3"><strong>TOTALS</strong></th>
                        <th>
                            <div class="text-end fw-bold">{{ currency($total['quantity']) }}</div>
                        </th>
                        <th>
                            <div class="text-end fw-bold">{{ currency($total['gross_amount']) }}</div>
                        </th>
                        <th>
                            <div class="text-end text-danger fw-bold">-{{ currency($total['discount']) }}</div>
                        </th>
                        <th>
                            <div class="text-end fw-bold">{{ currency($total['net_amount']) }}</div>
                        </th>
                        <th>
                            <div class="text-end fw-bold">{{ currency($total['tax_amount']) }}</div>
                        </th>
                        <th>
                            <div class="text-end fw-bold">{{ currency($total['total']) }}</div>
                        </th>
                        <th>
                            <div class="text-end fw-bold text-primary">{{ currency($total['effective_total']) }}</div>
                        </th>
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
                $('#branch_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('branch_id', value);
                });
                $('#employee_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('employee_id', value);
                });
                $('#status').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('status', value);
                });
            });
        </script>
    @endpush
</div>

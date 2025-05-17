<div>
    <div class="card-header">
        <div class="row">
            <div class="col-md-6 d-flex gap-1 align-items-center mb-3">
                <div class="btn-group">
                    @can('inventory.export')
                        <button class="btn btn-icon btn-outline-light" title="To export the items as excel" wire:click="export()"><i class="demo-pli-file-excel fs-5"></i></button>
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
                <div class="btn-group">
                    @can('inventory.import')
                        <button class="btn btn-icon btn-outline-light" data-bs-toggle="modal" data-bs-target="#ProductImportModal">
                            <i class="demo-pli-download-from-cloud fs-5"></i>
                        </button>
                    @endcan
                </div>
            </div>
        </div>
        <hr>
        <div class="col-lg-12">
            <div class="row">
                <div class="col-md-3 p-2" wire:ignore>
                    {{ html()->select('branch_id', [auth()->user()->default_branch_id => auth()->user()->branch?->name])->value(auth()->user()->default_branch_id)->class('select-assigned-branch_id-list')->id('branch_id')->placeholder('Branch') }}
                </div>
                <div class="col-md-3" wire:ignore>
                    {{ html()->select('department_id', [])->value('')->class('select-department_id-list')->id('department_id')->placeholder('Department') }}
                </div>
                <div class="col-md-3" wire:ignore>
                    {{ html()->select('main_category_id', [])->value('')->class('select-category_id-list')->id('main_category_id')->placeholder('Main Category') }}
                </div>
                <div class="col-md-3" wire:ignore>
                    {{ html()->select('sub_category_id', [])->value('')->class('select-category_id-list')->id('sub_category_id')->placeholder('Sub Category') }}
                </div>
            </div>
            <div class="row">
                <div class="col-md-6" wire:ignore>
                    {{ html()->select('product_id', [])->value('')->class('select-product_id-list')->attribute('type', 'product')->id('product_id')->placeholder('Product') }}
                </div>
                <div class="col-md-3">
                    <br>
                    <div class="form-check mb-4">
                        <label for="non_zero" class="form-check-label">
                            {{ html()->checkbox('non_zero', [])->value('')->class('form-check-input')->attribute('wire:model.live', 'non_zero') }}
                            Non Zero Only
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped align-middle table-sm">
                <thead>
                    <tr class="text-capitalize">
                        <th>
                            <input type="checkbox" wire:model.live="selectAll" />
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="inventories.id" label="id" />
                        </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="branches.name" label="Branch" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="departments.name" label="department" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="main_categories.name" label="Main Category" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="sub_categories.name" label="sub Category" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="units.name" label="unit" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="products.code" label="code" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="products.name" label="name" /> </th>
                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="inventories.quantity" label="qty" /> </th>
                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="inventories.cost" label="cost" /> </th>
                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="inventories.total" label="Total" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="inventories.barcode" label="barcode" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="inventories.batch" label="batch" /> </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data as $item)
                        <tr>
                            <td> {{ $item->id }} </td>
                            <td>{{ $item->branch_name ?? '' }}</td>
                            <td>{{ $item->department_name ?? '' }}</td>
                            <td>{{ $item->main_category_name }}</td>
                            <td>{{ $item->sub_category_name }}</td>
                            <td>{{ $item->unit_name }}</td>
                            <td>{{ $item->code }}</td>
                            <td>
                                <a href="{{ route('inventory::product::view', $item->product_id) }}">
                                    {{ $item->name }}
                                    @if ($item->name_arabic)
                                        <br>
                                        <span style="text-align: right; display: block;" dir="rtl">
                                            {{ $item->name_arabic }}
                                        </span>
                                    @endif
                                </a>
                            </td>
                            <td class="text-end">{{ $item->quantity }}</td>
                            <td class="text-end">{{ currency($item->cost) }}</td>
                            <th class="text-end">{{ currency($item->total) }}</th>
                            <td>{{ $item->barcode }}</td>
                            <td>{{ $item->batch }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <th class="text-end" colspan="10">Total</th>
                    <th class="text-end">{{ currency($total) }}</th>
                    <th></th>
                    <th></th>
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
                $('#department_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('department_id', value);
                });
                $('#main_category_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('main_category_id', value);
                });
                $('#sub_category_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('sub_category_id', value);
                });
                $('#product_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('product_id', value);
                });
            });
        </script>
    @endpush
</div>

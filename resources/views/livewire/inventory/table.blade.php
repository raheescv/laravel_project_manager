<div>
    <div class="card-header">
        <div class="row">
            <div class="col-md-6 d-flex gap-1 align-items-center mb-3">
                <div class="btn-group">
                    @can('inventory.export')
                        <button class="btn btn-icon btn-outline-light" title="To export the items as excel" wire:click="export()"><i class="demo-pli-file-excel fs-5"></i></button>
                    @endcan
                    @can('inventory.delete')
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
                <div class="col-md-3" wire:ignore>
                    {{ html()->select('department_id', [])->value('')->class('select-department_id-list')->id('department_id')->placeholder('Department') }}
                </div>
                <div class="col-md-3" wire:ignore>
                    {{ html()->select('main_category_id', [])->value('')->class('select-category_id-list')->id('main_category_id')->placeholder('Main Category') }}
                </div>
                <div class="col-md-3" wire:ignore>
                    {{ html()->select('sub_category_id', [])->value('')->class('select-category_id-list')->id('sub_category_id')->placeholder('Sub Category') }}
                </div>
                <div class="col-md-3" wire:ignore>
                    {{ html()->select('product_id', [])->value('')->class('select-product_id-list')->id('product_id')->placeholder('Product') }}
                </div>
            </div>
            <div class="row">
                <div class="col-md-3 p-2" wire:ignore>
                    {{ html()->select('branch_id', [])->value('')->class('select-branch_id-list')->id('branch_id')->placeholder('Branch') }}
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
                            <a href="#" wire:click.prevent="sortBy('inventories.id')">
                                #
                                @if ($sortField === 'inventories.id')
                                    {!! sortDirection($sortDirection) !!}
                                @endif
                            </a>
                        </th>
                        <th>
                            <a href="#" wire:click.prevent="sortBy('branches.name')">
                                Branch
                                @if ($sortField === 'branches.name')
                                    {!! sortDirection($sortDirection) !!}
                                @endif
                            </a>
                        </th>
                        <th>
                            <a href="#" wire:click.prevent="sortBy('departments.name')">
                                Department
                                @if ($sortField === 'departments.name')
                                    {!! sortDirection($sortDirection) !!}
                                @endif
                            </a>
                        </th>
                        <th>
                            <a href="#" wire:click.prevent="sortBy('main_categories.name')">
                                Main Category
                                @if ($sortField === 'main_categories.name')
                                    {!! sortDirection($sortDirection) !!}
                                @endif
                            </a>
                        </th>
                        <th>
                            <a href="#" wire:click.prevent="sortBy('sub_categories.name')">
                                Sub Category
                                @if ($sortField === 'sub_categories.name')
                                    {!! sortDirection($sortDirection) !!}
                                @endif
                            </a>
                        </th>
                        <th>
                            <a href="#" wire:click.prevent="sortBy('units.name')">
                                Unit
                                @if ($sortField === 'units.name')
                                    {!! sortDirection($sortDirection) !!}
                                @endif
                            </a>
                        </th>
                        <th>
                            <a href="#" wire:click.prevent="sortBy('products.code')">
                                Code
                                @if ($sortField === 'products.code')
                                    {!! sortDirection($sortDirection) !!}
                                @endif
                            </a>
                        </th>
                        <th>
                            <a href="#" wire:click.prevent="sortBy('products.name')">
                                Name
                                @if ($sortField === 'products.name')
                                    {!! sortDirection($sortDirection) !!}
                                @endif
                            </a>
                        </th>
                        <th class="text-end">
                            <a href="#" wire:click.prevent="sortBy('inventories.quantity')">
                                Qty
                                @if ($sortField === 'inventories.quantity')
                                    {!! sortDirection($sortDirection) !!}
                                @endif
                            </a>
                        </th>
                        <th class="text-end">
                            <a href="#" wire:click.prevent="sortBy('inventories.cost')">
                                cost
                                @if ($sortField === 'inventories.cost')
                                    {!! sortDirection($sortDirection) !!}
                                @endif
                            </a>
                        </th>
                        <th>
                            <a href="#" wire:click.prevent="sortBy('inventories.barcode')">
                                Barcode
                                @if ($sortField === 'inventories.barcode')
                                    {!! sortDirection($sortDirection) !!}
                                @endif
                            </a>
                        </th>
                        <th>
                            <a href="#" wire:click.prevent="sortBy('inventories.batch')">
                                Batch
                                @if ($sortField === 'inventories.batch')
                                    {!! sortDirection($sortDirection) !!}
                                @endif
                            </a>
                        </th>
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
                            <td>{{ $item->barcode }}</td>
                            <td>{{ $item->batch }}</td>
                        </tr>
                    @endforeach
                </tbody>
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

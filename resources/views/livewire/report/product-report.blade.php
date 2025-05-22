<div>
    <div class="card-header">
        <div class="row">
            <div class="col-md-5">
            </div>
            <div class="col-md-2">
                <input type="text" wire:model.live="barcode" class="form-control" placeholder="Search barcode...">
            </div>
            <div class="col-md-4">
                <input type="text" wire:model.live="search" class="form-control" placeholder="Search products...">
            </div>
            <div class="col-md-1">
                <div class="form-group">
                    <select wire:model.live="limit" class="form-control">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
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
                    {{ html()->date('to_date')->value('')->class('form-control')->id('to_date')->attribute('wire:model.live', 'to_date') }}
                </div>
                <div class="col-md-2" wire:ignore>
                    <label for="branch_id">Branch</label>
                    {{ html()->select('branch_id', [auth()->user()->default_branch_id => auth()->user()->branch?->name])->value(auth()->user()->default_branch_id)->class('select-assigned-branch_id-list')->id('branch_id')->placeholder('All') }}
                </div>
                <div class="col-md-3" wire:ignore>
                    <label for="main_category_id">Category</label>
                    {{ html()->select('main_category_id', [])->value('')->class('select-category_id-list')->id('main_category_id')->placeholder('All') }}
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped align-middle table-sm">
                <thead>
                    <tr class="text-capitalize">
                        <th width="40%"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="products.name" label="Product" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="products.code" label="Code" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="products.barcode" label="Barcode" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="category_name" label="Category" /> </th>
                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="current_stock" label="Current Stock" /> </th>
                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="total_sold" label="Total Sold" /> </th>
                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="total_purchased" label="Total Purchased" /> </th>
                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="transfer_in" label="Transfer In" /> </th>
                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="transfer_out" label="Transfer Out" /> </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($products as $product)
                        <tr>
                            <td> <a href="{{ route('inventory::product::view', $product->id) }}">{{ $product->name }}</a> </td>
                            <td>{{ $product->code }}</td>
                            <td>{{ $product->barcode }}</td>
                            <td>{{ $product->category_name }}</td>
                            <td class="text-end">{{ number_format($product->current_stock ?? 0) }}</td>
                            <td class="text-end">{{ number_format($product->total_sold ?? 0) }}</td>
                            <td class="text-end">{{ number_format($product->total_purchased ?? 0) }}</td>
                            <td class="text-end">{{ number_format($product->transfer_in ?? 0) }}</td>
                            <td class="text-end">{{ number_format($product->transfer_out ?? 0) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $products->links() }}
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
            });
        </script>
    @endpush
</div>

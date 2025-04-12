<div>
    <div class="card-header">
        <div class="row">
            <div class="col-md-6 d-flex gap-1 align-items-center mb-3">
                <div class="btn-group">
                    <button class="btn btn-icon btn-outline-light" title="To export the items as excel" wire:click="export()"><i class="demo-pli-file-excel fs-5"></i></button>
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
                    <label for="product_id">Product</label>
                    {{ html()->select('product_id', [])->value('')->class('select-product_id-list')->id('product_id')->placeholder('Product') }}
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-sm">
                <thead>
                    <tr class="text-capitalize">
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="created_at" label="Date" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="branch.name" label="Branch" /> </th>
                        <th>Department</th>
                        <th>Main Category</th>
                        <th>Sub Category</th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="product.name" label="Product" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="barcode" label="barcode" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="batch" label="batch" /> </th>
                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="quantity_in" label="In" /> </th>
                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="quantity_out" label="out" /> </th>
                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="balance" label="balance" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="remarks" label="remarks" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="user_name" label="User" /> </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data as $item)
                        <tr>
                            <td>{{ systemDateTime($item->created_at) }}</td>
                            <td>{{ $item->branch?->name }}</td>
                            <td>{{ $item->product?->department?->name }}</td>
                            <td>{{ $item->product?->mainCategory?->name }}</td>
                            <td>{{ $item->product?->subCategory?->name }}</td>
                            <td> <a href="{{ route('inventory::product::view', $item->product_id) }}">{{ $item->product?->name }}</a> </td>
                            <td>{{ $item->barcode }}</td>
                            <td>{{ $item->batch }}</td>
                            <td class="text-end">{{ $item->quantity_in }}</td>
                            <td class="text-end">{{ $item->quantity_out }}</td>
                            <td class="text-end">{{ $item->balance }}</td>
                            <td>
                                @php
                                    switch ($item->model) {
                                        case 'Sale':
                                            $href = route('sale::view', $item->model_id);
                                            break;
                                        case 'SaleReturn':
                                            $href = route('sale_return::view', $item->model_id);
                                            break;
                                        default:
                                            $href = '';
                                            break;
                                    }
                                @endphp
                                @if ($href)
                                    <a href="{{ $href }}">{{ $item->remarks }}</a>
                                @else
                                    {{ $item->remarks }}
                                @endif
                            </td>
                            <td>{{ $item->user_name }}</td>
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
                $('#product_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('product_id', value);
                });
                $('#branch_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('branch_id', value);
                });
            });
        </script>
    @endpush
</div>

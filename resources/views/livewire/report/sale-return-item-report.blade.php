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
                    {{-- <input type="text" wire:model.live="search" autofocus placeholder="Search..." class="form-control" autocomplete="off"> --}}
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
                <div class="col-md-3" wire:ignore>
                    <label for="branch_id">Branch</label>
                    {{ html()->select('branch_id', [session('branch_id') => session('branch_name')])->class('select-assigned-branch_id-list')->id('branch_id')->placeholder('All') }}
                </div>
                <div class="col-md-5" wire:ignore>
                    <label for="product_id">Product</label>
                    {{ html()->select('product_id', [])->value('')->class('select-product_id-list')->attribute('type', '')->id('product_id')->placeholder('Product') }}
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped align-middle table-sm">
                <thead>
                    <tr class="text-capitalize">
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="sale_return_items.id" label="id" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="sale_returns.date" label="date" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="sale_returns.reference_no" label="reference no" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="product_id" label="product" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="unit_id" label="unit" /> </th>
                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="unit_price" label="unit price" /> </th>
                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="quantity" label="quantity" /> </th>
                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="gross_amount" label="gross amount" /> </th>
                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="discount" label="discount" /> </th>
                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="net_amount" label="net amount" /> </th>
                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tax_amount" label="tax amount" /> </th>
                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="total" label="total" /> </th>
                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="effective_total" label="effective total" /> </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data as $item)
                        <tr>
                            <td> <a href="{{ route('sale_return::view', $item->sale_return_id) }}">{{ $item->id }}</a> </td>
                            <td>{{ systemDate($item->date) }}</td>
                            <td> <a href="{{ route('sale_return::view', $item->sale_return_id) }}">{{ $item->reference_no ? $item->reference_no : $item->sale_return_id }}</a> </td>
                            <td>{{ $item->product?->name }}</td>
                            <td>{{ $item->unit?->name }}</td>
                            <td class="text-end">{{ currency($item->unit_price) }}</td>
                            <td class="text-end">{{ currency($item->quantity) }}</td>
                            <td class="text-end">{{ currency($item->gross_amount) }}</td>
                            <td class="text-end">{{ currency($item->discount) }}</td>
                            <td class="text-end">{{ currency($item->net_amount) }}</td>
                            <td class="text-end">{{ currency($item->tax_amount) }}</td>
                            <td class="text-end">{{ currency($item->total) }}</td>
                            <td class="text-end">{{ currency($item->effective_total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="7">Total</th>
                        <th class="text-end">{{ currency($total['gross_amount']) }}</th>
                        <th class="text-end">{{ currency($total['discount']) }}</th>
                        <th class="text-end">{{ currency($total['net_amount']) }}</th>
                        <th class="text-end">{{ currency($total['tax_amount']) }}</th>
                        <th class="text-end">{{ currency($total['total']) }}</th>
                        <th class="text-end">{{ currency($total['effective_total']) }}</th>
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
            });
        </script>
    @endpush
</div>

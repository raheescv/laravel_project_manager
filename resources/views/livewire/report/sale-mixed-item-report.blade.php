<div>
    <div class="card-header">
        <div class="row">
            <div class="col-md-6 d-flex gap-1 align-items-center mb-3">
                <div class="btn-group">
                    {{-- Reserved for future export if needed --}}
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
            </div>
        </div>
        <hr>
        <div class="col-lg-12">
            <div class="row mb-2">
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
                    {{ html()->select('branch_id', [auth()->user()->default_branch_id => auth()->user()->branch?->name])->value(auth()->user()->default_branch_id)->class('select-assigned-branch_id-list')->id('branch_id')->placeholder('All') }}
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
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="type" label="type" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="date" label="date" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="reference" label="ref" /> </th>
                        <th> product </th>
                        <th> code </th>
                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="unit_price" label="unit price" /> </th>
                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="quantity" label="quantity" /> </th>
                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="gross_amount" label="gross" /> </th>
                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="discount" label="discount" /> </th>
                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="net_amount" label="net" /> </th>
                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tax_amount" label="tax" /> </th>
                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="total" label="total" /> </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data as $row)
                        <tr class="table-{{ $row->type === 'sale' ? 'success' : 'danger' }}">
                            <td>
                                <span class="badge bg-{{ $row->type === 'sale' ? 'success' : 'danger' }}">{{ $row->type === 'sale' ? 'Sale' : 'Return' }}</span>
                            </td>
                            <td class="text-nowrap">{{ systemDate($row->date) }}</td>
                            <td class="text-nowrap">
                                @if ($row->type === 'sale')
                                    <a href="{{ route('sale::view', $row->parent_id) }}">{{ $row->reference }}</a>
                                @else
                                    <a href="{{ route('sale_return::view', $row->parent_id) }}">{{ $row->reference }}</a>
                                @endif
                            </td>
                            <td class="text-nowrap">{{ $row->product_name }}</td>
                            <td class="text-nowrap">{{ $row->product_code }}</td>
                            <td class="text-end">{{ currency($row->unit_price) }}</td>
                            <td class="text-end">{{ currency($row->quantity) }}</td>
                            <td class="text-end">{{ currency($row->gross_amount) }}</td>
                            <td class="text-end">{{ $row->discount != 0 ? currency($row->discount) : '-' }}</td>
                            <td class="text-end">{{ currency($row->net_amount) }}</td>
                            <td class="text-end">{{ $row->tax_amount != 0 ? currency($row->tax_amount) : '-' }}</td>
                            <td class="text-end">{{ currency($row->total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="6" class="text-end">Total</th>
                        <th class="text-end">{{ currency($total['quantity'] ?? 0) }}</th>
                        <th class="text-end">{{ currency($total['gross_amount'] ?? 0) }}</th>
                        <th class="text-end">{{ currency($total['discount'] ?? 0) }}</th>
                        <th class="text-end">{{ currency($total['net_amount'] ?? 0) }}</th>
                        <th class="text-end">{{ currency($total['tax_amount'] ?? 0) }}</th>
                        <th class="text-end">{{ currency($total['total'] ?? 0) }}</th>
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
            });
        </script>
    @endpush
</div>


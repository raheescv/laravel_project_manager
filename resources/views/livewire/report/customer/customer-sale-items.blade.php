<div>
    <div class="card">
        <div class="card-header">
            <div class="card-tools">
                <div class="row g-2">
                    <div class="col-md-3" wire:ignore>
                        <b><label for="product_id">Product</label></b>
                        {{ html()->select('product_id', [])->value('')->class('select-product_id-list customer_sale_item_table_change')->id('item_product_id')->placeholder('All') }}
                    </div>
                    <div class="col-md-3" wire:ignore>
                        <b><label for="employee_id">Employee</label></b>
                        {{ html()->select('employee_id', [])->value('')->class('select-employee_id-list customer_sale_item_table_change')->id('item_employee_id')->placeholder('All') }}
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr class="bg-primary">
                            <th class="text-white">Date</th>
                            <th class="text-white">Invoice</th>
                            <th class="text-white">Customer</th>
                            <th class="text-white">Employee</th>
                            <th class="text-white">Item</th>
                            <th class="text-white text-end">Quantity</th>
                            <th class="text-white text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
                            <tr>
                                <td>{{ systemDate($item->date) }}</td>
                                <td> <a href="{{ route('sale::view', $item->sale_id) }}">{{ $item->invoice_no }} </a> </td>
                                <td>{{ $item->customer }}</td>
                                <td>{{ $item->employee }}</td>
                                <td>{{ $item->product }}</td>
                                <td class="text-end">{{ number_format($item->quantity) }}</td>
                                <td class="text-end">{{ currency($item->total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="fw-bold bg-light">
                        <tr>
                            <td colspan="5">Total</td>
                            <td class="text-end">{{ number_format($totalQuantity) }}</td>
                            <td class="text-end">{{ currency($totalAmount) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="mt-3">
                {{ $items->links() }}
            </div>
        </div>
    </div>
    @push('scripts')
        <script>
            $(document).ready(function() {
                $('.customer_sale_item_table_change').on('change', function() {
                    var data = {
                        customer_id: $('#customer_id').val() || null,
                        from_date: $('#from_date').val(),
                        to_date: $('#to_date').val(),
                        product_id: $('#item_product_id').val() || null,
                        employee_id: $('#item_employee_id').val(),
                        branch_id: $('#table_branch_id').val() || null,
                        nationality: $('#nationality').val(),
                    };
                    Livewire.dispatch('customerSaleItemsFilterChanged', data);
                });
            });
        </script>
    @endpush
</div>

<div>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr class="bg-primary">
                            <th class="text-white">Date</th>
                            <th class="text-white">Order</th>
                            <th class="text-white">Customer</th>
                            <th class="text-white">Mobile</th>
                            <th class="text-white">Item</th>
                            <th class="text-white">Color</th>
                            <th class="text-white text-end">Quantity</th>
                            <th class="text-white text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
                            <tr>
                                <td>{{ systemDate($item->order_date) }}</td>
                                <td><a href="{{ route('tailoring::order::show', $item->tailoring_order_id) }}">{{ $item->order_no }}</a></td>
                                <td>{{ $item->account_name ?? $item->customer_name }}</td>
                                <td>{{ $item->account_mobile ?? $item->customer_mobile }}</td>
                                <td>{{ $item->product_name }}</td>
                                <td>{{ $item->product_color }}</td>
                                <td class="text-end">{{ number_format((float) $item->quantity, 3) }}</td>
                                <td class="text-end">{{ currency($item->total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="fw-bold bg-light">
                        <tr>
                            <td colspan="6">Total</td>
                            <td class="text-end">{{ number_format((float) $totalQuantity, 3) }}</td>
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
                $('.customer_item_table_change').on('change keyup', function() {
                    var data = {
                        from_date: $('#from_date').val(),
                        to_date: $('#to_date').val(),
                        customer_id: $('#customer_id').val() || null,
                        branch_id: $('#table_branch_id').val() || null,
                        nationality: $('#nationality').val(),
                    };
                    Livewire.dispatch('customerTailoringItemsFilterChanged', data);
                });
            });
        </script>
    @endpush
</div>

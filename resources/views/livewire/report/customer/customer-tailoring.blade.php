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
                            <th class="text-white text-end">Items</th>
                            <th class="text-white text-end">Discount</th>
                            <th class="text-white text-end">Amount</th>
                            <th class="text-white text-end">Paid</th>
                            <th class="text-white text-end">Balance</th>
                            <th class="text-white text-end">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            <tr>
                                <td>{{ systemDate($order->order_date) }}</td>
                                <td><a href="{{ route('tailoring::order::show', $order->id) }}">{{ $order->order_no }}</a></td>
                                <td>{{ $order->account?->name ?? $order->customer_name }}</td>
                                <td>{{ $order->account?->mobile ?? $order->customer_mobile }}</td>
                                <td class="text-end">{{ number_format($order->items_count) }}</td>
                                <td class="text-end">{{ currency($order->other_discount) }}</td>
                                <td class="text-end">{{ currency($order->grand_total) }}</td>
                                <td class="text-end">{{ currency($order->paid) }}</td>
                                <td class="text-end">{{ currency($order->balance) }}</td>
                                <td class="text-end">
                                    <span class="badge bg-{{ $order->balance == 0 ? 'success' : 'warning' }}">
                                        {{ ucfirst($order->balance == 0 ? 'paid' : 'unpaid') }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="fw-bold bg-light">
                        <tr>
                            <td colspan="4">Total</td>
                            <td class="text-end">{{ number_format($totalItems) }}</td>
                            <td class="text-end">{{ currency($totalDiscount) }}</td>
                            <td class="text-end">{{ currency($totalAmount) }}</td>
                            <td class="text-end">{{ currency($totalPaid) }}</td>
                            <td class="text-end">{{ currency($totalBalance) }}</td>
                            <td class="text-end">{{ number_format($totalOrders) }} Orders</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="mt-3">
                {{ $orders->links() }}
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
                    Livewire.dispatch('customerTailoringFilterChanged', data);
                });
            });
        </script>
    @endpush
</div>

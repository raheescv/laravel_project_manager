<div>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr class="bg-primary">
                            <th class="text-white">Date</th>
                            <th class="text-white">Invoice</th>
                            <th class="text-white">Customer</th>
                            <th class="text-white">Mobile</th>
                            <th class="text-white text-end">Items</th>
                            <th class="text-white text-end">Discount</th>
                            <th class="text-white text-end">Amount</th>
                            <th class="text-white text-end">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sales as $sale)
                            <tr>
                                <td>{{ systemDate($sale->date) }}</td>
                                <td> <a href="{{ route('sale::view', $sale->id) }}">{{ $sale->invoice_no }} </a> </td>
                                <td>{{ $sale->customer }}</td>
                                <td>{{ $sale->mobile }}</td>
                                <td class="text-end">{{ $sale->items_count }}</td>
                                <td class="text-end">{{ currency($sale->other_discount) }}</td>
                                <td class="text-end">{{ currency($sale->grand_total) }}</td>
                                <td class="text-end">
                                    <span class="badge bg-{{ $sale->balance == 0 ? 'success' : 'warning' }}">
                                        {{ ucfirst($sale->balance == 0 ? 'paid' : 'unpaid') }}
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
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="mt-3">
                {{ $sales->links() }}
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
                    Livewire.dispatch('customerSalesFilterChanged', data);
                });
            });
        </script>
    @endpush
</div>

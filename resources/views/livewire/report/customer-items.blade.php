<div>
    <div class="card">
        <div class="card-header">
            <div class="card-tools">
                <div class="row g-2">
                    <div class="col-md-3" wire:ignore>
                        <b><label for="product_id">Product</label></b>
                        {{ html()->select('product_id', [])->value('')->class('select-product_id-list customer_item_table_change')->id('product_id')->placeholder('All') }}
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr class="bg-primary">
                            <th width="10%" class="text-white">Customer</th>
                            <th width="40%" class="text-white">Product</th>
                            <th width="15%" class="text-white text-end">Quantity</th>
                            <th width="20%" class="text-white text-end">Total Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
                            <tr>
                                <td>{{ $item->customer }}</td>
                                <td>{{ $item->product }}</td>
                                <td class="text-end">{{ number_format($item->total_quantity) }}</td>
                                <td class="text-end">{{ currency($item->total_amount) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
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
                $('.customer_item_table_change').on('change', function() {
                    var data = {
                        customer_id: $('#customer_id').val() || null,
                        from_date: $('#from_date').val(),
                        to_date: $('#to_date').val(),
                        product_id: $('#product_id').val() || null,
                    };
                    Livewire.dispatch('customerItemsFilterChanged', data);
                });
            });
        </script>
    @endpush
</div>

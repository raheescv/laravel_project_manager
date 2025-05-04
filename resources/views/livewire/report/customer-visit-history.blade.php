<div>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr class="bg-primary">
                            <th width='40%' class="text-white">Customer</th>
                            <th width='15%' class="text-white">Mobile</th>
                            <th width='10%' class="text-white">First Visit Date</th>
                            <th width='15%' class="text-white text-end">Total</th>
                            <th width='10%' class="text-white text-end">No Of Visits</th>
                            <th width="10%" class="text-white">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($visits as $visit)
                            <tr>
                                <td>{{ $visit->name }}</td>
                                <td>{{ $visit->mobile }}</td>
                                <td>{{ systemDate($visit->first_sale_date) }}</td>
                                <td class="text-end">{{ currency($visit->total) }}</td>
                                <td class="text-end">{{ $visit->visits }}</td>
                                <td>
                                    @if ($visit->is_new_customer)
                                        <span class="badge bg-success">New</span>
                                    @else
                                        <span class="badge bg-secondary">Existing</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $visits->links() }}
            </div>
        </div>
    </div>
    @push('scripts')
        <script>
            $(document).ready(function() {
                $('.table_change').on('change', function() {
                    let data = {
                        customer_id: $('#customer_id').val() || null,
                        from_date: $('#from_date').val(),
                        to_date: $('#to_date').val()
                    };
                    Livewire.dispatch('customerVisitHistoryFilterChanged', data);
                });
            });
        </script>
    @endpush
</div>

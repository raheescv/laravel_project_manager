<div>
    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-md-4">
            <div class="card bg-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-1 text-white">{{ $totalCustomers ?? 0 }}</h4>
                            <p class="text-white mb-0">Total Customers</p>
                        </div>
                        <i class="fa fa-users fa-2x text-white-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-4">
            <div class="card bg-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-1 text-white">{{ $newCustomers ?? 0 }}</h4>
                            <p class="text-white mb-0">New Customers</p>
                        </div>
                        <i class="fa fa-user-plus fa-2x text-white-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-4">
            <div class="card bg-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-1 text-white">{{ $existingCustomers ?? 0 }}</h4>
                            <p class="text-white mb-0">Existing Customers</p>
                        </div>
                        <i class="fa fa-user fa-2x text-white-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
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

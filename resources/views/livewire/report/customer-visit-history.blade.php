<div>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Customer Visit History</h3>
            <div class="card-tools">
                <div class="row g-2 mb-3">
                    <div class="col-md-3">
                        <b><label for="from_date">From Date</label></b>
                        {{ html()->date('from_date')->value('')->class('form-control')->attribute('wire:model.live', 'from_date') }}
                    </div>
                    <div class="col-md-3">
                        <b><label for="to_date">To Date</label></b>
                        {{ html()->date('to_date')->value('')->class('form-control')->attribute('wire:model.live', 'to_date') }}
                    </div>
                    <div class="col-md-3" wire:ignore>
                        <b><label for="customer_id">Customer</label></b>
                        {{ html()->select('customer_id', [])->value('')->class('select-customer_id-list')->id('customer_id')->placeholder('All') }}
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr class="bg-primary">
                            <th width='40%' class="text-white">Customer Name</th>
                            <th width='15%' class="text-white">Customer Mobile</th>
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
                $('#customer_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('customer_id', value);
                });
            });
        </script>
    @endpush
</div>

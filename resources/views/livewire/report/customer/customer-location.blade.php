<div>
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr class="bg-primary">
                                    <th class="text-white">Nationality</th>
                                    <th class="text-white text-end">Customer Count</th>
                                    <th class="text-white text-end">Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $totalCustomers = $data->sum('customer_count');
                                @endphp
                                @foreach ($data as $item)
                                    <tr>
                                        <td>{{ $item->nationality }}</td>
                                        <td class="text-end">{{ $item->customer_count }}</td>
                                        <td class="text-end">{{ number_format(($item->customer_count / $totalCustomers) * 100, 2) }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $data->links() }}
                    </div>
                </div>
                <div class="col-md-6" wire:ignore>
                    <div id="nationalityPieChart" style="height: 370px; width: 100%;"></div>
                </div>
            </div>
        </div>
    </div>
    @push('scripts')
        <script>
            $(document).ready(function() {
                Livewire.dispatch('CustomerLocationRefreshComponent', {
                    from_date: $('#from_date').val(),
                    to_date: $('#to_date').val(),
                    customer_id: $('#customer_id').val() || null,
                });
                window.addEventListener('updateCustomerLocationPieChart', event => {
                    var options = {
                        title: {
                            text: "Nationality Distribution"
                        },
                        data: [{
                            type: "doughnut",
                            innerRadius: "50%",
                            showInLegend: true,
                            legendText: "{label}",
                            indexLabel: "{label}: {y}",
                            indexLabelFontSize: 12,
                            indexLabelFontFamily: "Helvetica Neue",
                            dataPoints: event.detail[0]
                        }],
                    };
                    $("#nationalityPieChart").CanvasJSChart(options);
                });
                // Filter change handler
                $('.customer_sale_item_table_change').on('change', function() {
                    var data = {
                        customer_id: $('#customer_id').val() || null,
                        from_date: $('#from_date').val(),
                        to_date: $('#to_date').val(),
                        nationality: $('#nationality').val(),
                    };
                    Livewire.dispatch('CustomerLocationRefreshComponent', data);
                });
            });
        </script>
    @endpush
</div>

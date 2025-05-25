<div>
    <div class="card-header">
        <div class="row">
            <div class="col-md-6 d-flex gap-1 align-items-center mb-3">
                <div class="btn-group">
                    @can('account.delete')
                        <button class="btn btn-icon btn-outline-light" title="To delete the selected items" wire:click="delete()" wire:confirm="Are you sure you want to delete the selected items?">
                            <i class="demo-pli-recycling fs-5"></i>
                        </button>
                    @endcan
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
                <div class="form-group">
                    <input type="text" wire:model.live="filter.search" autofocus placeholder="Search..." class="form-control" autocomplete="off">
                </div>
            </div>
        </div>
        <hr>
        <div class="col-lg-12">
            <div class="row">
                <div class="col-md-2">
                    <label for="from_date">From Date</label>
                    {{ html()->date('from_date')->value('')->class('form-control')->id('from_date')->attribute('wire:model.live', 'filter.from_date') }}
                </div>
                <div class="col-md-2">
                    <label for="to_date">To Date</label>
                    {{ html()->date('to_date')->value('')->class('form-control')->id('to_date')->attribute('wire:model.live', 'filter.to_date') }}
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="tab-base">
                <ul class="nav nav-underline nav-component border-bottom" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link px-3 active" data-bs-toggle="tab" data-bs-target="#tab-List" type="button" role="tab" aria-controls="home" aria-selected="true">
                            List
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link px-3" data-bs-toggle="tab" data-bs-target="#tab-groupedChart" type="button" role="tab" aria-controls="home" aria-selected="true">
                            Grouped List
                        </button>
                    </li>
                </ul>
                <div class="tab-content">
                    <div id="tab-List" class="tab-pane fade active show" role="tabpanel">
                        <div style="height: 300px" wire:ignore>
                            <canvas id="lineChart"></canvas>
                        </div>
                        <hr>
                        <div class="table-responsive">
                            <table class="table table-striped table-sm">
                                <thead>
                                    <tr class="text-capitalize">
                                        <th>
                                            <input type="checkbox" wire:model.live="selectAll" />
                                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="id" label="id" />
                                        </th>
                                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="date" label="date" /> </th>
                                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="account_id" label="account name" /> </th>
                                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="person_name" label="payee" /> </th>
                                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="reference_number" label="reference No" /> </th>
                                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="description" label="description" /> </th>
                                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="debit" label="debit" /> </th>
                                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="credit" label="credit" /> </th>
                                    </tr>
                                    <tr>
                                        <th class="text-end" colspan="6">Total</th>
                                        <th class="text-end">{{ currency($total['debit']) }}</th>
                                        <th class="text-end">{{ currency($total['credit']) }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($data as $item)
                                        <tr>
                                            <td>
                                                <input type="checkbox" value="{{ $item->journal_id }}" wire:model.live="selected" />
                                                {{ $item->journal_id }}
                                            </td>
                                            <td>{{ systemDate($item->journal->date) }}</td>
                                            <td>{{ $item->account->name }}</td>
                                            <td>{{ $item->person_name }}</td>
                                            <td>{{ $item->reference_number }}</td>
                                            <td>
                                                @switch($item->model)
                                                    @case('Sale')
                                                        <a href="{{ route('sale::view', $item->model_id) }}">{{ $item->description }}</a>
                                                    @break

                                                    @case('SaleReturn')
                                                        <a href="{{ route('sale_return::view', $item->model_id) }}">{{ $item->description }}</a>
                                                    @break

                                                    @default
                                                        {{ $item->description }}
                                                @endswitch
                                            </td>
                                            <td class="text-end">{{ currency($item->debit) }}</td>
                                            <td class="text-end">{{ currency($item->credit) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        {{ $data->links() }}
                    </div>
                    <div id="tab-groupedChart" class="tab-pane fade active show" role="tabpanel">
                        <div class="row">
                            <div class="col-md-12">
                                <h3>Statistics</h3>
                                <div class="table-responsive">
                                    <table class="table table-striped table-sm">
                                        <thead>
                                            <tr class="bg-primary">
                                                <th class="text-white">Account Head</th>
                                                <th class="text-white text-end">Debit</th>
                                                <th class="text-white text-end">Credit</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($groupedChartData as $item)
                                                <tr>
                                                    <td> <a href="{{ route('account::view', $item->account_id) }}">{{ $item->account->name }}</a> </td>
                                                    <td class="text-end">{{ currency($item->debit) }}</td>
                                                    <td class="text-end">{{ currency($item->credit) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @push('scripts')
        <script src="{{ asset('assets/vendors/chart.js/chart.umd.min.js') }}"></script>
        <script src="{{ asset('assets/vendors/chart.js/chartjs-plugin-datalabels@2.min.js') }}"></script>
        <script>
            // Register the plugin to all charts
            Chart.register(ChartDataLabels);
            let lineChart = null;

            function createChart(chartData, labels) {
                const ctx = document.getElementById('lineChart').getContext('2d');

                // Ensure old chart is destroyed
                if (lineChart) {
                    lineChart.destroy();
                }

                lineChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                                label: 'Credit',
                                data: chartData.map(item => item.credit),
                                borderColor: 'rgb(75, 192, 192)',
                                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                                tension: 0.3,
                                fill: false
                            },
                            {
                                label: 'Debit',
                                data: chartData.map(item => item.debit),
                                borderColor: 'rgb(255, 99, 132)',
                                backgroundColor: 'rgba(255, 99, 132, 0.1)',
                                tension: 0.3,
                                fill: false
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            intersect: false,
                            mode: 'index'
                        },
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top'
                            },
                            title: {
                                display: true,
                                text: '{{ $account->name }}`s Monthly Overview',
                            },
                            datalabels: {
                                display: true,
                                color: 'black',
                                align: 'top',
                                formatter: function(value) {
                                    return value.toLocaleString();
                                }
                            }
                        },
                        scales: {
                            y: {
                                display: true,
                                beginAtZero: true,
                                grid: {
                                    display: true,
                                    color: 'rgba(0, 0, 0, 0.05)'
                                },
                                ticks: {
                                    display: true,
                                    callback: function(value) {
                                        return value.toLocaleString();
                                    }
                                }
                            },
                            x: {
                                display: true,
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    display: true
                                }
                            }
                        }
                    },
                    plugins: [ChartDataLabels]
                });

                return lineChart;
            }

            document.addEventListener('livewire:initialized', () => {
                let chartData = @json($lineChartData);
                let labels = chartData.map(item => item.month);
                createChart(chartData, labels);

                // Listen for chart view toggle
                @this.on('propertyUpdated', () => {
                    let chartData = @json($lineChartData);
                    let labels = chartData.map(item => item.month);
                    createChart(chartData, labels);
                });
            });
        </script>
    @endpush
</div>

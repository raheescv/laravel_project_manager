<div>
    @push('styles')
        <style>
            .modern-card {
                background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
                border: none;
                border-radius: 15px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
                transition: all 0.3s ease;
            }

            .modern-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            }

            .table-modern th {
                background: linear-gradient(145deg, #f8f9fa 0%, #e9ecef 100%);
                color: #495057;
            }

            .card-header {
                background: linear-gradient(145deg, #f8f9fa 0%, #e9ecef 100%);
                border-bottom: none;
                padding: 1.5rem;
            }
        </style>
    @endpush
    @if ($product->type == 'product')
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card modern-card mb-3">
                    <div class="card-body p-4">
                        <h5 class="card-title mb-4">Basic Information</h5>
                        <div class="table-responsive">
                            <table class="table table-borderless align-middle text-capitalize table-modern">
                                <tr>
                                    <th>Department</th>
                                    <td>{{ $product->department?->name }}</td>
                                </tr>
                                <tr>
                                    <th>Main Category</th>
                                    <td>{{ $product->mainCategory?->name }}</td>
                                </tr>
                                <tr>
                                    <th>Sub Category</th>
                                    <td>{{ $product->subCategory?->name }}</td>
                                </tr>
                                <tr>
                                    <th>Unit</th>
                                    <td>{{ $product->unit?->name }}</td>
                                </tr>
                                <tr>
                                    <th>Location</th>
                                    <td>{{ $product->location }}</td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>{{ $product->status }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card modern-card mb-3">
                    <div class="card-body p-4">
                        <h5 class="card-title mb-4">Pricing Details</h5>
                        <div class="table-responsive">
                            <table class="table table-borderless align-middle text-capitalize table-modern">
                                <tr>
                                    <th>MRP</th>
                                    <td> {{ currency($product->mrp) }} <br> </td>
                                </tr>
                                <tr>
                                    <th>Cost</th>
                                    <td> {{ currency($product->cost) }} <br> </td>
                                </tr>
                                <tr>
                                    <th>HSN Code</th>
                                    <td>{{ $product->hsn_code }}</td>
                                </tr>
                                <tr>
                                    <th>Barcode</th>
                                    <td>{{ $product->barcode }}</td>
                                </tr>
                                <tr>
                                    <th>Tax</th>
                                    <td>{{ $product->tax }}</td>
                                </tr>
                                <tr>
                                    <th>Description</th>
                                    <td>{{ $product->description }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card modern-card mb-3">
                    <div class="card-body p-4">
                        <h5 class="card-title mb-4">Product Specifications</h5>
                        <div class="table-responsive">
                            <table class="table table-borderless align-middle text-capitalize table-modern">
                                <tr>
                                    <th>Pattern</th>
                                    <td> {{ $product->pattern }} <br> </td>
                                </tr>
                                <tr>
                                    <th>Color</th>
                                    <td> {{ $product->color }} <br> </td>
                                </tr>
                                <tr>
                                    <th>Size</th>
                                    <td>{{ $product->size }}</td>
                                </tr>
                                <tr>
                                    <th>Model</th>
                                    <td>{{ $product->model }}</td>
                                </tr>
                                <tr>
                                    <th>Brand</th>
                                    <td>{{ $product->brand?->name }}</td>
                                </tr>
                                <tr>
                                    <th>Part No</th>
                                    <td>{{ $product->part_no }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
    @if ($product->type == 'service')
        <div class="row">
            <div class="col-md-6">
                <div class="card modern-card mb-3">
                    <div class="card-body p-4">
                        <h5 class="card-title mb-4">Service Details</h5>
                        <div class="table-responsive">
                            <table class="table table-borderless align-middle text-capitalize table-modern">
                                <tr>
                                    <th>Service</th>
                                    <td>
                                        {{ $product->name }} <br>
                                        @if ($product->name_arabic)
                                            <br>
                                            <span style="text-align: right; display: block;" dir="rtl">
                                                {{ $product->name_arabic }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Department</th>
                                    <td>{{ $product->department?->name }}</td>
                                </tr>
                                <tr>
                                    <th>Main Category</th>
                                    <td>{{ $product->mainCategory?->name }}</td>
                                </tr>
                                <tr>
                                    <th>Sub Category</th>
                                    <td>{{ $product->subCategory?->name }}</td>
                                </tr>
                                <tr>
                                    <th>Unit</th>
                                    <td>{{ $product->unit?->name }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card modern-card mb-3">
                    <div class="card-body p-4">
                        <h5 class="card-title mb-4">Pricing Details</h5>
                        <div class="table-responsive">
                            <table class="table table-borderless align-middle text-capitalize table-modern">
                                <tr>
                                    <th>Price</th>
                                    <td> {{ currency($product->mrp) }} <br> </td>
                                </tr>
                                <tr>
                                    <th>Time</th>
                                    <td> {{ $product->time }} <br> </td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>{{ $product->status }}</td>
                                </tr>
                                <tr>
                                    <th>Favorite</th>
                                    <td>{{ $product->is_favorite ? 'Yes' : 'No' }}</td>
                                </tr>
                                <tr>
                                    <th>Description</th>
                                    <td>{{ $product->description }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
    <div class="row">
        <div class="col-md-12">
            <div class="card modern-card mb-3">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Inventory</h5>
                        <div class="d-flex gap-3">
                            <input type="text" wire:model.live="search" placeholder="Search..." class="form-control" autocomplete="off">
                        </div>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-capitalize table-modern">
                            <thead>
                                <tr class="text-capitalize">
                                    <th> # </th>
                                    <th> Branch </th>
                                    <th> Barcode </th>
                                    <th> batch </th>
                                    <th class="text-end"> cost </th>
                                    <th class="text-end">
                                        @if ($product->type == 'product')
                                            quantity
                                        @else
                                            Used Count
                                        @endif
                                    </th>
                                    @if ($product->type == 'product')
                                        <th class="text-end">Total</th>
                                        <th class="text-end">Action</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($data as $item)
                                    <tr>
                                        <td>{{ $item->id }}</td>
                                        <td>{{ $item->branch?->name }}</td>
                                        <td>
                                            {{ $item->barcode }}
                                            <a href="{{ route('inventory::barcode::print', $item->id) }}"><i class="fa fa-2x fa-print pull-right"></i></a>
                                        </td>
                                        <td>{{ $item->batch }}</td>
                                        <td class="text-end">{{ currency($item->cost) }}</td>
                                        <td class="text-end">
                                            @if ($product->type == 'product')
                                                {{ $item->quantity }}
                                            @else
                                                {{ abs($item->quantity) }}
                                            @endif
                                        </td>
                                        @if ($product->type == 'product')
                                            <td class="text-end">{{ currency($item->total) }}</td>
                                            <td class="text-end">
                                                @can('inventory.edit')
                                                    <i table_id="{{ $item->id }}" class="demo-psi-pencil fs-5 me-2 pointer edit"></i>
                                                @endcan
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="5" class="text-end">Total</th>
                                    <th class="text-end">
                                        <b>
                                            @if ($product->type == 'product')
                                                {{ $data->sum('quantity') }}
                                            @else
                                                {{ abs($data->sum('quantity')) }}
                                            @endif
                                        </b>
                                    </th>
                                    @if ($product->type == 'product')
                                        <th class='text-end'>{{ currency($data->sum('total')) }}</th>
                                        <th></th>
                                    @endif
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card modern-card">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title mb-0">Inventory Movement</h5>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn {{ $chartView === 'monthly' ? 'btn-primary' : 'btn-outline-primary' }}" wire:click="toggleChartView"
                                    {{ $chartView === 'monthly' ? 'disabled' : '' }}>Monthly</button>
                                <button type="button" class="btn {{ $chartView === 'daily' ? 'btn-primary' : 'btn-outline-primary' }}" wire:click="toggleChartView"
                                    {{ $chartView === 'daily' ? 'disabled' : '' }}>Daily</button>
                            </div>
                        </div>
                        <div class="chart-container" style="position: relative; height: 300px;">
                            <canvas id="inventoryChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card modern-card mb-3">
                    <div class="card-header">
                        <h3>Log</h3>
                        <div class="row">
                            <div class="col-lg-6">
                                <div wire:ignore>
                                    {{ html()->select('branch_id', [auth()->user()->default_branch_id => auth()->user()->branch?->name])->value(auth()->user()->default_branch_id)->class('select-assigned-branch_id-list')->id('branch_id')->placeholder('All') }}
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="row ">
                                    <input type="text" wire:model.live="log_search" autofocus placeholder="Search..." class="form-control" autocomplete="off">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle text-capitalize table-modern">
                                <thead>
                                    <tr class="text-capitalize">
                                        <th width="5%"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="id" label="#" /> </th>
                                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="created_at" label="date" /> </th>
                                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="branch_id" label="Branch" /> </th>
                                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="barcode" label="barcode" /> </th>
                                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="batch" label="batch" /> </th>
                                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="cost" label="cost" /> </th>
                                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="quantity_in" label="In" /> </th>
                                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="quantity_out" label="out" /> </th>
                                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="balance" label="balance" /> </th>
                                        <th width="40%"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="remarks" label="remarks" /> </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($logs as $item)
                                        <tr>
                                            <td>{{ $item->id }}</td>
                                            <td>{{ systemDateTime($item->created_at) }}</td>
                                            <td>{{ $item->branch?->name }}</td>
                                            <td>{{ $item->barcode }}</td>
                                            <td>{{ $item->batch }}</td>
                                            <td class="text-end">{{ currency($item->cost) }}</td>
                                            <td class="text-end">{{ $item->quantity_in }}</td>
                                            <td class="text-end">{{ $item->quantity_out }}</td>
                                            <td class="text-end">
                                                @if ($product->type == 'product')
                                                    {{ $item->balance }}
                                                @else
                                                    {{ $item->balance * -1 }}
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    switch ($item->model) {
                                                        case 'Sale':
                                                            $href = route('sale::view', $item->model_id);
                                                            break;
                                                        case 'SaleReturn':
                                                            $href = route('sale_return::view', $item->model_id);
                                                            break;
                                                        case 'InventoryTransfer':
                                                            $href = route('inventory::transfer::view', $item->model_id);
                                                            break;
                                                        case 'Purchase':
                                                            $href = route('purchase::edit', $item->model_id);
                                                            break;
                                                        default:
                                                            $href = '';
                                                            break;
                                                    }
                                                @endphp
                                                @if ($href)
                                                    <a href="{{ $href }}">{{ $item->remarks }}</a>
                                                @else
                                                    {{ $item->remarks }}
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        {{ $logs->links() }}
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
            let inventoryChart = null;

            function createChart(chartData, labels, currentView) {
                const ctx = document.getElementById('inventoryChart').getContext('2d');

                // Ensure old chart is destroyed
                if (inventoryChart) {
                    inventoryChart.destroy();
                }

                inventoryChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                                label: 'Quantity In',
                                data: chartData.map(item => item.total_in),
                                borderColor: 'rgb(75, 192, 192)',
                                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                                tension: 0.3,
                                fill: false
                            },
                            {
                                label: 'Quantity Out',
                                data: chartData.map(item => item.total_out),
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
                                text: currentView === 'monthly' ? 'Monthly Inventory Movement (Last 1 Year)' : 'Daily Inventory Movement (Last 30 Days)'
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

                return inventoryChart;
            }

            document.addEventListener('livewire:initialized', () => {
                const monthlyData = @json($monthly_summary);
                const dailyData = @json($daily_summary);
                const currentView = @json($chartView);

                const chartData = currentView === 'monthly' ? monthlyData : dailyData;
                const labels = currentView === 'monthly' ?
                    chartData.map(item => item.month_name) :
                    chartData.map(item => item.day_name);

                createChart(chartData, labels, currentView);

                // Listen for chart view toggle using updated Livewire 3 syntax
                Livewire.on('propertyUpdated', (data) => {
                    const currentView = data[0];
                    const chartData = currentView === 'monthly' ? monthlyData : dailyData;
                    const labels = currentView === 'monthly' ?
                        chartData.map(item => item.month_name) :
                        chartData.map(item => item.day_name);

                    createChart(chartData, labels, currentView);
                });
            });

            // Other event handlers
            $('#branch_id').on('change', function(e) {
                const value = $(this).val() || null;
                @this.set('branch_id', value);
            });

            $(document).on('click', '.edit', function() {
                Livewire.dispatch("Inventory-Page-Update-Component", {
                    id: $(this).attr('table_id')
                });
            });

            window.addEventListener('RefreshInventoryTable', event => {
                Livewire.dispatch("Inventory-Refresh-Component");
            });
        </script>
    @endpush
</div>

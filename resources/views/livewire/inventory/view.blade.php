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
                                    <td>{{ $product->brand?->name ?? 'N/A' }}</td>
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
                        <div class="d-flex gap-3 align-items-center">
                            <div class="input-group" style="width: 200px;">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="demo-pli-filter-2"></i>
                                </span>
                                <select wire:model.live="inventory_filter" class="form-select border-start-0">
                                    <option value="all">All Inventory</option>
                                    <option value="main">Main Only</option>
                                    <option value="employee">Employee Only</option>
                                </select>
                            </div>
                            <input type="text" wire:model.live="search" placeholder="Search..." class="form-control" autocomplete="off" style="width: 200px;">
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
                                    <th> Employee </th>
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
                                            {{ $item->employee?->name }}
                                            @if ($item->employee_id)
                                                <a href="{{ route('users::employee::view', $item->employee_id) }}"><i class="fa fa-2x fa-user pull-right"></i></a>
                                            @endif
                                        </td>
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
                                                <div class="btn-group" role="group">
                                                    @can('inventory.edit')
                                                        <button class="btn btn-sm btn-outline-info" wire:click="$dispatch('Inventory-Page-Update-Component', {id: {{ $item->id }}})"
                                                            title="Edit Inventory">
                                                            <i class="demo-psi-pencil fs-5 me-2"></i>
                                                        </button>
                                                    @endcan
                                                    @can('inventory.transfer')
                                                        @if ($item->employee_id)
                                                            <button class="btn btn-sm btn-outline-warning"
                                                                wire:click="$dispatch('EmployeeInventory-Transfer-Component', {inventoryId: {{ $item->id }}, type: 'return'})" title="Return to Branch"
                                                                @if ($item->quantity <= 0) disabled @endif>
                                                                <i class="fa fa-share"></i>
                                                            </button>
                                                        @else
                                                            <button class="btn btn-sm btn-outline-success"
                                                                wire:click="$dispatch('EmployeeInventory-Transfer-Component', {inventoryId: {{ $item->id }}, type: 'transfer'})"
                                                                title="Transfer to Employee" @if ($item->quantity <= 0) disabled @endif>
                                                                <i class="fa fa-user"></i>
                                                            </button>
                                                        @endif
                                                    @endcan
                                                </div>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="6" class="text-end">Total</th>
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
                            <div class="btn-group" role="group" x-data="{
                                view: @js($chartView),
                                toggleView(newView) {
                                    this.view = newView;
                                    window.dispatchEvent(new CustomEvent('chart-view-changed', { detail: newView }));
                                }
                            }">
                                <button type="button" class="btn" :class="view === 'monthly' ? 'btn-primary' : 'btn-outline-primary'" @click="toggleView('monthly')"
                                    :disabled="view === 'monthly'">
                                    Monthly
                                </button>
                                <button type="button" class="btn" :class="view === 'daily' ? 'btn-primary' : 'btn-outline-primary'" @click="toggleView('daily')"
                                    :disabled="view === 'daily'">
                                    Daily
                                </button>
                            </div>
                        </div>
                        <div class="chart-container" wire:ignore style="position: relative; height: 300px;">
                            <canvas id="inventoryChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card modern-card mb-3">
                    <div class="card-header pb-0">
                        <ul class="nav nav-tabs nav-tabs-bordered" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link @if ($selectedTab == 'log') active @endif d-flex align-items-center gap-2"
                                    wire:click="tabSelect('log')" type="button" role="tab">
                                    <i class="fa fa-history text-primary"></i>
                                    Log
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link @if ($selectedTab == 'image') active @endif d-flex align-items-center gap-2"
                                    wire:click="tabSelect('image')" type="button" role="tab">
                                    <i class="fa fa-image text-danger"></i>
                                    Product Image
                                </button>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body p-4">
                        <div class="tab-content">
                            <div class="tab-pane fade @if ($selectedTab == 'log') active show @endif" role="tabpanel">
                                <div class="row mb-4">
                                    <div class="col-lg-4">
                                        <div wire:ignore>
                                            {{ html()->select('branch_id', [session('branch_id') => session('branch_name')])->value(session('branch_id'))->class('select-assigned-branch_id-list')->id('branch_id')->placeholder('All') }}
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div wire:ignore>
                                            {{ html()->select('employee_id', [])->value('')->class('select-employee_id-list')->id('employee_id')->placeholder('All Employees') }}
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="row">
                                            <input type="text" wire:model.live="log_search" autofocus placeholder="Search logs..." class="form-control"
                                                autocomplete="off">
                                        </div>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle text-capitalize table-modern">
                                        <thead>
                                            <tr class="text-capitalize">
                                                <th width="5%">
                                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="id" label="#" />
                                                </th>
                                                <th>
                                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="created_at" label="date" />
                                                </th>
                                                <th>
                                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="branch_id" label="Branch" />
                                                </th>
                                                <th>
                                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="employee_id" label="Employee" />
                                                </th>
                                                <th>
                                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="barcode" label="barcode" />
                                                </th>
                                                <th>
                                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="batch" label="batch" />
                                                </th>
                                                <th class="text-end">
                                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="cost" label="cost" />
                                                </th>
                                                <th class="text-end">
                                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="quantity_in" label="In" />
                                                </th>
                                                <th class="text-end">
                                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="quantity_out" label="out" />
                                                </th>
                                                <th class="text-end">
                                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="balance" label="balance" />
                                                </th>
                                                <th width="40%">
                                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="remarks" label="remarks" />
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($logs as $item)
                                                <tr>
                                                    <td>{{ $item->id }}</td>
                                                    <td>{{ systemDateTime($item->created_at) }}</td>
                                                    <td>{{ $item->branch?->name }}</td>
                                                    <td>{{ $item->employee?->name }}</td>
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
                            <div class="tab-pane fade @if ($selectedTab == 'image') active show @endif" role="tabpanel">
                                <div class="row g-4">
                                    <div class="col-md-12">
                                        <div class="d-flex flex-wrap gap-3">
                                            @if ($product->thumbnail)
                                                <div class="card border shadow-sm" style="width: 200px;">
                                                    <img src="{{ url($product->thumbnail) }}" class="card-img-top" alt="Thumbnail"
                                                        style="height: 200px; object-fit: cover;">
                                                    <div class="card-footer bg-light text-center small py-2">
                                                        Main Thumbnail
                                                    </div>
                                                </div>
                                            @endif

                                            @foreach ($product->images as $image)
                                                <div class="card border shadow-sm" style="width: 200px;">
                                                    <img src="{{ url($image->path) }}" class="card-img-top" alt="{{ $image->name }}"
                                                        style="height: 200px; object-fit: cover;">
                                                    <div class="card-footer bg-light text-center small py-2">
                                                        {{ $image->name ?: 'Product Image' }}
                                                    </div>
                                                </div>
                                            @endforeach

                                            @if (!$product->thumbnail && count($product->images) == 0)
                                                <div class="col-12 text-center py-5">
                                                    <i class="fa fa-image fa-4x text-muted mb-3"></i>
                                                    <p class="text-muted">No images available for this product.</p>
                                                </div>
                                            @endif
                                        </div>

                                        @if ($product->angleImages()->count() > 0)
                                            <hr class="my-5">
                                            <h5 class="mb-4">360° View Images</h5>
                                            <div class="row row-cols-2 row-cols-md-4 row-cols-lg-6 g-3">
                                                @foreach ($product->angleImages()->orderedByAngle()->get() as $image)
                                                    <div class="col">
                                                        <div class="card h-100 border text-center shadow-sm">
                                                            <img src="{{ url($image->path) }}" class="card-img-top" alt="360 Image"
                                                                style="height: 120px; object-fit: cover;">
                                                            <div class="card-footer bg-light small py-1">
                                                                {{ $image->degree }}°
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
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
                let currentView = @json($chartView);
                let inventoryChart = null;

                // Initialize chart
                const chartData = currentView === 'monthly' ? monthlyData : dailyData;
                const labels = currentView === 'monthly' ?
                    chartData.map(item => item.month_name) :
                    chartData.map(item => item.day_name);

                inventoryChart = createChart(chartData, labels, currentView);

                // Listen for client-side chart view toggle (no Livewire re-render)
                window.addEventListener('chart-view-changed', (event) => {
                    currentView = event.detail;

                    // Update chart without re-rendering component
                    const newChartData = currentView === 'monthly' ? monthlyData : dailyData;
                    const newLabels = currentView === 'monthly' ?
                        newChartData.map(item => item.month_name) :
                        newChartData.map(item => item.day_name);

                    // Destroy old chart and create new one
                    if (inventoryChart) {
                        inventoryChart.destroy();
                    }
                    inventoryChart = createChart(newChartData, newLabels, currentView);
                });

                // Also listen for Livewire event (fallback, but should use skipRender)
                Livewire.on('chartViewUpdated', (view) => {
                    currentView = view;
                    const newChartData = currentView === 'monthly' ? monthlyData : dailyData;
                    const newLabels = currentView === 'monthly' ?
                        newChartData.map(item => item.month_name) :
                        newChartData.map(item => item.day_name);

                    if (inventoryChart) {
                        inventoryChart.destroy();
                    }
                    inventoryChart = createChart(newChartData, newLabels, currentView);
                });
            });

            // Other event handlers
            $('#branch_id').on('change', function(e) {
                const value = $(this).val() || null;
                @this.set('branch_id', value);
            });
            // Other event handlers
            $('#employee_id').on('change', function(e) {
                const value = $(this).val() || null;
                @this.set('employee_id', value);
            });

            window.addEventListener('RefreshInventoryTable', event => {
                Livewire.dispatch("Inventory-Refresh-Component");
            });
        </script>
    @endpush
</div>

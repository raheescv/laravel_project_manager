<div class="card shadow-sm">
    <div class="card-body">
        <!-- Filters -->
        <div class="row mb-4">
            <div class="col-12 mb-3">
                <h6 class="text-muted text-uppercase"><i class="fa fa-filter me-2"></i>Filters</h6>
                <hr class="mt-2">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Report Type</label>
                <select wire:model.live="report_type" class="form-select shadow-sm">
                    <option value="non_moving">📦 Non-Moving Items</option>
                    <option value="top_moving">🔥 Top Moving Products</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Branch</label>
                <select wire:model.live="branch_id" class="form-select shadow-sm">
                    <option value=""><i class="fa fa-building"></i> All Branches</option>
                    @foreach ($branches as $id => $name)
                        <option value="{{ $id }}">🏢 {{ $name }}</option>
                    @endforeach
                </select>
            </div>
            @if ($report_type === 'top_moving')
                <div class="col-md-2">
                    <label class="form-label fw-bold">From Date</label>
                    <div class="input-group shadow-sm">
                        <span class="input-group-text"><i class="fa fa-calendar"></i></span>
                        <input type="date" wire:model.live="from_date" class="form-control">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">To Date</label>
                    <div class="input-group shadow-sm">
                        <span class="input-group-text"><i class="fa fa-calendar"></i></span>
                        <input type="date" wire:model.live="to_date" class="form-control">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Top Items</label>
                    <div class="input-group shadow-sm">
                        <span class="input-group-text"><i class="fa fa-list-ol"></i></span>
                        <input type="number" wire:model.live="limit" class="form-control" min="5" max="20">
                    </div>
                </div>
            @else
                <div class="col-md-3">
                    <label class="form-label fw-bold">Non-Moving Days Threshold</label>
                    <div class="input-group shadow-sm">
                        <span class="input-group-text"><i class="fa fa-clock"></i></span>
                        <input type="number" wire:model.live="days_threshold" class="form-control" min="1">
                        <span class="input-group-text bg-light">Days</span>
                    </div>
                </div>
            @endif
        </div>

        <!-- Chart Section for Top Moving Products -->
        @if ($report_type === 'top_moving' && $chartData)
            <div class="row mb-4">
                <div class="col-12 mb-3">
                    <h6 class="text-muted text-uppercase"><i class="fa fa-chart-pie me-2"></i>Distribution Chart</h6>
                    <hr class="mt-2">
                </div>
                <div class="col-md-8 mx-auto">
                    <div class="card shadow-sm border-0 bg-light">
                        <div class="card-body p-4" wire:ignore>
                            <canvas id="productChart" style="height: 400px;"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Data Table -->
        <div class="row mb-3">
            <div class="col-12">
                <h6 class="text-muted text-uppercase"><i class="fa fa-table me-2"></i>Detailed Data</h6>
                <hr class="mt-2">
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-sm table-striped align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="border-0">Code</th>
                        <th class="border-0">Product Name</th>
                        <th class="border-0">Branch</th>
                        @if ($report_type === 'non_moving')
                            <th class="text-end">Current Stock</th>
                            <th class="text-end">Stock Value</th>
                            <th>Last Movement</th>
                            <th class="text-end">Days Without Movement</th>
                        @else
                            <th class="text-end">Total Out</th>
                            <th class="text-end">Total In</th>
                            <th class="text-end">Net Movement</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($products as $product)
                        <tr>
                            <td>{{ $product->code }}</td>
                            <td> <a href="{{ route('inventory::product::view', $product->id) }}">{{ $product->name }}</a> </td>
                            <td>{{ $product->branch_name }}</td>
                            @if ($report_type === 'non_moving')
                                <td class="text-end fw-bold">{{ number_format($product->quantity) }}</td>
                                <td class="text-end">
                                    <span class="text-success fw-bold">{{ number_format($product->stock_value, 2) }}</span>
                                </td>
                                <td>
                                    @if ($product->last_movement)
                                        <span class="badge bg-info">{{ systemDate($product->last_movement) }}</span>
                                    @else
                                        <span class="badge bg-warning">Never</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if ($product->last_movement)
                                        <span class="badge {{ Carbon\Carbon::parse($product->last_movement)->diffInDays(now()) > $days_threshold ? 'bg-danger' : 'bg-success' }}">
                                            {{ round(Carbon\Carbon::parse($product->last_movement)->diffInDays(now())) }} days
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">N/A</span>
                                    @endif
                                </td>
                            @else
                                <td class="text-end">
                                    <span class="text-primary fw-bold">{{ number_format($product->total_quantity_out) }}</span>
                                </td>
                                <td class="text-end">
                                    <span class="text-info fw-bold">{{ number_format($product->total_quantity_in) }}</span>
                                </td>
                                <td class="text-end">
                                    <span class="badge fs-6 {{ $product->total_quantity_out - $product->total_quantity_in > 0 ? 'bg-success' : 'bg-danger' }}">
                                        {{ number_format($product->total_quantity_out - $product->total_quantity_in, 2) }}
                                    </span>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($report_type === 'non_moving')
            <div class="mt-4 d-flex justify-content-center">
                {{ $products->links() }}
            </div>
        @endif
    </div>
    @push('scripts')
        <script>
            Chart.register(ChartDataLabels);

            let chart = null;
            const chartConfig = {
                type: 'pie',
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 500
                    },
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                padding: 20,
                                generateLabels: function(chart) {
                                    const data = chart.data;
                                    if (!data.datasets[0].data) return [];
                                    const total = data.datasets[0].data.reduce((a, b) => a + b, 0);
                                    return data.labels.map((label, i) => {
                                        const value = data.datasets[0].data[i];
                                        const percentage = ((value / total) * 100).toFixed(1);
                                        return {
                                            text: `${label} (${percentage}%)`,
                                            fillStyle: data.datasets[0].backgroundColor[i],
                                            hidden: isNaN(value),
                                            lineCap: 'round',
                                            lineDash: [],
                                            lineDashOffset: 0,
                                            lineJoin: 'round',
                                            lineWidth: 1,
                                            strokeStyle: '#fff',
                                            pointStyle: 'circle',
                                            rotation: 0
                                        };
                                    });
                                }
                            }
                        },
                        title: {
                            display: true,
                            text: 'Top 10 Moving Products Distribution',
                            font: {
                                size: 16,
                                weight: 'bold'
                            },
                            padding: {
                                top: 10,
                                bottom: 30
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const value = context.raw;
                                    const label = context.label;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return `${label}: ${value.toLocaleString()} (${percentage}%)`;
                                }
                            }
                        },
                        datalabels: {
                            color: '#fff',
                            textShadow: '0 0 3px #000',
                            font: {
                                weight: 'bold',
                                size: 14
                            },
                            formatter: function(value, context) {
                                if (!value) return '';
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return percentage > 5 ? `${percentage}%` : '';
                            }
                        }
                    }
                },
                plugins: [ChartDataLabels]
            };

            function updateChart(newData) {
                if (!newData || !newData.datasets || !newData.datasets[0].data) return;

                const canvas = document.getElementById('productChart');
                if (!canvas) return;

                if (!chart) {
                    chart = new Chart(canvas.getContext('2d'), {
                        ...chartConfig,
                        data: newData
                    });
                } else {
                    // Update existing chart data
                    chart.data = newData;
                    chart.update('none'); // Update without animation for smoother transitions
                }
            }

            function initChartIfNeeded() {
                const chartData = @js($chartData);
                if (chartData) {
                    updateChart(chartData);
                }
            }

            // Initialize chart
            document.addEventListener("DOMContentLoaded", initChartIfNeeded);
            document.addEventListener("livewire:initialized", initChartIfNeeded);

            // Handle Livewire updates
            Livewire.on('updateChart', (data) => {
                if (data && data[0]) {
                    updateChart(data[0]);
                }
            });
        </script>
    @endpush
</div>

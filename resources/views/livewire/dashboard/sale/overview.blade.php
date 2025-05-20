<div class="card h-100 border-0 shadow-lg">
    <div class="card-header bg-white border-0 py-3">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0 text-primary fw-bold">Sales Overview</h4>
                <p class="text-muted small mb-0">Daily sales performance tracking</p>
            </div>
            <div class="btn-group" role="group">
                <button wire:click="changePeriod('day')" class="btn btn-sm {{ $period === 'day' ? 'btn-primary' : 'btn-light' }}">Day</button>
                <button wire:click="changePeriod('week')" class="btn btn-sm {{ $period === 'week' ? 'btn-primary' : 'btn-light' }}">Week</button>
                <button wire:click="changePeriod('month')" class="btn btn-sm {{ $period === 'month' ? 'btn-primary' : 'btn-light' }}">Month</button>
            </div>
            <div class="dropdown">
                <button class="btn btn-light btn-sm rounded-pill px-3 d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                    <i class="demo-pli-gear fs-5"></i>
                    <span class="d-none d-md-inline">Options</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                    <li>
                        <a href="{{ route('sale::index') }}" class="dropdown-item d-flex align-items-center gap-2">
                            <i class="demo-pli-calendar-4 fs-5"></i>
                            View Details
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="p-4">
            <div class="chart-container" style="height: 200px;" wire:ignore>
                <canvas id="sale-overview-chart" class="w-100"></canvas>
            </div>
        </div>

        <div class="px-4 pb-4">
            <div class="row g-4">
                <div class="col-md-7">
                    <div class="stats-grid">
                        <!-- Today's Sales Section -->
                        <div class="p-2 rounded-3 bg-light-subtle border">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0 text-primary">
                                    <i class="demo-pli-shopping-basket me-2"></i>Today's Sales
                                </h5>
                                <span class="badge bg-primary rounded-pill">Today</span>
                            </div>
                            <div class="row align-items-center">
                                <div class="col-7">
                                    <h3 class="display-6 fw-bold mb-0">{{ currency($todaySale) }}</h3>
                                </div>
                                <div class="col-5">
                                    <div class="d-flex flex-column gap-2">
                                        <div class="p-2 rounded bg-white shadow-sm">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-muted small">Lowest</span>
                                                <span class="badge bg-warning-subtle text-warning">{{ currency($lowestSale) }}</span>
                                            </div>
                                        </div>
                                        <div class="p-2 rounded bg-white shadow-sm">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-muted small">Highest</span>
                                                <span class="badge bg-success-subtle text-success">{{ currency($highestSale) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-5">
                    <div class="p-2 rounded-3 bg-light-subtle border h-100">
                        <h5 class="card-title mb-3 text-primary">
                            <i class="demo-pli-wallet me-2"></i>Payment Methods
                        </h5>
                        <div class="payment-methods">
                            @foreach ($paymentData as $index => $item)
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted">{{ $item['method'] }}</span>
                                        <span class="fw-medium">{{ currency($item['amount']) }}</span>
                                    </div>
                                    <div class="progress bg-white" style="height: 8px;">
                                        @php
                                            $colors = ['bg-primary', 'bg-success', 'bg-info'];
                                        @endphp
                                        <div class="progress-bar {{ $colors[$index % 3] }}" role="progressbar" style="width: {{ $item['percentage'] }}%" aria-valuenow="{{ $item['percentage'] }}"
                                            aria-valuemin="0" aria-valuemax="100">
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @push('scripts')
        <script>
            // Register the plugin
            Chart.register(ChartDataLabels);
            let salesChart;

            function initializeChart(data) {
                console.log(data);
                if (salesChart) {
                    salesChart.destroy();
                }

                const ctx = document.getElementById("sale-overview-chart").getContext('2d');
                const gradient = ctx.createLinearGradient(0, 0, 0, 200);
                gradient.addColorStop(0, 'rgba(66, 135, 245, 0.2)');
                gradient.addColorStop(1, 'rgba(66, 135, 245, 0.0)');

                salesChart = new Chart(ctx, {
                    type: "line",
                    data: {
                        datasets: [{
                            label: "Sales",
                            data: data,
                            borderColor: '#4287f5',
                            backgroundColor: gradient,
                            fill: true,
                            tension: 0.4,
                            parsing: {
                                xAxisKey: "date",
                                yAxisKey: "amount"
                            }
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                enabled: true,
                                mode: 'index',
                                intersect: false
                            },
                            datalabels: {
                                align: 'top',
                                anchor: 'end',
                                offset: 5,
                                color: '#666',
                                font: {
                                    size: 10,
                                    weight: 'bold'
                                },
                                formatter: function(value) {
                                    return value.amount.toLocaleString('en-US', {
                                        minimumFractionDigits: 0,
                                        maximumFractionDigits: 0
                                    });
                                },
                                display: true
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    display: true,
                                    drawBorder: false,
                                    color: 'rgba(0,0,0,0.05)'
                                },
                                ticks: {
                                    font: {
                                        size: 11
                                    },
                                    color: '#666'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    font: {
                                        size: 11
                                    },
                                    color: '#666',
                                    maxTicksLimit: 7
                                }
                            }
                        },
                        elements: {
                            point: {
                                radius: 3,
                                hoverRadius: 5
                            }
                        }
                    }
                });
            }

            // Initialize chart when DOM loads
            document.addEventListener("DOMContentLoaded", () => {
                initializeChart(@js($data));
            });

            // Re-initialize chart when Livewire updates the data
            Livewire.on('chartDataUpdated', (data) => {
                console.log('Chart data updated:', data[0]);
                initializeChart(data[0]);
            });

            // Listen for Livewire updates
            Livewire.hook('message.processed', (message, component) => {
                if (message.updateQueue && message.updateQueue.some(update => update.payload.value && update.payload.value.data)) {
                    initializeChart(message.updateQueue.find(update => update.payload.value && update.payload.value.data).payload.value.data);
                }
            });
        </script>
    @endpush
</div>

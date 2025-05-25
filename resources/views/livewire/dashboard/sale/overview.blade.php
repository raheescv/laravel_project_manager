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
                <button wire:click="changePeriod('year')" class="btn btn-sm {{ $period === 'year' ? 'btn-primary' : 'btn-light' }}">Year</button>
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

                // Create gradient based on period
                const gradient = ctx.createLinearGradient(0, 0, 0, 300);
                if (data.length <= 12) { // For yearly view
                    gradient.addColorStop(0, 'rgba(79, 70, 229, 0.65)'); // Indigo
                    gradient.addColorStop(0.5, 'rgba(79, 70, 229, 0.35)'); // Mid fade
                    gradient.addColorStop(1, 'rgba(79, 70, 229, 0.05)'); // Almost transparent
                } else {
                    gradient.addColorStop(0, 'rgba(16, 185, 129, 0.65)'); // Emerald
                    gradient.addColorStop(0.5, 'rgba(16, 185, 129, 0.35)'); // Mid fade
                    gradient.addColorStop(1, 'rgba(16, 185, 129, 0.05)'); // Almost transparent
                }

                salesChart = new Chart(ctx, {
                    type: "bar",
                    data: {
                        datasets: [{
                            label: "Sales",
                            data: data,
                            borderColor: data.length <= 12 ? '#4F46E5' : '#059669',
                            borderWidth: 1.5,
                            backgroundColor: gradient,
                            borderRadius: {
                                topLeft: 8,
                                topRight: 8
                            },
                            barThickness: data.length <= 12 ? 40 : 'flex',
                            minBarLength: 6,
                            categoryPercentage: data.length <= 12 ? 0.9 : 0.8,
                            barPercentage: data.length <= 12 ? 0.9 : 0.9,
                            parsing: {
                                xAxisKey: "date",
                                yAxisKey: "amount"
                            }
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        layout: {
                            padding: {
                                top: 20,
                                right: 20,
                                bottom: 0,
                                left: 0
                            }
                        },
                        interaction: {
                            intersect: false,
                            mode: 'index'
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: 'rgba(255, 255, 255, 0.98)',
                                titleColor: '#0f172a',
                                bodyColor: data.length <= 12 ? '#4F46E5' : '#059669',
                                borderColor: '#e2e8f0',
                                borderWidth: 1,
                                padding: {
                                    top: 10,
                                    right: 15,
                                    bottom: 10,
                                    left: 15
                                },
                                cornerRadius: 8,
                                displayColors: false,
                                titleFont: {
                                    size: 13,
                                    weight: '600',
                                    family: "'Inter', system-ui, sans-serif"
                                },
                                bodyFont: {
                                    size: 13,
                                    weight: '500',
                                    family: "'Inter', system-ui, sans-serif"
                                },
                                callbacks: {
                                    label: function(context) {
                                        return new Intl.NumberFormat('en-US', {
                                            minimumFractionDigits: 0,
                                            maximumFractionDigits: 0
                                        }).format(context.parsed.y);
                                    }
                                }
                            },
                            datalabels: {
                                align: 'top',
                                anchor: 'end',
                                offset: 6,
                                color: data.length <= 12 ? '#4F46E5' : '#059669',
                                font: {
                                    size: 11,
                                    weight: '600',
                                    family: "'Inter', system-ui, sans-serif"
                                },
                                formatter: function(value) {
                                    if (value.amount < 1000) return ''; // Hide small values
                                    return new Intl.NumberFormat('en-US', {
                                        notation: data.length <= 12 ? 'standard' : 'compact',
                                        minimumFractionDigits: 0,
                                        maximumFractionDigits: data.length <= 12 ? 0 : 1
                                    }).format(value.amount);
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                border: {
                                    display: false
                                },
                                grid: {
                                    color: 'rgba(226, 232, 240, 0.4)',
                                    drawBorder: false,
                                    lineWidth: 1
                                },
                                ticks: {
                                    font: {
                                        size: 11,
                                        weight: '500',
                                        family: "'Inter', system-ui, sans-serif"
                                    },
                                    padding: 12,
                                    color: '#64748b',
                                    maxTicksLimit: 6,
                                    callback: function(value) {}
                                }
                            },
                            x: {
                                border: {
                                    display: false
                                },
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    font: {
                                        size: 11,
                                        weight: '500',
                                        family: "'Inter', system-ui, sans-serif"
                                    },
                                    color: '#64748b',
                                    padding: 8,
                                    maxRotation: 0,
                                    autoSkip: true,
                                    maxTicksLimit: 10
                                }
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

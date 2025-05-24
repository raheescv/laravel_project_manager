<div>
    <div class="card shadow-sm">
        <div class="card-body">
            <!-- Filter Section -->
            <div class="row mb-4 g-3">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="branch_id" class="form-label fw-bold text-secondary mb-2">
                            <i class="pli-building me-1"></i>Branch
                        </label>
                        <select wire:model.live="branch_id" class="form-select shadow-sm border-light" id="branch_id">
                            <option value="">All Branches</option>
                            @foreach ($branches as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="period" class="form-label fw-bold text-secondary mb-2">
                            <i class="pli-time-clock me-1"></i>Period
                        </label>
                        <select wire:model.live="period" class="form-select shadow-sm border-light" id="period">
                            <option value="monthly">Current Month</option>
                            <option value="quarterly">Current Quarter</option>
                            <option value="yearly">Current Year</option>
                            <option value="previous_month">Previous Month</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="start_date" class="form-label fw-bold text-secondary mb-2">
                            <i class="pli-calendar-4 me-1"></i>Start Date
                        </label>
                        <input type="date" wire:model.live="start_date" class="form-control shadow-sm border-light" id="start_date">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="end_date" class="form-label fw-bold text-secondary mb-2">
                            <i class="pli-calendar-4 me-1"></i>End Date
                        </label>
                        <input type="date" wire:model.live="end_date" class="form-control shadow-sm border-light" id="end_date">
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card shadow-sm bg-gradient h-100 hover-shadow-lg transition-all duration-300">
                        <div class="card-body position-relative overflow-hidden bg-success bg-opacity-10">
                            <div class="position-absolute top-0 end-0 opacity-10 mt-n3 me-n3">
                                <i class="pli-money-bag" style="font-size: 6rem"></i>
                            </div>
                            <h6 class="card-title text-success mb-3 d-flex align-items-center">
                                <i class="pli-money-bag me-2"></i>
                                <span>Total Income</span>
                            </h6>
                            <h3 class="mb-0 fw-bold">{{ currency($totalIncome) }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm bg-gradient h-100 hover-shadow-lg transition-all duration-300">
                        <div class="card-body position-relative overflow-hidden bg-danger bg-opacity-10">
                            <div class="position-absolute top-0 end-0 opacity-10 mt-n3 me-n3">
                                <i class="pli-receipt" style="font-size: 6rem"></i>
                            </div>
                            <h6 class="card-title text-danger mb-3 d-flex align-items-center">
                                <i class="pli-receipt me-2"></i>
                                <span>Total Expenses</span>
                            </h6>
                            <h3 class="mb-0 fw-bold">{{ currency($totalExpenses) }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm bg-gradient h-100 hover-shadow-lg transition-all duration-300">
                        <div class="card-body position-relative overflow-hidden {{ $netProfitLoss > 0 ? 'bg-primary bg-opacity-10' : 'bg-danger bg-opacity-10' }}">
                            <div class="position-absolute top-0 end-0 opacity-10 mt-n3 me-n3">
                                <i class="{{ $netProfitLoss > 0 ? 'pli-statistic' : 'pli-down' }}" style="font-size: 6rem"></i>
                            </div>
                            <h6 class="card-title {{ $netProfitLoss > 0 ? 'text-primary' : 'text-danger' }} mb-3 d-flex align-items-center">
                                @if ($netProfitLoss > 0)
                                    <i class="pli-statistic me-2"></i>
                                    <span>Net Profit</span>
                                @else
                                    <i class="pli-down me-2"></i>
                                    <span>Net Loss</span>
                                @endif
                            </h6>
                            <h3 class="mb-0 fw-bold">{{ currency($netProfitLoss) }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">
                                <i class="pli-pie-chart-3 me-2"></i>Income Distribution
                            </h6>
                        </div>
                        <div class="card-body">
                            <canvas id="incomeChart" wire:ignore></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">
                                <i class="pli-pie-chart-3 me-2"></i>Expense Distribution
                            </h6>
                        </div>
                        <div class="card-body">
                            <canvas id="expenseChart" wire:ignore></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detailed Statement -->
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover shadow-sm">
                    <thead>
                        <tr>
                            <th colspan="2" class="text-center bg-light py-3">
                                <h5 class="mb-1 text-primary">
                                    <i class="pli-file-text me-2"></i>Detailed Statement
                                </h5>
                                <small class="text-muted d-block">
                                    <i class="pli-calendar me-1"></i>
                                    {{ \Carbon\Carbon::parse($start_date)->format('d M Y') }} -
                                    {{ \Carbon\Carbon::parse($end_date)->format('d M Y') }}
                                </small>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Income Section -->
                        <tr class="bg-light">
                            <th colspan="2">
                                <i class="psi-coins me-2"></i>Income
                            </th>
                        </tr>
                        @forelse($incomeDetails as $name => $amount)
                            <tr>
                                <td class="ps-4">{{ $name }}</td>
                                <td class="text-end">{{ currency($amount) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-center">No income records found</td>
                            </tr>
                        @endforelse
                        <tr class="table-light">
                            <td><strong>Total Income</strong></td>
                            <td class="text-end"><strong>{{ currency($totalIncome) }}</strong></td>
                        </tr>

                        <!-- Expenses Section -->
                        <tr class="bg-light">
                            <th colspan="2">
                                <i class="psi-receipt me-2"></i>Expenses
                            </th>
                        </tr>
                        @forelse($expenseDetails as $name => $amount)
                            <tr>
                                <td class="ps-4">{{ $name }}</td>
                                <td class="text-end">{{ currency($amount) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-center">No expense records found</td>
                            </tr>
                        @endforelse
                        <tr class="table-light">
                            <td><strong>Total Expenses</strong></td>
                            <td class="text-end"><strong>{{ currency($totalExpenses) }}</strong></td>
                        </tr>

                        <!-- Profit/Loss Section -->
                        <tr class="table-primary">
                            <td><strong>Net Profit/Loss</strong></td>
                            <td class="text-end">
                                <strong>{{ currency($netProfitLoss) }}</strong>
                                @if ($netProfitLoss > 0)
                                    <span class="text-success">(Profit)</span>
                                @else
                                    <span class="text-danger">(Loss)</span>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            @if ($totalIncome > 0 || $totalExpenses > 0)
                <div class="row mt-4">
                    <div class="col-md-3">
                    </div>
                    <div class="col-md-6">
                        <div class="card shadow-sm bg-gradient hover-shadow-lg transition-all duration-300">
                            <div class="card-body position-relative overflow-hidden bg-primary bg-opacity-10">
                                <div class="position-absolute top-0 end-0 opacity-10 mt-n3 me-n3">
                                    <i class="pli-financial" style="font-size: 6rem"></i>
                                </div>
                                <h6 class="card-title text-primary mb-3">
                                    <i class="pli-financial me-2"></i>Financial Metrics
                                </h6>
                                <div class="table-responsive">
                                    <table class="table table-sm border-0">
                                        <tr class="border-bottom">
                                            <td class="ps-0 py-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="me-3">
                                                        <span class="badge bg-success bg-opacity-10 text-success p-2">
                                                            <i class="pli-arrow-up"></i>
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-1">Profit Margin</h6>
                                                        <small class="text-muted">Net profit as percentage of revenue</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-end align-middle pe-0">
                                                <h5 class="mb-0 {{ $netProfitLoss > 0 ? 'text-success' : 'text-danger' }}">
                                                    {{ $totalIncome > 0 ? currency(($netProfitLoss / $totalIncome) * 100) : 0 }}%
                                                </h5>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="ps-0 py-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="me-3">
                                                        <span class="badge bg-danger bg-opacity-10 text-danger p-2">
                                                            <i class="pli-receipt"></i>
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-1">Expense Ratio</h6>
                                                        <small class="text-muted">Total expenses as percentage of revenue</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-end align-middle pe-0">
                                                <h5 class="mb-0 {{ $totalIncome > 0 && $totalExpenses / $totalIncome < 0.7 ? 'text-success' : 'text-danger' }}">
                                                    {{ $totalIncome > 0 ? currency(($totalExpenses / $totalIncome) * 100) : 0 }}%
                                                </h5>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('livewire:initialized', function() {
                // Register Chart.js plugins
                Chart.register(ChartDataLabels);

                // Get chart contexts
                const incomeCtx = document.getElementById('incomeChart').getContext('2d');
                const expenseCtx = document.getElementById('expenseChart').getContext('2d');


                const chartOptions = {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        animateScale: true,
                        animateRotate: true,
                        duration: 1200,
                        easing: 'easeOutElastic'
                    },
                    cutout: '60%',
                    plugins: {
                        datalabels: {
                            color: function(context) {
                                const value = context.dataset.data[context.dataIndex];
                                const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                                return (value / total) > 0.1 ? '#fff' : '#333';
                            },
                            font: {
                                weight: 'bold',
                                size: function(context) {
                                    const value = context.dataset.data[context.dataIndex];
                                    const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                                    return (value / total) > 0.15 ? 13 : 11;
                                },
                                lineHeight: 1.2,
                                family: '"Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
                            },
                            textStrokeColor: 'rgba(0,0,0,0.4)',
                            textStrokeWidth: 3,
                            textShadowBlur: 5,
                            textShadowColor: 'rgba(0,0,0,0.3)',
                            formatter: function(value, context) {
                                const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                if (percentage > 5) {
                                    return [
                                        context.chart.data.labels[context.dataIndex],
                                        `${percentage}%`,
                                        currency(value)
                                    ];
                                }
                                return '';
                            },
                            offset: 5,
                            align: 'center',
                            anchor: 'center',
                            clamp: true,
                            rotation: function(context) {
                                const angle = context.dataset.circumference * context.dataset.data[context.dataIndex] / context.dataset.data.reduce((a, b) => a + b, 0) * 2 * Math.PI;
                                return angle < Math.PI ? 0 : 180;
                            }
                        },
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                pointStyle: 'rectRounded',
                                generateLabels: function(chart) {
                                    const data = chart.data;
                                    if (data.labels.length && data.datasets.length) {
                                        const dataset = data.datasets[0];
                                        const total = dataset.data.reduce((acc, val) => acc + val, 0);
                                        return data.labels.map((label, i) => {
                                            const value = dataset.data[i];
                                            const percentage = ((value / total) * 100).toFixed(1);
                                            return {
                                                text: `${label} - ${currency(value)} (${percentage}%)`,
                                                fillStyle: dataset.backgroundColor[i],
                                                strokeStyle: dataset.borderColor[i],
                                                lineWidth: 1,
                                                hidden: false,
                                                index: i
                                            };
                                        });
                                    }
                                    return [];
                                },
                                font: {
                                    size: 12,
                                    family: '"Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                                    const value = context.raw;
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return ` ${context.label}: ${currency(value)} (${percentage}%)`;
                                }
                            },
                            padding: 12,
                            backgroundColor: 'rgba(0,0,0,0.85)',
                            titleFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            bodyFont: {
                                size: 13
                            },
                            borderColor: 'rgba(255,255,255,0.1)',
                            borderWidth: 1,
                            usePointStyle: true,
                            boxPadding: 6,
                            cornerRadius: 8,
                            displayColors: true,
                            animation: {
                                duration: 150
                            }
                        }
                    },
                    layout: {
                        padding: {
                            top: 15,
                            bottom: 15,
                            left: 10,
                            right: 10
                        }
                    },
                    elements: {
                        arc: {
                            borderWidth: 2,
                            hoverOffset: 8
                        }
                    }
                };
                // Helper function for currency formatting
                function currency(value) {
                    return value.toLocaleString('en-US', {
                        style: 'currency',
                        currency: 'USD',
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 0
                    });
                }

                let incomeChart, expenseChart;

                // Function to create/update charts
                function updateCharts(data) {
                    console.log(data);
                    // Destroy existing charts if they exist
                    if (incomeChart) incomeChart.destroy();
                    if (expenseChart) expenseChart.destroy();

                    // Create new charts with updated data
                    // Define color palettes
                    const incomeColors = [{
                            base: '#2563eb',
                            light: '#3b82f6'
                        }, // Blue
                        {
                            base: '#059669',
                            light: '#10b981'
                        }, // Green
                        {
                            base: '#7c3aed',
                            light: '#8b5cf6'
                        }, // Purple
                        {
                            base: '#0891b2',
                            light: '#06b6d4'
                        }, // Cyan
                        {
                            base: '#6366f1',
                            light: '#818cf8'
                        } // Indigo
                    ];

                    const expenseColors = [{
                            base: '#dc2626',
                            light: '#ef4444'
                        }, // Red
                        {
                            base: '#ea580c',
                            light: '#f97316'
                        }, // Orange
                        {
                            base: '#d97706',
                            light: '#f59e0b'
                        }, // Amber
                        {
                            base: '#be123c',
                            light: '#e11d48'
                        }, // Rose
                        {
                            base: '#be185d',
                            light: '#ec4899'
                        } // Pink
                    ];

                    incomeChart = new Chart(incomeCtx, {
                        type: 'doughnut',
                        data: {
                            labels: data.incomeData.map(item => item.name),
                            datasets: [{
                                data: data.incomeData.map(item => item.y),
                                backgroundColor: incomeColors.map(c => c.light),
                                borderColor: incomeColors.map(c => c.base),
                                borderWidth: 2,
                                hoverBackgroundColor: incomeColors.map(c => c.base),
                                hoverBorderColor: incomeColors.map(c => c.base),
                                hoverBorderWidth: 0
                            }]
                        },
                        options: chartOptions
                    });

                    expenseChart = new Chart(expenseCtx, {
                        type: 'doughnut',
                        data: {
                            labels: data.expenseData.map(item => item.name),
                            datasets: [{
                                data: data.expenseData.map(item => item.y),
                                backgroundColor: expenseColors.map(c => c.light),
                                borderColor: expenseColors.map(c => c.base),
                                borderWidth: 2,
                                hoverBackgroundColor: expenseColors.map(c => c.base),
                                hoverBorderColor: expenseColors.map(c => c.base),
                                hoverBorderWidth: 0
                            }]
                        },
                        options: chartOptions
                    });
                }

                // Create initial charts
                const initialData = {
                    incomeData: {!! $incomeChartData ?: '[]' !!},
                    expenseData: {!! $expenseChartData ?: '[]' !!}
                };
                updateCharts(initialData);

                // Update charts when data changes
                Livewire.on('refreshCharts', (event) => {
                    updateCharts(event[0]);
                });
            });
        </script>
    @endpush
</div>

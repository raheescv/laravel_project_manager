<div>
    <!-- Date Range Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-center">
                <div class="col-md-4">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn {{ $dateRange === '7d' ? 'btn-primary' : 'btn-outline-primary' }}" wire:click="setDateRange('7d')">7 Days</button>
                        <button type="button" class="btn {{ $dateRange === '30d' ? 'btn-primary' : 'btn-outline-primary' }}" wire:click="setDateRange('30d')">30 Days</button>
                        <button type="button" class="btn {{ $dateRange === 'this_month' ? 'btn-primary' : 'btn-outline-primary' }}" wire:click="setDateRange('this_month')">This Month</button>
                    </div>
                </div>
                <div class="col-md-4">
                    <select wire:model.live="branch_id" class="form-select">
                        <option value="">All Branches</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <select wire:model.live="user_id" class="form-select">
                        <option value="">All Users</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Overview Cards -->
    <div class="row g-4 mb-4">
        <!-- Total Visitors -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0">
                            <i class="demo-psi-male-female fs-2 text-primary"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h3 class="mb-1">{{ number_format($stats['total_visitors']) }}</h3>
                            <p class="mb-0">Total Visitors</p>
                        </div>
                        <div class="ms-auto">
                            @if ($stats['daily_change'] > 0)
                                <span class="badge bg-success">+{{ $stats['daily_change'] }}%</span>
                            @elseif($stats['daily_change'] < 0)
                                <span class="badge bg-danger">{{ $stats['daily_change'] }}%</span>
                            @else
                                <span class="badge bg-secondary">0%</span>
                            @endif
                        </div>
                    </div>
                    <div class="progress" style="height: 4px;">
                        <div class="progress-bar bg-primary" style="width: {{ min(100, $stats['engagement_rate']) }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Users -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0">
                            <i class="fa fa-user fs-2 text-success"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h3 class="mb-1">{{ number_format($stats['active_users']) }}</h3>
                            <p class="mb-0">Active Users</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Page Views -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0">
                            <i class="demo-psi-monitor-2 fs-2 text-info"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h3 class="mb-1">{{ number_format($stats['page_views']) }}</h3>
                            <p class="mb-0">Page Views</p>
                        </div>
                        <div class="ms-auto">
                            @if ($stats['weekly_change'] > 0)
                                <span class="badge bg-success">+{{ $stats['weekly_change'] }}%</span>
                            @elseif($stats['weekly_change'] < 0)
                                <span class="badge bg-danger">{{ $stats['weekly_change'] }}%</span>
                            @else
                                <span class="badge bg-secondary">0%</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Traffic & Popular Pages -->
    <div class="row g-4">
        <!-- Traffic Chart -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0">
                    <h5 class="card-title mb-0">Traffic Overview</h5>
                </div>
                <div class="card-body">
                    <div style="height: 300px;" wire:ignore>
                        <canvas id="trafficChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Popular Pages -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0">
                    <h5 class="card-title mb-0">Popular Pages</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Page</th>
                                    <th class="text-end">Views</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($popularPages as $page)
                                    <tr>
                                        <td>{{ $page['url'] }}</td>
                                        <td class="text-end">{{ number_format($page['views']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('livewire:initialized', function() {
                Chart.register(ChartDataLabels);

                // Initialize traffic chart
                let trafficCtx = document.getElementById('trafficChart').getContext('2d');
                trafficChart = new Chart(trafficCtx, {
                    type: 'line',
                    data: @js($trafficData),
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            title: {
                                display: true,
                                text: 'Daily Visitor Traffic',
                                position: 'top',
                                font: {
                                    size: 16,
                                    weight: 'bold'
                                },
                                padding: {
                                    top: 10,
                                    bottom: 20
                                }
                            },
                            datalabels: {
                                anchor: 'end',
                                align: 'top',
                                offset: 5,
                                backgroundColor: 'rgba(255,255,255,0.8)',
                                borderRadius: 4,
                                padding: 4,
                                color: '#666',
                                font: {
                                    weight: 'bold',
                                    size: 11
                                },
                                formatter: function(value) {
                                    return value > 0 ? value.toLocaleString() : '';
                                }
                            },
                            datalabels: {
                                display: true,
                                anchor: 'end',
                                align: 'top',
                                offset: 1,
                                backgroundColor: 'rgba(255,255,255,0.8)',
                                borderRadius: 4,
                                padding: 4,
                                color: '#666',
                                font: {
                                    weight: 'bold',
                                    size: 11
                                },
                                formatter: function(value) {
                                    return value > 0 ? value.toLocaleString() : '';
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: false
                                }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return value.toLocaleString();
                                    }
                                }
                            }
                        }
                    }
                });
            });

            // Handle real-time updates
            window.addEventListener('visitorDataUpdated', event => {
                updateChart(event.detail[0]);
            });

            function updateChart(data) {
                if (trafficChart) {
                    trafficChart.data = data;
                    trafficChart.update('active');
                }
            }
        </script>
    @endpush
</div>

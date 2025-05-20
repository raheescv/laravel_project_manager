<div>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <!-- Enhanced Header -->
            <div class="d-md-flex align-items-center justify-content-between mb-3">
                <div class="animate__animated animate__fadeIn">
                    <h1 class="page-title mb-0 mt-2">Visitor Analytics</h1>
                    <p class="lead mb-0 text-muted">
                        <i class="demo-pli-bar-chart fs-5 me-2 text-primary align-middle"></i>
                        Real-time visitor insights and analytics
                    </p>
                </div>

                <!-- Enhanced Date Range Picker -->
                <div class="d-flex gap-3 mt-3 mt-md-0 animate__animated animate__fadeIn animate__delay-1s">
                    <div class="btn-group shadow-sm">
                        <button wire:click="setDateRange('7d')" class="btn @if ($dateRange === '7d') btn-primary @else btn-light @endif">
                            <i class="fa fa-calendar-week me-2"></i>Last 7 Days
                        </button>
                        <button wire:click="setDateRange('30d')" class="btn @if ($dateRange === '30d') btn-primary @else btn-light @endif">
                            <i class="fa fa-calendar-alt me-2"></i>Last 30 Days
                        </button>
                        <button wire:click="setDateRange('this_month')" class="btn @if ($dateRange === 'this_month') btn-primary @else btn-light @endif">
                            <i class="fa fa-calendar me-2"></i>This Month
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="content__boxed" wire:loading.class="opacity-50">
        <div class="content__wrap">
            <!-- Stat Cards Row -->
            <div class="row g-3 mb-4">
                <!-- Total Visitors Card -->
                <div class="col-sm-6 col-md-4">
                    <div class="card border-0 shadow-hover-lg h-100 overflow-hidden animate__animated animate__fadeInUp">
                        <div class="card-body position-relative">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h6 class="text-muted mb-1">Total Visitors</h6>
                                    <h3 class="mb-0 fw-bold text-primary counter">
                                        {{ number_format($stats['total_visitors']) }}
                                    </h3>
                                </div>
                                <div class="p-3 rounded-circle bg-primary bg-opacity-10">
                                    <i class="fa fa-users fs-3 text-primary"></i>
                                </div>
                            </div>

                            <!-- Trend Indicator -->
                            <div class="d-flex align-items-center mt-3">
                                @if ($stats['weekly_change'] > 0)
                                    <span class="badge bg-success-subtle text-success p-2">
                                        <i class="fa fa-arrow-up me-1"></i>{{ number_format($stats['weekly_change'], 1) }}%
                                    </span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger p-2">
                                        <i class="fa fa-arrow-down me-1"></i>{{ number_format(abs($stats['weekly_change']), 1) }}%
                                    </span>
                                @endif
                                <span class="text-muted ms-2 small">vs last period</span>
                            </div>

                            <!-- Mini Sparkline -->
                            <div class="sparkline-container mt-3" style="height: 30px;">
                                <canvas id="visitorsSparkline"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Active Users Card -->
                <div class="col-sm-6 col-md-4">
                    <div class="card border-0 shadow-hover-lg h-100 overflow-hidden animate__animated animate__fadeInUp" style="animation-delay: 0.2s">
                        <div class="card-body position-relative">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h6 class="text-muted mb-1">Active Users</h6>
                                    <div class="d-flex align-items-center">
                                        <h3 class="mb-0 fw-bold text-success active-users-count">
                                            {{ $stats['active_users'] }}
                                        </h3>
                                        <span class="badge bg-success ms-2 pulse">LIVE</span>
                                    </div>
                                </div>
                                <div class="p-3 rounded-circle bg-success bg-opacity-10">
                                    <i class="fa fa-user-clock fs-3 text-success"></i>
                                </div>
                            </div>

                            <!-- Real-time Chart -->
                            <div class="real-time-chart mt-3" style="height: 50px;">
                                <canvas id="activeUsersChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Page Views Card -->
                <div class="col-sm-6 col-md-4">
                    <div class="card border-0 shadow-hover-lg h-100 overflow-hidden animate__animated animate__fadeInUp" style="animation-delay: 0.4s">
                        <div class="card-body position-relative">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h6 class="text-muted mb-1">Page Views</h6>
                                    <h3 class="mb-0 fw-bold text-info counter">
                                        {{ number_format($stats['page_views']) }}
                                    </h3>
                                </div>
                                <div class="p-3 rounded-circle bg-info bg-opacity-10">
                                    <i class="fa fa-eye fs-3 text-info"></i>
                                </div>
                            </div>

                            <!-- Views per Hour -->
                            <div class="mt-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted small">Views/Hour</span>
                                    <span class="badge bg-info-subtle text-info">{{ round($stats['page_views'] / 24) }}</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-info" role="progressbar" style="width: {{ min(($stats['page_views'] / 24 / 100) * 100, 100) }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row g-3 mb-4">
                <!-- Traffic Overview -->
                <div class="col-md-12">
                    <div class="card border-0 shadow-hover-lg animate__animated animate__fadeIn" style="animation-delay: 0.8s">
                        <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center py-3">
                            <h5 class="mb-0">Traffic Overview</h5>
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-outline-primary active">Hourly</button>
                                <button type="button" class="btn btn-sm btn-outline-primary">Daily</button>
                                <button type="button" class="btn btn-sm btn-outline-primary">Weekly</button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart-container" style="height: 300px;">
                                <canvas id="trafficChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Row -->
            <div class="row g-3">
                <!-- Popular Pages -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-hover-lg animate__animated animate__fadeIn" style="animation-delay: 1.2s">
                        <div class="card-header bg-transparent border-0 py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Popular Pages</h5>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Page</th>
                                            <th class="text-end">Views</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($popularPages as $page)
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="page-icon rounded bg-light p-2 me-2">
                                                            <i class="fa fa-globe fs-4 text-primary"></i>
                                                        </div>
                                                        <div>
                                                            <div class="text-truncate" style="max-width: 400px;">
                                                                {{ $page['url'] }}
                                                            </div>
                                                            <small class="text-muted">
                                                                {{ parse_url($page['url'], PHP_URL_PATH) }}
                                                            </small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-end">
                                                    <span class="fw-bold">{{ number_format($page['views']) }}</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Device Analytics -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-hover-lg animate__animated animate__fadeIn" style="animation-delay: 1.4s">
                        <div class="card-header bg-transparent border-0 py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Device Analytics</h5>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Device</th>
                                            <th class="text-end">Users</th>
                                            <th class="text-end">Sessions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($deviceStats as $stat)
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="device-icon rounded bg-light p-2 me-2">
                                                            <i
                                                                class="fa fa-{{ $stat['device'] === 'desktop' ? 'desktop' : ($stat['device'] === 'mobile' ? 'mobile-alt' : 'tablet') }} fs-4
                                                            {{ $stat['device'] === 'desktop' ? 'text-primary' : ($stat['device'] === 'mobile' ? 'text-success' : 'text-warning') }}">
                                                            </i>
                                                        </div>
                                                        <span class="text-capitalize">{{ $stat['device'] }}</span>
                                                    </div>
                                                </td>
                                                <td class="text-end">
                                                    <span class="fw-bold">{{ number_format($stat['users']) }}</span>
                                                </td>
                                                <td class="text-end">
                                                    <span class="fw-bold">{{ number_format($stat['sessions']) }}</span>
                                                </td>
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

    @push('styles')
        <style>
            /* Enhanced Card Hover Effects */
            .shadow-hover-lg {
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }

            .shadow-hover-lg:hover {
                transform: translateY(-5px);
                box-shadow: 0 1rem 3rem rgba(0, 0, 0, .175) !important;
            }

            /* Animated Counter */
            .counter {
                transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            }

            /* Live Badge Pulse Animation */
            .pulse {
                animation: pulse 2s infinite;
            }

            @keyframes pulse {
                0% {
                    box-shadow: 0 0 0 0 rgba(25, 135, 84, 0.4);
                }

                70% {
                    box-shadow: 0 0 0 10px rgba(25, 135, 84, 0);
                }

                100% {
                    box-shadow: 0 0 0 0 rgba(25, 135, 84, 0);
                }
            }

            /* Progress Bar Animation */
            .progress-bar {
                transition: width 1s ease;
            }

            /* Enhanced Table Styling */
            .table {
                margin-bottom: 0;
            }

            .table th {
                font-weight: 600;
                text-transform: uppercase;
                font-size: 0.75rem;
                letter-spacing: 0.5px;
            }

            .table td {
                vertical-align: middle;
            }

            /* Device Icon Transitions */
            .device-icon {
                transition: transform 0.2s ease;
            }

            .device-icon:hover {
                transform: scale(1.1);
            }

            /* Chart Container */
            .chart-container {
                position: relative;
            }

            /* Loader Animation */
            .opacity-50 {
                position: relative;
            }

            .opacity-50::after {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(255, 255, 255, 0.7);
                backdrop-filter: blur(4px);
                z-index: 1000;
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            // Initialize charts when the document is ready
            document.addEventListener('DOMContentLoaded', function() {
                initCharts();
                initCounters();
                initActiveUsersUpdate();
            });

            // Initialize animated counters
            function initCounters() {
                document.querySelectorAll('.counter').forEach(counter => {
                    const target = parseInt(counter.innerText.replace(/,/g, ''));
                    let current = 0;
                    const increment = target / 40;
                    const duration = 1000;
                    const step = duration / 40;

                    const updateCounter = () => {
                        current += increment;
                        if (current < target) {
                            counter.innerText = Math.floor(current).toLocaleString();
                            setTimeout(updateCounter, step);
                        } else {
                            counter.innerText = target.toLocaleString();
                        }
                    };

                    updateCounter();
                });
            }

            // Initialize all charts
            function initCharts() {
                // Traffic Overview Chart
                const trafficCtx = document.getElementById('trafficChart').getContext('2d');
                new Chart(trafficCtx, {
                    type: 'line',
                    data: {
                        labels: @json(array_keys($trafficData)),
                        datasets: [{
                            label: 'Visitors',
                            data: @json(array_values($trafficData)),
                            borderColor: '#0d6efd',
                            backgroundColor: 'rgba(13, 110, 253, 0.1)',
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    drawBorder: false
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                });
                // Active Users Sparkline
                const sparklineCtx = document.getElementById('activeUsersChart').getContext('2d');
                new Chart(sparklineCtx, {
                    type: 'line',
                    data: {
                        labels: Array.from({
                            length: 20
                        }, (_, i) => i),
                        datasets: [{
                            data: @json($sparklineData),
                            borderColor: '#198754',
                            borderWidth: 2,
                            pointRadius: 0,
                            fill: true,
                            backgroundColor: 'rgba(25, 135, 84, 0.1)',
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            x: {
                                display: false
                            },
                            y: {
                                display: false
                            }
                        }
                    }
                });
            }

            // Listen for Livewire updates
            window.addEventListener('visitorDataUpdated', event => {
                initCharts();
                initCounters();
            });
        </script>
    @endpush
</div>

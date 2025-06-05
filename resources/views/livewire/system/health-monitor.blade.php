<div>
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center text-white">
                <h4 class="mb-0 text-white">
                    <i class="fa fa-heartbeat text-primary me-2"></i>
                    System Health Monitor
                </h4>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary btn-sm" wire:click="runHealthCheck" wire:loading.attr="disabled">
                        <i class="fa fa-sync-alt" wire:loading.class="fa-spin"></i>
                        <span wire:loading.remove>Run Check</span>
                        <span wire:loading>Running...</span>
                    </button>

                    <button class="btn btn-outline-secondary btn-sm text-white" wire:click="toggleAutoRefresh">
                        <i class="fa fa-{{ $autoRefresh ? 'pause' : 'play' }}"></i>
                        {{ $autoRefresh ? 'Pause' : 'Start' }} Auto-refresh
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Overall Status -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center gap-3">
                                @php
                                    $statusConfig = [
                                        'ok' => ['icon' => 'check-circle', 'color' => 'success', 'text' => 'All Systems Operational'],
                                        'warning' => ['icon' => 'exclamation-triangle', 'color' => 'warning', 'text' => 'Some Issues Detected'],
                                        'failed' => ['icon' => 'times-circle', 'color' => 'danger', 'text' => 'Critical Issues Found'],
                                        'unknown' => ['icon' => 'question-circle', 'color' => 'secondary', 'text' => 'Status Unknown'],
                                    ];
                                    $config = $statusConfig[$this->overallStatus] ?? $statusConfig['unknown'];
                                @endphp

                                <div class="bg-{{ $config['color'] }} bg-opacity-10 p-3 rounded-circle">
                                    <i class="fa fa-{{ $config['icon'] }} text-{{ $config['color'] }} fs-3"></i>
                                </div>
                                <div>
                                    <h5 class="mb-1 text-{{ $config['color'] }}">{{ $config['text'] }}</h5>
                                    <small class="text-muted">Last updated: {{ now()->format('M d, Y H:i:s') }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="row text-center">
                                @php $counts = $this->statusCounts; @endphp
                                <div class="col-3">
                                    <div class="text-success">
                                        <div class="fs-4 fw-bold">{{ $counts['ok'] }}</div>
                                        <small>Healthy</small>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="text-warning">
                                        <div class="fs-4 fw-bold">{{ $counts['warning'] }}</div>
                                        <small>Warning</small>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="text-danger">
                                        <div class="fs-4 fw-bold">{{ $counts['failed'] }}</div>
                                        <small>Failed</small>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="text-primary">
                                        <div class="fs-4 fw-bold">{{ $counts['total'] }}</div>
                                        <small>Total</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Health Check Results -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0">
                        <i class="fa fa-list text-primary me-2"></i>
                        Health Check Details
                    </h6>
                </div>
                <div class="card-body p-0">
                    @if ($this->healthResults->isEmpty())
                        <div class="text-center py-5">
                            <i class="fa fa-info-circle text-muted fs-1 mb-3"></i>
                            <h6 class="text-muted">No health check results available</h6>
                            <p class="text-muted mb-0">Run a health check to see the system status</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="border-0 py-3">Check Name</th>
                                        <th class="border-0 py-3">Status</th>
                                        <th class="border-0 py-3">Message</th>
                                        <th class="border-0 py-3">Last Run</th>
                                        <th class="border-0 py-3">Duration</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($this->healthResults as $checkName => $results)
                                        @php $latestResult = $results->first(); @endphp
                                        <tr>
                                            <td class="py-3">
                                                <div class="d-flex align-items-center gap-2">
                                                    <i class="fa fa-cog text-muted"></i>
                                                    <span class="fw-medium">{{ str_replace(['_', '-'], ' ', \Illuminate\Support\Str::title($checkName)) }}</span>
                                                </div>
                                            </td>
                                            <td class="py-3">
                                                @php
                                                    $statusColors = [
                                                        'ok' => 'success',
                                                        'warning' => 'warning',
                                                        'failed' => 'danger',
                                                    ];
                                                    $color = $statusColors[$latestResult->status] ?? 'secondary';
                                                @endphp
                                                <span class="badge bg-{{ $color }} bg-opacity-10 text-{{ $color }} px-3 py-2">
                                                    <i
                                                        class="fa fa-{{ $latestResult->status === 'ok' ? 'check' : ($latestResult->status === 'warning' ? 'exclamation-triangle' : 'times') }} me-1"></i>
                                                    {{ ucfirst($latestResult->status) }}
                                                </span>
                                            </td>
                                            <td class="py-3">
                                                <div class="text-truncate" style="max-width: 300px;" title="{{ $latestResult->notification_message ?? 'No message' }}">
                                                    {{ $latestResult->notification_message ?? 'No message' }}
                                                </div>
                                            </td>
                                            <td class="py-3">
                                                <small class="text-muted">
                                                    {{ $latestResult->created_at->diffForHumans() }}
                                                </small>
                                            </td>
                                            <td class="py-3">
                                                <small class="text-muted">
                                                    {{ $latestResult->runtime_milliseconds }}ms
                                                </small>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if ($autoRefresh)
        <script>
            setTimeout(() => {
                @this.call('runHealthCheck');
            }, {{ $refreshInterval * 1000 }});
        </script>
    @endif
</div>

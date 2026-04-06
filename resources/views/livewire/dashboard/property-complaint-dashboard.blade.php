<div>
    {{-- Header with filter --}}
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-3">
        <div></div>
        <div class="btn-group btn-group-sm">
            <button wire:click="setDateFilter('today')" class="btn btn-{{ $dateFilter === 'today' ? 'danger' : 'outline-danger' }} btn-sm">Today</button>
            <button wire:click="setDateFilter('week')" class="btn btn-{{ $dateFilter === 'week' ? 'danger' : 'outline-danger' }} btn-sm">Week</button>
            <button wire:click="setDateFilter('month')" class="btn btn-{{ $dateFilter === 'month' ? 'danger' : 'outline-danger' }} btn-sm">Month</button>
            <button wire:click="setDateFilter('all')" class="btn btn-{{ $dateFilter === 'all' ? 'danger' : 'outline-danger' }} btn-sm">All</button>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted small mb-1">Total Complaints</p>
                            <h4 class="h5 mb-0 fw-bold">{{ number_format($totalComplaints) }}</h4>
                        </div>
                        <div class="rounded-3 bg-primary bg-opacity-10 p-2">
                            <i class="fa fa-list-alt text-primary fa-lg"></i>
                        </div>
                    </div>
                    @if($totalComplaintsChange != 0)
                        <div class="mt-2">
                            <small class="text-{{ $totalComplaintsChange > 0 ? 'danger' : 'success' }}">
                                <i class="fa fa-{{ $totalComplaintsChange > 0 ? 'arrow-up' : 'arrow-down' }} me-1"></i>
                                {{ abs($totalComplaintsChange) }}% vs last month
                            </small>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted small mb-1">Pending</p>
                            <h4 class="h5 mb-0 fw-bold">{{ number_format($pendingComplaints) }}</h4>
                        </div>
                        <div class="rounded-3 bg-warning bg-opacity-10 p-2">
                            <i class="fa fa-clock-o text-warning fa-lg"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <small class="text-muted">{{ $pendingPercentage }}% of total</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted small mb-1">Urgent (Critical)</p>
                            <h4 class="h5 mb-0 fw-bold text-danger">{{ number_format($urgentComplaints) }}</h4>
                        </div>
                        <div class="rounded-3 bg-danger bg-opacity-10 p-2">
                            <i class="fa fa-exclamation-triangle text-danger fa-lg"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <small class="text-muted">{{ $urgentPercentage }}% of total</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted small mb-1">Resolved</p>
                            <h4 class="h5 mb-0 fw-bold text-success">{{ number_format($resolvedComplaints) }}</h4>
                        </div>
                        <div class="rounded-3 bg-success bg-opacity-10 p-2">
                            <i class="fa fa-check-circle text-success fa-lg"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <small class="text-muted">{{ $resolvedPercentage }}% resolution rate</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Overdue Alert --}}
    @if($overdueCount > 0)
        <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center mb-4" role="alert">
            <i class="fa fa-exclamation-circle fa-lg me-3"></i>
            <div>
                <strong>{{ $overdueCount }} overdue {{ $overdueCount === 1 ? 'complaint' : 'complaints' }}</strong>
                exceeding target resolution time. Immediate attention required.
            </div>
        </div>
    @endif

    <div class="row g-3 mb-4">
        {{-- Complaints by Building --}}
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h6 class="mb-0 fw-semibold"><i class="fa fa-building-o text-primary me-2"></i>By Building (Top 5)</h6>
                </div>
                <div class="card-body pt-0">
                    @if(count($complaintsByBuilding) > 0)
                        @php $maxBldg = max(array_column($complaintsByBuilding, 'count')); @endphp
                        @foreach($complaintsByBuilding as $bldg)
                            <div class="mb-3">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span class="fw-medium">{{ $bldg['name'] }}</span>
                                    <span class="text-danger fw-semibold">{{ $bldg['count'] }}</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-danger bg-opacity-75"
                                        style="width: {{ $maxBldg > 0 ? round(($bldg['count'] / $maxBldg) * 100) : 0 }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center text-muted py-3">
                            <i class="fa fa-info-circle"></i> No data
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Monthly Trend --}}
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h6 class="mb-0 fw-semibold"><i class="fa fa-line-chart text-info me-2"></i>Monthly Trend</h6>
                </div>
                <div class="card-body pt-0">
                    @foreach($monthlyTrend as $trend)
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="fw-medium">{{ $trend['month'] }}</span>
                            </div>
                            <div class="d-flex gap-2 small text-muted mb-1">
                                <span><i class="fa fa-plus-circle text-danger me-1"></i>New: {{ $trend['new'] }}</span>
                                <span><i class="fa fa-check-circle text-success me-1"></i>Resolved: {{ $trend['resolved'] }}</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                @php $trendTotal = max($trend['new'], 1); @endphp
                                <div class="progress-bar bg-success" style="width: {{ round(($trend['resolved'] / $trendTotal) * 100) }}%"></div>
                                <div class="progress-bar bg-danger bg-opacity-50" style="width: {{ round((max($trend['new'] - $trend['resolved'], 0) / $trendTotal) * 100) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Resolution by Priority --}}
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h6 class="mb-0 fw-semibold"><i class="fa fa-tachometer text-warning me-2"></i>Resolution Time</h6>
                </div>
                <div class="card-body pt-0">
                    @if($averageResolutionTime > 0)
                        <div class="text-center mb-3 p-3 bg-light rounded-3">
                            <div class="h4 mb-0 fw-bold text-primary">{{ $averageResolutionTime }} days</div>
                            <small class="text-muted">Average Resolution Time</small>
                        </div>
                    @endif
                    @foreach($resolutionByPriority as $rp)
                        <div class="d-flex align-items-center justify-content-between mb-2 small">
                            <div class="d-flex align-items-center">
                                <span class="badge bg-{{ $rp['color'] }} me-2" style="width: 8px; height: 8px; padding: 0; border-radius: 50%;"></span>
                                <span>{{ $rp['label'] }}</span>
                            </div>
                            <div>
                                <span class="fw-semibold {{ $rp['avg_days'] > $rp['target'] ? 'text-danger' : 'text-success' }}">
                                    {{ $rp['avg_days'] }}d
                                </span>
                                <span class="text-muted"> / {{ $rp['target'] }}d target</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

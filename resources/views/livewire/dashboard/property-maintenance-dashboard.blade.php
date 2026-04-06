<div>
    {{-- Header with filter --}}
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-3">
        <div></div>
        <div class="btn-group btn-group-sm">
            <button wire:click="setDateFilter('today')" class="btn btn-{{ $dateFilter === 'today' ? 'info' : 'outline-info' }}">Today</button>
            <button wire:click="setDateFilter('week')" class="btn btn-{{ $dateFilter === 'week' ? 'info' : 'outline-info' }}">Week</button>
            <button wire:click="setDateFilter('month')" class="btn btn-{{ $dateFilter === 'month' ? 'info' : 'outline-info' }}">Month</button>
            <button wire:click="setDateFilter('all')" class="btn btn-{{ $dateFilter === 'all' ? 'info' : 'outline-info' }}">All</button>
        </div>
    </div>

    {{-- Status Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 bg-primary bg-opacity-10 p-2 me-3">
                            <i class="fa fa-wrench text-primary fa-lg"></i>
                        </div>
                        <div>
                            <h4 class="h5 mb-0 fw-bold">{{ number_format($totalMaintenance) }}</h4>
                            <p class="text-muted small mb-0">Total Requests</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 bg-warning bg-opacity-10 p-2 me-3">
                            <i class="fa fa-clock-o text-warning fa-lg"></i>
                        </div>
                        <div>
                            <h4 class="h5 mb-0 fw-bold">{{ number_format($pendingMaintenance) }}</h4>
                            <p class="text-muted small mb-0">Pending</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 bg-info bg-opacity-10 p-2 me-3">
                            <i class="fa fa-spinner text-info fa-lg"></i>
                        </div>
                        <div>
                            <h4 class="h5 mb-0 fw-bold">{{ number_format($inProgressMaintenance) }}</h4>
                            <p class="text-muted small mb-0">In Progress</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 bg-success bg-opacity-10 p-2 me-3">
                            <i class="fa fa-check-circle text-success fa-lg"></i>
                        </div>
                        <div>
                            <h4 class="h5 mb-0 fw-bold">{{ number_format($completedMaintenance) }}</h4>
                            <p class="text-muted small mb-0">Completed</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        {{-- Priority & Segment Breakdown --}}
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h6 class="mb-0 fw-semibold"><i class="fa fa-flag text-danger me-2"></i>Priority Breakdown</h6>
                </div>
                <div class="card-body pt-0">
                    @foreach($priorityBreakdown as $priority)
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="d-flex align-items-center">
                                <span class="badge bg-{{ $priority['color'] }} me-2" style="width: 10px; height: 10px; padding: 0; border-radius: 50%;"></span>
                                <span class="small">{{ $priority['label'] }}</span>
                            </div>
                            <span class="badge bg-{{ $priority['color'] }} bg-opacity-10 text-{{ $priority['color'] }}">{{ $priority['count'] }}</span>
                        </div>
                    @endforeach

                    <hr class="my-3">
                    <h6 class="small fw-semibold text-muted mb-3"><i class="fa fa-tags me-1"></i>By Segment</h6>
                    @foreach($segmentBreakdown as $segment)
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="small">{{ $segment['label'] }}</span>
                            <span class="badge bg-{{ $segment['color'] }} bg-opacity-10 text-{{ $segment['color'] }}">{{ $segment['count'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Complaint Status & Technician Workload --}}
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h6 class="mb-0 fw-semibold"><i class="fa fa-exclamation-triangle text-warning me-2"></i>Complaint Status</h6>
                </div>
                <div class="card-body pt-0">
                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <div class="text-center p-2 rounded-3 bg-dark bg-opacity-10">
                                <div class="h5 mb-0 fw-bold">{{ $outstandingComplaints }}</div>
                                <small class="text-muted">Outstanding</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="text-center p-2 rounded-3 bg-info bg-opacity-10">
                                <div class="h5 mb-0 fw-bold">{{ $assignedComplaints }}</div>
                                <small class="text-muted">Assigned</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="text-center p-2 rounded-3 bg-success bg-opacity-10">
                                <div class="h5 mb-0 fw-bold">{{ $completedComplaints }}</div>
                                <small class="text-muted">Completed</small>
                            </div>
                        </div>
                    </div>

                    @if(count($technicianWorkload) > 0)
                        <hr class="my-3">
                        <h6 class="small fw-semibold text-muted mb-3"><i class="fa fa-user me-1"></i>Top Technicians</h6>
                        @foreach($technicianWorkload as $tech)
                            <div class="mb-2">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span>{{ $tech['name'] }}</span>
                                    <span class="text-muted">{{ $tech['completed'] }}/{{ $tech['total'] }}</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-success" style="width: {{ $tech['total'] > 0 ? round(($tech['completed'] / $tech['total']) * 100) : 0 }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

        {{-- Maintenance by Group --}}
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h6 class="mb-0 fw-semibold"><i class="fa fa-bar-chart text-info me-2"></i>By Property Group</h6>
                </div>
                <div class="card-body pt-0">
                    @if(count($maintenanceByGroup) > 0)
                        @php $maxCount = max(array_column($maintenanceByGroup, 'count')); @endphp
                        @foreach($maintenanceByGroup as $group)
                            <div class="mb-3">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span class="fw-medium">{{ $group['name'] }}</span>
                                    <span class="text-primary fw-semibold">{{ $group['count'] }}</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-primary bg-opacity-75"
                                        style="width: {{ $maxCount > 0 ? round(($group['count'] / $maxCount) * 100) : 0 }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center text-muted py-3">
                            <i class="fa fa-info-circle"></i> No data available
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Maintenance Requests --}}
    @if(count($recentMaintenance) > 0)
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom-0 py-3">
                <h6 class="mb-0 fw-semibold"><i class="fa fa-history text-muted me-2"></i>Recent Maintenance Requests</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Date</th>
                                <th>Property</th>
                                <th>Building</th>
                                <th>Customer</th>
                                <th class="text-center">Priority</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentMaintenance as $m)
                                <tr>
                                    <td class="ps-3 small">{{ $m['date'] }}</td>
                                    <td class="small fw-medium">{{ $m['property'] }}</td>
                                    <td class="small">{{ $m['building'] }}</td>
                                    <td class="small">{{ $m['customer'] }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $m['priority_color'] }} bg-opacity-10 text-{{ $m['priority_color'] }}">{{ $m['priority'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $m['status_color'] }} bg-opacity-10 text-{{ $m['status_color'] }}">{{ $m['status'] }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>

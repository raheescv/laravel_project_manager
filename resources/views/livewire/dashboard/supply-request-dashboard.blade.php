<div>
    {{-- Status Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3 text-center">
                    <div class="rounded-3 bg-warning bg-opacity-10 d-inline-flex p-2 mb-2">
                        <i class="fa fa-clock-o text-warning fa-lg"></i>
                    </div>
                    <h5 class="mb-0 fw-bold">{{ $requirementCount }}</h5>
                    <small class="text-muted">Requirement</small>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3 text-center">
                    <div class="rounded-3 bg-info bg-opacity-10 d-inline-flex p-2 mb-2">
                        <i class="fa fa-check-circle text-info fa-lg"></i>
                    </div>
                    <h5 class="mb-0 fw-bold">{{ $approvedCount }}</h5>
                    <small class="text-muted">Approved</small>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3 text-center">
                    <div class="rounded-3 bg-primary bg-opacity-10 d-inline-flex p-2 mb-2">
                        <i class="fa fa-shield text-primary fa-lg"></i>
                    </div>
                    <h5 class="mb-0 fw-bold">{{ $finalApprovedCount }}</h5>
                    <small class="text-muted">Final Approved</small>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3 text-center">
                    <div class="rounded-3 bg-dark bg-opacity-10 d-inline-flex p-2 mb-2">
                        <i class="fa fa-money text-dark fa-lg"></i>
                    </div>
                    <h5 class="mb-0 fw-bold">{{ $collectedCount }}</h5>
                    <small class="text-muted">Collected</small>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3 text-center">
                    <div class="rounded-3 bg-success bg-opacity-10 d-inline-flex p-2 mb-2">
                        <i class="fa fa-flag-checkered text-success fa-lg"></i>
                    </div>
                    <h5 class="mb-0 fw-bold">{{ $completedCount }}</h5>
                    <small class="text-muted">Completed</small>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3 text-center">
                    <div class="rounded-3 bg-danger bg-opacity-10 d-inline-flex p-2 mb-2">
                        <i class="fa fa-times-circle text-danger fa-lg"></i>
                    </div>
                    <h5 class="mb-0 fw-bold">{{ $rejectedCount }}</h5>
                    <small class="text-muted">Rejected</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        {{-- Financial Summary --}}
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h6 class="mb-0 fw-semibold"><i class="fa fa-dollar text-success me-2"></i>Financial Summary</h6>
                </div>
                <div class="card-body pt-0">
                    <div class="text-center p-3 bg-teal bg-gradient rounded-3 mb-3">
                        <div class="h4 mb-0 fw-bold text-white">{{ currency($totalAmount) }}</div>
                        <small class="text-white-50">Total Supply Value</small>
                    </div>
                    <div class="d-flex justify-content-between small mb-2">
                        <span class="text-muted">Completed Value</span>
                        <span class="fw-semibold text-success">{{ currency($completedAmount) }}</span>
                    </div>
                    <div class="d-flex justify-content-between small mb-2">
                        <span class="text-muted">Total Requests</span>
                        <span class="fw-semibold">{{ $totalCount }}</span>
                    </div>
                    @if($totalCount > 0)
                        <div class="d-flex justify-content-between small">
                            <span class="text-muted">Completion Rate</span>
                            <span class="fw-semibold text-primary">{{ round(($completedCount / $totalCount) * 100, 1) }}%</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Recent Requests --}}
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h6 class="mb-0 fw-semibold"><i class="fa fa-history text-muted me-2"></i>Recent Supply Requests</h6>
                </div>
                <div class="card-body p-0">
                    @if(count($recentRequests) > 0)
                        <div class="table-responsive">
                            <table class="table table-hover table-sm mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Order#</th>
                                        <th>Date</th>
                                        <th>Property</th>
                                        <th>Type</th>
                                        <th class="text-end">Amount</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentRequests as $sr)
                                        <tr>
                                            <td class="ps-3 small fw-medium">{{ $sr['order_no'] ?? '-' }}</td>
                                            <td class="small">{{ $sr['date'] }}</td>
                                            <td class="small">{{ $sr['property'] }}</td>
                                            <td class="small">{{ $sr['type'] }}</td>
                                            <td class="text-end small">{{ currency($sr['amount']) }}</td>
                                            <td class="text-center">
                                                <span class="badge bg-{{ $sr['status_color'] }} bg-opacity-10 text-{{ $sr['status_color'] }}">{{ $sr['status'] }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="fa fa-inbox fa-2x mb-2 d-block"></i>
                            No recent supply requests
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

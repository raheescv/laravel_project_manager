<div>
    {{-- Quick Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 bg-warning bg-opacity-10 p-2 me-3">
                            <i class="fa fa-calendar text-warning fa-lg"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 fw-bold">{{ $upcomingCount }}</h5>
                            <small class="text-muted">Expiring (90 days)</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 bg-danger bg-opacity-10 p-2 me-3">
                            <i class="fa fa-calendar-o text-danger fa-lg"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 fw-bold">{{ $expiringThisMonth }}</h5>
                            <small class="text-muted">This Month</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 bg-orange bg-opacity-10 p-2 me-3">
                            <i class="fa fa-clock-o text-orange fa-lg"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 fw-bold">{{ $expiringNext30Days }}</h5>
                            <small class="text-muted">Next 30 Days</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 bg-secondary bg-opacity-10 p-2 me-3">
                            <i class="fa fa-ban text-secondary fa-lg"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 fw-bold text-danger">{{ $expiredCount }}</h5>
                            <small class="text-muted">Expired</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        {{-- Upcoming End Dates --}}
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <h6 class="mb-0 fw-semibold">
                            <i class="fa fa-clock-o text-warning me-2"></i>Upcoming End Dates
                        </h6>
                        <span class="badge bg-warning bg-opacity-10 text-warning">{{ $upcomingCount }} total</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if(count($upcomingEndDates) > 0)
                        <div class="table-responsive">
                            <table class="table table-hover table-sm mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Agreement</th>
                                        <th>Property</th>
                                        <th>Customer</th>
                                        <th class="text-end">Rent</th>
                                        <th class="text-center">End Date</th>
                                        <th class="text-center">Days Left</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($upcomingEndDates as $r)
                                        <tr>
                                            <td class="ps-3 small fw-medium">{{ $r['agreement_no'] }}</td>
                                            <td class="small">{{ $r['property'] }}</td>
                                            <td class="small">{{ $r['customer'] }}</td>
                                            <td class="text-end small">{{ currency($r['rent']) }}</td>
                                            <td class="text-center small">{{ $r['end_date'] }}</td>
                                            <td class="text-center">
                                                <span class="badge bg-{{ $r['urgency'] }} bg-opacity-10 text-{{ $r['urgency'] }}">
                                                    {{ $r['days_left'] }} {{ $r['days_left'] === 1 ? 'day' : 'days' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="fa fa-check-circle fa-2x text-success mb-2 d-block"></i>
                            No upcoming expirations
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Expired Rent Outs --}}
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <h6 class="mb-0 fw-semibold">
                            <i class="fa fa-times-circle text-danger me-2"></i>Expired Agreements
                        </h6>
                        <span class="badge bg-danger bg-opacity-10 text-danger">{{ $expiredCount }} total</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if(count($expiredRentOuts) > 0)
                        <div class="table-responsive">
                            <table class="table table-hover table-sm mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Agreement</th>
                                        <th>Property</th>
                                        <th>Customer</th>
                                        <th class="text-end">Rent</th>
                                        <th class="text-center">End Date</th>
                                        <th class="text-center">Days Expired</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($expiredRentOuts as $r)
                                        <tr>
                                            <td class="ps-3 small fw-medium">{{ $r['agreement_no'] }}</td>
                                            <td class="small">{{ $r['property'] }}</td>
                                            <td class="small">{{ $r['customer'] }}</td>
                                            <td class="text-end small">{{ currency($r['rent']) }}</td>
                                            <td class="text-center small">{{ $r['end_date'] }}</td>
                                            <td class="text-center">
                                                <span class="badge bg-danger bg-opacity-10 text-danger">
                                                    {{ $r['days_expired'] }} {{ $r['days_expired'] === 1 ? 'day' : 'days' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="fa fa-check-circle fa-2x text-success mb-2 d-block"></i>
                            No expired agreements
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

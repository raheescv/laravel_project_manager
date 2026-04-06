<div>
    {{-- Property Overview Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card bg-primary bg-gradient h-100 shadow-sm border-0">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-white bg-opacity-25 p-2 me-3">
                            <i class="fa fa-building text-white"></i>
                        </div>
                        <div>
                            <h3 class="h5 mb-0 text-white">{{ number_format($totalProperties) }}</h3>
                            <p class="text-white-50 small mb-0">Total Properties</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card bg-info bg-gradient h-100 shadow-sm border-0">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-white bg-opacity-25 p-2 me-3">
                            <i class="fa fa-university text-white"></i>
                        </div>
                        <div>
                            <h3 class="h5 mb-0 text-white">{{ number_format($totalBuildings) }}</h3>
                            <p class="text-white-50 small mb-0">Total Buildings</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card bg-success bg-gradient h-100 shadow-sm border-0">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-white bg-opacity-25 p-2 me-3">
                            <i class="fa fa-check-circle text-white"></i>
                        </div>
                        <div>
                            <h3 class="h5 mb-0 text-white">{{ number_format($occupiedProperties) }}</h3>
                            <p class="text-white-50 small mb-0">Occupied</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card bg-warning bg-gradient h-100 shadow-sm border-0">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-white bg-opacity-25 p-2 me-3">
                            <i class="fa fa-exclamation-circle text-white"></i>
                        </div>
                        <div>
                            <h3 class="h5 mb-0 text-white">{{ number_format($vacantProperties) }}</h3>
                            <p class="text-white-50 small mb-0">Vacant</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Group Occupancy Rates --}}
    @if(count($groupOccupancyRates) > 0)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom-0 py-3">
                <div class="d-flex align-items-center justify-content-between">
                    <h6 class="mb-0 fw-semibold text-dark">
                        <i class="fa fa-pie-chart text-primary me-2"></i>Group Occupancy Rates
                    </h6>
                    @if($totalProperties > 0)
                        <span class="badge bg-primary bg-opacity-10 text-primary">
                            Overall: {{ $totalProperties > 0 ? round(($occupiedProperties / $totalProperties) * 100, 1) : 0 }}%
                        </span>
                    @endif
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Group</th>
                                <th class="text-center">Total</th>
                                <th class="text-center">Occupied</th>
                                <th class="text-center">Vacant</th>
                                <th>Occupancy Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($groupOccupancyRates as $group)
                                <tr>
                                    <td class="ps-3 fw-medium">{{ $group['name'] }}</td>
                                    <td class="text-center">{{ $group['total'] }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-success bg-opacity-10 text-success">{{ $group['occupied'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-warning bg-opacity-10 text-warning">{{ $group['vacant'] }}</span>
                                    </td>
                                    <td style="min-width: 200px;">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height: 8px;">
                                                <div class="progress-bar bg-{{ $group['rate'] >= 75 ? 'success' : ($group['rate'] >= 50 ? 'info' : ($group['rate'] >= 25 ? 'warning' : 'danger')) }}"
                                                    style="width: {{ $group['rate'] }}%"></div>
                                            </div>
                                            <small class="fw-semibold text-muted" style="min-width: 45px;">{{ $group['rate'] }}%</small>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- Income Reports --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom-0 py-3">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
                <h6 class="mb-0 fw-semibold text-dark">
                    <i class="fa fa-line-chart text-success me-2"></i>Income Collection Report
                </h6>
                <div class="btn-group btn-group-sm">
                    <button wire:click="setIncomeFilter('today')" class="btn btn-{{ $incomeFilter === 'today' ? 'primary' : 'outline-primary' }}">Today</button>
                    <button wire:click="setIncomeFilter('week')" class="btn btn-{{ $incomeFilter === 'week' ? 'primary' : 'outline-primary' }}">Week</button>
                    <button wire:click="setIncomeFilter('month')" class="btn btn-{{ $incomeFilter === 'month' ? 'primary' : 'outline-primary' }}">Month</button>
                    <button wire:click="setIncomeFilter('all')" class="btn btn-{{ $incomeFilter === 'all' ? 'primary' : 'outline-primary' }}">All</button>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            @if(count($groupIncomeReports) > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Group</th>
                                <th class="text-end">Expected</th>
                                <th class="text-end">Collected</th>
                                <th class="text-end">Pending</th>
                                <th>Collection Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($groupIncomeReports as $report)
                                <tr>
                                    <td class="ps-3 fw-medium">{{ $report['name'] }}</td>
                                    <td class="text-end">{{ currency($report['collection']) }}</td>
                                    <td class="text-end text-success">{{ currency($report['paid']) }}</td>
                                    <td class="text-end text-danger">{{ currency($report['pending']) }}</td>
                                    <td style="min-width: 180px;">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height: 8px;">
                                                <div class="progress-bar bg-{{ $report['rate'] >= 75 ? 'success' : ($report['rate'] >= 50 ? 'warning' : 'danger') }}"
                                                    style="width: {{ $report['rate'] }}%"></div>
                                            </div>
                                            <small class="fw-semibold text-muted" style="min-width: 45px;">{{ $report['rate'] }}%</small>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light fw-semibold">
                            <tr>
                                <td class="ps-3">Total</td>
                                <td class="text-end">{{ currency($totalIncomeData['collection'] ?? 0) }}</td>
                                <td class="text-end text-success">{{ currency($totalIncomeData['paid'] ?? 0) }}</td>
                                <td class="text-end text-danger">{{ currency($totalIncomeData['pending'] ?? 0) }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height: 8px;">
                                            <div class="progress-bar bg-primary" style="width: {{ $totalIncomeData['rate'] ?? 0 }}%"></div>
                                        </div>
                                        <small class="fw-semibold text-primary" style="min-width: 45px;">{{ $totalIncomeData['rate'] ?? 0 }}%</small>
                                    </div>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <div class="text-center py-4 text-muted">
                    <i class="fa fa-info-circle me-1"></i> No income data available for the selected period.
                </div>
            @endif
        </div>
    </div>
</div>

@php
    $occupiedRate = $totalProperties > 0 ? round(($occupiedProperties / $totalProperties) * 100, 1) : 0;
    $filterLabel = match ($incomeFilter) {
        'today' => 'Today',
        'week' => 'This Week',
        'month' => 'This Month',
        default => 'All Time',
    };
@endphp

<div class="property-dashboard-page">
    <style>
        .property-dashboard-page .dashboard-shell {
            background:
                radial-gradient(circle at top right, rgba(13, 110, 253, 0.08), transparent 28%),
                linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
            border: 1px solid rgba(13, 110, 253, 0.08);
            border-radius: 1.5rem;
            padding: 1.5rem;
        }

        .property-dashboard-page .hero-panel {
            background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%);
            border-radius: 1.25rem;
            color: #fff;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        .property-dashboard-page .hero-panel::after {
            content: "";
            position: absolute;
            inset: auto -4rem -4rem auto;
            width: 12rem;
            height: 12rem;
            background: rgba(255, 255, 255, 0.12);
            border-radius: 999px;
        }

        .property-dashboard-page .metric-card,
        .property-dashboard-page .insight-card,
        .property-dashboard-page .income-card {
            border: 0;
            border-radius: 1.1rem;
            box-shadow: 0 0.75rem 2rem rgba(31, 45, 61, 0.08);
        }

        .property-dashboard-page .metric-card {
            overflow: hidden;
            min-height: 100%;
        }

        .property-dashboard-page .metric-card .card-body {
            padding: 1.2rem;
        }

        .property-dashboard-page .metric-icon {
            width: 3rem;
            height: 3rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 1rem;
            background: rgba(255, 255, 255, 0.18);
            font-size: 1.1rem;
        }

        .property-dashboard-page .metric-value {
            font-size: 2rem;
            line-height: 1;
            font-weight: 700;
        }

        .property-dashboard-page .section-title {
            font-weight: 700;
            color: #1f2d3d;
            margin-bottom: 0.25rem;
        }

        .property-dashboard-page .section-note {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 0;
        }

        .property-dashboard-page .insight-card .card-body,
        .property-dashboard-page .income-card .card-body {
            padding: 1.25rem;
        }

        .property-dashboard-page .group-progress {
            height: 0.6rem;
            border-radius: 999px;
            background: #e9ecef;
            overflow: hidden;
        }

        .property-dashboard-page .group-progress-bar {
            height: 100%;
            border-radius: inherit;
        }

        .property-dashboard-page .group-progress-bar.high {
            background: linear-gradient(90deg, #198754, #20c997);
        }

        .property-dashboard-page .group-progress-bar.medium {
            background: linear-gradient(90deg, #ffc107, #fd7e14);
        }

        .property-dashboard-page .group-progress-bar.low {
            background: linear-gradient(90deg, #dc3545, #fd7e14);
        }

        .property-dashboard-page .mini-stat {
            background: #f8f9fa;
            border-radius: 0.9rem;
            padding: 0.85rem;
            text-align: center;
        }

        .property-dashboard-page .mini-stat .value {
            display: block;
            font-size: 1.1rem;
            font-weight: 700;
            color: #1f2d3d;
        }

        .property-dashboard-page .mini-stat .label {
            display: block;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #6c757d;
        }

        .property-dashboard-page .filter-toolbar .btn {
            border-radius: 999px;
        }

        .property-dashboard-page .income-kpi {
            background: #f8f9fa;
            border-radius: 0.9rem;
            padding: 0.9rem 1rem;
        }

        .property-dashboard-page .income-kpi .label {
            color: #6c757d;
            font-size: 0.8rem;
            margin-bottom: 0.2rem;
        }

        .property-dashboard-page .income-kpi .value {
            font-size: 1.05rem;
            font-weight: 700;
            color: #1f2d3d;
        }

        @media (max-width: 767.98px) {
            .property-dashboard-page .dashboard-shell {
                padding: 1rem;
            }

            .property-dashboard-page .hero-panel {
                padding: 1.15rem;
            }

            .property-dashboard-page .metric-value {
                font-size: 1.65rem;
            }
        }
    </style>

    <div class="dashboard-shell">
        <div class="hero-panel mb-4">
            <div class="row align-items-center g-3 position-relative">
                <div class="col-lg-8">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="metric-icon">
                            <i class="fa fa-building"></i>
                        </span>
                        <div>
                            <h3 class="mb-1 text-white">Property Management Dashboard</h3>
                            <p class="mb-0 text-white text-opacity-75">Occupancy, availability, and income performance in one place.</p>
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-sm-4">
                            <div class="mini-stat bg-white bg-opacity-10 text-white border border-white border-opacity-10">
                                <span class="value text-white">{{ number_format($totalBuildings) }}</span>
                                <span class="label text-white-50">Buildings</span>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="mini-stat bg-white bg-opacity-10 text-white border border-white border-opacity-10">
                                <span class="value text-white">{{ number_format($totalProperties) }}</span>
                                <span class="label text-white-50">Units</span>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="mini-stat bg-white bg-opacity-10 text-white border border-white border-opacity-10">
                                <span class="value text-white">{{ number_format($occupiedRate, 1) }}%</span>
                                <span class="label text-white-50">Occupancy</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="bg-white bg-opacity-10 border border-white border-opacity-10 rounded-4 p-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small text-white text-opacity-75">Overall Occupancy</span>
                            <strong>{{ number_format($occupiedRate, 1) }}%</strong>
                        </div>
                        <div class="progress" style="height: 0.7rem;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $occupiedRate }}%" aria-valuenow="{{ $occupiedRate }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <div class="mt-3 small text-white text-opacity-75">
                            {{ number_format($occupiedProperties) }} occupied, {{ number_format($vacantProperties) }} vacant
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            @can('property building.view')
                <div class="col-sm-6 col-xl-3">
                    <a href="{{ route('property::building::index') }}" class="text-decoration-none">
                        <div class="card metric-card bg-primary bg-gradient text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="text-white-50 small mb-2">Total Buildings</div>
                                        <div class="metric-value">{{ number_format($totalBuildings) }}</div>
                                    </div>
                                    <span class="metric-icon"><i class="fa fa-university"></i></span>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            @endcan
            @can('property.view')
                <div class="col-sm-6 col-xl-3">
                    <a href="{{ route('property::property::index') }}" class="text-decoration-none">
                        <div class="card metric-card bg-success bg-gradient text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="text-white-50 small mb-2">Total Units</div>
                                        <div class="metric-value">{{ number_format($totalProperties) }}</div>
                                    </div>
                                    <span class="metric-icon"><i class="fa fa-home"></i></span>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <a href="{{ route('property::property::index', ['status' => 'occupied']) }}" class="text-decoration-none">
                        <div class="card metric-card bg-info bg-gradient text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="text-white-50 small mb-2">Occupied Units</div>
                                        <div class="metric-value">{{ number_format($occupiedProperties) }}</div>
                                    </div>
                                    <span class="metric-icon"><i class="fa fa-check-circle"></i></span>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <a href="{{ route('property::property::index', ['status' => 'vacant']) }}" class="text-decoration-none">
                        <div class="card metric-card bg-warning bg-gradient text-dark">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="text-dark text-opacity-75 small mb-2">Vacant Units</div>
                                        <div class="metric-value">{{ number_format($vacantProperties) }}</div>
                                    </div>
                                    <span class="metric-icon bg-dark bg-opacity-10 text-dark"><i class="fa fa-key"></i></span>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            @endcan
        </div>

        <div class="row g-4 mb-4">
            <div class="col-xl-6">
                <div class="d-flex justify-content-between align-items-end mb-3">
                    <div>
                        <h5 class="section-title">Group Occupancy</h5>
                        <p class="section-note">Per-project unit occupancy across all property groups.</p>
                    </div>
                </div>
                @if (count($groupOccupancyRates) > 0)
                    <div class="row g-3">
                        @foreach ($groupOccupancyRates as $group)
                            @php
                                $rateClass = $group['rate'] >= 80 ? 'high' : ($group['rate'] >= 60 ? 'medium' : 'low');
                            @endphp
                            <div class="col-12">
                                <div class="card insight-card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                            <div>
                                                <h6 class="mb-1">
                                                    <a href="{{ route('property::property::index', ['property_group_id' => $group['id']]) }}" class="text-decoration-none">
                                                        {{ $group['name'] }}
                                                    </a>
                                                </h6>
                                                <div class="text-muted small">Occupancy performance</div>
                                            </div>
                                            <span class="badge text-bg-light border">{{ number_format($group['rate'], 1) }}%</span>
                                        </div>
                                        <div class="row g-2 mb-3">
                                            <div class="col-4">
                                                <div class="mini-stat">
                                                    <span class="value">{{ number_format($group['total']) }}</span>
                                                    <span class="label">Total</span>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="mini-stat">
                                                    <span class="value text-success">{{ number_format($group['occupied']) }}</span>
                                                    <span class="label">Occupied</span>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="mini-stat">
                                                    <span class="value text-warning">{{ number_format($group['vacant']) }}</span>
                                                    <span class="label">Vacant</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="group-progress">
                                            <div class="group-progress-bar {{ $rateClass }}" style="width: {{ $group['rate'] }}%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="card insight-card">
                        <div class="card-body text-center text-muted py-4">No occupancy data available.</div>
                    </div>
                @endif
            </div>

            <div class="col-xl-6">
                <div class="d-flex justify-content-between align-items-end mb-3">
                    <div>
                        <h5 class="section-title">Availability Snapshot</h5>
                        <p class="section-note">Tracks available versus sold inventory by group.</p>
                    </div>
                </div>
                @if (count($groupAvailabilityRates) > 0)
                    <div class="row g-3">
                        @foreach ($groupAvailabilityRates as $group)
                            @php
                                $rateClass = $group['available_rate'] >= 80 ? 'high' : ($group['available_rate'] >= 60 ? 'medium' : 'low');
                            @endphp
                            <div class="col-12">
                                <div class="card insight-card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                            <div>
                                                <h6 class="mb-1">
                                                    <a href="{{ route('property::property::index', ['property_group_id' => $group['id']]) }}" class="text-decoration-none">
                                                        {{ $group['name'] }}
                                                    </a>
                                                </h6>
                                                <div class="text-muted small">Availability mix</div>
                                            </div>
                                            <span class="badge text-bg-light border">{{ number_format($group['available_rate'], 1) }}%</span>
                                        </div>
                                        <div class="row g-2 mb-3">
                                            <div class="col-4">
                                                <div class="mini-stat">
                                                    <span class="value">{{ number_format($group['total']) }}</span>
                                                    <span class="label">Total</span>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="mini-stat">
                                                    <span class="value text-success">{{ number_format($group['available']) }}</span>
                                                    <span class="label">Available</span>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="mini-stat">
                                                    <span class="value text-danger">{{ number_format($group['sold']) }}</span>
                                                    <span class="label">Sold</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="group-progress">
                                            <div class="group-progress-bar {{ $rateClass }}" style="width: {{ $group['available_rate'] }}%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="card insight-card">
                        <div class="card-body text-center text-muted py-4">No availability data available.</div>
                    </div>
                @endif
            </div>
        </div>

        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-3">
            <div>
                <h5 class="section-title">Group Income Report</h5>
                <p class="section-note">Collection, pending balances, and overdue exposure for {{ strtolower($filterLabel) }}.</p>
            </div>
            <div class="btn-group btn-group-sm filter-toolbar" role="group" aria-label="Income filter">
                <button type="button" class="btn {{ $incomeFilter === 'today' ? 'btn-primary' : 'btn-outline-primary' }}" wire:click="setIncomeFilter('today')">Today</button>
                <button type="button" class="btn {{ $incomeFilter === 'week' ? 'btn-primary' : 'btn-outline-primary' }}" wire:click="setIncomeFilter('week')">Week</button>
                <button type="button" class="btn {{ $incomeFilter === 'month' ? 'btn-primary' : 'btn-outline-primary' }}" wire:click="setIncomeFilter('month')">Month</button>
                <button type="button" class="btn {{ $incomeFilter === 'all' ? 'btn-primary' : 'btn-outline-primary' }}" wire:click="setIncomeFilter('all')">All</button>
            </div>
        </div>

        @if (count($groupIncomeReports) > 0)
            <div class="row g-3 mb-4">
                @foreach ($groupIncomeReports as $group)
                    @php
                        $displayIncome = $incomeFilter === 'all' ? $group['total_income'] : $group['period_income'];
                        $incomeClass = $group['collection_rate'] >= 90 ? 'success' : ($group['collection_rate'] >= 70 ? 'warning' : 'danger');
                    @endphp
                    <div class="col-xl-6">
                        <div class="card income-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                    <div>
                                        <h6 class="mb-1">
                                            <a href="{{ route('property::rent::payments', ['property_group_id' => $group['id']]) }}" class="text-decoration-none">
                                                {{ $group['name'] }}
                                            </a>
                                        </h6>
                                        <div class="text-muted small">{{ $filterLabel }} collections</div>
                                    </div>
                                    <div class="text-end">
                                        <div class="small text-muted">Collected</div>
                                        <div class="fs-5 fw-bold text-{{ $incomeClass }}">{{ currency($displayIncome) }}</div>
                                    </div>
                                </div>

                                <div class="row g-2 mb-3">
                                    <div class="col-4">
                                        <div class="income-kpi">
                                            <div class="label">Collection Rate</div>
                                            <div class="value text-{{ $incomeClass }}">{{ number_format($group['collection_rate'], 1) }}%</div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="income-kpi">
                                            <div class="label">Units</div>
                                            <div class="value">{{ number_format($group['total_units']) }}</div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="income-kpi">
                                            <div class="label">Overdue</div>
                                            <div class="value text-danger">{{ currency($group['overdue_amount']) }}</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <div class="income-kpi">
                                            <div class="label">{{ $filterLabel }}</div>
                                            <div class="value">{{ currency($displayIncome) }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="income-kpi">
                                            <div class="label">Pending Payments</div>
                                            <div class="value text-danger">{{ currency($group['pending_payments']) }}</div>
                                        </div>
                                    </div>
                                    @if ($incomeFilter !== 'month')
                                        <div class="col-md-6">
                                            <div class="income-kpi">
                                                <div class="label">Monthly Income</div>
                                                <div class="value">{{ currency($group['monthly_income']) }}</div>
                                            </div>
                                        </div>
                                    @endif
                                    @if ($incomeFilter !== 'all')
                                        <div class="col-md-6">
                                            <div class="income-kpi">
                                                <div class="label">Total Income</div>
                                                <div class="value">{{ currency($group['total_income']) }}</div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="card income-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="mb-1">Total Income Summary</h6>
                            <div class="text-muted small">Combined performance across all groups.</div>
                        </div>
                        <span class="badge text-bg-primary">{{ number_format($totalIncomeData['overall_collection_rate'] ?? 0, 1) }}% collected</span>
                    </div>
                    <div class="row g-3">
                        <div class="col-sm-6 col-xl-3">
                            <div class="mini-stat">
                                <span class="value text-success">{{ currency($totalIncomeData['total_collected'] ?? 0) }}</span>
                                <span class="label">Total Collected</span>
                            </div>
                        </div>
                        <div class="col-sm-6 col-xl-3">
                            <div class="mini-stat">
                                <span class="value text-warning">{{ currency($totalIncomeData['total_pending'] ?? 0) }}</span>
                                <span class="label">Total Pending</span>
                            </div>
                        </div>
                        <div class="col-sm-6 col-xl-3">
                            <div class="mini-stat">
                                <span class="value text-danger">{{ currency($totalIncomeData['total_overdue'] ?? 0) }}</span>
                                <span class="label">Total Overdue</span>
                            </div>
                        </div>
                        <div class="col-sm-6 col-xl-3">
                            <div class="mini-stat">
                                <span class="value text-primary">{{ number_format($totalIncomeData['overall_collection_rate'] ?? 0, 1) }}%</span>
                                <span class="label">Collection Rate</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="card income-card">
                <div class="card-body text-center text-muted py-5">
                    <i class="fa fa-info-circle me-1"></i> No income data available for the selected period.
                </div>
            </div>
        @endif
    </div>
</div>

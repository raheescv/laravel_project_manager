<div class="lead-dashboard-page">
    @if(auth()->user()->canAny(['property lead.dashboard status count', 'property lead.dashboard recent activities']))
        <div class="row g-3">
            @can('property lead.dashboard status count')
                <div class="col-lg-8">
                    <div class="row g-3">
                        @php
                            $cards = [
                                ['label' => 'Total Leads', 'value' => $totalLeads, 'icon' => 'fa-users', 'color' => 'primary', 'url' => route('property::lead::list')],
                                ['label' => 'New Deals This Week', 'value' => $newDealsThisWeek, 'icon' => 'fa-bolt', 'color' => 'warning', 'url' => route('property::lead::list', ['status' => 'New Lead'])],
                                ['label' => "Follow-ups Today", 'value' => $followUpsToday, 'icon' => 'fa-phone', 'color' => 'danger', 'url' => route('property::lead::list', ['status' => 'Follow Up'])],
                                ['label' => 'Total Closed', 'value' => $closedDeals, 'icon' => 'fa-check-circle', 'color' => 'success', 'url' => route('property::lead::list', ['status' => 'Closed Deal'])],
                                ['label' => 'Visit Scheduled', 'value' => $totalVisitScheduled, 'icon' => 'fa-calendar', 'color' => 'info', 'url' => route('property::lead::list', ['status' => 'Visit Scheduled'])],
                                ['label' => 'Call Backs', 'value' => $totalCallBack, 'icon' => 'fa-reply', 'color' => 'secondary', 'url' => route('property::lead::list', ['status' => 'Call Back'])],
                            ];
                        @endphp
                        @foreach($cards as $card)
                            <div class="col-md-4 col-sm-6">
                                <a href="{{ $card['url'] }}" class="text-decoration-none">
                                    <div class="card shadow-sm border-0 h-100 lead-kpi-card">
                                        <div class="card-body d-flex align-items-center">
                                            <div class="kpi-icon bg-{{ $card['color'] }}-subtle text-{{ $card['color'] }} me-3">
                                                <i class="fa {{ $card['icon'] }} fa-lg"></i>
                                            </div>
                                            <div>
                                                <div class="text-muted small fw-semibold text-uppercase">{{ $card['label'] }}</div>
                                                <div class="fs-3 fw-bold text-dark mb-0">{{ number_format($card['value']) }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @endforeach
                        <div class="col-12">
                            <div class="card shadow-sm border-0">
                                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                                    <h5 class="mb-0 fw-semibold"><i class="fa fa-building text-primary me-2"></i>Available Units</h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="bg-light text-muted">
                                                <tr class="text-uppercase small">
                                                    <th class="ps-3 fw-semibold">Property Group</th>
                                                    <th class="text-end pe-3 fw-semibold">Total Available</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($availableUnits as $unit)
                                                    <tr>
                                                        <td class="ps-3 fw-medium">{{ $unit['name'] ?? '—' }}</td>
                                                        <td class="text-end pe-3">
                                                            <span class="badge bg-success-subtle text-success">{{ number_format($unit['total']) }}</span>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="2" class="text-center text-muted py-4 small">
                                                            <i class="fa fa-building fa-2x d-block mb-2 opacity-25"></i>
                                                            No units available.
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endcan

            @can('property lead.dashboard recent activities')
                <div class="col-lg-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0 fw-semibold"><i class="fa fa-history text-primary me-2"></i>Recent Activity</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0 recent-activity-list">
                                @forelse($recentLeadUpdates as $item)
                                    <li class="d-flex align-items-start gap-2 py-2 border-bottom">
                                        @php
                                            $icon = match($item->status) {
                                                'Follow Up' => 'fa-phone-square text-primary',
                                                'Visit Scheduled' => 'fa-calendar-check-o text-success',
                                                'Closed Deal' => 'fa-check-circle text-success',
                                                'Call Back' => 'fa-reply text-warning',
                                                default => 'fa-info-circle text-info',
                                            };
                                        @endphp
                                        <i class="fa {{ $icon }} fa-lg mt-1"></i>
                                        <div class="flex-grow-1">
                                            <a href="{{ route('property::lead::edit', $item->id) }}" class="text-decoration-none">
                                                <div class="fw-semibold text-dark small">{{ $item->name }}</div>
                                                <div class="small text-muted">
                                                    <span class="badge {{ leadStatusBadgeClass($item->status) }}">{{ $item->status }}</span>
                                                    @if($item->assignee)
                                                        · {{ $item->assignee->name }}
                                                    @endif
                                                </div>
                                            </a>
                                        </div>
                                        <small class="text-muted text-nowrap">{{ $item->updated_at?->diffForHumans() }}</small>
                                    </li>
                                @empty
                                    <li class="text-center text-muted py-4 small">
                                        <i class="fa fa-bell-o fa-2x d-block mb-2 opacity-25"></i>
                                        No recent activity.
                                    </li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>
            @endcan
        </div>
    @endif

    @if(auth()->user()->canAny(['property lead.dashboard source pie chart', 'property lead.dashboard employee bar chart']))
        <div class="row g-3 mt-1">
            @can('property lead.dashboard source pie chart')
                <div class="col-lg-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0 fw-semibold"><i class="fa fa-pie-chart text-primary me-2"></i>Leads Per Source</h5>
                        </div>
                        <div class="card-body">
                            <div style="height: 320px;">
                                <canvas id="leadsSourceChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            @endcan
            @can('property lead.dashboard employee bar chart')
                <div class="col-lg-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0 fw-semibold"><i class="fa fa-bar-chart text-primary me-2"></i>Leads Per Employee</h5>
                        </div>
                        <div class="card-body">
                            <div style="height: 320px;">
                                <canvas id="employeeLeadsChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            @endcan
        </div>
    @endif

    @push('styles')
        <style>
            .lead-dashboard-page .kpi-icon { width: 56px; height: 56px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; }
            .lead-kpi-card { transition: all .2s ease-in-out; }
            .lead-kpi-card:hover { transform: translateY(-2px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.07) !important; }
            .recent-activity-list li:last-child { border-bottom: 0 !important; }
        </style>
    @endpush

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
        <script>
            (function () {
                const palette = ['#0d6efd','#198754','#ffc107','#dc3545','#0dcaf0','#6f42c1','#fd7e14','#20c997','#6c757d','#d63384'];
                const sourceCtx = document.getElementById('leadsSourceChart');
                if (sourceCtx) {
                    new Chart(sourceCtx, {
                        type: 'doughnut',
                        data: {
                            labels: @json($sourceLabels),
                            datasets: [{ data: @json($sourceData), backgroundColor: palette, borderWidth: 0 }]
                        },
                        options: {
                            responsive: true, maintainAspectRatio: false, cutout: '65%',
                            plugins: { legend: { position: 'right', labels: { boxWidth: 12, usePointStyle: true } } }
                        }
                    });
                }
                const empCtx = document.getElementById('employeeLeadsChart');
                if (empCtx) {
                    new Chart(empCtx, {
                        type: 'bar',
                        data: {
                            labels: @json($employeeLabels),
                            datasets: [{ label: 'Leads', data: @json($employeeData), backgroundColor: '#0d6efd', borderRadius: 4, maxBarThickness: 36 }]
                        },
                        options: {
                            responsive: true, maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(0,0,0,0.05)' } },
                                x: { grid: { display: false } }
                            }
                        }
                    });
                }
            })();
        </script>
    @endpush
</div>

<div wire:poll.30s="refresh">
    {{-- ============================================================
         Trading platform — live ops panel
         Bootstrap 5 + Nifty admin theme · FA 4.3 icons only
         ============================================================ --}}

    {{-- Header card with title + quick-action links --}}
    <div class="card mb-3 shadow-sm border-0">
        <div class="card-body py-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="d-flex align-items-center">
                    <span class="rounded-circle bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center me-3"
                          style="width:42px;height:42px;">
                        <i class="fa fa-line-chart fs-5"></i>
                    </span>
                    <div>
                        <h5 class="mb-0 fw-semibold">Trading Platform</h5>
                        <small class="text-muted">Live cron · risk · alerts · paper</small>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('flat_trade::strategies') }}" class="btn btn-sm btn-outline-primary">
                        <i class="fa fa-magic me-1"></i> Strategies
                    </a>
                    <a href="{{ route('flat_trade::alerts') }}" class="btn btn-sm btn-outline-primary">
                        <i class="fa fa-bell-o me-1"></i> Alerts
                    </a>
                    <a href="{{ route('flat_trade::backtest') }}" class="btn btn-sm btn-outline-primary">
                        <i class="fa fa-flask me-1"></i> Backtest
                    </a>
                    <a href="{{ route('flat_trade::ai_analyst') }}" class="btn btn-sm btn-outline-primary">
                        <i class="fa fa-comments-o me-1"></i> AI Analyst
                    </a>
                    <button wire:click="refresh" class="btn btn-sm btn-outline-secondary" wire:loading.attr="disabled" wire:target="refresh">
                        <i class="fa fa-refresh me-1" wire:loading.class="fa-spin" wire:target="refresh"></i> Refresh
                    </button>
                    <button wire:click="toggleKillSwitch"
                            class="btn btn-sm {{ $killSwitch ? 'btn-danger' : 'btn-warning' }}">
                        <i class="fa {{ $killSwitch ? 'fa-stop-circle' : 'fa-power-off' }} me-1"></i>
                        {{ $killSwitch ? 'Disengage Kill Switch' : 'Engage Kill Switch' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Status banners --}}
    @if ($killSwitch)
        <div class="alert alert-danger d-flex align-items-center shadow-sm border-0" role="alert">
            <i class="fa fa-exclamation-triangle fs-4 me-3"></i>
            <div>
                <strong>Kill switch is ENGAGED.</strong>
                All new BUY orders are blocked. SELL / FLATTEN remain allowed for safe exits.
            </div>
        </div>
    @endif

    @if (! empty($today['circuit_tripped']))
        <div class="alert alert-warning d-flex align-items-center shadow-sm border-0" role="alert">
            <i class="fa fa-bolt fs-4 me-3"></i>
            <div>
                <strong>Circuit breaker tripped at {{ $today['circuit_tripped_at'] ?? '—' }}.</strong>
                Reason: {{ $today['circuit_reason'] ?? 'unspecified' }}
            </div>
        </div>
    @endif

    {{-- KPI tiles --}}
    <div class="row g-3 mb-3">
        @php
            $kpis = [
                ['label' => 'Cron runs today', 'value' => $today['runs'] ?? 0,        'icon' => 'fa-clock-o',          'color' => 'primary'],
                ['label' => 'Orders placed',   'value' => $today['placed'] ?? 0,      'icon' => 'fa-check-circle',     'color' => 'success'],
                ['label' => 'Rejected',        'value' => $today['rejected'] ?? 0,    'icon' => 'fa-times-circle',     'color' => 'danger'],
                ['label' => 'Risk events',     'value' => $today['risk_events'] ?? 0, 'icon' => 'fa-shield',           'color' => 'warning'],
                ['label' => 'Paper open',      'value' => $today['paper_open'] ?? 0,  'icon' => 'fa-file-text-o',      'color' => 'info'],
            ];
        @endphp
        @foreach ($kpis as $k)
            <div class="col-6 col-md-4 col-lg">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <span class="rounded-circle bg-{{ $k['color'] }} bg-opacity-10 text-{{ $k['color'] }} d-inline-flex align-items-center justify-content-center me-3 flex-shrink-0"
                              style="width:46px;height:46px;">
                            <i class="fa {{ $k['icon'] }} fs-5"></i>
                        </span>
                        <div class="min-w-0">
                            <div class="small text-muted text-truncate">{{ $k['label'] }}</div>
                            <div class="h4 mb-0 fw-bold">{{ number_format($k['value']) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Live positions --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="card-title mb-0">
                <i class="fa fa-briefcase text-primary me-2"></i> Live positions
            </h6>
            <span class="badge bg-light text-dark">{{ count($positions) }} open</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Symbol</th>
                        <th class="text-end">Qty</th>
                        <th class="text-end">Avg</th>
                        <th class="text-end">LTP</th>
                        <th class="text-end">P&amp;L</th>
                        <th class="text-end">P&amp;L %</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($positions as $p)
                        <tr>
                            <td class="fw-semibold font-monospace">{{ $p['symbol'] }}</td>
                            <td class="text-end">{{ number_format($p['quantity']) }}</td>
                            <td class="text-end">₹{{ number_format($p['avg_price'], 2) }}</td>
                            <td class="text-end">₹{{ number_format($p['ltp'], 2) }}</td>
                            <td class="text-end fw-semibold {{ $p['pnl_absolute'] >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ $p['pnl_absolute'] >= 0 ? '+' : '' }}₹{{ number_format($p['pnl_absolute'], 2) }}
                            </td>
                            <td class="text-end">
                                <span class="badge bg-{{ $p['pnl_percent'] >= 0 ? 'success' : 'danger' }} bg-opacity-10 text-{{ $p['pnl_percent'] >= 0 ? 'success' : 'danger' }}">
                                    <i class="fa fa-{{ $p['pnl_percent'] >= 0 ? 'arrow-up' : 'arrow-down' }} me-1"></i>
                                    {{ number_format($p['pnl_percent'], 2) }}%
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <i class="fa fa-inbox fs-3 d-block mb-2 opacity-50"></i>
                                No open positions
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Three columns: cron runs · alerts · risk events --}}
    <div class="row g-3">
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">
                        <i class="fa fa-clock-o text-primary me-2"></i> Recent cron runs
                    </h6>
                </div>
                <ul class="list-group list-group-flush small">
                    @forelse ($recentRuns as $r)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div class="min-w-0">
                                <div class="font-monospace text-truncate">{{ $r->command }}@if($r->action) <span class="text-muted">· {{ $r->action }}</span>@endif</div>
                                <small class="text-muted">{{ optional($r->started_at)->diffForHumans() }}</small>
                            </div>
                            <span class="badge bg-{{ $r->outcome === 'success' ? 'success' : ($r->outcome === 'error' ? 'danger' : ($r->outcome === 'skipped' ? 'secondary' : 'info')) }} bg-opacity-10 text-{{ $r->outcome === 'success' ? 'success' : ($r->outcome === 'error' ? 'danger' : ($r->outcome === 'skipped' ? 'secondary' : 'info')) }}">
                                {{ $r->outcome }}
                            </span>
                        </li>
                    @empty
                        <li class="list-group-item text-center text-muted py-4">
                            <i class="fa fa-clock-o fs-3 d-block mb-2 opacity-50"></i>
                            No runs yet
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">
                        <i class="fa fa-bell text-warning me-2"></i> Recent alerts
                    </h6>
                </div>
                <ul class="list-group list-group-flush small">
                    @forelse ($recentAlerts as $a)
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <span class="fw-semibold text-truncate">{{ $a->title }}</span>
                                <small class="text-muted ms-2 flex-shrink-0">{{ $a->created_at->diffForHumans() }}</small>
                            </div>
                            <div class="text-muted text-truncate">{{ $a->body }}</div>
                        </li>
                    @empty
                        <li class="list-group-item text-center text-muted py-4">
                            <i class="fa fa-bell-o fs-3 d-block mb-2 opacity-50"></i>
                            No alerts yet
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">
                        <i class="fa fa-shield text-danger me-2"></i> Risk events
                    </h6>
                </div>
                <ul class="list-group list-group-flush small">
                    @forelse ($recentRisk as $e)
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <span class="font-monospace">{{ $e->rule_code }}</span>
                                <small class="text-muted ms-2 flex-shrink-0">{{ optional($e->occurred_at)->diffForHumans() }}</small>
                            </div>
                            <div class="text-muted text-truncate">{{ $e->message }}</div>
                        </li>
                    @empty
                        <li class="list-group-item text-center text-muted py-4">
                            <i class="fa fa-shield fs-3 d-block mb-2 opacity-50"></i>
                            All clear
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>

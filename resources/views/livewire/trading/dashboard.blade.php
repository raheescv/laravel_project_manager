<div wire:poll.30s="refresh">
    {{-- ============================================================
         Risk Operations Center
         Bootstrap 5 + Nifty admin theme · FA 4.3 icons only
         Focused single-page risk dashboard — no cron/alert/strategy
         overviews (those have dedicated drill-downs).
         ============================================================ --}}

    {{-- Header --}}
    <div class="card mb-3 shadow-sm border-0">
        <div class="card-body py-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="d-flex align-items-center">
                    <span class="rounded-circle bg-danger bg-opacity-10 text-danger d-inline-flex align-items-center justify-content-center me-3"
                          style="width:42px;height:42px;">
                        <i class="fa fa-shield fs-5"></i>
                    </span>
                    <div>
                        <h5 class="mb-0 fw-semibold">Risk Operations Center</h5>
                        <small class="text-muted">Kill switch · circuit breaker · exposure · events</small>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button wire:click="refresh"
                            class="btn btn-sm btn-outline-secondary"
                            wire:loading.attr="disabled" wire:target="refresh">
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

    {{-- Status banners (only the ones currently active) --}}
    @if ($killSwitch)
        <div class="alert alert-danger d-flex align-items-start shadow-sm border-0" role="alert">
            <i class="fa fa-exclamation-triangle fs-4 me-3 mt-1"></i>
            <div class="flex-grow-1">
                <strong>Kill switch is ENGAGED.</strong>
                All new BUY orders are blocked. SELL / FLATTEN remain allowed for safe exits.
                @if (! empty($killSwitchMeta['reason']))
                    <div class="small mt-1">
                        <span class="opacity-75">Reason:</span> {{ $killSwitchMeta['reason'] }}
                        @if (! empty($killSwitchMeta['engaged_at']))
                            <span class="opacity-75 ms-2">· since</span>
                            {{ \Carbon\Carbon::parse($killSwitchMeta['engaged_at'])->diffForHumans() }}
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @endif

    @if (! empty($circuit['tripped']))
        <div class="alert alert-warning d-flex align-items-start shadow-sm border-0" role="alert">
            <i class="fa fa-bolt fs-4 me-3 mt-1"></i>
            <div>
                <strong>Circuit breaker tripped at {{ $circuit['tripped_at'] ?? '—' }}.</strong>
                <div class="small mt-1">
                    <span class="opacity-75">Reason:</span> {{ $circuit['reason'] ?? 'unspecified' }}
                </div>
            </div>
        </div>
    @endif

    {{-- Risk snapshot tiles (risk-specific metrics, not generic cron/order counts) --}}
    <div class="row g-3 mb-3">
        @php
            $realized = $circuit['realized_pnl'] ?? 0;
            $unrealized = $exposure['unrealized_pnl'] ?? 0;
            $tiles = [
                [
                    'label' => 'Realized P&L today',
                    'value' => '₹'.number_format($realized, 2),
                    'icon' => 'fa-line-chart',
                    'color' => $realized >= 0 ? 'success' : 'danger',
                ],
                [
                    'label' => 'Unrealized P&L',
                    'value' => '₹'.number_format($unrealized, 2),
                    'icon' => 'fa-area-chart',
                    'color' => $unrealized >= 0 ? 'success' : 'danger',
                ],
                [
                    'label' => 'Open exposure',
                    'value' => '₹'.number_format($exposure['total_notional'] ?? 0, 2),
                    'icon' => 'fa-briefcase',
                    'color' => 'primary',
                ],
                [
                    'label' => 'Open positions',
                    'value' => ($exposure['positions_count'] ?? 0).' / '.($thresholds['max_concurrent_positions'] ?? 0),
                    'icon' => 'fa-cubes',
                    'color' => ($exposure['positions_count'] ?? 0) >= ($thresholds['max_concurrent_positions'] ?? 0) ? 'danger' : 'info',
                ],
                [
                    'label' => 'Risk events today',
                    'value' => number_format($severityCounts['total'] ?? 0),
                    'icon' => 'fa-shield',
                    'color' => ($severityCounts['breaker'] ?? 0) > 0 ? 'danger' : (($severityCounts['blocked'] ?? 0) > 0 ? 'warning' : 'secondary'),
                ],
            ];
        @endphp
        @foreach ($tiles as $t)
            <div class="col-6 col-md-4 col-lg">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <span class="rounded-circle bg-{{ $t['color'] }} bg-opacity-10 text-{{ $t['color'] }} d-inline-flex align-items-center justify-content-center me-3 flex-shrink-0"
                              style="width:46px;height:46px;">
                            <i class="fa {{ $t['icon'] }} fs-5"></i>
                        </span>
                        <div class="min-w-0">
                            <div class="small text-muted text-truncate">{{ $t['label'] }}</div>
                            <div class="h5 mb-0 fw-bold text-truncate">{{ $t['value'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Two-up: rules status (left) + live positions (right) --}}
    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">
                        <i class="fa fa-tasks text-primary me-2"></i> Risk rule thresholds
                    </h6>
                </div>
                <ul class="list-group list-group-flush small">
                    @php
                        $dailyUsed = abs(min(0, $circuit['realized_pnl'] ?? 0));
                        $dailyLimit = max(0.0001, $thresholds['max_daily_loss'] ?? 0);
                        $dailyPct = min(100, ($dailyUsed / $dailyLimit) * 100);
                        $posUsed = $exposure['positions_count'] ?? 0;
                        $posCap = max(1, $thresholds['max_concurrent_positions'] ?? 1);
                        $posPct = min(100, ($posUsed / $posCap) * 100);
                        $sizeUsed = $exposure['max_notional'] ?? 0;
                        $sizeCap = max(0.0001, $thresholds['max_position_size'] ?? 0);
                        $sizePct = min(100, ($sizeUsed / $sizeCap) * 100);
                    @endphp

                    <li class="list-group-item">
                        <div class="d-flex justify-content-between mb-1">
                            <span><i class="fa fa-power-off me-2 text-muted"></i> Kill switch</span>
                            <span class="badge bg-{{ $killSwitch ? 'danger' : 'success' }} bg-opacity-10 text-{{ $killSwitch ? 'danger' : 'success' }}">
                                {{ $killSwitch ? 'ENGAGED' : 'CLEAR' }}
                            </span>
                        </div>
                        <small class="text-muted">Operator-controlled. Blocks new BUYs; SELL/FLATTEN always allowed.</small>
                    </li>

                    <li class="list-group-item">
                        <div class="d-flex justify-content-between mb-1">
                            <span><i class="fa fa-arrow-down me-2 text-muted"></i> Daily loss limit</span>
                            <span class="font-monospace small">
                                ₹{{ number_format($dailyUsed, 0) }} / ₹{{ number_format($dailyLimit, 0) }}
                            </span>
                        </div>
                        <div class="progress" style="height:6px;">
                            <div class="progress-bar bg-{{ $dailyPct >= 100 ? 'danger' : ($dailyPct >= 75 ? 'warning' : 'success') }}"
                                 role="progressbar" style="width: {{ $dailyPct }}%"></div>
                        </div>
                    </li>

                    <li class="list-group-item">
                        <div class="d-flex justify-content-between mb-1">
                            <span><i class="fa fa-cubes me-2 text-muted"></i> Concurrent positions</span>
                            <span class="font-monospace small">{{ $posUsed }} / {{ $posCap }}</span>
                        </div>
                        <div class="progress" style="height:6px;">
                            <div class="progress-bar bg-{{ $posPct >= 100 ? 'danger' : ($posPct >= 75 ? 'warning' : 'info') }}"
                                 role="progressbar" style="width: {{ $posPct }}%"></div>
                        </div>
                    </li>

                    <li class="list-group-item">
                        <div class="d-flex justify-content-between mb-1">
                            <span><i class="fa fa-expand me-2 text-muted"></i> Max position size</span>
                            <span class="font-monospace small">
                                ₹{{ number_format($sizeUsed, 0) }} / ₹{{ number_format($sizeCap, 0) }}
                            </span>
                        </div>
                        <div class="progress" style="height:6px;">
                            <div class="progress-bar bg-{{ $sizePct >= 100 ? 'danger' : ($sizePct >= 75 ? 'warning' : 'primary') }}"
                                 role="progressbar" style="width: {{ $sizePct }}%"></div>
                        </div>
                    </li>

                    <li class="list-group-item d-flex justify-content-between">
                        <span><i class="fa fa-clock-o me-2 text-muted"></i> Symbol cooldown</span>
                        <span class="font-monospace small">{{ $thresholds['cooldown_minutes'] ?? 0 }} min after stop-loss</span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-sm h-100">
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
                                <th class="text-end">Notional</th>
                                <th class="text-end">P&amp;L</th>
                                <th class="text-end">P&amp;L %</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($positions as $p)
                                @php
                                    $notional = ($p['ltp'] ?? 0) * ($p['quantity'] ?? 0);
                                    $sizeCap = max(0.0001, $thresholds['max_position_size'] ?? 0);
                                    $breach = $notional > $sizeCap;
                                @endphp
                                <tr>
                                    <td class="fw-semibold font-monospace">
                                        {{ $p['symbol'] }}
                                        @if ($breach)
                                            <i class="fa fa-exclamation-triangle text-danger ms-1"
                                               title="Notional exceeds max position size"></i>
                                        @endif
                                    </td>
                                    <td class="text-end">{{ number_format($p['quantity']) }}</td>
                                    <td class="text-end">₹{{ number_format($p['avg_price'], 2) }}</td>
                                    <td class="text-end">₹{{ number_format($p['ltp'], 2) }}</td>
                                    <td class="text-end font-monospace small {{ $breach ? 'text-danger fw-semibold' : '' }}">
                                        ₹{{ number_format($notional, 0) }}
                                    </td>
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
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="fa fa-inbox fs-3 d-block mb-2 opacity-50"></i>
                                        No open positions
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Risk events timeline with severity filter --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h6 class="card-title mb-0">
                <i class="fa fa-shield text-danger me-2"></i> Risk events
                <small class="text-muted fw-normal ms-2">latest 25</small>
            </h6>
            @php
                $filters = [
                    [null, 'All', $severityCounts['total'] ?? 0, 'secondary'],
                    ['breaker', 'Breaker', $severityCounts['breaker'] ?? 0, 'danger'],
                    ['blocked', 'Blocked', $severityCounts['blocked'] ?? 0, 'warning'],
                    ['warning', 'Warning', $severityCounts['warning'] ?? 0, 'warning'],
                    ['info', 'Info', $severityCounts['info'] ?? 0, 'info'],
                ];
            @endphp
            <div class="btn-group btn-group-sm" role="group" aria-label="Severity filter">
                @foreach ($filters as [$sev, $label, $count, $color])
                    <button type="button"
                            wire:click="setSeverityFilter({{ $sev === null ? 'null' : "'".$sev."'" }})"
                            class="btn btn-{{ $severityFilter === $sev ? $color : 'outline-'.$color }}">
                        {{ $label }}
                        <span class="badge bg-light text-dark ms-1">{{ $count }}</span>
                    </button>
                @endforeach
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th style="width:140px;">Time</th>
                        <th style="width:110px;">Severity</th>
                        <th style="width:170px;">Rule</th>
                        <th style="width:110px;">Symbol</th>
                        <th style="width:130px;">Strategy</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($riskEvents as $e)
                        @php
                            $sevColor = match ($e->severity) {
                                'breaker' => 'danger',
                                'blocked' => 'warning',
                                'warning' => 'warning',
                                'info' => 'info',
                                default => 'secondary',
                            };
                        @endphp
                        <tr>
                            <td class="text-muted">
                                {{ optional($e->occurred_at)->format('H:i:s') }}
                                <div class="text-muted opacity-75" style="font-size:11px;">
                                    {{ optional($e->occurred_at)->diffForHumans(null, true) }} ago
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-{{ $sevColor }} bg-opacity-10 text-{{ $sevColor }} text-uppercase">
                                    {{ $e->severity }}
                                </span>
                            </td>
                            <td><code class="text-dark">{{ $e->rule_code }}</code></td>
                            <td class="font-monospace">{{ $e->symbol ?? '—' }}</td>
                            <td class="font-monospace">{{ $e->strategy_code ?? '—' }}</td>
                            <td class="text-muted">{{ $e->message }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <i class="fa fa-shield fs-3 d-block mb-2 opacity-50"></i>
                                @if ($severityFilter)
                                    No <strong>{{ $severityFilter }}</strong> events
                                @else
                                    All clear — no risk events
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div>
    {{-- Header --}}
    <div class="card mb-3 shadow-sm border-0">
        <div class="card-body py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex align-items-center">
                <span class="rounded-circle bg-success bg-opacity-10 text-success d-inline-flex align-items-center justify-content-center me-3"
                      style="width:42px;height:42px;">
                    <i class="fa fa-flask fs-5"></i>
                </span>
                <div>
                    <h5 class="mb-0 fw-semibold">Strategy Backtest</h5>
                    <small class="text-muted">Replay bars against any live strategy</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Runner --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white">
            <h6 class="card-title mb-0">
                <i class="fa fa-play-circle text-primary me-2"></i> New backtest
            </h6>
        </div>
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small text-muted">Strategy code</label>
                    <input type="text" class="form-control form-control-sm" wire:model="strategyCode" placeholder="momentum_score">
                </div>
                <div class="col-md-6">
                    <label class="form-label small text-muted">Symbols (comma-separated)</label>
                    <input type="text" class="form-control form-control-sm" wire:model="symbols" placeholder="SBIN-EQ, TCS-EQ, INFY-EQ">
                </div>
                <div class="col-md-3">
                    <button wire:click="run" wire:loading.attr="disabled" wire:target="run" class="btn btn-sm btn-success w-100">
                        <i class="fa fa-play me-1" wire:loading.class="fa-spin" wire:target="run"></i>
                        <span wire:loading.remove wire:target="run">Run backtest</span>
                        <span wire:loading wire:target="run">Running…</span>
                    </button>
                </div>
            </div>
            @if ($message)
                <div class="alert alert-info d-flex align-items-center mt-3 mb-0">
                    <i class="fa fa-info-circle me-2"></i>
                    <div>{{ $message }}</div>
                </div>
            @endif
            <div class="form-text small mt-2">
                <i class="fa fa-lightbulb-o me-1"></i>
                Bars come from <code>trading_bars</code>. Up to 2000 bars per symbol.
            </div>
        </div>
    </div>

    {{-- Past runs --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="card-title mb-0">
                <i class="fa fa-history text-primary me-2"></i> Past runs
            </h6>
            <span class="badge bg-light text-dark">{{ $runs->count() }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Strategy</th>
                        <th class="text-end">Return %</th>
                        <th class="text-end">Max DD %</th>
                        <th class="text-end">Sharpe</th>
                        <th class="text-end">Win rate</th>
                        <th class="text-end">Trades</th>
                        <th class="text-muted">When</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($runs as $r)
                        <tr>
                            <td>#{{ $r->id }}</td>
                            <td class="font-monospace">{{ $r->strategy_code }}</td>
                            <td class="text-end fw-semibold {{ $r->total_return_percent >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ $r->total_return_percent >= 0 ? '+' : '' }}{{ number_format($r->total_return_percent, 2) }}%
                            </td>
                            <td class="text-end text-danger">{{ number_format($r->max_drawdown_percent, 2) }}%</td>
                            <td class="text-end">{{ number_format($r->sharpe, 2) }}</td>
                            <td class="text-end">{{ number_format($r->win_rate * 100, 1) }}%</td>
                            <td class="text-end">{{ $r->trades_count }}</td>
                            <td class="text-muted small">{{ $r->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="fa fa-flask fs-2 d-block mb-2 opacity-50"></i>
                                No backtest runs yet
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

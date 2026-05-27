<div class="card border-0 shadow-sm h-100">
    <div class="card-header bg-info text-white border-0">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h6 class="mb-0 fw-semibold">
                <i class="fa fa-line-chart me-2"></i>
                Market Information
            </h6>
            <div class="d-flex gap-2 align-items-center">
                <div class="btn-group btn-group-sm" role="group" aria-label="Exchange">
                    @foreach (['NSE', 'BSE'] as $ex)
                        <button type="button"
                                wire:click="changeExchange('{{ $ex }}')"
                                class="btn btn-sm fw-semibold {{ $exchange === $ex ? 'bg-white text-info' : 'btn-outline-light' }}">
                            {{ $ex }}
                        </button>
                    @endforeach
                </div>
                <button type="button"
                        class="btn btn-sm btn-outline-light"
                        wire:click="refreshMarketData"
                        @if($loading) disabled @endif>
                    <i class="fa fa-refresh {{ $loading ? 'fa-spin' : '' }}"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="card-body p-0">
        @if($loading)
            <div class="d-flex justify-content-center align-items-center py-5">
                <div class="spinner-border text-info" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <span class="ms-2 text-muted">Loading market data...</span>
            </div>
        @elseif($error)
            <div class="alert alert-danger d-flex align-items-center m-3 mb-0" role="alert">
                <i class="fa fa-exclamation-triangle me-2"></i>
                <span class="flex-grow-1">{{ $error }}</span>
                <button type="button" class="btn btn-sm btn-outline-danger ms-2" wire:click="refreshMarketData">
                    <i class="fa fa-refresh me-1"></i> Retry
                </button>
            </div>
        @else
            <div class="row g-0">
                {{-- Market indices --}}
                <div class="col-md-6 border-end">
                    <div class="p-3">
                        <h6 class="text-secondary fw-semibold mb-3">
                            <i class="fa fa-line-chart me-1"></i>
                            Market Indices
                        </h6>
                        @if(empty($indices))
                            <div class="text-center text-muted py-3">
                                <i class="fa fa-info-circle fs-1 opacity-50"></i>
                                <p class="mb-0 small">No indices data available</p>
                            </div>
                        @else
                            <div class="list-group list-group-flush">
                                @foreach($indices as $index)
                                    <div class="list-group-item border-0 px-0 py-2">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="fw-semibold text-dark">{{ $index['name'] ?? 'N/A' }}</div>
                                                <small class="text-muted">{{ $index['symbol'] ?? 'N/A' }}</small>
                                            </div>
                                            <div class="text-end">
                                                <div class="fw-semibold">{{ number_format($index['ltp'] ?? 0, 2) }}</div>
                                                <small class="{{ ($index['change'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                                    {{ ($index['change'] ?? 0) >= 0 ? '+' : '' }}{{ number_format($index['change'] ?? 0, 2) }}
                                                    ({{ ($index['change_percent'] ?? 0) >= 0 ? '+' : '' }}{{ number_format($index['change_percent'] ?? 0, 2) }}%)
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Top gainers / losers --}}
                <div class="col-md-6">
                    <div class="p-3">
                        <h6 class="text-success fw-semibold mb-3">
                            <i class="fa fa-arrow-up me-1"></i>
                            Top Gainers
                        </h6>
                        @if(empty($topGainers))
                            <div class="text-center text-muted py-2">
                                <small>No gainers data available</small>
                            </div>
                        @else
                            <div class="list-group list-group-flush mb-3">
                                @foreach($topGainers as $gainer)
                                    <div class="list-group-item border-0 px-0 py-1">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="fw-semibold text-dark small">{{ $gainer['symbol'] ?? 'N/A' }}</div>
                                                <small class="text-muted">{{ $gainer['name'] ?? 'N/A' }}</small>
                                            </div>
                                            <div class="text-end">
                                                <div class="fw-semibold small">{{ number_format($gainer['ltp'] ?? 0, 2) }}</div>
                                                <small class="text-success">
                                                    +{{ number_format($gainer['change'] ?? 0, 2) }}
                                                    (+{{ number_format($gainer['change_percent'] ?? 0, 2) }}%)
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <h6 class="text-danger fw-semibold mb-3">
                            <i class="fa fa-arrow-down me-1"></i>
                            Top Losers
                        </h6>
                        @if(empty($topLosers))
                            <div class="text-center text-muted py-2">
                                <small>No losers data available</small>
                            </div>
                        @else
                            <div class="list-group list-group-flush">
                                @foreach($topLosers as $loser)
                                    <div class="list-group-item border-0 px-0 py-1">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="fw-semibold text-dark small">{{ $loser['symbol'] ?? 'N/A' }}</div>
                                                <small class="text-muted">{{ $loser['name'] ?? 'N/A' }}</small>
                                            </div>
                                            <div class="text-end">
                                                <div class="fw-semibold small">{{ number_format($loser['ltp'] ?? 0, 2) }}</div>
                                                <small class="text-danger">
                                                    {{ number_format($loser['change'] ?? 0, 2) }}
                                                    ({{ number_format($loser['change_percent'] ?? 0, 2) }}%)
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Top volume --}}
            <div class="border-top">
                <div class="p-3">
                    <h6 class="text-primary fw-semibold mb-3">
                        <i class="fa fa-bar-chart me-1"></i>
                        Top Volume
                    </h6>
                    @if(empty($topVolume))
                        <div class="text-center text-muted py-2">
                            <small>No volume data available</small>
                        </div>
                    @else
                        <div class="row g-2">
                            @foreach($topVolume as $volume)
                                <div class="col-6">
                                    <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
                                        <div>
                                            <div class="fw-semibold text-dark small">{{ $volume['symbol'] ?? 'N/A' }}</div>
                                            <small class="text-muted">{{ number_format($volume['volume'] ?? 0) }}</small>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-semibold small">{{ number_format($volume['ltp'] ?? 0, 2) }}</div>
                                            <small class="{{ ($volume['change'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ ($volume['change'] ?? 0) >= 0 ? '+' : '' }}{{ number_format($volume['change_percent'] ?? 0, 2) }}%
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <div class="card-footer bg-light border-0 text-center">
        <small class="text-muted">
            <i class="fa fa-clock-o me-1"></i>
            Last updated: {{ now()->format('H:i A') }}
            <span class="ms-2">Auto-refresh: {{ $refreshInterval / 1000 }}s</span>
        </small>
    </div>
</div>

<script>
    document.addEventListener('livewire:load', function () {
        setInterval(function() {
            @this.call('refreshMarketData');
        }, {{ $refreshInterval }});
    });
</script>

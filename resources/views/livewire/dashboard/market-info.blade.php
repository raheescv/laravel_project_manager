<div class="card h-100 shadow-sm border-0 rounded-3 overflow-hidden">
    <div class="card-header bg-gradient bg-info text-white border-0">
        <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-semibold">
                <i class="demo-pli-chart-line me-2"></i>
                Market Information
            </h6>
            <div class="d-flex gap-2">
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" 
                            class="btn {{ $exchange === 'NSE' ? 'btn-light' : 'btn-outline-light' }} btn-sm"
                            wire:click="changeExchange('NSE')">
                        NSE
                    </button>
                    <button type="button" 
                            class="btn {{ $exchange === 'BSE' ? 'btn-light' : 'btn-outline-light' }} btn-sm"
                            wire:click="changeExchange('BSE')">
                        BSE
                    </button>
                </div>
                <button type="button" 
                        class="btn btn-outline-light btn-sm"
                        wire:click="refreshMarketData"
                        @if($loading) disabled @endif>
                    <i class="demo-pli-reload {{ $loading ? 'fa-spin' : '' }}"></i>
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
            <div class="alert alert-danger m-3" role="alert">
                <i class="demo-pli-warning-2 me-2"></i>
                {{ $error }}
                <button type="button" class="btn btn-sm btn-outline-danger ms-2" wire:click="refreshMarketData">
                    Retry
                </button>
            </div>
        @else
            <div class="row g-0">
                <!-- Market Indices -->
                <div class="col-md-6 border-end">
                    <div class="p-3">
                        <h6 class="text-secondary fw-semibold mb-3">
                            <i class="demo-pli-chart-line me-1"></i>
                            Market Indices
                        </h6>
                        @if(empty($indices))
                            <div class="text-center text-muted py-3">
                                <i class="demo-pli-information display-6"></i>
                                <p class="mb-0">No indices data available</p>
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
                                                <div class="fw-semibold">₹{{ number_format($index['ltp'] ?? 0, 2) }}</div>
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

                <!-- Top Gainers/Losers -->
                <div class="col-md-6">
                    <div class="p-3">
                        <!-- Top Gainers -->
                        <h6 class="text-success fw-semibold mb-3">
                            <i class="demo-pli-arrow-up me-1"></i>
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
                                                <div class="fw-semibold small">₹{{ number_format($gainer['ltp'] ?? 0, 2) }}</div>
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

                        <!-- Top Losers -->
                        <h6 class="text-danger fw-semibold mb-3">
                            <i class="demo-pli-arrow-down me-1"></i>
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
                                                <div class="fw-semibold small">₹{{ number_format($loser['ltp'] ?? 0, 2) }}</div>
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

            <!-- Top Volume -->
            <div class="border-top">
                <div class="p-3">
                    <h6 class="text-primary fw-semibold mb-3">
                        <i class="demo-pli-chart-bar me-1"></i>
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
                                            <div class="fw-semibold small">₹{{ number_format($volume['ltp'] ?? 0, 2) }}</div>
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
            <i class="demo-pli-clock me-1"></i>
            Last updated: {{ now()->format('H:i A') }}
            <span class="ms-2">Auto-refresh: {{ $refreshInterval / 1000 }}s</span>
        </small>
    </div>
</div>

<script>
    // Auto-refresh functionality
    document.addEventListener('livewire:load', function () {
        setInterval(function() {
            @this.call('refreshMarketData');
        }, {{ $refreshInterval }});
    });
</script>

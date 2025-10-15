<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('FlatTrade Dashboard') }}
            </h2>
            <div class="d-flex gap-2">
                @if($account_connected)
                    <form method="POST" action="{{ route('flat_trade::disconnect') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to disconnect your FlatTrade account?')">
                            <i class="fa fa-unlink me-1"></i> Disconnect
                        </button>
                    </form>
                @else
                    <a href="{{ route('flat_trade::connect') }}" class="btn btn-primary btn-sm">
                        <i class="fa fa-link me-1"></i> Connect Account
                    </a>
                @endif
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="refreshData()">
                    <i class="fa fa-refresh me-1"></i> Refresh
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Connection Status Alert -->
            @if(!$account_connected)
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="fa fa-exclamation-triangle me-2"></i>
                    <strong>Account Not Connected!</strong> Please connect your FlatTrade account to access trading features.
                    <a href="{{ route('flat_trade::connect') }}" class="alert-link">Connect Now</a>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- Account Status Card -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fa fa-chart-line me-2 text-primary"></i>
                                Account Status
                            </h5>
                            <span class="badge bg-{{ $account_connected ? 'success' : 'danger' }}">
                                {{ $account_status }}
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <h4 class="text-primary mb-1" id="account-balance">₹{{ number_format($account_balance, 2) }}</h4>
                                        <small class="text-muted">Available Balance</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <h4 class="text-info mb-1" id="total-holdings">₹0.00</h4>
                                        <small class="text-muted">Total Holdings</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <h4 class="text-success mb-1" id="day-pnl">₹0.00</h4>
                                        <small class="text-muted">Day P&L</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <h4 class="text-warning mb-1" id="total-pnl">₹0.00</h4>
                                        <small class="text-muted">Total P&L</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fa fa-bolt me-2 text-warning"></i>
                                Quick Actions
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <button type="button" class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#buyOrderModal">
                                        <i class="fa fa-arrow-up me-2"></i>Buy Order
                                    </button>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <button type="button" class="btn btn-danger w-100" data-bs-toggle="modal" data-bs-target="#sellOrderModal">
                                        <i class="fa fa-arrow-down me-2"></i>Sell Order
                                    </button>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <button type="button" class="btn btn-info w-100" data-bs-toggle="modal" data-bs-target="#bracketOrderModal">
                                        <i class="fa fa-brackets me-2"></i>Bracket Order
                                    </button>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <button type="button" class="btn btn-warning w-100" data-bs-toggle="modal" data-bs-target="#tradeCycleModal">
                                        <i class="fa fa-sync me-2"></i>Trade Cycle
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Market Data and Holdings -->
            <div class="row">
                <!-- Market Data -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fa fa-chart-bar me-2 text-info"></i>
                                Market Data
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="symbol-search" class="form-label">Search Symbol</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="symbol-search" placeholder="Enter symbol (e.g., RELIANCE, TCS)">
                                    <button class="btn btn-outline-primary" type="button" onclick="getMarketData()">
                                        <i class="fa fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            <div id="market-data-container">
                                <div class="text-center text-muted">
                                    <i class="fa fa-chart-line fa-3x mb-3"></i>
                                    <p>Search for a symbol to view market data</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Holdings -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fa fa-briefcase me-2 text-success"></i>
                                Holdings
                            </h5>
                        </div>
                        <div class="card-body">
                            <div id="holdings-container">
                                @if($account_connected)
                                    <div class="text-center">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <p class="mt-2 text-muted">Loading holdings...</p>
                                    </div>
                                @else
                                    <div class="text-center text-muted">
                                        <i class="fa fa-briefcase fa-3x mb-3"></i>
                                        <p>Connect your account to view holdings</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fa fa-list me-2 text-secondary"></i>
                                Recent Orders
                            </h5>
                        </div>
                        <div class="card-body">
                            <div id="orders-container">
                                @if($account_connected)
                                    <div class="text-center">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <p class="mt-2 text-muted">Loading orders...</p>
                                    </div>
                                @else
                                    <div class="text-center text-muted">
                                        <i class="fa fa-list fa-3x mb-3"></i>
                                        <p>Connect your account to view recent orders</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-xl-12 mb-4">
                    @livewire('dashboard.market-info')
                </div>
            </div>
        </div>
    </div>

    <!-- Buy Order Modal -->
    <div class="modal fade" id="buyOrderModal" tabindex="-1" aria-labelledby="buyOrderModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="buyOrderModalLabel">
                        <i class="fa fa-arrow-up text-success me-2"></i>Place Buy Order
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="buyOrderForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="buy-symbol" class="form-label">Symbol *</label>
                            <input type="text" class="form-control" id="buy-symbol" name="symbol" required placeholder="e.g., RELIANCE">
                        </div>
                        <div class="mb-3">
                            <label for="buy-quantity" class="form-label">Quantity *</label>
                            <input type="number" class="form-control" id="buy-quantity" name="quantity" required min="1">
                        </div>
                        <div class="mb-3">
                            <label for="buy-order-type" class="form-label">Order Type *</label>
                            <select class="form-select" id="buy-order-type" name="order_type" required>
                                <option value="MARKET">Market</option>
                                <option value="LIMIT">Limit</option>
                            </select>
                        </div>
                        <div class="mb-3" id="buy-price-container" style="display: none;">
                            <label for="buy-price" class="form-label">Price</label>
                            <input type="number" class="form-control" id="buy-price" name="price" step="0.01" min="0.01">
                        </div>
                        <div class="mb-3">
                            <label for="buy-max-price" class="form-label">Max Price (Optional)</label>
                            <input type="number" class="form-control" id="buy-max-price" name="max_price" step="0.01" min="0.01">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fa fa-arrow-up me-1"></i>Place Buy Order
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Sell Order Modal -->
    <div class="modal fade" id="sellOrderModal" tabindex="-1" aria-labelledby="sellOrderModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="sellOrderModalLabel">
                        <i class="fa fa-arrow-down text-danger me-2"></i>Place Sell Order
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="sellOrderForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="sell-symbol" class="form-label">Symbol *</label>
                            <input type="text" class="form-control" id="sell-symbol" name="symbol" required placeholder="e.g., RELIANCE">
                        </div>
                        <div class="mb-3">
                            <label for="sell-quantity" class="form-label">Quantity *</label>
                            <input type="number" class="form-control" id="sell-quantity" name="quantity" required min="1">
                        </div>
                        <div class="mb-3">
                            <label for="sell-order-type" class="form-label">Order Type *</label>
                            <select class="form-select" id="sell-order-type" name="order_type" required>
                                <option value="MARKET">Market</option>
                                <option value="LIMIT">Limit</option>
                            </select>
                        </div>
                        <div class="mb-3" id="sell-price-container" style="display: none;">
                            <label for="sell-price" class="form-label">Price</label>
                            <input type="number" class="form-control" id="sell-price" name="price" step="0.01" min="0.01">
                        </div>
                        <div class="mb-3">
                            <label for="sell-min-price" class="form-label">Min Price (Optional)</label>
                            <input type="number" class="form-control" id="sell-min-price" name="min_price" step="0.01" min="0.01">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fa fa-arrow-down me-1"></i>Place Sell Order
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bracket Order Modal -->
    <div class="modal fade" id="bracketOrderModal" tabindex="-1" aria-labelledby="bracketOrderModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bracketOrderModalLabel">
                        <i class="fa fa-brackets text-info me-2"></i>Place Bracket Order
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="bracketOrderForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="bracket-symbol" class="form-label">Symbol *</label>
                            <input type="text" class="form-control" id="bracket-symbol" name="symbol" required placeholder="e.g., RELIANCE">
                        </div>
                        <div class="mb-3">
                            <label for="bracket-quantity" class="form-label">Quantity *</label>
                            <input type="number" class="form-control" id="bracket-quantity" name="quantity" required min="1">
                        </div>
                        <div class="mb-3">
                            <label for="bracket-transaction-type" class="form-label">Transaction Type *</label>
                            <select class="form-select" id="bracket-transaction-type" name="transaction_type" required>
                                <option value="BUY">Buy</option>
                                <option value="SELL">Sell</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="bracket-entry-price" class="form-label">Entry Price *</label>
                            <input type="number" class="form-control" id="bracket-entry-price" name="entry_price" required step="0.01" min="0.01">
                        </div>
                        <div class="mb-3">
                            <label for="bracket-stop-loss" class="form-label">Stop Loss Price *</label>
                            <input type="number" class="form-control" id="bracket-stop-loss" name="stop_loss_price" required step="0.01" min="0.01">
                        </div>
                        <div class="mb-3">
                            <label for="bracket-target" class="form-label">Target Price *</label>
                            <input type="number" class="form-control" id="bracket-target" name="target_price" required step="0.01" min="0.01">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-info">
                            <i class="fa fa-brackets me-1"></i>Place Bracket Order
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Trade Cycle Modal -->
    <div class="modal fade" id="tradeCycleModal" tabindex="-1" aria-labelledby="tradeCycleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tradeCycleModalLabel">
                        <i class="fa fa-sync text-warning me-2"></i>Execute Trade Cycle
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="tradeCycleForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="cycle-symbol" class="form-label">Symbol *</label>
                            <input type="text" class="form-control" id="cycle-symbol" name="symbol" required placeholder="e.g., RELIANCE">
                        </div>
                        <div class="mb-3">
                            <label for="cycle-quantity" class="form-label">Quantity *</label>
                            <input type="number" class="form-control" id="cycle-quantity" name="quantity" required min="1">
                        </div>
                        <div class="mb-3">
                            <label for="cycle-entry-price" class="form-label">Entry Price *</label>
                            <input type="number" class="form-control" id="cycle-entry-price" name="entry_price" required step="0.01" min="0.01">
                        </div>
                        <div class="mb-3">
                            <label for="cycle-stop-loss" class="form-label">Stop Loss % (Default: 5%)</label>
                            <input type="number" class="form-control" id="cycle-stop-loss" name="stop_loss_percent" step="0.1" min="1" max="50" value="5">
                        </div>
                        <div class="mb-3">
                            <label for="cycle-target" class="form-label">Target % (Default: 10%)</label>
                            <input type="number" class="form-control" id="cycle-target" name="target_percent" step="0.1" min="1" max="100" value="10">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fa fa-sync me-1"></i>Execute Trade Cycle
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('styles')
    <style>
        .flat-trade-card {
            border-left: 4px solid #007bff;
        }

        .market-data-item {
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }

        .market-data-item:last-child {
            border-bottom: none;
        }

        .price-up {
            color: #28a745;
        }

        .price-down {
            color: #dc3545;
        }

        .order-status-pending {
            color: #ffc107;
        }

        .order-status-completed {
            color: #28a745;
        }

        .order-status-cancelled {
            color: #dc3545;
        }

        .holding-item {
            padding: 0.75rem 0;
            border-bottom: 1px solid #eee;
        }

        .holding-item:last-child {
            border-bottom: none;
        }

        .pnl-positive {
            color: #28a745;
        }

        .pnl-negative {
            color: #dc3545;
        }
    </style>
    @endpush

    @push('scripts')
    <script>
        let marketDataInterval;
        let accountDataInterval;

        $(document).ready(function() {
            // Initialize order type handlers
            $('#buy-order-type, #sell-order-type').change(function() {
                const isLimit = $(this).val() === 'LIMIT';
                const container = $(this).attr('id').includes('buy') ? '#buy-price-container' : '#sell-price-container';
                $(container).toggle(isLimit);
            });

            // Load initial data if connected
            @if($account_connected)
                loadAccountData();
                loadHoldings();
                loadOrders();
            @endif

            // Set up auto-refresh
            if ({{ $account_connected ? 'true' : 'false' }}) {
                accountDataInterval = setInterval(loadAccountData, 30000); // Every 30 seconds
            }
        });

        function refreshData() {
            if ({{ $account_connected ? 'true' : 'false' }}) {
                loadAccountData();
                loadHoldings();
                loadOrders();
                toastr.success('Data refreshed successfully');
            } else {
                toastr.warning('Please connect your account first');
            }
        }

        function loadAccountData() {
            $.get('{{ route("flat_trade::balance") }}')
                .done(function(response) {
                    if (response.success) {
                        
                        // Update account balance
                        $('#account-balance').text('₹' + parseFloat(response.balance.cash || 0).toLocaleString('en-IN', {minimumFractionDigits: 2}));
                        
                        // Update holdings value (if available)
                        $('#total-holdings').text('₹' + parseFloat(response.balance.total_holdings || 0).toLocaleString('en-IN', {minimumFractionDigits: 2}));
                        
                        // Update P&L values (if available)
                        $('#day-pnl').text('₹' + parseFloat(response.balance.day_pnl || 0).toLocaleString('en-IN', {minimumFractionDigits: 2}));
                        $('#total-pnl').text('₹' + parseFloat(response.balance.total_pnl || 0).toLocaleString('en-IN', {minimumFractionDigits: 2}));

                        // Add color classes for P&L
                        const dayPnl = parseFloat(response.balance.day_pnl || 0);
                        const totalPnl = parseFloat(response.balance.total_pnl || 0);

                        $('#day-pnl').removeClass('pnl-positive pnl-negative').addClass(dayPnl >= 0 ? 'pnl-positive' : 'pnl-negative');
                        $('#total-pnl').removeClass('pnl-positive pnl-negative').addClass(totalPnl >= 0 ? 'pnl-positive' : 'pnl-negative');
                        
                        // Update additional balance info if available
                        if (response.balance.payin !== undefined) {
                            $('#payin-amount').text('₹' + parseFloat(response.balance.payin || 0).toLocaleString('en-IN', {minimumFractionDigits: 2}));
                        }
                        if (response.balance.payout !== undefined) {
                            $('#payout-amount').text('₹' + parseFloat(response.balance.payout || 0).toLocaleString('en-IN', {minimumFractionDigits: 2}));
                        }
                    }
                })
                .fail(function() {
                    console.error('Failed to load account data');
                });
        }

        function loadHoldings() {
            $.get('{{ route("flat_trade::holdings") }}')
                .done(function(response) {
                    if (response.success && response.holdings.length > 0) {
                        let html = '<div class="table-responsive"><table class="table table-sm">';
                        html += '<thead><tr><th>Symbol</th><th>Quantity</th><th>Avg Price</th><th>Current Price</th><th>P&L</th></tr></thead><tbody>';
                        response.holdings.forEach(function(holding) {
                            // Extract data from the sample data structure
                            const symbol = holding.exch_tsym && holding.exch_tsym[0] ? holding.exch_tsym[0].tsym.replace('-EQ', '') : 'N/A';
                            const quantity = parseInt(holding.holdqty || 0);
                            const avgPrice = parseFloat(holding.upldprc || 0);
                            const currentPrice = parseFloat(holding.upldprc || 0); // Using upldprc as current price for sample
                            const totalValue = parseFloat(holding.sell_amt || 0);
                            const pnl = totalValue - (quantity * avgPrice);
                            const pnlClass = pnl >= 0 ? 'pnl-positive' : 'pnl-negative';

                            html += '<tr>';
                            html += '<td><strong>' + symbol + '</strong></td>';
                            html += '<td>' + quantity + '</td>';
                            html += '<td>₹' + avgPrice.toFixed(2) + '</td>';
                            html += '<td>₹' + currentPrice.toFixed(2) + '</td>';
                            html += '<td class="' + pnlClass + '">₹' + pnl.toFixed(2) + '</td>';
                            html += '</tr>';
                        });

                            html += '</tbody></table></div>';
                            $('#holdings-container').html(html);
                        } else {
                            $('#holdings-container').html('<div class="text-center text-muted"><i class="fa fa-briefcase fa-3x mb-3"></i><p>No holdings found</p></div>');
                        }
                })
                .fail(function() {
                    $('#holdings-container').html('<div class="text-center text-danger"><i class="fa fa-exclamation-triangle fa-3x mb-3"></i><p>Failed to load holdings</p></div>');
                });
        }

        function loadOrders() {
            // Show loading state
            $('#orders-container').html('<div class="text-center"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Loading orders...</p></div>');

            // Fetch order book and trade book from API
            Promise.all([
                $.get('{{ route("flat_trade::order_book") }}'),
                $.get('{{ route("flat_trade::trade_book") }}')
            ])
            .then(function(responses) {
                const orderBookResponse = responses[0];
                const tradeBookResponse = responses[1];
                let allOrders = [];
                
                // Process order book (pending/active orders)
                if (orderBookResponse.success && orderBookResponse.order_book && orderBookResponse.order_book) {
                    orderBookResponse.order_book.forEach(function(order) {
                        allOrders.push({
                            order_id: order.norenordno || order.orderno || 'N/A',
                            symbol: order.tsym ? order.tsym.replace('-EQ', '') : 'N/A',
                            transaction_type: order.trantype || 'BUY',
                            quantity: parseInt(order.qty || 0),
                            price: parseFloat(order.prc || 0),
                            order_type: order.prctyp || 'MARKET',
                            status: order.status || 'PENDING',
                            timestamp: order.reqtime || order.ordtime || 'N/A',
                            exchange: order.exch || 'NSE',
                            type: 'order'
                        });
                    });
                }
                
                // Process trade book (completed trades)
                if (tradeBookResponse.success && tradeBookResponse.trade_book && tradeBookResponse.trade_book) {
                    tradeBookResponse.trade_book.forEach(function(trade) {
                        allOrders.push({
                            order_id: trade.norenordno || trade.orderno || 'N/A',
                            symbol: trade.tsym ? trade.tsym.replace('-EQ', '') : 'N/A',
                            transaction_type: trade.trantype || 'BUY',
                            quantity: parseInt(trade.qty || 0),
                            price: parseFloat(trade.prc || 0),
                            order_type: trade.prctyp || 'MARKET',
                            status: 'COMPLETE',
                            timestamp: trade.fltm || trade.ordtime || 'N/A',
                            exchange: trade.exch || 'NSE',
                            type: 'trade'
                        });
                    });
                }
                
                // Sort orders by timestamp (newest first)
                allOrders.sort(function(a, b) {
                    return new Date(b.timestamp) - new Date(a.timestamp);
                });
                
                // Display orders
                if (allOrders.length > 0) {
                    let html = '<div class="table-responsive"><table class="table table-sm">';
                    html += '<thead><tr><th>Order ID</th><th>Symbol</th><th>Type</th><th>Qty</th><th>Price</th><th>Status</th><th>Time</th></tr></thead><tbody>';
                    
                    allOrders.forEach(function(order) {
                        const statusClass = getOrderStatusClass(order.status);
                        const typeClass = order.transaction_type === 'B' ? 'text-success' : 'text-danger';
                        const typeIcon = order.transaction_type === 'B' ? 'fa-arrow-up' : 'fa-arrow-down';
                        const typeText = order.transaction_type === 'B' ? 'BUY' : 'SELL';
                        
                        html += '<tr>';
                        html += '<td><small class="text-muted">' + order.order_id + '</small></td>';
                        html += '<td><strong>' + order.symbol + '</strong></td>';
                        html += '<td><i class="fa ' + typeIcon + ' me-1 ' + typeClass + '"></i>' + typeText + '</td>';
                        html += '<td>' + order.quantity + '</td>';
                        html += '<td>₹' + parseFloat(order.price).toFixed(2) + '</td>';
                        html += '<td><span class="badge ' + statusClass + '">' + order.status + '</span></td>';
                        html += '<td><small class="text-muted">' + order.timestamp + '</small></td>';
                        html += '</tr>';
                    });
                    
                    html += '</tbody></table></div>';
                    $('#orders-container').html(html);
                } else {
                    $('#orders-container').html('<div class="text-center text-muted"><i class="fa fa-list fa-3x mb-3"></i><p>No orders found</p></div>');
                }
            })
            .catch(function(error) {
                console.error('Failed to load orders:', error);
                $('#orders-container').html('<div class="text-center text-danger"><i class="fa fa-exclamation-triangle fa-3x mb-3"></i><p>Failed to load orders</p></div>');
            });
        }

        function getOrderStatusClass(status) {
            switch(status.toUpperCase()) {
                case 'COMPLETE':
                    return 'bg-success';
                case 'PENDING':
                    return 'bg-warning';
                case 'CANCELLED':
                    return 'bg-danger';
                case 'REJECTED':
                    return 'bg-danger';
                case 'PARTIAL':
                    return 'bg-info';
                default:
                    return 'bg-secondary';
            }
        }

        function getMarketData() {
            const symbol = $('#symbol-search').val().trim().toUpperCase();
            if (!symbol) {
                toastr.error('Please enter a symbol');
                return;
            }

            $('#market-data-container').html('<div class="text-center"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Loading market data...</p></div>');

            $.get('{{ route("flat_trade::market_data") }}', { symbol: symbol })
                .done(function(response) {
                    if (response.success) {
                        const data = response.market_data;
                        const analysis = response.analysis;

                        let html = '<div class="market-data-item">';
                        html += '<h6 class="mb-3">' + symbol + '</h6>';
                        html += '<div class="row">';
                        html += '<div class="col-6"><strong>Current Price:</strong></div>';
                        html += '<div class="col-6">₹' + parseFloat(data.last_price || 0).toFixed(2) + '</div>';
                        html += '</div>';
                        html += '<div class="row">';
                        html += '<div class="col-6"><strong>Change:</strong></div>';
                        html += '<div class="col-6 ' + (parseFloat(data.change_percent || 0) >= 0 ? 'price-up' : 'price-down') + '">';
                        html += (parseFloat(data.change_percent || 0) >= 0 ? '+' : '') + parseFloat(data.change_percent || 0).toFixed(2) + '%';
                        html += '</div>';
                        html += '</div>';
                        html += '<div class="row">';
                        html += '<div class="col-6"><strong>Volume:</strong></div>';
                        html += '<div class="col-6">' + parseInt(data.volume || 0).toLocaleString() + '</div>';
                        html += '</div>';
                        html += '<div class="row">';
                        html += '<div class="col-6"><strong>Bid:</strong></div>';
                        html += '<div class="col-6">₹' + parseFloat(analysis.bid_price || 0).toFixed(2) + '</div>';
                        html += '</div>';
                        html += '<div class="row">';
                        html += '<div class="col-6"><strong>Ask:</strong></div>';
                        html += '<div class="col-6">₹' + parseFloat(analysis.ask_price || 0).toFixed(2) + '</div>';
                        html += '</div>';
                        html += '<div class="row">';
                        html += '<div class="col-6"><strong>Spread:</strong></div>';
                        html += '<div class="col-6">₹' + parseFloat(analysis.spread || 0).toFixed(2) + '</div>';
                        html += '</div>';
                        html += '</div>';

                        $('#market-data-container').html(html);
                    } else {
                        $('#market-data-container').html('<div class="text-center text-danger"><i class="fa fa-exclamation-triangle fa-3x mb-3"></i><p>' + (response.message || 'Failed to load market data') + '</p></div>');
                    }
                })
                .fail(function() {
                    $('#market-data-container').html('<div class="text-center text-danger"><i class="fa fa-exclamation-triangle fa-3x mb-3"></i><p>Failed to load market data</p></div>');
                });
        }

        // Form submissions
        $('#buyOrderForm').submit(function(e) {
            e.preventDefault();
            const formData = $(this).serialize();

            $.post('{{ route("flat_trade::buy") }}', formData)
                .done(function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        $('#buyOrderModal').modal('hide');
                        $(this)[0].reset();
                        loadAccountData();
                        loadHoldings();
                    } else {
                        toastr.error(response.message);
                    }
                })
                .fail(function() {
                    toastr.error('Failed to place buy order');
                });
        });

        $('#sellOrderForm').submit(function(e) {
            e.preventDefault();
            const formData = $(this).serialize();

            $.post('{{ route("flat_trade::sell") }}', formData)
                .done(function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        $('#sellOrderModal').modal('hide');
                        $(this)[0].reset();
                        loadAccountData();
                        loadHoldings();
                    } else {
                        toastr.error(response.message);
                    }
                })
                .fail(function() {
                    toastr.error('Failed to place sell order');
                });
        });

        $('#bracketOrderForm').submit(function(e) {
            e.preventDefault();
            const formData = $(this).serialize();

            $.post('{{ route("flat_trade::bracket_order") }}', formData)
                .done(function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        $('#bracketOrderModal').modal('hide');
                        $(this)[0].reset();
                        loadAccountData();
                        loadHoldings();
                    } else {
                        toastr.error(response.message);
                    }
                })
                .fail(function() {
                    toastr.error('Failed to place bracket order');
                });
        });

        $('#tradeCycleForm').submit(function(e) {
            e.preventDefault();
            const formData = $(this).serialize();

            $.post('{{ route("flat_trade::trade_cycle") }}', formData)
                .done(function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        $('#tradeCycleModal').modal('hide');
                        $(this)[0].reset();
                        loadAccountData();
                        loadHoldings();
                    } else {
                        toastr.error(response.message);
                    }
                })
                .fail(function() {
                    toastr.error('Failed to execute trade cycle');
                });
        });

        // Clean up intervals on page unload
        $(window).on('beforeunload', function() {
            if (marketDataInterval) clearInterval(marketDataInterval);
            if (accountDataInterval) clearInterval(accountDataInterval);
        });
    </script>
    @endpush
</x-app-layout>

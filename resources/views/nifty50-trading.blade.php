<x-app-layout>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fa fa-chart-line"></i> Nifty 50 Real Trading
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-sm btn-primary" onclick="refreshMarketData()">
                            <i class="fa fa-sync"></i> Refresh
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Market Status -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-info"><i class="fa fa-clock"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Market Status</span>
                                    <span class="info-box-number" id="market-status">Loading...</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-success"><i class="fa fa-chart-bar"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Best Stocks Found</span>
                                    <span class="info-box-number" id="stocks-count">0</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning"><i class="fa fa-exclamation-triangle"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Risk Level</span>
                                    <span class="info-box-number" id="risk-level">Moderate</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-primary"><i class="fa fa-wallet"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Available Balance</span>
                                    <span class="info-box-number" id="available-balance">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Trading Configuration -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Trading Configuration</h4>
                                </div>
                                <div class="card-body">
                                    <form id="trading-config-form">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="max-stocks">Max Stocks to Trade</label>
                                                    <select class="form-control" id="max-stocks" name="max_stocks">
                                                        <option value="3">3 Stocks</option>
                                                        <option value="5" selected>5 Stocks</option>
                                                        <option value="10">10 Stocks</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="quantity">Quantity per Stock</label>
                                                    <input type="number" class="form-control" id="quantity" name="quantity" value="1" min="1" max="100">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="order-type">Order Type</label>
                                                    <select class="form-control" id="order-type" name="order_type">
                                                        <option value="market" selected>Market Order</option>
                                                        <option value="limit">Limit Order</option>
                                                        <option value="bracket">Bracket Order</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="product">Product Type</label>
                                                    <select class="form-control" id="product" name="product">
                                                        <option value="C" selected>CNC</option>
                                                        <option value="H">Holding</option>
                                                        <option value="B">Bracket</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="min-profit">Min Profit %</label>
                                                    <input type="number" class="form-control" id="min-profit" name="min_profit_percent" value="2.0" step="0.1" min="0.1" max="50">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="max-loss">Max Loss %</label>
                                                    <input type="number" class="form-control" id="max-loss" name="max_loss_percent" value="3.0" step="0.1" min="0.1" max="50">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="min-change">Min Change %</label>
                                                    <input type="number" class="form-control" id="min-change" name="min_change_percent" value="1.0" step="0.1" min="0.1" max="20">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>&nbsp;</label>
                                                    <button type="button" class="btn btn-success btn-block" onclick="loadBestStocks()">
                                                        <i class="fa fa-search"></i> Find Best Stocks
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Best Stocks Table -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Best Performing Nifty 50 Stocks</h4>
                                </div>
                                <div class="card-body">
                                    <div id="stocks-loading" class="text-center" style="display: none;">
                                        <i class="fa fa-spinner fa-spin fa-2x"></i>
                                        <p>Loading best stocks...</p>
                                    </div>
                                    <div id="stocks-table-container">
                                        <table class="table table-striped" id="stocks-table">
                                            <thead>
                                                <tr>
                                                    <th>Symbol</th>
                                                    <th>LTP</th>
                                                    <th>Change %</th>
                                                    <th>Volume</th>
                                                    <th>High</th>
                                                    <th>Low</th>
                                                    <th>Score</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="stocks-tbody">
                                                <tr>
                                                    <td colspan="8" class="text-center">Click "Find Best Stocks" to load data</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Trading Actions -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Trading Actions</h4>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <button type="button" class="btn btn-warning btn-lg btn-block" onclick="executeDryRun()">
                                                <i class="fa fa-play-circle"></i> Dry Run (Test Mode)
                                            </button>
                                            <small class="text-muted">Test the trading logic without placing actual orders</small>
                                        </div>
                                        <div class="col-md-6">
                                            <button type="button" class="btn btn-danger btn-lg btn-block" onclick="executeRealTrading()">
                                                <i class="fa fa-rocket"></i> Execute Real Trading
                                            </button>
                                            <small class="text-muted">Place actual orders with real money</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Results Section -->
                    <div class="row mt-4" id="results-section" style="display: none;">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Trading Results</h4>
                                </div>
                                <div class="card-body">
                                    <div id="results-content"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Trading</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fa fa-exclamation-triangle"></i>
                    <strong>Warning:</strong> This will place real orders with actual money. Are you sure you want to proceed?
                </div>
                <div id="confirm-details"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirm-execute">Execute Trading</button>
            </div>
        </div>
    </div>
</div>

    @push('scripts')
<script>
let selectedStocks = [];
let marketStatus = 'unknown';

// Load market status on page load
$(document).ready(function() {
    loadMarketStatus();
    loadUserPositions();
});

function loadMarketStatus() {
    $.get('/flat_trade/nifty50/market-status')
        .done(function(response) {
            if (response.success) {
                marketStatus = response.data.status || 'unknown';
                $('#market-status').text(marketStatus.toUpperCase());
            }
        })
        .fail(function() {
            $('#market-status').text('ERROR');
        });
}

function loadUserPositions() {
    $.get('/flat_trade/nifty50/positions')
        .done(function(response) {
            if (response.success) {
                // Update balance info if available
                if (response.data.balance) {
                    $('#available-balance').text('' + response.data.balance);
                }
            }
        })
        .fail(function() {
            $('#available-balance').text('ERROR');
        });
}

function loadBestStocks() {
    const formData = $('#trading-config-form').serialize();

    $('#stocks-loading').show();
    $('#stocks-table-container').hide();

    $.get('/flat_trade/nifty50/best-stocks?' + formData)
        .done(function(response) {
            if (response.success) {
                displayStocks(response.data);
                $('#stocks-count').text(response.count);
            } else {
                showAlert('Error loading stocks: ' + response.message, 'error');
            }
        })
        .fail(function() {
            showAlert('Failed to load stocks', 'error');
        })
        .always(function() {
            $('#stocks-loading').hide();
            $('#stocks-table-container').show();
        });
}

function displayStocks(stocks) {
    const tbody = $('#stocks-tbody');
    tbody.empty();

    if (stocks.length === 0) {
        tbody.append('<tr><td colspan="8" class="text-center">No suitable stocks found</td></tr>');
        return;
    }

    stocks.forEach(function(stock, index) {
        const changeClass = stock.change_percent >= 0 ? 'text-success' : 'text-danger';
        const changeIcon = stock.change_percent >= 0 ? 'fa-arrow-up' : 'fa-arrow-down';

        const row = `
            <tr>
                <td><strong>${stock.symbol}</strong></td>
                <td>${stock.ltp.toFixed(2)}</td>
                <td class="${changeClass}">
                    <i class="fa ${changeIcon}"></i> ${stock.change_percent.toFixed(2)}%
                </td>
                <td>${formatNumber(stock.volume)}</td>
                <td>${stock.high.toFixed(2)}</td>
                <td>${stock.low.toFixed(2)}</td>
                <td>
                    <span class="badge badge-info">${stock.suitability_score.toFixed(1)}</span>
                </td>
                <td>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input stock-checkbox"
                               value="${stock.symbol}" data-stock='${JSON.stringify(stock)}'>
                        <label class="form-check-label">Select</label>
                    </div>
                </td>
            </tr>
        `;
        tbody.append(row);
    });

    // Update selected stocks when checkboxes change
    $('.stock-checkbox').change(function() {
        updateSelectedStocks();
    });
}

function updateSelectedStocks() {
    selectedStocks = [];
    $('.stock-checkbox:checked').each(function() {
        const stockData = JSON.parse($(this).data('stock'));
        const quantity = parseInt($('#quantity').val());
        selectedStocks.push({
            symbol: stockData.symbol,
            quantity: quantity,
            ltp: stockData.ltp,
            change_percent: stockData.change_percent
        });
    });
}

function executeDryRun() {
    if (selectedStocks.length === 0) {
        showAlert('Please select at least one stock', 'warning');
        return;
    }

    const formData = $('#trading-config-form').serializeArray();
    const config = {};
    formData.forEach(function(item) {
        config[item.name] = item.value;
    });

    const requestData = {
        stocks: selectedStocks,
        ...config,
        confirm_trading: true,
        dry_run: true
    };

    showAlert('Executing dry run...', 'info');

    $.post('/flat_trade/nifty50/execute-trading', requestData)
        .done(function(response) {
            if (response.success) {
                displayResults(response.data, true);
            } else {
                showAlert('Dry run failed: ' + response.message, 'error');
            }
        })
        .fail(function() {
            showAlert('Dry run failed', 'error');
        });
}

function executeRealTrading() {
    if (selectedStocks.length === 0) {
        showAlert('Please select at least one stock', 'warning');
        return;
    }

    if (marketStatus !== 'open') {
        showAlert('Market is currently closed. Trading is not allowed.', 'warning');
        return;
    }

    // Show confirmation modal
    showConfirmationModal();
}

function showConfirmationModal() {
    const totalInvestment = selectedStocks.reduce((sum, stock) => sum + (stock.ltp * stock.quantity), 0);

    const details = `
        <div class="row">
            <div class="col-md-6">
                <strong>Selected Stocks:</strong> ${selectedStocks.length}<br>
                <strong>Total Investment:</strong> ${totalInvestment.toFixed(2)}<br>
                <strong>Order Type:</strong> ${$('#order-type').val()}<br>
                <strong>Product:</strong> ${$('#product').val()}
            </div>
            <div class="col-md-6">
                <strong>Min Profit:</strong> ${$('#min-profit').val()}%<br>
                <strong>Max Loss:</strong> ${$('#max-loss').val()}%<br>
                <strong>Quantity per Stock:</strong> ${$('#quantity').val()}
            </div>
        </div>
        <hr>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Symbol</th>
                        <th>Quantity</th>
                        <th>LTP</th>
                        <th>Investment</th>
                    </tr>
                </thead>
                <tbody>
                    ${selectedStocks.map(stock => `
                        <tr>
                            <td>${stock.symbol}</td>
                            <td>${stock.quantity}</td>
                            <td>${stock.ltp.toFixed(2)}</td>
                            <td>${(stock.ltp * stock.quantity).toFixed(2)}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;

    $('#confirm-details').html(details);
    $('#confirmModal').modal('show');

    $('#confirm-execute').off('click').on('click', function() {
        $('#confirmModal').modal('hide');
        executeRealTradingConfirmed();
    });
}

function executeRealTradingConfirmed() {
    const formData = $('#trading-config-form').serializeArray();
    const config = {};
    formData.forEach(function(item) {
        config[item.name] = item.value;
    });

    const requestData = {
        stocks: selectedStocks,
        ...config,
        confirm_trading: true
    };

    showAlert('Executing real trading orders...', 'info');

    $.post('/flat_trade/nifty50/execute-trading', requestData)
        .done(function(response) {
            if (response.success) {
                displayResults(response.data, false);
                showAlert('Trading orders executed successfully!', 'success');
            } else {
                showAlert('Trading failed: ' + response.message, 'error');
            }
        })
        .fail(function() {
            showAlert('Trading failed', 'error');
        });
}

function displayResults(data, isDryRun) {
    const resultsHtml = `
        <div class="alert ${isDryRun ? 'alert-info' : 'alert-success'}">
            <h5><i class="fa fa-chart-line"></i> ${isDryRun ? 'Dry Run Results' : 'Trading Results'}</h5>
        </div>

        <div class="row">
            <div class="col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-primary"><i class="fa fa-list"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Stocks</span>
                        <span class="info-box-number">${data.summary.total_stocks}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-success"><i class="fa fa-check"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Successful</span>
                        <span class="info-box-number">${data.summary.successful_orders}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-danger"><i class="fa fa-times"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Failed</span>
                        <span class="info-box-number">${data.summary.failed_orders}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-warning"><i class="fa fa-rupee-sign"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Investment</span>
                        <span class="info-box-number">${data.summary.total_investment.toFixed(2)}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Symbol</th>
                        <th>Status</th>
                        <th>Order ID</th>
                        <th>Entry Price</th>
                        <th>Stop Loss</th>
                        <th>Target</th>
                        <th>Investment</th>
                    </tr>
                </thead>
                <tbody>
                    ${data.results.map(result => `
                        <tr class="${result.success ? 'table-success' : 'table-danger'}">
                            <td><strong>${result.symbol}</strong></td>
                            <td>
                                <span class="badge ${result.success ? 'badge-success' : 'badge-danger'}">
                                    ${result.success ? 'SUCCESS' : 'FAILED'}
                                </span>
                            </td>
                            <td>${result.order_id || 'N/A'}</td>
                            <td>${result.entry_price ? result.entry_price.toFixed(2) : 'N/A'}</td>
                            <td>${result.stop_loss ? result.stop_loss.toFixed(2) : 'N/A'}</td>
                            <td>${result.target ? result.target.toFixed(2) : 'N/A'}</td>
                            <td>${result.investment_amount ? result.investment_amount.toFixed(2) : '0.00'}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;

    $('#results-content').html(resultsHtml);
    $('#results-section').show();
}

function refreshMarketData() {
    loadMarketStatus();
    loadUserPositions();
    if (selectedStocks.length > 0) {
        loadBestStocks();
    }
}

function formatNumber(num) {
    if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M';
    } else if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'K';
    }
    return num.toString();
}

function showAlert(message, type) {
    const alertClass = type === 'error' ? 'alert-danger' :
                     type === 'warning' ? 'alert-warning' :
                     type === 'success' ? 'alert-success' : 'alert-info';

    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    `;

    // Remove existing alerts
    $('.alert').remove();

    // Add new alert at the top
    $('.card-body').prepend(alertHtml);

    // Auto-dismiss after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);
}
</script>
    @endpush
    </x-app-layout>

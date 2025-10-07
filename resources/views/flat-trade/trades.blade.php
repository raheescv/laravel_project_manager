<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('FlatTrade - Trade History') }}
            </h2>
            <div class="d-flex gap-2">
                <a href="{{ route('flat_trade::dashboard') }}" class="btn btn-outline-primary btn-sm">
                    <i class="fa fa-arrow-left me-1"></i> Back to Dashboard
                </a>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="refreshTrades()">
                    <i class="fa fa-refresh me-1"></i> Refresh
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Filters -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fa fa-filter me-2 text-primary"></i>
                                Filters
                            </h5>
                        </div>
                        <div class="card-body">
                            <form id="tradeFilters" class="row g-3">
                                <div class="col-md-3">
                                    <label for="filter-symbol" class="form-label">Symbol</label>
                                    <input type="text" class="form-control" id="filter-symbol" placeholder="e.g., RELIANCE">
                                </div>
                                <div class="col-md-3">
                                    <label for="filter-date-from" class="form-label">Date From</label>
                                    <input type="date" class="form-control" id="filter-date-from">
                                </div>
                                <div class="col-md-3">
                                    <label for="filter-date-to" class="form-label">Date To</label>
                                    <input type="date" class="form-control" id="filter-date-to">
                                </div>
                                <div class="col-md-3">
                                    <label for="filter-status" class="form-label">Status</label>
                                    <select class="form-select" id="filter-status">
                                        <option value="">All Status</option>
                                        <option value="COMPLETE">Complete</option>
                                        <option value="PENDING">Pending</option>
                                        <option value="CANCELLED">Cancelled</option>
                                        <option value="REJECTED">Rejected</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <button type="button" class="btn btn-primary" onclick="applyFilters()">
                                        <i class="fa fa-search me-1"></i> Apply Filters
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="clearFilters()">
                                        <i class="fa fa-times me-1"></i> Clear
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Trade History Table -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fa fa-history me-2 text-secondary"></i>
                                Trade History
                            </h5>
                            <div class="d-flex align-items-center">
                                <span class="badge bg-info me-2" id="total-trades">0 trades</span>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-primary" onclick="exportTrades('csv')">
                                        <i class="fa fa-download me-1"></i> CSV
                                    </button>
                                    <button type="button" class="btn btn-outline-primary" onclick="exportTrades('pdf')">
                                        <i class="fa fa-file-pdf me-1"></i> PDF
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="trades-container">
                                <div class="text-center">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="mt-2 text-muted">Loading trade history...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Trade Summary -->
            <div class="row mt-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body text-center">
                            <i class="fa fa-chart-line fa-2x mb-2"></i>
                            <h4 id="total-trades-count">0</h4>
                            <small>Total Trades</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <i class="fa fa-arrow-up fa-2x mb-2"></i>
                            <h4 id="successful-trades">0</h4>
                            <small>Successful</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body text-center">
                            <i class="fa fa-clock fa-2x mb-2"></i>
                            <h4 id="pending-trades">0</h4>
                            <small>Pending</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body text-center">
                            <i class="fa fa-times fa-2x mb-2"></i>
                            <h4 id="failed-trades">0</h4>
                            <small>Failed</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Trade Details Modal -->
    <div class="modal fade" id="tradeDetailsModal" tabindex="-1" aria-labelledby="tradeDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tradeDetailsModalLabel">
                        <i class="fa fa-info-circle text-info me-2"></i>Trade Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="trade-details-content">
                    <!-- Trade details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
    <style>
        .trade-table {
            font-size: 0.9rem;
        }
        
        .trade-table th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
        }
        
        .trade-status-complete {
            color: #28a745;
        }
        
        .trade-status-pending {
            color: #ffc107;
        }
        
        .trade-status-cancelled {
            color: #dc3545;
        }
        
        .trade-status-rejected {
            color: #6c757d;
        }
        
        .trade-type-buy {
            color: #28a745;
            font-weight: 600;
        }
        
        .trade-type-sell {
            color: #dc3545;
            font-weight: 600;
        }
        
        .pnl-positive {
            color: #28a745;
            font-weight: 600;
        }
        
        .pnl-negative {
            color: #dc3545;
            font-weight: 600;
        }
        
        .trade-row {
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .trade-row:hover {
            background-color: #f8f9fa;
        }
        
        .summary-card {
            transition: transform 0.2s ease-in-out;
        }
        
        .summary-card:hover {
            transform: translateY(-2px);
        }
    </style>
    @endpush

    @push('scripts')
    <script>
        let currentFilters = {};
        let allTrades = [];

        $(document).ready(function() {
            // Set default date range (last 30 days)
            const today = new Date();
            const thirtyDaysAgo = new Date(today.getTime() - (30 * 24 * 60 * 60 * 1000));
            
            $('#filter-date-to').val(today.toISOString().split('T')[0]);
            $('#filter-date-from').val(thirtyDaysAgo.toISOString().split('T')[0]);
            
            // Load initial data
            loadTrades();
        });

        function loadTrades() {
            $('#trades-container').html('<div class="text-center"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Loading trade history...</p></div>');
            
            // This would be implemented when the API endpoint is available
            // For now, show a placeholder
            setTimeout(function() {
                $('#trades-container').html(`
                    <div class="text-center text-muted">
                        <i class="fa fa-history fa-3x mb-3"></i>
                        <h5>No Trade History Available</h5>
                        <p>Trade history will be displayed here once you start trading through the FlatTrade integration.</p>
                        <a href="{{ route('flat_trade::dashboard') }}" class="btn btn-primary">
                            <i class="fa fa-arrow-right me-1"></i> Go to Dashboard
                        </a>
                    </div>
                `);
                updateSummaryStats([]);
            }, 1000);
        }

        function applyFilters() {
            currentFilters = {
                symbol: $('#filter-symbol').val(),
                date_from: $('#filter-date-from').val(),
                date_to: $('#filter-date-to').val(),
                status: $('#filter-status').val()
            };
            
            loadTrades();
        }

        function clearFilters() {
            $('#tradeFilters')[0].reset();
            currentFilters = {};
            loadTrades();
        }

        function refreshTrades() {
            loadTrades();
            toastr.success('Trade history refreshed');
        }

        function updateSummaryStats(trades) {
            const total = trades.length;
            const successful = trades.filter(t => t.status === 'COMPLETE').length;
            const pending = trades.filter(t => t.status === 'PENDING').length;
            const failed = trades.filter(t => ['CANCELLED', 'REJECTED'].includes(t.status)).length;
            
            $('#total-trades-count').text(total);
            $('#successful-trades').text(successful);
            $('#pending-trades').text(pending);
            $('#failed-trades').text(failed);
            $('#total-trades').text(total + ' trades');
        }

        function showTradeDetails(tradeId) {
            // This would load detailed trade information
            $('#trade-details-content').html('<div class="text-center"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Loading trade details...</p></div>');
            $('#tradeDetailsModal').modal('show');
            
            // Placeholder for trade details
            setTimeout(function() {
                $('#trade-details-content').html(`
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Trade Information</h6>
                            <table class="table table-sm">
                                <tr><td><strong>Order ID:</strong></td><td>${tradeId}</td></tr>
                                <tr><td><strong>Symbol:</strong></td><td>RELIANCE</td></tr>
                                <tr><td><strong>Type:</strong></td><td>BUY</td></tr>
                                <tr><td><strong>Quantity:</strong></td><td>10</td></tr>
                                <tr><td><strong>Price:</strong></td><td>₹2,500.00</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Status & Timing</h6>
                            <table class="table table-sm">
                                <tr><td><strong>Status:</strong></td><td><span class="badge bg-success">COMPLETE</span></td></tr>
                                <tr><td><strong>Placed:</strong></td><td>2024-01-20 10:30:00</td></tr>
                                <tr><td><strong>Executed:</strong></td><td>2024-01-20 10:30:15</td></tr>
                                <tr><td><strong>P&L:</strong></td><td class="pnl-positive">+₹150.00</td></tr>
                            </table>
                        </div>
                    </div>
                `);
            }, 1000);
        }

        function exportTrades(format) {
            toastr.info(`Exporting trades in ${format.toUpperCase()} format...`);
            // This would implement the actual export functionality
        }

        // Sample function to render trades table (would be replaced with real data)
        function renderTradesTable(trades) {
            if (trades.length === 0) {
                return '<div class="text-center text-muted"><i class="fa fa-history fa-3x mb-3"></i><p>No trades found</p></div>';
            }
            
            let html = '<div class="table-responsive"><table class="table table-hover trade-table">';
            html += '<thead><tr>';
            html += '<th>Date/Time</th>';
            html += '<th>Symbol</th>';
            html += '<th>Type</th>';
            html += '<th>Quantity</th>';
            html += '<th>Price</th>';
            html += '<th>Amount</th>';
            html += '<th>Status</th>';
            html += '<th>P&L</th>';
            html += '<th>Actions</th>';
            html += '</tr></thead><tbody>';
            
            trades.forEach(function(trade) {
                const statusClass = 'trade-status-' + trade.status.toLowerCase();
                const typeClass = 'trade-type-' + trade.type.toLowerCase();
                const pnlClass = parseFloat(trade.pnl || 0) >= 0 ? 'pnl-positive' : 'pnl-negative';
                
                html += '<tr class="trade-row" onclick="showTradeDetails(\'' + trade.id + '\')">';
                html += '<td>' + trade.timestamp + '</td>';
                html += '<td><strong>' + trade.symbol + '</strong></td>';
                html += '<td><span class="' + typeClass + '">' + trade.type + '</span></td>';
                html += '<td>' + trade.quantity + '</td>';
                html += '<td>₹' + parseFloat(trade.price || 0).toFixed(2) + '</td>';
                html += '<td>₹' + parseFloat(trade.amount || 0).toFixed(2) + '</td>';
                html += '<td><span class="' + statusClass + '">' + trade.status + '</span></td>';
                html += '<td class="' + pnlClass + '">₹' + parseFloat(trade.pnl || 0).toFixed(2) + '</td>';
                html += '<td>';
                html += '<button class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation(); showTradeDetails(\'' + trade.id + '\')">';
                html += '<i class="fa fa-eye"></i>';
                html += '</button>';
                html += '</td>';
                html += '</tr>';
            });
            
            html += '</tbody></table></div>';
            return html;
        }
    </script>
    @endpush
</x-app-layout>

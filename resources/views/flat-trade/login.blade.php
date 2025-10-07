<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('FlatTrade Login') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card shadow-lg">
                        <div class="card-header bg-primary text-white">
                            <div class="d-flex align-items-center">
                                <i class="fa fa-chart-line fa-2x me-3"></i>
                                <div>
                                    <h4 class="mb-0">FlatTrade Integration</h4>
                                    <small>Connect your FlatTrade account for seamless trading</small>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-5">
                            <!-- Connection Status -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <div class="alert alert-info" role="alert">
                                        <i class="fa fa-info-circle me-2"></i>
                                        <strong>About FlatTrade Integration:</strong>
                                        Connect your FlatTrade account to access professional trading features including real-time market data, order placement, and portfolio management.
                                    </div>
                                </div>
                            </div>

                            <!-- Features Overview -->
                            <div class="row mb-4">
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <i class="fa fa-chart-bar text-primary fa-2x"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-1">Real-time Market Data</h6>
                                            <small class="text-muted">Get live quotes, order book, and market analysis</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <i class="fa fa-bolt text-warning fa-2x"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-1">Quick Order Placement</h6>
                                            <small class="text-muted">Place buy, sell, and bracket orders instantly</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <i class="fa fa-briefcase text-success fa-2x"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-1">Portfolio Management</h6>
                                            <small class="text-muted">Track holdings, P&L, and account balance</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <i class="fa fa-sync text-info fa-2x"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-1">Smart Trading</h6>
                                            <small class="text-muted">Automated trade cycles with stop-loss and targets</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Connection Status Card -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <div class="card border-{{ $account_connected ? 'success' : 'warning' }}">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1">
                                                        <i class="fa fa-{{ $account_connected ? 'check-circle text-success' : 'exclamation-triangle text-warning' }} me-2"></i>
                                                        Account Status
                                                    </h6>
                                                    <p class="mb-0 text-muted">
                                                        @if($account_connected)
                                                            Your FlatTrade account is connected and ready for trading.
                                                        @else
                                                            Connect your FlatTrade account to start trading.
                                                        @endif
                                                    </p>
                                                </div>
                                                <div class="text-end">
                                                    <span class="badge bg-{{ $account_connected ? 'success' : 'warning' }} fs-6">
                                                        {{ $account_status }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                                        @if($account_connected)
                                            <a href="{{ route('flat_trade::dashboard') }}" class="btn btn-success btn-lg me-md-2">
                                                <i class="fa fa-tachometer me-2"></i>
                                                Go to Dashboard
                                            </a>
                                            <form method="POST" action="{{ route('flat_trade::disconnect') }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-danger btn-lg" onclick="return confirm('Are you sure you want to disconnect your FlatTrade account?')">
                                                    <i class="fa fa-unlink me-2"></i>
                                                    Disconnect Account
                                                </button>
                                            </form>
                                        @else
                                            <a href="{{ route('flat_trade::connect') }}" class="btn btn-primary btn-lg me-md-2">
                                                <i class="fa fa-link me-2"></i>
                                                Connect FlatTrade Account
                                            </a>
                                            <a href="{{ route('home') }}" class="btn btn-outline-secondary btn-lg">
                                                <i class="fa fa-arrow-left me-2"></i>
                                                Back to Dashboard
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Security Notice -->
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="alert alert-light border" role="alert">
                                        <h6 class="alert-heading">
                                            <i class="fa fa-shield-alt text-success me-2"></i>
                                            Security & Privacy
                                        </h6>
                                        <p class="mb-2">Your connection to FlatTrade is secure and uses industry-standard OAuth 2.0 authentication:</p>
                                        <ul class="mb-0 small">
                                            <li>Your credentials are never stored on our servers</li>
                                            <li>All API communications are encrypted</li>
                                            <li>You can disconnect your account at any time</li>
                                            <li>We only access the minimum required permissions</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- API Status -->
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="card bg-light">
                                        <div class="card-body py-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1">
                                                        <i class="fa fa-server text-info me-2"></i>
                                                        API Status
                                                    </h6>
                                                    <small class="text-muted">FlatTrade API connectivity</small>
                                                </div>
                                                <div>
                                                    <span class="badge bg-success" id="api-status">
                                                        <i class="fa fa-check-circle me-1"></i>
                                                        Online
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
    <style>
        .flat-trade-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .feature-card {
            transition: transform 0.2s ease-in-out;
        }

        .feature-card:hover {
            transform: translateY(-2px);
        }

        .connection-status-card {
            border-left: 4px solid;
        }

        .btn-lg {
            padding: 0.75rem 2rem;
            font-size: 1.1rem;
        }

        .security-notice {
            background: linear-gradient(45deg, #f8f9fa, #e9ecef);
        }
    </style>
    @endpush

    @push('scripts')
    <script>
        $(document).ready(function() {
            // Check API status
            checkApiStatus();

            // Auto-refresh API status every 30 seconds
            setInterval(checkApiStatus, 30000);
        });

        function checkApiStatus() {
            $.get('{{ route("flat_trade::status") }}')
                .done(function(response) {
                    if (response.connected) {
                        $('#api-status').removeClass('bg-danger').addClass('bg-success')
                            .html('<i class="fa fa-check-circle me-1"></i>Online');
                    } else {
                        $('#api-status').removeClass('bg-success').addClass('bg-danger')
                            .html('<i class="fa fa-times-circle me-1"></i>Offline');
                    }
                })
                .fail(function() {
                    $('#api-status').removeClass('bg-success').addClass('bg-danger')
                        .html('<i class="fa fa-times-circle me-1"></i>Offline');
                });
        }
    </script>
    @endpush
</x-app-layout>

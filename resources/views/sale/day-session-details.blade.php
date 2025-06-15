<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('sale::day-sessions-report') }}">Day Sessions Report</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Session #{{ $session->id }}</li>
                </ol>
            </nav>
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="page-title mb-0 mt-2 text-white">Day Session Details</h1>
                    <p class="lead mb-0">
                        Detailed view of session #{{ $session->id }} for {{ $session->branch->name }}
                    </p>
                </div>
                <div class="text-end">
                    <a href="{{ route('print::sale::day-session-report', $session->id) }}" class="btn btn-success text-white me-2" target="_blank" title="Print Thermal Receipt">
                        <i class="fa fa-print me-2"></i>Print
                    </a>
                    <a href="{{ route('sale::day-sessions-report') }}" class="btn btn-outline-secondary text-white">
                        <i class="fa fa-arrow-left me-2"></i>Back to Report
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <!-- Session Overview Cards -->
            <div class="row g-4 mb-4">
                <!-- Session Status Card -->
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="rounded-circle p-3 me-3" style="background: linear-gradient(135deg, #4a6fa5 0%, #357abd 100%);">
                                    <i class="fa fa-calendar text-white" style="font-size: 24px;"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1 text-muted">Session Status</h6>
                                    <h4 class="mb-0 fw-bold" style="color: #2c3e50;">
                                        @if ($session->status == 'open')
                                            <span class="badge bg-success bg-opacity-10 text-success px-3 py-2">
                                                <i class="fa fa-circle me-1" style="font-size: 8px;"></i>Active Session
                                            </span>
                                        @else
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary px-3 py-2">
                                                <i class="fa fa-check-circle me-1"></i>Closed Session
                                            </span>
                                        @endif
                                    </h4>
                                </div>
                            </div>
                            <div class="border-top pt-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <small class="text-muted d-block">Session ID</small>
                                        <span class="fw-bold" style="color: #2c3e50;">#{{ $session->id }}</span>
                                    </div>
                                    <div class="text-end">
                                        <small class="text-muted d-block">Branch</small>
                                        <span class="fw-bold" style="color: #2c3e50;">{{ $session->branch->name }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sales Summary Card -->
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="rounded-circle p-3 me-3" style="background: linear-gradient(135deg, #28a745 0%, #34d399 100%);">
                                    <i class="fa fa-shopping-cart text-white" style="font-size: 24px;"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1 text-muted">Sales Summary</h6>
                                    <h4 class="mb-0 fw-bold" style="color: #2c3e50;">{{ $session->sales->count() }} Transactions</h4>
                                </div>
                            </div>
                            <div class="border-top pt-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <small class="text-muted d-block">Total Revenue</small>
                                        <span class="fw-bold" style="color: #28a745; font-size: 1.25rem;">
                                            {{ number_format($session->sales->sum('paid'), 2) }}
                                        </span>
                                    </div>
                                    <div class="text-end">
                                        <small class="text-muted d-block">Duration</small>
                                        <span class="fw-bold" style="color: #2c3e50;">
                                            @if ($session->status == 'closed' && $session->closed_at)
                                                {{ round($session->opened_at->diffInHours($session->closed_at), 2) }}h {{ $session->opened_at->diff($session->closed_at)->format('%im') }}
                                            @else
                                                {{ round($session->opened_at->diffInHours(now()), 2) }}h {{ $session->opened_at->diff(now())->format('%im') }}
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cash Summary Card -->
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="rounded-circle p-3 me-3" style="background: linear-gradient(135deg, #b8860b 0%, #daa520 100%);">
                                    <i class="fa fa-money text-white" style="font-size: 24px;"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1 text-muted">Cash Summary</h6>
                                    <h4 class="mb-0 fw-bold" style="color: #2c3e50;">
                                        @if ($session->status == 'closed')
                                            {{ number_format($session->closing_amount, 2) }}
                                        @else
                                            {{ number_format($session->opening_amount, 2) }}
                                        @endif
                                    </h4>
                                </div>
                            </div>
                            <div class="border-top pt-3">
                                @if ($session->status == 'closed')
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <small class="text-muted d-block">Cash Difference</small>
                                            <span class="fw-bold"
                                                style="color: {{ $session->difference_amount > 0 ? '#28a745' : ($session->difference_amount < 0 ? '#dc3545' : '#6c757d') }}; font-size: 1.25rem;">
                                                @if ($session->difference_amount > 0)
                                                    <i class="fa fa-arrow-up me-1"></i>+{{ number_format($session->difference_amount, 2) }}
                                                @elseif($session->difference_amount < 0)
                                                    <i class="fa fa-arrow-down me-1"></i>{{ number_format($session->difference_amount, 2) }}
                                                @else
                                                    <i class="fa fa-check me-1"></i>{{ number_format($session->difference_amount, 2) }}
                                                @endif
                                            </span>
                                        </div>
                                        <div class="text-end">
                                            <small class="text-muted d-block">Status</small>
                                            <span
                                                class="badge {{ $session->difference_amount > 0 ? 'bg-success' : ($session->difference_amount < 0 ? 'bg-danger' : 'bg-secondary') }} bg-opacity-10
                                                              {{ $session->difference_amount > 0 ? 'text-success' : ($session->difference_amount < 0 ? 'text-danger' : 'text-secondary') }} px-3 py-2">
                                                {{ $session->difference_amount > 0 ? 'Surplus' : ($session->difference_amount < 0 ? 'Shortage' : 'Balanced') }}
                                            </span>
                                        </div>
                                    </div>
                                @else
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <small class="text-muted d-block">Opening Balance</small>
                                            <span class="fw-bold" style="color: #b8860b; font-size: 1.25rem;">
                                                {{ number_format($session->opening_amount, 2) }}
                                            </span>
                                        </div>
                                        <div class="text-end">
                                            <small class="text-muted d-block">Expected</small>
                                            <span class="fw-bold" style="color: #5a9fd4; font-size: 1.25rem;">
                                                {{ number_format($session->expected_amount, 2) }}
                                            </span>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Session Timeline -->
            <div class="row g-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0 d-flex align-items-center">
                                <i class="fa fa-history me-2" style="color: #4a6fa5;"></i>
                                Session Timeline
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="timeline p-4">
                                <!-- Opening Event -->
                                <div class="timeline-item d-flex position-relative pb-4">
                                    <div class="timeline-marker rounded-circle p-2 me-3" style="background: linear-gradient(135deg, #28a745 0%, #34d399 100%);">
                                        <i class="fa fa-play text-white"></i>
                                    </div>
                                    <div class="timeline-content flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="mb-0 fw-bold" style="color: #2c3e50;">Session Opened</h6>
                                            <small class="text-muted">{{ $session->opened_at->format('M d, Y g:i A') }}</small>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle p-2 me-2" style="background-color: rgba(74, 111, 165, 0.1);">
                                                <i class="fa fa-user" style="color: #4a6fa5;"></i>
                                            </div>
                                            <span class="text-muted">By {{ $session->opener->name ?? 'Unknown' }}</span>
                                        </div>
                                        <div class="mt-2">
                                            <span class="badge bg-success bg-opacity-10 text-success">
                                                Opening Balance: {{ number_format($session->opening_amount, 2) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                @if ($session->status == 'closed')
                                    <!-- Closing Event -->
                                    <div class="timeline-item d-flex position-relative">
                                        <div class="timeline-marker rounded-circle p-2 me-3" style="background: linear-gradient(135deg, #dc3545 0%, #ef4444 100%);">
                                            <i class="fa fa-stop text-white"></i>
                                        </div>
                                        <div class="timeline-content flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <h6 class="mb-0 fw-bold" style="color: #2c3e50;">Session Closed</h6>
                                                <small class="text-muted">{{ $session->closed_at->format('M d, Y g:i A') }}</small>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <div class="rounded-circle p-2 me-2" style="background-color: rgba(74, 111, 165, 0.1);">
                                                    <i class="fa fa-user" style="color: #4a6fa5;"></i>
                                                </div>
                                                <span class="text-muted">By {{ $session->closer->name ?? 'Unknown' }}</span>
                                            </div>
                                            <div class="mt-2">
                                                <div class="d-flex gap-2 flex-wrap">
                                                    <span class="badge bg-warning bg-opacity-10 text-warning">
                                                        Closing Balance: {{ number_format($session->closing_amount, 2) }}
                                                    </span>
                                                    <span class="badge bg-info bg-opacity-10 text-info">
                                                        Expected: {{ number_format($session->expected_amount, 2) }}
                                                    </span>
                                                    <span
                                                        class="badge {{ $session->difference_amount > 0 ? 'bg-success' : ($session->difference_amount < 0 ? 'bg-danger' : 'bg-secondary') }} bg-opacity-10
                                                                  {{ $session->difference_amount > 0 ? 'text-success' : ($session->difference_amount < 0 ? 'text-danger' : 'text-secondary') }}">
                                                        Difference: {{ number_format($session->difference_amount, 2) }}
                                                        ({{ $session->difference_amount > 0 ? 'Surplus' : ($session->difference_amount < 0 ? 'Shortage' : 'Balanced') }})
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes Section (if exists) -->
            @if ($session->notes)
                <div class="row g-4 mt-4">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-start">
                                    <div class="rounded-circle p-3 me-3" style="background-color: rgba(108, 117, 125, 0.1);">
                                        <i class="fa fa-sticky-note" style="color: #6c757d; font-size: 20px;"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-2 fw-bold" style="color: #2c3e50;">Session Notes</h6>
                                        <p class="mb-0 text-muted">{{ $session->notes }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Sales List Section -->
            <div class="row mt-5">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0 d-flex align-items-center">
                                <i class="fa fa-list me-2" style="color: #4a6fa5;"></i>
                                Session Sales Details
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            @livewire('sale-day-session.day-session-sales-list', ['sessionId' => $session->id])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @push('styles')
        <style>
            /* Custom styles for the timeline */
            .timeline {
                position: relative;
            }

            .timeline::before {
                content: '';
                position: absolute;
                left: 24px;
                top: 0;
                bottom: 0;
                width: 2px;
                background: #e9ecef;
            }

            .timeline-item {
                position: relative;
            }

            .timeline-marker {
                width: 32px;
                height: 32px;
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 1;
            }

            /* Card hover effects */
            .card {
                transition: all 0.3s ease;
            }

            .card:hover {
                transform: translateY(-5px);
                box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1) !important;
            }

            /* Badge styles */
            .badge {
                font-weight: 500;
                padding: 0.5em 0.75em;
                border-radius: 0.375rem;
            }

            /* Responsive adjustments */
            @media (max-width: 768px) {
                .card-body {
                    padding: 1rem !important;
                }

                .timeline::before {
                    left: 20px;
                }

                .timeline-marker {
                    width: 28px;
                    height: 28px;
                }

                .fw-bold {
                    font-size: 1rem !important;
                }
            }

            /* Theme colors */
            :root {
                --primary-color: #4a6fa5;
                --primary-gradient: linear-gradient(135deg, #4a6fa5 0%, #357abd 100%);
                --success-color: #28a745;
                --success-gradient: linear-gradient(135deg, #28a745 0%, #34d399 100%);
                --warning-color: #b8860b;
                --warning-gradient: linear-gradient(135deg, #b8860b 0%, #daa520 100%);
                --danger-color: #dc3545;
                --danger-gradient: linear-gradient(135deg, #dc3545 0%, #ef4444 100%);
                --text-primary: #2c3e50;
                --text-secondary: #6c757d;
            }
        </style>
    @endpush
</x-app-layout>

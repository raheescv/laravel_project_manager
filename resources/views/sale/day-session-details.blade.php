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
                    <a href="{{ route('sale::day-sessions-report') }}" class="btn btn-outline-secondary">
                        <i class="fa fa-arrow-left me-2"></i>Back to Report
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-header" style="background: linear-gradient(135deg, #4a6fa5 0%, #357abd 100%); color: white; border-bottom: none;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle p-2 me-3" style="background-color: rgba(255,255,255,0.2);">
                                        <i class="fa fa-calendar" style="font-size: 24px;"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-0">Day Session Details</h5>
                                        <small class="text-light opacity-75">Session #{{ $session->id }} - {{ $session->branch->name }}</small>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="d-flex align-items-center gap-3">
                                        @if ($session->status == 'open')
                                            <span class="badge" style="background-color: #28a745; font-size: 14px; padding: 8px 15px;">
                                                <i class="fa fa-circle me-2" style="font-size: 8px;"></i>Currently Open
                                            </span>
                                        @else
                                            <span class="badge" style="background-color: #6c757d; font-size: 14px; padding: 8px 15px;">
                                                <i class="fa fa-check-circle me-2"></i>Closed on {{ $session->closed_at->format('M d, Y') }}
                                            </span>
                                        @endif
                                        <div class="dropdown">
                                            <button class="btn btn-sm d-flex align-items-center"
                                                style="background-color: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); color: white; padding: 8px 12px;" type="button"
                                                data-bs-toggle="dropdown">
                                                <i class="fa fa-ellipsis-v me-2"></i>Actions
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" style="min-width: 200px;">
                                                <li>
                                                    <h6 class="dropdown-header" style="color: #6c757d; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">
                                                        <i class="fa fa-cog me-2" style="font-size: 10px;"></i>Export Options
                                                    </h6>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item d-flex align-items-center py-2" href="#" style="color: #495057;">
                                                        <i class="fa fa-print me-3" style="color: #4a6fa5; font-size: 14px;"></i>Print Session Report
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item d-flex align-items-center py-2" href="#" style="color: #495057;">
                                                        <i class="fa fa-download me-3" style="color: #28a745; font-size: 14px;"></i>Export as PDF
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item d-flex align-items-center py-2" href="#" style="color: #495057;">
                                                        <i class="fa fa-file-excel-o me-3" style="color: #b8860b; font-size: 14px;"></i>Export to Excel
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body" style="background-color: #fafafa;">
                            @if (session()->has('success'))
                                <div class="alert alert-success border-0 shadow-sm" style="background-color: #d4edda; border-left: 4px solid #28a745 !important;">
                                    <i class="fa fa-check-circle me-2"></i>{{ session('success') }}
                                </div>
                            @endif
                            @if (session()->has('error'))
                                <div class="alert alert-danger border-0 shadow-sm" style="background-color: #f8d7da; border-left: 4px solid #dc3545 !important;">
                                    <i class="fa fa-exclamation-circle me-2"></i>{{ session('error') }}
                                </div>
                            @endif

                            <!-- Summary Statistics -->
                            <div class="row g-4 mb-4">
                                <div class="col-md-3">
                                    <div class="d-flex align-items-center p-3 rounded" style="background-color: white; border-left: 4px solid #4a6fa5; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                        <div class="me-3">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #4a6fa5;">
                                                <i class="fa fa-shopping-cart" style="color: white; font-size: 20px;"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="small text-muted mb-1 fw-medium">Total Sales</div>
                                            <div class="h4 mb-0 fw-bold" style="color: #4a6fa5;">
                                                {{ $session->sales->count() }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="d-flex align-items-center p-3 rounded" style="background-color: white; border-left: 4px solid #b8860b; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                        <div class="me-3">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #b8860b;">
                                                <i class="fa fa-money" style="color: white; font-size: 20px;"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="small text-muted mb-1 fw-medium">Sales Amount</div>
                                            <div class="h4 mb-0 fw-bold" style="color: #b8860b;">
                                                {{ number_format($session->sales->sum('paid'), 2) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="d-flex align-items-center p-3 rounded" style="background-color: white; border-left: 4px solid #28a745; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                        <div class="me-3">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #28a745;">
                                                <i class="fa fa-clock-o" style="color: white; font-size: 20px;"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="small text-muted mb-1 fw-medium">Duration</div>
                                            <div class="h4 mb-0 fw-bold" style="color: #28a745;">
                                                @if ($session->status == 'closed' && $session->closed_at)
                                                    {{ $session->opened_at->diffInHours($session->closed_at) }}h {{ $session->opened_at->diff($session->closed_at)->format('%im') }}
                                                @else
                                                    {{ $session->opened_at->diffInHours(now()) }}h {{ $session->opened_at->diff(now())->format('%im') }}
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @if ($session->status == 'closed')
                                    <div class="col-md-3">
                                        <div class="d-flex align-items-center p-3 rounded"
                                            style="background-color: white; border-left: 4px solid {{ $session->difference_amount < 0 ? '#dc3545' : ($session->difference_amount > 0 ? '#28a745' : '#6c757d') }}; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                            <div class="me-3">
                                                <div class="rounded-circle d-flex align-items-center justify-content-center"
                                                    style="width: 50px; height: 50px; background-color: {{ $session->difference_amount < 0 ? '#dc3545' : ($session->difference_amount > 0 ? '#28a745' : '#6c757d') }};">
                                                    @if ($session->difference_amount > 0)
                                                        <i class="fa fa-arrow-up" style="color: white; font-size: 20px;"></i>
                                                    @elseif ($session->difference_amount < 0)
                                                        <i class="fa fa-arrow-down" style="color: white; font-size: 20px;"></i>
                                                    @else
                                                        <i class="fa fa-check" style="color: white; font-size: 20px;"></i>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="small text-muted mb-1 fw-medium">Cash Difference</div>
                                                <div class="h4 mb-0 fw-bold"
                                                    style="color: {{ $session->difference_amount < 0 ? '#dc3545' : ($session->difference_amount > 0 ? '#28a745' : '#6c757d') }};">
                                                    {{ number_format($session->difference_amount, 2) }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="col-md-3">
                                        <div class="d-flex align-items-center p-3 rounded" style="background-color: white; border-left: 4px solid #5a9fd4; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                            <div class="me-3">
                                                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #5a9fd4;">
                                                    <i class="fa fa-unlock" style="color: white; font-size: 20px;"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="small text-muted mb-1 fw-medium">Status</div>
                                                <div class="h4 mb-0 fw-bold" style="color: #5a9fd4;">
                                                    Active
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="card-header" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-bottom: 1px solid #e9ecef;">
                                            <h6 class="mb-0 d-flex align-items-center" style="color: #495057;">
                                                <div class="rounded-circle p-2 me-3" style="background-color: #4a6fa5; color: white;">
                                                    <i class="fa fa-info" style="font-size: 14px;"></i>
                                                </div>
                                                Session Information
                                            </h6>
                                        </div>
                                        <div class="card-body" style="background-color: white;">
                                            <div class="row g-3">
                                                <div class="col-12">
                                                    <div class="d-flex align-items-center p-3 rounded" style="background-color: #f8f9fa; border-left: 3px solid #6c757d;">
                                                        <i class="fa fa-hashtag me-3" style="color: #6c757d; font-size: 18px;"></i>
                                                        <div class="flex-grow-1">
                                                            <div class="small text-muted">Session ID</div>
                                                            <div class="fw-bold" style="color: #495057;">{{ $session->id }}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-12">
                                                    <div class="d-flex align-items-center p-3 rounded" style="background-color: #f8f9fa; border-left: 3px solid #4a6fa5;">
                                                        <i class="fa fa-building me-3" style="color: #4a6fa5; font-size: 18px;"></i>
                                                        <div class="flex-grow-1">
                                                            <div class="small text-muted">Branch</div>
                                                            <div class="fw-bold" style="color: #495057;">{{ $session->branch->name }}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-12">
                                                    <div class="d-flex align-items-center p-3 rounded"
                                                        style="background-color: #f8f9fa; border-left: 3px solid {{ $session->status == 'open' ? '#28a745' : '#6c757d' }};">
                                                        <i class="fa fa-flag me-3" style="color: {{ $session->status == 'open' ? '#28a745' : '#6c757d' }}; font-size: 18px;"></i>
                                                        <div class="flex-grow-1">
                                                            <div class="small text-muted">Status</div>
                                                            <div>
                                                                @if ($session->status == 'open')
                                                                    <span class="badge d-inline-flex align-items-center" style="background-color: #28a745; color: white; font-size: 13px;">
                                                                        <i class="fa fa-circle me-1" style="font-size: 6px;"></i>Open
                                                                    </span>
                                                                @else
                                                                    <span class="badge d-inline-flex align-items-center" style="background-color: #6c757d; color: white; font-size: 13px;">
                                                                        <i class="fa fa-check-circle me-1" style="font-size: 10px;"></i>Closed
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                @if ($session->notes)
                                                    <div class="col-12">
                                                        <div class="d-flex align-items-start p-3 rounded" style="background-color: #f8f9fa; border-left: 3px solid #b8860b;">
                                                            <i class="fa fa-sticky-note me-3" style="color: #b8860b; font-size: 18px; margin-top: 2px;"></i>
                                                            <div class="flex-grow-1">
                                                                <div class="small text-muted">Notes</div>
                                                                <div style="color: #495057;">{{ $session->notes }}</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="card-header" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-bottom: 1px solid #e9ecef;">
                                            <h6 class="mb-0 d-flex align-items-center" style="color: #495057;">
                                                <div class="rounded-circle p-2 me-3" style="background-color: #28a745; color: white;">
                                                    <i class="fa fa-unlock" style="font-size: 14px;"></i>
                                                </div>
                                                Opening & Closing Information
                                            </h6>
                                        </div>
                                        <div class="card-body" style="background-color: white;">
                                            <div class="table-responsive">
                                                <table class="table table-borderless mb-0">
                                                    <tr>
                                                        <th style="color: #6c757d; font-weight: 500; width: 40%; padding: 12px 0;">
                                                            <i class="fa fa-calendar-o me-2" style="color: #5a9fd4;"></i>Opened At:
                                                        </th>
                                                        <td style="color: #495057; padding: 12px 0; font-weight: 500;">
                                                            {{ $session->opened_at->format('M d, Y \a\t g:i A') }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th style="color: #6c757d; font-weight: 500; padding: 12px 0;">
                                                            <i class="fa fa-user me-2" style="color: #4a6fa5;"></i>Opened By:
                                                        </th>
                                                        <td class="fw-bold" style="color: #495057; padding: 12px 0;">{{ $session->opener->name ?? 'Unknown' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th style="color: #6c757d; font-weight: 500; padding: 12px 0;">
                                                            <i class="fa fa-money me-2" style="color: #b8860b;"></i>Opening Amount:
                                                        </th>
                                                        <td class="fw-bold" style="color: #b8860b; padding: 12px 0; font-size: 16px;">{{ number_format($session->opening_amount, 2) }}</td>
                                                    </tr>
                                                    @if ($session->status == 'closed')
                                                        <tr style="border-top: 2px solid #f8f9fa;">
                                                            <th style="color: #6c757d; font-weight: 500; padding: 16px 0 12px 0;">
                                                                <i class="fa fa-calendar-check me-2" style="color: #dc3545;"></i>Closed At:
                                                            </th>
                                                            <td style="color: #495057; padding: 16px 0 12px 0; font-weight: 500;">
                                                                {{ $session->closed_at->format('M d, Y \a\t g:i A') }}
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th style="color: #6c757d; font-weight: 500; padding: 12px 0;">
                                                                <i class="fa fa-user me-2" style="color: #4a6fa5;"></i>Closed By:
                                                            </th>
                                                            <td class="fw-bold" style="color: #495057; padding: 12px 0;">{{ $session->closer->name ?? 'Unknown' }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th style="color: #6c757d; font-weight: 500; padding: 12px 0;">
                                                                <i class="fa fa-money me-2" style="color: #b8860b;"></i>Closing Amount:
                                                            </th>
                                                            <td class="fw-bold" style="color: #b8860b; padding: 12px 0; font-size: 16px;">{{ number_format($session->closing_amount, 2) }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th style="color: #6c757d; font-weight: 500; padding: 12px 0;">
                                                                <i class="fa fa-calculator me-2" style="color: #5a9fd4;"></i>Expected Amount:
                                                            </th>
                                                            <td class="fw-bold" style="color: #5a9fd4; padding: 12px 0; font-size: 16px;">{{ number_format($session->expected_amount, 2) }}</td>
                                                        </tr>
                                                        <tr style="border-top: 2px solid #f8f9fa;">
                                                            <th style="color: #6c757d; font-weight: 500; padding: 16px 0 12px 0;">
                                                                <i class="fa fa-balance-scale me-2" style="color: #6c757d;"></i>Cash Difference:
                                                            </th>
                                                            <td style="padding: 16px 0 12px 0;">
                                                                @if ($session->difference_amount > 0)
                                                                    <span class="fw-bold d-flex align-items-center" style="color: #28a745; font-size: 18px;">
                                                                        <i class="fa fa-arrow-up me-2" style="font-size: 16px;"></i>+{{ number_format($session->difference_amount, 2) }}
                                                                    </span>
                                                                @elseif($session->difference_amount < 0)
                                                                    <span class="fw-bold d-flex align-items-center" style="color: #dc3545; font-size: 18px;">
                                                                        <i class="fa fa-arrow-down me-2" style="font-size: 16px;"></i>{{ number_format($session->difference_amount, 2) }}
                                                                    </span>
                                                                @else
                                                                    <span class="fw-bold d-flex align-items-center" style="color: #28a745; font-size: 18px;">
                                                                        <i class="fa fa-check me-2" style="font-size: 16px;"></i>{{ number_format($session->difference_amount, 2) }}
                                                                    </span>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endif
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-5">
                    <div class="col-12">
                        <div style="position: relative;">
                            <!-- Decorative separator -->
                            <div class="text-center mb-4">
                                <div
                                    style="display: inline-block; padding: 10px 30px; background: linear-gradient(135deg, #4a6fa5 0%, #357abd 100%); color: white; border-radius: 25px; box-shadow: 0 4px 8px rgba(74, 111, 165, 0.3);">
                                    <i class="fa fa-list me-2"></i>
                                    <span class="fw-medium">Session Sales Details</span>
                                </div>
                            </div>
                            @livewire('sale-day-session.day-session-sales-list', ['sessionId' => $session->id])
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script>
            function showCloseSessionModal() {
                const modal = new bootstrap.Modal(document.getElementById('closeSessionModal'));
                modal.show();
            }

            function submitCloseSession() {
                const form = document.getElementById('closeSessionForm');
                const closingAmount = document.getElementById('closing_amount');
                const confirmBtn = document.getElementById('confirmCloseBtn');

                // Validate closing amount
                if (!closingAmount.value || parseFloat(closingAmount.value) < 0) {
                    alert('Please enter a valid closing amount.');
                    closingAmount.focus();
                    return;
                }

                // Show loading state
                confirmBtn.disabled = true;
                confirmBtn.innerHTML = '<i class="fa fa-spinner fa-spin me-2"></i>Closing Session...';

                // Submit the form
                form.submit();
            }

            // Calculate difference when closing amount changes
            document.addEventListener('DOMContentLoaded', function() {
                const closingAmountInput = document.getElementById('closing_amount');
                const differenceCard = document.getElementById('differenceCard');
                const differenceAmount = document.getElementById('differenceAmount');
                const differenceNote = document.getElementById('differenceNote');

                const expectedAmount = {{ ($session->opening_amount ?? 0) + $session->sales()->sum('paid') }};

                if (closingAmountInput) {
                    closingAmountInput.addEventListener('input', function() {
                        const closingAmount = parseFloat(this.value) || 0;
                        const difference = closingAmount - expectedAmount;

                        if (this.value && closingAmount >= 0) {
                            differenceCard.style.display = 'block';

                            if (difference > 0) {
                                differenceCard.className = 'card border-0 border-start border-success border-4';
                                differenceCard.style.backgroundColor = '#d4edda';
                                differenceAmount.className = 'fw-bold fs-5 text-success';
                                differenceAmount.innerHTML = '<i class="fa fa-arrow-up me-2"></i>+' + Math.abs(difference).toFixed(2) + ' (Surplus)';
                                differenceNote.textContent = 'You have more cash than expected. Please verify the count.';
                            } else if (difference < 0) {
                                differenceCard.className = 'card border-0 border-start border-danger border-4';
                                differenceCard.style.backgroundColor = '#f8d7da';
                                differenceAmount.className = 'fw-bold fs-5 text-danger';
                                differenceAmount.innerHTML = '<i class="fa fa-arrow-down me-2"></i>-' + Math.abs(difference).toFixed(2) + ' (Shortage)';
                                differenceNote.textContent = 'You have less cash than expected. Please verify the count.';
                            } else {
                                differenceCard.className = 'card border-0 border-start border-success border-4';
                                differenceCard.style.backgroundColor = '#d4edda';
                                differenceAmount.className = 'fw-bold fs-5 text-success';
                                differenceAmount.innerHTML = '<i class="fa fa-check me-2"></i>Perfect Match!';
                                differenceNote.textContent = 'The cash amount matches the expected total exactly.';
                            }
                        } else {
                            differenceCard.style.display = 'none';
                        }
                    });
                }
            });
        </script>
</x-app-layout>

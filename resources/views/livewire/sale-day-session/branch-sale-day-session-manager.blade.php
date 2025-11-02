<div>
    <style>
        /* Theme colors and variables */
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

            /* Soft background colors */
            --bs-primary-soft: rgba(74, 111, 165, 0.1);
            --bs-success-soft: rgba(40, 167, 69, 0.1);
            --bs-warning-soft: rgba(184, 134, 11, 0.1);
            --bs-danger-soft: rgba(220, 53, 69, 0.1);
            --bs-info-soft: rgba(90, 159, 212, 0.1);
        }

        /* Card styles */
        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.12);
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.18);
        }

        /* Timeline styles */
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

        /* Badge styles */
        .badge {
            font-weight: 500;
            padding: 0.5em 0.75em;
            border-radius: 0.375rem;
        }

        /* Form control styles */
        .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(74, 111, 165, 0.15);
        }

        /* Button styles */
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background: var(--primary-gradient);
            border: none;
        }

        .btn-success {
            background: var(--success-gradient);
            border: none;
        }

        .btn-danger {
            background: var(--danger-gradient);
            border: none;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        /* Alert styles */
        .alert {
            border: none;
            border-radius: 0.75rem;
            padding: 1rem 1.5rem;
        }

        .alert-success {
            background-color: var(--bs-success-soft);
            color: var(--success-color);
        }

        .alert-danger {
            background-color: var(--bs-danger-soft);
            color: var(--danger-color);
        }

        /* Table styles */
        .table {
            margin-bottom: 0;
        }

        .table th {
            background: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
            color: var(--text-secondary);
            font-weight: 600;
            padding: 1rem;
        }

        .table td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #e2e8f0;
        }

        .table tbody tr:hover {
            background: #f8fafc;
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

            .btn {
                padding: 0.5rem 1rem;
            }
        }
    </style>

    <div class="container-fluid py-4">
        <div class="row g-4">
            <!-- Header Card -->
            <div class="col-12">
                <div class="card">
                    <div class="card-header" style="background: var(--primary-gradient); border-bottom: none; padding: 1.5rem;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle p-3 me-3" style="background-color: rgba(255,255,255,0.2);">
                                    <i class="fa fa-calendar text-white" style="font-size: 24px;"></i>
                                </div>
                                <div>
                                    <h4 class="mb-1 text-white">Day Session Management</h4>
                                    <p class="mb-0 text-white-50">Manage daily operations efficiently</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle p-2 me-2" style="background-color: rgba(255,255,255,0.2);">
                                        <i class="fa fa-building text-white"></i>
                                    </div>
                                    <select class="form-select" wire:model="branch_id" wire:change="changeBranch($event.target.value)"
                                        style="background-color: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3);">
                                        @foreach ($branches as $branch)
                                            <option value="{{ $branch->id }}" class="text-dark">{{ $branch->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle p-2 me-2" style="background-color: rgba(255,255,255,0.2);">
                                        <i class="fa fa-calendar text-white"></i>
                                    </div>
                                    <input type="date" class="form-control"
                                           wire:model="date"
                                           style="background-color: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3);">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-4">
                        <!-- Alert Messages -->
                        @if (session()->has('success'))
                            <div class="alert alert-success d-flex align-items-center mb-4">
                                <i class="fa fa-check-circle me-3"></i>
                                <span class="fw-medium">{{ session('success') }}</span>
                            </div>
                        @endif
                        @if (session()->has('error'))
                            <div class="alert alert-danger d-flex align-items-center mb-4">
                                <i class="fa fa-exclamation-circle me-3"></i>
                                <span class="fw-medium">{{ session('error') }}</span>
                            </div>
                        @endif

                        <!-- Current Session Display -->
                        @if ($currentSession)
                            <!-- Session Status Card -->
                            <div class="card mb-4">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle p-3 me-3" style="background: var(--success-gradient);">
                                                <i class="fa fa-play text-white"></i>
                                            </div>
                                            <div>
                                                <h5 class="mb-1" style="color: var(--text-primary);">Session Active</h5>
                                                <p class="mb-0 text-muted">Started {{ systemDateTime($sessionStats['opened_at']) }} by {{ $sessionStats['opened_by'] }}</p>
                                            </div>
                                        </div>
                                        <span class="badge" style="background: var(--success-gradient); color: white;">Session #{{ $currentSession->id }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Session Stats Cards -->
                            <div class="row g-4 mb-4">
                                <div class="col-md-3">
                                    <div class="card h-100">
                                        <div class="card-body p-4">
                                            <div class="d-flex align-items-center mb-3">
                                                <div class="rounded-circle p-3 me-3" style="background: var(--success-gradient);">
                                                    <i class="fa fa-bank text-white"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-1 text-muted">Opening Balance</h6>
                                                    <h4 class="mb-0 fw-bold" style="color: var(--text-primary);">{{ currency($sessionStats['opening_amount']) }}</h4>
                                                </div>
                                            </div>
                                            <div class="border-top pt-3">
                                                <span class="badge" style="background-color: var(--bs-success-soft); color: var(--success-color);">
                                                    Initial Cash
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="card h-100">
                                        <div class="card-body p-4">
                                            <div class="d-flex align-items-center mb-3">
                                                <div class="rounded-circle p-3 me-3" style="background: var(--primary-gradient);">
                                                    <i class="fa fa-shopping-cart text-white"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-1 text-muted">Total Sales</h6>
                                                    <h4 class="mb-0 fw-bold" style="color: var(--text-primary);">{{ $sessionStats['total_sales'] }}</h4>
                                                </div>
                                            </div>
                                            <div class="border-top pt-3">
                                                <span class="badge" style="background-color: var(--bs-primary-soft); color: var(--primary-color);">
                                                    Transactions
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="card h-100">
                                        <div class="card-body p-4">
                                            <div class="d-flex align-items-center mb-3">
                                                <div class="rounded-circle p-3 me-3" style="background: var(--warning-gradient);">
                                                    <i class="fa fa-money text-white"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-1 text-muted">Total Revenue</h6>
                                                    <h4 class="mb-0 fw-bold" style="color: var(--text-primary);">{{ currency($sessionStats['total_amount']) }}</h4>
                                                </div>
                                            </div>
                                            <div class="border-top pt-3">
                                                <span class="badge" style="background-color: var(--bs-warning-soft); color: var(--warning-color);">
                                                    Total Income
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="card h-100">
                                        <div class="card-body p-4">
                                            <div class="d-flex align-items-center mb-3">
                                                <div class="rounded-circle p-3 me-3" style="background: var(--danger-gradient);">
                                                    <i class="fa fa-calculator text-white"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-1 text-muted">Expected Amount</h6>
                                                    <h4 class="mb-0 fw-bold" style="color: var(--text-primary);">{{ currency($sessionStats['expected_amount']) }}</h4>
                                                </div>
                                            </div>
                                            <div class="border-top pt-3">
                                                <span class="badge" style="background-color: var(--bs-danger-soft); color: var(--danger-color);">
                                                    Projected
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Close Session Form -->
                            @php
                                $isPastSession = isset($sessionStats['opened_at']) && \Carbon\Carbon::parse($sessionStats['opened_at'])->lt(\Carbon\Carbon::today());
                            @endphp
                            <div class="card">
                                <div class="card-header" style="background: {{ $isPastSession ? 'var(--danger-gradient)' : 'var(--primary-gradient)' }}; border-bottom: none; padding: 1.5rem;">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <h5 class="mb-0 text-white">
                                            <i class="fa fa-lock me-2"></i>Close Session
                                        </h5>
                                        <span class="badge" style="background-color: rgba(255,255,255,0.2); color: white;">Critical Action</span>
                                    </div>
                                </div>
                                <div class="card-body p-4">
                                    <form wire:submit.prevent="closeDay">
                                        <div class="row g-4">
                                            <div class="col-md-4">
                                                <label class="form-label fw-bold" style="color: var(--text-primary);">
                                                    <i class="fa fa-money me-2" style="color: var(--success-color);"></i>Closing Amount
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <input type="number" step="0.01" class="form-control" wire:model.live="closing_amount" placeholder="0.00">
                                                @error('closing_amount')
                                                    <div class="text-danger small mt-2">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            @can('day close.sync amount')
                                            <div class="col-md-4">
                                                <label class="form-label fw-bold" style="color: var(--text-primary);">
                                                    <i class="fa fa-money me-2" style="color: var(--success-color);"></i>Sync Amount
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <input type="number" step="0.01" class="form-control" wire:model="sync_amount" placeholder="0.00">
                                                @error('sync_amount')
                                                    <div class="text-danger small mt-2">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            @endcan
                                            <div class="col-md-12">
                                                <label class="form-label fw-bold" style="color: var(--text-primary);">
                                                    <i class="fa fa-comment me-2" style="color: var(--text-secondary);"></i>Notes
                                                    <span class="text-muted">(Optional)</span>
                                                </label>
                                                <textarea class="form-control" wire:model="notes" rows="2" placeholder="Session notes..."></textarea>
                                                @error('notes')
                                                    <div class="text-danger small mt-2">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center mt-4 pt-4 border-top">
                                            <button type="button" class="btn btn-danger" onclick="confirmCloseSession()">
                                                <i class="fa fa-lock me-2"></i>Close Session
                                            </button>
                                            <div class="d-flex gap-3">
                                                <a href="{{ route('sale::create') }}" class="btn btn-success">
                                                    <i class="fa fa-plus me-2"></i>New Sale
                                                </a>
                                                <a href="{{ route('sale::day-session', $currentSession->id) }}" class="btn btn-primary">
                                                    <i class="fa fa-eye me-2"></i>Details
                                                </a>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @else
                            <!-- No Session State -->
                            <div class="text-center py-5">
                                <div class="mb-4">
                                    <div class="rounded-circle p-4 mx-auto" style="background: var(--success-gradient); width: 80px; height: 80px;">
                                        <i class="fa fa-unlock-alt text-white" style="font-size: 32px;"></i>
                                    </div>
                                </div>
                                <h3 class="mb-3" style="color: var(--text-primary);">Day Session Not Started</h3>
                                <p class="text-muted mb-4">Start a new session to enable sales processing and cash management.</p>

                                <div class="row justify-content-center">
                                    <div class="col-lg-6">
                                        <div class="card">
                                            <div class="card-header" style="background: var(--success-gradient); border-bottom: none; padding: 1.5rem;">
                                                <h5 class="mb-0 text-white">
                                                    <i class="fa fa-unlock me-2"></i>Start New Session
                                                </h5>
                                            </div>
                                            <div class="card-body p-4">
                                                <form wire:submit.prevent="openDay">
                                                    <div class="mb-4">
                                                        <label class="form-label fw-bold" style="color: var(--text-primary);">
                                                            <i class="fa fa-money me-2" style="color: var(--success-color);"></i>Opening Cash Amount
                                                            <span class="text-danger">*</span>
                                                        </label>
                                                        <div class="input-group">
                                                            <span class="input-group-text" style="background: var(--success-gradient); color: white;">
                                                                <i class="fa fa-dollar"></i>
                                                            </span>
                                                            <input type="number" step="0.01" class="form-control" wire:model="opening_amount" placeholder="0.00">
                                                        </div>
                                                        @error('opening_amount')
                                                            <div class="text-danger small mt-2">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                    <button type="submit" class="btn btn-success w-100">
                                                        <i class="fa fa-unlock me-2"></i>Start Session
                                                    </button>
                                                    <div class="mt-3 p-3 rounded" style="background-color: var(--bs-success-soft);">
                                                        <p class="text-muted mb-0">
                                                            <i class="fa fa-info-circle me-2"></i>
                                                            Count all cash before starting
                                                        </p>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Open Sessions Table -->
            @if (count($openSessions) > 0 && (!$currentSession || $currentSession->branch_id != Auth::user()->default_branch_id))
                <div class="col-12 mt-4">
                    <div class="card">
                        <div class="card-header" style="background: var(--primary-gradient); border-bottom: none; padding: 1.5rem;">
                            <div class="d-flex align-items-center justify-content-between">
                                <h5 class="mb-0 text-white">
                                    <i class="fa fa-list me-2"></i>All Open Sessions
                                </h5>
                                <span class="badge" style="background-color: rgba(255,255,255,0.2); color: white;">{{ count($openSessions) }} Active</span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Branch</th>
                                            <th>Started</th>
                                            <th>Opened By</th>
                                            <th>Amount</th>
                                            <th class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($openSessions as $session)
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center gap-3">
                                                        <div class="rounded-circle p-2" style="background-color: var(--bs-primary-soft);">
                                                            <i class="fa fa-building" style="color: var(--primary-color);"></i>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-1" style="color: var(--text-primary);">{{ $session->branch->name }}</h6>
                                                            <span class="badge" style="background-color: var(--bs-success-soft); color: var(--success-color);">Active</span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div style="color: var(--text-primary);">{{ $session->opened_at->format('M d, Y') }}</div>
                                                    <div class="text-muted">{{ $session->opened_at->format('h:i A') }}</div>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="rounded-circle p-2" style="background-color: var(--bs-info-soft);">
                                                            <i class="fa fa-user" style="color: var(--primary-color);"></i>
                                                        </div>
                                                        <span style="color: var(--text-primary);">{{ $session->opener->name ?? 'Unknown' }}</span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge" style="background-color: var(--bs-warning-soft); color: var(--warning-color);">
                                                        {{ currency($session->opening_amount) }}
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <div class="d-flex justify-content-center gap-2">
                                                        <button class="btn btn-primary" wire:click="changeBranch({{ $session->branch_id }})">
                                                            <i class="fa fa-cog me-2"></i>Manage
                                                        </button>
                                                        <a href="{{ route('sale::day-session', $session->id) }}" class="btn btn-primary">
                                                            <i class="fa fa-eye me-2"></i>Details
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@push('scripts')
<script>
function confirmCloseSession() {
    Swal.fire({
        title: 'Are you sure?',
        html: "Are you sure you want to close the session? This action cannot be undone. @if($currentSession?->branch?->moq_sync) <br><i> The API sync amount is " + @this.get('sync_amount') + "</i> @endif ",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fa fa-lock me-2"></i>Yes, Close Session',
        cancelButtonText: '<i class="fa fa-times me-2"></i>Cancel',
        reverseButtons: true,
        focusCancel: true,
        customClass: {
            confirmButton: 'btn btn-danger',
            cancelButton: 'btn btn-secondary'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Submit the form
            @this.call('closeDay');
        }
    });
}
</script>
@endpush

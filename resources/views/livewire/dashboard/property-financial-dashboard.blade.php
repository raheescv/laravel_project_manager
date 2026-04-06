<div>
    {{-- Agreement Type Toggle --}}
    <div class="d-flex justify-content-end mb-3">
        <button wire:click="toggleAgreementType" class="btn btn-sm btn-outline-warning">
            <i class="fa fa-exchange me-1"></i>
            Switch to {{ $agreementType === 'rental' ? 'Lease / Sale' : 'Rental' }}
        </button>
    </div>

    {{-- Current Month Collection --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card bg-indigo bg-gradient border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-white bg-opacity-25 p-2 me-3">
                            <i class="fa fa-th text-white"></i>
                        </div>
                        <div>
                            <h4 class="h5 mb-0 text-white">{{ currency($collection) }}</h4>
                            <p class="text-white-50 small mb-0">Expected ({{ now()->format('M Y') }})</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success bg-gradient border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-white bg-opacity-25 p-2 me-3">
                            <i class="fa fa-check text-white"></i>
                        </div>
                        <div>
                            <h4 class="h5 mb-0 text-white">{{ currency($paid) }}</h4>
                            <p class="text-white-50 small mb-0">Collected</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-danger bg-gradient border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-white bg-opacity-25 p-2 me-3">
                            <i class="fa fa-clock-o text-white"></i>
                        </div>
                        <div>
                            <h4 class="h5 mb-0 text-white">{{ currency($pending) }}</h4>
                            <p class="text-white-50 small mb-0">Pending</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Collection Progress --}}
    @if($collection > 0)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between small mb-2">
                    <span class="fw-semibold">Collection Progress ({{ now()->format('F') }})</span>
                    <span class="fw-bold text-primary">{{ $collection > 0 ? round(($paid / $collection) * 100, 1) : 0 }}%</span>
                </div>
                <div class="progress" style="height: 12px;">
                    <div class="progress-bar bg-success" style="width: {{ $collection > 0 ? round(($paid / $collection) * 100) : 0 }}%"></div>
                </div>
            </div>
        </div>
    @endif

    <div class="row g-3 mb-4">
        {{-- Overdue Payments --}}
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h6 class="mb-0 fw-semibold"><i class="fa fa-exclamation-circle text-danger me-2"></i>Overdue Payments</h6>
                </div>
                <div class="card-body pt-0">
                    <div class="text-center p-3 bg-danger bg-opacity-10 rounded-3 mb-3">
                        <div class="h4 mb-0 fw-bold text-danger">{{ currency($overdueAmount) }}</div>
                        <small class="text-muted">Total Overdue Amount</small>
                    </div>
                    <div class="d-flex justify-content-center">
                        <span class="badge bg-danger bg-opacity-10 text-danger px-3 py-2">
                            <i class="fa fa-file-text-o me-1"></i> {{ $overdueCount }} overdue {{ $overdueCount === 1 ? 'payment' : 'payments' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Cheque Management --}}
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h6 class="mb-0 fw-semibold"><i class="fa fa-money text-info me-2"></i>Cheque Management</h6>
                </div>
                <div class="card-body pt-0">
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div class="p-2 rounded-3 bg-success bg-opacity-10 text-center">
                                <div class="h6 mb-0 fw-bold text-success">{{ currency($clearedCheques) }}</div>
                                <small class="text-muted">Cleared ({{ $clearedCount }})</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded-3 bg-warning bg-opacity-10 text-center">
                                <div class="h6 mb-0 fw-bold text-warning">{{ currency($unclearedCheques) }}</div>
                                <small class="text-muted">Uncleared ({{ $unclearedCount }})</small>
                            </div>
                        </div>
                    </div>
                    @if($clearedCheques + $unclearedCheques > 0)
                        <div class="progress" style="height: 8px;">
                            @php $totalCheque = $clearedCheques + $unclearedCheques; @endphp
                            <div class="progress-bar bg-success" style="width: {{ round(($clearedCheques / $totalCheque) * 100) }}%"></div>
                            <div class="progress-bar bg-warning" style="width: {{ round(($unclearedCheques / $totalCheque) * 100) }}%"></div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Security Deposits --}}
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <h6 class="mb-0 fw-semibold"><i class="fa fa-shield text-primary me-2"></i>Security Deposits</h6>
                </div>
                <div class="card-body pt-0">
                    <div class="text-center p-3 bg-primary bg-opacity-10 rounded-3 mb-3">
                        <div class="h4 mb-0 fw-bold text-primary">{{ currency($totalSecurity) }}</div>
                        <small class="text-muted">Total Security Held</small>
                    </div>
                    <div class="d-flex justify-content-center">
                        <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2">
                            <i class="fa fa-file-text-o me-1"></i> {{ $securityCount }} active {{ $securityCount === 1 ? 'deposit' : 'deposits' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

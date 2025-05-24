<div>
    <div class="card shadow-sm">
        <div class="card-body">
            <!-- Filter Section -->
            <div class="row mb-4 g-3">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="branch_id" class="form-label fw-bold text-secondary mb-2">
                            <i class="pli-building me-1"></i>Branch
                        </label>
                        <select wire:model.live="branch_id" class="form-select shadow-sm border-light" id="branch_id">
                            <option value="">All Branches</option>
                            @foreach ($branches as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="period" class="form-label fw-bold text-secondary mb-2">
                            <i class="pli-time-clock me-1"></i>Period
                        </label>
                        <select wire:model.live="period" class="form-select shadow-sm border-light" id="period">
                            <option value="monthly">Current Month</option>
                            <option value="quarterly">Current Quarter</option>
                            <option value="yearly">Current Year</option>
                            <option value="previous_month">Previous Month</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="start_date" class="form-label fw-bold text-secondary mb-2">
                            <i class="pli-calendar-4 me-1"></i>Start Date
                        </label>
                        <input type="date" wire:model.live="start_date" class="form-control shadow-sm border-light" id="start_date">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="end_date" class="form-label fw-bold text-secondary mb-2">
                            <i class="pli-calendar-4 me-1"></i>End Date
                        </label>
                        <input type="date" wire:model.live="end_date" class="form-control shadow-sm border-light" id="end_date">
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-12">
                    <div class="card shadow-lg border-0 rounded-3 bg-gradient">
                        <div class="card-body p-0">
                            <div class="row g-0">
                                <div class="col-md-4 p-4 border-end">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0 me-3">
                                            <div class="p-3 rounded-circle bg-primary bg-opacity-10">
                                                <i class="pli-money-bag text-primary" style="font-size: 2rem"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <h6 class="text-primary mb-0">Total Assets</h6>
                                                <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary px-3">Asset</span>
                                            </div>
                                            <h3 class="mb-0 fw-bold">{{ currency($totalAssets) }}</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 p-4 border-end">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0 me-3">
                                            <div class="p-3 rounded-circle bg-warning bg-opacity-10">
                                                <i class="pli-credit-card text-warning" style="font-size: 2rem"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <h6 class="text-warning mb-0">Total Liabilities</h6>
                                                <span class="badge rounded-pill bg-warning bg-opacity-10 text-warning px-3">Liability</span>
                                            </div>
                                            <h3 class="mb-0 fw-bold">{{ currency($totalLiabilities) }}</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 p-4">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0 me-3">
                                            <div class="p-3 rounded-circle bg-success bg-opacity-10">
                                                <i class="pli-coins text-success" style="font-size: 2rem"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <h6 class="text-success mb-0">Total Equity</h6>
                                                <span class="badge rounded-pill bg-success bg-opacity-10 text-success px-3">Equity</span>
                                            </div>
                                            <h3 class="mb-0 fw-bold">{{ currency($totalEquity) }}</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Balance Sheet Statement -->
            <div class="row g-3">
                <!-- Assets Column -->
                <div class="col-md-4">
                    <div class="card shadow-lg rounded-3 border-0 mb-4 h-100">
                        <div class="card-header bg-gradient bg-primary bg-opacity-10 py-3">
                            <h5 class="mb-0 text-primary">
                                <i class="pli-money-bag me-2"></i>Assets
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Current Assets -->
                            <div class="mb-4">
                                <h6 class="text-primary border-bottom pb-2">Current Assets</h6>
                                @forelse($currentAssets as $asset)
                                    <div class="d-flex justify-content-between py-1">
                                        <span>{{ $asset['account'] }}</span>
                                        <strong>{{ currency($asset['amount']) }}</strong>
                                    </div>
                                @empty
                                    <p class="text-muted mb-0">No current assets found</p>
                                @endforelse
                                <div class="d-flex justify-content-between border-top mt-2 pt-2">
                                    <strong>Total Current Assets</strong>
                                    <strong class="text-primary">{{ currency($totalCurrentAssets) }}</strong>
                                </div>
                            </div>

                            <!-- Fixed Assets -->
                            <div class="mb-4">
                                <h6 class="text-primary border-bottom pb-2">Fixed Assets</h6>
                                @forelse($fixedAssets as $asset)
                                    <div class="d-flex justify-content-between py-1">
                                        <span>{{ $asset['account'] }}</span>
                                        <strong>{{ currency($asset['amount']) }}</strong>
                                    </div>
                                @empty
                                    <p class="text-muted mb-0">No fixed assets found</p>
                                @endforelse
                                <div class="d-flex justify-content-between border-top mt-2 pt-2">
                                    <strong>Total Fixed Assets</strong>
                                    <strong class="text-primary">{{ currency($totalFixedAssets) }}</strong>
                                </div>
                            </div>

                            <!-- Other Assets -->
                            <div class="mb-4">
                                <h6 class="text-primary border-bottom pb-2">Other Assets</h6>
                                @forelse($otherAssets as $asset)
                                    <div class="d-flex justify-content-between py-1">
                                        <span>{{ $asset['account'] }}</span>
                                        <strong>{{ currency($asset['amount']) }}</strong>
                                    </div>
                                @empty
                                    <p class="text-muted mb-0">No other assets found</p>
                                @endforelse
                                <div class="d-flex justify-content-between border-top mt-2 pt-2">
                                    <strong>Total Other Assets</strong>
                                    <strong class="text-primary">{{ currency($totalOtherAssets) }}</strong>
                                </div>
                            </div>

                            <!-- Total Assets -->
                            <div class="d-flex justify-content-between bg-primary bg-opacity-10 p-2 rounded mt-3">
                                <strong>Total Assets</strong>
                                <strong>{{ currency($totalAssets) }}</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Liabilities Column -->
                <div class="col-md-4">
                    <div class="card shadow-lg rounded-3 border-0 mb-4 h-100">
                        <div class="card-header bg-gradient bg-warning bg-opacity-10 py-3">
                            <h5 class="mb-0 text-warning">
                                <i class="pli-credit-card me-2"></i>Liabilities
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Current Liabilities -->
                            <div class="mb-4">
                                <h6 class="text-warning border-bottom pb-2">Current Liabilities</h6>
                                @forelse($currentLiabilities as $liability)
                                    <div class="d-flex justify-content-between py-1">
                                        <span>{{ $liability['account'] }}</span>
                                        <strong>{{ currency($liability['amount']) }}</strong>
                                    </div>
                                @empty
                                    <p class="text-muted mb-0">No current liabilities found</p>
                                @endforelse
                                <div class="d-flex justify-content-between border-top mt-2 pt-2">
                                    <strong>Total Current Liabilities</strong>
                                    <strong class="text-warning">{{ currency($totalCurrentLiabilities) }}</strong>
                                </div>
                            </div>

                            <!-- Long Term Liabilities -->
                            <div class="mb-4">
                                <h6 class="text-warning border-bottom pb-2">Long Term Liabilities</h6>
                                @forelse($longTermLiabilities as $liability)
                                    <div class="d-flex justify-content-between py-1">
                                        <span>{{ $liability['account'] }}</span>
                                        <strong>{{ currency($liability['amount']) }}</strong>
                                    </div>
                                @empty
                                    <p class="text-muted mb-0">No long term liabilities found</p>
                                @endforelse
                                <div class="d-flex justify-content-between border-top mt-2 pt-2">
                                    <strong>Total Long Term Liabilities</strong>
                                    <strong class="text-warning">{{ currency($totalLongTermLiabilities) }}</strong>
                                </div>
                            </div>

                            <!-- Total Liabilities -->
                            <div class="d-flex justify-content-between bg-warning bg-opacity-10 p-2 rounded mt-3">
                                <strong>Total Liabilities</strong>
                                <strong>{{ currency($totalLiabilities) }}</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Equity Column -->
                <div class="col-md-4">
                    <div class="card shadow-lg rounded-3 border-0 mb-4 h-100">
                        <div class="card-header bg-gradient bg-success bg-opacity-10 py-3">
                            <h5 class="mb-0 text-success">
                                <i class="pli-coins me-2"></i>Equity
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Owner's Equity -->
                            <div class="mb-4">
                                <h6 class="text-success border-bottom pb-2">Owner's Equity</h6>
                                @forelse($ownerEquity as $equity)
                                    <div class="d-flex justify-content-between py-1">
                                        <span>{{ $equity['account'] }}</span>
                                        <strong>{{ currency($equity['amount']) }}</strong>
                                    </div>
                                @empty
                                    <p class="text-muted mb-0">No equity accounts found</p>
                                @endforelse
                                <div class="d-flex justify-content-between border-top mt-2 pt-2">
                                    <strong>Total Owner's Equity</strong>
                                    <strong class="text-success">{{ currency($totalEquityAccounts) }}</strong>
                                </div>
                            </div>

                            <!-- Retained Earnings -->
                            <div class="mb-4">
                                <h6 class="text-success border-bottom pb-2">Retained Earnings</h6>
                                @forelse($retainedEarningAccounts as $earning)
                                    <div class="d-flex justify-content-between py-1">
                                        <span>{{ $earning['account'] }}</span>
                                        <strong>{{ currency($earning['amount']) }}</strong>
                                    </div>
                                @empty
                                    <p class="text-muted mb-0">No retained earnings found</p>
                                @endforelse
                                <div class="d-flex justify-content-between border-top mt-2 pt-2">
                                    <strong>Total Retained Earnings</strong>
                                    <strong class="text-success">{{ currency($totalRetainedEarnings) }}</strong>
                                </div>
                            </div>

                            <!-- Total Equity -->
                            <div class="d-flex justify-content-between bg-success bg-opacity-10 p-2 rounded mt-3">
                                <strong>Total Equity</strong>
                                <strong>{{ currency($totalEquity) }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Card -->
            <div class="card shadow-lg rounded-3 border-0 mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Balance Sheet as of {{ \Carbon\Carbon::parse($end_date)->format('d M Y') }}</h6>
                            <p class="mb-0 text-muted">Total Assets = Total Liabilities + Total Equity</p>
                        </div>
                        <div class="text-end">
                            <h4 class="mb-0 {{ $totalAssets === $totalLiabilities + $totalEquity ? 'text-success' : 'text-danger' }}">
                                {{ $totalAssets === $totalLiabilities + $totalEquity ? 'Balanced' : 'Unbalanced' }}
                            </h4>
                            <small class="text-muted">{{ currency($totalAssets) }} = {{ currency($totalLiabilities) }} + {{ currency($totalEquity) }}</small>
                        </div>
                    </div>
                </div>
            </div>

            @if ($totalAssets !== $totalLiabilities + $totalEquity)
                <div class="alert alert-danger shadow-sm border-0 rounded-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="pli-warning-triangle display-6 text-danger me-3"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="alert-heading mb-1">Balance Sheet Mismatch</h5>
                            <p class="mb-0">The balance sheet is not balanced. There is a difference of <strong>{{ currency(abs($totalAssets - ($totalLiabilities + $totalEquity))) }}</strong>
                                between Assets and Liabilities + Equity. Please review the entries for potential errors.</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

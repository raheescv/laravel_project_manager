<div>
    <div class="card shadow-sm">
        <div class="card-body">
            <!-- Filter Section -->
            <div class="row mb-4 g-3">
                <div class="col-md-3">
                    <div class="form-group" wire:ignore>
                        <label for="branch_id" class="form-label fw-bold text-secondary mb-2">
                            <i class="pli-building me-1"></i>Branch
                        </label>
                        {{ html()->select('branch_id', [session('branch_id') => session('branch_name')])->value(session('branch_id'))->class('select-branch_id-list')->id('branch_id')->placeholder('Select branch') }}
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
                        <label for="end_date" class="form-label fw-bold text-secondary mb-2">
                            <i class="pli-calendar-4 me-1"></i>As Of
                        </label>
                        <input type="date" wire:model.live="end_date" class="form-control shadow-sm border-light" id="end_date">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="form-label fw-bold text-secondary mb-2">
                            <i class="pli-user me-1"></i>Account Display Options
                        </label>
                        <div class="d-flex flex-column gap-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" wire:model.live="excludeCustomers" id="excludeCustomers">
                                <label class="form-check-label" for="excludeCustomers">
                                    Exclude Customer Accounts
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" wire:model.live="excludeVendors" id="excludeVendors">
                                <label class="form-check-label" for="excludeVendors">
                                    Exclude Vendor Accounts
                                </label>
                            </div>
                        </div>
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
                                            <h3 class="mb-0 fw-bold">{{ currency(abs($totalAssets)) }}</h3>
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
                                            <h3 class="mb-0 fw-bold">{{ currency(abs($totalLiabilities)) }}</h3>
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
                                            <h3 class="mb-0 fw-bold">{{ currency(abs($totalEquity)) }}</h3>
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
                            @if(count($currentAssets) > 0)
                            <div class="mb-4">
                                <h6 class="text-primary border-bottom pb-2">Current Assets</h6>
                                @foreach($currentAssets as $category)
                                    @if(isset($category['name']))
                                        {{-- Account Head/Category --}}
                                        <div class="mt-3 mb-2">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <strong class="text-primary">{{ $category['name'] }}</strong>
                                                <strong class="text-primary">{{ currency(abs($category['total'] ?? 0)) }}</strong>
                                            </div>

                                            {{-- Direct Accounts under Category --}}
                                            @if(!empty($category['directAccounts']))
                                                @foreach($category['directAccounts'] as $account)
                                                    <div class="d-flex justify-content-between py-1 ps-3">
                                                        <span class="text-muted">
                                                            <a target="_blank" href="{{ route('account::view', $account['id']) }}?from_date=&to_date={{ $end_date }}"
                                                                class="text-decoration-none text-muted hover:text-primary">{{ $account['name'] }}</a>
                                                        </span>
                                                        <strong class="text-end">{{ currency(abs($account['amount'])) }}</strong>
                                                    </div>
                                                @endforeach
                                            @endif

                                            {{-- Groups (Sub-Categories) under Category --}}
                                            @if(!empty($category['groups']))
                                                @foreach($category['groups'] as $group)
                                                    <div class="mt-2 mb-1">
                                                        <div class="d-flex justify-content-between align-items-center ps-3">
                                                            <strong class="text-secondary small">{{ $group['name'] }}</strong>
                                                            <strong class="text-secondary small">{{ currency(abs($group['total'] ?? 0)) }}</strong>
                                                        </div>

                                                        {{-- Accounts under Group --}}
                                                        @if(!empty($group['accounts']))
                                                            @foreach($group['accounts'] as $account)
                                                                <div class="d-flex justify-content-between py-1 ps-5">
                                                                    <span class="text-muted">
                                                                        <a target="_blank" href="{{ route('account::view', $account['id']) }}?from_date=&to_date={{ $end_date }}"
                                                                            class="text-decoration-none text-muted hover:text-primary">{{ $account['name'] }}</a>
                                                                    </span>
                                                                    <strong class="text-end">{{ currency(abs($account['amount'])) }}</strong>
                                                                </div>
                                                            @endforeach
                                                        @endif
                                                    </div>
                                                @endforeach
                                            @endif
                                        </div>
                                    @endif
                                @endforeach
                                <div class="d-flex justify-content-between border-top mt-2 pt-2">
                                    <strong>Total Current Assets</strong>
                                    <strong class="text-primary text-end">{{ currency(abs($totalCurrentAssets)) }}</strong>
                                </div>
                            </div>
                            @endif

                            <!-- Fixed Assets -->
                            @if(count($fixedAssets) > 0)
                            <div class="mb-4">
                                <h6 class="text-primary border-bottom pb-2">Fixed Assets</h6>
                                @foreach($fixedAssets as $category)
                                    @if(isset($category['name']))
                                        {{-- Account Head/Category --}}
                                        <div class="mt-3 mb-2">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <strong class="text-primary">{{ $category['name'] }}</strong>
                                                <strong class="text-primary">{{ currency(abs($category['total'] ?? 0)) }}</strong>
                                            </div>

                                            {{-- Direct Accounts under Category --}}
                                            @if(!empty($category['directAccounts']))
                                                @foreach($category['directAccounts'] as $account)
                                                    <div class="d-flex justify-content-between py-1 ps-3">
                                                        <span class="text-muted">
                                                            <a target="_blank" href="{{ route('account::view', $account['id']) }}?from_date=&to_date={{ $end_date }}"
                                                                class="text-decoration-none text-muted hover:text-primary">{{ $account['name'] }}</a>
                                                        </span>
                                                        <strong class="text-end">{{ currency(abs($account['amount'])) }}</strong>
                                                    </div>
                                                @endforeach
                                            @endif

                                            {{-- Groups (Sub-Categories) under Category --}}
                                            @if(!empty($category['groups']))
                                                @foreach($category['groups'] as $group)
                                                    <div class="mt-2 mb-1">
                                                        <div class="d-flex justify-content-between align-items-center ps-3">
                                                            <strong class="text-secondary small">{{ $group['name'] }}</strong>
                                                            <strong class="text-secondary small">{{ currency(abs($group['total'] ?? 0)) }}</strong>
                                                        </div>

                                                        {{-- Accounts under Group --}}
                                                        @if(!empty($group['accounts']))
                                                            @foreach($group['accounts'] as $account)
                                                                <div class="d-flex justify-content-between py-1 ps-5">
                                                                    <span class="text-muted">
                                                                        <a target="_blank" href="{{ route('account::view', $account['id']) }}?from_date=&to_date={{ $end_date }}"
                                                                            class="text-decoration-none text-muted hover:text-primary">{{ $account['name'] }}</a>
                                                                    </span>
                                                                    <strong class="text-end">{{ currency(abs($account['amount'])) }}</strong>
                                                                </div>
                                                            @endforeach
                                                        @endif
                                                    </div>
                                                @endforeach
                                            @endif
                                        </div>
                                    @endif
                                @endforeach
                                <div class="d-flex justify-content-between border-top mt-2 pt-2">
                                    <strong>Total Fixed Assets</strong>
                                    <strong class="text-primary text-end">{{ currency(abs($totalFixedAssets)) }}</strong>
                                </div>
                            </div>
                            @endif

                            <!-- Other Assets -->
                            @if(count($otherAssets) > 0)
                            <div class="mb-4">
                                <h6 class="text-primary border-bottom pb-2">Other Assets</h6>
                                @foreach($otherAssets as $category)
                                    @if(isset($category['name']))
                                        {{-- Account Head/Category --}}
                                        <div class="mt-3 mb-2">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <strong class="text-primary">{{ $category['name'] }}</strong>
                                                <strong class="text-primary">{{ currency(abs($category['total'] ?? 0)) }}</strong>
                                            </div>

                                            {{-- Direct Accounts under Category --}}
                                            @if(!empty($category['directAccounts']))
                                                @foreach($category['directAccounts'] as $account)
                                                    <div class="d-flex justify-content-between py-1 ps-3">
                                                        <span class="text-muted">
                                                            <a target="_blank" href="{{ route('account::view', $account['id']) }}?from_date=&to_date={{ $end_date }}"
                                                                class="text-decoration-none text-muted hover:text-primary">{{ $account['name'] }}</a>
                                                        </span>
                                                        <strong class="text-end">{{ currency(abs($account['amount'])) }}</strong>
                                                    </div>
                                                @endforeach
                                            @endif

                                            {{-- Groups (Sub-Categories) under Category --}}
                                            @if(!empty($category['groups']))
                                                @foreach($category['groups'] as $group)
                                                    <div class="mt-2 mb-1">
                                                        <div class="d-flex justify-content-between align-items-center ps-3">
                                                            <strong class="text-secondary small">{{ $group['name'] }}</strong>
                                                            <strong class="text-secondary small">{{ currency(abs($group['total'] ?? 0)) }}</strong>
                                                        </div>

                                                        {{-- Accounts under Group --}}
                                                        @if(!empty($group['accounts']))
                                                            @foreach($group['accounts'] as $account)
                                                                <div class="d-flex justify-content-between py-1 ps-5">
                                                                    <span class="text-muted">
                                                                        <a target="_blank" href="{{ route('account::view', $account['id']) }}?from_date=&to_date={{ $end_date }}"
                                                                            class="text-decoration-none text-muted hover:text-primary">{{ $account['name'] }}</a>
                                                                    </span>
                                                                    <strong class="text-end">{{ currency(abs($account['amount'])) }}</strong>
                                                                </div>
                                                            @endforeach
                                                        @endif
                                                    </div>
                                                @endforeach
                                            @endif
                                        </div>
                                    @endif
                                @endforeach
                                <div class="d-flex justify-content-between border-top mt-2 pt-2">
                                    <strong>Total Other Assets</strong>
                                    <strong class="text-primary text-end">{{ currency(abs($totalOtherAssets)) }}</strong>
                                </div>
                            </div>
                            @endif

                            <!-- Total Assets -->
                            <div class="d-flex justify-content-between bg-primary bg-opacity-10 p-3 rounded mt-3">
                                <strong class="fs-5">Total Assets</strong>
                                <strong class="fs-5 text-end">{{ currency(abs($totalAssets)) }}</strong>
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
                            @if(count($currentLiabilities) > 0)
                            <div class="mb-4">
                                <h6 class="text-warning border-bottom pb-2">Current Liabilities</h6>
                                @foreach($currentLiabilities as $category)
                                    @if(isset($category['name']))
                                        {{-- Account Head/Category --}}
                                        <div class="mt-3 mb-2">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <strong class="text-warning">{{ $category['name'] }}</strong>
                                                <strong class="text-warning">{{ currency(abs($category['total'] ?? 0)) }}</strong>
                                            </div>

                                            {{-- Direct Accounts under Category --}}
                                            @if(!empty($category['directAccounts']))
                                                @foreach($category['directAccounts'] as $account)
                                                    <div class="d-flex justify-content-between py-1 ps-3">
                                                        <span class="text-muted">
                                                            <a target="_blank" href="{{ route('account::view', $account['id']) }}?from_date=&to_date={{ $end_date }}"
                                                                class="text-decoration-none text-muted hover:text-warning">{{ $account['name'] }}</a>
                                                        </span>
                                                        <strong class="text-end">{{ currency(abs($account['amount'])) }}</strong>
                                                    </div>
                                                @endforeach
                                            @endif

                                            {{-- Groups (Sub-Categories) under Category --}}
                                            @if(!empty($category['groups']))
                                                @foreach($category['groups'] as $group)
                                                    <div class="mt-2 mb-1">
                                                        <div class="d-flex justify-content-between align-items-center ps-3">
                                                            <strong class="text-secondary small">{{ $group['name'] }}</strong>
                                                            <strong class="text-secondary small">{{ currency(abs($group['total'] ?? 0)) }}</strong>
                                                        </div>

                                                        {{-- Accounts under Group --}}
                                                        @if(!empty($group['accounts']))
                                                            @foreach($group['accounts'] as $account)
                                                                <div class="d-flex justify-content-between py-1 ps-5">
                                                                    <span class="text-muted">
                                                                        <a target="_blank" href="{{ route('account::view', $account['id']) }}?from_date=&to_date={{ $end_date }}"
                                                                            class="text-decoration-none text-muted hover:text-warning">{{ $account['name'] }}</a>
                                                                    </span>
                                                                    <strong class="text-end">{{ currency(abs($account['amount'])) }}</strong>
                                                                </div>
                                                            @endforeach
                                                        @endif
                                                    </div>
                                                @endforeach
                                            @endif
                                        </div>
                                    @endif
                                @endforeach
                                <div class="d-flex justify-content-between border-top mt-2 pt-2">
                                    <strong>Total Current Liabilities</strong>
                                    <strong class="text-warning text-end">{{ currency(abs($totalCurrentLiabilities)) }}</strong>
                                </div>
                            </div>
                            @endif

                            <!-- Long Term Liabilities -->
                            @if(count($longTermLiabilities) > 0)
                            <div class="mb-4">
                                <h6 class="text-warning border-bottom pb-2">Long Term Liabilities</h6>
                                @foreach($longTermLiabilities as $category)
                                    @if(isset($category['name']))
                                        {{-- Account Head/Category --}}
                                        <div class="mt-3 mb-2">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <strong class="text-warning">{{ $category['name'] }}</strong>
                                                <strong class="text-warning">{{ currency(abs($category['total'] ?? 0)) }}</strong>
                                            </div>

                                            {{-- Direct Accounts under Category --}}
                                            @if(!empty($category['directAccounts']))
                                                @foreach($category['directAccounts'] as $account)
                                                    <div class="d-flex justify-content-between py-1 ps-3">
                                                        <span class="text-muted">
                                                            <a target="_blank" href="{{ route('account::view', $account['id']) }}?from_date=&to_date={{ $end_date }}"
                                                                class="text-decoration-none text-muted hover:text-warning">{{ $account['name'] }}</a>
                                                        </span>
                                                        <strong class="text-end">{{ currency(abs($account['amount'])) }}</strong>
                                                    </div>
                                                @endforeach
                                            @endif

                                            {{-- Groups (Sub-Categories) under Category --}}
                                            @if(!empty($category['groups']))
                                                @foreach($category['groups'] as $group)
                                                    <div class="mt-2 mb-1">
                                                        <div class="d-flex justify-content-between align-items-center ps-3">
                                                            <strong class="text-secondary small">{{ $group['name'] }}</strong>
                                                            <strong class="text-secondary small">{{ currency(abs($group['total'] ?? 0)) }}</strong>
                                                        </div>

                                                        {{-- Accounts under Group --}}
                                                        @if(!empty($group['accounts']))
                                                            @foreach($group['accounts'] as $account)
                                                                <div class="d-flex justify-content-between py-1 ps-5">
                                                                    <span class="text-muted">
                                                                        <a target="_blank" href="{{ route('account::view', $account['id']) }}?from_date=&to_date={{ $end_date }}"
                                                                            class="text-decoration-none text-muted hover:text-warning">{{ $account['name'] }}</a>
                                                                    </span>
                                                                    <strong class="text-end">{{ currency(abs($account['amount'])) }}</strong>
                                                                </div>
                                                            @endforeach
                                                        @endif
                                                    </div>
                                                @endforeach
                                            @endif
                                        </div>
                                    @endif
                                @endforeach
                                <div class="d-flex justify-content-between border-top mt-2 pt-2">
                                    <strong>Total Long Term Liabilities</strong>
                                    <strong class="text-warning text-end">{{ currency(abs($totalLongTermLiabilities)) }}</strong>
                                </div>
                            </div>
                            @endif

                            <!-- Total Liabilities -->
                            <div class="d-flex justify-content-between bg-warning bg-opacity-10 p-3 rounded mt-3">
                                <strong class="fs-5">Total Liabilities</strong>
                                <strong class="fs-5 text-end">{{ currency(abs($totalLiabilities)) }}</strong>
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
                            @if(count($ownerEquity) > 0)
                            <div class="mb-4">
                                <h6 class="text-success border-bottom pb-2">Owner's Equity</h6>
                                @foreach($ownerEquity as $category)
                                    @if(isset($category['name']))
                                        {{-- Account Head/Category --}}
                                        <div class="mt-3 mb-2">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <strong class="text-success">{{ $category['name'] }}</strong>
                                                <strong class="text-success">{{ currency(abs($category['total'] ?? 0)) }}</strong>
                                            </div>

                                            {{-- Direct Accounts under Category --}}
                                            @if(!empty($category['directAccounts']))
                                                @foreach($category['directAccounts'] as $account)
                                                    <div class="d-flex justify-content-between py-1 ps-3">
                                                        <span class="text-muted">
                                                            <a target="_blank" href="{{ route('account::view', $account['id']) }}?from_date=&to_date={{ $end_date }}"
                                                                class="text-decoration-none text-muted hover:text-success">{{ $account['name'] }}</a>
                                                        </span>
                                                        <strong class="text-end">{{ currency(abs($account['amount'])) }}</strong>
                                                    </div>
                                                @endforeach
                                            @endif

                                            {{-- Groups (Sub-Categories) under Category --}}
                                            @if(!empty($category['groups']))
                                                @foreach($category['groups'] as $group)
                                                    <div class="mt-2 mb-1">
                                                        <div class="d-flex justify-content-between align-items-center ps-3">
                                                            <strong class="text-secondary small">{{ $group['name'] }}</strong>
                                                            <strong class="text-secondary small">{{ currency(abs($group['total'] ?? 0)) }}</strong>
                                                        </div>

                                                        {{-- Accounts under Group --}}
                                                        @if(!empty($group['accounts']))
                                                            @foreach($group['accounts'] as $account)
                                                                <div class="d-flex justify-content-between py-1 ps-5">
                                                                    <span class="text-muted">
                                                                        <a target="_blank" href="{{ route('account::view', $account['id']) }}?from_date=&to_date={{ $end_date }}"
                                                                            class="text-decoration-none text-muted hover:text-success">{{ $account['name'] }}</a>
                                                                    </span>
                                                                    <strong class="text-end">{{ currency(abs($account['amount'])) }}</strong>
                                                                </div>
                                                            @endforeach
                                                        @endif
                                                    </div>
                                                @endforeach
                                            @endif
                                        </div>
                                    @endif
                                @endforeach
                                <div class="d-flex justify-content-between border-top mt-2 pt-2">
                                    <strong>Total Owner's Equity</strong>
                                    <strong class="text-success text-end">{{ currency(abs($totalEquityAccounts)) }}</strong>
                                </div>
                            </div>
                            @endif

                            <!-- Retained Earnings -->
                            @if(count($retainedEarningAccounts) > 0)
                            <div class="mb-4">
                                <h6 class="text-success border-bottom pb-2">Retained Earnings</h6>
                                @foreach($retainedEarningAccounts as $category)
                                    @if(isset($category['name']))
                                        {{-- Account Head/Category --}}
                                        <div class="mt-3 mb-2">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <strong class="text-success">{{ $category['name'] }}</strong>
                                                <strong class="text-success">{{ currency(abs($category['total'] ?? 0)) }}</strong>
                                            </div>

                                            {{-- Direct Accounts under Category --}}
                                            @if(!empty($category['directAccounts']))
                                                @foreach($category['directAccounts'] as $account)
                                                    <div class="d-flex justify-content-between py-1 ps-3">
                                                        <span class="text-muted">
                                                            <a target="_blank" href="{{ route('account::view', $account['id']) }}?from_date=&to_date={{ $end_date }}"
                                                                class="text-decoration-none text-muted hover:text-success">{{ $account['name'] }}</a>
                                                        </span>
                                                        <strong class="text-end">{{ currency(abs($account['amount'])) }}</strong>
                                                    </div>
                                                @endforeach
                                            @endif

                                            {{-- Groups (Sub-Categories) under Category --}}
                                            @if(!empty($category['groups']))
                                                @foreach($category['groups'] as $group)
                                                    <div class="mt-2 mb-1">
                                                        <div class="d-flex justify-content-between align-items-center ps-3">
                                                            <strong class="text-secondary small">{{ $group['name'] }}</strong>
                                                            <strong class="text-secondary small">{{ currency(abs($group['total'] ?? 0)) }}</strong>
                                                        </div>

                                                        {{-- Accounts under Group --}}
                                                        @if(!empty($group['accounts']))
                                                            @foreach($group['accounts'] as $account)
                                                                <div class="d-flex justify-content-between py-1 ps-5">
                                                                    <span class="text-muted">
                                                                        <a target="_blank" href="{{ route('account::view', $account['id']) }}?from_date=&to_date={{ $end_date }}"
                                                                            class="text-decoration-none text-muted hover:text-success">{{ $account['name'] }}</a>
                                                                    </span>
                                                                    <strong class="text-end">{{ currency(abs($account['amount'])) }}</strong>
                                                                </div>
                                                            @endforeach
                                                        @endif
                                                    </div>
                                                @endforeach
                                            @endif
                                        </div>
                                    @endif
                                @endforeach
                                <div class="d-flex justify-content-between border-top mt-2 pt-2">
                                    <strong>Total Retained Earnings</strong>
                                    <strong class="text-success text-end">{{ currency(abs($totalRetainedEarnings)) }}</strong>
                                </div>
                            </div>
                            @endif

                            <!-- Total Equity -->
                            <div class="d-flex justify-content-between bg-success bg-opacity-10 p-3 rounded mt-3">
                                <strong class="fs-5">Total Equity</strong>
                                <strong class="fs-5 text-end">{{ currency(abs($totalEquity)) }}</strong>
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
                            @php
                                $difference = abs($totalAssets - ($totalLiabilities + $totalEquity));
                                $isBalanced = $difference < 0.01; // Allow for rounding differences
                            @endphp
                            <h4 class="mb-0 {{ $isBalanced ? 'text-success' : 'text-danger' }}">
                                {{ $isBalanced ? '✓ Balanced' : '✗ Unbalanced' }}
                            </h4>
                            <small class="text-muted">
                                {{ currency(abs($totalAssets)) }} = {{ currency(abs($totalLiabilities)) }} + {{ currency(abs($totalEquity)) }}
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            @php
                $difference = abs($totalAssets - ($totalLiabilities + $totalEquity));
                $isBalanced = $difference < 0.01;
            @endphp

            @if (!$isBalanced)
                <div class="alert alert-danger shadow-sm border-0 rounded-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="pli-warning-triangle display-6 text-danger me-3"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="alert-heading mb-1">Balance Sheet Mismatch</h5>
                            <p class="mb-0">
                                The balance sheet is not balanced. There is a difference of
                                <strong>{{ currency($difference) }}</strong> between Assets and Liabilities + Equity.
                                Please review the entries for potential errors.
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#branch_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('branch_id', value);
                });
            });
        </script>
    @endpush
</div>

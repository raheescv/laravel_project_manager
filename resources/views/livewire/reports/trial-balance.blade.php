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
                                <div class="col-md-6 p-4 border-end">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0 me-3">
                                            <div class="p-3 rounded-circle bg-primary bg-opacity-10">
                                                <i class="pli-arrow-up text-primary" style="font-size: 2rem"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <h6 class="text-primary mb-0">Total Debit</h6>
                                                <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary px-3">DR</span>
                                            </div>
                                            <h3 class="mb-0 fw-bold">{{ currency($totalDebit) }}</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 p-4">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0 me-3">
                                            <div class="p-3 rounded-circle bg-success bg-opacity-10">
                                                <i class="pli-arrow-down text-success" style="font-size: 2rem"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <h6 class="text-success mb-0">Total Credit</h6>
                                                <span class="badge rounded-pill bg-success bg-opacity-10 text-success px-3">CR</span>
                                            </div>
                                            <h3 class="mb-0 fw-bold">{{ currency($totalCredit) }}</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Trial Balance Statement -->
            <div class="card shadow-lg rounded-3 border-0 mb-4">
                <div class="card-header bg-gradient bg-primary bg-opacity-10 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 text-primary">
                                <i class="pli-file-text me-2"></i>Trial Balance Statement
                            </h5>
                            <small class="text-muted">
                                <i class="pli-calendar me-1"></i>
                                {{ \Carbon\Carbon::parse($start_date)->format('d M Y') }} -
                                {{ \Carbon\Carbon::parse($end_date)->format('d M Y') }}
                            </small>
                        </div>
                        <div class="text-end">
                            <small class="d-block text-muted mb-1">Statement Balance</small>
                            <h4 class="mb-0 {{ $totalDebit === $totalCredit ? 'text-success' : 'text-danger' }}">
                                {{ $totalDebit === $totalCredit ? 'Balanced' : 'Unbalanced' }}
                            </h4>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr class="bg-light">
                                    <th style="width: 50%">Account</th>
                                    <th class="text-end" style="width: 25%">Debit</th>
                                    <th class="text-end" style="width: 25%">Credit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Asset Accounts -->
                                @if (collect($debitAccounts)->filter(fn($account) => str_contains(strtolower($account['account']), 'asset'))->count() > 0)
                                    <tr class="bg-light">
                                        <th colspan="3" class="text-primary">
                                            <i class="pli-money-bag me-2"></i>Assets
                                        </th>
                                    </tr>
                                    @foreach ($debitAccounts as $account)
                                        @if (str_contains(strtolower($account['account']), 'asset'))
                                            <tr class="border-start border-3 border-primary">
                                                <td class="ps-4">{{ $account['account'] }}</td>
                                                <td class="text-end">{{ currency($account['debit']) }}</td>
                                                <td class="text-end">{{ currency($account['credit']) }}</td>
                                            </tr>
                                        @endif
                                    @endforeach
                                @endif

                                <!-- Expense Accounts -->
                                @if (collect($debitAccounts)->filter(fn($account) => str_contains(strtolower($account['account']), 'expense'))->count() > 0)
                                    <tr class="bg-light">
                                        <th colspan="3" class="text-danger">
                                            <i class="pli-receipt me-2"></i>Expenses
                                        </th>
                                    </tr>
                                    @foreach ($debitAccounts as $account)
                                        @if (str_contains(strtolower($account['account']), 'expense'))
                                            <tr class="border-start border-3 border-danger">
                                                <td class="ps-4">{{ $account['account'] }}</td>
                                                <td class="text-end">{{ currency($account['debit']) }}</td>
                                                <td class="text-end">{{ currency($account['credit']) }}</td>
                                            </tr>
                                        @endif
                                    @endforeach
                                @endif

                                <!-- Liability Accounts -->
                                @if (collect($creditAccounts)->filter(fn($account) => str_contains(strtolower($account['account']), 'liability'))->count() > 0)
                                    <tr class="bg-light">
                                        <th colspan="3" class="text-warning">
                                            <i class="pli-credit-card me-2"></i>Liabilities
                                        </th>
                                    </tr>
                                    @foreach ($creditAccounts as $account)
                                        @if (str_contains(strtolower($account['account']), 'liability'))
                                            <tr class="border-start border-3 border-warning">
                                                <td class="ps-4">{{ $account['account'] }}</td>
                                                <td class="text-end">{{ currency($account['debit']) }}</td>
                                                <td class="text-end">{{ currency($account['credit']) }}</td>
                                            </tr>
                                        @endif
                                    @endforeach
                                @endif

                                <!-- Income Accounts -->
                                @if (collect($creditAccounts)->filter(fn($account) => str_contains(strtolower($account['account']), 'income'))->count() > 0)
                                    <tr class="bg-light">
                                        <th colspan="3" class="text-success">
                                            <i class="pli-coins me-2"></i>Income
                                        </th>
                                    </tr>
                                    @foreach ($creditAccounts as $account)
                                        @if (str_contains(strtolower($account['account']), 'income'))
                                            <tr class="border-start border-3 border-success">
                                                <td class="ps-4">{{ $account['account'] }}</td>
                                                <td class="text-end">{{ currency($account['debit']) }}</td>
                                                <td class="text-end">{{ currency($account['credit']) }}</td>
                                            </tr>
                                        @endif
                                    @endforeach
                                @endif

                                <!-- Other Accounts -->
                                @php
                                    $otherDebitAccounts = collect($debitAccounts)->filter(
                                        fn($account) => !str_contains(strtolower($account['account']), 'asset') && !str_contains(strtolower($account['account']), 'expense'),
                                    );
                                    $otherCreditAccounts = collect($creditAccounts)->filter(
                                        fn($account) => !str_contains(strtolower($account['account']), 'liability') && !str_contains(strtolower($account['account']), 'income'),
                                    );
                                @endphp

                                @if ($otherDebitAccounts->count() > 0 || $otherCreditAccounts->count() > 0)
                                    <tr class="bg-light">
                                        <th colspan="3" class="text-secondary">
                                            <i class="pli-folder me-2"></i>Other Accounts
                                        </th>
                                    </tr>
                                    @foreach ($otherDebitAccounts as $account)
                                        <tr class="border-start border-3 border-secondary">
                                            <td class="ps-4">{{ $account['account'] }}</td>
                                            <td class="text-end">{{ currency($account['debit']) }}</td>
                                            <td class="text-end">{{ currency($account['credit']) }}</td>
                                        </tr>
                                    @endforeach
                                    @foreach ($otherCreditAccounts as $account)
                                        <tr class="border-start border-3 border-secondary">
                                            <td class="ps-4">{{ $account['account'] }}</td>
                                            <td class="text-end">{{ currency($account['debit']) }}</td>
                                            <td class="text-end">{{ currency($account['credit']) }}</td>
                                        </tr>
                                    @endforeach
                                @endif

                                <!-- Totals -->
                                <tr class="bg-primary bg-opacity-10 fw-bold">
                                    <td>Total</td>
                                    <td class="text-end">{{ currency($totalDebit) }}</td>
                                    <td class="text-end">{{ currency($totalCredit) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if ($totalDebit !== $totalCredit)
                <div class="alert alert-danger shadow-sm border-0 rounded-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="pli-warning-triangle display-6 text-danger me-3"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="alert-heading mb-1">Trial Balance Mismatch</h5>
                            <p class="mb-0">The trial balance is not balanced. There is a difference of <strong>{{ currency(abs($totalDebit - $totalCredit)) }}</strong>. Please review the entries
                                for potential errors.</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<div>
    {{-- Loading Bar --}}
    <div wire:loading.delay class="position-fixed top-0 start-0 w-100" style="z-index: 1060; height: 3px;">
        <div class="bg-primary h-100 is-loading-bar"></div>
    </div>

    {{-- Filters --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="row g-3 align-items-end">
                <div class="col-lg-2 col-md-4">
                    <label for="branch_id" class="form-label small text-muted mb-1">Branch</label>
                    <div wire:ignore>
                        {{ html()->select('branch_id', [session('branch_id') => session('branch_name')])->value(session('branch_id'))->class('select-branch_id-list')->id('branch_id')->placeholder('All Branches') }}
                    </div>
                </div>
                <div class="col-lg-2 col-md-4">
                    <label for="period" class="form-label small text-muted mb-1">Period</label>
                    <select wire:model.live="period" class="form-select" id="period">
                        <option value="monthly">Current Month</option>
                        <option value="quarterly">Current Quarter</option>
                        <option value="yearly">Current Year</option>
                        <option value="previous_month">Previous Month</option>
                    </select>
                </div>
                <div class="col-lg-2 col-md-4">
                    <label for="start_date" class="form-label small text-muted mb-1">From</label>
                    <input type="date" wire:model.live="start_date" class="form-control" id="start_date">
                </div>
                <div class="col-lg-2 col-md-4">
                    <label for="end_date" class="form-label small text-muted mb-1">To</label>
                    <input type="date" wire:model.live="end_date" class="form-control" id="end_date">
                </div>
                <div class="col-lg-4 col-md-8 text-end">
                </div>
            </div>
        </div>
    </div>

    {{-- Summary Cards --}}
    @php
        $isProfit = $netProfit >= 0;
    @endphp
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body py-3 d-flex align-items-center">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3 bg-success bg-opacity-10" style="width: 48px; height: 48px; min-width: 48px;">
                        <i class="pli-arrow-down-2 fs-4 text-success"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Total Income</div>
                        <div class="fs-5 fw-bold text-success">{{ number_format($totalIncome, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body py-3 d-flex align-items-center">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3 bg-danger bg-opacity-10" style="width: 48px; height: 48px; min-width: 48px;">
                        <i class="pli-arrow-up-2 fs-4 text-danger"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Total Expenses</div>
                        <div class="fs-5 fw-bold text-danger">{{ number_format($totalExpense, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body py-3 d-flex align-items-center">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3 bg-{{ $isProfit ? 'primary' : 'warning' }} bg-opacity-10" style="width: 48px; height: 48px; min-width: 48px;">
                        <i class="pli-financial fs-4 text-{{ $isProfit ? 'primary' : 'warning' }}"></i>
                    </div>
                    <div>
                        <div class="small text-muted">{{ $isProfit ? 'Net Profit' : 'Net Loss' }}</div>
                        <div class="fs-5 fw-bold text-{{ $isProfit ? 'primary' : 'warning' }}">{{ number_format(abs($netProfit), 2) }}</div>
                    </div>
                    <span class="badge bg-{{ $isProfit ? 'success' : 'danger' }} bg-opacity-10 text-{{ $isProfit ? 'success' : 'danger' }} ms-auto px-2 py-1">
                        {{ $isProfit ? 'Profit' : 'Loss' }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Income Statement Table --}}
    <div class="card border-0 shadow-sm" x-data="incomeStatementTree()">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0 fw-bold">Profit & Loss Statement</h5>
                    <small class="text-muted">{{ date('M d, Y', strtotime($start_date)) }} &mdash; {{ date('M d, Y', strtotime($end_date)) }}</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="btn-group btn-group-sm" role="group">
                        <button @click="expandAll()" class="btn btn-outline-secondary"><i class="pli-arrow-down-2 me-1"></i>Expand</button>
                        <button @click="collapseAll()" class="btn btn-outline-secondary"><i class="pli-arrow-up-2 me-1"></i>Collapse</button>
                    </div>
                    <div class="vr mx-1"></div>
                    <button wire:click="export" class="btn btn-sm btn-success">
                        <i class="pli-file-excel me-1"></i>Excel
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0" style="font-size: 0.875rem;">
                    <thead>
                        <tr class="bg-light">
                            <th class="border-0 py-2 ps-3" style="width: 50%;">Particulars</th>
                            <th class="border-0 py-2 text-end" style="width: 16.66%;">Debit</th>
                            <th class="border-0 py-2 text-end" style="width: 16.66%;">Credit</th>
                            <th class="border-0 py-2 text-end pe-3" style="width: 16.66%;">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- INCOME SECTION --}}
                        <tr class="bg-light bg-opacity-50" style="border-left: 3px solid var(--bs-success);">
                            <td colspan="4" class="py-2 ps-3">
                                <button @click="toggleSection('income')" class="btn btn-link p-0 text-decoration-none fw-bold d-inline-flex align-items-center text-success">
                                    <i class="pli-arrow-right me-2" :class="{ 'is-rotate': sections.income }" style="transition: transform 0.2s; font-size: 0.75rem;"></i>
                                    Income
                                </button>
                            </td>
                        </tr>

                        @if (!empty($incomeTree))
                            @foreach ($incomeTree as $index => $item)
                                @if ($index === 'uncategorized' && is_array($item))
                                    @foreach ($item as $account)
                                        <tr x-show="sections.income" x-cloak>
                                            <td class="py-1 ps-4">
                                                <a href="{{ route('account::view', $account['id']) }}?from_date={{ $start_date }}&to_date={{ $end_date }}" target="_blank" class="text-decoration-none">{{ $account['name'] }}</a>
                                            </td>
                                            <td class="text-end py-1 text-nowrap">{{ $account['debit'] > 0 ? number_format($account['debit'], 2) : '-' }}</td>
                                            <td class="text-end py-1 text-nowrap">{{ $account['credit'] > 0 ? number_format($account['credit'], 2) : '-' }}</td>
                                            <td class="text-end pe-3 py-1 text-nowrap {{ $account['balance'] < 0 ? 'text-danger' : '' }}">{{ number_format($account['balance'], 2) }}</td>
                                        </tr>
                                    @endforeach
                                @elseif (isset($item['name']))
                                    {{-- Category --}}
                                    @php $itemBalance = $item['balance'] ?? ($item['debit'] - $item['credit']); @endphp
                                    <tr x-show="sections.income" x-cloak class="{{ $itemBalance < 0 ? 'table-danger bg-opacity-25' : '' }}">
                                        <td class="py-2 ps-4">
                                            <button @click="toggle('cat', {{ $item['id'] }})" class="btn btn-link p-0 text-decoration-none text-dark fw-semibold d-inline-flex align-items-center">
                                                <i class="pli-arrow-right me-2" :class="{ 'is-rotate': isOpen('cat', {{ $item['id'] }}) }" style="transition: transform 0.2s; font-size: 0.7rem;"></i>
                                                {{ $item['name'] }}
                                            </button>
                                        </td>
                                        <td class="text-end py-2 fw-semibold text-nowrap">{{ $item['debit'] > 0 ? number_format($item['debit'], 2) : '-' }}</td>
                                        <td class="text-end py-2 fw-semibold text-nowrap">{{ $item['credit'] > 0 ? number_format($item['credit'], 2) : '-' }}</td>
                                        <td class="text-end pe-3 py-2 fw-semibold text-nowrap {{ $itemBalance < 0 ? 'text-danger' : '' }}">{{ number_format($itemBalance, 2) }}</td>
                                    </tr>

                                    {{-- Direct Accounts --}}
                                    @foreach ($item['directAccounts'] ?? [] as $account)
                                        @php $acctBal = $account['balance'] ?? ($account['debit'] - $account['credit']); @endphp
                                        <tr x-show="sections.income && isOpen('cat', {{ $item['id'] }})" x-cloak class="{{ $acctBal < 0 ? 'table-danger bg-opacity-25' : '' }}">
                                            <td class="py-1" style="padding-left: 2.5rem;">
                                                <span class="text-muted me-1" style="font-size: 0.5rem;">&bull;</span>
                                                <a href="{{ route('account::view', $account['id']) }}?from_date={{ $start_date }}&to_date={{ $end_date }}" target="_blank" class="text-decoration-none">{{ $account['name'] }}</a>
                                            </td>
                                            <td class="text-end py-1 text-nowrap">{{ $account['debit'] > 0 ? number_format($account['debit'], 2) : '-' }}</td>
                                            <td class="text-end py-1 text-nowrap">{{ $account['credit'] > 0 ? number_format($account['credit'], 2) : '-' }}</td>
                                            <td class="text-end pe-3 py-1 text-nowrap {{ $acctBal < 0 ? 'text-danger' : '' }}">{{ number_format($acctBal, 2) }}</td>
                                        </tr>
                                    @endforeach

                                    {{-- Groups --}}
                                    @foreach ($item['groups'] ?? [] as $group)
                                        @php $groupBal = $group['balance'] ?? ($group['debit'] - $group['credit']); @endphp
                                        <tr x-show="sections.income && isOpen('cat', {{ $item['id'] }})" x-cloak class="{{ $groupBal < 0 ? 'table-danger bg-opacity-25' : '' }}">
                                            <td class="py-1" style="padding-left: 2.5rem;">
                                                <button @click="toggle('grp', {{ $group['id'] }})" class="btn btn-link p-0 text-decoration-none text-dark d-inline-flex align-items-center" style="font-weight: 500;">
                                                    <i class="pli-arrow-right me-2" :class="{ 'is-rotate': isOpen('grp', {{ $group['id'] }}) }" style="transition: transform 0.2s; font-size: 0.65rem;"></i>
                                                    {{ $group['name'] }}
                                                </button>
                                            </td>
                                            <td class="text-end py-1 text-nowrap" style="font-weight: 500;">{{ $group['debit'] > 0 ? number_format($group['debit'], 2) : '-' }}</td>
                                            <td class="text-end py-1 text-nowrap" style="font-weight: 500;">{{ $group['credit'] > 0 ? number_format($group['credit'], 2) : '-' }}</td>
                                            <td class="text-end pe-3 py-1 text-nowrap {{ $groupBal < 0 ? 'text-danger' : '' }}" style="font-weight: 500;">{{ number_format($groupBal, 2) }}</td>
                                        </tr>
                                        @foreach ($group['accounts'] ?? [] as $account)
                                            @php $acctBal = $account['balance'] ?? ($account['debit'] - $account['credit']); @endphp
                                            <tr x-show="sections.income && isOpen('cat', {{ $item['id'] }}) && isOpen('grp', {{ $group['id'] }})" x-cloak class="{{ $acctBal < 0 ? 'table-danger bg-opacity-25' : '' }}">
                                                <td class="py-1" style="padding-left: 4rem;">
                                                    <span class="text-muted me-1" style="font-size: 0.5rem;">&bull;</span>
                                                    <a href="{{ route('account::view', $account['id']) }}?from_date={{ $start_date }}&to_date={{ $end_date }}" target="_blank" class="text-decoration-none">{{ $account['name'] }}</a>
                                                </td>
                                                <td class="text-end py-1 text-nowrap">{{ $account['debit'] > 0 ? number_format($account['debit'], 2) : '-' }}</td>
                                                <td class="text-end py-1 text-nowrap">{{ $account['credit'] > 0 ? number_format($account['credit'], 2) : '-' }}</td>
                                                <td class="text-end pe-3 py-1 text-nowrap {{ $acctBal < 0 ? 'text-danger' : '' }}">{{ number_format($acctBal, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    @endforeach
                                @endif
                            @endforeach
                        @else
                            <tr x-show="sections.income" x-cloak>
                                <td colspan="4" class="text-center text-muted py-2 fst-italic small">No income accounts found</td>
                            </tr>
                        @endif

                        {{-- Income Total --}}
                        @php $incomeSectionBalance = $totalIncomeDebit - $totalIncomeCredit; @endphp
                        <tr class="border-top {{ $incomeSectionBalance < 0 ? '' : '' }}" x-show="sections.income" x-cloak style="border-left: 3px solid var(--bs-success);">
                            <td class="py-2 ps-3 fw-bold small">Total Income</td>
                            <td class="text-end py-2 fw-bold text-nowrap">{{ number_format($totalIncomeDebit, 2) }}</td>
                            <td class="text-end py-2 fw-bold text-nowrap">{{ number_format($totalIncomeCredit, 2) }}</td>
                            <td class="text-end pe-3 py-2 fw-bold text-nowrap {{ $incomeSectionBalance < 0 ? 'text-danger' : '' }}">{{ number_format($incomeSectionBalance, 2) }}</td>
                        </tr>

                        {{-- EXPENSE SECTION --}}
                        <tr class="bg-light bg-opacity-50" style="border-left: 3px solid var(--bs-danger);">
                            <td colspan="4" class="py-2 ps-3">
                                <button @click="toggleSection('expense')" class="btn btn-link p-0 text-decoration-none fw-bold d-inline-flex align-items-center text-danger">
                                    <i class="pli-arrow-right me-2" :class="{ 'is-rotate': sections.expense }" style="transition: transform 0.2s; font-size: 0.75rem;"></i>
                                    Expenses
                                </button>
                            </td>
                        </tr>

                        @if (!empty($expenseTree))
                            @foreach ($expenseTree as $index => $item)
                                @if ($index === 'uncategorized' && is_array($item))
                                    @foreach ($item as $account)
                                        <tr x-show="sections.expense" x-cloak>
                                            <td class="py-1 ps-4">
                                                <a href="{{ route('account::view', $account['id']) }}?from_date={{ $start_date }}&to_date={{ $end_date }}" target="_blank" class="text-decoration-none">{{ $account['name'] }}</a>
                                            </td>
                                            <td class="text-end py-1 text-nowrap">{{ $account['debit'] > 0 ? number_format($account['debit'], 2) : '-' }}</td>
                                            <td class="text-end py-1 text-nowrap">{{ $account['credit'] > 0 ? number_format($account['credit'], 2) : '-' }}</td>
                                            <td class="text-end pe-3 py-1 text-nowrap {{ $account['balance'] < 0 ? 'text-danger' : '' }}">{{ number_format($account['balance'], 2) }}</td>
                                        </tr>
                                    @endforeach
                                @elseif (isset($item['name']))
                                    @php $itemBalance = $item['balance'] ?? ($item['debit'] - $item['credit']); @endphp
                                    <tr x-show="sections.expense" x-cloak class="{{ $itemBalance < 0 ? 'table-danger bg-opacity-25' : '' }}">
                                        <td class="py-2 ps-4">
                                            <button @click="toggle('cat', {{ $item['id'] }})" class="btn btn-link p-0 text-decoration-none text-dark fw-semibold d-inline-flex align-items-center">
                                                <i class="pli-arrow-right me-2" :class="{ 'is-rotate': isOpen('cat', {{ $item['id'] }}) }" style="transition: transform 0.2s; font-size: 0.7rem;"></i>
                                                {{ $item['name'] }}
                                            </button>
                                        </td>
                                        <td class="text-end py-2 fw-semibold text-nowrap">{{ $item['debit'] > 0 ? number_format($item['debit'], 2) : '-' }}</td>
                                        <td class="text-end py-2 fw-semibold text-nowrap">{{ $item['credit'] > 0 ? number_format($item['credit'], 2) : '-' }}</td>
                                        <td class="text-end pe-3 py-2 fw-semibold text-nowrap {{ $itemBalance < 0 ? 'text-danger' : '' }}">{{ number_format($itemBalance, 2) }}</td>
                                    </tr>

                                    @foreach ($item['directAccounts'] ?? [] as $account)
                                        @php $acctBal = $account['balance'] ?? ($account['debit'] - $account['credit']); @endphp
                                        <tr x-show="sections.expense && isOpen('cat', {{ $item['id'] }})" x-cloak class="{{ $acctBal < 0 ? 'table-danger bg-opacity-25' : '' }}">
                                            <td class="py-1" style="padding-left: 2.5rem;">
                                                <span class="text-muted me-1" style="font-size: 0.5rem;">&bull;</span>
                                                <a href="{{ route('account::view', $account['id']) }}?from_date={{ $start_date }}&to_date={{ $end_date }}" target="_blank" class="text-decoration-none">{{ $account['name'] }}</a>
                                            </td>
                                            <td class="text-end py-1 text-nowrap">{{ $account['debit'] > 0 ? number_format($account['debit'], 2) : '-' }}</td>
                                            <td class="text-end py-1 text-nowrap">{{ $account['credit'] > 0 ? number_format($account['credit'], 2) : '-' }}</td>
                                            <td class="text-end pe-3 py-1 text-nowrap {{ $acctBal < 0 ? 'text-danger' : '' }}">{{ number_format($acctBal, 2) }}</td>
                                        </tr>
                                    @endforeach

                                    @foreach ($item['groups'] ?? [] as $group)
                                        @php $groupBal = $group['balance'] ?? ($group['debit'] - $group['credit']); @endphp
                                        <tr x-show="sections.expense && isOpen('cat', {{ $item['id'] }})" x-cloak class="{{ $groupBal < 0 ? 'table-danger bg-opacity-25' : '' }}">
                                            <td class="py-1" style="padding-left: 2.5rem;">
                                                <button @click="toggle('grp', {{ $group['id'] }})" class="btn btn-link p-0 text-decoration-none text-dark d-inline-flex align-items-center" style="font-weight: 500;">
                                                    <i class="pli-arrow-right me-2" :class="{ 'is-rotate': isOpen('grp', {{ $group['id'] }}) }" style="transition: transform 0.2s; font-size: 0.65rem;"></i>
                                                    {{ $group['name'] }}
                                                </button>
                                            </td>
                                            <td class="text-end py-1 text-nowrap" style="font-weight: 500;">{{ $group['debit'] > 0 ? number_format($group['debit'], 2) : '-' }}</td>
                                            <td class="text-end py-1 text-nowrap" style="font-weight: 500;">{{ $group['credit'] > 0 ? number_format($group['credit'], 2) : '-' }}</td>
                                            <td class="text-end pe-3 py-1 text-nowrap {{ $groupBal < 0 ? 'text-danger' : '' }}" style="font-weight: 500;">{{ number_format($groupBal, 2) }}</td>
                                        </tr>
                                        @foreach ($group['accounts'] ?? [] as $account)
                                            @php $acctBal = $account['balance'] ?? ($account['debit'] - $account['credit']); @endphp
                                            <tr x-show="sections.expense && isOpen('cat', {{ $item['id'] }}) && isOpen('grp', {{ $group['id'] }})" x-cloak class="{{ $acctBal < 0 ? 'table-danger bg-opacity-25' : '' }}">
                                                <td class="py-1" style="padding-left: 4rem;">
                                                    <span class="text-muted me-1" style="font-size: 0.5rem;">&bull;</span>
                                                    <a href="{{ route('account::view', $account['id']) }}?from_date={{ $start_date }}&to_date={{ $end_date }}" target="_blank" class="text-decoration-none">{{ $account['name'] }}</a>
                                                </td>
                                                <td class="text-end py-1 text-nowrap">{{ $account['debit'] > 0 ? number_format($account['debit'], 2) : '-' }}</td>
                                                <td class="text-end py-1 text-nowrap">{{ $account['credit'] > 0 ? number_format($account['credit'], 2) : '-' }}</td>
                                                <td class="text-end pe-3 py-1 text-nowrap {{ $acctBal < 0 ? 'text-danger' : '' }}">{{ number_format($acctBal, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    @endforeach
                                @endif
                            @endforeach
                        @else
                            <tr x-show="sections.expense" x-cloak>
                                <td colspan="4" class="text-center text-muted py-2 fst-italic small">No expense accounts found</td>
                            </tr>
                        @endif

                        {{-- Expense Total --}}
                        @php $expenseSectionBalance = $totalExpenseDebit - $totalExpenseCredit; @endphp
                        <tr class="border-top" x-show="sections.expense" x-cloak style="border-left: 3px solid var(--bs-danger);">
                            <td class="py-2 ps-3 fw-bold small">Total Expenses</td>
                            <td class="text-end py-2 fw-bold text-nowrap">{{ number_format($totalExpenseDebit, 2) }}</td>
                            <td class="text-end py-2 fw-bold text-nowrap">{{ number_format($totalExpenseCredit, 2) }}</td>
                            <td class="text-end pe-3 py-2 fw-bold text-nowrap {{ $expenseSectionBalance < 0 ? 'text-danger' : '' }}">{{ number_format($expenseSectionBalance, 2) }}</td>
                        </tr>

                        {{-- OTHER / UNCATEGORIZED SECTION --}}
                        @if (!empty($otherTree))
                            <tr class="bg-light bg-opacity-50" style="border-left: 3px solid var(--bs-secondary);">
                                <td colspan="4" class="py-2 ps-3">
                                    <button @click="toggleSection('other')" class="btn btn-link p-0 text-decoration-none fw-bold d-inline-flex align-items-center text-secondary">
                                        <i class="pli-arrow-right me-2" :class="{ 'is-rotate': sections.other }" style="transition: transform 0.2s; font-size: 0.75rem;"></i>
                                        Uncategorized
                                    </button>
                                    <span class="badge bg-warning bg-opacity-10 text-warning ms-2" style="font-size: 0.65rem;">Needs classification</span>
                                </td>
                            </tr>
                            @foreach ($otherTree as $account)
                                @php $acctBal = $account['balance'] ?? ($account['debit'] - $account['credit']); @endphp
                                <tr x-show="sections.other" x-cloak>
                                    <td class="py-1 ps-4">
                                        <a href="{{ route('account::view', $account['id']) }}?from_date={{ $start_date }}&to_date={{ $end_date }}" target="_blank" class="text-decoration-none">{{ $account['name'] }}</a>
                                    </td>
                                    <td class="text-end py-1 text-nowrap">{{ $account['debit'] > 0 ? number_format($account['debit'], 2) : '-' }}</td>
                                    <td class="text-end py-1 text-nowrap">{{ $account['credit'] > 0 ? number_format($account['credit'], 2) : '-' }}</td>
                                    <td class="text-end pe-3 py-1 text-nowrap {{ $acctBal < 0 ? 'text-danger' : '' }}">{{ number_format($acctBal, 2) }}</td>
                                </tr>
                            @endforeach
                            @php $otherSectionBalance = $totalOtherDebit - $totalOtherCredit; @endphp
                            <tr class="border-top" x-show="sections.other" x-cloak style="border-left: 3px solid var(--bs-secondary);">
                                <td class="py-2 ps-3 fw-bold small">Total Uncategorized</td>
                                <td class="text-end py-2 fw-bold text-nowrap">{{ number_format($totalOtherDebit, 2) }}</td>
                                <td class="text-end py-2 fw-bold text-nowrap">{{ number_format($totalOtherCredit, 2) }}</td>
                                <td class="text-end pe-3 py-2 fw-bold text-nowrap {{ $otherSectionBalance < 0 ? 'text-danger' : '' }}">{{ number_format($otherSectionBalance, 2) }}</td>
                            </tr>
                        @endif

                        {{-- NET PROFIT/LOSS --}}
                        <tr class="bg-{{ $isProfit ? 'success' : 'danger' }} bg-opacity-10 fw-bold" style="border-top: 2px solid #333;">
                            <td class="py-3 ps-3 fs-6">{{ $isProfit ? 'Net Profit' : 'Net Loss' }}</td>
                            <td class="text-end py-3 fs-6 text-nowrap">{{ number_format($totalDebit, 2) }}</td>
                            <td class="text-end py-3 fs-6 text-nowrap">{{ number_format($totalCredit, 2) }}</td>
                            <td class="text-end pe-3 py-3 fs-6 text-nowrap text-{{ $isProfit ? 'success' : 'danger' }}">{{ number_format($totalDebit - $totalCredit, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function incomeStatementTree() {
                return {
                    sections: { income: true, expense: true, other: true },
                    cats: {},
                    grps: {},

                    toggleSection(s) { this.sections[s] = !this.sections[s]; },
                    toggle(type, id) {
                        const map = type === 'cat' ? this.cats : this.grps;
                        const key = id;
                        map[key] = !map[key];
                        if (type === 'cat') this.cats = Object.assign({}, this.cats);
                        else this.grps = Object.assign({}, this.grps);
                    },
                    isOpen(type, id) {
                        const map = type === 'cat' ? this.cats : this.grps;
                        return type === 'cat' ? map[id] !== false : map[id] === true;
                    },
                    expandAll() {
                        this.sections = { income: true, expense: true, other: true };
                        for (let k in this.cats) this.cats[k] = true;
                        for (let k in this.grps) this.grps[k] = true;
                        this.cats = Object.assign({}, this.cats);
                        this.grps = Object.assign({}, this.grps);
                    },
                    collapseAll() {
                        this.sections = { income: false, expense: false, other: false };
                        for (let k in this.cats) this.cats[k] = false;
                        for (let k in this.grps) this.grps[k] = false;
                        this.cats = Object.assign({}, this.cats);
                        this.grps = Object.assign({}, this.grps);
                    }
                }
            }

            $(document).ready(function() {
                $('#branch_id').on('change', function(e) {
                    @this.set('branch_id', $(this).val() || null);
                });
            });
        </script>
    @endpush

    <style>
        .is-rotate { transform: rotate(90deg); }
        [x-cloak] { display: none !important; }
        .is-loading-bar { animation: is-loading 1.5s ease-in-out infinite; }
        @keyframes is-loading {
            0% { width: 0; margin-left: 0; }
            50% { width: 60%; margin-left: 20%; }
            100% { width: 0; margin-left: 100%; }
        }
        @media print {
            .card-header .btn { display: none !important; }
            [x-cloak] { display: table-row !important; }
            tr[x-show] { display: table-row !important; }
        }
    </style>
</div>

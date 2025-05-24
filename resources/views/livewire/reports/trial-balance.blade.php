<div>
    <div class="card bg-white/30 backdrop-blur-xl shadow-lg border-0 rounded-3">
        <div class="card-body">
            <!-- Filter Section -->
            <div class="row mb-4 g-4">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="branch_id" class="form-label fs-6 text-secondary mb-2 d-flex align-items-center">
                            <i class="pli-building me-2 fs-5"></i>Branch
                        </label>
                        <select wire:model.live="branch_id" class="form-select form-select-lg border-0 shadow-sm bg-light/50 hover:bg-light transition-colors" id="branch_id">
                            <option value="">All Branches</option>
                            @foreach ($branches as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="period" class="form-label fs-6 text-secondary mb-2 d-flex align-items-center">
                            <i class="pli-time-clock me-2 fs-5"></i>Period
                        </label>
                        <select wire:model.live="period" class="form-select form-select-lg border-0 shadow-sm bg-light/50 hover:bg-light transition-colors" id="period">
                            <option value="monthly">Current Month</option>
                            <option value="quarterly">Current Quarter</option>
                            <option value="yearly">Current Year</option>
                            <option value="previous_month">Previous Month</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="start_date" class="form-label fs-6 text-secondary mb-2 d-flex align-items-center">
                            <i class="pli-calendar-4 me-2 fs-5"></i>Start Date
                        </label>
                        <input type="date" wire:model.live="start_date" class="form-control form-control-lg border-0 shadow-sm bg-light/50 hover:bg-light transition-colors" id="start_date">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="end_date" class="form-label fs-6 text-secondary mb-2 d-flex align-items-center">
                            <i class="pli-calendar-4 me-2 fs-5"></i>End Date
                        </label>
                        <input type="date" wire:model.live="end_date" class="form-control form-control-lg border-0 shadow-sm bg-light/50 hover:bg-light transition-colors" id="end_date">
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="row g-4 mb-5">
                <div class="col-md-4">
                    <div class="card h-100 bg-gradient-to-br from-primary/5 to-primary/10 border-0 shadow-lg rounded-3 hover:scale-102 transition-transform">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="text-muted mb-0 d-flex align-items-center">
                                    <i class="pli-money-bag fs-4 me-2"></i>Total Assets
                                </h6>
                                <div class="badge bg-primary/10 text-primary px-3 py-2 rounded-pill">{{ $period }}</div>
                            </div>
                            <h3 class="mb-0 fw-bold">{{ number_format($totalAssets, 2) }}</h3>
                            <div class="text-success-emphasis small mt-2">
                                <i class="pli-arrow-up me-1"></i>
                                <span>5.3% increase from last period</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 bg-gradient-to-br from-info/5 to-info/10 border-0 shadow-lg rounded-3 hover:scale-102 transition-transform">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="text-muted mb-0 d-flex align-items-center">
                                    <i class="pli-financial fs-4 me-2"></i>Total Liabilities
                                </h6>
                                <div class="badge bg-info/10 text-info px-3 py-2 rounded-pill">{{ $period }}</div>
                            </div>
                            <h3 class="mb-0 fw-bold">{{ number_format($totalLiabilities, 2) }}</h3>
                            <div class="text-danger-emphasis small mt-2">
                                <i class="pli-arrow-down me-1"></i>
                                <span>2.1% decrease from last period</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 bg-gradient-to-br from-success/5 to-success/10 border-0 shadow-lg rounded-3 hover:scale-102 transition-transform">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="text-muted mb-0 d-flex align-items-center">
                                    <i class="pli-dollar-sign-2 fs-4 me-2"></i>Net Balance
                                </h6>
                                <div class="badge bg-success/10 text-success px-3 py-2 rounded-pill">{{ $period }}</div>
                            </div>
                            <h3 class="mb-0 fw-bold">{{ number_format($netBalance, 2) }}</h3>
                            <div class="text-success-emphasis small mt-2">
                                <i class="pli-arrow-up me-1"></i>
                                <span>Balanced</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Trial Balance Statement -->
            <div class="card bg-white/40 backdrop-blur-xl shadow-xl border-0 rounded-3">
                <div class="card-header bg-gradient-to-r from-primary/5 to-primary/10 border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-0 fw-bold">Trial Balance Statement</h5>
                            <p class="text-muted small mb-0">
                                {{ date('F d, Y', strtotime($start_date)) }} - {{ date('F d, Y', strtotime($end_date)) }}
                            </p>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-light border-0 shadow-sm hover:bg-light/80 transition-colors" onclick="window.print()">
                                <i class="pli-printer fs-5 me-2"></i>Print
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table  table-sm table-hover align-middle mb-0">
                            <thead class="bg-light/50">
                                <tr>
                                    <th class="border-0 py-3">Account Name</th>
                                    <th class="border-0 py-3 text-end">Debit</th>
                                    <th class="border-0 py-3 text-end pe-4">Credit</th>
                                </tr>
                            </thead>
                            <tbody class="border-top-0">
                                <!-- Assets Section -->
                                <tr class="bg-light/30">
                                    <td colspan="4" class="py-3 ps-4 fw-bold text-primary">Assets</td>
                                </tr>
                                @foreach ($assets ?? [] as $asset)
                                    <tr class="hover:bg-light/40 transition-colors">
                                        <td>{{ $asset->name }}</td>
                                        <td class="text-end">{{ $asset->debit > 0 ? number_format($asset->debit, 2) : '-' }}</td>
                                        <td class="text-end pe-4">{{ $asset->credit > 0 ? number_format($asset->credit, 2) : '-' }}</td>
                                    </tr>
                                @endforeach
                                <tr class="border-top">
                                    <td colspan="1" class="ps-4 fw-bold">Total Assets</td>
                                    <td class="text-end fw-bold">{{ number_format($totalAssetsDebit, 2) }}</td>
                                    <td class="text-end pe-4 fw-bold">{{ number_format($totalAssetsCredit, 2) }}</td>
                                </tr>

                                <!-- Liability Section -->
                                <tr class="bg-light/30">
                                    <td colspan="4" class="py-3 ps-4 fw-bold text-warning">Liabilities</td>
                                </tr>
                                @foreach ($liabilities as $liability)
                                    <tr class="hover:bg-light/40 transition-colors">
                                        <td>{{ $liability->name }}</td>
                                        <td class="text-end">{{ $liability->debit > 0 ? number_format($liability->debit, 2) : '-' }}</td>
                                        <td class="text-end pe-4">{{ $liability->credit > 0 ? number_format($liability->credit, 2) : '-' }}</td>
                                    </tr>
                                @endforeach
                                <tr class="border-top">
                                    <td colspan="1" class="ps-4 fw-bold">Total Liabilities</td>
                                    <td class="text-end fw-bold">{{ number_format($totalLiabilitiesDebit, 2) }}</td>
                                    <td class="text-end pe-4 fw-bold">{{ number_format($totalLiabilitiesCredit, 2) }}</td>
                                </tr>

                                <!-- Equity Section -->
                                <tr class="bg-light/30">
                                    <td colspan="4" class="py-3 ps-4 fw-bold text-success">Equity</td>
                                </tr>
                                @foreach ($equity as $equityItem)
                                    <tr class="hover:bg-light/40 transition-colors">
                                        <td>{{ $equityItem->name }}</td>
                                        <td class="text-end">{{ $equityItem->debit > 0 ? number_format($equityItem->debit, 2) : '-' }}</td>
                                        <td class="text-end pe-4">{{ $equityItem->credit > 0 ? number_format($equityItem->credit, 2) : '-' }}</td>
                                    </tr>
                                @endforeach
                                <tr class="border-top">
                                    <td colspan="1" class="ps-4 fw-bold">Total Equity</td>
                                    <td class="text-end fw-bold">{{ number_format($totalEquityDebit, 2) }}</td>
                                    <td class="text-end pe-4 fw-bold">{{ number_format($totalEquityCredit, 2) }}</td>
                                </tr>

                                <!-- Income Section -->
                                <tr class="bg-light/30">
                                    <td colspan="4" class="py-3 ps-4 fw-bold text-success">Income</td>
                                </tr>
                                @foreach ($income as $incomeItem)
                                    <tr class="hover:bg-light/40 transition-colors">
                                        <td>{{ $incomeItem->name }}</td>
                                        <td class="text-end">{{ $incomeItem->debit > 0 ? number_format($incomeItem->debit, 2) : '-' }}</td>
                                        <td class="text-end pe-4">{{ $incomeItem->credit > 0 ? number_format($incomeItem->credit, 2) : '-' }}</td>
                                    </tr>
                                @endforeach
                                <tr class="border-top">
                                    <td colspan="1" class="ps-4 fw-bold">Total Income</td>
                                    <td class="text-end fw-bold">{{ number_format($totalIncomeDebit, 2) }}</td>
                                    <td class="text-end pe-4 fw-bold">{{ number_format($totalIncomeCredit, 2) }}</td>
                                </tr>

                                <!-- Expense Section -->
                                <tr class="bg-light/30">
                                    <td colspan="4" class="py-3 ps-4 fw-bold text-danger">Expenses</td>
                                </tr>
                                @foreach ($expenses as $expense)
                                    <tr class="hover:bg-light/40 transition-colors">
                                        <td>{{ $expense->name }}</td>
                                        <td class="text-end">{{ $expense->debit > 0 ? number_format($expense->debit, 2) : '-' }}</td>
                                        <td class="text-end pe-4">{{ $expense->credit > 0 ? number_format($expense->credit, 2) : '-' }}</td>
                                    </tr>
                                @endforeach
                                <tr class="border-top">
                                    <td colspan="1" class="ps-4 fw-bold">Total Expenses</td>
                                    <td class="text-end fw-bold">{{ number_format($totalExpensesDebit, 2) }}</td>
                                    <td class="text-end pe-4 fw-bold">{{ number_format($totalExpensesCredit, 2) }}</td>
                                </tr>

                                <!-- Grand Total -->
                                <tr class="bg-primary bg-opacity-10 fw-bold">
                                    <td colspan="1" class="ps-4">Grand Total</td>
                                    <td class="text-end">{{ number_format($totalDebit, 2) }}</td>
                                    <td class="text-end pe-4">{{ number_format($totalCredit, 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @php
                $difference = round($totalDebit - $totalCredit, 2);
            @endphp
            @if (abs($difference) > 0.01)
                <div class="alert alert-danger shadow-sm border-0 rounded-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="pli-warning-triangle display-6 text-danger me-3"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="alert-heading mb-1">Trial Balance Mismatch</h5>
                            <p class="mb-0">
                                The trial balance is not balanced. There is a difference of <strong>{{ number_format(abs($difference), 2) }}</strong>. Please review the entries
                                for potential errors.
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

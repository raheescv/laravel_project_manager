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
                            <button @click="expandAll()" class="btn btn-light border-0 shadow-sm hover:bg-light/80 transition-colors">
                                <i class="pli-arrow-down fs-5 me-2"></i>Expand All
                            </button>
                            <button @click="collapseAll()" class="btn btn-light border-0 shadow-sm hover:bg-light/80 transition-colors">
                                <i class="pli-arrow-up fs-5 me-2"></i>Collapse All
                            </button>
                            <button class="btn btn-light border-0 shadow-sm hover:bg-light/80 transition-colors" onclick="window.print()">
                                <i class="pli-printer fs-5 me-2"></i>Print
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0" x-data="trialBalanceTree()">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
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
                                    <td colspan="3" class="py-3 fw-bold text-primary" style="padding-left: 1rem;">
                                        <button @click="toggleSection('assets')" class="btn btn-link p-0 text-decoration-none text-primary fw-bold d-inline-flex align-items-center">
                                            <i class="pli-arrow-right me-2" :class="{ 'rotate-90': expandedSections.assets }"
                                                style="transition: transform 0.2s; width: 1rem; text-align: center;"></i>
                                            <span>Assets</span>
                                        </button>
                                    </td>
                                </tr>
                                @if (!empty($assetsTree))
                                    @include('livewire.reports.partials.trial-balance-tree', [
                                        'tree' => $assetsTree,
                                        'totalDebit' => $totalAssetsDebit,
                                        'totalCredit' => $totalAssetsCredit,
                                        'sectionName' => 'assets',
                                        'showCondition' => 'expandedSections.assets',
                                    ])
                                @else
                                    @foreach ($assets ?? [] as $asset)
                                        <tr class="hover:bg-light/40 transition-colors" x-show="expandedSections.assets">
                                            <td class="py-1" style="padding-left: 2rem;">{{ $asset->name }}</td>
                                            <td class="text-end py-1">{{ $asset->debit > 0 ? number_format($asset->debit, 2) : '-' }}</td>
                                            <td class="text-end pe-4 py-1">{{ $asset->credit > 0 ? number_format($asset->credit, 2) : '-' }}</td>
                                        </tr>
                                    @endforeach
                                    <tr class="border-top" x-show="expandedSections.assets">
                                        <td class="py-2 fw-bold" style="padding-left: 1rem;">Total Assets</td>
                                        <td class="text-end py-2 fw-bold">{{ number_format($totalAssetsDebit, 2) }}</td>
                                        <td class="text-end pe-4 py-2 fw-bold">{{ number_format($totalAssetsCredit, 2) }}</td>
                                    </tr>
                                @endif

                                <!-- Liability Section -->
                                <tr class="bg-light/30">
                                    <td colspan="3" class="py-3 fw-bold text-warning" style="padding-left: 1rem;">
                                        <button @click="toggleSection('liabilities')" class="btn btn-link p-0 text-decoration-none text-warning fw-bold d-inline-flex align-items-center">
                                            <i class="pli-arrow-right me-2" :class="{ 'rotate-90': expandedSections.liabilities }"
                                                style="transition: transform 0.2s; width: 1rem; text-align: center;"></i>
                                            <span>Liabilities</span>
                                        </button>
                                    </td>
                                </tr>
                                @if (!empty($liabilitiesTree))
                                    @include('livewire.reports.partials.trial-balance-tree', [
                                        'tree' => $liabilitiesTree,
                                        'totalDebit' => $totalLiabilitiesDebit,
                                        'totalCredit' => $totalLiabilitiesCredit,
                                        'sectionName' => 'liabilities',
                                        'showCondition' => 'expandedSections.liabilities',
                                    ])
                                @else
                                    @foreach ($liabilities ?? [] as $liability)
                                        <tr class="hover:bg-light/40 transition-colors" x-show="expandedSections.liabilities">
                                            <td class="py-1" style="padding-left: 2rem;">{{ $liability->name }}</td>
                                            <td class="text-end py-1">{{ $liability->debit > 0 ? number_format($liability->debit, 2) : '-' }}</td>
                                            <td class="text-end pe-4 py-1">{{ $liability->credit > 0 ? number_format($liability->credit, 2) : '-' }}</td>
                                        </tr>
                                    @endforeach
                                    <tr class="border-top" x-show="expandedSections.liabilities">
                                        <td class="py-2 fw-bold" style="padding-left: 1rem;">Total Liabilities</td>
                                        <td class="text-end py-2 fw-bold">{{ number_format($totalLiabilitiesDebit, 2) }}</td>
                                        <td class="text-end pe-4 py-2 fw-bold">{{ number_format($totalLiabilitiesCredit, 2) }}</td>
                                    </tr>
                                @endif

                                <!-- Equity Section -->
                                <tr class="bg-light/30">
                                    <td colspan="3" class="py-3 fw-bold text-success" style="padding-left: 1rem;">
                                        <button @click="toggleSection('equity')" class="btn btn-link p-0 text-decoration-none text-success fw-bold d-inline-flex align-items-center">
                                            <i class="pli-arrow-right me-2" :class="{ 'rotate-90': expandedSections.equity }"
                                                style="transition: transform 0.2s; width: 1rem; text-align: center;"></i>
                                            <span>Equity</span>
                                        </button>
                                    </td>
                                </tr>
                                @if (!empty($equityTree))
                                    @include('livewire.reports.partials.trial-balance-tree', [
                                        'tree' => $equityTree,
                                        'totalDebit' => $totalEquityDebit,
                                        'totalCredit' => $totalEquityCredit,
                                        'sectionName' => 'equity',
                                        'showCondition' => 'expandedSections.equity',
                                    ])
                                @else
                                    @foreach ($equity ?? [] as $equityItem)
                                        <tr class="hover:bg-light/40 transition-colors" x-show="expandedSections.equity">
                                            <td class="py-1" style="padding-left: 2rem;">{{ $equityItem->name }}</td>
                                            <td class="text-end py-1">{{ $equityItem->debit > 0 ? number_format($equityItem->debit, 2) : '-' }}</td>
                                            <td class="text-end pe-4 py-1">{{ $equityItem->credit > 0 ? number_format($equityItem->credit, 2) : '-' }}</td>
                                        </tr>
                                    @endforeach
                                    <tr class="border-top" x-show="expandedSections.equity">
                                        <td class="py-2 fw-bold" style="padding-left: 1rem;">Total Equity</td>
                                        <td class="text-end py-2 fw-bold">{{ number_format($totalEquityDebit, 2) }}</td>
                                        <td class="text-end pe-4 py-2 fw-bold">{{ number_format($totalEquityCredit, 2) }}</td>
                                    </tr>
                                @endif

                                <!-- Income Section -->
                                <tr class="bg-light/30">
                                    <td colspan="3" class="py-3 fw-bold text-success" style="padding-left: 1rem;">
                                        <button @click="toggleSection('income')" class="btn btn-link p-0 text-decoration-none text-success fw-bold d-inline-flex align-items-center">
                                            <i class="pli-arrow-right me-2" :class="{ 'rotate-90': expandedSections.income }"
                                                style="transition: transform 0.2s; width: 1rem; text-align: center;"></i>
                                            <span>Income</span>
                                        </button>
                                    </td>
                                </tr>
                                @if (!empty($incomeTree))
                                    @include('livewire.reports.partials.trial-balance-tree', [
                                        'tree' => $incomeTree,
                                        'totalDebit' => $totalIncomeDebit,
                                        'totalCredit' => $totalIncomeCredit,
                                        'sectionName' => 'income',
                                        'showCondition' => 'expandedSections.income',
                                    ])
                                @else
                                    @foreach ($income ?? [] as $incomeItem)
                                        <tr class="hover:bg-light/40 transition-colors" x-show="expandedSections.income">
                                            <td class="py-1" style="padding-left: 2rem;">{{ $incomeItem->name }}</td>
                                            <td class="text-end py-1">{{ $incomeItem->debit > 0 ? number_format($incomeItem->debit, 2) : '-' }}</td>
                                            <td class="text-end pe-4 py-1">{{ $incomeItem->credit > 0 ? number_format($incomeItem->credit, 2) : '-' }}</td>
                                        </tr>
                                    @endforeach
                                    <tr class="border-top" x-show="expandedSections.income">
                                        <td class="py-2 fw-bold" style="padding-left: 1rem;">Total Income</td>
                                        <td class="text-end py-2 fw-bold">{{ number_format($totalIncomeDebit, 2) }}</td>
                                        <td class="text-end pe-4 py-2 fw-bold">{{ number_format($totalIncomeCredit, 2) }}</td>
                                    </tr>
                                @endif

                                <!-- Expense Section -->
                                <tr class="bg-light/30">
                                    <td colspan="3" class="py-3 fw-bold text-danger" style="padding-left: 1rem;">
                                        <button @click="toggleSection('expenses')" class="btn btn-link p-0 text-decoration-none text-danger fw-bold d-inline-flex align-items-center">
                                            <i class="pli-arrow-right me-2" :class="{ 'rotate-90': expandedSections.expenses }"
                                                style="transition: transform 0.2s; width: 1rem; text-align: center;"></i>
                                            <span>Expenses</span>
                                        </button>
                                    </td>
                                </tr>
                                @if (!empty($expensesTree))
                                    @include('livewire.reports.partials.trial-balance-tree', [
                                        'tree' => $expensesTree,
                                        'totalDebit' => $totalExpensesDebit,
                                        'totalCredit' => $totalExpensesCredit,
                                        'sectionName' => 'expenses',
                                        'showCondition' => 'expandedSections.expenses',
                                    ])
                                @else
                                    @foreach ($expenses ?? [] as $expense)
                                        <tr class="hover:bg-light/40 transition-colors" x-show="expandedSections.expenses">
                                            <td class="py-1" style="padding-left: 2rem;">{{ $expense->name }}</td>
                                            <td class="text-end py-1">{{ $expense->debit > 0 ? number_format($expense->debit, 2) : '-' }}</td>
                                            <td class="text-end pe-4 py-1">{{ $expense->credit > 0 ? number_format($expense->credit, 2) : '-' }}</td>
                                        </tr>
                                    @endforeach
                                    <tr class="border-top" x-show="expandedSections.expenses">
                                        <td class="py-2 fw-bold" style="padding-left: 1rem;">Total Expenses</td>
                                        <td class="text-end py-2 fw-bold">{{ number_format($totalExpensesDebit, 2) }}</td>
                                        <td class="text-end pe-4 py-2 fw-bold">{{ number_format($totalExpensesCredit, 2) }}</td>
                                    </tr>
                                @endif

                                <!-- Grand Total -->
                                <tr class="bg-primary bg-opacity-10 fw-bold">
                                    <td class="fw-bold" style="padding-left: 1rem;">Grand Total</td>
                                    <td class="text-end">{{ number_format($totalDebit, 2) }}</td>
                                    <td class="text-end pe-4">{{ number_format($totalCredit, 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <script>
                    function trialBalanceTree() {
                        return {
                            expandedSections: {
                                assets: true,
                                liabilities: true,
                                equity: true,
                                income: true,
                                expenses: true
                            },
                            expandedCategories: {},
                            expandedGroups: {},
                            toggleSection(section) {
                                this.expandedSections[section] = !this.expandedSections[section];
                            },
                            toggleCategory(section, categoryId) {
                                const key = `${section}_${categoryId}`;
                                this.expandedCategories[key] = !this.expandedCategories[key];
                            },
                            toggleGroup(section, groupId) {
                                const key = `${section}_${groupId}`;
                                this.expandedGroups[key] = !this.expandedGroups[key];
                            },
                            isCategoryExpanded(section, categoryId) {
                                const key = `${section}_${categoryId}`;
                                return this.expandedCategories[key] !== false; // Default to expanded
                            },
                            isGroupExpanded(section, groupId) {
                                const key = `${section}_${groupId}`;
                                return this.expandedGroups[key] !== false; // Default to expanded
                            },
                            expandAll() {
                                this.expandedSections.assets = true;
                                this.expandedSections.liabilities = true;
                                this.expandedSections.equity = true;
                                this.expandedSections.income = true;
                                this.expandedSections.expenses = true;
                                // Expand all categories and groups
                                this.expandedCategories = {};
                                this.expandedGroups = {};
                            },
                            collapseAll() {
                                this.expandedSections.assets = false;
                                this.expandedSections.liabilities = false;
                                this.expandedSections.equity = false;
                                this.expandedSections.income = false;
                                this.expandedSections.expenses = false;
                            }
                        }
                    }
                </script>
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

<div>
    {{-- Loading Bar --}}
    <div wire:loading.delay class="position-fixed top-0 start-0 w-100" style="z-index: 1060; height: 3px;">
        <div class="bg-primary h-100 tb-loading-bar"></div>
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
                <div class="col-lg-4 col-md-8">
                    <label for="selected_account_ids" class="form-label small text-muted mb-1">Filter Accounts <span class="text-muted">(optional)</span></label>
                    <div wire:ignore>
                        {{ html()->select('selected_account_ids', [])->multiple()->class('select-account_id-list')->id('selected_account_ids')->attribute('placeholder', 'All accounts') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Summary Row --}}
    <div class="row g-3 mb-4">
        @php
            $difference = round($totalDebit - $totalCredit, 2);
            $isBalanced = abs($difference) <= 0.01;
            $summaryCards = [
                ['label' => 'Total Debit', 'value' => $totalDebit, 'color' => 'primary', 'icon' => 'pli-arrow-up-2'],
                ['label' => 'Total Credit', 'value' => $totalCredit, 'color' => 'info', 'icon' => 'pli-arrow-down-2'],
                ['label' => 'Difference', 'value' => abs($difference), 'color' => $isBalanced ? 'success' : 'danger', 'icon' => $isBalanced ? 'pli-check' : 'pli-warning-triangle'],
            ];
        @endphp
        @foreach ($summaryCards as $card)
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body py-3 d-flex align-items-center">
                        <div class="rounded-circle d-flex align-items-center justify-content-center me-3 bg-{{ $card['color'] }} bg-opacity-10" style="width: 48px; height: 48px; min-width: 48px;">
                            <i class="{{ $card['icon'] }} fs-4 text-{{ $card['color'] }}"></i>
                        </div>
                        <div>
                            <div class="small text-muted">{{ $card['label'] }}</div>
                            <div class="fs-5 fw-bold text-{{ $card['color'] }}">{{ number_format($card['value'], 2) }}</div>
                        </div>
                        @if ($loop->last)
                            <span class="badge bg-{{ $isBalanced ? 'success' : 'danger' }} bg-opacity-10 text-{{ $isBalanced ? 'success' : 'danger' }} ms-auto px-2 py-1">
                                {{ $isBalanced ? 'Balanced' : 'Unbalanced' }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Imbalance Alert --}}
    @if (!$isBalanced)
        <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center mb-4 py-2" role="alert">
            <i class="pli-warning-triangle fs-4 text-danger me-3"></i>
            <div>
                <strong>Trial Balance Mismatch</strong> &mdash; Difference of <strong>{{ number_format(abs($difference), 2) }}</strong>. Please review entries for errors.
            </div>
        </div>
    @endif

    {{-- Trial Balance Table --}}
    <div class="card border-0 shadow-sm" x-data="trialBalanceTree()">
        {{-- Table Header --}}
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0 fw-bold">Trial Balance</h5>
                    <small class="text-muted">{{ date('M d, Y', strtotime($start_date)) }} &mdash; {{ date('M d, Y', strtotime($end_date)) }}</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="btn-group btn-group-sm" role="group">
                        <button @click="expandAll()" class="btn btn-outline-secondary" title="Expand All">
                            <i class="pli-arrow-down-2 me-1"></i>Expand
                        </button>
                        <button @click="collapseAll()" class="btn btn-outline-secondary" title="Collapse All">
                            <i class="pli-arrow-up-2 me-1"></i>Collapse
                        </button>
                    </div>
                    <div class="vr mx-1"></div>
                    <button wire:click="export" class="btn btn-sm btn-success">
                        <i class="pli-file-excel me-1"></i>Excel
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                        <i class="pli-printer me-1"></i>Print
                    </button>
                </div>
            </div>
        </div>

        {{-- Table Body --}}
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0" style="font-size: 0.875rem;">
                    <thead>
                        <tr class="bg-light">
                            <th class="border-0 py-2 ps-3" style="width: 50%;">Account</th>
                            <th class="border-0 py-2 text-end" style="width: 16.66%;">Debit</th>
                            <th class="border-0 py-2 text-end" style="width: 16.66%;">Credit</th>
                            <th class="border-0 py-2 text-end pe-3" style="width: 16.66%;">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $sections = [
                                ['key' => 'assets', 'label' => 'Assets', 'color' => 'primary', 'tree' => $assetsTree, 'items' => $assets, 'debit' => $totalAssetsDebit, 'credit' => $totalAssetsCredit],
                                ['key' => 'liabilities', 'label' => 'Liabilities', 'color' => 'warning', 'tree' => $liabilitiesTree, 'items' => $liabilities, 'debit' => $totalLiabilitiesDebit, 'credit' => $totalLiabilitiesCredit],
                                ['key' => 'equity', 'label' => 'Equity', 'color' => 'success', 'tree' => $equityTree, 'items' => $equity, 'debit' => $totalEquityDebit, 'credit' => $totalEquityCredit],
                                ['key' => 'income', 'label' => 'Income', 'color' => 'info', 'tree' => $incomeTree, 'items' => $income, 'debit' => $totalIncomeDebit, 'credit' => $totalIncomeCredit],
                                ['key' => 'expenses', 'label' => 'Expenses', 'color' => 'danger', 'tree' => $expensesTree, 'items' => $expenses, 'debit' => $totalExpensesDebit, 'credit' => $totalExpensesCredit],
                                ['key' => 'other', 'label' => 'Uncategorized', 'color' => 'secondary', 'tree' => $otherTree, 'items' => $other, 'debit' => $totalOtherDebit, 'credit' => $totalOtherCredit],
                            ];
                            // Remove empty sections
                            $sections = array_filter($sections, fn($s) => !empty($s['tree']) || !empty($s['items']));
                        @endphp

                        @foreach ($sections as $section)
                            {{-- Section Header --}}
                            <tr class="bg-light bg-opacity-50" style="border-left: 3px solid var(--bs-{{ $section['color'] }});">
                                <td colspan="4" class="py-2 ps-3">
                                    <button @click="toggleSection('{{ $section['key'] }}')" class="btn btn-link p-0 text-decoration-none fw-bold d-inline-flex align-items-center text-{{ $section['color'] }}">
                                        <i class="pli-arrow-right me-2" :class="{ 'tb-rotate': expandedSections.{{ $section['key'] }} }" style="transition: transform 0.2s; font-size: 0.75rem;"></i>
                                        {{ $section['label'] }}
                                    </button>
                                </td>
                            </tr>

                            {{-- Section Content --}}
                            @if (!empty($section['tree']))
                                @include('livewire.reports.partials.trial-balance-tree', [
                                    'tree' => $section['tree'],
                                    'totalDebit' => $section['debit'],
                                    'totalCredit' => $section['credit'],
                                    'sectionName' => $section['key'],
                                    'sectionColor' => $section['color'],
                                    'showCondition' => "expandedSections.{$section['key']}",
                                    'start_date' => $start_date,
                                    'end_date' => $end_date,
                                ])
                            @else
                                @forelse ($section['items'] ?? [] as $item)
                                    <tr x-show="expandedSections.{{ $section['key'] }}" x-cloak>
                                        <td class="py-1 ps-4">
                                            @if (isset($item->id))
                                                <a href="{{ route('account::view', $item->id) }}?from_date={{ $start_date }}&to_date={{ $end_date }}" target="_blank" class="text-decoration-none">{{ $item->name }}</a>
                                            @else
                                                {{ $item->name }}
                                            @endif
                                        </td>
                                        <td class="text-end py-1 text-nowrap">{{ $item->debit > 0 ? number_format($item->debit, 2) : '-' }}</td>
                                        <td class="text-end py-1 text-nowrap">{{ $item->credit > 0 ? number_format($item->credit, 2) : '-' }}</td>
                                        <td class="text-end pe-3 py-1 text-nowrap">{{ number_format($item->balance ?? ($item->debit - $item->credit), 2) }}</td>
                                    </tr>
                                @empty
                                    <tr x-show="expandedSections.{{ $section['key'] }}" x-cloak>
                                        <td colspan="4" class="text-center text-muted py-2 fst-italic small">No {{ strtolower($section['label']) }} accounts found</td>
                                    </tr>
                                @endforelse
                                @if (!empty($section['items']))
                                    <tr class="border-top" x-show="expandedSections.{{ $section['key'] }}" x-cloak>
                                        <td class="py-2 ps-3 fw-bold small">Total {{ $section['label'] }}</td>
                                        <td class="text-end py-2 fw-bold text-nowrap">{{ number_format($section['debit'], 2) }}</td>
                                        <td class="text-end py-2 fw-bold text-nowrap">{{ number_format($section['credit'], 2) }}</td>
                                        <td class="text-end pe-3 py-2 fw-bold text-nowrap">{{ number_format($section['debit'] - $section['credit'], 2) }}</td>
                                    </tr>
                                @endif
                            @endif
                        @endforeach

                        {{-- Grand Total --}}
                        <tr class="bg-dark bg-opacity-10 fw-bold" style="border-top: 2px solid #333;">
                            <td class="py-3 ps-3 fs-6">Grand Total</td>
                            <td class="text-end py-3 fs-6 text-nowrap">{{ number_format($totalDebit, 2) }}</td>
                            <td class="text-end py-3 fs-6 text-nowrap">{{ number_format($totalCredit, 2) }}</td>
                            <td class="text-end pe-3 py-3 fs-6 text-nowrap {{ !$isBalanced ? 'text-danger' : '' }}">{{ number_format($totalDebit - $totalCredit, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function trialBalanceTree() {
                return {
                    expandedSections: { assets: true, liabilities: true, equity: true, income: true, expenses: true, other: true },
                    expandedCategories: {},
                    expandedGroups: {},

                    toggleSection(section) {
                        this.expandedSections[section] = !this.expandedSections[section];
                    },
                    toggleCategory(section, categoryId) {
                        const key = section + '_' + categoryId;
                        this.expandedCategories[key] = !this.expandedCategories[key];
                    },
                    toggleGroup(section, groupId) {
                        const key = section + '_' + groupId;
                        this.expandedGroups[key] = !this.expandedGroups[key];
                    },
                    isCategoryExpanded(section, categoryId) {
                        const key = section + '_' + categoryId;
                        return this.expandedCategories[key] !== false;
                    },
                    isGroupExpanded(section, groupId) {
                        const key = section + '_' + groupId;
                        return this.expandedGroups[key] === true;
                    },
                    expandAll() {
                        this.expandedSections = { assets: true, liabilities: true, equity: true, income: true, expenses: true, other: true };
                        // Set all categories/groups to expanded
                        for (let key in this.expandedCategories) this.expandedCategories[key] = true;
                        for (let key in this.expandedGroups) this.expandedGroups[key] = true;
                        // Trigger Alpine reactivity
                        this.expandedCategories = Object.assign({}, this.expandedCategories);
                        this.expandedGroups = Object.assign({}, this.expandedGroups);
                    },
                    collapseAll() {
                        this.expandedSections = { assets: false, liabilities: false, equity: false, income: false, expenses: false, other: false };
                        for (let key in this.expandedCategories) this.expandedCategories[key] = false;
                        for (let key in this.expandedGroups) this.expandedGroups[key] = false;
                        this.expandedCategories = Object.assign({}, this.expandedCategories);
                        this.expandedGroups = Object.assign({}, this.expandedGroups);
                    }
                }
            }

            $(document).ready(function() {
                $('#branch_id').on('change', function(e) {
                    @this.set('branch_id', $(this).val() || null);
                });
                $('#selected_account_ids').on('change', function(e) {
                    @this.set('selected_account_ids', $(this).val() || []);
                });
            });
        </script>
    @endpush

    <style>
        .tb-rotate { transform: rotate(90deg); }
        [x-cloak] { display: none !important; }
        .tb-loading-bar {
            animation: tb-loading 1.5s ease-in-out infinite;
        }
        @keyframes tb-loading {
            0% { width: 0; margin-left: 0; }
            50% { width: 60%; margin-left: 20%; }
            100% { width: 0; margin-left: 100%; }
        }

        @media print {
            .card-header .d-flex .btn { display: none !important; }
            .card-header .vr { display: none !important; }
            [x-cloak] { display: table-row !important; }
            tr[x-show] { display: table-row !important; }
        }
    </style>
</div>

<div>
    <div class="card shadow-sm">
        <div class="card-body">
            <!-- Filter Section -->
            <div class="row mb-4 g-3">
                <div class="col-md-2">
                    <div class="form-group" wire:ignore>
                        <label for="branch_id" class="form-label fw-bold text-secondary mb-2">
                            <i class="pli-building me-1"></i>Branch
                        </label>
                        {{ html()->select('branch_id', [session('branch_id') => session('branch_name')])->value(session('branch_id'))->class('select-assigned-branch_id-list border-start-0 ps-0')->id('branch_id')->attribute('style', 'width:80%')->placeholder('Select Branch') }}
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="period" class="form-label fw-bold text-secondary mb-2">
                            <i class="pli-time-clock me-1"></i>Period
                        </label>
                        <select wire:model="period" class="form-select shadow-sm border-light" id="period">
                            <option value="monthly">Current Month</option>
                            <option value="quarterly">Current Quarter</option>
                            <option value="yearly">Current Year</option>
                            <option value="previous_month">Previous Month</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="start_date" class="form-label fw-bold text-secondary mb-2">
                            <i class="pli-calendar-4 me-1"></i>Start Date
                        </label>
                        <input type="date" wire:model="start_date" class="form-control shadow-sm border-light" id="start_date">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="end_date" class="form-label fw-bold text-secondary mb-2">
                            <i class="pli-calendar-4 me-1"></i>End Date
                        </label>
                        <input type="date" wire:model="end_date" class="form-control shadow-sm border-light" id="end_date">
                    </div>
                </div>
                <div class="col-md-1">
                    <div class="form-group">
                        <label class="form-label fw-bold text-secondary mb-2 d-block">&nbsp;</label>
                        <button type="button" wire:click="fetchData" class="btn btn-primary w-100 shadow-sm">
                            <i class="pli-reload me-1"></i>Fetch
                        </button>
                    </div>
                </div>
                <div class="col-md-1">
                    <div class="form-group">
                        <label class="form-label fw-bold text-secondary mb-2 d-block">&nbsp;</label>
                        <button type="button" wire:click="resetFilters" class="btn btn-outline-secondary w-100 shadow-sm">
                            <i class="pli-cross me-1"></i>Reset
                        </button>
                    </div>
                </div>
                <div class="col-2">
                    <div class="form-group">
                        <label class="form-label fw-bold text-secondary mb-2 d-block">&nbsp;</label>
                        <button type="button" wire:click="export" class="btn btn-success shadow-sm">
                            <i class="pli-file-excel me-1"></i>Export to Excel
                        </button>
                    </div>
                </div>
            </div>

            <!-- Profit & Loss Report - T-Account Format -->
            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0" id="profitLossTable" style="font-size: 0.9rem;">
                    <thead>
                        <tr>
                            <th class="text-left bg-light" style="width: 40%; border-right: 2px solid #dee2e6;">
                                <strong>PARTICULARS</strong>
                            </th>
                            <th class="text-center bg-light" style="width: 15%; border-right: 2px solid #dee2e6;">
                                <strong>AMOUNT</strong>
                            </th>
                            <th class="text-left bg-light" style="width: 40%;">
                                <strong>PARTICULARS</strong>
                            </th>
                            <th class="text-center bg-light" style="width: 15%;">
                                <strong>AMOUNT</strong>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- ========== TRADING ACCOUNT (Gross Profit/Loss) ========== --}}

                        {{-- Row 1: Opening Stock | Net Sale --}}
                        <tr>
                            <td class="ps-3" style="border-right: 2px solid #dee2e6;">
                                <button type="button" wire:click="openModal('opening_stock')" class="btn btn-sm btn-link p-0 fw-bold text-dark text-decoration-none">
                                    <i class="fa fa-search-plus me-1 text-muted" style="font-size:0.75rem;"></i>OPENING STOCK
                                </button>
                            </td>
                            <td class="text-end pe-3" style="border-right: 2px solid #dee2e6;">{{ currency($openingStock) }}</td>
                            <td class="ps-3">
                                <button type="button" wire:click="openModal('net_sale')" class="btn btn-sm btn-link p-0 fw-bold text-dark text-decoration-none">
                                    <i class="fa fa-search-plus me-1 text-muted" style="font-size:0.75rem;"></i>NET SALE
                                </button>
                            </td>
                            <td class="text-end pe-3">{{ currency($netSale) }}</td>
                        </tr>

                        {{-- Row 2: Net Purchase | Closing Stock --}}
                        <tr>
                            <td class="ps-3" style="border-right: 2px solid #dee2e6;">
                                <button type="button" wire:click="openModal('net_purchase')" class="btn btn-sm btn-link p-0 fw-bold text-dark text-decoration-none">
                                    <i class="fa fa-search-plus me-1 text-muted" style="font-size:0.75rem;"></i>NET PURCHASE
                                </button>
                            </td>
                            <td class="text-end pe-3" style="border-right: 2px solid #dee2e6;">{{ currency($netPurchase) }}</td>
                            <td class="ps-3">
                                <button type="button" wire:click="openModal('closing_stock')" class="btn btn-sm btn-link p-0 fw-bold text-dark text-decoration-none">
                                    <i class="fa fa-search-plus me-1 text-muted" style="font-size:0.75rem;"></i>CLOSING STOCK
                                </button>
                            </td>
                            <td class="text-end pe-3" style="background-color: #e3f2fd;">{{ currency($closingStock) }}</td>
                        </tr>

                        {{-- Row 3: Direct Expense | Direct Income --}}
                        @php
                            $directExpenseMaster = collect($directExpenseStructure)->firstWhere('name', 'Direct Expense');
                            $directIncomeMaster = collect($directIncomeStructure)->firstWhere('name', 'Direct Income');
                        @endphp
                        <tr>
                            {{-- Left: Direct Expense --}}
                            @if ($directExpenseMaster)
                                <td class="ps-3" style="border-right: 2px solid #dee2e6;">
                                    <button type="button" wire:click="toggleGroup({{ $directExpenseMaster['id'] }})" class="btn btn-sm btn-link p-0 text-start text-decoration-none fw-bold">
                                        <i class="fa fa-{{ in_array($directExpenseMaster['id'], $expandedGroups) ? 'minus' : 'plus' }} me-1"></i>
                                        DIRECT EXPENSE
                                    </button>
                                </td>
                                <td class="text-end pe-3" style="border-right: 2px solid #dee2e6; background-color: #e3f2fd;">
                                    <strong>{{ currency($directExpenseMaster['total']) }}</strong>
                                </td>
                            @else
                                <td class="ps-3" style="border-right: 2px solid #dee2e6;"><strong>DIRECT EXPENSE</strong></td>
                                <td class="text-end pe-3" style="border-right: 2px solid #dee2e6; background-color: #e3f2fd;">{{ currency($directExpense) }}</td>
                            @endif

                            {{-- Right: Direct Income --}}
                            @if ($directIncomeMaster)
                                <td class="ps-3">
                                    <button type="button" wire:click="toggleGroup({{ $directIncomeMaster['id'] }})" class="btn btn-sm btn-link p-0 text-start text-decoration-none fw-bold">
                                        <i class="fa fa-{{ in_array($directIncomeMaster['id'], $expandedGroups) ? 'minus' : 'plus' }} me-1"></i>
                                        DIRECT INCOME
                                    </button>
                                </td>
                                <td class="text-end pe-3" style="background-color: #e3f2fd;">
                                    <strong>{{ currency($directIncomeMaster['total']) }}</strong>
                                </td>
                            @else
                                <td class="ps-3"><strong>DIRECT INCOME</strong></td>
                                <td class="text-end pe-3" style="background-color: #e3f2fd;">{{ currency($directIncome) }}</td>
                            @endif
                        </tr>

                        {{-- Expanded Direct Expense details --}}
                        @if ($directExpenseMaster && in_array($directExpenseMaster['id'], $expandedGroups))
                            @include('livewire.reports.partials.pl-category-rows', [
                                'master' => $directExpenseMaster,
                                'side' => 'left',
                            ])
                        @endif

                        {{-- Expanded Direct Income details --}}
                        @if ($directIncomeMaster && in_array($directIncomeMaster['id'], $expandedGroups))
                            @include('livewire.reports.partials.pl-category-rows', [
                                'master' => $directIncomeMaster,
                                'side' => 'right',
                            ])
                        @endif

                        {{-- Gross Profit / Gross Loss --}}
                        <tr>
                            @if ($grossProfit > 0)
                                <td class="ps-3" style="border-right: 2px solid #dee2e6;"><strong class="text-success">GROSS PROFIT C/D</strong></td>
                                <td class="text-end pe-3 text-success" style="border-right: 2px solid #dee2e6;"><strong>{{ currency($grossProfit) }}</strong></td>
                                <td class="ps-3"></td>
                                <td class="text-end pe-3"></td>
                            @elseif ($grossLoss > 0)
                                <td class="ps-3" style="border-right: 2px solid #dee2e6;"></td>
                                <td class="text-end pe-3" style="border-right: 2px solid #dee2e6;"></td>
                                <td class="ps-3"><strong class="text-danger">GROSS LOSS C/D</strong></td>
                                <td class="text-end pe-3 text-danger"><strong>{{ currency($grossLoss) }}</strong></td>
                            @else
                                <td class="ps-3" style="border-right: 2px solid #dee2e6;"></td>
                                <td class="text-end pe-3" style="border-right: 2px solid #dee2e6;"></td>
                                <td class="ps-3"></td>
                                <td class="text-end pe-3"></td>
                            @endif
                        </tr>

                        {{-- Trading Account Totals --}}
                        <tr class="table-light" style="border-top: 2px solid #dee2e6;">
                            <td class="ps-3" style="border-right: 2px solid #dee2e6;"><strong>TOTAL</strong></td>
                            <td class="text-end pe-3" style="border-right: 2px solid #dee2e6;"><strong>{{ currency($leftTotal1) }}</strong></td>
                            <td class="ps-3"><strong>TOTAL</strong></td>
                            <td class="text-end pe-3"><strong>{{ currency($rightTotal1) }}</strong></td>
                        </tr>

                        {{-- ========== PROFIT & LOSS ACCOUNT (Net Profit/Loss) ========== --}}

                        {{-- Gross Loss B/D or Gross Profit B/D --}}
                        <tr>
                            @if ($grossLoss > 0)
                                <td class="ps-3" style="border-right: 2px solid #dee2e6;"><strong class="text-danger">GROSS LOSS B/D</strong></td>
                                <td class="text-end pe-3 text-danger" style="border-right: 2px solid #dee2e6;"><strong>{{ currency($grossLoss) }}</strong></td>
                                <td class="ps-3"></td>
                                <td class="text-end pe-3"></td>
                            @elseif ($grossProfit > 0)
                                <td class="ps-3" style="border-right: 2px solid #dee2e6;"></td>
                                <td class="text-end pe-3" style="border-right: 2px solid #dee2e6;"></td>
                                <td class="ps-3"><strong class="text-success">GROSS PROFIT B/D</strong></td>
                                <td class="text-end pe-3 text-success"><strong>{{ currency($grossProfit) }}</strong></td>
                            @else
                                <td class="ps-3" style="border-right: 2px solid #dee2e6;"></td>
                                <td class="text-end pe-3" style="border-right: 2px solid #dee2e6;"></td>
                                <td class="ps-3"></td>
                                <td class="text-end pe-3"></td>
                            @endif
                        </tr>

                        {{-- Indirect Expense | Indirect Income --}}
                        @php
                            $indirectExpenseMaster = collect($directExpenseStructure)->firstWhere('name', 'Indirect Expense');
                            $indirectIncomeMaster = collect($directIncomeStructure)->firstWhere('name', 'Indirect Income');
                        @endphp
                        <tr>
                            {{-- Left: Indirect Expense --}}
                            @if ($indirectExpenseMaster)
                                <td class="ps-3" style="border-right: 2px solid #dee2e6;">
                                    <button type="button" wire:click="toggleGroup({{ $indirectExpenseMaster['id'] }})" class="btn btn-sm btn-link p-0 text-start text-decoration-none fw-bold">
                                        <i class="fa fa-{{ in_array($indirectExpenseMaster['id'], $expandedGroups) ? 'minus' : 'plus' }} me-1"></i>
                                        INDIRECT EXPENSE
                                    </button>
                                </td>
                                <td class="text-end pe-3" style="border-right: 2px solid #dee2e6; background-color: #e3f2fd;">
                                    <strong>{{ currency($indirectExpenseMaster['total']) }}</strong>
                                </td>
                            @else
                                <td class="ps-3" style="border-right: 2px solid #dee2e6;"><strong>INDIRECT EXPENSE</strong></td>
                                <td class="text-end pe-3" style="border-right: 2px solid #dee2e6; background-color: #e3f2fd;">{{ currency($indirectExpense) }}</td>
                            @endif

                            {{-- Right: Indirect Income --}}
                            @if ($indirectIncomeMaster)
                                <td class="ps-3">
                                    <button type="button" wire:click="toggleGroup({{ $indirectIncomeMaster['id'] }})" class="btn btn-sm btn-link p-0 text-start text-decoration-none fw-bold">
                                        <i class="fa fa-{{ in_array($indirectIncomeMaster['id'], $expandedGroups) ? 'minus' : 'plus' }} me-1"></i>
                                        INDIRECT INCOME
                                    </button>
                                </td>
                                <td class="text-end pe-3" style="background-color: #e3f2fd;">
                                    <strong>{{ currency($indirectIncomeMaster['total']) }}</strong>
                                </td>
                            @else
                                <td class="ps-3"><strong>INDIRECT INCOME</strong></td>
                                <td class="text-end pe-3" style="background-color: #e3f2fd;">{{ currency($indirectIncome) }}</td>
                            @endif
                        </tr>

                        {{-- Expanded Indirect Expense details --}}
                        @if ($indirectExpenseMaster && in_array($indirectExpenseMaster['id'], $expandedGroups))
                            @include('livewire.reports.partials.pl-category-rows', [
                                'master' => $indirectExpenseMaster,
                                'side' => 'left',
                            ])
                        @endif

                        {{-- Expanded Indirect Income details --}}
                        @if ($indirectIncomeMaster && in_array($indirectIncomeMaster['id'], $expandedGroups))
                            @include('livewire.reports.partials.pl-category-rows', [
                                'master' => $indirectIncomeMaster,
                                'side' => 'right',
                            ])
                        @endif

                        {{-- Net Profit / Net Loss --}}
                        <tr>
                            @if ($netProfitAmount > 0)
                                <td class="ps-3" style="border-right: 2px solid #dee2e6;"><strong class="text-success">NET PROFIT C/D</strong></td>
                                <td class="text-end pe-3 text-success" style="border-right: 2px solid #dee2e6;"><strong>{{ currency($netProfitAmount) }}</strong></td>
                                <td class="ps-3"></td>
                                <td class="text-end pe-3"></td>
                            @elseif ($netLossAmount > 0)
                                <td class="ps-3" style="border-right: 2px solid #dee2e6;"></td>
                                <td class="text-end pe-3" style="border-right: 2px solid #dee2e6;"></td>
                                <td class="ps-3"><strong class="text-danger">NET LOSS C/D</strong></td>
                                <td class="text-end pe-3 text-danger"><strong>{{ currency($netLossAmount) }}</strong></td>
                            @else
                                <td class="ps-3" style="border-right: 2px solid #dee2e6;"></td>
                                <td class="text-end pe-3" style="border-right: 2px solid #dee2e6;"></td>
                                <td class="ps-3"></td>
                                <td class="text-end pe-3"></td>
                            @endif
                        </tr>

                        {{-- P&L Account Totals --}}
                        <tr class="table-light" style="border-top: 2px solid #dee2e6;">
                            <td class="ps-3" style="border-right: 2px solid #dee2e6;"><strong>TOTAL</strong></td>
                            <td class="text-end pe-3" style="border-right: 2px solid #dee2e6;"><strong>{{ currency($leftTotal2) }}</strong></td>
                            <td class="ps-3"><strong>TOTAL</strong></td>
                            <td class="text-end pe-3"><strong>{{ currency($rightTotal2) }}</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Transaction Detail Modal --}}
    <div class="modal fade" id="plDetailModal" tabindex="-1" aria-labelledby="plDetailModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title fw-bold" id="plDetailModalLabel">
                        @php
                            $sectionLabels = [
                                'net_sale'      => ['label' => 'NET SALE', 'icon' => 'pli-receipt-4', 'color' => 'text-success'],
                                'net_purchase'  => ['label' => 'NET PURCHASE', 'icon' => 'pli-shopping-basket', 'color' => 'text-primary'],
                                'opening_stock' => ['label' => 'OPENING STOCK', 'icon' => 'pli-box-open', 'color' => 'text-info'],
                                'closing_stock' => ['label' => 'CLOSING STOCK', 'icon' => 'pli-box-open', 'color' => 'text-warning'],
                            ];
                            $meta = $sectionLabels[$modalSection] ?? ['label' => strtoupper(str_replace('_', ' ', $modalSection)), 'icon' => 'pli-receipt-4', 'color' => ''];
                        @endphp
                        <i class="{{ $meta['icon'] }} me-2 {{ $meta['color'] }}"></i>
                        {{ $meta['label'] }} — Transaction Breakdown
                        <small class="text-muted fw-normal ms-2">{{ $start_date }} to {{ $end_date }}</small>
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-0">
                    @if(count($modalTransactions) === 0)
                        <div class="text-center py-5 text-muted">
                            <i class="pli-receipt-4" style="font-size: 2.5rem;"></i>
                            <p class="mt-2 mb-0">No transactions found for this period.</p>
                        </div>
                    @else
                        @php
                            $totalDebit  = array_sum(array_column($modalTransactions, 'debit'));
                            $totalCredit = array_sum(array_column($modalTransactions, 'credit'));
                        @endphp

                        {{-- Summary bar --}}
                        <div class="d-flex gap-4 px-4 py-2 bg-light border-bottom" style="font-size:0.85rem;">
                            <span><strong>{{ count($modalTransactions) }}</strong> transactions</span>
                            @if($totalDebit > 0)
                                <span>Total Debit: <strong class="text-danger">{{ currency($totalDebit) }}</strong></span>
                            @endif
                            @if($totalCredit > 0)
                                <span>Total Credit: <strong class="text-success">{{ currency($totalCredit) }}</strong></span>
                            @endif
                            @if(in_array($modalSection, ['net_sale', 'net_purchase']))
                                <span>Net: <strong>{{ currency(abs($totalCredit - $totalDebit)) }}</strong></span>
                            @endif
                            @if($modalSection === 'opening_stock')
                                <span>Balance: <strong class="text-info">{{ currency($openingStock) }}</strong></span>
                            @endif
                            @if($modalSection === 'closing_stock')
                                <span>Balance: <strong class="text-warning">{{ currency($closingStock) }}</strong></span>
                            @endif
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0" style="font-size:0.85rem;">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th class="ps-3">#</th>
                                        <th>Date</th>
                                        <th>Reference</th>
                                        <th>Type</th>
                                        <th>Account</th>
                                        <th>Party / Description</th>
                                        <th class="text-end pe-3">Debit</th>
                                        <th class="text-end pe-3">Credit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($modalTransactions as $i => $tx)
                                        <tr>
                                            <td class="ps-3 text-muted">{{ $i + 1 }}</td>
                                            <td class="text-nowrap">{{ \Carbon\Carbon::parse($tx['date'])->format('d M Y') }}</td>
                                            <td>
                                                @if($tx['reference_number'])
                                                    <span class="badge bg-light text-dark border">{{ $tx['reference_number'] }}</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($tx['type'])
                                                    <span class="badge
                                                        @if(in_array($tx['type'], ['Purchase', 'Grn'])) bg-primary
                                                        @elseif($tx['type'] === 'PurchaseReturn') bg-warning text-dark
                                                        @elseif($tx['type'] === 'Sale') bg-success
                                                        @elseif($tx['type'] === 'SaleReturn') bg-danger
                                                        @else bg-secondary
                                                        @endif
                                                    ">{{ $tx['type'] }}</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td class="text-muted">{{ $tx['account'] ?? '—' }}</td>
                                            <td style="max-width:220px;" class="text-truncate">
                                                <span title="{{ $tx['person_name'] }} {{ $tx['description'] }}">
                                                    {{ $tx['person_name'] ?: ($tx['description'] ?: '—') }}
                                                </span>
                                            </td>
                                            <td class="text-end pe-3 {{ $tx['debit'] > 0 ? 'text-danger' : 'text-muted' }}">
                                                {{ $tx['debit'] > 0 ? currency($tx['debit']) : '—' }}
                                            </td>
                                            <td class="text-end pe-3 {{ $tx['credit'] > 0 ? 'text-success' : 'text-muted' }}">
                                                {{ $tx['credit'] > 0 ? currency($tx['credit']) : '—' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light fw-bold">
                                    <tr style="border-top: 2px solid #dee2e6;">
                                        <td colspan="6" class="ps-3 text-end">TOTAL</td>
                                        <td class="text-end pe-3 text-danger">{{ $totalDebit > 0 ? currency($totalDebit) : '—' }}</td>
                                        <td class="text-end pe-3 text-success">{{ $totalCredit > 0 ? currency($totalCredit) : '—' }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @endif
                </div>

                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
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

            window.addEventListener('open-pl-detail-modal', () => {
                const el = document.getElementById('plDetailModal');
                if (el) {
                    const modal = bootstrap.Modal.getOrCreateInstance(el);
                    modal.show();
                }
            });
        </script>
    @endpush
</div>

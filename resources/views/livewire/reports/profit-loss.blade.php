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
                            <td class="ps-3" style="border-right: 2px solid #dee2e6;"><strong>OPENING STOCK</strong></td>
                            <td class="text-end pe-3" style="border-right: 2px solid #dee2e6;">{{ currency($openingStock) }}</td>
                            <td class="ps-3"><strong>NET SALE</strong></td>
                            <td class="text-end pe-3">{{ currency($netSale) }}</td>
                        </tr>

                        {{-- Row 2: Net Purchase | Closing Stock --}}
                        <tr>
                            <td class="ps-3" style="border-right: 2px solid #dee2e6;"><strong>NET PURCHASE</strong></td>
                            <td class="text-end pe-3" style="border-right: 2px solid #dee2e6;">{{ currency($netPurchase) }}</td>
                            <td class="ps-3"><strong>CLOSING STOCK</strong></td>
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

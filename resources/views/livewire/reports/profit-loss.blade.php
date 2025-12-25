<div>
    <div class="card shadow-sm">
        <div class="card-body">
            <!-- Filter Section -->
            <div class="row mb-4 g-3">
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="branch_id" class="form-label fw-bold text-secondary mb-2">
                            <i class="pli-building me-1"></i>Branch
                        </label>
                        <select wire:model="branch_id" class="form-select shadow-sm border-light" id="branch_id">
                            <option value="">All Branches</option>
                            @foreach ($branches as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
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
            <!-- Export Button -->
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
                        <!-- Top Section: Gross Profit/Loss Calculation -->
                        <tr>
                            <!-- Left: Opening Stock -->
                            <td class="ps-3" style="border-right: 2px solid #dee2e6;"><strong>OPENING STOCK</strong></td>
                            <td class="text-end pe-3" style="border-right: 2px solid #dee2e6;">{{ currency($openingStock) }}</td>
                            <!-- Right: Net Sale -->
                            <td class="ps-3"><strong>NET SALE</strong></td>
                            <td class="text-end pe-3">{{ currency($netSale) }}</td>
                        </tr>
                        <tr>
                            <!-- Left: Net Purchase -->
                            <td class="ps-3" style="border-right: 2px solid #dee2e6;"><strong>NET PURCHASE</strong></td>
                            <td class="text-end pe-3" style="border-right: 2px solid #dee2e6;">{{ currency($netPurchase) }}</td>
                            <!-- Right: Closing Stock -->
                            <td class="ps-3">
                                <strong>CLOSING STOCK</strong>
                            </td>
                            <td class="text-end pe-3" style="background-color: #e3f2fd;">{{ currency($closingStock) }}</td>
                        </tr>
                        <!-- Direct Expense and Direct Income with hierarchical structure -->
                        @php
                            $directExpenseMaster = collect($directExpenseStructure)->firstWhere('name', 'Direct Expense');
                            $directIncomeMaster = collect($directIncomeStructure)->firstWhere('name', 'Direct Income');
                        @endphp
                        <tr>
                            <!-- Left: Direct Expense Master -->
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
                            <!-- Right: Direct Income Master -->
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
                        <!-- Show Direct Expense groups and accounts if expanded -->
                        @if ($directExpenseMaster && in_array($directExpenseMaster['id'], $expandedGroups))
                            @foreach ($directExpenseMaster['groups'] as $group)
                                @if ($group['total'])
                                    <tr>
                                        <td class="ps-3" style="border-right: 2px solid #dee2e6; position: relative;">
                                            <span style="position: absolute; left: 0.75rem; top: 0; height: 50%; width: 1px; background-color: #d0d0d0;"></span>
                                            <span style="position: absolute; left: 0.75rem; top: 50%; width: 1rem; height: 1px; background-color: #d0d0d0;"></span>
                                            <button type="button" wire:click="toggleGroup({{ $group['id'] }})" class="btn btn-sm btn-link p-0 text-start text-decoration-none" style="padding-left: 1.5rem !important;">
                                                <i class="fa fa-{{ in_array($group['id'], $expandedGroups) ? 'minus' : 'plus' }} me-1"></i>
                                                {{ $group['name'] }}
                                            </button>
                                        </td>
                                        <td class="text-end pe-3" style="border-right: 2px solid #dee2e6; background-color: #f5f5f5;">
                                            {{ currency($group['total']) }}
                                        </td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                @endif
                                @if (in_array($group['id'], $expandedGroups))
                                    @foreach ($group['accounts'] as $account)
                                        @if ($account['amount'] > 0)
                                            <tr>
                                                <td class="ps-3" style="border-right: 2px solid #dee2e6; position: relative;">
                                                    <span style="position: absolute; left: 0.75rem; top: 0; height: 50%; width: 1px; background-color: #d0d0d0;"></span>
                                                    <span style="position: absolute; left: 2.25rem; top: 0; height: 50%; width: 1px; background-color: #d0d0d0;"></span>
                                                    <span style="position: absolute; left: 2.25rem; top: 50%; width: 1rem; height: 1px; background-color: #d0d0d0;"></span>
                                                    <a href="{{ route('account::view', $account['id']) }}?from_date={{ $start_date }}&to_date={{ $end_date }}" class="text-decoration-none" style="padding-left: 3rem !important; display: block;">{{ $account['name'] }}</a>
                                                </td>
                                                <td class="text-end pe-3" style="border-right: 2px solid #dee2e6; background-color: #ffffff;">
                                                    {{ currency($account['amount']) }}
                                                </td>
                                                <td></td>
                                                <td></td>
                                            </tr>
                                        @endif
                                    @endforeach
                                @endif
                            @endforeach
                            <!-- Show Direct Expense accounts directly under master category -->
                            @if (!empty($directExpenseMaster['directAccounts']))
                                @foreach ($directExpenseMaster['directAccounts'] as $account)
                                    @if ($account['amount'] > 0)
                                        <tr>
                                            <td class="ps-3" style="border-right: 2px solid #dee2e6; position: relative;">
                                                <span style="position: absolute; left: 0.75rem; top: 0; height: 50%; width: 1px; background-color: #d0d0d0;"></span>
                                                <span style="position: absolute; left: 0.75rem; top: 50%; width: 1rem; height: 1px; background-color: #d0d0d0;"></span>
                                                <a href="{{ route('account::view', $account['id']) }}?from_date={{ $start_date }}&to_date={{ $end_date }}" class="text-decoration-none" style="padding-left: 1.5rem !important; display: block;">{{ $account['name'] }}</a>
                                            </td>
                                            <td class="text-end pe-3" style="border-right: 2px solid #dee2e6; background-color: #ffffff;">
                                                {{ currency($account['amount']) }}
                                            </td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                    @endif
                                @endforeach
                            @endif
                        @endif
                        <!-- Show Direct Income groups and accounts if expanded -->
                        @if ($directIncomeMaster && in_array($directIncomeMaster['id'], $expandedGroups))
                            @foreach ($directIncomeMaster['groups'] as $group)
                                @if ($group['total'])
                                    <tr>
                                        <td style="border-right: 2px solid #dee2e6;"></td>
                                        <td style="border-right: 2px solid #dee2e6;"></td>
                                        <td class="ps-3" style="position: relative;">
                                            <span style="position: absolute; left: 0.75rem; top: 0; height: 50%; width: 1px; background-color: #d0d0d0;"></span>
                                            <span style="position: absolute; left: 0.75rem; top: 50%; width: 1rem; height: 1px; background-color: #d0d0d0;"></span>
                                            <button type="button" wire:click="toggleGroup({{ $group['id'] }})" class="btn btn-sm btn-link p-0 text-start text-decoration-none" style="padding-left: 1.5rem !important;">
                                                <i class="fa fa-{{ in_array($group['id'], $expandedGroups) ? 'minus' : 'plus' }} me-1"></i>
                                                {{ $group['name'] }}
                                            </button>
                                        </td>
                                        <td class="text-end pe-3" style="background-color: #f5f5f5;">
                                            {{ currency($group['total']) }}
                                        </td>
                                    </tr>
                                @endif
                                @if (in_array($group['id'], $expandedGroups))
                                    @foreach ($group['accounts'] as $account)
                                        @if ($account['amount'] > 0)
                                            <tr>
                                                <td style="border-right: 2px solid #dee2e6;"></td>
                                                <td style="border-right: 2px solid #dee2e6;"></td>
                                                <td class="ps-3" style="position: relative;">
                                                    <span style="position: absolute; left: 0.75rem; top: 0; height: 50%; width: 1px; background-color: #d0d0d0;"></span>
                                                    <span style="position: absolute; left: 2.25rem; top: 0; height: 50%; width: 1px; background-color: #d0d0d0;"></span>
                                                    <span style="position: absolute; left: 2.25rem; top: 50%; width: 1rem; height: 1px; background-color: #d0d0d0;"></span>
                                                    <a href="{{ route('account::view', $account['id']) }}?from_date={{ $start_date }}&to_date={{ $end_date }}" class="text-decoration-none" style="padding-left: 3rem !important; display: block;">{{ $account['name'] }}</a>
                                                </td>
                                                <td class="text-end pe-3" style="background-color: #ffffff;">
                                                    {{ currency($account['amount']) }}
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                @endif
                            @endforeach
                            <!-- Show Direct Income accounts directly under master category -->
                            @if (!empty($directIncomeMaster['directAccounts']))
                                @foreach ($directIncomeMaster['directAccounts'] as $account)
                                    @if ($account['amount'] > 0)
                                        <tr>
                                            <td style="border-right: 2px solid #dee2e6;"></td>
                                            <td style="border-right: 2px solid #dee2e6;"></td>
                                            <td class="ps-3" style="position: relative;">
                                                <span style="position: absolute; left: 0.75rem; top: 0; height: 50%; width: 1px; background-color: #d0d0d0;"></span>
                                                <span style="position: absolute; left: 0.75rem; top: 50%; width: 1rem; height: 1px; background-color: #d0d0d0;"></span>
                                                <a href="{{ route('account::view', $account['id']) }}?from_date={{ $start_date }}&to_date={{ $end_date }}" class="text-decoration-none" style="padding-left: 1.5rem !important; display: block;">{{ $account['name'] }}</a>
                                            </td>
                                            <td class="text-end pe-3" style="background-color: #ffffff;">
                                                {{ currency($account['amount']) }}
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            @endif
                        @endif
                        <tr>
                            @if ($grossProfit > 0)
                                <td class="ps-3"><strong class="text-success">GROSS PROFIT C/D</strong></td>
                                <td class="text-end pe-3 text-success"><strong class="text-success">{{ currency($grossProfit) }}</strong></td>
                                <td class="ps-3" style="border-right: 2px solid #dee2e6;"></td>
                                <td class="text-end pe-3" style="border-right: 2px solid #dee2e6;"></td>
                            @elseif($grossLoss > 0)
                                <td class="ps-3" style="border-right: 2px solid #dee2e6;"></td>
                                <td class="text-end pe-3" style="border-right: 2px solid #dee2e6;"></td>
                                <td class="ps-3"><strong class="text-danger">GROSS LOSS C/D</strong></td>
                                <td class="text-end pe-3 text-danger"><strong class="text-danger">{{ currency($grossLoss) }}</strong></td>
                            @endif
                        </tr>
                        <!-- Total Row for Top Section -->
                        <tr class="table-light" style="border-top: 2px solid #dee2e6;">
                            <td class="ps-3" style="border-right: 2px solid #dee2e6;"><strong>TOTAL</strong></td>
                            <td class="text-end pe-3" style="border-right: 2px solid #dee2e6;"><strong>{{ currency($leftTotal1) }}</strong></td>
                            <td class="ps-3"><strong>TOTAL</strong></td>
                            <td class="text-end pe-3"><strong>{{ currency($rightTotal1) }}</strong></td>
                        </tr>

                        <!-- Bottom Section: Net Profit/Loss Calculation -->
                        <tr>
                            <!-- Left: Gross Loss B/D -->
                            @if ($grossLoss > 0)
                                <td class="ps-3" style="border-right: 2px solid #dee2e6;"><strong class="text-danger">GROSS LOSS B/D</strong></td>
                                <td class="text-end pe-3 text-danger" style="border-right: 2px solid #dee2e6;"><strong class="text-danger">{{ currency($grossLoss) }}</strong></td>
                                <!-- Right: Empty -->
                                <td class="ps-3"></td>
                                <td class="text-end pe-3"></td>
                            @endif
                            @if ($grossProfit > 0)
                                <!-- Right: Empty -->
                                <td class="ps-3"></td>
                                <td class="text-end pe-3"></td>
                                <!-- Left: Gross Profit B/D -->
                                <td class="ps-3" style="border-right: 2px solid #dee2e6;"><strong class="text-success">GROSS PROFIT B/D</strong></td>
                                <td class="text-end pe-3 text-success" style="border-right: 2px solid #dee2e6;"><strong class="text-success">{{ currency($grossProfit) }}</strong></td>
                            @endif
                        </tr>
                        <!-- Indirect Expense and Indirect Income with hierarchical structure -->
                        @php
                            $indirectExpenseMaster = collect($directExpenseStructure)->firstWhere('name', 'Indirect Expense');
                            $indirectIncomeMaster = collect($directIncomeStructure)->firstWhere('name', 'Indirect Income');
                        @endphp
                        <tr>
                            <!-- Left: Indirect Expense Master -->
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
                            <!-- Right: Indirect Income Master -->
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
                        <!-- Show Indirect Expense groups and accounts if expanded -->
                        @if ($indirectExpenseMaster && in_array($indirectExpenseMaster['id'], $expandedGroups))
                            @foreach ($indirectExpenseMaster['groups'] as $group)
                                @if ($group['total'])
                                    <tr>
                                        <td class="ps-3" style="border-right: 2px solid #dee2e6; position: relative;">
                                            <span style="position: absolute; left: 0.75rem; top: 0; height: 50%; width: 1px; background-color: #d0d0d0;"></span>
                                            <span style="position: absolute; left: 0.75rem; top: 50%; width: 1rem; height: 1px; background-color: #d0d0d0;"></span>
                                            <button type="button" wire:click="toggleGroup({{ $group['id'] }})" class="btn btn-sm btn-link p-0 text-start text-decoration-none" style="padding-left: 1.5rem !important;">
                                                <i class="fa fa-{{ in_array($group['id'], $expandedGroups) ? 'minus' : 'plus' }} me-1"></i>
                                                {{ $group['name'] }}
                                            </button>
                                        </td>
                                        <td class="text-end pe-3" style="border-right: 2px solid #dee2e6; background-color: #f5f5f5;">
                                            {{ currency($group['total']) }}
                                        </td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                @endif
                                @if (in_array($group['id'], $expandedGroups))
                                    @foreach ($group['accounts'] as $account)
                                        @if ($account['amount'] > 0)
                                            <tr>
                                                <td class="ps-3" style="border-right: 2px solid #dee2e6; position: relative;">
                                                    <span style="position: absolute; left: 0.75rem; top: 0; height: 50%; width: 1px; background-color: #d0d0d0;"></span>
                                                    <span style="position: absolute; left: 2.25rem; top: 0; height: 50%; width: 1px; background-color: #d0d0d0;"></span>
                                                    <span style="position: absolute; left: 2.25rem; top: 50%; width: 1rem; height: 1px; background-color: #d0d0d0;"></span>
                                                    <a href="{{ route('account::view', $account['id']) }}?from_date={{ $start_date }}&to_date={{ $end_date }}" class="text-decoration-none" style="padding-left: 3rem !important; display: block;">{{ $account['name'] }}</a>
                                                </td>
                                                <td class="text-end pe-3" style="border-right: 2px solid #dee2e6; background-color: #ffffff;">
                                                    {{ currency($account['amount']) }}
                                                </td>
                                                <td></td>
                                                <td></td>
                                            </tr>
                                        @endif
                                    @endforeach
                                @endif
                            @endforeach
                            <!-- Show Indirect Expense accounts directly under master category -->
                            @if (!empty($indirectExpenseMaster['directAccounts']))
                                @foreach ($indirectExpenseMaster['directAccounts'] as $account)
                                    @if ($account['amount'] > 0)
                                        <tr>
                                            <td class="ps-3" style="border-right: 2px solid #dee2e6; position: relative;">
                                                <span style="position: absolute; left: 0.75rem; top: 0; height: 50%; width: 1px; background-color: #d0d0d0;"></span>
                                                <span style="position: absolute; left: 0.75rem; top: 50%; width: 1rem; height: 1px; background-color: #d0d0d0;"></span>
                                                <a href="{{ route('account::view', $account['id']) }}?from_date={{ $start_date }}&to_date={{ $end_date }}" class="text-decoration-none" style="padding-left: 1.5rem !important; display: block;">{{ $account['name'] }}</a>
                                            </td>
                                            <td class="text-end pe-3" style="border-right: 2px solid #dee2e6; background-color: #ffffff;">
                                                {{ currency($account['amount']) }}
                                            </td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                    @endif
                                @endforeach
                            @endif
                        @endif
                        <!-- Show Indirect Income groups and accounts if expanded -->
                        @if ($indirectIncomeMaster && in_array($indirectIncomeMaster['id'], $expandedGroups))
                            @foreach ($indirectIncomeMaster['groups'] as $group)
                                @if ($group['total'])
                                    <tr>
                                        <td style="border-right: 2px solid #dee2e6;"></td>
                                        <td style="border-right: 2px solid #dee2e6;"></td>
                                        <td class="ps-3" style="position: relative;">
                                            <span style="position: absolute; left: 0.75rem; top: 0; height: 50%; width: 1px; background-color: #d0d0d0;"></span>
                                            <span style="position: absolute; left: 0.75rem; top: 50%; width: 1rem; height: 1px; background-color: #d0d0d0;"></span>
                                            <button type="button" wire:click="toggleGroup({{ $group['id'] }})" class="btn btn-sm btn-link p-0 text-start text-decoration-none" style="padding-left: 1.5rem !important;">
                                                <i class="fa fa-{{ in_array($group['id'], $expandedGroups) ? 'minus' : 'plus' }} me-1"></i>
                                                {{ $group['name'] }}
                                            </button>
                                        </td>
                                        <td class="text-end pe-3" style="background-color: #f5f5f5;">
                                            {{ currency($group['total']) }}
                                        </td>
                                    </tr>
                                @endif
                                @if (in_array($group['id'], $expandedGroups))
                                    @foreach ($group['accounts'] as $account)
                                        @if ($account['amount'] > 0)
                                            <tr>
                                                <td style="border-right: 2px solid #dee2e6;"></td>
                                                <td style="border-right: 2px solid #dee2e6;"></td>
                                                <td class="ps-3" style="position: relative;">
                                                    <span style="position: absolute; left: 0.75rem; top: 0; height: 50%; width: 1px; background-color: #d0d0d0;"></span>
                                                    <span style="position: absolute; left: 2.25rem; top: 0; height: 50%; width: 1px; background-color: #d0d0d0;"></span>
                                                    <span style="position: absolute; left: 2.25rem; top: 50%; width: 1rem; height: 1px; background-color: #d0d0d0;"></span>
                                                    <a href="{{ route('account::view', $account['id']) }}?from_date={{ $start_date }}&to_date={{ $end_date }}" class="text-decoration-none" style="padding-left: 3rem !important; display: block;">{{ $account['name'] }}</a>
                                                </td>
                                                <td class="text-end pe-3" style="background-color: #ffffff;">
                                                    {{ currency($account['amount']) }}
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                @endif
                            @endforeach
                            <!-- Show Indirect Income accounts directly under master category -->
                            @if (!empty($indirectIncomeMaster['directAccounts']))
                                @foreach ($indirectIncomeMaster['directAccounts'] as $account)
                                    @if ($account['amount'] > 0)
                                        <tr>
                                            <td style="border-right: 2px solid #dee2e6;"></td>
                                            <td style="border-right: 2px solid #dee2e6;"></td>
                                            <td class="ps-3" style="position: relative;">
                                                <span style="position: absolute; left: 0.75rem; top: 0; height: 50%; width: 1px; background-color: #d0d0d0;"></span>
                                                <span style="position: absolute; left: 0.75rem; top: 50%; width: 1rem; height: 1px; background-color: #d0d0d0;"></span>
                                                <a href="{{ route('account::view', $account['id']) }}?from_date={{ $start_date }}&to_date={{ $end_date }}" class="text-decoration-none" style="padding-left: 1.5rem !important; display: block;">{{ $account['name'] }}</a>
                                            </td>
                                            <td class="text-end pe-3" style="background-color: #ffffff;">
                                                {{ currency($account['amount']) }}
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            @endif
                        @endif
                        <tr>
                            @if ($netProfitAmount > 0)
                                <!-- Left: Net Profit C/D -->
                                <td class="ps-3" style="border-right: 2px solid #dee2e6;"><strong class="text-success">NET PROFIT C/D</strong></td>
                                <td class="text-end pe-3 text-success" style="border-right: 2px solid #dee2e6;"><strong class="text-success">{{ currency($netProfitAmount) }}</strong></td>
                                <!-- Right: Empty -->
                                <td class="ps-3"></td>
                                <td class="text-end pe-3"></td>
                            @endif
                            @if ($netLossAmount > 0)
                                <!-- Right: Empty -->
                                <td class="ps-3"></td>
                                <td class="text-end pe-3"></td>
                                <!-- Left: Net Loss C/D -->
                                <td class="ps-3" style="border-right: 2px solid #dee2e6;"><strong class="text-danger">NET LOSS C/D</strong></td>
                                <td class="text-end pe-3 text-danger" style="border-right: 2px solid #dee2e6;"><strong class="text-danger">{{ currency($netLossAmount) }}</strong></td>
                            @endif
                        </tr>
                        <!-- Total Row for Bottom Section -->
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
            function copyTable() {
                const table = document.getElementById('profitLossTable');
                const range = document.createRange();
                range.selectNode(table);
                window.getSelection().removeAllRanges();
                window.getSelection().addRange(range);
                document.execCommand('copy');
                window.getSelection().removeAllRanges();
                alert('Table copied to clipboard!');
            }

            function exportToExcel() {
                // Implement Excel export
                alert('Excel export functionality will be implemented');
            }

            function exportToCSV() {
                const table = document.getElementById('profitLossTable');
                let csv = [];
                const rows = table.querySelectorAll('tr');

                for (let i = 0; i < rows.length; i++) {
                    const row = [],
                        cols = rows[i].querySelectorAll('td, th');
                    for (let j = 0; j < cols.length; j++) {
                        row.push(cols[j].innerText.trim());
                    }
                    csv.push(row.join(','));
                }

                const csvContent = csv.join('\n');
                const blob = new Blob([csvContent], {
                    type: 'text/csv;charset=utf-8;'
                });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'profit_loss_report.csv';
                a.click();
                window.URL.revokeObjectURL(url);
            }

            function exportToPDF() {
                window.print();
            }
        </script>
    @endpush
</div>

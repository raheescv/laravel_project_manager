<div>
    {{-- Advanced Payment Filters --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-success text-white py-2 cursor-pointer" data-bs-toggle="collapse" data-bs-target="#advancedFilters">
            <i class="demo-psi-filter me-2"></i> Advanced Payment Filters
            <i class="demo-psi-arrow-down float-end mt-1"></i>
        </div>
        <div class="collapse show" id="advancedFilters">
            <div class="card-body">
                {{-- Property & Location Filters --}}
                <h6 class="text-primary fw-bold mb-3"><i class="demo-psi-building me-1"></i> PROPERTY & LOCATION FILTERS</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label small">Group/Project</label>
                        {{ html()->select('filterGroup', $groups->prepend('Select Group/Project', ''))->value($filterGroup)->class('form-select form-select-sm')->attribute('wire:model', 'filterGroup') }}
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Building</label>
                        {{ html()->select('filterBuilding', $buildings->prepend('Select Building', ''))->value($filterBuilding)->class('form-select form-select-sm')->attribute('wire:model', 'filterBuilding') }}
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Property Type</label>
                        {{ html()->select('filterType', $types->prepend('Select Type', ''))->value($filterType)->class('form-select form-select-sm')->attribute('wire:model', 'filterType') }}
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Property Unit</label>
                        <input type="text" wire:model="filterProperty" class="form-select form-select-sm" placeholder="Search Here">
                    </div>
                </div>

                {{-- Customer & Sales Filters --}}
                <h6 class="text-success fw-bold mb-3"><i class="demo-psi-male me-1"></i> CUSTOMER & SALES FILTERS</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label small">Customer</label>
                        <input type="text" wire:model="filterCustomer" class="form-control form-control-sm" placeholder="Search Customer Name">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Salesman</label>
                        <input type="text" wire:model="filterSalesman" class="form-control form-control-sm" placeholder="Select Salesman">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Ownership</label>
                        <input type="text" wire:model="filterOwnership" class="form-control form-control-sm" placeholder="">
                    </div>
                </div>

                {{-- Payment & Date Filters --}}
                <h6 class="text-info fw-bold mb-3"><i class="demo-psi-calendar me-1"></i> PAYMENT & DATE FILTERS</h6>
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label small">From Date</label>
                        <input type="date" wire:model="dateFrom" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">To Date</label>
                        <input type="date" wire:model="dateTo" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Payment Mode</label>
                        <select wire:model="filterPaymentMode" class="form-select form-select-sm">
                            <option value="">All Modes</option>
                            @foreach($paymentModes as $mode)
                                <option value="{{ $mode->value }}">{{ $mode->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Payment Status</label>
                        <select wire:model="filterPaymentStatus" class="form-select form-select-sm">
                            <option value="">All</option>
                            <option value="pending">Pending Only</option>
                            <option value="paid">Paid Only</option>
                            <option value="overdue">Overdue Only</option>
                        </select>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="row g-3">
                    <div class="col-md-4">
                        <button wire:click="applyFilters" class="btn btn-info text-white w-100">
                            <i class="demo-psi-magnifi-glass me-1"></i> APPLY FILTERS
                        </button>
                    </div>
                    <div class="col-md-4">
                        <button wire:click="download" class="btn btn-success w-100">
                            <i class="demo-psi-download me-1"></i> EXPORT DATA
                        </button>
                    </div>
                    <div class="col-md-4">
                        <button wire:click="resetFilters" class="btn btn-danger w-100">
                            <i class="demo-psi-recycling me-1"></i> RESET FILTERS
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Filter Bar --}}
    <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
        <input type="date" wire:model="dateFrom" class="form-control form-control-sm" style="width: auto;">
        <input type="date" wire:model="dateTo" class="form-control form-control-sm" style="width: auto;">
        <button wire:click="quickFilter('')" class="btn btn-sm {{ $quickFilterMode === '' ? 'btn-info' : 'btn-outline-info' }}">
            <i class="demo-psi-magnifi-glass me-1"></i> Quick Filter
        </button>
        <button wire:click="quickFilter('overdue')" class="btn btn-sm {{ $quickFilterMode === 'overdue' ? 'btn-warning' : 'btn-outline-warning' }}">
            <i class="demo-psi-alarm me-1"></i> Overdue
        </button>
        <button class="btn btn-sm btn-outline-success" data-bs-toggle="collapse" data-bs-target="#advancedFilters">
            <i class="demo-psi-gear me-1"></i> Advanced
        </button>
    </div>

    {{-- Payment Statistics Summary --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-dark text-white py-2 d-flex justify-content-between">
            <span><i class="demo-psi-bar-chart me-1"></i></span>
            <span class="fw-bold">Payment Statistics Summary</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><i class="demo-psi-credit-card me-1"></i> PAYMENT MODE</th>
                            <th class="text-end"><i class="demo-psi-coins me-1"></i> TOTAL AMOUNT</th>
                            <th class="text-end"><i class="demo-psi-money me-1"></i> AMOUNT PAID</th>
                            <th class="text-end"><i class="demo-psi-receipt me-1"></i> BALANCE DUE</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($statistics as $stat)
                            <tr class="{{ $stat['mode'] === 'GRAND TOTAL' ? 'fw-bold table-secondary' : '' }}">
                                <td>
                                    @if($stat['mode'] !== 'GRAND TOTAL')
                                        <span class="badge bg-{{ $stat['color'] }}">{{ $stat['mode'] }}</span>
                                    @else
                                        <i class="demo-psi-calculator me-1"></i> {{ $stat['mode'] }}
                                    @endif
                                </td>
                                <td class="text-end">{{ number_format($stat['total_amount'], 2) }}</td>
                                <td class="text-end">{{ number_format($stat['amount_paid'], 2) }}</td>
                                <td class="text-end {{ $stat['balance_due'] > 0 ? 'text-danger fw-bold' : 'text-success' }}">{{ number_format($stat['balance_due'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Overdue Payment Alert --}}
    @if($overdueAlert['overdue_count'] > 0)
        <div class="card border-warning mb-3">
            <div class="card-header bg-warning text-dark py-2 text-center fw-bold">
                <i class="demo-psi-alarm me-1"></i> Overdue Payment Alert
                <span class="badge bg-danger rounded-pill float-end">{{ $overdueAlert['overdue_count'] }}</span>
            </div>
            <div class="card-body bg-warning bg-opacity-10">
                <div class="row text-center">
                    <div class="col-md-3">
                        <h4 class="text-danger">{{ $overdueAlert['overdue_count'] }}</h4>
                        <small class="text-muted">Overdue Payments</small>
                    </div>
                    <div class="col-md-3">
                        <h4 class="text-danger">{{ number_format($overdueAlert['overdue_amount'], 2) }}</h4>
                        <small class="text-muted">Overdue Amount</small>
                    </div>
                    <div class="col-md-3">
                        <h4 class="text-danger">{{ $overdueAlert['overdue_percentage'] }}%</h4>
                        <small class="text-muted">Overdue Percentage</small>
                    </div>
                    <div class="col-md-3">
                        <h4 class="text-success">{{ $overdueAlert['on_time_count'] }}</h4>
                        <small class="text-muted">On-Time Payments</small>
                    </div>
                </div>
                <p class="text-center mt-2 mb-0 small">
                    <i class="demo-psi-information me-1"></i>
                    <strong>Attention:</strong> There are {{ $overdueAlert['overdue_count'] }} payment(s) that have passed their due date with a total outstanding amount of {{ number_format($overdueAlert['overdue_amount'], 2) }}. Please review and take appropriate action.
                </p>
            </div>
        </div>
    @endif

    {{-- Payment Records --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-dark text-white py-2 d-flex justify-content-between">
            <span><i class="demo-psi-file me-1"></i></span>
            <span class="fw-bold">Payment Records</span>
        </div>
        <div class="card-body p-0">
            {{-- Table Controls --}}
            <div class="d-flex flex-wrap gap-2 p-3 border-bottom align-items-center">
                {{-- Column Visibility Dropdown --}}
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                        Column visibility
                    </button>
                    <div class="dropdown-menu p-2" style="min-width: 200px;">
                        @foreach(['date' => 'Date', 'customer' => 'Customer', 'salesman' => 'Salesman', 'group' => 'Group/Project', 'building' => 'Building', 'property' => 'Property No/Unit', 'ownership' => 'Ownership', 'payment_mode' => 'Payment Mode', 'amount' => 'Amount', 'paid' => 'Paid', 'balance' => 'Balance'] as $col => $label)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" wire:click="toggleColumn('{{ $col }}')" {{ $this->isColumnVisible($col) ? 'checked' : '' }} id="col-{{ $col }}">
                                <label class="form-check-label small" for="col-{{ $col }}">{{ $label }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>

                <select wire:model.live="limit" class="form-select form-select-sm" style="width: auto;">
                    <option value="20">Show 20 rows</option>
                    <option value="50">Show 50 rows</option>
                    <option value="100">Show 100 rows</option>
                </select>

                <button wire:click="updatedSelectAll(true)" class="btn btn-sm btn-outline-primary">Select all</button>

                @if(count($selected) > 0)
                    <button wire:click="paySelected" class="btn btn-sm btn-primary">
                        Pay Selected ({{ count($selected) }})
                    </button>
                @endif

                <div class="ms-auto">
                    <input type="text" wire:model.live.debounce.300ms="search" class="form-control form-control-sm" placeholder="Search:" style="width: 200px;">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="30">
                                <input type="checkbox" class="form-check-input" wire:model.live="selectAll">
                            </th>
                            <th width="40">#</th>
                            @if($this->isColumnVisible('date'))
                                <th class="cursor-pointer" wire:click="sortBy('due_date')">
                                    Date
                                    @if($sortField === 'due_date')
                                        <i class="demo-psi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                    @endif
                                </th>
                            @endif
                            @if($this->isColumnVisible('customer'))
                                <th>Customer</th>
                            @endif
                            @if($this->isColumnVisible('salesman'))
                                <th>Salesman</th>
                            @endif
                            @if($this->isColumnVisible('group'))
                                <th>Group/Project</th>
                            @endif
                            @if($this->isColumnVisible('building'))
                                <th>Building</th>
                            @endif
                            @if($this->isColumnVisible('property'))
                                <th class="cursor-pointer" wire:click="sortBy('rent_out_id')">
                                    Property No/Unit
                                    @if($sortField === 'rent_out_id')
                                        <i class="demo-psi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                    @endif
                                </th>
                            @endif
                            @if($this->isColumnVisible('ownership'))
                                <th>Ownership</th>
                            @endif
                            @if($this->isColumnVisible('payment_mode'))
                                <th>Payment Mode</th>
                            @endif
                            @if($this->isColumnVisible('amount'))
                                <th class="text-end cursor-pointer" wire:click="sortBy('total')">
                                    Amount
                                    @if($sortField === 'total')
                                        <i class="demo-psi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                    @endif
                                </th>
                            @endif
                            @if($this->isColumnVisible('paid'))
                                <th class="text-end cursor-pointer" wire:click="sortBy('paid')">
                                    Paid
                                    @if($sortField === 'paid')
                                        <i class="demo-psi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                    @endif
                                </th>
                            @endif
                            @if($this->isColumnVisible('balance'))
                                <th class="text-end cursor-pointer" wire:click="sortBy('balance')">
                                    Balance
                                    @if($sortField === 'balance')
                                        <i class="demo-psi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                    @endif
                                </th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data as $index => $item)
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input" value="{{ $item->id }}" wire:model.live="selected">
                                </td>
                                <td>{{ $data->firstItem() + $index }}</td>
                                @if($this->isColumnVisible('date'))
                                    <td>{{ $item->due_date?->format('d-m-Y') }}</td>
                                @endif
                                @if($this->isColumnVisible('customer'))
                                    <td>
                                        @if($item->rentOut)
                                            <a href="{{ route($config->viewRoute, $item->rent_out_id) }}" class="text-decoration-none">
                                                {{ $item->rentOut->customer?->name }}
                                            </a>
                                        @endif
                                    </td>
                                @endif
                                @if($this->isColumnVisible('salesman'))
                                    <td>{{ $item->rentOut?->salesman?->name }}</td>
                                @endif
                                @if($this->isColumnVisible('group'))
                                    <td>{{ $item->rentOut?->group?->name }}</td>
                                @endif
                                @if($this->isColumnVisible('building'))
                                    <td>{{ $item->rentOut?->building?->name }}</td>
                                @endif
                                @if($this->isColumnVisible('property'))
                                    <td>
                                        <a href="{{ route($config->viewRoute, $item->rent_out_id) }}" class="text-decoration-none text-success">
                                            {{ $item->rentOut?->property?->number }}
                                        </a>
                                    </td>
                                @endif
                                @if($this->isColumnVisible('ownership'))
                                    <td>{{ $item->rentOut?->property?->ownership }}</td>
                                @endif
                                @if($this->isColumnVisible('payment_mode'))
                                    <td>{{ ucfirst($item->payment_mode ?? '') }}</td>
                                @endif
                                @if($this->isColumnVisible('amount'))
                                    <td class="text-end">{{ number_format($item->total, 2) }}</td>
                                @endif
                                @if($this->isColumnVisible('paid'))
                                    <td class="text-end">{{ number_format($item->paid, 2) }}</td>
                                @endif
                                @if($this->isColumnVisible('balance'))
                                    <td class="text-end {{ $item->balance > 0 ? 'text-danger fw-bold' : 'text-success' }}">
                                        {{ number_format($item->balance, 2) }}
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="20" class="text-center py-4 text-muted">No payment records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($data->hasPages())
            <div class="card-footer bg-white py-3">
                {{ $data->links() }}
            </div>
        @endif
    </div>
</div>

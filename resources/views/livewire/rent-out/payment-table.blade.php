<div>
    <div class="card shadow-sm">
        <div class="card-header bg-light py-3">
            {{-- ═══ Top Bar: Actions + Show/Search/Column Visibility ═══ --}}
            <div class="row mt-3">
                <div class="col-md-6 d-flex flex-wrap gap-2 align-items-center mb-3 mb-md-0">
                    @if (count($selected) > 0)
                        <button wire:click="paySelected" class="btn btn-primary d-flex align-items-center shadow-sm">
                            <i class="fa fa-money me-2 fs-5"></i>
                            Pay Selected
                            <span class="badge bg-light text-primary ms-2">{{ count($selected) }}</span>
                        </button>
                    @endif
                    <button wire:click="download" class="btn btn-success btn-sm d-flex align-items-center shadow-sm">
                        <i class="fa fa-file-excel-o me-md-1 fs-5"></i>
                        <span class="d-none d-md-inline">Excel</span>
                    </button>
                    <button wire:click="quickFilter('overdue')"
                        class="btn btn-sm d-flex align-items-center shadow-sm
                            {{ $quickFilterMode === 'overdue' ? 'btn-warning' : 'btn-outline-warning' }}">
                        <i class="fa fa-bell me-md-1 fs-5"></i>
                        <span class="d-none d-md-inline">Overdue</span>
                    </button>
                    @if ($quickFilterMode !== '')
                        <button wire:click="quickFilter('')"
                            class="btn btn-sm btn-outline-secondary d-flex align-items-center shadow-sm">
                            <i class="fa fa-times me-md-1 fs-5"></i>
                            <span class="d-none d-md-inline">Clear Quick</span>
                        </button>
                    @endif
                </div>
                <div class="col-md-6">
                    <div class="row g-2 align-items-center">
                        <div class="col-auto">
                            <label class="form-label mb-0 text-muted small fw-semibold">Show:</label>
                        </div>
                        <div class="col-auto">
                            <select wire:model.live="limit"
                                class="form-select form-select-sm border-secondary-subtle shadow-sm">
                                <option value="20">20</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                                <option value="500">500</option>
                            </select>
                        </div>
                        <div class="col">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white border-secondary-subtle">
                                    <i class="fa fa-search"></i>
                                </span>
                                <input type="text" wire:model.live.debounce.300ms="search" autofocus
                                    placeholder="Search payments..."
                                    class="form-control form-control-sm border-secondary-subtle shadow-sm"
                                    autocomplete="off">
                            </div>
                        </div>
                        <div class="col-auto">
                            {{-- Column Visibility Dropdown --}}
                            <div class="dropdown">
                                <button
                                    class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1 shadow-sm"
                                    type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                                    aria-expanded="false">
                                    <i class="fa fa-columns"></i>
                                    <span class="d-none d-md-inline">Column visibility</span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow" style="min-width:220px;">
                                    <li class="dropdown-header fw-semibold text-muted"
                                        style="font-size:.75rem; letter-spacing:.04em;">TOGGLE COLUMNS</li>
                                    <li>
                                        <hr class="dropdown-divider my-1">
                                    </li>
                                    @php
                                        $columnLabels = [
                                            'date' => 'Date',
                                            'customer' => 'Customer',
                                            'salesman' => 'Salesman',
                                            'group' => 'Group/Project',
                                            'building' => 'Building',
                                            'property' => 'Property No/Unit',
                                            'ownership' => 'Ownership',
                                            'payment_mode' => 'Payment Mode',
                                            'amount' => 'Amount',
                                            'paid' => 'Paid',
                                            'balance' => 'Balance',
                                        ];
                                    @endphp
                                    @foreach ($columnLabels as $key => $label)
                                        <li>
                                            <label class="dropdown-item d-flex align-items-center gap-2 py-2"
                                                style="cursor:pointer; font-size:.85rem;">
                                                <div class="form-check form-switch mb-0">
                                                    <input class="form-check-input" type="checkbox" role="switch"
                                                        @checked($this->isColumnVisible($key))
                                                        wire:click="toggleColumn('{{ $key }}')"
                                                        style="cursor:pointer;">
                                                </div>
                                                {{ $label }}
                                            </label>
                                        </li>
                                    @endforeach
                                    <li>
                                        <hr class="dropdown-divider my-1">
                                    </li>
                                    <li>
                                        <button class="dropdown-item text-center text-warning fw-semibold"
                                            wire:click="resetColumns" style="font-size:.85rem;">
                                            <i class="fa fa-undo me-1"></i> Reset to Defaults
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-3">

            {{-- ═══ Filter Row 1: Group, Building, Property, Customer, Status ═══ --}}
            <div class="row g-3">
                <div class="col-md-4 col-lg" wire:ignore>
                    <label class="form-label fw-medium">
                        <i class="fa fa-folder-open text-primary me-1 small"></i> Group/Project
                    </label>
                    {{ html()->select('filterGroup', [])->value('')->class('select-property_group_id-list border-secondary-subtle shadow-sm')->id('payment_filterGroup')->placeholder('All Groups')->attribute('wire:model', 'filterGroup') }}
                </div>
                <div class="col-md-4 col-lg" wire:ignore>
                    <label class="form-label fw-medium">
                        <i class="fa fa-building text-primary me-1 small"></i> Building
                    </label>
                    {{ html()->select('filterBuilding', [])->value('')->class('select-property_building_id-list border-secondary-subtle shadow-sm')->id('payment_filterBuilding')->placeholder('All Buildings')->attribute('wire:model', 'filterBuilding')->attribute('data-group-select', '#payment_filterGroup') }}
                </div>
                <div class="col-md-4 col-lg-6" wire:ignore>
                    <label class="form-label fw-medium">
                        <i class="fa fa-home text-primary me-1 small"></i> Property/Unit
                    </label>
                    {{ html()->select('filterProperty', [])->value('')->class('select-property_id-list border-secondary-subtle shadow-sm')->id('payment_filterProperty')->placeholder('All Properties')->attribute('wire:model', 'filterProperty')->attribute('data-building-select', '#payment_filterBuilding')->attribute('data-group-select', '#payment_filterGroup') }}
                </div>
            </div>

            {{-- ═══ Filter Row 2: Dates + Payment Mode ═══ --}}
            <div class="row g-3 mt-1">
                <div class="col-md-4 col-lg-4" wire:ignore>
                    <label class="form-label fw-medium">
                        <i class="fa fa-user text-primary me-1 small"></i> Customer
                    </label>
                    {{ html()->select('filterCustomer', [])->value('')->class('select-customer_id-list border-secondary-subtle shadow-sm')->id('payment_filterCustomer')->placeholder('All Customers')->attribute('wire:model', 'filterCustomer') }}
                </div>
                <div class="col-md-4 col-lg">
                    <label class="form-label fw-medium">
                        <i class="fa fa-flag text-primary me-1 small"></i> Payment Status
                    </label>
                    <select wire:model.live="filterPaymentStatus"
                        class="form-select form-select-sm border-secondary-subtle shadow-sm">
                        <option value="">All</option>
                        <option value="pending">Pending</option>
                        <option value="paid">Paid</option>
                        <option value="overdue">Overdue</option>
                    </select>
                </div>
                <div class="col-md-4 col-lg">
                    <label class="form-label fw-medium">
                        <i class="fa fa-calendar text-primary me-1 small"></i> From Date
                    </label>
                    <input type="date" wire:model.live="dateFrom"
                        class="form-control form-control-sm border-secondary-subtle shadow-sm">
                </div>
                <div class="col-md-4 col-lg">
                    <label class="form-label fw-medium">
                        <i class="fa fa-calendar-check-o text-primary me-1 small"></i> To Date
                    </label>
                    <input type="date" wire:model.live="dateTo"
                        class="form-control form-control-sm border-secondary-subtle shadow-sm">
                </div>
                <div class="col-md-4 col-lg">
                    <label class="form-label fw-medium">
                        <i class="fa fa-credit-card text-primary me-1 small"></i> Payment Mode
                    </label>
                    <select wire:model.live="filterPaymentMode"
                        class="form-select form-select-sm border-secondary-subtle shadow-sm">
                        <option value="">All Modes</option>
                        @foreach ($paymentModes as $mode)
                            <option value="{{ $mode->value }}">{{ $mode->label() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- ═══ Reset Filters ═══ --}}
            <div class="row mt-3">
                <div class="col-12">
                    <button wire:click="resetFilters"
                        class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1 shadow-sm">
                        <i class="fa fa-times"></i>
                        Reset Filters
                    </button>
                </div>
            </div>
        </div>

        {{-- ═══ Statistics Summary ═══ --}}
        @php
            $grand = collect($statistics)->firstWhere('mode', 'GRAND TOTAL');
            $modeIcons = [
                'Cash' => 'fa-money',
                'Cheque' => 'fa-university',
                'POS' => 'fa-credit-card',
                'Bank Transfer' => 'fa-exchange',
            ];
            $collectionPct = $grand && $grand['total_amount'] > 0
                ? round(($grand['amount_paid'] / $grand['total_amount']) * 100, 1)
                : 0;
        @endphp

        <div class="card-body border-bottom bg-body-tertiary">
            {{-- ─── Payment Mode Cards ─── --}}
            <div class="row g-3">
                @foreach ($statistics as $stat)
                    @if ($stat['mode'] !== 'GRAND TOTAL')
                        @php
                            $modePct = $stat['total_amount'] > 0
                                ? round(($stat['amount_paid'] / $stat['total_amount']) * 100, 1)
                                : 0;
                            $iconClass = $modeIcons[$stat['mode']] ?? 'fa-credit-card';
                        @endphp
                        <div class="col-md-6 col-lg-3">
                            <div class="card border-0 shadow-sm h-100 overflow-hidden position-relative">
                                <div class="position-absolute top-0 start-0 w-100 h-100 opacity-10 bg-{{ $stat['color'] }}"></div>
                                <div class="position-absolute top-0 start-0 h-100 bg-{{ $stat['color'] }}" style="width:4px;"></div>
                                <div class="card-body p-3 position-relative">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center bg-{{ $stat['color'] }} text-white shadow-sm"
                                                style="width:38px; height:38px;">
                                                <i class="fa {{ $iconClass }} fs-6"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark" style="font-size:.9rem;">{{ $stat['mode'] }}</div>
                                                <div class="text-muted" style="font-size:.7rem;">Payment Mode</div>
                                            </div>
                                        </div>
                                        <span class="badge bg-{{ $stat['color'] }}-subtle text-{{ $stat['color'] }}-emphasis border border-{{ $stat['color'] }}-subtle">
                                            {{ $modePct }}%
                                        </span>
                                    </div>
                                    <div class="text-uppercase text-muted fw-semibold mb-1" style="font-size:.65rem; letter-spacing:.05em;">
                                        Total Amount
                                    </div>
                                    <div class="fw-bold text-dark mb-2" style="font-size:1.15rem;">
                                        {{ number_format($stat['total_amount'], 2) }}
                                    </div>
                                    <div class="progress mb-2" style="height:5px;">
                                        <div class="progress-bar bg-{{ $stat['color'] }}" role="progressbar"
                                            style="width: {{ $modePct }}%"></div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <div>
                                            <div class="text-muted" style="font-size:.65rem;">PAID</div>
                                            <div class="fw-semibold text-success small">
                                                <i class="fa fa-check-circle me-1"></i>{{ number_format($stat['amount_paid'], 2) }}
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <div class="text-muted" style="font-size:.65rem;">BALANCE</div>
                                            <div class="fw-semibold small {{ $stat['balance_due'] > 0 ? 'text-danger' : 'text-muted' }}">
                                                <i class="fa fa-exclamation-circle me-1"></i>{{ number_format($stat['balance_due'], 2) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            {{-- ─── Grand Total Hero Card ─── --}}
            @if ($grand)
                <div class="row g-3 mt-2">
                    <div class="col-12">
                        <div class="card border-0 shadow text-white overflow-hidden position-relative"
                            style="background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-info) 100%);">
                            <div class="position-absolute top-0 end-0 opacity-10" style="font-size:14rem; line-height:1; transform:translate(20%,-10%);">
                                <i class="fa fa-line-chart"></i>
                            </div>
                            <div class="card-body p-4 position-relative">
                                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center bg-white bg-opacity-25 shadow-sm"
                                            style="width:52px; height:52px;">
                                            <i class="fa fa-calculator fs-4"></i>
                                        </div>
                                        <div>
                                            <div class="text-uppercase fw-bold opacity-75" style="font-size:.7rem; letter-spacing:.1em;">
                                                Payment Summary
                                            </div>
                                            <div class="fw-bold fs-5">Overall Performance</div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-white bg-opacity-25 text-white border border-white border-opacity-25 px-3 py-2">
                                            <i class="fa fa-percent me-1"></i>{{ $collectionPct }}% Collected
                                        </span>
                                    </div>
                                </div>

                                <div class="progress mb-4 bg-white bg-opacity-25" style="height:8px;">
                                    <div class="progress-bar bg-white" role="progressbar"
                                        style="width: {{ $collectionPct }}%"></div>
                                </div>

                                <div class="row g-3 text-center">
                                    <div class="col-6 col-md-3">
                                        <div class="rounded-3 p-3 h-100 text-white" style="background: rgba(255,255,255,0.18); border: 1px solid rgba(255,255,255,0.25);">
                                            <div class="d-flex align-items-center justify-content-center mb-2">
                                                <div class="rounded-circle bg-white bg-opacity-25 d-flex align-items-center justify-content-center"
                                                    style="width:36px; height:36px;">
                                                    <i class="fa fa-money"></i>
                                                </div>
                                            </div>
                                            <div class="text-uppercase opacity-75 fw-semibold" style="font-size:.65rem; letter-spacing:.08em;">
                                                Grand Total
                                            </div>
                                            <div class="fw-bold mt-1" style="font-size:1.3rem;">
                                                {{ number_format($grand['total_amount'], 2) }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="rounded-3 p-3 h-100 text-white" style="background: rgba(255,255,255,0.18); border: 1px solid rgba(255,255,255,0.25);">
                                            <div class="d-flex align-items-center justify-content-center mb-2">
                                                <div class="rounded-circle bg-success bg-opacity-75 d-flex align-items-center justify-content-center"
                                                    style="width:36px; height:36px;">
                                                    <i class="fa fa-check"></i>
                                                </div>
                                            </div>
                                            <div class="text-uppercase opacity-75 fw-semibold" style="font-size:.65rem; letter-spacing:.08em;">
                                                Amount Paid
                                            </div>
                                            <div class="fw-bold mt-1" style="font-size:1.3rem;">
                                                {{ number_format($grand['amount_paid'], 2) }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="rounded-3 p-3 h-100 text-white" style="background: rgba(255,255,255,0.18); border: 1px solid rgba(255,255,255,0.25);">
                                            <div class="d-flex align-items-center justify-content-center mb-2">
                                                <div class="rounded-circle bg-danger bg-opacity-75 d-flex align-items-center justify-content-center"
                                                    style="width:36px; height:36px;">
                                                    <i class="fa fa-clock-o"></i>
                                                </div>
                                            </div>
                                            <div class="text-uppercase opacity-75 fw-semibold" style="font-size:.65rem; letter-spacing:.08em;">
                                                Balance Due
                                            </div>
                                            <div class="fw-bold mt-1" style="font-size:1.3rem;">
                                                {{ number_format($grand['balance_due'], 2) }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="rounded-3 p-3 h-100 text-white" style="background: rgba(255,255,255,0.18); border: 1px solid rgba(255,255,255,0.25);">
                                            <div class="d-flex align-items-center justify-content-center mb-2">
                                                <div class="rounded-circle bg-warning bg-opacity-75 d-flex align-items-center justify-content-center"
                                                    style="width:36px; height:36px;">
                                                    <i class="fa fa-exclamation-triangle"></i>
                                                </div>
                                            </div>
                                            <div class="text-uppercase opacity-75 fw-semibold" style="font-size:.65rem; letter-spacing:.08em;">
                                                Overdue
                                            </div>
                                            <div class="fw-bold mt-1" style="font-size:1.3rem;">
                                                {{ $overdueAlert['overdue_count'] }}
                                                <span class="opacity-75" style="font-size:.85rem;">({{ $overdueAlert['overdue_percentage'] }}%)</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- ─── Overdue Alert ─── --}}
            @if ($overdueAlert['overdue_count'] > 0)
                <div class="alert border-0 shadow-sm mt-3 mb-0 d-flex align-items-center gap-3 position-relative overflow-hidden"
                    style="background: linear-gradient(90deg, rgba(var(--bs-warning-rgb), 0.18) 0%, rgba(var(--bs-warning-rgb), 0.04) 100%); border-left: 4px solid var(--bs-warning) !important;">
                    <div class="rounded-circle bg-warning bg-opacity-25 d-flex align-items-center justify-content-center flex-shrink-0"
                        style="width:44px; height:44px;">
                        <i class="fa fa-exclamation-triangle text-warning fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold text-warning-emphasis mb-1">Attention Required</div>
                        <div class="small text-body-secondary mb-0">
                            <span class="fw-bold text-danger">{{ $overdueAlert['overdue_count'] }}</span>
                            payment(s) are overdue with an outstanding amount of
                            <span class="fw-bold text-danger">{{ number_format($overdueAlert['overdue_amount'], 2) }}</span>.
                            Please follow up promptly.
                        </div>
                    </div>
                    <button wire:click="quickFilter('overdue')"
                        class="btn btn-sm btn-warning shadow-sm flex-shrink-0 d-none d-md-flex align-items-center gap-1">
                        <i class="fa fa-eye"></i> View Overdue
                    </button>
                </div>
            @endif
        </div>

        {{-- ═══ Table Body ═══ --}}
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle border-bottom mb-0 table-sm">
                    <thead class="bg-light text-muted">
                        <tr class="text-capitalize small">
                            <th class="fw-semibold py-2">
                                <div class="form-check ms-1">
                                    <input type="checkbox" wire:model.live="selectAll"
                                        class="form-check-input shadow-sm" id="paymentSelectAllCheckbox" />
                                    <label class="form-check-label" for="paymentSelectAllCheckbox">#</label>
                                </div>
                            </th>
                            @if ($this->isColumnVisible('date'))
                                <th class="fw-semibold">
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="due_date" label="Date" />
                                </th>
                            @endif
                            @if ($this->isColumnVisible('customer'))
                                <th class="fw-semibold">Customer</th>
                            @endif
                            @if ($this->isColumnVisible('salesman'))
                                <th class="fw-semibold">Salesman</th>
                            @endif
                            @if ($this->isColumnVisible('group'))
                                <th class="fw-semibold">Group/Project</th>
                            @endif
                            @if ($this->isColumnVisible('building'))
                                <th class="fw-semibold">Building</th>
                            @endif
                            @if ($this->isColumnVisible('property'))
                                <th class="fw-semibold">
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="rent_out_id" label="Property No/Unit" />
                                </th>
                            @endif
                            @if ($this->isColumnVisible('ownership'))
                                <th class="fw-semibold">Ownership</th>
                            @endif
                            @if ($this->isColumnVisible('payment_mode'))
                                <th class="fw-semibold">Payment Mode</th>
                            @endif
                            @if ($this->isColumnVisible('amount'))
                                <th class="fw-semibold text-end">
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="total" label="Amount" />
                                </th>
                            @endif
                            @if ($this->isColumnVisible('paid'))
                                <th class="fw-semibold text-end">
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="paid" label="Paid" />
                                </th>
                            @endif
                            @if ($this->isColumnVisible('balance'))
                                <th class="fw-semibold text-end">
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="balance" label="Balance" />
                                </th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data as $index => $item)
                            <tr>
                                <td>
                                    <div class="form-check ms-1">
                                        <input type="checkbox" value="{{ $item->id }}"
                                            wire:model.live="selected" class="form-check-input shadow-sm"
                                            id="paymentCheckbox{{ $item->id }}" />
                                        <label class="form-check-label" for="paymentCheckbox{{ $item->id }}">
                                            <span class="badge bg-light text-dark border">#{{ $data->firstItem() + $index }}</span>
                                        </label>
                                    </div>
                                </td>
                                @if ($this->isColumnVisible('date'))
                                    <td class="text-nowrap">
                                        <i class="fa fa-calendar me-1 text-muted opacity-75"></i>
                                        {{ $item->due_date?->format('d-m-Y') }}
                                    </td>
                                @endif
                                @if ($this->isColumnVisible('customer'))
                                    <td>
                                        @if ($item->rentOut)
                                            <a href="{{ route($config->viewRoute, $item->rent_out_id) }}"
                                                class="text-decoration-none">
                                                <i class="fa fa-user me-1 text-muted opacity-75"></i>
                                                {{ $item->rentOut->customer?->name }}
                                            </a>
                                        @endif
                                    </td>
                                @endif
                                @if ($this->isColumnVisible('salesman'))
                                    <td>
                                        <i class="fa fa-id-badge me-1 text-muted opacity-75"></i>
                                        {{ $item->rentOut?->salesman?->name }}
                                    </td>
                                @endif
                                @if ($this->isColumnVisible('group'))
                                    <td>
                                        <i class="fa fa-folder-open me-1 text-muted opacity-75"></i>
                                        {{ $item->rentOut?->group?->name }}
                                    </td>
                                @endif
                                @if ($this->isColumnVisible('building'))
                                    <td>
                                        <i class="fa fa-building-o me-1 text-muted opacity-75"></i>
                                        {{ $item->rentOut?->building?->name }}
                                    </td>
                                @endif
                                @if ($this->isColumnVisible('property'))
                                    <td>
                                        <a href="{{ route($config->viewRoute, $item->rent_out_id) }}"
                                            class="text-decoration-none">
                                            <i class="fa fa-home me-1 text-muted opacity-75"></i>
                                            {{ $item->rentOut?->property?->number }}
                                        </a>
                                    </td>
                                @endif
                                @if ($this->isColumnVisible('ownership'))
                                    <td>{{ $item->rentOut?->property?->ownership }}</td>
                                @endif
                                @if ($this->isColumnVisible('payment_mode'))
                                    <td>
                                        @if ($item->payment_mode)
                                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">
                                                {{ ucfirst($item->payment_mode) }}
                                            </span>
                                        @endif
                                    </td>
                                @endif
                                @if ($this->isColumnVisible('amount'))
                                    <td class="text-end fw-medium">{{ number_format($item->total, 2) }}</td>
                                @endif
                                @if ($this->isColumnVisible('paid'))
                                    <td class="text-end text-success">{{ number_format($item->paid, 2) }}</td>
                                @endif
                                @if ($this->isColumnVisible('balance'))
                                    <td class="text-end {{ $item->balance > 0 ? 'text-danger fw-semibold' : 'text-success' }}">
                                        {{ number_format($item->balance, 2) }}
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="20" class="text-center py-5 text-muted">
                                    <i class="fa fa-inbox fa-2x mb-2 d-block opacity-50"></i>
                                    No payment records found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($data->isNotEmpty())
                        @php
                            $footerColspan = 1; // # column
                            foreach (['date', 'customer', 'salesman', 'group', 'building', 'property', 'ownership', 'payment_mode'] as $col) {
                                if ($this->isColumnVisible($col)) {
                                    $footerColspan++;
                                }
                            }
                        @endphp
                        <tfoot class="bg-light fw-bold border-top">
                            <tr>
                                <td colspan="{{ $footerColspan }}" class="text-end text-uppercase small text-muted">
                                    Total
                                </td>
                                @if ($this->isColumnVisible('amount'))
                                    <td class="text-end">{{ number_format($grand['total_amount'] ?? 0, 2) }}</td>
                                @endif
                                @if ($this->isColumnVisible('paid'))
                                    <td class="text-end text-success">{{ number_format($grand['amount_paid'] ?? 0, 2) }}</td>
                                @endif
                                @if ($this->isColumnVisible('balance'))
                                    <td class="text-end {{ ($grand['balance_due'] ?? 0) > 0 ? 'text-danger' : 'text-success' }}">
                                        {{ number_format($grand['balance_due'] ?? 0, 2) }}
                                    </td>
                                @endif
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
            @if ($data->hasPages())
                <div class="p-3 border-top">
                    {{ $data->links() }}
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                // ── Helper: clear & reload a TomSelect by ID ──
                function clearAndReload(id) {
                    var el = document.getElementById(id);
                    if (el && el.tomSelect) {
                        el.tomSelect.clear();
                        el.tomSelect.clearOptions();
                        el.tomSelect.load('');
                    }
                }

                // ── Cascade: Group → Building → Property ──
                $('#payment_filterGroup').on('change', function(e) {
                    @this.set('filterGroup', $(this).val() || '');
                    clearAndReload('payment_filterBuilding');
                    clearAndReload('payment_filterProperty');
                    @this.set('filterBuilding', '');
                    @this.set('filterProperty', '');
                });
                $('#payment_filterBuilding').on('change', function(e) {
                    @this.set('filterBuilding', $(this).val() || '');
                    clearAndReload('payment_filterProperty');
                    @this.set('filterProperty', '');
                });
                $('#payment_filterProperty').on('change', function(e) {
                    @this.set('filterProperty', $(this).val() || '');
                });
                $('#payment_filterCustomer').on('change', function(e) {
                    @this.set('filterCustomer', $(this).val() || '');
                });
            });
        </script>
    @endpush
</div>

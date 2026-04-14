<div>
    {{-- ═══════════════ KPI Dashboard (Above Filters) ═══════════════ --}}
    <div class="row g-3 mb-3">
        {{-- Total Charged --}}
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 overflow-hidden position-relative">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 bg-primary bg-opacity-10 text-primary"
                            style="width:52px;height:52px;">
                            <i class="fa fa-cogs fa-lg"></i>
                        </div>
                        <div class="flex-grow-1 ms-3 min-w-0">
                            <div class="text-muted small text-uppercase fw-semibold"
                                style="letter-spacing:.04em;font-size:.7rem;">
                                Total Charged
                            </div>
                            <div class="h5 mb-0 fw-bold text-dark text-truncate">
                                {{ number_format($kpis['total_charge'], 2) }}
                            </div>
                            <div class="small text-muted">
                                <i class="fa fa-list-alt me-1"></i>{{ number_format($kpis['transactions']) }} txns
                            </div>
                        </div>
                    </div>
                </div>
                <div class="position-absolute bottom-0 start-0 end-0 bg-primary" style="height:3px;"></div>
            </div>
        </div>

        {{-- Total Collected --}}
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 overflow-hidden position-relative">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 bg-success bg-opacity-10 text-success"
                            style="width:52px;height:52px;">
                            <i class="fa fa-money fa-lg"></i>
                        </div>
                        <div class="flex-grow-1 ms-3 min-w-0">
                            <div class="text-muted small text-uppercase fw-semibold"
                                style="letter-spacing:.04em;font-size:.7rem;">
                                Total Collected
                            </div>
                            <div class="h5 mb-0 fw-bold text-success text-truncate">
                                {{ number_format($kpis['total_paid'], 2) }}
                            </div>
                            <div class="small text-muted">
                                <i class="fa fa-percent me-1"></i>{{ $kpis['collection_rate'] }}% collection rate
                            </div>
                        </div>
                    </div>
                </div>
                <div class="position-absolute bottom-0 start-0 end-0 bg-success" style="height:3px;"></div>
            </div>
        </div>

        {{-- Outstanding --}}
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 overflow-hidden position-relative">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        @php $outClass = $kpis['total_balance'] > 0 ? 'danger' : 'success'; @endphp
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 bg-{{ $outClass }} bg-opacity-10 text-{{ $outClass }}"
                            style="width:52px;height:52px;">
                            <i class="fa fa-exclamation-triangle fa-lg"></i>
                        </div>
                        <div class="flex-grow-1 ms-3 min-w-0">
                            <div class="text-muted small text-uppercase fw-semibold"
                                style="letter-spacing:.04em;font-size:.7rem;">
                                Outstanding
                            </div>
                            <div class="h5 mb-0 fw-bold text-{{ $outClass }} text-truncate">
                                {{ number_format($kpis['total_balance'], 2) }}
                            </div>
                            <div class="small text-muted">Pending balance</div>
                        </div>
                    </div>
                </div>
                <div class="position-absolute bottom-0 start-0 end-0 bg-{{ $outClass }}" style="height:3px;"></div>
            </div>
        </div>

        {{-- Customers --}}
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 overflow-hidden position-relative">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 bg-info bg-opacity-10 text-info"
                            style="width:52px;height:52px;">
                            <i class="fa fa-users fa-lg"></i>
                        </div>
                        <div class="flex-grow-1 ms-3 min-w-0">
                            <div class="text-muted small text-uppercase fw-semibold"
                                style="letter-spacing:.04em;font-size:.7rem;">
                                Customers
                            </div>
                            <div class="h5 mb-0 fw-bold text-info text-truncate">
                                {{ number_format($kpis['customers']) }}
                            </div>
                            <div class="small text-muted">Unique rentouts</div>
                        </div>
                    </div>
                </div>
                <div class="position-absolute bottom-0 start-0 end-0 bg-info" style="height:3px;"></div>
            </div>
        </div>
    </div>

    {{-- ═══════════════ Source Breakdown (Above Filters) ═══════════════ --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white py-2 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center">
                <i class="fa fa-bar-chart text-primary me-2"></i>
                <span class="fw-semibold">Transaction Breakdown by Source</span>
            </div>
            <span class="badge bg-light text-secondary border">
                <i class="fa fa-calendar me-1"></i>
                {{ $dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('d M Y') : '...' }}
                &rarr;
                {{ $dateTo ? \Carbon\Carbon::parse($dateTo)->format('d M Y') : '...' }}
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr class="small text-uppercase text-muted">
                            <th class="ps-3">Source</th>
                            <th class="text-end d-none d-sm-table-cell">Txns</th>
                            <th class="text-end">Charged</th>
                            <th class="text-end">Paid</th>
                            <th class="text-end pe-3">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($summary as $row)
                            <tr>
                                <td class="ps-3">
                                    <span class="badge bg-primary bg-opacity-10 text-primary">
                                        <i class="fa fa-tag me-1"></i>{{ $row['name'] }}
                                    </span>
                                </td>
                                <td class="text-end d-none d-sm-table-cell text-muted">
                                    {{ number_format($row['txns']) }}
                                </td>
                                <td class="text-end">{{ number_format($row['charge'], 2) }}</td>
                                <td class="text-end text-success">{{ number_format($row['paid'], 2) }}</td>
                                <td
                                    class="text-end pe-3 {{ $row['balance'] > 0 ? 'text-danger fw-semibold' : 'text-success' }}">
                                    {{ number_format($row['balance'], 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted small">
                                    <i class="fa fa-inbox me-1"></i> No category data for the selected filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if (count($summary))
                        <tfoot class="table-light fw-bold border-top">
                            <tr>
                                <td class="ps-3"><i class="fa fa-calculator me-1 text-muted"></i> Total</td>
                                <td class="text-end d-none d-sm-table-cell">
                                    {{ number_format($kpis['transactions']) }}
                                </td>
                                <td class="text-end">{{ number_format($kpis['total_charge'], 2) }}</td>
                                <td class="text-end text-success">{{ number_format($kpis['total_paid'], 2) }}</td>
                                <td
                                    class="text-end pe-3 {{ $kpis['total_balance'] > 0 ? 'text-danger' : 'text-success' }}">
                                    {{ number_format($kpis['total_balance'], 2) }}
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    {{-- ═══════════════ Main Card: Filters + Table ═══════════════ --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light py-3">
            {{-- ─── Top Action Bar ─── --}}
            <div class="row g-2 align-items-center">
                <div class="col-12 col-md-auto d-flex flex-wrap gap-2 align-items-center">
                    <button wire:click="download"
                        class="btn btn-success btn-sm d-inline-flex align-items-center gap-1 shadow-sm">
                        <i class="fa fa-file-excel-o"></i>
                        <span>Export Excel</span>
                    </button>
                </div>

                <div class="col-12 col-md d-flex flex-wrap gap-2 align-items-center justify-content-md-end">
                    <div class="d-flex align-items-center gap-1">
                        <label class="form-label mb-0 text-muted small fw-semibold d-none d-sm-inline">Show</label>
                        <select wire:model.live="limit"
                            class="form-select form-select-sm border-secondary-subtle shadow-sm" style="width:auto;">
                            <option value="20">20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="200">200</option>
                        </select>
                    </div>

                    <div class="input-group input-group-sm flex-grow-1" style="max-width:280px;min-width:180px;">
                        <span class="input-group-text bg-white border-secondary-subtle">
                            <i class="fa fa-search text-muted"></i>
                        </span>
                        <input type="text" wire:model.live.debounce.300ms="search"
                            placeholder="Search customer / id / voucher..."
                            class="form-control form-control-sm border-secondary-subtle shadow-sm" autocomplete="off">
                    </div>

                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 shadow-sm"
                            type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                            <i class="fa fa-columns"></i>
                            <span class="d-none d-md-inline">Columns</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow" style="min-width:220px;">
                            <li class="dropdown-header fw-semibold text-muted"
                                style="font-size:.75rem;letter-spacing:.04em;">TOGGLE COLUMNS</li>
                            <li>
                                <hr class="dropdown-divider my-1">
                            </li>
                            @php
                                $columnLabels = [
                                    'date' => 'Date',
                                    'voucher' => 'Voucher No',
                                    'customer' => 'Customer',
                                    'group' => 'Property Group',
                                    'building' => 'Building',
                                    'property' => 'Property/Unit',
                                    'category' => 'Category',
                                    'source' => 'Source',
                                    'group_col' => 'Txn Group',
                                    'payment_type' => 'Payment Type',
                                    'remark' => 'Remark',
                                    'charge' => 'Charge',
                                    'paid' => 'Paid',
                                    'balance' => 'Balance',
                                ];
                            @endphp
                            @foreach ($columnLabels as $key => $label)
                                <li>
                                    <label class="dropdown-item d-flex align-items-center gap-2 py-2"
                                        style="cursor:pointer;font-size:.85rem;">
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input" type="checkbox" role="switch"
                                                @checked($this->isColumnVisible($key))
                                                wire:click="toggleColumn('{{ $key }}')" style="cursor:pointer;">
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

            <hr class="my-3">

            {{-- ─── Filters Row 1: Property scope ─── --}}
            <div class="row g-3">
                <div class="col-12 col-sm-6 col-lg-4 col-xl-3" wire:ignore>
                    <label class="form-label fw-medium small mb-1">
                        <i class="fa fa-folder-open text-primary me-1"></i> Group/Project
                    </label>
                    {{ html()->select('filterGroup', [])->value('')->class('select-property_group_id-list border-secondary-subtle shadow-sm')->id('daybook_filterGroup')->placeholder('All Groups')->attribute('wire:model', 'filterGroup') }}
                </div>
                <div class="col-12 col-sm-6 col-lg-4 col-xl-3" wire:ignore>
                    <label class="form-label fw-medium small mb-1">
                        <i class="fa fa-building text-primary me-1"></i> Building
                    </label>
                    {{ html()->select('filterBuilding', [])->value('')->class('select-property_building_id-list border-secondary-subtle shadow-sm')->id('daybook_filterBuilding')->placeholder('All Buildings')->attribute('wire:model', 'filterBuilding')->attribute('data-group-select', '#daybook_filterGroup') }}
                </div>
                <div class="col-12 col-sm-6 col-lg-4 col-xl-3" wire:ignore>
                    <label class="form-label fw-medium small mb-1">
                        <i class="fa fa-home text-primary me-1"></i> Property/Unit
                    </label>
                    {{ html()->select('filterProperty', [])->value('')->class('select-property_id-list border-secondary-subtle shadow-sm')->id('daybook_filterProperty')->placeholder('All Properties')->attribute('wire:model', 'filterProperty')->attribute('data-building-select', '#daybook_filterBuilding')->attribute('data-group-select', '#daybook_filterGroup') }}
                </div>
                <div class="col-12 col-sm-6 col-lg-4 col-xl-3" wire:ignore>
                    <label class="form-label fw-medium small mb-1">
                        <i class="fa fa-user text-primary me-1"></i> Customer
                    </label>
                    {{ html()->select('filterCustomer', [])->value('')->class('select-customer_id-list border-secondary-subtle shadow-sm')->id('daybook_filterCustomer')->placeholder('All Customers')->attribute('wire:model', 'filterCustomer') }}
                </div>
            </div>

            {{-- ─── Filters Row 2: Type/Ownership/Date range ─── --}}
            <div class="row g-3 mt-1">
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <label class="form-label fw-medium small mb-1">
                        <i class="fa fa-th-large text-primary me-1"></i> Property Type
                    </label>
                    <select wire:model.live="filterType"
                        class="form-select form-select-sm border-secondary-subtle shadow-sm">
                        <option value="">All Types</option>
                        @foreach ($types as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <label class="form-label fw-medium small mb-1">
                        <i class="fa fa-key text-primary me-1"></i> Ownership
                    </label>
                    <select wire:model.live="filterOwnership"
                        class="form-select form-select-sm border-secondary-subtle shadow-sm">
                        <option value="">All</option>
                        <option value="owned">Owned</option>
                        <option value="rented">Rented</option>
                    </select>
                </div>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <label class="form-label fw-medium small mb-1">
                        <i class="fa fa-calendar text-primary me-1"></i> From
                    </label>
                    <input type="date" wire:model="dateFrom"
                        class="form-control form-control-sm border-secondary-subtle shadow-sm">
                </div>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <label class="form-label fw-medium small mb-1">
                        <i class="fa fa-calendar-check-o text-primary me-1"></i> To
                    </label>
                    <input type="date" wire:model="dateTo"
                        class="form-control form-control-sm border-secondary-subtle shadow-sm">
                </div>
                <div class="col-12 col-md-8 col-lg-6 col-xl-4">
                    <label class="form-label fw-medium small mb-1">
                        <i class="fa fa-tags text-primary me-1"></i> Service Category
                    </label>
                    <select wire:model.live="filterCategory"
                        class="form-select form-select-sm border-secondary-subtle shadow-sm">
                        <option value="">All Categories</option>
                        @foreach ($categories as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- ─── Filters Row 3: Source / Direction / Payment Type ─── --}}
            <div class="row g-3 mt-1">
                <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                    <label class="form-label fw-medium small mb-1">
                        <i class="fa fa-filter text-primary me-1"></i> Source
                    </label>
                    <select wire:model.live="filterSource"
                        class="form-select form-select-sm border-secondary-subtle shadow-sm">
                        <option value="">All Sources</option>
                        @foreach ($sources as $src)
                            <option value="{{ $src }}">{{ $src }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                    <label class="form-label fw-medium small mb-1">
                        <i class="fa fa-object-group text-primary me-1"></i> Group
                    </label>
                    <select wire:model.live="filterGroupCol"
                        class="form-select form-select-sm border-secondary-subtle shadow-sm">
                        <option value="">All Groups</option>
                        @foreach ($groupCols as $g)
                            <option value="{{ $g }}">{{ ucfirst(str_replace('_', ' ', $g)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                    <label class="form-label fw-medium small mb-1">
                        <i class="fa fa-exchange text-primary me-1"></i> Direction
                    </label>
                    <select wire:model.live="filterDirection"
                        class="form-select form-select-sm border-secondary-subtle shadow-sm">
                        <option value="">All Movements</option>
                        <option value="charge">Charges Only</option>
                        <option value="payment">Payments Only</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                    <label class="form-label fw-medium small mb-1">
                        <i class="fa fa-credit-card text-primary me-1"></i> Payment Type
                    </label>
                    <select wire:model.live="filterPaymentType"
                        class="form-select form-select-sm border-secondary-subtle shadow-sm">
                        <option value="">All</option>
                        @foreach ($paymentTypes as $type)
                            <option value="{{ $type }}">{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-12 col-xl-3 d-flex align-items-end gap-2">
                    <button wire:click="applyFilters"
                        class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1 shadow-sm flex-grow-1 flex-xl-grow-0">
                        <i class="fa fa-filter"></i> Apply
                    </button>
                    <button wire:click="resetFilters"
                        class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1 shadow-sm flex-grow-1 flex-xl-grow-0">
                        <i class="fa fa-times"></i> Reset
                    </button>
                </div>
            </div>
        </div>

        {{-- ─── Detail Table ─── --}}
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle border-bottom mb-0 table-sm">
                    <thead class="bg-light text-muted">
                        <tr class="text-capitalize small">
                            <th class="fw-semibold py-2 ps-3" style="width:60px;">#</th>
                            @if ($this->isColumnVisible('date'))
                                <th class="fw-semibold">
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="date"
                                        label="Date" />
                                </th>
                            @endif
                            @if ($this->isColumnVisible('voucher'))
                                <th class="fw-semibold d-none d-md-table-cell">Voucher</th>
                            @endif
                            @if ($this->isColumnVisible('customer'))
                                <th class="fw-semibold">Customer</th>
                            @endif
                            @if ($this->isColumnVisible('group'))
                                <th class="fw-semibold d-none d-xl-table-cell">Group</th>
                            @endif
                            @if ($this->isColumnVisible('building'))
                                <th class="fw-semibold d-none d-xl-table-cell">Building</th>
                            @endif
                            @if ($this->isColumnVisible('property'))
                                <th class="fw-semibold d-none d-md-table-cell">Property</th>
                            @endif
                            @if ($this->isColumnVisible('category'))
                                <th class="fw-semibold">Category</th>
                            @endif
                            @if ($this->isColumnVisible('source'))
                                <th class="fw-semibold d-none d-lg-table-cell">
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField"
                                        field="source" label="Source" />
                                </th>
                            @endif
                            @if ($this->isColumnVisible('group_col'))
                                <th class="fw-semibold d-none d-xl-table-cell">Txn Group</th>
                            @endif
                            @if ($this->isColumnVisible('payment_type'))
                                <th class="fw-semibold d-none d-lg-table-cell">Payment Type</th>
                            @endif
                            @if ($this->isColumnVisible('remark'))
                                <th class="fw-semibold d-none d-xl-table-cell">Remark</th>
                            @endif
                            @if ($this->isColumnVisible('charge'))
                                <th class="fw-semibold text-end">
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField"
                                        field="debit" label="Charge" />
                                </th>
                            @endif
                            @if ($this->isColumnVisible('paid'))
                                <th class="fw-semibold text-end">
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField"
                                        field="credit" label="Paid" />
                                </th>
                            @endif
                            @if ($this->isColumnVisible('balance'))
                                <th class="fw-semibold text-end pe-3">Balance</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data as $index => $item)
                            @php
                                $rowCharge = (float) $item->debit;
                                $rowPaid = (float) $item->credit;
                                $rowBalance = $rowCharge - $rowPaid;
                                $categoryName =
                                    $item->category && isset($categories[$item->category])
                                        ? $categories[$item->category]
                                        : ($item->category ?: '—');
                            @endphp
                            <tr>
                                <td class="ps-3">
                                    <span class="badge bg-light text-dark border">
                                        #{{ $data->firstItem() + $index }}
                                    </span>
                                </td>
                                @if ($this->isColumnVisible('date'))
                                    <td class="text-nowrap">
                                        <i class="fa fa-calendar me-1 text-muted opacity-75"></i>
                                        <span class="small">{{ $item->date?->format('d-m-Y') }}</span>
                                    </td>
                                @endif
                                @if ($this->isColumnVisible('voucher'))
                                    <td class="d-none d-md-table-cell small text-muted">
                                        {{ $item->voucher_no ?: '—' }}
                                    </td>
                                @endif
                                @if ($this->isColumnVisible('customer'))
                                    <td>
                                        @if ($item->rentOut)
                                            <a href="{{ route('property::rent::view', $item->rent_out_id) }}"
                                                class="text-decoration-none text-body fw-medium">
                                                <i class="fa fa-user me-1 text-muted opacity-75"></i>
                                                {{ $item->rentOut?->customer?->name ?? '—' }}
                                            </a>
                                            <div class="small text-muted d-md-none">
                                                <i class="fa fa-home me-1 opacity-75"></i>
                                                {{ $item->rentOut?->property?->number }}
                                            </div>
                                        @else
                                            —
                                        @endif
                                    </td>
                                @endif
                                @if ($this->isColumnVisible('group'))
                                    <td class="d-none d-xl-table-cell small text-muted">
                                        {{ $item->rentOut?->group?->name ?? '—' }}
                                    </td>
                                @endif
                                @if ($this->isColumnVisible('building'))
                                    <td class="d-none d-xl-table-cell small text-muted">
                                        {{ $item->rentOut?->building?->name ?? '—' }}
                                    </td>
                                @endif
                                @if ($this->isColumnVisible('property'))
                                    <td class="d-none d-md-table-cell">
                                        <i class="fa fa-home me-1 text-muted opacity-75"></i>
                                        {{ $item->rentOut?->property?->number ?? '—' }}
                                    </td>
                                @endif
                                @if ($this->isColumnVisible('category'))
                                    <td>
                                        <span class="badge bg-primary bg-opacity-10 text-primary">
                                            {{ $categoryName }}
                                        </span>
                                    </td>
                                @endif
                                @if ($this->isColumnVisible('source'))
                                    <td class="d-none d-lg-table-cell">
                                        @php
                                            $srcMap = [
                                                'Service' => 'info',
                                                'ServiceCharge' => 'warning',
                                                'Rent' => 'primary',
                                                'PaymentTerm' => 'primary',
                                                'Utility' => 'success',
                                                'UtilityTerm' => 'success',
                                                'Security' => 'secondary',
                                                'Cheque' => 'dark',
                                            ];
                                            $srcColor = $srcMap[$item->source] ?? 'secondary';
                                        @endphp
                                        <span
                                            class="badge bg-{{ $srcColor }} bg-opacity-10 text-{{ $srcColor }}">
                                            {{ $item->source ?: '—' }}
                                        </span>
                                    </td>
                                @endif
                                @if ($this->isColumnVisible('group_col'))
                                    <td class="d-none d-xl-table-cell small text-muted">
                                        {{ $item->group ? ucfirst(str_replace('_', ' ', $item->group)) : '—' }}
                                    </td>
                                @endif
                                @if ($this->isColumnVisible('payment_type'))
                                    <td class="d-none d-lg-table-cell small text-muted">
                                        {{ $item->payment_type ? ucfirst(str_replace('_', ' ', $item->payment_type)) : '—' }}
                                    </td>
                                @endif
                                @if ($this->isColumnVisible('remark'))
                                    <td class="d-none d-xl-table-cell text-muted small" style="max-width:240px;">
                                        <span class="d-inline-block text-truncate" style="max-width:240px;"
                                            title="{{ $item->remark }}">
                                            {{ $item->remark ?: '—' }}
                                        </span>
                                    </td>
                                @endif
                                @if ($this->isColumnVisible('charge'))
                                    <td class="text-end fw-medium">
                                        @if ($rowCharge > 0)
                                            {{ number_format($rowCharge, 2) }}
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                @endif
                                @if ($this->isColumnVisible('paid'))
                                    <td class="text-end fw-medium text-success">
                                        @if ($rowPaid > 0)
                                            {{ number_format($rowPaid, 2) }}
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                @endif
                                @if ($this->isColumnVisible('balance'))
                                    <td
                                        class="text-end pe-3 {{ $rowBalance > 0 ? 'text-danger fw-bold' : ($rowBalance < 0 ? 'text-success' : 'text-muted') }}">
                                        {{ number_format($rowBalance, 2) }}
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="20" class="text-center py-5 text-muted">
                                    <i class="fa fa-inbox fa-2x mb-2 d-block opacity-50"></i>
                                    <div>No service transactions found for the selected filters.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($data->count())
                        <tfoot class="bg-light fw-semibold">
                            @php
                                $pageCharge = $data->sum('debit');
                                $pagePaid = $data->sum('credit');
                                $pageBalance = $pageCharge - $pagePaid;
                                $hiddenBefore = collect([
                                    'date',
                                    'voucher',
                                    'customer',
                                    'group',
                                    'building',
                                    'property',
                                    'category',
                                    'source',
                                    'group_col',
                                    'payment_type',
                                    'remark',
                                ])->filter(fn($c) => $this->isColumnVisible($c))->count();
                            @endphp
                            <tr>
                                <td colspan="{{ $hiddenBefore + 1 }}" class="text-end ps-3">Page Total</td>
                                @if ($this->isColumnVisible('charge'))
                                    <td class="text-end">{{ number_format($pageCharge, 2) }}</td>
                                @endif
                                @if ($this->isColumnVisible('paid'))
                                    <td class="text-end text-success">{{ number_format($pagePaid, 2) }}</td>
                                @endif
                                @if ($this->isColumnVisible('balance'))
                                    <td
                                        class="text-end pe-3 {{ $pageBalance > 0 ? 'text-danger' : 'text-success' }}">
                                        {{ number_format($pageBalance, 2) }}
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
                function clearAndReload(id) {
                    var el = document.getElementById(id);
                    if (el && el.tomSelect) {
                        el.tomSelect.clear();
                        el.tomSelect.clearOptions();
                        el.tomSelect.load('');
                    }
                }

                $('#daybook_filterGroup').on('change', function() {
                    @this.set('filterGroup', $(this).val() || '');
                    clearAndReload('daybook_filterBuilding');
                    clearAndReload('daybook_filterProperty');
                    @this.set('filterBuilding', '');
                    @this.set('filterProperty', '');
                });
                $('#daybook_filterBuilding').on('change', function() {
                    @this.set('filterBuilding', $(this).val() || '');
                    clearAndReload('daybook_filterProperty');
                    @this.set('filterProperty', '');
                });
                $('#daybook_filterProperty').on('change', function() {
                    @this.set('filterProperty', $(this).val() || '');
                });
                $('#daybook_filterCustomer').on('change', function() {
                    @this.set('filterCustomer', $(this).val() || '');
                });
            });
        </script>
    @endpush
</div>

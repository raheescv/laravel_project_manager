{{--
    One row per sale agreement: charges aggregated, receipts folded in, balance
    outstanding. A row expands to the individual charge lines behind it.

    Column visibility is seeded from (and written back to) this user's saved
    preference, and toggling stays client side so it never re-renders (and never
    wipes) the TomSelect filter row above the table.
--}}
<div x-data="{
    columns: @js($this->columns),
    labels: @js($this->columnLabels()),
    expanded: [],
    async setColumn(key, visible) {
        this.columns[key] = visible;
        this.columns = await $wire.setColumnVisibility(key, visible);
    },
    async resetColumns() {
        this.columns = await $wire.resetColumns();
    },
    isOpen(id) {
        return this.expanded.includes(id);
    },
    toggleRow(id) {
        this.expanded = this.isOpen(id) ? this.expanded.filter(i => i !== id) : [...this.expanded, id];
    },
    {{-- colspan for the detail and empty rows: index column plus whatever is visible. --}}
    spanAll() {
        return 1 + Object.keys(this.columns).filter(k => this.columns[k]).length;
    },
}">
    <style>
        [x-cloak] {
            display: none !important;
        }

        .svc-row-toggle {
            border: 0;
            background: transparent;
            line-height: 1;
            padding: 0;
            color: #6c757d;
        }

        .svc-row-toggle:hover {
            color: #0d6efd;
        }

        .svc-meter {
            height: 5px;
            border-radius: 3px;
            background: rgba(13, 110, 253, .12);
            overflow: hidden;
        }

        .svc-meter>span {
            display: block;
            height: 100%;
            border-radius: 3px;
        }

        .svc-detail {
            background: #f8f9fb;
        }
    </style>

    {{-- ═══════════════ KPI Dashboard ═══════════════ --}}
    <div class="row g-3 mb-3">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 overflow-hidden position-relative">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 bg-primary bg-opacity-10 text-primary"
                            style="width:52px;height:52px;">
                            <i class="fa fa-cogs fa-lg"></i>
                        </div>
                        <div class="flex-grow-1 ms-3 min-w-0">
                            <div class="text-muted small text-uppercase fw-semibold"
                                style="letter-spacing:.04em;font-size:.7rem;">Charged</div>
                            <div class="h5 mb-0 fw-bold text-dark text-truncate">
                                {{ number_format($kpis['amount'], 2) }}
                            </div>
                            <div class="small text-muted">
                                <i class="fa fa-list-alt me-1"></i>{{ number_format($kpis['lines']) }} charges
                            </div>
                        </div>
                    </div>
                </div>
                <div class="position-absolute bottom-0 start-0 end-0 bg-primary" style="height:3px;"></div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 overflow-hidden position-relative">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 bg-success bg-opacity-10 text-success"
                            style="width:52px;height:52px;">
                            <i class="fa fa-check-circle fa-lg"></i>
                        </div>
                        <div class="flex-grow-1 ms-3 min-w-0">
                            <div class="text-muted small text-uppercase fw-semibold"
                                style="letter-spacing:.04em;font-size:.7rem;">Collected</div>
                            <div class="h5 mb-0 fw-bold text-success text-truncate">
                                {{ number_format($kpis['paid'], 2) }}
                            </div>
                            <div class="svc-meter mt-2">
                                <span class="bg-success"
                                    style="width: {{ min(100, max(0, $kpis['collection_rate'])) }}%;"></span>
                            </div>
                            <div class="small text-muted mt-1">{{ $kpis['collection_rate'] }}% of charged</div>
                        </div>
                    </div>
                </div>
                <div class="position-absolute bottom-0 start-0 end-0 bg-success" style="height:3px;"></div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 overflow-hidden position-relative">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 bg-danger bg-opacity-10 text-danger"
                            style="width:52px;height:52px;">
                            <i class="fa fa-exclamation-triangle fa-lg"></i>
                        </div>
                        <div class="flex-grow-1 ms-3 min-w-0">
                            <div class="text-muted small text-uppercase fw-semibold"
                                style="letter-spacing:.04em;font-size:.7rem;">Outstanding</div>
                            <div class="h5 mb-0 fw-bold text-danger text-truncate">
                                {{ number_format($kpis['balance'], 2) }}
                            </div>
                            <div class="small">
                                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none small"
                                    wire:click="$set('filterStatus', 'unpaid')">
                                    Show unpaid only
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="position-absolute bottom-0 start-0 end-0 bg-danger" style="height:3px;"></div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 overflow-hidden position-relative">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 bg-info bg-opacity-10 text-info"
                            style="width:52px;height:52px;">
                            <i class="fa fa-file-text-o fa-lg"></i>
                        </div>
                        <div class="flex-grow-1 ms-3 min-w-0">
                            <div class="text-muted small text-uppercase fw-semibold"
                                style="letter-spacing:.04em;font-size:.7rem;">Agreements</div>
                            <div class="h5 mb-0 fw-bold text-info text-truncate">
                                {{ number_format($kpis['agreements']) }}
                            </div>
                            <div class="small text-muted text-truncate">
                                <i class="fa fa-calendar me-1"></i>
                                {{ $dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('d M Y') : '...' }}
                                &rarr;
                                {{ $dateTo ? \Carbon\Carbon::parse($dateTo)->format('d M Y') : '...' }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="position-absolute bottom-0 start-0 end-0 bg-info" style="height:3px;"></div>
            </div>
        </div>
    </div>

    {{-- ═══════════════ Group Breakdown ═══════════════ --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white py-2 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center">
                <i class="fa fa-bar-chart text-primary me-2"></i>
                <span class="fw-semibold">Service Charge Breakdown by Project/Group</span>
            </div>
            <span class="badge bg-light text-muted border">{{ count($summary) }} groups</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr class="small text-uppercase text-muted">
                            <th class="ps-3">Project / Group</th>
                            <th class="text-end d-none d-sm-table-cell">Agreements</th>
                            <th class="text-end d-none d-md-table-cell">Charges</th>
                            <th class="text-end">Charged</th>
                            <th class="text-end">Paid</th>
                            <th class="text-end">Balance</th>
                            <th class="text-end pe-3" style="min-width:130px;">Collected</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($summary as $row)
                            <tr>
                                <td class="ps-3">
                                    <span class="badge bg-primary bg-opacity-10 text-primary">
                                        <i class="fa fa-folder-open me-1"></i>{{ $row['name'] }}
                                    </span>
                                </td>
                                <td class="text-end d-none d-sm-table-cell text-muted">
                                    {{ number_format($row['agreements']) }}
                                </td>
                                <td class="text-end d-none d-md-table-cell text-muted">
                                    {{ number_format($row['lines']) }}
                                </td>
                                <td class="text-end">{{ number_format($row['amount'], 2) }}</td>
                                <td class="text-end text-success">{{ number_format($row['paid'], 2) }}</td>
                                <td class="text-end {{ $row['balance'] > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">
                                    {{ number_format($row['balance'], 2) }}
                                </td>
                                <td class="pe-3">
                                    <div class="d-flex align-items-center gap-2 justify-content-end">
                                        <div class="svc-meter flex-grow-1" style="max-width:80px;">
                                            <span
                                                class="{{ $row['collection_rate'] >= 100 ? 'bg-success' : ($row['collection_rate'] > 0 ? 'bg-warning' : 'bg-danger') }}"
                                                style="width: {{ min(100, max(0, $row['collection_rate'])) }}%;"></span>
                                        </div>
                                        <span class="small text-muted"
                                            style="min-width:44px;">{{ $row['collection_rate'] }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted small">
                                    <i class="fa fa-inbox me-1"></i> No data for the selected filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if (count($summary))
                        <tfoot class="table-light fw-bold border-top">
                            <tr>
                                <td class="ps-3"><i class="fa fa-calculator me-1 text-muted"></i> Total</td>
                                <td class="text-end d-none d-sm-table-cell">{{ number_format($kpis['agreements']) }}</td>
                                <td class="text-end d-none d-md-table-cell">{{ number_format($kpis['lines']) }}</td>
                                <td class="text-end">{{ number_format($kpis['amount'], 2) }}</td>
                                <td class="text-end text-success">{{ number_format($kpis['paid'], 2) }}</td>
                                <td class="text-end {{ $kpis['balance'] > 0 ? 'text-danger' : '' }}">
                                    {{ number_format($kpis['balance'], 2) }}
                                </td>
                                <td class="text-end pe-3">{{ $kpis['collection_rate'] }}%</td>
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
            {{-- Top Action Bar --}}
            <div class="row g-2 align-items-center">
                <div class="col-12 col-md-auto d-flex flex-wrap gap-2 align-items-center">
                    <button wire:click="download"
                        class="btn btn-success btn-sm d-inline-flex align-items-center gap-1 shadow-sm">
                        <i class="fa fa-file-excel-o"></i>
                        <span>Export Excel</span>
                    </button>
                    <div class="btn-group btn-group-sm shadow-sm" role="group" aria-label="Settlement filter">
                        @foreach (['' => 'All', 'unpaid' => 'Unpaid', 'partial' => 'Partial', 'paid' => 'Paid'] as $value => $label)
                            <button type="button"
                                class="btn {{ $filterStatus === $value ? 'btn-primary' : 'btn-outline-secondary' }}"
                                wire:click="$set('filterStatus', '{{ $value }}')">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
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
                            placeholder="Search customer / unit / remark..."
                            class="form-control form-control-sm border-secondary-subtle shadow-sm" autocomplete="off">
                    </div>

                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 shadow-sm"
                            type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                            <i class="fa fa-columns"></i>
                            <span class="d-none d-md-inline">Columns</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow"
                            style="min-width:230px;max-height:60vh;overflow-y:auto;">
                            <li class="dropdown-header fw-semibold text-muted"
                                style="font-size:.75rem;letter-spacing:.04em;">
                                TOGGLE COLUMNS
                            </li>
                            <li>
                                <hr class="dropdown-divider my-1">
                            </li>
                            <template x-for="(label, key) in labels" :key="key">
                                <li>
                                    <label class="dropdown-item d-flex align-items-center gap-2 py-2"
                                        style="cursor:pointer;font-size:.85rem;">
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input" type="checkbox" role="switch"
                                                :checked="columns[key]" @change="setColumn(key, $event.target.checked)"
                                                style="cursor:pointer;">
                                        </div>
                                        <span x-text="label"></span>
                                    </label>
                                </li>
                            </template>
                            <li>
                                <hr class="dropdown-divider my-1">
                            </li>
                            <li>
                                <button class="dropdown-item text-center text-warning fw-semibold"
                                    @click="resetColumns()" style="font-size:.85rem;">
                                    <i class="fa fa-undo me-1"></i> Reset to Defaults
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <hr class="my-3">

            {{-- Filters Row 1 --}}
            <div class="row g-3">
                <div class="col-12 col-sm-6 col-lg-4 col-xl-3" wire:ignore wire:key="svc-filter-group">
                    <label class="form-label fw-medium small mb-1">
                        <i class="fa fa-folder-open text-primary me-1"></i> Project/Group
                    </label>
                    {{ html()->select('filterGroup', [])->value('')->class('select-property_group_id-list border-secondary-subtle shadow-sm')->id('svc_filterGroup')->placeholder('All Groups')->attribute('wire:model', 'filterGroup') }}
                </div>
                <div class="col-12 col-sm-6 col-lg-4 col-xl-3" wire:ignore wire:key="svc-filter-building">
                    <label class="form-label fw-medium small mb-1">
                        <i class="fa fa-building text-primary me-1"></i> Building
                    </label>
                    {{ html()->select('filterBuilding', [])->value('')->class('select-property_building_id-list border-secondary-subtle shadow-sm')->id('svc_filterBuilding')->placeholder('All Buildings')->attribute('wire:model', 'filterBuilding')->attribute('data-group-select', '#svc_filterGroup') }}
                </div>
                <div class="col-12 col-sm-6 col-lg-4 col-xl-3" wire:ignore wire:key="svc-filter-property">
                    <label class="form-label fw-medium small mb-1">
                        <i class="fa fa-home text-primary me-1"></i> Property/Unit
                    </label>
                    {{ html()->select('filterProperty', [])->value('')->class('select-property_id-list border-secondary-subtle shadow-sm')->id('svc_filterProperty')->placeholder('All Properties')->attribute('wire:model', 'filterProperty')->attribute('data-building-select', '#svc_filterBuilding')->attribute('data-group-select', '#svc_filterGroup') }}
                </div>
                <div class="col-12 col-sm-6 col-lg-4 col-xl-3" wire:ignore wire:key="svc-filter-customer">
                    <label class="form-label fw-medium small mb-1">
                        <i class="fa fa-user text-primary me-1"></i> Customer
                    </label>
                    {{ html()->select('filterCustomer', [])->value('')->class('select-customer_id-list border-secondary-subtle shadow-sm')->id('svc_filterCustomer')->placeholder('All Customers')->attribute('wire:model', 'filterCustomer') }}
                </div>
            </div>

            {{-- Filters Row 2 --}}
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
                        @foreach ($ownerships as $ownership)
                            <option value="{{ $ownership }}">{{ $ownership }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <label class="form-label fw-medium small mb-1">
                        <i class="fa fa-money text-primary me-1"></i> Settlement
                    </label>
                    <select wire:model.live="filterStatus"
                        class="form-select form-select-sm border-secondary-subtle shadow-sm">
                        <option value="">All</option>
                        <option value="unpaid">Unpaid</option>
                        <option value="partial">Partially paid</option>
                        <option value="paid">Fully paid</option>
                    </select>
                </div>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <label class="form-label fw-medium small mb-1">
                        <i class="fa fa-calendar text-primary me-1"></i> Period From
                    </label>
                    <input type="date" wire:model="dateFrom"
                        class="form-control form-control-sm border-secondary-subtle shadow-sm">
                </div>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <label class="form-label fw-medium small mb-1">
                        <i class="fa fa-calendar-check-o text-primary me-1"></i> Period To
                    </label>
                    <input type="date" wire:model="dateTo"
                        class="form-control form-control-sm border-secondary-subtle shadow-sm">
                </div>
                <div class="col-12 col-md-8 col-lg-12 col-xl-2 d-flex align-items-end gap-2">
                    <button wire:click="applyFilters"
                        class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1 shadow-sm">
                        <i class="fa fa-filter"></i> Apply
                    </button>
                    <button wire:click="resetFilters"
                        class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1 shadow-sm">
                        <i class="fa fa-times"></i> Reset
                    </button>
                </div>
            </div>
            <div class="small text-muted mt-2">
                <i class="fa fa-info-circle me-1"></i>
                Charges are matched on the period they cover; receipts are counted up to
                <strong>Period To</strong> and capped at what was charged.
            </div>
        </div>

        {{-- Grouped Table --}}
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle border-bottom mb-0 table-sm">
                    <thead class="bg-light text-muted">
                        <tr class="text-capitalize small">
                            <th class="fw-semibold py-2 ps-3" style="width:76px;">#</th>
                            <th x-show.important="columns.date" class="fw-semibold text-nowrap">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="date"
                                    label="Last Charged" />
                            </th>
                            <th x-show.important="columns.customer" class="fw-semibold text-nowrap">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="customer"
                                    label="Customer" />
                            </th>
                            <th x-show.important="columns.group" class="fw-semibold text-nowrap d-none d-xl-table-cell">
                                Group</th>
                            <th x-show.important="columns.building"
                                class="fw-semibold text-nowrap d-none d-xl-table-cell">Building</th>
                            <th x-show.important="columns.property"
                                class="fw-semibold text-nowrap text-end d-none d-md-table-cell">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="property"
                                    label="Property No" />
                            </th>
                            <th x-show.important="columns.type" class="fw-semibold text-nowrap d-none d-xl-table-cell">
                                Property Type</th>
                            <th x-show.important="columns.ownership"
                                class="fw-semibold text-nowrap d-none d-xl-table-cell">Ownership</th>
                            <th x-show.important="columns.start_date"
                                class="fw-semibold text-nowrap d-none d-lg-table-cell">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="start_date"
                                    label="Period From" />
                            </th>
                            <th x-show.important="columns.end_date"
                                class="fw-semibold text-nowrap d-none d-lg-table-cell">Period To</th>
                            <th x-show.important="columns.no_of_months"
                                class="fw-semibold text-end d-none d-lg-table-cell">Months</th>
                            <th x-show.important="columns.no_of_days" class="fw-semibold text-end d-none d-lg-table-cell">
                                Days</th>
                            <th x-show.important="columns.unit_size" class="fw-semibold text-end d-none d-lg-table-cell">
                                Unit Size</th>
                            <th x-show.important="columns.per_square_meter_price"
                                class="fw-semibold text-end d-none d-xl-table-cell">Per Sq M Price</th>
                            <th x-show.important="columns.per_day_price"
                                class="fw-semibold text-end d-none d-xl-table-cell">Per Day Price</th>
                            <th x-show.important="columns.lines" class="fw-semibold text-end d-none d-md-table-cell">
                                Charges</th>
                            <th x-show.important="columns.amount" class="fw-semibold text-end">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="amount"
                                    label="Charged" />
                            </th>
                            <th x-show.important="columns.paid" class="fw-semibold text-end">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="paid"
                                    label="Paid" />
                            </th>
                            <th x-show.important="columns.balance" class="fw-semibold text-end">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="balance"
                                    label="Balance" />
                            </th>
                            <th x-show.important="columns.status" class="fw-semibold text-center text-nowrap">Status</th>
                            <th x-show.important="columns.remark" class="fw-semibold d-none d-xl-table-cell">Remark</th>
                            <th x-show.important="columns.reason" class="fw-semibold d-none d-xl-table-cell">Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data as $index => $item)
                            @php
                                $rowLines = $lines[$item->rent_out_id] ?? collect();
                                $badge = match ($item->status) {
                                    'paid' => 'success',
                                    'partial' => 'warning',
                                    default => 'danger',
                                };
                                $rate = $item->amount > 0 ? round(($item->paid / $item->amount) * 100, 1) : 0;
                            @endphp
                            <tr wire:key="svc-{{ $item->rent_out_id }}">
                                <td class="ps-3 text-nowrap">
                                    <button type="button" class="svc-row-toggle me-1"
                                        @click="toggleRow({{ $item->rent_out_id }})"
                                        :title="isOpen({{ $item->rent_out_id }}) ? 'Hide charges' : 'Show charges'">
                                        <i class="fa"
                                            :class="isOpen({{ $item->rent_out_id }}) ? 'fa-chevron-down' :
                                                'fa-chevron-right'"></i>
                                    </button>
                                    <span class="badge bg-light text-dark border">#{{ $data->firstItem() + $index }}</span>
                                </td>
                                <td x-show.important="columns.date" class="text-nowrap">
                                    <i class="fa fa-calendar me-1 text-muted opacity-75"></i>
                                    <span class="small">
                                        {{ $item->last_charged_at ? \Carbon\Carbon::parse($item->last_charged_at)->format('d-m-Y') : '—' }}
                                    </span>
                                </td>
                                <td x-show.important="columns.customer">
                                    <a href="{{ route('property::sale::view', $item->rent_out_id) }}" target="_blank"
                                        class="text-decoration-none text-body fw-medium">
                                        <i class="fa fa-user me-1 text-muted opacity-75"></i>
                                        {{ $item->customer_name ?? '—' }}
                                    </a>
                                    <div class="small text-muted d-md-none">
                                        <i class="fa fa-home me-1 opacity-75"></i>{{ $item->property_number }}
                                    </div>
                                </td>
                                <td x-show.important="columns.group" class="d-none d-xl-table-cell small text-muted">
                                    {{ $item->group_name ?? '—' }}
                                </td>
                                <td x-show.important="columns.building" class="d-none d-xl-table-cell small text-muted">
                                    {{ $item->building_name ?? '—' }}
                                </td>
                                <td x-show.important="columns.property" class="d-none d-md-table-cell text-end">
                                    <i class="fa fa-home me-1 text-muted opacity-75"></i>
                                    {{ $item->property_number ?? '—' }}
                                </td>
                                <td x-show.important="columns.type" class="d-none d-xl-table-cell small text-muted">
                                    {{ $item->type_name ?? '—' }}
                                </td>
                                <td x-show.important="columns.ownership" class="d-none d-xl-table-cell small text-muted">
                                    {{ $item->ownership ? ucfirst($item->ownership) : '—' }}
                                </td>
                                <td x-show.important="columns.start_date"
                                    class="d-none d-lg-table-cell text-nowrap small">
                                    {{ $item->period_start ? \Carbon\Carbon::parse($item->period_start)->format('d-m-Y') : '—' }}
                                </td>
                                <td x-show.important="columns.end_date" class="d-none d-lg-table-cell text-nowrap small">
                                    {{ $item->period_end ? \Carbon\Carbon::parse($item->period_end)->format('d-m-Y') : '—' }}
                                </td>
                                <td x-show.important="columns.no_of_months" class="d-none d-lg-table-cell text-end small">
                                    {{ $item->no_of_months ? number_format($item->no_of_months) : '—' }}
                                </td>
                                <td x-show.important="columns.no_of_days" class="d-none d-lg-table-cell text-end small">
                                    {{ $item->no_of_days ? number_format($item->no_of_days) : '—' }}
                                </td>
                                <td x-show.important="columns.unit_size" class="d-none d-lg-table-cell text-end small">
                                    {{ $item->unit_size ? number_format($item->unit_size, 2) : '—' }}
                                </td>
                                <td x-show.important="columns.per_square_meter_price"
                                    class="d-none d-xl-table-cell text-end small">
                                    {{ $item->per_square_meter_price ? number_format($item->per_square_meter_price, 2) : '—' }}
                                </td>
                                <td x-show.important="columns.per_day_price"
                                    class="d-none d-xl-table-cell text-end small">
                                    {{ $item->per_day_price ? number_format($item->per_day_price, 2) : '—' }}
                                </td>
                                <td x-show.important="columns.lines" class="d-none d-md-table-cell text-end">
                                    <span
                                        class="badge bg-secondary bg-opacity-10 text-secondary">{{ number_format($item->charge_count) }}</span>
                                </td>
                                <td x-show.important="columns.amount" class="text-end fw-medium">
                                    {{ number_format($item->amount, 2) }}
                                </td>
                                <td x-show.important="columns.paid" class="text-end text-success">
                                    {{ number_format($item->paid, 2) }}
                                    <div class="svc-meter mt-1">
                                        <span class="bg-{{ $badge }}"
                                            style="width: {{ min(100, max(0, $rate)) }}%;"></span>
                                    </div>
                                </td>
                                <td x-show.important="columns.balance"
                                    class="text-end {{ $item->balance > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">
                                    {{ number_format($item->balance, 2) }}
                                </td>
                                <td x-show.important="columns.status" class="text-center text-nowrap">
                                    <span class="badge bg-{{ $badge }} bg-opacity-10 text-{{ $badge }}">
                                        {{ ucfirst($item->status) }}
                                    </span>
                                </td>
                                <td x-show.important="columns.remark" class="d-none d-xl-table-cell text-muted small"
                                    style="max-width:240px;">
                                    <span class="d-inline-block text-truncate" style="max-width:240px;"
                                        title="{{ $item->remark }}">
                                        {{ $item->remark ?: '—' }}
                                    </span>
                                </td>
                                <td x-show.important="columns.reason" class="d-none d-xl-table-cell text-muted small">
                                    {{ $item->reason ?: '—' }}
                                </td>
                            </tr>

                            {{-- The charge lines behind this agreement --}}
                            <tr x-show="isOpen({{ $item->rent_out_id }})" x-cloak class="svc-detail"
                                wire:key="svc-detail-{{ $item->rent_out_id }}">
                                <td :colspan="spanAll()" class="p-0">
                                    <div class="px-3 py-2">
                                        <div class="small text-uppercase text-muted fw-semibold mb-2"
                                            style="letter-spacing:.04em;font-size:.7rem;">
                                            <i class="fa fa-list-ul me-1"></i>
                                            {{ number_format($rowLines->count()) }}
                                            charge{{ $rowLines->count() === 1 ? '' : 's' }}
                                            &mdash; {{ $item->customer_name }} / {{ $item->property_number }}
                                        </div>
                                        <table class="table table-sm mb-0 bg-white">
                                            <thead class="text-muted">
                                                <tr class="small">
                                                    <th class="fw-semibold">Raised</th>
                                                    <th class="fw-semibold">Name</th>
                                                    <th class="fw-semibold text-nowrap">From</th>
                                                    <th class="fw-semibold text-nowrap">To</th>
                                                    <th class="fw-semibold text-end">Months</th>
                                                    <th class="fw-semibold text-end">Days</th>
                                                    <th class="fw-semibold text-end">Unit Size</th>
                                                    <th class="fw-semibold text-end">Per Sq M</th>
                                                    <th class="fw-semibold text-end">Per Day</th>
                                                    <th class="fw-semibold text-end">Amount</th>
                                                    <th class="fw-semibold">Remark</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($rowLines as $line)
                                                    <tr class="small">
                                                        <td class="text-nowrap">{{ $line->created_at?->format('d-m-Y') ?? '—' }}
                                                        </td>
                                                        <td>{{ $line->name }}</td>
                                                        <td class="text-nowrap">{{ $line->start_date?->format('d-m-Y') ?? '—' }}
                                                        </td>
                                                        <td class="text-nowrap">{{ $line->end_date?->format('d-m-Y') ?? '—' }}
                                                        </td>
                                                        <td class="text-end">{{ $line->no_of_months ?? '—' }}</td>
                                                        <td class="text-end">{{ $line->no_of_days ?? '—' }}</td>
                                                        <td class="text-end">
                                                            {{ $line->unit_size ? number_format($line->unit_size, 2) : '—' }}
                                                        </td>
                                                        <td class="text-end">
                                                            {{ $line->per_square_meter_price ? number_format($line->per_square_meter_price, 2) : '—' }}
                                                        </td>
                                                        <td class="text-end">
                                                            {{ $line->per_day_price ? number_format($line->per_day_price, 2) : '—' }}
                                                        </td>
                                                        <td class="text-end fw-medium">{{ number_format($line->amount, 2) }}</td>
                                                        <td class="text-muted">{{ $line->remark ?: ($line->description ?: '—') }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot class="fw-semibold">
                                                <tr class="small">
                                                    <td colspan="9" class="text-end">Charged</td>
                                                    <td class="text-end">{{ number_format($rowLines->sum('amount'), 2) }}</td>
                                                    <td></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td :colspan="spanAll()" class="text-center py-5 text-muted">
                                    <i class="fa fa-inbox fa-2x mb-2 d-block opacity-50"></i>
                                    <div>No service charges found for the selected filters.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($data->count())
                        @php
                            $pageAmount = $data->sum('amount');
                            $pagePaid = $data->sum('paid');
                            $pageBalance = $data->sum('balance');
                            $pageLines = $data->sum('charge_count');
                        @endphp
                        <tfoot class="bg-light fw-semibold">
                            <tr>
                                <td class="ps-3 text-nowrap">Page</td>
                                @foreach ($this->leadingColumns() as $column)
                                    @if ($column === 'lines')
                                        <td x-show.important="columns.lines" class="text-end d-none d-md-table-cell">
                                            {{ number_format($pageLines) }}
                                        </td>
                                    @else
                                        <td x-show.important="columns.{{ $column }}"></td>
                                    @endif
                                @endforeach
                                <td x-show.important="columns.amount" class="text-end">
                                    {{ number_format($pageAmount, 2) }}</td>
                                <td x-show.important="columns.paid" class="text-end text-success">
                                    {{ number_format($pagePaid, 2) }}</td>
                                <td x-show.important="columns.balance"
                                    class="text-end {{ $pageBalance > 0 ? 'text-danger' : '' }}">
                                    {{ number_format($pageBalance, 2) }}
                                </td>
                                <td x-show.important="columns.status"></td>
                                <td x-show.important="columns.remark"></td>
                                <td x-show.important="columns.reason"></td>
                            </tr>
                            <tr class="border-top">
                                <td class="ps-3 text-nowrap"><i class="fa fa-calculator me-1 text-muted"></i> Report</td>
                                @foreach ($this->leadingColumns() as $column)
                                    @if ($column === 'lines')
                                        <td x-show.important="columns.lines" class="text-end d-none d-md-table-cell">
                                            {{ number_format($kpis['lines']) }}
                                        </td>
                                    @else
                                        <td x-show.important="columns.{{ $column }}"></td>
                                    @endif
                                @endforeach
                                <td x-show.important="columns.amount" class="text-end">
                                    {{ number_format($kpis['amount'], 2) }}</td>
                                <td x-show.important="columns.paid" class="text-end text-success">
                                    {{ number_format($kpis['paid'], 2) }}</td>
                                <td x-show.important="columns.balance"
                                    class="text-end {{ $kpis['balance'] > 0 ? 'text-danger' : '' }}">
                                    {{ number_format($kpis['balance'], 2) }}
                                </td>
                                <td x-show.important="columns.status" class="text-center small text-muted">
                                    {{ $kpis['collection_rate'] }}%
                                </td>
                                <td x-show.important="columns.remark"></td>
                                <td x-show.important="columns.reason"></td>
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
                $('#svc_filterGroup').on('change', function() {
                    @this.set('filterGroup', $(this).val() || '');
                    clearAndReload('svc_filterBuilding');
                    clearAndReload('svc_filterProperty');
                    @this.set('filterBuilding', '');
                    @this.set('filterProperty', '');
                });
                $('#svc_filterBuilding').on('change', function() {
                    @this.set('filterBuilding', $(this).val() || '');
                    clearAndReload('svc_filterProperty');
                    @this.set('filterProperty', '');
                });
                $('#svc_filterProperty').on('change', function() {
                    @this.set('filterProperty', $(this).val() || '');
                });
                $('#svc_filterCustomer').on('change', function() {
                    @this.set('filterCustomer', $(this).val() || '');
                });
            });
        </script>
    @endpush
</div>

<div>
    {{-- ═══════════════ KPI Dashboard ═══════════════ --}}
    <div class="row g-3 mb-3">
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm h-100 overflow-hidden position-relative">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 bg-primary bg-opacity-10 text-primary"
                            style="width:52px;height:52px;">
                            <i class="fa fa-cogs fa-lg"></i>
                        </div>
                        <div class="flex-grow-1 ms-3 min-w-0">
                            <div class="text-muted small text-uppercase fw-semibold"
                                style="letter-spacing:.04em;font-size:.7rem;">Total Amount</div>
                            <div class="h5 mb-0 fw-bold text-dark text-truncate">
                                {{ number_format($kpis['total_amount'], 2) }}
                            </div>
                            <div class="small text-muted">
                                <i class="fa fa-list-alt me-1"></i>{{ number_format($kpis['transactions']) }} entries
                            </div>
                        </div>
                    </div>
                </div>
                <div class="position-absolute bottom-0 start-0 end-0 bg-primary" style="height:3px;"></div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm h-100 overflow-hidden position-relative">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 bg-info bg-opacity-10 text-info"
                            style="width:52px;height:52px;">
                            <i class="fa fa-users fa-lg"></i>
                        </div>
                        <div class="flex-grow-1 ms-3 min-w-0">
                            <div class="text-muted small text-uppercase fw-semibold"
                                style="letter-spacing:.04em;font-size:.7rem;">Customers</div>
                            <div class="h5 mb-0 fw-bold text-info text-truncate">
                                {{ number_format($kpis['customers']) }}
                            </div>
                            <div class="small text-muted">Unique sales</div>
                        </div>
                    </div>
                </div>
                <div class="position-absolute bottom-0 start-0 end-0 bg-info" style="height:3px;"></div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm h-100 overflow-hidden position-relative">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 bg-success bg-opacity-10 text-success"
                            style="width:52px;height:52px;">
                            <i class="fa fa-calendar fa-lg"></i>
                        </div>
                        <div class="flex-grow-1 ms-3 min-w-0">
                            <div class="text-muted small text-uppercase fw-semibold"
                                style="letter-spacing:.04em;font-size:.7rem;">Period</div>
                            <div class="h6 mb-0 fw-bold text-success text-truncate">
                                {{ $dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('d M Y') : '...' }}
                                &rarr;
                                {{ $dateTo ? \Carbon\Carbon::parse($dateTo)->format('d M Y') : '...' }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="position-absolute bottom-0 start-0 end-0 bg-success" style="height:3px;"></div>
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
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr class="small text-uppercase text-muted">
                            <th class="ps-3">Project / Group</th>
                            <th class="text-end d-none d-sm-table-cell">Entries</th>
                            <th class="text-end pe-3">Amount</th>
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
                                    {{ number_format($row['txns']) }}
                                </td>
                                <td class="text-end pe-3">{{ number_format($row['amount'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center py-4 text-muted small">
                                    <i class="fa fa-inbox me-1"></i> No data for the selected filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if (count($summary))
                        <tfoot class="table-light fw-bold border-top">
                            <tr>
                                <td class="ps-3"><i class="fa fa-calculator me-1 text-muted"></i> Total</td>
                                <td class="text-end d-none d-sm-table-cell">{{ number_format($kpis['transactions']) }}</td>
                                <td class="text-end pe-3">{{ number_format($kpis['total_amount'], 2) }}</td>
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
                    <button wire:click="download" class="btn btn-success btn-sm d-inline-flex align-items-center gap-1 shadow-sm">
                        <i class="fa fa-file-excel-o"></i>
                        <span>Export Excel</span>
                    </button>
                </div>

                <div class="col-12 col-md d-flex flex-wrap gap-2 align-items-center justify-content-md-end">
                    <div class="d-flex align-items-center gap-1">
                        <label class="form-label mb-0 text-muted small fw-semibold d-none d-sm-inline">Show</label>
                        <select wire:model.live="limit" class="form-select form-select-sm border-secondary-subtle shadow-sm" style="width:auto;">
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
                            placeholder="Search customer / id / remark..."
                            class="form-control form-control-sm border-secondary-subtle shadow-sm" autocomplete="off">
                    </div>

                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 shadow-sm"
                            type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                            <i class="fa fa-columns"></i>
                            <span class="d-none d-md-inline">Columns</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow" style="min-width:220px;">
                            <li class="dropdown-header fw-semibold text-muted" style="font-size:.75rem;letter-spacing:.04em;">TOGGLE COLUMNS</li>
                            <li><hr class="dropdown-divider my-1"></li>
                            @php
                                $columnLabels = [
                                    'date' => 'Date',
                                    'customer' => 'Customer',
                                    'group' => 'Group',
                                    'building' => 'Building',
                                    'property' => 'Property No',
                                    'start_date' => 'Start Date',
                                    'end_date' => 'End Date',
                                    'no_of_months' => 'Months',
                                    'no_of_days' => 'Days',
                                    'unit_size' => 'Unit Size',
                                    'per_square_meter_price' => 'Per Sq M Price',
                                    'per_day_price' => 'Per Day Price',
                                    'amount' => 'Amount',
                                    'remark' => 'Remark',
                                    'reason' => 'Reason',
                                ];
                            @endphp
                            @foreach ($columnLabels as $key => $label)
                                <li>
                                    <label class="dropdown-item d-flex align-items-center gap-2 py-2" style="cursor:pointer;font-size:.85rem;">
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input" type="checkbox" role="switch"
                                                @checked($this->isColumnVisible($key))
                                                wire:click="toggleColumn('{{ $key }}')" style="cursor:pointer;">
                                        </div>
                                        {{ $label }}
                                    </label>
                                </li>
                            @endforeach
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <button class="dropdown-item text-center text-warning fw-semibold" wire:click="resetColumns" style="font-size:.85rem;">
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
                <div class="col-12 col-sm-6 col-lg-4 col-xl-3" wire:ignore>
                    <label class="form-label fw-medium small mb-1">
                        <i class="fa fa-folder-open text-primary me-1"></i> Project/Group
                    </label>
                    {{ html()->select('filterGroup', [])->value('')->class('select-property_group_id-list border-secondary-subtle shadow-sm')->id('svc_filterGroup')->placeholder('All Groups')->attribute('wire:model', 'filterGroup') }}
                </div>
                <div class="col-12 col-sm-6 col-lg-4 col-xl-3" wire:ignore>
                    <label class="form-label fw-medium small mb-1">
                        <i class="fa fa-building text-primary me-1"></i> Building
                    </label>
                    {{ html()->select('filterBuilding', [])->value('')->class('select-property_building_id-list border-secondary-subtle shadow-sm')->id('svc_filterBuilding')->placeholder('All Buildings')->attribute('wire:model', 'filterBuilding')->attribute('data-group-select', '#svc_filterGroup') }}
                </div>
                <div class="col-12 col-sm-6 col-lg-4 col-xl-3" wire:ignore>
                    <label class="form-label fw-medium small mb-1">
                        <i class="fa fa-home text-primary me-1"></i> Property/Unit
                    </label>
                    {{ html()->select('filterProperty', [])->value('')->class('select-property_id-list border-secondary-subtle shadow-sm')->id('svc_filterProperty')->placeholder('All Properties')->attribute('wire:model', 'filterProperty')->attribute('data-building-select', '#svc_filterBuilding')->attribute('data-group-select', '#svc_filterGroup') }}
                </div>
                <div class="col-12 col-sm-6 col-lg-4 col-xl-3" wire:ignore>
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
                    <select wire:model.live="filterType" class="form-select form-select-sm border-secondary-subtle shadow-sm">
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
                    <select wire:model.live="filterOwnership" class="form-select form-select-sm border-secondary-subtle shadow-sm">
                        <option value="">All</option>
                        <option value="owned">Owned</option>
                        <option value="rented">Rented</option>
                    </select>
                </div>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <label class="form-label fw-medium small mb-1">
                        <i class="fa fa-calendar text-primary me-1"></i> From
                    </label>
                    <input type="date" wire:model="dateFrom" class="form-control form-control-sm border-secondary-subtle shadow-sm">
                </div>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <label class="form-label fw-medium small mb-1">
                        <i class="fa fa-calendar-check-o text-primary me-1"></i> To
                    </label>
                    <input type="date" wire:model="dateTo" class="form-control form-control-sm border-secondary-subtle shadow-sm">
                </div>
                <div class="col-12 col-md-8 col-lg-12 col-xl-4 d-flex align-items-end gap-2">
                    <button wire:click="applyFilters" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1 shadow-sm">
                        <i class="fa fa-filter"></i> Apply
                    </button>
                    <button wire:click="resetFilters" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1 shadow-sm">
                        <i class="fa fa-times"></i> Reset
                    </button>
                </div>
            </div>
        </div>

        {{-- Detail Table --}}
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle border-bottom mb-0 table-sm">
                    <thead class="bg-light text-muted">
                        <tr class="text-capitalize small">
                            <th class="fw-semibold py-2 ps-3" style="width:60px;">#</th>
                            @if ($this->isColumnVisible('date'))
                                <th class="fw-semibold text-nowrap">
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="created_at" label="Date" />
                                </th>
                            @endif
                            @if ($this->isColumnVisible('customer'))
                                <th class="fw-semibold text-nowrap">Customer</th>
                            @endif
                            @if ($this->isColumnVisible('group'))
                                <th class="fw-semibold text-nowrap d-none d-xl-table-cell">Group</th>
                            @endif
                            @if ($this->isColumnVisible('building'))
                                <th class="fw-semibold text-nowrap d-none d-xl-table-cell">Building</th>
                            @endif
                            @if ($this->isColumnVisible('property'))
                                <th class="fw-semibold text-nowrap text-end d-none d-md-table-cell">Property No</th>
                            @endif
                            @if ($this->isColumnVisible('start_date'))
                                <th class="fw-semibold text-nowrap d-none d-lg-table-cell">Start Date</th>
                            @endif
                            @if ($this->isColumnVisible('end_date'))
                                <th class="fw-semibold text-nowrap d-none d-lg-table-cell">End Date</th>
                            @endif
                            @if ($this->isColumnVisible('no_of_months'))
                                <th class="fw-semibold text-end d-none d-lg-table-cell">Months</th>
                            @endif
                            @if ($this->isColumnVisible('no_of_days'))
                                <th class="fw-semibold text-end d-none d-lg-table-cell">Days</th>
                            @endif
                            @if ($this->isColumnVisible('unit_size'))
                                <th class="fw-semibold text-end d-none d-lg-table-cell">Unit Size</th>
                            @endif
                            @if ($this->isColumnVisible('per_square_meter_price'))
                                <th class="fw-semibold text-end d-none d-xl-table-cell">Per Sq M Price</th>
                            @endif
                            @if ($this->isColumnVisible('per_day_price'))
                                <th class="fw-semibold text-end d-none d-xl-table-cell">Per Day Price</th>
                            @endif
                            @if ($this->isColumnVisible('amount'))
                                <th class="fw-semibold text-end">
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="amount" label="Amount" />
                                </th>
                            @endif
                            @if ($this->isColumnVisible('remark'))
                                <th class="fw-semibold d-none d-xl-table-cell">Remark</th>
                            @endif
                            @if ($this->isColumnVisible('reason'))
                                <th class="fw-semibold d-none d-xl-table-cell">Reason</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data as $index => $item)
                            <tr>
                                <td class="ps-3">
                                    <span class="badge bg-light text-dark border">#{{ $data->firstItem() + $index }}</span>
                                </td>
                                @if ($this->isColumnVisible('date'))
                                    <td class="text-nowrap">
                                        <i class="fa fa-calendar me-1 text-muted opacity-75"></i>
                                        <span class="small">{{ $item->created_at?->format('d-m-Y') }}</span>
                                    </td>
                                @endif
                                @if ($this->isColumnVisible('customer'))
                                    <td>
                                        @if ($item->rentOut)
                                            <a href="{{ route('property::sale::view', $item->rent_out_id) }}"
                                                target="_blank"
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
                                    <td class="d-none d-xl-table-cell small text-muted">{{ $item->rentOut?->group?->name ?? '—' }}</td>
                                @endif
                                @if ($this->isColumnVisible('building'))
                                    <td class="d-none d-xl-table-cell small text-muted">{{ $item->rentOut?->building?->name ?? '—' }}</td>
                                @endif
                                @if ($this->isColumnVisible('property'))
                                    <td class="d-none d-md-table-cell text-end">
                                        <i class="fa fa-home me-1 text-muted opacity-75"></i>
                                        {{ $item->rentOut?->property?->number ?? '—' }}
                                    </td>
                                @endif
                                @if ($this->isColumnVisible('start_date'))
                                    <td class="d-none d-lg-table-cell text-nowrap small">
                                        {{ $item->start_date?->format('d-m-Y') ?? '—' }}
                                    </td>
                                @endif
                                @if ($this->isColumnVisible('end_date'))
                                    <td class="d-none d-lg-table-cell text-nowrap small">
                                        {{ $item->end_date?->format('d-m-Y') ?? '—' }}
                                    </td>
                                @endif
                                @if ($this->isColumnVisible('no_of_months'))
                                    <td class="d-none d-lg-table-cell text-end small">
                                        {{ $item->no_of_months ?? '—' }}
                                    </td>
                                @endif
                                @if ($this->isColumnVisible('no_of_days'))
                                    <td class="d-none d-lg-table-cell text-end small">
                                        {{ $item->no_of_days ?? '—' }}
                                    </td>
                                @endif
                                @if ($this->isColumnVisible('unit_size'))
                                    <td class="d-none d-lg-table-cell text-end small">
                                        {{ $item->unit_size ? number_format($item->unit_size, 2) : '—' }}
                                    </td>
                                @endif
                                @if ($this->isColumnVisible('per_square_meter_price'))
                                    <td class="d-none d-xl-table-cell text-end small">
                                        {{ $item->per_square_meter_price ? number_format($item->per_square_meter_price, 2) : '—' }}
                                    </td>
                                @endif
                                @if ($this->isColumnVisible('per_day_price'))
                                    <td class="d-none d-xl-table-cell text-end small">
                                        {{ $item->per_day_price ? number_format($item->per_day_price, 2) : '—' }}
                                    </td>
                                @endif
                                @if ($this->isColumnVisible('amount'))
                                    <td class="text-end fw-medium">
                                        {{ number_format($item->amount, 2) }}
                                    </td>
                                @endif
                                @if ($this->isColumnVisible('remark'))
                                    <td class="d-none d-xl-table-cell text-muted small" style="max-width:240px;">
                                        <span class="d-inline-block text-truncate" style="max-width:240px;" title="{{ $item->remark }}">
                                            {{ $item->remark ?: '—' }}
                                        </span>
                                    </td>
                                @endif
                                @if ($this->isColumnVisible('reason'))
                                    <td class="d-none d-xl-table-cell text-muted small">
                                        {{ $item->reason ?: '—' }}
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="20" class="text-center py-5 text-muted">
                                    <i class="fa fa-inbox fa-2x mb-2 d-block opacity-50"></i>
                                    <div>No service charges found for the selected filters.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($data->count())
                        <tfoot class="bg-light fw-semibold">
                            @php
                                $pageAmount = $data->sum('amount');
                                $hiddenBefore = collect([
                                    'date',
                                    'customer',
                                    'group',
                                    'building',
                                    'property',
                                    'start_date',
                                    'end_date',
                                    'no_of_months',
                                    'no_of_days',
                                    'unit_size',
                                    'per_square_meter_price',
                                    'per_day_price',
                                ])->filter(fn($c) => $this->isColumnVisible($c))->count();
                            @endphp
                            <tr>
                                <td colspan="{{ $hiddenBefore + 1 }}" class="text-end ps-3">Page Total</td>
                                @if ($this->isColumnVisible('amount'))
                                    <td class="text-end">{{ number_format($pageAmount, 2) }}</td>
                                @endif
                                @if ($this->isColumnVisible('remark'))
                                    <td class="d-none d-xl-table-cell"></td>
                                @endif
                                @if ($this->isColumnVisible('reason'))
                                    <td class="d-none d-xl-table-cell"></td>
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

<div>
    {{-- ═══ KPI Dashboard ═══ --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-3 d-flex align-items-center justify-content-center"
                                style="width:46px;height:46px;">
                                <i class="fa fa-cogs fa-lg"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small text-uppercase fw-semibold" style="letter-spacing:.04em;">
                                Total Charged</div>
                            <div class="h5 mb-0 fw-bold text-dark">{{ number_format($kpis['total_charge'], 2) }}</div>
                            <div class="small text-muted">{{ $kpis['transactions'] }} transactions</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-opacity-10 text-success rounded-3 d-flex align-items-center justify-content-center"
                                style="width:46px;height:46px;">
                                <i class="fa fa-money fa-lg"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small text-uppercase fw-semibold" style="letter-spacing:.04em;">
                                Total Collected</div>
                            <div class="h5 mb-0 fw-bold text-success">{{ number_format($kpis['total_paid'], 2) }}</div>
                            <div class="small text-muted">{{ $kpis['collection_rate'] }}% collection rate</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-danger bg-opacity-10 text-danger rounded-3 d-flex align-items-center justify-content-center"
                                style="width:46px;height:46px;">
                                <i class="fa fa-exclamation-triangle fa-lg"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small text-uppercase fw-semibold" style="letter-spacing:.04em;">
                                Outstanding</div>
                            <div class="h5 mb-0 fw-bold {{ $kpis['total_balance'] > 0 ? 'text-danger' : 'text-success' }}">
                                {{ number_format($kpis['total_balance'], 2) }}
                            </div>
                            <div class="small text-muted">Pending balance</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-info bg-opacity-10 text-info rounded-3 d-flex align-items-center justify-content-center"
                                style="width:46px;height:46px;">
                                <i class="fa fa-users fa-lg"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small text-uppercase fw-semibold" style="letter-spacing:.04em;">
                                Customers</div>
                            <div class="h5 mb-0 fw-bold text-dark">{{ $kpis['customers'] }}</div>
                            <div class="small text-muted">Unique rentouts</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ Category Summary ═══ --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white py-2 d-flex align-items-center">
            <i class="fa fa-bar-chart text-primary me-2"></i>
            <span class="fw-semibold">Service Category Summary</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr class="small text-uppercase text-muted">
                            <th class="ps-3">Category</th>
                            <th class="text-end">Charged</th>
                            <th class="text-end">Paid</th>
                            <th class="text-end pe-3">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($summary as $row)
                            @if($row['name'] === 'Total')
                                <tr class="fw-bold table-light border-top">
                                    <td class="ps-3"><i class="fa fa-calculator me-1 text-muted"></i> {{ $row['name'] }}</td>
                                    <td class="text-end">{{ number_format($row['charge'], 2) }}</td>
                                    <td class="text-end text-success">{{ number_format($row['paid'], 2) }}</td>
                                    <td class="text-end pe-3 {{ $row['balance'] > 0 ? 'text-danger' : 'text-success' }}">
                                        {{ number_format($row['balance'], 2) }}
                                    </td>
                                </tr>
                            @else
                                <tr>
                                    <td class="ps-3">
                                        <span class="badge bg-primary bg-opacity-10 text-primary">
                                            {{ $row['name'] }}
                                        </span>
                                    </td>
                                    <td class="text-end">{{ number_format($row['charge'], 2) }}</td>
                                    <td class="text-end text-success">{{ number_format($row['paid'], 2) }}</td>
                                    <td class="text-end pe-3 {{ $row['balance'] > 0 ? 'text-danger fw-semibold' : 'text-success' }}">
                                        {{ number_format($row['balance'], 2) }}
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted small">
                                    <i class="fa fa-inbox me-1"></i> No service data for the selected filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ═══ Main Table Card ═══ --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light py-3">
            {{-- Top Bar: Actions + Show/Search --}}
            <div class="row g-2 align-items-center">
                <div class="col-12 col-md-6 d-flex flex-wrap gap-2 align-items-center">
                    <button wire:click="download" class="btn btn-success btn-sm d-flex align-items-center shadow-sm">
                        <i class="fa fa-file-excel-o me-md-1 fs-6"></i>
                        <span class="d-none d-md-inline ms-1">Export Excel</span>
                    </button>
                    <span class="badge bg-secondary bg-opacity-10 text-secondary border d-none d-md-inline-flex align-items-center">
                        <i class="fa fa-calendar me-1"></i>
                        {{ $dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('d M Y') : '...' }}
                        &rarr;
                        {{ $dateTo ? \Carbon\Carbon::parse($dateTo)->format('d M Y') : '...' }}
                    </span>
                </div>
                <div class="col-12 col-md-6">
                    <div class="row g-2 align-items-center">
                        <div class="col-auto">
                            <label class="form-label mb-0 text-muted small fw-semibold">Show</label>
                        </div>
                        <div class="col-auto">
                            <select wire:model.live="limit"
                                class="form-select form-select-sm border-secondary-subtle shadow-sm">
                                <option value="20">20</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                        <div class="col">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white border-secondary-subtle">
                                    <i class="fa fa-search"></i>
                                </span>
                                <input type="text" wire:model.live.debounce.300ms="search" autofocus
                                    placeholder="Search customer / id..."
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
                                    <span class="d-none d-md-inline">Columns</span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow" style="min-width:220px;">
                                    <li class="dropdown-header fw-semibold text-muted"
                                        style="font-size:.75rem; letter-spacing:.04em;">TOGGLE COLUMNS</li>
                                    <li><hr class="dropdown-divider my-1"></li>
                                    @php
                                        $columnLabels = [
                                            'date' => 'Date',
                                            'customer' => 'Customer',
                                            'group' => 'Group/Project',
                                            'building' => 'Building',
                                            'property' => 'Property No/Unit',
                                            'ownership' => 'Ownership',
                                            'category' => 'Category',
                                            'source' => 'Source',
                                            'remark' => 'Remark',
                                            'charge' => 'Charge',
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
                                    <li><hr class="dropdown-divider my-1"></li>
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

            {{-- Filter Row 1: Group / Building / Property / Customer --}}
            <div class="row g-3">
                <div class="col-12 col-sm-6 col-lg-3" wire:ignore>
                    <label class="form-label fw-medium small">
                        <i class="fa fa-folder-open text-primary me-1"></i> Group/Project
                    </label>
                    {{ html()->select('filterGroup', [])->value('')->class('select-property_group_id-list border-secondary-subtle shadow-sm')->id('service_filterGroup')->placeholder('All Groups')->attribute('wire:model', 'filterGroup') }}
                </div>
                <div class="col-12 col-sm-6 col-lg-3" wire:ignore>
                    <label class="form-label fw-medium small">
                        <i class="fa fa-building text-primary me-1"></i> Building
                    </label>
                    {{ html()->select('filterBuilding', [])->value('')->class('select-property_building_id-list border-secondary-subtle shadow-sm')->id('service_filterBuilding')->placeholder('All Buildings')->attribute('wire:model', 'filterBuilding')->attribute('data-group-select', '#service_filterGroup') }}
                </div>
                <div class="col-12 col-sm-6 col-lg-3" wire:ignore>
                    <label class="form-label fw-medium small">
                        <i class="fa fa-home text-primary me-1"></i> Property/Unit
                    </label>
                    {{ html()->select('filterProperty', [])->value('')->class('select-property_id-list border-secondary-subtle shadow-sm')->id('service_filterProperty')->placeholder('All Properties')->attribute('wire:model', 'filterProperty')->attribute('data-building-select', '#service_filterBuilding')->attribute('data-group-select', '#service_filterGroup') }}
                </div>
                <div class="col-12 col-sm-6 col-lg-3" wire:ignore>
                    <label class="form-label fw-medium small">
                        <i class="fa fa-user text-primary me-1"></i> Customer
                    </label>
                    {{ html()->select('filterCustomer', [])->value('')->class('select-customer_id-list border-secondary-subtle shadow-sm')->id('service_filterCustomer')->placeholder('All Customers')->attribute('wire:model', 'filterCustomer') }}
                </div>
            </div>

            {{-- Filter Row 2: Dates / Category / Source / Direction --}}
            <div class="row g-3 mt-1">
                <div class="col-6 col-md-4 col-lg-2">
                    <label class="form-label fw-medium small">
                        <i class="fa fa-calendar text-primary me-1"></i> From
                    </label>
                    <input type="date" wire:model="dateFrom" class="form-control form-control-sm border-secondary-subtle shadow-sm">
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <label class="form-label fw-medium small">
                        <i class="fa fa-calendar-check-o text-primary me-1"></i> To
                    </label>
                    <input type="date" wire:model="dateTo" class="form-control form-control-sm border-secondary-subtle shadow-sm">
                </div>
                <div class="col-12 col-md-4 col-lg-3">
                    <label class="form-label fw-medium small">
                        <i class="fa fa-tags text-primary me-1"></i> Category
                    </label>
                    <select wire:model.live="filterCategory" class="form-select form-select-sm border-secondary-subtle shadow-sm">
                        <option value="">All Categories</option>
                        @foreach($categories as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <label class="form-label fw-medium small">
                        <i class="fa fa-filter text-primary me-1"></i> Source
                    </label>
                    <select wire:model.live="filterSource" class="form-select form-select-sm border-secondary-subtle shadow-sm">
                        <option value="">All</option>
                        <option value="Service">Service</option>
                        <option value="ServiceCharge">Service Charge</option>
                    </select>
                </div>
                <div class="col-6 col-md-4 col-lg-3">
                    <label class="form-label fw-medium small">
                        <i class="fa fa-exchange text-primary me-1"></i> Direction
                    </label>
                    <select wire:model.live="filterDirection" class="form-select form-select-sm border-secondary-subtle shadow-sm">
                        <option value="">All Movements</option>
                        <option value="charge">Charges Only</option>
                        <option value="payment">Payments Only</option>
                    </select>
                </div>
            </div>

            {{-- Action buttons --}}
            <div class="row mt-3">
                <div class="col-12 d-flex flex-wrap gap-2">
                    <button wire:click="applyFilters" class="btn btn-sm btn-primary d-flex align-items-center gap-1 shadow-sm">
                        <i class="fa fa-filter"></i> Apply
                    </button>
                    <button wire:click="resetFilters"
                        class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1 shadow-sm">
                        <i class="fa fa-times"></i> Reset Filters
                    </button>
                </div>
            </div>
        </div>

        {{-- ═══ Table Body ═══ --}}
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle border-bottom mb-0 table-sm">
                    <thead class="bg-light text-muted">
                        <tr class="text-capitalize small">
                            <th class="fw-semibold py-2 ps-3">#</th>
                            @if($this->isColumnVisible('date'))
                                <th class="fw-semibold">
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="date" label="Date" />
                                </th>
                            @endif
                            @if($this->isColumnVisible('customer'))
                                <th class="fw-semibold">Customer</th>
                            @endif
                            @if($this->isColumnVisible('group'))
                                <th class="fw-semibold d-none d-lg-table-cell">Group/Project</th>
                            @endif
                            @if($this->isColumnVisible('building'))
                                <th class="fw-semibold d-none d-lg-table-cell">Building</th>
                            @endif
                            @if($this->isColumnVisible('property'))
                                <th class="fw-semibold d-none d-md-table-cell">Property</th>
                            @endif
                            @if($this->isColumnVisible('ownership'))
                                <th class="fw-semibold d-none d-xl-table-cell">Ownership</th>
                            @endif
                            @if($this->isColumnVisible('category'))
                                <th class="fw-semibold">Category</th>
                            @endif
                            @if($this->isColumnVisible('source'))
                                <th class="fw-semibold d-none d-md-table-cell">
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="source" label="Source" />
                                </th>
                            @endif
                            @if($this->isColumnVisible('remark'))
                                <th class="fw-semibold d-none d-xl-table-cell">Remark</th>
                            @endif
                            @if($this->isColumnVisible('charge'))
                                <th class="fw-semibold text-end">
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="debit" label="Charge" />
                                </th>
                            @endif
                            @if($this->isColumnVisible('paid'))
                                <th class="fw-semibold text-end">
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="credit" label="Paid" />
                                </th>
                            @endif
                            @if($this->isColumnVisible('balance'))
                                <th class="fw-semibold text-end pe-3">Balance</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data as $index => $item)
                            @php
                                $rowCharge = (float) $item->debit;
                                $rowPaid = (float) $item->credit;
                                $rowBalance = $rowCharge - $rowPaid;
                                $categoryName = $item->category && isset($categories[$item->category])
                                    ? $categories[$item->category]
                                    : ($item->category ?: '-');
                            @endphp
                            <tr>
                                <td class="ps-3">
                                    <span class="badge bg-light text-dark border">#{{ $data->firstItem() + $index }}</span>
                                </td>
                                @if($this->isColumnVisible('date'))
                                    <td>
                                        <i class="fa fa-calendar me-1 text-muted opacity-75"></i>{{ $item->date?->format('d-m-Y') }}
                                    </td>
                                @endif
                                @if($this->isColumnVisible('customer'))
                                    <td>
                                        <a href="{{ route('property::rent::view', $item->rent_out_id) }}" class="text-decoration-none">
                                            <i class="fa fa-user me-1 text-muted opacity-75"></i>
                                            {{ $item->rentOut?->customer?->name ?? '-' }}
                                        </a>
                                        <div class="small text-muted d-md-none">
                                            {{ $item->rentOut?->property?->number }}
                                        </div>
                                    </td>
                                @endif
                                @if($this->isColumnVisible('group'))
                                    <td class="d-none d-lg-table-cell">
                                        <i class="fa fa-folder-open me-1 text-muted opacity-75"></i>
                                        {{ $item->rentOut?->group?->name ?? '-' }}
                                    </td>
                                @endif
                                @if($this->isColumnVisible('building'))
                                    <td class="d-none d-lg-table-cell">
                                        <i class="fa fa-building-o me-1 text-muted opacity-75"></i>
                                        {{ $item->rentOut?->building?->name ?? '-' }}
                                    </td>
                                @endif
                                @if($this->isColumnVisible('property'))
                                    <td class="d-none d-md-table-cell">
                                        <i class="fa fa-home me-1 text-muted opacity-75"></i>
                                        {{ $item->rentOut?->property?->number ?? '-' }}
                                    </td>
                                @endif
                                @if($this->isColumnVisible('ownership'))
                                    <td class="d-none d-xl-table-cell">{{ $item->rentOut?->property?->ownership ?? '-' }}</td>
                                @endif
                                @if($this->isColumnVisible('category'))
                                    <td>
                                        <span class="badge bg-primary bg-opacity-10 text-primary">
                                            {{ $categoryName }}
                                        </span>
                                    </td>
                                @endif
                                @if($this->isColumnVisible('source'))
                                    <td class="d-none d-md-table-cell">
                                        <span class="badge bg-{{ $item->source === 'Service' ? 'info' : 'warning' }} bg-opacity-10 text-{{ $item->source === 'Service' ? 'info' : 'warning' }}">
                                            {{ $item->source }}
                                        </span>
                                    </td>
                                @endif
                                @if($this->isColumnVisible('remark'))
                                    <td class="d-none d-xl-table-cell text-muted small">
                                        {{ \Illuminate\Support\Str::limit($item->remark, 40) }}
                                    </td>
                                @endif
                                @if($this->isColumnVisible('charge'))
                                    <td class="text-end fw-medium">{{ number_format($rowCharge, 2) }}</td>
                                @endif
                                @if($this->isColumnVisible('paid'))
                                    <td class="text-end fw-medium text-success">{{ number_format($rowPaid, 2) }}</td>
                                @endif
                                @if($this->isColumnVisible('balance'))
                                    <td class="text-end pe-3 {{ $rowBalance > 0 ? 'text-danger fw-bold' : 'text-success' }}">
                                        {{ number_format($rowBalance, 2) }}
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="20" class="text-center py-5 text-muted">
                                    <i class="fa fa-inbox fa-2x mb-2 d-block opacity-50"></i>
                                    No service records found for the selected filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($data->hasPages())
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

                $('#service_filterGroup').on('change', function() {
                    @this.set('filterGroup', $(this).val() || '');
                    clearAndReload('service_filterBuilding');
                    clearAndReload('service_filterProperty');
                    @this.set('filterBuilding', '');
                    @this.set('filterProperty', '');
                });
                $('#service_filterBuilding').on('change', function() {
                    @this.set('filterBuilding', $(this).val() || '');
                    clearAndReload('service_filterProperty');
                    @this.set('filterProperty', '');
                });
                $('#service_filterProperty').on('change', function() {
                    @this.set('filterProperty', $(this).val() || '');
                });
                $('#service_filterCustomer').on('change', function() {
                    @this.set('filterCustomer', $(this).val() || '');
                });
            });
        </script>
    @endpush
</div>

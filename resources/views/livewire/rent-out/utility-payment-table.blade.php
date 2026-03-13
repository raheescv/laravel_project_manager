<div>
    {{-- Utility Summary --}}
    <div class="card shadow-sm mb-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Utility</th>
                            <th></th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">Paid</th>
                            <th class="text-end">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($summary as $row)
                            <tr class="{{ $row['name'] === 'Total' ? 'fw-bold table-secondary' : '' }}">
                                <td>{{ $row['name'] }}</td>
                                <td></td>
                                <td class="text-end">{{ number_format($row['amount'], 2) }}</td>
                                <td class="text-end">{{ number_format($row['paid'], 2) }}</td>
                                <td class="text-end {{ $row['balance'] > 0 ? 'text-danger fw-bold' : '' }}">{{ number_format($row['balance'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Main Table Card --}}
    <div class="card shadow-sm">
        <div class="card-header bg-light py-3">
            {{-- ═══ Top Bar: Actions + Show/Search ═══ --}}
            <div class="row mt-3">
                <div class="col-md-6 d-flex flex-wrap gap-2 align-items-center mb-3 mb-md-0">
                    <button wire:click="download" class="btn btn-success btn-sm d-flex align-items-center shadow-sm">
                        <i class="fa fa-file-excel-o me-md-1 fs-5"></i>
                        <span class="d-none d-md-inline">Excel</span>
                    </button>
                    <button wire:click="updatedSelectAll(true)" class="btn btn-outline-primary btn-sm d-flex align-items-center shadow-sm">
                        <i class="fa fa-check-square-o me-md-1"></i>
                        <span class="d-none d-md-inline">Select all</span>
                    </button>
                    @if(count($selected) > 0)
                        <button wire:click="paySelected" class="btn btn-primary btn-sm d-flex align-items-center shadow-sm">
                            <i class="fa fa-money me-md-1"></i>
                            Pay Selected ({{ count($selected) }})
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
                            </select>
                        </div>
                        <div class="col">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white border-secondary-subtle">
                                    <i class="fa fa-search"></i>
                                </span>
                                <input type="text" wire:model.live.debounce.300ms="search" autofocus
                                    placeholder="Search utilities..."
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
                                            'group' => 'Group/Project',
                                            'building' => 'Building',
                                            'property' => 'Property No/Unit',
                                            'ownership' => 'Ownership',
                                            'utility' => 'Utility',
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

            {{-- ═══ Filter Row 1: Group, Building, Property, Customer ═══ --}}
            <div class="row g-3">
                <div class="col-md-4 col-lg" wire:ignore>
                    <label class="form-label fw-medium">
                        <i class="fa fa-folder-open text-primary me-1 small"></i> Group/Project
                    </label>
                    {{ html()->select('filterGroup', [])->value('')->class('select-property_group_id-list border-secondary-subtle shadow-sm')->id('utility_filterGroup')->placeholder('All Groups')->attribute('wire:model', 'filterGroup') }}
                </div>
                <div class="col-md-4 col-lg" wire:ignore>
                    <label class="form-label fw-medium">
                        <i class="fa fa-building text-primary me-1 small"></i> Building
                    </label>
                    {{ html()->select('filterBuilding', [])->value('')->class('select-property_building_id-list border-secondary-subtle shadow-sm')->id('utility_filterBuilding')->placeholder('All Buildings')->attribute('wire:model', 'filterBuilding')->attribute('data-group-select', '#utility_filterGroup') }}
                </div>
                <div class="col-md-4 col-lg" wire:ignore>
                    <label class="form-label fw-medium">
                        <i class="fa fa-home text-primary me-1 small"></i> Property/Unit
                    </label>
                    {{ html()->select('filterProperty', [])->value('')->class('select-property_id-list border-secondary-subtle shadow-sm')->id('utility_filterProperty')->placeholder('All Properties')->attribute('wire:model', 'filterProperty')->attribute('data-building-select', '#utility_filterBuilding')->attribute('data-group-select', '#utility_filterGroup') }}
                </div>
                <div class="col-md-4 col-lg" wire:ignore>
                    <label class="form-label fw-medium">
                        <i class="fa fa-user text-primary me-1 small"></i> Customer
                    </label>
                    {{ html()->select('filterCustomer', [])->value('')->class('select-customer_id-list border-secondary-subtle shadow-sm')->id('utility_filterCustomer')->placeholder('All Customers')->attribute('wire:model', 'filterCustomer') }}
                </div>
            </div>

            {{-- ═══ Filter Row 2: Dates + Utilities + Paid/Pending + Ownership ═══ --}}
            <div class="row g-3 mt-1">
                <div class="col-md-4 col-lg">
                    <label class="form-label fw-medium">
                        <i class="fa fa-calendar text-primary me-1 small"></i> From Date
                    </label>
                    <input type="date" wire:model="dateFrom" class="form-control form-control-sm border-secondary-subtle shadow-sm">
                </div>
                <div class="col-md-4 col-lg">
                    <label class="form-label fw-medium">
                        <i class="fa fa-calendar-check-o text-primary me-1 small"></i> To Date
                    </label>
                    <input type="date" wire:model="dateTo" class="form-control form-control-sm border-secondary-subtle shadow-sm">
                </div>
                <div class="col-md-4 col-lg">
                    <label class="form-label fw-medium">
                        <i class="fa fa-bolt text-primary me-1 small"></i> Utilities
                    </label>
                    <select wire:model="filterUtility" class="form-select form-select-sm border-secondary-subtle shadow-sm">
                        <option value="">All</option>
                        @foreach($utilities as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 col-lg">
                    <label class="form-label fw-medium">
                        <i class="fa fa-check-circle text-primary me-1 small"></i> Paid/Pending
                    </label>
                    <select wire:model="filterPaidStatus" class="form-select form-select-sm border-secondary-subtle shadow-sm">
                        <option value="">All</option>
                        <option value="pending">Pending</option>
                        <option value="paid">Paid</option>
                    </select>
                </div>
                <div class="col-md-4 col-lg">
                    <label class="form-label fw-medium">
                        <i class="fa fa-id-card text-primary me-1 small"></i> Ownership
                    </label>
                    <input type="text" wire:model="filterOwnership" class="form-control form-control-sm border-secondary-subtle shadow-sm">
                </div>
            </div>

            {{-- ═══ Reset Filters + Apply ═══ --}}
            <div class="row mt-3">
                <div class="col-12 d-flex gap-2">
                    <button wire:click="applyFilters" class="btn btn-sm btn-primary d-flex align-items-center gap-1 shadow-sm">
                        <i class="fa fa-filter"></i> Apply
                    </button>
                    <button wire:click="resetFilters"
                        class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1 shadow-sm">
                        <i class="fa fa-times"></i>
                        Reset Filters
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
                            <th class="fw-semibold py-2">#</th>
                            <th class="fw-semibold py-2">
                                <div class="form-check ms-1">
                                    <input type="checkbox" wire:model.live="selectAll"
                                        class="form-check-input shadow-sm" id="selectAllCheckbox" />
                                </div>
                            </th>
                            @if($this->isColumnVisible('date'))
                                <th class="fw-semibold">
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="date" label="Date" />
                                </th>
                            @endif
                            @if($this->isColumnVisible('customer'))
                                <th class="fw-semibold">Customer</th>
                            @endif
                            @if($this->isColumnVisible('group'))
                                <th class="fw-semibold">
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="rent_out_id" label="Group/Project" />
                                </th>
                            @endif
                            @if($this->isColumnVisible('building'))
                                <th class="fw-semibold">Building</th>
                            @endif
                            @if($this->isColumnVisible('property'))
                                <th class="fw-semibold">Property No/Unit</th>
                            @endif
                            @if($this->isColumnVisible('ownership'))
                                <th class="fw-semibold">Ownership</th>
                            @endif
                            @if($this->isColumnVisible('utility'))
                                <th class="fw-semibold">
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="utility_id" label="Utility" />
                                </th>
                            @endif
                            @if($this->isColumnVisible('amount'))
                                <th class="fw-semibold text-end">
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="amount" label="Amount" />
                                </th>
                            @endif
                            @if($this->isColumnVisible('paid'))
                                <th class="fw-semibold text-end">
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="paid" label="Paid" />
                                </th>
                            @endif
                            @if($this->isColumnVisible('balance'))
                                <th class="fw-semibold text-end">
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="balance" label="Balance" />
                                </th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data as $index => $item)
                            <tr>
                                <td>
                                    <span class="badge bg-light text-dark border">#{{ $data->firstItem() + $index }}</span>
                                </td>
                                <td>
                                    <div class="form-check ms-1">
                                        <input type="checkbox" value="{{ $item->id }}"
                                            wire:model.live="selected" class="form-check-input shadow-sm"
                                            id="checkbox{{ $item->id }}" />
                                    </div>
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
                                            {{ $item->rentOut?->customer?->name }}
                                        </a>
                                    </td>
                                @endif
                                @if($this->isColumnVisible('group'))
                                    <td>
                                        <i class="fa fa-folder-open me-1 text-muted opacity-75"></i>
                                        {{ $item->rentOut?->group?->name }}
                                    </td>
                                @endif
                                @if($this->isColumnVisible('building'))
                                    <td>
                                        <i class="fa fa-building-o me-1 text-muted opacity-75"></i>
                                        {{ $item->rentOut?->building?->name }}
                                    </td>
                                @endif
                                @if($this->isColumnVisible('property'))
                                    <td>
                                        <i class="fa fa-home me-1 text-muted opacity-75"></i>
                                        {{ $item->rentOut?->property?->number }}
                                    </td>
                                @endif
                                @if($this->isColumnVisible('ownership'))
                                    <td>{{ $item->rentOut?->property?->ownership }}</td>
                                @endif
                                @if($this->isColumnVisible('utility'))
                                    <td>
                                        <i class="fa fa-bolt me-1 text-muted opacity-75"></i>
                                        {{ $item->utility?->name }}
                                    </td>
                                @endif
                                @if($this->isColumnVisible('amount'))
                                    <td class="text-end fw-medium">{{ number_format($item->amount, 2) }}</td>
                                @endif
                                @if($this->isColumnVisible('paid'))
                                    <td class="text-end fw-medium">{{ number_format($item->paid, 2) }}</td>
                                @endif
                                @if($this->isColumnVisible('balance'))
                                    <td class="text-end {{ $item->balance > 0 ? 'text-danger fw-bold' : 'text-success' }}">
                                        {{ number_format($item->balance, 2) }}
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="20" class="text-center py-5 text-muted">
                                    <i class="fa fa-inbox fa-2x mb-2 d-block opacity-50"></i>
                                    No utility payment records found.
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

                $('#utility_filterGroup').on('change', function(e) {
                    @this.set('filterGroup', $(this).val() || '');
                    clearAndReload('utility_filterBuilding');
                    clearAndReload('utility_filterProperty');
                    @this.set('filterBuilding', '');
                    @this.set('filterProperty', '');
                });
                $('#utility_filterBuilding').on('change', function(e) {
                    @this.set('filterBuilding', $(this).val() || '');
                    clearAndReload('utility_filterProperty');
                    @this.set('filterProperty', '');
                });
                $('#utility_filterProperty').on('change', function(e) {
                    @this.set('filterProperty', $(this).val() || '');
                });
                $('#utility_filterCustomer').on('change', function(e) {
                    @this.set('filterCustomer', $(this).val() || '');
                });

            });
        </script>
    @endpush
</div>

<div>
    {{-- Summary Cards --}}
    @php
        $summaryCardsList = [
            ['label' => 'Total Security Amount', 'value' => $summaryCards['total'], 'color' => 'success', 'icon' => 'pli-shield'],
            ['label' => 'Overdue Amount', 'value' => $summaryCards['overdue'], 'color' => 'danger', 'icon' => 'pli-alarm-clock'],
            ['label' => 'Paid Amount', 'value' => $summaryCards['paid'], 'color' => 'info', 'icon' => 'pli-check'],
        ];
    @endphp
    <div class="row g-3 mb-4">
        @foreach ($summaryCardsList as $card)
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body py-3 d-flex align-items-center">
                        <div class="rounded-circle d-flex align-items-center justify-content-center me-3 bg-{{ $card['color'] }} bg-opacity-10"
                             style="width: 48px; height: 48px; min-width: 48px;">
                            <i class="{{ $card['icon'] }} fs-4 text-{{ $card['color'] }}"></i>
                        </div>
                        <div>
                            <div class="small text-muted">{{ $card['label'] }}</div>
                            <div class="fs-5 fw-bold text-{{ $card['color'] }}">{{ number_format($card['value'], 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
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
                                    placeholder="Search security records..."
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
                                            'customer' => 'Customer',
                                            'group' => 'Property Group',
                                            'building' => 'Property Building',
                                            'type' => 'Property Type',
                                            'property' => 'Property No/Unit',
                                            'security_type' => 'Type',
                                            'payment_method' => 'Payment Method',
                                            'cheque_no' => 'Cheque No',
                                            'bank' => 'Bank Name',
                                            'due_date' => 'Due Date',
                                            'amount' => 'Amount',
                                            'status' => 'Status',
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

            {{-- ═══ Filter Row 1: Group, Building, Property, Customer, Security Type ═══ --}}
            <div class="row g-3">
                <div class="col-md-4 col-lg" wire:ignore>
                    <label class="form-label fw-medium">
                        <i class="fa fa-folder-open text-primary me-1 small"></i> Group
                    </label>
                    {{ html()->select('filterGroup', [])->value('')->class('select-property_group_id-list border-secondary-subtle shadow-sm')->id('security_filterGroup')->placeholder('All Groups')->attribute('wire:model', 'filterGroup') }}
                </div>
                <div class="col-md-4 col-lg" wire:ignore>
                    <label class="form-label fw-medium">
                        <i class="fa fa-building text-primary me-1 small"></i> Building
                    </label>
                    {{ html()->select('filterBuilding', [])->value('')->class('select-property_building_id-list border-secondary-subtle shadow-sm')->id('security_filterBuilding')->placeholder('All Buildings')->attribute('wire:model', 'filterBuilding')->attribute('data-group-select', '#security_filterGroup') }}
                </div>
                <div class="col-md-4 col-lg" wire:ignore>
                    <label class="form-label fw-medium">
                        <i class="fa fa-home text-primary me-1 small"></i> Property/Unit
                    </label>
                    {{ html()->select('filterProperty', [])->value('')->class('select-property_id-list border-secondary-subtle shadow-sm')->id('security_filterProperty')->placeholder('All Properties')->attribute('wire:model', 'filterProperty')->attribute('data-building-select', '#security_filterBuilding')->attribute('data-group-select', '#security_filterGroup') }}
                </div>
                <div class="col-md-4 col-lg" wire:ignore>
                    <label class="form-label fw-medium">
                        <i class="fa fa-user text-primary me-1 small"></i> Customer
                    </label>
                    {{ html()->select('filterCustomer', [])->value('')->class('select-customer_id-list border-secondary-subtle shadow-sm')->id('security_filterCustomer')->placeholder('All Customers')->attribute('wire:model', 'filterCustomer') }}
                </div>
                <div class="col-md-4 col-lg">
                    <label class="form-label fw-medium">
                        <i class="fa fa-shield text-primary me-1 small"></i> Security Type
                    </label>
                    <select wire:model="filterSecurityType" class="form-select form-select-sm border-secondary-subtle shadow-sm">
                        <option value="">All</option>
                        @foreach($securityTypes as $type)
                            <option value="{{ $type->value }}">{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- ═══ Filter Row 2: Payment Method, Status, From Date, To Date ═══ --}}
            <div class="row g-3 mt-1">
                <div class="col-md-4 col-lg">
                    <label class="form-label fw-medium">
                        <i class="fa fa-credit-card text-primary me-1 small"></i> Payment Method
                    </label>
                    <select wire:model="filterPaymentMethod" class="form-select form-select-sm border-secondary-subtle shadow-sm">
                        <option value="">All</option>
                        @foreach($paymentModes as $mode)
                            <option value="{{ $mode->value }}">{{ $mode->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 col-lg">
                    <label class="form-label fw-medium">
                        <i class="fa fa-flag text-primary me-1 small"></i> Status
                    </label>
                    <select wire:model="filterSecurityStatus" class="form-select form-select-sm border-secondary-subtle shadow-sm">
                        <option value="">All</option>
                        @foreach($securityStatuses as $status)
                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
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
                            @if($this->isColumnVisible('customer'))
                                <th class="fw-semibold">
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="rent_out_id" label="Customer" />
                                </th>
                            @endif
                            @if($this->isColumnVisible('group'))
                                <th class="fw-semibold">Property Group</th>
                            @endif
                            @if($this->isColumnVisible('building'))
                                <th class="fw-semibold">Property Building</th>
                            @endif
                            @if($this->isColumnVisible('type'))
                                <th class="fw-semibold">Property Type</th>
                            @endif
                            @if($this->isColumnVisible('property'))
                                <th class="fw-semibold">Property No/Unit</th>
                            @endif
                            @if($this->isColumnVisible('security_type'))
                                <th class="fw-semibold">
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="type" label="Type" />
                                </th>
                            @endif
                            @if($this->isColumnVisible('payment_method'))
                                <th class="fw-semibold">Payment Method</th>
                            @endif
                            @if($this->isColumnVisible('cheque_no'))
                                <th class="fw-semibold">Cheque No</th>
                            @endif
                            @if($this->isColumnVisible('bank'))
                                <th class="fw-semibold">Bank Name</th>
                            @endif
                            @if($this->isColumnVisible('due_date'))
                                <th class="fw-semibold">
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="due_date" label="Due Date" />
                                </th>
                            @endif
                            @if($this->isColumnVisible('amount'))
                                <th class="fw-semibold text-end">
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="amount" label="Amount" />
                                </th>
                            @endif
                            @if($this->isColumnVisible('status'))
                                <th class="fw-semibold">
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="status" label="Status" />
                                </th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data as $index => $item)
                            <tr>
                                <td>
                                    <span class="badge bg-light text-dark border">#{{ $item->id }}</span>
                                </td>
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
                                @if($this->isColumnVisible('type'))
                                    <td>{{ $item->rentOut?->type?->name }}</td>
                                @endif
                                @if($this->isColumnVisible('property'))
                                    <td>
                                        <i class="fa fa-home me-1 text-muted opacity-75"></i>
                                        {{ $item->rentOut?->property?->number }}
                                    </td>
                                @endif
                                @if($this->isColumnVisible('security_type'))
                                    <td>{{ $item->type?->label() }}</td>
                                @endif
                                @if($this->isColumnVisible('payment_method'))
                                    <td>{{ $item->payment_mode?->label() }}</td>
                                @endif
                                @if($this->isColumnVisible('cheque_no'))
                                    <td>{{ $item->cheque_no }}</td>
                                @endif
                                @if($this->isColumnVisible('bank'))
                                    <td>
                                        <i class="fa fa-university me-1 text-muted opacity-75"></i>
                                        {{ $item->bank_name }}
                                    </td>
                                @endif
                                @if($this->isColumnVisible('due_date'))
                                    <td>
                                        <i class="fa fa-calendar me-1 text-muted opacity-75"></i>{{ $item->due_date?->format('d-m-Y') }}
                                    </td>
                                @endif
                                @if($this->isColumnVisible('amount'))
                                    <td class="text-end fw-medium">{{ number_format($item->amount, 2) }}</td>
                                @endif
                                @if($this->isColumnVisible('status'))
                                    <td>
                                        @if($item->status)
                                            <span class="badge bg-{{ $item->status->color() }}">{{ $item->status->label() }}</span>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="20" class="text-center py-5 text-muted">
                                    <i class="fa fa-inbox fa-2x mb-2 d-block opacity-50"></i>
                                    No security records found.
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

                $('#security_filterGroup').on('change', function(e) {
                    @this.set('filterGroup', $(this).val() || '');
                    clearAndReload('security_filterBuilding');
                    clearAndReload('security_filterProperty');
                    @this.set('filterBuilding', '');
                    @this.set('filterProperty', '');
                });
                $('#security_filterBuilding').on('change', function(e) {
                    @this.set('filterBuilding', $(this).val() || '');
                    clearAndReload('security_filterProperty');
                    @this.set('filterProperty', '');
                });
                $('#security_filterProperty').on('change', function(e) {
                    @this.set('filterProperty', $(this).val() || '');
                });
                $('#security_filterCustomer').on('change', function(e) {
                    @this.set('filterCustomer', $(this).val() || '');
                });

            });
        </script>
    @endpush
</div>

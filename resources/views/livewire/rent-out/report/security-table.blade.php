<div>
    {{-- Summary Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 overflow-hidden">
                <div class="card-header bg-success text-white py-2 fw-bold text-center">Total Security Amount</div>
                <div class="card-body text-center border border-success">
                    <h3 class="mb-0">{{ number_format($summaryCards['total'], 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 overflow-hidden">
                <div class="card-header bg-danger text-white py-2 fw-bold text-center">Overdue Amount</div>
                <div class="card-body text-center border border-danger">
                    <h3 class="mb-0 text-danger">{{ number_format($summaryCards['overdue'], 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 overflow-hidden">
                <div class="card-header bg-primary text-white py-2 fw-bold text-center">Paid Amount</div>
                <div class="card-body text-center border border-primary">
                    <h3 class="mb-0 text-primary">{{ number_format($summaryCards['paid'], 2) }}</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- Security Report Filters --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white py-2">
            <i class="demo-psi-filter me-2"></i> <strong>Security Report Filters</strong>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Customer</label>
                    <input type="text" wire:model="filterCustomer" class="form-control form-control-sm" placeholder="Search Customer Name">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Group</label>
                    {{ html()->select('filterGroup', $groups->prepend('Search Here', ''))->value($filterGroup)->class('form-select form-select-sm')->attribute('wire:model', 'filterGroup') }}
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Building</label>
                    {{ html()->select('filterBuilding', $buildings->prepend('Search Here', ''))->value($filterBuilding)->class('form-select form-select-sm')->attribute('wire:model', 'filterBuilding') }}
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Type</label>
                    {{ html()->select('filterType', $types->prepend('Search Here', ''))->value($filterType)->class('form-select form-select-sm')->attribute('wire:model', 'filterType') }}
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Property No/Unit</label>
                    <input type="text" wire:model="filterProperty" class="form-control form-control-sm" placeholder="Search Here">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Security Type</label>
                    <select wire:model="filterSecurityType" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach($securityTypes as $type)
                            <option value="{{ $type->value }}">{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Payment Method</label>
                    <select wire:model="filterPaymentMethod" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach($paymentModes as $mode)
                            <option value="{{ $mode->value }}">{{ $mode->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Status</label>
                    <select wire:model="filterSecurityStatus" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach($securityStatuses as $status)
                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">From Date</label>
                    <input type="date" wire:model="dateFrom" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">To Date</label>
                    <input type="date" wire:model="dateTo" class="form-control form-control-sm">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button wire:click="applyFilters" class="btn btn-sm btn-info text-white w-100">
                        <i class="demo-psi-magnifi-glass me-1"></i> Search
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Data Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            {{-- Table Controls --}}
            <div class="d-flex flex-wrap gap-2 p-3 border-bottom align-items-center">
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                        Column visibility
                    </button>
                    <div class="dropdown-menu p-2" style="min-width: 200px;">
                        @foreach(['customer' => 'Customer', 'group' => 'Property Group', 'building' => 'Property Building', 'type' => 'Property Type', 'property' => 'Property No/Unit', 'security_type' => 'Type', 'payment_method' => 'Payment Method', 'cheque_no' => 'Cheque No', 'bank' => 'Bank Name', 'due_date' => 'Due Date', 'amount' => 'Amount', 'status' => 'Status'] as $col => $label)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" wire:click="toggleColumn('{{ $col }}')" {{ $this->isColumnVisible($col) ? 'checked' : '' }} id="scol-{{ $col }}">
                                <label class="form-check-label small" for="scol-{{ $col }}">{{ $label }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>

                <select wire:model.live="limit" class="form-select form-select-sm" style="width: auto;">
                    <option value="20">Show 20 rows</option>
                    <option value="50">Show 50 rows</option>
                    <option value="100">Show 100 rows</option>
                </select>

                <button wire:click="download" class="btn btn-sm btn-success">Excel</button>

                <div class="ms-auto">
                    <input type="text" wire:model.live.debounce.300ms="search" class="form-control form-control-sm" placeholder="Search:" style="width: 200px;">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="50">#</th>
                            @if($this->isColumnVisible('customer'))
                                <th class="cursor-pointer" wire:click="sortBy('rent_out_id')">
                                    Customer
                                    @if($sortField === 'rent_out_id')
                                        <i class="demo-psi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                    @endif
                                </th>
                            @endif
                            @if($this->isColumnVisible('group'))
                                <th>Property Group</th>
                            @endif
                            @if($this->isColumnVisible('building'))
                                <th>Property Building</th>
                            @endif
                            @if($this->isColumnVisible('type'))
                                <th>Property Type</th>
                            @endif
                            @if($this->isColumnVisible('property'))
                                <th>Property No/Unit</th>
                            @endif
                            @if($this->isColumnVisible('security_type'))
                                <th class="cursor-pointer" wire:click="sortBy('type')">
                                    Type
                                    @if($sortField === 'type')
                                        <i class="demo-psi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                    @endif
                                </th>
                            @endif
                            @if($this->isColumnVisible('payment_method'))
                                <th>Payment Method</th>
                            @endif
                            @if($this->isColumnVisible('cheque_no'))
                                <th>Cheque No</th>
                            @endif
                            @if($this->isColumnVisible('bank'))
                                <th>Bank Name</th>
                            @endif
                            @if($this->isColumnVisible('due_date'))
                                <th class="cursor-pointer" wire:click="sortBy('due_date')">
                                    Due Date
                                    @if($sortField === 'due_date')
                                        <i class="demo-psi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                    @endif
                                </th>
                            @endif
                            @if($this->isColumnVisible('amount'))
                                <th class="text-end cursor-pointer" wire:click="sortBy('amount')">
                                    Amount
                                    @if($sortField === 'amount')
                                        <i class="demo-psi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                    @endif
                                </th>
                            @endif
                            @if($this->isColumnVisible('status'))
                                <th class="cursor-pointer" wire:click="sortBy('status')">
                                    Status
                                    @if($sortField === 'status')
                                        <i class="demo-psi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                    @endif
                                </th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data as $index => $item)
                            <tr>
                                <td>{{ $item->id }}</td>
                                @if($this->isColumnVisible('customer'))
                                    <td>
                                        <a href="{{ route('property::rent::view', $item->rent_out_id) }}" class="text-decoration-none">
                                            {{ $item->rentOut?->customer?->name }}
                                        </a>
                                    </td>
                                @endif
                                @if($this->isColumnVisible('group'))
                                    <td>{{ $item->rentOut?->group?->name }}</td>
                                @endif
                                @if($this->isColumnVisible('building'))
                                    <td>{{ $item->rentOut?->building?->name }}</td>
                                @endif
                                @if($this->isColumnVisible('type'))
                                    <td>{{ $item->rentOut?->type?->name }}</td>
                                @endif
                                @if($this->isColumnVisible('property'))
                                    <td>{{ $item->rentOut?->property?->number }}</td>
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
                                    <td>{{ $item->bank_name }}</td>
                                @endif
                                @if($this->isColumnVisible('due_date'))
                                    <td>{{ $item->due_date?->format('d-m-Y') }}</td>
                                @endif
                                @if($this->isColumnVisible('amount'))
                                    <td class="text-end">{{ number_format($item->amount, 2) }}</td>
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
                                <td colspan="20" class="text-center py-4 text-muted">No security records found.</td>
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

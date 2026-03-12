<div>
    {{-- Filters --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Group/Project</label>
                    {{ html()->select('filterGroup', $groups->prepend('Select Type', ''))->value($filterGroup)->class('form-select form-select-sm')->attribute('wire:model', 'filterGroup') }}
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Building</label>
                    {{ html()->select('filterBuilding', $buildings->prepend('Select Building', ''))->value($filterBuilding)->class('form-select form-select-sm')->attribute('wire:model', 'filterBuilding') }}
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Type</label>
                    {{ html()->select('filterType', $types->prepend('Select Type', ''))->value($filterType)->class('form-select form-select-sm')->attribute('wire:model', 'filterType') }}
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Property No/Unit</label>
                    <input type="text" wire:model="filterProperty" class="form-control form-control-sm" placeholder="Search Here">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Customer</label>
                    <input type="text" wire:model="filterCustomer" class="form-control form-control-sm" placeholder="Search Customer Name">
                </div>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-2">
                    <label class="form-label small fw-bold">From Date</label>
                    <input type="date" wire:model="dateFrom" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">To Date</label>
                    <input type="date" wire:model="dateTo" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Utilities</label>
                    <select wire:model="filterUtility" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach($utilities as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Paid/Pending</label>
                    <select wire:model="filterPaidStatus" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="pending">Pending</option>
                        <option value="paid">Paid</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Ownership</label>
                    <input type="text" wire:model="filterOwnership" class="form-control form-control-sm">
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button wire:click="applyFilters" class="btn btn-sm btn-primary">Apply</button>
                    <button wire:click="download" class="btn btn-sm btn-success">
                        <i class="demo-psi-download"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Utility Summary --}}
    <div class="card border-0 shadow-sm mb-3">
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
                        @foreach(['date' => 'Date', 'customer' => 'Customer', 'group' => 'Group/Project', 'building' => 'Building', 'property' => 'Property No/Unit', 'ownership' => 'Ownership', 'utility' => 'Utility', 'amount' => 'Amount', 'paid' => 'Paid', 'balance' => 'Balance'] as $col => $label)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" wire:click="toggleColumn('{{ $col }}')" {{ $this->isColumnVisible($col) ? 'checked' : '' }} id="ucol-{{ $col }}">
                                <label class="form-check-label small" for="ucol-{{ $col }}">{{ $label }}</label>
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
                            <th width="30">#</th>
                            <th width="30">
                                <input type="checkbox" class="form-check-input" wire:model.live="selectAll">
                            </th>
                            @if($this->isColumnVisible('date'))
                                <th class="cursor-pointer" wire:click="sortBy('date')">
                                    Date
                                    @if($sortField === 'date')
                                        <i class="demo-psi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                    @endif
                                </th>
                            @endif
                            @if($this->isColumnVisible('customer'))
                                <th>Customer</th>
                            @endif
                            @if($this->isColumnVisible('group'))
                                <th class="cursor-pointer" wire:click="sortBy('rent_out_id')">
                                    Group/Project
                                    @if($sortField === 'rent_out_id')
                                        <i class="demo-psi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                    @endif
                                </th>
                            @endif
                            @if($this->isColumnVisible('building'))
                                <th>Building</th>
                            @endif
                            @if($this->isColumnVisible('property'))
                                <th>Property No/Unit</th>
                            @endif
                            @if($this->isColumnVisible('ownership'))
                                <th>Ownership</th>
                            @endif
                            @if($this->isColumnVisible('utility'))
                                <th class="cursor-pointer" wire:click="sortBy('utility_id')">
                                    Utility
                                    @if($sortField === 'utility_id')
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
                                <td>{{ $data->firstItem() + $index }}</td>
                                <td>
                                    <input type="checkbox" class="form-check-input" value="{{ $item->id }}" wire:model.live="selected">
                                </td>
                                @if($this->isColumnVisible('date'))
                                    <td>{{ $item->date?->format('d-m-Y') }}</td>
                                @endif
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
                                @if($this->isColumnVisible('property'))
                                    <td>{{ $item->rentOut?->property?->number }}</td>
                                @endif
                                @if($this->isColumnVisible('ownership'))
                                    <td>{{ $item->rentOut?->property?->ownership }}</td>
                                @endif
                                @if($this->isColumnVisible('utility'))
                                    <td>{{ $item->utility?->name }}</td>
                                @endif
                                @if($this->isColumnVisible('amount'))
                                    <td class="text-end">{{ number_format($item->amount, 2) }}</td>
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
                                <td colspan="20" class="text-center py-4 text-muted">No utility payment records found.</td>
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

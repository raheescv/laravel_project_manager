<div>
    {{-- Filters --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white py-2 cursor-pointer" data-bs-toggle="collapse" data-bs-target="#chequeFilters">
            <i class="demo-psi-filter me-2"></i> <strong>Cheque Management</strong> <span class="text-muted">Filters</span>
            <i class="demo-psi-arrow-down float-end mt-1"></i>
        </div>
        <div class="collapse show" id="chequeFilters">
            <div class="card-body">
                <h6 class="text-muted fw-bold mb-3"><i class="demo-psi-building me-1"></i> Property Information</h6>
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Group/Project</label>
                        {{ html()->select('filterGroup', $groups->prepend('Select Type', ''))->value($filterGroup)->class('form-select form-select-sm')->attribute('wire:model', 'filterGroup') }}
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Building</label>
                        {{ html()->select('filterBuilding', $buildings->prepend('Select Building', ''))->value($filterBuilding)->class('form-select form-select-sm')->attribute('wire:model', 'filterBuilding') }}
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Type</label>
                        {{ html()->select('filterType', $types->prepend('Select Type', ''))->value($filterType)->class('form-select form-select-sm')->attribute('wire:model', 'filterType') }}
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Property No/Unit</label>
                        <input type="text" wire:model="filterProperty" class="form-control form-control-sm" placeholder="Search Here">
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
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Customer</label>
                        <input type="text" wire:model="filterCustomer" class="form-control form-control-sm" placeholder="Search Customer Name">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Ownership</label>
                        <input type="text" wire:model="filterOwnership" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Status</label>
                        <select wire:model="filterStatus" class="form-select form-select-sm">
                            <option value="">All</option>
                            @foreach($chequeStatuses as $status)
                                <option value="{{ $status->value }}">{{ $status->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="text-end">
                    <button wire:click="applyFilters" class="btn btn-sm btn-success">
                        <i class="demo-psi-check me-1"></i> Apply Filters
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Cheque Management Data --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-dark text-white py-2 d-flex justify-content-between">
            <span><i class="demo-psi-file me-1"></i> Cheque Management Data</span>
            <span class="badge bg-info rounded-pill">Records: {{ $data->total() }}</span>
        </div>
        <div class="card-body p-0">
            {{-- Table Controls --}}
            <div class="d-flex flex-wrap gap-2 p-3 border-bottom align-items-center">
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                        Column visibility
                    </button>
                    <div class="dropdown-menu p-2" style="min-width: 200px;">
                        @foreach(['date' => 'Date', 'customer' => 'Customer', 'building' => 'Building', 'property' => 'Property No/Unit', 'bank' => 'Bank', 'cheque_no' => 'Cheque No', 'amount' => 'Amount', 'status' => 'Status'] as $col => $label)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" wire:click="toggleColumn('{{ $col }}')" {{ $this->isColumnVisible($col) ? 'checked' : '' }} id="ccol-{{ $col }}">
                                <label class="form-check-label small" for="ccol-{{ $col }}">{{ $label }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>

                <button wire:click="download" class="btn btn-sm btn-success">Excel</button>

                <button wire:click="updatedSelectAll(true)" class="btn btn-sm btn-outline-primary">Select all</button>
                <button wire:click="deselectAll" class="btn btn-sm btn-outline-secondary">Deselect all</button>

                @if(count($selected) > 0)
                    <button wire:click="openStatusModal" class="btn btn-sm btn-warning">
                        Status Change ({{ count($selected) }})
                    </button>
                @endif

                <select wire:model.live="limit" class="form-select form-select-sm" style="width: auto;">
                    <option value="15">Show 15 rows</option>
                    <option value="50">Show 50 rows</option>
                    <option value="100">Show 100 rows</option>
                </select>

                <div class="ms-auto">
                    <input type="text" wire:model.live.debounce.300ms="search" class="form-control form-control-sm" placeholder="Search:" style="width: 200px;">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="50">#</th>
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
                                <th class="cursor-pointer" wire:click="sortBy('rent_out_id')">
                                    Customer
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
                            @if($this->isColumnVisible('bank'))
                                <th>Bank</th>
                            @endif
                            @if($this->isColumnVisible('cheque_no'))
                                <th class="cursor-pointer" wire:click="sortBy('cheque_no')">
                                    Cheque No
                                    @if($sortField === 'cheque_no')
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
                                <td>
                                    <input type="checkbox" class="form-check-input" value="{{ $item->id }}" wire:model.live="selected">
                                </td>
                                @if($this->isColumnVisible('date'))
                                    <td>{{ $item->date?->format('d-m-Y') }}</td>
                                @endif
                                @if($this->isColumnVisible('customer'))
                                    <td>{{ $item->rentOut?->customer?->name }}</td>
                                @endif
                                @if($this->isColumnVisible('building'))
                                    <td>{{ $item->rentOut?->building?->name }}</td>
                                @endif
                                @if($this->isColumnVisible('property'))
                                    <td>
                                        <a href="{{ route('property::rent::view', $item->rent_out_id) }}" class="text-decoration-none text-success">
                                            {{ $item->rentOut?->property?->number }}
                                        </a>
                                    </td>
                                @endif
                                @if($this->isColumnVisible('bank'))
                                    <td>{{ $item->bank_name }}</td>
                                @endif
                                @if($this->isColumnVisible('cheque_no'))
                                    <td>{{ $item->cheque_no }}</td>
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
                                <td colspan="20" class="text-center py-4 text-muted">No cheque records found.</td>
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

    {{-- Status Change Modal --}}
    @if($showStatusModal)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Rentout Cheque Management Model</h5>
                        <button type="button" class="btn-close" wire:click="$set('showStatusModal', false)"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Status</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="demo-psi-check"></i></span>
                                <select wire:model="statusChangeStatus" class="form-select">
                                    @foreach($chequeStatuses as $status)
                                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Payment Method</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="demo-psi-credit-card"></i></span>
                                <select wire:model="statusChangePaymentMethod" class="form-select">
                                    <option value="">Select Payment Method</option>
                                    @foreach(\App\Enums\RentOut\PaymentMode::cases() as $mode)
                                        <option value="{{ $mode->value }}">{{ $mode->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Journal Date</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="demo-psi-calendar"></i></span>
                                <input type="date" wire:model="statusChangeJournalDate" class="form-control">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Remark</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="demo-psi-speech-bubble"></i></span>
                                <input type="text" wire:model="statusChangeRemark" class="form-control" placeholder="Enter your remark">
                            </div>
                        </div>

                        <div class="mb-0">
                            <label class="form-label fw-bold">Selected Cheques:</label>
                            <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Customer</th>
                                            <th>Cheque No</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($this->selectedCheques as $index => $cheque)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>{{ $cheque['customer'] }}</td>
                                                <td>{{ $cheque['cheque_no'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="$set('showStatusModal', false)">
                            <i class="demo-psi-cross me-1"></i> Close
                        </button>
                        <button type="button" class="btn btn-success" wire:click="updateChequeStatus">
                            <i class="demo-psi-check me-1"></i> Update
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<div>
    <!-- Filters Section -->
    <div class="card mb-3 shadow-sm">
        <div class="card-header bg-light py-3">
            <h5 class="card-title mb-0 d-flex align-items-center">
                <i class="fa fa-filter me-2 text-primary"></i>
                Filters
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="from_date" class="form-label small fw-medium">
                        <i class="fa fa-calendar me-1 text-muted"></i>
                        From Date
                    </label>
                    {{ html()->date('from_date')->value('')->class('form-control border-secondary-subtle shadow-sm')->id('from_date')->attribute('wire:model.live', 'from_date') }}
                </div>
                <div class="col-md-3">
                    <label for="to_date" class="form-label small fw-medium">
                        <i class="fa fa-calendar me-1 text-muted"></i>
                        To Date
                    </label>
                    {{ html()->date('to_date')->value('')->class('form-control border-secondary-subtle shadow-sm')->id('to_date')->attribute('wire:model.live', 'to_date') }}
                </div>
                <div class="col-md-3">
                    <label for="account_id" class="form-label small fw-medium">
                        <i class="fa fa-university me-1 text-muted"></i>
                        Bank Account
                    </label>
                    {{ html()->select('account_id', ['' => 'All Bank Accounts'] + $bankAccounts->pluck('name', 'id')->toArray())->value('')->class('form-control border-secondary-subtle shadow-sm')->id('account_id')->attribute('wire:model.live', 'account_id') }}
                </div>
                <div class="col-md-3">
                    <label for="delivered_date_filter" class="form-label small fw-medium">
                        <i class="fa fa-check-circle me-1 text-muted"></i>
                        Status
                    </label>
                    {{ html()->select('delivered_date_filter', ['all' => 'All', 'delivered' => 'Delivered', 'pending' => 'Pending'])->value('pending')->class('form-control border-secondary-subtle shadow-sm')->id('delivered_date_filter')->attribute('wire:model.live', 'delivered_date_filter') }}
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Update Section -->
    @if (count($selected) > 0)
        <div class="card mb-3 shadow-sm border-primary">
            <div class="card-header bg-primary text-white py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="small">
                        <i class="fa fa-check-square me-1"></i>
                        {{ count($selected) }} item(s) selected
                    </span>
                    <button class="btn btn-sm btn-light" wire:click="$set('selected', [])">
                        <i class="fa fa-times me-1"></i>Clear
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="bulkDeliveredDate" class="form-label small fw-medium">
                            <i class="fa fa-calendar-check me-1 text-muted"></i>
                            Set Delivered Date (Same for All)
                        </label>
                        {{ html()->date('bulkDeliveredDate')->value('')->class('form-control border-secondary-subtle shadow-sm')->id('bulkDeliveredDate')->attribute('wire:model', 'bulkDeliveredDate') }}
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-success w-100" wire:click="updateDeliveredDate">
                            <i class="fa fa-save me-1"></i>
                            Update Selected
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Multiple Row Update Section -->
    @if (count($rowDates) > 0)
        <div class="card mb-3 shadow-sm border-info">
            <div class="card-header bg-info text-white py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="small">
                        <i class="fa fa-edit me-1"></i>
                        {{ count($rowDates) }} row(s) with dates set
                    </span>
                    <button class="btn btn-sm btn-light" wire:click="$set('rowDates', [])">
                        <i class="fa fa-times me-1"></i>Clear All
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <p class="mb-0 small text-muted">
                        <i class="fa fa-info-circle me-1"></i>
                        You have set dates for {{ count($rowDates) }} row(s). Click below to update all at once.
                    </p>
                    <button class="btn btn-info" wire:click="updateMultipleRows">
                        <i class="fa fa-save me-1"></i>
                        Update All {{ count($rowDates) }} Rows
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Summary Cards -->
    <div class="row mb-3">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-gradient-primary text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <small class="opacity-75">Total Transactions</small>
                            <h4 class="mb-0 fw-bold">{{ number_format($summary->total_count) }}</h4>
                        </div>
                        <div class="fs-1 opacity-50">
                            <i class="fa fa-list"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-gradient-danger text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <small class="opacity-75">Total Debit</small>
                            <h4 class="mb-0 fw-bold">{{ currency($summary->total_debit) }}</h4>
                        </div>
                        <div class="fs-1 opacity-50">
                            <i class="fa fa-arrow-down"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-gradient-success text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <small class="opacity-75">Total Credit</small>
                            <h4 class="mb-0 fw-bold">{{ currency($summary->total_credit) }}</h4>
                        </div>
                        <div class="fs-1 opacity-50">
                            <i class="fa fa-arrow-up"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-gradient-warning text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <small class="opacity-75">Pending</small>
                            <h4 class="mb-0 fw-bold">{{ number_format($summary->pending_count) }}</h4>
                        </div>
                        <div class="fs-1 opacity-50">
                            <i class="fa fa-clock"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-light py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-semibold d-flex align-items-center">
                    <i class="fa fa-list me-2 text-primary"></i>
                    Bank Transactions
                </h5>
                <select wire:model.live="perPage" class="form-select form-select-sm border-secondary-subtle shadow-sm" style="width: 120px">
                    <option value="10">10 per page</option>
                    <option value="25">25 per page</option>
                    <option value="50">50 per page</option>
                    <option value="100">100 per page</option>
                </select>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="border-0" style="width: 40px;">
                                <input type="checkbox" class="form-check-input" wire:model.live="selectAll" id="selectAll">
                            </th>
                            <th class="border-0" style="cursor: pointer;" wire:click="sort('date')">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <i class="fa fa-calendar me-2 text-secondary small"></i>
                                        <span class="fw-semibold">Date</span>
                                    </div>
                                    @if($sortBy === 'date')
                                        <i class="fa fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-primary"></i>
                                    @else
                                        <i class="fa fa-sort text-muted opacity-50"></i>
                                    @endif
                                </div>
                            </th>
                            <th class="border-0" style="cursor: pointer;" wire:click="sort('account')">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <i class="fa fa-university me-2 text-secondary small"></i>
                                        <span class="fw-semibold">Account</span>
                                    </div>
                                    @if($sortBy === 'account')
                                        <i class="fa fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-primary"></i>
                                    @else
                                        <i class="fa fa-sort text-muted opacity-50"></i>
                                    @endif
                                </div>
                            </th>
                            <th class="border-0" style="cursor: pointer;" wire:click="sort('description')">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <i class="fa fa-file-text me-2 text-secondary small"></i>
                                        <span class="fw-semibold">Description</span>
                                    </div>
                                    @if($sortBy === 'description')
                                        <i class="fa fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-primary"></i>
                                    @else
                                        <i class="fa fa-sort text-muted opacity-50"></i>
                                    @endif
                                </div>
                            </th>
                            <th class="border-0" style="cursor: pointer;" wire:click="sort('reference')">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <i class="fa fa-hashtag me-2 text-secondary small"></i>
                                        <span class="fw-semibold">Reference</span>
                                    </div>
                                    @if($sortBy === 'reference')
                                        <i class="fa fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-primary"></i>
                                    @else
                                        <i class="fa fa-sort text-muted opacity-50"></i>
                                    @endif
                                </div>
                            </th>
                            <th class="border-0 text-end" style="cursor: pointer;" wire:click="sort('debit')">
                                <div class="d-flex align-items-center justify-content-end">
                                    <div class="text-end">
                                        <i class="fa fa-arrow-down me-2 text-danger small"></i>
                                        <span class="fw-semibold">Debit</span>
                                    </div>
                                    @if($sortBy === 'debit')
                                        <i class="fa fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-primary ms-2"></i>
                                    @else
                                        <i class="fa fa-sort text-muted opacity-50 ms-2"></i>
                                    @endif
                                </div>
                            </th>
                            <th class="border-0 text-end" style="cursor: pointer;" wire:click="sort('credit')">
                                <div class="d-flex align-items-center justify-content-end">
                                    <div class="text-end">
                                        <i class="fa fa-arrow-up me-2 text-success small"></i>
                                        <span class="fw-semibold">Credit</span>
                                    </div>
                                    @if($sortBy === 'credit')
                                        <i class="fa fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-primary ms-2"></i>
                                    @else
                                        <i class="fa fa-sort text-muted opacity-50 ms-2"></i>
                                    @endif
                                </div>
                            </th>
                            <th class="border-0" style="cursor: pointer;" wire:click="sort('delivered_date')">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <i class="fa fa-calendar-check me-2 text-secondary small"></i>
                                        <span class="fw-semibold">Delivered Date</span>
                                    </div>
                                    @if($sortBy === 'delivered_date')
                                        <i class="fa fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-primary"></i>
                                    @else
                                        <i class="fa fa-sort text-muted opacity-50"></i>
                                    @endif
                                </div>
                            </th>
                            <th class="border-0" style="width: 200px;">
                                <i class="fa fa-edit me-2 text-secondary small"></i>
                                <span class="fw-semibold">Update Date</span>
                            </th>
                            <th class="border-0 text-center" style="width: 100px;">
                                <i class="fa fa-cog me-2 text-secondary small"></i>
                                <span class="fw-semibold">Action</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr class="{{ in_array($item->id, $selected) ? 'table-primary' : '' }}">
                                <td>
                                    <input type="checkbox" class="form-check-input" wire:model.live="selected" value="{{ $item->id }}" id="select_{{ $item->id }}">
                                </td>
                                <td>
                                    <span class="fw-medium">{{ date('d M Y', strtotime($item->date)) }}</span>
                                </td>
                                <td>
                                    <span class="text-secondary">{{ $item->account_name }}</span>
                                </td>
                                <td>
                                    <span class="text-secondary">{{ $item->description ?? ($item->journal_remarks ?? '-') }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ $item->reference_number ?? '-' }}</span>
                                </td>
                                <td class="text-end">
                                    @if ($item->debit > 0)
                                        <strong class="text-danger">{{ currency($item->debit) }}</strong>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if ($item->credit > 0)
                                        <strong class="text-success">{{ currency($item->credit) }}</strong>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($item->delivered_date)
                                        <span class="badge bg-success">
                                            <i class="fa fa-check me-1"></i>
                                            {{ date('d M Y', strtotime($item->delivered_date)) }}
                                        </span>
                                    @else
                                        <span class="badge bg-warning text-dark">
                                            <i class="fa fa-clock me-1"></i>
                                            Pending
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-1 align-items-center">
                                        <input type="date"
                                            class="form-control form-control-sm {{ isset($rowDates[$item->id]) && !empty($rowDates[$item->id]) && $rowDates[$item->id] != ($item->delivered_date ?? '') ? 'border-warning' : '' }}"
                                            wire:model.lazy="rowDates.{{ $item->id }}" value="{{ $rowDates[$item->id] ?? ($item->delivered_date ?? '') }}" style="min-width: 140px;"
                                            placeholder="Select date">
                                        @if (isset($rowDates[$item->id]) && !empty($rowDates[$item->id]) && $rowDates[$item->id] != ($item->delivered_date ?? ''))
                                            <button class="btn btn-sm btn-primary" wire:click="updateRowDate({{ $item->id }})" title="Update this row">
                                                <i class="fa fa-save"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-secondary" wire:click="clearRowDate({{ $item->id }})" title="Clear date">
                                                <i class="fa fa-times"></i>
                                            </button>
                                        @elseif(isset($rowDates[$item->id]) && empty($rowDates[$item->id]))
                                            <button class="btn btn-sm btn-outline-secondary" wire:click="clearRowDate({{ $item->id }})" title="Clear">
                                                <i class="fa fa-times"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#updateDateModal{{ $item->id }}"
                                            title="Update Delivered Date">
                                            <i class="fa fa-edit"></i>
                                        </button>
                                    </div>

                                    <!-- Modal for updating single item -->
                                    <div class="modal fade" id="updateDateModal{{ $item->id }}" tabindex="-1" aria-hidden="true" wire:ignore.self>
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content shadow-lg border-0">
                                                <div class="modal-header text-white border-0 pb-3" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                                    <div class="d-flex align-items-center">
                                                        <div class="bg-white bg-opacity-20 rounded-circle p-2 me-3">
                                                            <i class="fa fa-calendar fa-lg"></i>
                                                        </div>
                                                        <div>
                                                            <h5 class="modal-title mb-0 fw-bold">Update Delivered Date</h5>
                                                            <small class="opacity-75">Set the reconciliation date for this transaction</small>
                                                        </div>
                                                    </div>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body p-4">
                                                    <!-- Transaction Details Card -->
                                                    <div class="card border-0 bg-light mb-4">
                                                        <div class="card-body p-3">
                                                            <h6 class="card-title text-muted mb-3 small text-uppercase fw-bold">
                                                                <i class="fa fa-info-circle me-2"></i>Transaction Details
                                                            </h6>
                                                            <div class="row g-3">
                                                                <div class="col-md-4">
                                                                    <div class="d-flex align-items-center">
                                                                        <div class="bg-primary bg-opacity-10 rounded p-2 me-2">
                                                                            <i class="fa fa-calendar text-primary"></i>
                                                                        </div>
                                                                        <div>
                                                                            <small class="text-muted d-block">Transaction Date</small>
                                                                            <strong class="text-dark">{{ date('d M Y', strtotime($item->date)) }}</strong>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <div class="d-flex align-items-center">
                                                                        <div class="bg-info bg-opacity-10 rounded p-2 me-2">
                                                                            <i class="fa fa-university text-info"></i>
                                                                        </div>
                                                                        <div>
                                                                            <small class="text-muted d-block">Account</small>
                                                                            <strong class="text-dark">{{ $item->account_name }}</strong>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <div class="d-flex align-items-center">
                                                                        <div class="rounded p-2 me-2 {{ $item->debit > 0 ? 'bg-danger bg-opacity-10' : 'bg-success bg-opacity-10' }}">
                                                                            <i class="fa {{ $item->debit > 0 ? 'fa-arrow-down text-danger' : 'fa-arrow-up text-success' }}"></i>
                                                                        </div>
                                                                        <div>
                                                                            <small class="text-muted d-block">Amount</small>
                                                                            <strong class="{{ $item->debit > 0 ? 'text-danger' : 'text-success' }}">
                                                                                {{ currency($item->debit > 0 ? $item->debit : $item->credit) }}
                                                                            </strong>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            @if ($item->reference_number)
                                                                <div class="mt-3 pt-3 border-top">
                                                                    <small class="text-muted">
                                                                        <i class="fa fa-hashtag me-1"></i>
                                                                        <strong>Reference:</strong>
                                                                        <span class="badge bg-secondary">{{ $item->reference_number }}</span>
                                                                    </small>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    <!-- Date Input Section -->
                                                    <div class="mb-3">
                                                        <label for="deliveredDate{{ $item->id }}" class="form-label fw-semibold mb-2">
                                                            <i class="fa fa-calendar-alt me-2 text-primary"></i>
                                                            Delivered Date
                                                        </label>
                                                        <div class="input-group input-group-lg">
                                                            <span class="input-group-text bg-primary text-white border-primary">
                                                                <i class="fa fa-calendar-check"></i>
                                                            </span>
                                                            <input type="date" class="form-control form-control-lg border-primary" id="deliveredDate{{ $item->id }}"
                                                                value="{{ $item->delivered_date ?? '' }}" style="font-size: 1rem;">
                                                        </div>
                                                        @if ($item->delivered_date)
                                                            <div class="mt-2">
                                                                <small class="text-muted">
                                                                    <i class="fa fa-info-circle me-1"></i>
                                                                    Current: <span class="badge bg-success">{{ date('d M Y', strtotime($item->delivered_date)) }}</span>
                                                                </small>
                                                            </div>
                                                        @else
                                                            <div class="mt-2">
                                                                <small class="text-warning">
                                                                    <i class="fa fa-exclamation-triangle me-1"></i>
                                                                    No delivered date set yet
                                                                </small>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="modal-footer bg-light border-0 pt-3 pb-4 px-4">
                                                    <button type="button" class="btn btn-outline-secondary btn-lg px-4" data-bs-dismiss="modal">
                                                        <i class="fa fa-times me-2"></i>
                                                        Cancel
                                                    </button>
                                                    <button type="button" class="btn btn-primary btn-lg px-4 shadow-sm" onclick="updateDate({{ $item->id }})" data-bs-dismiss="modal">
                                                        <i class="fa fa-save me-2"></i>
                                                        Update Date
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fa fa-inbox fs-1 d-block mb-3 opacity-50"></i>
                                        <h6 class="mb-2">No Transactions Found</h6>
                                        <p class="mb-0 small">Try adjusting your filters</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($items->hasPages())
                <div class="card-footer bg-light py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Showing {{ $items->firstItem() ?? 0 }} to {{ $items->lastItem() ?? 0 }} of {{ $items->total() }} entries
                        </div>
                        <div>
                            {{ $items->links() }}
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @push('styles')
        <style>
            .modal-content {
                border-radius: 15px;
                overflow: hidden;
            }

            .modal-header[style*="gradient"] {
                position: relative;
            }

            .modal-header[style*="gradient"]::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
                opacity: 0.1;
            }

            .input-group-text {
                transition: all 0.3s ease;
            }

            .input-group:focus-within .input-group-text {
                transform: scale(1.05);
                box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            }

            .form-control-lg:focus {
                border-color: #667eea;
                box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            }

            .card.bg-light {
                transition: all 0.3s ease;
            }

            .card.bg-light:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            }

            .btn-lg {
                transition: all 0.3s ease;
                font-weight: 600;
            }

            .btn-lg:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            }

            /* Sortable column styles */
            th[style*="cursor: pointer"] {
                user-select: none;
                transition: background-color 0.2s ease;
            }

            th[style*="cursor: pointer"]:hover {
                background-color: rgba(0, 0, 0, 0.05) !important;
            }

            th[style*="cursor: pointer"] .fa-sort,
            th[style*="cursor: pointer"] .fa-sort-up,
            th[style*="cursor: pointer"] .fa-sort-down {
                transition: all 0.2s ease;
            }

            th[style*="cursor: pointer"]:hover .fa-sort {
                opacity: 1 !important;
                color: #667eea !important;
            }

            /* Summary card gradients */
            .bg-gradient-primary {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            }

            .bg-gradient-danger {
                background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            }

            .bg-gradient-success {
                background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            }

            .bg-gradient-warning {
                background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            function updateDate(id) {
                const dateInput = document.getElementById('deliveredDate' + id);
                const date = dateInput.value;
                if (date) {
                    @this.updateSingleDeliveredDate(id, date);
                } else {
                    alert('Please select a delivered date.');
                }
            }

            $(document).ready(function() {
                // Reinitialize modals after Livewire updates
                Livewire.hook('message.processed', (message, component) => {
                    // Reinitialize Bootstrap modals if needed
                    $('.modal').each(function() {
                        if (!$(this).data('bs.modal')) {
                            new bootstrap.Modal(this);
                        }
                    });
                });
            });
        </script>
    @endpush
</div>

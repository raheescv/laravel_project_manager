<div>
    @use('App\Enums\Grn\GrnStatus')

    @if ($errors->any())
        <div class="mb-4 alert alert-danger alert-dismissible fade show">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Status Banner --}}
    @php
        $statusColor = match ($grn->status) {
            GrnStatus::ACCEPTED => 'success',
            GrnStatus::REJECTED => 'danger',
            default => 'warning',
        };
        $statusIcon = match ($grn->status) {
            GrnStatus::ACCEPTED => 'fa fa-check-circle',
            GrnStatus::REJECTED => 'fa fa-times-circle',
            default => 'fa fa-clock-o',
        };
    @endphp

    <div class="mb-4 card border-0 shadow-sm overflow-hidden">
        <div class="card-body p-0">
            <div class="d-flex align-items-center p-3 bg-{{ $statusColor }} bg-opacity-10 border-start border-4 border-{{ $statusColor }}">
                <div class="icon-box bg-{{ $statusColor }} bg-opacity-25 rounded-circle p-2 me-3">
                    <i class="{{ $statusIcon }} text-{{ $statusColor }} fs-4"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="mb-0 fw-bold">GRN {{ $grn->grn_no }}</h5>
                            <small class="text-muted">{{ $grn->branch?->name }}</small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            @if ($grn->status === GrnStatus::PENDING)
                                @can('grn.create')
                                    <a href="{{ route('grn::edit', $grn->id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fa fa-pencil me-1"></i> Edit
                                    </a>
                                @endcan
                                @can('grn.decide')
                                    <a href="{{ route('grn::decision', $grn->id) }}" class="btn btn-sm btn-outline-warning">
                                        <i class="fa fa-gavel me-1"></i> Accept / Reject
                                    </a>
                                @endcan
                            @endif
                            <span class="badge bg-{{ $statusColor }} rounded-pill px-3 py-2 fs-6">
                                <i class="{{ $statusIcon }} me-1"></i>
                                {{ $grn->status->label() }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- GRN Details --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-3">
                        <div class="icon-box bg-primary bg-opacity-10 rounded-circle p-2 me-2">
                            <i class="demo-psi-file text-primary fs-4"></i>
                        </div>
                        <h5 class="mb-0 fw-bold">GRN Information</h5>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="p-2 rounded bg-light bg-opacity-50">
                                <div class="d-flex align-items-center mb-1">
                                    <i class="demo-psi-tag text-primary me-2"></i>
                                    <small class="text-muted">GRN No</small>
                                </div>
                                <div class="fw-medium">{{ $grn->grn_no }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded bg-light bg-opacity-50">
                                <div class="d-flex align-items-center mb-1">
                                    <i class="demo-psi-calendar-4 text-primary me-2"></i>
                                    <small class="text-muted">Date</small>
                                </div>
                                <div class="fw-medium">{{ systemDate($grn->date) }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded bg-light bg-opacity-50">
                                <div class="d-flex align-items-center mb-1">
                                    <i class="demo-psi-shopping-cart text-primary me-2"></i>
                                    <small class="text-muted">LPO</small>
                                </div>
                                <div class="fw-medium">
                                    @if ($grn->localPurchaseOrder)
                                        <a href="{{ route('lpo::view', $grn->localPurchaseOrder->id) }}" class="text-primary text-decoration-none">
                                            LPO #{{ $grn->localPurchaseOrder->id }}
                                            <i class="fa fa-external-link-alt small ms-1"></i>
                                        </a>
                                    @else
                                        -
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded bg-light bg-opacity-50">
                                <div class="d-flex align-items-center mb-1">
                                    <i class="fa fa-building text-primary me-2"></i>
                                    <small class="text-muted">Vendor</small>
                                </div>
                                <div class="fw-medium">{{ $grn->vendor?->name ?? $grn->localPurchaseOrder?->vendor?->name ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded bg-light bg-opacity-50">
                                <div class="d-flex align-items-center mb-1">
                                    <i class="demo-psi-male text-primary me-2"></i>
                                    <small class="text-muted">Created By</small>
                                </div>
                                <div class="fw-medium">{{ $grn->creator?->name ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded bg-light bg-opacity-50">
                                <div class="d-flex align-items-center mb-1">
                                    <i class="demo-psi-home text-info me-2"></i>
                                    <small class="text-muted">Branch</small>
                                </div>
                                <div class="fw-medium">{{ $grn->branch?->name ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded bg-light bg-opacity-50">
                                <div class="d-flex align-items-center mb-1">
                                    <i class="demo-psi-basket-coins text-success me-2"></i>
                                    <small class="text-muted">Total Items</small>
                                </div>
                                <div class="fw-medium">{{ $grn->items->count() }} items</div>
                            </div>
                        </div>
                        @if ($grn->remarks)
                            <div class="col-12">
                                <div class="p-2 rounded bg-light bg-opacity-50">
                                    <div class="d-flex align-items-center mb-1">
                                        <i class="demo-psi-speech-bubble-3 text-primary me-2"></i>
                                        <small class="text-muted">Remarks</small>
                                    </div>
                                    <div class="fw-medium">{{ $grn->remarks }}</div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @if ($grn->status !== GrnStatus::PENDING)
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center mb-3">
                            <div class="icon-box bg-{{ $statusColor }} bg-opacity-10 rounded-circle p-2 me-2">
                                <i class="{{ $statusIcon }} text-{{ $statusColor }} fs-4"></i>
                            </div>
                            <h5 class="mb-0 fw-bold">Decision Details</h5>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="p-2 rounded bg-light bg-opacity-50">
                                    <div class="d-flex align-items-center mb-1">
                                        <i class="demo-psi-male text-{{ $statusColor }} me-2"></i>
                                        <small class="text-muted">Action By</small>
                                    </div>
                                    <div class="fw-medium">{{ $grn->decisionMaker?->name ?? '-' }}</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 rounded bg-light bg-opacity-50">
                                    <div class="d-flex align-items-center mb-1">
                                        <i class="demo-psi-calendar-4 text-{{ $statusColor }} me-2"></i>
                                        <small class="text-muted">Action On</small>
                                    </div>
                                    <div class="fw-medium">{{ $grn->decision_at?->format('d M Y, h:i A') }}</div>
                                </div>
                            </div>
                            @if ($grn->decision_note)
                                <div class="col-12">
                                    <div class="p-2 rounded bg-light bg-opacity-50">
                                        <div class="d-flex align-items-center mb-1">
                                            <i class="demo-psi-speech-bubble-3 text-{{ $statusColor }} me-2"></i>
                                            <small class="text-muted">Remarks</small>
                                        </div>
                                        <div class="fw-medium">{{ $grn->decision_note }}</div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Items Table --}}
    <div class="mb-4 card border-0 shadow-sm">
        <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center">
                    <div class="icon-box bg-info bg-opacity-10 rounded-circle p-2 me-2">
                        <i class="demo-psi-basket-coins text-info fs-4"></i>
                    </div>
                    <h5 class="mb-0 fw-bold">Received Items</h5>
                </div>
                <span class="badge bg-info bg-opacity-10 text-info rounded-pill px-3 py-2">
                    {{ $grn->items->count() }} items
                </span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr class="bg-light">
                            <th class="border-0 rounded-start">#</th>
                            <th class="border-0">Product</th>
                            <th class="border-0">Code</th>
                            <th class="border-0">Category</th>
                            <th class="border-0">Sub Category</th>
                            <th class="border-0">Brand</th>
                            <th class="border-0">Unit</th>
                            <th class="border-0 text-end rounded-end">Received Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($grn->items as $index => $item)
                            <tr>
                                <td>
                                    <span class="badge bg-light text-muted rounded-pill">{{ $index + 1 }}</span>
                                </td>
                                <td>
                                    <span class="fw-medium">{{ $item->product->name }}</span>
                                </td>
                                <td>
                                    <span class="text-muted small">{{ $item->product->code ?? '-' }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-primary bg-opacity-10 text-primary">{{ $item->product->mainCategory?->name ?? '-' }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary">{{ $item->product->subCategory?->name ?? '-' }}</span>
                                </td>
                                <td>
                                    <span class="text-muted">{{ $item->product->brand?->name ?? '-' }}</span>
                                </td>
                                <td>
                                    <span class="text-muted">{{ $item->product->unit?->name ?? '-' }}</span>
                                </td>
                                <td class="text-end">
                                    <span class="fw-bold">{{ $item->quantity }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="demo-psi-basket-coins fs-1 d-block mb-2 opacity-25"></i>
                                        No items received
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($grn->items->count())
                        <tfoot>
                            <tr class="bg-light">
                                <td colspan="7" class="fw-bold border-0 rounded-start">Total</td>
                                <td class="fw-bold text-end border-0 rounded-end">{{ $grn->items->sum('quantity') }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    {{-- Journal Entries --}}
    @if ($grn->journals->count())
        @php
            $vendorId = $grn->vendor_id ?? $grn->localPurchaseOrder?->vendor_id;
        @endphp
        <div class="mb-4 card border-0 shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center">
                        <div class="icon-box rounded-circle p-2 me-2" style="background-color: rgba(111, 66, 193, 0.1);">
                            <i class="fa fa-book fs-4" style="color: #6f42c1;"></i>
                        </div>
                        <h5 class="mb-0 fw-bold">Journal Entries</h5>
                    </div>
                    <span class="badge rounded-pill px-3 py-2" style="background-color: rgba(111, 66, 193, 0.1); color: #6f42c1;">
                        {{ $grn->journals->count() }} {{ Str::plural('journal', $grn->journals->count()) }}
                    </span>
                </div>

                @foreach ($grn->journals as $journal)
                    <div class="mb-3 {{ !$loop->last ? 'border-bottom pb-3' : '' }}">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div>
                                <span class="fw-semibold text-dark">{{ $journal->description }}</span>
                                @if ($journal->reference_number)
                                    <span class="badge bg-light text-muted ms-2">Ref: {{ $journal->reference_number }}</span>
                                @endif
                            </div>
                            <small class="text-muted">
                                <i class="fa fa-calendar me-1"></i>
                                {{ $journal->date ? \Carbon\Carbon::parse($journal->date)->format('d M Y') : '-' }}
                            </small>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead>
                                    <tr class="bg-light">
                                        <th class="border-0 rounded-start small text-muted fw-semibold">Account</th>
                                        <th class="border-0 small text-muted fw-semibold">Remarks</th>
                                        <th class="border-0 text-end small text-muted fw-semibold" style="width: 130px;">Debit</th>
                                        <th class="border-0 text-end rounded-end small text-muted fw-semibold" style="width: 130px;">Credit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($journal->entries->where('account_id', '!=', $vendorId) as $entry)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="fa fa-{{ $entry->debit > 0 ? 'arrow-up text-success' : 'arrow-down text-danger' }} me-2 small"></i>
                                                    <span class="fw-medium">{{ $entry->account?->name ?? '-' }}</span>
                                                </div>
                                            </td>
                                            <td class="text-muted small">{{ $entry->remarks ?? '-' }}</td>
                                            <td class="text-end">
                                                @if ($entry->debit > 0)
                                                    <span class="fw-medium text-success">{{ number_format($entry->debit, 2) }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                @if ($entry->credit > 0)
                                                    <span class="fw-medium text-danger">{{ number_format($entry->credit, 2) }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    @php
                                        $filteredEntries = $journal->entries->where('account_id', '!=', $vendorId);
                                    @endphp
                                    <tr class="bg-light border-top">
                                        <td colspan="2" class="border-0 fw-bold text-end">Total</td>
                                        <td class="border-0 text-end fw-bold text-success">
                                            {{ number_format($filteredEntries->sum('debit'), 2) }}
                                        </td>
                                        <td class="border-0 text-end fw-bold text-danger">
                                            {{ number_format($filteredEntries->sum('credit'), 2) }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Approval Action --}}
    @if ($grn->status == GrnStatus::PENDING && $is_approvable)
        <div class="mb-4 card border-0 shadow-sm border-top border-3 border-warning">
            <div class="card-body p-4">
                <div class="d-flex align-items-center mb-3">
                    <div class="icon-box bg-warning bg-opacity-10 rounded-circle p-2 me-2">
                        <i class="fa fa-gavel text-warning fs-4"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 fw-bold">Take Action</h5>
                        <small class="text-muted">Accept or reject this goods received note</small>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        <i class="demo-psi-speech-bubble-3 me-1"></i> Remarks
                    </label>
                    <textarea class="form-control" rows="3" wire:model="remarks" placeholder="Enter remarks (required for rejection)"></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-danger px-4" wire:click="reject" wire:confirm="Are you sure you want to reject this GRN?">
                        <i class="fa fa-times me-1"></i> Reject
                    </button>
                    <button type="button" class="btn btn-success px-4" wire:click="accept" wire:confirm="Are you sure you want to accept this GRN?">
                        <i class="fa fa-check me-1"></i> Accept
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

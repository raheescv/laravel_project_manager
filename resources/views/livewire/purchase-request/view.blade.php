<div>
    @use('App\Enums\PurchaseRequest\PurchaseRequestStatus')

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
        $statusColor = match ($purchase_request->status) {
            PurchaseRequestStatus::APPROVED => 'success',
            PurchaseRequestStatus::REJECTED => 'danger',
            default => 'warning',
        };
        $statusIcon = match ($purchase_request->status) {
            PurchaseRequestStatus::APPROVED => 'fa fa-check-circle',
            PurchaseRequestStatus::REJECTED => 'fa fa-times-circle',
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
                            <h5 class="mb-0 fw-bold">Purchase Request #{{ $purchase_request->id }}</h5>
                            <small class="text-muted">{{ $purchase_request->branch?->name }}</small>
                        </div>
                        <span class="badge bg-{{ $statusColor }} rounded-pill px-3 py-2 fs-6">
                            <i class="{{ $statusIcon }} me-1"></i>
                            {{ $purchase_request->status->label() }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Request Details --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-3">
                        <div class="icon-box bg-primary bg-opacity-10 rounded-circle p-2 me-2">
                            <i class="demo-psi-file text-primary fs-4"></i>
                        </div>
                        <h5 class="mb-0 fw-bold">Request Information</h5>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="p-2 rounded bg-light bg-opacity-50">
                                <div class="d-flex align-items-center mb-1">
                                    <i class="demo-psi-male text-primary me-2"></i>
                                    <small class="text-muted">Requested By</small>
                                </div>
                                <div class="fw-medium">{{ $purchase_request->creator?->name ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded bg-light bg-opacity-50">
                                <div class="d-flex align-items-center mb-1">
                                    <i class="demo-psi-calendar-4 text-primary me-2"></i>
                                    <small class="text-muted">Requested On</small>
                                </div>
                                <div class="fw-medium">{{ $purchase_request->created_at?->format('d M Y, h:i A') }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded bg-light bg-opacity-50">
                                <div class="d-flex align-items-center mb-1">
                                    <i class="demo-psi-home text-info me-2"></i>
                                    <small class="text-muted">Branch</small>
                                </div>
                                <div class="fw-medium">{{ $purchase_request->branch?->name ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded bg-light bg-opacity-50">
                                <div class="d-flex align-items-center mb-1">
                                    <i class="demo-psi-basket-coins text-success me-2"></i>
                                    <small class="text-muted">Total Products</small>
                                </div>
                                <div class="fw-medium">{{ $purchase_request->products->count() }} items</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if ($purchase_request->status !== PurchaseRequestStatus::PENDING)
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
                                    <div class="fw-medium">{{ $purchase_request->decisionMaker?->name ?? '-' }}</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 rounded bg-light bg-opacity-50">
                                    <div class="d-flex align-items-center mb-1">
                                        <i class="demo-psi-calendar-4 text-{{ $statusColor }} me-2"></i>
                                        <small class="text-muted">Action On</small>
                                    </div>
                                    <div class="fw-medium">{{ $purchase_request->decision_at?->format('d M Y, h:i A') }}</div>
                                </div>
                            </div>
                            @if ($purchase_request->decision_note)
                                <div class="col-12">
                                    <div class="p-2 rounded bg-light bg-opacity-50">
                                        <div class="d-flex align-items-center mb-1">
                                            <i class="demo-psi-speech-bubble-3 text-{{ $statusColor }} me-2"></i>
                                            <small class="text-muted">Remarks</small>
                                        </div>
                                        <div class="fw-medium">{{ $purchase_request->decision_note }}</div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Products Table --}}
    <div class="mb-4 card border-0 shadow-sm">
        <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center">
                    <div class="icon-box bg-info bg-opacity-10 rounded-circle p-2 me-2">
                        <i class="demo-psi-basket-items text-info fs-4"></i>
                    </div>
                    <h5 class="mb-0 fw-bold">Products</h5>
                </div>
                <span class="badge bg-info bg-opacity-10 text-info rounded-pill px-3 py-2">
                    {{ $purchase_request->products->count() }} items | Total Qty: {{ $purchase_request->products->sum('quantity') }}
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
                            <th class="border-0 rounded-end text-end">Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($purchase_request->products as $index => $item)
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
                                        <i class="demo-psi-basket-items fs-1 d-block mb-2 opacity-25"></i>
                                        No products added
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($purchase_request->products->count())
                        <tfoot>
                            <tr class="bg-light">
                                <td colspan="7" class="fw-bold border-0 rounded-start">Total Quantity</td>
                                <td class="fw-bold text-end border-0 rounded-end">{{ $purchase_request->products->sum('quantity') }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    {{-- Approval Action --}}
    @if ($purchase_request->status == PurchaseRequestStatus::PENDING && $is_approvable)
        <div class="mb-4 card border-0 shadow-sm border-top border-3 border-warning">
            <div class="card-body p-4">
                <div class="d-flex align-items-center mb-3">
                    <div class="icon-box bg-warning bg-opacity-10 rounded-circle p-2 me-2">
                        <i class="fa fa-gavel text-warning fs-4"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 fw-bold">Take Action</h5>
                        <small class="text-muted">Approve or reject this purchase request</small>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        <i class="demo-psi-speech-bubble-3 me-1"></i> Remarks
                    </label>
                    <textarea class="form-control" rows="3" wire:model="remarks" placeholder="Enter remarks (required for rejection)"></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-danger px-4" wire:click="reject" wire:confirm="Are you sure you want to reject this request?">
                        <i class="fa fa-times me-1"></i> Reject
                    </button>
                    <button type="button" class="btn btn-success px-4" wire:click="approve" wire:confirm="Are you sure you want to approve this request?">
                        <i class="fa fa-check me-1"></i> Approve
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

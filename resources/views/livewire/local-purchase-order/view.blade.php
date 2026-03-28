<div>
    @use('App\Enums\LocalPurchaseOrder\LocalPurchaseOrderStatus')

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
        $statusColor = match ($order->status) {
            LocalPurchaseOrderStatus::APPROVED => 'success',
            LocalPurchaseOrderStatus::REJECTED => 'danger',
            default => 'warning',
        };
        $statusIcon = match ($order->status) {
            LocalPurchaseOrderStatus::APPROVED => 'fa fa-check-circle',
            LocalPurchaseOrderStatus::REJECTED => 'fa fa-times-circle',
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
                            <h5 class="mb-0 fw-bold">Local Purchase Order #{{ $order->id }}</h5>
                            <small class="text-muted">{{ $order->branch?->name }}</small>
                        </div>
                        <span class="badge bg-{{ $statusColor }} rounded-pill px-3 py-2 fs-6">
                            <i class="{{ $statusIcon }} me-1"></i>
                            {{ $order->status->label() }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Order Details --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-3">
                        <div class="icon-box bg-primary bg-opacity-10 rounded-circle p-2 me-2">
                            <i class="demo-psi-file text-primary fs-4"></i>
                        </div>
                        <h5 class="mb-0 fw-bold">Order Information</h5>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="p-2 rounded bg-light bg-opacity-50">
                                <div class="d-flex align-items-center mb-1">
                                    <i class="demo-psi-shop text-primary me-2"></i>
                                    <small class="text-muted">Vendor</small>
                                </div>
                                <div class="fw-medium">{{ $order->vendor?->name ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded bg-light bg-opacity-50">
                                <div class="d-flex align-items-center mb-1">
                                    <i class="demo-psi-calendar-4 text-primary me-2"></i>
                                    <small class="text-muted">Date</small>
                                </div>
                                <div class="fw-medium">{{ $order->date ? \Carbon\Carbon::parse($order->date)->format('d M Y') : '-' }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded bg-light bg-opacity-50">
                                <div class="d-flex align-items-center mb-1">
                                    <i class="demo-psi-male text-primary me-2"></i>
                                    <small class="text-muted">Created By</small>
                                </div>
                                <div class="fw-medium">{{ $order->creator?->name ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded bg-light bg-opacity-50">
                                <div class="d-flex align-items-center mb-1">
                                    <i class="demo-psi-home text-info me-2"></i>
                                    <small class="text-muted">Branch</small>
                                </div>
                                <div class="fw-medium">{{ $order->branch?->name ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded bg-light bg-opacity-50">
                                <div class="d-flex align-items-center mb-1">
                                    <i class="demo-psi-basket-coins text-success me-2"></i>
                                    <small class="text-muted">Total Products</small>
                                </div>
                                <div class="fw-medium">{{ $order->items->count() }} items</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded bg-light bg-opacity-50">
                                <div class="d-flex align-items-center mb-1">
                                    <i class="demo-psi-coin text-success me-2"></i>
                                    <small class="text-muted">Total Amount</small>
                                </div>
                                <div class="fw-medium">{{ number_format($order->items->sum(fn($i) => $i->quantity * $i->rate), 2) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if ($order->status !== LocalPurchaseOrderStatus::PENDING)
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
                                    <div class="fw-medium">{{ $order->decisionMaker?->name ?? '-' }}</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 rounded bg-light bg-opacity-50">
                                    <div class="d-flex align-items-center mb-1">
                                        <i class="demo-psi-calendar-4 text-{{ $statusColor }} me-2"></i>
                                        <small class="text-muted">Action On</small>
                                    </div>
                                    <div class="fw-medium">{{ $order->decision_at?->format('d M Y, h:i A') }}</div>
                                </div>
                            </div>
                            @if ($order->decision_note)
                                <div class="col-12">
                                    <div class="p-2 rounded bg-light bg-opacity-50">
                                        <div class="d-flex align-items-center mb-1">
                                            <i class="demo-psi-speech-bubble-3 text-{{ $statusColor }} me-2"></i>
                                            <small class="text-muted">Remarks</small>
                                        </div>
                                        <div class="fw-medium">{{ $order->decision_note }}</div>
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
                        <i class="demo-psi-basket-coins text-info fs-4"></i>
                    </div>
                    <h5 class="mb-0 fw-bold">Products</h5>
                </div>
                <span class="badge bg-info bg-opacity-10 text-info rounded-pill px-3 py-2">
                    {{ $order->items->count() }} items | Total: {{ number_format($order->items->sum(fn($i) => $i->quantity * $i->rate), 2) }}
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
                            <th class="border-0 text-end">Qty</th>
                            <th class="border-0 text-end">Rate</th>
                            <th class="border-0 text-end rounded-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($order->items as $index => $item)
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
                                    <span
                                        class="badge bg-primary bg-opacity-10 text-primary">{{ $item->product->mainCategory?->name ?? '-' }}</span>
                                </td>
                                <td>
                                    <span
                                        class="badge bg-secondary bg-opacity-10 text-secondary">{{ $item->product->subCategory?->name ?? '-' }}</span>
                                </td>
                                <td>
                                    <span class="text-muted">{{ $item->product->brand?->name ?? '-' }}</span>
                                </td>
                                <td>
                                    <span class="text-muted">{{ $item->product->unit?->name ?? '-' }}</span>
                                </td>
                                <td class="text-end">
                                    <span class="fw-medium">{{ $item->quantity }}</span>
                                </td>
                                <td class="text-end">
                                    <span class="text-muted">{{ number_format($item->rate, 2) }}</span>
                                </td>
                                <td class="text-end">
                                    <span class="fw-bold">{{ number_format($item->quantity * $item->rate, 2) }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="demo-psi-basket-coins fs-1 d-block mb-2 opacity-25"></i>
                                        No products added
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($order->items->count())
                        <tfoot>
                            <tr class="bg-light">
                                <td colspan="7" class="fw-bold border-0 rounded-start">Total</td>
                                <td class="fw-bold text-end border-0">{{ $order->items->sum('quantity') }}</td>
                                <td class="border-0"></td>
                                <td class="fw-bold text-end border-0 rounded-end">
                                    {{ number_format($order->items->sum(fn($i) => $i->quantity * $i->rate), 2) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    {{-- Fulfillment Summary --}}
    @if ($order->grns->count() && $order->items->count())
        @php
            $allGrnItems = $order->grns->flatMap->items;
            $receivedByProduct = $allGrnItems->groupBy('product_id')->map(fn($items) => $items->sum('quantity'));
            $totalOrdered = $order->items->sum('quantity');
            $totalReceived = $receivedByProduct->sum();
            $overallPercent = $totalOrdered > 0 ? round(($totalReceived / $totalOrdered) * 100) : 0;
        @endphp

        <div class="mb-4 card border-0 shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center">
                        <div class="icon-box bg-primary bg-opacity-10 rounded-circle p-2 me-2">
                            <i class="fa fa-tasks text-primary fs-4"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 fw-bold">Fulfillment Summary</h5>
                            <small class="text-muted">Ordered vs Received across all GRNs</small>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span
                            class="badge bg-{{ $overallPercent >= 100 ? 'success' : ($overallPercent > 0 ? 'warning' : 'secondary') }} rounded-pill px-3 py-2">
                            {{ $overallPercent }}% Fulfilled
                        </span>
                    </div>
                </div>

                {{-- Overall Progress --}}
                <div class="mb-3">
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-{{ $overallPercent >= 100 ? 'success' : ($overallPercent >= 50 ? 'info' : 'warning') }}"
                            style="width: {{ min($overallPercent, 100) }}%"></div>
                    </div>
                    <div class="d-flex justify-content-between mt-1">
                        <small class="text-muted">Total Ordered: <strong>{{ $totalOrdered }}</strong></small>
                        <small class="text-muted">Total Received: <strong class="text-success">{{ $totalReceived }}</strong></small>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr class="bg-light">
                                <th class="border-0 rounded-start">#</th>
                                <th class="border-0">Product</th>
                                <th class="border-0 text-end">Ordered</th>
                                <th class="border-0 text-end">Received</th>
                                <th class="border-0 text-end">Pending</th>
                                <th class="border-0" style="width: 200px;">Progress</th>
                                <th class="border-0 text-center rounded-end">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order->items as $index => $item)
                                @php
                                    $received = $receivedByProduct->get($item->product_id, 0);
                                    $pending = $item->quantity - $received;
                                    $percent = $item->quantity > 0 ? round(($received / $item->quantity) * 100) : 0;
                                @endphp
                                <tr>
                                    <td>
                                        <span class="badge bg-light text-muted rounded-pill">{{ $index + 1 }}</span>
                                    </td>
                                    <td>
                                        <span class="fw-medium">{{ $item->product->name }}</span>
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-medium">{{ $item->quantity }}</span>
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-bold text-success">{{ $received }}</span>
                                    </td>
                                    <td class="text-end">
                                        @if ($pending != 0)
                                            <span class="fw-medium text-danger">{{ $pending }}</span>
                                        @else
                                            <span class="text-muted">0</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height: 6px;">
                                                <div class="progress-bar bg-{{ $percent >= 100 ? 'success' : ($percent > 0 ? 'warning' : 'secondary') }}"
                                                    style="width: {{ min($percent, 100) }}%"></div>
                                            </div>
                                            <small class="text-muted fw-semibold" style="width: 35px;">{{ $percent }}%</small>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @if ($percent >= 100)
                                            <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2 py-1">
                                                <i class="fa fa-check-circle me-1"></i> Full
                                            </span>
                                        @elseif ($percent > 0)
                                            <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill px-2 py-1">
                                                <i class="fa fa-clock-o me-1"></i> Partial
                                            </span>
                                        @else
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-2 py-1">
                                                <i class="fa fa-minus-circle me-1"></i> None
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bg-light">
                                <td colspan="2" class="fw-bold border-0 rounded-start">Total</td>
                                <td class="fw-bold text-end border-0">{{ $totalOrdered }}</td>
                                <td class="fw-bold text-end border-0 text-success">{{ $totalReceived }}</td>
                                <td class="fw-bold text-end border-0 {{ $totalOrdered - $totalReceived > 0 ? 'text-danger' : '' }}">
                                    {{ $totalOrdered - $totalReceived }}</td>
                                <td class="border-0"></td>
                                <td class="border-0 rounded-end"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- GRN Received Items --}}
    @if ($order->grns->count())
        <div class="mb-4 card border-0 shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center">
                        <div class="icon-box bg-success bg-opacity-10 rounded-circle p-2 me-2">
                            <i class="fa fa-cubes text-success fs-4"></i>
                        </div>
                        <h5 class="mb-0 fw-bold">Goods Received (GRN)</h5>
                    </div>
                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-2">
                        {{ $order->grns->count() }} GRN{{ $order->grns->count() > 1 ? 's' : '' }}
                    </span>
                </div>

                @foreach ($order->grns as $grn)
                    <div class="border rounded mb-3">
                        <div class="bg-light px-3 py-2 d-flex align-items-center justify-content-between border-bottom">
                            <div class="d-flex align-items-center">
                                <a href="{{ route('grn::view', $grn->id) }}" class="fw-semibold text-primary text-decoration-none">
                                    <i class="fa fa-file-text me-1"></i> {{ $grn->grn_no }}
                                </a>
                                <span class="text-muted ms-3 small">
                                    <i class="demo-psi-calendar-4 me-1"></i>
                                    {{ \Carbon\Carbon::parse($grn->date)->format('d M Y') }}
                                </span>
                            </div>
                            <span
                                class="badge bg-{{ $grn->status->value === 'accepted' ? 'success' : ($grn->status->value === 'pending' ? 'warning' : 'danger') }} rounded-pill">
                                {{ $grn->status->label() }}
                            </span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr class="bg-light bg-opacity-50">
                                        <th class="border-0 ps-3">#</th>
                                        <th class="border-0">Product</th>
                                        <th class="border-0 text-end pe-3">Received Qty</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($grn->items as $grnIndex => $grnItem)
                                        <tr>
                                            <td class="ps-3">
                                                <span class="badge bg-light text-muted rounded-pill">{{ $grnIndex + 1 }}</span>
                                            </td>
                                            <td>
                                                <span class="fw-medium">{{ $grnItem->product?->name }}</span>
                                            </td>
                                            <td class="text-end pe-3">
                                                <span class="fw-bold text-success">{{ $grnItem->quantity }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Approval Action --}}
    @if ($order->status == LocalPurchaseOrderStatus::PENDING && $is_approvable)
        <div class="mb-4 card border-0 shadow-sm border-top border-3 border-warning">
            <div class="card-body p-4">
                <div class="d-flex align-items-center mb-3">
                    <div class="icon-box bg-warning bg-opacity-10 rounded-circle p-2 me-2">
                        <i class="fa fa-gavel text-warning fs-4"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 fw-bold">Take Action</h5>
                        <small class="text-muted">Approve or reject this local purchase order</small>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        <i class="demo-psi-speech-bubble-3 me-1"></i> Remarks
                    </label>
                    <textarea class="form-control" rows="3" wire:model="remarks" placeholder="Enter remarks (required for rejection)"></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-danger px-4" wire:click="reject"
                        wire:confirm="Are you sure you want to reject this order?">
                        <i class="fa fa-times me-1"></i> Reject
                    </button>
                    <button type="button" class="btn btn-success px-4" wire:click="approve"
                        wire:confirm="Are you sure you want to approve this order?">
                        <i class="fa fa-check me-1"></i> Approve
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

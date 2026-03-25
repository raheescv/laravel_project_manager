<div>
    @if ($errors->any())
        <div class="mb-4 alert alert-danger">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-4 border-0 shadow-sm card">
        <div class="bg-white card-header">
            <h5 class="mb-0 fw-bold">Order Details</h5>
        </div>

        <div class="card-body">
            <div class="row g-3">

                <div class="col-md-4">
                    <label class="small fw-semibold">Vendor</label>
                    <input type="text" class="form-control" value="{{ $order->vendor->name ?? '-' }}" readonly>
                </div>

                <div class="col-md-4">
                    <label class="small fw-semibold">Created By</label>
                    <input type="text" class="form-control" value="{{ $order->creator->name ?? '-' }}" readonly>
                </div>

                <div class="col-md-4">
                    <label class="small fw-semibold">Created At</label>
                    <input type="text" class="form-control" value="{{ $order->created_at?->format('d M Y, h:i A') }}"
                        readonly>
                </div>

            </div>
        </div>
    </div>

    <div class="mb-4 border-0 shadow-sm card">
        <div class="bg-white card-header">
            <h5 class="mb-0 fw-bold">Products</h5>
        </div>

        <div class="card-body">

            <div class="table-responsive">
                <table class="table align-middle table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">#</th>
                            <th width="45%">Product</th>
                            <th width="15%">Qty</th>
                            <th width="15%">Rate</th>
                            <th width="20%">Amount</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($order->items as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $item->product->name }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td>{{ number_format($item->rate, 2) }}</td>
                                <td>{{ number_format($item->quantity * $item->rate, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">
                                    No products added
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3 text-end">
                <strong>Total Amount:</strong>
                {{ number_format($order->items->sum(fn($i) => $i->quantity * $i->rate), 2) }}
            </div>

        </div>
    </div>

    @if ($order->purchaseRequests && $order->purchaseRequests->count())
        <div class="mb-4 border-0 shadow-sm card">
            <div class="bg-white card-header">
                <h5 class="mb-0 fw-bold">Linked Purchase Requests</h5>
            </div>

            <div class="card-body">

                <ul class="list-group">
                    @foreach ($order->purchaseRequests as $pr)
                        <li class="list-group-item d-flex justify-content-between">
                            <span>PR #{{ $pr->id }}</span>
                            <span class="text-muted">
                                {{ $pr->products->count() }} products
                            </span>
                        </li>
                    @endforeach
                </ul>

            </div>
        </div>
    @endif

    @if ($order->status !== \App\Enums\LocalPurchaseOrder\LocalPurchaseOrderStatus::PENDING)
        <div class="mb-4 border-0 shadow-sm card">
            <div class="bg-white card-header">
                <h5 class="mb-0 fw-bold">Approval Details</h5>
            </div>

            <div class="card-body">
                <div class="row g-3">

                    <div class="col-md-4">
                        <label class="small fw-semibold">Action By</label>
                        <input type="text" class="form-control" value="{{ $order->decisionMaker->name ?? '-' }}"
                            readonly>
                    </div>

                    <div class="col-md-4">
                        <label class="small fw-semibold">Action At</label>
                        <input type="text" class="form-control"
                            value="{{ $order->decision_at->format('d M Y, h:i A') }}" readonly>
                    </div>

                    <div class="col-md-12">
                        <label class="small fw-semibold">Remarks</label>
                        <textarea class="form-control" rows="3" readonly>
{{ $order->decision_note ?? '—' }}
                        </textarea>
                    </div>

                </div>
            </div>
        </div>
    @endif

    @if ($order->status == \App\Enums\LocalPurchaseOrder\LocalPurchaseOrderStatus::PENDING && $is_approvable)
        <div class="mb-4 border-0 shadow-sm card">
            <div class="bg-white card-header">
                <h5 class="mb-0 fw-bold">Approval Action</h5>
            </div>

            <div class="card-body">

                <div class="mb-3">
                    <label class="form-label fw-semibold">Remarks</label>
                    <textarea class="form-control" rows="3" wire:model="remarks" placeholder="Enter remarks (required for rejection)"></textarea>
                </div>

                <div class="gap-2 d-flex justify-content-end">

                    <button type="button" class="btn btn-danger" wire:click="reject">
                        <i class="fa fa-times"></i> Reject
                    </button>

                    <button type="button" class="btn btn-success" wire:click="approve">
                        <i class="fa fa-check"></i> Approve
                    </button>

                </div>

            </div>
        </div>
    @endif
</div>

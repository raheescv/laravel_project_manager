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
            <h5 class="mb-0 fw-bold">Request Details</h5>
        </div>

        <div class="card-body">
            <div class="row g-3">

                <div class="col-md-4">
                    <label class="small fw-semibold">Requested By</label>
                    <input type="text" class="form-control" value="{{ $purchase_request->creator->name ?? '-' }}"
                        readonly>
                </div>

                <div class="col-md-4">
                    <label class="small fw-semibold">Status</label>
                    <input type="text" class="form-control" value="{{ $purchase_request->status->label() }}"
                        readonly>
                </div>

                <div class="col-md-4">
                    <label class="small fw-semibold">Created At</label>
                    <input type="text" class="form-control"
                        value="{{ $purchase_request->created_at?->format('d M Y, h:i A') }}" readonly>
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
                            <th width="65%">Product</th>
                            <th width="30%">Quantity</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($purchase_request->products as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $item->product->name }}</td>
                                <td>{{ $item->quantity }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">
                                    No products added
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3 text-end">
                <strong>Total Qty:</strong>
                {{ $purchase_request->products->sum('quantity') }}
            </div>

        </div>
    </div>

    @if ($purchase_request->status !== \App\Enums\PurchaseRequest\PurchaseRequestStatus::PENDING)
        <div class="mb-4 border-0 shadow-sm card">
            <div class="bg-white card-header">
                <h5 class="mb-0 fw-bold">Approval Details</h5>
            </div>

            <div class="card-body">
                <div class="row g-3">

                    <div class="col-md-4">
                        <label class="small fw-semibold">Action By</label>
                        <input type="text" class="form-control"
                            value="{{ $purchase_request->decisionMaker->name ?? '-' }}" readonly>
                    </div>

                    <div class="col-md-4">
                        <label class="small fw-semibold">Action At</label>
                        <input type="text" class="form-control"
                            value="{{ $purchase_request->decision_at->format('d M Y, h:i A') }}" readonly>
                    </div>

                    <div class="col-md-12">
                        <label class="small fw-semibold">Remarks</label>
                        <textarea class="form-control" rows="3" readonly>
{{ $purchase_request->decision_note ?? '—' }}
                        </textarea>
                    </div>

                </div>
            </div>
        </div>
    @endif

    @if ($purchase_request->status == \App\Enums\PurchaseRequest\PurchaseRequestStatus::PENDING && $is_approvable)
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

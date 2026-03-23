<div>
    <form wire:submit="submitAction">

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
                <h5 class="mb-0 fw-bold">Purchase Request Details</h5>
            </div>

            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <label class="small fw-semibold">Requested By</label>
                        <input type="text" class="form-control" value="{{ $purchase_request->creator->name ?? '-' }}"
                            readonly>
                    </div>

                    <div class="col-md-6">
                        <label class="small fw-semibold">Status</label>
                        <input type="text" class="form-control" value="{{ $purchase_request->status->label() }}"
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
                        <thead>
                            <tr>
                                <th width="60%">Product</th>
                                <th width="20%">Quantity</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($purchase_request->products as $item)
                                <tr>
                                    <td>{{ $item->product->name }}</td>
                                    <td>{{ $item->quantity }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

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

    </form>
</div>

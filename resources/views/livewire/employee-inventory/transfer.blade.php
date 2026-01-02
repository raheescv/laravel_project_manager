<div>
    <div class="modal-header">
        <h1 class="modal-title fs-5">
            <i class="demo-pli-user me-2 text-primary"></i>
            Transfer Stock to Employee
        </h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <form wire:submit.prevent="transfer">
        <div class="modal-body">
            @if ($this->getErrorBag()->count())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($this->getErrorBag()->toArray() as $key => $errors)
                            @foreach ($errors as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($inventory)
                <div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <div class="bg-primary bg-opacity-10 rounded-circle p-2 d-flex align-items-center justify-content-center">
                                <i class="demo-pli-information text-primary fs-5"></i>
                            </div>
                            <h6 class="mb-0 fw-semibold text-dark">Product Information</h6>
                        </div>
                        <div class="row g-2">
                            <div class="col-12">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="demo-pli-file-edit text-muted mt-1"></i>
                                    <div class="flex-grow-1">
                                        <small class="text-muted d-block mb-0">Product Name</small>
                                        <div class="fw-semibold text-dark">{{ $inventory->product->name ?? 'N/A' }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="demo-pli-box-with-folders text-success mt-1"></i>
                                    <div class="flex-grow-1">
                                        <small class="text-muted d-block mb-0">Available</small>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">{{ $inventory->quantity }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="demo-pli-tag text-info mt-1"></i>
                                    <div class="flex-grow-1">
                                        <small class="text-muted d-block mb-0">Batch</small>
                                        <div class="fw-medium text-dark small">{{ $inventory->batch }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="demo-pli-barcode text-secondary mt-1"></i>
                                    <div class="flex-grow-1">
                                        <small class="text-muted d-block mb-0">Barcode</small>
                                        <code class="bg-white px-2 py-1 rounded border text-dark small">{{ $inventory->barcode }}</code>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="demo-pli-building text-info mt-1"></i>
                                    <div class="flex-grow-1">
                                        <small class="text-muted d-block mb-0">Branch</small>
                                        <div class="fw-medium text-dark small">{{ $inventory->branch?->name }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="form-check form-switch py-2 mb-2">
                <input class="form-check-input" type="checkbox" id="return_to_main_branch" wire:model.live="return_to_main_branch">
                <label class="form-check-label" for="return_to_main_branch"> &emsp; Return to {{ $inventory?->branch?->name }} Branch </label>
            </div>

            <div class="row" @if($return_to_main_branch) style="display: none;" @endif>
                <div class="col-md-12 mb-3" wire:ignore >
                    <label for="employee_id" class="form-label fw-semibold">
                        <i class="demo-pli-user me-1 text-primary"></i>
                        Select Employee <span class="text-danger">*</span>
                    </label>
                    {{ html()->select('employee_id', [])->value('')->class('select-employee_id-list')->id('transfer_employee_id')->attribute('wire:model', 'employee_id')->placeholder('Search and select employee...') }}
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 mb-3">
                    <label for="quantity" class="form-label fw-semibold">
                        <i class="demo-pli-box-with-folders me-1 text-success"></i>
                        Quantity <span class="text-danger">*</span>
                    </label>
                    <input type="number" step="0.001" min="0.001" wire:model.live="quantity" class="form-control" id="quantity" placeholder="Enter quantity to transfer" required>
                    @if ($inventory && $quantity)
                        <div class="form-text">
                            Available: <strong>{{ $inventory->quantity }}</strong> |
                            Remaining after transfer: <strong>{{ $inventory->quantity - ($quantity ?? 0) }}</strong>
                        </div>
                    @endif
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 mb-3">
                    <label for="reason" class="form-label fw-semibold">
                        <i class="demo-pli-file-edit me-1 text-warning"></i>
                        Reason for Transfer <span class="text-danger">*</span>
                    </label>
                    <textarea wire:model="reason" class="form-control" id="reason" rows="3" placeholder="Enter reason for transferring stock to employee (e.g., Office supplies, Personal use, etc.)" required></textarea>
                    <div class="form-text">This reason will be logged for audit purposes.</div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                <i class="demo-pli-cross me-1"></i>
                Cancel
            </button>
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                <span wire:loading.remove>
                    <i class="demo-pli-check me-1"></i>
                    Transfer Stock
                </span>
                <span wire:loading>
                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                    Transferring...
                </span>
            </button>
        </div>
    </form>
</div>

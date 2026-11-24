<div>
    {{-- Header --}}
    <div class="modal-header bg-warning text-dark border-0 py-2 px-3">
        <h6 class="modal-title fw-bold mb-0">
            <i class="fa fa-money me-2"></i> Pay Commission
        </h6>
        <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
    </div>

    {{-- Body --}}
    <div class="modal-body p-3">
        <div class="alert alert-light border small py-2 mb-3 d-flex justify-content-between">
            <span><i class="fa fa-user me-1 text-success"></i> Commission payable to {{ $agentName }}</span>
            <span class="fw-bold">{{ number_format($amount, 2) }}</span>
        </div>
        <div class="row g-3">
            <div class="col-6">
                <label class="form-label fw-semibold small mb-1">
                    <i class="fa fa-calendar me-1 text-muted"></i> Date <span class="text-danger">*</span>
                </label>
                <input type="date" class="form-control form-control-sm" wire:model="form.paid_date">
                @error('form.paid_date') <small class="text-danger">{{ $message }}</small> @enderror
            </div>
            <div class="col-6">
                <label class="form-label fw-semibold small mb-1">
                    <i class="fa fa-credit-card me-1 text-muted"></i> Payment Method <span class="text-danger">*</span>
                </label>
                <select class="form-select form-select-sm" wire:model="form.payment_account_id">
                    <option value="">Select Payment Method</option>
                    @foreach ($paymentMethods as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('form.payment_account_id') <small class="text-danger">{{ $message }}</small> @enderror
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="modal-footer py-2 px-3 border-top">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">
            <i class="fa fa-times me-1"></i> Close
        </button>
        <button type="button" class="btn btn-sm btn-warning" wire:click="save" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="save"><i class="fa fa-money me-1"></i> Pay</span>
            <span wire:loading wire:target="save"><i class="fa fa-spinner fa-spin me-1"></i> Processing...</span>
        </button>
    </div>
</div>

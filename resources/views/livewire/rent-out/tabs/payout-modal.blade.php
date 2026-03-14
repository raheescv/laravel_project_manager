<div>
    {{-- Header --}}
    <div class="modal-header bg-warning text-dark border-0 py-2 px-3">
        <h6 class="modal-title fw-bold mb-0">
            <i class="fa {{ $editingId ? 'fa-pencil' : 'fa-share' }} me-2"></i> {{ $editingId ? 'Edit Payment' : 'Payout' }}
        </h6>
        <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
    </div>

    {{-- Body --}}
    <div class="modal-body p-3">
        <div class="row g-3">
            <div class="col-4">
                <label class="form-label fw-semibold small mb-1">
                    <i class="fa fa-calendar me-1 text-muted"></i> Date <span class="text-danger">*</span>
                </label>
                <input type="date" class="form-control form-control-sm" wire:model="form.date">
                @error('form.date') <small class="text-danger">{{ $message }}</small> @enderror
            </div>
            <div class="col-4">
                <label class="form-label fw-semibold small mb-1">
                    <i class="fa fa-credit-card me-1 text-muted"></i> Payment Method <span class="text-danger">*</span>
                </label>
                <select class="form-select form-select-sm select-payment_method_id-list" wire:model="form.account_id">
                    <option value="">Select Payment Method</option>
                    @foreach ($paymentMethods as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('form.account_id') <small class="text-danger">{{ $message }}</small> @enderror
            </div>
            <div class="col-4">
                <label class="form-label fw-semibold small mb-1">
                    <i class="fa fa-money me-1 text-muted"></i> Amount <span class="text-danger">*</span>
                </label>
                <input type="number" class="form-control form-control-sm" wire:model="form.amount" step="0.01">
                @error('form.amount') <small class="text-danger">{{ $message }}</small> @enderror
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold small mb-1">
                    <i class="fa fa-comment-o me-1 text-muted"></i> Remark
                </label>
                <textarea class="form-control form-control-sm" wire:model="form.remark" rows="2"
                    placeholder="Enter any remarks about this payout..."></textarea>
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="modal-footer py-2 px-3 border-top">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">
            <i class="fa fa-times me-1"></i> Close
        </button>
        <button type="button" class="btn btn-sm btn-{{ $editingId ? 'primary' : 'warning' }}" wire:click="save" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="save"><i class="fa {{ $editingId ? 'fa-save' : 'fa-share' }} me-1"></i> {{ $editingId ? 'Update Payment' : 'Process Payout' }}</span>
            <span wire:loading wire:target="save"><i class="fa fa-spinner fa-spin me-1"></i> {{ $editingId ? 'Updating...' : 'Processing...' }}</span>
        </button>
    </div>
</div>

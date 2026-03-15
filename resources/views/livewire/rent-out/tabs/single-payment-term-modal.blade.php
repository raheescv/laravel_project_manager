<div>
    {{-- Header --}}
    <div class="modal-header py-2 px-3 text-white border-0"
        style="background: linear-gradient(135deg, #3a9e7a, #2e7d56);">
        <h6 class="modal-title fw-bold mb-0">
            <i class="fa fa-calendar-plus-o me-2"></i>
            {{ $editingTermId ? 'Edit Payment Term' : 'Add Single Payment Term' }}
        </h6>
        <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
    </div>

    {{-- Body --}}
    <div class="modal-body p-3">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold small mb-1"><i class="fa fa-calendar me-1 text-muted"></i> Date <span
                        class="text-danger">*</span></label>
                <input type="date" class="form-control form-control-sm"
                    wire:model="form.due_date">
                @error('form.due_date')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold small mb-1"><i class="fa fa-tag me-1 text-muted"></i> Label</label>
                <select class="form-select form-select-sm" wire:model="form.label">
                    <option value="">Select</option>
                    @foreach ($labelOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold small mb-1"><i class="fa fa-money me-1 text-muted"></i> Amount <span
                        class="text-danger">*</span></label>
                <input type="number" class="form-control form-control-sm" wire:model="form.amount"
                    step="0.01">
                @error('form.amount')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold small mb-1"><i class="fa fa-percent me-1 text-muted"></i> Discount</label>
                <input type="number" class="form-control form-control-sm" wire:model="form.discount"
                    step="0.01">
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold small mb-1"><i class="fa fa-comment-o me-1 text-muted"></i> Remark</label>
                <textarea class="form-control form-control-sm" wire:model="form.remarks" rows="2"
                    placeholder="Optional remark..."></textarea>
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="modal-footer py-2 px-3 border-top">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">
            <i class="fa fa-times me-1"></i> Close
        </button>
        <button type="button" class="btn btn-sm btn-success" wire:click="save"
            wire:loading.attr="disabled">
            <i class="fa fa-check me-1"></i>
            <span wire:loading.remove wire:target="save">Save</span>
            <span wire:loading wire:target="save">Saving...</span>
        </button>
    </div>
</div>

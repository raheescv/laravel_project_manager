<div>
    {{-- Header --}}
    <div class="modal-header bg-success text-white border-0 py-2 px-3">
        <h6 class="modal-title fw-bold mb-0 text-white">
            <i class="fa fa-percent me-2"></i> {{ $commissionId ? 'Edit' : 'Add' }} Commission
        </h6>
        <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
    </div>

    {{-- Body --}}
    <div class="modal-body p-3">
        <div class="row g-3">
            <div class="col-6">
                <label class="form-label fw-semibold small mb-1">
                    <i class="fa fa-user me-1 text-muted"></i> Salesman
                </label>
                <div class="form-control form-control-sm bg-light">{{ $salesmanName }}</div>
            </div>
            <div class="col-6">
                <label class="form-label fw-semibold small mb-1">
                    <i class="fa fa-calendar me-1 text-muted"></i> Date <span class="text-danger">*</span>
                </label>
                <input type="date" class="form-control form-control-sm" wire:model="form.date">
                @error('form.date') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            <div class="col-6">
                <label class="form-label fw-semibold small mb-1">
                    <i class="fa fa-tag me-1 text-muted"></i> Commission Type <span class="text-danger">*</span>
                </label>
                <div class="btn-group btn-group-sm w-100" role="group">
                    <input type="radio" class="btn-check" id="commissionTypePercentage" value="percentage" wire:model.live="form.commission_type">
                    <label class="btn btn-outline-primary" for="commissionTypePercentage">Percentage</label>
                    <input type="radio" class="btn-check" id="commissionTypeFixed" value="fixed" wire:model.live="form.commission_type">
                    <label class="btn btn-outline-primary" for="commissionTypeFixed">Fixed Amount</label>
                </div>
            </div>
            <div class="col-6">
                <label class="form-label fw-semibold small mb-1">
                    <i class="fa fa-money me-1 text-muted"></i> Contract Amount <span class="text-danger">*</span>
                </label>
                <input type="number" class="form-control form-control-sm" wire:model.live.debounce.400ms="form.contract_amount" step="0.01">
                @error('form.contract_amount') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            @if (($form['commission_type'] ?? 'percentage') === 'percentage')
                <div class="col-6">
                    <label class="form-label fw-semibold small mb-1">
                        <i class="fa fa-percent me-1 text-muted"></i> Commission % <span class="text-danger">*</span>
                    </label>
                    <input type="number" class="form-control form-control-sm" wire:model.live.debounce.400ms="form.commission_percentage" step="0.01" placeholder="e.g. 5">
                    @error('form.commission_percentage') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
            @else
                <div class="col-6">
                    <label class="form-label fw-semibold small mb-1">
                        <i class="fa fa-money me-1 text-muted"></i> Commission Amount <span class="text-danger">*</span>
                    </label>
                    <input type="number" class="form-control form-control-sm" wire:model.live.debounce.400ms="form.amount" step="0.01">
                    @error('form.amount') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
            @endif
            <div class="col-6">
                <label class="form-label fw-semibold small mb-1">
                    <i class="fa fa-calculator me-1 text-muted"></i> Commission
                </label>
                <div class="form-control form-control-sm bg-light fw-bold text-end">{{ number_format($this->calculatedAmount, 2) }}</div>
            </div>

            <div class="col-12">
                <label class="form-label fw-semibold small mb-1">
                    <i class="fa fa-comment-o me-1 text-muted"></i> Remark
                </label>
                <textarea class="form-control form-control-sm" wire:model="form.remark" rows="2" placeholder="Enter any remarks..."></textarea>
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="modal-footer py-2 px-3 border-top">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">
            <i class="fa fa-times me-1"></i> Close
        </button>
        <button type="button" class="btn btn-sm btn-success" wire:click="save" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="save"><i class="fa fa-save me-1"></i> Save</span>
            <span wire:loading wire:target="save"><i class="fa fa-spinner fa-spin me-1"></i> Saving...</span>
        </button>
    </div>
</div>

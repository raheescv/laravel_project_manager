<div>
    @if ($showModal)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    {{-- Header --}}
                    <div class="modal-header py-2 px-3 text-white border-0"
                        style="background: linear-gradient(135deg, #3a9e7a, #2e7d56);">
                        <h6 class="modal-title fw-bold mb-0 text-white">
                            <i class="fa fa-check-square-o me-2"></i>
                            {{ $editingId ? 'Edit Cheque' : 'Add Cheque' }}
                        </h6>
                        <button type="button" class="btn-close btn-close-white btn-sm" wire:click="close"></button>
                    </div>

                    {{-- Body --}}
                    <div class="modal-body p-3">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small mb-1"><i class="fa fa-file-text-o me-1 text-muted"></i> Cheque No <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" wire:model="form.cheque_no">
                                @error('form.cheque_no')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small mb-1"><i class="fa fa-university me-1 text-muted"></i> Bank Name</label>
                                <input type="text" class="form-control form-control-sm" wire:model="form.bank_name">
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
                                <label class="form-label fw-semibold small mb-1"><i class="fa fa-calendar me-1 text-muted"></i> Date</label>
                                <input type="date" class="form-control form-control-sm" wire:model="form.date">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-semibold small mb-1"><i class="fa fa-user me-1 text-muted"></i> Payee Name</label>
                                <input type="text" class="form-control form-control-sm" wire:model="form.payee_name">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold small mb-1"><i class="fa fa-comment-o me-1 text-muted"></i> Remarks</label>
                                <textarea class="form-control form-control-sm" wire:model="form.remarks" rows="2"
                                    placeholder="Optional remarks..."></textarea>
                            </div>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="modal-footer py-2 px-3 border-top">
                        <button type="button" class="btn btn-sm btn-secondary" wire:click="close">
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
            </div>
        </div>
    @endif
</div>

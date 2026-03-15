<div>
    {{-- Header --}}
    <div class="modal-header py-2 px-3 text-white border-0"
        style="background: linear-gradient(135deg, #3a9e7a, #2e7d56);">
        <h6 class="modal-title fw-bold mb-0">
            <i class="fa fa-shield me-2"></i>
            {{ $editingId ? 'Edit Security Deposit' : 'Add Security Deposit' }}
        </h6>
        <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
    </div>

    {{-- Body --}}
    <div class="modal-body p-3">
        <div class="row g-3">
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
                <label class="form-label fw-semibold small mb-1"><i class="fa fa-credit-card me-1 text-muted"></i> Payment Mode <span
                        class="text-danger">*</span></label>
                <select class="form-select form-select-sm" wire:model.live="form.payment_mode">
                    <option value="">Select</option>
                    @foreach ($paymentModes as $mode)
                        <option value="{{ $mode->value }}">{{ $mode->label() }}</option>
                    @endforeach
                </select>
                @error('form.payment_mode')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            @if ($form['payment_mode'] === 'cheque')
                <div class="col-md-6">
                    <label class="form-label fw-semibold small mb-1"><i class="fa fa-university me-1 text-muted"></i> Bank Name <span
                            class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm"
                        wire:model="form.bank_name" placeholder="Bank name">
                    @error('form.bank_name')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small mb-1"><i class="fa fa-file-text-o me-1 text-muted"></i> Cheque No <span
                            class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm"
                        wire:model="form.cheque_no" placeholder="Cheque No">
                    @error('form.cheque_no')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>
            @endif
            <div class="col-md-6">
                <label class="form-label fw-semibold small mb-1"><i class="fa fa-tag me-1 text-muted"></i> Type <span
                        class="text-danger">*</span></label>
                <select class="form-select form-select-sm" wire:model="form.type">
                    <option value="">Select</option>
                    @foreach ($securityTypes as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                    @endforeach
                </select>
                @error('form.type')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold small mb-1"><i class="fa fa-info-circle me-1 text-muted"></i> Status <span
                        class="text-danger">*</span></label>
                <select class="form-select form-select-sm" wire:model="form.status">
                    <option value="">Select</option>
                    @foreach ($securityStatuses as $status)
                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
                    @endforeach
                </select>
                @error('form.status')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold small mb-1"><i class="fa fa-calendar me-1 text-muted"></i> Due Date <span
                        class="text-danger">*</span></label>
                <input type="date" class="form-control form-control-sm"
                    wire:model="form.due_date">
                @error('form.due_date')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
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

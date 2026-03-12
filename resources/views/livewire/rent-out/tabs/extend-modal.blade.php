<div>
    @if ($showModal)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    {{-- Header --}}
                    <div class="modal-header py-2 px-3 text-white border-0"
                        style="background: linear-gradient(135deg, #3a9e7a, #2e7d56);">
                        <h6 class="modal-title fw-bold mb-0">
                            <i class="fa fa-calendar-plus-o me-2"></i>
                            Extend Agreement
                        </h6>
                        <button type="button" class="btn-close btn-close-white btn-sm" wire:click="close"></button>
                    </div>

                    {{-- Body --}}
                    <div class="modal-body p-3">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small mb-1"><i class="fa fa-calendar-o me-1 text-muted"></i> Start Date <span
                                        class="text-danger">*</span></label>
                                <input type="date" class="form-control form-control-sm"
                                    wire:model="form.start_date">
                                @error('form.start_date')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small mb-1"><i class="fa fa-calendar me-1 text-muted"></i> End Date <span
                                        class="text-danger">*</span></label>
                                <input type="date" class="form-control form-control-sm"
                                    wire:model="form.end_date">
                                @error('form.end_date')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small mb-1"><i class="fa fa-money me-1 text-muted"></i> Rent Amount <span
                                        class="text-danger">*</span></label>
                                <input type="number" class="form-control form-control-sm" wire:model="form.rent_amount"
                                    step="0.01">
                                @error('form.rent_amount')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small mb-1"><i class="fa fa-credit-card me-1 text-muted"></i> Payment Mode <span
                                        class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" wire:model="form.payment_mode">
                                    <option value="">Select</option>
                                    @foreach (\App\Enums\RentOut\PaymentMode::cases() as $mode)
                                        <option value="{{ $mode->value }}">{{ $mode->label() }}</option>
                                    @endforeach
                                </select>
                                @error('form.payment_mode')
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

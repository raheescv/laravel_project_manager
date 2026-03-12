<div>
    @if ($showModal)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    {{-- Header --}}
                    <div class="modal-header py-2 px-3 text-white border-0"
                        style="background: linear-gradient(135deg, #3a9e7a, #2e7d56);">
                        <h6 class="modal-title fw-bold mb-0">
                            <i class="fa fa-cogs me-2"></i>
                            {{ $editingId ? 'Edit Service' : 'Add Service' }}
                        </h6>
                        <button type="button" class="btn-close btn-close-white btn-sm" wire:click="close"></button>
                    </div>

                    {{-- Body --}}
                    <div class="modal-body p-3">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold small mb-1"><i class="fa fa-cog me-1 text-muted"></i> Service Name <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm"
                                    wire:model="form.name" placeholder="Enter service name">
                                @error('form.name')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold small mb-1"><i class="fa fa-money me-1 text-muted"></i> Amount <span
                                        class="text-danger">*</span></label>
                                <input type="number" class="form-control form-control-sm" wire:model="form.amount"
                                    step="0.01">
                                @error('form.amount')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold small mb-1"><i class="fa fa-align-left me-1 text-muted"></i> Description</label>
                                <textarea class="form-control form-control-sm" wire:model="form.description" rows="2"
                                    placeholder="Optional description..."></textarea>
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

<div>
    {{-- Header --}}
    <div class="modal-header bg-success text-white border-0 py-2 px-3">
        <h6 class="modal-title fw-bold mb-0 text-white">
            <i class="fa fa-line-chart me-2"></i> ROI (Return on Investment)
        </h6>
        <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
    </div>

    {{-- Body --}}
    <div class="modal-body p-3">
        <div class="row g-3">
            {{-- Row 1: Date, Start Date, End Date --}}
            <div class="col-4">
                <label class="form-label fw-semibold small mb-1">
                    <i class="fa fa-calendar me-1 text-muted"></i> Date <span class="text-danger">*</span>
                </label>
                <input type="date" class="form-control form-control-sm" wire:model="date">
                @error('date')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="col-4">
                <label class="form-label fw-semibold small mb-1">
                    <i class="fa fa-calendar-plus-o me-1 text-muted"></i> Start Date <span class="text-danger">*</span>
                </label>
                <input type="date" class="form-control form-control-sm" wire:model.live="startDate">
                @error('startDate')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="col-4">
                <label class="form-label fw-semibold small mb-1">
                    <i class="fa fa-calendar-check-o me-1 text-muted"></i> End Date <span class="text-danger">*</span>
                </label>
                <input type="date" class="form-control form-control-sm" wire:model.live="endDate">
                @error('endDate')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            {{-- Row 2: Months, Sale Price, Per Month ROI, Amount --}}
            <div class="col-6 col-md-3">
                <label class="form-label fw-semibold small mb-1">
                    <i class="fa fa-hashtag me-1 text-muted"></i> No Of Months
                </label>
                <input type="text" class="form-control form-control-sm bg-light" value="{{ $noOfMonths }}" readonly>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label fw-semibold small mb-1">
                    <i class="fa fa-tag me-1 text-muted"></i> Sale Price
                </label>
                <input type="text" class="form-control form-control-sm bg-light" value="{{ number_format($salePrice, 2) }}" readonly>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label fw-semibold small mb-1">
                    <i class="fa fa-line-chart me-1 text-muted"></i> Per Month ROI <span class="text-danger">*</span>
                </label>
                <input type="number" class="form-control form-control-sm" wire:model.live.debounce.300ms="perMonthRoi" step="0.01">
                @error('perMonthRoi')
                    <small class="text-danger">{{ $message }}</small>
                @else
                    @if ($this->yield > 0)
                        <small class="text-muted">&asymp; {{ number_format($this->yield, 2) }}% per year of sale price</small>
                    @endif
                @enderror
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label fw-semibold small mb-1">
                    <i class="fa fa-calculator me-1 text-muted"></i> Amount <span class="text-danger">*</span>
                </label>
                <input type="text" class="form-control form-control-sm bg-light fw-bold" value="{{ number_format($amount, 2) }}" readonly>
                @error('amount')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            {{-- Row 3: Remark --}}
            <div class="col-12">
                <label class="form-label fw-semibold small mb-1">
                    <i class="fa fa-comment-o me-1 text-muted"></i> Remark
                </label>
                <textarea class="form-control form-control-sm" wire:model="remark" rows="2" placeholder="Optional remark..."></textarea>
            </div>

            {{-- Row 4: Description --}}
            <div class="col-12">
                <label class="form-label fw-semibold small mb-1">
                    <i class="fa fa-file-text-o me-1 text-muted"></i> Description
                </label>
                <input type="text" class="form-control form-control-sm bg-light" value="{{ $description }}" readonly>
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="modal-footer py-2 px-3 border-top">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">
            <i class="fa fa-times me-1"></i> Close
        </button>
        <button type="button" class="btn btn-sm btn-primary" wire:click="save" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="save"><i class="fa fa-check me-1"></i> Save</span>
            <span wire:loading wire:target="save"><i class="fa fa-spinner fa-spin me-1"></i> Saving...</span>
        </button>
    </div>
</div>

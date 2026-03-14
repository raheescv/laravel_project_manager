<div>
    {{-- Header --}}
    <div class="modal-header bg-info text-white border-0 py-2 px-3">
        <h6 class="modal-title fw-bold mb-0 text-white">
            <i class="fa fa-calculator me-2"></i> Service Charge
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

            {{-- Row 2: Days, Months --}}
            <div class="col-6">
                <label class="form-label fw-semibold small mb-1">
                    <i class="fa fa-hashtag me-1 text-muted"></i> No Of Days
                </label>
                <input type="text" class="form-control form-control-sm bg-light" value="{{ $noOfDays }}"
                    readonly>
            </div>
            <div class="col-6">
                <label class="form-label fw-semibold small mb-1">
                    <i class="fa fa-hashtag me-1 text-muted"></i> No Of Months
                </label>
                <input type="text" class="form-control form-control-sm bg-light" value="{{ $noOfMonths }}"
                    readonly>
            </div>

            {{-- Row 3: Unit Size, Per Sq Meter, Per Day Price, Amount --}}
            <div class="col-3">
                <label class="form-label fw-semibold small mb-1">
                    <i class="fa fa-arrows-alt me-1 text-muted"></i> Unit Size <span class="text-danger">*</span>
                </label>
                <input type="number" class="form-control form-control-sm bg-light" wire:model.live="unitSize"
                    step="0.01" readonly>
            </div>
            <div class="col-3">
                <label class="form-label fw-semibold small mb-1">
                    <i class="fa fa-th me-1 text-muted"></i> Per Sq Meter <span class="text-danger">*</span>
                </label>
                <input type="number" class="form-control form-control-sm" wire:model.live="perSqMeterPrice"
                    step="0.01">
                @error('perSqMeterPrice')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="col-3">
                <label class="form-label fw-semibold small mb-1">
                    <i class="fa fa-money me-1 text-muted"></i> Per Day Price
                </label>
                <input type="text" class="form-control form-control-sm bg-light"
                    value="{{ number_format($perDayPrice, 2) }}" readonly>
            </div>
            <div class="col-3">
                <label class="form-label fw-semibold small mb-1">
                    <i class="fa fa-calculator me-1 text-muted"></i> Amount <span class="text-danger">*</span>
                </label>
                <input type="text" class="form-control form-control-sm bg-light fw-bold"
                    value="{{ number_format($amount, 2) }}" readonly>
            </div>

            {{-- Row 4: Remark --}}
            <div class="col-12">
                <label class="form-label fw-semibold small mb-1">
                    <i class="fa fa-comment-o me-1 text-muted"></i> Remark
                </label>
                <textarea class="form-control form-control-sm" wire:model="remark" rows="2" placeholder="Optional remark..."></textarea>
            </div>

            {{-- Row 5: Description --}}
            <div class="col-12">
                <label class="form-label fw-semibold small mb-1">
                    <i class="fa fa-file-text-o me-1 text-muted"></i> Description
                </label>
                <input type="text" class="form-control form-control-sm bg-light" value="{{ $description }}"
                    readonly>
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

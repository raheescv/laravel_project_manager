<div>
    {{-- Header --}}
    <div class="modal-header py-2 px-3 text-white border-0"
        style="background: linear-gradient(135deg, #5b7fb5, #3f6096);">
        <h6 class="modal-title fw-bold mb-0">
            <i class="fa fa-copy me-2"></i> Add Multiple Cheques
        </h6>
        <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
    </div>

    {{-- Body --}}
    <div class="modal-body p-3">
        {{-- Form Fields --}}
        <div class="row g-2 mb-3">
            <div class="col-6 col-md-3">
                <label class="form-label fw-semibold small mb-1">Start Cheque No <span
                        class="text-danger">*</span></label>
                <input type="text"
                    class="form-control form-control-sm border-secondary-subtle shadow-sm"
                    wire:model.live.debounce.300ms="multiStartNo">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label fw-semibold small mb-1">Amount <span
                        class="text-danger">*</span></label>
                <input type="number"
                    class="form-control form-control-sm border-secondary-subtle shadow-sm"
                    wire:model.live.debounce.300ms="multiAmount" step="0.01">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label fw-semibold small mb-1">Start Date <span
                        class="text-danger">*</span></label>
                <input type="date"
                    class="form-control form-control-sm border-secondary-subtle shadow-sm"
                    wire:model.live.debounce.300ms="multiStartDate">
            </div>
            <div class="col-4 col-md-1">
                <label class="form-label fw-semibold small mb-1">Count</label>
                <input type="number"
                    class="form-control form-control-sm border-secondary-subtle shadow-sm"
                    wire:model.live.debounce.300ms="multiCount">
            </div>
            <div class="col-8 col-md-3">
                <label class="form-label fw-semibold small mb-1">Frequency</label>
                <select class="form-select form-select-sm border-secondary-subtle shadow-sm"
                    wire:model.live="multiFrequency">
                    <option value="Monthly">Monthly</option>
                    <option value="Quarterly">Quarterly</option>
                    <option value="Half Yearly">Half Yearly</option>
                    <option value="Yearly">Yearly</option>
                </select>
            </div>
        </div>

        {{-- Default Bank & Payee (applied to new rows) --}}
        <div class="row g-2 mb-3">
            <div class="col-6 col-md-4">
                <label class="form-label fw-semibold small mb-1">
                    <i class="fa fa-university me-1 text-muted"></i> Default Bank Name
                </label>
                <input type="text"
                    class="form-control form-control-sm border-secondary-subtle shadow-sm"
                    wire:model.live.debounce.300ms="multiBankName" placeholder="Applied to new rows...">
            </div>
            <div class="col-6 col-md-4">
                <label class="form-label fw-semibold small mb-1">
                    <i class="fa fa-user me-1 text-muted"></i> Default Payee Name
                </label>
                <input type="text"
                    class="form-control form-control-sm border-secondary-subtle shadow-sm"
                    wire:model.live.debounce.300ms="multiPayeeName" placeholder="Applied to new rows...">
            </div>
        </div>

        {{-- Editable Table --}}
        @if (count($previewList) > 0)
            <div class="table-responsive" style="max-height: 380px; overflow-y: auto;">
                <table class="table table-hover table-bordered table-sm mb-0 align-middle">
                    <thead class="bg-light text-muted sticky-top" style="z-index: 1;">
                        <tr class="text-capitalize small">
                            <th class="fw-semibold py-2 text-center" style="width: 40px;">#</th>
                            <th class="fw-semibold py-2">Cheque No</th>
                            <th class="fw-semibold py-2">Date</th>
                            <th class="fw-semibold py-2 text-end">Amount</th>
                            <th class="fw-semibold py-2">Bank Name</th>
                            <th class="fw-semibold py-2">Payee Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($previewList as $index => $item)
                            <tr>
                                <td class="text-center py-1 text-muted small">{{ $index + 1 }}
                                </td>
                                <td class="py-1">
                                    <input type="text"
                                        class="form-control form-control-sm border-secondary-subtle"
                                        wire:model="previewList.{{ $index }}.cheque_no">
                                </td>
                                <td class="py-1">
                                    <input type="date"
                                        class="form-control form-control-sm border-secondary-subtle"
                                        wire:model="previewList.{{ $index }}.date">
                                </td>
                                <td class="py-1">
                                    <input type="number"
                                        class="form-control form-control-sm text-end border-secondary-subtle"
                                        wire:model="previewList.{{ $index }}.amount"
                                        step="0.01">
                                </td>
                                <td class="py-1">
                                    <input type="text"
                                        class="form-control form-control-sm border-secondary-subtle"
                                        wire:model="previewList.{{ $index }}.bank_name"
                                        placeholder="Bank...">
                                </td>
                                <td class="py-1">
                                    <input type="text"
                                        class="form-control form-control-sm border-secondary-subtle"
                                        wire:model="previewList.{{ $index }}.payee_name"
                                        placeholder="Payee...">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light sticky-bottom">
                        <tr class="fw-bold small">
                            <td colspan="3" class="py-2 text-end">Total</td>
                            <td class="py-2 text-end text-primary">
                                {{ number_format($this->previewTotal, 2) }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @else
            <div class="text-center text-muted py-4">
                <i class="fa fa-money d-block fs-3 mb-2 opacity-50"></i>
                <span class="small">Fill in the fields above to generate cheques.</span>
            </div>
        @endif
    </div>

    {{-- Footer --}}
    <div class="modal-footer py-2 px-3 border-top bg-light">
        @if (count($previewList) > 0)
            <span class="me-auto badge bg-primary">{{ count($previewList) }} cheques</span>
        @endif
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">
            <i class="fa fa-times me-1"></i> Close
        </button>
        <button type="button" class="btn btn-sm btn-success shadow-sm" wire:click="save"
            wire:loading.attr="disabled" @if (count($previewList) === 0) disabled @endif>
            <i class="fa fa-check me-1"></i>
            <span wire:loading.remove wire:target="save">Save</span>
            <span wire:loading wire:target="save">Saving...</span>
        </button>
    </div>
</div>

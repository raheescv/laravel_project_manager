<div>
    {{-- Header --}}
    <div class="modal-header py-2 px-3 text-white border-0"
        style="background: linear-gradient(135deg, #5b7fb5, #3f6096);">
        <h6 class="modal-title fw-bold mb-0">
            <i class="fa fa-calendar me-2"></i> Add Multiple Payment Terms
        </h6>
        <button type="button" class="btn-close btn-close-white btn-sm"
            data-bs-dismiss="modal"></button>
    </div>

    {{-- Body --}}
    <div class="modal-body p-3">
        {{-- Agreement Info Summary --}}
        @if (!empty($info))
            <div class="card border mb-3" style="background: #f8f9fa;">
                <div class="card-body p-2">
                    <div class="row g-2 small">
                        <div class="col-6 col-md-3 d-flex justify-content-between">
                            <span class="fw-semibold text-muted">Price:</span>
                            <span
                                class="fw-bold text-primary">{{ number_format($info['rent'] ?? 0, 2) }}</span>
                        </div>
                        <div class="col-6 col-md-2 d-flex justify-content-between">
                            <span class="fw-semibold text-muted">Terms:</span>
                            <span class="fw-bold">{{ $info['noOfTerms'] ?? '' }}</span>
                        </div>
                        <div class="col-6 col-md-3 d-flex justify-content-between">
                            <span class="fw-semibold text-muted">Frequency:</span>
                            <span class="fw-bold">{{ $info['frequency'] ?? '' }}</span>
                        </div>
                        <div class="col-6 col-md-2 d-flex justify-content-between">
                            <span class="fw-semibold text-muted">Start:</span>
                            <span>{{ $info['startDate'] ?? '' }}</span>
                        </div>
                        <div class="col-6 col-md-2 d-flex justify-content-between">
                            <span class="fw-semibold text-muted">End:</span>
                            <span
                                class="text-danger fw-semibold">{{ $info['endDate'] ?? '' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Form Fields --}}
        <div class="row g-2 mb-3">
            <div class="col-md-3">
                <label class="form-label fw-semibold small mb-1">From Date <span
                        class="text-danger">*</span></label>
                <input type="date"
                    class="form-control form-control-sm border-secondary-subtle shadow-sm"
                    wire:model.live.debounce.300ms="fromDate">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold small mb-1">No Of Terms</label>
                <input type="number"
                    class="form-control form-control-sm border-secondary-subtle shadow-sm"
                    wire:model.live.debounce.300ms="noOfTerms">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold small mb-1">Amount <span
                        class="text-danger">*</span></label>
                <input type="number"
                    class="form-control form-control-sm border-secondary-subtle shadow-sm"
                    wire:model.live.debounce.300ms="rent" step="0.01">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold small mb-1">Frequency</label>
                <select class="form-select form-select-sm border-secondary-subtle shadow-sm"
                    wire:model.live="frequency">
                    <option value="Daily">Daily</option>
                    <option value="Weekly">Weekly</option>
                    <option value="Bi-Weekly">Bi-Weekly</option>
                    <option value="Monthly">Monthly</option>
                    <option value="Quarterly">Quarterly</option>
                    <option value="Half Yearly">Half Yearly</option>
                    <option value="Yearly">Yearly</option>
                    <option value="One Time">One Time</option>
                </select>
            </div>
        </div>

        {{-- Editable Table --}}
        @if (count($previewList) > 0)
            <div class="table-responsive" style="max-height: 380px; overflow-y: auto;">
                <table class="table table-hover table-bordered table-sm mb-0 align-middle">
                    <thead class="bg-light text-muted sticky-top" style="z-index: 1;">
                        <tr class="text-capitalize small">
                            <th class="fw-semibold py-2 text-center" style="width: 40px;">#</th>
                            <th class="fw-semibold py-2" style="width: 160px;">Date</th>
                            <th class="fw-semibold py-2 text-end" style="width: 140px;">Amount</th>
                            <th class="fw-semibold py-2 text-end" style="width: 120px;">Discount
                            </th>
                            <th class="fw-semibold py-2">Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($previewList as $index => $item)
                            <tr>
                                <td class="text-center py-1 text-muted small">{{ $index + 1 }}
                                </td>
                                <td class="py-1">
                                    <input type="date"
                                        class="form-control form-control-sm border-secondary-subtle"
                                        wire:model="previewList.{{ $index }}.date">
                                </td>
                                <td class="py-1">
                                    <input type="number"
                                        class="form-control form-control-sm text-end border-secondary-subtle"
                                        wire:model="previewList.{{ $index }}.rent"
                                        step="0.01">
                                </td>
                                <td class="py-1">
                                    <input type="number"
                                        class="form-control form-control-sm text-end border-secondary-subtle"
                                        wire:model="previewList.{{ $index }}.discount"
                                        step="0.01">
                                </td>
                                <td class="py-1">
                                    <input type="text"
                                        class="form-control form-control-sm border-secondary-subtle"
                                        wire:model="previewList.{{ $index }}.remark"
                                        placeholder="Remark...">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light sticky-bottom">
                        <tr class="fw-bold small">
                            <td colspan="2" class="py-2 text-end">Total</td>
                            <td class="py-2 text-end text-primary">
                                {{ number_format($this->previewTotal, 2) }}</td>
                            <td class="py-2 text-end text-danger">
                                {{ number_format($this->previewDiscountTotal, 2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @else
            <div class="text-center text-muted py-4">
                <i class="fa fa-calendar-o d-block fs-3 mb-2 opacity-50"></i>
                <span class="small">Fill in the fields above to generate payment terms.</span>
            </div>
        @endif
    </div>

    {{-- Footer --}}
    <div class="modal-footer py-2 px-3 border-top bg-light">
        @if (count($previewList) > 0)
            <span class="me-auto badge bg-primary">{{ count($previewList) }} terms</span>
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

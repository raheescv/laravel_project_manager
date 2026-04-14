<div>
    {{-- Header --}}
    <div class="modal-header py-2 px-3 text-white border-0"
        style="background: linear-gradient(135deg, #3a9e7a, #2e7d56);">
        <h6 class="modal-title fw-bold mb-0">
            <i class="fa fa-bolt me-2"></i>
            {{ $editingId ? 'Edit Utility Term' : 'Add Utility Terms' }}
        </h6>
        <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
    </div>

    {{-- Body --}}
    <div class="modal-body p-3">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold small mb-1">
                    <i class="fa fa-bolt me-1 text-muted"></i> Utility <span class="text-danger">*</span>
                </label>
                <select class="form-select form-select-sm" wire:model="form.utility_id">
                    <option value="">Select Utility</option>
                    @foreach ($utilities as $utility)
                        <option value="{{ $utility['id'] }}">{{ $utility['name'] }}</option>
                    @endforeach
                </select>
                @error('form.utility_id')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold small mb-1">
                    <i class="fa fa-money me-1 text-muted"></i> Amount <span class="text-danger">*</span>
                </label>
                <input type="number" class="form-control form-control-sm"
                    wire:model="form.amount" step="0.01">
                @error('form.amount')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            @if ($editingId)
                {{-- Single edit mode --}}
                <div class="col-md-6">
                    <label class="form-label fw-semibold small mb-1"><i class="fa fa-calendar me-1 text-muted"></i> Date</label>
                    <input type="date" class="form-control form-control-sm"
                        wire:model="form.date">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small mb-1"><i class="fa fa-comment-o me-1 text-muted"></i> Remarks</label>
                    <input type="text" class="form-control form-control-sm"
                        wire:model="form.remarks" placeholder="Optional remarks...">
                </div>
            @else
                {{-- Generate mode --}}
                <div class="col-md-5">
                    <label class="form-label fw-semibold small mb-1">
                        <i class="fa fa-calendar me-1 text-muted"></i> From Date <span class="text-danger">*</span>
                    </label>
                    <input type="date" class="form-control form-control-sm"
                        wire:model="fromDate">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small mb-1"><i class="fa fa-list-ol me-1 text-muted"></i> No Of Terms</label>
                    <input type="number" class="form-control form-control-sm"
                        wire:model="noOfTerms" min="1">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="button" class="btn btn-sm btn-info text-white" wire:click="generate"
                        wire:loading.attr="disabled">
                        Generate
                    </button>
                </div>
            @endif
        </div>

        {{-- Generated terms table --}}
        @if (!$editingId && count($generatedTerms) > 0)
            <div class="table-responsive mt-3">
                <table class="table table-bordered table-hover table-sm text-uppercase mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($generatedTerms as $key => $item)
                            <tr>
                                <td>
                                    <input type="date" class="form-control form-control-sm"
                                        wire:model="generatedTerms.{{ $key }}.date">
                                </td>
                                <td>
                                    <input type="number" class="form-control form-control-sm"
                                        wire:model="generatedTerms.{{ $key }}.amount" step="0.01">
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                        wire:click="deleteGeneratedTerm({{ $key }})">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
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

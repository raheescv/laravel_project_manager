<div>
    {{-- Header --}}
    <div class="modal-header bg-success text-white border-0 py-2 px-3">
        <h6 class="modal-title fw-bold mb-0 text-white">
            <i class="fa fa-money me-2"></i> Pay Existing Service
        </h6>
        <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
    </div>

    {{-- Body --}}
    <div class="modal-body p-3">
        {{-- Services Summary --}}
        <h6 class="fw-bold small mb-2">
            <i class="fa fa-list me-1 text-muted"></i> Services
        </h6>
        <div class="table-responsive mb-3">
            <table class="table table-sm table-bordered table-hover mb-0">
                <thead class="bg-light">
                    <tr class="small">
                        <th class="fw-semibold py-1">Category</th>
                        <th class="fw-semibold py-1 text-end">Credit</th>
                        <th class="fw-semibold py-1 text-end">Debit</th>
                        <th class="fw-semibold py-1 text-end">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($serviceCharges as $row)
                        @php $balance = $row->credit - $row->debit; @endphp
                        @if ($balance != 0)
                            <tr class="small">
                                <td>{{ $row->category }}</td>
                                <td class="text-end">{{ number_format($row->credit, 2) }}</td>
                                <td class="text-end">{{ number_format($row->debit, 2) }}</td>
                                <td class="text-end fw-bold">{{ number_format($balance, 2) }}</td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted small py-2">No service charges yet</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Payment Form --}}
        <div class="row g-3">
            <div class="col-6">
                <label class="form-label fw-semibold small mb-1">
                    <i class="fa fa-calendar me-1 text-muted"></i> Date <span class="text-danger">*</span>
                </label>
                <input type="date" class="form-control form-control-sm" wire:model="form.date">
                @error('form.date')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="col-6">
                <label class="form-label fw-semibold small mb-1">
                    <i class="fa fa-money me-1 text-muted"></i> Amount <span class="text-danger">*</span>
                </label>
                <input type="number" class="form-control form-control-sm" wire:model="form.amount" step="0.01">
                @error('form.amount')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="col-6">
                <label class="form-label fw-semibold small mb-1">
                    <i class="fa fa-tag me-1 text-muted"></i> Category <span class="text-danger">*</span>
                </label>
                <select class="select-account_id-list" wire:model="form.category">
                    <option value="">Please Select Any</option>
                </select>
                @error('form.category')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="col-6">
                <label class="form-label fw-semibold small mb-1">
                    <i class="fa fa-credit-card me-1 text-muted"></i> Payment Mode <span class="text-danger">*</span>
                </label>
                <select class="select-payment_method_id-list" wire:model="form.account_id">
                    <option value="">Please Select Any</option>
                    @foreach ($paymentMethods as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('form.account_id')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold small mb-1">
                    <i class="fa fa-comment-o me-1 text-muted"></i> Remark
                </label>
                <textarea class="form-control form-control-sm" wire:model="form.remark" rows="2" placeholder="Optional remark..."></textarea>
            </div>
        </div>
    </div>
    {{-- Footer --}}
    <div class="modal-footer py-2 px-3 border-top">
        <button type="button" class="btn btn-sm btn-primary" wire:click="payNow" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="payNow"><i class="fa fa-credit-card me-1"></i> Save</span>
            <span wire:loading wire:target="payNow"><i class="fa fa-spinner fa-spin me-1"></i> Saving...</span>
        </button>
    </div>
</div>

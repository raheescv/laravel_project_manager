<div>
    <div class="modal-header bg-warning text-dark border-0 py-2 px-3">
        <h6 class="modal-title fw-bold mb-0">
            <i class="fa fa-exchange me-2"></i> Cheque Status Update
        </h6>
        <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body p-3">
        <div class="mb-3">
            <label class="form-label fw-semibold small mb-1">
                <i class="fa fa-flag me-1 text-muted"></i> Status <span class="text-danger">*</span>
            </label>
            <select wire:model.live="statusChangeStatus" class="form-select form-select-sm">
                @foreach ($chequeStatuses as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>

        <div style="visibility: @if(!in_array($statusChangeStatus,['cleared','return'])) hidden @endif">
            <div class="mb-3" wire:ignore>
                <label class="form-label fw-semibold small mb-1">
                    <i class="fa fa-credit-card me-1 text-muted"></i> Payment Method
                </label>
                {{ html()->select('payment_method_id', [])->value('')->class('select-payment_method_id-list')->attribute('style', 'width:100%')->placeholder('Select Payment Method')->id('statusChangePaymentMethod') }}
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold small mb-1">
                    <i class="fa fa-calendar me-1 text-muted"></i> Journal Date
                </label>
                <input type="date" wire:model="statusChangeJournalDate" class="form-control form-control-sm">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold small mb-1">
                <i class="fa fa-comment-o me-1 text-muted"></i> Remark
            </label>
            <input type="text" wire:model="statusChangeRemark" class="form-control form-control-sm"
                placeholder="Optional remark...">
        </div>

        <div class="mb-0">
            <label class="form-label fw-semibold small mb-1">
                <i class="fa fa-list-ul me-1 text-muted"></i> Selected Cheques
                <span class="badge bg-secondary ms-1">{{ count($this->selectedCheques) }}</span>
            </label>
            <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
                <table class="table table-sm table-hover mb-0">
                    <thead class="bg-light sticky-top">
                        <tr class="small text-muted">
                            <th>#</th>
                            <th><i class="fa fa-user me-1"></i>Customer</th>
                            <th><i class="fa fa-file-text-o me-1"></i>Cheque No</th>
                            <th class="text-end"><i class="fa fa-money me-1"></i>Amount</th>
                            <th><i class="fa fa-calendar-o me-1"></i>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->selectedCheques as $index => $cheque)
                            <tr>
                                <td class="small">{{ $index + 1 }}</td>
                                <td class="small">
                                    <a href="{{ route($cheque['agreement_type'] === 'lease' ? 'property::sale::view' : 'property::rent::view', $cheque['rent_out_id']) }}"
                                        target="_blank" class="text-decoration-none">
                                        <i class="fa fa-external-link me-1 text-primary"></i>{{ $cheque['customer'] }}
                                    </a>
                                </td>
                                <td class="small">{{ $cheque['cheque_no'] }}</td>
                                <td class="small text-end fw-medium">{{ number_format($cheque['amount'], 2) }}</td>
                                <td class="small">{{ $cheque['date'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="modal-footer py-2 px-3 border-top">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">
            <i class="fa fa-times me-1"></i> Close
        </button>
        <button type="button" class="btn btn-sm btn-success" wire:click="updateChequeStatus" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="updateChequeStatus"><i class="fa fa-check me-1"></i> Update</span>
            <span wire:loading wire:target="updateChequeStatus"><i class="fa fa-spinner fa-spin me-1"></i> Updating...</span>
        </button>
    </div>

    @script
    <script>
        $('#statusChangePaymentMethod').on('change', function(e) {
            const value = $(this).val() || null;
            $wire.set('statusChangePaymentMethod', value);
        });
    </script>
    @endscript
</div>

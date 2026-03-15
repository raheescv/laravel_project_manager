<div>
    <div class="modal-header bg-success text-white border-0 py-2 px-3">
        <h6 class="modal-title fw-bold mb-0">
            <i class="fa fa-check-circle me-2"></i> Select Payment Term
        </h6>
        <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body p-3">
        <div class="alert alert-info small py-2 mb-3">
            <i class="fa fa-info-circle me-1"></i>
            No payment term found matching cheque date(s). Please select a payment term to apply the
            cheque payment.
            @if (count($pendingCheques) > 0)
                <div class="mt-1">
                    @foreach ($pendingCheques as $pc)
                        <span class="badge bg-light text-dark border me-1">
                            #{{ $pc['cheque_no'] }} - {{ $pc['date'] }} -
                            {{ number_format($pc['amount'], 2) }}
                        </span>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
            <table class="table table-sm table-hover mb-0">
                <thead class="bg-light sticky-top">
                    <tr class="small text-muted">
                        <th style="width: 30px;"></th>
                        <th>Due Date</th>
                        <th>Label</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($availableTerms as $term)
                        <tr class="{{ $selectedTermId == $term['id'] ? 'table-primary' : '' }}"
                            wire:click="$set('selectedTermId', {{ $term['id'] }})"
                            style="cursor: pointer;">
                            <td>
                                <input type="radio" name="selected_term"
                                    value="{{ $term['id'] }}"
                                    {{ $selectedTermId == $term['id'] ? 'checked' : '' }}
                                    class="form-check-input form-check-input-sm">
                            </td>
                            <td class="small">{{ $term['due_date'] }}</td>
                            <td class="small">{{ $term['label'] }}</td>
                            <td class="small text-end">{{ $term['amount'] }}</td>
                            <td class="small text-end fw-medium text-danger">{{ $term['balance'] }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="modal-footer py-2 px-3 border-top">
        <button type="button" class="btn btn-sm btn-secondary" wire:click="skipTermPayment">
            <i class="fa fa-forward me-1"></i> Skip Payment
        </button>
        <button type="button" class="btn btn-sm btn-success" wire:click="confirmTermPayment"
            {{ !$selectedTermId ? 'disabled' : '' }}>
            <i class="fa fa-check me-1"></i> Pay Selected Term
        </button>
    </div>
</div>

{{-- Payment Terms Tab --}}
{{-- Action Buttons --}}
<div class="d-flex flex-wrap gap-2 align-items-center mb-3">
    <button type="button" class="btn btn-primary d-flex align-items-center shadow-sm" wire:click="openSingleTermModal">
        <i class="fa fa-plus me-2"></i> Add Single Term
    </button>
    <button type="button" class="btn btn-primary d-flex align-items-center shadow-sm" wire:click="openMultipleTermModal">
        <i class="fa fa-plus-circle me-2"></i> Add Multiple Term
    </button>
    <div class="btn-group shadow-sm">
        <button type="button" class="btn btn-success btn-sm d-flex align-items-center" onclick="paySelectedTerms()" title="Pay Selected" data-bs-toggle="tooltip">
            <i class="fa fa-money me-md-1"></i>
            <span class="d-none d-md-inline">Pay Selected</span>
        </button>
        <button type="button" class="btn btn-danger btn-sm d-flex align-items-center" onclick="deleteSelectedTerms()" title="Delete Selected" data-bs-toggle="tooltip">
            <i class="fa fa-trash me-md-1"></i>
            <span class="d-none d-md-inline">Delete Selected</span>
        </button>
    </div>
    <span class="badge bg-light text-dark border ms-auto">
        <i class="fa fa-list me-1 opacity-50"></i>{{ $rentOut->paymentTerms->count() }} rows
    </span>
</div>
<div class="table-responsive">
    <table class="table table-hover align-middle border-bottom mb-0 table-sm" id="paymentTermsTable">
        <thead class="bg-light text-muted">
            <tr class="text-capitalize small">
                <th class="fw-semibold py-2" style="width:30px;">
                    <div class="form-check ms-1">
                        <input type="checkbox" class="form-check-input shadow-sm" id="selectAllTermsCheckbox" onclick="toggleSelectAllTerms()">
                    </div>
                </th>
                <th class="fw-semibold py-2">#</th>
                <th class="fw-semibold py-2">Date</th>
                <th class="fw-semibold py-2">Label</th>
                <th class="fw-semibold py-2 text-end">{{ $isRental ? 'Rent' : 'Installment' }}</th>
                <th class="fw-semibold py-2 text-end">Discount</th>
                <th class="fw-semibold py-2 text-end">Amount</th>
                <th class="fw-semibold py-2 text-end">Paid</th>
                <th class="fw-semibold py-2 text-end">Balance</th>
                <th class="fw-semibold py-2 text-center">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rentOut->paymentTerms as $index => $term)
                @php
                    $rowClass = match($term->paid_flag) {
                        'Paid' => 'table-success',
                        'Partially Paid' => 'table-info',
                        'Current Pending' => '',
                        'Pending' => 'table-danger',
                        default => ''
                    };
                @endphp
                <tr class="{{ $rowClass }}">
                    <td>
                        <div class="form-check ms-1">
                            <input type="checkbox" class="form-check-input shadow-sm term-checkbox" value="{{ $term->id }}">
                        </div>
                    </td>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $term->due_date?->format('d-m-Y') }}</td>
                    <td><span class="badge bg-light text-dark border">{{ ucwords($term->label ?? '') }}</span></td>
                    <td class="text-end">{{ number_format($term->amount, 2) }}</td>
                    <td class="text-end">{{ number_format($term->discount, 2) }}</td>
                    <td class="text-end fw-medium">{{ number_format($term->total, 2) }}</td>
                    <td class="text-end text-success fw-medium">{{ number_format($term->paid ?? 0, 2) }}</td>
                    <td class="text-end text-danger fw-medium">{{ number_format($term->balance ?? 0, 2) }}</td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-light btn-sm" wire:click="editPaymentTerm({{ $term->id }})" title="Edit" data-bs-toggle="tooltip">
                                <i class="fa fa-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-danger btn-sm text-white" wire:click="deletePaymentTerm({{ $term->id }})" wire:confirm="Are you sure you want to delete this payment term?" title="Delete" data-bs-toggle="tooltip">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="10" class="text-center text-muted py-3">No payment terms found</td></tr>
            @endforelse
        </tbody>
        @if($rentOut->paymentTerms->count() > 0)
            <tfoot class="table-light">
                <tr class="fw-bold small">
                    <td colspan="4" class="text-end py-2">Total</td>
                    <td class="text-end py-2">{{ number_format($rentOut->paymentTerms->sum('amount'), 2) }}</td>
                    <td class="text-end py-2">{{ number_format($rentOut->paymentTerms->sum('discount'), 2) }}</td>
                    <td class="text-end py-2">{{ number_format($rentOut->paymentTerms->sum('total'), 2) }}</td>
                    <td class="text-end py-2 text-success">{{ number_format($rentOut->paymentTerms->sum('paid'), 2) }}</td>
                    <td class="text-end py-2 text-danger">{{ number_format($rentOut->paymentTerms->sum('balance'), 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>

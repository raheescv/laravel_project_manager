<div>
    {{-- Action Buttons --}}
    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
        <button type="button" class="btn btn-primary d-flex align-items-center shadow-sm"
            wire:click="openSingleTermModal">
            <i class="fa fa-plus me-2"></i> Add Single Term
        </button>
        <button type="button" class="btn btn-primary d-flex align-items-center shadow-sm"
            wire:click="openMultipleTermModal">
            <i class="fa fa-plus-circle me-2"></i> Add Multiple Term
        </button>
        <div class="btn-group shadow-sm">
            <button type="button" class="btn btn-success btn-sm d-flex align-items-center" onclick="paySelectedTerms()"
                title="Pay Selected" data-bs-toggle="tooltip">
                <i class="fa fa-money me-md-1"></i>
                <span class="d-none d-md-inline">Pay Selected</span>
            </button>
            <button type="button" class="btn btn-danger btn-sm d-flex align-items-center"
                onclick="deleteSelectedTerms()" title="Delete Selected" data-bs-toggle="tooltip">
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
                            <input type="checkbox" class="form-check-input shadow-sm" id="selectAllTermsCheckbox"
                                onclick="toggleSelectAllTerms()">
                        </div>
                    </th>
                    <th class="fw-semibold py-2">#</th>
                    @php
                        $sortableColumns = [
                            'due_date' => ['label' => 'Due Date', 'class' => ''],
                            'label' => ['label' => 'Label', 'class' => ''],
                            'amount' => ['label' => $isRental ? 'Rent' : 'Installment', 'class' => 'text-end'],
                            'discount' => ['label' => 'Discount', 'class' => 'text-end'],
                            'total' => ['label' => 'Amount', 'class' => 'text-end'],
                            'paid' => ['label' => 'Paid', 'class' => 'text-end'],
                            'balance' => ['label' => 'Balance', 'class' => 'text-end'],
                        ];
                    @endphp
                    @foreach ($sortableColumns as $field => $col)
                        <th class="fw-semibold py-2 {{ $col['class'] }}" style="cursor: pointer; user-select: none;"
                            wire:click="sortBy('{{ $field }}')">
                            {{ $col['label'] }}
                            @if ($sortField === $field)
                                <i class="fa fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                            @else
                                <i class="fa fa-sort ms-1 opacity-25"></i>
                            @endif
                        </th>
                    @endforeach
                    <th class="fw-semibold py-2 text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rentOut->paymentTerms as $index => $term)
                    @php
                        $rowClass = match ($term->paid_flag) {
                            'Paid' => 'table-success',
                            'Partially Paid' => 'table-info',
                            'Current Pending' => '',
                            'Pending' => 'table-danger',
                            default => '',
                        };
                    @endphp
                    <tr class="{{ $rowClass }}">
                        <td>
                            <div class="form-check ms-1">
                                <input type="checkbox" class="form-check-input shadow-sm term-checkbox"
                                    value="{{ $term->id }}">
                            </div>
                        </td>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $term->due_date?->format('d-m-Y') }}</td>
                        <td><span class="badge bg-light text-dark border">{{ ucwords($term->label ?? '') }}</span></td>
                        <td class="text-end">{{ currency($term->amount) }}</td>
                        <td class="text-end">{{ currency($term->discount) }}</td>
                        <td class="text-end fw-medium">{{ currency($term->total) }}</td>
                        <td class="text-end text-success fw-medium">{{ currency($term->paid ?? 0) }}</td>
                        <td class="text-end text-danger fw-medium">{{ currency($term->balance ?? 0) }}</td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-light btn-sm"
                                    wire:click="editPaymentTerm({{ $term->id }})" title="Edit"
                                    data-bs-toggle="tooltip">
                                    <i class="fa fa-pencil"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-3">No payment terms found</td>
                    </tr>
                @endforelse
            </tbody>
            @if ($rentOut->paymentTerms->count() > 0)
                <tfoot class="table-light">
                    <tr class="fw-bold small">
                        <td colspan="4" class="text-end py-2">Total</td>
                        <td class="text-end py-2">{{ currency($rentOut->paymentTerms->sum('amount')) }}</td>
                        <td class="text-end py-2">{{ currency($rentOut->paymentTerms->sum('discount')) }}</td>
                        <td class="text-end py-2">{{ currency($rentOut->paymentTerms->sum('total')) }}</td>
                        <td class="text-end py-2 text-success">
                            {{ currency($rentOut->paymentTerms->sum('paid')) }}</td>
                        <td class="text-end py-2 text-danger">
                            {{ currency($rentOut->paymentTerms->sum('balance')) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>

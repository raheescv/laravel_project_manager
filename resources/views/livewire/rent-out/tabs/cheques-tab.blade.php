<div>
    {{-- Action Buttons --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="small text-muted">
            <i class="fa fa-check-square-o me-1"></i>
            <strong>{{ $rentOut->cheques->count() }}</strong> cheque(s)
            &middot; Total: <strong class="text-primary">{{ number_format($rentOut->cheques->sum('amount'), 2) }}</strong>
        </div>
        <div class="d-flex gap-2">
            @if(count($selectedCheques) > 0)
                <button type="button" class="btn btn-sm btn-danger shadow-sm" wire:click="deleteSelected"
                    wire:confirm="Are you sure you want to delete {{ count($selectedCheques) }} selected cheque(s)?">
                    <i class="fa fa-trash me-1"></i> Delete ({{ count($selectedCheques) }})
                </button>
            @endif
            <button type="button" class="btn btn-sm btn-primary shadow-sm" wire:click="openSingleChequeModal">
                <i class="fa fa-plus me-1"></i> Single Cheque
            </button>
            <button type="button" class="btn btn-sm btn-outline-primary shadow-sm" wire:click="openMultipleChequeModal">
                <i class="fa fa-copy me-1"></i> Multiple Cheques
            </button>
        </div>
    </div>

    {{-- Table --}}
    <div class="table-responsive">
        <table class="table table-hover align-middle border-bottom mb-0 table-sm">
            <thead class="bg-light text-muted">
                <tr class="text-capitalize small">
                    <th class="fw-semibold py-2" style="width: 30px;">
                        <input type="checkbox" wire:model.live="selectAll" class="form-check-input form-check-input-sm">
                    </th>
                    <th class="fw-semibold py-2">#</th>
                    @php
                        $sortableColumns = [
                            'cheque_no' => ['label' => 'Cheque No', 'class' => ''],
                            'bank_name' => ['label' => 'Bank', 'class' => ''],
                            'date' => ['label' => 'Date', 'class' => ''],
                            'amount' => ['label' => 'Amount', 'class' => 'text-end'],
                            'payee_name' => ['label' => 'Payee', 'class' => ''],
                            'status' => ['label' => 'Status', 'class' => ''],
                            'remarks' => ['label' => 'Remarks', 'class' => ''],
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
                    <th class="fw-semibold py-2 text-center" style="width: 120px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rentOut->cheques as $index => $cheque)
                    <tr class="{{ $cheque->status?->value === 'cleared' ? 'table-success' : ($cheque->status?->value === 'bounce' || $cheque->status?->value === 'return' ? 'table-danger' : '') }}">
                        <td>
                            <input type="checkbox" wire:model.live="selectedCheques" value="{{ $cheque->id }}" class="form-check-input form-check-input-sm">
                        </td>
                        <td>{{ $index + 1 }}</td>
                        <td><i class="fa fa-check-square-o me-1 text-muted opacity-75"></i>{{ $cheque->cheque_no }}</td>
                        <td>{{ $cheque->bank_name }}</td>
                        <td>{{ $cheque->date?->format('d-m-Y') }}</td>
                        <td class="text-end fw-medium">{{ number_format($cheque->amount, 2) }}</td>
                        <td>{{ $cheque->payee_name }}</td>
                        <td>
                            <div class=" d-inline-block">
                                <button class="badge bg-{{ $cheque->status?->color() }} border-0" type="button">
                                    {{ $cheque->status?->label() }}
                                </button>
                            </div>
                        </td>
                        <td>{{ $cheque->remarks }}</td>
                        <td class="text-center">
                            <button type="button" class="btn btn-light btn-sm" wire:click="editCheque({{ $cheque->id }})" title="Edit">
                                <i class="fa fa-pencil text-primary"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center text-muted py-3">No cheques found</td></tr>
                @endforelse
            </tbody>
            @if($rentOut->cheques->count() > 0)
                <tfoot class="table-light">
                    <tr class="fw-bold small">
                        <td colspan="5" class="py-2 text-end">Total</td>
                        <td class="py-2 text-end text-primary">{{ number_format($rentOut->cheques->sum('amount'), 2) }}</td>
                        <td colspan="4"></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>

    {{-- Term Selector Modal (shown when cheque cleared but no matching term by date) --}}
    <div class="modal" id="TermSelectorModal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 550px;">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-success text-white border-0 py-2 px-3">
                    <h6 class="modal-title fw-bold mb-0">
                        <i class="fa fa-check-circle me-2"></i> Select Payment Term
                    </h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="alert alert-info small py-2 mb-3">
                        <i class="fa fa-info-circle me-1"></i>
                        No payment term found matching cheque date <strong>{{ $pendingChequeDate }}</strong>.
                        Please select a term to apply the payment of <strong>{{ number_format($pendingChequeAmount, 2) }}</strong>.
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
                                @foreach($availableTerms as $term)
                                    <tr class="{{ $selectedTermId == $term['id'] ? 'table-primary' : '' }}"
                                        wire:click="$set('selectedTermId', {{ $term['id'] }})" style="cursor: pointer;">
                                        <td>
                                            <input type="radio" name="selected_term" value="{{ $term['id'] }}"
                                                {{ $selectedTermId == $term['id'] ? 'checked' : '' }}
                                                class="form-check-input form-check-input-sm">
                                        </td>
                                        <td class="small">{{ $term['due_date'] }}</td>
                                        <td class="small">{{ $term['label'] }}</td>
                                        <td class="small text-end">{{ $term['amount'] }}</td>
                                        <td class="small text-end fw-medium text-danger">{{ $term['balance'] }}</td>
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
        </div>
    </div>

    @script
    <script>
        $wire.on('ToggleTermSelectorModal', () => {
            $('#TermSelectorModal').modal('toggle');
        });
    </script>
    @endscript
</div>

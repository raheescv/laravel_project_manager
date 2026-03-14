<div>
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="small text-muted">
            <i class="fa fa-list me-1"></i>
            <strong>{{ $rentOut->utilityTerms->count() }}</strong> utility term(s)
            &middot; Total: <strong class="text-primary">{{ currency($rentOut->utilityTerms->sum('amount')) }}</strong>
            &middot; Paid: <strong class="text-success">{{ currency($rentOut->utilityTerms->sum('paid')) }}</strong>
            &middot; Balance: <strong class="text-danger">{{ currency($rentOut->utilityTerms->sum('balance')) }}</strong>
        </div>
    </div>

    {{-- Action Buttons --}}
    <div class="d-flex flex-wrap gap-1 mb-2">
        @if ($rentOut->utilityTerms->count() > 0)
            <button type="button" class="btn btn-sm btn-outline-danger" wire:click="deleteSelected"
                wire:confirm="Are you sure you want to delete the selected ({{ count($selectedTerms) }}) rows?">
                <i class="fa fa-trash me-1"></i> Delete Selected
                @if (count($selectedTerms) > 0)
                    <span class="badge bg-danger ms-1">{{ count($selectedTerms) }}</span>
                @endif
            </button>
            <button type="button" class="btn btn-sm btn-outline-success" wire:click="paySelected">
                <i class="fa fa-money me-1"></i> Pay Selected
                @if (count($selectedTerms) > 0)
                    <span class="badge bg-success ms-1">{{ count($selectedTerms) }}</span>
                @endif
            </button>
        @endif
        <button type="button" class="btn btn-sm btn-primary shadow-sm ms-auto" wire:click="openUtilityTermModal">
            <i class="fa fa-plus me-1"></i> Add Term
        </button>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle border-bottom mb-0 table-sm">
            <thead class="bg-light text-muted">
                <tr class="text-capitalize small">
                    <th class="fw-semibold py-2 text-center" style="width: 35px;">
                        <input type="checkbox" class="form-check-input" wire:model.live="selectAll">
                    </th>
                    <th class="fw-semibold py-2">#</th>
                    @php
                        $sortableColumns = [
                            'utility_id' => ['label' => 'Utility', 'class' => ''],
                            'date' => ['label' => 'Date', 'class' => ''],
                            'amount' => ['label' => 'Amount', 'class' => 'text-end'],
                            'paid' => ['label' => 'Paid', 'class' => 'text-end'],
                            'balance' => ['label' => 'Balance', 'class' => 'text-end'],
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
                    <th class="fw-semibold py-2 text-center" style="width: 90px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rentOut->utilityTerms as $index => $uTerm)
                    @php
                        $rowClass = $uTerm->balance <= 0 ? 'table-success' : ($uTerm->paid > 0 ? 'table-info' : '');
                    @endphp
                    <tr class="{{ $rowClass }}">
                        <td class="text-center">
                            <input type="checkbox" class="form-check-input" value="{{ $uTerm->id }}"
                                wire:model.live="selectedTerms">
                        </td>
                        <td>{{ $index + 1 }}</td>
                        <td><i class="fa fa-bolt me-1 text-warning opacity-75"></i>{{ $uTerm->utility?->name ?? '—' }}
                        </td>
                        <td>{{ $uTerm->date?->format('d-m-Y') }}</td>
                        <td class="text-end fw-medium">{{ currency($uTerm->amount) }}</td>
                        <td class="text-end fw-medium text-success">{{ currency($uTerm->paid) }}</td>
                        <td class="text-end fw-medium {{ $uTerm->balance > 0 ? 'text-danger' : 'text-success' }}">
                            {{ currency($uTerm->balance) }}
                        </td>
                        <td>{{ $uTerm->remarks }}</td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-light btn-sm"
                                    wire:click="editUtilityTerm({{ $uTerm->id }})" title="Edit">
                                    <i class="fa fa-pencil text-primary"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-3">No utility terms found</td>
                    </tr>
                @endforelse
            </tbody>
            @if ($rentOut->utilityTerms->count() > 0)
                <tfoot class="table-light">
                    <tr class="fw-bold small">
                        <td colspan="4" class="py-2 text-end">Total</td>
                        <td class="py-2 text-end text-primary">
                            {{ currency($rentOut->utilityTerms->sum('amount')) }}</td>
                        <td class="py-2 text-end text-success">
                            {{ currency($rentOut->utilityTerms->sum('paid')) }}</td>
                        <td class="py-2 text-end text-danger">
                            {{ currency($rentOut->utilityTerms->sum('balance')) }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>

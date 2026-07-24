<div>
    {{-- Summary --}}
    @php $balance = $totalDebit - $totalCredit; @endphp
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="small text-muted">
            <i class="fa fa-exchange me-1"></i>
            <strong>{{ $entries->count() }}</strong> transaction(s)
            &middot; Balance: <strong class="{{ $balance > 0 ? 'text-danger' : 'text-success' }}">{{ number_format($balance, 2) }}</strong>
        </div>
    </div>

    {{-- Table --}}
    <div class="table-responsive">
        <table class="table table-hover align-middle border-bottom mb-0 table-sm">
            <thead class="bg-light text-muted">
                <tr class="text-capitalize small">
                    <th class="fw-semibold py-2">#</th>
                    @php
                        $sortableColumns = [
                            'date' => ['label' => 'Date', 'class' => ''],
                            'category' => ['label' => 'Category', 'class' => ''],
                            'payment_mode' => ['label' => 'Payment Mode', 'class' => ''],
                            'debit' => ['label' => 'Debit', 'class' => 'text-end'],
                            'credit' => ['label' => 'Credit', 'class' => 'text-end'],
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
                    <th class="fw-semibold py-2 text-end">Balance</th>
                    <th class="fw-semibold py-2">Remark</th>
                </tr>
            </thead>
            <tbody>
                @forelse($entries as $index => $entry)
                    <tr>
                        <td class="small">{{ $index + 1 }}</td>
                        <td class="small text-nowrap">{{ $entry['date']?->format('d-m-Y') }}</td>
                        <td class="small text-nowrap">{{ $entry['category'] }}</td>
                        <td class="small text-nowrap">{{ $entry['payment_mode'] }}</td>
                        <td class="text-end text-danger fw-medium small">
                            {{ $entry['debit'] > 0 ? number_format($entry['debit'], 2) : '' }}
                        </td>
                        <td class="text-end text-success fw-medium small">
                            {{ $entry['credit'] > 0 ? number_format($entry['credit'], 2) : '' }}
                        </td>
                        <td class="text-end fw-bold small {{ $entry['balance'] > 0 ? 'text-danger' : 'text-success' }}">
                            {{ number_format($entry['balance'], 2) }}
                        </td>
                        <td class="small text-muted">{{ $entry['remark'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-3">No transactions found</td></tr>
                @endforelse
            </tbody>
            @if($entries->count() > 0)
                <tfoot class="table-light">
                    <tr class="fw-bold small">
                        <td colspan="4" class="py-2 text-end">Total</td>
                        <td class="py-2 text-end text-danger">{{ number_format($totalDebit, 2) }}</td>
                        <td class="py-2 text-end text-success">{{ number_format($totalCredit, 2) }}</td>
                        <td class="py-2 text-end {{ $balance > 0 ? 'text-danger' : 'text-success' }}">{{ number_format($balance, 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>

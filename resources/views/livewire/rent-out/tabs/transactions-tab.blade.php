<div>
    {{-- Summary --}}
    @php
        $totalCredit = $payments->sum('credit');
        $totalDebit = $payments->sum('debit');
        $netBalance = $totalCredit - $totalDebit;
    @endphp
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="small text-muted">
            <i class="fa fa-exchange me-1"></i>
            <strong>{{ $payments->count() }}</strong> transaction(s)
            &middot; Net Balance: <strong class="{{ $netBalance >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($netBalance, 2) }}</strong>
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
                            'account_id' => ['label' => 'Payment Mode', 'class' => ''],
                            'credit' => ['label' => 'Credit', 'class' => 'text-end'],
                            'debit' => ['label' => 'Debit', 'class' => 'text-end'],
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
                @php $runningBalance = 0; @endphp
                @forelse($payments as $index => $payment)
                    @php
                        $runningBalance += ($payment->credit ?? 0) - ($payment->debit ?? 0);
                    @endphp
                    <tr>
                        <td class="small">{{ $index + 1 }}</td>
                        <td class="small text-nowrap">{{ $payment->date?->format('d-m-Y') ?? '' }}</td>
                        <td class="small text-nowrap">{{ $payment->category ?? '' }}</td>
                        <td class="small text-nowrap">{{ $payment->account?->name ?? '' }}</td>
                        <td class="text-end text-success fw-medium small">
                            {{ $payment->credit > 0 ? number_format($payment->credit, 2) : '' }}
                        </td>
                        <td class="text-end text-danger fw-medium small">
                            {{ $payment->debit > 0 ? number_format($payment->debit, 2) : '' }}
                        </td>
                        <td class="text-end fw-bold small {{ $runningBalance >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ number_format($runningBalance, 2) }}
                        </td>
                        <td class="small text-muted">{{ $payment->remark ?? '' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-3">No transactions found</td></tr>
                @endforelse
            </tbody>
            @if($payments->count() > 0)
                <tfoot class="table-light">
                    <tr class="fw-bold small">
                        <td colspan="4" class="py-2 text-end">Total</td>
                        <td class="py-2 text-end text-success">{{ number_format($totalCredit, 2) }}</td>
                        <td class="py-2 text-end text-danger">{{ number_format($totalDebit, 2) }}</td>
                        <td class="py-2 text-end {{ $netBalance >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($netBalance, 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>

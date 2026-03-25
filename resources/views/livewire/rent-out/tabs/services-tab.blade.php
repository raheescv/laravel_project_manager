<div>
    {{-- Action Buttons --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div class="small text-muted">
            <i class="fa fa-cogs me-1"></i> Services & Charges
        </div>
        <div class="d-flex gap-1 flex-wrap">
            @if(count($selectedPayments) > 0)
                <button type="button" class="btn btn-outline-danger d-inline-flex align-items-center"
                    style="font-size: .7rem; padding: .2rem .5rem; border-radius: 4px;"
                    wire:click="deleteSelected"
                    wire:confirm="Are you sure you want to delete {{ count($selectedPayments) }} selected payment(s)?">
                    <i class="fa fa-trash me-1"></i> Delete ({{ count($selectedPayments) }})
                </button>
            @endif
            <button type="button" class="btn btn-outline-primary d-inline-flex align-items-center"
                style="font-size: .7rem; padding: .2rem .5rem; border-radius: 4px;"
                wire:click="openServiceModal">
                <i class="fa fa-plus me-1"></i> Add Service
            </button>
            <button type="button" class="btn btn-outline-info d-inline-flex align-items-center"
                style="font-size: .7rem; padding: .2rem .5rem; border-radius: 4px;"
                wire:click="openServiceChargeModal">
                <i class="fa fa-calculator me-1"></i> Service Charge
            </button>
            <button type="button" class="btn btn-outline-success d-inline-flex align-items-center"
                style="font-size: .7rem; padding: .2rem .5rem; border-radius: 4px;"
                wire:click="openServicePaymentModal">
                <i class="fa fa-money me-1"></i> Pay Existing
            </button>
        </div>
    </div>

    {{-- Category Summary --}}
    @if($categorySummary->count() > 0)
        <div class="mb-3">
            <h6 class="fw-bold small text-muted mb-2"><i class="fa fa-bar-chart me-1"></i> Services Summary</h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover mb-0">
                    <thead class="bg-light text-muted">
                        <tr class="small">
                            <th class="fw-semibold py-1">Category</th>
                            <th class="fw-semibold py-1 text-end">Credit</th>
                            <th class="fw-semibold py-1 text-end">Debit</th>
                            <th class="fw-semibold py-1 text-end">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($categorySummary as $row)
                            @php $balance = $row->credit - $row->debit; @endphp
                            @if($balance != 0)
                                <tr class="small">
                                    <td>{{ $categoryNames[$row->category] ?? $row->category }}</td>
                                    <td class="text-end text-success">{{ number_format($row->credit, 2) }}</td>
                                    <td class="text-end text-danger">{{ number_format($row->debit, 2) }}</td>
                                    <td class="text-end fw-bold">{{ number_format($balance, 2) }}</td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Service Transactions --}}
    @if($servicePayments->count() > 0)
        <h6 class="fw-bold small text-muted mb-2"><i class="fa fa-exchange me-1"></i> Service Transactions</h6>
        <div class="table-responsive" style="overflow: visible;">
            <table class="table table-hover align-middle border-bottom mb-0 table-sm">
                <thead class="bg-light text-muted">
                    <tr class="text-capitalize small">
                        <th class="fw-semibold py-2" style="width: 30px;">
                            <input type="checkbox" wire:model.live="selectAll" class="form-check-input form-check-input-sm">
                        </th>
                        <th class="fw-semibold py-2">#</th>
                        @php
                            $sortableColumns = [
                                'date' => ['label' => 'Date', 'class' => ''],
                                'source' => ['label' => 'Source', 'class' => 'd-none d-md-table-cell'],
                                'category' => ['label' => 'Category', 'class' => ''],
                                'account_id' => ['label' => 'Payment Mode', 'class' => 'd-none d-md-table-cell'],
                                'credit' => ['label' => 'Credit', 'class' => 'text-end'],
                                'debit' => ['label' => 'Debit', 'class' => 'text-end'],
                                'remark' => ['label' => 'Remark', 'class' => 'd-none d-lg-table-cell'],
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
                        <th class="fw-semibold py-2 text-center" style="width: 50px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($servicePayments as $index => $payment)
                        <tr>
                            <td>
                                <input type="checkbox" wire:model.live="selectedPayments" value="{{ $payment->id }}" class="form-check-input form-check-input-sm">
                            </td>
                            <td class="small">{{ $index + 1 }}</td>
                            <td class="small text-nowrap">{{ $payment->date?->format('d-m-Y') ?? '' }}</td>
                            <td class="d-none d-md-table-cell text-nowrap">
                                <span class="badge bg-{{ match($payment->source) {
                                    'ServiceCharge' => 'info',
                                    'Service' => 'secondary',
                                    default => 'light text-dark',
                                } }} bg-opacity-75 small">{{ $payment->source }}</span>
                            </td>
                            <td class="small text-nowrap">{{ $categoryNames[$payment->category] ?? $payment->category ?? '' }}</td>
                            <td class="small d-none d-md-table-cell text-nowrap">{{ $payment->account?->name ?? '' }}</td>
                            <td class="text-end text-success fw-medium small">
                                {{ $payment->credit > 0 ? number_format($payment->credit, 2) : '' }}
                            </td>
                            <td class="text-end text-danger fw-medium small">
                                {{ $payment->debit > 0 ? number_format($payment->debit, 2) : '' }}
                            </td>
                            <td class="small text-muted d-none d-lg-table-cell">{{ $payment->remark ?? '' }}</td>
                            <td class="text-center" style="width: 60px;">
                                <div class="dropdown">
                                    <button type="button" class="btn btn-sm border-0 p-1 d-inline-flex align-items-center justify-content-center"
                                        style="width: 32px; height: 32px; background: #f0f2f5; border-radius: 8px; color: #495057;"
                                        data-bs-toggle="dropdown" aria-expanded="false"
                                        onmouseover="this.style.background='#1a73e8'; this.style.color='#fff';"
                                        onmouseout="this.style.background='#f0f2f5'; this.style.color='#495057';">
                                        <i class="fa fa-ellipsis-v" style="font-size: 16px;"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" style="min-width: 140px; z-index: 1050;">
                                        <li>
                                            <a class="dropdown-item small py-2" href="#" wire:click.prevent="editPayment({{ $payment->id }})">
                                                <i class="fa fa-pencil text-primary me-2"></i> Edit
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item small py-2" href="#" wire:click.prevent="printReceipt({{ $payment->id }})">
                                                <i class="fa fa-print text-info me-2"></i> Print Receipt
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider my-1"></li>
                                        <li>
                                            <a class="dropdown-item small py-2 text-danger" href="#"
                                                wire:click.prevent="deletePayment({{ $payment->id }})"
                                                wire:confirm="Are you sure you want to delete this payment?">
                                                <i class="fa fa-trash me-2"></i> Delete
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light">
                    <tr class="fw-bold small">
                        <td colspan="6" class="py-2 text-end">Total</td>
                        <td class="py-2 text-end text-success">{{ number_format($servicePayments->sum('credit'), 2) }}</td>
                        <td class="py-2 text-end text-danger">{{ number_format($servicePayments->sum('debit'), 2) }}</td>
                        <td class="d-none d-lg-table-cell"></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @else
        <div class="text-center text-muted py-3 small">No service transactions found</div>
    @endif
</div>

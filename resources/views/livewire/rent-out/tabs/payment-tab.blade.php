<div>
    {{-- Summary Bar --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="small text-muted">
            <i class="fa fa-credit-card me-1"></i>
            <strong>{{ $payments->count() }}</strong> payment record(s)
            &middot; Credit: <strong class="text-success">{{ number_format($payments->sum('credit'), 2) }}</strong>
            &middot; Debit: <strong class="text-danger">{{ number_format($payments->sum('debit'), 2) }}</strong>
        </div>
        <div class="d-flex gap-2">
            @if (count($selectedIds) > 0)
                <button type="button" class="btn btn-sm btn-outline-danger" wire:click="deleteSelected"
                    wire:confirm="Are you sure you want to delete {{ count($selectedIds) }} selected record(s)?">
                    <i class="fa fa-trash me-1"></i> Delete ({{ count($selectedIds) }})
                </button>
            @endif
            <button type="button" class="btn btn-sm btn-warning" wire:click="openPayoutModal">
                <i class="fa fa-share me-1"></i> Payout
            </button>
        </div>
    </div>

    {{-- Source Summary Cards --}}
    @if ($sourceSummary->count() > 0)
        <div class="row g-2 mb-3">
            @foreach ($sourceSummary as $summary)
                <div class="col-auto">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body py-2 px-3">
                            <div class="d-flex align-items-center gap-2">
                                <span
                                    class="badge bg-{{ match ($summary->source) {
                                        'PaymentTerm' => 'primary',
                                        'UtilityTerm' => 'info',
                                        'ServiceCharge' => 'success',
                                        'Service' => 'secondary',
                                        'Payout' => 'warning',
                                        default => 'light text-dark',
                                    } }}">{{ $summary->source }}</span>
                                <div class="small">
                                    <span class="text-muted">{{ $summary->count }}x</span>
                                    &middot; <span
                                        class="text-success">{{ number_format($summary->total_credit, 2) }}</span>
                                    @if ($summary->total_debit > 0)
                                        &middot; <span
                                            class="text-danger">{{ number_format($summary->total_debit, 2) }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Filters --}}
    <div class="row g-2 mb-3 align-items-end">
        <div class="col">
            <label class="form-label fw-semibold small mb-1">
                <i class="fa fa-filter me-1 text-muted"></i> Source
            </label>
            <select class="form-select form-select-sm" wire:model.live="filterSource">
                <option value="">All Sources</option>
                @foreach ($sources as $source)
                    <option value="{{ $source }}">{{ $source }}</option>
                @endforeach
            </select>
        </div>
        <div class="col">
            <label class="form-label fw-semibold small mb-1">
                <i class="fa fa-tag me-1 text-muted"></i> Category
            </label>
            <select class="form-select form-select-sm" wire:model.live="filterCategory">
                <option value="">All Categories</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat }}">{{ $cat }}</option>
                @endforeach
            </select>
        </div>
        <div class="col">
            <label class="form-label fw-semibold small mb-1">
                <i class="fa fa-credit-card me-1 text-muted"></i> Payment Mode
            </label>
            <select class="form-select form-select-sm" wire:model.live="filterPaymentMode">
                <option value="">All Modes</option>
                @foreach ($paymentModes as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col">
            <label class="form-label fw-semibold small mb-1">
                <i class="fa fa-calendar me-1 text-muted"></i> From
            </label>
            <input type="date" class="form-control form-control-sm" wire:model.live="filterDateFrom">
        </div>
        <div class="col">
            <label class="form-label fw-semibold small mb-1">
                <i class="fa fa-calendar me-1 text-muted"></i> To
            </label>
            <input type="date" class="form-control form-control-sm" wire:model.live="filterDateTo">
        </div>
        <div class="col-auto">
            <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="resetFilters"
                title="Reset Filters">
                <i class="fa fa-refresh"></i>
            </button>
        </div>
    </div>

    {{-- Table --}}
    <div class="table-responsive" style="overflow: visible;">
        <table class="table table-hover align-middle border-bottom mb-0 table-sm">
            <thead class="bg-light text-muted">
                <tr class="text-capitalize small">
                    <th class="fw-semibold py-2">
                        <input type="checkbox" class="form-check-input" wire:model.live="selectAll">
                    </th>
                    <th class="fw-semibold py-2">#</th>
                    @php
                        $sortableColumns = [
                            'date' => ['label' => 'Date', 'class' => ''],
                            'source' => ['label' => 'Source', 'class' => ''],
                            'group' => ['label' => 'Group', 'class' => ''],
                            'category' => ['label' => 'Category', 'class' => ''],
                            'account_id' => ['label' => 'Payment Mode', 'class' => ''],
                            'credit' => ['label' => 'Credit', 'class' => 'text-end'],
                            'debit' => ['label' => 'Debit', 'class' => 'text-end'],
                            'remark' => ['label' => 'Remark', 'class' => ''],
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
                    <th class="fw-semibold py-2 text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $index => $payment)
                    <tr wire:key="payment-{{ $payment->id }}">
                        <td>
                            <input type="checkbox" class="form-check-input" wire:model.live="selectedIds"
                                value="{{ $payment->id }}">
                        </td>
                        <td class="small">{{ $index + 1 }}</td>
                        <td class="small text-nowrap">{{ $payment->date?->format('d-m-Y') ?? '' }}</td>
                        <td>
                            <span
                                class="badge bg-{{ match ($payment->source) {
                                    'PaymentTerm' => 'primary',
                                    'UtilityTerm' => 'info',
                                    'ServiceCharge' => 'success',
                                    'Service' => 'secondary',
                                    'Payout' => 'warning',
                                    default => 'light text-dark',
                                } }} bg-opacity-75 small">{{ $payment->source }}</span>
                        </td>
                        <td class="small text-nowrap">{{ $payment->group ?? '' }}</td>
                        <td class="small text-nowrap">{{ $payment->category?->name ?? '' }}</td>
                        <td class="small text-nowrap">{{ $payment->account?->name ?? '' }}</td>
                        <td class="text-end text-success fw-medium small">
                            {{ $payment->credit > 0 ? number_format($payment->credit, 2) : '' }}
                        </td>
                        <td class="text-end text-danger fw-medium small">
                            {{ $payment->debit > 0 ? number_format($payment->debit, 2) : '' }}
                        </td>
                        <td class="small text-muted">{{ $payment->remark ?? '' }}</td>
                        <td class="text-center">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light border-0 p-1" data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                    <i class="fa fa-ellipsis-v text-muted"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="z-index: 1050;">
                                    <li>
                                        <a class="dropdown-item small" href="#"
                                            wire:click.prevent="editPayment({{ $payment->id }})">
                                            <i class="fa fa-pencil me-2 text-primary"></i> Edit
                                        </a>
                                    </li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li>
                                        <a class="dropdown-item small"
                                            href="{{ route('print::rentout::payment-receipt', $payment->id) }}"
                                            target="_blank">
                                            <i class="fa fa-print me-2 text-primary"></i> Print Receipt
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item small"
                                            href="{{ route('print::rentout::payment-voucher', $payment->id) }}"
                                            target="_blank">
                                            <i class="fa fa-file-text-o me-2 text-info"></i> Print Voucher
                                        </a>
                                    </li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li>
                                        <a class="dropdown-item small"
                                            href="{{ route('audit::index', ['model' => 'RentOutPayment', 'id' => $payment->id]) }}"
                                            target="_blank">
                                            <i class="fa fa-history me-2 text-secondary"></i> Audit History
                                        </a>
                                    </li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li>
                                        <button class="dropdown-item small text-danger"
                                            wire:click="deletePayment({{ $payment->id }})"
                                            wire:confirm="Are you sure you want to delete this payment record?">
                                            <i class="fa fa-trash me-2"></i> Delete
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="text-center text-muted py-3">No payment records found</td>
                    </tr>
                @endforelse
            </tbody>
            @if ($payments->count() > 0)
                <tfoot class="table-light">
                    <tr class="fw-bold small">
                        <td colspan="7" class="py-2 text-end">Total</td>
                        <td class="py-2 text-end text-success">{{ number_format($payments->sum('credit'), 2) }}</td>
                        <td class="py-2 text-end text-danger">{{ number_format($payments->sum('debit'), 2) }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>

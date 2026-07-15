<div x-data="{
    columns: {
        date: true, due_date: true, cheque_date: true, source: true,
        group: true, category: true, reason: true, account_id: true,
        cheque_no: true, credit: true, remark: true, created_at: false
    },
    labels: {
        date: 'Date', due_date: 'Due Date', cheque_date: 'Cheque Date', source: 'Source',
        group: 'Group', category: 'Category', reason: 'Reason', account_id: 'Payment Mode',
        cheque_no: 'Cheque No', credit: 'Amount', remark: 'Remark', created_at: 'Created At'
    }
}">
    <style>
        [x-cloak] {
            display: none !important;
        }

        /* Stacked-card layout on tablet/phone (below lg) */
        @media (max-width: 991.98px) {
            .rentout-payment-table thead {
                display: none;
            }

            .rentout-payment-table,
            .rentout-payment-table tbody,
            .rentout-payment-table tfoot,
            .rentout-payment-table tr,
            .rentout-payment-table td {
                display: block;
                width: 100%;
            }

            .rentout-payment-table tbody tr {
                border: 1px solid #e9ecef;
                border-radius: .6rem;
                margin-bottom: .75rem;
                padding: .35rem .85rem;
                background: #fff;
                box-shadow: 0 1px 3px rgba(0, 0, 0, .04);
            }

            .rentout-payment-table tbody td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 1rem;
                text-align: right;
                border: none;
                border-bottom: 1px solid #f1f3f5;
                padding: .45rem 0;
                white-space: normal;
            }

            .rentout-payment-table tbody td:last-child {
                border-bottom: none;
            }

            .rentout-payment-table tbody td::before {
                content: attr(data-label);
                font-weight: 600;
                color: #6c757d;
                text-transform: uppercase;
                font-size: .65rem;
                letter-spacing: .3px;
                text-align: left;
                flex-shrink: 0;
            }

            /* Hide empty cells so cards stay compact */
            .rentout-payment-table tbody td.is-empty {
                display: none;
            }

            /* Full-width empty state / total rows */
            .rentout-payment-table td[colspan] {
                text-align: center;
            }

            .rentout-payment-table td[colspan]::before {
                content: none;
            }

            .rentout-payment-table tfoot tr {
                border: none;
                background: transparent;
                padding: .25rem .85rem;
            }

            .rentout-payment-table tfoot td {
                display: flex;
                justify-content: space-between;
                border: none;
                padding: .2rem 0;
            }
        }
    </style>

    {{-- Summary Bar --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="small text-muted">
            <i class="fa fa-credit-card me-1"></i>
            <strong>{{ $payments->count() }}</strong> payment(s) received
            &middot; Total: <strong class="text-success">{{ number_format($payments->sum('credit'), 2) }}</strong>
        </div>
        <div class="d-flex gap-1">
            @if (count($selectedIds) > 0)
                <button type="button" class="btn btn-outline-danger d-inline-flex align-items-center"
                    style="font-size: .7rem; padding: .2rem .5rem; border-radius: 4px;"
                    wire:click="deleteSelected"
                    wire:confirm="Are you sure you want to delete {{ count($selectedIds) }} selected record(s)?">
                    <i class="fa fa-trash me-1"></i> Delete ({{ count($selectedIds) }})
                </button>
            @endif
            <button type="button" class="btn btn-outline-warning d-inline-flex align-items-center"
                style="font-size: .7rem; padding: .2rem .5rem; border-radius: 4px;"
                wire:click="openPayoutModal">
                <i class="fa fa-share me-1"></i> Payout
            </button>

            {{-- Column visibility --}}
            <div x-data="{
                open: false,
                x: 0,
                y: 0,
                maxH: 300,
                toggle(e) {
                    const r = e.currentTarget.getBoundingClientRect();
                    this.x = r.right;
                    this.y = r.bottom + 4;
                    this.maxH = window.innerHeight - r.bottom - 20;
                    this.open = !this.open;
                }
            }" @keydown.escape.window="open = false" @scroll.window="open = false">
                <button type="button" class="btn btn-outline-secondary d-inline-flex align-items-center"
                    style="font-size: .7rem; padding: .2rem .5rem; border-radius: 4px;" @click="toggle($event)">
                    <i class="fa fa-columns me-1"></i> Columns
                </button>
                <div x-show="open" x-cloak @click.outside="open = false"
                    class="card shadow-sm py-2"
                    :style="`position: fixed; top: ${y}px; left: ${x}px; transform: translateX(-100%); z-index: 2000; min-width: 190px; max-height: ${maxH}px; overflow-y: auto;`">
                    <template x-for="(label, key) in labels" :key="key">
                        <label class="d-flex align-items-center gap-2 px-3 py-1 small"
                            style="cursor: pointer; white-space: nowrap;">
                            <input type="checkbox" class="form-check-input mt-0" x-model="columns[key]">
                            <span x-text="label"></span>
                        </label>
                    </template>
                </div>
            </div>
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
        <div class="col-6 col-md">
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
        <div class="col-6 col-md">
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
        <div class="col-6 col-md">
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
        <div class="col-6 col-md">
            <label class="form-label fw-semibold small mb-1">
                <i class="fa fa-calendar me-1 text-muted"></i> From
            </label>
            <input type="date" class="form-control form-control-sm" wire:model.live="filterDateFrom">
        </div>
        <div class="col-6 col-md">
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
    <div class="table-responsive" style="overflow-x: auto;">
        <table class="table table-hover align-middle border-bottom mb-0 table-sm rentout-payment-table">
            <thead class="bg-light text-muted">
                <tr class="text-capitalize small">
                    <th class="fw-semibold py-2">
                        <input type="checkbox" class="form-check-input" wire:model.live="selectAll">
                    </th>
                    <th class="fw-semibold py-2">#</th>
                    @php
                        $sortableColumns = [
                            'date' => ['label' => 'Date', 'class' => ''],
                            'due_date' => ['label' => 'Due Date', 'class' => ''],
                            'cheque_date' => ['label' => 'Cheque Date', 'class' => ''],
                            'source' => ['label' => 'Source', 'class' => ''],
                            'group' => ['label' => 'Group', 'class' => ''],
                            'category' => ['label' => 'Category', 'class' => ''],
                            'reason' => ['label' => 'Reason', 'class' => ''],
                            'account_id' => ['label' => 'Payment Mode', 'class' => ''],
                            'cheque_no' => ['label' => 'Cheque No', 'class' => ''],
                            'credit' => ['label' => 'Amount', 'class' => 'text-end'],
                            'remark' => ['label' => 'Remark', 'class' => ''],
                            'created_at' => ['label' => 'Created At', 'class' => 'text-nowrap'],
                        ];
                    @endphp
                    @foreach ($sortableColumns as $field => $col)
                        <th class="fw-semibold py-2 {{ $col['class'] }}" style="cursor: pointer; user-select: none;"
                            x-show="columns['{{ $field }}']" wire:click="sortBy('{{ $field }}')">
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
                        <td data-label="Select">
                            <input type="checkbox" class="form-check-input" wire:model.live="selectedIds"
                                value="{{ $payment->id }}">
                        </td>
                        <td data-label="#" class="small">{{ $index + 1 }}</td>
                        <td data-label="Date" x-show="columns.date"
                            class="small text-nowrap {{ $payment->date ? '' : 'is-empty' }}">{{ $payment->date?->format('d-m-Y') ?? '' }}</td>
                        <td data-label="Due Date" x-show="columns.due_date"
                            class="small text-nowrap {{ $payment->due_date ? '' : 'is-empty' }}">{{ $payment->due_date?->format('d-m-Y') ?? '' }}</td>
                        <td data-label="Cheque Date" x-show="columns.cheque_date"
                            class="small text-nowrap {{ $payment->cheque_date ? '' : 'is-empty' }}">{{ $payment->cheque_date?->format('d-m-Y') ?? '' }}</td>
                        <td data-label="Source" x-show="columns.source">
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
                        <td data-label="Group" x-show="columns.group"
                            class="small text-nowrap {{ $payment->group ? '' : 'is-empty' }}">{{ $payment->group ?? '' }}</td>
                        <td data-label="Category" x-show="columns.category"
                            class="small text-nowrap {{ $payment->category ? '' : 'is-empty' }}">{{ $payment->category ?? '' }}</td>
                        <td data-label="Reason" x-show="columns.reason"
                            class="small text-nowrap {{ $payment->reason ? '' : 'is-empty' }}">{{ $payment->reason ?? '' }}</td>
                        <td data-label="Payment Mode" x-show="columns.account_id"
                            class="small text-nowrap {{ $payment->account?->name ? '' : 'is-empty' }}">{{ $payment->account?->name ?? '' }}</td>
                        <td data-label="Cheque No" x-show="columns.cheque_no"
                            class="small text-nowrap {{ $payment->cheque_no ? '' : 'is-empty' }}">{{ $payment->cheque_no ?? '' }}</td>
                        <td data-label="Amount" x-show="columns.credit" class="text-end text-success fw-medium small">
                            {{ number_format($payment->credit, 2) }}
                        </td>
                        <td data-label="Remark" x-show="columns.remark"
                            class="small text-muted {{ $payment->remark ? '' : 'is-empty' }}">{{ $payment->remark ?? '' }}</td>
                        <td data-label="Created At" x-show="columns.created_at"
                            class="small text-muted text-nowrap {{ $payment->created_at ? '' : 'is-empty' }}">{{ $payment->created_at?->format('d-m-Y h:i A') ?? '' }}</td>
                        <td data-label="Action" class="text-center">
                            <div x-data="{
                                open: false,
                                x: 0,
                                y: 0,
                                toggle(e) {
                                    const r = e.currentTarget.getBoundingClientRect();
                                    this.x = r.right;
                                    this.y = r.bottom;
                                    this.open = !this.open;
                                }
                            }" @click.outside="open = false" @keydown.escape.window="open = false"
                                @scroll.window="open = false">
                                <button type="button" class="btn btn-sm btn-light border-0 p-1"
                                    @click="toggle($event)">
                                    <i class="fa fa-ellipsis-v text-muted"></i>
                                </button>
                                <div x-show="open" x-cloak class="card shadow-sm py-1 text-start"
                                    :style="`position: fixed; top: ${y}px; left: ${x}px; transform: translateX(-100%); z-index: 2000; min-width: 180px;`">
                                    <a class="dropdown-item small" href="#" @click="open = false"
                                        wire:click.prevent="editPayment({{ $payment->id }})">
                                        <i class="fa fa-pencil me-2 text-primary"></i> Edit
                                    </a>
                                    <hr class="dropdown-divider my-1">
                                    <a class="dropdown-item small" @click="open = false"
                                        href="{{ route('print::rentout::payment-receipt', $payment->id) }}"
                                        target="_blank">
                                        <i class="fa fa-print me-2 text-primary"></i> Print Receipt
                                    </a>
                                    <a class="dropdown-item small" @click="open = false"
                                        href="{{ route('print::rentout::payment-voucher', $payment->id) }}"
                                        target="_blank">
                                        <i class="fa fa-file-text-o me-2 text-info"></i> Print Voucher
                                    </a>
                                    <hr class="dropdown-divider my-1">
                                    <a class="dropdown-item small" @click="open = false"
                                        href="{{ route('audit::index', ['model' => 'RentOutTransaction', 'id' => $payment->id]) }}"
                                        target="_blank">
                                        <i class="fa fa-history me-2 text-secondary"></i> Audit History
                                    </a>
                                    <hr class="dropdown-divider my-1">
                                    <button type="button" class="dropdown-item small text-danger" @click="open = false"
                                        wire:click="deletePayment({{ $payment->id }})"
                                        wire:confirm="Are you sure you want to delete this payment record?">
                                        <i class="fa fa-trash me-2"></i> Delete
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="15" class="text-center text-muted py-3">No payment records found</td>
                    </tr>
                @endforelse
            </tbody>
            @if ($payments->count() > 0)
                <tfoot class="table-light">
                    <tr class="fw-bold small">
                        <td colspan="11" class="py-2 text-end">Total</td>
                        <td class="py-2 text-end text-success" data-label="Total">{{ number_format($payments->sum('credit'), 2) }}</td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>

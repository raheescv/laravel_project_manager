@php
    use App\Enums\RentOut\CommissionStatus;
    use App\Models\RentOutCommission;
@endphp
<div>
    <style>
        .cmt-steps { display: flex; overflow-x: auto; padding: .25rem 0; }
        .cmt-step { flex: 1 0 100px; text-align: center; position: relative; }
        .cmt-step::before { content: ""; position: absolute; top: 13px; left: -50%; width: 100%; height: 2px; background: var(--bs-border-color); z-index: 0; }
        .cmt-step:first-child::before { display: none; }
        .cmt-step.is-done::before, .cmt-step.is-current::before, .cmt-step.is-skipped::before { background: var(--bs-success); }
        .cmt-dot { width: 28px; height: 28px; border-radius: 50%; margin: 0 auto .25rem; display: flex; align-items: center; justify-content: center; position: relative; z-index: 1; background: var(--bs-body-bg); border: 2px solid var(--bs-border-color); color: var(--bs-secondary-color); font-size: .7rem; font-weight: 600; }
        .cmt-step.is-done .cmt-dot { background: var(--bs-success); border-color: var(--bs-success); color: #fff; }
        .cmt-step.is-current .cmt-dot { border-color: var(--bs-primary); border-style: dashed; color: var(--bs-primary); box-shadow: 0 0 0 4px rgba(var(--bs-primary-rgb), .15); }
        .cmt-step.is-skipped .cmt-dot { border-style: dashed; }
        .cmt-step-label { font-size: .7rem; font-weight: 600; color: var(--bs-emphasis-color); }
        .cmt-step.is-pending .cmt-step-label, .cmt-step.is-skipped .cmt-step-label { color: var(--bs-secondary-color); }
        .cmt-step-sub { font-size: .62rem; color: var(--bs-secondary-color); }
        .cmt-avatar { width: 30px; height: 30px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: .68rem; font-weight: 600; color: #fff; background: var(--bs-primary); flex: none; }
        .cmt-label { font-size: .62rem; letter-spacing: .06em; text-transform: uppercase; font-weight: 600; color: var(--bs-secondary-color); }
        .cmt-timeline { list-style: none; padding: 0; margin: 0; }
        .cmt-timeline li { position: relative; padding: 0 0 .7rem 1.1rem; font-size: .74rem; }
        .cmt-timeline li::before { content: ""; position: absolute; left: 2px; top: 5px; width: 8px; height: 8px; border-radius: 50%; background: var(--cmt-tone, var(--bs-success)); }
        .cmt-timeline li::after { content: ""; position: absolute; left: 5.5px; top: 15px; bottom: 0; width: 1px; background: var(--bs-border-color); }
        .cmt-timeline li:last-child::after { display: none; }
        .cmt-row-selected > td { background-color: var(--bs-primary-bg-subtle) !important; }
    </style>

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2 small text-muted">
            <span><i class="fa fa-percent me-1"></i> Commission Expense</span>
            @if ($selected)
                <span class="badge bg-{{ $selected->status->color() }} bg-opacity-75">{{ $selected->status->label() }}</span>
                @if ($selected->invoice_no)
                    <span class="text-muted">{{ $selected->invoice_no }}</span>
                @endif
            @endif
        </div>
        <div class="d-flex gap-1 flex-wrap">
            @if ($selected)
                @include('livewire.rent-out.partials.commission-actions', ['commission' => $selected, 'canReject' => $canReject, 'canEdit' => true, 'labelled' => true])
            @endif
            @if ($canAdd)
                @can('rent out commission.create')
                    <button type="button" class="btn btn-sm btn-outline-success" wire:click="openCommissionModal">
                        <i class="fa fa-plus me-1"></i> Add Commission
                    </button>
                @endcan
            @endif
        </div>
    </div>

    @if (! $selected)
        <div class="text-center text-muted py-4 small border rounded-3">
            <i class="fa fa-percent d-block mb-2 fs-4 opacity-50"></i>
            No commission added yet
        </div>
    @else
        @if ($selected->status === CommissionStatus::Rejected)
            <div class="alert alert-danger py-2 small mb-3">
                <i class="fa fa-ban me-1"></i>
                Rejected by <b>{{ $selected->rejecter?->name }}</b> on {{ $selected->rejected_at?->format('d-m-Y') }}:
                "{{ $selected->rejection_reason }}". Edit and resubmit, or delete it.
            </div>
        @endif

        {{-- Progress --}}
        <div class="border rounded-3 px-2 py-3 mb-3">
            <div class="cmt-steps">
                @foreach ($steps as $index => $step)
                    <div class="cmt-step is-{{ $step['state'] }}">
                        <div class="cmt-dot">
                            @if ($step['state'] === 'done')
                                <i class="fa fa-check"></i>
                            @elseif ($step['state'] === 'skipped')
                                <i class="fa fa-minus"></i>
                            @else
                                {{ $index + 1 }}
                            @endif
                        </div>
                        <div class="cmt-step-label">{{ $step['label'] }}</div>
                        <div class="cmt-step-sub">{{ $step['sub'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-8">
                {{-- Figures --}}
                <div class="row g-2 mb-3">
                    <div class="col-6 col-md-3">
                        <div class="border rounded-3 px-3 py-2 h-100">
                            <div class="cmt-label">Salesman</div>
                            <div class="d-flex align-items-center gap-2 mt-1">
                                <span class="cmt-avatar">{{ collect(explode(' ', (string) $selected->agent?->name))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('') }}</span>
                                <span class="fw-semibold small text-truncate">{{ $selected->agent?->name }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="border rounded-3 px-3 py-2 h-100">
                            <div class="cmt-label">Type</div>
                            <div class="fw-semibold mt-1">{{ $selected->typeLabel() }}</div>
                            @if ($selected->commission_type === RentOutCommission::TYPE_PERCENTAGE)
                                <div class="small text-muted">{{ number_format($selected->commission_percentage, 2) }} %</div>
                            @endif
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="border rounded-3 px-3 py-2 h-100">
                            <div class="cmt-label">Contract Amount</div>
                            <div class="fw-bold mt-1">{{ number_format($selected->contract_amount, 2) }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="border border-primary-subtle bg-primary-subtle rounded-3 px-3 py-2 h-100">
                            <div class="cmt-label">Commission</div>
                            <div class="fw-bold mt-1 text-primary-emphasis">{{ number_format($selected->amount, 2) }}</div>
                        </div>
                    </div>
                </div>

                <div class="bg-body-tertiary border rounded-3 px-3 py-2 small mb-3 font-monospace">
                    <span class="text-muted">Calculation</span>&nbsp;
                    @if ($selected->commission_type === RentOutCommission::TYPE_PERCENTAGE)
                        {{ number_format($selected->contract_amount, 2) }} × {{ number_format($selected->commission_percentage, 2) }} % = <b>{{ number_format($selected->amount, 2) }}</b>
                    @else
                        Fixed amount = <b>{{ number_format($selected->amount, 2) }}</b>
                    @endif
                    @if ($selected->remark)
                        <span class="text-muted ms-2">· {{ $selected->remark }}</span>
                    @endif
                </div>

                {{-- Documents --}}
                <h6 class="fw-bold small text-muted mb-2"><i class="fa fa-file-text-o me-1"></i> Documents</h6>
                <div class="table-responsive">
                    <table class="table table-sm align-middle border-bottom mb-0">
                        <thead class="bg-light text-muted">
                            <tr class="small">
                                <th class="fw-semibold py-2">Document</th>
                                <th class="fw-semibold py-2">No.</th>
                                <th class="fw-semibold py-2">Date</th>
                                <th class="fw-semibold py-2 text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <tr>
                                <td><i class="fa fa-file-text-o me-1 text-muted"></i> Commission Invoice</td>
                                @if ($selected->invoice_no)
                                    <td>{{ $selected->invoice_no }}</td>
                                    <td>{{ $selected->finance_approved_at?->format('d-m-Y') }}</td>
                                    <td class="text-end">{{ number_format($selected->amount, 2) }}</td>
                                @else
                                    <td colspan="3" class="text-muted">— after Finance Approval —</td>
                                @endif
                            </tr>
                            <tr>
                                <td><i class="fa fa-book me-1 text-muted"></i> Journal (Dr Expense / Cr Payable)</td>
                                @if ($selected->journal_id)
                                    <td>#{{ $selected->journal_id }}</td>
                                    <td>{{ $selected->finance_approved_at?->format('d-m-Y') }}</td>
                                    <td class="text-end">{{ number_format($selected->amount, 2) }}</td>
                                @else
                                    <td colspan="3" class="text-muted">—</td>
                                @endif
                            </tr>
                            <tr>
                                <td><i class="fa fa-money me-1 text-muted"></i> Payment Voucher</td>
                                @if ($selected->payment_journal_id)
                                    <td>#{{ $selected->payment_journal_id }} <span class="text-muted">· {{ $selected->paymentAccount?->name }}</span></td>
                                    <td>{{ $selected->paid_date?->format('d-m-Y') }}</td>
                                    <td class="text-end">{{ number_format($selected->amount, 2) }}</td>
                                @else
                                    <td colspan="3" class="text-muted">—</td>
                                @endif
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Activity --}}
            <div class="col-lg-4">
                <div class="border rounded-3 p-3 h-100">
                    <div class="cmt-label mb-2">Activity</div>
                    <ul class="cmt-timeline">
                        @foreach ($activity as $event)
                            <li style="--cmt-tone: var(--bs-{{ $event['tone'] }});">
                                <b>{{ $event['title'] }}</b>
                                <span class="text-muted">· {{ $event['at']->format('d M Y') }}</span>
                                <div class="text-muted">{{ $event['detail'] }}</div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        {{-- Other commissions on this agreement --}}
        @if ($commissions->count() > 1)
            <h6 class="fw-bold small text-muted mt-3 mb-2"><i class="fa fa-list me-1"></i> All Commissions</h6>
            <div class="table-responsive">
                <table class="table table-hover align-middle border-bottom mb-0 table-sm">
                    <thead class="bg-light text-muted">
                        <tr class="small">
                            <th class="fw-semibold py-2">#</th>
                            <th class="fw-semibold py-2">Date</th>
                            <th class="fw-semibold py-2">Salesman</th>
                            <th class="fw-semibold py-2 text-end">%</th>
                            <th class="fw-semibold py-2 text-end">Commission</th>
                            <th class="fw-semibold py-2">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($commissions as $index => $commission)
                            <tr wire:key="commission-{{ $commission->id }}" role="button"
                                class="{{ $commission->id === $selected->id ? 'cmt-row-selected' : '' }}"
                                wire:click="selectCommission({{ $commission->id }})">
                                <td class="small">{{ $index + 1 }}</td>
                                <td class="small text-nowrap">{{ $commission->date?->format('d-m-Y') }}</td>
                                <td class="small">{{ $commission->agent?->name }}</td>
                                <td class="small text-end">{{ $commission->commission_type === RentOutCommission::TYPE_PERCENTAGE ? number_format($commission->commission_percentage, 2) : '-' }}</td>
                                <td class="small text-end fw-bold">{{ number_format($commission->amount, 2) }}</td>
                                <td><span class="badge bg-{{ $commission->status->color() }} bg-opacity-75 small">{{ $commission->status->label() }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr class="fw-bold small">
                            <td colspan="4" class="py-2 text-end">Total (excl. rejected) · Paid {{ number_format($totals['paid'], 2) }} · Unpaid {{ number_format($totals['payable'], 2) }}</td>
                            <td class="py-2 text-end">{{ number_format($totals['total'], 2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    @endif
</div>

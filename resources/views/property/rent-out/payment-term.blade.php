@php
    $isBooked = ($rentOut->status?->value ?? (string) $rentOut->status) === 'booked';
    $backRoute = route($isBooked ? $config->bookingViewRoute : $config->viewRoute, $rentOut->id);

    $flagBadge = match ($term->paid_flag) {
        'Paid' => 'success',
        'Partially Paid' => 'info',
        'Pending' => 'danger',
        default => 'secondary',
    };

    $activeJournals = $journals->reject(fn ($journal) => $journal->trashed());
    $activeEntries = $activeJournals->flatMap->entries;
    $reversedCount = $journals->count() - $activeJournals->count();

    $paymentAudits = $transactions->flatMap->audits->sortByDesc('created_at')->values();
    $journalAudits = $journals->flatMap->audits->sortByDesc('created_at')->values();
    $termAudits = $term->audits->sortByDesc('created_at')->values();

    $stats = [
        ['label' => 'Due Date', 'value' => $term->due_date?->format('d-m-Y') ?? '—', 'class' => ''],
        ['label' => $config->isRental ? 'Rent' : 'Installment', 'value' => currency($term->amount), 'class' => ''],
        ['label' => 'Discount', 'value' => currency($term->discount), 'class' => ''],
        ['label' => 'Amount', 'value' => currency($term->total), 'class' => 'fw-bold'],
        ['label' => 'Paid', 'value' => currency($term->paid ?? 0), 'class' => 'text-success'],
        ['label' => 'Balance', 'value' => currency($term->balance ?? 0), 'class' => 'text-danger'],
    ];
@endphp

<x-app-layout>
    <div class="content__boxed">
        <div class="content__wrap ptv">

            {{-- Header --}}
            <div class="card border-0 shadow-sm mb-2">
                <div class="card-body py-2 px-3 d-flex flex-wrap align-items-center gap-2">
                    <div class="ptv-icon"><i class="fa fa-calendar"></i></div>
                    <div class="me-auto">
                        <div class="d-flex align-items-center flex-wrap gap-2">
                            <h6 class="fw-bold mb-0">Payment Term <span class="text-muted fw-normal">#{{ $term->id }}</span></h6>
                            @if ($term->label)
                                <span class="badge bg-light text-dark border">{{ ucwords($term->label) }}</span>
                            @endif
                            <span class="badge bg-{{ $flagBadge }}">{{ $term->paid_flag }}</span>
                            @if ($term->trashed())
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Deleted Term</span>
                            @endif
                        </div>
                        <div class="text-muted small">
                            {{ $config->singularLabel }}
                            <a href="{{ $backRoute }}" class="fw-semibold">#{{ $rentOut->id }}</a>
                            @if ($rentOut->property)
                                · {{ $rentOut->property->number }}
                            @endif
                            @if ($rentOut->customer)
                                · {{ $rentOut->customer->name }}
                            @endif
                            @if ($term->remarks)
                                · <span class="fst-italic">{{ $term->remarks }}</span>
                            @endif
                        </div>
                    </div>
                    <a href="{{ $backRoute }}" class="btn btn-outline-secondary btn-sm ptv-btn">
                        <i class="fa fa-arrow-left me-1"></i> Back to {{ $config->singularLabel }}
                    </a>
                </div>

                {{-- Stats strip --}}
                <div class="ptv-stats border-top">
                    @foreach ($stats as $stat)
                        <div class="ptv-stat">
                            <div class="ptv-stat-label">{{ $stat['label'] }}</div>
                            <div class="ptv-stat-value {{ $stat['class'] }}">{{ $stat['value'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Journal entries --}}
            @if ($canViewJournals)
                <div class="card border-0 shadow-sm mb-2" id="journals">
                    <div class="card-header bg-white py-2 px-3 d-flex align-items-center gap-2">
                        <i class="fa fa-book text-primary"></i>
                        <span class="fw-semibold small">Journal Entries</span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle">{{ $activeJournals->count() }} active</span>
                        @if ($reversedCount)
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">{{ $reversedCount }} reversed</span>
                        @endif
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0 ptv-table">
                            <thead class="bg-light text-muted">
                                <tr class="small">
                                    <th>Date</th>
                                    <th>Account</th>
                                    <th>Description</th>
                                    <th class="text-end">Debit</th>
                                    <th class="text-end">Credit</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($journals as $journal)
                                    @php($payment = $transactionsByJournal->get($journal->id))
                                    <tr class="ptv-group {{ $journal->trashed() ? 'is-reversed' : '' }}">
                                        <td colspan="5">
                                            <div class="d-flex flex-wrap align-items-center gap-2">
                                                <span class="fw-semibold">Journal #{{ $journal->id }}</span>
                                                <span class="text-muted">{{ systemDate($journal->date) }}</span>
                                                @if ($payment?->voucher_no)
                                                    <span class="badge bg-light text-dark border">{{ $payment->voucher_no }}</span>
                                                @endif
                                                @if ($payment?->payment_type)
                                                    <span class="badge bg-light text-dark border">{{ $payment->payment_type }}</span>
                                                @endif
                                                @if ($journal->person_name)
                                                    <span class="text-muted"><i class="fa fa-user-o me-1"></i>{{ $journal->person_name }}</span>
                                                @endif
                                                <span class="ms-auto d-inline-flex align-items-center gap-2">
                                                    @if ($journal->trashed())
                                                        <span class="badge bg-danger">Reversed</span>
                                                    @else
                                                        <span class="badge bg-success">Active</span>
                                                    @endif
                                                    <a href="{{ route('audit::index', ['model' => 'Journal', 'id' => $journal->id]) }}"
                                                        target="_blank" class="text-muted" title="Journal Audit" data-bs-toggle="tooltip">
                                                        <i class="fa fa-history"></i>
                                                    </a>
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                    @foreach ($journal->entries->sortByDesc('debit') as $entry)
                                        <tr class="{{ $journal->trashed() ? 'is-reversed' : '' }}">
                                            <td class="text-muted ps-4">{{ systemDate($entry->date) }}</td>
                                            <td>
                                                <a href="{{ route('account::view', $entry->account_id) }}" class="{{ $entry->credit > 0 ? 'ms-3' : '' }}">
                                                    {{ $entry->account?->name ?? '—' }}
                                                </a>
                                            </td>
                                            <td class="text-muted text-truncate" style="max-width: 280px;">{{ $entry->remarks ?: $journal->description }}</td>
                                            <td class="text-end">{{ $entry->debit != 0 ? currency($entry->debit) : '—' }}</td>
                                            <td class="text-end">{{ $entry->credit != 0 ? currency($entry->credit) : '—' }}</td>
                                        </tr>
                                    @endforeach
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-3">
                                            <i class="fa fa-book opacity-25 me-1"></i> No journal entries are connected to this payment term.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if ($activeEntries->isNotEmpty())
                                <tfoot class="table-light">
                                    <tr class="fw-bold small">
                                        <td colspan="3" class="text-end">Total (Active)</td>
                                        <td class="text-end">{{ currency($activeEntries->sum('debit')) }}</td>
                                        <td class="text-end">{{ currency($activeEntries->sum('credit')) }}</td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            @endif

            {{-- Audit trail --}}
            <div class="card border-0 shadow-sm mb-2" id="audit">
                <div class="card-header bg-white py-0 px-3 d-flex flex-wrap align-items-center gap-2">
                    <i class="fa fa-history text-primary"></i>
                    <span class="fw-semibold small me-2 text-nowrap">Audit Trail</span>
                    <ul class="nav nav-tabs border-0 ptv-tabs flex-nowrap" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#ptv-audit-term" type="button" role="tab">
                                Term <span class="badge bg-light text-dark border">{{ $termAudits->count() }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#ptv-audit-payments" type="button" role="tab">
                                Payments <span class="badge bg-light text-dark border">{{ $paymentAudits->count() }}</span>
                            </button>
                        </li>
                        @if ($canViewJournals)
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#ptv-audit-journals" type="button" role="tab">
                                    Journals <span class="badge bg-light text-dark border">{{ $journalAudits->count() }}</span>
                                </button>
                            </li>
                        @endif
                    </ul>
                    <a href="{{ route('audit::index', ['model' => 'RentOutPaymentTerm', 'id' => $term->id]) }}" target="_blank"
                        class="ms-auto small text-muted" title="Open full audit page" data-bs-toggle="tooltip">
                        <i class="fa fa-external-link"></i>
                    </a>
                </div>
                <div class="tab-content p-2">
                    <div id="ptv-audit-term" class="tab-pane fade show active" role="tabpanel">
                        <x-audit.grid :audits="$termAudits" emptyMessage="No audit entries for this payment term." />
                    </div>
                    <div id="ptv-audit-payments" class="tab-pane fade" role="tabpanel">
                        <x-audit.grid :audits="$paymentAudits" :showRecord="true" emptyMessage="No payment audit entries for this term." />
                    </div>
                    @if ($canViewJournals)
                        <div id="ptv-audit-journals" class="tab-pane fade" role="tabpanel">
                            <x-audit.grid :audits="$journalAudits" :showRecord="true" emptyMessage="No journal audit entries for this term." />
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('styles')
        <style>
            .ptv { font-size: .8rem; }
            .ptv-icon { width: 34px; height: 34px; border-radius: 8px; display: grid; place-items: center; background: rgba(13, 110, 253, .1); color: #0d6efd; }
            .ptv-btn { font-size: .7rem; padding: .2rem .5rem; border-radius: 4px; }
            .ptv-stats { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); }
            .ptv-stat { padding: .45rem .75rem; border-right: 1px solid var(--bs-border-color-translucent); }
            .ptv-stat:last-child { border-right: 0; }
            .ptv-stat-label { font-size: .62rem; text-transform: uppercase; letter-spacing: .5px; color: var(--bs-secondary-color); }
            .ptv-stat-value { font-size: .85rem; font-weight: 600; white-space: nowrap; }
            @media (max-width: 767.98px) {
                .ptv-stats { grid-template-columns: repeat(3, minmax(0, 1fr)); }
                .ptv-stat:nth-child(3n) { border-right: 0; }
                .ptv-stat:nth-child(-n+3) { border-bottom: 1px solid var(--bs-border-color-translucent); }
            }
            .ptv-table { font-size: .76rem; }
            .ptv-table th { font-weight: 600; padding: .4rem .6rem; }
            .ptv-table td { padding: .3rem .6rem; }
            .ptv-group td { background: var(--bs-tertiary-bg); border-top: 1px solid var(--bs-border-color); }
            .ptv-table tr.is-reversed td { color: var(--bs-secondary-color); }
            .ptv-table tr.is-reversed:not(.ptv-group) td { text-decoration: line-through; text-decoration-color: rgba(220, 53, 69, .5); }
            .ptv-tabs .nav-link { white-space: nowrap; font-size: .72rem; padding: .55rem .6rem; border: 0; border-bottom: 2px solid transparent; color: var(--bs-secondary-color); }
            .ptv-tabs .nav-link.active { color: #0d6efd; border-bottom-color: #0d6efd; background: transparent; }
        </style>
    @endpush
</x-app-layout>

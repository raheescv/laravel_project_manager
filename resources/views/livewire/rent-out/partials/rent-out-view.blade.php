{{--
    Agreement View Partial (Non-booking) — "Premium Hero" design.
    Variables required from parent:
      - $rentOut, $indexRoute, $indexLabel, $editPermission, $editRoute, $config

    The whole page is wrapped in .rvx so the premium design system
    (resources/views/components/rent-out/view/premium.blade.php) styles both
    these panels AND every management tab rendered inside it. All colour is
    derived from the active settings theme (--bs-primary / --bs-* tokens), so
    it tracks the chosen colour scheme and dark mode automatically.
--}}
@php
    use App\Enums\RentOut\RentOutStatus;

    $isRental = $rentOut->agreement_type?->value === 'rental';
    $title = $isRental ? 'Rental Agreement' : 'Sale Agreement';

    $totalRent = $rentOut->paymentTerms->sum('amount');
    $totalDiscount = $rentOut->paymentTerms->sum('discount');
    $totalPaid = $rentOut->paymentTerms->where('status', 'paid')->sum('total');
    $totalPending = $totalRent - $totalDiscount - $totalPaid;
    $paidMonths = $rentOut->paymentTerms->where('status', 'paid')->count();
    $totalMonths = $rentOut->paymentTerms->count();
    $securityTotal = $rentOut->securities->sum('amount');
    $utilitiesPaid = $rentOut->utilityTerms->sum('amount') - $rentOut->utilityTerms->sum('balance');
    $utilitiesPending = $rentOut->utilityTerms->sum('balance');
    $daysRemaining = round(now()->diffInDays($rentOut->end_date, false));
    $net = max($totalRent - $totalDiscount, 0);
    $paidPercent = $net > 0 ? min(round(($totalPaid / $net) * 100), 100) : 0;
    $grandPending = $totalPending + ($isRental ? $utilitiesPending : 0);

    $activeStatus = !in_array($rentOut->status, [RentOutStatus::Vacated, RentOutStatus::Cancelled]);
    $statusColor = $rentOut->status?->color() ?? 'secondary';
    $overdue = $daysRemaining <= 0;

    $custName = trim($rentOut->customer?->name ?? '');
    $initials = collect(explode(' ', $custName))->filter()->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('');
    $initials = mb_strtoupper($initials !== '' ? $initials : '—');
@endphp

<x-rent-out.view.premium />

<div class="rvx">

    {{-- ════════════════════════════  HERO  ════════════════════════════ --}}
    <header class="hero">
        <span class="hero-glow a"></span><span class="hero-glow b"></span>

        <nav class="crumb mb-3" aria-label="breadcrumb">
            <a href="{{ route($indexRoute) }}"><i class="fa fa-home"></i> Home</a>
            <span class="sep">›</span>
            <a href="{{ route($indexRoute) }}">{{ $indexLabel }}</a>
            <span class="sep">›</span>
            <span class="here">{{ $rentOut->agreement_no }}</span>
        </nav>

        <div class="row g-4 align-items-end">
            <div class="col-lg-7">
                <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                    <span class="pill pill-rental"><i class="fa fa-building-o"></i> {{ $isRental ? 'Rental' : 'Sale' }}</span>
                    @if ($rentOut->status)
                        <span class="pill pill-status" style="color: var(--bs-{{ $statusColor }}-text-emphasis);">
                            <span class="dot" style="background: var(--bs-{{ $statusColor }});"></span>
                            {{ $rentOut->status->label() }}@if ($rentOut->booking_status) · {{ $rentOut->booking_status?->label() }} @endif
                        </span>
                    @endif
                </div>
                <h1 class="hero-title">{{ $title }} <span class="hash">#{{ $rentOut->agreement_no }}</span></h1>
                <div class="hero-meta mt-2">
                    @if ($custName)<i class="fa fa-user-o me-1"></i> {{ $custName }}@endif
                    @if ($rentOut->building?->name)
                        <span class="mx-2" style="opacity:.4">•</span>
                        <i class="fa fa-map-marker me-1"></i> {{ $rentOut->building?->name }}@if ($rentOut->property?->number) · Unit {{ $rentOut->property?->number }} @endif
                    @endif
                </div>
            </div>

            <div class="col-lg-5">
                <div class="hero-actions d-flex flex-wrap gap-2 justify-content-lg-end mb-3">
                    <button type="button" class="btn btn-glass" data-bs-toggle="modal" data-bs-target="#SOAStatementModal"><i class="fa fa-print"></i> SOA</button>
                    @if ($isRental)
                        <button type="button" class="btn btn-glass" data-bs-toggle="modal" data-bs-target="#SOAUtilitiesModal"><i class="fa fa-bolt"></i> Utilities</button>
                    @endif
                    @if ($activeStatus)
                        <button type="button" class="btn btn-glass-warn" wire:click="openVacateModal"><i class="fa fa-sign-out"></i> Vacate</button>
                    @endif
                    @can($editPermission)
                        <a href="{{ route($editRoute, $rentOut->id) }}" class="btn btn-on-hero"><i class="fa fa-pencil"></i> Edit</a>
                    @endcan
                </div>
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="hero-prog-label"><i class="fa fa-credit-card me-1"></i> Payment Progress</span>
                        <span class="hero-prog-val">{{ $paidPercent }}%</span>
                    </div>
                    <div class="hero-track"><div class="hero-fill {{ $paidPercent > 0 ? '' : 'empty' }}" data-fill="{{ $paidPercent }}"></div></div>
                    <div class="hero-prog-label mt-1" style="font-size:11px;">
                        {{ $paidMonths }} of {{ $totalMonths }} instalments collected · {{ number_format($totalPending, 2) }} outstanding
                    </div>
                </div>
            </div>
        </div>
    </header>

    {{-- ════════════════════════════  GLASS KPI CARDS  ════════════════════════════ --}}
    <section class="kpi-row" aria-label="Key metrics">
        <div class="row g-3">
            <x-rent-out.view.kpi :tone="$overdue ? 'danger' : 'info'" icon="fa-calendar"
                :label="$overdue ? 'Days Overdue' : 'Days Remaining'" :value="abs($daysRemaining)"
                :sub="$overdue ? 'past end date' : 'days left on tenancy'">
                <x-slot:badge>
                    <span class="chip {{ $overdue ? 'chip-danger' : 'chip-info' }}">{{ $overdue ? 'overdue' : 'live' }}</span>
                </x-slot>
            </x-rent-out.view.kpi>

            <x-rent-out.view.kpi tone="purple" icon="fa-check-square-o" label="Paid Instalments"
                :value="$paidMonths" :sub="'of ' . $totalMonths . ' total'">
                <x-slot:badge><span class="chip chip-soft">{{ $paidMonths }} / {{ $totalMonths }}</span></x-slot>
            </x-rent-out.view.kpi>

            <x-rent-out.view.kpi tone="success" icon="fa-check-circle-o" label="Collected"
                :value="number_format($totalPaid, 2)" sub="received to date">
                <x-slot:badge><span class="chip chip-success"><i class="fa fa-arrow-up"></i> {{ $paidPercent }}%</span></x-slot>
            </x-rent-out.view.kpi>

            <x-rent-out.view.kpi tone="danger" icon="fa-clock-o" label="Outstanding"
                :value="number_format($totalPending, 2)" sub="balance due">
                <x-slot:badge><span class="chip chip-danger">due</span></x-slot>
            </x-rent-out.view.kpi>
        </div>
    </section>

    {{-- ════════════════════════════  INFO GRID  ════════════════════════════ --}}
    <section class="row g-3 mt-1">

        {{-- Property & Customer --}}
        <div class="col-lg-4">
            <x-rent-out.view.panel icon="fa-building" title="Property & Customer" sub="Unit and tenant details">
                <div class="cust mb-3">
                    <span class="avatar">{{ $initials }}</span>
                    <div class="min-w-0">
                        <div class="nm text-truncate">{{ $custName !== '' ? $custName : '—' }}</div>
                        <div class="sub"><i class="fa fa-user-o me-1"></i> Tenant of record</div>
                    </div>
                </div>
                <div class="dl">
                    <x-rent-out.view.field icon="fa-hashtag" label="Reference No" :value="$rentOut->agreement_no" />
                    <x-rent-out.view.field icon="fa-th-large" label="Group" :value="$rentOut->group?->name" />
                    <x-rent-out.view.field icon="fa-building" label="Building" :value="$rentOut->building?->name" />
                    <x-rent-out.view.field icon="fa-cubes" label="Unit Type" :value="$rentOut->type?->name" />
                    <x-rent-out.view.field icon="fa-home" label="Unit / Property">
                        @if ($rentOut->property?->number)<span class="chip chip-info">{{ $rentOut->property?->number }}</span>@else — @endif
                    </x-rent-out.view.field>
                    <x-rent-out.view.field icon="fa-sign-out" label="Vacate Date">
                        @if ($rentOut->vacate_date)
                            <span class="{{ $rentOut->vacate_date > now() ? 'text-warning' : '' }}">{{ $rentOut->vacate_date->format('d M Y') }}</span>
                            @if ($rentOut->vacate_date > now() && $activeStatus)
                                <button type="button" class="btn-mini" wire:click="openVacateModal"><i class="fa fa-pencil"></i></button>
                            @endif
                        @elseif ($activeStatus)
                            <span class="muted">Not set</span><button type="button" class="btn-mini" wire:click="openVacateModal"><i class="fa fa-plus"></i> Set</button>
                        @else
                            <span class="muted">—</span>
                        @endif
                    </x-rent-out.view.field>
                    <x-rent-out.view.field icon="fa-circle-o" label="Status">
                        <span class="chip chip-{{ $statusColor === 'success' ? 'success' : ($statusColor === 'danger' ? 'danger' : ($statusColor === 'warning' ? 'warning' : 'soft')) }}">
                            {{ $rentOut->status?->label() }}
                        </span>
                    </x-rent-out.view.field>
                </div>
            </x-rent-out.view.panel>
        </div>

        {{-- Agreement Details --}}
        <div class="col-lg-4">
            <x-rent-out.view.panel icon="fa-file-text-o" :title="$title" sub="Term & financial structure">
                <div class="rent-hero mb-3">
                    <div>
                        <div class="section-eyebrow mb-1">{{ $isRental ? 'Monthly Rent' : 'Sale Price' }}</div>
                        <div class="amount">{{ number_format($rentOut->rent, 2) }}</div>
                    </div>
                    <div class="text-end">
                        <span class="chip chip-soft mb-1"><i class="fa fa-clock-o"></i> {{ $isRental ? ($rentOut->booking_type ?: 'Term') : $rentOut->agreement_type?->label() }}</span>
                        <div class="per">{{ $rentOut->totalStay() }} months{{ $isRental ? ' · ' . ($rentOut->free_month ?? 0) . ' free' : '' }}</div>
                    </div>
                </div>

                <div class="dl">
                    <x-rent-out.view.field icon="fa-calendar-o" label="Start Date" :value="$rentOut->start_date?->format('d M Y')" />
                    <x-rent-out.view.field icon="fa-calendar-check-o" label="End Date">
                        {{ $rentOut->end_date?->format('d M Y') ?? '—' }}
                        @if ($rentOut->end_date)
                            <span class="chip {{ $overdue ? 'chip-danger' : 'chip-info' }} ms-1">{{ $overdue ? abs($daysRemaining) . 'd ago' : $daysRemaining . 'd left' }}</span>
                        @endif
                    </x-rent-out.view.field>
                    <x-rent-out.view.field icon="fa-tag" label="{{ $isRental ? 'Booking Type' : 'Agreement Type' }}" :value="$isRental ? $rentOut->booking_type : $rentOut->agreement_type?->label()" />
                    <x-rent-out.view.field icon="fa-user" label="Salesman" :value="$rentOut->salesman?->name" />
                    <x-rent-out.view.field icon="fa-hourglass-half" label="Duration" :value="$rentOut->totalStay() . ' months'" />
                    @if ($isRental)
                        <x-rent-out.view.field icon="fa-gift" label="Free Months" :value="(string) ($rentOut->free_month ?? 0)" />
                    @endif
                    <x-rent-out.view.field icon="fa-lock" label="Security Amount" :value="number_format($securityTotal, 2)" />
                </div>

                <div class="fin-grid fin-grid-2 mt-3">
                    <x-rent-out.view.fin label="Total" tone="total" :value="number_format($totalRent, 2)" />
                    <x-rent-out.view.fin label="Discount" :value="number_format($totalDiscount, 2)" />
                </div>
            </x-rent-out.view.panel>
        </div>

        {{-- Collection Info --}}
        <div class="col-lg-4">
            <x-rent-out.view.panel icon="fa-money" title="Collection Info" sub="Schedule & breakdown">
                <x-slot:tools><span class="chip chip-soft">{{ $paidMonths }} / {{ $totalMonths }} paid</span></x-slot>

                <div class="d-flex gap-2 mb-3 flex-wrap">
                    <x-rent-out.view.fin label="Frequency" fill value-size="14px">
                        <i class="fa fa-repeat me-1" style="color:var(--brand-600)"></i>{{ $rentOut->payment_frequency ?: '—' }}
                    </x-rent-out.view.fin>
                    <x-rent-out.view.fin label="Start Day" fill value-size="14px" :value="(string) ($rentOut->collection_starting_day ?? '—')" />
                    <x-rent-out.view.fin label="Mode" fill value-size="14px">
                        <i class="fa fa-money me-1" style="color:var(--success)"></i>{{ $rentOut->collection_payment_mode?->label() ?: '—' }}
                    </x-rent-out.view.fin>
                </div>

                @if ($rentOut->collection_payment_mode?->value === 'cheque')
                    <div class="d-flex gap-2 mb-3 flex-wrap">
                        <x-rent-out.view.fin label="Bank" fill value-size="13px" :value="$rentOut->collection_bank_name ?: '—'" />
                        <x-rent-out.view.fin label="Cheque Start No." fill value-size="13px" :value="$rentOut->collection_cheque_no ?: '—'" />
                    </div>
                @endif

                <div class="section-eyebrow mb-2">Breakdown</div>
                <table class="mini-table">
                    <thead>
                        <tr><th>Component</th><th class="text-end">Paid</th><th class="text-end">Pending</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="ico-cell"><span class="b" style="background:var(--info-bg);color:var(--info)"><i class="fa fa-home"></i></span> {{ $isRental ? 'Rent' : 'Sale' }}</span></td>
                            <td class="text-end t-paid">{{ number_format($totalPaid, 2) }}</td>
                            <td class="text-end t-pend">{{ number_format($totalPending, 2) }}</td>
                        </tr>
                        @if ($isRental)
                            <tr>
                                <td><span class="ico-cell"><span class="b" style="background:var(--warning-bg);color:var(--warning)"><i class="fa fa-bolt"></i></span> Utilities</span></td>
                                <td class="text-end t-paid">{{ number_format($utilitiesPaid, 2) }}</td>
                                <td class="text-end t-pend">{{ number_format($utilitiesPending, 2) }}</td>
                            </tr>
                        @endif
                        @if ($rentOut->management_fee > 0)
                            <tr>
                                <td><span class="ico-cell"><span class="b" style="background:var(--purple-bg);color:var(--purple)"><i class="fa fa-briefcase"></i></span> Mgmt Fee</span></td>
                                <td class="text-end t-paid">{{ number_format($rentOut->management_fee, 2) }}</td>
                                <td class="text-end">—</td>
                            </tr>
                        @endif
                    </tbody>
                </table>

                @if ($grandPending > 0)
                    <div class="alert-pending mt-3"><i class="fa fa-exclamation-triangle me-1"></i> {{ number_format($grandPending, 2) }} total pending{{ $isRental ? ' across rent & utilities' : '' }}</div>
                @endif
            </x-rent-out.view.panel>
        </div>
    </section>

    {{-- REMARKS --}}
    @if (trim($rentOut->remark ?? ''))
        <section class="mt-3">
            <div class="panel panel-pad d-flex align-items-start gap-3">
                <span class="ph-ic" style="background:var(--warning-bg); color:var(--warning);"><i class="fa fa-comment-o"></i></span>
                <div>
                    <div class="section-eyebrow mb-1" style="color:var(--warning);">Remarks</div>
                    <div style="font-size:13px; color:var(--text-2);">{{ $rentOut->remark }}</div>
                </div>
            </div>
        </section>
    @endif

    {{-- ════════════════════════════  MANAGEMENT TABS  ════════════════════════════ --}}
    <section class="mt-4">
        @include('livewire.rent-out.partials.management-tabs')
    </section>

    {{-- ════════════════════════════  MODALS  ════════════════════════════ --}}
    <x-rent-out.view.soa-modal id="SOAStatementModal" formId="SOAStatementForm" title="SOA Statement" icon="fa-calendar"
        :route="route('print::rentout::statement', $rentOut->id)" hint="Select the date range for the SOA Statement." />

    @if ($isRental)
        <x-rent-out.view.soa-modal id="SOAUtilitiesModal" formId="SOAUtilitiesForm" title="SOA Utilities" icon="fa-bolt"
            :route="route('print::rentout::utilities-statement', $rentOut->id)" hint="Select the date range for the Utilities SOA." />
    @endif

    {{-- VACATE MODAL --}}
    @if ($showVacateModal)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,.45);">
            <div class="modal-dialog modal-sm modal-dialog-centered">
                <div class="modal-content shadow-lg">
                    <div class="modal-header rv-modal-header border-0 py-2 px-3">
                        <h6 class="modal-title text-white fw-bold mb-0 rv-modal-title"><i class="fa fa-sign-out me-1"></i> Vacate</h6>
                        <button type="button" class="btn-close btn-close-white" wire:click="$set('showVacateModal', false)"></button>
                    </div>
                    <div class="modal-body px-3 py-3">
                        <div class="mb-2">
                            <label for="vacateDate" class="form-label">Vacate Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control form-control-sm @error('vacateDate') is-invalid @enderror"
                                id="vacateDate" wire:model="vacateDate"
                                min="{{ $rentOut->start_date->format('Y-m-d') }}" max="{{ $rentOut->end_date->format('Y-m-d') }}">
                            @error('vacateDate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        @if ($rentOut->start_date && $rentOut->end_date)
                            <small class="text-muted"><i class="fa fa-info-circle me-1"></i>{{ $rentOut->start_date->format('d M Y') }} — {{ $rentOut->end_date->format('d M Y') }}</small>
                        @endif
                    </div>
                    <div class="modal-footer border-0 px-3 py-2">
                        <button type="button" class="btn btn-sm btn-light" wire:click="$set('showVacateModal', false)"><i class="fa fa-times me-1"></i> Close</button>
                        <button type="button" class="btn btn-sm btn-success" wire:click="saveVacate"
                            onclick="return confirm('Are you sure you want to set/update the vacate date?')"><i class="fa fa-check me-1"></i> Save</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

{{-- Shared SOA form handler + hero progress animation --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.rv-soa-form').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var from = form.querySelector('.rv-soa-from').value;
                var to = form.querySelector('.rv-soa-to').value;
                if (!from || !to) { alert('Please select both dates.'); return; }
                if (from > to) { alert('From date cannot be after to date.'); return; }
                window.open(form.dataset.soaRoute + '/' + from + '/' + to, '_blank');
                var modalEl = document.getElementById(form.dataset.soaModal);
                var modal = modalEl ? bootstrap.Modal.getInstance(modalEl) : null;
                if (modal) modal.hide();
            });
        });

        window.requestAnimationFrame(function () {
            document.querySelectorAll('.rvx .hero-fill').forEach(function (fill) {
                var pct = parseFloat(fill.getAttribute('data-fill')) || 0;
                if (pct > 0) { fill.classList.remove('empty'); fill.style.width = pct + '%'; }
            });
        });
    });
</script>

{{--
    Common Agreement View Partial
    Variables required from parent:
      - $rentOut         : the RentOut model
      - $indexRoute      : route name for breadcrumb index link
      - $indexLabel      : breadcrumb index link label
      - $editPermission  : gate/can permission string
      - $editRoute       : route name for edit (agreement create)
      - $bookingRoute    : route name for edit (booking create)
--}}
@php
    $isRental         = $rentOut->agreement_type?->value === 'rental';
    $title            = $isRental ? 'Rental Agreement' : 'Sale Agreement';
    $accentColor      = $isRental ? '#0891b2' : '#24447f';
    $accentDark       = $isRental ? '#0e7490' : '#1b3460';
    $accentDeep       = $isRental ? '#164e63' : '#0f1c33';

    $totalRent        = $rentOut->paymentTerms->sum('amount');
    $totalDiscount    = $rentOut->paymentTerms->sum('discount');
    $totalPaid        = $rentOut->paymentTerms->where('status', 'paid')->sum('total');
    $totalPending     = $totalRent - $totalDiscount - $totalPaid;
    $paidMonths       = $rentOut->paymentTerms->where('status', 'paid')->count();
    $totalMonths      = $rentOut->paymentTerms->count();
    $securityTotal    = $rentOut->securities->sum('amount');
    $utilitiesPaid    = $rentOut->utilityTerms->sum('amount') - $rentOut->utilityTerms->sum('balance');
    $utilitiesPending = $rentOut->utilityTerms->sum('balance');
    $daysRemaining    = round(now()->diffInDays($rentOut->end_date, false));
    $net              = max($totalRent - $totalDiscount, 0);
    $paidPercent      = $net > 0 ? min(round(($totalPaid / $net) * 100), 100) : 0;
@endphp

{{-- ═══════════════════ PAGE HEADER ═══════════════════ --}}
<div class="rounded-3 p-4 mb-4 text-white position-relative overflow-hidden"
     style="background: linear-gradient(135deg, {{ $accentColor }} 0%, {{ $accentDark }} 60%, {{ $accentDeep }} 100%);">

    {{-- Decorative background icon --}}
    <div class="position-absolute top-0 end-0 opacity-10"
         style="font-size: 10rem; line-height: 1; transform: translate(15%, -20%); pointer-events:none;">
        <i class="fa fa-{{ $isRental ? 'home' : 'handshake-o' }}"></i>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 position-relative">

        {{-- Title & Breadcrumb --}}
        <div>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                <span class="badge px-2 py-1 fw-normal"
                      style="background: rgba(255,255,255,.2); font-size:0.7rem; letter-spacing:0.06em; text-transform:uppercase;">
                    {{ $isRental ? 'Rental' : 'Sale' }}
                </span>
                @if($rentOut->status)
                    <span class="badge bg-{{ $rentOut->status->color() }} px-2 py-1">
                        {{ $rentOut->status->label() }}
                    </span>
                @endif
            </div>
            <h4 class="mb-1 fw-bold lh-1">
                {{ $title }}
                <span class="fw-light opacity-75">#{{ $rentOut->agreement_no }}</span>
            </h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small" style="--bs-breadcrumb-divider-color: rgba(255,255,255,.4);">
                    <li class="breadcrumb-item">
                        <a href="{{ route($indexRoute) }}" class="text-white-50 text-decoration-none">
                            <i class="fa fa-home"></i>
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route($indexRoute) }}" class="text-white-50 text-decoration-none">
                            {{ $indexLabel }}
                        </a>
                    </li>
                    <li class="breadcrumb-item active text-white">{{ $rentOut->agreement_no }}</li>
                </ol>
            </nav>
        </div>

        {{-- Action Buttons --}}
        <div class="d-flex flex-wrap align-items-center gap-2">
            <a href="{{ route('print::rentout::statement', $rentOut->id) }}" target="_blank"
               class="btn btn-sm border-white border-opacity-50 text-white"
               style="background: rgba(255,255,255,.15);">
                <i class="fa fa-print me-1"></i> SOA
            </a>
            @if($isRental)
                <a href="{{ route('print::rentout::utilities-statement', $rentOut->id) }}" target="_blank"
                   class="btn btn-sm border-white border-opacity-50 text-white"
                   style="background: rgba(255,255,255,.15);">
                    <i class="fa fa-bolt me-1"></i> Utilities SOA
                </a>
            @endif
            @can($editPermission)
                @if($rentOut->status?->value === 'booked')
                    <a href="{{ route($bookingRoute, $rentOut->id) }}" class="btn btn-sm btn-warning fw-semibold">
                        <i class="fa fa-pencil me-1"></i> Edit
                    </a>
                @else
                    <a href="{{ route($editRoute, $rentOut->id) }}" class="btn btn-sm btn-warning fw-semibold">
                        <i class="fa fa-pencil me-1"></i> Edit
                    </a>
                @endif
            @endcan
        </div>
    </div>

    {{-- Payment Progress Bar --}}
    <div class="mt-3 pt-2 position-relative">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <small style="opacity:.75;"><i class="fa fa-pie-chart me-1"></i>Payment Progress</small>
            <small style="opacity:.75;" class="fw-bold">{{ $paidPercent }}% collected</small>
        </div>
        <div class="progress" style="height:6px; background:rgba(255,255,255,.2); border-radius:10px;">
            <div class="progress-bar"
                 style="background: linear-gradient(90deg, #fbbf24, #10b981, #059669); width:{{ $paidPercent }}%; border-radius:10px;"
                 role="progressbar"
                 aria-valuenow="{{ $paidPercent }}" aria-valuemin="0" aria-valuemax="100">
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════ STAT PILLS ═══════════════════ --}}
<div class="row g-3 mb-4">
    @php
        $daysColor   = $daysRemaining > 0 ? '#059669' : '#dc2626';
        $daysBorder  = $daysRemaining > 0 ? '#059669' : '#dc2626';
        $daysBg      = $daysRemaining > 0 ? '#ecfdf5' : '#fef2f2';
        $daysIconBg  = $daysRemaining > 0 ? 'rgba(5,150,105,.12)' : 'rgba(220,38,38,.12)';
    @endphp
    <div class="col-6 col-md-3">
        <div class="card border-0 text-center py-3 h-100"
             style="background: {{ $daysBg }}; border-left: 3px solid {{ $daysBorder }} !important; box-shadow: 0 2px 8px rgba(0,0,0,.06); border-radius: 8px;">
            <div class="d-flex justify-content-center mb-2">
                <span class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width:32px; height:32px; background:{{ $daysIconBg }};">
                    <i class="fa fa-calendar" style="color:{{ $daysColor }}; font-size:.85rem;"></i>
                </span>
            </div>
            <div class="small mb-1" style="color:#64748b;">Days {{ $daysRemaining > 0 ? 'Remaining' : 'Overdue' }}</div>
            <div class="fw-bold fs-3" style="color:{{ $daysColor }};">{{ abs($daysRemaining) }}</div>
            <div class="small" style="color:#94a3b8;">{{ $daysRemaining > 0 ? 'days left' : 'expired' }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 text-center py-3 h-100"
             style="background: #eef2f9; border-left: 3px solid #24447f !important; box-shadow: 0 2px 8px rgba(0,0,0,.06); border-radius: 8px;">
            <div class="d-flex justify-content-center mb-2">
                <span class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width:32px; height:32px; background:rgba(36,68,127,.12);">
                    <i class="fa fa-check-square-o" style="color:#24447f; font-size:.85rem;"></i>
                </span>
            </div>
            <div class="small mb-1" style="color:#64748b;">Paid Instalments</div>
            <div class="fw-bold fs-3" style="color:#24447f;">{{ $paidMonths }}</div>
            <div class="small" style="color:#94a3b8;">of {{ $totalMonths }} total</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 text-center py-3 h-100"
             style="background: #ecfeff; border-left: 3px solid #0891b2 !important; box-shadow: 0 2px 8px rgba(0,0,0,.06); border-radius: 8px;">
            <div class="d-flex justify-content-center mb-2">
                <span class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width:32px; height:32px; background:rgba(8,145,178,.12);">
                    <i class="fa fa-check-circle" style="color:#0891b2; font-size:.85rem;"></i>
                </span>
            </div>
            <div class="small mb-1" style="color:#64748b;">Collected</div>
            <div class="fw-bold fs-5" style="color:#0e7490;">{{ number_format($totalPaid, 2) }}</div>
            <div class="small" style="color:#94a3b8;">received</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 text-center py-3 h-100"
             style="background: #fffbeb; border-left: 3px solid #f59e0b !important; box-shadow: 0 2px 8px rgba(0,0,0,.06); border-radius: 8px;">
            <div class="d-flex justify-content-center mb-2">
                <span class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width:32px; height:32px; background:rgba(245,158,11,.12);">
                    <i class="fa fa-clock-o" style="color:#d97706; font-size:.85rem;"></i>
                </span>
            </div>
            <div class="small mb-1" style="color:#64748b;">Outstanding</div>
            <div class="fw-bold fs-5" style="color:#d97706;">{{ number_format($totalPending, 2) }}</div>
            <div class="small" style="color:#94a3b8;">balance due</div>
        </div>
    </div>
</div>

{{-- ═══════════════════ 3-COLUMN INFO CARDS ═══════════════════ --}}
<div class="row g-3 mb-4">

    {{-- ── Column 1: Property & Customer ── --}}
    <div class="col-lg-4">
        <div class="card border-0 h-100" style="box-shadow: 0 2px 12px rgba(0,0,0,.08); border-radius: 8px; overflow:hidden;">
            <div class="card-header py-2 px-3 text-white fw-semibold small position-relative overflow-hidden"
                 style="background: linear-gradient(135deg, #3b6cb5, #24447f); border:none;">
                <i class="fa fa-building me-2"></i>Property & Customer
                <i class="fa fa-building position-absolute" style="right:12px; top:50%; transform:translateY(-50%); font-size:1.5rem; opacity:.15;"></i>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover align-middle mb-0" style="font-size:.82rem;">
                    <tbody>
                        <tr>
                            <td class="fw-semibold text-muted ps-3 py-2" style="width:42%;">Reference No</td>
                            <td class="py-2 fw-bold" style="color:#24447f;">{{ $rentOut->agreement_no }}</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-muted ps-3 py-2">Group</td>
                            <td class="py-2">{{ $rentOut->group?->name ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-muted ps-3 py-2">Building</td>
                            <td class="py-2">{{ $rentOut->building?->name ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-muted ps-3 py-2">Unit Type</td>
                            <td class="py-2">{{ $rentOut->type?->name ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-muted ps-3 py-2">Unit / Property</td>
                            <td class="py-2 fw-semibold">{{ $rentOut->property?->number ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-muted ps-3 py-2">Customer</td>
                            <td class="py-2">{{ $rentOut->customer?->name ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-muted ps-3 py-2">Vacate Date</td>
                            <td class="py-2">
                                @if($rentOut->vacate_date)
                                    <span class="{{ $rentOut->vacate_date > now() ? 'text-warning' : 'text-muted' }}">
                                        <i class="fa fa-calendar-times-o me-1"></i>
                                        {{ $rentOut->vacate_date->format('d M Y') }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-muted ps-3 py-2">Status</td>
                            <td class="py-2">
                                @if($rentOut->status)
                                    <span class="badge bg-{{ $rentOut->status->color() }}">
                                        {{ $rentOut->status->label() }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── Column 2: Agreement Details ── --}}
    <div class="col-lg-4">
        <div class="card border-0 h-100" style="box-shadow: 0 2px 12px rgba(0,0,0,.08); border-radius: 8px; overflow:hidden;">
            <div class="card-header py-2 px-3 text-white fw-semibold small position-relative overflow-hidden"
                 style="background: linear-gradient(135deg, #0ba5c9, #0e7490); border:none;">
                <i class="fa fa-file-text-o me-2"></i>{{ $title }} Details
                <i class="fa fa-file-text-o position-absolute" style="right:12px; top:50%; transform:translateY(-50%); font-size:1.5rem; opacity:.15;"></i>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover align-middle mb-0" style="font-size:.82rem;">
                    <tbody>
                        <tr>
                            <td class="fw-semibold text-muted ps-3 py-2" style="width:42%;">Start Date</td>
                            <td class="py-2">{{ $rentOut->start_date?->format('d M Y') ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-muted ps-3 py-2">End Date</td>
                            <td class="py-2">
                                {{ $rentOut->end_date?->format('d M Y') ?? '—' }}
                                @if($rentOut->end_date)
                                    <br>
                                    <small class="{{ $daysRemaining > 0 ? 'text-success' : 'text-danger' }}">
                                        <i class="fa fa-{{ $daysRemaining > 0 ? 'clock-o' : 'exclamation-circle' }} me-1"></i>
                                        @if($daysRemaining > 0)
                                            {{ $daysRemaining }} days left
                                        @else
                                            Expired {{ abs($daysRemaining) }} days ago
                                        @endif
                                    </small>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-muted ps-3 py-2">
                                {{ $isRental ? 'Booking Type' : 'Agreement Type' }}
                            </td>
                            <td class="py-2">
                                {{ $isRental ? $rentOut->booking_type : $rentOut->agreement_type?->label() }}
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-muted ps-3 py-2">Salesman</td>
                            <td class="py-2">{{ $rentOut->salesman?->name ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-muted ps-3 py-2">Duration</td>
                            <td class="py-2">
                                <span class="badge bg-secondary">{{ $rentOut->totalStay() }} months</span>
                            </td>
                        </tr>
                        @if($isRental)
                        <tr>
                            <td class="fw-semibold text-muted ps-3 py-2">Free Months</td>
                            <td class="py-2">
                                <span class="badge bg-info text-white">{{ $rentOut->free_month ?? 0 }}</span>
                            </td>
                        </tr>
                        @endif
                        <tr>
                            <td class="fw-semibold text-muted ps-3 py-2">
                                {{ $isRental ? 'Monthly Rent' : 'Sale Price' }}
                            </td>
                            <td class="py-2 fw-bold" style="color: var(--color-success, #059669);">{{ number_format($rentOut->rent, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-muted ps-3 py-2">Security Amount</td>
                            <td class="py-2 fw-bold" style="color:#0e7490;">{{ number_format($securityTotal, 2) }}</td>
                        </tr>
                    </tbody>
                </table>

                {{-- Financial mini-summary --}}
                <div class="border-top p-3" style="background:#f8f9fa;">
                    <div class="small fw-semibold text-muted mb-2">
                        <i class="fa fa-bar-chart me-1"></i> Financial Summary
                    </div>
                    <div class="row g-2 text-center">
                        <div class="col-4">
                            <div class="rounded py-2 px-1" style="background:#ecfeff; border:1px solid #a5f3fc;">
                                <div class="text-muted" style="font-size:.68rem;">Total</div>
                                <div class="fw-bold small" style="color:#0e7490;">{{ number_format($totalRent, 2) }}</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="rounded py-2 px-1" style="background: var(--color-warning-bg, #fffbeb); border:1px solid #f5d98a;">
                                <div class="text-muted" style="font-size:.68rem;">Discount</div>
                                <div class="fw-bold small" style="color: var(--color-warning, #d97706);">{{ number_format($totalDiscount, 2) }}</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="rounded py-2 px-1" style="background: var(--color-success-bg, #ecfdf5); border:1px solid #86dbb5;">
                                <div class="text-muted" style="font-size:.68rem;">Paid</div>
                                <div class="fw-bold small" style="color: var(--color-success, #059669);">{{ number_format($totalPaid, 2) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Column 3: Collection & Payment Status ── --}}
    <div class="col-lg-4">
        <div class="card border-0 h-100" style="box-shadow: 0 2px 12px rgba(0,0,0,.08); border-radius: 8px; overflow:hidden;">
            <div class="card-header py-2 px-3 text-white fw-semibold small position-relative overflow-hidden"
                 style="background: linear-gradient(135deg, #10b981, #047857); border:none;">
                <i class="fa fa-money me-2"></i>Collection Information
                <i class="fa fa-money position-absolute" style="right:12px; top:50%; transform:translateY(-50%); font-size:1.5rem; opacity:.15;"></i>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover align-middle mb-0" style="font-size:.82rem;">
                    <tbody>
                        <tr>
                            <td class="fw-semibold text-muted ps-3 py-2" style="width:48%;">Frequency</td>
                            <td class="py-2">{{ $rentOut->payment_frequency ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-muted ps-3 py-2">Starting Day</td>
                            <td class="py-2">{{ $rentOut->collection_starting_day ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-muted ps-3 py-2">Payment Mode</td>
                            <td class="py-2">{{ $rentOut->collection_payment_mode?->label() ?? '—' }}</td>
                        </tr>
                        @if($rentOut->collection_payment_mode?->value === 'cheque')
                        <tr>
                            <td class="fw-semibold text-muted ps-3 py-2">Bank Name</td>
                            <td class="py-2">{{ $rentOut->collection_bank_name ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-muted ps-3 py-2">Cheque Start No.</td>
                            <td class="py-2">{{ $rentOut->collection_cheque_no ?? '—' }}</td>
                        </tr>
                        @endif
                    </tbody>
                </table>

                {{-- Payment Breakdown Table --}}
                <div class="border-top p-3" style="background:#f8f9fa;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small fw-semibold text-muted">
                            <i class="fa fa-list me-1"></i> Payment Breakdown
                        </span>
                        <span class="badge rounded-pill" style="background: linear-gradient(135deg, #0891b2, #0e7490);">{{ $paidMonths }}/{{ $totalMonths }} paid</span>
                    </div>
                    <table class="table table-sm table-bordered mb-0" style="font-size:.78rem;">
                        <thead>
                            <tr style="background: linear-gradient(135deg, #0891b2, #0e7490); color: #ffffff;">
                                <th class="py-1 px-2 fw-semibold border-0">Type</th>
                                <th class="py-1 px-2 text-end fw-semibold border-0">Paid</th>
                                <th class="py-1 px-2 text-end fw-semibold border-0">Pending</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="py-1 px-2 fw-semibold">
                                    <i class="fa fa-{{ $isRental ? 'home' : 'tag' }} me-1 text-muted"></i>
                                    {{ $isRental ? 'Rent' : 'Sale' }}
                                </td>
                                <td class="py-1 px-2 text-end fw-bold" style="color: var(--color-success, #059669);">{{ number_format($totalPaid, 2) }}</td>
                                <td class="py-1 px-2 text-end fw-bold" style="color: var(--color-error, #dc2626);">{{ number_format($totalPending, 2) }}</td>
                            </tr>
                            @if($isRental)
                            <tr>
                                <td class="py-1 px-2 fw-semibold">
                                    <i class="fa fa-bolt me-1 text-muted"></i> Utilities
                                </td>
                                <td class="py-1 px-2 text-end fw-bold" style="color: var(--color-success, #059669);">{{ number_format($utilitiesPaid, 2) }}</td>
                                <td class="py-1 px-2 text-end fw-bold" style="color: var(--color-error, #dc2626);">{{ number_format($utilitiesPending, 2) }}</td>
                            </tr>
                            @endif
                            @if($rentOut->management_fee > 0)
                            <tr>
                                <td class="py-1 px-2 fw-semibold">
                                    <i class="fa fa-briefcase me-1 text-muted"></i> Mgmt Fee
                                </td>
                                <td class="py-1 px-2 text-end fw-bold" style="color: var(--color-success, #059669);">{{ number_format($rentOut->management_fee, 2) }}</td>
                                <td class="py-1 px-2 text-end fw-bold">—</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════ REMARKS ═══════════════════ --}}
@if(trim($rentOut->remark ?? ''))
    <div class="card border-0 shadow-sm mb-4" style="border-left: 4px solid #ffc107 !important;">
        <div class="card-header bg-white py-2 border-bottom d-flex align-items-center gap-2">
            <span class="badge bg-warning text-dark"><i class="fa fa-comment-o"></i></span>
            <h6 class="mb-0 fw-semibold">Remarks</h6>
        </div>
        <div class="card-body py-3 text-secondary">
            {{ $rentOut->remark }}
        </div>
    </div>
@endif

{{-- ═══════════════════ MANAGEMENT TABS ═══════════════════ --}}
@include('livewire.rent-out.partials.management-tabs')

{{-- Modals are included in the main blade file (property/sale/view or property/rent/view) --}}

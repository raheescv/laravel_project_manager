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
                        {{ $rentOut->status->label() }} | {{ $rentOut->booking_status }}
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
            @if(!($isBooking ?? false))
                <button type="button"
                   class="btn btn-sm border-white border-opacity-50 text-white"
                   style="background: rgba(255,255,255,.15);"
                   data-bs-toggle="modal" data-bs-target="#SOAStatementModal">
                    <i class="fa fa-print me-1"></i> SOA
                </button>
                @if($isRental)
                    <button type="button"
                       class="btn btn-sm border-white border-opacity-50 text-white"
                       style="background: rgba(255,255,255,.15);"
                       data-bs-toggle="modal" data-bs-target="#SOAUtilitiesModal">
                        <i class="fa fa-bolt me-1"></i> Utilities SOA
                    </button>
                @endif
            @else
                <a href="{{ route('print::rentout::reservation-form', $rentOut->id) }}" target="_blank"
                   class="btn btn-sm border-white border-opacity-50 text-white"
                   style="background: rgba(255,255,255,.15);">
                    <i class="fa fa-file-text me-1"></i> Reservation Form
                </a>
                @if($isRental)
                    <a href="{{ route('print::rentout::residential-lease', $rentOut->id) }}" target="_blank"
                       class="btn btn-sm border-white border-opacity-50 text-white"
                       style="background: rgba(255,255,255,.15);">
                        <i class="fa fa-file-contract me-1"></i> Residential Lease
                    </a>
                @else
                    <a href="{{ route('print::rentout::residential-lease', $rentOut->id) }}" target="_blank"
                       class="btn btn-sm border-white border-opacity-50 text-white"
                       style="background: rgba(255,255,255,.15);">
                        <i class="fa fa-file-contract me-1"></i> Sales Agreement
                    </a>
                @endif
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
    {{-- Days Remaining / Overdue --}}
    <div class="col-6 col-md-3">
        <div class="card border-0 py-3 px-3 h-100"
             style="background: {{ $daysBg }}; border-left: 4px solid {{ $daysBorder }} !important; box-shadow: 0 4px 14px rgba(0,0,0,.07); border-radius: 12px; transition: transform .2s, box-shadow .2s;"
             onmouseenter="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,.12)';"
             onmouseleave="this.style.transform='translateY(0)';this.style.boxShadow='0 4px 14px rgba(0,0,0,.07)';">
            <div class="d-flex align-items-center gap-3">
                <span class="d-inline-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
                      style="width:48px; height:48px; background:{{ $daysIconBg }}; box-shadow: 0 2px 8px {{ $daysRemaining > 0 ? 'rgba(5,150,105,.2)' : 'rgba(220,38,38,.2)' }};">
                    <i class="fa fa-calendar" style="color:{{ $daysColor }}; font-size:1.1rem;"></i>
                </span>
                <div>
                    <div class="text-uppercase fw-semibold" style="color:#64748b; font-size:.65rem; letter-spacing:.06em;">Days {{ $daysRemaining > 0 ? 'Remaining' : 'Overdue' }}</div>
                    <div class="fw-bold" style="color:{{ $daysColor }}; font-size:1.65rem; line-height:1.2;">{{ abs($daysRemaining) }}</div>
                    <div style="color:#94a3b8; font-size:.72rem;">{{ $daysRemaining > 0 ? 'days left' : 'expired' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Paid Instalments --}}
    <div class="col-6 col-md-3">
        <div class="card border-0 py-3 px-3 h-100"
             style="background: #eef2f9; border-left: 4px solid #24447f !important; box-shadow: 0 4px 14px rgba(0,0,0,.07); border-radius: 12px; transition: transform .2s, box-shadow .2s;"
             onmouseenter="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,.12)';"
             onmouseleave="this.style.transform='translateY(0)';this.style.boxShadow='0 4px 14px rgba(0,0,0,.07)';">
            <div class="d-flex align-items-center gap-3">
                <span class="d-inline-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
                      style="width:48px; height:48px; background:rgba(36,68,127,.12); box-shadow: 0 2px 8px rgba(36,68,127,.2);">
                    <i class="fa fa-check-square-o" style="color:#24447f; font-size:1.1rem;"></i>
                </span>
                <div>
                    <div class="text-uppercase fw-semibold" style="color:#64748b; font-size:.65rem; letter-spacing:.06em;">Paid Instalments</div>
                    <div class="fw-bold" style="color:#24447f; font-size:1.65rem; line-height:1.2;">{{ $paidMonths }}</div>
                    <div style="color:#94a3b8; font-size:.72rem;">of {{ $totalMonths }} total</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Collected --}}
    <div class="col-6 col-md-3">
        <div class="card border-0 py-3 px-3 h-100"
             style="background: #ecfeff; border-left: 4px solid #0891b2 !important; box-shadow: 0 4px 14px rgba(0,0,0,.07); border-radius: 12px; transition: transform .2s, box-shadow .2s;"
             onmouseenter="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,.12)';"
             onmouseleave="this.style.transform='translateY(0)';this.style.boxShadow='0 4px 14px rgba(0,0,0,.07)';">
            <div class="d-flex align-items-center gap-3">
                <span class="d-inline-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
                      style="width:48px; height:48px; background:rgba(8,145,178,.12); box-shadow: 0 2px 8px rgba(8,145,178,.2);">
                    <i class="fa fa-check-circle" style="color:#0891b2; font-size:1.1rem;"></i>
                </span>
                <div>
                    <div class="text-uppercase fw-semibold" style="color:#64748b; font-size:.65rem; letter-spacing:.06em;">Collected</div>
                    <div class="fw-bold" style="color:#0e7490; font-size:1.65rem; line-height:1.2;">{{ number_format($totalPaid, 2) }}</div>
                    <div style="color:#94a3b8; font-size:.72rem;">received</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Outstanding --}}
    <div class="col-6 col-md-3">
        <div class="card border-0 py-3 px-3 h-100"
             style="background: #fffbeb; border-left: 4px solid #f59e0b !important; box-shadow: 0 4px 14px rgba(0,0,0,.07); border-radius: 12px; transition: transform .2s, box-shadow .2s;"
             onmouseenter="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,.12)';"
             onmouseleave="this.style.transform='translateY(0)';this.style.boxShadow='0 4px 14px rgba(0,0,0,.07)';">
            <div class="d-flex align-items-center gap-3">
                <span class="d-inline-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
                      style="width:48px; height:48px; background:rgba(245,158,11,.12); box-shadow: 0 2px 8px rgba(245,158,11,.2);">
                    <i class="fa fa-clock-o" style="color:#d97706; font-size:1.1rem;"></i>
                </span>
                <div>
                    <div class="text-uppercase fw-semibold" style="color:#64748b; font-size:.65rem; letter-spacing:.06em;">Outstanding</div>
                    <div class="fw-bold" style="color:#d97706; font-size:1.65rem; line-height:1.2;">{{ number_format($totalPending, 2) }}</div>
                    <div style="color:#94a3b8; font-size:.72rem;">balance due</div>
                </div>
            </div>
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

{{-- ═══════════════════ BOOKING STATUS WORKFLOW ═══════════════════ --}}
@if(($isBooking ?? false) && $rentOut->booking_status)
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 8px; overflow: hidden;">
        <div class="card-header py-2 px-3 text-white fw-semibold small position-relative overflow-hidden"
             style="background: linear-gradient(135deg, #8b5cf6, #6d28d9); border: none;">
            <i class="fa fa-check-circle me-2"></i>Booking Status: {{ ucwords($rentOut->booking_status) }}
            <i class="fa fa-check-circle position-absolute" style="right:12px; top:50%; transform:translateY(-50%); font-size:1.5rem; opacity:.15;"></i>
        </div>
        <div class="card-body p-3">
            @php
                $steps = [
                    ['label' => 'Created By', 'user' => $rentOut->createdBy, 'date' => $rentOut->created_at],
                    ['label' => 'Submitted By', 'user' => $rentOut->submittedBy, 'date' => $rentOut->submitted_at],
                    ['label' => 'Financial Approved By', 'user' => $rentOut->financialApprovedBy, 'date' => $rentOut->financial_approved_at],
                    ['label' => 'Legal Approved By', 'user' => $rentOut->approvedBy, 'date' => $rentOut->approved_at],
                    ['label' => 'Completed By', 'user' => $rentOut->completedBy, 'date' => $rentOut->completed_at],
                ];
            @endphp

            <div class="d-flex flex-column gap-1">
                @foreach($steps as $step)
                    <div class="d-flex align-items-center py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div class="me-3 text-center" style="width: 28px;">
                            @if($step['user'])
                                <i class="fa fa-check-circle" style="color: #22c55e; font-size: 1.1rem;"></i>
                            @else
                                <i class="fa fa-circle-o" style="color: #94a3b8; font-size: 1.1rem;"></i>
                            @endif
                        </div>
                        <div class="flex-grow-1 d-flex justify-content-between align-items-center">
                            <div>
                                <span class="fw-semibold small" style="color: {{ $step['user'] ? '#1e293b' : '#94a3b8' }};">
                                    {{ $step['label'] }}: {{ $step['user']?->name ?? 'Pending' }}
                                </span>
                            </div>
                            @if($step['date'])
                                <small class="text-muted">{{ $step['date']->format('d M Y h:i A') }}</small>
                            @endif
                        </div>
                    </div>
                @endforeach

                @if($rentOut->status?->value === 'cancelled')
                    <div class="d-flex align-items-center py-2 border-top">
                        <div class="me-3 text-center" style="width: 28px;">
                            <i class="fa fa-times-circle" style="color: #ef4444; font-size: 1.1rem;"></i>
                        </div>
                        <div class="flex-grow-1">
                            <span class="fw-semibold small text-danger">
                                Cancelled
                            </span>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif

{{-- ═══════════════════ MANAGEMENT FEE CONFIGURATION (Submitted Booking) ═══════════════════ --}}
@if(($isBooking ?? false) && $rentOut->booking_status === 'submitted')
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 8px; overflow: hidden;">
        <div class="card-header py-2 px-3 text-white fw-semibold small position-relative overflow-hidden"
             style="background: linear-gradient(135deg, #8b5cf6, #6d28d9); border: none;">
            <i class="fa fa-credit-card me-2"></i>Management Fee Configuration
        </div>
        <div class="card-body p-3">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Payment Mode</label>
                    <select wire:model="management_fee_payment_mode" class="form-select form-select-sm">
                        <option value="">Select...</option>
                        @foreach(\App\Enums\RentOut\PaymentMode::cases() as $mode)
                            <option value="{{ $mode->value }}">{{ $mode->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Management Fee</label>
                    <input type="number" wire:model="management_fee" class="form-control form-control-sm" step="any" min="0">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Remarks</label>
                    <input type="text" wire:model="management_fee_remarks" class="form-control form-control-sm">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" wire:click="saveManagementFee" class="btn btn-sm btn-primary w-100">
                        <i class="fa fa-save me-1"></i> Save
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- ═══════════════════ MANAGEMENT TABS ═══════════════════ --}}
@if(!($isBooking ?? false) || $rentOut->booking_status !== 'submitted')
    @include('livewire.rent-out.partials.management-tabs')
@endif

{{-- ═══════════════════ BOOKING ACTION BUTTONS ═══════════════════ --}}
@if(($isBooking ?? false) && $rentOut->status?->value !== 'cancelled')
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 8px;">
        <div class="card-body py-3">
            <div class="d-flex flex-wrap align-items-center gap-2">
                {{-- Status Advance Buttons --}}
                @if($rentOut->booking_status !== 'completed')
                    @switch($rentOut->booking_status)
                        @case('submitted')
                            @if(!$rentOut->financial_approved_by)
                                @can($config->bookingFinancialApprovePermission)
                                    <button type="button" wire:click="statusChange('financial approved')"
                                            wire:confirm="Are you sure you want to financially approve this booking?"
                                            class="btn btn-info btn-sm">
                                        <i class="fa fa-check me-1"></i> Financial Approve
                                    </button>
                                @endcan
                            @endif
                            @break

                        @case('financial approved')
                            @if(!$rentOut->approved_by)
                                @can($config->bookingApprovePermission)
                                    <button type="button" wire:click="statusChange('approved')"
                                            wire:confirm="Are you sure you want to legally approve this booking?"
                                            class="btn btn-info btn-sm">
                                        <i class="fa fa-check me-1"></i> Legal Approve
                                    </button>
                                @endcan
                            @endif
                            @break

                        @case('approved')
                            @if(!$rentOut->completed_by)
                                @can($config->bookingCompletePermission)
                                    <button type="button" wire:click="statusChange('completed')"
                                            wire:confirm="Are you sure you want to complete this booking?"
                                            class="btn btn-info btn-sm">
                                        <i class="fa fa-check me-1"></i> Complete
                                    </button>
                                @endcan
                            @endif
                            @break
                    @endswitch
                @endif

                {{-- Cancel Button --}}
                @if($rentOut->status?->value === 'booked')
                    @can($config->bookingCancelPermission)
                        <button type="button" wire:click="cancelBooking"
                                wire:confirm="Are you sure you want to cancel this booking?"
                                class="btn btn-danger btn-sm">
                            <i class="fa fa-times me-1"></i> Cancel
                        </button>
                    @endcan
                @endif

                {{-- Confirm Button (when workflow is completed) --}}
                @if($rentOut->booking_status === 'completed' && $rentOut->status?->value === 'booked')
                    @can($config->bookingConfirmPermission)
                        <button type="button" wire:click="confirm"
                                wire:confirm="Are you sure you want to confirm this booking? This will convert it to an active agreement."
                                class="btn btn-primary btn-sm">
                            <i class="fa fa-check-circle me-1"></i> Confirm Booking
                        </button>
                    @endcan
                @endif
            </div>
        </div>
    </div>
@endif

{{-- ═══════════════════ OVERLAP CONFIRMATION MODAL ═══════════════════ --}}
@if($isBooking ?? false)
    <div class="modal fade @if($showOverlapModal ?? false) show @endif"
         style="@if($showOverlapModal ?? false) display: block; background: rgba(0,0,0,.5); @else display: none; @endif"
         tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-warning text-dark border-0">
                    <h6 class="modal-title fw-bold">
                        <i class="fa fa-exclamation-triangle me-2"></i>Overlapping Agreements Found
                    </h6>
                    <button type="button" class="btn-close" wire:click="closeOverlapModal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        The following existing agreements overlap with the booking period
                        (<strong>{{ $rentOut->start_date?->format('d M Y') }}</strong> to
                        <strong>{{ $rentOut->end_date?->format('d M Y') }}</strong>)
                        for this property. Do you still want to proceed?
                    </p>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0" style="font-size: .82rem;">
                            <thead style="background: #f8f9fa;">
                                <tr>
                                    <th class="py-2 px-2">ID</th>
                                    <th class="py-2 px-2">Customer</th>
                                    <th class="py-2 px-2">Start Date</th>
                                    <th class="py-2 px-2">End Date</th>
                                    <th class="py-2 px-2">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($overlappingRentouts ?? [] as $overlap)
                                    <tr>
                                        <td class="py-2 px-2">{{ $overlap['id'] }}</td>
                                        <td class="py-2 px-2">{{ $overlap['customer'] }}</td>
                                        <td class="py-2 px-2">{{ $overlap['start_date'] }}</td>
                                        <td class="py-2 px-2">{{ $overlap['end_date'] }}</td>
                                        <td class="py-2 px-2">{{ $overlap['status'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-sm btn-secondary" wire:click="closeOverlapModal">
                        <i class="fa fa-times me-1"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-sm btn-primary" wire:click="confirmBooking">
                        <i class="fa fa-check me-1"></i> Proceed Anyway
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- Modals are included in the main blade file (property/sale/view or property/rent/view) --}}

@if(!($isBooking ?? false))
    {{-- SOA Statement Date Range Modal --}}
    <div class="modal fade" id="SOAStatementModal" tabindex="-1" aria-labelledby="SOAStatementModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="SOAStatementModalLabel">
                        <i class="fa fa-calendar me-1"></i> SOA Statement - Date Range Selection
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="SOAStatementForm">
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle me-1"></i> Please select the date range for the SOA Statement.
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="statement_from_date" class="form-label">
                                        <i class="fa fa-calendar-o me-1"></i> From Date <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" class="form-control" id="statement_from_date" value="{{ date('Y-m-01') }}" required>
                                    <small class="text-muted">Select the start date for the statement</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="statement_to_date" class="form-label">
                                        <i class="fa fa-calendar-o me-1"></i> To Date <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" class="form-control" id="statement_to_date" value="{{ date('Y-m-d') }}" required>
                                    <small class="text-muted">Select the end date for the statement</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fa fa-times me-1"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-print me-1"></i> Generate Statement
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if($isRental)
        {{-- SOA Utilities Date Range Modal --}}
        <div class="modal fade" id="SOAUtilitiesModal" tabindex="-1" aria-labelledby="SOAUtilitiesModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="SOAUtilitiesModalLabel">
                            <i class="fa fa-calendar me-1"></i> SOA Utilities - Date Range Selection
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="SOAUtilitiesForm">
                        <div class="modal-body">
                            <div class="alert alert-info">
                                <i class="fa fa-info-circle me-1"></i> Please select the date range for the SOA Utilities statement.
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="utility_from_date" class="form-label">
                                            <i class="fa fa-calendar-o me-1"></i> From Date <span class="text-danger">*</span>
                                        </label>
                                        <input type="date" class="form-control" id="utility_from_date" value="{{ date('Y-m-01') }}" required>
                                        <small class="text-muted">Select the start date for the statement</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="utility_to_date" class="form-label">
                                            <i class="fa fa-calendar-o me-1"></i> To Date <span class="text-danger">*</span>
                                        </label>
                                        <input type="date" class="form-control" id="utility_to_date" value="{{ date('Y-m-d') }}" required>
                                        <small class="text-muted">Select the end date for the statement</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fa fa-times me-1"></i> Cancel
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-print me-1"></i> Generate Statement
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // SOA Statement Form
            var statementForm = document.getElementById('SOAStatementForm');
            if (statementForm) {
                statementForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    var fromDate = document.getElementById('statement_from_date').value;
                    var toDate = document.getElementById('statement_to_date').value;

                    if (!fromDate || !toDate) {
                        alert('Please select both from and to dates.');
                        return;
                    }
                    if (fromDate > toDate) {
                        alert('From date cannot be greater than to date.');
                        return;
                    }

                    var baseUrl = "{{ route('print::rentout::statement', $rentOut->id) }}";
                    var url = baseUrl + '/' + fromDate + '/' + toDate;
                    window.open(url, '_blank');

                    var modal = bootstrap.Modal.getInstance(document.getElementById('SOAStatementModal'));
                    if (modal) modal.hide();
                });
            }

            // SOA Utilities Form
            var utilitiesForm = document.getElementById('SOAUtilitiesForm');
            if (utilitiesForm) {
                utilitiesForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    var fromDate = document.getElementById('utility_from_date').value;
                    var toDate = document.getElementById('utility_to_date').value;

                    if (!fromDate || !toDate) {
                        alert('Please select both from and to dates.');
                        return;
                    }
                    if (fromDate > toDate) {
                        alert('From date cannot be greater than to date.');
                        return;
                    }

                    var baseUrl = "{{ route('print::rentout::utilities-statement', $rentOut->id) }}";
                    var url = baseUrl + '/' + fromDate + '/' + toDate;
                    window.open(url, '_blank');

                    var modal = bootstrap.Modal.getInstance(document.getElementById('SOAUtilitiesModal'));
                    if (modal) modal.hide();
                });
            }
        });
    </script>
@endif

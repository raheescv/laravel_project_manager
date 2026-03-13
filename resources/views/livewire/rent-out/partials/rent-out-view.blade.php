{{--
    Agreement View Partial (Non-booking)
    Variables required from parent:
      - $rentOut, $indexRoute, $indexLabel, $editPermission, $editRoute, $config
--}}
@php
    $isRental = $rentOut->agreement_type?->value === 'rental';
    $title = $isRental ? 'Rental Agreement' : 'Sale Agreement';
    $accentColor = $isRental ? '#0891b2' : '#4f46e5';
    $accentDark = $isRental ? '#0e7490' : '#3730a3';
    $accentDeep = $isRental ? '#164e63' : '#1e1b4b';

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
@endphp

<style>
    .rv-card { transition: transform .2s, box-shadow .2s; border-radius: 10px !important; overflow: hidden; }
    .rv-card:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,.07) !important; }
    .rv-row { transition: background .15s; }
    .rv-row:hover { background: #f8fafc; }
    .rv-lbl { color: #64748b; font-size: .76rem; }
    .rv-val { color: #1e293b; font-size: .76rem; font-weight: 600; }
    .rv-hdr { padding: .4rem .65rem !important; background: #fff; }
    .rv-hdr-icon { width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .rv-hdr-title { font-size: .8rem; font-weight: 600; color: #1e293b; }
    .rv-ab { border-left: 3px solid #4f46e5 !important; }
    .rv-ae { border-left: 3px solid #0891b2 !important; }
    .rv-ag { border-left: 3px solid #059669 !important; }
    .rv-aa { border-left: 3px solid #f59e0b !important; }
</style>

{{-- HEADER --}}
<div class="rounded-3 px-3 py-2 mb-2 text-white position-relative overflow-hidden"
    style="background: linear-gradient(135deg, {{ $accentColor }}, {{ $accentDark }} 50%, {{ $accentDeep }}); box-shadow: 0 6px 24px rgba(0,0,0,.12);">
    <div class="position-absolute" style="width: 140px; height: 140px; border-radius: 50%; background: rgba(255,255,255,.04); top: -45px; right: -15px;"></div>

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 position-relative">
        <div>
            <div class="d-flex flex-wrap align-items-center gap-1 mb-1">
                <span class="badge px-2 py-1 fw-normal"
                    style="background: rgba(255,255,255,.15); font-size:.65rem; letter-spacing:.05em; text-transform:uppercase; border: 1px solid rgba(255,255,255,.18);">
                    {{ $isRental ? 'Rental' : 'Sale' }}
                </span>
                @if ($rentOut->status)
                    <span class="badge bg-{{ $rentOut->status->color() }} px-2 py-1" style="font-size:.65rem;">
                        {{ $rentOut->status->label() }} | {{ $rentOut->booking_status?->label() }}
                    </span>
                @endif
            </div>
            <h5 class="mb-0 fw-bold" style="font-size: 1.05rem; letter-spacing: -.02em;">
                {{ $title }}
                <span class="fw-light opacity-75">#{{ $rentOut->agreement_no }}</span>
            </h5>
            <nav aria-label="breadcrumb" class="mt-1">
                <ol class="breadcrumb mb-0" style="font-size: .7rem; --bs-breadcrumb-divider-color: rgba(255,255,255,.35);">
                    <li class="breadcrumb-item"><a href="{{ route($indexRoute) }}" class="text-white-50 text-decoration-none"><i class="fa fa-home"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route($indexRoute) }}" class="text-white-50 text-decoration-none">{{ $indexLabel }}</a></li>
                    <li class="breadcrumb-item text-white fw-medium" aria-current="page">{{ $rentOut->agreement_no }}</li>
                </ol>
            </nav>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-1">
            <button type="button" class="btn btn-sm text-white fw-medium px-2 py-1"
                style="font-size: .73rem; background: rgba(255,255,255,.13); border: 1px solid rgba(255,255,255,.22); border-radius: 6px;"
                data-bs-toggle="modal" data-bs-target="#SOAStatementModal">
                <i class="fa fa-print me-1"></i>SOA
            </button>
            @if ($isRental)
                <button type="button" class="btn btn-sm text-white fw-medium px-2 py-1"
                    style="font-size: .73rem; background: rgba(255,255,255,.13); border: 1px solid rgba(255,255,255,.22); border-radius: 6px;"
                    data-bs-toggle="modal" data-bs-target="#SOAUtilitiesModal">
                    <i class="fa fa-bolt me-1"></i>Utilities SOA
                </button>
            @endif
            @if (!in_array($rentOut->status, [\App\Enums\RentOut\RentOutStatus::Vacated, \App\Enums\RentOut\RentOutStatus::Cancelled]))
                <button type="button" class="btn btn-sm text-white fw-medium px-2 py-1"
                    style="font-size: .73rem; background: rgba(255,255,255,.13); border: 1px solid rgba(255,255,255,.22); border-radius: 6px;"
                    wire:click="openVacateModal">
                    <i class="fa fa-sign-out me-1"></i>Vacate
                </button>
            @endif
            @can($editPermission)
                <a href="{{ route($editRoute, $rentOut->id) }}" class="btn btn-sm btn-light fw-medium px-2 py-1" style="font-size: .73rem; border-radius: 6px;">
                    <i class="fa fa-pencil me-1"></i>Edit
                </a>
            @endcan
        </div>
    </div>

    {{-- Progress Bar --}}
    <div class="mt-2 position-relative">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <small style="opacity:.7; font-size: .7rem;"><i class="fa fa-pie-chart me-1"></i>Payment Progress</small>
            <small style="opacity:.7; font-size: .7rem;" class="fw-bold">{{ $paidPercent }}%</small>
        </div>
        <div class="progress" style="height:4px; background:rgba(255,255,255,.15); border-radius:10px;">
            <div class="progress-bar"
                style="background: linear-gradient(90deg, #fbbf24, #10b981, #059669); width:{{ $paidPercent }}%; border-radius:10px;"
                role="progressbar" aria-valuenow="{{ $paidPercent }}" aria-valuemin="0" aria-valuemax="100">
            </div>
        </div>
    </div>
</div>

{{-- STAT PILLS --}}
<div class="row g-2 mb-2">
    @php
        $daysColor = $daysRemaining > 0 ? '#059669' : '#dc2626';
        $daysBg = $daysRemaining > 0 ? '#ecfdf5' : '#fef2f2';
        $daysIconBg = $daysRemaining > 0 ? 'rgba(5,150,105,.1)' : 'rgba(220,38,38,.1)';
        $stats = [
            ['label' => 'Days ' . ($daysRemaining > 0 ? 'Remaining' : 'Overdue'), 'value' => abs($daysRemaining), 'sub' => $daysRemaining > 0 ? 'days left' : 'expired', 'color' => $daysColor, 'bg' => $daysBg, 'iconBg' => $daysIconBg, 'icon' => 'fa-calendar', 'border' => $daysColor],
            ['label' => 'Paid Instalments', 'value' => $paidMonths, 'sub' => 'of ' . $totalMonths . ' total', 'color' => '#4f46e5', 'bg' => '#eef2ff', 'iconBg' => 'rgba(79,70,229,.1)', 'icon' => 'fa-check-square-o', 'border' => '#4f46e5'],
            ['label' => 'Collected', 'value' => number_format($totalPaid, 2), 'sub' => 'received', 'color' => '#0e7490', 'bg' => '#ecfeff', 'iconBg' => 'rgba(8,145,178,.1)', 'icon' => 'fa-check-circle', 'border' => '#0891b2'],
            ['label' => 'Outstanding', 'value' => number_format($totalPending, 2), 'sub' => 'balance due', 'color' => '#d97706', 'bg' => '#fffbeb', 'iconBg' => 'rgba(245,158,11,.1)', 'icon' => 'fa-clock-o', 'border' => '#f59e0b'],
        ];
    @endphp
    @foreach ($stats as $stat)
        <div class="col-6 col-md-3">
            <div class="card border-0 px-2 py-2 h-100 rv-card"
                style="background: {{ $stat['bg'] }}; border-left: 3px solid {{ $stat['border'] }} !important;">
                <div class="d-flex align-items-center gap-2">
                    <div class="rv-hdr-icon flex-shrink-0" style="width: 36px; height: 36px; background: {{ $stat['iconBg'] }};">
                        <i class="fa {{ $stat['icon'] }}" style="color: {{ $stat['color'] }}; font-size: .85rem;"></i>
                    </div>
                    <div>
                        <div class="text-uppercase fw-semibold" style="color: #64748b; font-size: .6rem; letter-spacing: .05em;">{{ $stat['label'] }}</div>
                        <div class="fw-bold" style="color: {{ $stat['color'] }}; font-size: 1.2rem; line-height: 1.2;">{{ $stat['value'] }}</div>
                        <div style="color: #94a3b8; font-size: .65rem;">{{ $stat['sub'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- 3-COLUMN INFO CARDS --}}
<div class="row g-2 mb-2">

    {{-- Property & Customer --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100 rv-card rv-ab">
            <div class="card-header rv-hdr border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <div class="rv-hdr-icon" style="background: #eef2ff;">
                        <i class="fa fa-building" style="color: #4f46e5; font-size: .7rem;"></i>
                    </div>
                    <span class="rv-hdr-title">Property & Customer</span>
                </div>
            </div>
            <div class="card-body p-0">
                @php
                    $propRows = [
                        'Reference No' => ['val' => $rentOut->agreement_no, 'color' => '#4f46e5'],
                        'Group' => ['val' => $rentOut->group?->name],
                        'Building' => ['val' => $rentOut->building?->name],
                        'Unit Type' => ['val' => $rentOut->type?->name],
                        'Unit / Property' => ['val' => $rentOut->property?->number],
                        'Customer' => ['val' => $rentOut->customer?->name],
                    ];
                @endphp
                @foreach ($propRows as $label => $info)
                    <div class="d-flex justify-content-between align-items-center px-2 py-1 rv-row border-bottom">
                        <span class="rv-lbl">{{ $label }}</span>
                        <span class="rv-val text-end" @if (!empty($info['color'])) style="color: {{ $info['color'] }};" @endif>{{ $info['val'] ?? '—' }}</span>
                    </div>
                @endforeach

                <div class="d-flex justify-content-between align-items-center px-2 py-1 rv-row border-bottom">
                    <span class="rv-lbl">Vacate Date</span>
                    <span class="rv-val text-end">
                        @if ($rentOut->vacate_date)
                            <span class="{{ $rentOut->vacate_date > now() ? 'text-warning' : 'text-muted' }}">
                                {{ $rentOut->vacate_date->format('d M Y') }}
                            </span>
                            @if ($rentOut->vacate_date > now() && !in_array($rentOut->status, [\App\Enums\RentOut\RentOutStatus::Vacated, \App\Enums\RentOut\RentOutStatus::Cancelled]))
                                <button type="button" class="btn btn-sm btn-link p-0 ms-1" wire:click="openVacateModal" title="Edit">
                                    <i class="fa fa-pencil text-primary" style="font-size:.65rem;"></i>
                                </button>
                            @endif
                        @else
                            @if (!in_array($rentOut->status, [\App\Enums\RentOut\RentOutStatus::Vacated, \App\Enums\RentOut\RentOutStatus::Cancelled]))
                                <button type="button" class="btn btn-sm btn-outline-warning py-0 px-1" wire:click="openVacateModal" style="font-size:.68rem;">
                                    <i class="fa fa-calendar-plus-o me-1"></i>Set
                                </button>
                            @else
                                —
                            @endif
                        @endif
                    </span>
                </div>

                <div class="d-flex justify-content-between align-items-center px-2 py-1 rv-row">
                    <span class="rv-lbl">Status</span>
                    <span class="rv-val">
                        @if ($rentOut->status)
                            <span class="badge bg-{{ $rentOut->status->color() }}" style="font-size: .65rem;">{{ $rentOut->status->label() }}</span>
                        @endif
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Agreement Details --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100 rv-card rv-ae">
            <div class="card-header rv-hdr border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <div class="rv-hdr-icon" style="background: #ecfeff;">
                        <i class="fa fa-file-text-o" style="color: #0891b2; font-size: .7rem;"></i>
                    </div>
                    <span class="rv-hdr-title">{{ $title }} Details</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="d-flex justify-content-between align-items-center px-2 py-1 rv-row border-bottom">
                    <span class="rv-lbl">Start Date</span>
                    <span class="rv-val">{{ $rentOut->start_date?->format('d M Y') ?? '—' }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-start px-2 py-1 rv-row border-bottom">
                    <span class="rv-lbl">End Date</span>
                    <span class="rv-val text-end">
                        {{ $rentOut->end_date?->format('d M Y') ?? '—' }}
                        @if ($rentOut->end_date)
                            <br><small class="{{ $daysRemaining > 0 ? 'text-success' : 'text-danger' }}" style="font-size:.65rem;">
                                {{ $daysRemaining > 0 ? $daysRemaining . 'd left' : abs($daysRemaining) . 'd ago' }}
                            </small>
                        @endif
                    </span>
                </div>
                <div class="d-flex justify-content-between align-items-center px-2 py-1 rv-row border-bottom">
                    <span class="rv-lbl">{{ $isRental ? 'Booking Type' : 'Agreement Type' }}</span>
                    <span class="rv-val">{{ $isRental ? $rentOut->booking_type : $rentOut->agreement_type?->label() }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center px-2 py-1 rv-row border-bottom">
                    <span class="rv-lbl">Salesman</span>
                    <span class="rv-val">{{ $rentOut->salesman?->name ?? '—' }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center px-2 py-1 rv-row border-bottom">
                    <span class="rv-lbl">Duration</span>
                    <span class="badge bg-secondary" style="font-size:.65rem;">{{ $rentOut->totalStay() }} months</span>
                </div>
                @if ($isRental)
                    <div class="d-flex justify-content-between align-items-center px-2 py-1 rv-row border-bottom">
                        <span class="rv-lbl">Free Months</span>
                        <span class="badge bg-info text-white" style="font-size:.65rem;">{{ $rentOut->free_month ?? 0 }}</span>
                    </div>
                @endif
                <div class="d-flex justify-content-between align-items-center px-2 py-1 rv-row border-bottom">
                    <span class="rv-lbl">{{ $isRental ? 'Monthly Rent' : 'Sale Price' }}</span>
                    <span class="rv-val" style="color: #059669;">{{ number_format($rentOut->rent, 2) }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center px-2 py-1 rv-row">
                    <span class="rv-lbl">Security Amount</span>
                    <span class="rv-val" style="color: #0e7490;">{{ number_format($securityTotal, 2) }}</span>
                </div>

                {{-- Financial Summary --}}
                <div class="border-top px-2 py-2" style="background: #f8fafc;">
                    <div class="rv-lbl mb-1"><i class="fa fa-bar-chart me-1"></i>Financial Summary</div>
                    <div class="row g-1 text-center">
                        <div class="col-4">
                            <div class="rounded py-1 px-1" style="background:#ecfeff; border:1px solid #a5f3fc;">
                                <div style="font-size:.6rem;" class="text-muted">Total</div>
                                <div class="fw-bold" style="color:#0e7490; font-size:.72rem;">{{ number_format($totalRent, 2) }}</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="rounded py-1 px-1" style="background:#fffbeb; border:1px solid #f5d98a;">
                                <div style="font-size:.6rem;" class="text-muted">Discount</div>
                                <div class="fw-bold" style="color:#d97706; font-size:.72rem;">{{ number_format($totalDiscount, 2) }}</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="rounded py-1 px-1" style="background:#ecfdf5; border:1px solid #86dbb5;">
                                <div style="font-size:.6rem;" class="text-muted">Paid</div>
                                <div class="fw-bold" style="color:#059669; font-size:.72rem;">{{ number_format($totalPaid, 2) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Collection & Payment --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100 rv-card rv-ag">
            <div class="card-header rv-hdr border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <div class="rv-hdr-icon" style="background: #ecfdf5;">
                        <i class="fa fa-money" style="color: #059669; font-size: .7rem;"></i>
                    </div>
                    <span class="rv-hdr-title">Collection Info</span>
                </div>
            </div>
            <div class="card-body p-0">
                @php
                    $collRows = [
                        'Frequency' => $rentOut->payment_frequency,
                        'Starting Day' => $rentOut->collection_starting_day,
                        'Payment Mode' => $rentOut->collection_payment_mode?->label(),
                    ];
                @endphp
                @foreach ($collRows as $label => $value)
                    <div class="d-flex justify-content-between align-items-center px-2 py-1 rv-row {{ !$loop->last ? 'border-bottom' : '' }}">
                        <span class="rv-lbl">{{ $label }}</span>
                        <span class="rv-val text-end">{{ $value ?? '—' }}</span>
                    </div>
                @endforeach
                @if ($rentOut->collection_payment_mode?->value === 'cheque')
                    <div class="d-flex justify-content-between align-items-center px-2 py-1 rv-row border-top">
                        <span class="rv-lbl">Bank Name</span>
                        <span class="rv-val text-end">{{ $rentOut->collection_bank_name ?? '—' }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center px-2 py-1 rv-row border-top">
                        <span class="rv-lbl">Cheque Start No.</span>
                        <span class="rv-val text-end">{{ $rentOut->collection_cheque_no ?? '—' }}</span>
                    </div>
                @endif

                {{-- Payment Breakdown --}}
                <div class="border-top px-2 py-2" style="background: #f8fafc;">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="rv-lbl"><i class="fa fa-list me-1"></i>Breakdown</span>
                        <span class="badge rounded-pill text-white" style="background: linear-gradient(135deg, #0891b2, #0e7490); font-size:.6rem;">{{ $paidMonths }}/{{ $totalMonths }} paid</span>
                    </div>
                    <table class="table table-sm mb-0" style="font-size:.72rem;">
                        <thead>
                            <tr style="background: linear-gradient(135deg, #0891b2, #0e7490); color: #fff;">
                                <th class="py-1 px-1 fw-semibold border-0">Type</th>
                                <th class="py-1 px-1 text-end fw-semibold border-0">Paid</th>
                                <th class="py-1 px-1 text-end fw-semibold border-0">Pending</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="py-1 px-1 fw-semibold"><i class="fa fa-{{ $isRental ? 'home' : 'tag' }} me-1 text-muted"></i>{{ $isRental ? 'Rent' : 'Sale' }}</td>
                                <td class="py-1 px-1 text-end fw-bold" style="color:#059669;">{{ number_format($totalPaid, 2) }}</td>
                                <td class="py-1 px-1 text-end fw-bold" style="color:#dc2626;">{{ number_format($totalPending, 2) }}</td>
                            </tr>
                            @if ($isRental)
                                <tr>
                                    <td class="py-1 px-1 fw-semibold"><i class="fa fa-bolt me-1 text-muted"></i>Utilities</td>
                                    <td class="py-1 px-1 text-end fw-bold" style="color:#059669;">{{ number_format($utilitiesPaid, 2) }}</td>
                                    <td class="py-1 px-1 text-end fw-bold" style="color:#dc2626;">{{ number_format($utilitiesPending, 2) }}</td>
                                </tr>
                            @endif
                            @if ($rentOut->management_fee > 0)
                                <tr>
                                    <td class="py-1 px-1 fw-semibold"><i class="fa fa-briefcase me-1 text-muted"></i>Mgmt Fee</td>
                                    <td class="py-1 px-1 text-end fw-bold" style="color:#059669;">{{ number_format($rentOut->management_fee, 2) }}</td>
                                    <td class="py-1 px-1 text-end fw-bold">—</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- REMARKS --}}
@if (trim($rentOut->remark ?? ''))
    <div class="card border-0 shadow-sm mb-2 rv-card rv-aa">
        <div class="card-body px-2 py-1">
            <div class="d-flex align-items-center gap-2">
                <div class="rv-hdr-icon flex-shrink-0" style="background: #fffbeb;">
                    <i class="fa fa-comment-o" style="color: #d97706; font-size: .65rem;"></i>
                </div>
                <p class="text-muted mb-0" style="font-size: .76rem;">{{ $rentOut->remark }}</p>
            </div>
        </div>
    </div>
@endif

{{-- MANAGEMENT TABS --}}
@include('livewire.rent-out.partials.management-tabs')

{{-- SOA Statement Modal --}}
<div class="modal fade" id="SOAStatementModal" tabindex="-1" aria-labelledby="SOAStatementModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header border-0 py-2 px-3" style="background: linear-gradient(135deg, {{ $accentColor }}, {{ $accentDark }});">
                <h6 class="modal-title text-white fw-bold" style="font-size: .85rem;" id="SOAStatementModalLabel">
                    <i class="fa fa-calendar me-1"></i>SOA Statement
                </h6>
                <button type="button" class="btn-close btn-close-white" style="font-size:.6rem;" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="SOAStatementForm">
                <div class="modal-body px-3 py-2">
                    <div class="alert alert-info py-1 px-2 mb-2" style="font-size:.75rem;">
                        <i class="fa fa-info-circle me-1"></i>Select the date range for the SOA Statement.
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label for="statement_from_date" class="form-label mb-1 rv-lbl">From Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control form-control-sm" id="statement_from_date" value="{{ date('Y-m-01') }}" required style="font-size:.78rem; border-radius: 6px;">
                        </div>
                        <div class="col-md-6">
                            <label for="statement_to_date" class="form-label mb-1 rv-lbl">To Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control form-control-sm" id="statement_to_date" value="{{ date('Y-m-d') }}" required style="font-size:.78rem; border-radius: 6px;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-3 py-2">
                    <button type="button" class="btn btn-sm btn-light fw-medium px-2 py-1" data-bs-dismiss="modal" style="font-size:.75rem; border-radius: 6px;">
                        <i class="fa fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-sm text-white fw-medium px-2 py-1"
                        style="font-size:.75rem; background: linear-gradient(135deg, {{ $accentColor }}, {{ $accentDark }}); border: none; border-radius: 6px;">
                        <i class="fa fa-print me-1"></i>Generate
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if ($isRental)
    {{-- SOA Utilities Modal --}}
    <div class="modal fade" id="SOAUtilitiesModal" tabindex="-1" aria-labelledby="SOAUtilitiesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
                <div class="modal-header border-0 py-2 px-3" style="background: linear-gradient(135deg, {{ $accentColor }}, {{ $accentDark }});">
                    <h6 class="modal-title text-white fw-bold" style="font-size: .85rem;" id="SOAUtilitiesModalLabel">
                        <i class="fa fa-bolt me-1"></i>SOA Utilities
                    </h6>
                    <button type="button" class="btn-close btn-close-white" style="font-size:.6rem;" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="SOAUtilitiesForm">
                    <div class="modal-body px-3 py-2">
                        <div class="alert alert-info py-1 px-2 mb-2" style="font-size:.75rem;">
                            <i class="fa fa-info-circle me-1"></i>Select the date range for the Utilities SOA.
                        </div>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label for="utility_from_date" class="form-label mb-1 rv-lbl">From Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control form-control-sm" id="utility_from_date" value="{{ date('Y-m-01') }}" required style="font-size:.78rem; border-radius: 6px;">
                            </div>
                            <div class="col-md-6">
                                <label for="utility_to_date" class="form-label mb-1 rv-lbl">To Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control form-control-sm" id="utility_to_date" value="{{ date('Y-m-d') }}" required style="font-size:.78rem; border-radius: 6px;">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 px-3 py-2">
                        <button type="button" class="btn btn-sm btn-light fw-medium px-2 py-1" data-bs-dismiss="modal" style="font-size:.75rem; border-radius: 6px;">
                            <i class="fa fa-times me-1"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-sm text-white fw-medium px-2 py-1"
                            style="font-size:.75rem; background: linear-gradient(135deg, {{ $accentColor }}, {{ $accentDark }}); border: none; border-radius: 6px;">
                            <i class="fa fa-print me-1"></i>Generate
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

{{-- VACATE MODAL --}}
@if ($showVacateModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,.45);">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
                <div class="modal-header border-0 py-2 px-3" style="background: linear-gradient(135deg, {{ $accentColor }}, {{ $accentDeep }});">
                    <h6 class="modal-title text-white fw-bold" style="font-size: .85rem;">
                        <i class="fa fa-sign-out me-1"></i>Vacate
                    </h6>
                    <button type="button" class="btn-close btn-close-white" style="font-size:.6rem;" wire:click="$set('showVacateModal', false)"></button>
                </div>
                <div class="modal-body px-3 py-2">
                    <div class="mb-2">
                        <label for="vacateDate" class="form-label mb-1 rv-lbl fw-semibold">Vacate Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control form-control-sm @error('vacateDate') is-invalid @enderror"
                            id="vacateDate" wire:model="vacateDate"
                            min="{{ $rentOut->start_date->format('Y-m-d') }}"
                            max="{{ $rentOut->end_date->format('Y-m-d') }}"
                            style="font-size:.78rem; border-radius: 6px;">
                        @error('vacateDate')
                            <div class="invalid-feedback" style="font-size:.7rem;">{{ $message }}</div>
                        @enderror
                    </div>
                    @if ($rentOut->start_date && $rentOut->end_date)
                        <small class="text-muted" style="font-size:.68rem;">
                            <i class="fa fa-info-circle me-1"></i>
                            {{ $rentOut->start_date->format('d M Y') }} — {{ $rentOut->end_date->format('d M Y') }}
                        </small>
                    @endif
                </div>
                <div class="modal-footer border-0 px-3 py-2">
                    <button type="button" class="btn btn-sm btn-light fw-medium px-2 py-1" wire:click="$set('showVacateModal', false)" style="font-size:.75rem; border-radius: 6px;">
                        <i class="fa fa-times me-1"></i>Close
                    </button>
                    <button type="button" class="btn btn-sm text-white fw-medium px-2 py-1" wire:click="saveVacate"
                        onclick="return confirm('Are you sure you want to set/update the vacate date?')"
                        style="font-size:.75rem; background: linear-gradient(135deg, #059669, #047857); border: none; border-radius: 6px;">
                        <i class="fa fa-check me-1"></i>Save
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var statementForm = document.getElementById('SOAStatementForm');
        if (statementForm) {
            statementForm.addEventListener('submit', function(e) {
                e.preventDefault();
                var fromDate = document.getElementById('statement_from_date').value;
                var toDate = document.getElementById('statement_to_date').value;
                if (!fromDate || !toDate) { alert('Please select both dates.'); return; }
                if (fromDate > toDate) { alert('From date cannot be after to date.'); return; }
                var url = "{{ route('print::rentout::statement', $rentOut->id) }}" + '/' + fromDate + '/' + toDate;
                window.open(url, '_blank');
                var modal = bootstrap.Modal.getInstance(document.getElementById('SOAStatementModal'));
                if (modal) modal.hide();
            });
        }

        var utilitiesForm = document.getElementById('SOAUtilitiesForm');
        if (utilitiesForm) {
            utilitiesForm.addEventListener('submit', function(e) {
                e.preventDefault();
                var fromDate = document.getElementById('utility_from_date').value;
                var toDate = document.getElementById('utility_to_date').value;
                if (!fromDate || !toDate) { alert('Please select both dates.'); return; }
                if (fromDate > toDate) { alert('From date cannot be after to date.'); return; }
                var url = "{{ route('print::rentout::utilities-statement', $rentOut->id) }}" + '/' + fromDate + '/' + toDate;
                window.open(url, '_blank');
                var modal = bootstrap.Modal.getInstance(document.getElementById('SOAUtilitiesModal'));
                if (modal) modal.hide();
            });
        }
    });
</script>

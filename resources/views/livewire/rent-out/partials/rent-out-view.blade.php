{{--
    Agreement View Partial (Non-booking)
    Variables required from parent:
      - $rentOut, $indexRoute, $indexLabel, $editPermission, $editRoute, $config
--}}
@php
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
@endphp

<style>
    .rv-card { transition: transform .15s ease, box-shadow .15s ease; border-radius: .5rem !important; overflow: hidden; }
    .rv-card:hover { transform: translateY(-1px); box-shadow: 0 .35rem .9rem rgba(var(--bs-body-color-rgb), .08) !important; }
    .rv-row { transition: background-color .15s ease; }
    .rv-row:hover { background-color: var(--bs-tertiary-bg); }
    .rv-lbl { color: var(--bs-secondary-color); font-size: .72rem; font-weight: 500; }
    .rv-val { color: var(--bs-emphasis-color); font-size: .76rem; font-weight: 600; }
    .rv-hdr { padding: .4rem .65rem !important; background-color: var(--bs-body-bg); }
    .rv-hdr-icon { width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .rv-hdr-title { font-size: .78rem; font-weight: 600; color: var(--bs-emphasis-color); }
    .rv-header-card {
        background-color: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color-translucent);
        border-left: 4px solid rgba(var(--bs-primary-rgb), 1);
    }
    .rv-header-card .rv-title { color: var(--bs-emphasis-color); }
    .rv-header-card .rv-title-no { color: rgba(var(--bs-primary-rgb), 1); }
    .rv-header-card .rv-sub { color: var(--bs-secondary-color); }
    .rv-header-card .breadcrumb-item,
    .rv-header-card .breadcrumb-item a { color: var(--bs-secondary-color) !important; text-decoration: none; }
    .rv-header-card .breadcrumb-item.active { color: rgba(var(--bs-primary-rgb), 1) !important; font-weight: 600; }
    .rv-progress { height: 6px; background-color: rgba(var(--bs-primary-rgb), .12); border-radius: 10px; }
    .rv-progress .progress-bar { background-color: rgba(var(--bs-primary-rgb), 1); border-radius: 10px; }
    .rv-stat-card { border-radius: .5rem; border: 1px solid var(--bs-border-color-translucent); }
    .rv-stat-card .rv-stat-icon { width: 36px; height: 36px; border-radius: .5rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .rv-fin-cell { border: 1px solid var(--bs-border-color-translucent); border-radius: .375rem; }
    .rv-table th { background-color: var(--bs-primary); color: #fff; font-weight: 600; font-size: .7rem; }
    .rv-table td, .rv-table th { padding: .35rem .5rem !important; }
    .rv-modal-header { background: linear-gradient(135deg, rgba(var(--bs-primary-rgb),1), rgba(var(--bs-primary-rgb),.78)); }
</style>

{{-- HEADER --}}
<div class="rv-header-card rounded-3 px-3 py-2 mb-2 shadow-sm">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div class="flex-grow-1 min-w-0">
            <div class="d-flex flex-wrap align-items-center gap-1 mb-1">
                <span class="badge bg-primary-subtle text-primary-emphasis border border-primary-subtle text-uppercase fw-semibold" style="font-size:.62rem; letter-spacing:.05em;">
                    {{ $isRental ? 'Rental' : 'Sale' }}
                </span>
                @if ($rentOut->status)
                    <span class="badge bg-{{ $rentOut->status->color() }}-subtle text-{{ $rentOut->status->color() }}-emphasis border border-{{ $rentOut->status->color() }}-subtle px-2 py-1" style="font-size:.62rem;">
                        {{ $rentOut->status->label() }}@if($rentOut->booking_status) | {{ $rentOut->booking_status?->label() }}@endif
                    </span>
                @endif
            </div>
            <h5 class="mb-0 fw-bold text-truncate rv-title" style="font-size:1.05rem; letter-spacing:-.02em;">
                {{ $title }}
                <span class="fw-semibold rv-title-no">#{{ $rentOut->agreement_no }}</span>
            </h5>
            <nav aria-label="breadcrumb" class="mt-1">
                <ol class="breadcrumb mb-0" style="font-size:.7rem;">
                    <li class="breadcrumb-item"><a href="{{ route($indexRoute) }}"><i class="fa fa-home"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route($indexRoute) }}">{{ $indexLabel }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $rentOut->agreement_no }}</li>
                </ol>
            </nav>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-1">
            <button type="button" class="btn btn-sm btn-outline-primary fw-medium px-2 py-1" style="font-size:.72rem;"
                data-bs-toggle="modal" data-bs-target="#SOAStatementModal">
                <i class="fa fa-print me-1"></i>SOA
            </button>
            @if ($isRental)
                <button type="button" class="btn btn-sm btn-outline-primary fw-medium px-2 py-1" style="font-size:.72rem;"
                    data-bs-toggle="modal" data-bs-target="#SOAUtilitiesModal">
                    <i class="fa fa-bolt me-1"></i>Utilities
                </button>
            @endif
            @if (!in_array($rentOut->status, [\App\Enums\RentOut\RentOutStatus::Vacated, \App\Enums\RentOut\RentOutStatus::Cancelled]))
                <button type="button" class="btn btn-sm btn-outline-warning fw-medium px-2 py-1" style="font-size:.72rem;" wire:click="openVacateModal">
                    <i class="fa fa-sign-out me-1"></i>Vacate
                </button>
            @endif
            @can($editPermission)
                <a href="{{ route($editRoute, $rentOut->id) }}" class="btn btn-sm btn-primary fw-medium px-2 py-1" style="font-size:.72rem;">
                    <i class="fa fa-pencil me-1"></i>Edit
                </a>
            @endcan
        </div>
    </div>

    {{-- Progress Bar --}}
    <div class="mt-2">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <small class="rv-sub fw-medium" style="font-size:.7rem;"><i class="fa fa-pie-chart me-1 text-primary"></i>Payment Progress</small>
            <small class="text-primary fw-bold" style="font-size:.7rem;">{{ $paidPercent }}%</small>
        </div>
        <div class="progress rv-progress" role="progressbar" aria-valuenow="{{ $paidPercent }}" aria-valuemin="0" aria-valuemax="100">
            <div class="progress-bar" style="width: {{ $paidPercent }}%;"></div>
        </div>
    </div>
</div>

{{-- STAT PILLS --}}
@php
    $daysBg = $daysRemaining > 0 ? 'bg-info' : 'bg-danger';
    $stats = [
        ['label' => 'Days ' . ($daysRemaining > 0 ? 'Remaining' : 'Overdue'), 'value' => abs($daysRemaining), 'sub' => $daysRemaining > 0 ? 'days left' : 'expired', 'bgClass' => $daysBg, 'icon' => 'fa-calendar'],
        ['label' => 'Paid Instalments', 'value' => $paidMonths, 'sub' => 'of ' . $totalMonths . ' total', 'bgClass' => 'bg-purple', 'icon' => 'fa-check-square-o'],
        ['label' => 'Collected', 'value' => number_format($totalPaid, 2), 'sub' => 'received', 'bgClass' => 'bg-success', 'icon' => 'fa-check-circle'],
        ['label' => 'Outstanding', 'value' => number_format($totalPending, 2), 'sub' => 'balance due', 'bgClass' => 'bg-danger', 'icon' => 'fa-clock-o'],
    ];
@endphp
<div class="row g-2 mb-2">
    @foreach ($stats as $stat)
        <div class="col-6 col-md-3">
            <div class="card {{ $stat['bgClass'] }} text-white hv-grow border-0 shadow-sm h-100 position-relative overflow-hidden">
                <i class="fa {{ $stat['icon'] }} position-absolute"
                    style="font-size:6rem; right:-1rem; bottom:-1.25rem; color:rgba(255,255,255,.14);"></i>
                <div class="card-body p-3 position-relative">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center"
                                style="width:48px;height:48px;">
                                <i class="fa {{ $stat['icon'] }} fs-4 text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3 min-w-0">
                            <p class="text-white text-opacity-75 text-uppercase fw-semibold mb-1"
                                style="font-size:.62rem; letter-spacing:.06em;">{{ $stat['label'] }}</p>
                            <h5 class="h3 mb-0 fw-bold text-white text-truncate" style="line-height:1.1;">
                                {{ $stat['value'] }}
                            </h5>
                            <small class="text-white text-opacity-75" style="font-size:.68rem;">{{ $stat['sub'] }}</small>
                        </div>
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
        <div class="card border-0 shadow-sm h-100 rv-card border-start border-primary border-3">
            <div class="card-header rv-hdr border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <div class="rv-hdr-icon bg-primary-subtle">
                        <i class="fa fa-building text-primary-emphasis" style="font-size:.72rem;"></i>
                    </div>
                    <span class="rv-hdr-title">Property & Customer</span>
                </div>
            </div>
            <div class="card-body p-0">
                @php
                    $propRows = [
                        'Reference No' => ['val' => $rentOut->agreement_no, 'highlight' => true],
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
                        <span class="rv-val text-end text-truncate ms-2 {{ !empty($info['highlight']) ? 'text-primary' : '' }}">{{ $info['val'] ?? '—' }}</span>
                    </div>
                @endforeach

                <div class="d-flex justify-content-between align-items-center px-2 py-1 rv-row border-bottom">
                    <span class="rv-lbl">Vacate Date</span>
                    <span class="rv-val text-end">
                        @if ($rentOut->vacate_date)
                            <span class="{{ $rentOut->vacate_date > now() ? 'text-warning-emphasis' : 'text-secondary' }}">
                                {{ $rentOut->vacate_date->format('d M Y') }}
                            </span>
                            @if ($rentOut->vacate_date > now() && !in_array($rentOut->status, [\App\Enums\RentOut\RentOutStatus::Vacated, \App\Enums\RentOut\RentOutStatus::Cancelled]))
                                <button type="button" class="btn btn-sm btn-link p-0 ms-1" wire:click="openVacateModal" title="Edit">
                                    <i class="fa fa-pencil text-primary" style="font-size:.65rem;"></i>
                                </button>
                            @endif
                        @else
                            @if (!in_array($rentOut->status, [\App\Enums\RentOut\RentOutStatus::Vacated, \App\Enums\RentOut\RentOutStatus::Cancelled]))
                                <button type="button" class="btn btn-sm btn-outline-warning py-0 px-1" wire:click="openVacateModal" style="font-size:.66rem;">
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
                            <span class="badge bg-{{ $rentOut->status->color() }}" style="font-size:.65rem;">{{ $rentOut->status->label() }}</span>
                        @endif
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Agreement Details --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100 rv-card border-start border-info border-3">
            <div class="card-header rv-hdr border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <div class="rv-hdr-icon bg-info-subtle">
                        <i class="fa fa-file-text-o text-info-emphasis" style="font-size:.72rem;"></i>
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
                    <span class="rv-val text-success-emphasis">{{ number_format($rentOut->rent, 2) }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center px-2 py-1 rv-row">
                    <span class="rv-lbl">Security Amount</span>
                    <span class="rv-val text-info-emphasis">{{ number_format($securityTotal, 2) }}</span>
                </div>

                {{-- Financial Summary --}}
                <div class="border-top px-2 py-2 bg-body-tertiary">
                    <div class="rv-lbl mb-1"><i class="fa fa-bar-chart me-1"></i>Financial Summary</div>
                    <div class="row g-1 text-center">
                        <div class="col-4">
                            <div class="rv-fin-cell bg-info-subtle py-1 px-1">
                                <div class="text-secondary" style="font-size:.6rem;">Total</div>
                                <div class="fw-bold text-info-emphasis" style="font-size:.72rem;">{{ number_format($totalRent, 2) }}</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="rv-fin-cell bg-warning-subtle py-1 px-1">
                                <div class="text-secondary" style="font-size:.6rem;">Discount</div>
                                <div class="fw-bold text-warning-emphasis" style="font-size:.72rem;">{{ number_format($totalDiscount, 2) }}</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="rv-fin-cell bg-success-subtle py-1 px-1">
                                <div class="text-secondary" style="font-size:.6rem;">Paid</div>
                                <div class="fw-bold text-success-emphasis" style="font-size:.72rem;">{{ number_format($totalPaid, 2) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Collection & Payment --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100 rv-card border-start border-success border-3">
            <div class="card-header rv-hdr border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <div class="rv-hdr-icon bg-success-subtle">
                        <i class="fa fa-money text-success-emphasis" style="font-size:.72rem;"></i>
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
                <div class="border-top px-2 py-2 bg-body-tertiary">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="rv-lbl"><i class="fa fa-list me-1"></i>Breakdown</span>
                        <span class="badge rounded-pill bg-primary" style="font-size:.6rem;">{{ $paidMonths }}/{{ $totalMonths }} paid</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm rv-table mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th class="border-0">Type</th>
                                    <th class="border-0 text-end">Paid</th>
                                    <th class="border-0 text-end">Pending</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="fw-semibold"><i class="fa fa-{{ $isRental ? 'home' : 'tag' }} me-1 text-secondary"></i>{{ $isRental ? 'Rent' : 'Sale' }}</td>
                                    <td class="text-end fw-bold text-success-emphasis">{{ number_format($totalPaid, 2) }}</td>
                                    <td class="text-end fw-bold text-danger-emphasis">{{ number_format($totalPending, 2) }}</td>
                                </tr>
                                @if ($isRental)
                                    <tr>
                                        <td class="fw-semibold"><i class="fa fa-bolt me-1 text-secondary"></i>Utilities</td>
                                        <td class="text-end fw-bold text-success-emphasis">{{ number_format($utilitiesPaid, 2) }}</td>
                                        <td class="text-end fw-bold text-danger-emphasis">{{ number_format($utilitiesPending, 2) }}</td>
                                    </tr>
                                @endif
                                @if ($rentOut->management_fee > 0)
                                    <tr>
                                        <td class="fw-semibold"><i class="fa fa-briefcase me-1 text-secondary"></i>Mgmt Fee</td>
                                        <td class="text-end fw-bold text-success-emphasis">{{ number_format($rentOut->management_fee, 2) }}</td>
                                        <td class="text-end fw-bold">—</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- REMARKS --}}
@if (trim($rentOut->remark ?? ''))
    <div class="card border-0 shadow-sm mb-2 rv-card border-start border-warning border-3">
        <div class="card-body px-2 py-1">
            <div class="d-flex align-items-center gap-2">
                <div class="rv-hdr-icon flex-shrink-0 bg-warning-subtle">
                    <i class="fa fa-comment-o text-warning-emphasis" style="font-size:.65rem;"></i>
                </div>
                <p class="text-secondary mb-0" style="font-size:.76rem;">{{ $rentOut->remark }}</p>
            </div>
        </div>
    </div>
@endif

{{-- MANAGEMENT TABS --}}
@include('livewire.rent-out.partials.management-tabs')

{{-- SOA Statement Modal --}}
<div class="modal fade" id="SOAStatementModal" tabindex="-1" aria-labelledby="SOAStatementModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden">
            <div class="modal-header rv-modal-header border-0 py-2 px-3">
                <h6 class="modal-title text-white fw-bold mb-0" style="font-size:.85rem;" id="SOAStatementModalLabel">
                    <i class="fa fa-calendar me-1"></i>SOA Statement
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="SOAStatementForm">
                <div class="modal-body px-3 py-2">
                    <div class="alert alert-info py-1 px-2 mb-2" style="font-size:.75rem;">
                        <i class="fa fa-info-circle me-1"></i>Select the date range for the SOA Statement.
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label for="statement_from_date" class="form-label mb-1 rv-lbl">From Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control form-control-sm" id="statement_from_date" value="{{ date('Y-m-01') }}" required style="font-size:.78rem;">
                        </div>
                        <div class="col-md-6">
                            <label for="statement_to_date" class="form-label mb-1 rv-lbl">To Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control form-control-sm" id="statement_to_date" value="{{ date('Y-m-d') }}" required style="font-size:.78rem;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-3 py-2">
                    <button type="button" class="btn btn-sm btn-light fw-medium px-2 py-1" data-bs-dismiss="modal" style="font-size:.75rem;">
                        <i class="fa fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-sm btn-primary fw-medium px-2 py-1" style="font-size:.75rem;">
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
            <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden">
                <div class="modal-header rv-modal-header border-0 py-2 px-3">
                    <h6 class="modal-title text-white fw-bold mb-0" style="font-size:.85rem;" id="SOAUtilitiesModalLabel">
                        <i class="fa fa-bolt me-1"></i>SOA Utilities
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="SOAUtilitiesForm">
                    <div class="modal-body px-3 py-2">
                        <div class="alert alert-info py-1 px-2 mb-2" style="font-size:.75rem;">
                            <i class="fa fa-info-circle me-1"></i>Select the date range for the Utilities SOA.
                        </div>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label for="utility_from_date" class="form-label mb-1 rv-lbl">From Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control form-control-sm" id="utility_from_date" value="{{ date('Y-m-01') }}" required style="font-size:.78rem;">
                            </div>
                            <div class="col-md-6">
                                <label for="utility_to_date" class="form-label mb-1 rv-lbl">To Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control form-control-sm" id="utility_to_date" value="{{ date('Y-m-d') }}" required style="font-size:.78rem;">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 px-3 py-2">
                        <button type="button" class="btn btn-sm btn-light fw-medium px-2 py-1" data-bs-dismiss="modal" style="font-size:.75rem;">
                            <i class="fa fa-times me-1"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-sm btn-primary fw-medium px-2 py-1" style="font-size:.75rem;">
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
            <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden">
                <div class="modal-header rv-modal-header border-0 py-2 px-3">
                    <h6 class="modal-title text-white fw-bold mb-0" style="font-size:.85rem;">
                        <i class="fa fa-sign-out me-1"></i>Vacate
                    </h6>
                    <button type="button" class="btn-close btn-close-white" wire:click="$set('showVacateModal', false)"></button>
                </div>
                <div class="modal-body px-3 py-2">
                    <div class="mb-2">
                        <label for="vacateDate" class="form-label mb-1 rv-lbl fw-semibold">Vacate Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control form-control-sm @error('vacateDate') is-invalid @enderror"
                            id="vacateDate" wire:model="vacateDate"
                            min="{{ $rentOut->start_date->format('Y-m-d') }}"
                            max="{{ $rentOut->end_date->format('Y-m-d') }}"
                            style="font-size:.78rem;">
                        @error('vacateDate')
                            <div class="invalid-feedback" style="font-size:.7rem;">{{ $message }}</div>
                        @enderror
                    </div>
                    @if ($rentOut->start_date && $rentOut->end_date)
                        <small class="text-secondary" style="font-size:.68rem;">
                            <i class="fa fa-info-circle me-1"></i>
                            {{ $rentOut->start_date->format('d M Y') }} — {{ $rentOut->end_date->format('d M Y') }}
                        </small>
                    @endif
                </div>
                <div class="modal-footer border-0 px-3 py-2">
                    <button type="button" class="btn btn-sm btn-light fw-medium px-2 py-1" wire:click="$set('showVacateModal', false)" style="font-size:.75rem;">
                        <i class="fa fa-times me-1"></i>Close
                    </button>
                    <button type="button" class="btn btn-sm btn-success fw-medium px-2 py-1" wire:click="saveVacate"
                        onclick="return confirm('Are you sure you want to set/update the vacate date?')"
                        style="font-size:.75rem;">
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

<div>
    @php
        use App\Enums\RentOut\RentOutBookingStatus;
    @endphp
    @if ($rentOut)
        @php
            $isRental = $rentOut->agreement_type?->value === 'rental';
            $title = $isRental ? 'Rental Booking' : 'Sale Booking';
            $accentColor = $isRental ? '#0891b2' : '#4f46e5';
            $accentDark = $isRental ? '#0e7490' : '#3730a3';
            $accentDeep = $isRental ? '#164e63' : '#1e1b4b';
        @endphp

        <style>
            .bk-card { transition: transform .2s, box-shadow .2s; border-radius: 10px !important; overflow: hidden; }
            .bk-card:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,.07) !important; }
            .bk-row { transition: background .15s; }
            .bk-row:hover { background: #f8fafc; }
            .bk-lbl { color: #64748b; font-size: .78rem; }
            .bk-val { color: #1e293b; font-size: .78rem; font-weight: 600; }
            .bk-hdr { padding: .5rem .75rem !important; background: #fff; }
            .bk-hdr-icon { width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
            .bk-hdr-title { font-size: .82rem; font-weight: 600; color: #1e293b; }
            .ab { border-left: 3px solid #4f46e5 !important; }
            .ae { border-left: 3px solid #059669 !important; }
            .aa { border-left: 3px solid #f59e0b !important; }
            .av { border-left: 3px solid #8b5cf6 !important; }
        </style>

        {{-- HEADER --}}
        <div class="rounded-3 px-3 py-3 mb-3 text-white position-relative overflow-hidden"
            style="background: linear-gradient(135deg, {{ $accentColor }}, {{ $accentDark }} 50%, {{ $accentDeep }}); box-shadow: 0 6px 24px rgba(0,0,0,.12);">
            <div class="position-absolute" style="width: 160px; height: 160px; border-radius: 50%; background: rgba(255,255,255,.04); top: -50px; right: -20px;"></div>

            {{-- Breadcrumb --}}
            <nav aria-label="breadcrumb" class="mb-2">
                <ol class="breadcrumb mb-0" style="font-size: .72rem; --bs-breadcrumb-divider-color: rgba(255,255,255,.35);">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-white-50 text-decoration-none"><i class="fa fa-home me-1"></i>Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route($config->bookingRoute) }}" class="text-white-50 text-decoration-none">{{ $config->bookingLabel }}</a></li>
                    <li class="breadcrumb-item text-white fw-medium" aria-current="page">#{{ $rentOut->id }}</li>
                </ol>
            </nav>

            {{-- Title & Status --}}
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                <div>
                    <h5 class="fw-bold mb-0 text-white" style="font-size: 1.1rem; letter-spacing: -.02em;">
                        {{ $title }} &mdash; {{ $rentOut->agreement_no }}
                    </h5>
                    <div class="d-flex align-items-center gap-2 mt-1" style="font-size: .75rem; opacity: .8;">
                        <span><i class="fa fa-user-circle me-1"></i>{{ $rentOut->customer?->name }}</span>
                        <span style="opacity: .4;">&bull;</span>
                        <span><i class="fa fa-home me-1"></i>{{ $rentOut->property?->number }}</span>
                    </div>
                </div>
                <div class="d-flex gap-1">
                    <span class="badge rounded-pill px-2 py-1"
                        style="background: rgba(255,255,255,.14); font-size: .7rem; border: 1px solid rgba(255,255,255,.18);">
                        {{ $isRental ? 'Rental' : 'Sale' }}
                    </span>
                    <span class="badge rounded-pill px-2 py-1"
                        style="background: rgba(255,255,255,.14); font-size: .7rem; border: 1px solid rgba(255,255,255,.18);">
                        {{ ucwords($rentOut->booking_status?->label()) }}
                    </span>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="d-flex flex-wrap gap-1">
                @can($config->bookingEditPermission)
                    <a href="{{ route($config->bookingEditRoute, $rentOut->id) }}"
                       class="btn btn-light btn-sm fw-medium px-2 py-1" style="font-size: .75rem; border-radius: 6px;">
                        <i class="fa fa-pencil me-1"></i>Edit
                    </a>
                @endcan
                @can($config->bookingReservationFormPermission)
                    <a href="{{ route('print::rentout::reservation-form', $rentOut->id) }}" target="_blank"
                       class="btn btn-sm text-white fw-medium px-2 py-1"
                       style="font-size: .75rem; background: rgba(255,255,255,.13); border: 1px solid rgba(255,255,255,.22); border-radius: 6px;">
                        <i class="fa fa-file-text me-1"></i>Reservation Form
                    </a>
                @endcan
                @can($config->bookingResidentialLeasePermission)
                    <a href="{{ route('print::rentout::residential-lease', $rentOut->id) }}" target="_blank"
                       class="btn btn-sm text-white fw-medium px-2 py-1"
                       style="font-size: .75rem; background: rgba(255,255,255,.13); border: 1px solid rgba(255,255,255,.22); border-radius: 6px;">
                        <i class="fa fa-file-contract me-1"></i>{{ $isRental ? 'Residential Lease' : 'Sales Agreement' }}
                    </a>
                @endcan
            </div>
        </div>

        {{-- 3-COLUMN INFO CARDS --}}
        <div class="row g-2 mb-2">
            {{-- Property & Customer --}}
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100 bk-card ab">
                    <div class="card-header bk-hdr border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <div class="bk-hdr-icon" style="background: #eef2ff;">
                                <i class="fa fa-building" style="color: #4f46e5; font-size: .75rem;"></i>
                            </div>
                            <span class="bk-hdr-title">Property & Customer</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        @php
                            $propertyInfo = [
                                'Reference No' => $rentOut->agreement_no,
                                'Group' => $rentOut->group?->name,
                                'Building' => $rentOut->building?->name,
                                'Unit Type' => $rentOut->type?->name,
                                'Property/Unit' => $rentOut->property?->number,
                                'Customer' => $rentOut->customer?->name,
                            ];
                        @endphp
                        @foreach ($propertyInfo as $label => $value)
                            <div class="d-flex justify-content-between align-items-center px-2 py-1 bk-row {{ !$loop->last ? 'border-bottom' : '' }}">
                                <span class="bk-lbl">{{ $label }}</span>
                                <span class="bk-val text-end">{{ $value ?? '—' }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Agreement Details --}}
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100 bk-card ae">
                    <div class="card-header bk-hdr border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <div class="bk-hdr-icon" style="background: #ecfdf5;">
                                <i class="fa fa-file-text-o" style="color: #059669; font-size: .75rem;"></i>
                            </div>
                            <span class="bk-hdr-title">{{ $title }} Details</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        @php
                            $agreementInfo = [
                                'Start Date' => $rentOut->start_date?->format('d M Y'),
                                'End Date' => $rentOut->end_date?->format('d M Y'),
                                'Booking Type' => ucfirst($rentOut->agreement_type?->value ?? ''),
                                'Salesman' => $rentOut->salesman?->name,
                                'Duration' => $rentOut->totalStay() . ' months',
                                'Free Months' => $rentOut->free_month ?? 0,
                                $isRental ? 'Monthly Rent' : 'Sale Price' => number_format($rentOut->rent ?? 0, 2),
                                'Security Amt' => number_format($rentOut->securities->sum('amount') ?? 0, 2),
                            ];
                        @endphp
                        @foreach ($agreementInfo as $label => $value)
                            <div class="d-flex justify-content-between align-items-center px-2 py-1 bk-row {{ !$loop->last ? 'border-bottom' : '' }}">
                                <span class="bk-lbl">{{ $label }}</span>
                                <span class="bk-val text-end">{{ $value ?? '—' }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Collection Info --}}
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100 bk-card aa">
                    <div class="card-header bk-hdr border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <div class="bk-hdr-icon" style="background: #fffbeb;">
                                <i class="fa fa-calendar" style="color: #d97706; font-size: .75rem;"></i>
                            </div>
                            <span class="bk-hdr-title">Collection Info</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        @php
                            $collectionInfo = [
                                'Payment Frequency' => ucfirst($rentOut->payment_frequency ?? ''),
                                'Collection Start Day' => $rentOut->collection_starting_day,
                                'Payment Mode' => $rentOut->collection_payment_mode?->label(),
                            ];
                        @endphp
                        @foreach ($collectionInfo as $label => $value)
                            <div class="d-flex justify-content-between align-items-center px-2 py-1 bk-row {{ !$loop->last ? 'border-bottom' : '' }}">
                                <span class="bk-lbl">{{ $label }}</span>
                                <span class="bk-val text-end">{{ $value ?? '—' }}</span>
                            </div>
                        @endforeach
                        @if ($rentOut->collection_payment_mode?->value === 'cheque')
                            <div class="d-flex justify-content-between align-items-center px-2 py-1 bk-row border-top">
                                <span class="bk-lbl">Bank Name</span>
                                <span class="bk-val text-end">{{ $rentOut->collection_bank_name ?? '—' }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center px-2 py-1 bk-row border-top">
                                <span class="bk-lbl">Cheque Starting No</span>
                                <span class="bk-val text-end">{{ $rentOut->collection_cheque_no ?? '—' }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- WORKFLOW STATUS (horizontal) --}}
        <div class="card border-0 shadow-sm mb-2 bk-card av">
            <div class="card-body px-2 py-2">
                @php
                    $steps = [
                        ['label' => 'Created', 'user' => $rentOut->createdBy, 'date' => $rentOut->created_at],
                        ['label' => 'Submitted', 'user' => $rentOut->submittedBy, 'date' => $rentOut->submitted_at],
                        ['label' => 'Financial Approved', 'user' => $rentOut->financialApprovedBy, 'date' => $rentOut->financial_approved_at],
                        ['label' => 'Legal Approved', 'user' => $rentOut->approvedBy, 'date' => $rentOut->approved_at],
                        ['label' => 'Completed', 'user' => $rentOut->completedBy, 'date' => $rentOut->completed_at],
                    ];
                @endphp
                <div class="d-flex align-items-center flex-wrap gap-0">
                    @foreach ($steps as $step)
                        <div class="d-flex align-items-center">
                            {{-- Step dot + label --}}
                            <div class="d-flex align-items-center gap-1">
                                @if ($step['user'])
                                    <div class="d-flex align-items-center justify-content-center rounded-circle" style="width: 20px; height: 20px; background: #dcfce7;">
                                        <i class="fa fa-check" style="color: #16a34a; font-size: .55rem;"></i>
                                    </div>
                                @else
                                    <div class="d-flex align-items-center justify-content-center rounded-circle" style="width: 20px; height: 20px; border: 2px solid #e2e8f0;">
                                    </div>
                                @endif
                                <div class="ms-1">
                                    <span class="fw-semibold" style="font-size: .72rem; color: {{ $step['user'] ? '#1e293b' : '#94a3b8' }};">{{ $step['label'] }}</span>
                                    @if ($step['user'])
                                        <span class="text-muted d-none d-xl-inline" style="font-size: .65rem;"> {{ $step['user']->name }}@if ($step['date']), {{ $step['date']->format('d/m/y') }}@endif</span>
                                    @endif
                                </div>
                            </div>
                            {{-- Connector line --}}
                            @if (!$loop->last)
                                <div class="mx-2" style="width: 24px; height: 2px; background: {{ $step['user'] ? '#bbf7d0' : '#f1f5f9' }}; border-radius: 1px;"></div>
                            @endif
                        </div>
                    @endforeach

                    @if ($rentOut->status?->value === 'cancelled')
                        <div class="ms-2 d-flex align-items-center gap-1">
                            <div class="d-flex align-items-center justify-content-center rounded-circle" style="width: 20px; height: 20px; background: #fee2e2;">
                                <i class="fa fa-times" style="color: #dc2626; font-size: .55rem;"></i>
                            </div>
                            <span class="fw-semibold text-danger" style="font-size: .72rem;">Cancelled</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- REMARKS --}}
        @if (trim($rentOut->remark ?? ''))
            <div class="card border-0 shadow-sm mb-3" style="border-radius: 10px;">
                <div class="card-body px-3 py-2">
                    <div class="d-flex align-items-center gap-2">
                        <div class="bk-hdr-icon flex-shrink-0" style="background: #f1f5f9; width: 24px; height: 24px;">
                            <i class="fa fa-comment-o" style="color: #64748b; font-size: .7rem;"></i>
                        </div>
                        <p class="text-muted mb-0" style="font-size: .78rem;">{{ $rentOut->remark }}</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- MANAGEMENT FEE CONFIG (Submitted) --}}
        @if ($rentOut->booking_status === RentOutBookingStatus::Submitted)
            <div class="card border-0 shadow-sm mb-3 av bk-card">
                <div class="card-header bk-hdr border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <div class="bk-hdr-icon" style="background: #f5f3ff;">
                            <i class="fa fa-credit-card" style="color: #7c3aed; font-size: .7rem;"></i>
                        </div>
                        <span class="bk-hdr-title">Management Fee Configuration</span>
                    </div>
                </div>
                <div class="card-body px-2 py-2">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3" wire:ignore>
                            <label class="form-label mb-1 bk-lbl">Payment Method</label>
                            <select id="management_fee_payment_method_id" class="select-payment_method_id-list">
                                <option value="">Select...</option>
                                @if ($rentOut->managementFeePaymentMethod)
                                    <option value="{{ $rentOut->management_fee_payment_method_id }}" selected>
                                        {{ $rentOut->managementFeePaymentMethod->name }}
                                    </option>
                                @endif
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label mb-1 bk-lbl">Management Fee</label>
                            <input type="number" wire:model="management_fee" class="form-control form-control-sm" step="any" min="0" style="border-radius: 6px; font-size: .78rem;">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label mb-1 bk-lbl">Remarks</label>
                            <input type="text" wire:model="management_fee_remarks" class="form-control form-control-sm" style="border-radius: 6px; font-size: .78rem;">
                        </div>
                        <div class="col-md-2">
                            <button type="button" wire:click="saveManagementFee"
                                class="btn btn-sm w-100 text-white fw-medium"
                                style="background: linear-gradient(135deg, #4f46e5, #7c3aed); border: none; border-radius: 6px; font-size: .75rem;">
                                <i class="fa fa-save me-1"></i>Save
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- MANAGEMENT INFO (Non-submitted) --}}
        @if (
            $rentOut->booking_status !== RentOutBookingStatus::Submitted &&
                ($rentOut->management_fee || $rentOut->management_fee_remarks))
            <div class="card border-0 shadow-sm mb-3 av bk-card">
                <div class="card-header bk-hdr border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <div class="bk-hdr-icon" style="background: #f5f3ff;">
                            <i class="fa fa-credit-card" style="color: #7c3aed; font-size: .7rem;"></i>
                        </div>
                        <span class="bk-hdr-title">Management Information</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="d-flex justify-content-between align-items-center px-2 py-1 bk-row border-bottom">
                        <span class="bk-lbl">Management Fee</span>
                        <span class="bk-val">{{ number_format($rentOut->management_fee ?? 0, 2) }}</span>
                    </div>
                    @if ($rentOut->management_fee_remarks)
                        <div class="d-flex justify-content-between align-items-center px-2 py-1 bk-row border-bottom">
                            <span class="bk-lbl">Remarks</span>
                            <span class="bk-val text-end">{{ $rentOut->management_fee_remarks }}</span>
                        </div>
                    @endif
                    <div class="d-flex justify-content-between align-items-center px-2 py-1 bk-row">
                        <span class="bk-lbl">Payment Method</span>
                        <span class="bk-val">{{ $rentOut->managementFeePaymentMethod?->name ?? '—' }}</span>
                    </div>
                </div>
            </div>
        @endif

        {{-- MANAGEMENT TABS --}}
        @if ($rentOut->booking_status !== RentOutBookingStatus::Submitted)
            @include('livewire.rent-out.partials.management-tabs')
        @endif

        {{-- ACTION BUTTONS --}}
        @if ($rentOut->status?->value !== 'cancelled')
            <div class="card border-0 shadow-sm mb-3" style="border-radius: 10px;">
                <div class="card-body px-3 py-2">
                    <div class="d-flex flex-wrap align-items-center gap-1">
                        @if ($rentOut->booking_status !== RentOutBookingStatus::Completed)
                            @switch($rentOut->booking_status)
                                @case(RentOutBookingStatus::Submitted)
                                    @if (!$rentOut->financial_approved_by)
                                        @can($config->bookingFinancialApprovePermission)
                                            <button type="button" wire:click="statusChange('financial approved')"
                                                wire:confirm="Are you sure you want to financially approve this booking?"
                                                class="btn btn-sm fw-medium text-white px-2 py-1"
                                                style="font-size: .75rem; background: linear-gradient(135deg, #0ea5e9, #0284c7); border: none; border-radius: 6px;">
                                                <i class="fa fa-check me-1"></i>Financial Approve
                                            </button>
                                        @endcan
                                    @endif
                                @break

                                @case(RentOutBookingStatus::FinancialApproved)
                                    @if (!$rentOut->approved_by)
                                        @can($config->bookingApprovePermission)
                                            <button type="button" wire:click="statusChange('approved')"
                                                wire:confirm="Are you sure you want to legally approve this booking?"
                                                class="btn btn-sm fw-medium text-white px-2 py-1"
                                                style="font-size: .75rem; background: linear-gradient(135deg, #0ea5e9, #0284c7); border: none; border-radius: 6px;">
                                                <i class="fa fa-check me-1"></i>Legal Approve
                                            </button>
                                        @endcan
                                    @endif
                                @break

                                @case(RentOutBookingStatus::Approved)
                                    @if (!$rentOut->completed_by)
                                        @can($config->bookingCompletePermission)
                                            <button type="button" wire:click="statusChange('completed')"
                                                wire:confirm="Are you sure you want to complete this booking?"
                                                class="btn btn-sm fw-medium text-white px-2 py-1"
                                                style="font-size: .75rem; background: linear-gradient(135deg, #0ea5e9, #0284c7); border: none; border-radius: 6px;">
                                                <i class="fa fa-check me-1"></i>Complete
                                            </button>
                                        @endcan
                                    @endif
                                @break
                            @endswitch
                        @endif

                        @if ($rentOut->status?->value === 'booked')
                            @can($config->bookingCancelPermission)
                                <button type="button" wire:click="cancelBooking"
                                    wire:confirm="Are you sure you want to cancel this booking?"
                                    class="btn btn-sm fw-medium text-white px-2 py-1"
                                    style="font-size: .75rem; background: linear-gradient(135deg, #ef4444, #dc2626); border: none; border-radius: 6px;">
                                    <i class="fa fa-times me-1"></i>Cancel
                                </button>
                            @endcan
                        @endif

                        @if ($rentOut->booking_status === RentOutBookingStatus::Completed && $rentOut->status?->value === 'booked')
                            @can($config->bookingConfirmPermission)
                                <button type="button" wire:click="confirm"
                                    wire:confirm="Are you sure you want to confirm this booking? This will convert it to an active agreement."
                                    class="btn btn-sm fw-medium text-white px-2 py-1"
                                    style="font-size: .75rem; background: linear-gradient(135deg, #4f46e5, #7c3aed); border: none; border-radius: 6px;">
                                    <i class="fa fa-check-circle me-1"></i>Confirm Booking
                                </button>
                            @endcan
                        @endif
                    </div>
                </div>
            </div>
        @endif

        {{-- OVERLAP MODAL --}}
        <div class="modal fade @if ($showOverlapModal) show @endif"
            style="@if ($showOverlapModal) display: block; background: rgba(0,0,0,.45); @else display: none; @endif"
            tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
                    <div class="modal-header border-0 py-2 px-3" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                        <h6 class="modal-title fw-bold text-white" style="font-size: .85rem;">
                            <i class="fa fa-exclamation-triangle me-1"></i>Overlapping Agreements Found
                        </h6>
                        <button type="button" class="btn-close btn-close-white" style="font-size: .65rem;" wire:click="closeOverlapModal"></button>
                    </div>
                    <div class="modal-body px-3 py-2">
                        <p class="text-muted mb-2" style="font-size: .78rem;">
                            Agreements overlap with period
                            <strong>{{ $rentOut->start_date?->format('d M Y') }}</strong> to
                            <strong>{{ $rentOut->end_date?->format('d M Y') }}</strong>. Proceed?
                        </p>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0" style="font-size: .76rem;">
                                <thead>
                                    <tr style="background: #f8fafc;">
                                        <th class="py-1 px-2 fw-semibold text-muted border-0">ID</th>
                                        <th class="py-1 px-2 fw-semibold text-muted border-0">Customer</th>
                                        <th class="py-1 px-2 fw-semibold text-muted border-0">Start</th>
                                        <th class="py-1 px-2 fw-semibold text-muted border-0">End</th>
                                        <th class="py-1 px-2 fw-semibold text-muted border-0">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($overlappingRentouts as $overlap)
                                        <tr>
                                            <td class="py-1 px-2">{{ $overlap['id'] }}</td>
                                            <td class="py-1 px-2">{{ $overlap['customer'] }}</td>
                                            <td class="py-1 px-2">{{ $overlap['start_date'] }}</td>
                                            <td class="py-1 px-2">{{ $overlap['end_date'] }}</td>
                                            <td class="py-1 px-2">{{ $overlap['status'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer border-0 px-3 py-2">
                        <button type="button" class="btn btn-sm btn-light fw-medium px-2 py-1" wire:click="closeOverlapModal" style="font-size: .75rem; border-radius: 6px;">
                            <i class="fa fa-times me-1"></i>Cancel
                        </button>
                        <button type="button" class="btn btn-sm text-white fw-medium px-2 py-1" wire:click="confirmBooking"
                                style="font-size: .75rem; background: linear-gradient(135deg, #4f46e5, #7c3aed); border: none; border-radius: 6px;">
                            <i class="fa fa-check me-1"></i>Proceed
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="card border-0 shadow-sm" style="border-radius: 10px;">
            <div class="card-body text-center py-4">
                <div class="bk-hdr-icon mx-auto mb-2" style="width: 48px; height: 48px; background: #fef3c7;">
                    <i class="fa fa-exclamation-triangle" style="color: #d97706; font-size: 1.2rem;"></i>
                </div>
                <p class="text-muted mb-0" style="font-size: .82rem;">{{ $config->notFoundMessage }}</p>
            </div>
        </div>
    @endif
</div>

@script
    <script>
        $('#management_fee_payment_method_id').on('change', function(e) {
            const value = $(this).val() || null;
            @this.set('management_fee_payment_method_id', value);
        });
    </script>
@endscript

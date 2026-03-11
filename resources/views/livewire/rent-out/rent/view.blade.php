<div>
    @if($rentOut)
        @php
            $isRental = $rentOut->agreement_type?->value === 'rental';
            $title = $isRental ? 'Rental Agreement' : 'Sale Agreement';
            $totalRent = $rentOut->paymentTerms->sum('amount');
            $totalDiscount = $rentOut->paymentTerms->sum('discount');
            $totalPaid = $rentOut->paymentTerms->where('status', 'paid')->sum('total');
            $totalPending = $totalRent - $totalDiscount - $totalPaid;
            $paidMonths = $rentOut->paymentTerms->where('status', 'paid')->count();
            $securityTotal = $rentOut->securities->sum('amount');
            $utilitiesPaid = $rentOut->utilityTerms->sum('amount') - $rentOut->utilityTerms->sum('balance');
            $utilitiesPending = $rentOut->utilityTerms->sum('balance');
            $daysRemaining = now()->diffInDays($rentOut->end_date, false);
        @endphp

        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-1 fw-bold">{{ $title }} #{{ $rentOut->agreement_no }}</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 small">
                        <li class="breadcrumb-item"><a href="{{ route('property::rent::index') }}"><i class="fa fa-home"></i></a></li>
                        <li class="breadcrumb-item"><a href="{{ $isRental ? route('property::rent::index') : route('property::sale::index') }}">{{ $isRental ? 'Rental Agreements' : 'Sale Agreements' }}</a></li>
                        <li class="breadcrumb-item active">{{ $rentOut->agreement_no }}</li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex align-items-center gap-2">
                @if($rentOut->status)
                    <span class="badge bg-{{ $rentOut->status->color() }} fs-6 px-3 py-2">{{ $rentOut->status->label() }}</span>
                @endif
                @can('rent out.edit')
                    @if($rentOut->status?->value === 'booked')
                        <a href="{{ route('property::rent::booking.create', $rentOut->id) }}" class="btn btn-sm btn-outline-primary">
                            <i class="fa fa-pencil me-1"></i> Edit
                        </a>
                    @else
                        <a href="{{ route('property::rent::create', $rentOut->id) }}" class="btn btn-sm btn-outline-primary">
                            <i class="fa fa-pencil me-1"></i> Edit
                        </a>
                    @endif
                @endcan
            </div>
        </div>

        {{-- 3-Column Overview Cards --}}
        <div class="row g-3 mb-4">
            {{-- Column 1: Property & Customer Information --}}
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header text-white py-2" style="background: linear-gradient(135deg, #0d6efd, #0a58ca);">
                        <h6 class="mb-0 fw-semibold"><i class="fa fa-home me-2"></i>Property & Customer Information</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <div class="list-group-item py-2 px-3">
                                <div class="row align-items-center">
                                    <div class="col-5 fw-semibold small">Reference No:</div>
                                    <div class="col-7 small">{{ $rentOut->agreement_no }}</div>
                                </div>
                            </div>
                            <div class="list-group-item py-2 px-3">
                                <div class="row align-items-center">
                                    <div class="col-5 fw-semibold small">Group:</div>
                                    <div class="col-7 small">{{ $rentOut->group?->name ?? 'N/A' }}</div>
                                </div>
                            </div>
                            <div class="list-group-item py-2 px-3">
                                <div class="row align-items-center">
                                    <div class="col-5 fw-semibold small">Building:</div>
                                    <div class="col-7 small">{{ $rentOut->building?->name ?? 'N/A' }}</div>
                                </div>
                            </div>
                            <div class="list-group-item py-2 px-3">
                                <div class="row align-items-center">
                                    <div class="col-5 fw-semibold small">Type:</div>
                                    <div class="col-7 small">{{ $rentOut->type?->name ?? 'N/A' }}</div>
                                </div>
                            </div>
                            <div class="list-group-item py-2 px-3">
                                <div class="row align-items-center">
                                    <div class="col-5 fw-semibold small">Property/Unit:</div>
                                    <div class="col-7 small">{{ $rentOut->property?->number ?? 'N/A' }}</div>
                                </div>
                            </div>
                            <div class="list-group-item py-2 px-3">
                                <div class="row align-items-center">
                                    <div class="col-5 fw-semibold small">Customer:</div>
                                    <div class="col-7 small">{{ $rentOut->customer?->name ?? 'N/A' }}</div>
                                </div>
                            </div>
                            <div class="list-group-item py-2 px-3">
                                <div class="row align-items-center">
                                    <div class="col-5 fw-semibold small">Vacate Date:</div>
                                    <div class="col-7 small">
                                        @if($rentOut->vacate_date)
                                            <span class="{{ $rentOut->vacate_date > now() ? 'text-warning' : 'text-muted' }}">
                                                {{ $rentOut->vacate_date->format('d-m-Y') }}
                                            </span>
                                        @else
                                            <span class="text-muted">&mdash;</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="list-group-item py-2 px-3">
                                <div class="row align-items-center">
                                    <div class="col-5 fw-semibold small">Status:</div>
                                    <div class="col-7">
                                        @if($rentOut->status)
                                            <span class="badge bg-{{ $rentOut->status->color() }}">{{ $rentOut->status->label() }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Column 2: Agreement Details --}}
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header text-white py-2" style="background: linear-gradient(135deg, #0dcaf0, #0aa2c0);">
                        <h6 class="mb-0 fw-semibold"><i class="fa fa-file-text-o me-2"></i>{{ $title }} Details</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <div class="list-group-item py-2 px-3">
                                <div class="row align-items-center">
                                    <div class="col-5 fw-semibold small">Start Date:</div>
                                    <div class="col-7 small">{{ $rentOut->start_date?->format('d-m-Y') }}</div>
                                </div>
                            </div>
                            <div class="list-group-item py-2 px-3">
                                <div class="row align-items-center">
                                    <div class="col-5 fw-semibold small">End Date:</div>
                                    <div class="col-7 small">
                                        {{ $rentOut->end_date?->format('d-m-Y') }}
                                        <br>
                                        <small class="{{ $daysRemaining > 0 ? 'text-success' : 'text-danger' }}">
                                            @if($daysRemaining > 0)
                                                ({{ $daysRemaining }} days remaining)
                                            @else
                                                (Expired {{ abs($daysRemaining) }} days ago)
                                            @endif
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="list-group-item py-2 px-3">
                                <div class="row align-items-center">
                                    <div class="col-5 fw-semibold small">{{ $isRental ? 'Booking Type:' : 'Agreement Type:' }}</div>
                                    <div class="col-7 small">{{ $isRental ? $rentOut->booking_type : $rentOut->agreement_type?->label() }}</div>
                                </div>
                            </div>
                            <div class="list-group-item py-2 px-3">
                                <div class="row align-items-center">
                                    <div class="col-5 fw-semibold small">Salesman:</div>
                                    <div class="col-7 small">{{ $rentOut->salesman?->name ?? 'N/A' }}</div>
                                </div>
                            </div>
                            <div class="list-group-item py-2 px-3">
                                <div class="row align-items-center">
                                    <div class="col-5 fw-semibold small">Duration:</div>
                                    <div class="col-7 small">{{ $rentOut->totalStay() }} months</div>
                                </div>
                            </div>
                            <div class="list-group-item py-2 px-3">
                                <div class="row align-items-center">
                                    <div class="col-5 fw-semibold small">Free Months:</div>
                                    <div class="col-7 small">{{ $rentOut->free_month ?? 0 }}</div>
                                </div>
                            </div>
                            <div class="list-group-item py-2 px-3">
                                <div class="row align-items-center">
                                    <div class="col-5 fw-semibold small">{{ $isRental ? 'Monthly Rent:' : 'Sale Price:' }}</div>
                                    <div class="col-7 small text-success fw-bold">{{ number_format($rentOut->rent, 2) }}</div>
                                </div>
                            </div>
                            <div class="list-group-item py-2 px-3">
                                <div class="row align-items-center">
                                    <div class="col-5 fw-semibold small">Security Amount:</div>
                                    <div class="col-7 small text-info fw-bold">{{ number_format($securityTotal, 2) }}</div>
                                </div>
                            </div>
                            <div class="list-group-item py-2 px-3">
                                <div class="row align-items-center">
                                    <div class="col-5 fw-semibold small">SOA Statement:</div>
                                    <div class="col-7">
                                        <a href="{{ route('print::rentout::statement', $rentOut->id) }}" target="_blank" class="btn btn-sm btn-primary py-0 px-2">
                                            <i class="fa fa-print me-1"></i> Print
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="list-group-item py-2 px-3">
                                <div class="row align-items-center">
                                    <div class="col-5 fw-semibold small">SOA Utilities:</div>
                                    <div class="col-7">
                                        <a href="{{ route('print::rentout::utilities-statement', $rentOut->id) }}" target="_blank" class="btn btn-sm btn-primary py-0 px-2">
                                            <i class="fa fa-print me-1"></i> Print
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Financial Summary --}}
                        <div class="bg-light border-top p-3">
                            <h6 class="small fw-bold mb-2"><i class="fa fa-calculator me-1"></i> Financial Summary</h6>
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="small text-muted">Total Amount</div>
                                    <div class="fw-bold text-primary">{{ number_format($totalRent, 2) }}</div>
                                </div>
                                <div class="col-4">
                                    <div class="small text-muted">Total Discount</div>
                                    <div class="fw-bold text-warning">{{ number_format($totalDiscount, 2) }}</div>
                                </div>
                                <div class="col-4">
                                    <div class="small text-muted">Total Payments</div>
                                    <div class="fw-bold text-success">{{ number_format($totalPaid, 2) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Column 3: Collection Information --}}
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header text-white py-2" style="background: linear-gradient(135deg, #198754, #157347);">
                        <h6 class="mb-0 fw-semibold"><i class="fa fa-money me-2"></i>Collection Information</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <div class="list-group-item py-2 px-3">
                                <div class="row align-items-center">
                                    <div class="col-6 fw-semibold small">Frequency:</div>
                                    <div class="col-6 small">{{ $rentOut->payment_frequency }}</div>
                                </div>
                            </div>
                            <div class="list-group-item py-2 px-3">
                                <div class="row align-items-center">
                                    <div class="col-6 fw-semibold small">Starting Date:</div>
                                    <div class="col-6 small">{{ $rentOut->collection_starting_day }}</div>
                                </div>
                            </div>
                            <div class="list-group-item py-2 px-3">
                                <div class="row align-items-center">
                                    <div class="col-6 fw-semibold small">Payment Mode:</div>
                                    <div class="col-6 small">{{ $rentOut->collection_payment_mode?->label() ?? '&mdash;' }}</div>
                                </div>
                            </div>
                            @if($rentOut->collection_payment_mode?->value === 'cheque')
                                <div class="list-group-item py-2 px-3">
                                    <div class="row align-items-center">
                                        <div class="col-6 fw-semibold small">Bank Name:</div>
                                        <div class="col-6 small">{{ $rentOut->collection_bank_name }}</div>
                                    </div>
                                </div>
                                <div class="list-group-item py-2 px-3">
                                    <div class="row align-items-center">
                                        <div class="col-6 fw-semibold small">Cheque Starting No:</div>
                                        <div class="col-6 small">{{ $rentOut->collection_cheque_no }}</div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Payment Status Summary --}}
                        <div class="bg-light border-top p-3">
                            <div class="text-center mb-2">
                                <span class="small fw-bold">Paid Months: <span class="text-primary">{{ $paidMonths }}</span></span>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-striped mb-0" style="font-size: 0.8rem;">
                                    <thead>
                                        <tr>
                                            <th class="py-1 px-2">Type</th>
                                            <th class="py-1 px-2 text-end">Paid</th>
                                            <th class="py-1 px-2 text-end">Pending</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="py-1 px-2 fw-semibold">{{ $isRental ? 'Rent' : 'Sale' }}</td>
                                            <td class="py-1 px-2 text-end text-success fw-bold">{{ number_format($totalPaid, 2) }}</td>
                                            <td class="py-1 px-2 text-end text-danger fw-bold">{{ number_format($totalPending, 2) }}</td>
                                        </tr>
                                        @if($isRental)
                                            <tr>
                                                <td class="py-1 px-2 fw-semibold">Utilities</td>
                                                <td class="py-1 px-2 text-end text-success fw-bold">{{ number_format($utilitiesPaid, 2) }}</td>
                                                <td class="py-1 px-2 text-end text-danger fw-bold">{{ number_format($utilitiesPending, 2) }}</td>
                                            </tr>
                                        @endif
                                        @if($rentOut->management_fee > 0)
                                            <tr>
                                                <td class="py-1 px-2 fw-semibold">Management Fee</td>
                                                <td class="py-1 px-2 text-end text-success fw-bold">{{ number_format($rentOut->management_fee, 2) }}</td>
                                                <td class="py-1 px-2 text-end text-danger fw-bold">0.00</td>
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

        {{-- Remarks Section --}}
        @if(trim($rentOut->remark ?? ''))
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-2 border-bottom">
                    <h6 class="mb-0 fw-semibold"><i class="fa fa-comment-o me-2"></i>Remarks</h6>
                </div>
                <div class="card-body py-3">
                    <div class="alert alert-info mb-0" style="background-color: #e8f4fd; border-color: #bee5eb;">
                        {{ $rentOut->remark }}
                    </div>
                </div>
            </div>
        @endif

        {{-- Management Sections (Shared Partial) --}}
        @include('livewire.rent-out.partials.management-tabs')

        {{-- Modals (Shared Partial) --}}
        @include('livewire.rent-out.partials.payment-term-modals')
    @else
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="fa fa-exclamation-triangle text-warning fs-1 mb-3 d-block"></i>
                <p class="text-muted mb-0">Rental agreement not found.</p>
            </div>
        </div>
    @endif
</div>

{{-- Scripts (Shared Partial) --}}
@include('livewire.rent-out.partials.payment-term-scripts')

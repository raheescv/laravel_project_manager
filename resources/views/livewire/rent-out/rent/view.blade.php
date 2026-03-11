<div>
    @if($rentOut)
        <div class="card mb-3">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Rental Agreement #{{ $rentOut->agreement_no }}</h4>
                    <div class="d-flex align-items-center gap-2">
                        @if($rentOut->status)
                            <span class="badge bg-{{ $rentOut->status->color() }} fs-6">{{ $rentOut->status->label() }}</span>
                        @endif
                        @can('rent out.edit')
                            @if($rentOut->status?->value === 'booked')
                                <a href="{{ route('property::rent::booking.create', $rentOut->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="demo-psi-pencil me-1"></i> Edit
                                </a>
                            @else
                                <a href="{{ route('property::rent::create', $rentOut->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="demo-psi-pencil me-1"></i> Edit
                                </a>
                            @endif
                        @endcan
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5>Agreement Details</h5>
                        <table class="table table-bordered">
                            <tr>
                                <th width="40%">Agreement No</th>
                                <td>{{ $rentOut->agreement_no }}</td>
                            </tr>
                            <tr>
                                <th>Reference No</th>
                                <td>{{ $rentOut->reference_no }}</td>
                            </tr>
                            <tr>
                                <th>Agreement Type</th>
                                <td>{{ $rentOut->agreement_type?->label() }}</td>
                            </tr>
                            <tr>
                                <th>Booking Type</th>
                                <td>{{ $rentOut->booking_type }}</td>
                            </tr>
                            <tr>
                                <th>Start Date</th>
                                <td>{{ $rentOut->start_date?->format('Y-m-d') }}</td>
                            </tr>
                            <tr>
                                <th>End Date</th>
                                <td>{{ $rentOut->end_date?->format('Y-m-d') }}</td>
                            </tr>
                            <tr>
                                <th>Vacate Date</th>
                                <td>{{ $rentOut->vacate_date?->format('Y-m-d') }}</td>
                            </tr>
                            <tr>
                                <th>Total Stay (Months)</th>
                                <td>{{ $rentOut->totalStay() }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h5>Customer & Property</h5>
                        <table class="table table-bordered">
                            <tr>
                                <th width="40%">Customer</th>
                                <td>{{ $rentOut->customer?->name }}</td>
                            </tr>
                            <tr>
                                <th>Property</th>
                                <td>{{ $rentOut->property?->name }}</td>
                            </tr>
                            <tr>
                                <th>Building</th>
                                <td>{{ $rentOut->building?->name }}</td>
                            </tr>
                            <tr>
                                <th>Group</th>
                                <td>{{ $rentOut->group?->name }}</td>
                            </tr>
                            <tr>
                                <th>Salesman</th>
                                <td>{{ $rentOut->salesman?->name }}</td>
                            </tr>
                        </table>

                        <h5>Financial Details</h5>
                        <table class="table table-bordered">
                            <tr>
                                <th width="40%">Rent</th>
                                <td>{{ number_format($rentOut->rent, 2) }}</td>
                            </tr>
                            <tr>
                                <th>Discount</th>
                                <td>{{ number_format($rentOut->discount, 2) }}</td>
                            </tr>
                            <tr>
                                <th>Total</th>
                                <td><strong>{{ number_format($rentOut->total, 2) }}</strong></td>
                            </tr>
                            <tr>
                                <th>Management Fee</th>
                                <td>{{ number_format($rentOut->management_fee, 2) }}</td>
                            </tr>
                            <tr>
                                <th>Down Payment</th>
                                <td>{{ number_format($rentOut->down_payment, 2) }}</td>
                            </tr>
                            <tr>
                                <th>Payment Mode</th>
                                <td>{{ $rentOut->collection_payment_mode?->label() }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                @if($rentOut->remark)
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h5>Remarks</h5>
                            <p>{{ $rentOut->remark }}</p>
                        </div>
                    </div>
                @endif

                {{-- Payment Terms --}}
                <div class="row mb-4">
                    <div class="col-md-12">
                        <h5>Payment Terms</h5>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Due Date</th>
                                        <th>Amount</th>
                                        <th>Discount</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Paid Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($rentOut->paymentTerms as $index => $term)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $term->due_date?->format('Y-m-d') }}</td>
                                            <td>{{ number_format($term->amount, 2) }}</td>
                                            <td>{{ number_format($term->discount, 2) }}</td>
                                            <td>{{ number_format($term->total, 2) }}</td>
                                            <td>
                                                <span class="badge bg-{{ $term->status === 'paid' ? 'success' : ($term->status === 'pending' && $term->due_date < now() ? 'danger' : 'warning') }}">
                                                    {{ ucfirst($term->status) }}
                                                </span>
                                            </td>
                                            <td>{{ $term->paid_date?->format('Y-m-d') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center">No payment terms found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Securities --}}
                <div class="row mb-4">
                    <div class="col-md-12">
                        <h5>Security Deposits</h5>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Amount</th>
                                        <th>Payment Mode</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Due Date</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($rentOut->securities as $index => $security)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ number_format($security->amount, 2) }}</td>
                                            <td>{{ $security->payment_mode?->label() }}</td>
                                            <td>{{ $security->type?->label() }}</td>
                                            <td>
                                                <span class="badge bg-{{ $security->status?->color() }}">
                                                    {{ $security->status?->label() }}
                                                </span>
                                            </td>
                                            <td>{{ $security->due_date?->format('Y-m-d') }}</td>
                                            <td>{{ $security->remarks }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center">No security deposits found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="card">
            <div class="card-body text-center">
                <p>Rental agreement not found.</p>
            </div>
        </div>
    @endif
</div>

<div>
    <div class="col-md-12 mb-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <!-- Invoice Information -->
                <div class="row g-3 mb-2">
                    <!-- Main Info Section -->
                    <div class="row g-2">
                        <!-- Left Column - Invoice Info -->
                        <div class="col-lg-4">
                            <div class="glass-card h-100">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="icon-box bg-primary bg-opacity-10 rounded-circle p-2 me-2">
                                            <i class="demo-psi-file text-primary-gradient fs-4"></i>
                                        </div>
                                        <div>
                                            <h5 class="mb-0 text-primary-gradient">Invoice Details</h5>
                                            <small class="text-muted">#{{ $sale->invoice_no }}</small>
                                        </div>
                                    </div>

                                    <div class="info-list">
                                        <div class="row g-2">
                                            <!-- Date & Due Date -->
                                            <div class="col-6">
                                                <div class="info-item p-2 hover-lift rounded bg-light bg-opacity-50">
                                                    <div class="d-flex align-items-center mb-1">
                                                        <i class="demo-psi-calendar-4 text-primary me-2"></i>
                                                        <small class="text-muted">Date</small>
                                                    </div>
                                                    <div class="fw-medium">{{ systemDate($sale->date) }}</div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="info-item p-2 hover-lift rounded bg-light bg-opacity-50">
                                                    <div class="d-flex align-items-center mb-1">
                                                        <i class="demo-psi-calendar-4 text-danger me-2"></i>
                                                        <small class="text-muted">Due</small>
                                                    </div>
                                                    <div class="fw-medium">{{ systemDate($sale->due_date) }}</div>
                                                </div>
                                            </div>

                                            <!-- Reference & Status -->
                                            <div class="col-6">
                                                <div class="info-item p-2 hover-lift rounded bg-light bg-opacity-50">
                                                    <div class="d-flex align-items-center mb-1">
                                                        <i class="fa fa-file-text-o text-info me-2"></i>
                                                        <small class="text-muted">Reference</small>
                                                    </div>
                                                    <div class="fw-medium">{{ $sale->reference_no ?: 'N/A' }}</div>
                                                </div>
                                            </div>

                                            <!-- Type -->
                                            <div class="col-6">
                                                <div class="info-item p-2 hover-lift rounded bg-light bg-opacity-50">
                                                    <div class="d-flex align-items-center mb-1">
                                                        <i class="demo-psi-tag-2 text-success me-2"></i>
                                                        <small class="text-muted">Type</small>
                                                    </div>
                                                    <div class="fw-medium">{{ ucfirst($sale->sale_type) }}</div>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="info-item p-2 hover-lift rounded bg-light bg-opacity-50">
                                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                                        <div class="d-flex align-items-center">
                                                            <i class="demo-psi-clock {{ $sale->status === 'completed' ? 'text-success' : ($sale->status === 'cancelled' ? 'text-danger' : 'text-warning') }} me-2"></i>
                                                            <small class="text-muted">Status</small>
                                                        </div>
                                                        <small class="text-muted">{{ systemDateTime($sale->updated_at) }}</small>
                                                    </div>
                                                    <span class="badge {{ $sale->status === 'completed' ? 'bg-success' : ($sale->status === 'cancelled' ? 'bg-danger' : 'bg-warning') }} rounded-pill">
                                                        {{ ucfirst($sale->status) }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column - Customer Info -->
                        <div class="col-lg-8">
                            <div class="glass-card h-100">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="d-flex align-items-center">
                                            <div class="icon-box bg-success bg-opacity-10 rounded-circle p-2 me-2">
                                                <i class="demo-psi-male text-success-gradient fs-4"></i>
                                            </div>
                                            <div>
                                                <h5 class="mb-0 text-success-gradient">Customer Information</h5>
                                                <small class="text-muted">Customer #{{ $sale->account_id }}</small>
                                            </div>
                                        </div>
                                        <a href="{{ route('account::customer::view', $sale->account_id) }}" class="btn btn-sm btn-outline-success hover-lift">
                                            <i class="fa fa-user me-1"></i>Profile
                                        </a>
                                    </div>

                                    <div class="row g-2">
                                        <!-- Customer Details -->
                                        <div class="col-sm-6">
                                            <div class="customer-info p-3 bg-light bg-opacity-50 rounded h-100">
                                                <h6 class="mb-2 text-primary d-flex align-items-center">
                                                    <i class="demo-psi-id-card me-2"></i>Customer Details
                                                </h6>
                                                <div class="ps-1">
                                                    <div class="mb-2">
                                                        <small class="text-muted d-block">Name</small>
                                                        <span class="fw-medium"> <a href="{{ route('account::customer::view', $sale->account_id) }}">{{ $sale->account?->name }}</a> </span>
                                                    </div>
                                                    @if ($sale->customer_name)
                                                        <div>
                                                            <small class="text-muted d-block">Display Name</small>
                                                            <span class="fw-medium">{{ $sale->customer_name }}</span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Contact Info -->
                                        <div class="col-sm-6">
                                            <div class="contact-info p-3 bg-light bg-opacity-50 rounded h-100">
                                                <h6 class="mb-2 text-primary d-flex align-items-center">
                                                    <i class="fa fa-phone me-2"></i>Contact Details
                                                </h6>
                                                <div class="ps-1">
                                                    @if ($sale->customer_mobile || $sale->account?->mobile)
                                                        <div class="mb-2">
                                                            <small class="text-muted d-block">Mobile</small>
                                                            <span class="fw-medium">{{ $sale->customer_mobile ?: $sale->account?->mobile }}</span>
                                                        </div>
                                                    @endif
                                                    @if ($sale->account?->email)
                                                        <div>
                                                            <small class="text-muted d-block">Email</small>
                                                            <span class="fw-medium">{{ $sale->account?->email }}</span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Feedback Section -->
                    @if ($sale->rating || $sale->feedback)
                        <div class="col-12 mb-2">
                            <div class="card border-0 bg-light">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="avatar avatar-lg bg-primary bg-opacity-10 rounded-circle me-3">
                                            <i class="demo-psi-like text-primary fs-2"></i>
                                        </div>
                                        <div>
                                            <h5 class="mb-1">Customer Feedback</h5>
                                            <small class="text-muted">Customer Experience Details</small>
                                        </div>
                                    </div>

                                    <div class="row g-4">
                                        @if ($sale->rating)
                                            <div class="col-md-4">
                                                <div class="p-4 bg-white rounded-3 shadow-sm h-100">
                                                    <h6 class="mb-3">Rating Score</h6>
                                                    <div class="d-flex align-items-center">
                                                        <div class="display-4 fw-bold text-primary me-3">{{ $sale->rating }}/5</div>
                                                        <div class="stars">
                                                            @for ($i = 1; $i <= 5; $i++)
                                                                <i class="fa fa-star fs-4 {{ $sale->rating >= $i ? 'text-warning' : 'text-muted' }}"></i>
                                                            @endfor
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif

                                        @if ($sale->feedback)
                                            <div class="col-md-8">
                                                <div class="p-4 bg-white rounded-3 shadow-sm h-100">
                                                    <h6 class="mb-3">Customer Comments</h6>
                                                    <p class="mb-0">{{ $sale->feedback }}</p>
                                                    @if ($sale->feedback_type)
                                                        <div class="mt-3">
                                                            <span class="badge bg-info bg-opacity-10 text-info">{{ $sale->feedback_type }}</span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Package Items Section (New) -->
                @if (count($sale->comboOffers) > 0)
                    <div class="mb-2">
                        <h5 class="card-title mb-3">
                            <i class="demo-psi-box-2 me-2"></i>Combo Offer Items
                        </h5>
                        <div class="row g-3">
                            @foreach ($sale->comboOffers as $item)
                                <div class="col-md-6 col-lg-6">
                                    <div class="card h-100 package-card border shadow-sm">
                                        <div class="card-header bg-primary bg-opacity-10 py-3">
                                            <h6 class="mb-0">
                                                <i class="demo-psi-box me-2"></i>
                                                {{ $item->comboOffer->name }}
                                            </h6>
                                        </div>
                                        <div class="card-body p-1">
                                            <div class="package-stats d-flex justify-content-between mb-1 p-2 bg-light rounded">
                                                <div class="text-center">
                                                    <div class="fw-bold text-primary">{{ $item->items->count() }}</div>
                                                    <small class="text-muted">Services</small>
                                                </div>
                                                <div class="text-center">
                                                    <div class="fw-bold text-success">{{ currency($item->amount) }}</div>
                                                    <small class="text-muted">Combo Offer Price</small>
                                                </div>
                                            </div>
                                            <div class="package-items">
                                                @foreach ($item->items as $item)
                                                    <div class="d-flex justify-content-between align-items-center p-2 border-bottom">
                                                        <div>
                                                            <div class="fw-semibold">{{ $item->product->name }}</div>
                                                            <small class="text-muted">{{ $item->employee->name }}</small>
                                                        </div>
                                                        <div class="text-end">
                                                            <div class="text-success">{{ currency($item->unit_price - $item->discount) }}</div>
                                                            @if ($item->discount > 0)
                                                                <small class="text-decoration-line-through text-muted">{{ currency($item->unit_price) }}</small>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Items Table with enhanced styling -->
                <div class="mb-4">
                    <h5 class="card-title d-flex align-items-center mb-3">
                        <i class="fa fa-shopping-cart me-2"></i>
                        Items
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-sm align-middle">
                            <thead>
                                <tr class="bg-primary text-white">
                                    <th class="text-white rounded-start">SL No</th>
                                    <th width="20%" class="text-white">Product/Service</th>
                                    <th class="text-white text-end">Unit Price</th>
                                    <th class="text-white text-end">Quantity</th>
                                    <th class="text-white text-end">Discount</th>
                                    <th class="text-white text-end">Tax %</th>
                                    <th class="text-white text-end">Total</th>
                                    @if ($sales['other_discount'] > 0)
                                        <th class="text-white text-end rounded-end">Effective Total</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $result = [];
                                    foreach ($items as $key => $value) {
                                        [$parent, $sub] = explode('-', $key);
                                        if (!isset($result[$parent])) {
                                            $result[$parent] = [];
                                        }
                                        $result[$parent][$sub] = $value;
                                    }
                                    $data = $result;
                                @endphp
                                @foreach ($data as $employee_id => $groupedItems)
                                    <tr>
                                        @php
                                            $first = array_values($groupedItems)[0];
                                        @endphp
                                        <th colspan="8" class="bg-light">
                                            <i class="demo-psi-user me-2"></i>
                                            {{ $first['employee_name'] }}
                                        </th>
                                    </tr>
                                    @foreach ($groupedItems as $item)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>
                                                <div class="d-flex align-items-start">
                                                    <i class="fa fa-cube text-primary me-2 fs-5"></i>
                                                    <div>
                                                        <a href="{{ route('inventory::product::view', $item['product_id']) }}" class="text-primary fw-semibold">{{ $item['name'] }}</a>
                                                        <div class="mt-1">
                                                            @if (!empty($item['sale_combo_offer_id']))
                                                                <span class="badge bg-info text-white me-1">Combo</span>
                                                            @endif
                                                            @if (!empty($item['assistant_name']))
                                                                <small class="text-muted">
                                                                    <i class="fa fa-user-plus me-1"></i>{{ $item['assistant_name'] }}
                                                                </small>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-end">{{ currency($item['unit_price']) }}</td>
                                            <td class="text-end">{{ currency($item['quantity'], 3) }}</td>
                                            <td class="text-end">
                                                @if ($item['discount'] != 0)
                                                    {{ currency($item['discount']) }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                @if ($item['tax_amount'] != 0)
                                                    {{ currency($item['tax_amount']) }} ({{ round($item['tax'], 2) }}%)
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="text-end fw-bold">{{ currency($item['total']) }}</td>
                                            @if ($sales['other_discount'] > 0)
                                                <td class="text-end fw-bold">{{ currency($item['effective_total']) }}</td>
                                            @endif
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                            <tfoot class="bg-light fw-bold">
                                @php
                                    $items = collect($items);
                                @endphp
                                <tr>
                                    <th colspan="3" class="text-end">Total</th>
                                    <th class="text-end">{{ currency($items->sum('quantity'), 3) }}</th>
                                    <th class="text-end">{{ currency($items->sum('discount')) }}</th>
                                    <th class="text-end">{{ currency($items->sum('tax_amount')) }}</th>
                                    <th class="text-end">{{ currency($items->sum('total')) }}</th>
                                    @if ($sales['other_discount'] > 0)
                                        <th class="text-end">{{ currency($items->sum('effective_total')) }}</th>
                                    @endif
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Payments and Totals with enhanced styling -->
                <div class="row g-4">
                    {{--  Payments area --}}
                    <div class="col-12 col-md-5">
                        <div class="card h-100 shadow-sm">
                            <div class="card-header bg-light d-flex align-items-center justify-content-between py-3">
                                <h5 class="card-title mb-0 d-flex align-items-center">
                                    <i class="demo-psi-wallet fs-4 me-2 text-primary"></i>
                                    Payment Details
                                </h5>
                                <span class="badge bg-primary rounded-pill">
                                    {{ count($payments) }} Payments
                                </span>
                            </div>
                            <div class="card-body p-0">
                                <div class="payment-list">
                                    @foreach ($payments as $key => $item)
                                        <div class="payment-item p-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <div class="d-flex align-items-center">
                                                    <div class="payment-icon me-3">
                                                        @switch(strtolower($item['name']))
                                                            @case('cash')
                                                                <div class="avatar avatar-md bg-success bg-opacity-10">
                                                                    <i class="fa fa-money text-success"></i>
                                                                </div>
                                                            @break

                                                            @case('card')
                                                                <div class="avatar avatar-md bg-info bg-opacity-10">
                                                                    <i class="fa fa-credit-card text-info"></i>
                                                                </div>
                                                            @break

                                                            @default
                                                                <div class="avatar avatar-md bg-warning bg-opacity-10">
                                                                    <i class="fa fa-university text-warning"></i>
                                                                </div>
                                                        @endswitch
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-1">{{ $item['name'] }}</h6>
                                                        <small class="text-muted">
                                                            Transaction #{{ str_pad($key + 1, 3, '0', STR_PAD_LEFT) }}
                                                        </small>
                                                    </div>
                                                </div>
                                                <div class="text-end">
                                                    <h5 class="mb-1 text-success">{{ currency($item['amount']) }}</h5>
                                                    <small class="text-muted">{{ systemDate($item['date']) }}</small>
                                                </div>
                                            </div>
                                            @if (isset($item['reference']))
                                                <div class="payment-details bg-light bg-opacity-50 rounded p-2 mt-2">
                                                    <small class="text-muted d-flex align-items-center">
                                                        <i class="demo-psi-file-text me-2"></i>
                                                        Reference: {{ $item['reference'] }}
                                                    </small>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>

                                <div class="p-3 bg-light border-top">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0">Total Paid</h6>
                                            <small class="text-muted">All payments combined</small>
                                        </div>
                                        <h4 class="mb-0 text-success">{{ currency(collect($payments)->sum('amount')) }}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <style>
                            .payment-list {
                                max-height: 400px;
                                overflow-y: auto;
                            }

                            .payment-item {
                                transition: all 0.2s ease;
                            }

                            .payment-item:hover {
                                background-color: var(--bs-light);
                            }

                            .avatar {
                                width: 40px;
                                height: 40px;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                border-radius: 12px;
                            }

                            .avatar.avatar-md {
                                width: 48px;
                                height: 48px;
                            }

                            .payment-details {
                                transition: all 0.2s ease;
                            }

                            .payment-item:hover .payment-details {
                                background-color: var(--bs-light) !important;
                            }
                        </style>
                    </div>
                    <div class="col-12 col-md-7">
                        <div class="card shadow-sm">
                            <div class="card-header bg-light py-3">
                                <h5 class="card-title mb-0">
                                    <i class="demo-psi-calculator me-2"></i>
                                    Financial Summary
                                </h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="row g-0">
                                    <div class="col-md-6 border-end">
                                        <div class="p-3">
                                            <div class="d-flex align-items-center mb-3">
                                                <div class="icon-sm bg-primary bg-opacity-10 rounded-circle me-2">
                                                    <i class="fa fa-money text-primary"></i>
                                                </div>
                                                <h6 class="mb-0">Base Amounts</h6>
                                            </div>
                                            <div class="list-group list-group-flush">
                                                <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-0">
                                                    <span class="text-muted">Gross Total</span>
                                                    <span class="fw-medium">{{ currency($sale->gross_amount) }}</span>
                                                </div>
                                                <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-0">
                                                    <span class="text-muted">Sale Total</span>
                                                    <span class="fw-medium">{{ currency($sale->total) }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="p-3">
                                            <div class="d-flex align-items-center mb-3">
                                                <div class="icon-sm bg-warning bg-opacity-10 rounded-circle me-2">
                                                    <i class="demo-psi-receipt-4 text-warning"></i>
                                                </div>
                                                <h6 class="mb-0">Adjustments</h6>
                                            </div>
                                            <div class="list-group list-group-flush">
                                                <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-0">
                                                    <span class="text-muted">Other Discount</span>
                                                    <span class="fw-medium text-danger">-{{ currency($sale->other_discount) }}</span>
                                                </div>
                                                <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-0">
                                                    <span class="text-muted">Freight</span>
                                                    <span class="fw-medium">{{ currency($sale->freight) }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="border-top p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="d-flex align-items-center">
                                            <div class="icon-sm bg-success bg-opacity-10 rounded-circle me-2">
                                                <i class="fa fa-credit-card text-success"></i>
                                            </div>
                                            <h6 class="mb-0">Final Summary</h6>
                                        </div>
                                    </div>
                                    <div class="list-group list-group-flush">
                                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-0">
                                            <span class="h6 mb-0">Total Payable Amount</span>
                                            <span class="h5 mb-0 text-success">{{ currency($sale->grand_total) }}</span>
                                        </div>
                                        @if ($sale->balance != 0)
                                            <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-0">
                                                <span class="text-muted">Amount Paid</span>
                                                <span class="fw-medium text-success">{{ currency($sale->paid) }}</span>
                                            </div>
                                            <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-0">
                                                <span class="text-muted">Balance Due</span>
                                                <span class="fw-medium text-danger">{{ currency($sale->balance) }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex justify-content-end gap-2 my-4 d-print-none">
                    @if ($sales['status'] != 'cancelled')
                        <a target="_blank" href="{{ route('print::sale::invoice', $sales['id']) }}" class="btn btn-light btn-icon" title="Print Invoice">
                            <i class="demo-pli-printer fs-4"></i>
                        </a>
                        @can('sale.cancel')
                            <button type="button" wire:click='save("cancelled")' wire:confirm="Are you sure to cancel this?" class="btn btn-danger">
                                <i class="demo-psi-cross me-2"></i>Cancel
                            </button>
                        @endcan
                        @can('sale.edit completed')
                            <a href="{{ route('sale::edit', $sales['id']) }}" type="button" class="btn btn-primary">
                                <i class="demo-psi-pen-5 me-2"></i>Edit
                            </a>
                        @endcan
                        @can('sale.cancel')
                            <button type="button" wire:click='sendToWhatsapp' class="btn btn-success">
                                <i class="demo-psi-whatsapp me-2"></i>Whatsapp
                            </button>
                        @endcan
                        @can('sale.change day session')
                            <button type="button" wire:click="openChangeSessionModal" class="btn btn-warning">
                                <i class="demo-psi-time-restore me-2"></i>Change Day Session
                            </button>
                        @endcan
                    @endif
                </div>

                @if ($sale['address'])
                    <div class="bg-light p-3 rounded mt-4">
                        <h6 class="fw-bold mb-2">
                            <i class="demo-psi-notepad me-2"></i>
                            Notes & Information
                        </h6>
                        <p class="mb-0">{{ $sale['address'] }}</p>
                    </div>
                @endif
            </div>

            <!-- Tabs Section -->
            <div class="tab-base">
                <ul class="nav nav-underline nav-component border-bottom" role="tablist">
                    @can('sale.view journal entries')
                        @if (count($sale->journals))
                            <li class="nav-item" role="presentation">
                                <button class="nav-link px-3 active" data-bs-toggle="tab" data-bs-target="#tab-journal-entries" type="button" role="tab">
                                    <i class="demo-psi-file-html me-2"></i>Journal Entries
                                </button>
                            </li>
                        @endif
                    @endcan
                    @if (count($sale_return_items))
                        <li class="nav-item" role="presentation">
                            <button class="nav-link px-3" data-bs-toggle="tab" data-bs-target="#tab-sale-return-items" type="button" role="tab">
                                <i class="demo-psi-back me-2"></i>Sale Return Items
                            </button>
                        </li>
                    @endif
                    @can('sale.audit view')
                        <li class="nav-item" role="presentation">
                            <button class="nav-link px-3" data-bs-toggle="tab" data-bs-target="#tab-audit-report" type="button" role="tab">
                                <i class="demo-psi-file-search me-2"></i>Audit Report
                            </button>
                        </li>
                    @endcan
                </ul>

                <div class="tab-content">
                    @can('sale.view journal entries')
                        @if (count($sale->journals))
                            <div id="tab-journal-entries" class="tab-pane fade active show" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-striped align-middle table-sm">
                                        <thead>
                                            <tr class="bg-primary text-white">
                                                <th class="text-white text-end">SL No</th>
                                                <th class="text-white">Date</th>
                                                <th class="text-white">Account Name</th>
                                                <th class="text-white">Description</th>
                                                <th class="text-white text-end">Debit</th>
                                                <th class="text-white text-end">Credit</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($sale->journals as $journal)
                                                @foreach ($journal->entries as $entry)
                                                    <tr>
                                                        <td class="text-end">{{ $entry->id }}</td>
                                                        <td>{{ systemDate($journal->date) }}</td>
                                                        <td>
                                                            <a href="{{ route('account::view', $entry->account_id) }}" class="text-primary">
                                                                {{ $entry->account?->name }}
                                                            </a>
                                                        </td>
                                                        <td>{{ $entry->remarks }}</td>
                                                        <td class="text-end">{{ currency($entry->debit) }}</td>
                                                        <td class="text-end">{{ currency($entry->credit) }}</td>
                                                    </tr>
                                                @endforeach
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    @endcan

                    <div id="tab-sale-return-items" class="tab-pane fade" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-striped align-middle table-sm">
                                <thead>
                                    <tr class="bg-primary text-white">
                                        <th class="text-white text-end">SL No</th>
                                        <th width="20%" class="text-white">Product/Service</th>
                                        <th class="text-white text-end">Unit Price</th>
                                        <th class="text-white text-end">Quantity</th>
                                        <th class="text-white text-end">Discount</th>
                                        <th class="text-white text-end">Tax %</th>
                                        <th class="text-white text-end">Total</th>
                                        <th class="text-white text-end">Effective Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($sale_return_items as $item)
                                        <tr>
                                            <td class="text-end">{{ $loop->iteration }}</td>
                                            <td class="text-white">
                                                <a href="{{ route('inventory::product::view', $item['product_id']) }}" class="text-primary">
                                                    {{ $item['name'] }}
                                                </a>
                                            </td>
                                            <td class="text-end">{{ currency($item['unit_price']) }}</td>
                                            <td class="text-end">{{ currency($item['quantity'], 3) }}</td>
                                            <td class="text-end">{{ currency($item['discount']) }}</td>
                                            <td class="text-end">{{ currency($item['tax_amount']) }} ({{ round($item['tax'], 2) }}%)</td>
                                            <td class="text-end">{{ currency($item['total']) }}</td>
                                            <td class="text-end">{{ currency($item['effective_total']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-light">
                                    <tr>
                                        <th colspan="3" class="text-end">Total</th>
                                        <th class="text-end">{{ currency($sale_return_items->sum('quantity')) }}</th>
                                        <th class="text-end">{{ currency($sale_return_items->sum('discount')) }}</th>
                                        <th class="text-end">{{ currency($sale_return_items->sum('tax_amount')) }}</th>
                                        <th class="text-end">{{ currency($sale_return_items->sum('total')) }}</th>
                                        <th class="text-end">{{ currency($sale_return_items->sum('effective_total')) }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <div id="tab-audit-report" class="tab-pane fade" role="tabpanel">
                        <ul class="nav nav-tabs mt-3" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#sale-audit" type="button">
                                    <i class="demo-psi-file me-2"></i>Sale
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#sale-items-audit" type="button">
                                    <i class="demo-psi-cart me-2"></i>Sale Items
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#sale-payments-audit" type="button">
                                    <i class="demo-psi-wallet me-2"></i>Sale Payments
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <div id="sale-audit" class="tab-pane fade show active" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-striped align-middle table-bordered table-sm">
                                        <thead>
                                            <tr class="bg-primary text-white">
                                                <th class="text-white text-nowrap">Date Time</th>
                                                <th class="text-white">User</th>
                                                <th class="text-white">Event</th>
                                                @php
                                                    $columns = $sale->audits->pluck('new_values')->filter()->map(fn($item) => array_keys($item))->flatten()->unique()->values()->all();
                                                @endphp
                                                @foreach ($columns as $key)
                                                    <th class="text-white text-end text-nowrap">{{ Str::title(str_replace('_', ' ', $key)) }}</th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($sale->audits as $audit)
                                                <tr>
                                                    <td class="text-nowrap">{{ $audit->created_at }}</td>
                                                    <td>{{ $audit->user?->name }}</td>
                                                    <td>{{ $audit->event }}</td>
                                                    @foreach ($columns as $key)
                                                        <td class="text-end text-nowrap">{{ $audit->new_values[$key] ?? '' }}</td>
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div id="sale-items-audit" class="tab-pane fade" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-striped align-middle table-sm">
                                        <thead>
                                            <tr class="bg-primary text-white">
                                                <th class="text-white">Date Time</th>
                                                <th class="text-white">User</th>
                                                <th class="text-white">Event</th>
                                                @php
                                                    $itemColumns = collect($sale->items)
                                                        ->flatMap->audits->pluck('new_values')
                                                        ->filter()
                                                        ->map(fn($item) => array_keys($item))
                                                        ->flatten()
                                                        ->unique()
                                                        ->values()
                                                        ->all();
                                                @endphp
                                                @foreach ($itemColumns as $key)
                                                    <th class="text-white text-end">{{ Str::title(str_replace('_', ' ', $key)) }}</th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($sale->items as $item)
                                                @foreach ($item->audits as $audit)
                                                    <tr>
                                                        <td>{{ $audit->created_at }}</td>
                                                        <td>{{ $audit->user?->name }}</td>
                                                        <td>{{ $audit->event }}</td>
                                                        @foreach ($itemColumns as $key)
                                                            <td class="text-end">{{ $audit->new_values[$key] ?? '' }}</td>
                                                        @endforeach
                                                    </tr>
                                                @endforeach
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div id="sale-payments-audit" class="tab-pane fade" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-striped align-middle table-sm">
                                        <thead>
                                            <tr class="bg-primary text-white">
                                                <th class="text-white">Date Time</th>
                                                <th class="text-white">User</th>
                                                <th class="text-white">Event</th>
                                                @php
                                                    $paymentColumns = collect($sale->payments)
                                                        ->flatMap->audits->pluck('new_values')
                                                        ->filter()
                                                        ->map(fn($item) => array_keys($item))
                                                        ->flatten()
                                                        ->unique()
                                                        ->values()
                                                        ->all();
                                                @endphp
                                                @foreach ($paymentColumns as $key)
                                                    <th class="text-white text-end">{{ Str::title(str_replace('_', ' ', $key)) }}</th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($sale->payments as $payment)
                                                @foreach ($payment->audits as $audit)
                                                    <tr>
                                                        <td>{{ $audit->created_at }}</td>
                                                        <td>{{ $audit->user?->name }}</td>
                                                        <td>{{ $audit->event }}</td>
                                                        @foreach ($paymentColumns as $key)
                                                            <td class="text-end">{{ $audit->new_values[$key] ?? '' }}</td>
                                                        @endforeach
                                                    </tr>
                                                @endforeach
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

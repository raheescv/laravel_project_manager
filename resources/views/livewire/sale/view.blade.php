<div>
    <div class="col-md-12 mb-2">
        <div class="card shadow-sm">
            <div class="card-body p-2">
                {{-- ── Header strip: invoice + customer compact ── --}}
                <div class="row g-2 mb-2">
                    <div class="col-lg-5">
                        <div class="glass-card h-100 border rounded">
                            <div class="card-body p-2">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="d-flex align-items-center">
                                        <div class="icon-box bg-primary bg-opacity-10 rounded-circle p-1 me-2">
                                            <i class="demo-psi-file text-primary-gradient fs-5"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 text-primary-gradient">Invoice</h6>
                                            <small class="text-muted">#{{ $sale->invoice_no }}</small>
                                        </div>
                                    </div>
                                    <span class="badge {{ $sale->status === 'completed' ? 'bg-success' : ($sale->status === 'cancelled' ? 'bg-danger' : 'bg-warning') }} rounded-pill">
                                        {{ ucfirst($sale->status) }}
                                    </span>
                                </div>
                                <div class="row g-1 small">
                                    <div class="col-6">
                                        <div class="info-item p-1 px-2 rounded bg-light bg-opacity-50">
                                            <small class="text-muted d-block"><i class="demo-psi-calendar-4 text-primary me-1"></i>Date</small>
                                            <span class="fw-medium">{{ systemDate($sale->date) }}</span>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="info-item p-1 px-2 rounded bg-light bg-opacity-50">
                                            <small class="text-muted d-block"><i class="demo-psi-calendar-4 text-danger me-1"></i>Due</small>
                                            <span class="fw-medium">{{ systemDate($sale->due_date) }}</span>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="info-item p-1 px-2 rounded bg-light bg-opacity-50">
                                            <small class="text-muted d-block"><i class="fa fa-file-text-o text-info me-1"></i>Reference</small>
                                            <span class="fw-medium">{{ $sale->reference_no ?: 'N/A' }}</span>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="info-item p-1 px-2 rounded bg-light bg-opacity-50">
                                            <small class="text-muted d-block"><i class="demo-psi-tag-2 text-success me-1"></i>Type</small>
                                            <span class="fw-medium">{{ ucfirst($sale->sale_type) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-7">
                        <div class="glass-card h-100 border rounded">
                            <div class="card-body p-2">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="d-flex align-items-center">
                                        <div class="icon-box bg-success bg-opacity-10 rounded-circle p-1 me-2">
                                            <i class="demo-psi-male text-success-gradient fs-5"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 text-success-gradient">Customer</h6>
                                            <small class="text-muted">#{{ $sale->account_id }}</small>
                                        </div>
                                    </div>
                                    <a href="{{ route('account::customer::view', $sale->account_id) }}" class="btn btn-sm btn-outline-success">
                                        <i class="fa fa-user me-1"></i>Profile
                                    </a>
                                </div>
                                <div class="row g-2 small">
                                    <div class="col-sm-6">
                                        <div class="p-2 bg-light bg-opacity-50 rounded h-100">
                                            <small class="text-muted d-block"><i class="demo-psi-id-card me-1"></i>Name</small>
                                            <a href="{{ route('account::customer::view', $sale->account_id) }}" class="fw-medium">{{ $sale->account?->name }}</a>
                                            @if ($sale->customer_name)
                                                <div class="mt-1"><small class="text-muted">Display:</small> <span class="fw-medium">{{ $sale->customer_name }}</span></div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="p-2 bg-light bg-opacity-50 rounded h-100">
                                            @if ($sale->customer_mobile || $sale->account?->mobile)
                                                <small class="text-muted d-block"><i class="fa fa-phone me-1"></i>Mobile</small>
                                                <span class="fw-medium">{{ $sale->customer_mobile ?: $sale->account?->mobile }}</span>
                                            @endif
                                            @if ($sale->account?->email)
                                                <div class="mt-1">
                                                    <small class="text-muted d-block"><i class="fa fa-envelope me-1"></i>Email</small>
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

                {{-- ── Customer Feedback (compact) ── --}}
                @if ($sale->rating || $sale->feedback)
                    <div class="card border bg-light mb-2">
                        <div class="card-body p-2">
                            <div class="d-flex align-items-center mb-2">
                                <div class="avatar bg-primary bg-opacity-10 rounded-circle me-2"><i class="demo-psi-like text-primary"></i></div>
                                <h6 class="mb-0">Customer Feedback</h6>
                            </div>
                            <div class="row g-2">
                                @if ($sale->rating)
                                    <div class="col-md-4">
                                        <div class="p-2 bg-white rounded shadow-sm h-100 d-flex align-items-center">
                                            <div class="h4 fw-bold text-primary mb-0 me-2">{{ $sale->rating }}/5</div>
                                            <div>
                                                @for ($i = 1; $i <= 5; $i++)
                                                    <i class="fa fa-star {{ $sale->rating >= $i ? 'text-warning' : 'text-muted' }}"></i>
                                                @endfor
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                @if ($sale->feedback)
                                    <div class="col-md-8">
                                        <div class="p-2 bg-white rounded shadow-sm h-100">
                                            <p class="mb-1 small">{{ $sale->feedback }}</p>
                                            @if ($sale->feedback_type)
                                                <span class="badge bg-info bg-opacity-10 text-info">{{ $sale->feedback_type }}</span>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                {{-- ── Combo Offer Items ── --}}
                @if (count($sale->comboOffers) > 0)
                    <div class="mb-2">
                        <h6 class="card-title mb-2"><i class="demo-psi-box-2 me-1"></i>Combo Offer Items</h6>
                        <div class="row g-2">
                            @foreach ($sale->comboOffers as $item)
                                <div class="col-md-6">
                                    <div class="card h-100 package-card border shadow-sm">
                                        <div class="card-header bg-primary bg-opacity-10 py-2 px-2">
                                            <h6 class="mb-0 small"><i class="demo-psi-box me-1"></i>{{ $item->comboOffer->name }}</h6>
                                        </div>
                                        <div class="card-body p-1">
                                            <div class="d-flex justify-content-between mb-1 p-1 bg-light rounded small">
                                                <div class="text-center">
                                                    <div class="fw-bold text-primary">{{ $item->items->count() }}</div>
                                                    <small class="text-muted">Services</small>
                                                </div>
                                                <div class="text-center">
                                                    <div class="fw-bold text-success">{{ currency($item->amount) }}</div>
                                                    <small class="text-muted">Combo Price</small>
                                                </div>
                                            </div>
                                            @foreach ($item->items as $subItem)
                                                <div class="d-flex justify-content-between align-items-center p-1 px-2 border-bottom small">
                                                    <div>
                                                        <div class="fw-semibold">{{ $subItem->product->name }}</div>
                                                        <small class="text-muted">{{ $subItem->employee->name }}</small>
                                                    </div>
                                                    <div class="text-end">
                                                        <div class="text-success">{{ currency($subItem->unit_price - $subItem->discount) }}</div>
                                                        @if ($subItem->discount > 0)
                                                            <small class="text-decoration-line-through text-muted">{{ currency($subItem->unit_price) }}</small>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- ── Items Table with inline returns + selection shortcut ── --}}
                @php
                    $returnsByItem = collect($sale_return_items)->groupBy('sale_item_id');
                    $totalReturnQty = collect($sale_return_items)->sum('quantity');
                    $totalReturnAmt = collect($sale_return_items)->sum('total');
                    $canReturn = auth()->user()?->can('sales return.create') && $sale->status !== 'cancelled';
                @endphp
                <div class="mb-2">
                    <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                        <h6 class="card-title d-flex align-items-center mb-0">
                            <i class="fa fa-shopping-cart me-1"></i> Items
                            @if (count($sale_return_items))
                                <span class="badge bg-warning text-dark ms-2" title="Items already returned">
                                    <i class="demo-psi-back me-1"></i>Returned: {{ currency($totalReturnQty, 3) }} qty / {{ currency($totalReturnAmt) }}
                                </span>
                            @endif
                        </h6>
                        @if ($canReturn)
                            <div class="d-flex align-items-center gap-2 d-print-none">
                                <div class="form-check form-check-sm m-0">
                                    <input class="form-check-input" type="checkbox" id="select-all-return-items"
                                        onclick="document.querySelectorAll('.sale-item-return-check:not(:disabled)').forEach(c => c.checked = this.checked); updateReturnSelectedCount();">
                                    <label class="form-check-label small" for="select-all-return-items">Select all</label>
                                </div>
                                <button type="button" class="btn btn-sm btn-warning" id="returnSelectedBtn"
                                    onclick="returnSelectedSaleItems({{ $sale->id }})" disabled>
                                    <i class="demo-psi-back me-1"></i>Return Selected (<span id="returnSelectedCount">0</span>)
                                </button>
                            </div>
                        @endif
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-sm align-middle mb-0">
                            <thead>
                                <tr class="bg-primary text-white">
                                    @if ($canReturn)
                                        <th class="text-white text-center" style="width:32px;"></th>
                                    @endif
                                    <th class="text-white">SL</th>
                                    <th width="20%" class="text-white">Product/Service</th>
                                    <th class="text-white text-end">Unit</th>
                                    <th class="text-white text-end">Unit Price</th>
                                    <th class="text-white text-end">Qty</th>
                                    <th class="text-white text-end">Discount</th>
                                    <th class="text-white text-end">Tax</th>
                                    <th class="text-white text-end">Total</th>
                                    @if ($sales['other_discount'] > 0)
                                        <th class="text-white text-end">Effective Total</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @php $data = collect($items)->groupBy('employee_id'); @endphp
                                @foreach ($data as $employee_id => $groupedItems)
                                    @php $first = $groupedItems->first(); @endphp
                                    <tr>
                                        <th colspan="{{ ($sales['other_discount'] > 0 ? 9 : 8) + ($canReturn ? 1 : 0) + 1 }}" class="bg-light py-1">
                                            <i class="demo-psi-user me-1"></i>{{ $first['employee_name'] }}
                                        </th>
                                    </tr>
                                    @foreach ($groupedItems as $item)
                                        @php
                                            $itemReturns = $returnsByItem->get($item['id'], collect());
                                            $returnedQty = $itemReturns->sum('quantity');
                                            $remaining = max(0, ($item['quantity'] ?? 0) - $returnedQty);
                                            $disabled = $remaining <= 0;
                                        @endphp
                                        <tr>
                                            @if ($canReturn)
                                                <td class="text-center">
                                                    <input type="checkbox" class="form-check-input sale-item-return-check"
                                                        value="{{ $item['id'] }}" {{ $disabled ? 'disabled' : '' }}
                                                        onchange="updateReturnSelectedCount()"
                                                        title="{{ $disabled ? 'Already fully returned' : 'Select to return' }}">
                                                </td>
                                            @endif
                                            <td>{{ $loop->iteration }}</td>
                                            <td>
                                                <div class="d-flex align-items-start">
                                                    <i class="fa fa-cube text-primary me-1"></i>
                                                    <div>
                                                        <a href="{{ route('inventory::product::view', $item['product_id']) }}" class="text-primary fw-semibold">{{ $item['name'] }}</a>
                                                        <div>
                                                            @if (!empty($item['sale_combo_offer_id']))
                                                                <span class="badge bg-info text-white">Combo</span>
                                                            @endif
                                                            @if (!empty($item['assistant_name']))
                                                                <small class="text-muted"><i class="fa fa-user-plus me-1"></i>{{ $item['assistant_name'] }}</small>
                                                            @endif
                                                            @if ($returnedQty > 0)
                                                                <span class="badge bg-warning text-dark" title="Returned quantity">
                                                                    <i class="demo-psi-back me-1"></i>Returned {{ currency($returnedQty, 3) }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-end">{{ $item['unit'] }}</td>
                                            <td class="text-end">{{ currency($item['unit_price']) }}</td>
                                            <td class="text-end">{{ currency($item['quantity'], 3) }}</td>
                                            <td class="text-end">{{ $item['discount'] != 0 ? currency($item['discount']) : '-' }}</td>
                                            <td class="text-end">
                                                @if ($item['tax_amount'] != 0)
                                                    {{ currency($item['tax_amount']) }} <small class="text-muted">({{ round($item['tax'], 2) }}%)</small>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="text-end fw-bold">{{ currency($item['total']) }}</td>
                                            @if ($sales['other_discount'] > 0)
                                                <td class="text-end fw-bold">{{ currency($item['effective_total']) }}</td>
                                            @endif
                                        </tr>
                                        @foreach ($itemReturns as $ri)
                                            <tr class="bg-warning bg-opacity-10 small">
                                                @if ($canReturn)
                                                    <td></td>
                                                @endif
                                                <td class="text-end text-muted"><i class="demo-psi-back"></i></td>
                                                <td colspan="2" class="text-muted">
                                                    <a href="{{ route('sale_return::view', $ri->sale_return_id) }}" class="text-warning">
                                                        Return #{{ $ri->sale_return_id }}
                                                    </a>
                                                    <small class="text-muted ms-1">— {{ $ri->product?->name }}</small>
                                                </td>
                                                <td class="text-end text-muted">{{ currency($ri->unit_price) }}</td>
                                                <td class="text-end text-muted">{{ currency($ri->quantity, 3) }}</td>
                                                <td class="text-end text-muted">{{ $ri->discount != 0 ? currency($ri->discount) : '-' }}</td>
                                                <td class="text-end text-muted">
                                                    @if ($ri->tax_amount != 0)
                                                        {{ currency($ri->tax_amount) }}
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td class="text-end text-muted fw-semibold">-{{ currency($ri->total) }}</td>
                                                @if ($sales['other_discount'] > 0)
                                                    <td class="text-end text-muted fw-semibold">-{{ currency($ri->effective_total) }}</td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    @endforeach
                                @endforeach
                            </tbody>
                            <tfoot class="bg-light fw-bold">
                                @php $items = collect($items); @endphp
                                <tr>
                                    @if ($canReturn) <th></th> @endif
                                    <th colspan="4" class="text-end">Total</th>
                                    <th class="text-end">{{ currency($items->sum('quantity'), 3) }}</th>
                                    <th class="text-end">{{ currency($items->sum('discount')) }}</th>
                                    <th class="text-end">{{ currency($items->sum('tax_amount')) }}</th>
                                    <th class="text-end">{{ currency($items->sum('total')) }}</th>
                                    @if ($sales['other_discount'] > 0)
                                        <th class="text-end">{{ currency($items->sum('effective_total')) }}</th>
                                    @endif
                                </tr>
                                @if (count($sale_return_items))
                                    <tr class="bg-warning bg-opacity-10">
                                        @if ($canReturn) <th></th> @endif
                                        <th colspan="4" class="text-end text-warning"><i class="demo-psi-back me-1"></i>Returned</th>
                                        <th class="text-end text-warning">-{{ currency($sale_return_items->sum('quantity'), 3) }}</th>
                                        <th class="text-end text-warning">-{{ currency($sale_return_items->sum('discount')) }}</th>
                                        <th class="text-end text-warning">-{{ currency($sale_return_items->sum('tax_amount')) }}</th>
                                        <th class="text-end text-warning">-{{ currency($sale_return_items->sum('total')) }}</th>
                                        @if ($sales['other_discount'] > 0)
                                            <th class="text-end text-warning">-{{ currency($sale_return_items->sum('effective_total')) }}</th>
                                        @endif
                                    </tr>
                                @endif
                            </tfoot>
                        </table>
                    </div>
                </div>

                {{-- ── Payments + Totals ── --}}
                <div class="row g-2">
                    <div class="col-12 col-md-5">
                        <div class="card h-100 shadow-sm">
                            <div class="card-header bg-light d-flex align-items-center justify-content-between py-2 px-2">
                                <h6 class="card-title mb-0 d-flex align-items-center">
                                    <i class="demo-psi-wallet me-1 text-primary"></i>Payment Details
                                </h6>
                                <span class="badge bg-primary rounded-pill">{{ count($payments) }} Payments</span>
                            </div>
                            <div class="card-body p-0">
                                <div class="payment-list">
                                    @foreach ($payments as $key => $item)
                                        <div class="payment-item p-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="d-flex align-items-center">
                                                    <div class="payment-icon me-2">
                                                        @switch(strtolower($item['name']))
                                                            @case('cash')
                                                                <div class="avatar bg-success bg-opacity-10"><i class="fa fa-money text-success"></i></div>
                                                            @break
                                                            @case('card')
                                                                <div class="avatar bg-info bg-opacity-10"><i class="fa fa-credit-card text-info"></i></div>
                                                            @break
                                                            @default
                                                                <div class="avatar bg-warning bg-opacity-10"><i class="fa fa-university text-warning"></i></div>
                                                        @endswitch
                                                    </div>
                                                    <div>
                                                        <div class="fw-semibold small">{{ $item['name'] }}</div>
                                                        <small class="text-muted">Txn #{{ str_pad($key + 1, 3, '0', STR_PAD_LEFT) }}</small>
                                                    </div>
                                                </div>
                                                <div class="text-end">
                                                    <div class="text-success fw-semibold">{{ currency($item['amount']) }}</div>
                                                    <small class="text-muted">{{ systemDate($item['date']) }}</small>
                                                </div>
                                            </div>
                                            @if (isset($item['reference']))
                                                <div class="bg-light bg-opacity-50 rounded p-1 mt-1">
                                                    <small class="text-muted"><i class="demo-psi-file-text me-1"></i>Ref: {{ $item['reference'] }}</small>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                                <div class="p-2 bg-light border-top d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-semibold small">Total Paid</div>
                                        <small class="text-muted">All payments combined</small>
                                    </div>
                                    <h6 class="mb-0 text-success">{{ currency(collect($payments)->sum('amount')) }}</h6>
                                </div>
                            </div>
                        </div>

                        <style>
                            .payment-list { max-height: 320px; overflow-y: auto; }
                            .payment-item { transition: all .2s ease; }
                            .payment-item:hover { background-color: var(--bs-light); }
                            .avatar { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 8px; }
                        </style>
                    </div>

                    <div class="col-12 col-md-7">
                        <div class="card h-100 shadow-sm">
                            <div class="card-header bg-light py-2 px-2">
                                <h6 class="card-title mb-0"><i class="demo-psi-calculator me-1"></i>Financial Summary</h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="row g-0">
                                    <div class="col-md-6 border-end">
                                        <div class="p-2">
                                            <div class="d-flex align-items-center mb-1">
                                                <i class="fa fa-money text-primary me-1"></i><span class="fw-semibold small">Base Amounts</span>
                                            </div>
                                            <div class="list-group list-group-flush">
                                                <div class="list-group-item d-flex justify-content-between px-0 py-1 border-0 small">
                                                    <span class="text-muted">Gross Total</span>
                                                    <span class="fw-medium">{{ currency($sale->gross_amount) }}</span>
                                                </div>
                                                <div class="list-group-item d-flex justify-content-between px-0 py-1 border-0 small">
                                                    <span class="text-muted">Sale Total</span>
                                                    <span class="fw-medium">{{ currency($sale->total) }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="p-2">
                                            <div class="d-flex align-items-center mb-1">
                                                <i class="demo-psi-receipt-4 text-warning me-1"></i><span class="fw-semibold small">Adjustments</span>
                                            </div>
                                            <div class="list-group list-group-flush">
                                                <div class="list-group-item d-flex justify-content-between px-0 py-1 border-0 small">
                                                    <span class="text-muted">Other Discount</span>
                                                    <span class="fw-medium text-danger">-{{ currency($sale->other_discount) }}</span>
                                                </div>
                                                <div class="list-group-item d-flex justify-content-between px-0 py-1 border-0 small">
                                                    <span class="text-muted">Freight</span>
                                                    <span class="fw-medium">{{ currency($sale->freight) }}</span>
                                                </div>
                                                <div class="list-group-item d-flex justify-content-between px-0 py-1 border-0 small">
                                                    <span class="text-muted">Round Off</span>
                                                    <span class="fw-medium">{{ currency($sale->round_off) }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="border-top p-2">
                                    <div class="list-group list-group-flush">
                                        <div class="list-group-item d-flex justify-content-between px-0 py-1 border-0">
                                            <span class="fw-semibold">Total Payable</span>
                                            <span class="h6 mb-0 text-success">{{ currency($sale->grand_total) }}</span>
                                        </div>
                                        @if ($sale->balance != 0)
                                            <div class="list-group-item d-flex justify-content-between px-0 py-1 border-0 small">
                                                <span class="text-muted">Amount Paid</span>
                                                <span class="fw-medium text-success">{{ currency($sale->paid) }}</span>
                                            </div>
                                            <div class="list-group-item d-flex justify-content-between px-0 py-1 border-0 small">
                                                <span class="text-muted">Balance Due</span>
                                                <span class="fw-medium text-danger">{{ currency($sale->balance) }}</span>
                                            </div>
                                        @endif
                                        @if (count($sale_return_items))
                                            <div class="list-group-item d-flex justify-content-between px-0 py-1 border-0 small">
                                                <span class="text-warning"><i class="demo-psi-back me-1"></i>Returned Total</span>
                                                <span class="fw-medium text-warning">-{{ currency($sale_return_items->sum('total')) }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── Action Buttons ── --}}
                <div class="d-flex justify-content-end gap-2 my-2 d-print-none flex-wrap">
                    @if ($sales['status'] != 'cancelled')
                        <a target="_blank" href="{{ route('print::sale::invoice', $sales['id']) }}" class="btn btn-sm btn-light btn-icon" title="Print Invoice">
                            <i class="demo-pli-printer fs-5"></i>
                        </a>
                        @can('sales return.create')
                            <a href="{{ route('sale_return::create', ['sale_id' => $sale->id]) }}" class="btn btn-sm btn-warning" title="Create a sale return for this invoice">
                                <i class="demo-psi-back me-1"></i>Return All
                            </a>
                        @endcan
                        @can('sale.cancel')
                            <button type="button" wire:click='save("cancelled")' wire:confirm="Are you sure to cancel this?" class="btn btn-sm btn-danger">
                                <i class="demo-psi-cross me-1"></i>Cancel
                            </button>
                        @endcan
                        @can('sale.edit completed')
                            <a href="{{ route('sale::edit', $sales['id']) }}" type="button" class="btn btn-sm btn-primary">
                                <i class="demo-psi-pen-5 me-1"></i>Edit
                            </a>
                        @endcan
                        @can('sale.cancel')
                            <button type="button" wire:click='sendToWhatsapp' class="btn btn-sm btn-success">
                                <i class="demo-psi-whatsapp me-1"></i>Whatsapp
                            </button>
                        @endcan
                        @can('sale.change day session')
                            <button type="button" wire:click="openChangeSessionModal" class="btn btn-sm btn-warning">
                                <i class="demo-psi-time-restore me-1"></i>Change Day Session
                            </button>
                        @endcan
                    @endif
                </div>

                @if ($sale['address'])
                    <div class="bg-light p-2 rounded mb-2">
                        <h6 class="fw-bold mb-1 small"><i class="demo-psi-notepad me-1"></i>Notes & Information</h6>
                        <p class="mb-0 small">{{ $sale['address'] }}</p>
                    </div>
                @endif
            </div>

            {{-- ── Tabs (journal & audit only — return items are inline above) ── --}}
            <div class="tab-base">
                <ul class="nav nav-underline nav-component border-bottom" role="tablist">
                    @can('sale.view journal entries')
                        @if (count($sale->journals))
                            <li class="nav-item" role="presentation">
                                <button class="nav-link px-3 active" data-bs-toggle="tab" data-bs-target="#tab-journal-entries" type="button" role="tab">
                                    <i class="demo-psi-file-html me-1"></i>Journal Entries
                                </button>
                            </li>
                        @endif
                    @endcan
                    @can('sale.audit view')
                        <li class="nav-item" role="presentation">
                            <button class="nav-link px-3" data-bs-toggle="tab" data-bs-target="#tab-audit-report" type="button" role="tab">
                                <i class="demo-psi-file-search me-1"></i>Audit Report
                            </button>
                        </li>
                    @endcan
                </ul>

                <div class="tab-content">
                    @can('sale.view journal entries')
                        @if (count($sale->journals))
                            <div id="tab-journal-entries" class="tab-pane fade active show" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-striped align-middle table-sm mb-0">
                                        <thead>
                                            <tr class="bg-primary text-white">
                                                <th class="text-white text-end">SL</th>
                                                <th class="text-white">Date</th>
                                                <th class="text-white">Account</th>
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
                                                        <td>{{ systemDate($entry->date) }}</td>
                                                        <td><a href="{{ route('account::view', $entry->account_id) }}" class="text-primary">{{ $entry->account?->name }}</a></td>
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

                    <div id="tab-audit-report" class="tab-pane fade" role="tabpanel">
                        <ul class="nav nav-tabs mt-2" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#sale-audit" type="button"><i class="demo-psi-file me-1"></i>Sale</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#sale-items-audit" type="button"><i class="demo-psi-cart me-1"></i>Sale Items</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#sale-payments-audit" type="button"><i class="demo-psi-wallet me-1"></i>Sale Payments</button>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <div id="sale-audit" class="tab-pane fade show active" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-striped align-middle table-bordered table-sm mb-0">
                                        <thead>
                                            <tr class="bg-primary text-white">
                                                <th class="text-white text-nowrap">Date Time</th>
                                                <th class="text-white">User</th>
                                                <th class="text-white">Event</th>
                                                @php $columns = $sale->audits->pluck('new_values')->filter()->map(fn($item) => array_keys($item))->flatten()->unique()->values()->all(); @endphp
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
                                    <table class="table table-striped align-middle table-sm mb-0">
                                        <thead>
                                            <tr class="bg-primary text-white">
                                                <th class="text-white">Date Time</th>
                                                <th class="text-white">User</th>
                                                <th class="text-white">Event</th>
                                                @php $itemColumns = collect($sale->items)->flatMap->audits->pluck('new_values')->filter()->map(fn($item) => array_keys($item))->flatten()->unique()->values()->all(); @endphp
                                                @foreach ($itemColumns as $key)
                                                    <th class="text-white text-end">{{ Str::title(str_replace('_', ' ', $key)) }}</th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($sale->items as $item)
                                                @foreach ($item->audits as $audit)
                                                    <tr>
                                                        <td class="text-nowrap">{{ $audit->created_at }}</td>
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
                                    <table class="table table-striped align-middle table-sm mb-0">
                                        <thead>
                                            <tr class="bg-primary text-white">
                                                <th class="text-white">Date Time</th>
                                                <th class="text-white">User</th>
                                                <th class="text-white">Event</th>
                                                @php $paymentColumns = collect($sale->payments)->flatMap->audits->pluck('new_values')->filter()->map(fn($item) => array_keys($item))->flatten()->unique()->values()->all(); @endphp
                                                @foreach ($paymentColumns as $key)
                                                    <th class="text-white text-end">{{ Str::title(str_replace('_', ' ', $key)) }}</th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($sale->payments as $payment)
                                                @foreach ($payment->audits as $audit)
                                                    <tr>
                                                        <td class="text-nowrap">{{ $audit->created_at }}</td>
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

    @push('scripts')
        <script>
            function updateReturnSelectedCount() {
                const checks = document.querySelectorAll('.sale-item-return-check:checked');
                const count = checks.length;
                const countEl = document.getElementById('returnSelectedCount');
                const btn = document.getElementById('returnSelectedBtn');
                if (countEl) countEl.textContent = count;
                if (btn) btn.disabled = count === 0;
            }

            function returnSelectedSaleItems(saleId) {
                const ids = [...document.querySelectorAll('.sale-item-return-check:checked')].map(c => c.value);
                if (!ids.length) return;
                const url = "{{ route('sale_return::create') }}" +
                    "?sale_id=" + encodeURIComponent(saleId) +
                    "&sale_item_ids=" + encodeURIComponent(ids.join(','));
                window.location.href = url;
            }
        </script>
    @endpush
</div>

<div>
    @php
        $statusClass = $purchase->status === 'completed' ? 'bg-success' : ($purchase->status === 'cancelled' ? 'bg-danger' : 'bg-warning');
        $purchaseAuditColumns = $purchase->audits->pluck('new_values')->filter()->map(fn ($item) => array_keys($item))->flatten()->unique()->values()->all();
        $itemAuditColumns = $purchase->items->flatMap->audits->pluck('new_values')->filter()->map(fn ($item) => array_keys($item))->flatten()->unique()->values()->all();
        $paymentAuditColumns = $purchase->payments->flatMap->audits->pluck('new_values')->filter()->map(fn ($item) => array_keys($item))->flatten()->unique()->values()->all();
        $productCostAudits = $purchase->items
            ->pluck('product')
            ->filter()
            ->unique('id')
            ->flatMap(fn ($product) => $product->audits
                ->filter(fn ($audit) => array_key_exists('cost', $audit->old_values ?? []) || array_key_exists('cost', $audit->new_values ?? []))
                ->map(fn ($audit) => ['product' => $product, 'audit' => $audit]))
            ->sortByDesc(fn ($row) => $row['audit']->created_at)
            ->values();
    @endphp

    <div class="col-md-12 mb-2">
        <div class="card shadow-sm">
            <div class="card-body p-2">
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
                                            <h6 class="mb-0 text-primary-gradient">Purchase</h6>
                                            <small class="text-muted">#{{ $purchase->invoice_no }}</small>
                                        </div>
                                    </div>
                                    <span class="badge {{ $statusClass }} rounded-pill">{{ ucfirst($purchase->status) }}</span>
                                </div>
                                <div class="row g-1 small">
                                    <div class="col-6">
                                        <div class="info-item p-1 px-2 rounded bg-light bg-opacity-50">
                                            <small class="text-muted d-block"><i class="demo-psi-calendar-4 text-primary me-1"></i>Date</small>
                                            <span class="fw-medium">{{ systemDate($purchase->date) }}</span>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="info-item p-1 px-2 rounded bg-light bg-opacity-50">
                                            <small class="text-muted d-block"><i class="demo-psi-calendar-4 text-danger me-1"></i>Delivery</small>
                                            <span class="fw-medium">{{ $purchase->delivery_date ? systemDate($purchase->delivery_date) : 'N/A' }}</span>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="info-item p-1 px-2 rounded bg-light bg-opacity-50">
                                            <small class="text-muted d-block"><i class="demo-psi-home text-info me-1"></i>Branch</small>
                                            <span class="fw-medium">{{ $purchase->branch?->name ?? 'N/A' }}</span>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="info-item p-1 px-2 rounded bg-light bg-opacity-50">
                                            <small class="text-muted d-block"><i class="demo-psi-basket-coins text-success me-1"></i>Items</small>
                                            <span class="fw-medium">{{ $purchase->items->count() }}</span>
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
                                            <i class="fa fa-building text-success-gradient fs-5"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 text-success-gradient">Vendor</h6>
                                            <small class="text-muted">#{{ $purchase->account_id }}</small>
                                        </div>
                                    </div>
                                    <a href="{{ route('purchase-vendor::view', $purchase->account_id) }}" class="btn btn-sm btn-outline-success">
                                        <i class="fa fa-user me-1"></i>Profile
                                    </a>
                                </div>
                                <div class="row g-2 small">
                                    <div class="col-sm-6">
                                        <div class="p-2 bg-light bg-opacity-50 rounded h-100">
                                            <small class="text-muted d-block"><i class="demo-psi-id-card me-1"></i>Name</small>
                                            <a href="{{ route('purchase-vendor::view', $purchase->account_id) }}" class="fw-medium">{{ $purchase->account?->name }}</a>
                                            @if ($purchase->localPurchaseOrder)
                                                <div class="mt-1">
                                                    <small class="text-muted">LPO:</small>
                                                    <a href="{{ route('lpo::view', $purchase->localPurchaseOrder->id) }}" class="fw-medium">#{{ $purchase->localPurchaseOrder->id }}</a>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="p-2 bg-light bg-opacity-50 rounded h-100">
                                            @if ($purchase->account?->mobile)
                                                <small class="text-muted d-block"><i class="fa fa-phone me-1"></i>Mobile</small>
                                                <span class="fw-medium">{{ $purchase->account?->mobile }}</span>
                                            @endif
                                            @if ($purchase->account?->email)
                                                <div class="mt-1">
                                                    <small class="text-muted d-block"><i class="fa fa-envelope me-1"></i>Email</small>
                                                    <span class="fw-medium">{{ $purchase->account?->email }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-2">
                    <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                        <h6 class="card-title d-flex align-items-center mb-0">
                            <i class="fa fa-shopping-cart me-1"></i> Purchase Items
                        </h6>
                        <span class="badge bg-primary rounded-pill">{{ currency($purchase->items->sum('quantity'), 3) }} Qty</span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-sm align-middle mb-0">
                            <thead>
                                <tr class="bg-primary text-white">
                                    <th class="text-white">SL</th>
                                    <th width="24%" class="text-white">Product</th>
                                    <th class="text-white">Account</th>
                                    <th class="text-white text-end">Unit</th>
                                    <th class="text-white text-end">Unit Cost</th>
                                    <th class="text-white text-end">Qty</th>
                                    <th class="text-white text-end">Discount</th>
                                    <th class="text-white text-end">Tax</th>
                                    <th class="text-white text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($purchase->items as $item)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="d-flex align-items-start">
                                                <i class="fa fa-cube text-primary me-1"></i>
                                                <div>
                                                    <a href="{{ route('inventory::product::view', $item->product_id) }}" class="text-primary fw-semibold">{{ $item->product?->name }}</a>
                                                    @if ($item->product?->barcode)
                                                        <div><small class="text-muted">{{ $item->product->barcode }}</small></div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $item->account?->name ?? '-' }}</td>
                                        <td class="text-end">{{ $item->unit?->name ?? '-' }}</td>
                                        <td class="text-end">{{ currency($item->unit_price) }}</td>
                                        <td class="text-end">{{ currency($item->quantity, 3) }}</td>
                                        <td class="text-end">{{ $item->discount != 0 ? currency($item->discount) : '-' }}</td>
                                        <td class="text-end">
                                            @if ($item->tax_amount != 0)
                                                {{ currency($item->tax_amount) }} <small class="text-muted">({{ round($item->tax, 2) }}%)</small>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="text-end fw-bold">{{ currency($item->total) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-light fw-bold">
                                <tr>
                                    <th colspan="5" class="text-end">Total</th>
                                    <th class="text-end">{{ currency($purchase->items->sum('quantity'), 3) }}</th>
                                    <th class="text-end">{{ currency($purchase->items->sum('discount')) }}</th>
                                    <th class="text-end">{{ currency($purchase->items->sum('tax_amount')) }}</th>
                                    <th class="text-end">{{ currency($purchase->items->sum('total')) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="row g-2">
                    <div class="col-12 col-md-5">
                        <div class="card h-100 shadow-sm">
                            <div class="card-header bg-light d-flex align-items-center justify-content-between py-2 px-2">
                                <h6 class="card-title mb-0 d-flex align-items-center">
                                    <i class="demo-psi-wallet me-1 text-primary"></i>Payment Details
                                </h6>
                                <span class="badge bg-primary rounded-pill">{{ $purchase->payments->count() }} Payments</span>
                            </div>
                            <div class="card-body p-0">
                                @forelse ($purchase->payments as $key => $payment)
                                    <div class="p-2 {{ ! $loop->last ? 'border-bottom' : '' }}">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center">
                                                <div class="avatar bg-success bg-opacity-10 me-2"><i class="fa fa-money text-success"></i></div>
                                                <div>
                                                    <div class="fw-semibold small">{{ $payment->paymentMethod?->name }}</div>
                                                    <small class="text-muted">Txn #{{ str_pad((string) ($key + 1), 3, '0', STR_PAD_LEFT) }}</small>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <div class="text-success fw-semibold">{{ currency($payment->amount) }}</div>
                                                <small class="text-muted">{{ systemDate($payment->date) }}</small>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="p-3 text-muted small">No payments recorded.</div>
                                @endforelse
                                <div class="p-2 bg-light border-top d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-semibold small">Total Paid</div>
                                        <small class="text-muted">All payments combined</small>
                                    </div>
                                    <h6 class="mb-0 text-success">{{ currency($purchase->paid) }}</h6>
                                </div>
                            </div>
                        </div>
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
                                                    <span class="fw-medium">{{ currency($purchase->gross_amount) }}</span>
                                                </div>
                                                <div class="list-group-item d-flex justify-content-between px-0 py-1 border-0 small">
                                                    <span class="text-muted">Purchase Total</span>
                                                    <span class="fw-medium">{{ currency($purchase->total) }}</span>
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
                                                    <span class="fw-medium text-danger">-{{ currency($purchase->other_discount) }}</span>
                                                </div>
                                                <div class="list-group-item d-flex justify-content-between px-0 py-1 border-0 small">
                                                    <span class="text-muted">Freight</span>
                                                    <span class="fw-medium">{{ currency($purchase->freight) }}</span>
                                                </div>
                                                <div class="list-group-item d-flex justify-content-between px-0 py-1 border-0 small">
                                                    <span class="text-muted">Item Discount</span>
                                                    <span class="fw-medium text-danger">-{{ currency($purchase->item_discount) }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="border-top p-2">
                                    <div class="list-group list-group-flush">
                                        <div class="list-group-item d-flex justify-content-between px-0 py-1 border-0">
                                            <span class="fw-semibold">Total Payable</span>
                                            <span class="h6 mb-0 text-success">{{ currency($purchase->grand_total) }}</span>
                                        </div>
                                        <div class="list-group-item d-flex justify-content-between px-0 py-1 border-0 small">
                                            <span class="text-muted">Amount Paid</span>
                                            <span class="fw-medium text-success">{{ currency($purchase->paid) }}</span>
                                        </div>
                                        <div class="list-group-item d-flex justify-content-between px-0 py-1 border-0 small">
                                            <span class="text-muted">Balance Due</span>
                                            <span class="fw-medium {{ $purchase->balance > 0 ? 'text-danger' : 'text-success' }}">{{ currency($purchase->balance) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 my-2 d-print-none flex-wrap">
                    @if ($purchase->status !== 'cancelled')
                        <a target="_blank" href="{{ route('purchase::print', $purchase->id) }}" class="btn btn-sm btn-light btn-icon" title="Print Purchase Note">
                            <i class="demo-pli-printer fs-5"></i>
                        </a>
                        @can('purchase.barcode print')
                            <a target="_blank" href="{{ route('purchase::barcode-print', $purchase->id) }}" class="btn btn-sm btn-light" title="Print Barcode">
                                <i class="fa fa-barcode me-1"></i>Barcode
                            </a>
                        @endcan
                        @can('purchase.cancel')
                            <button type="button" wire:click='save("cancelled")' wire:confirm="Are you sure to cancel this?" class="btn btn-sm btn-danger">
                                <i class="demo-psi-cross me-1"></i>Cancel
                            </button>
                        @endcan
                        @can('purchase.edit completed')
                            <a href="{{ route('purchase::edit', $purchase->id) }}" type="button" class="btn btn-sm btn-primary">
                                <i class="demo-psi-pen-5 me-1"></i>Edit
                            </a>
                        @endcan
                    @endif
                </div>

                @if ($purchase->address)
                    <div class="bg-light p-2 rounded mb-2">
                        <h6 class="fw-bold mb-1 small"><i class="demo-psi-notepad me-1"></i>Notes & Information</h6>
                        <p class="mb-0 small">{{ $purchase->address }}</p>
                    </div>
                @endif
            </div>

            <div class="tab-base">
                <ul class="nav nav-underline nav-component border-bottom" role="tablist">
                    @if ($purchase->journals->count())
                        <li class="nav-item" role="presentation">
                            <button class="nav-link px-3 active" data-bs-toggle="tab" data-bs-target="#tab-journal-entries" type="button" role="tab">
                                <i class="demo-psi-file-html me-1"></i>Journal Entries
                            </button>
                        </li>
                    @endif
                    <li class="nav-item" role="presentation">
                        <button class="nav-link px-3 {{ $purchase->journals->isEmpty() ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-audit-report" type="button" role="tab">
                            <i class="demo-psi-file-search me-1"></i>Audit Report
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    @if ($purchase->journals->count())
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
                                        @foreach ($purchase->journals as $journal)
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

                    <div id="tab-audit-report" class="tab-pane fade {{ $purchase->journals->isEmpty() ? 'active show' : '' }}" role="tabpanel">
                        <ul class="nav nav-tabs mt-2" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#purchase-audit" type="button"><i class="demo-psi-file me-1"></i>Purchase</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#purchase-items-audit" type="button"><i class="demo-psi-cart me-1"></i>Purchase Items</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#purchase-payments-audit" type="button"><i class="demo-psi-wallet me-1"></i>Purchase Payments</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#product-cost-audit" type="button"><i class="fa fa-cube me-1"></i>Product Cost Audit</button>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <div id="purchase-audit" class="tab-pane fade show active" role="tabpanel">
                                <x-purchase.audit-table :audits="$purchase->audits" :columns="$purchaseAuditColumns" />
                            </div>

                            <div id="purchase-items-audit" class="tab-pane fade" role="tabpanel">
                                <x-purchase.audit-table :audits="$purchase->items->flatMap->audits" :columns="$itemAuditColumns" />
                            </div>

                            <div id="purchase-payments-audit" class="tab-pane fade" role="tabpanel">
                                <x-purchase.audit-table :audits="$purchase->payments->flatMap->audits" :columns="$paymentAuditColumns" />
                            </div>

                            <div id="product-cost-audit" class="tab-pane fade" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-striped align-middle table-sm mb-0">
                                        <thead>
                                            <tr class="bg-primary text-white">
                                                <th class="text-white">Date Time</th>
                                                <th class="text-white">Product</th>
                                                <th class="text-white">User</th>
                                                <th class="text-white">Event</th>
                                                <th class="text-white text-end">Old Cost</th>
                                                <th class="text-white text-end">New Cost</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($productCostAudits as $row)
                                                <tr>
                                                    <td class="text-nowrap">{{ $row['audit']->created_at }}</td>
                                                    <td>
                                                        <a href="{{ route('inventory::product::view', $row['product']->id) }}" class="text-primary fw-semibold">{{ $row['product']->name }}</a>
                                                    </td>
                                                    <td>{{ $row['audit']->user?->name ?? '-' }}</td>
                                                    <td>{{ $row['audit']->event }}</td>
                                                    <td class="text-end text-danger">{{ array_key_exists('cost', $row['audit']->old_values ?? []) ? currency($row['audit']->old_values['cost']) : '-' }}</td>
                                                    <td class="text-end text-success">{{ array_key_exists('cost', $row['audit']->new_values ?? []) ? currency($row['audit']->new_values['cost']) : '-' }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="text-center text-muted py-3">No product cost changes found for these purchase items.</td>
                                                </tr>
                                            @endforelse
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

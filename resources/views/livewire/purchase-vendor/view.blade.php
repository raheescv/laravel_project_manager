<div class="vendor-view-container">
    {{-- Vendor Info Hero --}}
    <div class="card border-0 shadow-lg overflow-hidden mb-3">
        <div class="position-absolute top-0 start-0 w-100 h-100 opacity-25">
            <div class="bg-gradient-primary h-100 w-100"></div>
        </div>
        <div class="card-body position-relative p-3">
            <div class="row align-items-center g-2">
                <div class="col-12 col-lg-5">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle shadow bg-primary bg-gradient text-white"
                                style="width: 60px; height: 60px; font-size: 1.5rem;">
                                <i class="fa fa-building"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h2 class="mb-1 text-dark fw-bold" style="font-size: 1.5rem;">
                                {{ $vendor['name'] ?? 'Vendor' }}
                            </h2>
                            <div class="d-flex flex-wrap gap-1 align-items-center">
                                <span class="badge bg-light text-dark px-2 py-1 rounded-pill shadow-sm" style="font-size: 0.7rem;">
                                    <i class="fa fa-id-card me-1 text-primary"></i> ID: #{{ $vendor['id'] ?? '' }}
                                </span>
                                @if ($vendor['mobile'] ?? null)
                                    <span class="badge bg-light text-dark px-2 py-1 rounded-pill shadow-sm" style="font-size: 0.7rem;">
                                        <i class="fa fa-phone me-1 text-success"></i> {{ $vendor['mobile'] }}
                                    </span>
                                @endif
                                @if ($vendor['email'] ?? null)
                                    <span class="badge bg-light text-dark px-2 py-1 rounded-pill shadow-sm" style="font-size: 0.7rem;">
                                        <i class="fa fa-envelope me-1 text-info"></i> {{ $vendor['email'] }}
                                    </span>
                                @endif
                                @if ($vendor['place'] ?? null)
                                    <span class="badge bg-light text-dark px-2 py-1 rounded-pill shadow-sm" style="font-size: 0.7rem;">
                                        <i class="fa fa-map-marker me-1 text-danger"></i> {{ $vendor['place'] }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-7">
                    <div class="row g-2">
                        <div class="col-4">
                            <div class="text-center p-2 rounded-3 shadow-sm bg-white">
                                <h6 class="text-muted mb-0 fw-semibold" style="font-size: 0.7rem;">Total Purchase</h6>
                                <div class="fw-bold text-primary" style="font-size: 1rem;">{{ currency($total_purchases?->grand_total ?? 0) }}</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="text-center p-2 rounded-3 shadow-sm bg-white">
                                <h6 class="text-muted mb-0 fw-semibold" style="font-size: 0.7rem;">Total Paid</h6>
                                <div class="fw-bold text-success" style="font-size: 1rem;">{{ currency($total_purchases?->paid ?? 0) }}</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="text-center p-2 rounded-3 shadow-sm bg-white">
                                <h6 class="text-muted mb-0 fw-semibold" style="font-size: 0.7rem;">Balance</h6>
                                <div class="fw-bold text-danger" style="font-size: 1rem;">{{ currency($total_purchases?->balance ?? 0) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="tab-base">
        <ul class="nav nav-underline nav-component border-bottom flex-nowrap overflow-x-auto" role="tablist" style="scrollbar-width: thin;">
            @foreach (['Statement', 'Payment', 'LPO', 'GRN', 'LPO Purchase'] as $tab)
                <li class="nav-item flex-shrink-0" role="presentation">
                    <button class="nav-link px-2 px-md-3 @if ($selected_tab === $tab) active @endif" data-bs-toggle="tab"
                        data-bs-target="#tab-{{ Str::slug($tab) }}" type="button" role="tab"
                        wire:click="$set('selected_tab', '{{ $tab }}')">
                        {{ $tab }}
                    </button>
                </li>
            @endforeach
        </ul>
        <div class="tab-content">
            {{-- Statement Tab --}}
            <div id="tab-statement" class="tab-pane fade @if ($selected_tab === 'Statement') active show @endif" role="tabpanel">
                <div class="p-3">
                    <div class="row g-2 mb-3">
                        <div class="col-md-3">
                            <label class="form-label small text-muted">From Date</label>
                            <input type="date" wire:model.live="statement_from_date" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-muted">To Date</label>
                            <input type="date" wire:model.live="statement_to_date" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-muted">Limit</label>
                            <select wire:model.live="statement_limit" class="form-control form-control-sm">
                                <option value="20">20</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                                <option value="500">500</option>
                            </select>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-sm align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Description</th>
                                    <th>Reference</th>
                                    <th class="text-end">Debit</th>
                                    <th class="text-end">Credit</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($statements as $entry)
                                    <tr>
                                        <td class="text-nowrap">{{ $entry->date ?? '-' }}</td>
                                        <td>{{ $entry->description ?? '-' }}</td>
                                        <td>{{ $entry->reference_number ?? '-' }}</td>
                                        <td class="text-end">
                                            @if (($entry->debit ?? 0) > 0)
                                                <span class="text-danger">{{ currency($entry->debit) }}</span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if (($entry->credit ?? 0) > 0)
                                                <span class="text-success">{{ currency($entry->credit) }}</span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-3">No statement entries found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Payment Tab --}}
            <div id="tab-payment" class="tab-pane fade @if ($selected_tab === 'Payment') active show @endif" role="tabpanel">
                <div class="p-3">
                    @livewire('purchase.vendor-payment', ['name' => $vendor['name'] ?? '', 'vendor_id' => $vendor_id])
                </div>
            </div>

            {{-- LPO Tab --}}
            <div id="tab-lpo" class="tab-pane fade @if ($selected_tab === 'LPO') active show @endif" role="tabpanel">
                <div class="p-3">
                    <div class="row g-2 mb-3">
                        <div class="col-md-3">
                            <label class="form-label small text-muted">Limit</label>
                            <select wire:model.live="lpo_limit" class="form-control form-control-sm">
                                <option value="20">20</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-sm align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Date</th>
                                    <th class="text-end">Total</th>
                                    <th>Status</th>
                                    <th>Created By</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($lpos as $lpo)
                                    <tr>
                                        <td>#{{ $lpo->id }}</td>
                                        <td class="text-nowrap">{{ $lpo->date }}</td>
                                        <td class="text-end fw-medium">{{ currency($lpo->total) }}</td>
                                        <td>
                                            <span class="badge bg-{{ $lpo->status->value === 'approved' ? 'success' : ($lpo->status->value === 'rejected' ? 'danger' : 'warning') }}">
                                                {{ ucfirst($lpo->status->value) }}
                                            </span>
                                        </td>
                                        <td>{{ $lpo->creator?->name ?? '-' }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('lpo::view', $lpo->id) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-3">No LPOs found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- GRN Tab --}}
            <div id="tab-grn" class="tab-pane fade @if ($selected_tab === 'GRN') active show @endif" role="tabpanel">
                <div class="p-3">
                    <div class="row g-2 mb-3">
                        <div class="col-md-3">
                            <label class="form-label small text-muted">Limit</label>
                            <select wire:model.live="grn_limit" class="form-control form-control-sm">
                                <option value="20">20</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-sm align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>GRN No</th>
                                    <th>Date</th>
                                    <th>LPO</th>
                                    <th>Status</th>
                                    <th>Created By</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($grns as $grn)
                                    <tr>
                                        <td>{{ $grn->grn_no }}</td>
                                        <td class="text-nowrap">{{ $grn->date }}</td>
                                        <td>
                                            @if ($grn->localPurchaseOrder)
                                                <a href="{{ route('lpo::view', $grn->local_purchase_order_id) }}" class="text-primary">
                                                    #{{ $grn->local_purchase_order_id }}
                                                </a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $grn->status->value === 'accepted' ? 'success' : ($grn->status->value === 'rejected' ? 'danger' : 'warning') }}">
                                                {{ ucfirst($grn->status->value) }}
                                            </span>
                                        </td>
                                        <td>{{ $grn->creator?->name ?? '-' }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('grn::view', $grn->id) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-3">No GRNs found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- LPO Purchase Tab --}}
            <div id="tab-lpo-purchase" class="tab-pane fade @if ($selected_tab === 'LPO Purchase') active show @endif" role="tabpanel">
                <div class="p-3">
                    <div class="row g-2 mb-3">
                        <div class="col-md-3">
                            <label class="form-label small text-muted">Limit</label>
                            <select wire:model.live="lpo_purchase_limit" class="form-control form-control-sm">
                                <option value="20">20</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-sm align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Invoice No</th>
                                    <th>Date</th>
                                    <th>LPO</th>
                                    <th class="text-end">Grand Total</th>
                                    <th class="text-end">Paid</th>
                                    <th class="text-end">Balance</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($lpo_purchases as $purchase)
                                    <tr>
                                        <td>#{{ $purchase->id }}</td>
                                        <td>
                                            <a href="{{ route('lpo-purchase::view', $purchase->id) }}" class="text-primary fw-semibold">
                                                {{ $purchase->invoice_no }}
                                            </a>
                                        </td>
                                        <td class="text-nowrap">{{ $purchase->date }}</td>
                                        <td>
                                            @if ($purchase->localPurchaseOrder)
                                                <a href="{{ route('lpo::view', $purchase->local_purchase_order_id) }}" class="text-primary">
                                                    #{{ $purchase->local_purchase_order_id }}
                                                </a>
                                            @endif
                                        </td>
                                        <td class="text-end fw-bold text-primary">{{ currency($purchase->grand_total) }}</td>
                                        <td class="text-end text-success fw-semibold">{{ currency($purchase->paid) }}</td>
                                        <td class="text-end text-danger fw-semibold">{{ currency($purchase->balance) }}</td>
                                        <td>
                                            <span class="badge bg-{{ $purchase->status === 'completed' ? 'success' : ($purchase->status === 'cancelled' ? 'danger' : 'warning') }}">
                                                {{ ucfirst($purchase->status) }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('lpo-purchase::view', $purchase->id) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-3">No LPO purchases found</td>
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

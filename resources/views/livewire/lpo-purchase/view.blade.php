<div>
    @if ($errors->any())
        <div class="mb-4 alert alert-danger alert-dismissible fade show">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Status Banner --}}
    @php
        $statusColor = match ($purchase->status) {
            'accepted' => 'success',
            'rejected' => 'danger',
            'pending' => 'warning',
            'completed' => 'success',
            default => 'secondary',
        };
        $statusIcon = match ($purchase->status) {
            'accepted' => 'fa fa-check-circle',
            'rejected' => 'fa fa-times-circle',
            'pending' => 'fa fa-clock-o',
            'completed' => 'fa fa-check-circle',
            default => 'fa fa-info-circle',
        };
    @endphp

    <div class="mb-4 card border-0 shadow-sm overflow-hidden">
        <div class="card-body p-0">
            <div class="d-flex align-items-center p-3 bg-{{ $statusColor }} bg-opacity-10 border-start border-4 border-{{ $statusColor }}">
                <div class="icon-box bg-{{ $statusColor }} bg-opacity-25 rounded-circle p-2 me-3">
                    <i class="{{ $statusIcon }} text-{{ $statusColor }} fs-4"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="mb-0 fw-bold">LPO Purchase {{ $purchase->invoice_no ?? '#'.$purchase->id }}</h5>
                            <small class="text-muted">{{ $purchase->branch?->name }}</small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            @if ($purchase->status === 'pending')
                                @can('lpo-purchase.edit')
                                    <a href="{{ route('lpo-purchase::edit', $purchase->id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fa fa-pencil me-1"></i> Edit
                                    </a>
                                @endcan
                                @can('lpo-purchase.decide')
                                    <a href="{{ route('lpo-purchase::decision', $purchase->id) }}" class="btn btn-sm btn-outline-warning">
                                        <i class="fa fa-gavel me-1"></i> Accept / Reject
                                    </a>
                                @endcan
                            @endif
                            <span class="badge bg-{{ $statusColor }} rounded-pill px-3 py-2 fs-6">
                                <i class="{{ $statusIcon }} me-1"></i>
                                {{ ucfirst($purchase->status) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Purchase Details --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-3">
                        <div class="icon-box bg-primary bg-opacity-10 rounded-circle p-2 me-2">
                            <i class="demo-psi-file text-primary fs-4"></i>
                        </div>
                        <h5 class="mb-0 fw-bold">Purchase Information</h5>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="p-2 rounded bg-light bg-opacity-50">
                                <div class="d-flex align-items-center mb-1">
                                    <i class="fa fa-barcode text-primary me-2"></i>
                                    <small class="text-muted">Invoice No</small>
                                </div>
                                <div class="fw-medium">{{ $purchase->invoice_no ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded bg-light bg-opacity-50">
                                <div class="d-flex align-items-center mb-1">
                                    <i class="demo-psi-calendar-4 text-primary me-2"></i>
                                    <small class="text-muted">Date</small>
                                </div>
                                <div class="fw-medium">{{ $purchase->date ? \Carbon\Carbon::parse($purchase->date)->format('d M Y') : '-' }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded bg-light bg-opacity-50">
                                <div class="d-flex align-items-center mb-1">
                                    <i class="fa fa-shopping-cart text-primary me-2"></i>
                                    <small class="text-muted">LPO</small>
                                </div>
                                <div class="fw-medium">
                                    @if ($purchase->localPurchaseOrder)
                                        <a href="{{ route('lpo::view', $purchase->localPurchaseOrder->id) }}" class="text-primary text-decoration-none">
                                            LPO #{{ $purchase->localPurchaseOrder->id }}
                                            <i class="fa fa-external-link small ms-1"></i>
                                        </a>
                                    @else
                                        -
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded bg-light bg-opacity-50">
                                <div class="d-flex align-items-center mb-1">
                                    <i class="fa fa-building text-primary me-2"></i>
                                    <small class="text-muted">Vendor</small>
                                </div>
                                <div class="fw-medium">{{ $purchase->account?->name ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded bg-light bg-opacity-50">
                                <div class="d-flex align-items-center mb-1">
                                    <i class="demo-psi-male text-primary me-2"></i>
                                    <small class="text-muted">Created By</small>
                                </div>
                                <div class="fw-medium">{{ $purchase->createdUser?->name ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded bg-light bg-opacity-50">
                                <div class="d-flex align-items-center mb-1">
                                    <i class="demo-psi-home text-info me-2"></i>
                                    <small class="text-muted">Branch</small>
                                </div>
                                <div class="fw-medium">{{ $purchase->branch?->name ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded bg-light bg-opacity-50">
                                <div class="d-flex align-items-center mb-1">
                                    <i class="demo-psi-basket-coins text-success me-2"></i>
                                    <small class="text-muted">Total Items</small>
                                </div>
                                <div class="fw-medium">{{ $purchase->items->count() }} items</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded bg-light bg-opacity-50">
                                <div class="d-flex align-items-center mb-1">
                                    <i class="fa fa-money text-success me-2"></i>
                                    <small class="text-muted">Grand Total</small>
                                </div>
                                <div class="fw-bold text-success">{{ number_format($purchase->grand_total, 2) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if ($purchase->status !== 'pending')
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center mb-3">
                            <div class="icon-box bg-{{ $statusColor }} bg-opacity-10 rounded-circle p-2 me-2">
                                <i class="{{ $statusIcon }} text-{{ $statusColor }} fs-4"></i>
                            </div>
                            <h5 class="mb-0 fw-bold">Decision Details</h5>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="p-2 rounded bg-light bg-opacity-50">
                                    <div class="d-flex align-items-center mb-1">
                                        <i class="demo-psi-male text-{{ $statusColor }} me-2"></i>
                                        <small class="text-muted">Action By</small>
                                    </div>
                                    <div class="fw-medium">{{ $purchase->decisionMaker?->name ?? '-' }}</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 rounded bg-light bg-opacity-50">
                                    <div class="d-flex align-items-center mb-1">
                                        <i class="demo-psi-calendar-4 text-{{ $statusColor }} me-2"></i>
                                        <small class="text-muted">Action On</small>
                                    </div>
                                    <div class="fw-medium">{{ $purchase->decision_at?->format('d M Y, h:i A') }}</div>
                                </div>
                            </div>
                            @if ($purchase->decision_note)
                                <div class="col-12">
                                    <div class="p-2 rounded bg-light bg-opacity-50">
                                        <div class="d-flex align-items-center mb-1">
                                            <i class="demo-psi-speech-bubble-3 text-{{ $statusColor }} me-2"></i>
                                            <small class="text-muted">Remarks</small>
                                        </div>
                                        <div class="fw-medium">{{ $purchase->decision_note }}</div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Items Table --}}
    <div class="mb-4 card border-0 shadow-sm">
        <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center">
                    <div class="icon-box bg-info bg-opacity-10 rounded-circle p-2 me-2">
                        <i class="demo-psi-basket-coins text-info fs-4"></i>
                    </div>
                    <h5 class="mb-0 fw-bold">Purchase Items</h5>
                </div>
                <span class="badge bg-info bg-opacity-10 text-info rounded-pill px-3 py-2">
                    {{ $purchase->items->count() }} items
                </span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr class="bg-light">
                            <th class="border-0 rounded-start">#</th>
                            <th class="border-0">Product</th>
                            <th class="border-0">Category</th>
                            <th class="border-0">Brand</th>
                            <th class="border-0">Unit</th>
                            <th class="border-0 text-end">Qty</th>
                            <th class="border-0 text-end">Unit Price</th>
                            <th class="border-0 text-end">Discount</th>
                            <th class="border-0 text-end">Tax %</th>
                            <th class="border-0 text-end rounded-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($purchase->items as $index => $item)
                            @php
                                $gross = $item->quantity * $item->unit_price;
                                $net = $gross - $item->discount;
                                $taxAmt = $net * ($item->tax / 100);
                                $total = $net + $taxAmt;
                            @endphp
                            <tr>
                                <td>
                                    <span class="badge bg-light text-muted rounded-pill">{{ $index + 1 }}</span>
                                </td>
                                <td>
                                    <span class="fw-medium">{{ $item->product->name }}</span>
                                    <br><small class="text-muted">{{ $item->product->code ?? '' }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-primary bg-opacity-10 text-primary">{{ $item->product->mainCategory?->name ?? '-' }}</span>
                                </td>
                                <td>
                                    <span class="text-muted">{{ $item->product->brand?->name ?? '-' }}</span>
                                </td>
                                <td>
                                    <span class="text-muted">{{ $item->unit?->name ?? $item->product->unit?->name ?? '-' }}</span>
                                </td>
                                <td class="text-end">
                                    <span class="fw-medium">{{ $item->quantity }}</span>
                                </td>
                                <td class="text-end">
                                    {{ number_format($item->unit_price, 2) }}
                                </td>
                                <td class="text-end text-danger">
                                    {{ $item->discount > 0 ? number_format($item->discount, 2) : '-' }}
                                </td>
                                <td class="text-end">
                                    {{ $item->tax > 0 ? $item->tax.'%' : '-' }}
                                </td>
                                <td class="text-end fw-bold">
                                    {{ number_format($total, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="demo-psi-basket-coins fs-1 d-block mb-2 opacity-25"></i>
                                        No items
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($purchase->items->count())
                        <tfoot>
                            <tr class="bg-light">
                                <td colspan="6" class="border-0"></td>
                                <td colspan="3" class="border-0 text-end text-muted">Gross Amount:</td>
                                <td class="border-0 text-end fw-medium">{{ number_format($purchase->gross_amount, 2) }}</td>
                            </tr>
                            @if ($purchase->item_discount > 0)
                                <tr class="bg-light">
                                    <td colspan="6" class="border-0"></td>
                                    <td colspan="3" class="border-0 text-end text-muted">Item Discount:</td>
                                    <td class="border-0 text-end text-danger">- {{ number_format($purchase->item_discount, 2) }}</td>
                                </tr>
                            @endif
                            @if ($purchase->tax_amount > 0)
                                <tr class="bg-light">
                                    <td colspan="6" class="border-0"></td>
                                    <td colspan="3" class="border-0 text-end text-muted">Tax:</td>
                                    <td class="border-0 text-end">+ {{ number_format($purchase->tax_amount, 2) }}</td>
                                </tr>
                            @endif
                            @if ($purchase->other_discount > 0)
                                <tr class="bg-light">
                                    <td colspan="6" class="border-0"></td>
                                    <td colspan="3" class="border-0 text-end text-muted">Other Discount:</td>
                                    <td class="border-0 text-end text-danger">- {{ number_format($purchase->other_discount, 2) }}</td>
                                </tr>
                            @endif
                            @if ($purchase->freight > 0)
                                <tr class="bg-light">
                                    <td colspan="6" class="border-0"></td>
                                    <td colspan="3" class="border-0 text-end text-muted">Freight:</td>
                                    <td class="border-0 text-end">+ {{ number_format($purchase->freight, 2) }}</td>
                                </tr>
                            @endif
                            <tr class="bg-light border-top">
                                <td colspan="6" class="fw-bold border-0"></td>
                                <td colspan="3" class="fw-bold text-end border-0">Grand Total:</td>
                                <td class="fw-bold text-end text-success border-0 fs-5">{{ number_format($purchase->grand_total, 2) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    {{-- Journal Entries --}}
    @if ($purchase->journals->count())
        <div class="mb-4 card border-0 shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center">
                        <div class="icon-box bg-purple bg-opacity-10 rounded-circle p-2 me-2" style="background-color: rgba(111, 66, 193, 0.1);">
                            <i class="fa fa-book text-purple fs-4" style="color: #6f42c1;"></i>
                        </div>
                        <h5 class="mb-0 fw-bold">Journal Entries</h5>
                    </div>
                    <span class="badge rounded-pill px-3 py-2" style="background-color: rgba(111, 66, 193, 0.1); color: #6f42c1;">
                        {{ $purchase->journals->count() }} {{ Str::plural('journal', $purchase->journals->count()) }}
                    </span>
                </div>

                @foreach ($purchase->journals as $journal)
                    <div class="mb-3 {{ !$loop->last ? 'border-bottom pb-3' : '' }}">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div>
                                <span class="fw-semibold text-dark">{{ $journal->description }}</span>
                                @if ($journal->reference_number)
                                    <span class="badge bg-light text-muted ms-2">Ref: {{ $journal->reference_number }}</span>
                                @endif
                            </div>
                            <small class="text-muted">
                                <i class="fa fa-calendar me-1"></i>
                                {{ $journal->date ? \Carbon\Carbon::parse($journal->date)->format('d M Y') : '-' }}
                            </small>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead>
                                    <tr class="bg-light">
                                        <th class="border-0 rounded-start small text-muted fw-semibold">Account</th>
                                        <th class="border-0 small text-muted fw-semibold">Remarks</th>
                                        <th class="border-0 text-end small text-muted fw-semibold" style="width: 130px;">Debit</th>
                                        <th class="border-0 text-end rounded-end small text-muted fw-semibold" style="width: 130px;">Credit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($journal->entries->where('account_id', '!=', $purchase->account_id) as $entry)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="fa fa-{{ $entry->debit > 0 ? 'arrow-up text-success' : 'arrow-down text-danger' }} me-2 small"></i>
                                                    <span class="fw-medium">{{ $entry->account?->name ?? '-' }}</span>
                                                </div>
                                            </td>
                                            <td class="text-muted small">{{ $entry->remarks ?? '-' }}</td>
                                            <td class="text-end">
                                                @if ($entry->debit > 0)
                                                    <span class="fw-medium text-success">{{ number_format($entry->debit, 2) }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                @if ($entry->credit > 0)
                                                    <span class="fw-medium text-danger">{{ number_format($entry->credit, 2) }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    @php
                                        $filteredEntries = $journal->entries->where('account_id', '!=', $purchase->account_id);
                                    @endphp
                                    <tr class="bg-light border-top">
                                        <td colspan="2" class="border-0 fw-bold text-end">Total</td>
                                        <td class="border-0 text-end fw-bold text-success">
                                            {{ number_format($filteredEntries->sum('debit'), 2) }}
                                        </td>
                                        <td class="border-0 text-end fw-bold text-danger">
                                            {{ number_format($filteredEntries->sum('credit'), 2) }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Approval Action --}}
    @if ($purchase->status === 'pending' && $is_approvable)
        <div class="mb-4 card border-0 shadow-sm border-top border-3 border-warning">
            <div class="card-body p-4">
                <div class="d-flex align-items-center mb-3">
                    <div class="icon-box bg-warning bg-opacity-10 rounded-circle p-2 me-2">
                        <i class="fa fa-gavel text-warning fs-4"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 fw-bold">Take Action</h5>
                        <small class="text-muted">Accept or reject this LPO purchase</small>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        <i class="demo-psi-speech-bubble-3 me-1"></i> Remarks
                    </label>
                    <textarea class="form-control" rows="3" wire:model="remarks" placeholder="Enter remarks (required for rejection)"></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-danger px-4" wire:click="reject" wire:confirm="Are you sure you want to reject this LPO Purchase?">
                        <i class="fa fa-times me-1"></i> Reject
                    </button>
                    <button type="button" class="btn btn-success px-4" wire:click="accept" wire:confirm="Are you sure you want to accept this LPO Purchase? Journal entries will be created.">
                        <i class="fa fa-check me-1"></i> Accept
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

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
                                            <small class="text-muted">#{{ $sale_return->invoice_no }}</small>
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
                                                    <div class="fw-medium">{{ systemDate($sale_return->date) }}</div>
                                                </div>
                                            </div>

                                            <!-- Reference & Status -->
                                            <div class="col-6">
                                                <div class="info-item p-2 hover-lift rounded bg-light bg-opacity-50">
                                                    <div class="d-flex align-items-center mb-1">
                                                        <i class="fa fa-file-text-o text-info me-2"></i>
                                                        <small class="text-muted">Reference</small>
                                                    </div>
                                                    <div class="fw-medium">{{ $sale_return->reference_no ?: 'N/A' }}</div>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="info-item p-2 hover-lift rounded bg-light bg-opacity-50">
                                                    <div class="d-flex align-items-center mb-1">
                                                        <i
                                                            class="demo-psi-clock {{ $sale_return->status === 'completed' ? 'text-success' : ($sale_return->status === 'cancelled' ? 'text-danger' : 'text-warning') }} me-2"></i>
                                                        <small class="text-muted">Status</small>
                                                    </div>
                                                    <span
                                                        class="badge {{ $sale_return->status === 'completed' ? 'bg-success' : ($sale_return->status === 'cancelled' ? 'bg-danger' : 'bg-warning') }} rounded-pill">
                                                        {{ ucfirst($sale_return->status) }}
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
                                                <small class="text-muted">Customer #{{ $sale_return->account_id }}</small>
                                            </div>
                                        </div>
                                        <a href="{{ route('account::customer::view', $sale_return->account_id) }}" class="btn btn-sm btn-outline-success hover-lift">
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
                                                        <span class="fw-medium">{{ $sale_return->account?->name }}</span>
                                                    </div>
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
                                                    @if ($sale_return->account?->email)
                                                        <div>
                                                            <small class="text-muted d-block">Email</small>
                                                            <span class="fw-medium">{{ $sale_return->account?->email }}</span>
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
                </div>

                <!-- Items Table with enhanced styling -->
                <div class="mb-4">
                    <h5 class="card-title d-flex align-items-center mb-3">
                        <i class="fa fa-shopping-cart me-2"></i>
                        Items
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-sm align-middle">
                            <thead>
                                <tr class="bg-primary">
                                    <th class="text-white">SL No</th>
                                    <th class="text-white" width="20%">Product/Service</th>
                                    <th class="text-white text-end">Unit Price</th>
                                    <th class="text-white text-end">Quantity</th>
                                    <th class="text-white text-end">Discount</th>
                                    <th class="text-white text-end">Tax %</th>
                                    <th class="text-white text-end">Total</th>
                                    @if ($sale_returns['other_discount'] > 0)
                                        <th class="text-white text-end">Effective Total</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($items as $item)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="fa fa-box me-2"></i>
                                                <div>
                                                    <a href="{{ route('inventory::product::view', $item['product_id']) }}" class="text-primary">{{ $item['name'] }}</a>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end">{{ currency($item['unit_price']) }}</td>
                                        <td class="text-end">{{ currency($item['quantity']) }}</td>
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
                                        <td class="text-end">{{ currency($item['total']) }}</td>
                                        @if ($sale_returns['other_discount'] > 0)
                                            <td class="text-end">{{ currency($item['effective_total']) }}</td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-group-divider">
                                @php
                                    $items = collect($items);
                                @endphp
                                <tr class="fw-bold">
                                    <td colspan="3" class="text-end">Total</td>
                                    <td class="text-end">{{ currency($items->sum('quantity')) }}</td>
                                    <td class="text-end">{{ currency($items->sum('discount')) }}</td>
                                    <td class="text-end">{{ currency($items->sum('tax_amount')) }}</td>
                                    <td class="text-end">{{ currency($items->sum('total')) }}</td>
                                    @if ($sale_returns['other_discount'] > 0)
                                        <td class="text-end">{{ currency($items->sum('effective_total')) }}</td>
                                    @endif
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-light py-3">
                                <h5 class="card-title mb-0">Order Summary</h5>
                            </div>
                            <div class="card-body">
                                <div class="list-group list-group-flush">
                                    <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-0">
                                        <span class="text-muted">Gross Total</span>
                                        <span class="fw-medium">{{ currency($sale_return->gross_amount) }}</span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-0">
                                        <span class="text-muted">Sale Return Total</span>
                                        <span class="fw-medium">{{ currency($sale_return->total) }}</span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-0">
                                        <span class="text-muted">Other Discount</span>
                                        <span class="fw-medium text-danger">-{{ currency($sale_return->other_discount) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-light py-3">
                                <h5 class="card-title mb-0">Final Summary</h5>
                            </div>
                            <div class="card-body">
                                <div class="list-group list-group-flush">
                                    <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-0">
                                        <span class="h6 mb-0">Total Return Amount</span>
                                        <span class="h5 mb-0 text-success">{{ currency($sale_return->grand_total) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex justify-content-end gap-2 my-4 d-print-none">
                    @if ($sale_returns['status'] != 'cancelled')
                        @can('sales return.cancel')
                            <button type="button" wire:click='save("cancelled")' wire:confirm="Are you sure to cancel this?" class="btn btn-danger">
                                <i class="fa fa-times me-2"></i>Cancel
                            </button>
                        @endcan
                        @can('sales return.edit completed')
                            <a href="{{ route('sale_return::edit', $sale_returns['id']) }}" class="btn btn-primary">
                                <i class="fa fa-pencil me-2"></i>Edit
                            </a>
                        @endcan
                        @can('sales return.cancel')
                            <button type="button" wire:click='sendToWhatsapp' class="btn btn-success">
                                <i class="fa fa-whatsapp me-2"></i>Whatsapp
                            </button>
                        @endcan
                    @endif
                </div>

                @if ($sale_return['address'])
                    <div class="bg-light p-3 rounded mt-4">
                        <h6 class="fw-bold mb-2">
                            <i class="fa fa-sticky-note me-2"></i>
                            Notes & Information
                        </h6>
                        <p class="mb-0">{{ $sale_return['address'] }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @can('sales return.view journal entries')
        @if (count($sale_return->journals))
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <h5 class="card-title d-flex align-items-center mb-3">
                        <i class="fa fa-book me-2"></i>Journal Entries
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-striped table-sm align-middle">
                            <thead>
                                <tr class="bg-primary">
                                    <th class="text-white text-end">SL No</th>
                                    <th class="text-white">Date</th>
                                    <th class="text-white">Account Name</th>
                                    <th class="text-white">Description</th>
                                    <th class="text-white text-end">Debit</th>
                                    <th class="text-white text-end">Credit</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($sale_return->journals as $journal)
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
            </div>
        @endif
    @endcan
</div>

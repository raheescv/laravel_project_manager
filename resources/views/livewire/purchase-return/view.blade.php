<div>
    <div class="col-md-12 mb-3">
        <div class="card shadow-sm">
            <div class="card-body">
                {{-- Header --}}
                <div class="d-flex justify-content-between align-items-center mb-3 d-print-none">
                    <h4 class="mb-0 d-flex align-items-center">
                        <i class="fa fa-undo text-primary me-2"></i>
                        {{ __('Purchase Return Details') }}
                    </h4>
                    <div class="d-flex gap-2">
                        @can('purchase return.edit')
                            @if ($purchaseReturn->status != 'cancelled')
                                <a href="{{ route('purchase_return::edit', $purchaseReturn->id) }}" class="btn btn-primary btn-sm">
                                    <i class="fa fa-pencil me-1"></i>{{ __('Edit') }}
                                </a>
                            @endif
                        @endcan
                        <a href="{{ route('purchase_return::index') }}" class="btn btn-secondary btn-sm">
                            <i class="fa fa-arrow-left me-1"></i>{{ __('Back') }}
                        </a>
                    </div>
                </div>

                {{-- Top Info Cards --}}
                <div class="row g-2 mb-3">
                    {{-- Invoice Info --}}
                    <div class="col-lg-4">
                        <div class="glass-card h-100">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="icon-box bg-primary bg-opacity-10 rounded-circle p-2 me-2">
                                        <i class="fa fa-file-text-o text-primary fs-4"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-0 text-primary-gradient">{{ __('Return Details') }}</h5>
                                        <small class="text-muted">#{{ $purchaseReturn->invoice_no }}</small>
                                    </div>
                                </div>

                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="info-item p-2 hover-lift rounded bg-light bg-opacity-50">
                                            <div class="d-flex align-items-center mb-1">
                                                <i class="fa fa-calendar text-primary me-2"></i>
                                                <small class="text-muted">{{ __('Date') }}</small>
                                            </div>
                                            <div class="fw-medium">{{ systemDate($purchaseReturn->date) }}</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="info-item p-2 hover-lift rounded bg-light bg-opacity-50">
                                            <div class="d-flex align-items-center mb-1">
                                                <i class="fa fa-building-o text-info me-2"></i>
                                                <small class="text-muted">{{ __('Branch') }}</small>
                                            </div>
                                            <div class="fw-medium">{{ $purchaseReturn->branch?->name ?: 'N/A' }}</div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="info-item p-2 hover-lift rounded bg-light bg-opacity-50">
                                            <div class="d-flex align-items-center mb-1">
                                                <i
                                                    class="fa fa-clock-o {{ $purchaseReturn->status === 'completed' ? 'text-success' : ($purchaseReturn->status === 'cancelled' ? 'text-danger' : 'text-warning') }} me-2"></i>
                                                <small class="text-muted">{{ __('Status') }}</small>
                                            </div>
                                            <span
                                                class="badge {{ $purchaseReturn->status === 'completed' ? 'bg-success' : ($purchaseReturn->status === 'cancelled' ? 'bg-danger' : 'bg-warning') }} rounded-pill">
                                                {{ ucfirst($purchaseReturn->status) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Vendor Info --}}
                    <div class="col-lg-5">
                        <div class="glass-card h-100">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="d-flex align-items-center">
                                        <div class="icon-box bg-success bg-opacity-10 rounded-circle p-2 me-2">
                                            <i class="fa fa-truck text-success fs-4"></i>
                                        </div>
                                        <div>
                                            <h5 class="mb-0 text-success-gradient">{{ __('Vendor Information') }}</h5>
                                            <small class="text-muted">{{ __('Vendor') }} #{{ $purchaseReturn->account_id }}</small>
                                        </div>
                                    </div>
                                    <a href="{{ route('account::view', $purchaseReturn->account_id) }}" class="btn btn-sm btn-outline-success hover-lift">
                                        <i class="fa fa-user me-1"></i>{{ __('Profile') }}
                                    </a>
                                </div>

                                <div class="row g-2">
                                    <div class="col-sm-6">
                                        <div class="p-3 bg-light bg-opacity-50 rounded h-100">
                                            <h6 class="mb-2 text-primary d-flex align-items-center">
                                                <i class="fa fa-id-card-o me-2"></i>{{ __('Vendor Details') }}
                                            </h6>
                                            <div class="ps-1">
                                                <small class="text-muted d-block">{{ __('Name') }}</small>
                                                <span class="fw-medium">{{ $purchaseReturn->account?->name }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="p-3 bg-light bg-opacity-50 rounded h-100">
                                            <h6 class="mb-2 text-primary d-flex align-items-center">
                                                <i class="fa fa-phone me-2"></i>{{ __('Contact') }}
                                            </h6>
                                            <div class="ps-1">
                                                @if ($purchaseReturn->account?->mobile)
                                                    <div class="mb-1">
                                                        <small class="text-muted d-block">{{ __('Phone') }}</small>
                                                        <span class="fw-medium">{{ $purchaseReturn->account?->mobile }}</span>
                                                    </div>
                                                @endif
                                                @if ($purchaseReturn->account?->email)
                                                    <div>
                                                        <small class="text-muted d-block">{{ __('Email') }}</small>
                                                        <span class="fw-medium">{{ $purchaseReturn->account?->email }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Audit Info --}}
                    <div class="col-lg-3">
                        <div class="glass-card h-100">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="icon-box bg-warning bg-opacity-10 rounded-circle p-2 me-2">
                                        <i class="fa fa-history text-warning fs-4"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-0">{{ __('Audit') }}</h5>
                                        <small class="text-muted">{{ __('Activity Trail') }}</small>
                                    </div>
                                </div>

                                <div class="info-list">
                                    @if ($purchaseReturn->createdUser)
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fa fa-plus-circle text-success me-2"></i>
                                            <div>
                                                <small class="text-muted d-block">{{ __('Created By') }}</small>
                                                <span class="fw-medium">{{ $purchaseReturn->createdUser->name }}</span>
                                            </div>
                                        </div>
                                    @endif
                                    @if ($purchaseReturn->updatedUser)
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fa fa-pencil text-info me-2"></i>
                                            <div>
                                                <small class="text-muted d-block">{{ __('Updated By') }}</small>
                                                <span class="fw-medium">{{ $purchaseReturn->updatedUser->name }}</span>
                                            </div>
                                        </div>
                                    @endif
                                    @if ($purchaseReturn->cancelledUser)
                                        <div class="d-flex align-items-center">
                                            <i class="fa fa-times-circle text-danger me-2"></i>
                                            <div>
                                                <small class="text-muted d-block">{{ __('Cancelled By') }}</small>
                                                <span class="fw-medium">{{ $purchaseReturn->cancelledUser->name }}</span>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Items Table --}}
                <div class="mb-4">
                    <h5 class="card-title d-flex align-items-center mb-3">
                        <i class="fa fa-shopping-cart me-2"></i>
                        {{ __('Items') }}
                        <span class="badge bg-secondary ms-2">{{ $purchaseReturn->items->count() }}</span>
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-sm align-middle">
                            <thead>
                                <tr class="bg-primary">
                                    <th class="text-white">#</th>
                                    <th class="text-white">{{ __('Product') }}</th>
                                    <th class="text-white text-end">{{ __('Quantity') }}</th>
                                    <th class="text-white">{{ __('Unit') }}</th>
                                    <th class="text-white text-end">{{ __('Unit Price') }}</th>
                                    <th class="text-white text-end">{{ __('Discount') }}</th>
                                    <th class="text-white text-end">{{ __('Tax') }}</th>
                                    <th class="text-white text-end">{{ __('Total') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($purchaseReturn->items as $index => $item)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="fa fa-cube me-2 text-muted"></i>
                                                <a href="{{ route('inventory::product::view', $item->product_id) }}" class="text-primary">
                                                    {{ $item->product?->name }}
                                                </a>
                                            </div>
                                        </td>
                                        <td class="text-end">{{ currency($item->quantity, 3) }}</td>
                                        <td>{{ $item->unit?->name }}</td>
                                        <td class="text-end">{{ currency($item->unit_price) }}</td>
                                        <td class="text-end">
                                            @if ($item->discount != 0)
                                                {{ currency($item->discount) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if ($item->tax_amount != 0)
                                                {{ currency($item->tax_amount) }} ({{ round($item->tax, 2) }}%)
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="text-end fw-medium">{{ currency($item->total) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-group-divider">
                                @php
                                    $items = $purchaseReturn->items;
                                @endphp
                                <tr class="fw-bold">
                                    <td colspan="2" class="text-end">{{ __('Total') }}</td>
                                    <td class="text-end">{{ currency($items->sum('quantity'), 3) }}</td>
                                    <td></td>
                                    <td></td>
                                    <td class="text-end">{{ currency($items->sum('discount')) }}</td>
                                    <td class="text-end">{{ currency($items->sum('tax_amount')) }}</td>
                                    <td class="text-end">{{ currency($items->sum('total')) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                {{-- Summary Cards --}}
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-light py-3">
                                <h5 class="card-title mb-0 d-flex align-items-center">
                                    <i class="fa fa-calculator me-2 text-primary"></i>{{ __('Order Summary') }}
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="list-group list-group-flush">
                                    <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-0">
                                        <span class="text-muted">{{ __('Gross Total') }}</span>
                                        <span class="fw-medium">{{ currency($purchaseReturn->gross_amount) }}</span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-0">
                                        <span class="text-muted">{{ __('Item Discount') }}</span>
                                        <span class="fw-medium text-danger">-{{ currency($purchaseReturn->item_discount) }}</span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-0">
                                        <span class="text-muted">{{ __('Tax Amount') }}</span>
                                        <span class="fw-medium">{{ currency($purchaseReturn->tax_amount) }}</span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-0">
                                        <span class="text-muted">{{ __('Subtotal') }}</span>
                                        <span class="fw-medium">{{ currency($purchaseReturn->total) }}</span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-0">
                                        <span class="text-muted">{{ __('Other Discount') }}</span>
                                        <span class="fw-medium text-danger">-{{ currency($purchaseReturn->other_discount) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-light py-3">
                                <h5 class="card-title mb-0 d-flex align-items-center">
                                    <i class="fa fa-money me-2 text-success"></i>{{ __('Payment Summary') }}
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="list-group list-group-flush">
                                    <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-0">
                                        <span class="h6 mb-0">{{ __('Grand Total') }}</span>
                                        <span class="h5 mb-0 text-primary">{{ currency($purchaseReturn->grand_total) }}</span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-0">
                                        <span class="text-muted">{{ __('Paid') }}</span>
                                        <span class="fw-medium text-success">{{ currency($purchaseReturn->paid) }}</span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-0 border-top pt-3 mt-2">
                                        <span class="h6 mb-0">{{ __('Balance') }}</span>
                                        <span class="h5 mb-0 {{ $purchaseReturn->balance > 0 ? 'text-warning' : 'text-success' }}">
                                            {{ currency($purchaseReturn->balance) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Reason / Notes --}}
                @if ($purchaseReturn->reason)
                    <div class="bg-light p-3 rounded mb-4">
                        <h6 class="fw-bold mb-2">
                            <i class="fa fa-sticky-note-o me-2"></i>
                            {{ __('Reason') }}
                        </h6>
                        <p class="mb-0">{{ $purchaseReturn->reason }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Payments --}}
    @if ($purchaseReturn->payments->count() > 0)
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h5 class="card-title d-flex align-items-center mb-3">
                    <i class="fa fa-credit-card me-2 text-success"></i>{{ __('Payments') }}
                    <span class="badge bg-success ms-2">{{ $purchaseReturn->payments->count() }}</span>
                </h5>
                <div class="table-responsive">
                    <table class="table table-striped table-sm align-middle">
                        <thead>
                            <tr class="bg-primary">
                                <th class="text-white">#</th>
                                <th class="text-white">{{ __('Date') }}</th>
                                <th class="text-white">{{ __('Payment Method') }}</th>
                                <th class="text-white text-end">{{ __('Amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($purchaseReturn->payments as $payment)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ systemDate($payment->date) }}</td>
                                    <td>
                                        <i class="fa fa-money me-1 text-muted"></i>
                                        {{ $payment->paymentMethod?->name }}
                                    </td>
                                    <td class="text-end fw-medium">{{ currency($payment->amount) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-group-divider fw-bold">
                            <tr>
                                <td colspan="3" class="text-end">{{ __('Total Paid') }}</td>
                                <td class="text-end text-success">{{ currency($purchaseReturn->paid) }}</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end">{{ __('Balance') }}</td>
                                <td class="text-end {{ $purchaseReturn->balance > 0 ? 'text-warning' : 'text-success' }}">
                                    {{ currency($purchaseReturn->balance) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- Journal Entries --}}
    @if (count($purchaseReturn->journals))
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h5 class="card-title d-flex align-items-center mb-3">
                    <i class="fa fa-book me-2 text-info"></i>{{ __('Journal Entries') }}
                </h5>
                <div class="table-responsive">
                    <table class="table table-striped table-sm align-middle">
                        <thead>
                            <tr class="bg-primary">
                                <th class="text-white text-end">#</th>
                                <th class="text-white">{{ __('Date') }}</th>
                                <th class="text-white">{{ __('Account Name') }}</th>
                                <th class="text-white">{{ __('Description') }}</th>
                                <th class="text-white text-end">{{ __('Debit') }}</th>
                                <th class="text-white text-end">{{ __('Credit') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($purchaseReturn->journals as $journal)
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

    {{-- Inventory Log --}}
    @if (count($inventory_logs))
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h5 class="card-title d-flex align-items-center mb-3">
                    <i class="fa fa-cubes me-2 text-warning"></i>{{ __('Inventory Log') }}
                    <span class="badge bg-secondary ms-2">{{ count($inventory_logs) }}</span>
                </h5>
                <div class="table-responsive">
                    <table class="table table-striped table-sm align-middle">
                        <thead>
                            <tr class="bg-primary">
                                <th class="text-white">#</th>
                                <th class="text-white">{{ __('Date') }}</th>
                                <th class="text-white">{{ __('Product') }}</th>
                                <th class="text-white">{{ __('Barcode') }}</th>
                                <th class="text-white">{{ __('Batch') }}</th>
                                <th class="text-white text-end">{{ __('In') }}</th>
                                <th class="text-white text-end">{{ __('Out') }}</th>
                                <th class="text-white text-end">{{ __('Balance') }}</th>
                                <th class="text-white text-end">{{ __('Cost') }}</th>
                                <th class="text-white">{{ __('Remarks') }}</th>
                                <th class="text-white">{{ __('User') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($inventory_logs as $log)
                                <tr>
                                    <td>{{ $log->id }}</td>
                                    <td class="text-nowrap">{{ systemDateTime($log->created_at) }}</td>
                                    <td>
                                        <a href="{{ route('inventory::product::view', $log->product_id) }}" class="text-primary">
                                            {{ $log->product?->name }}
                                        </a>
                                    </td>
                                    <td>{{ $log->barcode }}</td>
                                    <td>{{ $log->batch }}</td>
                                    <td class="text-end text-success">{{ $log->quantity_in > 0 ? $log->quantity_in : '-' }}</td>
                                    <td class="text-end text-danger">{{ $log->quantity_out > 0 ? $log->quantity_out : '-' }}</td>
                                    <td class="text-end fw-semibold">{{ $log->balance }}</td>
                                    <td class="text-end">{{ currency($log->cost) }}</td>
                                    <td>{{ $log->remarks }}</td>
                                    <td>{{ $log->user_name }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-light fw-bold">
                            <tr>
                                <th colspan="5" class="text-end">{{ __('Total') }}</th>
                                <th class="text-end text-success">{{ $inventory_logs->sum('quantity_in') }}</th>
                                <th class="text-end text-danger">{{ $inventory_logs->sum('quantity_out') }}</th>
                                <th colspan="4"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>

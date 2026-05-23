@php
    $statusKey = $purchaseReturn->status;
    $statusTone = $statusKey === 'completed' ? 'success' : ($statusKey === 'cancelled' ? 'danger' : 'warning');
    $grandTotal = (float) $purchaseReturn->grand_total;
    $paid = (float) $purchaseReturn->paid;
    $balance = (float) $purchaseReturn->balance;
    $paidPct = $grandTotal > 0 ? min(100, round(($paid / $grandTotal) * 100, 1)) : 0;
    $balanceTone = $balance > 0 ? 'warning' : 'success';
@endphp

<div>
    {{-- ====================== HERO ====================== --}}
    <div class="card border-0 border-top border-4 border-{{ $statusTone }} shadow rounded-3 mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap align-items-start gap-3">
                {{-- Status icon block --}}
                <div class="bg-{{ $statusTone }}-subtle text-{{ $statusTone }}-emphasis rounded-3 p-3 d-inline-flex align-items-center justify-content-center flex-shrink-0">
                    <i class="fa fa-undo fa-2x"></i>
                </div>

                {{-- Title + meta --}}
                <div class="flex-grow-1">
                    <div class="text-primary text-uppercase small fw-bold mb-1">
                        {{ __('Purchase Return') }}
                    </div>
                    <h2 class="mb-1 fw-bold text-body-emphasis">#{{ $purchaseReturn->invoice_no ?: 'PR-'.$purchaseReturn->id }}</h2>
                    <div class="text-body-secondary mb-3">
                        <i class="fa fa-truck text-success me-1"></i>{{ $purchaseReturn->account?->name ?: __('Unknown Vendor') }}
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge rounded-pill bg-{{ $statusTone }} text-white px-3 py-2">
                            <i class="fa fa-circle me-1" style="font-size:.5rem"></i>{{ ucfirst($statusKey) }}
                        </span>
                        <span class="badge rounded-pill bg-body-tertiary text-body-emphasis border px-3 py-2">
                            <i class="fa fa-calendar text-primary me-1"></i>{{ systemDate($purchaseReturn->date) }}
                        </span>
                        @if ($purchaseReturn->branch?->name)
                            <span class="badge rounded-pill bg-body-tertiary text-body-emphasis border px-3 py-2">
                                <i class="fa fa-building-o text-info me-1"></i>{{ $purchaseReturn->branch->name }}
                            </span>
                        @endif
                        @if ($purchaseReturn->createdUser?->name)
                            <span class="badge rounded-pill bg-body-tertiary text-body-emphasis border px-3 py-2">
                                <i class="fa fa-user text-warning me-1"></i>{{ $purchaseReturn->createdUser->name }}
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Action buttons --}}
                <div class="d-flex flex-wrap gap-2 d-print-none ms-auto">
                    @can('purchase return.edit')
                        @if ($statusKey !== 'cancelled')
                            <a href="{{ route('purchase_return::edit', $purchaseReturn->id) }}" class="btn btn-primary btn-sm fw-semibold">
                                <i class="fa fa-pencil me-1"></i>{{ __('Edit') }}
                            </a>
                        @endif
                    @endcan
                    <a href="{{ route('purchase_return::index') }}" class="btn btn-outline-secondary btn-sm fw-semibold">
                        <i class="fa fa-arrow-left me-1"></i>{{ __('Back') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- ====================== STAT TILES ====================== --}}
    <div class="row g-3 mb-3">
        @php
            $tiles = [
                ['label' => __('Grand Total'), 'value' => currency($grandTotal),                  'hint' => __('Net return value'), 'icon' => 'fa-money',        'tone' => 'primary'],
                ['label' => __('Paid'),        'value' => currency($paid),                        'hint' => $paidPct.'% '.__('settled'), 'icon' => 'fa-check-circle', 'tone' => 'success'],
                ['label' => __('Balance'),     'value' => currency($balance),                     'hint' => $balance > 0 ? __('Outstanding') : __('Fully cleared'), 'icon' => 'fa-warning', 'tone' => $balanceTone],
                ['label' => __('Items'),       'value' => $purchaseReturn->items->count(),        'hint' => currency($purchaseReturn->items->sum('quantity'), 3).' '.__('total qty'), 'icon' => 'fa-cubes',  'tone' => 'info'],
            ];
        @endphp
        @foreach ($tiles as $t)
            <div class="col-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm border-start border-4 border-{{ $t['tone'] }}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="flex-grow-1 me-2">
                                <div class="small text-body-secondary text-uppercase fw-semibold">{{ $t['label'] }}</div>
                                <div class="h4 mb-0 mt-1 fw-bold text-{{ $t['tone'] }}-emphasis">{{ $t['value'] }}</div>
                            </div>
                            <div class="bg-{{ $t['tone'] }}-subtle text-{{ $t['tone'] }}-emphasis rounded p-3 d-inline-flex align-items-center justify-content-center">
                                <i class="fa {{ $t['icon'] }} fa-lg"></i>
                            </div>
                        </div>
                        <div class="small text-body-secondary mt-2">{{ $t['hint'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ====================== INFO CARDS ====================== --}}
    <div class="row g-3 mb-3">
        {{-- Return details --}}
        <div class="col-lg-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-body-tertiary border-0">
                    <h6 class="mb-0 fw-bold">
                        <i class="fa fa-file-text-o text-primary me-2"></i>{{ __('Return Details') }}
                    </h6>
                </div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-body-secondary fw-semibold">{{ __('Invoice') }}</dt>
                        <dd class="col-7 mb-2">{{ $purchaseReturn->invoice_no ?: '—' }}</dd>

                        <dt class="col-5 text-body-secondary fw-semibold">{{ __('Date') }}</dt>
                        <dd class="col-7 mb-2">{{ systemDate($purchaseReturn->date) }}</dd>

                        <dt class="col-5 text-body-secondary fw-semibold">{{ __('Branch') }}</dt>
                        <dd class="col-7 mb-2">{{ $purchaseReturn->branch?->name ?: '—' }}</dd>

                        <dt class="col-5 text-body-secondary fw-semibold">{{ __('Status') }}</dt>
                        <dd class="col-7 mb-0">
                            <span class="badge rounded-pill bg-{{ $statusTone }}-subtle text-{{ $statusTone }}-emphasis border border-{{ $statusTone }}-subtle">
                                <i class="fa fa-circle me-1" style="font-size:.5rem"></i>{{ ucfirst($statusKey) }}
                            </span>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>

        {{-- Vendor --}}
        <div class="col-lg-5">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-body-tertiary border-0 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold">
                        <i class="fa fa-truck text-success me-2"></i>{{ __('Vendor') }}
                    </h6>
                    <a href="{{ route('account::view', $purchaseReturn->account_id) }}" class="btn btn-sm btn-link text-decoration-none p-0">
                        <i class="fa fa-external-link me-1"></i>{{ __('Profile') }}
                    </a>
                </div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-4 text-body-secondary fw-semibold">{{ __('Name') }}</dt>
                        <dd class="col-8 mb-2 fw-semibold">{{ $purchaseReturn->account?->name ?: '—' }}</dd>

                        @if ($purchaseReturn->account?->mobile)
                            <dt class="col-4 text-body-secondary fw-semibold">{{ __('Phone') }}</dt>
                            <dd class="col-8 mb-2">
                                <i class="fa fa-phone text-success me-1"></i>{{ $purchaseReturn->account->mobile }}
                            </dd>
                        @endif

                        @if ($purchaseReturn->account?->email)
                            <dt class="col-4 text-body-secondary fw-semibold">{{ __('Email') }}</dt>
                            <dd class="col-8 mb-0">
                                <i class="fa fa-envelope-o text-info me-1"></i>{{ $purchaseReturn->account->email }}
                            </dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>

        {{-- Activity --}}
        <div class="col-lg-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-body-tertiary border-0">
                    <h6 class="mb-0 fw-bold">
                        <i class="fa fa-history text-warning me-2"></i>{{ __('Activity') }}
                    </h6>
                </div>
                <ul class="list-group list-group-flush small">
                    @if ($purchaseReturn->createdUser)
                        <li class="list-group-item d-flex align-items-center">
                            <i class="fa fa-plus-circle text-success me-2"></i>
                            <div class="flex-grow-1">
                                <div class="text-body-secondary" style="font-size:.7rem">{{ __('Created') }}</div>
                                <div class="fw-semibold">{{ $purchaseReturn->createdUser->name }}</div>
                            </div>
                        </li>
                    @endif
                    @if ($purchaseReturn->updatedUser)
                        <li class="list-group-item d-flex align-items-center">
                            <i class="fa fa-pencil text-info me-2"></i>
                            <div class="flex-grow-1">
                                <div class="text-body-secondary" style="font-size:.7rem">{{ __('Updated') }}</div>
                                <div class="fw-semibold">{{ $purchaseReturn->updatedUser->name }}</div>
                            </div>
                        </li>
                    @endif
                    @if ($purchaseReturn->cancelledUser)
                        <li class="list-group-item d-flex align-items-center">
                            <i class="fa fa-times-circle text-danger me-2"></i>
                            <div class="flex-grow-1">
                                <div class="text-body-secondary" style="font-size:.7rem">{{ __('Cancelled') }}</div>
                                <div class="fw-semibold">{{ $purchaseReturn->cancelledUser->name }}</div>
                            </div>
                        </li>
                    @endif
                    @if (!$purchaseReturn->createdUser && !$purchaseReturn->updatedUser && !$purchaseReturn->cancelledUser)
                        <li class="list-group-item text-body-secondary">{{ __('No activity recorded') }}</li>
                    @endif
                </ul>
            </div>
        </div>
    </div>

    {{-- ====================== ITEMS ====================== --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-body-tertiary border-0 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold">
                <i class="fa fa-shopping-cart text-primary me-2"></i>{{ __('Items') }}
            </h6>
            <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis">{{ $purchaseReturn->items->count() }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">#</th>
                        <th>{{ __('Product') }}</th>
                        <th class="text-end">{{ __('Quantity') }}</th>
                        <th>{{ __('Unit') }}</th>
                        <th class="text-end">{{ __('Unit Price') }}</th>
                        <th class="text-end">{{ __('Discount') }}</th>
                        <th class="text-end">{{ __('Tax') }}</th>
                        <th class="text-end pe-3">{{ __('Total') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($purchaseReturn->items as $index => $item)
                        <tr>
                            <td class="ps-3 text-body-secondary">{{ $index + 1 }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="bg-primary-subtle text-primary-emphasis rounded p-2 me-2 d-inline-flex align-items-center justify-content-center">
                                        <i class="fa fa-cube"></i>
                                    </span>
                                    <a href="{{ route('inventory::product::view', $item->product_id) }}" class="text-decoration-none fw-semibold">
                                        {{ $item->product?->name }}
                                    </a>
                                </div>
                            </td>
                            <td class="text-end">{{ currency($item->quantity, 3) }}</td>
                            <td>
                                <span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis">{{ $item->unit?->name ?: '—' }}</span>
                            </td>
                            <td class="text-end">{{ currency($item->unit_price) }}</td>
                            <td class="text-end">
                                @if ($item->discount != 0)
                                    <span class="badge rounded-pill bg-danger-subtle text-danger-emphasis">−{{ currency($item->discount) }}</span>
                                @else
                                    <span class="text-body-secondary">—</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if ($item->tax_amount != 0)
                                    <span class="badge rounded-pill bg-info-subtle text-info-emphasis">
                                        {{ currency($item->tax_amount) }} ({{ round($item->tax, 2) }}%)
                                    </span>
                                @else
                                    <span class="text-body-secondary">—</span>
                                @endif
                            </td>
                            <td class="text-end pe-3 fw-bold">{{ currency($item->total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light">
                    @php $items = $purchaseReturn->items; @endphp
                    <tr class="fw-bold">
                        <td colspan="2" class="ps-3 text-end text-body-secondary text-uppercase small">{{ __('Totals') }}</td>
                        <td class="text-end">{{ currency($items->sum('quantity'), 3) }}</td>
                        <td></td>
                        <td></td>
                        <td class="text-end">{{ currency($items->sum('discount')) }}</td>
                        <td class="text-end">{{ currency($items->sum('tax_amount')) }}</td>
                        <td class="text-end pe-3 text-primary-emphasis">{{ currency($items->sum('total')) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- ====================== SUMMARY + PAYMENT ====================== --}}
    <div class="row g-3 mb-3">
        <div class="col-lg-7">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-body-tertiary border-0">
                    <h6 class="mb-0 fw-bold">
                        <i class="fa fa-calculator text-primary me-2"></i>{{ __('Order Summary') }}
                    </h6>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-body-secondary">{{ __('Gross Total') }}</span>
                        <span class="fw-semibold">{{ currency($purchaseReturn->gross_amount) }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-body-secondary">{{ __('Item Discount') }}</span>
                        <span class="fw-semibold text-danger">−{{ currency($purchaseReturn->item_discount) }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-body-secondary">{{ __('Tax Amount') }}</span>
                        <span class="fw-semibold">{{ currency($purchaseReturn->tax_amount) }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-body-secondary">{{ __('Subtotal') }}</span>
                        <span class="fw-semibold">{{ currency($purchaseReturn->total) }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-body-secondary">{{ __('Other Discount') }}</span>
                        <span class="fw-semibold text-danger">−{{ currency($purchaseReturn->other_discount) }}</span>
                    </li>
                    @if ((float) $purchaseReturn->freight > 0)
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-body-secondary">{{ __('Freight') }}</span>
                            <span class="fw-semibold">{{ currency($purchaseReturn->freight) }}</span>
                        </li>
                    @endif
                    <li class="list-group-item d-flex justify-content-between bg-primary-subtle">
                        <span class="fw-bold text-primary-emphasis">{{ __('Grand Total') }}</span>
                        <span class="h5 mb-0 fw-bold text-primary-emphasis">{{ currency($grandTotal) }}</span>
                    </li>
                </ul>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-body-tertiary border-0">
                    <h6 class="mb-0 fw-bold">
                        <i class="fa fa-credit-card text-success me-2"></i>{{ __('Payment Progress') }}
                    </h6>
                </div>
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-baseline mb-2">
                        <span class="text-body-secondary small">
                            {{ currency($paid) }} {{ __('of') }} {{ currency($grandTotal) }}
                        </span>
                        <span class="fw-bold text-{{ $balanceTone }}-emphasis">{{ $paidPct }}%</span>
                    </div>
                    <div class="progress mb-3" role="progressbar" aria-valuenow="{{ $paidPct }}" aria-valuemin="0" aria-valuemax="100" style="height: .6rem;">
                        <div class="progress-bar bg-{{ $balanceTone }}" style="width: {{ $paidPct }}%"></div>
                    </div>

                    <ul class="list-group list-group-flush flex-grow-1">
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-body-secondary">{{ __('Paid') }}</span>
                            <span class="fw-semibold text-success">{{ currency($paid) }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-body-secondary">{{ __('Balance') }}</span>
                            <span class="fw-semibold text-{{ $balanceTone }}">{{ currency($balance) }}</span>
                        </li>
                    </ul>

                    <div class="alert alert-{{ $balance <= 0 ? 'success' : 'warning' }} mb-0 text-center fw-semibold py-2">
                        @if ($balance <= 0)
                            <i class="fa fa-check-circle me-1"></i>{{ __('Fully Settled') }}
                        @else
                            <i class="fa fa-clock-o me-1"></i>{{ __('Payment Pending') }}
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ====================== REASON ====================== --}}
    @if ($purchaseReturn->reason)
        <div class="alert alert-warning d-flex mb-3">
            <i class="fa fa-file-text-o me-3 mt-1"></i>
            <div>
                <div class="fw-bold mb-1">{{ __('Return Reason') }}</div>
                <div>{{ $purchaseReturn->reason }}</div>
            </div>
        </div>
    @endif

    {{-- ====================== PAYMENTS ====================== --}}
    @if ($purchaseReturn->payments->count() > 0)
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-body-tertiary border-0 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold">
                    <i class="fa fa-money text-success me-2"></i>{{ __('Payments') }}
                </h6>
                <span class="badge rounded-pill bg-success-subtle text-success-emphasis">{{ $purchaseReturn->payments->count() }}</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">#</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Method') }}</th>
                            <th class="text-end pe-3">{{ __('Amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($purchaseReturn->payments as $payment)
                            <tr>
                                <td class="ps-3 text-body-secondary">{{ $loop->iteration }}</td>
                                <td>{{ systemDate($payment->date) }}</td>
                                <td>
                                    <span class="badge rounded-pill bg-info-subtle text-info-emphasis">
                                        <i class="fa fa-money me-1"></i>{{ $payment->paymentMethod?->name }}
                                    </span>
                                </td>
                                <td class="text-end pe-3 fw-bold text-success">{{ currency($payment->amount) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr class="fw-bold">
                            <td colspan="3" class="ps-3 text-end text-body-secondary text-uppercase small">{{ __('Total Paid') }}</td>
                            <td class="text-end pe-3 text-success">{{ currency($purchaseReturn->paid) }}</td>
                        </tr>
                        <tr class="fw-bold">
                            <td colspan="3" class="ps-3 text-end text-body-secondary text-uppercase small">{{ __('Balance') }}</td>
                            <td class="text-end pe-3 text-{{ $balanceTone }}">{{ currency($balance) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif

    {{-- ====================== JOURNAL ENTRIES ====================== --}}
    @if (count($purchaseReturn->journals))
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-body-tertiary border-0">
                <h6 class="mb-0 fw-bold">
                    <i class="fa fa-book text-info me-2"></i>{{ __('Journal Entries') }}
                </h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3 text-end">#</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Account') }}</th>
                            <th>{{ __('Description') }}</th>
                            <th class="text-end">{{ __('Debit') }}</th>
                            <th class="text-end pe-3">{{ __('Credit') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($purchaseReturn->journals as $journal)
                            @foreach ($journal->entries as $entry)
                                <tr>
                                    <td class="ps-3 text-end text-body-secondary">{{ $entry->id }}</td>
                                    <td class="text-nowrap">{{ systemDate($journal->date) }}</td>
                                    <td>
                                        <a href="{{ route('account::view', $entry->account_id) }}" class="text-decoration-none">
                                            {{ $entry->account?->name }}
                                        </a>
                                    </td>
                                    <td class="text-body-secondary small">{{ $entry->remarks }}</td>
                                    <td class="text-end">
                                        @if ($entry->debit > 0)
                                            <span class="fw-semibold text-success">{{ currency($entry->debit) }}</span>
                                        @else
                                            <span class="text-body-secondary">—</span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-3">
                                        @if ($entry->credit > 0)
                                            <span class="fw-semibold text-danger">{{ currency($entry->credit) }}</span>
                                        @else
                                            <span class="text-body-secondary">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ====================== INVENTORY LOG ====================== --}}
    @if (count($inventory_logs))
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-body-tertiary border-0 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold">
                    <i class="fa fa-cubes text-warning me-2"></i>{{ __('Inventory Log') }}
                </h6>
                <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis">{{ count($inventory_logs) }}</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">#</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Product') }}</th>
                            <th>{{ __('Barcode') }}</th>
                            <th>{{ __('Batch') }}</th>
                            <th class="text-end">{{ __('In') }}</th>
                            <th class="text-end">{{ __('Out') }}</th>
                            <th class="text-end">{{ __('Balance') }}</th>
                            <th class="text-end">{{ __('Cost') }}</th>
                            <th>{{ __('Remarks') }}</th>
                            <th class="pe-3">{{ __('User') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($inventory_logs as $log)
                            <tr>
                                <td class="ps-3 text-body-secondary">{{ $log->id }}</td>
                                <td class="text-nowrap">{{ systemDateTime($log->created_at) }}</td>
                                <td>
                                    <a href="{{ route('inventory::product::view', $log->product_id) }}" class="text-decoration-none">
                                        {{ $log->product?->name }}
                                    </a>
                                </td>
                                <td>
                                    @if ($log->barcode)
                                        <span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis">{{ $log->barcode }}</span>
                                    @else
                                        <span class="text-body-secondary">—</span>
                                    @endif
                                </td>
                                <td>{{ $log->batch ?: '—' }}</td>
                                <td class="text-end">
                                    @if ($log->quantity_in > 0)
                                        <span class="badge rounded-pill bg-success-subtle text-success-emphasis">↑ {{ $log->quantity_in }}</span>
                                    @else
                                        <span class="text-body-secondary">—</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if ($log->quantity_out > 0)
                                        <span class="badge rounded-pill bg-danger-subtle text-danger-emphasis">↓ {{ $log->quantity_out }}</span>
                                    @else
                                        <span class="text-body-secondary">—</span>
                                    @endif
                                </td>
                                <td class="text-end fw-bold">{{ $log->balance }}</td>
                                <td class="text-end">{{ currency($log->cost) }}</td>
                                <td class="text-body-secondary small">{{ $log->remarks }}</td>
                                <td class="pe-3 small">{{ $log->user_name }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr class="fw-bold">
                            <td colspan="5" class="ps-3 text-end text-body-secondary text-uppercase small">{{ __('Totals') }}</td>
                            <td class="text-end text-success">↑ {{ $inventory_logs->sum('quantity_in') }}</td>
                            <td class="text-end text-danger">↓ {{ $inventory_logs->sum('quantity_out') }}</td>
                            <td colspan="4"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif
</div>

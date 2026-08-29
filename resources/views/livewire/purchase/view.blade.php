@php
    $itemsCollection = $purchase->items;
    $returnsByItem = collect($purchase_return_items)->groupBy('purchase_item_id');
    $totalReturnQty = collect($purchase_return_items)->sum('quantity');
    $totalReturnAmt = collect($purchase_return_items)->sum('total');
    $hasReturns = count($purchase_return_items) > 0;

    // Column count for the group/spanning rows: SL, Product, Unit, Unit cost, Qty,
    // Discount, Tax, Total.
    $colCount = 8;

    // A purchase has no stylist to group by; the posting account is the equivalent
    // axis — it is what splits one bill across ledgers. Bills that never set one get
    // flat rows instead of a header row that says nothing.
    $groupByAccount = $purchase->items->pluck('account_id')->filter()->isNotEmpty();

    $statusTone = match ($purchase->status) {
        'completed' => 'b-ok',
        'cancelled' => 'b-bad',
        default => 'b-wn',
    };

    // Cost changes the products on this bill picked up — the vendor's price landing on
    // the product card is the one number a buyer always wants to trace back.
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

<div class="dvx">
    {{-- ── LEAD: identity left, money right ─────────────────────────────────── --}}
    <div class="s-lead">
        <div class="l-ic"><i class="fa fa-file-text-o"></i></div>
        <div class="l-main">
            <div class="s-eyebrow">Purchase Bill @if ($purchase->branch?->name) &middot; {{ $purchase->branch->name }} @endif</div>
            <div class="s-no">{{ $purchase->invoice_no }}</div>
            <div class="s-meta">
                <span><i class="fa fa-calendar"></i><b>{{ systemDate($purchase->date) }}</b></span>
                <span><i class="fa fa-building"></i><b>{{ $purchase->account?->name }}</b></span>
                @if ($purchase->delivery_date)
                    <span><i class="fa fa-truck"></i>Delivered {{ systemDate($purchase->delivery_date) }}</span>
                @endif
                @if ($purchase->localPurchaseOrder)
                    <span><i class="fa fa-file-o"></i>LPO <a href="{{ route('lpo::view', $purchase->localPurchaseOrder->id) }}">#{{ $purchase->localPurchaseOrder->id }}</a></span>
                @endif
            </div>
        </div>
        <div class="l-right">
            <div class="lb">Total payable</div>
            <div class="big mono">{{ currency($purchase->grand_total) }}</div>
            <div>
                <span class="s-badge {{ $statusTone }}"><i class="fa fa-circle" style="font-size:6px"></i>{{ ucfirst($purchase->status) }}</span>
                @if ($hasReturns)
                    <span class="s-badge b-wn"><i class="fa fa-reply"></i>Part returned</span>
                @endif
            </div>
        </div>
    </div>

    {{-- ── KPI TILES ────────────────────────────────────────────────────────── --}}
    <div class="s-kpis">
        <div class="s-kpi">
            <div class="k-ic" style="background:var(--tint);color:var(--acc)"><i class="fa fa-shopping-cart"></i></div>
            <div>
                <div class="k-k">Items</div>
                <div class="k-v mono">{{ $itemsCollection->count() }} <small>/ {{ currency($itemsCollection->sum('quantity'), 3) }} qty</small></div>
            </div>
        </div>
        <div class="s-kpi">
            <div class="k-ic" style="background:rgba(var(--ok-rgb),.12);color:var(--ok)"><i class="fa fa-check-circle-o"></i></div>
            <div>
                <div class="k-k">Paid</div>
                <div class="k-v mono" style="color:var(--ok)">{{ currency($purchase->paid) }}</div>
            </div>
        </div>
        <div class="s-kpi">
            @if ($purchase->balance != 0)
                <div class="k-ic" style="background:rgba(var(--bad-rgb),.10);color:var(--bad)"><i class="fa fa-exclamation-circle"></i></div>
                <div>
                    <div class="k-k">Balance due</div>
                    <div class="k-v mono" style="color:var(--bad)">{{ currency($purchase->balance) }}</div>
                </div>
            @else
                <div class="k-ic" style="background:rgba(var(--ok-rgb),.12);color:var(--ok)"><i class="fa fa-check"></i></div>
                <div>
                    <div class="k-k">Balance due</div>
                    <div class="k-v mono" style="color:var(--ok)">Settled</div>
                </div>
            @endif
        </div>
        @if ($hasReturns)
            <div class="s-kpi">
                <div class="k-ic" style="background:rgba(var(--warn-rgb),.16);color:var(--warn)"><i class="fa fa-reply"></i></div>
                <div>
                    <div class="k-k">Returned</div>
                    <div class="k-v mono" style="color:var(--warn)">{{ currency($totalReturnAmt) }}</div>
                </div>
            </div>
        @else
            <div class="s-kpi">
                <div class="k-ic" style="background:var(--sf-3);color:var(--mut)"><i class="fa fa-user"></i></div>
                <div>
                    <div class="k-k">Created by</div>
                    <div class="k-v" style="font-size:13px">{{ $purchase->createdUser?->name ?: '—' }}</div>
                </div>
            </div>
        @endif
    </div>

    {{-- ── DETAILS: bill | vendor ───────────────────────────────────────────── --}}
    <div class="s-card">
        <div class="s-two">
            <div>
                <div class="s-fl"><i class="fa fa-file-o"></i> Bill details</div>
                <div class="s-dr"><span class="l"><i class="fa fa-calendar"></i>Date</span><span class="v">{{ systemDate($purchase->date) }}</span></div>
                <div class="s-dr"><span class="l"><i class="fa fa-truck"></i>Delivery date</span><span class="v">{{ $purchase->delivery_date ? systemDate($purchase->delivery_date) : '—' }}</span></div>
                <div class="s-dr"><span class="l"><i class="fa fa-file-text-o"></i>Invoice no</span><span class="v">{{ $purchase->invoice_no ?: '—' }}</span></div>
                <div class="s-dr">
                    <span class="l"><i class="fa fa-clipboard"></i>Local purchase order</span>
                    <span class="v">
                        @if ($purchase->localPurchaseOrder)
                            <a href="{{ route('lpo::view', $purchase->localPurchaseOrder->id) }}">#{{ $purchase->localPurchaseOrder->id }}</a>
                        @else
                            —
                        @endif
                    </span>
                </div>
                <div class="s-dr"><span class="l"><i class="fa fa-building-o"></i>Branch</span><span class="v">{{ $purchase->branch?->name ?: '—' }}</span></div>
                <div class="s-dr"><span class="l"><i class="fa fa-user"></i>Created by</span><span class="v">{{ $purchase->createdUser?->name ?: '—' }}</span></div>
                @if ($purchase->status === 'cancelled' && $purchase->cancelledUser)
                    <div class="s-dr"><span class="l"><i class="fa fa-times-circle"></i>Cancelled by</span><span class="v" style="color:var(--bad)">{{ $purchase->cancelledUser?->name }}</span></div>
                @endif
            </div>
            <div>
                <div class="s-fl">
                    <i class="fa fa-building"></i> Vendor
                    <a href="{{ route('purchase-vendor::view', $purchase->account_id) }}" class="s-btn flat" style="margin-inline-start:auto">
                        <i class="fa fa-external-link"></i>Profile
                    </a>
                </div>
                <div class="s-dr">
                    <span class="l"><i class="fa fa-building-o"></i>Name</span>
                    <span class="v"><a href="{{ route('purchase-vendor::view', $purchase->account_id) }}">{{ $purchase->account?->name }}</a></span>
                </div>
                <div class="s-dr"><span class="l"><i class="fa fa-hashtag"></i>Account</span><span class="v mono">#{{ $purchase->account_id }}</span></div>
                <div class="s-dr"><span class="l"><i class="fa fa-phone"></i>Mobile</span><span class="v">{{ $purchase->account?->mobile ?: '—' }}</span></div>
                <div class="s-dr"><span class="l"><i class="fa fa-envelope-o"></i>Email</span><span class="v">{{ $purchase->account?->email ?: '—' }}</span></div>
                @if ($purchase->updatedUser)
                    <div class="s-dr"><span class="l"><i class="fa fa-pencil"></i>Last updated by</span><span class="v">{{ $purchase->updatedUser?->name }}</span></div>
                @endif
            </div>
        </div>
    </div>

    {{-- ── ITEMS + payments/summary footer ──────────────────────────────────── --}}
    <div class="s-card">
        <div class="s-ch">
            <i class="fa fa-shopping-cart hi"></i>
            <h4>Items</h4>
            @if ($hasReturns)
                <span class="s-chip c-w" title="Items already returned to the vendor">
                    <i class="fa fa-reply"></i> Returned {{ currency($totalReturnQty, 3) }} / {{ currency($totalReturnAmt) }}
                </span>
            @endif
            <span class="cs">{{ $itemsCollection->count() }} line{{ $itemsCollection->count() > 1 ? 's' : '' }}</span>
        </div>

        <div class="s-tw">
            <table class="s-tbl">
                <thead>
                    <tr>
                        <th style="width:38px">#</th>
                        <th>Product</th>
                        <th>Unit</th>
                        <th class="num">Unit cost</th>
                        <th class="num">Qty</th>
                        <th class="num">Discount</th>
                        <th class="num">Tax</th>
                        <th class="num">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($groupByAccount ? $itemsCollection->groupBy('account_id') : collect([$itemsCollection]) as $groupedItems)
                        @if ($groupByAccount)
                            @php $groupName = $groupedItems->first()->account?->name ?: 'Unassigned account'; @endphp
                            <tr class="s-grp">
                                <td colspan="{{ $colCount }}">
                                    <div class="s-gn">
                                        <span class="av">{{ strtoupper(mb_substr($groupName, 0, 1)) }}</span>
                                        {{ $groupName }}
                                        <em>{{ $groupedItems->count() }} item{{ $groupedItems->count() > 1 ? 's' : '' }} &middot; {{ currency($groupedItems->sum('total')) }}</em>
                                    </div>
                                </td>
                            </tr>
                        @endif
                        @foreach ($groupedItems as $item)
                            @php
                                $itemReturns = $returnsByItem->get($item->id, collect());
                                $returnedQty = $itemReturns->sum('quantity');
                            @endphp
                            <tr>
                                <td class="mono">{{ $loop->iteration }}</td>
                                <td>
                                    <a href="{{ route('inventory::product::view', $item->product_id) }}" class="s-pn">{{ $item->product?->name ?: $item->name }}</a>
                                    @if ($returnedQty > 0)
                                        <span class="s-chip c-w" title="Returned quantity">Returned {{ currency($returnedQty, 3) }}</span>
                                    @endif
                                    @if ($item->product?->barcode)
                                        <div class="s-ps"><i class="fa fa-barcode"></i> {{ $item->product->barcode }}</div>
                                    @endif
                                </td>
                                <td>{{ $item->unit?->name ?: '—' }}</td>
                                <td class="num mono">{{ currency($item->unit_price) }}</td>
                                <td class="num mono">{{ currency($item->quantity, 3) }}</td>
                                <td class="num mono" @if ($item->discount != 0) style="color:var(--bad)" @endif>
                                    {{ $item->discount != 0 ? currency($item->discount) : '—' }}
                                </td>
                                <td class="num mono">
                                    @if ($item->tax_amount != 0)
                                        {{ currency($item->tax_amount) }} <span style="color:var(--mut)">({{ round($item->tax, 2) }}%)</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="num mono" style="font-weight:800">{{ currency($item->total) }}</td>
                            </tr>
                            @foreach ($itemReturns as $ri)
                                <tr class="s-ret">
                                    <td class="num"><i class="fa fa-reply"></i></td>
                                    <td colspan="2">
                                        <a href="{{ route('purchase_return::view', $ri->purchase_return_id) }}">Return #{{ $ri->purchaseReturn?->invoice_no ?: $ri->purchase_return_id }}</a>
                                        <span>&middot; {{ $ri->product?->name }}</span>
                                    </td>
                                    <td class="num mono">{{ currency($ri->unit_price) }}</td>
                                    <td class="num mono">{{ currency($ri->quantity, 3) }}</td>
                                    <td class="num mono">{{ $ri->discount != 0 ? currency($ri->discount) : '—' }}</td>
                                    <td class="num mono">{{ $ri->tax_amount != 0 ? currency($ri->tax_amount) : '—' }}</td>
                                    <td class="num mono" style="font-weight:750">-{{ currency($ri->total) }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="num">Total</td>
                        <td class="num mono">{{ currency($itemsCollection->sum('quantity'), 3) }}</td>
                        <td class="num mono">{{ currency($itemsCollection->sum('discount')) }}</td>
                        <td class="num mono">{{ currency($itemsCollection->sum('tax_amount')) }}</td>
                        <td class="num mono">{{ currency($itemsCollection->sum('total')) }}</td>
                    </tr>
                    @if ($hasReturns)
                        <tr class="s-rr">
                            <td colspan="4" class="num"><i class="fa fa-reply"></i> Returned</td>
                            <td class="num mono">-{{ currency(collect($purchase_return_items)->sum('quantity'), 3) }}</td>
                            <td class="num mono">{{ collect($purchase_return_items)->sum('discount') != 0 ? '-' . currency(collect($purchase_return_items)->sum('discount')) : '—' }}</td>
                            <td class="num mono">{{ collect($purchase_return_items)->sum('tax_amount') != 0 ? '-' . currency(collect($purchase_return_items)->sum('tax_amount')) : '—' }}</td>
                            <td class="num mono">-{{ currency($totalReturnAmt) }}</td>
                        </tr>
                    @endif
                </tfoot>
            </table>
        </div>

        <div class="s-foot">
            <div>
                <div class="s-fl" style="padding-top:0"><i class="fa fa-credit-card"></i> Payments &middot; {{ $purchase->payments->count() }} made</div>
                @forelse ($purchase->payments as $key => $payment)
                    <div class="s-pay">
                        @switch(strtolower((string) $payment->paymentMethod?->name))
                            @case('cash')
                                <div class="s-pd" style="background:rgba(var(--ok-rgb),.13);color:var(--ok)"><i class="fa fa-money"></i></div>
                            @break

                            @case('card')
                                <div class="s-pd" style="background:rgba(var(--info-rgb),.13);color:var(--info)"><i class="fa fa-credit-card"></i></div>
                            @break

                            @default
                                <div class="s-pd" style="background:var(--tint);color:var(--acc)"><i class="fa fa-university"></i></div>
                        @endswitch
                        <div style="flex:1">
                            <div style="font-weight:700">{{ $payment->paymentMethod?->name ?: 'Payment' }}</div>
                            <div class="s-ps">
                                Txn #{{ str_pad((string) ($key + 1), 3, '0', STR_PAD_LEFT) }} &middot; {{ systemDate($payment->date) }}
                                @if ($payment->name)
                                    &middot; Ref {{ $payment->name }}
                                @endif
                            </div>
                        </div>
                        <div class="mono" style="font-weight:800;color:var(--ok)">{{ currency($payment->amount) }}</div>
                    </div>
                @empty
                    <div class="s-ps" style="padding:6px 0">No payments recorded against this bill.</div>
                @endforelse

                <div class="s-due {{ $purchase->balance == 0 ? 'settled' : '' }}">
                    <span class="l">{{ $purchase->balance == 0 ? 'Fully settled' : 'Balance due' }}</span>
                    <span class="v mono">{{ currency($purchase->balance) }}</span>
                </div>
            </div>
            <div>
                <div class="s-fl" style="padding-top:0"><i class="fa fa-calculator"></i> Financial summary</div>
                <div class="s-sr"><span class="l">Gross amount</span><span class="v mono">{{ currency($purchase->gross_amount) }}</span></div>
                @if ($purchase->item_discount != 0)
                    <div class="s-sr neg"><span class="l">Item discount</span><span class="v mono">-{{ currency($purchase->item_discount) }}</span></div>
                @endif
                @if ($purchase->tax_amount != 0)
                    <div class="s-sr"><span class="l">Tax</span><span class="v mono">{{ currency($purchase->tax_amount) }}</span></div>
                @endif
                <div class="s-sr"><span class="l">Purchase total</span><span class="v mono">{{ currency($purchase->total) }}</span></div>
                @if ($purchase->other_discount != 0)
                    <div class="s-sr neg"><span class="l">Other discount</span><span class="v mono">-{{ currency($purchase->other_discount) }}</span></div>
                @endif
                @if ($purchase->freight != 0)
                    <div class="s-sr"><span class="l">Freight</span><span class="v mono">{{ currency($purchase->freight) }}</span></div>
                @endif
                @if ($hasReturns)
                    <div class="s-sr wv"><span class="l"><i class="fa fa-reply"></i> Returned</span><span class="v mono">-{{ currency($totalReturnAmt) }}</span></div>
                @endif
                <div class="s-sr tot"><span class="l">Total payable</span><span class="v mono">{{ currency($purchase->grand_total) }}</span></div>
                <div class="s-sr"><span class="l">Amount paid</span><span class="v mono" style="color:var(--ok)">{{ currency($purchase->paid) }}</span></div>
            </div>
        </div>
    </div>

    {{-- ── NOTES ────────────────────────────────────────────────────────────── --}}
    @if ($purchase->address)
        <div class="s-card">
            <div class="s-ch"><i class="fa fa-pencil-square-o hi"></i>
                <h4>Notes &amp; information</h4>
            </div>
            <div class="s-note">{{ $purchase->address }}</div>
        </div>
    @endif

    {{-- ── ACTIONS ──────────────────────────────────────────────────────────── --}}
    @if ($purchase->status !== 'cancelled')
        <div class="s-toolbar d-print-none">
            <a target="_blank" href="{{ route('purchase::print', $purchase->id) }}" class="s-btn" title="Print Purchase Note">
                <i class="fa fa-print"></i>Print
            </a>
            @can('purchase.barcode print')
                <a target="_blank" href="{{ route('purchase::barcode-print', $purchase->id) }}" class="s-btn" title="Print Barcode">
                    <i class="fa fa-barcode"></i>Barcode
                </a>
            @endcan
            @can('purchase.cancel')
                <button type="button" wire:click='save("cancelled")' wire:confirm="Are you sure to cancel this?" class="s-btn bad">
                    <i class="fa fa-times"></i>Cancel
                </button>
            @endcan
            @can('purchase.edit completed')
                <a href="{{ route('purchase::edit', $purchase->id) }}" class="s-btn pri">
                    <i class="fa fa-pencil"></i>Edit purchase
                </a>
            @endcan
        </div>
    @endif

    {{-- ── TABS: journal, audit & inventory (return lines are inline above) ──── --}}
    <div class="s-card">
        <ul class="s-tabs nav" role="tablist">
            @if ($purchase->journals->count())
                <li class="nav-item" role="presentation">
                    <button class="s-tab nav-link active" data-bs-toggle="tab" data-bs-target="#tab-journal-entries" type="button" role="tab">
                        <i class="fa fa-book"></i>Journal entries
                    </button>
                </li>
            @endif
            <li class="nav-item" role="presentation">
                <button class="s-tab nav-link {{ $purchase->journals->isEmpty() ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-audit-report" type="button" role="tab">
                    <i class="fa fa-history"></i>Audit report
                </button>
            </li>
            @if (count($inventory_logs))
                <li class="nav-item" role="presentation">
                    <button class="s-tab nav-link" data-bs-toggle="tab" data-bs-target="#tab-inventory-log" type="button" role="tab">
                        <i class="fa fa-cubes"></i>Inventory log <span class="s-cnt">{{ count($inventory_logs) }}</span>
                    </button>
                </li>
            @endif
        </ul>

        <div class="tab-content">
            @if ($purchase->journals->count())
                <div id="tab-journal-entries" class="tab-pane fade active show" role="tabpanel">
                    <div class="s-tw">
                        <table class="s-tbl">
                            <thead>
                                <tr>
                                    <th style="width:70px">#</th>
                                    <th>Date</th>
                                    <th>Account</th>
                                    <th>Description</th>
                                    <th class="num">Debit</th>
                                    <th class="num">Credit</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($purchase->journals as $journal)
                                    @foreach ($journal->entries as $entry)
                                        <tr>
                                            <td class="mono">{{ $entry->id }}</td>
                                            <td>{{ systemDate($entry->date) }}</td>
                                            <td><a href="{{ route('account::view', $entry->account_id) }}" class="s-pn acc">{{ $entry->account?->name }}</a></td>
                                            <td style="color:var(--mut)">{{ $entry->remarks }}</td>
                                            <td class="num mono">{{ $entry->debit != 0 ? currency($entry->debit) : '—' }}</td>
                                            <td class="num mono">{{ $entry->credit != 0 ? currency($entry->credit) : '—' }}</td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <div id="tab-audit-report" class="tab-pane fade {{ $purchase->journals->isEmpty() ? 'active show' : '' }}" role="tabpanel">
                <ul class="s-tabs nav" role="tablist" style="border-top:1px solid var(--ln-s)">
                    <li class="nav-item" role="presentation">
                        <button class="s-tab nav-link active" data-bs-toggle="tab" data-bs-target="#purchase-audit" type="button"><i class="fa fa-file-o"></i>Purchase</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="s-tab nav-link" data-bs-toggle="tab" data-bs-target="#purchase-items-audit" type="button"><i class="fa fa-shopping-cart"></i>Purchase items</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="s-tab nav-link" data-bs-toggle="tab" data-bs-target="#purchase-payments-audit" type="button"><i class="fa fa-credit-card"></i>Purchase payments</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="s-tab nav-link" data-bs-toggle="tab" data-bs-target="#product-cost-audit" type="button"><i class="fa fa-cube"></i>Product cost</button>
                    </li>
                </ul>
                <div class="tab-content">
                    <div id="purchase-audit" class="tab-pane fade show active" role="tabpanel">
                        <x-audit.table :audits="$purchase->audits" emptyMessage="No purchase audit entries found." />
                    </div>
                    <div id="purchase-items-audit" class="tab-pane fade" role="tabpanel">
                        <x-audit.table :audits="$purchase->items->flatMap->audits" emptyMessage="No purchase item audit entries found." />
                    </div>
                    <div id="purchase-payments-audit" class="tab-pane fade" role="tabpanel">
                        <x-audit.table :audits="$purchase->payments->flatMap->audits" emptyMessage="No purchase payment audit entries found." />
                    </div>
                    <div id="product-cost-audit" class="tab-pane fade" role="tabpanel">
                        <div class="s-tw">
                            <table class="s-tbl">
                                <thead>
                                    <tr>
                                        <th>Date time</th>
                                        <th>Product</th>
                                        <th>User</th>
                                        <th>Event</th>
                                        <th class="num">Old cost</th>
                                        <th class="num">New cost</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($productCostAudits as $row)
                                        <tr>
                                            <td style="white-space:nowrap">{{ systemDateTime($row['audit']->created_at) }}</td>
                                            <td><a href="{{ route('inventory::product::view', $row['product']->id) }}" class="s-pn acc">{{ $row['product']->name }}</a></td>
                                            <td>{{ $row['audit']->user?->name ?: '—' }}</td>
                                            <td><span class="s-chip c-m">{{ $row['audit']->event }}</span></td>
                                            <td class="num mono" style="color:var(--bad)">{{ array_key_exists('cost', $row['audit']->old_values ?? []) ? currency($row['audit']->old_values['cost']) : '—' }}</td>
                                            <td class="num mono" style="color:var(--ok)">{{ array_key_exists('cost', $row['audit']->new_values ?? []) ? currency($row['audit']->new_values['cost']) : '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" style="text-align:center;color:var(--mut);padding:14px">No product cost changes found for these purchase items.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            @if (count($inventory_logs))
                <div id="tab-inventory-log" class="tab-pane fade" role="tabpanel">
                    <div class="s-tw">
                        <table class="s-tbl">
                            <thead>
                                <tr>
                                    <th style="width:60px">#</th>
                                    <th>Date</th>
                                    <th>Product</th>
                                    <th>Barcode</th>
                                    <th>Batch</th>
                                    <th class="num">In</th>
                                    <th class="num">Out</th>
                                    <th class="num">Balance</th>
                                    <th class="num">Cost</th>
                                    <th>Remarks</th>
                                    <th>User</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($inventory_logs as $log)
                                    <tr>
                                        <td class="mono">{{ $log->id }}</td>
                                        <td style="white-space:nowrap">{{ systemDateTime($log->created_at) }}</td>
                                        <td><a href="{{ route('inventory::product::view', $log->product_id) }}" class="s-pn acc">{{ $log->product?->name }}</a></td>
                                        <td class="mono">{{ $log->barcode }}</td>
                                        <td>{{ $log->batch }}</td>
                                        <td class="num mono" style="color:var(--ok)">{{ $log->quantity_in > 0 ? $log->quantity_in : '—' }}</td>
                                        <td class="num mono" style="color:var(--bad)">{{ $log->quantity_out > 0 ? $log->quantity_out : '—' }}</td>
                                        <td class="num mono" style="font-weight:750">{{ $log->balance }}</td>
                                        <td class="num mono">{{ currency($log->cost) }}</td>
                                        <td style="color:var(--mut)">{{ $log->remarks }}</td>
                                        <td>{{ $log->user_name }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5" class="num">Total</td>
                                    <td class="num mono" style="color:var(--ok)">{{ $inventory_logs->sum('quantity_in') }}</td>
                                    <td class="num mono" style="color:var(--bad)">{{ $inventory_logs->sum('quantity_out') }}</td>
                                    <td colspan="4"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@php
    $returnsByItem = collect($sale_return_items)->groupBy('sale_item_id');
    $itemsCollection = collect($items);
    $totalReturnQty = collect($sale_return_items)->sum('quantity');
    $totalReturnAmt = collect($sale_return_items)->sum('total');
    $hasReturns = count($sale_return_items) > 0;
    $canReturn = auth()->user()?->can('sales return.create') && $sale->status !== 'cancelled';
    $showEffective = $sales['other_discount'] > 0;

    // Column count for the group/spanning rows: checkbox? + SL, Product, Unit, Rate,
    // Qty, Discount, Tax, Total (8) + the effective-total column when a bill-level
    // discount spreads across the lines.
    $colCount = 8 + ($canReturn ? 1 : 0) + ($showEffective ? 1 : 0);

    $statusTone = match ($sale->status) {
        'completed' => 'b-ok',
        'cancelled' => 'b-bad',
        default => 'b-wn',
    };
@endphp

<div class="svx">
    {{-- ── LEAD: identity left, money right ─────────────────────────────────── --}}
    <div class="s-lead">
        <div class="l-ic"><i class="fa fa-file-text-o"></i></div>
        <div class="l-main">
            <div class="s-eyebrow">Sale Invoice @if ($sale->branch?->name) &middot; {{ $sale->branch->name }} @endif</div>
            <div class="s-no">{{ $sale->invoice_no }}</div>
            <div class="s-meta">
                <span><i class="fa fa-calendar"></i><b>{{ systemDate($sale->date) }}</b></span>
                <span><i class="fa fa-user"></i><b>{{ $sale->customer_name ?: $sale->account?->name }}</b></span>
                @if ($sale->source)
                    <x-sale.source-badge :source="$sale->source" />
                @endif
                <span><i class="fa fa-tag"></i>{{ ucfirst($sale->sale_type) }}</span>
                @if ($sale->saleDaySession)
                    <span><i class="fa fa-clock-o"></i>Session #{{ $sale->sale_day_session_id }}</span>
                @endif
            </div>
        </div>
        <div class="l-right">
            <div class="lb">Total payable</div>
            <div class="big mono">{{ currency($sale->grand_total) }}</div>
            <div>
                <span class="s-badge {{ $statusTone }}"><i class="fa fa-circle" style="font-size:6px"></i>{{ ucfirst($sale->status) }}</span>
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
                <div class="k-v mono">{{ count($items) }} <small>/ {{ currency($itemsCollection->sum('quantity'), 3) }} qty</small></div>
            </div>
        </div>
        <div class="s-kpi">
            <div class="k-ic" style="background:rgba(var(--ok-rgb),.12);color:var(--ok)"><i class="fa fa-check-circle-o"></i></div>
            <div>
                <div class="k-k">Paid</div>
                <div class="k-v mono" style="color:var(--ok)">{{ currency($sale->paid) }}</div>
            </div>
        </div>
        <div class="s-kpi">
            @if ($sale->balance != 0)
                <div class="k-ic" style="background:rgba(var(--bad-rgb),.10);color:var(--bad)"><i class="fa fa-exclamation-circle"></i></div>
                <div>
                    <div class="k-k">Balance due</div>
                    <div class="k-v mono" style="color:var(--bad)">{{ currency($sale->balance) }}</div>
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
                    <div class="k-v" style="font-size:13px">{{ $sale->createdUser?->name ?: '—' }}</div>
                </div>
            </div>
        @endif
    </div>

    {{-- ── DETAILS: invoice | customer ──────────────────────────────────────── --}}
    <div class="s-card">
        <div class="s-two">
            <div>
                <div class="s-fl"><i class="fa fa-file-o"></i> Invoice details</div>
                <div class="s-dr"><span class="l"><i class="fa fa-calendar"></i>Date</span><span class="v">{{ systemDate($sale->date) }}</span></div>
                <div class="s-dr"><span class="l"><i class="fa fa-calendar-o"></i>Due date</span><span class="v">{{ systemDate($sale->due_date) }}</span></div>
                <div class="s-dr">
                    {{-- A sale synced from a till that was offline keeps the reference printed on
                         the customer's copy here, so say so: that is the only number they can
                         quote, and it is not an invoice number. --}}
                    <span class="l">
                        <i class="fa {{ $sale->client_uuid ? 'fa-mobile' : 'fa-file-text-o' }}"></i>{{ $sale->client_uuid ? 'Offline receipt no' : 'Reference' }}
                    </span>
                    <span class="v">{{ $sale->reference_no ?: '—' }}</span>
                </div>
                <div class="s-dr"><span class="l"><i class="fa fa-tag"></i>Sale type</span><span class="v">{{ ucfirst($sale->sale_type) }}</span></div>
                <div class="s-dr"><span class="l"><i class="fa fa-random"></i>Source</span><span class="v"><x-sale.source-badge :source="$sale->source" /></span></div>
                <div class="s-dr"><span class="l"><i class="fa fa-building-o"></i>Branch</span><span class="v">{{ $sale->branch?->name ?: '—' }}</span></div>
                @if ($sale->status === 'cancelled' && $sale->cancelledUser)
                    <div class="s-dr"><span class="l"><i class="fa fa-times-circle"></i>Cancelled by</span><span class="v" style="color:var(--bad)">{{ $sale->cancelledUser?->name }}</span></div>
                @endif
            </div>
            <div>
                <div class="s-fl">
                    <i class="fa fa-user"></i> Customer
                    <a href="{{ route('account::customer::view', $sale->account_id) }}" class="s-btn flat" style="margin-inline-start:auto">
                        <i class="fa fa-external-link"></i>Profile
                    </a>
                </div>
                <div class="s-dr">
                    <span class="l"><i class="fa fa-user"></i>Name</span>
                    <span class="v"><a href="{{ route('account::customer::view', $sale->account_id) }}">{{ $sale->account?->name }}</a></span>
                </div>
                @if ($sale->customer_name)
                    <div class="s-dr"><span class="l"><i class="fa fa-font"></i>Display name</span><span class="v">{{ $sale->customer_name }}</span></div>
                @endif
                <div class="s-dr"><span class="l"><i class="fa fa-phone"></i>Mobile</span><span class="v">{{ $sale->customer_mobile ?: ($sale->account?->mobile ?: '—') }}</span></div>
                <div class="s-dr"><span class="l"><i class="fa fa-envelope-o"></i>Email</span><span class="v">{{ $sale->account?->email ?: '—' }}</span></div>
                <div class="s-dr"><span class="l"><i class="fa fa-user"></i>Created by</span><span class="v">{{ $sale->createdUser?->name ?: '—' }}</span></div>
                @if ($sale->rating)
                    <div class="s-dr">
                        <span class="l"><i class="fa fa-star"></i>Rating</span>
                        <span class="v">
                            @for ($i = 1; $i <= 5; $i++)
                                <i class="fa {{ $sale->rating >= $i ? 'fa-star' : 'fa-star-o' }}" style="color:{{ $sale->rating >= $i ? '#f0ad4e' : 'var(--mut)' }}"></i>
                            @endfor
                            {{ $sale->rating }}/5
                        </span>
                    </div>
                @endif
                @if ($sale->feedback)
                    <div class="s-dr">
                        <span class="l"><i class="fa fa-quote-left"></i>Feedback</span>
                        <span class="v" style="font-weight:550">
                            {{ $sale->feedback }}
                            @if ($sale->feedback_type)
                                <span class="s-chip c-i">{{ $sale->feedback_type }}</span>
                            @endif
                        </span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ── COMBO OFFER ITEMS ────────────────────────────────────────────────── --}}
    @if (count($sale->comboOffers) > 0)
        <div class="s-card">
            <div class="s-ch">
                <i class="fa fa-cubes hi"></i>
                <h4>Combo offer items</h4>
                <span class="cs">{{ count($sale->comboOffers) }} combo{{ count($sale->comboOffers) > 1 ? 's' : '' }}</span>
            </div>
            <div class="s-combos">
                @foreach ($sale->comboOffers as $combo)
                    <div class="s-combo">
                        <div class="ch">
                            <span class="nm"><i class="fa fa-gift"></i> {{ $combo->comboOffer->name }}</span>
                            <span class="mono" style="font-weight:800">{{ currency($combo->amount) }}</span>
                        </div>
                        @foreach ($combo->items as $subItem)
                            <div class="li">
                                <span>
                                    <span style="font-weight:650">{{ $subItem->product->name }}</span>
                                    <span class="sub">&middot; {{ $subItem->employee->name }}</span>
                                </span>
                                <span class="mono" style="font-weight:700">
                                    {{ currency($subItem->unit_price - $subItem->discount) }}
                                    @if ($subItem->discount > 0)
                                        <s class="sub">{{ currency($subItem->unit_price) }}</s>
                                    @endif
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ── ITEMS + payments/summary footer ──────────────────────────────────── --}}
    <div class="s-card">
        <div class="s-ch">
            <i class="fa fa-shopping-cart hi"></i>
            <h4>Items</h4>
            @if ($hasReturns)
                <span class="s-chip c-w" title="Items already returned">
                    <i class="fa fa-reply"></i> Returned {{ currency($totalReturnQty, 3) }} / {{ currency($totalReturnAmt) }}
                </span>
            @endif
            <span class="cs">{{ count($items) }} line{{ count($items) > 1 ? 's' : '' }}</span>
            @if ($canReturn)
                <div class="d-flex align-items-center gap-2 d-print-none">
                    <label class="d-flex align-items-center gap-1" style="font-size:11.5px;color:var(--mut);cursor:pointer;margin:0">
                        <input type="checkbox" class="form-check-input mt-0" id="select-all-return-items"
                            onclick="document.querySelectorAll('.sale-item-return-check:not(:disabled)').forEach(c => c.checked = this.checked); updateReturnSelectedCount();">
                        Select all
                    </label>
                    <button type="button" class="s-btn wn flat" id="returnSelectedBtn" onclick="returnSelectedSaleItems({{ $sale->id }})" disabled>
                        <i class="fa fa-reply"></i><span>Return selected (<span id="returnSelectedCount">0</span>)</span></button>
                </div>
            @endif
        </div>

        <div class="s-tw">
            <table class="s-tbl">
                <thead>
                    <tr>
                        @if ($canReturn)
                            <th style="width:32px"></th>
                        @endif
                        <th style="width:38px">#</th>
                        <th>Product / Service</th>
                        <th>Unit</th>
                        <th class="num">Rate</th>
                        <th class="num">Qty</th>
                        <th class="num">Discount</th>
                        <th class="num">Tax</th>
                        <th class="num">Total</th>
                        @if ($showEffective)
                            <th class="num">Effective total</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($itemsCollection->groupBy('employee_id') as $employee_id => $groupedItems)
                        @php $first = $groupedItems->first(); @endphp
                        <tr class="s-grp">
                            <td colspan="{{ $colCount }}">
                                <div class="s-gn">
                                    <span class="av">{{ strtoupper(mb_substr($first['employee_name'] ?: '?', 0, 1)) }}</span>
                                    {{ $first['employee_name'] }}
                                    <em>{{ $groupedItems->count() }} item{{ $groupedItems->count() > 1 ? 's' : '' }} &middot; {{ currency($groupedItems->sum('total')) }}</em>
                                </div>
                            </td>
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
                                    <td>
                                        <input type="checkbox" class="form-check-input mt-0 sale-item-return-check"
                                            value="{{ $item['id'] }}" {{ $disabled ? 'disabled' : '' }}
                                            onchange="updateReturnSelectedCount()"
                                            title="{{ $disabled ? 'Already fully returned' : 'Select to return' }}">
                                    </td>
                                @endif
                                <td class="mono">{{ $loop->iteration }}</td>
                                <td>
                                    <a href="{{ route('inventory::product::view', $item['product_id']) }}" class="s-pn">{{ $item['name'] }}</a>
                                    @if (!empty($item['sale_combo_offer_id']))
                                        <span class="s-chip c-a">Combo</span>
                                    @endif
                                    @if ($returnedQty > 0)
                                        <span class="s-chip c-w" title="Returned quantity">Returned {{ currency($returnedQty, 3) }}</span>
                                    @endif
                                    @if (!empty($item['assistant_name']))
                                        <div class="s-ps"><i class="fa fa-user-plus"></i> {{ $item['assistant_name'] }}</div>
                                    @endif
                                </td>
                                <td>{{ $item['unit'] }}</td>
                                <td class="num mono">{{ currency($item['unit_price']) }}</td>
                                <td class="num mono">{{ currency($item['quantity'], 3) }}</td>
                                <td class="num mono" @if ($item['discount'] != 0) style="color:var(--bad)" @endif>
                                    {{ $item['discount'] != 0 ? currency($item['discount']) : '—' }}
                                </td>
                                <td class="num mono">
                                    @if ($item['tax_amount'] != 0)
                                        {{ currency($item['tax_amount']) }} <span style="color:var(--mut)">({{ round($item['tax'], 2) }}%)</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="num mono" style="font-weight:800">{{ currency($item['total']) }}</td>
                                @if ($showEffective)
                                    <td class="num mono" style="font-weight:800">{{ currency($item['effective_total']) }}</td>
                                @endif
                            </tr>
                            @foreach ($itemReturns as $ri)
                                <tr class="s-ret">
                                    @if ($canReturn)
                                        <td></td>
                                    @endif
                                    <td class="num"><i class="fa fa-reply"></i></td>
                                    <td colspan="2">
                                        <a href="{{ route('sale_return::view', $ri->sale_return_id) }}">Return #{{ $ri->sale_return_id }}</a>
                                        <span>&middot; {{ $ri->product?->name }}</span>
                                    </td>
                                    <td class="num mono">{{ currency($ri->unit_price) }}</td>
                                    <td class="num mono">{{ currency($ri->quantity, 3) }}</td>
                                    <td class="num mono">{{ $ri->discount != 0 ? currency($ri->discount) : '—' }}</td>
                                    <td class="num mono">{{ $ri->tax_amount != 0 ? currency($ri->tax_amount) : '—' }}</td>
                                    <td class="num mono" style="font-weight:750">-{{ currency($ri->total) }}</td>
                                    @if ($showEffective)
                                        <td class="num mono" style="font-weight:750">-{{ currency($ri->effective_total) }}</td>
                                    @endif
                                </tr>
                            @endforeach
                        @endforeach
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        @if ($canReturn)
                            <td></td>
                        @endif
                        <td colspan="4" class="num">Total</td>
                        <td class="num mono">{{ currency($itemsCollection->sum('quantity'), 3) }}</td>
                        <td class="num mono">{{ currency($itemsCollection->sum('discount')) }}</td>
                        <td class="num mono">{{ currency($itemsCollection->sum('tax_amount')) }}</td>
                        <td class="num mono">{{ currency($itemsCollection->sum('total')) }}</td>
                        @if ($showEffective)
                            <td class="num mono">{{ currency($itemsCollection->sum('effective_total')) }}</td>
                        @endif
                    </tr>
                    @if ($hasReturns)
                        <tr class="s-rr">
                            @if ($canReturn)
                                <td></td>
                            @endif
                            <td colspan="4" class="num"><i class="fa fa-reply"></i> Returned</td>
                            <td class="num mono">-{{ currency($sale_return_items->sum('quantity'), 3) }}</td>
                            <td class="num mono">{{ $sale_return_items->sum('discount') != 0 ? '-' . currency($sale_return_items->sum('discount')) : '—' }}</td>
                            <td class="num mono">{{ $sale_return_items->sum('tax_amount') != 0 ? '-' . currency($sale_return_items->sum('tax_amount')) : '—' }}</td>
                            <td class="num mono">-{{ currency($sale_return_items->sum('total')) }}</td>
                            @if ($showEffective)
                                <td class="num mono">-{{ currency($sale_return_items->sum('effective_total')) }}</td>
                            @endif
                        </tr>
                    @endif
                </tfoot>
            </table>
        </div>

        <div class="s-foot">
            <div>
                <div class="s-fl" style="padding-top:0"><i class="fa fa-credit-card"></i> Payments &middot; {{ count($payments) }} received</div>
                @forelse ($payments as $key => $payment)
                    <div class="s-pay">
                        @switch(strtolower($payment['name']))
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
                            <div style="font-weight:700">{{ $payment['name'] }}</div>
                            <div class="s-ps">
                                Txn #{{ str_pad($key + 1, 3, '0', STR_PAD_LEFT) }} &middot; {{ systemDate($payment['date']) }}
                                @if (isset($payment['reference']))
                                    &middot; Ref {{ $payment['reference'] }}
                                @endif
                            </div>
                        </div>
                        <div class="mono" style="font-weight:800;color:var(--ok)">{{ currency($payment['amount']) }}</div>
                    </div>
                @empty
                    <div class="s-ps" style="padding:6px 0">No payments recorded against this invoice.</div>
                @endforelse

                <div class="s-due {{ $sale->balance == 0 ? 'settled' : '' }}">
                    <span class="l">{{ $sale->balance == 0 ? 'Fully settled' : 'Balance due' }}</span>
                    <span class="v mono">{{ currency($sale->balance) }}</span>
                </div>
            </div>
            <div>
                <div class="s-fl" style="padding-top:0"><i class="fa fa-calculator"></i> Financial summary</div>
                <div class="s-sr"><span class="l">Gross amount</span><span class="v mono">{{ currency($sale->gross_amount) }}</span></div>
                @if ($itemsCollection->sum('discount') != 0)
                    <div class="s-sr neg"><span class="l">Item discount</span><span class="v mono">-{{ currency($itemsCollection->sum('discount')) }}</span></div>
                @endif
                @if ($itemsCollection->sum('tax_amount') != 0)
                    <div class="s-sr"><span class="l">Tax</span><span class="v mono">{{ currency($itemsCollection->sum('tax_amount')) }}</span></div>
                @endif
                <div class="s-sr"><span class="l">Sale total</span><span class="v mono">{{ currency($sale->total) }}</span></div>
                @if ($sale->other_discount != 0)
                    <div class="s-sr neg"><span class="l">Other discount</span><span class="v mono">-{{ currency($sale->other_discount) }}</span></div>
                @endif
                @if ($sale->freight != 0)
                    <div class="s-sr"><span class="l">Freight</span><span class="v mono">{{ currency($sale->freight) }}</span></div>
                @endif
                @if ($sale->round_off != 0)
                    <div class="s-sr"><span class="l">Round off</span><span class="v mono">{{ currency($sale->round_off) }}</span></div>
                @endif
                @if ($hasReturns)
                    <div class="s-sr wv"><span class="l"><i class="fa fa-reply"></i> Returned</span><span class="v mono">-{{ currency($totalReturnAmt) }}</span></div>
                @endif
                <div class="s-sr tot"><span class="l">Total payable</span><span class="v mono">{{ currency($sale->grand_total) }}</span></div>
                <div class="s-sr"><span class="l">Amount paid</span><span class="v mono" style="color:var(--ok)">{{ currency($sale->paid) }}</span></div>
            </div>
        </div>
    </div>

    {{-- ── NOTES ────────────────────────────────────────────────────────────── --}}
    @if ($sale['address'])
        <div class="s-card">
            <div class="s-ch"><i class="fa fa-pencil-square-o hi"></i>
                <h4>Notes &amp; information</h4>
            </div>
            <div class="s-note">{{ $sale['address'] }}</div>
        </div>
    @endif

    {{-- ── ACTIONS ──────────────────────────────────────────────────────────── --}}
    @if ($sales['status'] != 'cancelled')
        <div class="s-toolbar d-print-none">
            <a target="_blank" href="{{ route('print::sale::invoice', $sales['id']) }}" class="s-btn" title="Print Invoice">
                <i class="fa fa-print"></i>Print
            </a>
            @can('sale.cancel')
                <button type="button" wire:click='sendToWhatsapp' class="s-btn">
                    <i class="fa fa-whatsapp"></i>WhatsApp
                </button>
            @endcan
            @can('sale.change day session')
                <button type="button" wire:click="openChangeSessionModal" class="s-btn">
                    <i class="fa fa-clock-o"></i>Change day session
                </button>
            @endcan
            @can('sales return.create')
                <a href="{{ route('sale_return::create', ['sale_id' => $sale->id]) }}" class="s-btn wn" title="Create a sale return for this invoice">
                    <i class="fa fa-reply"></i>Return all
                </a>
            @endcan
            @can('sale.cancel')
                <button type="button" wire:click='save("cancelled")' wire:confirm="Are you sure to cancel this?" class="s-btn bad">
                    <i class="fa fa-times"></i>Cancel
                </button>
            @endcan
            @can('sale.edit completed')
                <a href="{{ route('sale::edit', $sales['id']) }}" class="s-btn pri">
                    <i class="fa fa-pencil"></i>Edit sale
                </a>
            @endcan
        </div>
    @endif

    {{-- ── TABS: journal & audit (return items are inline above) ────────────── --}}
    <div class="s-card">
        <ul class="s-tabs nav" role="tablist">
            @can('sale.view journal entries')
                @if (count($sale->journals))
                    <li class="nav-item" role="presentation">
                        <button class="s-tab nav-link active" data-bs-toggle="tab" data-bs-target="#tab-journal-entries" type="button" role="tab">
                            <i class="fa fa-book"></i>Journal entries
                        </button>
                    </li>
                @endif
            @endcan
            @can('sale.audit view')
                <li class="nav-item" role="presentation">
                    <button class="s-tab nav-link" data-bs-toggle="tab" data-bs-target="#tab-audit-report" type="button" role="tab">
                        <i class="fa fa-history"></i>Audit report
                    </button>
                </li>
            @endcan
            @if (count($inventory_logs))
                <li class="nav-item" role="presentation">
                    <button class="s-tab nav-link" data-bs-toggle="tab" data-bs-target="#tab-inventory-log" type="button" role="tab">
                        <i class="fa fa-cubes"></i>Inventory log <span class="s-cnt">{{ count($inventory_logs) }}</span>
                    </button>
                </li>
            @endif
        </ul>

        <div class="tab-content">
            @can('sale.view journal entries')
                @if (count($sale->journals))
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
                                    @foreach ($sale->journals as $journal)
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
            @endcan

            <div id="tab-audit-report" class="tab-pane fade" role="tabpanel">
                <ul class="s-tabs nav" role="tablist" style="border-top:1px solid var(--ln-s)">
                    <li class="nav-item" role="presentation">
                        <button class="s-tab nav-link active" data-bs-toggle="tab" data-bs-target="#sale-audit" type="button"><i class="fa fa-file-o"></i>Sale</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="s-tab nav-link" data-bs-toggle="tab" data-bs-target="#sale-items-audit" type="button"><i class="fa fa-shopping-cart"></i>Sale items</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="s-tab nav-link" data-bs-toggle="tab" data-bs-target="#sale-payments-audit" type="button"><i class="fa fa-credit-card"></i>Sale payments</button>
                    </li>
                </ul>
                <div class="tab-content">
                    <div id="sale-audit" class="tab-pane fade show active" role="tabpanel">
                        <x-audit.table :audits="$sale->audits" emptyMessage="No sale audit entries found." />
                    </div>
                    <div id="sale-items-audit" class="tab-pane fade" role="tabpanel">
                        <x-audit.table :audits="collect($sale->items)->flatMap->audits" emptyMessage="No sale item audit entries found." />
                    </div>
                    <div id="sale-payments-audit" class="tab-pane fade" role="tabpanel">
                        <x-audit.table :audits="collect($sale->payments)->flatMap->audits" emptyMessage="No sale payment audit entries found." />
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

<div>
    @use('Illuminate\Support\Str')
    @php
        $canViewTailoring = auth()->user()->can('tailoring order.view');
        $methodLook = function ($name): array {
            $n = strtolower((string) $name);

            return match (true) {
                str_contains($n, 'cash') => ['fa-money', 'is-cash', 'i-green'],
                str_contains($n, 'card') || str_contains($n, 'visa') || str_contains($n, 'master') => ['fa-credit-card', 'is-card', 'i-accent'],
                str_contains($n, 'bank') || str_contains($n, 'transfer') => ['fa-university', 'is-other', 'i-amber'],
                default => ['fa-exchange', 'is-other', 'i-amber'],
            };
        };
    @endphp

    <x-sale.day-session-premium />

    <div class="dsv stack" x-data="{ tab: 'sales' }">
        {{-- ============ COLLECTIONS BY METHOD ============ --}}
        @if (count($paymentSummary) > 0)
            <div class="panel">
                <div class="panel__head">
                    <h3><i class="fa fa-credit-card"></i> Collections by method</h3>
                    <div class="panel__aside">
                        <span class="pill">{{ count($paymentSummary) }} {{ Str::plural('method', count($paymentSummary)) }}</span>
                        <span class="pill pill--green">Total {{ currency($paymentSummaryTotal) }}</span>
                    </div>
                </div>
                <div class="panel__body">
                    <div class="methods">
                        @foreach ($paymentSummary as $payment)
                            @php
                                [$icon, $kind, $tone] = $methodLook($payment->payment_method_name);
                                $pct = $paymentSummaryTotal != 0 ? ($payment->total_paid / $paymentSummaryTotal) * 100 : 0;
                            @endphp
                            <div class="method {{ $kind }}">
                                <div class="method__top">
                                    <span class="method__ic {{ $tone }}"><i class="fa {{ $icon }}"></i></span>
                                    <span class="method__name" title="{{ $payment->payment_method_name }}">{{ $payment->payment_method_name }}</span>
                                    <span class="method__pct">{{ number_format($pct, 1) }}%</span>
                                </div>
                                <div class="method__amt">{{ currency($payment->total_paid) }}</div>
                                <div class="method__meta">{{ $payment->count }} {{ Str::plural('transaction', $payment->count) }}</div>
                                <div class="method__bar"><span style="width: {{ max(2, min(100, $pct)) }}%"></span></div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- ============ TRANSACTIONS ============ --}}
        <div class="panel">
            <div class="panel__head">
                <div class="tabs" role="tablist">
                    <button type="button" class="tab" :class="{ on: tab === 'sales' }" @click="tab = 'sales'" role="tab">
                        <i class="fa fa-shopping-cart"></i> Sales <span class="n">{{ $sales->total() }}</span>
                    </button>
                    @if ($canViewTailoring)
                        <button type="button" class="tab" :class="{ on: tab === 'tailoring' }" @click="tab = 'tailoring'" role="tab">
                            <i class="fa fa-scissors"></i> Tailoring <span class="n">{{ $tailoringOrders->total() }}</span>
                        </button>
                    @endif
                    <button type="button" class="tab" :class="{ on: tab === 'payments' }" @click="tab = 'payments'" role="tab">
                        <i class="fa fa-exchange"></i> Payments <span class="n">{{ $combinedPayments->total() }}</span>
                    </button>
                </div>
                <div class="tools">
                    <label class="search">
                        <i class="fa fa-search"></i>
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Invoice, order, customer or mobile…" aria-label="Search transactions">
                    </label>
                    <select class="sel" wire:model.live="perPage" aria-label="Rows per page">
                        <option value="10">10 rows</option>
                        <option value="25">25 rows</option>
                        <option value="50">50 rows</option>
                        <option value="100">100 rows</option>
                    </select>
                </div>
            </div>

            {{-- ---- Sales ---- --}}
            <div x-show="tab === 'sales'" role="tabpanel">
                <div class="tbl-wrap">
                    <table class="sx">
                        <thead>
                            <tr>
                                <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="invoice_no" label="Invoice" /></th>
                                <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="account_id" label="Customer" /></th>
                                <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="created_at" label="Date" /></th>
                                <th class="end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="total" label="Total" /></th>
                                <th class="end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="item_discount" label="Discount" /></th>
                                <th class="end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tax_amount" label="Tax" /></th>
                                <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="payment_method_name" label="Method" /></th>
                                <th class="end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="paid" label="Paid" /></th>
                                <th class="end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="balance" label="Balance" /></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($sales as $sale)
                                @php $mobile = $sale->account->mobile ?? $sale->customer_mobile; @endphp
                                <tr>
                                    <td>
                                        <a class="ref" href="{{ route('sale::view', $sale->id) }}">{{ $sale->invoice_no }}</a>
                                        <span class="sub">#{{ $sale->id }}</span>
                                    </td>
                                    <td>
                                        <span class="cust">{{ $sale->account->name ?? ($sale->customer_name ?: '—') }}</span>
                                        @if ($mobile)
                                            <span class="sub"><i class="fa fa-phone"></i>{{ $mobile }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="num">{{ systemDate($sale->date) }}</span>
                                        <span class="sub num"><i class="fa fa-clock-o"></i>{{ $sale->created_at?->format('h:i A') }}</span>
                                    </td>
                                    <td class="end num fw">{{ currency($sale->total) }}</td>
                                    <td class="end num {{ $sale->item_discount != 0 ? 't-red' : 't-muted' }}">{{ $sale->item_discount != 0 ? currency($sale->item_discount) : '—' }}</td>
                                    <td class="end num t-muted">{{ $sale->tax_amount != 0 ? currency($sale->tax_amount) : '—' }}</td>
                                    <td>
                                        @if ($sale->payment_method_name)
                                            <span class="mth">{{ $sale->payment_method_name }}</span>
                                        @else
                                            <span class="t-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="end num fw t-green">{{ currency($sale->paid) }}</td>
                                    <td class="end num {{ $sale->balance != 0 ? 'fw t-red' : 't-muted' }}">{{ $sale->balance != 0 ? currency($sale->balance) : '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="empty-row">
                                        <i class="fa fa-shopping-cart"></i>
                                        <b>No sales found</b>
                                        {{ $search ? 'Nothing matches your search in this session.' : 'No sales have been recorded for this day session yet.' }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3"><span class="lbl">Session totals · {{ $totals['total_count'] }} {{ Str::plural('sale', $totals['total_count']) }}</span></td>
                                <td class="end">{{ currency($totals['total']) }}</td>
                                <td class="end t-red">{{ currency($totals['item_discount']) }}</td>
                                <td class="end t-muted">{{ currency($totals['tax_amount']) }}</td>
                                <td></td>
                                <td class="end t-green">{{ currency($totals['paid']) }}</td>
                                <td class="end {{ $totals['balance'] != 0 ? 't-red' : '' }}">{{ currency($totals['balance']) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="pager">
                    <div class="pager__info">Showing <b>{{ $sales->firstItem() ?? 0 }}–{{ $sales->lastItem() ?? 0 }}</b> of <b>{{ $sales->total() }}</b> sales</div>
                    {{ $sales->links(data: ['scrollTo' => false]) }}
                </div>
            </div>

            {{-- ---- Tailoring ---- --}}
            @if ($canViewTailoring)
                <div x-show="tab === 'tailoring'" x-cloak role="tabpanel">
                    <div class="tbl-wrap">
                        <table class="sx">
                            <thead>
                                <tr>
                                    <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="order_no" label="Order" /></th>
                                    <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="customer_name" label="Customer" /></th>
                                    <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="created_at" label="Date" /></th>
                                    <th class="end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="total" label="Total" /></th>
                                    <th class="end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="item_discount" label="Discount" /></th>
                                    <th class="end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tax_amount" label="Tax" /></th>
                                    <th>Method</th>
                                    <th class="end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="paid" label="Paid" /></th>
                                    <th class="end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="balance" label="Balance" /></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($tailoringOrders as $order)
                                    @php $mobile = $order->account->mobile ?? $order->customer_mobile; @endphp
                                    <tr>
                                        <td>
                                            <a class="ref" href="{{ route('tailoring::order::show', $order->id) }}">{{ $order->order_no }}</a>
                                            <span class="sub">#{{ $order->id }}</span>
                                        </td>
                                        <td>
                                            <span class="cust">{{ $order->account->name ?? ($order->customer_name ?: '—') }}</span>
                                            @if ($mobile)
                                                <span class="sub"><i class="fa fa-phone"></i>{{ $mobile }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="num">{{ systemDate($order->order_date) }}</span>
                                            <span class="sub num"><i class="fa fa-clock-o"></i>{{ $order->created_at?->format('h:i A') }}</span>
                                        </td>
                                        <td class="end num fw">{{ currency($order->total) }}</td>
                                        <td class="end num {{ $order->item_discount != 0 ? 't-red' : 't-muted' }}">{{ $order->item_discount != 0 ? currency($order->item_discount) : '—' }}</td>
                                        <td class="end num t-muted">{{ $order->tax_amount != 0 ? currency($order->tax_amount) : '—' }}</td>
                                        <td>
                                            @if ($order->payment_method_name)
                                                <span class="mth">{{ $order->payment_method_name }}</span>
                                            @else
                                                <span class="t-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="end num fw t-green">{{ currency($order->paid) }}</td>
                                        <td class="end num {{ $order->balance != 0 ? 'fw t-red' : 't-muted' }}">{{ $order->balance != 0 ? currency($order->balance) : '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="empty-row">
                                            <i class="fa fa-scissors"></i>
                                            <b>No tailoring orders found</b>
                                            {{ $search ? 'Nothing matches your search in this session.' : 'No tailoring orders have been recorded for this day session yet.' }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3"><span class="lbl">Tailoring totals · {{ $tailoringTotals['total_count'] }} {{ Str::plural('order', $tailoringTotals['total_count']) }}</span></td>
                                    <td class="end">{{ currency($tailoringTotals['total']) }}</td>
                                    <td class="end t-red">{{ currency($tailoringTotals['item_discount']) }}</td>
                                    <td class="end t-muted">{{ currency($tailoringTotals['tax_amount']) }}</td>
                                    <td></td>
                                    <td class="end t-green">{{ currency($tailoringTotals['paid']) }}</td>
                                    <td class="end {{ $tailoringTotals['balance'] != 0 ? 't-red' : '' }}">{{ currency($tailoringTotals['balance']) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="pager">
                        <div class="pager__info">Showing <b>{{ $tailoringOrders->firstItem() ?? 0 }}–{{ $tailoringOrders->lastItem() ?? 0 }}</b> of <b>{{ $tailoringOrders->total() }}</b> orders</div>
                        {{ $tailoringOrders->links(data: ['scrollTo' => false]) }}
                    </div>
                </div>
            @endif

            {{-- ---- Payments ---- --}}
            <div x-show="tab === 'payments'" x-cloak role="tabpanel">
                <div class="tbl-wrap">
                    <table class="sx">
                        <thead>
                            <tr>
                                <th>Paid on</th>
                                <th>Source</th>
                                <th>Reference</th>
                                <th>Customer</th>
                                <th>Method</th>
                                <th class="end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($combinedPayments as $payment)
                                <tr>
                                    <td class="num">{{ systemDate($payment->payment_date) }}</td>
                                    <td><span class="src {{ $payment->source === 'Sale' ? 'src--sale' : 'src--tailoring' }}">{{ $payment->source }}</span></td>
                                    <td>
                                        <span class="ref">{{ $payment->reference_no }}</span>
                                        <span class="sub num"><i class="fa fa-calendar-o"></i>{{ systemDate($payment->invoice_date) }}</span>
                                    </td>
                                    <td>
                                        <span class="cust">{{ $payment->customer_name ?: '—' }}</span>
                                        @if ($payment->customer_mobile)
                                            <span class="sub"><i class="fa fa-phone"></i>{{ $payment->customer_mobile }}</span>
                                        @endif
                                    </td>
                                    <td><span class="mth">{{ $payment->payment_method_name }}</span></td>
                                    <td class="end num fw t-green">{{ currency($payment->amount) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="empty-row">
                                        <i class="fa fa-exchange"></i>
                                        <b>No payments found</b>
                                        {{ $search ? 'Nothing matches your search in this session.' : 'No payments were received on this session date.' }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5"><span class="lbl">Page total</span></td>
                                <td class="end t-green">{{ currency($combinedPayments->sum('amount')) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="pager">
                    <div class="pager__info">Showing <b>{{ $combinedPayments->firstItem() ?? 0 }}–{{ $combinedPayments->lastItem() ?? 0 }}</b> of <b>{{ $combinedPayments->total() }}</b> payments</div>
                    {{ $combinedPayments->links(data: ['scrollTo' => false]) }}
                </div>
            </div>
        </div>
    </div>
</div>

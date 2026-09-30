<div>
    <x-report.studio />

    @php
        $statuses = [
            '' => 'All statuses',
            'paid' => 'Paid',
            'pending' => 'Pending',
            'failed' => 'Failed',
            'review' => 'Needs review',
            'refunded' => 'Refunded',
        ];
        $statusTone = fn (string $status) => match ($status) {
            'paid' => '',
            'failed' => 'off',
            'refunded' => 'info',
            'pending' => 'warn',
            default => 'bad',
        };
    @endphp

    <div class="wrx" wire:loading.class="is-busy">
        <div class="shell">
            {{-- ── Filter rail ─────────────────────────────────── --}}
            <aside class="rail">
                <div class="grp">
                    <h4>Period</h4>
                    <div class="quick">
                        @foreach ($ranges as $key => $label)
                            <button type="button" class="{{ $activeRange === $key ? 'is-on' : '' }}" wire:click="setRange('{{ $key }}')">{{ $label }}</button>
                        @endforeach
                    </div>
                    <div class="f">
                        <label for="op_from">From</label>
                        <input type="date" id="op_from" wire:model.live="from_date" max="{{ $to_date }}">
                    </div>
                    <div class="f">
                        <label for="op_to">To</label>
                        <input type="date" id="op_to" wire:model.live="to_date" max="{{ date('Y-m-d') }}">
                    </div>
                </div>

                <div class="grp">
                    <h4>Payments</h4>
                    <div class="f">
                        <label for="op_status">Status</label>
                        <select id="op_status" wire:model.live="status">
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grp">
                    <h4>Fulfilment</h4>
                    <div class="seg" role="radiogroup" aria-label="Fulfilment">
                        @foreach (['' => ['fa-th-large', 'All orders'], 'pickup' => ['fa-building', 'Collect in shop'], 'delivery' => ['fa-truck', 'Delivery']] as $value => [$icon, $label])
                            <label>
                                <input type="radio" name="op_fulfilment" value="{{ $value }}" wire:model.live="fulfilment">
                                <i class="fa {{ $icon }}"></i> {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="rail-foot">
                    <button type="button" class="btn-x solid" wire:click="export">
                        <i class="fa fa-download"></i> Export
                    </button>
                    <button type="button" class="btn-x ghost" wire:click="resetFilters">Reset filters</button>
                </div>
            </aside>

            {{-- ── Main column ─────────────────────────────────── --}}
            <div class="main">
                <div class="wrxsum">
                    <div class="wrxsum__row">
                        <div class="stat hero">
                            <span class="stat__ic"><i class="fa fa-money"></i></span>
                            <div class="k">Collected</div>
                            <div class="v">{{ currency($totals['collected']) }}</div>
                        </div>
                        <div class="stat">
                            <span class="stat__ic"><i class="fa fa-check"></i></span>
                            <div class="k">Paid orders</div>
                            <div class="v">{{ number_format($totals['paid']) }}</div>
                        </div>
                        <div class="stat warn">
                            <span class="stat__ic"><i class="fa fa-clock-o"></i></span>
                            <div class="k">Pending</div>
                            <div class="v">{{ number_format($totals['pending']) }}</div>
                        </div>
                        <div class="stat off">
                            <span class="stat__ic"><i class="fa fa-times"></i></span>
                            <div class="k">Failed</div>
                            <div class="v">{{ number_format($totals['failed']) }}</div>
                        </div>
                        <div class="stat bad">
                            <span class="stat__ic"><i class="fa fa-exclamation-triangle"></i></span>
                            <div class="k">Needs review</div>
                            <div class="v">{{ number_format($totals['review']) }}</div>
                        </div>
                        <div class="stat off">
                            <span class="stat__ic"><i class="fa fa-undo"></i></span>
                            <div class="k">Refunded</div>
                            <div class="v">{{ number_format($totals['refunded']) }}</div>
                        </div>
                    </div>
                    <p class="wrxsum__note">
                        "Collected" is every charge Tap captured, including "Needs review" — money taken for an order that could not be recorded as a sale
                        (stock ran out, amount mismatch…). Fix the cause, then press Check on the row to record the sale.
                        Refunded payments ({{ currency($totals['refunded_amount']) }}) are not counted; refunding cancels the order's sale.
                    </p>
                </div>

                <div class="tools">
                    <div class="search">
                        <i class="fa fa-search"></i>
                        <input type="text" id="op_search" wire:model.live.debounce.400ms="search"
                            placeholder="Customer, mobile, email, Tap charge or invoice" aria-label="Search online payments">
                    </div>
                    <span class="busy" wire:loading><i class="fa fa-refresh fa-spin"></i></span>
                    <div class="tools__end">
                        <span class="tools__cnt">{{ number_format($rows->total()) }} {{ Str::plural('payment', $rows->total()) }}</span>
                        <select wire:model.live="perPage" aria-label="Rows per page">
                            <option value="25">25 rows</option>
                            <option value="100">100 rows</option>
                            <option value="500">500 rows</option>
                        </select>
                    </div>
                </div>

                <div class="tbl-wrap">
                    <table class="wt wide">
                        <thead>
                            <tr>
                                <th>
                                    <button type="button" class="th-sort {{ $sortField === 'storefront_checkouts.id' ? 'is-on' : '' }}" wire:click="sortBy('storefront_checkouts.id')">
                                        Date <i class="fa fa-sort{{ $sortField === 'storefront_checkouts.id' ? '-' . $sortDirection : '' }}"></i>
                                    </button>
                                </th>
                                <th>
                                    <button type="button" class="th-sort {{ $sortField === 'storefront_checkouts.customer_name' ? 'is-on' : '' }}" wire:click="sortBy('storefront_checkouts.customer_name')">
                                        Customer <i class="fa fa-sort{{ $sortField === 'storefront_checkouts.customer_name' ? '-' . $sortDirection : '' }}"></i>
                                    </button>
                                </th>
                                <th>Fulfilment</th>
                                <th class="num">
                                    <button type="button" class="th-sort {{ $sortField === 'storefront_checkouts.amount' ? 'is-on' : '' }}" wire:click="sortBy('storefront_checkouts.amount')">
                                        Amount <i class="fa fa-sort{{ $sortField === 'storefront_checkouts.amount' ? '-' . $sortDirection : '' }}"></i>
                                    </button>
                                </th>
                                <th>
                                    <button type="button" class="th-sort {{ $sortField === 'storefront_checkouts.status' ? 'is-on' : '' }}" wire:click="sortBy('storefront_checkouts.status')">
                                        Status <i class="fa fa-sort{{ $sortField === 'storefront_checkouts.status' ? '-' . $sortDirection : '' }}"></i>
                                    </button>
                                </th>
                                <th>Tap status</th>
                                <th>Invoice</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                <tr wire:key="op-{{ $row->id }}" class="{{ $row->status === 'review' ? 'is-flagged' : '' }}">
                                    <td class="nowrap">
                                        {{ systemDate($row->created_at) }}
                                        <div class="sub mono">{{ $row->created_at?->format('h:i A') }}</div>
                                    </td>
                                    <td class="nowrap">
                                        <span class="nm">{{ $row->customer_name }}</span>
                                        <div class="sub mono">{{ $row->customer_mobile }}</div>
                                        @if ($row->customer_email)
                                            <div class="sub">{{ $row->customer_email }}</div>
                                        @endif
                                    </td>
                                    <td style="max-width: 220px; white-space: normal">
                                        @if ($row->fulfilment === 'delivery')
                                            <span class="tag info"><i class="fa fa-truck"></i>Delivery</span>
                                            <div class="sub">
                                                @if ($row->zone_number)
                                                    <span class="mono">Z {{ $row->zone_number }} · St {{ $row->street_number }} · Bldg {{ $row->building_number }}</span>{{ $row->city ? ', ' . $row->city : '' }}
                                                @else
                                                    {{ $row->address ?: '—' }}
                                                @endif
                                            </div>
                                            @if ($row->latitude && $row->longitude)
                                                <a href="https://www.google.com/maps?q={{ $row->latitude }},{{ $row->longitude }}" target="_blank" rel="noopener" class="sub">
                                                    <i class="fa fa-map-marker"></i> View on map
                                                </a>
                                            @endif
                                        @else
                                            <span class="tag off"><i class="fa fa-building"></i>Collect</span>
                                            <div class="sub">{{ $row->branch?->name ?? '—' }}</div>
                                        @endif
                                    </td>
                                    <td class="num nowrap bal">{{ $row->currency }} {{ currency($row->amount) }}</td>
                                    <td class="nowrap" style="white-space: normal; max-width: 200px">
                                        <span class="tag {{ $statusTone($row->status) }}">{{ $statuses[$row->status] ?? ucfirst($row->status) }}</span>
                                        @if ($row->failure_reason && $row->status !== 'paid')
                                            <div class="sub out">{{ $row->failure_reason }}</div>
                                        @endif
                                        @if ($row->refundPending())
                                            <div class="sub"><i class="fa fa-undo"></i> Refund {{ $row->refund_status }}</div>
                                        @elseif ($row->refundFailed())
                                            <div class="sub out"><i class="fa fa-undo"></i> Refund {{ $row->refund_status }}</div>
                                        @elseif ($row->refunded_at)
                                            <div class="sub">{{ systemDateTime($row->refunded_at) }}</div>
                                        @endif
                                    </td>
                                    <td class="nowrap mono" style="font-size: 11.5px" title="{{ $row->gateway_charge_id }}">{{ $row->gateway_status ?: '—' }}</td>
                                    <td class="nowrap">
                                        @if ($row->sale)
                                            <a href="{{ route('sale::view', $row->sale_id) }}" class="nm">{{ $row->sale->invoice_no }}</a>
                                        @else
                                            <span class="sub">—</span>
                                        @endif
                                    </td>
                                    <td class="num nowrap">
                                        @if (in_array($row->status, ['pending', 'review'], true) && $row->gateway_charge_id)
                                            <button type="button" class="icon-btn" wire:click="check({{ $row->id }})" wire:loading.attr="disabled" title="Ask Tap for the result">
                                                <i class="fa fa-refresh"></i> Check
                                            </button>
                                        @endif
                                        @if ($row->refundPending())
                                            <button type="button" class="icon-btn" wire:click="checkRefund({{ $row->id }})" wire:loading.attr="disabled" title="Ask Tap where the refund stands">
                                                <i class="fa fa-refresh"></i> Refund status
                                            </button>
                                        @elseif ($canRefund && $row->isRefundable())
                                            <button type="button" class="icon-btn" wire:loading.attr="disabled" title="Send the payment back to the customer"
                                                onclick="confirmOnlineRefund({{ $row->id }}, @js($row->currency . ' ' . currency($row->amount)), @js($row->customer_name), @js($row->sale?->invoice_no))">
                                                <i class="fa fa-undo"></i> Refund
                                            </button>
                                        @endif
                                        <button type="button" class="icon-btn" wire:click="showDetails({{ $row->id }})" title="Full transaction details">
                                            <i class="fa fa-eye"></i> Details
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8">
                                        <div class="empty">
                                            <div class="empty__ring"><i class="fa fa-globe"></i></div>
                                            <h4>No online payments match these filters</h4>
                                            <p>Try a wider date range, another status, or clear the fulfilment filter.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="foot">
                    <span>
                        @if ($rows->total())
                            Showing {{ number_format($rows->firstItem()) }}–{{ number_format($rows->lastItem()) }} of {{ number_format($rows->total()) }}
                        @else
                            No rows
                        @endif
                    </span>
                    @if ($rows->hasPages())
                        {{ $rows->links() }}
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if ($detail)
        @include('livewire.sale.partials.online-payment-details', ['detail' => $detail, 'statuses' => $statuses, 'statusTone' => $statusTone])
    @endif
</div>

@push('scripts')
    <script>
        function confirmOnlineRefund(id, amount, customer, invoice) {
            const esc = (value) => String(value).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
            Swal.fire({
                title: 'Refund ' + amount + '?',
                html: 'The full payment goes back to <b>' + esc(customer) + '</b> through Tap.' +
                    (invoice ? '<br>Sale <b>' + esc(invoice) + '</b> will be cancelled once Tap completes the refund — stock returns and its journal is reversed.' : '') +
                    '<br><small>This cannot be undone.</small>',
                icon: 'warning',
                input: 'text',
                inputPlaceholder: 'Reason (optional)',
                inputAttributes: { maxlength: 250 },
                showCancelButton: true,
                confirmButtonText: '<i class="fa fa-undo me-2"></i>Refund',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                focusCancel: true,
                customClass: { confirmButton: 'btn btn-danger', cancelButton: 'btn btn-secondary' },
            }).then((result) => {
                if (result.isConfirmed) {
                    @this.call('refund', id, result.value || '');
                }
            });
        }
    </script>
@endpush

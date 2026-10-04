@php
    $tap = $detail->paymentDetails();
    $explained = $detail->status !== 'paid' ? $detail->chargeExplanation() : null;
    $tone = match ($detail->status) {
        'paid' => 'ok',
        'pending' => 'warn',
        'failed' => 'off',
        'refunded' => 'info',
        default => 'bad',
    };
    $methodIcon = match (strtolower((string) $tap['method'])) {
        'visa' => 'fa-cc-visa',
        'mastercard', 'master' => 'fa-cc-mastercard',
        'amex', 'american_express' => 'fa-cc-amex',
        'apple_pay', 'applepay' => 'fa-apple',
        default => 'fa-credit-card',
    };
    $json = fn ($value) => json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $hasRefund = $detail->refund_id || $detail->refund_request;
    $exchanges = array_values(array_filter([
        ['POST', '/charges', 'Charge request', 'Sent when the customer pressed Pay', $detail->gateway_request, 'out'],
        ['GET', '/charges/' . $detail->gateway_charge_id, 'Charge response', 'Latest charge from Tap — refreshed on every check', $detail->gateway_response, 'in'],
        $hasRefund ? ['POST', '/refunds', 'Refund request', 'Sent when the refund was requested', $detail->refund_request, 'out'] : null,
        $hasRefund ? ['GET', '/refunds/' . $detail->refund_id, 'Refund response', 'Latest refund from Tap', $detail->refund_response, 'in'] : null,
    ]));
    $payment = [
        ['Charge ID', $detail->gateway_charge_id, true],
        ['Tap status', $detail->gateway_status, false],
        ['Created', $detail->created_at ? systemDateTime($detail->created_at) : null, false],
        ['Paid at', $detail->paid_at ? systemDateTime($detail->paid_at) : null, false],
        ['Payment type', $tap['payment_type'] ? ucfirst(strtolower($tap['payment_type'])) : null, false],
        ['Payment ref', $tap['payment_reference'], true],
        ['Receipt no', $tap['receipt_no'], true],
        ['Bank ref', $tap['acquirer_reference'], true],
        ['Gateway ref', $tap['gateway_reference'], true],
        ['Response', $tap['response_message'] ? $tap['response_message'] . ($tap['response_code'] ? ' · ' . $tap['response_code'] : '') : null, false],
    ];
    $customer = [
        ['Mobile', $detail->customer_mobile, true],
        ['Email', $detail->customer_email, false],
        ['Branch', $detail->branch?->name, false],
        ['City', $detail->city, false],
        ['Address', $detail->address, false],
    ];
    $refund = [
        ['Refund ID', $detail->refund_id, true],
        ['Tap status', $detail->refund_status, false],
        ['Amount', $detail->refund_amount !== null ? $detail->currency . ' ' . number_format((float) $detail->refund_amount, 2) : null, false],
        ['Reason', $detail->refund_reason, false],
        ['Requested by', $detail->refundRequestedBy?->name, false],
        ['Requested at', $detail->refund_requested_at ? systemDateTime($detail->refund_requested_at) : null, false],
        ['Refunded at', $detail->refunded_at ? systemDateTime($detail->refunded_at) : null, false],
        ['Response', data_get($detail->refund_response, 'response.message') ?: data_get($detail->refund_response, 'error'), false],
    ];
    $itemCount = collect($detail->items ?? [])->sum(fn ($line) => (float) ($line['quantity'] ?? 0));
@endphp

<x-payment.details-styles />

<div class="modal fade show d-block opd" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="opd_title"
    wire:click.self="closeDetails" wire:keydown.escape.window="closeDetails"
    x-data="{
        tab: 'overview',
        open: { 0: false, 1: true, 2: false, 3: true },
        copy(text, el) { navigator.clipboard?.writeText(text); el.classList.add('is-done'); setTimeout(() => el.classList.remove('is-done'), 1200); },
    }">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable modal-fullscreen-md-down">
        <div class="modal-content">
            {{-- ── Hero ─────────────────────────────── --}}
            <div class="opd-hero">
                <button type="button" class="opd-close" wire:click="closeDetails" aria-label="Close"><i class="fa fa-times"></i></button>

                <div class="opd-eyebrow">
                    <i class="fa fa-globe"></i> Online payment
                    <span class="opd-pill {{ $tone }}">{{ $statuses[$detail->status] ?? ucfirst($detail->status) }}</span>
                    @if ($detail->refundPending())
                        <span class="opd-pill warn"><i class="fa fa-undo"></i> Refund {{ strtolower($detail->refund_status) }}</span>
                    @endif
                </div>

                <div class="opd-amount" id="opd_title">
                    <small>{{ $detail->currency }}</small><b>{{ number_format((float) $detail->amount, 2) }}</b>
                </div>
                <div class="opd-who">
                    <strong>{{ $detail->customer_name }}</strong>
                    @if ($detail->created_at)
                        · {{ systemDateTime($detail->created_at) }}
                    @endif
                </div>

                <div class="opd-chips">
                    @if ($tap['method'] || $tap['card_number'])
                        <span class="opd-pill"><i class="fa {{ $methodIcon }}"></i> {{ $tap['method'] ? str_replace('_', ' ', $tap['method']) : 'Card' }} <span class="opd-mono">{{ $tap['card_number'] }}</span></span>
                    @endif
                    <span class="opd-pill"><i class="fa {{ $detail->isDelivery() ? 'fa-truck' : 'fa-building' }}"></i> {{ $detail->isDelivery() ? 'Delivery' : 'Collect in shop' }}</span>
                    @if ($detail->sale)
                        <a href="{{ route('sale::view', $detail->sale_id) }}" class="opd-pill {{ $detail->sale->status === 'cancelled' ? 'bad' : '' }}">
                            <i class="fa fa-file-text-o"></i> {{ $detail->sale->invoice_no }} · {{ ucfirst($detail->sale->status) }}
                        </a>
                    @endif
                    <span class="opd-pill opd-mono" title="Checkout reference">#{{ $detail->reference }}</span>
                </div>

                <nav class="opd-tabs" role="tablist">
                    <button type="button" role="tab" :class="{ 'is-on': tab === 'overview' }" x-on:click="tab = 'overview'"><i class="fa fa-th-large"></i> Overview</button>
                    <button type="button" role="tab" :class="{ 'is-on': tab === 'items' }" x-on:click="tab = 'items'"><i class="fa fa-shopping-bag"></i> Items<span class="n">{{ rtrim(rtrim(number_format($itemCount, 2), '0'), '.') }}</span></button>
                    @if ($hasRefund)
                        <button type="button" role="tab" :class="{ 'is-on': tab === 'refund' }" x-on:click="tab = 'refund'"><i class="fa fa-undo"></i> Refund</button>
                    @endif
                    <button type="button" role="tab" :class="{ 'is-on': tab === 'log' }" x-on:click="tab = 'log'"><i class="fa fa-code"></i> Tap log<span class="n">{{ count($exchanges) }}</span></button>
                </nav>
            </div>

            <div class="modal-body">
                @if ($explained)
                    <section class="opd-why {{ $explained['tone'] }}">
                        <div class="opd-why-head">
                            <i class="fa {{ $explained['tone'] === 'warn' ? 'fa-clock-o' : 'fa-exclamation-triangle' }}"></i>
                            <div>
                                <b>{{ $explained['headline'] }}</b>
                                <p>{{ $explained['summary'] }}</p>
                            </div>
                        </div>
                        @if ($detail->failure_reason && strcasecmp($detail->failure_reason, (string) $tap['response_message']) !== 0)
                            <div class="opd-why-own"><i class="fa fa-info-circle"></i> {{ $detail->failure_reason }}</div>
                        @endif
                        <div class="opd-why-body">
                            @if ($explained['facts'])
                                <ul class="opd-why-facts">
                                    @foreach ($explained['facts'] as $fact)
                                        <li>{{ $fact }}</li>
                                    @endforeach
                                </ul>
                            @endif
                            @if ($explained['timeline'])
                                <ol class="opd-why-trail">
                                    @foreach ($explained['timeline'] as $step)
                                        <li class="{{ in_array($step['status'], ['INITIATED', 'CAPTURED', 'AUTHORIZED'], true) ? '' : 'end' }}"><b>{{ $step['label'] }}</b><span>{{ $step['at'] }}</span></li>
                                    @endforeach
                                </ol>
                            @endif
                        </div>
                        @if ($explained['next'])
                            <div class="opd-why-next"><i class="fa fa-arrow-right"></i> {{ $explained['next'] }}</div>
                        @endif
                    </section>
                @elseif ($detail->failure_reason)
                    <div class="opd-note"><i class="fa fa-exclamation-triangle" style="margin-top:2px"></i><span>{{ $detail->failure_reason }}</span></div>
                @endif

                {{-- ── Overview ─────────────────────────── --}}
                <div x-show="tab === 'overview'" class="opd-grid">
                    <section class="opd-card">
                        <h6><i class="fa fa-credit-card"></i> Payment</h6>
                        @foreach ($payment as [$label, $value, $copyable])
                            @continue(blank($value))
                            <div class="opd-kv">
                                <span>{{ $label }}</span>
                                <div>
                                    <span class="v {{ $copyable ? 'opd-mono' : '' }}">{{ $value }}</span>
                                    @if ($copyable)
                                        <button type="button" class="opd-copy" title="Copy" x-on:click="copy(@js($value), $el)"><i class="fa fa-copy"></i></button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </section>

                    <section class="opd-card">
                        <h6><i class="fa fa-user"></i> Customer &amp; {{ $detail->isDelivery() ? 'delivery' : 'pickup' }}</h6>
                        @if ($detail->zone_number || $detail->street_number || $detail->building_number)
                            <div class="opd-plates">
                                <div class="opd-plate"><span>Zone</span><b>{{ $detail->zone_number ?: '—' }}</b></div>
                                <div class="opd-plate"><span>Street</span><b>{{ $detail->street_number ?: '—' }}</b></div>
                                <div class="opd-plate"><span>Building</span><b>{{ $detail->building_number ?: '—' }}</b></div>
                            </div>
                        @endif
                        <div class="opd-kv"><span>Name</span><div><span class="v">{{ $detail->customer_name }}</span></div></div>
                        @foreach ($customer as [$label, $value, $copyable])
                            @continue(blank($value))
                            <div class="opd-kv">
                                <span>{{ $label }}</span>
                                <div>
                                    <span class="v {{ $copyable ? 'opd-mono' : '' }}">{{ $value }}</span>
                                    @if ($copyable)
                                        <button type="button" class="opd-copy" title="Copy" x-on:click="copy(@js($value), $el)"><i class="fa fa-copy"></i></button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                        @if ($detail->mapUrl())
                            <a href="{{ $detail->mapUrl() }}" target="_blank" rel="noopener" class="opd-map"><i class="fa fa-map-marker"></i> Open in Google Maps</a>
                        @endif
                    </section>
                </div>

                {{-- ── Items ────────────────────────────── --}}
                <div x-show="tab === 'items'" x-cloak class="opd-card">
                    @if ($detail->items)
                        <table class="opd-lines">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th class="r">Qty</th>
                                    <th class="r hide-sm">Price</th>
                                    <th class="r hide-sm">Tax %</th>
                                    <th class="r">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($detail->items as $line)
                                    <tr>
                                        <td style="font-weight:600">{{ $line['name'] ?? '#' . ($line['product_id'] ?? '') }}</td>
                                        <td class="r">{{ $line['quantity'] ?? '' }}</td>
                                        <td class="r hide-sm">{{ number_format((float) ($line['unit_price'] ?? 0), 2) }}</td>
                                        <td class="r hide-sm">{{ $line['tax'] ?? 0 }}</td>
                                        <td class="r">{{ number_format((float) ($line['unit_price'] ?? 0) * (float) ($line['quantity'] ?? 0), 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="2">Charged</td>
                                    <td class="hide-sm"></td>
                                    <td class="hide-sm"></td>
                                    <td class="r">{{ $detail->currency }} {{ number_format((float) $detail->amount, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    @else
                        <div style="color:var(--opd-mut)">No items recorded.</div>
                    @endif
                </div>

                {{-- ── Refund ───────────────────────────── --}}
                @if ($hasRefund)
                    <div x-show="tab === 'refund'" x-cloak class="opd-card">
                        <h6><i class="fa fa-undo"></i> Refund through Tap</h6>
                        @foreach ($refund as [$label, $value, $copyable])
                            @continue(blank($value))
                            <div class="opd-kv">
                                <span>{{ $label }}</span>
                                <div>
                                    <span class="v {{ $copyable ? 'opd-mono' : '' }}">{{ $value }}</span>
                                    @if ($copyable)
                                        <button type="button" class="opd-copy" title="Copy" x-on:click="copy(@js($value), $el)"><i class="fa fa-copy"></i></button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- ── Tap log: raw requests and responses ─ --}}
                <div x-show="tab === 'log'" x-cloak class="opd-log">
                    @foreach ($exchanges as $index => [$verb, $path, $title, $hint, $payload, $direction])
                        <div class="opd-ex" :class="{ 'is-open': open[{{ $index }}] }">
                            <button type="button" class="opd-ex-head" x-on:click="open[{{ $index }}] = ! open[{{ $index }}]">
                                <span class="opd-verb {{ $direction }}">{{ $direction === 'out' ? $verb : 'RESPONSE' }}</span>
                                <span style="min-width:0">
                                    <b>{{ $title }}</b>
                                    <small><span class="opd-mono">{{ $path }}</span> · {{ $hint }}</small>
                                </span>
                                <i class="fa fa-chevron-down chev"></i>
                            </button>
                            <div x-show="open[{{ $index }}]" x-collapse>
                                @if ($payload)
                                    <div class="opd-code">
                                        <button type="button" class="opd-copy-btn" x-on:click="copy($el.nextElementSibling.textContent, $el); $el.innerText = 'Copied'; setTimeout(() => $el.innerText = 'Copy', 1200)">Copy</button>
                                        <pre>{{ $json($payload) }}</pre>
                                    </div>
                                @else
                                    <div class="opd-empty">Nothing recorded{{ $direction === 'out' ? ' — checkouts started before request logging was added have no request on file.' : '.' }}</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="modal-footer">
                @if ($detail->sale)
                    <a href="{{ route('sale::view', $detail->sale_id) }}" class="opd-btn"><i class="fa fa-file-text-o"></i> Open invoice</a>
                @endif
                @if ($detail->refundPending())
                    <button type="button" class="opd-btn" wire:click="checkRefund({{ $detail->id }})" wire:loading.attr="disabled"><i class="fa fa-refresh"></i> Refund status</button>
                @elseif ($canRefund && $detail->isRefundable())
                    <button type="button" class="opd-btn danger"
                        onclick="confirmOnlineRefund({{ $detail->id }}, @js($detail->currency . ' ' . currency($detail->amount)), @js($detail->customer_name), @js($detail->sale?->invoice_no))">
                        <i class="fa fa-undo"></i> Refund
                    </button>
                @endif
                <button type="button" class="opd-btn solid" wire:click="closeDetails">Close</button>
            </div>
        </div>
    </div>
</div>
<div class="modal-backdrop fade show"></div>

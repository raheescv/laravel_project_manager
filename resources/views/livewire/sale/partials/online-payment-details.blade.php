@php
    $tap = $detail->paymentDetails();
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

@once
    <style>
        /* Online payment details (.opd) — sits over the .wrx studio page, same emerald accent. */
        .opd {
            --opd-ac: #0f9d76;
            --opd-ac-soft: #e7f6f1;
            --opd-ink: #171a20;
            --opd-ink-2: #4c5361;
            --opd-mut: #8b93a3;
            --opd-line: #e6e8ec;
            --opd-soft: #f6f7f9;
            --opd-card: #ffffff;
            --opd-red: #d94b4b;
            --opd-red-soft: #fdeaea;
            --opd-amber: #b4791a;
            --opd-amber-soft: #fdf3e3;
            --opd-blue: #3d6ad6;
            --opd-blue-soft: #eaf0fd;
            --opd-code: #0f1420;
            --opd-mono: ui-monospace, 'SF Mono', 'Cascadia Code', Menlo, monospace;
            color: var(--opd-ink);
            font-size: 13px;
        }

        [data-bs-theme="dark"] .opd {
            color-scheme: dark;
            --opd-ac: #2ec294;
            --opd-ac-soft: rgba(46, 194, 148, .14);
            --opd-ink: #eaf0fb;
            --opd-ink-2: #c2cbdd;
            --opd-mut: #8b96ad;
            --opd-line: rgba(255, 255, 255, .10);
            --opd-soft: rgba(255, 255, 255, .04);
            --opd-card: #161b28;
            --opd-red: #f16b6b;
            --opd-red-soft: rgba(241, 107, 107, .14);
            --opd-amber: #e0ab4a;
            --opd-amber-soft: rgba(224, 171, 74, .14);
            --opd-blue: #7fa2f5;
            --opd-blue-soft: rgba(127, 162, 245, .14);
            --opd-code: #0b0f18;
        }

        .opd .modal-content {
            background: var(--opd-card);
            border: 0;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 30px 80px -20px rgba(15, 20, 32, .45);
        }

        .opd-hero {
            position: relative;
            padding: 20px 24px 0;
            background: linear-gradient(180deg, var(--opd-ac-soft), transparent);
            border-bottom: 1px solid var(--opd-line);
        }

        .opd-close {
            position: absolute;
            top: 14px;
            inset-inline-end: 14px;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            border: 1px solid var(--opd-line);
            background: var(--opd-card);
            color: var(--opd-ink-2);
            display: grid;
            place-items: center;
        }

        .opd-close:hover { color: var(--opd-ink); border-color: var(--opd-mut); }

        .opd-eyebrow {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            color: var(--opd-mut);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            padding-inline-end: 44px;
        }

        .opd-amount {
            display: flex;
            align-items: baseline;
            gap: 10px;
            flex-wrap: wrap;
            margin: 6px 0 4px;
        }

        .opd-amount b {
            font-size: 30px;
            font-weight: 800;
            letter-spacing: -.02em;
            line-height: 1.1;
            font-variant-numeric: tabular-nums;
        }

        .opd-amount small { color: var(--opd-mut); font-size: 14px; font-weight: 700; }

        .opd-who { color: var(--opd-ink-2); font-size: 14px; }
        .opd-who strong { color: var(--opd-ink); }

        .opd-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 11.5px;
            font-weight: 700;
            letter-spacing: 0;
            text-transform: none;
            background: var(--opd-soft);
            color: var(--opd-ink-2);
            border: 1px solid var(--opd-line);
        }

        .opd-pill.ok { background: var(--opd-ac-soft); color: var(--opd-ac); border-color: transparent; }
        .opd-pill.warn { background: var(--opd-amber-soft); color: var(--opd-amber); border-color: transparent; }
        .opd-pill.bad { background: var(--opd-red-soft); color: var(--opd-red); border-color: transparent; }
        .opd-pill.info { background: var(--opd-blue-soft); color: var(--opd-blue); border-color: transparent; }

        .opd-chips { display: flex; gap: 6px; flex-wrap: wrap; margin: 12px 0 14px; }
        .opd-chips a.opd-pill { text-decoration: none; }
        .opd-chips a.opd-pill:hover { border-color: var(--opd-ac); color: var(--opd-ac); }

        .opd-tabs { display: flex; gap: 2px; overflow-x: auto; scrollbar-width: none; margin-bottom: -1px; }
        .opd-tabs::-webkit-scrollbar { display: none; }

        .opd-tabs button {
            border: 0;
            background: none;
            padding: 10px 14px;
            color: var(--opd-mut);
            font-weight: 700;
            font-size: 12.5px;
            border-bottom: 2px solid transparent;
            white-space: nowrap;
        }

        .opd-tabs button:hover { color: var(--opd-ink); }
        .opd-tabs button.is-on { color: var(--opd-ac); border-bottom-color: var(--opd-ac); }
        .opd-tabs .n { color: var(--opd-mut); font-weight: 600; margin-inline-start: 3px; }

        .opd .modal-body { padding: 20px 24px; background: var(--opd-soft); }

        .opd-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }

        .opd-card {
            background: var(--opd-card);
            border: 1px solid var(--opd-line);
            border-radius: 14px;
            padding: 14px 16px;
            min-width: 0;
        }

        .opd-card h6 {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0 0 8px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: var(--opd-mut);
        }

        .opd-card h6 i { color: var(--opd-ac); font-size: 13px; }

        .opd-kv {
            display: grid;
            grid-template-columns: 118px minmax(0, 1fr);
            gap: 10px;
            padding: 7px 0;
            border-top: 1px dashed var(--opd-line);
            align-items: start;
        }

        .opd-kv:first-of-type { border-top: 0; }
        .opd-kv > span { color: var(--opd-mut); }
        .opd-kv > div { display: flex; align-items: flex-start; gap: 6px; min-width: 0; font-weight: 600; }
        .opd-kv .v { overflow-wrap: anywhere; }
        .opd-mono { font-family: var(--opd-mono); font-size: 12px; font-weight: 500; }

        .opd-copy {
            flex: none;
            border: 0;
            background: none;
            padding: 0 2px;
            color: var(--opd-mut);
            line-height: 1.4;
        }

        .opd-copy:hover, .opd-copy.is-done { color: var(--opd-ac); }

        .opd-plates { display: flex; gap: 6px; margin: 2px 0 6px; }

        .opd-plate {
            flex: 1;
            text-align: center;
            border: 1.5px solid #1f5fae;
            border-radius: 8px;
            padding: 4px 6px;
            background: var(--opd-card);
        }

        .opd-plate span { display: block; font-size: 10px; color: var(--opd-mut); text-transform: uppercase; font-weight: 700; }
        .opd-plate b { font-family: var(--opd-mono); font-size: 16px; color: #1f5fae; }
        [data-bs-theme="dark"] .opd-plate { border-color: #6f9fe0; }
        [data-bs-theme="dark"] .opd-plate b { color: #9dc0f0; }

        .opd-map { display: inline-flex; gap: 6px; align-items: center; font-weight: 700; color: var(--opd-ac); text-decoration: none; margin-top: 4px; }

        .opd-lines { width: 100%; border-collapse: collapse; }
        .opd-lines th { font-size: 11px; text-transform: uppercase; letter-spacing: .05em; color: var(--opd-mut); font-weight: 700; padding: 0 8px 8px; }
        .opd-lines td { padding: 10px 8px; border-top: 1px solid var(--opd-line); font-variant-numeric: tabular-nums; }
        .opd-lines .r { text-align: end; }
        .opd-lines tfoot td { font-weight: 800; border-top: 2px solid var(--opd-line); }

        .opd-log { display: grid; gap: 12px; }

        .opd-ex { background: var(--opd-card); border: 1px solid var(--opd-line); border-radius: 14px; overflow: hidden; }

        .opd-ex-head {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding: 12px 14px;
            background: none;
            border: 0;
            text-align: start;
            color: var(--opd-ink);
        }

        .opd-verb {
            flex: none;
            font-family: var(--opd-mono);
            font-size: 10.5px;
            font-weight: 800;
            padding: 3px 7px;
            border-radius: 6px;
            background: var(--opd-blue-soft);
            color: var(--opd-blue);
        }

        .opd-verb.in { background: var(--opd-ac-soft); color: var(--opd-ac); }
        .opd-ex-head b { display: block; font-size: 13px; }
        .opd-ex-head small { display: block; color: var(--opd-mut); overflow-wrap: anywhere; }
        .opd-ex-head .opd-mono { color: var(--opd-mut); }
        .opd-ex-head .chev { margin-inline-start: auto; color: var(--opd-mut); transition: transform .2s; }
        .opd-ex.is-open .chev { transform: rotate(180deg); }

        .opd-code { position: relative; background: var(--opd-code); }

        .opd-code pre {
            margin: 0;
            padding: 14px 16px;
            max-height: 380px;
            overflow: auto;
            color: #d6deeb;
            font-family: var(--opd-mono);
            font-size: 11.5px;
            line-height: 1.55;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .opd-code .opd-copy-btn {
            position: absolute;
            top: 8px;
            inset-inline-end: 10px;
            border: 1px solid rgba(255, 255, 255, .15);
            background: rgba(255, 255, 255, .06);
            color: #d6deeb;
            border-radius: 8px;
            padding: 3px 10px;
            font-size: 11.5px;
        }

        .opd-code .opd-copy-btn:hover { background: rgba(255, 255, 255, .12); }
        .opd-empty { padding: 12px 14px; color: var(--opd-mut); border-top: 1px solid var(--opd-line); }

        .opd .modal-footer {
            padding: 12px 24px;
            border-top: 1px solid var(--opd-line);
            background: var(--opd-card);
            gap: 8px;
        }

        .opd-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            border-radius: 10px;
            padding: 8px 14px;
            font-weight: 700;
            font-size: 12.5px;
            border: 1px solid var(--opd-line);
            background: var(--opd-card);
            color: var(--opd-ink);
            text-decoration: none;
        }

        .opd-btn:hover { border-color: var(--opd-mut); color: var(--opd-ink); }
        .opd-btn.danger { color: var(--opd-red); border-color: var(--opd-red-soft); background: var(--opd-red-soft); }
        .opd-btn.danger:hover { border-color: var(--opd-red); }
        .opd-btn.solid { background: var(--opd-ink); border-color: var(--opd-ink); color: var(--opd-card); }

        .opd-note {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            padding: 10px 14px;
            border-radius: 12px;
            background: var(--opd-red-soft);
            color: var(--opd-red);
            font-weight: 600;
            margin-bottom: 14px;
        }

        @media (max-width: 767.98px) {
            .opd-hero { padding: 16px 16px 0; }
            .opd .modal-body { padding: 14px 16px; }
            .opd .modal-footer { padding: 10px 16px; }
            .opd-grid { grid-template-columns: minmax(0, 1fr); }
            .opd-amount b { font-size: 26px; }
            .opd-kv { grid-template-columns: 100px minmax(0, 1fr); }
            .opd .modal-content { border-radius: 0; }
            .opd .modal-footer .opd-btn { flex: 1; justify-content: center; }
            .opd-lines .hide-sm { display: none; }
        }
    </style>
@endonce

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
                @if ($detail->failure_reason)
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

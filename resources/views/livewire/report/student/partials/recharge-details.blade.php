@php
    $explained = $detail->explanation($detailLogs);
    $statusTone = fn (string $status) => match ($status) {
        'success' => 'ok',
        'refunded' => 'info',
        'failed', 'review' => 'bad',
        default => 'warn',
    };
    $isRefund = $detail->type === 'refund';
    $json = fn ($value) => preg_replace_callback(
        '/(&quot;(?:[^&]|&(?!quot;))*?&quot;)(\s*:)?|\b(true|false|null)\b|(-?\d+(?:\.\d+)?)/',
        fn ($m) => match (true) {
            ($m[2] ?? '') !== '' => '<span class="j-key">' . $m[1] . '</span>' . $m[2],
            ($m[1] ?? '') !== '' => '<span class="j-str">' . $m[1] . '</span>',
            ($m[3] ?? '') !== '' => '<span class="j-lit">' . $m[3] . '</span>',
            default => '<span class="j-num">' . $m[4] . '</span>',
        },
        e(is_string($value) ? $value : json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)),
    );
    $student = $detail->account;
    $studentDetail = $student?->studentDetail;
    $shownLogs = $detailLogs->slice(-50)->values();
    $payment = [
        ['PUN', $detail->pun, true],
        [$isRefund ? 'Refund of' : null, $detail->original_pun, true],
        ['Gateway', $detail->isCreditCard() ? 'Mastercard Gateway (MPGS) · credit card' : 'QPay · debit card', false],
        ['Gateway said', trim($detail->gateway_status . ' ' . $detail->gateway_status_message) ?: null, false],
        ['Confirmation', $detail->confirmation_id, true],
        ['Card', $detail->cardLabel(), false],
        ['Funding', $detail->funding_method ? ucfirst(strtolower($detail->funding_method)) : null, false],
        ['Started', $detail->created_at ? systemDateTime($detail->created_at) : null, false],
        ['Completed', $detail->completed_at ? systemDateTime($detail->completed_at) : null, false],
        ['Last checked', $detail->last_inquired_at ? systemDateTime($detail->last_inquired_at) : null, false],
        ['Journal', $detail->journal_id ? '#' . $detail->journal_id : null, false],
    ];
    $people = [
        ['Admission no', $studentDetail?->admission_no, true],
        ['Class', trim(implode(' - ', array_filter([$studentDetail?->grade, $studentDetail?->section]))) ?: null, false],
        ['Parent', $detail->guardian?->name, false],
        ['Mobile', $detail->guardian?->mobile, true],
        ['Email', $detail->guardian?->email, false],
    ];
@endphp

<x-payment.details-styles />

<style>
    .opd-code .j-key { color: #82aaff; }
    .opd-code .j-str { color: #c3e88d; }
    .opd-code .j-num { color: #f78c6c; }
    .opd-code .j-lit { color: #c792ea; }

    /* Compact: the recharge popup carries less than an order, so it sits tighter. */
    .opd--compact { font-size: 12.5px; }
    .opd--compact .modal-content { border-radius: 14px; }
    .opd--compact .opd-hero { padding: 14px 18px 0; }
    .opd--compact .opd-close { top: 10px; width: 30px; height: 30px; }
    .opd--compact .opd-amount { margin: 2px 0 2px; }
    .opd--compact .opd-amount b { font-size: 24px; }
    .opd--compact .opd-amount small { font-size: 12px; }
    .opd--compact .opd-who { font-size: 12.5px; }
    .opd--compact .opd-chips { margin: 8px 0 8px; gap: 4px; }
    .opd--compact .opd-pill { padding: 2px 8px; font-size: 11px; }
    .opd--compact .opd-tabs button { padding: 7px 11px; font-size: 12px; }
    .opd--compact .modal-body { padding: 12px 16px; }
    .opd--compact .opd-why { margin-bottom: 10px; border-radius: 10px; }
    .opd--compact .opd-why-head { padding: 8px 12px; }
    .opd--compact .opd-why-head b { font-size: 13.5px; }
    .opd--compact .opd-why-head p { font-size: 12.5px; }
    .opd--compact .opd-why-body { padding: 8px 12px; gap: 10px; }
    .opd--compact .opd-why-facts { font-size: 12px; }
    .opd--compact .opd-why-facts li + li { margin-top: 2px; }
    .opd--compact .opd-why-trail { font-size: 11.5px; }
    .opd--compact .opd-why-trail li { padding-bottom: 5px; }
    .opd--compact .opd-why-next { padding: 6px 12px; font-size: 12px; }
    .opd--compact .opd-grid { gap: 10px; }
    .opd--compact .opd-card { padding: 10px 12px; border-radius: 10px; }
    .opd--compact .opd-card h6 { margin-bottom: 4px; font-size: 10.5px; }
    .opd--compact .opd-kv { grid-template-columns: 100px minmax(0, 1fr); padding: 4px 0; gap: 8px; }
    .opd--compact .opd-log { gap: 6px; }
    .opd--compact .opd-ex { border-radius: 10px; }
    .opd--compact .opd-ex-head { padding: 7px 10px; gap: 8px; }
    .opd--compact .opd-ex-head b { font-size: 12.5px; }
    .opd--compact .opd-ex-head small { font-size: 11.5px; }
    .opd--compact .opd-ex-head .opd-pill { padding: 0 6px; font-size: 10.5px; vertical-align: 1px; }
    .opd--compact .opd-verb { font-size: 10px; padding: 2px 6px; }
    .opd--compact .opd-empty { padding: 6px 10px; font-size: 11.5px; }
    .opd--compact .opd-code pre { padding: 10px 12px; max-height: 260px; font-size: 11px; line-height: 1.45; }
    .opd--compact .opd-code .opd-copy-btn { top: 6px; padding: 2px 8px; font-size: 11px; }
    .opd--compact .opd-lines td { padding: 6px 6px; }
    .opd--compact .opd-lines .opd-btn { padding: 3px 9px; font-size: 11.5px; }
    .opd--compact .modal-footer { padding: 8px 16px; }
    .opd--compact .modal-footer .opd-btn { padding: 6px 12px; font-size: 12px; }
</style>

<div class="modal fade show d-block opd opd--compact" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="qrd_title"
    wire:click.self="closeDetails" wire:keydown.escape.window="closeDetails"
    x-data="{
        tab: 'overview',
        open: {},
        copy(text, el) { navigator.clipboard?.writeText(text); el.classList.add('is-done'); setTimeout(() => el.classList.remove('is-done'), 1200); },
    }">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable modal-fullscreen-md-down">
        <div class="modal-content">
            {{-- ── Hero ─────────────────────────────── --}}
            <div class="opd-hero">
                <button type="button" class="opd-close" wire:click="closeDetails" aria-label="Close"><i class="fa fa-times"></i></button>

                <div class="opd-eyebrow">
                    <i class="fa {{ $detail->isCreditCard() ? 'fa-credit-card' : 'fa-globe' }}"></i> Online {{ $isRefund ? 'refund' : 'top-up' }} · {{ $detail->gatewayLabel() }}
                    <span class="opd-pill {{ $explained['tone'] }}">{{ $detail->statusLabel() }}</span>
                    @if ($detail->tampered_at)
                        <span class="opd-pill bad" title="The response failed the secure hash check and was verified by inquiry">Tampered</span>
                    @endif
                </div>

                <div class="opd-amount" id="qrd_title">
                    <small>{{ in_array($detail->currency_code, [null, '', \App\Services\Payment\QPayClient::CURRENCY_QAR], true) ? 'QAR' : $detail->currency_code }}</small><b>{{ $isRefund ? '−' : '' }}{{ number_format((float) $detail->amount, 2) }}</b>
                </div>
                <div class="opd-who">
                    <strong>{{ $student?->name }}</strong>
                    @if ($detail->guardian?->name)
                        · paid by {{ $detail->guardian->name }}
                    @endif
                    @if ($detail->created_at)
                        · {{ systemDateTime($detail->created_at) }}
                    @endif
                </div>

                <div class="opd-chips">
                    <span class="opd-pill"><i class="fa {{ $detail->isCreditCard() ? 'fa-credit-card' : 'fa-globe' }}"></i> {{ $detail->methodLabel() }} <span class="opd-mono">{{ $detail->cardLabel() }}</span></span>
                    @if ($student)
                        <a href="{{ route('student::view', $detail->account_id) }}" class="opd-pill"><i class="fa fa-user"></i> {{ $student->name }}</a>
                    @endif
                    <span class="opd-pill opd-mono" title="Payment unique number">#{{ $detail->pun }}</span>
                    <button type="button" class="opd-pill" title="Copy a link that opens this transaction"
                        x-on:click="copy(@js(route('student::report::recharges', ['txn' => $detail->id])), $el); $el.querySelector('span').innerText = 'Link copied'; setTimeout(() => $el.querySelector('span').innerText = 'Copy link', 1400)">
                        <i class="fa fa-link"></i> <span>Copy link</span>
                    </button>
                </div>

                <nav class="opd-tabs" role="tablist">
                    <button type="button" role="tab" :class="{ 'is-on': tab === 'overview' }" x-on:click="tab = 'overview'"><i class="fa fa-th-large"></i> Overview</button>
                    @if ($detailRelated->isNotEmpty())
                        <button type="button" role="tab" :class="{ 'is-on': tab === 'related' }" x-on:click="tab = 'related'"><i class="fa fa-undo"></i> {{ $isRefund ? 'Payment & refunds' : 'Refunds' }}<span class="n">{{ $detailRelated->count() }}</span></button>
                    @endif
                    <button type="button" role="tab" :class="{ 'is-on': tab === 'log' }" x-on:click="tab = 'log'"><i class="fa fa-code"></i> {{ $detail->gatewayLabel() }} log<span class="n">{{ $detailLogs->count() }}</span></button>
                </nav>
            </div>

            <div class="modal-body">
                {{-- ── What happened, in plain words ────── --}}
                <section class="opd-why {{ $explained['tone'] === 'info' ? 'ok' : $explained['tone'] }}">
                    <div class="opd-why-head">
                        <i class="fa {{ match ($explained['tone']) { 'ok', 'info' => 'fa-check-circle', 'warn' => 'fa-clock-o', default => 'fa-exclamation-triangle' } }}"></i>
                        <div>
                            <b>{{ $explained['headline'] }}</b>
                            <p>{{ $explained['summary'] }}</p>
                        </div>
                    </div>
                    <div class="opd-why-body">
                        <ul class="opd-why-facts">
                            @foreach ($explained['facts'] as $fact)
                                <li>{{ $fact }}</li>
                            @endforeach
                        </ul>
                        @if ($explained['timeline'])
                            <ol class="opd-why-trail">
                                @foreach ($explained['timeline'] as $step)
                                    <li class="{{ $step['ok'] ? '' : 'end' }}"><b>{{ $step['label'] }}</b><span>{{ $step['at'] }}</span></li>
                                @endforeach
                            </ol>
                        @endif
                    </div>
                    @if ($explained['next'])
                        <div class="opd-why-next"><i class="fa fa-arrow-right"></i> {{ $explained['next'] }}</div>
                    @endif
                </section>

                {{-- ── Overview ─────────────────────────── --}}
                <div x-show="tab === 'overview'" class="opd-grid">
                    <section class="opd-card">
                        <h6><i class="fa fa-credit-card"></i> {{ $isRefund ? 'Refund' : 'Payment' }}</h6>
                        @foreach ($payment as [$label, $value, $copyable])
                            @continue(blank($label) || blank($value))
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
                        <h6><i class="fa fa-user"></i> Student &amp; parent</h6>
                        <div class="opd-kv"><span>Student</span><div><span class="v">{{ $student?->name ?: '—' }}</span></div></div>
                        @foreach ($people as [$label, $value, $copyable])
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
                        @if ($detail->failure_reason)
                            <div class="opd-kv"><span>Note</span><div><span class="v">{{ $detail->failure_reason }}</span></div></div>
                        @endif
                    </section>
                </div>

                {{-- ── Linked payment / refunds ─────────── --}}
                @if ($detailRelated->isNotEmpty())
                    <div x-show="tab === 'related'" x-cloak class="opd-card">
                        <table class="opd-lines">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>What</th>
                                    <th class="hide-sm">PUN</th>
                                    <th class="r">Amount</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($detailRelated as $related)
                                    <tr>
                                        <td>{{ systemDateTime($related->created_at) }}</td>
                                        <td style="font-weight:600">{{ $related->type === 'refund' ? 'Refund' : 'Payment' }}</td>
                                        <td class="hide-sm opd-mono">{{ $related->pun }}</td>
                                        <td class="r">{{ $related->type === 'refund' ? '−' : '' }}{{ currency($related->amount) }}</td>
                                        <td><span class="opd-pill {{ $statusTone($related->status) }}">{{ $related->statusLabel() }}</span></td>
                                        <td class="r"><button type="button" class="opd-btn" wire:click="showDetails({{ $related->id }})">Open</button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                {{-- ── Gateway log: every request and response ─ --}}
                <div x-show="tab === 'log'" x-cloak class="opd-log">
                    @if ($detailLogs->count() > $shownLogs->count())
                        <div class="opd-empty" style="border:0;padding:0">Showing the latest {{ $shownLogs->count() }} of {{ $detailLogs->count() }} messages.</div>
                    @endif
                    @forelse ($shownLogs as $log)
                        @php($said = \App\Support\Payment\QPayRechargeExplanation::exchange($log))
                        <div class="opd-ex" :class="{ 'is-open': open[{{ $log->id }}] }">
                            <button type="button" class="opd-ex-head" x-on:click="open[{{ $log->id }}] = ! open[{{ $log->id }}]">
                                <span class="opd-verb">{{ $log->method ?: 'POST' }}</span>
                                <span style="min-width:0">
                                    <b>{{ $said['title'] }} <span class="opd-pill {{ $said['tone'] }}">{{ ucfirst($log->status) }}</span></b>
                                    <small>{{ systemDateTime($log->created_at) }} · {{ $said['hint'] }}</small>
                                    @if ($said['outcome'])
                                        <small style="color:var(--opd-ink-2)"><i class="fa fa-comment-o"></i> {{ $said['outcome'] }}</small>
                                    @endif
                                </span>
                                <i class="fa fa-chevron-down chev"></i>
                            </button>
                            <div x-show="open[{{ $log->id }}]" x-collapse>
                                <div class="opd-empty opd-mono">{{ $log->endpoint }}@if ($log->user_name) · by {{ $log->user_name }}@endif</div>
                                @foreach ([['Request', \App\Support\Payment\QPayRechargeExplanation::decoded($log->request) ?? $log->request], ['Response', \App\Support\Payment\QPayRechargeExplanation::decoded($log->response) ?? $log->response]] as [$part, $payload])
                                    <div class="opd-empty" style="font-weight:700">{{ $part }}</div>
                                    @if ($payload)
                                        <div class="opd-code">
                                            <button type="button" class="opd-copy-btn" x-on:click="copy($el.nextElementSibling.textContent, $el); $el.innerText = 'Copied'; setTimeout(() => $el.innerText = 'Copy', 1200)">Copy</button>
                                            <pre>{!! $json($payload) !!}</pre>
                                        </div>
                                    @else
                                        <div class="opd-empty">Nothing recorded.</div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="opd-card" style="color:var(--opd-mut)">No {{ $detail->gatewayLabel() }} messages were logged for this payment.</div>
                    @endforelse

                    @if ($detail->payload)
                        <div class="opd-ex" :class="{ 'is-open': open.saved }">
                            <button type="button" class="opd-ex-head" x-on:click="open.saved = ! open.saved">
                                <span class="opd-verb in">SAVED</span>
                                <span style="min-width:0">
                                    <b>Result kept on this transaction</b>
                                    <small>The last answer {{ $detail->gatewayLabel() }} gave, as stored when the payment was settled</small>
                                </span>
                                <i class="fa fa-chevron-down chev"></i>
                            </button>
                            <div x-show="open.saved" x-collapse>
                                <div class="opd-code">
                                    <button type="button" class="opd-copy-btn" x-on:click="copy($el.nextElementSibling.textContent, $el); $el.innerText = 'Copied'; setTimeout(() => $el.innerText = 'Copy', 1200)">Copy</button>
                                    <pre>{!! $json($detail->payload) !!}</pre>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="modal-footer">
                @if ($student)
                    <a href="{{ route('student::view', $detail->account_id) }}" class="opd-btn"><i class="fa fa-user"></i> Open student</a>
                @endif
                @if ($detail->awaitsResult())
                    <button type="button" class="opd-btn" wire:click="inquire({{ $detail->id }})" wire:loading.attr="disabled"><i class="fa fa-refresh"></i> Check with {{ $detail->gatewayLabel() }}</button>
                @endif
                @can('student topup.release')
                    @if ($detail->isReleasable())
                        <button type="button" class="opd-btn" wire:click="release({{ $detail->id }})" wire:loading.attr="disabled"
                            wire:confirm="Release this {{ currency($detail->amount) }} top-up so the parent can pay again?&#10;&#10;QPay is asked once more first. If it still has no answer the top-up is marked Unresolved — not failed — and we keep asking; the card is credited if it turns out to have been paid."
                            title="Free the card from this unanswered payment">
                            <i class="fa fa-unlock"></i> Release
                        </button>
                    @endif
                @endcan
                <button type="button" class="opd-btn solid" wire:click="closeDetails">Close</button>
            </div>
        </div>
    </div>
</div>
<div class="modal-backdrop fade show"></div>

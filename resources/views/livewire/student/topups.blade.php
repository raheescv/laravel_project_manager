{{-- Student view → Top-ups. Styled by the parent .svx system (components/student/view-premium).
     The office entry form is the page-level student.topup-modal component, opened by the button below. --}}
@php
    $channelTone = ['Debit card' => ['topup', 'fa-globe'], 'Credit card' => ['topup', 'fa-credit-card'], 'Office' => ['purchase', 'fa-building-o']];
    $statusTone = [
        'success' => 'success',
        'failed' => 'failed',
        'cancelled' => 'failed',
        'pending' => 'pending',
        'refund_pending' => 'pending',
        'review' => 'pending',
    ];
@endphp
<div>
    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
        <p class="small text-body-secondary mb-0" style="max-width: 62ch">
            Money on and off the card outside a purchase: what the office takes at the counter, and what parents pay online — by debit card through QPay or by credit card through the Mastercard Gateway.
        </p>
        @canany(['student topup.create', 'student topup.refund'])
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#StudentTopupModal">
                <i class="fa fa-plus me-1"></i>Record top-up
            </button>
        @endcanany
    </div>

    <div class="tblw">
        <div class="table-responsive">
            <table class="table tbl">
                <thead>
                    <tr>
                        <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="date" label="Date" /></th>
                        <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="channel" label="Channel" /></th>
                        <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="method" label="Method / card" /></th>
                        <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="note" label="Reason / reference" /></th>
                        <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="by" label="By" /></th>
                        <th class="text-end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="amount" label="Amount" /></th>
                        <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="status" label="Status" /></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        @php
                            $transaction = $row['qpay'];
                            [$tone, $icon] = $channelTone[$row['channel']] ?? ['plain', null];
                        @endphp
                        <tr wire:key="topup-{{ $row['channel'] }}-{{ $transaction?->id ?? $loop->index }}">
                            <td class="text-nowrap">{{ systemDate($row['date']) }}</td>
                            <td><span class="tc {{ $tone }}">@if ($icon)<i class="fa {{ $icon }}"></i>@endif{{ $row['channel'] }}</span></td>
                            <td>{{ $row['method'] }}</td>
                            <td>
                                {{ $row['note'] ?: '-' }}
                                @if ($row['reference'])
                                    <div class="mono text-body-secondary">{{ $row['reference'] }}</div>
                                @endif
                            </td>
                            <td>{{ $row['by'] ?? '-' }}</td>
                            <td class="text-end fw-semibold text-nowrap {{ $row['amount'] < 0 ? 'text-danger-emphasis' : 'text-success-emphasis' }}">
                                {{ $row['amount'] < 0 ? '−' : '+' }}{{ currency(abs($row['amount'])) }}
                            </td>
                            <td class="text-nowrap">
                                <span class="sp {{ $statusTone[$row['status']] ?? '' }}">{{ $row['status_label'] }}</span>
                                @if ($transaction?->tampered_at)
                                    <span class="sp failed" title="The response failed the secure hash check and was verified by inquiry">Tampered</span>
                                @endif
                            </td>
                            <td class="text-nowrap text-end">
                                @if ($transaction?->awaitsResult() && $transaction->type === 'payment')
                                    <button type="button" class="btn btn-sm btn-light" wire:click="inquire({{ $transaction->id }})" wire:loading.attr="disabled" title="Ask {{ $transaction->gatewayLabel() }} for the result">
                                        <i class="fa fa-refresh"></i> Check
                                    </button>
                                @endif
                                {{-- A payment QPay will not answer for blocks the parent from topping up again.
                                     Releasing frees the card without claiming the money was never taken. --}}
                                @can('student topup.release')
                                    @if ($transaction?->isPending() && $transaction->type === 'payment' && ! $transaction->isCreditCard())
                                        <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="release({{ $transaction->id }})" wire:loading.attr="disabled"
                                            wire:confirm="Release this {{ currency($transaction->amount) }} top-up so the parent can pay again?&#10;&#10;QPay is asked once more first. If it still has no answer the top-up is marked Unresolved — not failed — and we keep asking; the card is credited if it turns out to have been paid."
                                            title="Free the card from this unanswered payment">
                                            <i class="fa fa-unlock"></i> Release
                                        </button>
                                    @endif
                                @endcan
                                @can('student topup.refund')
                                    @if ($transaction && $transaction->status === 'success' && $transaction->type === 'payment')
                                        <button type="button" class="btn btn-sm btn-outline-danger" wire:click="refund({{ $transaction->id }})"
                                            wire:confirm="Refund {{ currency($transaction->amount) }} to the parent's {{ strtolower($transaction->methodLabel()) }} through {{ $transaction->gatewayLabel() }}? The amount is taken off the student's card.">
                                            <i class="fa fa-undo"></i> Refund
                                        </button>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr class="none">
                            <td colspan="8"><i class="fa fa-plus-circle me-1"></i>Nothing on the card yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

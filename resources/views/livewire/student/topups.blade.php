<div>
    @canany(['student topup.create', 'student topup.refund'])
        <div class="d-flex justify-content-between align-items-center mb-3">
            <p class="small text-body-secondary mb-0">
                Money on and off the card outside a purchase: what the office takes at the counter, and what parents pay online through QPay.
            </p>
            <button type="button" class="btn btn-sm {{ $show_form ? 'btn-light' : 'btn-primary' }}" wire:click="toggleForm">
                <i class="fa {{ $show_form ? 'fa-times' : 'fa-plus' }} me-1"></i>{{ $show_form ? 'Cancel' : 'Record top-up' }}
            </button>
        </div>

        @if ($show_form)
            <form class="border rounded p-3 mb-3 bg-body-tertiary" wire:submit="record">
                <div class="row g-2 align-items-end">
                    <div class="col-6 col-md-2">
                        <label class="form-label small fw-medium mb-1" for="tp_direction">Entry</label>
                        <select id="tp_direction" class="form-select form-select-sm" wire:model.live="direction">
                            @can('student topup.create')
                                <option value="add">Add to card</option>
                            @endcan
                            @can('student topup.refund')
                                <option value="deduct">Take off card</option>
                            @endcan
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small fw-medium mb-1" for="tp_amount">Amount</label>
                        <input type="number" step="0.01" min="0.01" id="tp_amount" class="form-control form-control-sm" wire:model="amount" required>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-medium mb-1" for="tp_method">
                            {{ $direction === 'deduct' ? 'Paid out from' : 'Received in' }}
                        </label>
                        <select id="tp_method" class="form-select form-select-sm" wire:model="payment_account_id" required>
                            <option value="">Choose…</option>
                            @foreach ($paymentMethods as $method)
                                <option value="{{ $method->id }}">{{ $method->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small fw-medium mb-1" for="tp_date">Date</label>
                        <input type="date" id="tp_date" class="form-control form-control-sm" wire:model="date" max="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label small fw-medium mb-1" for="tp_reason">Reason</label>
                        <input type="text" id="tp_reason" class="form-control form-control-sm" wire:model="reason" maxlength="255"
                            placeholder="{{ $direction === 'deduct' ? 'e.g. balance paid back to parent' : 'e.g. cash from parent at the office' }}" required>
                    </div>
                    <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                        <button type="submit" class="btn btn-primary btn-sm" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="record"><i class="fa fa-save me-1"></i>{{ $direction === 'deduct' ? 'Take off card' : 'Add to card' }}</span>
                            <span wire:loading wire:target="record">Saving…</span>
                        </button>
                    </div>
                </div>
                <div class="form-text mt-2">
                    Posted to the books straight away: the money lands in the account you choose and the student's card balance moves by the same amount.
                    A deduction cannot take the card below zero.
                </div>
            </form>
        @endif
    @endcanany

    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="bg-light text-muted small">
                <tr>
                    <th>Date</th>
                    <th>Channel</th>
                    <th>Method / card</th>
                    <th>Reason / reference</th>
                    <th>By</th>
                    <th class="text-end">Amount</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    @php($transaction = $row['qpay'])
                    <tr wire:key="topup-{{ $row['channel'] }}-{{ $transaction?->id ?? $loop->index }}">
                        <td class="text-nowrap">{{ systemDate($row['date']) }}</td>
                        <td><span class="badge {{ $row['channel'] === 'QPay' ? 'bg-info text-dark' : 'bg-light text-dark border' }}">{{ $row['channel'] }}</span></td>
                        <td class="small">{{ $row['method'] }}</td>
                        <td class="small">
                            {{ $row['note'] ?: '-' }}
                            @if ($row['reference'])
                                <div class="font-monospace text-body-secondary">{{ $row['reference'] }}</div>
                            @endif
                        </td>
                        <td class="small">{{ $row['by'] ?? '-' }}</td>
                        <td class="text-end fw-semibold text-nowrap {{ $row['amount'] < 0 ? 'text-danger' : 'text-success' }}">
                            {{ $row['amount'] < 0 ? '-' : '+' }}{{ currency(abs($row['amount'])) }}
                        </td>
                        <td>
                            <span @class([
                                'badge',
                                'bg-success' => $row['status'] === 'success',
                                'bg-danger' => $row['status'] === 'failed',
                                'bg-warning text-dark' => in_array($row['status'], ['pending', 'refund_pending', 'review']),
                                'bg-secondary' => $row['status'] === 'refunded',
                            ])>{{ $row['status_label'] }}</span>
                            @if ($transaction?->tampered_at)
                                <span class="badge bg-danger" title="The response failed the secure hash check and was verified by inquiry">Tampered</span>
                            @endif
                        </td>
                        <td class="text-nowrap text-end">
                            @if ($transaction?->isPending() && $transaction->type === 'payment')
                                <button type="button" class="btn btn-sm btn-light" wire:click="inquire({{ $transaction->id }})" wire:loading.attr="disabled" title="Ask QPay for the result">
                                    <i class="fa fa-refresh"></i> Check
                                </button>
                            @endif
                            @can('student topup.refund')
                                @if ($transaction && $transaction->status === 'success' && $transaction->type === 'payment')
                                    <button type="button" class="btn btn-sm btn-outline-danger" wire:click="refund({{ $transaction->id }})"
                                        wire:confirm="Refund {{ currency($transaction->amount) }} to the parent's card through QPay? The amount is taken off the student's card.">
                                        <i class="fa fa-undo"></i> Refund
                                    </button>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-body-secondary py-3">Nothing on the card yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

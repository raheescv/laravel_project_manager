{{--
    Student view → Top-ups → "Record top-up" modal.

    The office entry form, lifted out of the tab into its own modal so the table
    stays the page. State still lives on the Topups Livewire component (wire:model),
    so this file is markup only; Alpine mirrors the two fields the modal reacts to
    (direction and amount) so the labels and the new-balance line move without a
    round trip. Styled by the parent .svx system (components/student/view-premium).

    Opened by Bootstrap's data API; closed by Bootstrap, or by the
    `student-topup-saved` event the component fires once the entry is posted.
--}}
@props(['direction' => 'add', 'paymentMethods' => [], 'balance' => 0])

@php
    $canAdd = auth()->user()?->can('student topup.create');
    $canDeduct = auth()->user()?->can('student topup.refund');
    $decimals = currency_decimals();
    $suggestions = [
        'add' => ['Cash from parent at the office', 'Bank transfer received', 'Balance correction'],
        'deduct' => ['Balance paid back to parent', 'Recorded twice by mistake', 'Leaving school'],
    ];
@endphp

<div wire:ignore.self class="modal fade tpm" id="StudentTopupModal" tabindex="-1" aria-labelledby="StudentTopupModalTitle" aria-hidden="true"
    x-data="{
        dir: @js($direction),
        amount: '',
        balance: {{ (float) $balance }},
        decimals: {{ $decimals }},
        get value() { return Math.max(0, parseFloat(this.amount) || 0) },
        get next() { return this.dir === 'deduct' ? this.balance - this.value : this.balance + this.value },
        get tooMuch() { return this.dir === 'deduct' && this.value > this.balance },
        money(value) { return Number(value).toLocaleString(undefined, { minimumFractionDigits: this.decimals, maximumFractionDigits: this.decimals }) },
        fill(value) { this.$refs.amount.value = value; this.$refs.amount.dispatchEvent(new Event('input', { bubbles: true })); },
    }"
    x-on:student-topup-saved.window="amount = ''; dir = @js($direction); bootstrap.Modal.getOrCreateInstance($el).hide()">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form wire:submit="record" autocomplete="off">
                <div class="mh" :class="dir === 'deduct' ? 'out' : 'in'">
                    <span class="mi"><i class="fa" :class="dir === 'deduct' ? 'fa-arrow-up' : 'fa-arrow-down'"></i></span>
                    <div class="min-w-0">
                        <h5 id="StudentTopupModalTitle" x-text="dir === 'deduct' ? 'Take money off the card' : 'Add money to the card'">Add money to the card</h5>
                        <p>Card balance now <b>{{ currency($balance) }}</b></p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="fl">
                        <span class="lb">Entry</span>
                        <div class="seg" role="radiogroup" aria-label="Entry">
                            @if ($canAdd)
                                <label class="opt in" :class="{ on: dir === 'add' }">
                                    <input type="radio" class="visually-hidden" name="tp_direction" value="add" wire:model="direction"
                                        x-on:change="dir = 'add'" @checked($direction !== 'deduct')>
                                    <span class="ic"><i class="fa fa-arrow-down"></i></span>
                                    <span class="tx">
                                        <b>Add to card</b>
                                        <small>Money taken at the office</small>
                                    </span>
                                    <i class="fa fa-check-circle tk"></i>
                                </label>
                            @endif
                            @if ($canDeduct)
                                <label class="opt out" :class="{ on: dir === 'deduct' }">
                                    <input type="radio" class="visually-hidden" name="tp_direction" value="deduct" wire:model="direction"
                                        x-on:change="dir = 'deduct'" @checked($direction === 'deduct')>
                                    <span class="ic"><i class="fa fa-arrow-up"></i></span>
                                    <span class="tx">
                                        <b>Take off card</b>
                                        <small>Money paid back out</small>
                                    </span>
                                    <i class="fa fa-check-circle tk"></i>
                                </label>
                            @endif
                        </div>
                    </div>

                    <div class="fl">
                        <label class="lb" for="tp_amount">Amount</label>
                        <div class="amt" :class="{ bad: tooMuch }">
                            <input type="number" step="0.01" min="0.01" id="tp_amount" x-ref="amount" wire:model="amount"
                                x-on:input="amount = $event.target.value" placeholder="0{{ $decimals ? '.'.str_repeat('0', $decimals) : '' }}" required>
                            <div class="qk">
                                @foreach ([10, 20, 50, 100] as $quick)
                                    <button type="button" x-on:click="fill({{ $quick }})">{{ $quick }}</button>
                                @endforeach
                            </div>
                        </div>
                        <div class="hint" x-show="value > 0" x-cloak>
                            <template x-if="!tooMuch">
                                <span>Card balance after this entry <b x-text="money(next)"></b></span>
                            </template>
                            <template x-if="tooMuch">
                                <span class="bad"><i class="fa fa-exclamation-triangle me-1"></i>Only <b x-text="money(balance)"></b> is on the card — an office entry cannot take it below zero.</span>
                            </template>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-7">
                            <div class="fl">
                                <label class="lb" for="tp_method" x-text="dir === 'deduct' ? 'Paid out from' : 'Received in'">Received in</label>
                                <select id="tp_method" class="form-select" wire:model="payment_account_id" required>
                                    <option value="">Choose…</option>
                                    @foreach ($paymentMethods as $method)
                                        <option value="{{ $method->id }}">{{ $method->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-5">
                            <div class="fl">
                                <label class="lb" for="tp_date">Date</label>
                                <input type="date" id="tp_date" class="form-control" wire:model="date" max="{{ date('Y-m-d') }}">
                            </div>
                        </div>
                    </div>

                    <div class="fl">
                        <label class="lb" for="tp_reason">Reason</label>
                        <input type="text" id="tp_reason" x-ref="reason" class="form-control" wire:model="reason" maxlength="255" required
                            :placeholder="dir === 'deduct' ? 'e.g. balance paid back to parent' : 'e.g. cash from parent at the office'">
                        <div class="sug">
                            @foreach ($suggestions as $key => $options)
                                @foreach ($options as $option)
                                    <button type="button" x-show="dir === '{{ $key }}'" x-cloak
                                        x-on:click="$refs.reason.value = @js($option); $refs.reason.dispatchEvent(new Event('input', { bubbles: true }))">{{ $option }}</button>
                                @endforeach
                            @endforeach
                        </div>
                    </div>

                    <p class="note">
                        <i class="fa fa-info-circle me-1"></i>Posted to the books straight away: the money lands in the account you choose and the
                        student's card balance moves by the same amount.
                    </p>
                </div>

                <div class="mf">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" :disabled="tooMuch" wire:loading.attr="disabled" wire:target="record">
                        <span wire:loading.remove wire:target="record">
                            <i class="fa fa-check me-1"></i><span x-text="dir === 'deduct' ? 'Take off card' : 'Add to card'">Add to card</span>
                        </span>
                        <span wire:loading wire:target="record"><i class="fa fa-spinner fa-spin me-1"></i>Saving…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

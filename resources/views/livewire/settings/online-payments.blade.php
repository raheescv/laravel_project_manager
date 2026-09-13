<div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white py-2 d-flex align-items-center justify-content-between">
        <h5 class="mb-0 text-white"><i class="fa fa-credit-card me-1"></i> Online Payments</h5>
        @if ($saved_key_hint)
            <span class="badge {{ $live_mode ? 'bg-success' : 'bg-warning text-dark' }}">{{ $live_mode ? 'Live' : 'Test mode' }}</span>
        @else
            <span class="badge bg-light text-primary">Tap Payments</span>
        @endif
    </div>

    <form wire:submit="save">
        <div class="card-body p-3">
            <p class="small text-body-secondary mb-3">
                Let customers pay for their bag on the public storefront through Tap's secure payment page. A paid order
                becomes a completed sale: stock is deducted from the shop and the payment is posted to the payment method
                chosen below.
            </p>

            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" role="switch" id="op_enabled" wire:model="enabled">
                <label class="form-check-label fw-medium" for="op_enabled">Accept online payments on the storefront</label>
            </div>

            <div class="row g-3">
                <div class="col-12 col-lg-7">
                    <label class="form-label fw-medium small mb-1" for="op_secret_key">Secret key</label>
                    <input type="password" id="op_secret_key" class="form-control form-control-sm" wire:model="secret_key"
                        autocomplete="new-password" spellcheck="false"
                        placeholder="{{ $saved_key_hint ? 'Saved (' . $saved_key_hint . ') — leave blank to keep it' : 'sk_test_… or sk_live_…' }}">
                    <div class="form-text">
                        From the API keys page of your Tap dashboard. Stored encrypted and never sent to the storefront.
                        A <code>sk_test_</code> key only takes test payments.
                        @if ($saved_key_hint)
                            <button type="button" class="btn btn-link btn-sm text-danger p-0 align-baseline" wire:click="removeSecretKey"
                                wire:confirm="Remove the saved key? Online payments will be switched off.">Remove saved key</button>
                        @endif
                    </div>
                </div>
                <div class="col-12 col-lg-5">
                    <label class="form-label fw-medium small mb-1" for="op_merchant_id">
                        Merchant ID <span class="text-body-secondary fw-normal">(optional)</span>
                    </label>
                    <input type="text" id="op_merchant_id" class="form-control form-control-sm" wire:model="merchant_id" maxlength="50">
                    <div class="form-text">Only needed when your Tap account holds more than one merchant.</div>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label fw-medium small mb-1" for="op_payment_account_id">Record payments in</label>
                    <select id="op_payment_account_id" class="form-select form-select-sm" wire:model="payment_account_id">
                        <option value="">Choose a payment method…</option>
                        @foreach ($paymentAccounts as $account)
                            <option value="{{ $account->id }}">{{ $account->name }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">
                        @if ($paymentAccounts->isEmpty())
                            No payment methods are configured yet — add one under Configuration first.
                        @else
                            A dedicated Tap account keeps online takings apart from card and cash.
                        @endif
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-medium small mb-1" for="op_user_id">Record sales as</label>
                    <select id="op_user_id" class="form-select form-select-sm" wire:model="user_id">
                        <option value="">Choose a user…</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">Shown as the creator and salesperson on online sales.</div>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-medium small mb-1" for="op_delivery_branch_id">Delivery orders ship from</label>
                    <select id="op_delivery_branch_id" class="form-select form-select-sm" wire:model="delivery_branch_id">
                        <option value="">No delivery — collect in shop only</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">Collect-in-shop orders use the stock of the shop the customer picks.</div>
                </div>
            </div>
        </div>

        <div class="card-footer d-flex justify-content-end">
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save"><i class="fa fa-save me-1"></i> Save</span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>
        </div>
    </form>
</div>

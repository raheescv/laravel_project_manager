<div class="scx">
    @include('livewire.settings.partials.panel-styles')

    <form wire:submit="save" x-show="pane === 'qpay'" x-cloak>
        <div class="scx-body">
            <div class="sct-pane-head">
                <div>
                    <h6>Debit card top-ups</h6>
                    <p>QPay · QCB EZ-Connect — parents pay with a Qatar debit card.</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="scx-pill {{ $environment === 'production' ? 'is-live' : 'is-test' }}">{{ $environment === 'production' ? 'Production' : 'Staging' }}</span>
                    <label class="form-switch scx-switch" for="qp_enabled">
                        Offer to parents
                        <input class="form-check-input" type="checkbox" role="switch" id="qp_enabled" wire:model="enabled">
                    </label>
                </div>
            </div>
            <div class="scx-note mb-3 small">
                <i class="fa fa-info-circle"></i>
                <span>Your acquiring bank provides the Bank ID and Merchant ID; the secret key comes from the QPay merchant portal. The server's public IP must be whitelisted by QPay.</span>
            </div>

            <div class="scx-sub">Connection</div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="qp_environment">Environment</label>
                    <select id="qp_environment" class="form-select" wire:model.live="environment">
                        <option value="staging">Staging (test cards)</option>
                        <option value="production">Production (real payments)</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="qp_bank_id">Bank ID</label>
                    <input type="text" id="qp_bank_id" class="form-control" wire:model="bank_id" maxlength="10">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="qp_merchant_id">Merchant ID</label>
                    <input type="text" id="qp_merchant_id" class="form-control" wire:model="merchant_id" maxlength="10">
                </div>
                <div class="col-12">
                    <label class="form-label" for="qp_secret_key">Secret key</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa fa-key"></i></span>
                        <input type="password" id="qp_secret_key" class="form-control" wire:model="secret_key" autocomplete="new-password" spellcheck="false"
                            placeholder="{{ $saved_key_hint ? 'Saved (' . $saved_key_hint . ') — leave blank to keep it' : 'From QPay merchant portal → Manage Secret Key' }}">
                    </div>
                    <div class="form-text">Stored encrypted. If you regenerate the key in the merchant portal, paste the new one here straight away.</div>
                </div>
            </div>

            <div class="scx-sub mt-4">Booking</div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="qp_payment_account_id">Paid into</label>
                    <select id="qp_payment_account_id" class="form-select" wire:model="payment_account_id">
                        <option value="">Choose a payment method…</option>
                        @foreach ($paymentAccounts as $account)
                            <option value="{{ $account->id }}">{{ $account->name }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">The bank account QPay settles to. Booked Dr this account / Cr the student.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="qp_user_id">Record top-ups as</label>
                    <select id="qp_user_id" class="form-select" wire:model="user_id">
                        <option value="">Choose a user…</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">Top-up journals are booked in this user's default branch.</div>
                </div>
            </div>
        </div>

        <div class="scx-foot">
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save"><i class="fa fa-save me-1"></i> Save</span>
                <span wire:loading wire:target="save"><i class="fa fa-spinner fa-spin me-1"></i> Saving…</span>
            </button>
        </div>
    </form>
</div>

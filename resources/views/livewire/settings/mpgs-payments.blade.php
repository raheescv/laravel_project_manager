<div class="card shadow-sm border-0 mt-3">
    <div class="card-header bg-primary text-white py-2 d-flex align-items-center justify-content-between">
        <h5 class="mb-0 text-white"><i class="fa fa-credit-card me-1"></i> Credit card top-ups <span class="fw-normal opacity-75">· Mastercard Gateway (MPGS)</span></h5>
        @if (trim($merchant_id) !== '')
            <span class="badge {{ $isTest ? 'bg-warning text-dark' : 'bg-success' }}">{{ $isTest ? 'Test merchant' : 'Live merchant' }}</span>
        @endif
    </div>
    <form wire:submit="save">
        <div class="card-body p-3">
            <p class="small text-body-secondary mb-3">
                Parents who choose <strong>Credit card</strong> on the top-up page pay with Visa or Mastercard on the bank's Mastercard Gateway page.
                Your acquiring bank gives you the gateway address and Merchant ID; the API password is generated in the gateway's
                Merchant Administration → Admin → Integration Settings. A Merchant ID starting with <code>TEST</code> is the bank's test system — no real card is charged.
            </p>
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" role="switch" id="mp_enabled" wire:model="enabled">
                <label class="form-check-label fw-medium" for="mp_enabled">Offer credit card top-ups to parents</label>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-medium small mb-1" for="mp_gateway_url">Gateway address</label>
                    <input type="url" id="mp_gateway_url" class="form-control form-control-sm" wire:model="gateway_url" spellcheck="false"
                        placeholder="{{ \App\Support\Payment\MpgsSettings::DEFAULT_GATEWAY_URL }}">
                    <div class="form-text">From the bank — the test address until you go live.</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-medium small mb-1" for="mp_merchant_id">Merchant ID</label>
                    <input type="text" id="mp_merchant_id" class="form-control form-control-sm" wire:model.live.debounce.400ms="merchant_id" maxlength="40" spellcheck="false">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-medium small mb-1" for="mp_merchant_name">Name on the payment page</label>
                    <input type="text" id="mp_merchant_name" class="form-control form-control-sm" wire:model="merchant_name" maxlength="40">
                </div>
                <div class="col-12">
                    <label class="form-label fw-medium small mb-1" for="mp_api_password">API password</label>
                    <input type="password" id="mp_api_password" class="form-control form-control-sm" wire:model="api_password" autocomplete="new-password" spellcheck="false"
                        placeholder="{{ $saved_password_hint ? 'Saved (' . $saved_password_hint . ') — leave blank to keep it' : 'From Merchant Administration → Admin → Integration Settings' }}">
                    <div class="form-text">Stored encrypted. If you generate a new password in Merchant Administration, paste it here straight away.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-medium small mb-1" for="mp_payment_account_id">Credit card top-ups are paid into</label>
                    <select id="mp_payment_account_id" class="form-select form-select-sm" wire:model="payment_account_id">
                        <option value="">Choose a payment method…</option>
                        @foreach ($paymentAccounts as $account)
                            <option value="{{ $account->id }}">{{ $account->name }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">The bank account the card gateway settles to. A top-up is booked Dr this account / Cr the student.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-medium small mb-1" for="mp_user_id">Record top-ups as</label>
                    <select id="mp_user_id" class="form-select form-select-sm" wire:model="user_id">
                        <option value="">Choose a user…</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">Top-up journals are booked in this user's default branch.</div>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-outline-secondary" wire:click="testConnection" wire:loading.attr="disabled" title="Sign in to the gateway with the saved Merchant ID and API password">
                <span wire:loading.remove wire:target="testConnection"><i class="fa fa-plug me-1"></i> Test connection</span>
                <span wire:loading wire:target="testConnection">Connecting…</span>
            </button>
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save"><i class="fa fa-save me-1"></i> Save</span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>
        </div>
    </form>
</div>

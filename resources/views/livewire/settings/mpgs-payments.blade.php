<div class="scx">
    @include('livewire.settings.partials.panel-styles')

    <form wire:submit="save" x-show="pane === 'mpgs'" x-cloak>
        <div class="scx-body">
            <div class="sct-pane-head">
                <div>
                    <h6>Credit card top-ups</h6>
                    <p>Mastercard Gateway (MPGS) — parents pay with Visa or Mastercard on the bank's page.</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    @if (trim($merchant_id) !== '')
                        <span class="scx-pill {{ $isTest ? 'is-test' : 'is-live' }}">{{ $isTest ? 'Test merchant' : 'Live merchant' }}</span>
                    @endif
                    <label class="form-switch scx-switch" for="mp_enabled">
                        Offer to parents
                        <input class="form-check-input" type="checkbox" role="switch" id="mp_enabled" wire:model="enabled">
                    </label>
                </div>
            </div>
            <div class="scx-note mb-3 small">
                <i class="fa fa-info-circle"></i>
                <span>Your acquiring bank gives you the gateway address and Merchant ID; the API password is generated in Merchant Administration → Admin → Integration Settings. A Merchant ID starting with <code>TEST</code> is the bank's test system — no real card is charged.</span>
            </div>

            <div class="scx-sub">Connection</div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="mp_gateway_url">Gateway address</label>
                    <input type="url" id="mp_gateway_url" class="form-control" wire:model="gateway_url" spellcheck="false"
                        placeholder="{{ \App\Support\Payment\MpgsSettings::DEFAULT_GATEWAY_URL }}">
                    <div class="form-text">From the bank — the test address until you go live.</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="mp_merchant_id">Merchant ID</label>
                    <input type="text" id="mp_merchant_id" class="form-control" wire:model.live.debounce.400ms="merchant_id" maxlength="40" spellcheck="false">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="mp_merchant_name">Name on payment page</label>
                    <input type="text" id="mp_merchant_name" class="form-control" wire:model="merchant_name" maxlength="40">
                </div>
                <div class="col-12">
                    <label class="form-label" for="mp_api_password">API password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa fa-key"></i></span>
                        <input type="password" id="mp_api_password" class="form-control" wire:model="api_password" autocomplete="new-password" spellcheck="false"
                            placeholder="{{ $saved_password_hint ? 'Saved (' . $saved_password_hint . ') — leave blank to keep it' : 'From Merchant Administration → Admin → Integration Settings' }}">
                    </div>
                    <div class="form-text">Stored encrypted. If you generate a new password in Merchant Administration, paste it here straight away.</div>
                </div>
            </div>

            <div class="scx-sub mt-4">Webhook <span class="text-lowercase fw-normal">(optional)</span></div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="mp_notification_secret">Notification secret</label>
                    <input type="password" id="mp_notification_secret" class="form-control" wire:model="notification_secret" autocomplete="new-password" spellcheck="false"
                        placeholder="{{ $saved_notification_secret_hint ? 'Saved (' . $saved_notification_secret_hint . ') — leave blank to keep it' : 'From Merchant Administration → Admin → Webhook Notifications' }}">
                    <div class="form-text">Lets the gateway confirm each payment directly, so a top-up is credited even if the parent closes the page early.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="mp_notification_url">Webhook address</label>
                    <div class="input-group">
                        <input type="text" id="mp_notification_url" class="form-control bg-body-tertiary" value="{{ $notificationUrl }}" readonly onclick="this.select()">
                        <button type="button" class="btn btn-outline-secondary" title="Copy" onclick="navigator.clipboard?.writeText(document.getElementById('mp_notification_url').value); toastr.success('Copied')">
                            <i class="fa fa-files-o"></i>
                        </button>
                    </div>
                    <div class="form-text">Sent with every payment. Give it to the bank if they ask for the notification URL.</div>
                </div>
            </div>

            <div class="scx-sub mt-4">Booking</div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="mp_payment_account_id">Paid into</label>
                    <select id="mp_payment_account_id" class="form-select" wire:model="payment_account_id">
                        <option value="">Choose a payment method…</option>
                        @foreach ($paymentAccounts as $account)
                            <option value="{{ $account->id }}">{{ $account->name }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">The bank account the card gateway settles to. Booked Dr this account / Cr the student.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="mp_user_id">Record top-ups as</label>
                    <select id="mp_user_id" class="form-select" wire:model="user_id">
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
            <button type="button" class="btn btn-outline-secondary" wire:click="testConnection" wire:loading.attr="disabled" wire:target="testConnection" title="Sign in to the gateway with the saved Merchant ID and API password">
                <span wire:loading.remove wire:target="testConnection"><i class="fa fa-plug me-1"></i> Test connection</span>
                <span wire:loading wire:target="testConnection"><i class="fa fa-spinner fa-spin me-1"></i> Connecting…</span>
            </button>
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save"><i class="fa fa-save me-1"></i> Save</span>
                <span wire:loading wire:target="save"><i class="fa fa-spinner fa-spin me-1"></i> Saving…</span>
            </button>
        </div>
    </form>
</div>

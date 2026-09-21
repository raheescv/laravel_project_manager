<div class="card shadow-sm border-0 mt-3">
    <div class="card-header bg-primary text-white py-2 d-flex align-items-center justify-content-between">
        <h5 class="mb-0 text-white"><i class="fa fa-globe me-1"></i> Debit card top-ups <span class="fw-normal opacity-75">· QPay</span></h5>
        <span class="badge {{ $environment === 'production' ? 'bg-success' : 'bg-warning text-dark' }}">{{ $environment === 'production' ? 'Production' : 'Staging' }}</span>
    </div>
    <form wire:submit="save">
        <div class="card-body p-3">
            <p class="small text-body-secondary mb-3">
                Parents who choose <strong>Debit card</strong> on the top-up page pay with a Qatar debit card through QPay (QCB EZ-Connect).
                Your acquiring bank provides the Bank ID and Merchant ID; the secret key comes from the QPay merchant portal.
                The server's public IP must be whitelisted by QPay.
            </p>
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" role="switch" id="qp_enabled" wire:model="enabled">
                <label class="form-check-label fw-medium" for="qp_enabled">Offer debit card top-ups to parents</label>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-medium small mb-1" for="qp_environment">Environment</label>
                    <select id="qp_environment" class="form-select form-select-sm" wire:model.live="environment">
                        <option value="staging">Staging (test cards)</option>
                        <option value="production">Production (real payments)</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-medium small mb-1" for="qp_bank_id">Bank ID</label>
                    <input type="text" id="qp_bank_id" class="form-control form-control-sm" wire:model="bank_id" maxlength="10">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-medium small mb-1" for="qp_merchant_id">Merchant ID</label>
                    <input type="text" id="qp_merchant_id" class="form-control form-control-sm" wire:model="merchant_id" maxlength="10">
                </div>
                <div class="col-12">
                    <label class="form-label fw-medium small mb-1" for="qp_secret_key">Secret key</label>
                    <input type="password" id="qp_secret_key" class="form-control form-control-sm" wire:model="secret_key" autocomplete="new-password" spellcheck="false"
                        placeholder="{{ $saved_key_hint ? 'Saved (' . $saved_key_hint . ') — leave blank to keep it' : 'From QPay merchant portal → Manage Secret Key' }}">
                    <div class="form-text">Stored encrypted. If you regenerate the key in the merchant portal, paste the new one here straight away.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-medium small mb-1" for="qp_payment_account_id">Debit card top-ups are paid into</label>
                    <select id="qp_payment_account_id" class="form-select form-select-sm" wire:model="payment_account_id">
                        <option value="">Choose a payment method…</option>
                        @foreach ($paymentAccounts as $account)
                            <option value="{{ $account->id }}">{{ $account->name }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">The bank account QPay settles to. A top-up is booked Dr this account / Cr the student.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-medium small mb-1" for="qp_user_id">Record top-ups as</label>
                    <select id="qp_user_id" class="form-select form-select-sm" wire:model="user_id">
                        <option value="">Choose a user…</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">Top-up journals are booked in this user's default branch.</div>
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

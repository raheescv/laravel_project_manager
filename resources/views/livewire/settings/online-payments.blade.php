@php
    $tapStatus = match (true) {
        ! $saved_key_hint => ['class' => 'is-off', 'text' => 'Not connected'],
        $live_mode => ['class' => 'is-live', 'text' => 'Live'],
        default => ['class' => 'is-test', 'text' => 'Test mode'],
    };
@endphp

<div class="scx">
    @include('livewire.settings.partials.panel-styles')

    <style>
        .opx-provider {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 1rem 1.25rem;
            padding: 1.1rem 1.2rem;
            border: 1px solid var(--scx-line-soft);
            border-radius: 16px;
            background:
                radial-gradient(120% 160% at 0% 0%, color-mix(in srgb, var(--scx-acc) 12%, transparent), transparent 60%),
                var(--scx-surface-2);
        }

        .opx-logo {
            display: grid;
            place-items: center;
            flex: none;
            width: 52px;
            height: 52px;
            border-radius: 15px;
            background: linear-gradient(135deg, color-mix(in srgb, var(--scx-acc) 80%, #fff), var(--scx-acc) 45%, color-mix(in srgb, var(--scx-acc) 70%, #000));
            color: #fff;
            font-size: 1.3rem;
            box-shadow: 0 10px 22px -12px color-mix(in srgb, var(--scx-acc) 80%, transparent);
        }

        .opx-provider-text {
            flex: 1 1 280px;
            min-width: 0;
        }

        .opx-kicker {
            font-size: .66rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--bs-secondary-color);
        }

        .opx-provider h6 {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .5rem;
            margin: .1rem 0 .2rem;
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--bs-emphasis-color);
        }

        .opx-provider p {
            margin: 0;
            font-size: .78rem;
            color: var(--bs-secondary-color);
        }

        .opx-flow {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .6rem;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .opx-flow li {
            display: flex;
            align-items: center;
            gap: .6rem;
            padding: .65rem .8rem;
            border: 1px solid var(--scx-line-soft);
            border-radius: 12px;
            background: var(--scx-surface);
            font-size: .76rem;
            line-height: 1.35;
            color: var(--bs-body-color);
        }

        .opx-flow .scx-ic {
            width: 30px;
            height: 30px;
            border-radius: 9px;
            font-size: .85rem;
        }

        @media (max-width: 767.98px) {
            .opx-flow {
                grid-template-columns: 1fr;
            }
        }
    </style>

    {{-- Which service takes the payments --}}
    <div class="opx-provider">
        <span class="opx-logo"><i class="fa fa-credit-card"></i></span>
        <div class="opx-provider-text">
            <div class="opx-kicker">Payment provider</div>
            <h6>Tap Payments <span class="scx-pill {{ $tapStatus['class'] }}">{{ $tapStatus['text'] }}</span></h6>
            <p>Storefront customers pay on Tap's secure hosted payment page. Card details never touch this system.</p>
        </div>
        <a href="https://www.tap.company" target="_blank" rel="noopener" class="btn btn-light border btn-sm">
            <i class="fa fa-external-link me-1"></i> Tap website
        </a>
        <ol class="opx-flow w-100">
            <li><span class="scx-ic" style="--tone:#2f6fd6"><i class="fa fa-shopping-cart"></i></span><span>Customer checks out the bag and pays on Tap</span></li>
            <li><span class="scx-ic" style="--tone:#2f9e62"><i class="fa fa-check"></i></span><span>The paid order becomes a completed sale, stock deducted</span></li>
            <li><span class="scx-ic" style="--tone:#d4931c"><i class="fa fa-book"></i></span><span>Payment is posted to the payment method you choose below</span></li>
        </ol>
    </div>

    <form wire:submit="save" class="scx-section">
        <div class="scx-head flex-wrap">
            <span class="scx-ic"><i class="fa fa-key"></i></span>
            <div class="flex-grow-1" style="min-width: 12rem;">
                <h6>Connect your Tap account</h6>
                <p>Keys come from the API keys page of your Tap dashboard.</p>
            </div>
            <label class="form-switch scx-switch scx-head-end" for="op_enabled">
                Accept online payments
                <input class="form-check-input" type="checkbox" role="switch" id="op_enabled" wire:model="enabled">
            </label>
        </div>

        <div class="scx-body">
            <div class="row g-3">
                <div class="col-12 col-lg-7">
                    <label class="form-label" for="op_secret_key">Secret key</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa fa-lock"></i></span>
                        <input type="password" id="op_secret_key" class="form-control" wire:model="secret_key"
                            autocomplete="new-password" spellcheck="false"
                            placeholder="{{ $saved_key_hint ? 'Saved (' . $saved_key_hint . ') — leave blank to keep it' : 'sk_test_… or sk_live_…' }}">
                        @if ($saved_key_hint)
                            <button type="button" class="btn btn-outline-danger" wire:click="removeSecretKey"
                                wire:confirm="Remove the saved key? Online payments will be switched off." title="Remove saved key">
                                <i class="fa fa-trash-o"></i>
                            </button>
                        @endif
                    </div>
                    <div class="form-text">Stored encrypted, never sent to the storefront. An <code>sk_test_</code> key takes test payments only; the public key (<code>pk_…</code>) isn't needed.</div>
                </div>
                <div class="col-12 col-lg-5">
                    <label class="form-label" for="op_merchant_id">Merchant ID <span class="text-body-secondary fw-normal">(optional)</span></label>
                    <input type="text" id="op_merchant_id" class="form-control" wire:model="merchant_id" maxlength="50">
                    <div class="form-text">Only needed when your Tap account holds more than one merchant.</div>
                </div>
            </div>

            <div class="scx-sub mt-4">Recording</div>
            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <label class="form-label" for="op_payment_account_id">Record payments in</label>
                    <select id="op_payment_account_id" class="form-select" wire:model="payment_account_id">
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
                    <label class="form-label" for="op_user_id">Record sales as</label>
                    <select id="op_user_id" class="form-select" wire:model="user_id">
                        <option value="">Choose a user…</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">Shown as the creator and salesperson on online sales.</div>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label" for="op_delivery_branch_id">Delivery orders ship from</label>
                    <select id="op_delivery_branch_id" class="form-select" wire:model="delivery_branch_id">
                        <option value="">No delivery — collect in shop only</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">Paid online orders are booked here; stock is transferred in from the shop it came from.</div>
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

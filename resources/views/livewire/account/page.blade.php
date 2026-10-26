@php
    $accountTypeMeta = [
        'asset' => ['icon' => 'fa-cubes', 'accent' => '#10b981'],
        'liability' => ['icon' => 'fa-credit-card', 'accent' => '#f59e0b'],
        'income' => ['icon' => 'fa-line-chart', 'accent' => '#0ea5e9'],
        'expense' => ['icon' => 'fa-shopping-cart', 'accent' => '#ef4444'],
        'equity' => ['icon' => 'fa-university', 'accent' => '#8b5cf6'],
    ];
    $accountTypeAccents = collect($accountTypeMeta)->map(fn ($meta) => $meta['accent'])->all();
@endphp
<div class="acctx"
    x-data="{ selectedType: $wire.entangle('accounts.account_type'), accents: @js($accountTypeAccents), labels: @js(accountTypes()) }"
    :style="'--acctx-sel: ' + (accents[selectedType] ?? 'var(--bs-primary)')">
    <div class="acctx-head">
        <div>
            <h3 class="acctx-title">{{ $table_id ? 'Edit Account' : 'Create Account' }}</h3>
            <div class="acctx-crumb">Chart of accounts</div>
        </div>
        <span class="acctx-pill" x-show="selectedType" x-text="labels[selectedType] ?? ''" x-cloak></span>
        <button type="button" class="acctx-close" data-bs-dismiss="modal" aria-label="Close"><i class="fa fa-times"></i></button>
    </div>

    <form wire:submit="save">
        <div class="acctx-rows">
            @if ($this->getErrorBag()->count())
                <div class="acctx-alert" role="alert">
                    <i class="fa fa-exclamation-circle"></i>
                    <ul>
                        @foreach ($this->getErrorBag()->toArray() as $value)
                            <li>{{ $value[0] }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (!$type_selection_freeze)
                <div class="acctx-row acctx-row--top">
                    <label class="acctx-label">Type <span class="acctx-req">*</span></label>
                    <div>
                        <div class="acctx-types">
                            @foreach (accountTypes() as $key => $label)
                                @php $meta = $accountTypeMeta[$key] ?? ['icon' => 'fa-circle', 'accent' => 'var(--bs-primary)']; @endphp
                                <label class="acctx-type" style="--acc: {{ $meta['accent'] }};">
                                    <input type="radio" wire:model="accounts.account_type" value="{{ $key }}"
                                        x-on:change="selectedType = $event.target.value; if (selectedType !== 'asset') { $wire.set('accounts.is_cheque', false) }">
                                    <i class="fa {{ $meta['icon'] }}"></i> {{ $label }}
                                </label>
                            @endforeach
                        </div>
                        @error('accounts.account_type')
                            <div class="acctx-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            @endif

            <div class="acctx-row">
                <label for="modal_account_name" class="acctx-label">Name <span class="acctx-req">*</span></label>
                <div>
                    {{ html()->input('name')->value('')->class('acctx-input' . ($errors->has('accounts.name') ? ' is-invalid' : ''))->attribute('wire:model', 'accounts.name')->id('modal_account_name')->placeholder('Enter account name') }}
                    @error('accounts.name')
                        <div class="acctx-error">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="acctx-row">
                <label for="modal_account_alias" class="acctx-label">Alias</label>
                <div>
                    {{ html()->input('alias_name')->value('')->class('acctx-input' . ($errors->has('accounts.alias_name') ? ' is-invalid' : ''))->attribute('wire:model', 'accounts.alias_name')->id('modal_account_alias')->placeholder('Optional') }}
                    @error('accounts.alias_name')
                        <div class="acctx-error">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="acctx-row">
                <label for="modal_account_category_id" class="acctx-label">Category</label>
                <div class="acctx-select" wire:ignore>
                    {{ html()->select('account_category_id', $accountCategories ?? [])->value(old('account_category_id', $accounts['account_category_id'] ?? ''))->class('select-account_category_id')->id('modal_account_category_id')->placeholder('Select account category')->attribute('wire:model.live', 'accounts.account_category_id') }}
                </div>
            </div>

            <div class="acctx-row">
                <label for="opening_debit" class="acctx-label">Opening balance</label>
                <div>
                    <div class="acctx-pair">
                        <div class="acctx-amt acctx-amt--dr @error('accounts.opening_debit') is-invalid @enderror">
                            <b>DR</b>
                            {{ html()->input('opening_debit')->type('number')->value('')->attribute('step', 'any')->attribute('wire:model', 'accounts.opening_debit')->placeholder('0.00')->id('opening_debit') }}
                        </div>
                        <div class="acctx-amt acctx-amt--cr @error('accounts.opening_credit') is-invalid @enderror">
                            <b>CR</b>
                            {{ html()->input('opening_credit')->type('number')->value('')->attribute('step', 'any')->attribute('wire:model', 'accounts.opening_credit')->placeholder('0.00')->id('opening_credit') }}
                        </div>
                    </div>
                    @error('accounts.opening_debit')
                        <div class="acctx-error">{{ $message }}</div>
                    @enderror
                    @error('accounts.opening_credit')
                        <div class="acctx-error">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="acctx-row" x-show="selectedType === 'asset'" x-cloak>
                <label for="account_is_cheque" class="acctx-label">Cheque Account</label>
                <label class="acctx-toggle" for="account_is_cheque">
                    <input type="checkbox" role="switch" id="account_is_cheque" wire:model="accounts.is_cheque">
                    <span class="acctx-switch" aria-hidden="true"></span>
                    <span class="acctx-toggle-hint">Ask for bank name &amp; cheque no. on payment</span>
                </label>
            </div>

            <div class="acctx-row acctx-row--top">
                <label for="modal_account_description" class="acctx-label">Description</label>
                {{ html()->textarea('description')->value('')->class('acctx-input')->attribute('wire:model', 'accounts.description')->attribute('rows', '2')->id('modal_account_description')->placeholder('Optional') }}
            </div>
        </div>

        <div class="acctx-foot">
            <span class="acctx-help"><i class="fa fa-keyboard-o"></i> Enter to save</span>
            <button type="button" class="acctx-btn acctx-btn--ghost" data-bs-dismiss="modal">Close</button>
            <button type="button" wire:click="save(1)" class="acctx-btn acctx-btn--line">
                <i class="fa fa-plus"></i> Save &amp; New
            </button>
            <button type="submit" class="acctx-btn acctx-btn--primary">
                <span wire:loading.remove wire:target="save"><i class="fa fa-check"></i> Save</span>
                <span wire:loading wire:target="save"><i class="fa fa-spinner fa-spin"></i> Saving…</span>
            </button>
        </div>
    </form>
    @push('scripts')
        <script>
            $(document).ready(function() {
                window.addEventListener('AddToAccountSelectBox', event => {
                    var data = event.detail[0];
                    if (data['id']) {
                        var preselectedData = {
                            id: data['id'],
                            name: data['name'],
                        };
                        document.querySelectorAll('.select-account_id').forEach(function(element) {
                            var tomSelectInstance = element.tomselect;
                            if (tomSelectInstance) {
                                tomSelectInstance.addOption(preselectedData);
                                tomSelectInstance.addItem(data['id']);
                            }
                        });
                    }
                });
                $('#modal_account_category_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('accounts.account_category_id', value);
                });
                window.addEventListener('SelectDropDownValues', event => {
                    var data = event.detail[0];
                    var accountCategoryTomSelectInstance = document.querySelector('#modal_account_category_id').tomselect;
                    if (!data || !data.account_category_id) {
                        if (accountCategoryTomSelectInstance) {
                            accountCategoryTomSelectInstance.clear(true);
                        }
                        return;
                    }
                    @this.set('accounts.account_category_id', data.account_category_id);
                    if (accountCategoryTomSelectInstance && data.account_category) {
                        var preselectedData = {
                            id: data.account_category_id,
                            name: data.account_category['name'],
                        };
                        accountCategoryTomSelectInstance.addOption(preselectedData);
                        accountCategoryTomSelectInstance.addItem(preselectedData.id);
                    }
                });
            });
        </script>
    @endpush

    <style>
        /* ════════════  Account form — "Compact Flow" (scoped under .acctx)  ════════════
           Label-left rows like the Expense/Income ".exx" flow. Colour tracks the settings theme (--bs-primary);
           the top rule + header pill pick up the selected account type's accent (--acctx-sel). */
        .acctx [x-cloak] { display: none !important; }
        .acctx {
            --acctx-sel: var(--bs-primary);
            --acctx-bg: var(--bs-body-bg, #fff);
            --acctx-soft: var(--bs-tertiary-bg, #f5f7fa);
            --acctx-line: var(--bs-border-color, #e6eaf0);
            --acctx-ink: var(--bs-body-color, #111827);
            --acctx-ink-2: var(--bs-secondary-color, #556274);
            --acctx-mute: color-mix(in srgb, var(--acctx-ink-2) 70%, transparent);
            --acctx-ring: color-mix(in srgb, var(--bs-primary) 16%, transparent);
            --acctx-fz: 13px;
            font-size: var(--acctx-fz);
            color: var(--acctx-ink);
            background: var(--acctx-bg);
            border-top: 3px solid var(--acctx-sel);
            border-radius: inherit;
            transition: border-color .2s ease;
        }

        .acctx-head { display: flex; align-items: center; gap: 10px; padding: 14px 18px; border-bottom: 1px solid var(--acctx-line); }
        .acctx-title { margin: 0; font-size: 15px; font-weight: 700; line-height: 1.25; }
        .acctx-crumb { font-size: 11.5px; color: var(--acctx-mute); }
        .acctx-pill {
            font-size: 11px; font-weight: 600; padding: 3px 9px; border-radius: 20px;
            color: var(--acctx-sel); background: color-mix(in srgb, var(--acctx-sel) 12%, transparent);
        }
        .acctx-close {
            margin-inline-start: auto; border: 0; background: transparent; color: var(--acctx-mute);
            padding: 4px 7px; border-radius: 6px; cursor: pointer;
        }
        .acctx-close:hover { color: var(--acctx-ink); background: var(--acctx-soft); }

        .acctx-rows { padding: 6px 0; max-height: 72vh; overflow-y: auto; }
        .acctx-row { display: grid; grid-template-columns: 132px minmax(0, 1fr); align-items: center; gap: 12px; padding: 8px 18px; }
        .acctx-row + .acctx-row { border-top: 1px solid color-mix(in srgb, var(--acctx-line) 60%, transparent); }
        .acctx-row--top { align-items: start; }
        .acctx-row--top > .acctx-label { padding-top: 8px; }
        .acctx-label { margin: 0; font-size: 12px; font-weight: 600; color: var(--acctx-ink-2); }
        .acctx-req { color: var(--bs-danger); }

        .acctx-types { display: flex; flex-wrap: wrap; gap: 6px; }
        .acctx-type {
            position: relative; display: inline-flex; align-items: center; gap: 6px; margin: 0; cursor: pointer;
            padding: 5px 10px; border-radius: 8px; border: 1px solid var(--acctx-line);
            font-size: 12px; font-weight: 600; color: var(--acctx-ink-2); transition: all .12s ease;
        }
        .acctx-type input { position: absolute; opacity: 0; pointer-events: none; }
        .acctx-type i { color: var(--acc); }
        .acctx-type:hover { border-color: var(--acc); }
        .acctx-type:has(input:checked) { background: var(--acc); border-color: var(--acc); color: #fff; }
        .acctx-type:has(input:checked) i { color: #fff; }
        .acctx-type:has(input:focus-visible) { box-shadow: 0 0 0 3px color-mix(in srgb, var(--acc) 30%, transparent); }

        .acctx-input {
            width: 100%; height: 34px; padding: 0 10px; border: 1px solid var(--acctx-line); border-radius: 8px;
            background: var(--acctx-bg); color: var(--acctx-ink); font: inherit; outline: none;
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        .acctx-input::placeholder { color: var(--acctx-mute); }
        .acctx-input:focus { border-color: var(--bs-primary); box-shadow: 0 0 0 3px var(--acctx-ring); }
        .acctx-input.is-invalid { border-color: var(--bs-danger); }
        textarea.acctx-input { height: auto; min-height: 56px; padding: 8px 10px; resize: vertical; }

        .acctx-select .ts-wrapper { width: 100%; }
        .acctx-select .ts-control {
            min-height: 34px; padding: 5px 10px; border: 1px solid var(--acctx-line) !important; border-radius: 8px !important;
            background: var(--acctx-bg) !important; color: var(--acctx-ink); box-shadow: none !important; font-size: var(--acctx-fz);
        }
        .acctx-select .ts-wrapper.focus .ts-control { border-color: var(--bs-primary) !important; box-shadow: 0 0 0 3px var(--acctx-ring) !important; }
        .acctx-select .ts-control input { font-size: var(--acctx-fz); color: var(--acctx-ink); }
        .acctx-select .ts-control input::placeholder { color: var(--acctx-mute); }
        .acctx-select .ts-dropdown { z-index: 1080; font-size: var(--acctx-fz); }

        .acctx-pair { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .acctx-amt {
            display: flex; align-items: center; height: 34px; overflow: hidden;
            border: 1px solid var(--acctx-line); border-radius: 8px; background: var(--acctx-bg);
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        .acctx-amt:focus-within { border-color: var(--bs-primary); box-shadow: 0 0 0 3px var(--acctx-ring); }
        .acctx-amt.is-invalid { border-color: var(--bs-danger); }
        .acctx-amt b { display: grid; place-items: center; height: 100%; padding: 0 9px; font-size: 10.5px; letter-spacing: .05em; }
        .acctx-amt--dr b { color: #059669; background: color-mix(in srgb, #10b981 10%, transparent); }
        .acctx-amt--cr b { color: #dc2626; background: color-mix(in srgb, #ef4444 10%, transparent); }
        .acctx-amt input {
            flex: 1; min-width: 0; height: 100%; padding: 0 10px; border: 0; outline: none; background: transparent;
            color: var(--acctx-ink); font: inherit; font-weight: 600; text-align: end; font-variant-numeric: tabular-nums;
            -moz-appearance: textfield;
        }
        .acctx-amt input::-webkit-outer-spin-button,
        .acctx-amt input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }

        .acctx-toggle { position: relative; display: flex; align-items: center; gap: 10px; margin: 0; cursor: pointer; }
        .acctx-toggle input { position: absolute; opacity: 0; pointer-events: none; }
        .acctx-switch {
            position: relative; flex: none; width: 32px; height: 18px; border-radius: 20px;
            background: color-mix(in srgb, var(--acctx-ink-2) 30%, transparent); transition: background .2s ease;
        }
        .acctx-switch::after {
            content: ""; position: absolute; top: 2px; inset-inline-start: 2px; width: 14px; height: 14px;
            border-radius: 50%; background: #fff; box-shadow: 0 1px 2px rgba(0, 0, 0, .2); transition: inset-inline-start .2s ease;
        }
        .acctx-toggle:has(input:checked) .acctx-switch { background: var(--bs-primary); }
        .acctx-toggle:has(input:checked) .acctx-switch::after { inset-inline-start: 16px; }
        .acctx-toggle:has(input:focus-visible) .acctx-switch { box-shadow: 0 0 0 3px var(--acctx-ring); }
        .acctx-toggle-hint { font-size: 12px; color: var(--acctx-ink-2); }

        .acctx-alert {
            display: flex; gap: 8px; margin: 6px 18px; padding: 8px 12px; border-radius: 8px; font-size: 12px;
            color: var(--bs-danger-text-emphasis, var(--bs-danger));
            background: color-mix(in srgb, var(--bs-danger) 9%, transparent);
            border: 1px solid color-mix(in srgb, var(--bs-danger) 30%, transparent);
        }
        .acctx-alert i { margin-top: 2px; }
        .acctx-alert ul { margin: 0; padding-inline-start: 14px; }
        .acctx-error { margin-top: 4px; font-size: 11.5px; color: var(--bs-danger); }

        .acctx-foot {
            display: flex; align-items: center; gap: 8px; padding: 12px 18px;
            background: var(--acctx-soft); border-top: 1px solid var(--acctx-line);
            border-radius: 0 0 var(--bs-modal-inner-border-radius, 7px) var(--bs-modal-inner-border-radius, 7px);
        }
        .acctx-help { margin-inline-end: auto; font-size: 11.5px; color: var(--acctx-mute); }
        .acctx-btn {
            display: inline-flex; align-items: center; gap: 7px; height: 34px; padding: 0 14px;
            border-radius: 8px; border: 1px solid transparent; font-size: 12.5px; font-weight: 600; cursor: pointer;
            transition: all .15s ease;
        }
        .acctx-btn--ghost { background: transparent; color: var(--acctx-ink-2); }
        .acctx-btn--ghost:hover { color: var(--acctx-ink); background: color-mix(in srgb, var(--acctx-ink-2) 10%, transparent); }
        .acctx-btn--line { background: var(--acctx-bg); border-color: var(--acctx-line); color: var(--acctx-ink); }
        .acctx-btn--line:hover { border-color: var(--bs-primary); color: var(--bs-primary); }
        .acctx-btn--primary { background: var(--bs-primary); color: #fff; }
        .acctx-btn--primary:hover { filter: brightness(1.08); }

        @media (max-width: 575.98px) {
            .acctx-row { grid-template-columns: minmax(0, 1fr); gap: 6px; }
            .acctx-row--top > .acctx-label { padding-top: 0; }
            .acctx-help { display: none; }
            .acctx-foot { flex-wrap: wrap; justify-content: flex-end; }
        }
    </style>
</div>

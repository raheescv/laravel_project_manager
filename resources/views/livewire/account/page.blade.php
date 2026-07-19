@php
    $accountTypeMeta = [
        'asset' => ['icon' => 'fa-cubes', 'accent' => '#10b981', 'hint' => 'What you own'],
        'liability' => ['icon' => 'fa-credit-card', 'accent' => '#f59e0b', 'hint' => 'What you owe'],
        'income' => ['icon' => 'fa-line-chart', 'accent' => '#0ea5e9', 'hint' => 'Money in'],
        'expense' => ['icon' => 'fa-shopping-cart', 'accent' => '#ef4444', 'hint' => 'Money out'],
        'equity' => ['icon' => 'fa-university', 'accent' => '#8b5cf6', 'hint' => 'Owner capital'],
    ];
@endphp
<div class="acctx" x-data="{ selectedType: @js($accounts['account_type'] ?? '') }">
    <div class="acctx-header">
        <div class="acctx-header-left">
            <span class="acctx-header-icon"><i class="fa fa-book"></i></span>
            <div>
                <h1 class="acctx-title">{{ $table_id ? 'Edit Account' : 'Create Account' }}</h1>
                <p class="acctx-subtitle">{{ $table_id ? 'Update the chart-of-accounts entry' : 'Add a new entry to your chart of accounts' }}</p>
            </div>
        </div>
        <button type="button" class="acctx-close" data-bs-dismiss="modal" aria-label="Close"><i class="fa fa-times"></i></button>
    </div>

    <form wire:submit="save">
        <div class="acctx-body">
            {{-- Error Messages --}}
            @if ($this->getErrorBag()->count())
                <div class="acctx-alert" role="alert">
                    <i class="fa fa-exclamation-circle acctx-alert-icon"></i>
                    <div class="flex-grow-1">
                        <strong class="acctx-alert-title">Please fix the following errors</strong>
                        <ul class="acctx-alert-list">
                            @foreach ($this->getErrorBag()->toArray() as $value)
                                <li>{{ $value[0] }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <button type="button" class="acctx-alert-close" data-bs-dismiss="alert" aria-label="Close"><i class="fa fa-times"></i></button>
                </div>
            @endif

            {{-- Account Type — premium radio cards --}}
            @if (!$type_selection_freeze)
                <div class="acctx-field">
                    <label class="acctx-label">
                        <i class="fa fa-tag"></i> Account Type <span class="acctx-req">*</span>
                    </label>
                    <div class="acctx-typegrid @error('accounts.account_type') is-invalid @enderror">
                        @foreach (accountTypes() as $key => $label)
                            @php $meta = $accountTypeMeta[$key] ?? ['icon' => 'fa-circle', 'accent' => 'var(--bs-primary)', 'hint' => '']; @endphp
                            <label class="acctx-typecard" style="--acc: {{ $meta['accent'] }};">
                                <input type="radio" class="acctx-typeradio" wire:model="accounts.account_type" value="{{ $key }}"
                                    x-on:change="selectedType = $event.target.value; if (selectedType !== 'asset') { $wire.set('accounts.is_cheque', false) }">
                                <span class="acctx-typeicon"><i class="fa {{ $meta['icon'] }}"></i></span>
                                <span class="acctx-typebody">
                                    <span class="acctx-typename">{{ $label }}</span>
                                    <span class="acctx-typehint">{{ $meta['hint'] }}</span>
                                </span>
                                <i class="fa fa-check-circle acctx-typecheck"></i>
                            </label>
                        @endforeach
                    </div>
                    @error('accounts.account_type')
                        <div class="acctx-error"><i class="fa fa-exclamation-circle"></i> {{ $message }}</div>
                    @enderror
                </div>
            @endif

            {{-- Account Category --}}
            <div class="acctx-field">
                <label for="modal_account_category_id" class="acctx-label">
                    <i class="fa fa-folder"></i> Account Category
                </label>
                <div class="acctx-inputwrap" wire:ignore>
                    <span class="acctx-inputicon"><i class="fa fa-folder-open"></i></span>
                    {{ html()->select('account_category_id', $accountCategories ?? [])->value(old('account_category_id', $accounts['account_category_id'] ?? ''))->class('select-account_category_id acctx-input')->id('modal_account_category_id')->placeholder('Select account category')->attribute('wire:model.live', 'accounts.account_category_id') }}
                </div>
            </div>

            <div class="acctx-grid2">
                {{-- Name --}}
                <div class="acctx-field">
                    <label for="modal_account_name" class="acctx-label">
                        <i class="fa fa-user"></i> Name <span class="acctx-req">*</span>
                    </label>
                    <div class="acctx-inputwrap @error('accounts.name') is-invalid @enderror">
                        <span class="acctx-inputicon"><i class="fa fa-building"></i></span>
                        {{ html()->input('name')->value('')->class('acctx-input')->attribute('wire:model', 'accounts.name')->id('modal_account_name')->placeholder('Enter account name') }}
                    </div>
                    @error('accounts.name')
                        <div class="acctx-error"><i class="fa fa-exclamation-circle"></i> {{ $message }}</div>
                    @enderror
                </div>

                {{-- Alias Name --}}
                <div class="acctx-field">
                    <label for="modal_account_alias" class="acctx-label">
                        <i class="fa fa-tag"></i> Alias Name
                    </label>
                    <div class="acctx-inputwrap">
                        <span class="acctx-inputicon"><i class="fa fa-at"></i></span>
                        {{ html()->input('alias_name')->value('')->class('acctx-input')->attribute('wire:model', 'accounts.alias_name')->id('modal_account_alias')->placeholder('Enter alias name (optional)') }}
                    </div>
                </div>
            </div>

            {{-- Opening Balance --}}
            <div class="acctx-panel">
                <div class="acctx-panel-head">
                    <i class="fa fa-exchange"></i>
                    <span>Opening Balance</span>
                </div>
                <div class="acctx-grid2">
                    <div class="acctx-field mb-0">
                        <label for="opening_debit" class="acctx-label">
                            <i class="fa fa-arrow-up text-success"></i> Opening Debit
                        </label>
                        <div class="acctx-inputwrap @error('accounts.opening_debit') is-invalid @enderror">
                            <span class="acctx-inputicon"><i class="fa fa-money text-success"></i></span>
                            {{ html()->input('opening_debit')->type('number')->value('')->class('acctx-input')->attribute('wire:model', 'accounts.opening_debit')->placeholder('0.00')->id('opening_debit') }}
                        </div>
                        @error('accounts.opening_debit')
                            <div class="acctx-error"><i class="fa fa-exclamation-circle"></i> {{ $message }}</div>
                        @enderror
                    </div>
                    <div class="acctx-field mb-0">
                        <label for="opening_credit" class="acctx-label">
                            <i class="fa fa-arrow-down text-danger"></i> Opening Credit
                        </label>
                        <div class="acctx-inputwrap @error('accounts.opening_credit') is-invalid @enderror">
                            <span class="acctx-inputicon"><i class="fa fa-money text-danger"></i></span>
                            {{ html()->input('opening_credit')->type('number')->value('')->class('acctx-input')->attribute('wire:model', 'accounts.opening_credit')->placeholder('0.00')->id('opening_credit') }}
                        </div>
                        @error('accounts.opening_credit')
                            <div class="acctx-error"><i class="fa fa-exclamation-circle"></i> {{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <p class="acctx-note"><i class="fa fa-info-circle"></i> Enter the initial balance when creating this account. Leave blank if not applicable.</p>
            </div>

            {{-- Cheque Account — only relevant for Asset accounts --}}
            <label class="acctx-toggle" for="account_is_cheque" x-show="selectedType === 'asset'" x-cloak>
                <div class="acctx-toggle-switch">
                    <input class="form-check-input" type="checkbox" role="switch"
                        id="account_is_cheque" wire:model="accounts.is_cheque">
                </div>
                <div class="acctx-toggle-body">
                    <span class="acctx-toggle-title"><i class="fa fa-money"></i> Cheque Account</span>
                    <span class="acctx-toggle-hint">When this account is used as a payment method, cheque details (bank name &amp; cheque no) will be requested.</span>
                </div>
            </label>

            {{-- Description --}}
            <div class="acctx-field mb-0">
                <label for="modal_account_description" class="acctx-label">
                    <i class="fa fa-align-left"></i> Description
                </label>
                <div class="acctx-inputwrap acctx-inputwrap--top">
                    <span class="acctx-inputicon"><i class="fa fa-file-text"></i></span>
                    {{ html()->textarea('description')->value('')->class('acctx-input')->attribute('wire:model', 'accounts.description')->attribute('rows', '3')->id('modal_account_description')->placeholder('Enter account description (optional)') }}
                </div>
            </div>
        </div>

        <div class="acctx-footer">
            <button type="button" class="acctx-btn acctx-btn--ghost" data-bs-dismiss="modal">
                <i class="fa fa-times"></i> Close
            </button>
            <button type="button" wire:click="save(1)" class="acctx-btn acctx-btn--soft">
                <i class="fa fa-plus-circle"></i> Save & Add New
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
                    if (data && data.account_category_id) {
                        @this.set('accounts.account_category_id', data.account_category_id);
                        var accountCategoryTomSelectInstance = document.querySelector('#modal_account_category_id').tomselect;
                        if (accountCategoryTomSelectInstance && data.account_category) {
                            var preselectedData = {
                                id: data.account_category_id,
                                name: data.account_category['name'],
                            };
                            accountCategoryTomSelectInstance.addOption(preselectedData);
                            accountCategoryTomSelectInstance.addItem(preselectedData.id);
                        }
                    }
                });
            });
        </script>
    @endpush

    <style>
            .acctx [x-cloak] { display: none !important; }
            .acctx {
                --acctx-radius: 10px;
                --acctx-border: var(--bs-border-color, #e5e7eb);
                --acctx-surface: var(--bs-body-bg, #fff);
                --acctx-muted: var(--bs-secondary-color, #6b7280);
                --acctx-field-bg: var(--bs-tertiary-bg, #f8f9fb);
                color: var(--bs-body-color);
            }

            /* Header */
            .acctx-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.75rem;
                padding: 0.75rem 1rem;
                border-bottom: 1px solid var(--acctx-border);
                background:
                    radial-gradient(120% 140% at 0% 0%, color-mix(in srgb, var(--bs-primary) 10%, transparent) 0%, transparent 55%),
                    var(--acctx-surface);
            }
            .acctx-header-left { display: flex; align-items: center; gap: 0.65rem; }
            .acctx-header-icon {
                width: 36px; height: 36px; flex: 0 0 36px;
                display: inline-flex; align-items: center; justify-content: center;
                border-radius: 9px; font-size: 1rem; color: #fff;
                background: linear-gradient(135deg, var(--bs-primary), color-mix(in srgb, var(--bs-primary) 60%, #000));
                box-shadow: 0 4px 12px color-mix(in srgb, var(--bs-primary) 32%, transparent);
            }
            .acctx-title { font-size: 1rem; font-weight: 700; margin: 0; line-height: 1.2; }
            .acctx-subtitle { font-size: 0.72rem; color: var(--acctx-muted); margin: 1px 0 0; }
            .acctx-close {
                border: 1px solid var(--acctx-border); background: var(--acctx-surface);
                width: 30px; height: 30px; border-radius: 8px; color: var(--acctx-muted);
                display: inline-flex; align-items: center; justify-content: center;
                transition: all .15s ease; cursor: pointer;
            }
            .acctx-close:hover { color: var(--bs-danger); border-color: var(--bs-danger); transform: rotate(90deg); }

            /* Body */
            .acctx-body { padding: 0.9rem 1rem; display: flex; flex-direction: column; gap: 0.75rem; max-height: 72vh; overflow-y: auto; }
            .acctx-field { display: flex; flex-direction: column; }
            .acctx-field.mb-0 { margin-bottom: 0; }
            .acctx-grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
            @media (max-width: 640px) { .acctx-grid2 { grid-template-columns: 1fr; } }

            .acctx-label {
                font-size: 0.78rem; font-weight: 600; margin-bottom: 0.3rem;
                display: inline-flex; align-items: center; gap: 0.35rem; color: var(--bs-body-color);
            }
            .acctx-label > i:first-child { color: var(--acctx-muted); }
            .acctx-req { color: var(--bs-danger); }

            /* Inputs */
            .acctx-inputwrap {
                display: flex; align-items: stretch;
                border: 1px solid var(--acctx-border); border-radius: var(--acctx-radius);
                background: var(--acctx-surface); overflow: hidden;
                transition: border-color .15s ease, box-shadow .15s ease;
            }
            .acctx-inputwrap:focus-within {
                border-color: var(--bs-primary);
                box-shadow: 0 0 0 3px color-mix(in srgb, var(--bs-primary) 18%, transparent);
            }
            .acctx-inputwrap.is-invalid { border-color: var(--bs-danger); }
            .acctx-inputwrap--top .acctx-inputicon { align-items: flex-start; padding-top: 0.55rem; }
            .acctx-inputicon {
                display: inline-flex; align-items: center; justify-content: center;
                width: 38px; flex: 0 0 38px; background: var(--acctx-field-bg);
                color: var(--acctx-muted); border-right: 1px solid var(--acctx-border);
            }
            .acctx-input {
                flex: 1 1 auto; border: 0 !important; background: transparent !important;
                padding: 0.45rem 0.7rem; font-size: 0.86rem; color: var(--bs-body-color) !important;
                box-shadow: none !important; outline: none !important; width: 100%;
            }
            .acctx-input::placeholder { color: var(--acctx-muted); opacity: .7; }
            textarea.acctx-input { resize: vertical; min-height: 58px; }
            /* TomSelect fits flush inside the wrap — allow the dropdown to overflow */
            .acctx-inputwrap:has(.ts-wrapper) { overflow: visible; }
            .acctx-inputwrap .ts-wrapper { flex: 1 1 auto; border: 0 !important; min-width: 0; }
            .acctx-inputwrap .ts-dropdown { z-index: 1080; }
            .acctx-inputwrap .ts-control {
                border: 0 !important; background: transparent !important; box-shadow: none !important;
                border-radius: 0 !important; padding: 0.35rem 0.7rem; min-height: 36px;
            }

            /* Account Type radio cards */
            .acctx-typegrid {
                display: grid; grid-template-columns: repeat(5, 1fr); gap: 0.45rem;
            }
            @media (max-width: 720px) { .acctx-typegrid { grid-template-columns: repeat(2, 1fr); } }
            .acctx-typegrid.is-invalid { outline: 1px dashed color-mix(in srgb, var(--bs-danger) 60%, transparent); outline-offset: 3px; border-radius: var(--acctx-radius); }
            .acctx-typecard {
                position: relative; cursor: pointer;
                display: flex; flex-direction: row; align-items: center; text-align: left; gap: 0.5rem;
                padding: 0.5rem 0.6rem; border-radius: var(--acctx-radius);
                border: 1.5px solid var(--acctx-border); background: var(--acctx-surface);
                transition: transform .15s ease, border-color .15s ease, box-shadow .15s ease, background .15s ease;
            }
            .acctx-typecard:hover { transform: translateY(-1px); border-color: var(--acc); box-shadow: 0 5px 12px color-mix(in srgb, var(--acc) 20%, transparent); }
            .acctx-typeradio { position: absolute; opacity: 0; pointer-events: none; }
            .acctx-typeicon {
                width: 30px; height: 30px; flex: 0 0 30px; border-radius: 8px;
                display: inline-flex; align-items: center; justify-content: center; font-size: 0.9rem;
                color: var(--acc); background: color-mix(in srgb, var(--acc) 14%, transparent);
                transition: all .15s ease;
            }
            .acctx-typebody { display: flex; flex-direction: column; gap: 0; line-height: 1.1; min-width: 0; }
            .acctx-typename { font-weight: 600; font-size: 0.8rem; }
            .acctx-typehint { font-size: 0.64rem; color: var(--acctx-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .acctx-typecheck {
                position: absolute; top: 5px; right: 6px; font-size: 0.8rem;
                color: var(--acc); opacity: 0; transform: scale(.5); transition: all .18s ease;
            }
            /* Selected state */
            .acctx-typecard:has(.acctx-typeradio:checked) {
                border-color: var(--acc);
                background: color-mix(in srgb, var(--acc) 9%, var(--acctx-surface));
                box-shadow: 0 6px 16px color-mix(in srgb, var(--acc) 25%, transparent);
            }
            .acctx-typecard:has(.acctx-typeradio:checked) .acctx-typeicon { color: #fff; background: var(--acc); transform: scale(1.04); }
            .acctx-typecard:has(.acctx-typeradio:checked) .acctx-typecheck { opacity: 1; transform: scale(1); }
            .acctx-typecard:has(.acctx-typeradio:focus-visible) { box-shadow: 0 0 0 3px color-mix(in srgb, var(--acc) 30%, transparent); }

            /* Panel (opening balance) */
            .acctx-panel {
                border: 1px solid var(--acctx-border); border-radius: var(--acctx-radius);
                background: var(--acctx-field-bg); padding: 0.7rem 0.8rem;
            }
            .acctx-panel-head {
                display: flex; align-items: center; gap: 0.4rem; margin-bottom: 0.6rem;
                font-weight: 600; font-size: 0.8rem; color: var(--bs-primary);
            }
            .acctx-note { margin: 0.55rem 0 0; font-size: 0.7rem; color: var(--acctx-muted); }
            .acctx-note i { margin-right: 0.25rem; }

            /* Cheque toggle */
            .acctx-toggle {
                display: flex; align-items: center; gap: 0.65rem; cursor: pointer;
                border: 1px solid var(--acctx-border); border-radius: var(--acctx-radius);
                background: var(--acctx-field-bg); padding: 0.6rem 0.8rem; margin: 0;
                transition: border-color .15s ease, background .15s ease;
            }
            .acctx-toggle:has(input:checked) { border-color: var(--bs-primary); background: color-mix(in srgb, var(--bs-primary) 7%, var(--acctx-surface)); }
            .acctx-toggle-switch .form-check-input { width: 2.4em; height: 1.25em; margin: 0; cursor: pointer; }
            .acctx-toggle-body { display: flex; flex-direction: column; gap: 1px; }
            .acctx-toggle-title { font-weight: 600; font-size: 0.82rem; display: inline-flex; align-items: center; gap: 0.35rem; }
            .acctx-toggle-title i { color: var(--bs-primary); }
            .acctx-toggle-hint { font-size: 0.7rem; color: var(--acctx-muted); }

            /* Alert */
            .acctx-alert {
                display: flex; align-items: flex-start; gap: 0.5rem;
                border: 1px solid color-mix(in srgb, var(--bs-danger) 35%, transparent);
                background: color-mix(in srgb, var(--bs-danger) 10%, var(--acctx-surface));
                border-radius: var(--acctx-radius); padding: 0.6rem 0.8rem; color: var(--bs-danger-text-emphasis, var(--bs-danger));
            }
            .acctx-alert-icon { margin-top: 2px; color: var(--bs-danger); }
            .acctx-alert-title { display: block; margin-bottom: 0.25rem; font-size: 0.85rem; }
            .acctx-alert-list { margin: 0; padding-left: 1.1rem; font-size: 0.8rem; }
            .acctx-alert-close { border: 0; background: transparent; color: inherit; opacity: .6; cursor: pointer; }
            .acctx-alert-close:hover { opacity: 1; }

            .acctx-error { color: var(--bs-danger); font-size: 0.74rem; margin-top: 0.25rem; display: flex; align-items: center; gap: 0.3rem; }

            /* Footer */
            .acctx-footer {
                display: flex; justify-content: flex-end; gap: 0.5rem; flex-wrap: wrap;
                padding: 0.7rem 1rem; border-top: 1px solid var(--acctx-border);
                background: var(--acctx-field-bg);
            }
            .acctx-btn {
                display: inline-flex; align-items: center; gap: 0.4rem;
                border-radius: 9px; padding: 0.45rem 0.9rem; font-size: 0.82rem; font-weight: 600;
                border: 1px solid transparent; cursor: pointer; transition: all .15s ease;
            }
            .acctx-btn--ghost { background: var(--acctx-surface); border-color: var(--acctx-border); color: var(--acctx-muted); }
            .acctx-btn--ghost:hover { color: var(--bs-body-color); border-color: var(--acctx-muted); }
            .acctx-btn--soft { background: color-mix(in srgb, var(--bs-success) 14%, transparent); color: var(--bs-success); }
            .acctx-btn--soft:hover { background: var(--bs-success); color: #fff; }
            .acctx-btn--primary {
                background: linear-gradient(135deg, var(--bs-primary), color-mix(in srgb, var(--bs-primary) 65%, #000));
                color: #fff; box-shadow: 0 6px 15px color-mix(in srgb, var(--bs-primary) 35%, transparent);
            }
            .acctx-btn--primary:hover { filter: brightness(1.05); transform: translateY(-1px); }
    </style>
</div>

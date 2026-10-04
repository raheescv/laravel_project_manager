<div class="scx">
    @include('livewire.settings.partials.panel-styles')

    <form wire:submit="save">
        {{-- Card & Portal --}}
        <div class="scx-body" x-show="pane === 'card'">
            <div class="sct-pane-head">
                <div>
                    <h6>Card balance &amp; top-ups</h6>
                    <p>Limits that apply to every student's card.</p>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="sc_overdraft_limit">Overdraft limit</label>
                    <input type="number" step="0.01" min="0" id="sc_overdraft_limit" class="form-control" wire:model="overdraft_limit">
                    <div class="form-text">How far below zero a card may go. 0 = none.</div>
                    @error('overdraft_limit')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-8">
                    <span class="form-label d-block">Online top-up range</span>
                    <div class="input-group">
                        <span class="input-group-text">Min</span>
                        <input type="number" step="0.01" min="1" id="sc_topup_min" class="form-control" wire:model="topup_min" aria-label="Smallest online top-up">
                        <span class="input-group-text">Max</span>
                        <input type="number" step="0.01" min="1" id="sc_topup_max" class="form-control" wire:model="topup_max" aria-label="Largest online top-up">
                    </div>
                    <div class="form-text">The smallest and largest amount a parent can top up at once.</div>
                    @error('topup_min')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                    @error('topup_max')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12">
                    <label class="form-label" for="sc_portal_url">Parent portal address <span class="text-body-secondary fw-normal">(optional)</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa fa-link"></i></span>
                        <input type="url" id="sc_portal_url" class="form-control" wire:model="portal_url" placeholder="https://parents.yourschool.qa" inputmode="url" autocomplete="off">
                    </div>
                    <div class="form-text">Invite emails link here, and parents return here after paying. Empty = the address your provider set up.</div>
                    @error('portal_url')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Canteen pre-orders --}}
        <div class="scx-body" x-show="pane === 'canteen'" x-cloak>
            <div class="sct-pane-head">
                <div>
                    <h6>Canteen pre-orders</h6>
                    <p>Parents order for a day or every week in the portal; the items drop into the POS cart when the card is tapped. Nothing is charged at ordering.</p>
                </div>
                <label class="form-switch scx-switch" for="sc_pre_orders_enabled">
                    {{ $pre_orders_enabled ? 'On' : 'Off' }}
                    <input class="form-check-input" type="checkbox" role="switch" id="sc_pre_orders_enabled" wire:model.live="pre_orders_enabled">
                </label>
            </div>
            <style>
                .cnx-grid {
                    display: grid;
                    grid-template-columns: minmax(0, 5fr) minmax(0, 7fr);
                    gap: .85rem;
                    transition: opacity .15s;
                }

                .cnx-grid.is-off {
                    opacity: .5;
                }

                .cnx-card {
                    padding: .95rem 1rem 1rem;
                    border: 1px solid var(--scx-line-soft);
                    border-radius: 14px;
                }

                .cnx-card-head {
                    display: flex;
                    align-items: center;
                    gap: .55rem;
                    margin-bottom: .85rem;
                    font-weight: 700;
                    color: var(--bs-emphasis-color);
                }

                .cnx-card-head .scx-ic {
                    width: 28px;
                    height: 28px;
                    border-radius: 8px;
                    font-size: .8rem;
                }

                .cnx-days {
                    display: grid;
                    grid-template-columns: repeat(7, minmax(0, 1fr));
                    gap: .3rem;
                }

                .cnx-day {
                    display: grid;
                    place-items: center;
                    margin: 0;
                    padding: .45rem 0;
                    border: 1px solid var(--scx-line);
                    border-radius: 10px;
                    font-size: .74rem;
                    font-weight: 600;
                    color: var(--bs-secondary-color);
                    cursor: pointer;
                    user-select: none;
                    transition: background .15s, color .15s, border-color .15s;
                }

                .cnx-day:hover {
                    border-color: var(--scx-acc);
                }

                .btn-check:checked + .cnx-day {
                    border-color: color-mix(in srgb, var(--scx-acc) 45%, transparent);
                    background: var(--scx-acc-soft);
                    color: var(--scx-acc);
                }

                .btn-check:focus-visible + .cnx-day {
                    outline: 2px solid var(--scx-acc);
                    outline-offset: 1px;
                }

                .cnx-presets {
                    display: flex;
                    flex-wrap: wrap;
                    gap: .25rem .75rem;
                    margin-top: .45rem;
                }

                .cnx-presets button {
                    padding: 0;
                    border: 0;
                    background: none;
                    font-size: .72rem;
                    font-weight: 600;
                    color: var(--scx-acc);
                }

                .cnx-presets button:hover {
                    text-decoration: underline;
                }

                .cnx-card .ts-wrapper.multi .ts-control {
                    min-height: 2.6rem;
                    padding: .35rem .45rem;
                    border-radius: 12px;
                    box-shadow: none;
                }

                .cnx-card .ts-wrapper.multi .ts-control > .item,
                .cnx-card .ts-wrapper.multi.has-items .ts-control > .item {
                    margin: 2px;
                    padding: .12rem 0 .12rem .6rem;
                    border: 0;
                    border-radius: 999px;
                    background: var(--scx-acc-soft);
                    color: var(--scx-acc);
                    font-size: .74rem;
                    font-weight: 600;
                }

                .cnx-card .ts-wrapper.plugin-remove_button .item .remove {
                    margin-inline-start: .25rem;
                    padding: 0 .5rem;
                    border: 0 !important;
                    border-radius: 0 999px 999px 0;
                    color: inherit;
                    opacity: .6;
                }

                .cnx-card .ts-wrapper.plugin-remove_button .item .remove:hover {
                    background: transparent;
                    opacity: 1;
                }

                .cnx-card .ts-control input {
                    font-size: .8rem;
                }

                @media (max-width: 991.98px) {
                    .cnx-grid {
                        grid-template-columns: 1fr;
                    }
                }
            </style>

            <div class="cnx-grid {{ $pre_orders_enabled ? '' : 'is-off' }}">
                <div class="cnx-card">
                    <div class="cnx-card-head">
                        <span class="scx-ic" style="--tone:#2f6fd6"><i class="fa fa-calendar"></i></span>
                        When parents can order
                    </div>

                    <span class="form-label d-block">Open days</span>
                    <div class="cnx-days">
                        @foreach ($weekdays as $value => $label)
                            <input type="checkbox" class="btn-check" id="sc_day_{{ $value }}" value="{{ $value }}" wire:model="school_days" autocomplete="off">
                            <label class="cnx-day" for="sc_day_{{ $value }}">{{ $label }}</label>
                        @endforeach
                    </div>
                    <div class="cnx-presets">
                        <button type="button" x-on:click="$wire.set('school_days', ['7', '1', '2', '3', '4'])">Sun – Thu</button>
                        <button type="button" x-on:click="$wire.set('school_days', ['1', '2', '3', '4', '5'])">Mon – Fri</button>
                        <button type="button" x-on:click="$wire.set('school_days', ['7', '1', '2', '3', '4', '5', '6'])">Every day</button>
                    </div>
                    @error('school_days')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror

                    <label class="form-label mt-3" for="sc_pre_order_cutoff">Order cut-off time</label>
                    <input type="time" id="sc_pre_order_cutoff" class="form-control" wire:model="pre_order_cutoff">
                    <div class="form-text">After this time, that day's order is locked.</div>
                    @error('pre_order_cutoff')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                <div class="cnx-card">
                    <div class="cnx-card-head">
                        <span class="scx-ic" style="--tone:#e0672a"><i class="fa fa-cutlery"></i></span>
                        Pre-order menu
                    </div>
                    <label class="form-label" for="sc_pre_order_categories">Categories parents can order from</label>
                    <div wire:ignore>
                        <select id="sc_pre_order_categories" multiple data-pre-order-categories placeholder="Search categories…">
                            @foreach ($categories as $id => $name)
                                <option value="{{ $id }}" @selected(in_array((string) $id, $pre_order_category_ids, true))>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-text">Parents see the selling products in these categories, at their current price.</div>
                    @error('pre_order_category_ids')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        @php
            $cardErrors = $errors->hasAny(['overdraft_limit', 'topup_min', 'topup_max', 'portal_url']);
            $canteenErrors = $errors->hasAny(['school_days', 'school_days.*', 'pre_order_cutoff', 'pre_order_category_ids', 'pre_order_category_ids.*']);
        @endphp
        <div class="scx-foot" x-show="pane === 'card' || pane === 'canteen'">
            @if ($cardErrors)
                <button type="button" class="btn btn-link btn-sm text-danger p-0 me-auto" x-show="pane !== 'card'" x-on:click="pane = 'card'"><i class="fa fa-exclamation-circle me-1"></i>Fix Card &amp; Portal</button>
            @endif
            @if ($canteenErrors)
                <button type="button" class="btn btn-link btn-sm text-danger p-0 me-auto" x-show="pane !== 'canteen'" x-on:click="pane = 'canteen'"><i class="fa fa-exclamation-circle me-1"></i>Fix Canteen</button>
            @endif
            <span class="scx-foot-note">Card &amp; Portal and Canteen save together.</span>
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save"><i class="fa fa-save me-1"></i> Save</span>
                <span wire:loading wire:target="save"><i class="fa fa-spinner fa-spin me-1"></i> Saving…</span>
            </button>
        </div>
    </form>

    @script
        <script>
            (() => {
                const el = $wire.$el.querySelector('[data-pre-order-categories]');
                if (!el || el.tomselect || typeof TomSelect === 'undefined') return;
                new TomSelect(el, {
                    plugins: ['remove_button'],
                    maxOptions: null,
                    onChange(value) {
                        $wire.$set('pre_order_category_ids', value || [], false);
                    },
                });
            })()
        </script>
    @endscript
</div>

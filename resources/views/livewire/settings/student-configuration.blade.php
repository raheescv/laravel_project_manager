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

        {{-- Public registration page --}}
        <div class="scx-body" x-show="pane === 'registration'" x-cloak>
            <div class="sct-pane-head">
                <div>
                    <h6>Public registration page</h6>
                    <p>Parents register their children from the parent portal, without signing in.</p>
                </div>
                <label class="form-switch scx-switch" for="sc_registration_auto_approve">
                    {{ $registration_auto_approve ? 'Approve automatically' : 'Office approves' }}
                    <input class="form-check-input" type="checkbox" role="switch" id="sc_registration_auto_approve" wire:model.live="registration_auto_approve">
                </label>
            </div>
            <div class="alert alert-light border small py-2 mb-3">
                <i class="fa fa-info-circle me-1"></i>
                @if ($registration_auto_approve)
                    A new parent's children are added at once and the parent is emailed a link to set their portal password. A form that uses the mobile number of a parent you already have still waits under Students → Registrations, so nobody can add a child to another family.
                @else
                    Each form waits under Students → Registrations until you approve it. The parent gets their portal link after that.
                @endif
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="form-label mb-0">Schools</span>
                        <label class="form-check form-switch mb-0" for="sc_registration_enabled">
                            <input class="form-check-input" type="checkbox" role="switch" id="sc_registration_enabled" wire:model.live="registration_enabled">
                            <span class="form-check-label small">{{ $registration_enabled ? 'Page open' : 'Page closed' }}</span>
                        </label>
                    </div>
                    <div class="border rounded p-2" style="min-height: 9.5rem;">
                        @forelse ($registrationBranches as $branch)
                            <span class="badge bg-light text-dark border me-1 mb-1 fw-semibold"><i class="fa fa-building-o me-1"></i>{{ $branch->name }}</span>
                        @empty
                            <span class="text-body-secondary small">No branch is on the showcase yet, so the page offers no school.</span>
                        @endforelse
                    </div>
                    <div class="form-text">
                        The branches shown on the showcase, from Settings → Branches. A registered child is attached to the branch picked, and their pre-orders are sold there.
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label" for="sc_registration_classes">Classes</label>
                    <textarea id="sc_registration_classes" class="form-control" rows="7" wire:model="registration_classes"></textarea>
                    <div class="form-text">One per line.</div>
                    @error('registration_classes')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label" for="sc_registration_divisions">Divisions</label>
                    <textarea id="sc_registration_divisions" class="form-control" rows="7" wire:model="registration_divisions"></textarea>
                    <div class="form-text">One per line.</div>
                    @error('registration_divisions')
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
                    <p>Parents order and pay in the portal: each order becomes a completed sale dated the day it is served, paid from the student card (a parent who picks bank card tops the card up for it first). Weekly orders are paid day by day when each day's orders close. A tap at the till is always a new sale.</p>
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
                    <input type="time" id="sc_pre_order_cutoff" class="form-control" wire:model.live="pre_order_cutoff">
                    <div class="cnx-days mt-2" style="grid-template-columns: repeat(2, minmax(0, 1fr));" role="radiogroup" aria-label="Cut-off day">
                        <input type="radio" class="btn-check" id="sc_cutoff_same" value="0" wire:model.live="pre_order_cutoff_day_before" autocomplete="off">
                        <label class="cnx-day" for="sc_cutoff_same">On the day</label>
                        <input type="radio" class="btn-check" id="sc_cutoff_before" value="1" wire:model.live="pre_order_cutoff_day_before" autocomplete="off">
                        <label class="cnx-day" for="sc_cutoff_before">The day before</label>
                    </div>
                    <div class="form-text">
                        @if ($pre_order_cutoff_day_before === '1')
                            Orders for a day close at {{ $pre_order_cutoff }} the evening before. Sunday's orders close on Saturday.
                        @else
                            Orders for a day close at {{ $pre_order_cutoff }} on that day.
                        @endif
                    </div>
                    @error('pre_order_cutoff')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror

                    <label class="form-label mt-3" for="sc_pre_order_summary_emails">Email the orders to</label>
                    <textarea id="sc_pre_order_summary_emails" class="form-control" rows="2" wire:model="pre_order_summary_emails" placeholder="kitchen@school.qa"></textarea>
                    <div class="form-text">One address per line. When a day's orders close, these addresses get the list of what to prepare. Leave empty to send nothing.</div>
                    @error('pre_order_summary_emails')
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

                    <div class="row g-2 mt-2">
                        <div class="col-sm-6">
                            <label class="form-label" for="sc_pre_order_branch">Branch for students without one</label>
                            <select id="sc_pre_order_branch" class="form-select" wire:model="pre_order_branch_id">
                                <option value="">First branch ({{ $branches->sortBy('id')->first()?->name ?? 'none yet' }})</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">A student's pre-orders are sold at their own branch (the school picked when registering). This one is used when a student has none.</div>
                            @error('pre_order_branch_id')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="sc_pre_order_user">Record them as</label>
                            <select id="sc_pre_order_user" class="form-select" wire:model="pre_order_user_id">
                                <option value="">The System user</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">Shown as the cashier on pre-order sales.</div>
                            @error('pre_order_user_id')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @php
            $cardErrors = $errors->hasAny(['overdraft_limit', 'topup_min', 'topup_max', 'portal_url']);
            $canteenErrors = $errors->hasAny(['school_days', 'school_days.*', 'pre_order_cutoff', 'pre_order_cutoff_day_before', 'pre_order_summary_emails', 'pre_order_category_ids', 'pre_order_category_ids.*', 'pre_order_branch_id', 'pre_order_user_id']);
        @endphp
        <div class="scx-foot" x-show="pane === 'card' || pane === 'canteen' || pane === 'registration'">
            @if ($errors->hasAny(['registration_classes', 'registration_divisions']))
                <button type="button" class="btn btn-link btn-sm text-danger p-0 me-auto" x-show="pane !== 'registration'" x-on:click="pane = 'registration'"><i class="fa fa-exclamation-circle me-1"></i>Fix Registration</button>
            @endif
            @if ($cardErrors)
                <button type="button" class="btn btn-link btn-sm text-danger p-0 me-auto" x-show="pane !== 'card'" x-on:click="pane = 'card'"><i class="fa fa-exclamation-circle me-1"></i>Fix Card &amp; Portal</button>
            @endif
            @if ($canteenErrors)
                <button type="button" class="btn btn-link btn-sm text-danger p-0 me-auto" x-show="pane !== 'canteen'" x-on:click="pane = 'canteen'"><i class="fa fa-exclamation-circle me-1"></i>Fix Canteen</button>
            @endif
            <span class="scx-foot-note">Card &amp; Portal, Canteen and Registration save together.</span>
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

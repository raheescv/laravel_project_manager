<div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white py-2">
        <h5 class="mb-0 text-white"><i class="fa fa-graduation-cap me-1"></i> Student Cards</h5>
    </div>
    <form wire:submit="save">
        <div class="card-body p-3">
            <p class="small text-body-secondary mb-3">
                A student's card balance is their account's ledger: parents top it up online, and QLOUD POS spends it when the card is tapped.
            </p>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-medium small mb-1" for="sc_overdraft_limit">Overdraft limit</label>
                    <input type="number" step="0.01" min="0" id="sc_overdraft_limit" class="form-control form-control-sm" wire:model="overdraft_limit">
                    <div class="form-text">How far below zero a card may go on a purchase, for every student. 0 means no overdraft.</div>
                    @error('overdraft_limit')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-medium small mb-1" for="sc_topup_min">Smallest online top-up</label>
                    <input type="number" step="0.01" min="1" id="sc_topup_min" class="form-control form-control-sm" wire:model="topup_min">
                    @error('topup_min')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-medium small mb-1" for="sc_topup_max">Largest online top-up</label>
                    <input type="number" step="0.01" min="1" id="sc_topup_max" class="form-control form-control-sm" wire:model="topup_max">
                    @error('topup_max')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12">
                    <label class="form-label fw-medium small mb-1" for="sc_portal_url">Parent portal address <span class="text-body-secondary fw-normal">(Optional)</span></label>
                    <input type="url" id="sc_portal_url" class="form-control form-control-sm" wire:model="portal_url" placeholder="https://parents.yourschool.qa" inputmode="url" autocomplete="off">
                    <div class="form-text">The web address where parents open the portal. Invite emails link here, and parents come back here after paying on QPay. Leave empty to use the address your provider set up.</div>
                    @error('portal_url')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <hr class="my-4">

            <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                <div>
                    <h6 class="mb-1"><i class="fa fa-cutlery me-1"></i> Canteen pre-orders</h6>
                    <p class="small text-body-secondary mb-0">
                        Parents choose items in the parent portal, for one day or every week. When the card is tapped, QLOUD POS puts those items in the cart and the cashier charges them to the card as usual. Nothing is charged when the parent orders.
                    </p>
                </div>
                <div class="form-check form-switch flex-shrink-0 mt-1">
                    <input class="form-check-input" type="checkbox" role="switch" id="sc_pre_orders_enabled" wire:model.live="pre_orders_enabled">
                    <label class="form-check-label small fw-medium" for="sc_pre_orders_enabled">{{ $pre_orders_enabled ? 'On' : 'Off' }}</label>
                </div>
            </div>

            <div class="row g-3" @if (!$pre_orders_enabled) style="opacity:.55" @endif>
                <div class="col-12">
                    <span class="form-label fw-medium small mb-1 d-block">Days the canteen is open <span class="text-body-secondary fw-normal">(Required)</span></span>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($weekdays as $value => $label)
                            <input type="checkbox" class="btn-check" id="sc_day_{{ $value }}" value="{{ $value }}" wire:model="school_days" autocomplete="off">
                            <label class="btn btn-sm btn-outline-primary px-3" for="sc_day_{{ $value }}">{{ $label }}</label>
                        @endforeach
                    </div>
                    <div class="form-text">Parents can only pre-order for these days.</div>
                    @error('school_days')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-medium small mb-1" for="sc_pre_order_cutoff">Last time to order for the day</label>
                    <input type="time" id="sc_pre_order_cutoff" class="form-control form-control-sm" wire:model="pre_order_cutoff">
                    <div class="form-text">After this time, parents can no longer add, change or skip that day's order.</div>
                    @error('pre_order_cutoff')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-medium small mb-1" for="sc_pre_order_categories">Pre-order menu</label>
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
        <div class="card-footer d-flex justify-content-end">
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save"><i class="fa fa-save me-1"></i> Save</span>
                <span wire:loading wire:target="save">Saving…</span>
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

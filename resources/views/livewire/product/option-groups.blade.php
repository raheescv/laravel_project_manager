{{-- Inventory → Option Groups (scope .ogx). Preview: docs/product-options-plans-preview.html (Plan A). --}}
<div class="ogx">
    @once
        <style>
            .ogx { --og-acc: var(--bs-primary); --og-soft: color-mix(in srgb, var(--og-acc) 9%, var(--bs-body-bg)); --og-line: var(--bs-border-color-translucent); }
            .ogx .og-card { background: var(--bs-body-bg); border: 1px solid var(--og-line); border-radius: 16px; box-shadow: 0 1px 2px rgba(15, 23, 42, .05), 0 12px 30px -22px rgba(15, 23, 42, .45); }
            .ogx .og-list { display: grid; gap: 8px; padding: 12px; max-height: 70vh; overflow-y: auto; }
            .ogx .og-group { display: block; width: 100%; text-align: start; border: 1px solid var(--og-line); border-radius: 14px; background: var(--bs-body-bg); padding: 11px 13px; color: var(--bs-body-color); }
            .ogx .og-group:hover { border-color: var(--og-acc); }
            .ogx .og-group.on { border-color: var(--og-acc); background: var(--og-soft); box-shadow: 0 0 0 3px color-mix(in srgb, var(--og-acc) 18%, transparent); }
            .ogx .og-group b { font-size: 14.5px; }
            .ogx .og-group .og-preview { font-size: 12px; color: var(--bs-secondary-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .ogx .og-name { font-size: 20px; font-weight: 600; border: 0; border-bottom: 2px dashed var(--og-line); border-radius: 0; padding: 4px 2px; background: transparent; }
            .ogx .og-name:focus { box-shadow: none; border-bottom-color: var(--og-acc); }
            .ogx .og-rows { display: grid; gap: 8px; }
            .ogx .og-row { display: grid; grid-template-columns: 28px minmax(140px, 1.4fr) 100px minmax(150px, 1.3fr) minmax(130px, 1fr) 34px; gap: 8px; align-items: center; padding: 8px 10px; border: 1px solid var(--og-line); border-radius: 12px; }
            .ogx .og-row.head { border: 0; padding-top: 0; padding-bottom: 0; font-size: 10.5px; font-weight: 600; letter-spacing: .07em; text-transform: uppercase; color: var(--bs-secondary-color); }
            .ogx .og-move { display: grid; gap: 2px; }
            .ogx .og-move button { border: 0; background: none; padding: 0; line-height: 1; color: var(--bs-secondary-color); }
            .ogx .og-stock { display: flex; align-items: center; gap: 6px; min-width: 0; }
            .ogx .og-stock .btn { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; text-align: start; }
            .ogx .og-sheet-back { position: fixed; inset: 0; background: rgba(15, 23, 42, .45); z-index: 1055; }
            .ogx .og-sheet { position: fixed; z-index: 1056; top: 8vh; left: 50%; transform: translateX(-50%); width: min(520px, calc(100vw - 32px)); max-height: 80vh; display: flex; flex-direction: column; }
            .ogx .og-sheet .list-group { overflow-y: auto; }
            @media (max-width: 991px) {
                .ogx .og-row { grid-template-columns: 28px 1fr 90px 34px; }
                .ogx .og-row > .og-stock, .ogx .og-row > .og-next { grid-column: 2 / -1; }
                .ogx .og-row.head { display: none; }
            }
        </style>
    @endonce

    <div class="row g-3">
        {{-- Groups --}}
        <div class="col-lg-4">
            <div class="og-card">
                <div class="d-flex align-items-center gap-2 p-3 border-bottom">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="fa fa-search"></i></span>
                        <input type="text" class="form-control" placeholder="Search groups" wire:model.live.debounce.300ms="search">
                    </div>
                    @can('option group.create')
                        <button type="button" class="btn btn-primary btn-sm text-nowrap" wire:click="newGroup"><i class="fa fa-plus me-1"></i>New group</button>
                    @endcan
                </div>
                <div class="og-list">
                    @forelse ($groups as $group)
                        <button type="button" @class(['og-group', 'on' => $editing && $group->id === $editingId]) wire:click="edit({{ $group->id }})" wire:key="og-{{ $group->id }}">
                            <div class="d-flex align-items-center gap-2">
                                <b>{{ $group->name }}</b>
                                @if ($group->status !== 'active')
                                    <span class="badge text-bg-secondary">Off</span>
                                @endif
                                <span class="ms-auto badge {{ $group->min_select > 0 ? 'bg-danger-subtle text-danger-emphasis' : 'bg-body-secondary text-body-secondary' }}">{{ \App\Models\OptionGroup::ruleLabel((int) $group->min_select, $group->max_select) }}</span>
                            </div>
                            <div class="og-preview mt-1">{{ $group->items->pluck('name')->implode(' · ') ?: 'No options yet' }}</div>
                            <div class="small text-body-secondary mt-1">
                                <i class="fa fa-cube me-1"></i>{{ $group->products_count }} product{{ $group->products_count === 1 ? '' : 's' }}
                            </div>
                        </button>
                    @empty
                        <div class="text-center text-body-secondary py-5 px-3">
                            <i class="fa fa-list-ul fa-2x mb-2 d-block opacity-50"></i>
                            No option groups yet. Make one for each question the till should ask, e.g. <b>Drink base</b>, <b>Flavour</b> or <b>Tea add-ons</b>.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Editor --}}
        <div class="col-lg-8">
            @if ($editing)
                <div class="og-card" wire:key="editor-{{ $editingId }}">
                    <div class="p-3 border-bottom">
                        <div class="d-flex flex-wrap align-items-center gap-3">
                            <input type="text" class="form-control og-name flex-grow-1" style="min-width: 220px" placeholder="Group name, e.g. Flavour" wire:model="form.name" maxlength="100">
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input" type="checkbox" role="switch" id="og-active" @checked($form['status'] === 'active') wire:click="$set('form.status', '{{ $form['status'] === 'active' ? 'disabled' : 'active' }}')">
                                <label class="form-check-label small" for="og-active">{{ $form['status'] === 'active' ? 'Active' : 'Off' }}</label>
                            </div>
                        </div>
                    </div>

                    <div class="p-3 border-bottom">
                        <div class="small fw-semibold text-uppercase text-body-secondary mb-2" style="letter-spacing: .06em">How many can be picked</div>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            @php
                                $least = (int) ($form['min_select'] ?: 0);
                                $most = ($form['max_select'] ?? '') === '' ? null : (int) $form['max_select'];
                            @endphp
                            <div class="btn-group btn-group-sm" role="group">
                                @foreach ([['1, 1', 'Pick exactly 1', $least === 1 && $most === 1], ['0, 3', 'Optional · up to 3', $least === 0 && $most !== null], ['0, null', 'Optional · any', $least === 0 && $most === null]] as [$args, $text, $on])
                                    <button type="button" @class(['btn', 'btn-primary' => $on, 'btn-outline-secondary' => ! $on]) wire:click="applyPreset({{ $args }})">{{ $text }}</button>
                                @endforeach
                            </div>
                            <div class="input-group input-group-sm" style="width: 140px">
                                <span class="input-group-text">Least</span>
                                <input type="number" min="0" class="form-control" wire:model.live.debounce.400ms="form.min_select">
                            </div>
                            <div class="input-group input-group-sm" style="width: 170px">
                                <span class="input-group-text">Most</span>
                                <input type="number" min="1" class="form-control" placeholder="No limit" wire:model.live.debounce.400ms="form.max_select">
                            </div>
                            <span class="badge {{ (int) ($form['min_select'] ?: 0) > 0 ? 'bg-danger-subtle text-danger-emphasis' : 'bg-body-secondary text-body-secondary' }} fs-6 fw-semibold">{{ $rule }}</span>
                        </div>
                        <div class="small text-body-secondary mt-2">"Least" above 0 makes the group required: the till will not add the item until it is picked.</div>
                    </div>

                    <div class="p-3">
                        <div class="og-rows">
                            <div class="og-row head">
                                <span></span><span>Option</span><span>+ Price</span><span>Uses stock</span><span>Then ask</span><span></span>
                            </div>
                            @foreach ($form['items'] as $index => $item)
                                <div class="og-row" wire:key="og-item-{{ $editingId }}-{{ $index }}-{{ $item['id'] ?? 'n' }}">
                                    <div class="og-move">
                                        <button type="button" wire:click="moveItem({{ $index }}, -1)" title="Move up" @disabled($index === 0)><i class="fa fa-chevron-up"></i></button>
                                        <button type="button" wire:click="moveItem({{ $index }}, 1)" title="Move down" @disabled($index === count($form['items']) - 1)><i class="fa fa-chevron-down"></i></button>
                                    </div>
                                    <input type="text" class="form-control form-control-sm" placeholder="e.g. Mango Passion" wire:model="form.items.{{ $index }}.name" maxlength="100">
                                    <input type="number" step="any" min="0" class="form-control form-control-sm text-end" wire:model="form.items.{{ $index }}.price">
                                    <div class="og-stock">
                                        @if ($item['product_id'])
                                            <button type="button" class="btn btn-sm btn-light border flex-grow-1" wire:click="openStock({{ $index }})" title="{{ $item['product_name'] }}"><i class="fa fa-cube me-1 text-warning"></i>{{ $item['product_name'] }}</button>
                                            <input type="number" step="any" min="0" class="form-control form-control-sm text-end" style="width: 64px" title="Used per sale" wire:model="form.items.{{ $index }}.quantity">
                                            <button type="button" class="btn btn-sm btn-link text-body-secondary px-1" wire:click="clearStock({{ $index }})" title="Uses no stock"><i class="fa fa-times"></i></button>
                                        @else
                                            <button type="button" class="btn btn-sm btn-outline-secondary flex-grow-1" wire:click="openStock({{ $index }})"><i class="fa fa-plus me-1"></i>None</button>
                                        @endif
                                    </div>
                                    <select class="form-select form-select-sm og-next" wire:model="form.items.{{ $index }}.next_group_id">
                                        <option value="">—</option>
                                        @foreach ($followUps as $groupId => $groupName)
                                            <option value="{{ $groupId }}">{{ $groupName }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn btn-sm btn-link text-danger px-1" wire:click="removeItem({{ $index }})" title="Remove"><i class="fa fa-trash"></i></button>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-3" wire:click="addItem"><i class="fa fa-plus me-1"></i>Add option</button>
                        <div class="small text-body-secondary mt-3">
                            <i class="fa fa-info-circle me-1"></i><b>Uses stock</b> takes that item out of stock each time the option is sold (e.g. Red Bull → Red Bull 250ml × 1).
                            <b>Then ask</b> opens another group after this option is picked (e.g. Red Bull → Sugar choice).
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2 p-3 border-top bg-body-tertiary" style="border-radius: 0 0 16px 16px">
                        @if ($editingId)
                            @can('option group.delete')
                                <button type="button" class="btn btn-sm btn-outline-danger" wire:click="delete" wire:confirm="Delete {{ $form['name'] }}? Past sales keep what was picked."><i class="fa fa-trash me-1"></i>Delete</button>
                            @endcan
                        @endif
                        <button type="button" class="btn btn-primary ms-auto" wire:click="save" wire:loading.attr="disabled" wire:target="save">
                            <span wire:loading.remove wire:target="save"><i class="fa fa-check me-1"></i>{{ $editingId ? 'Save group' : 'Create group' }}</span>
                            <span wire:loading wire:target="save"><i class="fa fa-spinner fa-spin me-1"></i>Saving…</span>
                        </button>
                    </div>
                </div>
            @else
                <div class="og-card p-5 text-center text-body-secondary">
                    <i class="fa fa-hand-o-left fa-2x mb-2 d-block opacity-50"></i>
                    Pick a group on the left, or make a new one.
                </div>
            @endif
        </div>
    </div>

    {{-- Stock item sheet: tap a product to set it --}}
    @if ($stockRow !== null)
        <div class="og-sheet-back" wire:click="closeStock"></div>
        <div class="og-sheet og-card">
            <div class="d-flex align-items-center gap-2 p-3 border-bottom">
                <b class="flex-grow-1">Which stock item does "{{ $form['items'][$stockRow]['name'] ?: 'this option' }}" use?</b>
                <button type="button" class="btn-close" wire:click="closeStock" aria-label="Close"></button>
            </div>
            <div class="p-3 border-bottom">
                <input type="text" class="form-control" placeholder="Search name, code or barcode" wire:model.live.debounce.300ms="stockSearch" autofocus>
            </div>
            <div class="list-group list-group-flush">
                @forelse ($stockResults as $product)
                    <button type="button" class="list-group-item list-group-item-action d-flex align-items-center gap-2" wire:click="pickStock({{ $product->id }})" wire:key="stock-{{ $product->id }}">
                        <i class="fa fa-cube text-warning"></i>
                        <span class="flex-grow-1">{{ $product->displayName() }}</span>
                        <small class="text-body-secondary">{{ $product->code }}</small>
                    </button>
                @empty
                    <div class="text-center text-body-secondary py-4">No products match.</div>
                @endforelse
            </div>
        </div>
    @endif
</div>

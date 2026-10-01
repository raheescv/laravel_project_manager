<div class="lfx ldo">
    <x-property.lead-form.premium />
    @once
        <style>
            .ldo .ldo-head { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between; padding: 12px 14px; border-bottom: 1px solid var(--line-soft); }
            .ldo .ldo-title { margin: 0; font-size: 14px; font-weight: 700; color: var(--ink); }
            .ldo .ldo-sub { margin: 0; font-size: 11.5px; color: var(--faint); }
            .ldo .ldo-tools { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; padding: 10px 14px; border-bottom: 1px solid var(--line-soft); background: var(--surface-2); }
            .ldo .ldo-tools .ctl { background: var(--surface); }
            .ldo .ldo-add { display: flex; gap: 6px; flex: 1 1 340px; }
            .ldo .ldo-add .ctl-order { width: 90px; flex: none; }
            .ldo .ldo-search { flex: 0 1 220px; }
            .ldo .ldo-list { list-style: none; margin: 0; padding: 0; }
            .ldo .ldo-item { border-bottom: 1px solid var(--line-soft); }
            .ldo .ldo-item:last-child { border-bottom: 0; }
            .ldo .ldo-row { display: flex; align-items: center; gap: 10px; padding: 8px 14px; min-height: 46px; }
            .ldo .ldo-row:hover { background: color-mix(in srgb, var(--acc) 3%, var(--surface)); }
            .ldo .ldo-ord { width: 30px; height: 24px; border-radius: 7px; display: grid; place-items: center; font-size: 11px; font-weight: 700; flex: none; background: var(--surface-2); color: var(--muted); border: 1px solid var(--line-soft); }
            .ldo .ldo-val { flex: 1; min-width: 0; font-weight: 600; color: var(--ink); display: flex; align-items: center; gap: 8px; }
            .ldo .ldo-val .atx-dot { width: 8px; height: 8px; }
            .ldo .ldo-meta { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; justify-content: flex-end; }
            .ldo .ldo-tag { display: inline-flex; align-items: center; gap: 4px; height: 22px; padding: 0 8px; border-radius: 999px; font-size: 11px; font-weight: 600; background: var(--surface-2); color: var(--muted); border: 1px solid var(--line-soft); white-space: nowrap; }
            .ldo .ldo-tag.is-subs { cursor: pointer; background: var(--acc-soft); color: var(--acc); border-color: transparent; }
            .ldo .ldo-tag.is-subs.on { background: var(--acc); color: #fff; }
            .ldo .ldo-act { display: flex; gap: 2px; }
            .ldo .ldo-icon { width: 28px; height: 28px; border: 0; border-radius: 8px; background: transparent; color: var(--faint); display: grid; place-items: center; cursor: pointer; }
            .ldo .ldo-icon:hover { background: var(--surface-2); color: var(--ink); }
            .ldo .ldo-icon.del:hover { color: var(--bs-danger); }
            .ldo .ldo-edit { display: flex; gap: 6px; flex: 1; flex-wrap: wrap; }
            .ldo .ldo-edit .ctl { flex: 1 1 200px; }
            .ldo .ldo-edit .ctl-order { flex: 0 0 90px; }
            .ldo .ldo-subs { padding: 4px 14px 12px 54px; background: color-mix(in srgb, var(--acc) 3%, var(--surface)); }
            .ldo .ldo-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px; }
            .ldo .ldo-chip { display: inline-flex; align-items: center; gap: 2px; height: 28px; padding-inline: 10px 2px; border-radius: 999px; background: var(--surface); border: 1px solid var(--line); color: var(--ink); font-size: 12px; }
            .ldo .ldo-chip .ldo-icon { width: 24px; height: 24px; border-radius: 999px; font-size: 10px; }
            .ldo .ldo-chip-edit { display: inline-flex; gap: 4px; align-items: center; }
            .ldo .ldo-chip-edit .ctl { height: 28px; min-height: 28px; padding-block: 2px; width: 180px; }
            .ldo .ldo-subadd { display: flex; gap: 6px; max-width: 460px; }
            .ldo .ldo-empty-sub { font-size: 12px; color: var(--faint); margin-bottom: 8px; }
            .ldo .ldo-foot { padding: 8px 14px; font-size: 11.5px; color: var(--faint); border-top: 1px solid var(--line-soft); display: flex; gap: 6px; align-items: baseline; }
            @media (max-width: 575.98px) { .ldo .ldo-subs { padding-inline-start: 14px; } .ldo .ldo-tag.is-count { display: none; } }
        </style>
    @endonce

    <div class="lfx-card mb-0">
        <div class="ldo-head">
            <div>
                <p class="ldo-title"><i class="fa fa-list-ul text-primary me-2"></i>Lead dropdowns</p>
            </div>
            <div class="seg" role="tablist" aria-label="Lead option list">
                <input type="radio" id="ldo_sources" value="sources" @checked($list === 'sources') wire:click="setList('sources')">
                <label for="ldo_sources"><i class="fa fa-bullhorn"></i>Sources <span class="count ms-1">{{ $counts['sources'] }}</span></label>
                <input type="radio" id="ldo_statuses" value="statuses" @checked($list === 'statuses') wire:click="setList('statuses')">
                <label for="ldo_statuses"><i class="fa fa-flag"></i>Statuses <span class="count ms-1">{{ $counts['statuses'] }}</span></label>
            </div>
        </div>

        <div class="ldo-tools">
            <form class="ldo-add" wire:submit.prevent="add">
                <input type="text" wire:model="newValue" class="ctl {{ $errors->has('newValue') ? 'is-invalid' : '' }}" placeholder="Add a new {{ $noun }}…" aria-label="New {{ $noun }}">
                @if($list === 'statuses')
                    <input type="number" min="0" wire:model="newOrder" class="ctl ctl-order {{ $errors->has('newOrder') ? 'is-invalid' : '' }}" placeholder="Order" aria-label="Order no">
                @endif
                <button type="submit" class="btn-x pri" wire:loading.attr="disabled" wire:target="add"><i class="fa fa-plus"></i> Add</button>
            </form>
            <label class="adorn ldo-search mb-0">
                <i class="fa fa-search"></i>
                <input type="search" wire:model.live.debounce.250ms="search" class="ctl" placeholder="Find…" aria-label="Find {{ $noun }}">
            </label>
        </div>
        @error('newValue') <div class="err px-3 pt-2">{{ $message }}</div> @enderror
        @error('newOrder') <div class="err px-3 pt-2">{{ $message }}</div> @enderror

        @if($rows->isEmpty())
            <div class="empty-s"><i class="fa fa-list-ul"></i>{{ filled($search) ? 'Nothing matches "'.$search.'".' : 'No '.$noun.' yet — add the first one above.' }}</div>
        @else
            <ul class="ldo-list">
                @foreach($rows as $row)
                    @php $value = $row['value']; $isOpen = $openParent === $value; @endphp
                    <li class="ldo-item" wire:key="ldo-{{ $list }}-{{ md5($value) }}">
                        <div class="ldo-row">
                            @if($list === 'statuses')
                                <span class="ldo-ord" title="Order no">{{ $row['order'] ?? '–' }}</span>
                            @endif

                            @if($editing === $value)
                                <form class="ldo-edit" wire:submit.prevent="update">
                                    <input type="text" wire:model="editValue" class="ctl {{ $errors->has('editValue') ? 'is-invalid' : '' }}" aria-label="Rename {{ $noun }}" autofocus>
                                    @if($list === 'statuses')
                                        <input type="number" min="0" wire:model="editOrder" class="ctl ctl-order" placeholder="Order" aria-label="Order no">
                                    @endif
                                    <button type="submit" class="btn-x pri"><i class="fa fa-check"></i> Save</button>
                                    <button type="button" class="btn-x" wire:click="cancelEdit">Cancel</button>
                                    @error('editValue') <div class="err w-100">{{ $message }}</div> @enderror
                                    @if($row['leads'])
                                        <div class="hint w-100"><i class="fa fa-info-circle me-1"></i>Renaming also updates the {{ number_format($row['leads']) }} {{ str('lead')->plural($row['leads']) }} using it.</div>
                                    @endif
                                </form>
                            @else
                                <div class="ldo-val">
                                    @if($list === 'statuses')
                                        <span class="atx-dot tn tn-{{ \App\Support\LeadPipeline::tone($value) }}"></span>
                                    @endif
                                    <span class="text-truncate">{{ $value }}</span>
                                </div>
                                <div class="ldo-meta">
                                    <span class="ldo-tag is-count" title="Leads with this {{ $noun }}"><i class="fa fa-users"></i>{{ number_format($row['leads']) }}</span>
                                    <button type="button" class="ldo-tag is-subs {{ $isOpen ? 'on' : '' }}" wire:click="toggleSubs(@js($value))" aria-expanded="{{ $isOpen ? 'true' : 'false' }}">
                                        <i class="fa fa-sitemap"></i>{{ count($row['subs']) }} sub
                                    </button>
                                    <div class="ldo-act">
                                        <button type="button" class="ldo-icon" wire:click="edit(@js($value))" title="Rename" aria-label="Rename {{ $value }}"><i class="fa fa-pencil"></i></button>
                                        <button type="button" class="ldo-icon del" wire:click="delete(@js($value))"
                                            wire:confirm="Remove &quot;{{ $value }}&quot; from the {{ $noun }} list?{{ $row['leads'] ? ' The '.number_format($row['leads']).' leads that have it keep it.' : '' }}"
                                            title="Remove" aria-label="Remove {{ $value }}"><i class="fa fa-trash-o"></i></button>
                                    </div>
                                </div>
                            @endif
                        </div>

                        @if($isOpen)
                            <div class="ldo-subs">
                                @if(count($row['subs']))
                                    <div class="ldo-chips">
                                        @foreach($row['subs'] as $index => $sub)
                                            @if($editingSub === $index)
                                                <form class="ldo-chip-edit" wire:submit.prevent="updateSub">
                                                    <input type="text" wire:model="editSubValue" class="ctl" aria-label="Rename sub {{ $noun }}" autofocus>
                                                    <button type="submit" class="ldo-icon" title="Save"><i class="fa fa-check"></i></button>
                                                    <button type="button" class="ldo-icon" wire:click="$set('editingSub', null)" title="Cancel"><i class="fa fa-times"></i></button>
                                                </form>
                                            @else
                                                <span class="ldo-chip" wire:key="sub-{{ md5($value.$sub) }}">
                                                    {{ $sub }}
                                                    <button type="button" class="ldo-icon" wire:click="editSub({{ $index }})" title="Rename" aria-label="Rename {{ $sub }}"><i class="fa fa-pencil"></i></button>
                                                    <button type="button" class="ldo-icon del" wire:click="deleteSub({{ $index }})" wire:confirm="Remove &quot;{{ $sub }}&quot; under {{ $value }}?" title="Remove" aria-label="Remove {{ $sub }}"><i class="fa fa-times"></i></button>
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>
                                @else
                                    <div class="ldo-empty-sub">No sub {{ str($noun)->plural() }} under {{ $value }} yet. Without any, the lead form shows a free-text box.</div>
                                @endif
                                @error('editSubValue') <div class="err mb-2">{{ $message }}</div> @enderror
                                <form class="ldo-subadd" wire:submit.prevent="addSub">
                                    <input type="text" wire:model="newSub" class="ctl {{ $errors->has('newSub') ? 'is-invalid' : '' }}" placeholder="Add a sub {{ $noun }} under {{ $value }}…" aria-label="New sub {{ $noun }}">
                                    <button type="submit" class="btn-x pri"><i class="fa fa-plus"></i> Add</button>
                                </form>
                                @error('newSub') <div class="err">{{ $message }}</div> @enderror
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif

        <div class="ldo-foot">
            <i class="fa fa-info-circle"></i>
            <span>
                @if($list === 'statuses')
                    Statuses list by order no, then A–Z. Removing a status keeps it on existing leads; the lead board shows those under "Needs fixing".
                @else
                    Removing a source keeps it on existing leads; the lead form shows it as "(legacy)" until changed.
                @endif
            </span>
        </div>
    </div>
</div>

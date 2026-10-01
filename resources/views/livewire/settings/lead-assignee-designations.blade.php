<div class="lfx lad mb-3">
    <x-property.lead-form.premium />
    @once
        <style>
            .lad .lad-head { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between; padding: 12px 14px; border-bottom: 1px solid var(--line-soft); }
            .lad .lad-title { margin: 0; font-size: 14px; font-weight: 700; color: var(--ink); }
            .lad .lad-sub { margin: 2px 0 0; font-size: 11.5px; color: var(--faint); }
            .lad .lad-chips { display: flex; flex-wrap: wrap; gap: 8px; padding: 12px 14px; }
            .lad .lad-chip { display: inline-flex; align-items: center; gap: 6px; height: 32px; padding: 0 12px; border-radius: 999px; border: 1px solid var(--line); background: var(--surface); color: var(--ink); font-size: 12.5px; font-weight: 600; cursor: pointer; }
            .lad .lad-chip:hover { border-color: var(--acc); }
            .lad .lad-chip.on { background: var(--acc); border-color: var(--acc); color: #fff; }
            .lad .lad-chip .lad-n { font-size: 11px; font-weight: 600; opacity: .7; }
        </style>
    @endonce

    <div class="lfx-card mb-0">
        <div class="lad-head">
            <div>
                <p class="lad-title"><i class="fa fa-user text-primary me-2"></i>Lead assignee designations</p>
                <p class="lad-sub">
                    @if (empty($selected))
                        None chosen — "Assigned to" lists every employee.
                    @else
                        "Assigned to" lists only employees with a selected designation.
                    @endif
                </p>
            </div>
            @if (! empty($selected))
                <button type="button" class="btn-x" wire:click="clear"><i class="fa fa-times"></i> Clear</button>
            @endif
        </div>

        @if ($designations->isEmpty())
            <div class="empty-s"><i class="fa fa-user"></i>No designations yet — add them under Employee designations first.</div>
        @else
            <div class="lad-chips">
                @foreach ($designations as $designation)
                    @php $isOn = in_array($designation->id, $selected, true); @endphp
                    <button type="button" wire:key="lad-{{ $designation->id }}" wire:click="toggle({{ $designation->id }})" wire:loading.attr="disabled"
                        class="lad-chip {{ $isOn ? 'on' : '' }}" aria-pressed="{{ $isOn ? 'true' : 'false' }}">
                        <i class="fa {{ $isOn ? 'fa-check' : 'fa-plus' }}"></i>{{ $designation->name }}
                        <span class="lad-n">{{ $designation->employees_count }}</span>
                    </button>
                @endforeach
            </div>
        @endif
    </div>
</div>

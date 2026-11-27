{{-- Product page → "Options" tab: the option groups the till asks for this item. Rendered inside the page's <form>: every button is type="button". --}}
<div>
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <h5 class="m-0">Options at the till</h5>
        <span class="text-body-secondary small">Asked in this order when {{ $product?->name }} is tapped. Required groups must be picked before it is added.</span>
        @can('option group.view')
            <a href="{{ route('product::option_group::index') }}" class="ms-auto small"><i class="fa fa-list-ul me-1"></i>Manage option groups</a>
        @endcan
    </div>

    @if ($inherited && $inherited->optionGroups->isNotEmpty())
        <div class="alert alert-info d-flex align-items-center gap-2 py-2">
            <i class="fa fa-sitemap"></i>
            <span>This variant asks the options of <a href="{{ route('product::edit', $inherited->id) }}">{{ $inherited->name }}</a>: {{ $inherited->optionGroups->pluck('name')->implode(', ') }}. Attach groups here only to ask something different.</span>
        </div>
    @endif

    <div class="d-grid gap-2 mb-4">
        @forelse ($attached as $group)
            <div class="d-flex flex-wrap align-items-center gap-2 border rounded-3 px-3 py-2 bg-body" wire:key="attached-{{ $group->id }}">
                <div class="d-flex flex-column">
                    <button type="button" class="btn btn-link btn-sm p-0 lh-1 text-body-secondary" wire:click="move({{ $group->id }}, -1)" @disabled($loop->first) title="Ask earlier"><i class="fa fa-chevron-up"></i></button>
                    <button type="button" class="btn btn-link btn-sm p-0 lh-1 text-body-secondary" wire:click="move({{ $group->id }}, 1)" @disabled($loop->last) title="Ask later"><i class="fa fa-chevron-down"></i></button>
                </div>
                <span class="badge rounded-pill text-bg-secondary">{{ $loop->iteration }}</span>
                <b>{{ $group->name }}</b>
                @php
                    $min = $group->pivot->min_select ?? $group->min_select;
                    $max = $group->pivot->max_select ?? $group->max_select;
                @endphp
                <span class="badge {{ $min > 0 ? 'bg-danger-subtle text-danger-emphasis' : 'bg-body-secondary text-body-secondary' }}">{{ \App\Models\OptionGroup::ruleLabel((int) $min, $max === null ? null : (int) $max) }}</span>
                <span class="small text-body-secondary text-truncate" style="max-width: 360px">{{ $group->items->map(fn ($item) => $item->name . ((float) $item->price ? ' +' . number_format((float) $item->price, 2) : ''))->implode(' · ') }}</span>
                <button type="button" class="btn btn-sm btn-link text-danger ms-auto" wire:click="detach({{ $group->id }})" title="Stop asking"><i class="fa fa-times"></i></button>
            </div>
        @empty
            <div class="border rounded-3 p-4 text-center text-body-secondary">
                No options. The till adds {{ $product?->name }} straight to the cart.
            </div>
        @endforelse
    </div>

    <div class="small fw-semibold text-uppercase text-body-secondary mb-2" style="letter-spacing: .06em">Tap a group to ask it</div>
    <div class="d-flex flex-wrap gap-2">
        @forelse ($available as $group)
            <button type="button" class="btn btn-outline-secondary text-start" wire:click="attach({{ $group->id }})" wire:key="available-{{ $group->id }}">
                <i class="fa fa-plus me-1 text-primary"></i><b>{{ $group->name }}</b>
                <small class="d-block text-body-secondary text-truncate" style="max-width: 240px">{{ $group->items->pluck('name')->implode(' · ') }}</small>
            </button>
        @empty
            <span class="text-body-secondary small">
                No other groups.
                @can('option group.create')
                    <a href="{{ route('product::option_group::index') }}">Make one</a>, e.g. Drink base, Flavour or Tea add-ons.
                @endcan
            </span>
        @endforelse
    </div>
</div>
